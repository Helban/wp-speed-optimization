<?php
/**
 * Import the de-optimized landscaping photos as WordPress media, keeping them
 * deliberately huge. WordPress would normally down-scale anything over 2560px
 * and re-encode JPEGs at quality 82; both filters below are disabled so the
 * "before" site serves the original multi-megabyte files, exactly the problem
 * a real client hands over.
 *
 * Re-running replaces any attachment that shares a slug, so it stays repeatable.
 *
 * Run:
 *   docker compose run --rm wpcli wp eval-file /provisioning/import_before_images.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

add_filter( 'big_image_size_threshold', '__return_false' );
add_filter( 'jpeg_quality', static function () { return 100; } );
add_filter( 'wp_editor_set_quality', static function () { return 100; } );

$image_slugs = array( 'hero', 'lawn', 'patio', 'flowers', 'trees' );
$source_dir  = '/assets/before';

$imported = array();
foreach ( $image_slugs as $image_slug ) {
	$source_path = $source_dir . '/' . $image_slug . '.jpg';
	if ( ! file_exists( $source_path ) ) {
		WP_CLI::error( "Missing source image: {$source_path}. Run scripts/make_before_images.py first." );
	}

	// Drop any prior attachment with this slug so re-runs do not pile up.
	$prior_attachments = get_posts(
		array(
			'post_type'   => 'attachment',
			'name'        => $image_slug,
			'numberposts' => -1,
			'post_status' => 'inherit',
		)
	);
	foreach ( $prior_attachments as $prior_attachment ) {
		wp_delete_attachment( $prior_attachment->ID, true );
	}

	// media_handle_sideload() moves the file, so work on a writable copy.
	$temp_path = wp_tempnam( $image_slug . '.jpg' );
	copy( $source_path, $temp_path );

	$sideload = array( 'name' => $image_slug . '.jpg', 'tmp_name' => $temp_path );
	$attachment_id = media_handle_sideload( $sideload, 0 );
	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $temp_path );
		WP_CLI::error( "Sideload failed for {$image_slug}: " . $attachment_id->get_error_message() );
	}

	$imported[ $image_slug ] = $attachment_id;
	WP_CLI::log( sprintf( '%-8s -> attachment %d', $image_slug, $attachment_id ) );
}

WP_CLI::success( 'Imported ' . count( $imported ) . ' un-optimized images (no down-scaling).' );
