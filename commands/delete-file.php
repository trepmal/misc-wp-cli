<?php
/**
 * Delete file WP-CLI Command
 */

 class Delete_File_Command extends WP_CLI_Command {

	/**
	 * Delete file from VIP File System
	 *
	 * Will delete attachment post if found, and cache.
	 *
	 * ## OPTIONS
	 *
	 * <URL>
	 * : URL of file to delete
	 *
	 * [--skip-check]
	 * : Skip media library check
	 *
	 * [--yes]
	 * : Skip delete confirmation
	 *
	 * [--sandbox]
	 * : Force run on sandbox
	 *
	 */
	function __invoke( $args, $assoc_args ) {

		// only runs on VIP Go
		if ( ! defined( 'VIP_GO_APP_ID' ) ) {
			WP_CLI::error( 'This command can only be run on VIP Go.' );
		}

		list( $url ) = $args;
		$skip_library   = \WP_CLI\Utils\get_flag_value( $assoc_args, 'skip-check', false );
		$sandbox        = \WP_CLI\Utils\get_flag_value( $assoc_args, 'sandbox', false );

		// if running from a VIP Sandbox (VIP Staff only)
		if ( defined( 'WPCOM_SANDBOXED' ) && WPCOM_SANDBOXED && ! $sandbox ) {
			WP_CLI::error( 'Running from a sandbox will not purge cache correctly. Use --sandbox to run anyway.' );
		}

		$url = esc_url_raw( $url );
		/**
		 * TODO: if multisite, use url to determine which subsite.
		 */
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			WP_CLI::error( 'Not a valid URL: %s', $url );
		}
		WP_CLI::log( WP_CLI::colorize( sprintf( '%%bChecking:%%n %s', $url ) ) );

		preg_match('/wp-content\/uploads\/((sites\/(\d+)\/)?(.*?))$/', $url, $matches );

		if ( ! $matches ) {
			WP_CLI::error( 'Invalid URL, no match to file system path' );
		}

		list(
			$wp_content_path, // path starting from wp-content/
			$fs_path,         // path after uploads/
			$sites_and_id,    // site/<id>/
			$blog_id,         // <id>
			$attached_file    // path after site/<id>/ (may be same as $fs_path)
		) = $matches;

		$fs_url = sprintf( 'vip://wp-content/uploads/%s', $fs_path );

		if ( ! file_exists( $fs_url ) ) {
			WP_CLI::error( 'File not found' );
		}

		if ( ! $skip_library ) {
			global $wpdb;
			$post_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id from {$wpdb->postmeta} where meta_key = '_wp_attached_file' and meta_value = %s",
					$attached_file
				)
			);
			if ( $post_id ) {
				WP_CLI::log( "Attachment $post_id found. " );
				WP_CLI::run_command( [ 'post', 'get', $post_id ], [ 'fields'=>'post_title,guid'] );
			} else {
				WP_CLI::log( "No matching attachment found." );
				if ( is_multisite() ) {
					WP_CLI::log( "Only checked in {$wpdb->postmeta}. Use `--url` to check other network sites." );
				}
			}
		}

		if ( isset( $post_id ) ) {
			WP_CLI::confirm( "Delete $fs_path and attachment post $post_id?", $assoc_args );
			// post delete will invoke hooks for cache purges
			WP_CLI::run_command( [ 'post', 'delete', $post_id ], [ 'force' => 'true' ] );
		} else {
			// with no post_id, manually unlink and purge cache
			WP_CLI::confirm( "Delete $fs_path?", $assoc_args );
			$unlink = unlink( $fs_url );
			if ( $unlink ) {
				WP_CLI::log( "File deleted" );
				WP_CLI::run_command( [ 'vip', 'cache', 'purge-url', $url ] );
			} else {
				WP_CLI::log( "Unable to delete file" );
			}
		}

	}

}
