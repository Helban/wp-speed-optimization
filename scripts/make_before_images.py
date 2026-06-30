"""Download real landscaping photos as the "before" media for the case study.

Mimics a typical small-business client uploading full-resolution camera photos
straight into WordPress: large dimensions, no resizing for display, JPEG only.
Sizes are kept realistic (a few megabytes each, not absurd) so the before/after
stays credible. Each image is later served at its full size into a small slot,
which is the actual mistake we fix in the "after" pass.

Source: Pexels (free license, commercial use, no attribution required).
"""

from __future__ import annotations

import io
import pathlib

import httpx
from PIL import Image

# Pexels photo IDs mapped to their role on the page.
PEXELS_PHOTOS: dict[str, int] = {
    "hero": 130154,      # wide garden, used as the hero background
    "lawn": 9678161,     # green plants, the about-section side image
    "patio": 12029123,   # wooden benches, gallery
    "flowers": 38184019, # sunlit lush garden, gallery
    "trees": 33162373,   # park greenery, gallery
}

OUTPUT_DIR = pathlib.Path(__file__).resolve().parent.parent / "assets" / "before"
SOURCE_WIDTH = 2400
JPEG_QUALITY = 90
USER_AGENT = "Mozilla/5.0 (compatible; speed-demo-image-fetch/1.0)"


def fetch_source(photo_id: int) -> Image.Image:
    source_url = (
        f"https://images.pexels.com/photos/{photo_id}/pexels-photo-{photo_id}.jpeg"
        f"?cs=srgb&w={SOURCE_WIDTH}"
    )
    response = httpx.get(
        source_url,
        headers={"User-Agent": USER_AGENT},
        timeout=60,
        follow_redirects=True,
    )
    response.raise_for_status()
    return Image.open(io.BytesIO(response.content)).convert("RGB")


def main() -> None:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    for name, photo_id in PEXELS_PHOTOS.items():
        photo = fetch_source(photo_id)
        destination = OUTPUT_DIR / f"{name}.jpg"
        # Full-size JPEG, no width reduction for display: the un-optimized state.
        photo.save(destination, format="JPEG", quality=JPEG_QUALITY, optimize=False)
        size_kib = destination.stat().st_size // 1024
        print(f"{name:8} id={photo_id:<9} {photo.width}x{photo.height}  {size_kib} KiB")


if __name__ == "__main__":
    main()
