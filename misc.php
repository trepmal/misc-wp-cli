<?php

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	require 'commands/network-list.php';
	WP_CLI::add_command( 'network list', 'Network_List_CLI' );

	require 'commands/site-update.php';
	WP_CLI::add_command( 'site update', 'Site_Update_CLI' );

	require 'commands/cron-control-debug.php';
	WP_CLI::add_command( 'cron-control debug', 'Cron_Control_Debug_CLI' );

	require 'commands/wp-mail-test.php';
	WP_CLI::add_command( 'wp_mail test', 'WP_Mail_Test_CLI' );

}
