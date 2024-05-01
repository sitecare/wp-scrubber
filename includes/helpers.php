<?php
/**
 * Plugin specific helpers.
 *
 * @package TenUpWPScrubber
 */

namespace TenUpWPScrubber\Helpers;

/**
 * Get the size of the current database.
 *
 * @return int
 */
function get_database_size() {
	global $wpdb;

	$database_name = $wpdb->dbname;

	$query = "
		SELECT table_schema AS 'Database',
		SUM(data_length + index_length) / 1024 / 1024 AS 'Size (MB)'
		FROM information_schema.TABLES
		WHERE table_schema = '$database_name'
		GROUP BY table_schema;
	";

	$result = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT table_schema AS 'Database',
			SUM(data_length + index_length) / 1024 / 1024 AS 'Size (MB)'
			FROM information_schema.TABLES
			WHERE table_schema = %s
			GROUP BY table_schema;",
			$database_name
		)
	);

	if ( ! empty( $result ) ) {
		// Round to an integer.
		return intval( $result[0]->{'Size (MB)'} );
	}

	return 0;
}

/**
 * Apply_filters wrapper in case it's not defined
 *
 * @param string $hook_name The name of the filter hook.
 * @param mixed  $value     The value to filter.
 * @param mixed  ...$args   Additional parameters to pass to the callback functions.
 * @return mixed The filtered value after all hooked functions are applied to it.
 */
function wp_scrubber_apply_filters( $hook_name, $value, ...$args ) {
	if ( function_exists( 'apply_filters' ) ) {
		return apply_filters( $hook_name, $value, ...$args );
	}

	return $value;
}

/**
 * Logging helper function
 *
 * @param mixed    $message Message to log
 * @param callable $logger Logging function
 */
function log( $message, $logger = null ) {
	if ( ! empty( $logger ) && is_callable( $logger ) ) {
		$logger( $message );
	}
}

/**
 * Scrub comments
 *
 * Remove any comment data from the database.
 *
 * @param callable $logger Logging function
 * @param boolean  $replace_tables  Replace tables with temp ones
 */
function scrub_comments( $logger = null, $replace_tables = true ) {
	global $wpdb;

	// Drop tables if they exist.
	log( "Scrubbing comments on {$wpdb->comments}...", $logger );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->comments}_temp" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->commentmeta}_temp" );

	log( ' - Duplicating comments table into temp table...', $logger );
	$wpdb->query( "CREATE TABLE {$wpdb->comments}_temp LIKE $wpdb->comments" );
	$wpdb->query( "INSERT INTO {$wpdb->comments}_temp SELECT * FROM $wpdb->comments" );

	log( ' - Duplicating comment meta table into temp table...', $logger );
	$wpdb->query( "CREATE TABLE {$wpdb->commentmeta}_temp LIKE $wpdb->commentmeta" );
	$wpdb->query( "INSERT INTO {$wpdb->commentmeta}_temp SELECT * FROM $wpdb->commentmeta" );

	// TODO: We may want more sophisticated scrubbing of comments later, but right now we'll just truncate the tables.
	log( ' - Scrubbing comments table...', $logger );
	$wpdb->query( "TRUNCATE TABLE {$wpdb->comments}_temp" );
	$wpdb->query( "TRUNCATE TABLE {$wpdb->commentmeta}_temp" );

	if ( $replace_tables ) {
		log( ' - Replacing comment tables with the scrubbed versions...', $logger );
		$wpdb->query( "DROP TABLE {$wpdb->comments}" );
		$wpdb->query( "DROP TABLE {$wpdb->commentmeta}" );
		$wpdb->query( "RENAME TABLE {$wpdb->comments}_temp TO {$wpdb->comments}" );
		$wpdb->query( "RENAME TABLE {$wpdb->commentmeta}_temp TO {$wpdb->commentmeta}" );
	}
}

/**
 * Scrub WordPress Users
 *
 * @param array    $allowed_domains Allowed email domains
 * @param array    $allowed_emails  Allowed email addresses
 * @param callable $logger Logging function
 * @param boolean  $replace_tables  Replace user tables with temp ones
 * @return void
 */
