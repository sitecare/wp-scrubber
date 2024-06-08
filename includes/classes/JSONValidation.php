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
	 * @var array
	 */
	protected $errors = [];

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
	 * Get errors.
	 *
	 * @return array
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Validate user data configuration.
	 */
	protected function validate_user_config() {
		if ( empty( $this->config->user_data ) ) {
			return;
		}

		if ( ! is_object( $this->config->user_data ) ) {
			$this->errors[] = 'Invalid user_data configuration - Must be an object.';

		} else {
			$this->errors = array_merge( $this->errors, validate_object_config( $this->config->user_data, 'user_data' ) );
		}

	}

	/**
	 * Validate post types configuration.
	 */
	protected function validate_post_types_config() {
		if ( empty( $this->config->post_types ) ) {
			return;
		}

		if ( ! is_array( $this->config->post_types ) ) {
			$this->errors[] = 'Invalid post_types configuration - Must be an array.';
		}

		foreach ( $this->config->post_types as $post_type ) {
			if ( ! is_object( $post_type ) ) {
				$this->errors[] = 'Invalid post_type configuration - Must be an object.';

			} else {
				if ( empty( $post_type->name ) ) {
					$this->errors[] = 'Invalid post_type configuration - Missing post type name.';
				}

				$this->errors = array_merge( $this->errors, validate_object_config( $post_type, 'post_type' ) );
			}
		}
	}

	/**
	 * Validate taxonomies configuration.
	 */
	protected function validate_taxonomies_config() {
		if ( empty( $this->config->taxonomies ) ) {
			return;
		}

		if ( ! is_array( $this->config->taxonomies ) ) {
			$this->errors[] = 'Invalid taxonomies configuration - Must be an array.';
		}

		foreach ( $this->config->taxonomies as $taxonomy ) {
			if ( ! is_object( $taxonomy ) ) {
				$this->errors[] = 'Invalid taxonomy configuration - Must be an object.';

			} else {
				if ( empty( $taxonomy->name ) ) {
					$this->errors[] = 'Invalid taxonomy configuration - Missing taxonomy name.';
				}

				$this->errors = array_merge( $this->errors, validate_object_config( $taxonomy, 'taxonomy' ) );
			}

		}
	}

	/**
	 * Validate options configuration.
	 */
	protected function validate_options_config() {
		if ( empty( $this->config->options ) ) {
			return;
		}

		if ( ! is_array( $this->config->options ) ) {
			$this->errors[] = 'Invalid options configuration - Must be an array.';
		}

		foreach ( $this->config->options as $option ) {
			if ( ! is_object( $option ) ) {
				$this->errors[] = 'Invalid option configuration - Must be an object.';

			} else {
				$this->errors = array_merge( $this->errors, validate_field_config( $option, 'option' ) );
			}
		}
	}

	/**
	 * Validate custom tables configuration.
	 */
	protected function validate_custom_tables_config() {
		if ( empty( $this->config->custom_tables ) ) {
			return;

		}

		if ( ! is_array( $this->config->custom_tables ) ) {
			$this->errors[] = 'Invalid custom_tables configuration - Must be an array.';
		}

		foreach ( $this->config->custom_tables as $custom_table ) {
			if ( ! is_object( $custom_table ) ) {
				$this->errors[] = 'Invalid custom_table configuration - Must be an object.';

			} else {
				if ( empty( $custom_table->name ) ) {
					$this->errors[] = 'Invalid custom_table configuration - Missing table name.';
				}

				if ( empty( $custom_table->primary_key ) ) {
					$this->errors[] = 'Invalid custom_table configuration - Missing primary key.';
				}

				$this->errors = array_merge( $this->errors, validate_object_config( $custom_table, 'custom_table' ) );
			}
		}
	}

	/**
	 * Validate truncate configuration.
	 */
	protected function validate_truncate_config() {
		if ( empty( $this->config->truncate_tables ) ) {
			return;
		}

		if ( ! is_array( $this->config->truncate_tables ) ) {
			$this->errors[] = 'Invalid truncate_tables configuration - Must be an array.';
		}

		foreach ( $this->config->truncate_tables as $truncate_table ) {
			if ( ! is_string( $truncate_table ) ) {
				$this->errors[] = 'Invalid table in truncate_tables - Must be a string.';
			}
		}
	}

}
