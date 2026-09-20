#!/usr/bin/env python3
"""Focused tests for GenerateBlocks serialization and preflight tooling."""

from pathlib import Path
import json
import subprocess
import sys
import tempfile
import unittest

from gb_serialize import (
    build_css,
    make_layout_id,
    make_unique_id,
    normalize_at_rule,
    ordered,
    serialize_attrs,
)


HERE = Path(__file__).resolve().parent
PREFLIGHT = HERE / 'preflight.py'
ICON_FIXTURES = json.loads((HERE / 'fixtures/text-icons.json').read_text())


class SerializerTests(unittest.TestCase):
    def test_layout_scope_uses_real_post_or_four_digit_fallback(self):
        self.assertEqual(make_layout_id(1178400), 1178400)
        scope = make_layout_id()
        self.assertTrue(1000 <= scope <= 9999)
        self.assertEqual(make_unique_id('hero', scope, 1), f'hero-{scope}-1')
        self.assertEqual(make_unique_id('faq', scope, 2), f'faq-{scope}-2')
        occupied = set(range(1000, 10000)) - {5824}
        self.assertEqual(make_layout_id(used_ids=occupied), 5824)
        with self.assertRaises(ValueError):
            make_layout_id(used_ids=range(1000, 10000))
        for invalid in (0, -1, True, '42'):
            with self.subTest(post_id=invalid), self.assertRaises(ValueError):
                make_layout_id(invalid)

    def test_post_scoped_id(self):
        self.assertEqual(make_unique_id('hero', 42, 3, 'a'), 'hero-42-3a')
        with self.assertRaises(ValueError):
            make_unique_id('hero', 42, 0)
        with self.assertRaises(ValueError):
            make_unique_id('Hero', 42, 1)

    def test_wordpress_string_substitutions(self):
        raw = serialize_attrs({
            'uniqueId': 'hero-42-1',
            'styles': {'color': 'var(--ink)'},
            'htmlAttributes': {'href': 'https://example.com/?a=1&b=2'},
            'content': '<span title="x">A</span>',
        })
        self.assertIn(r'\u002d\u002dink', raw)
        self.assertIn(r'\u0026b=2', raw)
        self.assertIn(r'\u003cspan', raw)
        self.assertIn(r'\u0022x\u0022', raw)
        self.assertNotIn('var(--ink)', raw)

    def test_css_mode_selector_and_at_rule_compile(self):
        styles = {
            'display': 'grid',
            'gap': '2rem',
            'gridTemplateColumns': 'repeat(3, minmax(0, 1fr))',
            'transition': 'background-color .2s ease',
            '&:hover': {'backgroundColor': '#fff'},
            '@media (max-width: 767px)': {
                'gap': '1rem',
                'gridTemplateColumns': '1fr',
                '&:focus-visible': {'outline': '2px solid currentColor'},
            },
        }
        css = build_css('.gb-element-grid-42-1', styles)
        self.assertEqual(
            css,
            '.gb-element-grid-42-1{display:grid;gap:2rem;grid-template-columns:repeat(3,minmax(0,1fr));transition:background-color .2s ease}'
            '.gb-element-grid-42-1:hover{background-color:#fff}'
            '@media (max-width:767px){.gb-element-grid-42-1{gap:1rem;grid-template-columns:1fr}.gb-element-grid-42-1:focus-visible{outline:2px solid currentColor}}',
        )

    def test_literal_backslashes_survive_wordpress_71_encoding(self):
        attrs = {'css': r'.icon::before{content:"\e9d9"}', 'path': r'C:\kit\file'}
        encoded = serialize_attrs(attrs)
        self.assertEqual(json.loads(encoded), attrs)
        self.assertIn(r'\u005c', encoded)
        self.assertNotIn(chr(92) * 2, encoded)

    def test_at_rule_inside_selector(self):
        css = build_css('.gb-element-card-42-1', {
            '&:hover': {
                'color': 'red',
                '@media (max-width:767px)': {'color': 'inherit'},
            },
        })
        self.assertEqual(
            css,
            '.gb-element-card-42-1:hover{color:red}'
            '@media (max-width:767px){.gb-element-card-42-1:hover{color:inherit}}',
        )

    def test_rejects_unsupported_depth_and_at_rule(self):
        with self.assertRaises(ValueError):
            normalize_at_rule('@keyframes pulse')
        with self.assertRaises(ValueError):
            build_css('.gb-element-x', {'&:hover': {'> span': {'color': 'red'}}})


