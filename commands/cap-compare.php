<?php
/**
 * cap compare
 *
 */

class Cap_Compare_Command {

	/**
	 * Compare capabilities between user roles
	 *
	 * ## OPTIONS
	 *
	 * [--caps=<caps>]
	 * : comma-separated list of capabilities to check
	 *
	 * [--roles=<roles>]
	 * : comma-separated list of roles to check
	 *
	 * [--true-for-x]
	 * : Use bool values instead of "X"/"" in output
	 *
	 * [--format=<format>]
	 * : Accepted values: table, csv, json, yaml. Default: table
	 *
	 * ## EXAMPLES
	 *
	 *   Roles are ordered most-to-least capabilities
	 *   Capabilities are ordered rarest-to-most common
	 *
	 *     $ wp cap-compare --caps=publish_posts,upload_files --roles=editor,administrator
	 *     +---------------+--------+---------------+
	 *     | cap           | editor | administrator |
	 *     +---------------+--------+---------------+
	 *     | publish_posts | X      | X             |
	 *     | upload_files  | X      | X             |
	 *     +---------------+--------+---------------+
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function __invoke( $args, $assoc_args ) {

		// get options
		$show_caps  = array_filter( explode(',', \WP_CLI\Utils\get_flag_value( $assoc_args, 'caps', '' ) ) );
		$show_roles = array_filter( explode(',', \WP_CLI\Utils\get_flag_value( $assoc_args, 'roles', '' ) ) );
		$true_for_x = \WP_CLI\Utils\get_flag_value( $assoc_args, 'true-for-x', false );

		// get role data
		$wp_roles   = wp_roles();
		$role_data  = $wp_roles->roles;
		$role_names = $wp_roles->get_names();
		// roles, ordered by number of caps
		$role_keys = array_map( function( $role ) {
			return count( $role['capabilities'] );
		}, $role_data );
		arsort( $role_keys );
		$role_keys = array_keys( $role_keys );

		// skip roles by default, customizable via filter
		$skip_roles = apply_filters( 'wp_cli_caps_compare_skip_roles', [ 'vip_support', 'vip_support_inactive' ] );
		$role_keys  = array_diff( $role_keys, $skip_roles );

		// apply option
		$role_keys = $show_roles ? array_intersect( $show_roles, $role_keys ) : $role_keys;

		// map roles slugs to names for final display
		foreach ( $role_keys as $k => $v ) {
			$role_keys[ $k ] = $role_names[ $v ] ?: $v;
		}

		// init data array
		$caps_data = [];

		$has_indicator = $true_for_x ? true : 'X';
		$not_indicator = $true_for_x ? false : '';

		foreach ( $role_data as $role ) {
			$role_key = $role['name'];

			$caps = $role['capabilities'];

			foreach ( $caps as $cap => $true ) {

				// apply option
				if ( $show_caps && ! in_array( $cap, $show_caps, true ) ) {
					continue;
				}

				if ( isset( $caps_data[ $cap ] ) ) {
					$caps_data[ $cap ][ $role_key ] = $has_indicator;
				} else {
					// init row with all roles as keys with empty values
					$row = array_map(
						function( $i ) use ( $role_key, $has_indicator, $not_indicator ) {
							return $i === $role_key ? $has_indicator : $not_indicator;
						},
						$role_keys
					);
					$row = array_combine( $role_keys, $row );

					$caps_data[ $cap ] = array_merge( [ 'cap' => $cap, 'count' => null ], $row );
				}
			}
		}

		// count how many roles have each cap
		foreach ( $caps_data as $cap => $data ) {
			$count = 0;
			foreach ( $role_keys as $role_key ) {
				if ( isset( $data[ $role_key ] ) && 'X' === $data[ $role_key ] ) {
					$count++;
				}
			}
			$caps_data[ $cap ]['count'] = $count;
		}

		// sort by rarest cap first
		array_multisort( array_column( $caps_data, 'count' ), SORT_ASC, $caps_data );

		$header = array_merge( [ 'cap' ], $role_keys );

		$formatter = new \WP_CLI\Formatter( $assoc_args, $header, 'caps' );
		$formatter->display_items( $caps_data );
	}
}
