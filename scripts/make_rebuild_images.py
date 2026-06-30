"""Generate AVIF + WebP images for the lean rebuild, at display sizes.

The hand-coded rebuild controls exactly how each image is shown, so it ships
modern formats (AVIF first, WebP fallback) at the right widths. AVIF roughly
halves the hero bytes versus WebP, which is what pulls LCP down.

Reads assets/before/*.jpg and writes rebuild/img/{role}-{width}.{avif,webp}.
"""

from __future__ import annotations

import pathlib

from PIL import Image

BASE_DIR = pathlib.Path(__file__).resolve().parent.parent
BEFORE_DIR = BASE_DIR / "assets" / "before"
OUTPUT_DIR = BASE_DIR / "rebuild" / "demo" / "img"

# Role -> widths to emit. The hero gets two widths for responsive srcset.
WIDTHS: dict[str, list[int]] = {
    "hero": [800, 1100],
    "lawn": [760],
    "patio": [700],
    "flowers": [700],
    "trees": [700],
}
WEBP_QUALITY = 70
AVIF_QUALITY = 55


def resize_to_width(image: Image.Image, target_width: int) -> Image.Image:
    if image.width <= target_width:
        return image
    scale = target_width / image.width
    return image.resize((target_width, round(image.height * scale)), Image.LANCZOS)


def main() -> None:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    for role, widths in WIDTHS.items():
        source = Image.open(BEFORE_DIR / f"{role}.jpg").convert("RGB")
        for width in widths:
            sized = resize_to_width(source, width)
            webp_path = OUTPUT_DIR / f"{role}-{width}.webp"
            avif_path = OUTPUT_DIR / f"{role}-{width}.avif"
            sized.save(webp_path, format="WEBP", quality=WEBP_QUALITY, method=6)
            sized.save(avif_path, format="AVIF", quality=AVIF_QUALITY)
            print(
                f"{role}-{width}: avif {avif_path.stat().st_size // 1024} KiB, "
                f"webp {webp_path.stat().st_size // 1024} KiB"
            )


if __name__ == "__main__":
    main()
