<?php
/**
 * Main WP CLI command integration
 *
 * @package TenUpWPScrubber
 */

namespace TenUpWPScrubber;

use WP_CLI;
use WP_CLI_Command;

/**
 * Register migration commands.
 */
class Command extends WP_CLI_Command {


	/**
	 * Run scrubbing functions.
	 *
	 * @param array $modes      Areas to scrub
	 * @param array $args       Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments passed to the command.
	 * @return void
	 */
	protected function scrub( $modes, $args, $assoc_args ) {

		define( 'WP_IMPORTING', true );
		define( 'WP_ADMIN', true );

		$defaults = apply_filters(
			'wp_scrubber_scrub_all_defaults',
			array(
				'allowed-domains'   => '',
				'allowed-emails'    => '',
				'ignore-size-limit' => '',
			)
		);

		$assoc_args = wp_parse_args( $assoc_args, $defaults );

		$allowed_domains = [
			'get10up.com',
			'10up.com',
		];

		$allowed_emails = [];

		// Add additional email domains which should not be scrubbed.
		if ( ! empty( $assoc_args['allowed-domains'] ) ) {
			$allowed_domains = array_merge( $allowed_domains, explode( ',', $assoc_args['allowed-domains'] ) );
		}

		// Add user emails which should not be scrubbed.
		if ( ! empty( $assoc_args['allowed-emails'] ) ) {
			$allowed_emails = array_merge( $allowed_emails, explode( ',', $assoc_args['allowed-emails'] ) );
		}

		do_action( 'wp_scrubber_before_scrub', $args, $assoc_args );

		// Check the environment. Do not allow
		if ( 'production' === wp_get_environment_type() && ! apply_filters( 'wp_scrubber_allow_on_production', false ) ) {
			WP_CLI::error( 'This command cannot be run on a production environment.' );
		}

		// Limit the plugin on sites with large database sizes.
		$size_limit = apply_filters( 'wp_scrubber_db_size_limit', 2000 );
		if ( $size_limit < Helpers\get_database_size() && empty( $assoc_args['ignore-size-limit'] ) ) {
			WP_CLI::error( "This database is larger than {$size_limit}MB. Ignore this warning with `--ignore-size-limit`" );
		}

		// Run through the scrubbing process.
		if ( in_array( 'users', $modes, true ) ) {
			Helpers\scrub_users( $allowed_domains, $allowed_emails, 'WP_CLI::log' );
		}

		if ( in_array( 'comments', $modes, true ) ) {
			Helpers\scrub_comments( 'WP_CLI::log' );
		}

		// Flush the cache.
		wp_cache_flush();

		do_action( 'wp_scrubber_after_scrub', $args, $assoc_args );
	}

	/**
	 * Run all scrubbing functions.
	 *
	 * ## OPTIONS
	 *
	 * [--allowed-domains]
	 * : Comma separated list of email domains. Any WordPress user with this email domain will be ignored by the scrubbing scripts. 10up.com and get10up.com are ignored by default.
	 *
	 * [--allowed-emails]
	 * : Comma separated list of email addresses. Any WordPress user with this email will be ignored by the scrubbing scripts.
	 *
	 * [--ignore-size-limit]
	 * : Ignore the database size limit.
	 *
	 * @param array $args       Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments passed to the command.
	 * @return void
	 */
	public function all( $args, $assoc_args ) {
		$this->scrub( [ 'users', 'comments' ], $args, $assoc_args );
	}

	/**
	 * Run user scrubbing functions.
	 *
	 * ## OPTIONS
	 *
	 * [--allowed-domains]
	 * : Comma separated list of email domains. Any WordPress user with this email domain will be ignored by the scrubbing scripts. 10up.com and get10up.com are ignored by default.
	 *
	 * [--allowed-emails]
	 * : Comma separated list of email addresses. Any WordPress user with this email will be ignored by the scrubbing scripts.
	 *
	 * [--ignore-size-limit]
	 * : Ignore the database size limit.
	 *
	 * @param array $args       Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments passed to the command.
	 * @return void
	 */
	public function users( $args, $assoc_args ) {
		$this->scrub( [ 'users' ], $args, $assoc_args );
	}

