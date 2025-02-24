<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

if ( ! function_exists( 'wp_twig_template_override' ) ) {
	/**
	 * First override query templates with Twig-Template
	 *
	 * @param Twig_TemplateWrapper $template
	 * @param string               $type
	 * @param array                $templates
	 *
	 * @return string
	 * @see \get_query_template
	 */
	function wp_twig_template_override( $template, $type, $templates ) {
		// lookup twig
		foreach ( (array) $templates as $current_template ) {
			$twig_name = basename( $current_template, '.php' ) . '.html.twig';
			$twig_path = locate_template( $twig_name );

			if ( $twig_path ) {
				return $twig_path;
			}
		}

		// otherwise use previous
		return $template;
	}
}

if ( ! function_exists( 'wp_twig_dispatch_override' ) ) {
	/**
	 * Then load template and redirect dispatcher
	 *
	 * @param $template
	 *
	 * @return string
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 */
	function wp_twig_dispatch_override( $template ) {
		if ( '.twig' === substr( $template, - 5 ) ) {
			$theme_path = WP_CONTENT_DIR . '/themes/';
			$loader     = new FilesystemLoader( $theme_path );

			/**
			 * Change the environment options
			 *
			 * @see \Twig_Environment::__construct
			 */
			$options = apply_filters( 'wp_twig_environment_options', [] );

			$twig = new Environment( $loader, $options );
			do_action( 'wp_twig_environment', $twig );

			global $wp_twig_current;
			$wp_twig_current = $twig->load( str_replace( $theme_path, '', $template ) );

			return __DIR__ . '/dispatch.php';
		}

		return $template;
	}
}

if ( ! function_exists( 'wp_twig_render' ) ) {
	/**
	 * The dispatcher renders the template then
	 *
	 * @param Twig\TemplateWrapper $template
	 * @param array                $context
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	function wp_twig_render( Twig\TemplateWrapper $template, array $context = [] ): string {
		$pre = apply_filters( 'wp_twig_render_pre', null, $template, $context );

		if ( is_string( $pre ) ) {
			return $pre;
		}

		$source_name = $template->getSourceContext()->getName();
		$content     = $template->render(
			array_merge(
				$context,
				wp_twig_context( $source_name, $template->getBlockNames() )
			)
		);

		if ( false === $content ) {
			// Twig does not catch when ob_get_clean returns false.
			// We show the very last error because OB often eats up exception messages and error output.
			throw new RuntimeException(
				sprintf(
					'Problem while rendering "%s". Most recent known problem: %s',
					$source_name,
					json_encode( error_get_last() )
				)
			);
		}

		// Cast to string as ob_get_clean could return false
		return (string) $template->render(
			array_merge(
				$context,
				wp_twig_context( $template->getSourceContext()->getName(), $template->getBlockNames() )
			)
		);
	}
}

if ( ! function_exists( 'wp_twig_context' ) ) {
	/**
	 * Get context data based for source and used blocks
	 *
	 * @param string   $source_name The path to the template like "wp-twig/index.html.twig".
	 * @param string[] $block_names Names of all used blocks.
	 *
	 * @return mixed|void
	 */
	function wp_twig_context( string $source_name, array $block_names = [] ) {
		// Split theme and template name
		$defaults = [
			'theme'    => strtok( $source_name, '/' ),
			'template' => preg_replace( '/(|\.html)\.twig$/', '', strtok( '' ) ),
		];

		$context = $defaults;
		foreach ( $block_names as $block_name ) {
			$context = apply_filters( 'wp_twig_template_' . $defaults['template'] . '_block_' . $block_name, $context );
			$context = apply_filters( 'wp_twig_block_' . $block_name, $context );
		}

		$context = apply_filters( 'wp_twig_template_' . $defaults['template'], $context );

		return apply_filters( 'wp_twig_context', $context );
	}
}
