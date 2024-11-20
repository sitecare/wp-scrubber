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
	 * @return mixed
	 */
	protected function scrub( $modes, $args, $assoc_args ) {
		if ( ! defined( 'WP_IMPORTING' ) ) {
			define( 'WP_IMPORTING', true );
		}

		if ( ! defined( 'WP_ADMIN' ) ) {
			define( 'WP_ADMIN', true );
		}

		$defaults = apply_filters(
			'wp_scrubber_scrub_all_defaults',
			array(
				'allowed-domains'   => '',
				'allowed-emails'    => '',
				'ignore-size-limit' => '',
				'config'            => null,
				'ignore-config'     => null,
				'ignore-db-errors'  => false,
			)
		);

		$assoc_args    = wp_parse_args( $assoc_args, $defaults );
		$use_config    = ! empty( $assoc_args['config'] );
		$ignore_config = ! empty( $assoc_args['ignore-config'] );

		if ( $use_config ) {
			return $this->from_config( $args, $assoc_args );
		}

		$config_path = trailingslashit( WP_CONTENT_DIR ) . 'wp-scrubber.json';

		if ( file_exists( $config_path ) && ! $ignore_config ) {
			return $this->from_config( $args, $assoc_args );
		}

		$allowed_domains = [
			'get10up.com',
			'10up.com',
			'fueled.com',
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
	 * [--ignore-db-errors]
	 * : Ignore scrubbing errors during runtime.
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
	 * [<path>]
	 * : Path to the JSON config file relative to wp-content/ - Defaults to wp-scrubber.json
	 *
	 * @param array $args       Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments passed to the command.
	 * @return void
	 */
	protected function from_config( $args, $assoc_args ) {
		global $wpdb;

		$config_file = 'wp-scrubber.json';

		if ( ! empty( $args[0] ) ) {
			$config_file = sanitize_text_field( $args[0] );

			if ( '/' === substr( $config_file, 0, 1 ) ) {
				$config_file = substr( $config_file, 1 );
			}
		}

		$config_path = trailingslashit( WP_CONTENT_DIR ) . $config_file;
		$show_errors = empty( $assoc_args['ignore-db-errors'] );

		if ( ! file_exists( $config_path ) ) {
			WP_CLI::error( 'File does not exist: ' . $config_path );
		}

		if ( ! is_readable( $config_path ) ) {
			WP_CLI::error( 'File is not readable: ' . $config_path );
		}

		$config_json   = file_get_contents( $config_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$config        = json_decode( $config_json );
		$validation    = new JSONValidation( $config );
		$config_errors = $validation->get_errors();

		if ( ! empty( $config_errors ) ) {
			foreach ( $config_errors as $error ) {
				WP_CLI::log( WP_CLI::colorize( '%yConfig Error:%n ' ) . $error );
			}
			WP_CLI::error( 'Errors found in config file. Please correct them and try again.' );
		}

		if ( is_multisite() ) {
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				WP_CLI::log( WP_CLI::colorize( '%y' ) . 'Starting scrubbing blog ' . get_home_url() . WP_CLI::colorize( '%n' ) );
				$this->scrub_config( $config, $show_errors );
				WP_CLI::log( WP_CLI::colorize( '%y' ) . 'Finished scrubbing blog ' . get_home_url() . PHP_EOL . WP_CLI::colorize( '%n' ) );
				restore_current_blog();
			}
		} else {
			$this->scrub_config( $config, $show_errors );
		}

		WP_CLI::success( 'Scrubbing complete!' );
	}

	/**
	 * Scrub config.
	 *
	 * @param object $config      Config object.
	 * @param bool   $show_errors Show errors.
	 */
	protected function scrub_config( $config, $show_errors ) {
		$scrubber = new JSONScrubber( $config, $show_errors );
		$scrubber->scrub_users();
		$scrubber->scrub_post_types();
		$scrubber->scrub_taxonomies();
		$scrubber->scrub_options();
		$scrubber->scrub_custom_tables();
		$scrubber->truncate_tables();
	}
}
