<?php
/**
 * Break WordPress and do it differently
 */

if ( file_exists( __DIR__ . '/../vendor/autoload.php' ) ) {
	require_once __DIR__ . '/../vendor/autoload.php';
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/wp-hooks.php';
