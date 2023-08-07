<?php
/**
 * List networks
 *
 */

class Network_List_CLI extends WP_CLI_Command {

	/**
	 * List Networks
	 *
	 * ## OPTIONS
	 *
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
	 *     $ wp network list
	 *     +----+-------------+------+
	 *     | id | domain      | path |
	 *     +----+-------------+------+
	 *     | 1  | example.com | /    |
	 *     +----+-------------+------+
	 *
	 */
	function __invoke( $args, $assoc_args ) {

		if ( ! is_multisite() ) {
			WP_CLI::error( "This command is for multisites only." );
		}

		global $wpdb;

		$data = $wpdb->get_results( "SELECT * from {$wpdb->site}" );

		if ( empty( $data ) ) {
			WP_CLI::error( "No results found. That can't be good." );
		}

		$formatter = new \WP_CLI\Formatter( $assoc_args, array_keys( (array) $data[0] ), 'options' );
		$formatter->display_items( $data );

	}

}