function scrub_users( $allowed_domains = [], $allowed_emails = [], $logger = null, $replace_tables = true ) {
	global $wpdb;

	// Drop tables if they exist.
	log( 'Scrubbing users...', $logger );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->usermeta}_temp" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->users}_temp" );

	log( ' - Duplicating users table into temp tables...', $logger );
	$wpdb->query( "CREATE TABLE {$wpdb->users}_temp LIKE $wpdb->users" );
	$wpdb->query( "INSERT INTO {$wpdb->users}_temp SELECT * FROM $wpdb->users" );

	log( ' - Scrubbing each user record...', $logger );
	$dummy_users = get_dummy_users();

	$offset   = 0;
	$user_ids = [];

	while ( true ) {
		$users = $wpdb->get_results( $wpdb->prepare( "SELECT ID, user_login, user_email FROM {$wpdb->users}_temp LIMIT 1000 OFFSET %d", $offset ), 'ARRAY_A' );

		if ( empty( $users ) ) {
			break;
		}

		if ( 1000 <= $offset ) {
			usleep( 100 );
		}

		foreach ( $users as $user ) {
			$user_id    = (int) $user['ID'];
			$user_ids[] = $user_id;
			$dummy_user = $dummy_users[ $user_id % 1000 ];

			scrub_user( $user, $dummy_user, $allowed_domains, $allowed_emails );
		}

		$offset += 1000;
	}

	log( ' - Duplicating user meta table into temp table...', $logger );

	$wpdb->query( "CREATE TABLE {$wpdb->usermeta}_temp LIKE $wpdb->usermeta" );
	$wpdb->query( "INSERT INTO {$wpdb->usermeta}_temp SELECT * FROM $wpdb->usermeta" );

	// Just truncate user description and session tokens.
	$wpdb->query( "UPDATE {$wpdb->usermeta}_temp SET meta_value='' WHERE meta_key='description' OR meta_key='session_tokens'" );

	$user_ids_count = count( $user_ids );
	for ( $i = 0; $i < $user_ids_count; $i++ ) {
		if ( 1 < $i && 0 === $i % 1000 ) {
			usleep( 100 );
		}

		$user_id = $user_ids[ $i ];

		$dummy_user = $dummy_users[ $user_id % 1000 ];

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->usermeta}_temp SET meta_value=%s WHERE meta_key='first_name' AND user_id=%d",
				$dummy_user['first_name'],
				(int) $user_id
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->usermeta}_temp SET meta_value=%s WHERE meta_key='last_name' AND user_id=%d",
				$dummy_user['last_name'],
				$user_id
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->usermeta}_temp SET meta_value=%s WHERE meta_key='nickname' AND user_id=%d",
				$dummy_user['first_name'],
				$user_id
			)
		);
	}

	if ( $replace_tables ) {
		log( ' - Replacing user tables with the scrubbed versions...', $logger );

		$wpdb->query( "DROP TABLE {$wpdb->usermeta}" );
		$wpdb->query( "DROP TABLE {$wpdb->users}" );
		$wpdb->query( "RENAME TABLE {$wpdb->usermeta}_temp TO {$wpdb->usermeta}" );
		$wpdb->query( "RENAME TABLE {$wpdb->users}_temp TO {$wpdb->users}" );
	}
}

/**
 * Scrub the user data
 *
 * @param array $user User array from wpdb query.
 * @param array $dummy_user User array from dummy user csv.
 * @param array $allowed_domains Allowed email domains
 * @param array $allowed_emails  Allowed email addresses
 */
function scrub_user( $user, $dummy_user, $allowed_domains = [], $allowed_emails = [] ) {

	global $wpdb;

	$scrub_user = true;

	if ( ! should_scrub_user( $user, $allowed_domains, $allowed_emails ) ) {
		return false;
	}

	/**
	 * Allow site owners to define their own user password ruleset.
	 * Otherwise, use the WordPress generated password.
	 * wp_generate_password() could potentially have performance
	 * issues on sites with a large user base.
	 */
	$password = wp_scrubber_apply_filters( 'wp_scrubber_scrubbed_password', false );
	if ( false === $password ) {
		$password = wp_hash_password( wp_generate_password() );
	}

	return $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->users}_temp SET user_pass=%s, user_email=%s, user_url='', user_activation_key='', user_login=%s, user_nicename=%s, display_name=%s WHERE ID=%d",
			$password,
			$dummy_user['email'],
			$dummy_user['username'],
			$dummy_user['username'],
			$dummy_user['first_name'] . ' ' . $dummy_user['last_name'],
			$user['ID']
		)
	);
}

/**
 * Add conditions to check whether a user should be scrubbed or not.
 *
 * @param array $user User array from wpdb query.
 * @param array $allowed_domains Allowed email domains
 * @param array $allowed_emails  Allowed email addresses
 * @return boolean
 */
function should_scrub_user( $user, $allowed_domains = [], $allowed_emails = [] ) {

	$scrub = true;

	// Check if the user is part of list of allowed email domains.
	$allowed_email_domains = wp_scrubber_apply_filters( 'wp_scrubber_allowed_email_domains', $allowed_domains );

	foreach ( $allowed_email_domains as $domain ) {
		if ( str_contains( $user['user_email'], '@' . $domain ) ) {
			$scrub = false;
		}
	}

	// Check if the user has been specifically allowed.
	$allowed_emails = wp_scrubber_apply_filters( 'wp_scrubber_allowed_emails', $allowed_emails );
	foreach ( $allowed_emails as $email ) {
		if ( $user['user_email'] === $email ) {
			$scrub = false;
		}
	}

	return wp_scrubber_apply_filters( 'wp_scrubber_should_scrub_user', $scrub, $user );
}

/**
 * Get dummy users from csv file.
 *
 * @return array
 */
