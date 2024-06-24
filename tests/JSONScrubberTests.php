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