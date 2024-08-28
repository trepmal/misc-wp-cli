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
	 * [--format=<format>]
	 * : Format to use for the output. One of table, csv or json.
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

		$this->debug_data = [];

		add_action( 'wp_mail_failed', function( $e ) {
			$this->add_data_row( 'wp_mail_failed', $e );
		} );
		add_action( 'wp_mail_succeeded', function( $mail_data ) {
			$this->add_data_row( 'wp_mail_succeeded', $mail_data );
		} );

		$this->add_data_row( 'from_address', apply_filters( 'wp_mail_from', 'default' ) );

		$this->add_data_row( 'sent', wp_mail( $to, sprintf( 'TEST FROM %s (%s)', home_url(), time() ), 'lorem ipsum.' ) );


		$formatter = new \WP_CLI\Formatter( $assoc_args, array( 'data', 'value' ), 'wp_mail_test' );
		$formatter->display_items( $this->debug_data );
	}

	private function add_data_row( $label, $value ) {
		if ( is_iterable( $value ) ) {
			foreach ( $value as $k => $v ) {
				$this->debug_data[] = [ 'data' => "{$label}[{$k}]", 'value' => $v ];
			}
		} else {
			$this->debug_data[] = [ 'data' => $label, 'value' => $value ];
		}
	}

}
