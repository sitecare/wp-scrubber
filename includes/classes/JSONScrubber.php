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
	 * @param bool   $show_errors Show DB errors.
	 */
	public function __construct( $config, $show_errors = true ) {
		$this->config      = $config;
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
			$scrub = $this->scrub_object_by_type( $user_id, $this->config->user_data, 'user' );

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
				$scrub = $this->scrub_object_by_type( $post_id, $post_type, 'post' );

				if ( $this->show_errors && ( false === $scrub || is_wp_error( $scrub ) ) ) {
					WP_CLI::error( "Unable to scrub post ID: {$post_id}" );
				}

				$progress->tick();
			}

			$progress->finish();

			$revision_ids = Helpers\get_all_revision_ids_from_post_ids( $post_ids );
			$progress     = \WP_CLI\Utils\make_progress_bar( "Scrubbing {$post_type->name} revisions", count( $revision_ids ) );

			foreach ( $revision_ids as $revision_id ) {
				$scrub = $this->scrub_object_by_type( $revision_id, $post_type, 'revision' );

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
				$scrub = $this->scrub_object_by_type( $term_id, $taxonomy, 'term' );

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
					[ 'option_value' => $this->get_field_data_by_action( $option ) ],
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

			// For multisite, replace the table prefix, otherwise we need to list all tables for all sites and that's a pain.
			if ( str_starts_with( $name, 'wp_' ) ) {
				$name = preg_replace('/^wp_/', $wpdb->prefix, $name);
			}

			$pk       = $table->primary_key;
			$query    = "SELECT {$pk} FROM {$name}";
			$ids      = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$progress = \WP_CLI\Utils\make_progress_bar( "Scrubbing {$name} table", count( $ids ) );

			foreach ( $ids as $id ) {
				$new_data = [];

				foreach ( $table->columns as $field ) {
					$new_data[ $field->name ] = $this->get_field_data_by_action( $field );
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
			// For multisite, replace the table prefix, otherwise we need to list all tables for all sites and that's a pain.
			if ( str_starts_with( $table, 'wp_' ) ) {
				$table = preg_replace('/^wp_/', $wpdb->prefix, $table);
			}

			$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL
			$progress->tick();
		}

		$progress->finish();
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
	protected function scrub_object_by_type( int $object_id, object $object_config, string $object_type ) {
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
				$new_value = $this->get_field_data_by_action( $field );

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
				$result = $this->scrub_meta_field( $object_id, $meta_field, $object_type );

				if ( false === $result || is_wp_error( $result ) ) {
					return $result;
				}
			}
		}

		return true;
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
	protected function scrub_meta_field( int $object_id, object $field_config, string $object_type ) {
		global $wpdb;

		$meta_key = $field_config->name;

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
			$meta_value = $this->get_field_data_by_action( $field_config );

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
	 * Retrieves the data for a given field based on its action.
	 *
	 * @param object $field The field object.
	 *
	 * @return mixed The data for the field.
	 */
	protected function get_field_data_by_action( object $field ) {
		$data = null;

		switch ( $field->action ) {
			case 'remove':
				$data = '';
				break;

			case 'replace':
				$data = $field->value;
				break;

			case 'faker':
				$data = Helpers\get_fake_data( $field->faker_type );
				break;
		}

		return $data;
	}
}
