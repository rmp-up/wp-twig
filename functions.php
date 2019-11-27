<?php

require_once __DIR__ . '/includes/bootstrap.php';

/**
 * Example of injecting data
 *
 * This will be provided to `{% block foo %}`.
 */
add_filter(
	'wp_twig_block_foo',
	static function ( array $context ) {
		$context['who'] = 'Mary';

		return $context;
	}
);

add_filter(
	'wp_twig_template_index',
	static function ( array $context ) {
		$context['david'] = 'Doctor';

		return $context;
	}
);

add_filter(
	'wp_twig_template_404_block_foo',
	static function ( array $context ) {
		$context['david'] = 'The';

		return $context;
	}
);
