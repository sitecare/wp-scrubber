<?php
/**
 * JSONValidation class file
 *
 * @package TenUpWPScrubber
 */

namespace TenUpWPScrubber;

use WP_CLI;

/**
 * Class JSONValidation
 * Handles validation for the JSON config.
 */
class JSONValidation {

	/**
	 * @var object
	 */
	protected $config;

	/**
	 * Constructor.
	 *
	 * @param object $config JSON config.
	 */
	public function __construct( $config ) {
		$this->config = $config;

		$this->validate_user_config();
		$this->validate_post_types_config();
		$this->validate_taxonomies_config();
		$this->validate_options_config();
		$this->validate_custom_tables_config();
		$this->validate_truncate_config();
	}

	/**
	 * Validate user data configuration.
	 */
	protected function validate_user_config() {
		if ( empty( $config->user_data ) ) {
			return;
		}

		if ( ! is_object( $config->user_data ) ) {
			$errors[] = 'Invalid user_data configuration - Must be an object.';

		} else {
			$errors = array_merge( $errors, validate_object_config( $config->user_data, 'user_data' ) );
		}

	}

	/**
	 * Validate post types configuration.
	 */
	protected function validate_post_types_config() {
		if ( empty( $config->post_types ) ) {
			return;
		}

		if ( ! is_array( $config->post_types ) ) {
			$errors[] = 'Invalid post_types configuration - Must be an array.';
		}

		foreach ( $config->post_types as $post_type ) {
			if ( ! is_object( $post_type ) ) {
				$errors[] = 'Invalid post_type configuration - Must be an object.';

			} else {
				if ( empty( $post_type->name ) ) {
					$errors[] = 'Invalid post_type configuration - Missing post type name.';
				}

				$errors = array_merge( $errors, validate_object_config( $post_type, 'post_type' ) );
			}
		}
	}

	/**
	 * Validate taxonomies configuration.
	 */
	protected function validate_taxonomies_config() {
		if ( empty( $config->taxonomies ) ) {
			return;
		}

		if ( ! is_array( $config->taxonomies ) ) {
			$errors[] = 'Invalid taxonomies configuration - Must be an array.';
		}

		foreach ( $config->taxonomies as $taxonomy ) {
			if ( ! is_object( $taxonomy ) ) {
				$errors[] = 'Invalid taxonomy configuration - Must be an object.';

			} else {
				if ( empty( $taxonomy->name ) ) {
					$errors[] = 'Invalid taxonomy configuration - Missing taxonomy name.';
				}

				$errors = array_merge( $errors, validate_object_config( $taxonomy, 'taxonomy' ) );
			}

		}
	}

	/**
	 * Validate options configuration.
	 */
	protected function validate_options_config() {
		if ( empty( $config->options ) ) {
			return;
		}

		if ( ! is_array( $config->options ) ) {
			$errors[] = 'Invalid options configuration - Must be an array.';
		}

		foreach ( $config->options as $option ) {
			if ( ! is_object( $option ) ) {
				$errors[] = 'Invalid option configuration - Must be an object.';

			} else {
				$errors = array_merge( $errors, validate_field_config( $option, 'option' ) );
			}
		}
	}

	/**
	 * Validate custom tables configuration.
	 */
	protected function validate_custom_tables_config() {
		if ( empty( $config->custom_tables ) ) {
			return;

		}

		if ( ! is_array( $config->custom_tables ) ) {
			$errors[] = 'Invalid custom_tables configuration - Must be an array.';
		}

		foreach ( $config->custom_tables as $custom_table ) {
			if ( ! is_object( $custom_table ) ) {
				$errors[] = 'Invalid custom_table configuration - Must be an object.';

			} else {
				if ( empty( $custom_table->name ) ) {
					$errors[] = 'Invalid custom_table configuration - Missing table name.';
				}

				if ( empty( $custom_table->primary_key ) ) {
					$errors[] = 'Invalid custom_table configuration - Missing primary key.';
				}

				$errors = array_merge( $errors, validate_object_config( $custom_table, 'custom_table' ) );
			}
		}
	}

	/**
	 * Validate truncate configuration.
	 */
	protected function validate_truncate_config() {
		if ( empty( $config->truncate_tables ) ) {
			return;
		}

		if ( ! is_array( $config->truncate_tables ) ) {
			$errors[] = 'Invalid truncate_tables configuration - Must be an array.';
		}

		foreach ( $config->truncate_tables as $truncate_table ) {
			if ( ! is_string( $truncate_table ) ) {
				$errors[] = 'Invalid table in truncate_tables - Must be a string.';
			}
		}
	}

}
