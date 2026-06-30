<?php
/**
 * Plugin Name: Speed Optimization (case study)
 * Description: In-place front-end performance cleanup for the Greenfield demo.
 *              Active only after build_home.php sets the speeddemo_optimized
 *              flag, so the "before" state stays untouched and slow.
 *
 * Drop-in (mu-plugin) so it loads on every request without needing activation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function () {
		if ( ! get_option( 'speeddemo_optimized' ) ) {
			return;
		}

		// 1) Remove the always-on emoji detection script and styles.
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );

		// 2) Strip the render-blocking Google Fonts. OceanWP requests every weight
		//    of Roboto and Roboto Slab from an external domain; drop the <link>
		//    tags and fall back to the system font stack.
		add_filter(
			'style_loader_tag',
			static function ( $tag, $handle, $href ) {
				if ( false !== strpos( $href, 'fonts.googleapis.com' ) ) {
					return '';
				}
				return $tag;
			},
			10,
			3
		);

		// 3) Force every content image to lazy-load and drop any fetchpriority.
		//    WordPress eagerly loads the first images (and gave the about image
		//    fetchpriority=high), so ~800 KB of below-the-fold photos competed
		//    with the hero on the throttled connection. The hero is a CSS
		//    background, so it is unaffected here and keeps its own preload.
		add_filter(
			'wp_get_attachment_image_attributes',
			static function ( $attributes ) {
				$attributes['loading'] = 'lazy';
				unset( $attributes['fetchpriority'] );
				return $attributes;
			},
			20
		);

		// 4) Preload the hero image (the LCP element) so the browser fetches it
		//    immediately instead of waiting to parse the section background CSS.
		add_action(
			'wp_head',
			static function () {
				$hero = get_posts(
					array(
						'post_type'   => 'attachment',
						'name'        => 'hero-webp',
						'numberposts' => 1,
						'post_status' => 'inherit',
					)
				);
				if ( ! $hero ) {
					return;
				}
				$hero_url = wp_get_attachment_url( $hero[0]->ID );
				if ( $hero_url ) {
					printf(
						'<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n",
						esc_url( $hero_url )
					);
				}
			},
			1
		);
	},
	5
);
