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
		$file = fopen( __DIR__ . '/data/users.csv', 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions

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

		fclose( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
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

	$query = "SELECT ID
		FROM {$wpdb->users}";

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	return $wpdb->get_col( $query );
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

	$query = "SELECT ID
		FROM {$wpdb->posts}
		WHERE post_type = %s";

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	return $wpdb->get_col( $wpdb->prepare( $query, $post_type ) );
}

/**
 * Retrieves an array of all revision IDs of a given post type.
 *
 * @param array $post_ids The post IDs to retrieve revision IDs for.
 *
 * @return array An array of revision IDs.
 */
function get_all_revision_ids_from_post_ids( $post_ids ) {
	global $wpdb;

	$post_ids   = array_map( 'esc_sql', $post_ids );
	$post_ids   = array_map( 'intval', $post_ids );
	$ids_string = implode( ',', $post_ids );

	$query = "SELECT *
		FROM wp_posts
		WHERE post_type = 'revision'
		AND post_parent IN (${ids_string});";

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	return $wpdb->get_col( $query );
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

	$query = "SELECT term_id
		FROM {$wpdb->term_taxonomy}
		WHERE taxonomy = %s";

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	return $wpdb->get_col( $wpdb->prepare( $query, $taxonomy ) );
}

/**
 * Get fake data based on the specified type.
 *
 * @param string $type The type of fake data to generate.
 *
 * @return mixed The generated fake data.
 */
function get_fake_data( string $type ): mixed {
	static $faker;

	if ( ! $faker ) {
		$faker = \Faker\Factory::create();
	}

	try {
		$data = $faker->$type();
	} catch ( \Exception $e ) {
		$data = new \WP_Error( 'invalid_faker_type', 'Invalid faker type.' );
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
			$data = get_fake_data( $field->faker_type );
			break;
	}

	return $data;
}

/**
 * Scrub the meta field.
 *
 * @param int    $object_id     The object ID.
 * @param object $field_config The field configuration object.
 * @param string $object_type  The object type.
 *
 * @return bool|\WP_Error The result of the scrub operation. True on success, false or WP_Error on failure.
 */
function scrub_meta_field( int $object_id, object $field_config, string $object_type ): bool|\WP_Error {
	global $wpdb;

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
		return false !== $wpdb->delete(
			$table,
			[
				$object_key => $object_id,
				'meta_key'  => $meta_key,
			]
		);

	} else {
		$meta_value = get_field_data_by_action( $field_config );

		if ( is_wp_error( $meta_value ) ) {
			return $meta_value;
		}

		return false !== $wpdb->update(
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
 * TODO: add error messages instead of false returns
 *
 * @return bool|\WP_Error The result of the scrub operation. True on success, false or WP_Error on failure.
 */
function scrub_object_by_type( int $object_id, object $object_config, string $object_type ): bool|\WP_Error {
	global $wpdb;

	switch ( $object_type ) {
		case 'user':
			$table = $wpdb->users;
			$pk    = 'ID';
			break;

		case 'term':
			$table = $wpdb->terms;
			$pk    = 'term_id';
			break;

		case 'revision':
		case 'post':
		default:
			$table = $wpdb->posts;
			$pk    = 'ID';
			break;
	}

	if ( ! empty( $object_config->fields ) ) {
		$new_data = [];

		foreach ( $object_config->fields as $field ) {
			$new_value = get_field_data_by_action( $field );

			if ( is_wp_error( $new_value ) ) {
				return $new_value;
			}

			if ( 'term' === $object_type && 'description' === $field->name ) {
				$wpdb->update(
					$wpdb->term_taxonomy,
					[ 'description' => $new_value ],
					[
						'term_id'  => $object_id,
						'taxonomy' => $object_config->name,
					]
				);

			} else {
				$new_data[ $field->name ] = $new_value;
			}
		}

		$wpdb->update( $table, $new_data, [ $pk => $object_id ] );
	}

	if ( 'revision' !== $object_type && ! empty( $object_config->meta_fields ) ) {

		foreach ( $object_config->meta_fields as $meta_field ) {
			$result = scrub_meta_field( $object_id, $meta_field, $object_type );

			if ( false === $result || is_wp_error( $result ) ) {
				return $result;
			}
		}
	}

	return true;
}

/**
 * Validate the scrubber configuration.
 *
 * @param array $config The scrubber configuration object.
 *
 * @return mixed
 */
function validate_scrubber_config( string $config ): mixed {
	$config_arr    = json_decode( $config, true );
	$warnings      = [];
	$errors        = [];
	$valid_actions = [ 'remove', 'replace', 'faker' ];

	if ( ! is_array( $config_arr ) ) {
		$errors[] = 'Invalid JSON configuration.';
		return ['errors' => $errors, 'warnings' => $warnings];
	}

	$config_options = array_keys( $config_arr );
	$valid_options  = [ 'post_types', 'taxonomies', 'user_data', 'options', 'custom_tables', 'truncate_tables' ];
	$scrubber_diff  = array_diff( $config_options, $valid_options );

	if ( ! empty( $scrubber_diff ) ) {
		foreach ( $scrubber_diff as $diff ) {
			$warnings[] = 'Unknown scrubber config option: ' . $diff;
		}
	}

	$valid_pt_options = [ 'name', 'fields', 'meta_fields' ];

	if ( ! empty( $config_arr['post_types'] ) ) {
		if ( ! is_array( $config_arr['post_types'] ) ) {
			$errors[] = 'Invalid post_types configuration. - Must be an array.';
		}

		foreach ( $config_arr['post_types'] as $post_type ) {
			if ( empty( $post_type['name'] ) ) {
				$errors[] = 'Invalid post_types configuration. - Missing post type name.';
			}
		}
	}

	return ['errors' => $errors, 'warnings' => $warnings];
}
