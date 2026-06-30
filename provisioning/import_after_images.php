<?php
/**
 * Import the optimized WebP photos as WordPress media for the "after" state.
 * Slugs get a "-webp" suffix so both image sets coexist and build_home.php can
 * pick between them. Re-running replaces any attachment that shares a slug.
 *
 * Run:
 *   docker compose run --rm wpcli wp eval-file /provisioning/import_after_images.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$image_roles = array( 'hero', 'lawn', 'patio', 'flowers', 'trees' );
$source_dir  = '/assets/after';

$imported = array();
foreach ( $image_roles as $image_role ) {
	$source_path = $source_dir . '/' . $image_role . '.webp';
	if ( ! file_exists( $source_path ) ) {
		WP_CLI::error( "Missing source image: {$source_path}. Run scripts/make_after_images.py first." );
	}

	$target_slug = $image_role . '-webp';
	$prior_attachments = get_posts(
		array(
			'post_type'   => 'attachment',
			'name'        => $target_slug,
			'numberposts' => -1,
			'post_status' => 'inherit',
		)
	);
	foreach ( $prior_attachments as $prior_attachment ) {
		wp_delete_attachment( $prior_attachment->ID, true );
	}

	$temp_path = wp_tempnam( $target_slug . '.webp' );
	copy( $source_path, $temp_path );

	$sideload = array( 'name' => $target_slug . '.webp', 'tmp_name' => $temp_path );
	$attachment_id = media_handle_sideload( $sideload, 0 );
	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $temp_path );
		WP_CLI::error( "Sideload failed for {$target_slug}: " . $attachment_id->get_error_message() );
	}

	$imported[ $target_slug ] = $attachment_id;
	WP_CLI::log( sprintf( '%-14s -> attachment %d', $target_slug, $attachment_id ) );
}

WP_CLI::success( 'Imported ' . count( $imported ) . ' optimized WebP images.' );
