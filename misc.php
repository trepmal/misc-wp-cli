<?php

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require 'commands/network-list.php';
	WP_CLI::add_command( 'network list', 'Network_List_CLI' );

	require 'commands/site-update.php';
	WP_CLI::add_command( 'site update', 'Site_Update_CLI' );

}
