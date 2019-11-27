<?php

global $wp_query;

/** @var Twig_TemplateWrapper $wp_twig_current */
echo wp_twig_render( _wp_twig_current(), (array) $wp_query->query_vars );
