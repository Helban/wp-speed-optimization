# WordPress speed optimization case study

One landscaping homepage, built three ways, measured with Lighthouse on the mobile
profile. A typical bloated Elementor site, the same site optimized in place, and a
lean hand-coded rebuild of the same page.

| State | Performance | Accessibility | Best Practices | SEO | LCP | Page weight |
|-------|------------:|--------------:|---------------:|----:|----:|------------:|
| Before (bloated Elementor) | 63 | 96 | 100 | 85 | 26.5 s | 5,111 KiB |
| In place (Elementor kept) | 71 | 96 | 100 | 85 | 5.9 s | 1,183 KiB |
| Lean rebuild (hand-coded) | 99 | 100 | 100 | 100 | 2.0 s | 461 KiB |

Page weight drops 77% in place and 91% with the rebuild. All three render the same
full-width landing; only the build and the speed differ. Full write-up in
[`RESULTS.md`](RESULTS.md); raw Lighthouse reports in [`rebuild/reports/`](rebuild/reports/).

The brand (Greenfield Landscaping) is fictional. Photos are from Pexels (free license).

## What is in here

```
docker-compose.yml     WordPress 7 + MariaDB + an on-demand wp-cli runner
.env.example           copy to .env (local-only credentials)
setup.sh               build the bloated "before" WordPress state
optimize.sh            apply the in-place optimization (the "after")
provisioning/          PHP run via wp-cli: build the page, import images
mu-plugins/            the in-place performance fixes, gated by an option flag
scripts/               image tooling (Python: Pillow + httpx)
rebuild/               the deployable static site
  index.html           the case study page
  demo/                the lean hand-coded landing (the live demo)
  reports/             the three Lighthouse reports
```

## Reproduce the WordPress states

Needs Docker and Python 3.

```bash
cp .env.example .env
./setup.sh        # bloated "before" at http://localhost:8080
./optimize.sh     # switch the same page to the optimized "after"
```

`setup.sh` installs OceanWP + Elementor and a stack of plugins, generates the demo
images, and builds the page. `optimize.sh` converts the images to WebP, drops the
unused plugins and the Google Fonts, and turns on `mu-plugins/speed-optimization.php`
(lazy-loading, hero preload, asset cleanup). The mu-plugin is gated by the
`speeddemo_optimized` option, so re-running `setup.sh` returns to the slow state.

## Run the lean rebuild

```bash
python3 -m http.server 8090 --directory rebuild
# case study at http://localhost:8090/ , live demo at http://localhost:8090/demo/
```

The rebuild is plain HTML with the CSS inlined, AVIF and WebP images, and a preloaded
hero. Regenerate its images with `scripts/make_rebuild_images.py`.

## How each state was measured

```bash
npx lighthouse http://localhost:8080/ --preset=mobile --output=html
```

Lighthouse mobile uses simulated throttling (a slow 4G phone), which is what Google
PageSpeed and real mobile visitors see. The lean rebuild is also deployable, so its
score is reproducible in any public PageSpeed test.

## The honest finding

In place, the ceiling is the page builder's render-blocking CSS. Free tooling and
hand-rolled critical CSS get the page into the low-to-mid 70s, no further (critical CSS
inlined into the document hurt as much as it helped). Reaching the 90s in place needs a
premium optimizer that automates critical CSS and JS delay. The other route is the lean
rebuild, which removes the page-builder CSS entirely and reaches 99.
