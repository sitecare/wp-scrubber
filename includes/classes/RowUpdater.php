<?php
/**
 * Row updater class.
 * @package TenUpWPScrubber
 */

/**
 * Abstract class for updating rows in a table
 */
abstract class RowUpdater {

	abstract protected function get_table_name();

	abstract protected function get_primary_key();

	abstract protected function get_row_data();

	abstract protected function get_where();

	abstract protected function validate_config();

	public function update_row() {

	}
}
