<?php

use WP_Mock\Tools\TestCase;
use TenUpWPScrubber\JSONScrubber;

final class JSONScrubberTests extends TestCase {

	public function test_instance() {
		$config = new stdClass();
		$config->foo = 'bar';

		$instance    = new JSONScrubber( $config, false );
		$config_prop = $this->getInaccessibleProperty( $instance, 'config' )->getValue( $instance );
		$show_errors = $this->getInaccessibleProperty( $instance, 'show_errors' )->getValue( $instance );

		$this->assertEquals( $config->foo, $config_prop->foo );
		$this->assertFalse( $show_errors );
	}

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