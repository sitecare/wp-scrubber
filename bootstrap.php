<?php

// First we need to load the composer autoloader, so we can use WP Mock
require_once __DIR__ . '/vendor/autoload.php';

// Load WordPress and WP-CLI dependencies if needed.
if ( ! class_exists( 'WP_CLI' ) ) {
	require_once dirname( __DIR__ ) . '/vendor/wp-cli/wp-cli/php/class-wp-cli.php';
}

// Use patchwork
WP_Mock::setUsePatchwork( true );

// Bootstrap WP_Mock to initialize built-in features
WP_Mock::bootstrap();
