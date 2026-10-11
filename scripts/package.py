#!/usr/bin/env python3
"""Build an installable WordPress ZIP from an explicit public-file allowlist."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
import re

root = Path(__file__).resolve().parents[1]
files = [root / name for name in (
    'rank-math-headless-bridge.php', 'README.md', 'readme.txt', 'LICENSE', 'CHANGELOG.md',
    'CREDITS.md', 'CONTRIBUTING.md', 'SECURITY.md',
)]
for directory in ('includes', 'docs', 'examples'):
    files.extend(sorted(file for file in (root / directory).rglob('*') if file.is_file()))
header = (root / 'rank-math-headless-bridge.php').read_text()
match = re.search(r'^ \* Version: ([0-9A-Za-z.+-]+)$', header, re.MULTILINE)
if not match:
    raise SystemExit('Missing plugin version header')
target = root / 'dist' / f'rank-math-headless-bridge-{match.group(1)}.zip'
target.parent.mkdir(exist_ok=True)
with ZipFile(target, 'w', ZIP_DEFLATED) as archive:
    for file in files:
        if not file.is_file():
            raise SystemExit(f'Missing expected release file: {file.name}')
        archive.write(file, Path('rank-math-headless-bridge') / file.relative_to(root))
print(target)
