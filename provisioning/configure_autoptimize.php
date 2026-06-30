<?php
/**
 * Configure Autoptimize for the "after" state: combine and minify CSS (the ten
 * render-blocking stylesheets become one) and minify HTML. JS is left untouched
 * on purpose, aggregating it tends to break Elementor's inline scripts.
 *
 * Run:
 *   docker compose run --rm wpcli wp eval-file /provisioning/configure_autoptimize.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = array(
	'autoptimize_css'             => 'on',
	'autoptimize_css_aggregate'   => 'on',
	'autoptimize_js'              => '',
	'autoptimize_html'            => 'on',
	'autoptimize_html_keepcomments' => '',
	'autoptimize_cache_nogzip'    => 'on',
	'autoptimize_optimize_logged' => 'on',
);

foreach ( $settings as $option_name => $option_value ) {
	update_option( $option_name, $option_value );
}

if ( class_exists( 'autoptimizeCache' ) ) {
	autoptimizeCache::clearall();
}

WP_CLI::success( 'Autoptimize configured: CSS aggregate + minify, HTML minify, JS untouched.' );
