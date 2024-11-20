<?php
/**
 * PHPUnit tests for the TenUpWPScrubber\Command class.
 */

use TenUpWPScrubber\Command;
use WP_Mock\Tools\TestCase;

class CommandTest extends TestCase {

	/**
	 * Test that calling 'all' runs scrub with correct modes.
	 */
	public function test_all_runs_scrub_with_correct_modes() {
		$command = $this->getMockBuilder( Command::class )
			->onlyMethods( [ 'scrub' ] )
			->getMock();

		$args       = [];
		$assoc_args = [];

		$command->expects( $this->once() )
			->method( 'scrub' )
			->with( [ 'users', 'comments' ], $args, $assoc_args );

		$command->all( $args, $assoc_args );
	}

	/**
	 * Test that calling 'users' runs scrub with correct modes.
	 */
	public function test_users_runs_scrub_with_correct_modes() {
		$command = $this->getMockBuilder( Command::class )
			->onlyMethods( [ 'scrub' ] )
			->getMock();

		$args       = [];
		$assoc_args = [];

		$command->expects( $this->once() )
			->method( 'scrub' )
			->with( [ 'users' ], $args, $assoc_args );

		$command->users( $args, $assoc_args );
	}

	/**
	 * Test that calling 'comments' runs scrub with correct modes.
	 */
	public function test_comments_runs_scrub_with_correct_modes() {
		$command = $this->getMockBuilder( Command::class )
			->onlyMethods( [ 'scrub' ] )
			->getMock();

		$args       = [];
		$assoc_args = [];

		$command->expects( $this->once() )
			->method( 'scrub' )
			->with( [ 'comments' ], $args, $assoc_args );

		$command->comments( $args, $assoc_args );
	}
}