function get_dummy_users() {
	static $users = [];

	if ( empty( $users ) ) {
		// We use __DIR__ here because this file is loaded via Composer outside the context of plugin constants
		$file = fopen( __DIR__ . '/data/users.csv', 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

		$line = fgetcsv( $file );
		while ( false !== $line ) {

			$user = [
				'username'   => $line[0],
				'first_name' => $line[1],
				'last_name'  => $line[2],
				'email'      => $line[3],
			];

			$users[] = $user;

			$line = fgetcsv( $file );
		}

		fclose( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
	}

	return $users;
}

/**
 * Retrieves an array of all user IDs from the database.
 *
 * @return array An array of user IDs.
 */
function get_all_user_ids() {
	global $wpdb;
	return $wpdb->get_col( "SELECT ID FROM {$wpdb->users}" );
}

/**
 * Retrieves an array of all post IDs of a given post type.
 *
 * @param string $post_type The post type to retrieve post IDs for.
 *
 * @return array An array of post IDs.
 */
function get_all_post_ids_of_post_type( $post_type ) {
	global $wpdb;
	return $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", $post_type ) );
}

/**
 * Retrieves an array of all term IDs of a given taxonomy.
 *
 * @param string $taxonomy The taxonomy to retrieve term IDs for.
 *
 * @return array An array of term IDs.
 */
function get_all_term_ids_of_taxonomy( $taxonomy ) {
	global $wpdb;
	return $wpdb->get_col( $wpdb->prepare( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s", $taxonomy ) );
}

/**
 * Get fake data based on the specified type.
 *
 * @param string $type The type of fake data to generate.
 *
 * @return mixed The generated fake data.
 */
function get_fake_data( string $type ): mixed {
	$data  = null;
	$faker = \Faker\Factory::create();

	// TODO: call type as magic method.
	switch ( $type ) {
		case 'name':
			$data = $faker->name();
			break;

		case 'email':
			$data = $faker->email();
			break;

		case 'sentence':
			$data = substr( $faker->sentence(), 0, -1 );
			break;

		case 'word':
			$data = $faker->word();
			break;

		case 'url':
			$data = $faker->url();
			break;

		case 'randomDigit':
			$data = $faker->randomDigit();
			break;
	}

	return $data;
}

/**
 * Retrieves the data for a given field based on its action.
 *
 * @param object $field The field object.
 *
 * @return mixed The data for the field.
 */
function get_field_data_by_action( object $field ): mixed {
	$data = null;

	switch ( $field->action ) {
		case 'remove':
			$data = '';
			break;

		case 'replace':
			$data = $field->value;
			break;

		case 'faker':
		default:
			$data = get_fake_data( $field->faker_type );
			break;
	}

	return $data;
}

/**
 * Scrub the meta field.
 *
 * @param int    $oject_id    The object ID.
 * @param string $meta_key    The meta key.
 * @param string $object_type The object type.
 *
 * @return void
 */
function scrub_meta_field( int $oject_id, object $field_config, string $object_type = 'post' ): void {
	global $wpdb;

	// TODO: validate config object.
	$meta_key = $field_config->key;

	switch ( $object_type ) {
		case 'user':
			$table      = $wpdb->usermeta;
			$object_key = 'user_id';
			break;

		case 'term':
			$table      = $wpdb->termmeta;
			$object_key = 'term_id';
			break;

		case 'post':
		default:
			$table      = $wpdb->postmeta;
			$object_key = 'post_id';
			break;
	}

	if ( 'remove' === $field_config->action ) {
		$wpdb->delete(
			$table,
			[
				$object_key => $object_id,
				'meta_key'  => $meta_key,
			]
		);

	} else {
		$meta_value = Helpers\get_field_data_by_action( $field_config );

		$wpdb->update(
			$table,
			[ 'meta_value' => $meta_value ],
			[
				$object_key => $object_id,
				'meta_key'  => $meta_key,
			]
		);
	}
}

/**
 * Scrubs an object by type.
 *
 * @param int    $object_id     The ID of the object to scrub.
 * @param object $object_config The configuration object for the object.
 * @param string $object_type   The type of the object. Defaults to 'post'.
 *
 * @return void
 */
function scrub_object_by_type( int $object_id, object $object_config, string $object_type = 'post' ) {
	switch ( $object_type ) {
		case 'user':
			$table = $wpdb->users;
			$pk	   = 'ID';
			break;

		case 'term':
			$table = $wpdb->terms;
			$pk	   = 'term_id';
			break;

		case 'post':
		default:
			$table = $wpdb->posts;
			$pk	   = 'ID';
			break;
	}

	$new_data = [];

	foreach ( $object_config->fields as $field ) {
		$new_data[ $field->name ] = Helpers\get_field_data_by_action( $field );
	}

	$wpdb->update( $table, $new_data, [ $pk => $object_id ] );

	foreach ( $config->meta_fields as $meta_field ) {
		Helpers\scrub_meta_field( $post_id, $meta_field, 'post' );
	}
}
