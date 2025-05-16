<?php
/**
 * Find attachment by path
 */

 class Find_By_Path_Command extends WP_CLI_Command {

	/**
	 * Find attachment by path
	 *
	 * ## OPTIONS
	 *
	 * <URL>
	 * : Path or URL of file
	 * 
	 * [--<field>=<value>]
	 * : Args to pass to post get
	 *
	 */
	function __invoke( $args, $assoc_args ) {

		list( $url ) = $args;

		if ( str_contains( $url, 'wp-content/uploads' ) ) {
			preg_match('/wp-content\/uploads\/((sites\/(\d+)\/)?(.*?))$/', $url, $matches );

			list(
				$wp_content_path, // path starting from wp-content/
				$fs_path,         // path after uploads/
				$sites_and_id,    // site/<id>/
				$blog_id,         // <id>
				$attached_file    // path after site/<id>/ (may be same as $fs_path)
			) = $matches;
		} else {
			$attached_file = trim( $url, '/' );
		}

		global $wpdb;
		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id from {$wpdb->postmeta} where meta_key = '_wp_attached_file' and meta_value = %s",
				$attached_file
			)
		);
		if ( $post_id ) {
			// WP_CLI::log( "Attachment $post_id found. " );
			WP_CLI::run_command( [ 'post', 'get', $post_id ], $assoc_args );
		} else {
			WP_CLI::log( "No matching attachment found." );
			if ( is_multisite() ) {
				WP_CLI::log( "Only checked in {$wpdb->postmeta} table. Use `--url` to check other network sites." );
			}
		}

	}

}
