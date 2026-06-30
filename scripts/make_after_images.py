"""Produce optimized, display-sized WebP versions of the before images.

This mirrors the single biggest lever of an in-place WordPress speed job: take
the client's oversized JPEGs and emit images at the width they are actually
shown, in a modern format. The same photo, a fraction of the bytes, which is
what fixes LCP and total page weight.

Reads assets/before/*.jpg (the un-optimized originals) and writes
assets/after/*.webp.
"""

from __future__ import annotations

import pathlib

from PIL import Image

BASE_DIR = pathlib.Path(__file__).resolve().parent.parent
BEFORE_DIR = BASE_DIR / "assets" / "before"
AFTER_DIR = BASE_DIR / "assets" / "after"

# Width each image is actually displayed at, with some retina headroom.
# Mobile is the scored case: the hero spans the viewport, the gallery stacks to
# roughly one column, so these stay modest on purpose.
TARGET_WIDTHS: dict[str, int] = {
    "hero": 1100,
    "lawn": 760,
    "patio": 700,
    "flowers": 700,
    "trees": 700,
}
WEBP_QUALITY = 70


def resize_to_width(image: Image.Image, target_width: int) -> Image.Image:
    if image.width <= target_width:
        return image
    scale = target_width / image.width
    return image.resize((target_width, round(image.height * scale)), Image.LANCZOS)


def main() -> None:
    AFTER_DIR.mkdir(parents=True, exist_ok=True)
    for name, target_width in TARGET_WIDTHS.items():
        source_path = BEFORE_DIR / f"{name}.jpg"
        if not source_path.exists():
            raise SystemExit(f"Missing {source_path}. Run make_before_images.py first.")
        optimized = resize_to_width(Image.open(source_path).convert("RGB"), target_width)
        destination = AFTER_DIR / f"{name}.webp"
        optimized.save(destination, format="WEBP", quality=WEBP_QUALITY, method=6)
        before_kib = source_path.stat().st_size // 1024
        after_kib = destination.stat().st_size // 1024
        saved_pct = round((1 - after_kib / before_kib) * 100)
        print(
            f"{name:8} {before_kib:>5} KiB jpg -> {after_kib:>4} KiB webp "
            f"({target_width}px, -{saved_pct}%)"
        )


if __name__ == "__main__":
    main()
