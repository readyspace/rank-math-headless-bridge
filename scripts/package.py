#!/usr/bin/env python3
"""Build a lean WordPress.org ZIP from an explicit runtime-file allowlist."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
import re

root = Path(__file__).resolve().parents[1]
slug = 'headless-bridge-for-rank-math'
files = [root / name for name in (
    'rank-math-headless-bridge.php', 'uninstall.php', 'readme.txt', 'LICENSE',
    'CHANGELOG.md', 'CREDITS.md',
)]
files.extend(sorted(file for file in (root / 'includes').glob('*.php') if file.is_file()))
header = (root / 'rank-math-headless-bridge.php').read_text()
match = re.search(r'^ \* Version: ([0-9A-Za-z.+-]+)$', header, re.MULTILINE)
if not match:
    raise SystemExit('Missing plugin version header')
target = root / 'dist' / f'{slug}-{match.group(1)}.zip'
target.parent.mkdir(exist_ok=True)
with ZipFile(target, 'w', ZIP_DEFLATED) as archive:
    for file in files:
        if not file.is_file():
            raise SystemExit(f'Missing expected release file: {file.name}')
        relative = Path('credits.txt') if file.name == 'CREDITS.md' else file.relative_to(root)
        archive.write(file, Path(slug) / relative)
print(target)
