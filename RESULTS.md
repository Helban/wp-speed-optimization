# Results — Greenfield Landscaping speed case study

The same full-width landing page in three states, all measured with Lighthouse on
the mobile preset (simulated throttling), local builds. Reports in `exports/`.

| State | Perf | A11y | Best Practices | SEO | LCP | Page weight |
|-------|-----:|-----:|---------------:|----:|----:|------------:|
| **Before** — bloated Elementor build | 63 | 96 | 100 | 85 | 26.5 s | 5,111 KiB |
| **In place** — Elementor kept | 71 | 96 | 100 | 85 | 5.9 s | 1,183 KiB |
| **Lean rebuild** — hand-coded, no builder | 99 | 100 | 100 | 100 | 2.0 s | 461 KiB |

Page weight drops 77% in place and 91% with the rebuild. All three render the same
full-width landing (same hero, about, gallery, contact form); only the build and the
speed differ. Desktop scored higher even before; mobile is where the problem lives.

## Before — what was wrong

- Hero and gallery photos served as full-size 2400px JPEGs (~5 MB) into small slots.
- The hero is a CSS background, discovered late, so LCP waited behind everything.
- OceanWP + Elementor + Happy Addons + Contact Form 7 + Font Awesome + AddToAny each
  enqueue CSS/JS site-wide; three of those are not used on this page.
- Render-blocking Google Fonts (Roboto + Roboto Slab, every weight), no preload.

## In place — Elementor kept (`mu-plugins/speed-optimization.php`)

- Images to display-sized WebP (`scripts/make_after_images.py`): ~5 MB → ~0.8 MB.
- Deactivated the three unused plugins; removed the Google Fonts; system font fallback.
- Lazy-loaded below-the-fold images; preloaded the hero (the LCP element).

The remaining ceiling is the page builder's render-blocking CSS. Hand-rolling critical
CSS made it worse (HTML bloat plus layout shift), so the honest in-place ceiling with
free tooling is the low-to-mid 70s. Clearing it needs a premium optimizer (automatic
critical CSS and JS delay).

## Lean rebuild — hand-coded (`rebuild/demo/`)

Same content and layout, no WordPress, no page builder. One HTML file with CSS inlined
(zero render-blocking), system fonts, AVIF with WebP fallback (`scripts/make_rebuild_images.py`),
responsive hero with preload, lazy below-fold images with width and height set (CLS 0).

## The pitch

- "Speed up my WordPress" (keep your editor): the in-place column. Real, large wins
  without touching how you work, and honest about the page-builder ceiling.
- "Rebuild it properly": the lean column. The page builder's render-blocking CSS is
  the cost; a lean build removes it and reaches 99/100.
