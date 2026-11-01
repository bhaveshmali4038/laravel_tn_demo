"""Package reproducible source and deliverables without local secrets/runtime data."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parents[1]
destination = root / 'output/submission/laravel-tn-demo-submission.zip'
destination.parent.mkdir(parents=True, exist_ok=True)
required = [
    root / 'public/build/manifest.json',
    root / 'database/laravel_tn_demo.sql',
    root / 'output/submission/business-timing-walkthrough.mp4',
]
for item in required:
    if not item.is_file() or item.stat().st_size == 0:
        raise FileNotFoundError(f'Generate the required deliverable before packaging: {item}')
excluded = {'vendor', 'node_modules', 'tmp', 'output', '.git', '.agents', '.codex', '.aws', '.phpunit.cache'}
with ZipFile(destination, 'w', ZIP_DEFLATED) as archive:
    for item in root.rglob('*'):
        rel = item.relative_to(root)
        if any(part in excluded for part in rel.parts) or item.is_symlink() or not item.is_file():
            continue
        if item.name.startswith('.env') and item.name != '.env.example':
            continue
        if rel.parts[:2] == ('bootstrap', 'cache') and item.name != '.gitignore':
            continue
        if rel.parts[0] == 'storage' and rel.parts[:3] != ('storage', 'app', 'public') and item.name != '.gitignore':
            continue
        if rel.parts[:2] == ('public', 'storage') or item.name in {'.phpunit.result.cache', 'hot', '.DS_Store'}:
            continue
        archive.write(item, str(Path('laravel_tn_demo') / rel))
    archive.write(root / 'output/submission/business-timing-walkthrough.mp4', 'laravel_tn_demo/output/submission/business-timing-walkthrough.mp4')
print(destination)
