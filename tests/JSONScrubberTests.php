<?php

require_once __DIR__ . '/classes/TestScrubber.php';

use WP_Mock\Tools\TestCase;
use TenUpWPScrubber\JSONScrubber;
use function Patchwork\redefine;

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
	 * Test case for the `get_field_data_by_action` method.
	 * Tests the `faker` action.
	 */
	public function test_get_field_data_by_action_faker_error() {
		$scrubber = new JSONScrubber( new stdClass(), false );
		$method   = $this->getInaccessibleMethod( $scrubber, 'get_field_data_by_action' );
		$_field   = [
			'name'       => 'display_name',
			'action'     => 'faker',
			'faker_type' => 'thisIsNotAValidFakerType'
		];

		Mockery::mock( '\WP_Error' );

		$field  = json_decode( json_encode( $_field ) );
		$result = $method->invokeArgs( $scrubber, [ $field ] );

		$this->assertInstanceOf( '\WP_Error', $result );
	}

	/**
	 * Test case for the `scrub_meta_field` method.
	 * Tests the `user` object type.
	 */
	public function test_scrub_meta_field_user() {
		global $wpdb;

		$scrubber = new JSONScrubber( new stdClass(), false );
		$method   = $this->getInaccessibleMethod( $scrubber, 'scrub_meta_field' );
		$_config  = [
			'name'   => 'meta_field',
			'action' => 'replace',
			'value'  => 'foobar'
		];

		$wpdb = Mockery::mock('WPDB');
		$wpdb->usermeta = 'wp_usermeta';

		WP_Mock::userFunction('is_wp_error')
			->once()
			->andReturn( false );

		$wpdb->shouldReceive( 'update' )
			->once()
			->with(
				'wp_usermeta',
				[ 'meta_value' => 'foobar' ],
				[
					'user_id'  => 123,
					'meta_key' => 'meta_field'
				]
			)
			->andReturn( true );

		$config = json_decode( json_encode( $_config ) );
		$result = $method->invokeArgs( $scrubber, [ 123, $config, 'user' ] );

		$this->assertTrue( $result );
		$this->assertConditionsMet();
	}

	/**
	 * Test case for the `scrub_meta_field` method.
	 * Tests the `term` object type.
	 */
	public function test_scrub_meta_field_term() {
		global $wpdb;

		$scrubber = new JSONScrubber( new stdClass(), false );
		$method   = $this->getInaccessibleMethod( $scrubber, 'scrub_meta_field' );
		$_config  = [
			'name'   => 'meta_field',
			'action' => 'replace',
			'value'  => 'foobar'
		];

		$wpdb = Mockery::mock('WPDB');
		$wpdb->termmeta = 'wp_termmeta';

		WP_Mock::userFunction('is_wp_error')
			->once()
			->andReturn( false );

		$wpdb->shouldReceive( 'update' )
			->once()
			->with(
				'wp_termmeta',
				[ 'meta_value' => 'foobar' ],
				[
					'term_id'  => 123,
					'meta_key' => 'meta_field'
				]
			)
			->andReturn( true );

		$config = json_decode( json_encode( $_config ) );
		$result = $method->invokeArgs( $scrubber, [ 123, $config, 'term' ] );

		$this->assertTrue( $result );
		$this->assertConditionsMet();
	}

	/**
	 * Test case for the `scrub_meta_field` method.
	 * Tests the `post` object type.
	 */
	public function test_scrub_meta_field_post() {
		global $wpdb;

		$scrubber = new JSONScrubber( new stdClass(), false );
		$method   = $this->getInaccessibleMethod( $scrubber, 'scrub_meta_field' );
		$_config  = [
			'name'   => 'meta_field',
			'action' => 'replace',
			'value'  => 'foobar'
		];

		$wpdb = Mockery::mock('WPDB');
		$wpdb->postmeta = 'wp_postmeta';

		WP_Mock::userFunction('is_wp_error')
			->once()
			->andReturn( false );

		$wpdb->shouldReceive( 'update' )
			->once()
			->with(
				'wp_postmeta',
				[ 'meta_value' => 'foobar' ],
				[
					'post_id'  => 123,
					'meta_key' => 'meta_field'
				]
			)
			->andReturn( true );

		$config = json_decode( json_encode( $_config ) );
		$result = $method->invokeArgs( $scrubber, [ 123, $config, 'post' ] );

		$this->assertTrue( $result );
		$this->assertConditionsMet();
	}

	/**
	 * Test case for the `scrub_meta_field` method.
	 * Tests the `remove` action.
	 */
	public function test_scrub_meta_field_remove() {
		global $wpdb;

		$scrubber = new JSONScrubber( new stdClass(), false );
		$method   = $this->getInaccessibleMethod( $scrubber, 'scrub_meta_field' );
		$_config  = [
			'name'   => 'meta_field',
			'action' => 'remove',
		];

		$wpdb = Mockery::mock('WPDB');
		$wpdb->termmeta = 'wp_termmeta';

		$wpdb->shouldReceive( 'delete' )
			->once()
			->with(
				'wp_termmeta',
				[
					'term_id' => 123,
					'meta_key' => 'meta_field',
				]
			)
			->andReturn( true );

		$config = json_decode( json_encode( $_config ) );
		$result = $method->invokeArgs( $scrubber, [ 123, $config, 'term' ] );

		$this->assertTrue( $result );
		$this->assertConditionsMet();
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

		$wpdb->shouldReceive( 'update' )
			->once()
			->with(
				'wp_users',
				[ 'display_name' => 'Jane Doe' ],
				[ 'ID' => 123 ]
			);


		$config = json_decode( json_encode( $_config ) );
		$result = $method->invokeArgs( $scrubber, [ 123, $config, 'user' ] );

		$this->assertTrue( $result );
		$this->assertConditionsMet();
	}

	/**
	 * Test case for the `scrub_object_by_type` method.
	 * Tests the `post` type.
	 */
	public function test_scrub_object_by_type_post() {
		global $wpdb;

		$scrubber = new JSONScrubber( new stdClass(), false );
		$method   = $this->getInaccessibleMethod( $scrubber, 'scrub_object_by_type' );
		$_config  = [
			'fields' => [
				[
					'name'   => 'post_title',
					'action' => 'replace',
					'value'  => 'Lorem Ipsum',
				]
			],
			'meta_fields' => [
				[
					'name'   => 'meta_field',
					'action' => 'replace',
					'value'  => 'foobar',
				]
			],
		];

		$wpdb = Mockery::mock('WPDB');
		$wpdb->posts = 'wp_posts';
		$wpdb->postmeta = 'wp_postmeta';

		WP_Mock::userFunction('is_wp_error')
			->atLeast()
			->once()
			->andReturn( false );

		$wpdb->shouldReceive( 'update' )
			->once()
			->with(
				'wp_posts',
				[ 'post_title' => 'Lorem Ipsum' ],
				[ 'ID' => 123 ]
			);

		$wpdb->shouldReceive( 'update' )
			->once()
			->with(
				'wp_postmeta',
				[ 'meta_value' => 'foobar' ],
				[
					'post_id'  => 123,
					'meta_key' => 'meta_field',
				]
			);


		$config = json_decode( json_encode( $_config ) );
		$result = $method->invokeArgs( $scrubber, [ 123, $config, 'post' ] );

		$this->assertTrue( $result );
		$this->assertConditionsMet();
	}

	/**
	 * Test case for the `scrub_object_by_type` method.
	 * Tests the `term` type.
	 */
	public function test_scrub_object_by_type_term() {
		global $wpdb;

		$scrubber = new JSONScrubber( new stdClass(), false );
		$method   = $this->getInaccessibleMethod( $scrubber, 'scrub_object_by_type' );
		$_config  = [
			'name'   => 'text_tax',
			'fields' => [
				[
					'name'   => 'slug',
					'action' => 'replace',
					'value'  => 'foobar',
				],
				[
					'name'   => 'description',
					'action' => 'replace',
					'value'  => 'Lorem Ipsum',
				]
			],
		];

		$wpdb = Mockery::mock('WPDB');
		$wpdb->terms = 'wp_terms';
		$wpdb->term_taxonomy = 'wp_term_taxonomy';

		WP_Mock::userFunction('is_wp_error')
			->twice()
			->andReturn( false );

		$wpdb->shouldReceive( 'update' )
			->once()
			->with(
				'wp_term_taxonomy',
				[ 'description' => 'Lorem Ipsum' ],
				[
					'term_id' => 123,
					'taxonomy' => 'text_tax'
				],
			);

		$wpdb->shouldReceive( 'update' )
			->once()
			->with(
				'wp_terms',
				[ 'slug' => 'foobar' ],
				[ 'term_id' => 123 ],
			);


		$config = json_decode( json_encode( $_config ) );
		$result = $method->invokeArgs( $scrubber, [ 123, $config, 'term' ] );

		$this->assertTrue( $result );
		$this->assertConditionsMet();
	}

	/**
	 * Test case for the `scrub_users` method.
	 * Tests results for empty config.
	 */
	public function test_scrub_users_no_config() {
		$scrubber = new JSONScrubber( new stdClass(), false );
		$result   = $scrubber->scrub_users();

		$this->assertNull( $result );
	}

	/**
	 * Test case for the `scrub_users` method.
	 * Tests results for main user scrubbing method.
	 *
	 * Uses TestScrubber class to override the scrub_object_by_type method
	 * this allows us to test the main scrubbing method in isolation.
	 */
	public function test_scrub_users() {
		global $wpdb;

		$_config  = [
			'user_data' => [
				'fields' => [],
			],
		];
		$config   = json_decode( json_encode( $_config ) );
		$scrubber = new TestScrubber( $config, false );

		$wpdb = Mockery::mock('WPDB');
		$wpdb->users = 'wp_users';

		$wpdb->shouldReceive( 'get_col' )
			->once()
			->andReturns( [ 123, 124 ] );

		$progress = Mockery::mock( 'WP_CLI\Utils\ProgressBar' );

		$progress->shouldReceive( 'tick' )
			->twice();
		$progress->shouldReceive( 'finish' )
			->once();

		WP_Mock::userFunction( 'WP_CLI\Utils\make_progress_bar' )
			->once()
			->andReturns( $progress );

		$result = $scrubber->scrub_users();

		$this->assertNull( $result );
		$this->assertConditionsMet();
	}

	/**
	 * Test case for the `scrub_post_types` method.
	 * Tests results for empty config.
	 */
	public function test_scrub_post_types_no_config() {
		$scrubber = new JSONScrubber( new stdClass(), false );
		$result   = $scrubber->scrub_post_types();

		$this->assertNull( $result );
	}

	/**
	 * Test case for the `scrub_post_types` method.
	 * Tests results for main post type scrubbing method.
	 *
	 * Uses TestScrubber class to override the scrub_object_by_type method
	 * this allows us to test the main scrubbing method in isolation.
	 */
	public function test_scrub_post_types() {
		global $wpdb;

		$_config  = [
			'post_types' => [
				[
					'name' => 'test_cpt',
					'fields' => [],
				]
			],
		];
		$config   = json_decode( json_encode( $_config ) );
		$scrubber = new TestScrubber( $config, false );

		$wpdb = Mockery::mock('WPDB');
		$wpdb->posts = 'wp_posts';

		$wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturns( 'SELECT ID FROM wp_posts WHERE post_type = \'test_cpt\'' );

		$wpdb->shouldReceive( 'get_col' )
			->twice()
			->andReturns( [ 123, 124 ] );

		$progress = Mockery::mock( 'WP_CLI\Utils\ProgressBar' );

		$progress->shouldReceive( 'tick' )
			->times( 4 );
		$progress->shouldReceive( 'finish' )
			->twice();

		WP_Mock::userFunction( 'WP_CLI\Utils\make_progress_bar' )
			->twice()
			->andReturns( $progress );

		WP_Mock::userFunction( 'esc_sql' )
			->twice();

		$result = $scrubber->scrub_post_types();

		$this->assertNull( $result );
		$this->assertConditionsMet();
	}
}