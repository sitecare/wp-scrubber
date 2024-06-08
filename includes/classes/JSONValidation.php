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
	 * The JSON config.
	 *
	 * @var object
	 */
	protected $config;

	/**
	 * Validation errors.
	 *
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
			$this->validate_object_config( $this->config->user_data, 'user_data' );
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

				$this->validate_object_config( $post_type, 'post_type' );
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

				$this->validate_object_config( $taxonomy, 'taxonomy' );
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
				$this->validate_field_config( $option, 'option' );
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

				$this->validate_object_config( $custom_table, 'custom_table' );
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

	/**
	 * Validate the object configuration.
	 *
	 * @param object $obj_config The object configuration object.
	 * @param string $parent     The parent object name.
	 *
	 * @return void
	 */
	protected function validate_object_config( object $obj_config, string $parent ): void { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames

		if ( ! empty( $obj_config->fields ) ) {
			if ( ! is_array( $obj_config->fields ) ) {
				$this->errors[] = sprintf( 'Invalid %s fields configuration - Must be an array.', $parent );

			} else {
				foreach ( $obj_config->fields as $field ) {
					$this->validate_field_config( $field, $parent . ' field' );
				}
			}
		}

		if ( ! empty( $obj_config->meta_fields ) ) {
			if ( ! is_array( $obj_config->meta_fields ) ) {
				$this->errors[] = sprintf( 'Invalid %s meta_fields configuration - Must be an array.', $parent );

			} else {
				foreach ( $obj_config->meta_fields as $meta_field ) {
					$this->validate_field_config( $meta_field, $parent . ' meta_field' );
				}
			}
		}

		if ( ! empty( $obj_config->columns ) ) {
			if ( ! is_array( $obj_config->columns ) ) {
				$this->errors[] = sprintf( 'Invalid %s columns configuration - Must be an array.', $parent );

			} else {
				foreach ( $obj_config->columns as $column ) {
					$this->validate_field_config( $column, $parent . ' column' );
				}
			}
		}
	}

	/**
	 * Validate the field configuration.
	 *
	 * @param object $field   The field configuration object.
	 * @param string $parent  The parent field name.
	 *
	 * @return void
	 */
	protected function validate_field_config( object $field, string $parent ): void { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames

		if ( empty( $field->name ) ) {
			$this->errors[] = sprintf( 'Invalid %s configuration - Missing field name.', $parent );
		}

		if ( empty( $field->action ) ) {
			$this->errors[] = sprintf( 'Invalid %s configuration - Missing field action.', $parent );
		}

		if ( 'replace' === $field->action && ! isset( $field->value ) ) {
			$this->errors[] = sprintf( 'Invalid %s configuration - Missing replace value.', $parent );
		}

		if ( 'faker' === $field->action && empty( $field->faker_type ) ) {
			$this->errors[] = sprintf( 'Invalid %s configuration - Missing faker type.', $parent );
		}
	}
}
