#!/usr/bin/env python3
"""Compare current routed guidance with the preserved pre-optimization baseline."""
from pathlib import Path
from datetime import date
import json

ROOT = Path(__file__).resolve().parents[2]
SKILL = ROOT / 'skills/generateblocks-layouts'
baseline = json.loads((ROOT / 'reports/generateblocks-efficiency-results.json').read_text())
base = ['SKILL.md', 'references/authoring-contract.md', 'references/_index.md']
routes = {
    'mandatory_base': base,
    'responsive_static_supplied_design': base,
    'responsive_static_new_design': base + ['references/design-quality.md'],
    'responsive_query_supplied_design': base + ['references/query-block.md', 'references/dynamic-tags.md'],
    'shared_beta_new_design': base + ['references/design-quality.md', 'references/styling-scope.md', 'references/global-styles.md', 'references/design-systems-beta.md', 'references/css-mode.md'],
}
target = ROOT / 'reports/instruction-efficiency-comparison.json'
result = json.loads(target.read_text()) if target.exists() else {}
result.update(measured=date.today().isoformat(), unit='words',
              scope='Routed GenerateBlocks guidance only; not model tokens or latency. Conditional external brand/design guidance is excluded.',
              routes={}, entrypoints={})
for name, paths in routes.items():
    after = sum(len((SKILL / p).read_text().split()) for p in paths)
    before = baseline['instructionLoad'][name]['words']
    result['routes'][name] = {'beforeWords': before, 'afterWords': after,
                             'reductionPercent': round((before - after) / before * 100, 1),
                             'afterFiles': paths}
for name, previous in baseline['skillEntrypoints'].items():
    text = (ROOT / 'skills' / name / 'SKILL.md').read_text()
    after = len(text.split())
    before = previous['words']
    result['entrypoints'][name] = {'beforeWords': before, 'afterWords': after,
                                  'reductionPercent': round((before - after) / before * 100, 1),
                                  'afterCharacters': len(text)}
target.write_text(json.dumps(result, indent=2) + '\n')
for name, entry in result['routes'].items():
    print(f"{name}: {entry['beforeWords']} -> {entry['afterWords']} words ({entry['reductionPercent']}% less)")
