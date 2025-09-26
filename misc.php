<?php
/**
 * Plugin Name: misc-wp-cli
 * Author: trepmal
 */

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	require 'commands/network-list.php';
	WP_CLI::add_command( 'network list', 'Network_List_CLI' );

	require 'commands/site-update.php';
	WP_CLI::add_command( 'site update', 'Site_Update_CLI' );

	require 'commands/cron-control-debug.php';
	WP_CLI::add_command( 'cron-control debug', 'Cron_Control_Debug_CLI' );

	require 'commands/wp-mail-test.php';
	WP_CLI::add_command( 'wp_mail test', 'WP_Mail_Test_CLI' );

	require 'commands/serialized-check.php';
	WP_CLI::add_command( 'serialized-check', 'Serialized_Check_Command' );

	require 'commands/post-cache.php';
	WP_CLI::add_command( 'post cache', 'Post_Cache_Command' );

	require 'commands/delete-file.php';
	WP_CLI::add_command( 'delete-file', 'Delete_File_Command' );

	require 'commands/find-by-path.php';
	WP_CLI::add_command( 'find-by-path', 'Find_By_Path_Command' );

	require 'commands/cap-compare.php';
	WP_CLI::add_command( 'cap-compare', 'Cap_Compare_Command' );

}
