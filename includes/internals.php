<?php

use Twig\Environment;

if ( ! isset( $wp_twig_current ) ) {
	$wp_twig_current = null;
}

function _wp_twig_current() {
	global $wp_twig_current;

	return $wp_twig_current;
}


/**
 * @param null $dir
 *
 * @internal
 */
function _wp_twig_clean_cache( $dir = null ) {
	if ( null === $dir ) {
		$dir = WP_TWIG_CACHE_PATH;
	}

	foreach ( glob( $dir . '/*', GLOB_NOSORT ) as $path ) {
		if ( $path !== $dir && is_dir( $path ) ) {
			_wp_twig_clean_cache( $path );
			continue;
		}

		unlink( $path );
	}

	if ( WP_TWIG_CACHE_PATH !== $dir && is_dir( $dir ) ) {
		rmdir( $dir );
	}
}

/**
 * @param array $options
 *
 * @return array
 * @internal
 */
function _wp_twig_env_options( array $options ) {
	if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
		$options['cache'] = WP_TWIG_CACHE_PATH;
	}

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$options['debug'] = true;
	}

	if ( defined( 'CONCATENATE_SCRIPTS' ) && ! CONCATENATE_SCRIPTS ) {
		$options['optimizations'] = 0;
	}

	return $options;
}

function _wp_twig_env_generic_delegate( Environment $environment ) {
	$environment->registerUndefinedFunctionCallback( '_wp_twig_generic_delegate_function' );
	$environment->registerUndefinedFilterCallback( '_wp_twig_generic_delegate_filter' );
}

function _wp_twig_generic_delegate_target( $name ) {
	if ( function_exists( $name ) ) {
		return static function () use ( $name ) {
			$level = ob_get_level();
			$returnValue = call_user_func_array( $name, func_get_args() );
			while (ob_get_level() > $level) {
				ob_end_flush();
			}

			return $returnValue;
		};
	}

	if ( has_filter( $name ) ) {
		return static function () use ( $name ) {
			return apply_filters_ref_array( $name, func_get_args() );
		};
	}

	return null;
}

function _wp_twig_generic_delegate_filter( $name ) {
	static $cache = []; // This part can become a memory leak otherwise as Twig doesn't cache the answer.

	if ( array_key_exists( $name, $cache ) ) {
		return $cache[ $name ];
	}

	$callback = _wp_twig_generic_delegate_target( $name );

	if ( ! is_callable( $callback ) ) {
		throw new RuntimeException( sprintf( 'Filter %s not found', $name ) );
	}

	$cache[ $name ] = new Twig\TwigFilter( $name, $callback );

	return $cache[ $name ];
}

function _wp_twig_generic_delegate_function( $name ) {
	static $cache = []; // This part can become a memory leak otherwise as Twig doesn't cache the answer.

	if ( array_key_exists( $name, $cache ) ) {
		return $cache[ $name ];
	}

	$callback = _wp_twig_generic_delegate_target( $name );

	if ( ! is_callable( $callback ) && has_action( $name ) ) {
		// in case of a function we also lookup actions
		$callback = static function () use ( $name ) {
			do_action_ref_array( $name, func_get_args() );
		};
	}

	if ( ! is_callable( $callback ) ) {
		throw new RuntimeException( sprintf( 'Function %s not found', $name ) );
	}

	$cache[ $name ] = new \Twig\TwigFunction( $name, $callback );

	return $cache[ $name ];
}
