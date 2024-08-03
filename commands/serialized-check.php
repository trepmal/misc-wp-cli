<?php
/**
 * check string lengths in serialized data
 *
 */

class Serialized_Check_Command extends WP_CLI_Command {

	/**
	 * Check string lengths in serialized string
	 *
	 * ## OPTIONS
	 *
	 * <value>
	 * : String to check
	 *
	 * [--repair]
	 * : Attempt to repair. Does not write to option
	 *
	 * [--format=<format>]
	 * : Get value in a particular format.
	 * ---
	 * default: var_export
	 * options:
	 *   - var_export
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp serialized-check 'a:1:{s:3:"foo";s:3:"bar";}'
	 *     Success: String can unserialize
	 *
	 *     $ wp serialized-check 'a:1:{s:3:"foo";s:4:"bar";}'
	 *     Found `bar` ( actual: 3 expected: 4 )
	 *
	 *     $ wp serialized-check  'a:1:{s:3:"foo";s:2:"bar";}' --repair
	 *     Found `bar` ( actual: 3 expected: 2 )
	 *     Success: Repaired value below. Be sure to update database as needed.
	 *     a:1:{s:3:"foo";s:3:"bar";}
	 */
	function __invoke( $args, $assoc_args ) {

		list( $string ) = $args;
		$repair = absint( WP_CLI\Utils\get_flag_value( $assoc_args, 'repair', false ) );

		if ( empty( $string ) ) {
			WP_CLI::error( 'Provide a value' );
		}

		if ( unserialize( $string ) ) {
			WP_CLI::success( "String can unserialize");
			exit;
		}

		$regex = '/s:(\d+):"(.*?)";/';

		if ( ! $repair ) {

			preg_match_all( $regex, $string, $matches );

			$count = count( $matches[0] );

			for( $i=0; $i<$count; $i++ ) {

				$value           = stripslashes( $matches[2][$i] );
				$expected_length = intval( $matches[1][$i] );
				$actual_length   = strlen( $value );

				if ( $expected_length !== $actual_length ) {
					WP_CLI::log( sprintf( 'Found `%s` ( actual: %d expected: %d )', $value, $actual_length, $expected_length ) );
				}
			}

		} else {

			$newstring = preg_replace_callback( $regex, function( $matches ) {

				$value           = stripslashes( $matches[2] );
				$expected_length = intval( $matches[1] );
				$actual_length   = strlen( $value );

				if ( $expected_length !== $actual_length ) {
					WP_CLI::log( sprintf( 'Found `%s` ( actual: %d expected: %d )', $value, $actual_length, $expected_length ) );
					return sprintf( 's:%s:"%s";', $actual_length, $value );
				}
				return $matches[0];

			}, $string );

			if ( $newstring == $string ) {
				WP_CLI::success( "No changes made" );
				exit;
			}
			if ( unserialize( $newstring ) ) {
				WP_CLI::success( "Repaired value below. Be sure to update database as needed." );
				WP_CLI::print_value( $newstring, $assoc_args );
				exit;
			} else {
				WP_CLI::error( "Unable to repair" );
			}

		}

	}
}