	/**
	 * Run comment scrubbing functions.
	 *
	 * ## OPTIONS
	 *
	 * [--ignore-size-limit]
	 * : Ignore the database size limit.
	 *
	 * @param array $args       Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments passed to the command.
	 * @return void
	 */
	public function comments( $args, $assoc_args ) {
		$this->scrub( [ 'comments' ], $args, $assoc_args );
	}

	/**
	 * Run scrubbing functions defined in json config file.
	 *
	 * ## OPTIONS
	 *
	 * [--ignore-size-limit]
	 * : Ignore the database size limit.
	 *
	 * @param array $args       Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments passed to the command.
	 * @return void
	 *
	 * @alias from-config
	 */
	public function from_config( $args, $assoc_args ) {
		global $wpdb;

		// TODO: Allow path override in args
		// TODO: handle size limit arg

		$config_path = trailingslashit( WP_CONTENT_DIR ) . 'wp-scrubber.json';

		if ( ! file_exists( $config_path ) ) {
			WP_CLI::error( 'Unable to locate wp-scrubber.json in the wp-content/ directory.' );
		}

		if ( ! is_readable( $config_path ) ) {
			WP_CLI::error( 'The wp-scrubber.json file is not readable, please check/update the file permissions.' );
		}

		$config_json = file_get_contents( $config_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$config      = json_decode( $config_json );

		// TODO: Validate config before continuing

		if ( ! empty( $config->user_data ) ) {
			WP_CLI::log( 'Scrubbing user data' );

			$user_ids = Helpers\get_all_user_ids();

			foreach ( $user_ids as $user_id ) {
				Helpers\scrub_object_by_type( $user_id, $config->user_data, 'user' );
			}
		}

		if ( ! empty( $config->post_types ) ) {
			WP_CLI::log( 'Scrubbing post type data' );

			foreach ( $config->post_types as $post_type ) {
				$post_ids = Helpers\get_all_post_ids_of_post_type( $post_type->name );

				foreach ( $post_ids as $post_id ) {
					Helpers\scrub_object_by_type( $post_id, $post_type, 'post' );
				}

				// TODO: Handle post revisions?
			}
		}

		if ( ! empty( $config->taxonomies ) ) {
			WP_CLI::log( 'Scrubbing taxonomy data' );

			foreach ( $config->taxonomies as $taxonomy ) {
				$term_ids = Helpers\get_all_term_ids_of_taxonomy( $taxonomy->name );

				foreach ( $term_ids as $term_id ) {
					Helpers\scrub_object_by_type( $term_id, $taxonomy, 'term' );
				}

				// TODO: Handle term_taxonomy fields?
			}
		}

		if ( ! empty( $config->truncate_tables ) ) {
			WP_CLI::log( 'Truncating tables' );

			foreach ( $config->truncate_tables as $table ) {
				// phpcs:ignore WordPress.DB.PreparedSQL
				$wpdb->query( "TRUNCATE TABLE {$table}" );
			}
		}

		if ( ! empty( $config->options ) ) {
			WP_CLI::log( 'Scrubbing option data' );

			foreach ( $config->options as $option ) {

				// TODO: Use wpdb for direct queries?
				if ( 'remove' === $option->action ) {
					$wpdb->delete( $wpdb->options, [ 'option_name' => $option->name ] );
				} else {
					$wpdb->update(
						$wpdb->options,
						[ 'option_value' => Helpers\get_field_data_by_action( $option ) ],
						[ 'option_name' => $option->name ]
					);
				}
			}
		}

		if ( ! empty( $config->custom_tables ) ) {
			WP_CLI::log( 'Scrubbing custom table data' );

			foreach ( $config->custom_tables as $table ) {
				$name  = $table->name;
				$pk    = $table->primary_key;
				$query = "SELECT {$pk} FROM {$name}";
				$ids   = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				foreach ( $ids as $id ) {
					$new_data = [];

					foreach ( $table->columns as $field ) {
						$new_data[ $field->name ] = \TenUpWPScrubber\Helpers\get_field_data_by_action( $field );
					}

					$wpdb->update( $name, $new_data, [ $pk => $id ] );
				}
			}
		}

		WP_CLI::success( 'Scrubbing complete!' );
	}
}
