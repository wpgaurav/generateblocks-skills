#!/usr/bin/env bash
# Build standalone skill archives from the canonical source and generated dependencies.
set -euo pipefail
cd "$(dirname "$0")"
python3 - <<'PYBUILD'
from pathlib import Path
import shutil
import tempfile
import zipfile

root = Path.cwd()
names = ('generateblocks-layouts', 'html-to-generateblocks', 'elementor-to-generateblocks', 'figma-to-generateblocks')
out = root / 'importable'
out.mkdir(exist_ok=True)

def files(directory):
    return sorted(p for p in directory.rglob('*') if p.is_file()
                  and p.name not in {'.DS_Store', 'Thumbs.db'}
                  and '__pycache__' not in p.parts and p.suffix != '.pyc')

layout = root / 'skills/generateblocks-layouts'
for name in names:
    source = root / 'skills' / name
    if not source.is_dir():
        raise SystemExit(f'Missing skill: {source}')
    members = {str(Path(name) / p.relative_to(source)): p for p in files(source)}
    if name != 'generateblocks-layouts':
        # Generated snapshot: the authoring rules still have one maintained source.
        members.update({str(Path(name) / 'references/generateblocks-layouts' / p.relative_to(layout)): p
                        for p in files(layout)})
    target = out / f'{name}.zip'
    with tempfile.TemporaryDirectory(dir=out) as temp:
        staged = Path(temp) / target.name
        with zipfile.ZipFile(staged, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
            for arcname, path in sorted(members.items()):
                archive.write(path, arcname)
        with zipfile.ZipFile(staged) as archive:
            assert set(archive.namelist()) == set(members)
            assert all(archive.read(n) == p.read_bytes() for n, p in members.items())
        staged.replace(target)
    shutil.copyfile(target, out / f'{name}.skill')
    print(f'built {target.relative_to(root)} + .skill ({len(members)} source-verified files)')
PYBUILD
