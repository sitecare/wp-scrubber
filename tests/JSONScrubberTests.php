<?php

require_once __DIR__ . '/classes/TestScrubber.php';

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
	 * Tests case for all scrub methods with empty config.
	 */
	public function test_scrub_no_config() {
		$scrubber = new JSONScrubber( new stdClass(), false );

		$user_scrub     = $scrubber->scrub_users();
		$post_scrub     = $scrubber->scrub_post_types();
		$tax_scrub      = $scrubber->scrub_taxonomies();
		$options_scrub  = $scrubber->scrub_options();
		$custom_scrub   = $scrubber->scrub_custom_tables();
		$truncate_scrub = $scrubber->truncate_tables();

		$this->assertNull( $user_scrub );
		$this->assertNull( $post_scrub );
		$this->assertNull( $tax_scrub );
		$this->assertNull( $options_scrub );
		$this->assertNull( $custom_scrub );
		$this->assertNull( $truncate_scrub );
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

	public function test_scrub_taxonomies() {
		global $wpdb;

		$_config  = [
			'taxonomies' => [
				[
					'name' => 'test_tax',
					'fields' => [],
				]
			],
		];
		$config   = json_decode( json_encode( $_config ) );
		$scrubber = new TestScrubber( $config, false );

		$wpdb = Mockery::mock('WPDB');
		$wpdb->term_taxonomy = 'wp_term_taxonomy';

		$wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturns( 'SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = \'test_tax\'' );

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

		$result = $scrubber->scrub_taxonomies();

		$this->assertNull( $result );
		$this->assertConditionsMet();
	}

}