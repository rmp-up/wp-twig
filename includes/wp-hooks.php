<?php

/**
 * First resolve templates to twig
 *
 * @see \get_query_template()
 */
function _wp_twig_register_template_override() {
	add_filter( 'index_template', 'wp_twig_template_override', 10, 3 );
	add_filter( '404_template', 'wp_twig_template_override', 10, 3 );
	add_filter( 'archive_template', 'wp_twig_template_override', 10, 3 );
	add_filter( 'author_template', 'wp_twig_template_override', 10, 3 );
	add_filter( 'category_template', 'wp_twig_template_override', 10, 3 );
	add_filter( 'tag_template', 'wp_twig_template_override', 10, 3 );
	add_filter( 'taxonomy_template', 'wp_twig_template_override', 10, 3 );
	add_filter( 'date_template', 'wp_twig_template_override', 10, 3 );
	add_filter( 'home_template', 'wp_twig_template_override', 10, 3 );
	add_filter( 'front_page_template', 'wp_twig_template_override', 10, 3 );
	add_filter( 'page_template', 'wp_twig_template_override', 10, 3 );
	add_filter( 'search_template', 'wp_twig_template_override', 10, 3 );
	add_filter( 'single_template', 'wp_twig_template_override', 10, 3 );
	add_filter( 'embed_template', 'wp_twig_template_override', 10, 3 );
	add_filter( 'singular_template', 'wp_twig_template_override', 10, 3 );
	add_filter( 'attachment_template', 'wp_twig_template_override', 10, 3 );
	add_filter( 'paged_template', 'wp_twig_template_override', 10, 3 );

	// override the template that will be dispatched
	add_filter( 'template_include', 'wp_twig_dispatch_override' );
}

function _wp_twig_register_twig() {
	// Prepare environment based on WP-Constants
	add_filter( 'wp_twig_environment_options', '_wp_twig_env_options' );

	// Add common WP functions as filter
	add_action( 'wp_twig_environment', '_wp_twig_env_generic_delegate' );
}

function _wp_twig_register_cache_flush() {
	/**
	 * And clean cached templates if needed
	 */
	add_action( 'clean_page_cache', '_wp_twig_clean_cache' );
	add_action( 'clean_post_cache', '_wp_twig_clean_cache' );
	add_action( 'clean_site_cache', '_wp_twig_clean_cache' );

	if ( 'cli' === PHP_SAPI && defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::add_hook( 'after_invoke:cache flush', 'wp_twig_clean_cache' );
	}
}

/**
 * Register Twig capability in WordPress
 */
function wp_twig_register() {
	if ( ! defined( 'WP_TWIG_CACHE_PATH' ) ) {
		define( 'WP_TWIG_CACHE_PATH', WP_CONTENT_DIR . '/wp-twig-cache' );
	}

	_wp_twig_register_template_override();
	_wp_twig_register_twig();
	_wp_twig_register_cache_flush();
}

