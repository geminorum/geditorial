<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class LateChores extends WordPress\Main
{

	const BASE = 'geditorial';

	const TERMS_COUNT_ACTION = 'geditorial_late_terms_counts';

	public static function setup()
	{
		// custom-actions
		add_action( static::TERMS_COUNT_ACTION, [ __CLASS__, 'termCountDoCount' ], 10, 1 );
	}

	// use this after disabling counts
	// Services\LateChores::termCountCollect();
	public static function termCountCollect()
	{
		static $hooked = NULL;

		if ( ! is_null( $hooked ) || Core\WordPress::isCRON() )
			return $hooked;

		add_action( 'set_object_terms',
			function ( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
				self::termCount( $tt_ids );
				self::termCount( $old_tt_ids );
			}, 20, 6 );

		add_action( 'deleted_term_relationships',
			function ( $object_id, $tt_ids, $taxonomy ) {
				self::termCount( $tt_ids );
			}, 20, 3 );

		add_action( 'shutdown',
			function () {
				LateChores::termCountSchedule();
			} );

		$hooked = TRUE;
	}

	public static function termCount( $term_ids )
	{
		global $gEditorialLateTerms;

		if ( empty( $gEditorialLateTerms ) )
			$gEditorialLateTerms = [];

		$gEditorialLateTerms = array_merge( $gEditorialLateTerms, (array) $term_ids );
	}

	public static function termCountSchedule()
	{
		global $gEditorialLateTerms;

		if ( empty( $gEditorialLateTerms ) )
			return;

		$action = FALSE;
		$list   = Core\Arraay::prepNumeral( $gEditorialLateTerms );

		if ( ! empty( $list ) )
			$action = self::scheduleSingle( static::TERMS_COUNT_ACTION, [ $list ] );

		$gEditorialLateTerms = []; // reset!

		return $action;
	}

	public static function termCountDoCount( $term_ids )
	{
		if ( empty( $term_ids ) )
			return;

		$count = WordPress\Taxonomy::updateTermCount( $term_ids );
		$log   = sprintf( 'LATE TERM COUNT: (%s): %s', $count, implode( ',', $term_ids ) );

		Helper::log( $log, static::BASE, 'NOTICE', $term_ids );
	}

	// @REF: https://actionscheduler.org/usage/
	// @REF: https://github.com/woocommerce/woocommerce/wiki/WC_Queue---WooCommerce-Worker-Queue
	// @SEE: https://rudrastyh.com/wordpress/wp_schedule_single_event.html
	public static function scheduleSingle( $hook, $args, $group = NULL )
	{
		if ( function_exists( 'as_schedule_single_action' ) )
			return as_schedule_single_action( time(), $hook, $args );

		if ( function_exists( 'WC' ) )
			return WC()->queue()->add( $hook, $args, $group ?? static::BASE );

		return wp_schedule_single_event( time(), $hook, $args );
	}
}
