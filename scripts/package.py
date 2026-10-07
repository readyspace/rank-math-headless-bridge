#!/usr/bin/env python3
"""Build an installable WordPress ZIP from an explicit public-file allowlist."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parents[1]
files = [root / name for name in (
    'rank-math-headless-bridge.php', 'README.md', 'readme.txt', 'LICENSE',
)]
for directory in ('includes', 'docs'):
    files.extend(sorted((root / directory).glob('*')))
target = root / 'dist' / 'rank-math-headless-bridge-0.1.0.zip'
target.parent.mkdir(exist_ok=True)
with ZipFile(target, 'w', ZIP_DEFLATED) as archive:
    for file in files:
        if not file.is_file():
            raise SystemExit(f'Missing expected release file: {file.name}')
        archive.write(file, Path('rank-math-headless-bridge') / file.relative_to(root))
print(target)
