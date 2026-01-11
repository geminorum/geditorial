<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Modulation extends gEditorial\Service
{
	const PRODUCTION_BLACKLIST = [
		'alpha',
		'experimental',
		'unknown',
	];

	public static function moduleObject( $args, $folder = FALSE, $class = NULL )
	{
		if ( FALSE === $args )
			return FALSE;

		if ( ! isset( $args['name'], $args['title'] ) )
			return FALSE;

		$defaults = [
			'folder'     => $folder,
			'class'      => $class ?: self::moduleClass( $args['name'], FALSE ),
			'textdomain' => sprintf( '%s-%s', static::BASE, Core\Text::sanitizeBase( $args['name'] ) ),   // or `NULL` for plugin base

			'icon'      => 'screenoptions',   // `dashicons` class / SVG icon array
			'configure' => TRUE,              // or `settings`, `tools`, `reports`, `imports`, `customs`, `FALSE` to disable
			'i18n'      => TRUE,              // or `FALSE`, `adminonly`, `frontonly`, `restonly`
			'frontend'  => TRUE,              // Whether or not the module should be loaded on the frontend
			'autoload'  => FALSE,             // Autoloading a module will remove the ability to enable/disable it
			'disabled'  => FALSE,             // FALSE or string explaining why the module is not available
			'access'    => 'unknown',         // or `private`, `stable`, `beta`, `alpha`, `beta`, `deprecated`, `planned`
			'keywords'  => [],
		];

		return [
			$args['name'],
			(object) array_merge( $defaults, $args ),
		];
	}

	public static function moduleClass( $module, $check = TRUE, $root = NULL )
	{
		$class = '';
		$root  = $root ?? '\\geminorum\\gEditorial\\Modules';

		foreach ( explode( '-', str_replace( '_', '-', $module ) ) as $word )
			$class.= ucfirst( $word ).'';

		$class = $root.'\\'.$class.'\\'.$class;

		if ( $check && ! class_exists( $class ) )
			return FALSE;

		return $class;
	}

	public static function moduleSlug( $module, $link = TRUE )
	{
		return $link
			? ucwords( str_replace( [ '_', ' ' ], '-', $module ), '-' )
			: ucwords( str_replace( [ '_', '-' ], ' ', $module ) );
	}

	public static function moduleLoading( $module, $stage = NULL )
	{
		if ( 'private' === $module->access && ! GEDITORIAL_LOAD_PRIVATES )
			return FALSE;

		if ( 'beta' === $module->access && ! GEDITORIAL_BETA_FEATURES )
			return FALSE;

		$stage = $stage ?? self::const( 'WP_STAGE', 'production' ); // 'development'

		if ( 'production' !== $stage )
			return TRUE;

		if ( in_array( $module->access, static::PRODUCTION_BLACKLIST, TRUE ) )
			return FALSE;

		return TRUE;
	}

	public static function moduleEnabled( $options )
	{
		$enabled = $options->enabled ?? FALSE;

		if ( 'off' === $enabled )
			return FALSE;

		if ( 'on' === $enabled )
			return TRUE;

		return $enabled;
	}

	// TODO: must check for minimum version of WooCommerce
	public static function moduleCheckWooCommerce( $message = NULL )
	{
		return WordPress\WooCommerce::isActive()
			? FALSE
			: ( is_null( $message ) ? _x( 'Needs WooCommerce', 'Services: Modulation', 'geditorial-admin' ) : $message );
	}

	public static function moduleCheckLocale( $locale, $message = NULL )
	{
		$current = Core\L10n::locale( TRUE );

		foreach ( (array) $locale as $code )
			if ( $current === $code )
				return FALSE;

		return $message ?? _x( 'Not Available on Current Locale', 'Services: Modulation', 'geditorial-admin' );
	}

	public static function enqueueVirastar()
	{
		if ( ! gEditorial()->enabled( 'ortho' ) )
			return FALSE;

		return gEditorial()->module( 'ortho' )->enqueueVirastar();
	}

	public static function hasByline( $post, $context = NULL )
	{
		if ( ! gEditorial()->enabled( 'byline' ) )
			return FALSE;

		return (bool) gEditorial()->module( 'byline' )->has_content_for_post( $post, $context );
	}

	public static function isTaxonomyGenre( $taxonomy, $fallback = 'genre' )
	{
		return $taxonomy === gEditorial()->constant( 'genres', 'main_taxonomy', $fallback );
	}

	public static function isTaxonomyAudit( $taxonomy, $fallback = 'audit_attribute' )
	{
		return $taxonomy === gEditorial()->constant( 'audit', 'main_taxonomy', $fallback );
	}

	public static function setTaxonomyAudit( $post, $term_ids, $append = TRUE, $fallback = 'audit_attribute' )
	{
		if ( ! gEditorial()->enabled( 'audit' ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! gEditorial()->module( 'audit' )->posttype_supported( $post->post_type ) )
			return FALSE;

		if ( $append && empty( $term_ids ) )
			return FALSE;

		else if ( empty( $term_ids ) )
			$terms = NULL;

		else if ( is_string( $term_ids ) )
			$terms = $term_ids;

		else
			$terms = Core\Arraay::prepNumeral( $term_ids );

		$taxonomy = gEditorial()->constant( 'audit', 'main_taxonomy', $fallback );
		$result   = wp_set_object_terms( $post->ID, $terms, $taxonomy, $append );

		if ( is_wp_error( $result ) )
			return FALSE;

		clean_object_term_cache( $post->ID, $taxonomy );

		return $result;
	}
}
