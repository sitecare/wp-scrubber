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
	 * JSON config.
	 *
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

	protected function validate_user_config() {

	}

	protected function validate_post_types_config() {

	}

	protected function validate_taxonomies_config() {

	}

	protected function validate_options_config() {

	}

	protected function validate_custom_tables_config() {

	}

	protected function validate_truncate_config() {

	}

}
