<?php
/**
 *
 */

class Post_Cache_Command extends WP_CLI_Command {
	var $did_truncate = false;

	/**
	 * Post Cache Check
	 *
	 * ## OPTIONS
	 *
	 * <postid>
	 * : Post ID to check
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 */
	function check( $args, $assoc_args ) {

		$this->format = $assoc_args['format'];

		list( $post_id ) = $args;
		$post_id = absint( $post_id );

		$post_cached = get_post( $post_id );
		if ( ! $post_cached ) {
			WP_CLI::error( sprintf( 'Post %d not found', $post_id ) );
		}

		$post_meta_cached = get_post_meta( $post_id );

		global $wpdb;

		$post_db      = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d LIMIT 1", $post_id ) );
		$post_meta_db = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", $post_id ) );

		$output = [];

		foreach ( $post_db as $field => $value ) {
			$cached_value = $post_cached->$field;
			$output[] = [
				'part' => 'post',
				'field' => $field,
				'db_value' => $this->truncate( $value, 200 ),
				'cache_value' => $this->truncate( $cached_value,200 ),
				'match' => $value === $cached_value ? "match" : ( $value == $cached_value ? "match (loose)" : "❌" ),
			];

		}

		$post_meta_db2 = [];

		foreach ( $post_meta_db as $row => $fv ) {
			$field = $fv->meta_key;
			$value = $fv->meta_value;
			if ( ! isset( $post_meta_db2[ $field ] ) ) {
				$post_meta_db2[ $field ] = [ $value ];
			} else {
				$post_meta_db2[ $field ][] = $value; 
			}
		}

		foreach ( $post_meta_db2 as $field => $value ) {
			$cached_value = $post_meta_cached[ $field ];
			$output[] = [
				'part' => 'meta',
				'field' => $field,
				'db_value' => $this->truncate( maybe_serialize( $value ), 200 ),
				'cache_value' => $this->truncate( maybe_serialize( $cached_value ), 200 ),
				'match' => $value === $cached_value ? "match" : ( $value == $cached_value ? "match (loose)" : "❌" ),
			];
		}
		if ( $this->did_truncate ) {
			WP_CLI::log( 'Long values truncated' );
		}
		$formatter = new \WP_CLI\Formatter( $assoc_args, array( 'part', 'field', 'db_value', 'cache_value', 'match' ), 'post-cache-health' );
		$formatter->display_items( $output );

	}

	private function truncate( $input, $limit ) {
		if ( $this->format != 'table' || strlen( $input ) <= $limit+3 ) {
			return $input;
		}
		$this->did_truncate = true;
		return substr( $input, 0, $limit ) . ' [...]';
	}
}

