<?php // phpcs:ignore
/**
 * Class file for JSONScrubberTests.
 *
 * @package TenUpWPScrubber
 */

require_once __DIR__ . '/classes/TestScrubber.php';

use WP_Mock\Tools\TestCase;
use TenUpWPScrubber\JSONScrubber;

/**
 * Class JSONScrubberTests
 *
 * This class contains unit tests for the JSONScrubber class.
 */
final class JSONScrubberTest extends TestCase {

	/**
	 * Test the instance creation of JSONScrubber.
	 */
	public function test_instance() {
		$config      = new stdClass();
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

		$wpdb        = Mockery::mock( 'WPDB' );
		$wpdb->users = 'wp_users';

		$wpdb->shouldReceive( 'get_col' )
			->once()
			->andReturns( [ 123, 124 ] );

		$this->assert_progress( 2 );

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
					'name'   => 'test_cpt',
					'fields' => [],
				],
			],
		];
		$config   = json_decode( json_encode( $_config ) );
		$scrubber = new TestScrubber( $config, false );

		$wpdb        = Mockery::mock( 'WPDB' );
		$wpdb->posts = 'wp_posts';

		$wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturns( 'SELECT ID FROM wp_posts WHERE post_type = \'test_cpt\'' );

		$wpdb->shouldReceive( 'get_col' )
			->twice()
			->andReturns( [ 123, 124 ] );

		$this->assert_progress( 2 );
		$this->assert_progress( 2 );

		WP_Mock::userFunction( 'esc_sql' )
			->twice();

		$result = $scrubber->scrub_post_types();

		$this->assertNull( $result );
		$this->assertConditionsMet();
	}

	/**
	 * Test case for the `scrub_taxonomies` method.
	 * Tests results for main taxonomy scrubbing method.
	 *
	 * Uses TestScrubber class to override the scrub_object_by_type method
	 * this allows us to test the main scrubbing method in isolation.
	 */
	public function test_scrub_taxonomies() {
		global $wpdb;

		$_config  = [
			'taxonomies' => [
				[
					'name'   => 'test_tax',
					'fields' => [],
				],
			],
		];
		$config   = json_decode( json_encode( $_config ) );
		$scrubber = new TestScrubber( $config, false );

		$wpdb                = Mockery::mock( 'WPDB' );
		$wpdb->term_taxonomy = 'wp_term_taxonomy';

		$wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturns( 'SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = \'test_tax\'' );

		$wpdb->shouldReceive( 'get_col' )
			->once()
			->andReturns( [ 123, 124 ] );

		$this->assert_progress( 2 );

		$result = $scrubber->scrub_taxonomies();

		$this->assertNull( $result );
		$this->assertConditionsMet();
	}

	/**
	 * Test case for the `scrub_options` method with removals.
	 * Tests results for main options scrubbing method.
	 *
	 * Uses TestScrubber class to override the scrub_object_by_type method
	 * this allows us to test the main scrubbing method in isolation.
	 */
	public function test_scrub_options_remove() {
		global $wpdb;

		$_config  = [
			'options' => [
				[
					'name'   => 'test_option',
					'action' => 'remove',
				],
			],
		];
		$config   = json_decode( json_encode( $_config ) );
		$scrubber = new TestScrubber( $config, false );

		$wpdb          = Mockery::mock( 'WPDB' );
		$wpdb->options = 'wp_options';

		$wpdb->shouldReceive( 'delete' )
			->once()
			->with(
				'wp_options',
				[ 'option_name' => 'test_option' ]
			);

		$this->assert_progress();

		$result = $scrubber->scrub_options();

		$this->assertNull( $result );
		$this->assertConditionsMet();
	}

	/**
	 * Test case for the `scrub_options` method with updates.
	 * Tests results for main options scrubbing method.
	 *
	 * Uses TestScrubber class to override the scrub_object_by_type method
	 * this allows us to test the main scrubbing method in isolation.
	 */
	public function test_scrub_options_update() {
		global $wpdb;

		$_config  = [
			'options' => [
				[
					'name'   => 'test_option',
					'action' => 'replace',
					'value'  => 'new_value',
				],
			],
		];
		$config   = json_decode( json_encode( $_config ) );
		$scrubber = new TestScrubber( $config, false );

		$wpdb          = Mockery::mock( 'WPDB' );
		$wpdb->options = 'wp_options';

		$wpdb->shouldReceive( 'update' )
			->once()
			->with(
				'wp_options',
				[ 'option_value' => 'new_value' ],
				[ 'option_name' => 'test_option' ]
			);

		$this->assert_progress();

		$result = $scrubber->scrub_options();

		$this->assertNull( $result );
		$this->assertConditionsMet();
	}

	/**
	 * Test case for the `scrub_custom_tables` method.
	 * Tests results for main custom table scrubbing method.
	 *
	 * Uses TestScrubber class to override the scrub_object_by_type method
	 * this allows us to test the main scrubbing method in isolation.
	 */
	public function test_scrub_custom_tables() {
		global $wpdb;

		$_config  = [
			'custom_tables' => [
				[
					'name'        => 'custom_table_name',
					'primary_key' => 'id',
					'columns'     => [],
				],
			],
		];
		$config   = json_decode( json_encode( $_config ) );
		$scrubber = new TestScrubber( $config, false );

		$wpdb = Mockery::mock( 'WPDB' );

		$wpdb->shouldReceive( 'get_col' )
			->once()
			->with( 'SELECT id FROM custom_table_name' )
			->andReturns( [ 123, 124 ] );

		$wpdb->shouldReceive( 'update' )
			->once()
			->with(
				'custom_table_name',
				[],
				[ 'id' => 123 ]
			);

			$wpdb->shouldReceive( 'update' )
			->once()
			->with(
				'custom_table_name',
				[],
				[ 'id' => 124 ]
			);

		$this->assert_progress( 2 );

		$result = $scrubber->scrub_custom_tables();

		$this->assertNull( $result );
		$this->assertConditionsMet();
	}

	/**
	 * Test case for the `truncate_tables` method.
	 * Tests results for main table truncation method.
	 */
	public function test_truncate_tables() {
		global $wpdb;

		$_config  = [
			'truncate_tables' => [
				'custom_table_one',
				'custom_table_two',
			],
		];
		$config   = json_decode( json_encode( $_config ) );
		$scrubber = new TestScrubber( $config, false );

		$wpdb = Mockery::mock( 'WPDB' );

		$wpdb->shouldReceive( 'query' )
			->once()
			->with( 'TRUNCATE TABLE custom_table_one' )
			->andReturn( true );

			$wpdb->shouldReceive( 'query' )
			->once()
			->with( 'TRUNCATE TABLE custom_table_two' )
			->andReturn( true );

		$this->assert_progress( 2 );

		$result = $scrubber->truncate_tables();

		$this->assertNull( $result );
		$this->assertConditionsMet();
	}

	/**
	 * Helper method for progress assertions to reduce code.
	 * This method will assert the progress bar is created and ticked.
	 *
	 * @param int $count Number of times to tick the progress bar.
	 * @return void
	 */
	private function assert_progress( $count = 1 ): void {
		$progress = Mockery::mock( 'WP_CLI\Utils\ProgressBar' );

		$progress->shouldReceive( 'tick' )
			->times( $count );

		$progress->shouldReceive( 'finish' )
			->once();

		WP_Mock::userFunction( 'WP_CLI\Utils\make_progress_bar' )
		->once()
		->andReturns( $progress );
	}
}
