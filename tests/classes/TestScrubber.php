<?php
/**
 * TestScrubber class file
 *
 * @package TenUpWPScrubber
 */

use TenUpWPScrubber\JSONScrubber;

/**
 * Class TestScrubber
 * This class is used to test the JSONScrubber class.
 */
class TestScrubber extends JSONScrubber {

	/**
	 * Scrubs an object by type.
	 * This is an override method specifically for testing purposes.
	 * It allows us to test the main scrub functions in isolation.
	 *
	 * @param int    $object_id Object ID.
	 * @param object $object_config Object config.
	 * @param string $object_type Object type.
	 * @return bool|\WP_Error
	 */
	protected function scrub_object_by_type( int $object_id, object $object_config, string $object_type ): bool|\WP_Error {
		return true;
	}
}
