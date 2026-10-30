from __future__ import annotations

import re
import shutil
import sys
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "aurea--main" / "docs"
DESTINATION = Path(sys.argv[1]).resolve()
STATIC_EXTENSIONS = {
    ".css",
    ".html",
    ".jpeg",
    ".jpg",
    ".js",
    ".mp4",
    ".png",
    ".svg",
    ".webp",
    ".woff",
    ".woff2",
    ".ttf",
}


def prepare_html(source: Path, destination: Path) -> None:
    raw_content = source.read_bytes()
    if raw_content.startswith((b"\xff\xfe", b"\xfe\xff")):
        content = raw_content.decode("utf-16")
    else:
        try:
            content = raw_content.decode("utf-8-sig")
        except UnicodeDecodeError:
            content = raw_content.decode("cp1252")
    content = re.sub(
        r'<link\b[^>]*href=["\']guardar_comentario\.php["\'][^>]*>\s*',
        "",
        content,
        flags=re.IGNORECASE,
    )
    content = re.sub(
        r'<li\b[^>]*>\s*<a\b[^>]*href=["\'][^"\']*admin/index\.php["\'][^>]*>.*?'
        r"Administrador.*?</a>\s*</li>",
        "",
        content,
        flags=re.IGNORECASE | re.DOTALL,
    )
    content = re.sub(
        r'<!--\s*={3,}\s*CONTACTO\s*={3,}\s*-->\s*'
        r'<section\b[^>]*id=["\']contacto["\'][^>]*>.*?</section>',
        "",
        content,
        count=1,
        flags=re.IGNORECASE | re.DOTALL,
    )
    content = re.sub(
        r'<script>\s*\(function \(\) \{\s*const form = document\.getElementById\('
        r"'form-reserva'\).*?</script>",
        "",
        content,
        count=1,
        flags=re.IGNORECASE | re.DOTALL,
    )
    content = content.replace("index.php", "index.html")
    content = re.sub(
        r'<form\b[^>]*id=["\']form-reserva["\'][^>]*>.*?</form>',
        '<div class="paso-card"><p>Las reservas no están habilitadas en esta versión de demostración.</p>'
        '<a class="btn-reservar" href="https://wa.me/59177534284" target="_blank" '
        'rel="noopener noreferrer">Contactar por WhatsApp</a></div>',
        content,
        flags=re.IGNORECASE | re.DOTALL,
    )
    content = re.sub(
        r'<section\b[^>]*id=["\']reclamo["\'][^>]*>.*?</section>',
        '<section id="reclamo" class="reclamo-section"><div class="container"><div class="paso-card">'
        '<p>Para enviar un reclamo en esta versión de demostración, contáctanos por WhatsApp.</p>'
        '<a class="btn-reservar" href="https://wa.me/59177534284" target="_blank" '
        'rel="noopener noreferrer">Enviar reclamo por WhatsApp</a></div></div></section>',
        content,
        count=1,
        flags=re.IGNORECASE | re.DOTALL,
    )
    destination.parent.mkdir(parents=True, exist_ok=True)
    destination.write_text(content, encoding="utf-8")


if not SOURCE.is_dir():
    raise SystemExit(f"Static site source directory not found: {SOURCE}")

if DESTINATION.exists():
    shutil.rmtree(DESTINATION)
DESTINATION.mkdir(parents=True)

for source_file in SOURCE.rglob("*"):
    if not source_file.is_file():
        continue
    relative_path = source_file.relative_to(SOURCE)
    destination_file = DESTINATION / relative_path
    if source_file.name == "index.php":
        prepare_html(source_file, destination_file.with_name("index.html"))
    elif source_file.suffix.lower() == ".html":
        prepare_html(source_file, destination_file)
    elif source_file.suffix.lower() in STATIC_EXTENSIONS:
        destination_file.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source_file, destination_file)

if not (DESTINATION / "index.html").is_file():
    raise SystemExit("The GitHub Pages build did not produce index.html.")

(DESTINATION / ".nojekyll").touch()
