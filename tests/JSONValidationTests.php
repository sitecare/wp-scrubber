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
	 * Test case for validating field configuration with no errors.
	 */
	public function test_validate_field_config_no_errors() {
		$validator = new JSONValidation( new stdClass() );
		$method    = $this->getInaccessibleMethod( $validator, 'validate_field_config' );

		$_config = [
			'name'   => 'display_name',
			'action' => 'replace',
			'value'  => 'John Doe',
		];
		$config  = json_decode( json_encode( $_config ) );

		$method->invokeArgs( $validator, [ $config, 'test-parent' ] );
		$errors = $validator->get_errors();

		$this->assertEmpty( $errors );
	}

	/**
	 * Test case for validating field configuration with missing name.
	 */
	public function test_validate_field_config_missing_name() {
		$validator = new JSONValidation( new stdClass() );
		$method    = $this->getInaccessibleMethod( $validator, 'validate_field_config' );

		$_config = [
			'action' => 'replace',
			'value'  => 'John Doe',
		];
		$config  = json_decode( json_encode( $_config ) );

		$method->invokeArgs( $validator, [ $config, 'test-parent' ] );
		$errors = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid test-parent configuration - Missing field name.', $errors[0] );
	}

	/**
	 * Test case for validating field configuration with missing action.
	 */
	public function test_validate_field_config_missing_action() {
		$validator = new JSONValidation( new stdClass() );
		$method    = $this->getInaccessibleMethod( $validator, 'validate_field_config' );

		$_config = [
			'name'  => 'display_name',
			'value' => 'John Doe',
		];
		$config  = json_decode( json_encode( $_config ) );

		$method->invokeArgs( $validator, [ $config, 'test-parent' ] );
		$errors = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid test-parent configuration - Missing field action.', $errors[0] );
	}

	/**
	 * Test case for validating field configuration with missing replace.
	 */
	public function test_validate_field_config_missing_replace() {
		$validator = new JSONValidation( new stdClass() );
		$method    = $this->getInaccessibleMethod( $validator, 'validate_field_config' );

		$_config = [
			'name'   => 'display_name',
			'action' => 'replace'
		];
		$config  = json_decode( json_encode( $_config ) );

		$method->invokeArgs( $validator, [ $config, 'test-parent' ] );
		$errors = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid test-parent configuration - Missing replace value.', $errors[0] );
	}

	/**
	 * Test case for validating field configuration with missing faker_type.
	 */
	public function test_validate_field_config_missing_faker_type() {
		$validator = new JSONValidation( new stdClass() );
		$method    = $this->getInaccessibleMethod( $validator, 'validate_field_config' );

		$_config = [
			'name'   => 'display_name',
			'action' => 'faker',
		];
		$config  = json_decode( json_encode( $_config ) );

		$method->invokeArgs( $validator, [ $config, 'test-parent' ] );
		$errors = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid test-parent configuration - Missing faker type.', $errors[0] );
	}

	/**
	 * Test case for validating object configuration with no errors.
	 */
	public function test_validate_object_config_no_errors() {
		$validator = new JSONValidation( new stdClass() );
		$method    = $this->getInaccessibleMethod( $validator, 'validate_object_config' );

		$_config = [
			'fields'      => [
				[
					'name'   => 'display_name',
					'action' => 'replace',
					'value'  => 'John Doe',
				],
			],
			'meta_fields' => [
				[
					'name'       => 'meta_field',
					'action'     => 'faker',
					'faker_type' => 'text',
				],
			],
			'columns'     => [
				[
					'name'       => 'col',
					'action'     => 'remove',
				],
			],
		];
		$config  = json_decode( json_encode( $_config ) );

		$method->invokeArgs( $validator, [ $config, 'test-parent' ] );
		$errors = $validator->get_errors();

		$this->assertEmpty( $errors );
	}

	/**
	 * Test case for validating object configuration with array errors.
	 */
	public function test_validate_object_config_array_errors() {
		$validator = new JSONValidation( new stdClass() );
		$method    = $this->getInaccessibleMethod( $validator, 'validate_object_config' );

		$_config = [
			'fields'      => 1,
			'meta_fields' => 1,
			'columns'     => 1,
		];
		$config  = json_decode( json_encode( $_config ) );

		$method->invokeArgs( $validator, [ $config, 'test-parent' ] );
		$errors = $validator->get_errors();

		$this->assertCount( 3, $errors );
		$this->assertEquals( 'Invalid test-parent fields configuration - Must be an array.', $errors[0] );
		$this->assertEquals( 'Invalid test-parent meta_fields configuration - Must be an array.', $errors[1] );
		$this->assertEquals( 'Invalid test-parent columns configuration - Must be an array.', $errors[2] );
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
