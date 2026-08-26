#!/usr/bin/env python3
"""Focused tests for GenerateBlocks serialization and preflight tooling."""

from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

from gb_serialize import (
    build_css,
    make_unique_id,
    normalize_at_rule,
    ordered,
    serialize_attrs,
)


HERE = Path(__file__).resolve().parent
PREFLIGHT = HERE / 'preflight.py'


class SerializerTests(unittest.TestCase):
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
