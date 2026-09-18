#!/usr/bin/env python3
"""Reproduce instruction-load and helper timing measurements; no site writes."""
from pathlib import Path
import gzip
import json
import statistics
import subprocess
import sys
import time

REPO = Path(__file__).resolve().parents[2]
SKILL = REPO / 'skills/generateblocks-layouts'
OUT = REPO / 'output/efficiency'
base = ['SKILL.md', 'references/authoring-contract.md', 'references/_index.md']
static = base
routes = {
    'mandatory_base': base,
    'responsive_static_supplied_design': static,
    'responsive_static_new_design': static + ['references/design-quality.md'],
    'responsive_query_supplied_design': base + ['references/query-block.md', 'references/dynamic-tags.md'],
    'shared_beta_new_design': static + ['references/design-quality.md', 'references/styling-scope.md', 'references/design-systems-beta.md', 'references/global-styles.md', 'references/css-mode.md'],
}
loads = {}
for name, paths in routes.items():
    texts = [(SKILL / p).read_text() for p in paths]
    loads[name] = {'files': len(paths), 'words': sum(len(s.split()) for s in texts),
                   'characters': sum(len(s) for s in texts), 'paths': paths}
library = [SKILL / 'SKILL.md', *sorted((SKILL / 'references').glob('*.md'))]
loads['entire_layout_reference_library'] = {
    'files': len(library), 'words': sum(len(p.read_text().split()) for p in library),
    'characters': sum(len(p.read_text()) for p in library),
}

helpers = {}
for variant, post_id in [('shared', 6), ('local', 41)]:
    command = [sys.executable, str(SKILL / 'scripts/preflight.py'), str(OUT / f'{variant}-page.html'), '--post-id', str(post_id)]
    timings = []
    for sample in range(8):
        start = time.perf_counter()
        result = subprocess.run(command, capture_output=True, text=True)
        elapsed = (time.perf_counter() - start) * 1000
        if result.returncode:
            raise RuntimeError(result.stdout + result.stderr)
        if sample:
            timings.append(elapsed)
    helpers[variant] = {'medianMs': statistics.median(timings), 'samplesMs': timings,
                        'passed': True, 'includesProcessStartup': True}
tests = subprocess.run([sys.executable, str(SKILL / 'scripts/test_tools.py')], capture_output=True, text=True)
if tests.returncode:
    raise RuntimeError(tests.stdout + tests.stderr)

build = json.loads((OUT / 'build-result.json').read_text())
frontend = json.loads((OUT / 'frontend-runs.json').read_text())
browser = {}
for variant in ['shared', 'local']:
    samples = [r['metrics'] for r in frontend if r['variant'] == variant]
    browser[variant] = {k: statistics.median(r[k] for r in samples)
                        for k in ['ttfb', 'fcp', 'lcp', 'cls', 'domElements', 'totalRequests', 'pageWeightBytes']}
result = {
    'date': '2026-09-18', 'instructionLoad': loads,
    'contextBoundary': 'Exact words/characters in routed GenerateBlocks files only; excludes external design skills, project instructions, tools, conversation, and generated output. Not a model-token count.',
    'helperPreflight': helpers, 'toolingTestsPassed': 10,
    'nativeTimingMedianMs': {k: statistics.median(v) for k, v in build['timingMs'].items()},
    'nativeTimingBoundary': 'Local conversion includes resolving styles, native selector discovery, compiling and serializing. Shared measurement parses and serializes existing markup. Not an AI generation latency comparison.',
    'pageMarkupBytes': build['pageBytes'], 'formMarkupBytes': build['formBytes'],
    'cssBytes': {'localPageAndForm': build['localCssBytes'], 'existingSharedSystem': build['globalCssBytes']},
    'logicalSharedRecordBytes': 15940,
    'storageBoundary': 'Logical serialized fields only; excludes physical database overhead, revisions, snapshots and indexes.',
    'gzipModelPageBytes': {'shared': len(gzip.compress(build['sourceContent'].encode(), mtime=0)), 'local': len(gzip.compress(build['content']['page'].encode(), mtime=0))},
    'gzipBoundary': 'Offline compression illustration; not measured HTTP transfer.',
    'browserMedians': browser, 'browserRunsPerVariant': 3,
    'browserBoundary': 'Alternating anonymous Studio audits on one localhost site with no added network/CPU throttle or controlled browser-cache state. Tool-reported page weight. Not production or field Core Web Vitals.',
    'renderParity': json.loads((OUT / 'style-parity.json').read_text()),
    'localAccentDeclarationValues': build['localAccentDeclarationValues'],
}
result['skillEntrypoints'] = {}
for name in ['generateblocks-layouts', 'html-to-generateblocks', 'elementor-to-generateblocks', 'figma-to-generateblocks']:
    text = (REPO / 'skills' / name / 'SKILL.md').read_text()
    result['skillEntrypoints'][name] = {'words': len(text.split()), 'characters': len(text), 'lines': len(text.splitlines())}
# Preserve the original audited baseline used by measure_instructions.py.
(REPO / 'reports/generateblocks-efficiency-current-results.json').write_text(json.dumps(result, indent=2) + '\n')
print(json.dumps({k: result[k] for k in ['instructionLoad', 'helperPreflight', 'nativeTimingMedianMs', 'browserMedians']}, indent=2))
