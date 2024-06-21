<?php

use WP_Mock\Tools\TestCase;
use TenUpWPScrubber\JSONScrubber;

class JSONScrubberTests extends TestCase {

	public function test_instance() {
		$config = new stdClass();
		$config->foo = 'bar';

		$instance    = new JSONScrubber( $config, false );
		$config_prop = $this->getInaccessibleProperty( $instance, 'config' )->getValue( $instance );
		$show_errors = $this->getInaccessibleProperty( $instance, 'show_errors' )->getValue( $instance );

		$this->assertEquals( $config->foo, $config_prop->foo );
		$this->assertFalse( $show_errors );
	}

	protected function test_scrub_object_by_type() {
		$class    = new ReflectionClass( 'TenUpWPScrubber\JSONScrubber' );
		$method   = $class->getMethod( 'scrub_object_by_type' );
		$scrubber = new TenUpWPScrubber\JSONScrubber( new stdClass(), false );
		$config   = new stdClass();

		$result = $method->invokeArgs( $scrubber, [ 123, $config, 'user' ] );

		var_dump( $results );
	}
}