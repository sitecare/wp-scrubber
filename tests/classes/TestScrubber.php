<?php

use TenUpWPScrubber\JSONScrubber;

class TestScrubber extends JSONScrubber {

	/**
	 * Scrubs an object by type.
	 * This is an override method specifically for testing purposes.
	 * It allows us to test the main scrub functions in isolation.
	 *
	 * @inheritDoc
	 */
	protected function scrub_object_by_type( int $object_id, object $object_config, string $object_type ): bool|\WP_Error {
		return true;
	}
}
