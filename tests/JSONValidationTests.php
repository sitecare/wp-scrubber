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

	/**
	 * Tests the validate_user_config method.
	 * Tests case when no errors are present.
	 */
	public function test_validate_user_config_no_errors() {
		$_config = [
			'user_data' => [
				'fields' => [
					[
						'name'   => 'display_name',
						'action' => 'replace',
						'value'  => 'John Doe',
					],
				],
			],
		];
		$config = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertEmpty( $errors );
	}

	/**
	 * Tests the validate_user_config method.
	 * Tests case when config is not an object.
	 */
	public function test_validate_user_config_object_error() {
		$config = new stdClass();
		$config->user_data = [ 123 ];

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid user_data configuration - Must be an object.', $errors[0] );
	}
}
