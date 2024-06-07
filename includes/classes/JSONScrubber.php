<?php
/**
 * JSONScrubber class file
 *
 * @package TenUpWPScrubber
 */

namespace TenUpWPScrubber;

use WP_CLI;

/**
 * Class JSONScrubber
 * Handles scrubbing for the JSON config.
 */
class JSONScrubber {

	/**
	 * JSON config.
	 *
	 * @var object
	 */
	protected $config;

	/**
	 * Show errors.
	 *
	 * @var bool
	 */
	protected $show_errors;

	/**
	 * Constructor.
	 *
	 * @param object $config JSON config.
	 */
	public function __construct( $config, $show_errors = true ) {
		$this->config = $config;
		$this->show_errors = $show_errors;
	}

	/**
	 * Scrub user data.
	 */
	public function scrub_users() {
		if ( empty( $this->config->user_data ) ) {
			return;
		}

		$user_ids = Helpers\get_all_user_ids();
		$progress = \WP_CLI\Utils\make_progress_bar( 'Scrubbing user data', count( $user_ids ) );

		foreach ( $user_ids as $user_id ) {
			$scrub = Helpers\scrub_object_by_type( $user_id, $this->config->user_data, 'user' );

			if ( $this->show_errors && ( false === $scrub || is_wp_error( $scrub ) ) ) {
				WP_CLI::error( "Unable to scrub user ID: {$user_id}" );
			}

			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 * Scrub post types.
	 */
	public function scrub_post_types() {
		if ( empty( $this->config->post_types ) ) {
			return;
		}

		foreach ( $this->config->post_types as $post_type ) {
			$post_ids = Helpers\get_all_post_ids_of_post_type( $post_type->name );
			$progress = \WP_CLI\Utils\make_progress_bar( "Scrubbing {$post_type->name} posts", count( $post_ids ) );

			foreach ( $post_ids as $post_id ) {
				$scrub = Helpers\scrub_object_by_type( $post_id, $post_type, 'post' );

				if ( $this->show_errors && ( false === $scrub || is_wp_error( $scrub ) ) ) {
					WP_CLI::error( "Unable to scrub post ID: {$post_id}" );
				}

				$progress->tick();
			}

			$progress->finish();

			$revision_ids = Helpers\get_all_revision_ids_from_post_ids( $post_ids );
			$progress     = \WP_CLI\Utils\make_progress_bar( "Scrubbing {$post_type->name} revisions", count( $revision_ids ) );

			foreach ( $revision_ids as $revision_id ) {
				$scrub = Helpers\scrub_object_by_type( $revision_id, $post_type, 'revision' );

				if ( $this->show_errors && ( false === $scrub || is_wp_error( $scrub ) ) ) {
					WP_CLI::error( "Unable to scrub revision ID: {$revision_id}" );
				}

				$progress->tick();
			}

			$progress->finish();
		}
	}

	/**
	 * Scrub taxonomies.
	 */
	public function scrub_taxonomies() {
		if ( empty( $this->config->taxonomies ) ) {
			return;
		}

		foreach ( $this->config->taxonomies as $taxonomy ) {
			$term_ids = Helpers\get_all_term_ids_of_taxonomy( $taxonomy->name );
			$progress = \WP_CLI\Utils\make_progress_bar( "Scrubbing {$taxonomy->name} terms", count( $term_ids ) );

			foreach ( $term_ids as $term_id ) {
				$scrub = Helpers\scrub_object_by_type( $term_id, $taxonomy, 'term' );

				if ( $this->show_errors && ( false === $scrub || is_wp_error( $scrub ) ) ) {
					WP_CLI::error( "Unable to scrub term ID: {$term_id}" );
				}

				$progress->tick();
			}

			$progress->finish();
		}
	}

	/**
	 * Scrub options.
	 */
	public function scrub_options() {
		global $wpdb;

		if ( empty( $this->config->options ) ) {
			return;
		}

		$progress = \WP_CLI\Utils\make_progress_bar( 'Scrubbing options', count( $this->config->options ) );

		foreach ( $this->config->options as $option ) {

			if ( 'remove' === $option->action ) {
				$wpdb->delete( $wpdb->options, [ 'option_name' => $option->name ] );

			} else {
				$wpdb->update(
					$wpdb->options,
					[ 'option_value' => Helpers\get_field_data_by_action( $option ) ],
					[ 'option_name' => $option->name ]
				);
			}

			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 * Scrub custom tables.
	 */
	public function scrub_custom_tables() {
		global $wpdb;

		if ( empty( $this->config->custom_tables ) ) {
			return;
		}

		foreach ( $this->config->custom_tables as $table ) {
			$name     = $table->name;
			$pk       = $table->primary_key;
			$query    = "SELECT {$pk} FROM {$name}";
			$ids      = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$progress = \WP_CLI\Utils\make_progress_bar( "Scrubbing {$name} table", count( $ids ) );

			foreach ( $ids as $id ) {
				$new_data = [];

				foreach ( $table->columns as $field ) {
					$new_data[ $field->name ] = Helpers\get_field_data_by_action( $field );
				}

				$wpdb->update( $name, $new_data, [ $pk => $id ] );

				$progress->tick();
			}

			$progress->finish();
		}
	}

	/**
	 * Truncate tables.
	 */
	public function truncate_tables() {
		global $wpdb;

		if ( empty( $this->config->truncate_tables ) ) {
			return;
		}

		$progress = \WP_CLI\Utils\make_progress_bar( 'Truncating tables', count( $this->config->truncate_tables ) );

		foreach ( $this->config->truncate_tables as $table ) {
			$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL
			$progress->tick();
		}

		$progress->finish();
	}
}
