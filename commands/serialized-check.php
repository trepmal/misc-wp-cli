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
	 * ## EXAMPLES
	 *
	 *     $ wp serialized-check 'a:1:{s:3:"foo";s:3:"bar";}'
	 *     Success: string is ok
	 *
	 *     $ wp serialized-check 'a:1:{s:3:"foo";s:4:"bar";}'
	 *     bar: actual: 3, expected 4
	 */
	function __invoke( $args, $assoc_args ) {

		list( $string ) = $args;

		if ( unserialize( $string ) ) {
			WP_CLI::success( "string is ok ");
		}

		preg_match_all( '/s:(\d+):"(.*?)";/', $string, $matches );


		$count = count( $matches[0] );

		for( $i=0; $i<$count; $i++ ) {

			$value = stripslashes( $matches[2][$i] );

			$declared_length = intval( $matches[1][$i] );
			$actual_length = strlen( $value );

			if ( $declared_length !== $actual_length ) {
				WP_CLI::log( sprintf( '%s: actual: %d, expected %d', $value, $actual_length, $declared_length ) );
			}
		}

	}
}
