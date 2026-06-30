<?php
/**
 * Build the Greenfield Landscaping homepage in Elementor, in one of two states:
 *
 *   before : full-size JPEG photos, the un-optimized baseline.
 *   after  : the optimized WebP photos, same layout (in-place optimization).
 *
 * The layout is identical; only which media set is referenced changes, plus the
 * `speeddemo_optimized` flag the mu-plugin reads. Re-running deletes the prior
 * "home" page first, so it stays repeatable.
 *
 * Run:
 *   docker compose run --rm wpcli wp eval-file /provisioning/build_home.php
 *   docker compose run --rm wpcli wp --exec="define('SPEED_VARIANT','after');" eval-file /provisioning/build_home.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$variant     = defined( 'SPEED_VARIANT' ) ? SPEED_VARIANT : ( getenv( 'SPEED_VARIANT' ) ?: 'before' );
$slug_suffix = ( 'after' === $variant ) ? '-webp' : '';

$attachment_by_slug = static function ( $slug ) {
	$found = get_posts(
		array(
			'post_type'   => 'attachment',
			'name'        => $slug,
			'numberposts' => 1,
			'post_status' => 'inherit',
		)
	);
	return $found ? (int) $found[0]->ID : 0;
};

$media = array();
foreach ( array( 'hero', 'lawn', 'patio', 'flowers', 'trees' ) as $role ) {
	$media[ $role ] = $attachment_by_slug( $role . $slug_suffix );
	if ( ! $media[ $role ] ) {
		WP_CLI::error( "Missing attachment for '{$role}{$slug_suffix}'. Import the {$variant} images first." );
	}
}

$attachment_url = static function ( $attachment_id ) {
	return wp_get_attachment_url( $attachment_id );
};

// Elementor needs a unique element id per node; any unique hex string works.
$element_id = static function () {
	return substr( md5( uniqid( '', true ) ), 0, 7 );
};

// Default Contact Form 7 form, created on plugin activation.
$cf7_form_id = 0;
$cf7_forms   = get_posts(
	array(
		'post_type'   => 'wpcf7_contact_form',
		'numberposts' => 1,
		'orderby'     => 'ID',
		'order'       => 'ASC',
	)
);
if ( ! empty( $cf7_forms ) ) {
	$cf7_form_id = $cf7_forms[0]->ID;
}

$elementor_data = array();

// 1) Hero: full-bleed section with the photo as a CSS background image.
$elementor_data[] = array(
	'id'       => $element_id(),
	'elType'   => 'section',
	'settings' => array(
		'layout'               => 'full_width',
		'background_background' => 'classic',
		'background_image'      => array( 'id' => $media['hero'], 'url' => $attachment_url( $media['hero'] ) ),
		'background_size'       => 'cover',
		'background_position'   => 'center center',
		'min_height'            => array( 'unit' => 'vh', 'size' => 78 ),
		'content_position'      => 'middle',
		'padding'               => array( 'unit' => 'px', 'top' => '90', 'right' => '20', 'bottom' => '90', 'left' => '20', 'isLinked' => false ),
	),
	'elements' => array(
		array(
			'id'       => $element_id(),
			'elType'   => 'column',
			'settings' => array( '_column_size' => 100, 'align' => 'center' ),
			'elements' => array(
				array(
					'id'         => $element_id(),
					'elType'     => 'widget',
					'widgetType' => 'heading',
					'settings'   => array(
						'title'                  => 'Greenfield Landscaping',
						'align'                  => 'center',
						'title_color'            => '#ffffff',
						'typography_typography'  => 'custom',
						'typography_font_size'   => array( 'unit' => 'px', 'size' => 56 ),
						'typography_font_weight' => '700',
					),
					'elements'   => array(),
				),
				array(
					'id'         => $element_id(),
					'elType'     => 'widget',
					'widgetType' => 'heading',
					'settings'   => array(
						'title'                  => 'Garden design, lawn care and patios across the Tri-Valley',
						'header_size'            => 'h2',
						'align'                  => 'center',
						'title_color'            => '#f2f2f2',
						'typography_typography'  => 'custom',
						'typography_font_size'   => array( 'unit' => 'px', 'size' => 22 ),
						'typography_font_weight' => '400',
					),
					'elements'   => array(),
				),
				array(
					'id'         => $element_id(),
					'elType'     => 'widget',
					'widgetType' => 'button',
					'settings'   => array( 'text' => 'Get a free quote', 'align' => 'center', 'size' => 'lg' ),
					'elements'   => array(),
				),
			),
		),
	),
);

// 2) About: text on the left, a photo on the right.
$elementor_data[] = array(
	'id'       => $element_id(),
	'elType'   => 'section',
	'settings' => array( 'padding' => array( 'unit' => 'px', 'top' => '70', 'right' => '0', 'bottom' => '70', 'left' => '0', 'isLinked' => false ) ),
	'elements' => array(
		array(
			'id'       => $element_id(),
			'elType'   => 'column',
			'settings' => array( '_column_size' => 50 ),
			'elements' => array(
				array(
					'id'         => $element_id(),
					'elType'     => 'widget',
					'widgetType' => 'heading',
					'settings'   => array( 'title' => 'Your garden, properly looked after', 'header_size' => 'h2' ),
					'elements'   => array(),
				),
				array(
					'id'         => $element_id(),
					'elType'     => 'widget',
					'widgetType' => 'text-editor',
					'settings'   => array(
						'editor' => '<p>Greenfield Landscaping has shaped gardens, lawns and outdoor living spaces for homeowners and small businesses since 2009. From a single flower bed to a full backyard rebuild, we plan it, plant it and maintain it. Our crews show up on time, clean up after themselves, and stand behind the work.</p><p>Design, planting, irrigation, lawn care, patios and seasonal maintenance, all from one local team.</p>',
					),
					'elements'   => array(),
				),
			),
		),
		array(
			'id'       => $element_id(),
			'elType'   => 'column',
			'settings' => array( '_column_size' => 50 ),
			'elements' => array(
				array(
					'id'         => $element_id(),
					'elType'     => 'widget',
					'widgetType' => 'image',
					'settings'   => array(
						'image'      => array( 'id' => $media['lawn'], 'url' => $attachment_url( $media['lawn'] ) ),
						'image_size' => 'full',
					),
					'elements'   => array(),
				),
			),
		),
	),
);

// 3a) Gallery heading row.
$elementor_data[] = array(
	'id'       => $element_id(),
	'elType'   => 'section',
	'settings' => array( 'padding' => array( 'unit' => 'px', 'top' => '60', 'right' => '0', 'bottom' => '10', 'left' => '0', 'isLinked' => false ) ),
	'elements' => array(
		array(
			'id'       => $element_id(),
			'elType'   => 'column',
			'settings' => array( '_column_size' => 100 ),
			'elements' => array(
				array(
					'id'         => $element_id(),
					'elType'     => 'widget',
					'widgetType' => 'heading',
					'settings'   => array( 'title' => 'What we do', 'align' => 'center', 'header_size' => 'h2' ),
					'elements'   => array(),
				),
			),
		),
	),
);

// 3b) Gallery: three photos side by side.
$gallery_items   = array(
	array( 'id' => $media['patio'], 'caption' => 'Patios & paving' ),
	array( 'id' => $media['flowers'], 'caption' => 'Planting & beds' ),
	array( 'id' => $media['trees'], 'caption' => 'Trees & hedges' ),
);
$gallery_columns = array();
foreach ( $gallery_items as $gallery_item ) {
	$gallery_columns[] = array(
		'id'       => $element_id(),
		'elType'   => 'column',
		'settings' => array( '_column_size' => 33 ),
		'elements' => array(
			array(
				'id'         => $element_id(),
				'elType'     => 'widget',
				'widgetType' => 'image',
				'settings'   => array(
					'image'      => array( 'id' => $gallery_item['id'], 'url' => $attachment_url( $gallery_item['id'] ) ),
					'image_size' => 'full',
				),
				'elements'   => array(),
			),
			array(
				'id'         => $element_id(),
				'elType'     => 'widget',
				'widgetType' => 'heading',
				'settings'   => array( 'title' => $gallery_item['caption'], 'header_size' => 'h3', 'align' => 'center' ),
				'elements'   => array(),
			),
		),
	);
}
$elementor_data[] = array(
	'id'       => $element_id(),
	'elType'   => 'section',
	'settings' => array( 'padding' => array( 'unit' => 'px', 'top' => '10', 'right' => '0', 'bottom' => '70', 'left' => '0', 'isLinked' => false ) ),
	'elements' => $gallery_columns,
);

// 4) Contact: heading plus the Contact Form 7 shortcode.
$contact_widgets = array(
	array(
		'id'         => $element_id(),
		'elType'     => 'widget',
		'widgetType' => 'heading',
		'settings'   => array( 'title' => 'Get a free quote', 'align' => 'center', 'header_size' => 'h2' ),
		'elements'   => array(),
	),
);
if ( $cf7_form_id ) {
	$contact_widgets[] = array(
		'id'         => $element_id(),
		'elType'     => 'widget',
		'widgetType' => 'shortcode',
		'settings'   => array( 'shortcode' => '[contact-form-7 id="' . $cf7_form_id . '"]' ),
		'elements'   => array(),
	);
}
$elementor_data[] = array(
	'id'       => $element_id(),
	'elType'   => 'section',
	'settings' => array(
		'background_background' => 'classic',
		'background_color'      => '#f4f6f2',
		'padding'               => array( 'unit' => 'px', 'top' => '70', 'right' => '0', 'bottom' => '70', 'left' => '0', 'isLinked' => false ),
	),
	'elements' => array(
		array(
			'id'       => $element_id(),
			'elType'   => 'column',
			'settings' => array( '_column_size' => 100 ),
			'elements' => $contact_widgets,
		),
	),
);

// Replace any prior home page, then create a fresh one.
$existing_home = get_page_by_path( 'home' );
if ( $existing_home ) {
	wp_delete_post( $existing_home->ID, true );
}

$home_page_id = wp_insert_post(
	array(
		'post_title'   => 'Home',
		'post_name'    => 'home',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '',
	)
);

if ( is_wp_error( $home_page_id ) || ! $home_page_id ) {
	WP_CLI::error( 'Failed to create the home page.' );
}

update_post_meta( $home_page_id, '_elementor_edit_mode', 'builder' );
update_post_meta( $home_page_id, '_elementor_template_type', 'wp-page' );
update_post_meta( $home_page_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
update_post_meta( $home_page_id, '_elementor_data', wp_slash( wp_json_encode( $elementor_data ) ) );

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_page_id );
update_option( 'speeddemo_optimized', ( 'after' === $variant ) ? 1 : 0 );

// Full-width landing layout: no sidebar, no page-title bar, so the WordPress
// build matches the lean rebuild for a fair side-by-side comparison.
update_post_meta( $home_page_id, 'ocean_post_layout', 'full-width' );
update_post_meta( $home_page_id, '_wp_page_template', 'elementor_header_footer' );

// OceanWP renders a page-title bar by default ("Home" strip); hide it site-wide.
set_theme_mod( 'ocean_page_title_display', false );

// Primary navigation in the theme header, mirroring the rebuild's nav.
$primary_menu      = wp_get_nav_menu_object( 'Primary' );
$primary_menu_term = $primary_menu ? (int) $primary_menu->term_id : (int) wp_create_nav_menu( 'Primary' );
foreach ( (array) wp_get_nav_menu_items( $primary_menu_term ) as $existing_nav_item ) {
	wp_delete_post( $existing_nav_item->ID, true );
}
$site_home = home_url( '/' );
foreach ( array(
	'Home'     => $site_home,
	'About'    => $site_home . '#about',
	'Services' => $site_home . '#services',
	'Contact'  => $site_home . '#contact',
) as $nav_label => $nav_url ) {
	wp_update_nav_menu_item(
		$primary_menu_term,
		0,
		array(
			'menu-item-title'  => $nav_label,
			'menu-item-url'    => $nav_url,
			'menu-item-status' => 'publish',
			'menu-item-type'   => 'custom',
		)
	);
}
$theme_menu_locations              = get_theme_mod( 'nav_menu_locations', array() );
$theme_menu_locations['main_menu'] = $primary_menu_term;
set_theme_mod( 'nav_menu_locations', $theme_menu_locations );

// Regenerate Elementor's cached CSS so the new layout renders.
if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}

WP_CLI::success( sprintf( 'Built %s home page (ID %d), set as front page. CF7 form id: %d', $variant, $home_page_id, $cf7_form_id ) );
