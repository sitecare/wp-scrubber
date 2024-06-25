<?php
/**
 * Class file for JSONValidationTests.
 *
 * @package TenUpWPScrubber
 */

use WP_Mock\Tools\TestCase;
use TenUpWPScrubber\JSONValidation;

/**
 * Class JSONValidationTests
 *
 * This class contains unit tests for the JSONValidation class.
 */
final class JSONValidationTests extends TestCase {

	/**
	 * Test the instance creation of JSONValidation.
	 */
	public function test_instance() {
		$config      = new stdClass();
		$config->foo = 'bar';

		$instance    = new JSONValidation( $config);
		$config_prop = $this->getInaccessibleProperty( $instance, 'config' )->getValue( $instance );

		$this->assertEquals( $config->foo, $config_prop->foo );
		$this->assertEquals( [], $instance->get_errors() );
	}
}
