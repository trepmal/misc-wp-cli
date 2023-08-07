<?php
/**
 * Update sites (blogs)
 *
 */

class Site_Update_CLI extends WP_CLI_Command {

	/**
	 * Update site
	 *
	 * ## OPTIONS
	 *
	 * <blog_id>
	 * : Blog ID
	 *
	 * [--domain=<domain>]
	 * : New domain value
	 *
	 * [--path=<path>]
	 * : New path value
	 *
	 * [--format=<format>]
	 * : Format to use for the output. One of table, csv or json.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site update 1 --domain=newdomain.com
	 *
	 */
	function __invoke( $args, $assoc_args ) {

		if ( ! is_multisite() ) {
			WP_CLI::error( "This command is for multisites only." );
		}

		list( $blog_id ) = $args;
		$domain  = WP_CLI\Utils\get_flag_value( $assoc_args, 'domain', false );
		$path    = WP_CLI\Utils\get_flag_value( $assoc_args, 'page', false );

		if ( ! $domain && ! $path ) {
			WP_CLI::error( "At least one of domain or path must be set" );
		}


		global $wpdb;

		$data = $wpdb->get_row( $wpdb->prepare( "SELECT * from {$wpdb->blogs} where blog_id = %d limit 1", $blog_id ) );

		if ( empty( $data ) ) {
			WP_CLI::error( sprintf( "No record found for blog ID %d", $blog_id ) );
		}

		$new_domain = $domain ? $domain : $data->domain;
		$new_path = $path ? $path : $data->path;

		if ( $data->domain === $new_domain && $data->path = $new_path ) {
			WP_CLI::line( "No changes detected." );
			exit;
		}

		WP_CLI::line( WP_CLI::colorize( '%yCurrent values:%n' ) );
		WP_CLI::line( sprintf( 'Domain: %s', $data->domain ) );
		WP_CLI::line( sprintf( 'Path: %s', $data->path ) );

		WP_CLI::line( WP_CLI::colorize( '%bNew values:%n' ) );
		WP_CLI::line( sprintf( 'Domain: %s', $new_domain ) );
		WP_CLI::line( sprintf( 'Path: %s', $new_path ) );

		WP_CLI::confirm( 'Proceed?' );

		$updated = $wpdb->update(
			$wpdb->blogs,
			[
				'domain' => $new_domain,
				'path' => $new_path,
			],
			[
				'blog_id' => $blog_id,
			]
		);

		if ( $updated ) {
			WP_CLI::success( "Updated." );
			WP_CLI::line( "You may need to flush cache." );
		} else {
			WP_CLI::error( "Failed to update." );
		}

	}

}
