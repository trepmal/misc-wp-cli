<?php
/**
 * Cron_Control_Debug
 *
 */

class Cron_Control_Debug_CLI extends WP_CLI_Command {

	/**
	 * Check events in cron-control for serialization issues
	 *
	 * ## OPTIONS
	 *
	 * [--per-page=<per-page>]
	 * : Number of options from database to list. 'notoptions' are not counted
	 * and are always displayed. Default: 1000
	 *
	 * [--page=<page>]
	 * : Page of results
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
	 *     $ wp cron-control debug
	 *     # example output truncated
	 *     +-----+--------+----------------------------------+----------------------------+-------------------------+----------------------------------------+
	 *     | ID  | action | instance                         | args                       | args_ok                 | match                                  |
	 *     +-----+--------+----------------------------------+----------------------------+-------------------------+----------------------------------------+
	 *     | 123 | action | 49a3696adf0fbfacc12383a2d7400d51 | a:1:{s:3:"foo";s:3:"baz";} | ✅ args can unserialize | ❌ WARNING: args do not match instance |
	 *     +-----+--------+----------------------------------+----------------------------+-------------------------+----------------------------------------+
	 *
	 */
	function __invoke( $args, $assoc_args ) {

		$limit = absint( WP_CLI\Utils\get_flag_value( $assoc_args, 'per-page', 1000 ) );
		$page  = absint( WP_CLI\Utils\get_flag_value( $assoc_args, 'page', 1 ) );
		$page_for_math = $page - 1;
		$offset = $limit * $page_for_math;

		global $wpdb;

		$jobs_list = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT SQL_CALC_FOUND_ROWS ID,action,action_hashed,instance,args from {$wpdb->prefix}a8c_cron_control_jobs where status = 'pending' ORDER BY timestamp DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);

		$total_rows = $wpdb->get_var('SELECT FOUND_ROWS()');

		$output = [];
		$failures = 0;

		foreach ( $jobs_list as $job ) {

			$args_match   = ( $job->instance === md5( $job->args ) );
			$args_ok      = unserialize( $job->args ) !== false;
			$action_match = md5( $job->action ) === $job->action_hashed;

			$lock_key = md5( "ev-{$job->action}" );
			$locked   = wp_cache_get( "a8ccc_lock_$lock_key" );

			$locked_msg = sprintf(
				'❌ locked at: %s',
				date('r', wp_cache_get( "a8ccc_lock_ts_$lock_key") )
			);

			if ( ! $args_match || ! $args_ok || ! $action_match || $locked ) {
				$failures++;
				$output[ $job->ID ] = [
					'ID'       => $job->ID,
					'action'   => $job->action,
					'instance' => $job->instance,
					'locked'   => $locked ? $locked_msg : '',
					'args'     => $job->args,
					'args_match'    => ( $args_match   ? '✅ md5( args ) match instance'        : "❌ WARNING: args do not match instance" ),
					'args_ok'       => ( $args_ok      ? '✅ args can unserialize'              : "❌ WARNING: cannot unserialize" ),
					'action_match'  => ( $action_match ? '✅ md5( action ) match action_hashed' : "❌ WARNING: action does not match action_hashed" ),
					
				];
			}

		}

		$format = $assoc_args['format'];
		$formatter = new \WP_CLI\Formatter( $assoc_args, array( 'ID', 'action', 'instance', 'locked', 'args', 'args_match', 'args_ok', 'action_match' ), 'cron-control-debug' );
		$formatter->display_items( $output );

		WP_CLI::line( 'Only displaying problematic events' );

		if (
			// don't show footer for strict formats (csv, json...)
			// $format == 'table' && // $format not set until we wrap this in a proper command
			$total_rows > ( $offset + $limit )
		) {
			WP_CLI::line( sprintf(
				'Page %d/%d database options shown. Use `--per-page=%d --page=%d` for next set.',
				$page,
				ceil( $total_rows / $limit ),
				$limit,
				$page + 1
			) );
		}

		$queue_size = defined( 'CRON_CONTROL_JOB_QUEUE_SIZE' ) && CRON_CONTROL_JOB_QUEUE_SIZE ? CRON_CONTROL_JOB_QUEUE_SIZE : 10;

		if ( $queue_size <= $failures ) {
			WP_CLI::warning( 'There are enough failures to clog the job queue. (queue size: '. $queue_size.')' );
		}

	}

}