class PreflightTests(unittest.TestCase):
    def block_markup(self, border='1px solid #ddd'):
        uid = make_unique_id('card', 42, 1)
        styles = {
            'border': border,
            'borderRadius': '.75rem',
            'transition': 'border-color .2s ease',
            '&:hover': {'borderColor': '#111'},
            '@media (max-width:767px)': {'borderRadius': '.5rem'},
        }
        attrs = ordered('element', {
            'uniqueId': uid,
            'tagName': 'article',
            'styles': styles,
            'css': build_css(f'.gb-element-{uid}', styles),
            'className': 'gb-element',
        })
        return (
            '<!-- wp:generateblocks/element ' + serialize_attrs(attrs) + ' -->\n'
            f'<article class="gb-element-{uid} gb-element"></article>\n'
            '<!-- /wp:generateblocks/element -->\n'
        )

    def run_preflight(self, markup, *extra):
        with tempfile.TemporaryDirectory() as tmp:
            target = Path(tmp) / 'section.html'
            target.write_text(markup, encoding='utf-8')
            return subprocess.run(
                [sys.executable, str(PREFLIGHT), str(target), '--post-id', '42', *extra],
                text=True,
                capture_output=True,
                check=False,
            )

    def test_valid_css_mode_markup_passes(self):
        result = self.run_preflight(self.block_markup())
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)

    def test_native_text_icon_fixtures_pass(self):
        for case in ICON_FIXTURES['cases']:
            with self.subTest(case=case['name']):
                result = self.run_preflight(case['serialized'], '--id-scope', '4821')
                self.assertEqual(result.returncode, 0, result.stdout + result.stderr)

    def test_broken_published_icon_example_is_rejected(self):
        result = self.run_preflight(ICON_FIXTURES['brokenExample'], '--id-scope', '4821')
        self.assertEqual(result.returncode, 1, result.stdout + result.stderr)
        self.assertIn('icon label requires a direct span.gb-text wrapper', result.stdout)
        self.assertIn('outer gb-text class', result.stdout)

    def test_missing_icon_label_wrapper_is_rejected(self):
        source = ICON_FIXTURES['cases'][0]['serialized']
        broken = source.replace('<span class="gb-text">Fast delivery</span>', 'Fast delivery')
        self.assertNotEqual(source, broken)
        result = self.run_preflight(broken, '--id-scope', '4821')
        self.assertEqual(result.returncode, 1, result.stdout + result.stderr)
        self.assertIn('icon label requires a direct span.gb-text wrapper', result.stdout)

    def test_incorrect_icon_order_is_rejected(self):
        source = ICON_FIXTURES['cases'][1]['serialized']
        broken = source.replace('"iconLocation":"after"', '"iconLocation":"before"')
        self.assertNotEqual(source, broken)
        result = self.run_preflight(broken, '--id-scope', '4821')
        self.assertEqual(result.returncode, 1, result.stdout + result.stderr)
        self.assertIn('saved icon order does not match iconLocation', result.stdout)

    def test_icon_only_with_saved_label_is_rejected(self):
        source = ICON_FIXTURES['cases'][0]['serialized']
        end = source.index(' -->')
        start = source.index('{')
        attrs = json.loads(source[start:end])
        attrs['iconOnly'] = True
        broken = source[:start] + serialize_attrs(ordered('text', attrs)) + source[end:]
        result = self.run_preflight(broken, '--id-scope', '4821')
        self.assertEqual(result.returncode, 1, result.stdout + result.stderr)
        self.assertIn('iconOnly must not render label content', result.stdout)

    def test_icon_in_json_cannot_replace_html_source(self):
        source = ICON_FIXTURES['cases'][-1]['serialized']
        end = source.index(' -->')
        start = source.index('{')
        attrs = json.loads(source[start:end])
        attrs['icon'] = '<svg viewBox="0 0 24 24"></svg>'
        broken = source[:start] + serialize_attrs(ordered('text', attrs)) + source[end:]
        result = self.run_preflight(broken, '--id-scope', '4821')
        self.assertEqual(result.returncode, 1, result.stdout + result.stderr)
        self.assertIn('icon must be present in the saved HTML', result.stdout)

    def test_standalone_shape_and_legacy_headline_are_not_text_icons(self):
        from preflight import text_icon_issues
        source = '<!-- wp:generateblocks/shape {"uniqueId":"icon-4821-9"} --><span class="gb-shape"><svg></svg></span><!-- /wp:generateblocks/shape -->'
        source += '<!-- wp:generateblocks/headline {"uniqueId":"old"} --><h2 class="gb-headline"><span class="gb-icon">Icon</span>Label</h2><!-- /wp:generateblocks/headline -->'
        self.assertEqual(text_icon_issues(source), [])

    def test_preflight_accepts_fallback_layout_scope(self):
        scope = make_layout_id()
        markup = self.block_markup().replace('card-42-1', f'card-{scope}-1')
        result = self.run_preflight(markup, '--id-scope', str(scope))
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)

    def test_wrong_css_owner_is_caught_even_when_markup_is_valid(self):
        markup = self.block_markup()
        end = markup.index(' -->')
        # Reproduce an Element answer compiled with a Text selector. The saved
        # HTML class is still correct, so native block validation can pass.
        markup = markup[:end].replace('.gb-element-card-42-1', '.gb-text-card-42-1') + markup[end:]
        result = self.run_preflight(markup)
        self.assertEqual(result.returncode, 1, result.stdout + result.stderr)
        self.assertIn('compiled CSS block-type prefix mismatch', result.stdout)

    def test_css_can_target_a_child_with_another_block_type(self):
        attrs = ordered('element', {
            'uniqueId': 'card-42-1', 'tagName': 'div',
            'styles': {'& .gb-text-label-42-2': {'color': 'red'}},
            'css': '.gb-element-card-42-1 .gb-text-label-42-2{color:red}',
            'className': 'gb-element',
        })
        markup = '<!-- wp:generateblocks/element ' + serialize_attrs(attrs) + ' -->\n<div class="gb-element-card-42-1 gb-element"></div>\n<!-- /wp:generateblocks/element -->'
        result = self.run_preflight(markup)
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)

    def test_color_mix_percentage_is_not_a_border_width(self):
        result = self.run_preflight(self.block_markup('1px solid color-mix(in oklch, var(--ink) 88%, transparent)'))
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)

    def test_empty_attributes_are_caught_before_server_save(self):
        markup = self.block_markup().replace(',"className":', ',"htmlAttributes":{},"className":')
        result = self.run_preflight(markup)
        self.assertEqual(result.returncode, 1, result.stdout + result.stderr)
        self.assertIn('explicit empty htmlAttributes', result.stdout)

    def test_unused_styles_omitted_and_wrong_shapes_rejected(self):
        for value, expected in [(None, 0), ({}, 1), ([], 1)]:
            with self.subTest(styles=value):
                attrs = {'uniqueId': 'card-42-1', 'tagName': 'div', 'className': 'sample'}
                if value is not None:
                    attrs['styles'] = value
                markup = '<!-- wp:generateblocks/element ' + serialize_attrs(ordered('element', attrs)) + ' -->\n<div class="sample"></div>\n<!-- /wp:generateblocks/element -->'
                result = self.run_preflight(markup)
                self.assertEqual(result.returncode, expected, result.stdout + result.stderr)
                if value is not None:
                    self.assertIn('styles', result.stdout)

    def test_thick_rounded_surface_fails_without_exception(self):
        result = self.run_preflight(self.block_markup('2px solid #111'))
        self.assertEqual(result.returncode, 1, result.stdout + result.stderr)
        self.assertIn('rounded surfaces with border >=2px', result.stdout)

        allowed = self.run_preflight(
            self.block_markup('2px solid #111'), '--allow-thick-rounded'
        )
        self.assertEqual(allowed.returncode, 0, allowed.stdout + allowed.stderr)


if __name__ == '__main__':
    unittest.main()
