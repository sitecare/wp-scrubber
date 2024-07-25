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
final class JSONValidationTest extends TestCase {

	/**
	 * Test the instance creation of JSONValidation.
	 */
	public function test_instance() {
		$config      = new stdClass();
		$config->foo = 'bar';

		$instance    = new JSONValidation( $config );
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
			'action' => 'replace',
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
					'name'   => 'col',
					'action' => 'remove',
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
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertEmpty( $errors );
	}

	/**
	 * Tests the validate_user_config method.
	 * Tests case when config is not an object.
	 */
	public function test_validate_user_config_object_error() {
		$config            = new stdClass();
		$config->user_data = [ 123 ];

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid user_data configuration - Must be an object.', $errors[0] );
	}

	/**
	 * Test case for validating post types configuration with no errors.
	 */
	public function test_validate_post_types_config_no_errors() {
		$_config = [
			'post_types' => [
				[
					'name'   => 'post',
					'fields' => [
						[
							'name'       => 'post_content',
							'action'     => 'faker',
							'faker_type' => 'text',
						],
					],
				],
			],
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertEmpty( $errors );
	}

	/**
	 * Test case for validating post types configuration with array error.
	 */
	public function test_validate_post_types_config_array_error() {
		$_config = [
			'post_types' => 1,
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid post_types configuration - Must be an array.', $errors[0] );
	}

	/**
	 * Test case for validating post types configuration with object error.
	 */
	public function test_validate_post_types_config_object_error() {
		$_config = [
			'post_types' => [
				1,
			],
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid post_type configuration - Must be an object.', $errors[0] );
	}

	/**
	 * Test case for validating post types configuration with object error.
	 */
	public function test_validate_post_types_config_name_error() {
		$_config = [
			'post_types' => [
				[
					'fields' => [],
				],
			],
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid post_type configuration - Missing post type name.', $errors[0] );
	}

	/**
	 * Test case for validating taxonomies configuration with no errors.
	 */
	public function test_validate_taxonomies_config_no_errors() {
		$_config = [
			'taxonomies' => [
				[
					'name'   => 'test_tax',
					'fields' => [
						[
							'name'       => 'name',
							'action'     => 'faker',
							'faker_type' => 'text',
						],
					],
				],
			],
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertEmpty( $errors );
	}

	/**
	 * Test case for validating taxonomies configuration with array error.
	 */
	public function test_validate_taxonomies_config_array_error() {
		$_config = [
			'taxonomies' => 1,
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid taxonomies configuration - Must be an array.', $errors[0] );
	}

	/**
	 * Test case for validating taxonomies configuration with object error.
	 */
	public function test_validate_taxonomies_config_object_error() {
		$_config = [
			'taxonomies' => [ 1 ],
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid taxonomy configuration - Must be an object.', $errors[0] );
	}

	/**
	 * Test case for validating taxonomies configuration with object error.
	 */
	public function test_validate_taxonomies_config_name_error() {
		$_config = [
			'taxonomies' => [
				[
					'fields' => [],
				],
			],
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid taxonomy configuration - Missing taxonomy name.', $errors[0] );
	}

	/**
	 * Test case for validating options configuration with no errors.
	 */
	public function test_validate_options_config_no_errors() {
		$_config = [
			'options' => [
				[
					'name'   => 'test_option',
					'action' => 'replace',
					'value'  => 'foobar',
				],
			],
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertEmpty( $errors );
	}

	/**
	 * Test case for validating options configuration with an array error.
	 */
	public function test_validate_options_config_array_error() {
		$_config = [
			'options' => 1,
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid options configuration - Must be an array.', $errors[0] );
	}

	/**
	 * Test case for validating options configuration with an object error.
	 */
	public function test_validate_options_config_object_error() {
		$_config = [
			'options' => [ 1 ],
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid option configuration - Must be an object.', $errors[0] );
	}

	/**
	 * Test case for validating custom tables configuration with no errors.
	 */
	public function test_validate_custom_tables_config_no_errors() {
		$_config = [
			'custom_tables' => [
				[
					'name'        => 'custom_table_name',
					'primary_key' => 'id',
					'columns'     => [
						[
							'name'   => 'col',
							'action' => 'remove',
						],
					],
				],
			],
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertEmpty( $errors );
	}

	/**
	 * Test case for validating custom tables configuration with an array error.
	 */
	public function test_validate_custom_tables_config_array_error() {
		$_config = [
			'custom_tables' => 1,
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid custom_tables configuration - Must be an array.', $errors[0] );
	}

	/**
	 * Test case for validating custom tables configuration with an object error.
	 */
	public function test_validate_custom_tables_config_object_error() {
		$_config = [
			'custom_tables' => [ 1 ],
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid custom_table configuration - Must be an object.', $errors[0] );
	}

	/**
	 * Test case for validating custom tables configuration with an name error.
	 */
	public function test_validate_custom_tables_config_name_error() {
		$_config = [
			'custom_tables' => [
				[
					'primary_key' => 'id',
					'columns'     => [
						[
							'name'   => 'col',
							'action' => 'remove',
						],
					],
				],
			],
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid custom_table configuration - Missing table name.', $errors[0] );
	}

	/**
	 * Test case for validating custom tables configuration with an key error.
	 */
	public function test_validate_custom_tables_config_key_error() {
		$_config = [
			'custom_tables' => [
				[
					'name'    => 'custom_table_name',
					'columns' => [
						[
							'name'   => 'col',
							'action' => 'remove',
						],
					],
				],
			],
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid custom_table configuration - Missing primary key.', $errors[0] );
	}

	/**
	 * Test case for validating truncate configuration with no errors.
	 */
	public function test_validate_truncate_config_no_errors() {
		$_config = [
			'truncate_tables' => [
				'table1',
				'table2',
			],
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertEmpty( $errors );
	}

	/**
	 * Test case for validating the truncate configuration with array error.
	 */
	public function test_validate_truncate_config_array_error() {
		$_config = [
			'truncate_tables' => 1,
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid truncate_tables configuration - Must be an array.', $errors[0] );
	}

	/**
	 * Test case for validating the truncate configuration with string error.
	 */
	public function test_validate_truncate_config_string_error() {
		$_config = [
			'truncate_tables' => [ 1 ],
		];
		$config  = json_decode( json_encode( $_config ) );

		$validator = new JSONValidation( $config );
		$errors    = $validator->get_errors();

		$this->assertCount( 1, $errors );
		$this->assertEquals( 'Invalid table in truncate_tables - Must be a string.', $errors[0] );
	}
}
