<?php

use WP_Mock\Tools\TestCase;
use TenUpWPScrubber\JSONScrubber;

/**
 * Class JSONScrubberTests
 *
 * This class contains unit tests for the JSONScrubber class.
 */
final class JSONScrubberTests extends TestCase {

	/**
	 * Test the instance creation of JSONScrubber.
	 */
	public function test_instance() {
		$config = new stdClass();
		$config->foo = 'bar';

		$instance    = new JSONScrubber( $config, false );
		$config_prop = $this->getInaccessibleProperty( $instance, 'config' )->getValue( $instance );
		$show_errors = $this->getInaccessibleProperty( $instance, 'show_errors' )->getValue( $instance );

		$this->assertEquals( $config->foo, $config_prop->foo );
		$this->assertFalse( $show_errors );
	}


	/**
	 * Test case for the `get_field_data_by_action` method.
	 * Tests the `replace` action.
	 */
	public function test_get_field_data_by_action_replace() {
		$scrubber = new JSONScrubber( new stdClass(), false );
		$method   = $this->getInaccessibleMethod( $scrubber, 'get_field_data_by_action' );
		$_field   = [
			'name'   => 'display_name',
			'action' => 'replace',
			'value'  => 'Jane Doe'
		];

		$field  = json_decode( json_encode( $_field ) );
		$result = $method->invokeArgs( $scrubber, [ $field ] );

		$this->assertEquals( 'Jane Doe', $result );
	}

	/**
	 * Test case for the `get_field_data_by_action` method.
	 * Tests the `remove` action.
	 */
	public function test_get_field_data_by_action_remove() {
		$scrubber = new JSONScrubber( new stdClass(), false );
		$method   = $this->getInaccessibleMethod( $scrubber, 'get_field_data_by_action' );
		$_field   = [
			'name'   => 'display_name',
			'action' => 'remove',
		];

		$field  = json_decode( json_encode( $_field ) );
		$result = $method->invokeArgs( $scrubber, [ $field ] );

		$this->assertEquals( '', $result );
	}

	/**
	 * Test case for the `get_field_data_by_action` method.
	 * Tests the `faker` action.
	 */
	public function test_get_field_data_by_action_faker() {
		$scrubber = new JSONScrubber( new stdClass(), false );
		$method   = $this->getInaccessibleMethod( $scrubber, 'get_field_data_by_action' );
		$_field   = [
			'name'       => 'display_name',
			'action'     => 'faker',
			'faker_type' => 'randomDigit'
		];

		$field  = json_decode( json_encode( $_field ) );
		$result = $method->invokeArgs( $scrubber, [ $field ] );

		$this->assertIsInt( $result );
	}

	/**
	 * Test case for the `scrub_object_by_type` method.
	 * Tests the `user` type.
	 */
	public function test_scrub_object_by_type_user() {
		global $wpdb;

		$scrubber = new JSONScrubber( new stdClass(), false );
		$method   = $this->getInaccessibleMethod( $scrubber, 'scrub_object_by_type' );
		$_config  = [
			'fields' => [
				[
					'name'   => 'display_name',
					'action' => 'replace',
					'value'  => 'Jane Doe'
				]
			],
		];

		$wpdb = Mockery::mock('WPDB');
		$wpdb->users = 'wp_users';

		WP_Mock::userFunction('is_wp_error')
			->once()
			->andReturn( false );

		$wpdb->allows( 'update' )
			->once()
			->with(
				'wp_users',
				['display_name' => 'Jane Doe'],
				[ 'ID' => 123 ]
			);


		$config = json_decode( json_encode( $_config ) );
		$result = $method->invokeArgs( $scrubber, [ 123, $config, 'user' ] );

		$this->assertTrue( $result );
		$this->assertConditionsMet();
	}
}