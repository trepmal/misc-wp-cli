<?php
/**
 * wp_mail test
 *
 */

class WP_Mail_Test_CLI extends WP_CLI_Command {

	/**
	 * Test wp_mail
	 *
	 * ## OPTIONS
	 *
	 * --to=<to>
	 * : 'to' email address
	 *
	 *
	 */
	function __invoke( $args, $assoc_args ) {

		$to = WP_CLI\Utils\get_flag_value( $assoc_args, 'to', false );

		$pre_wp_mail = apply_filters( 'pre_wp_mail', null, $atts );

		if ( ! is_null( $pre_wp_mail ) ) {
			WP_CLI::log( 'pre_wp_mail filter blocking' );
			var_dump( $pre_wp_mail );
			exit;
		}

		add_action( 'wp_mail_failed', function( $e ) { var_dump( $e ); } );
		add_action( 'wp_mail_succeeded', function( $mail_data ) { var_dump( $mail_data ); } );

		var_dump( sprintf( 'from address: %s', apply_filters( 'wp_mail_from', 'default' ) ) );
		var_dump( wp_mail( $to, sprintf( 'TEST (%s)', time() ), 'lorem ipsum.' ) );

	}

}
