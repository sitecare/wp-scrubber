<?php

// First we need to load the composer autoloader, so we can use WP Mock
require_once __DIR__ . '/vendor/autoload.php';

// Use patchwork
WP_Mock::setUsePatchwork( true );

// Bootstrap WP_Mock to initialize built-in features
WP_Mock::bootstrap();
