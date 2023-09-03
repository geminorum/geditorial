<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class LateChores extends WordPress\Main
{

	// use this after disabling counts
	// Services\LateChores::termCountCollect();

	const BASE = 'geditorial';

	const TERMS_COUNT_ACTION = 'geditorial_late_terms_counts';

	public static function setup()
	{
		// custom-actions
		add_action( static::TERMS_COUNT_ACTION, [ __CLASS__, 'termCountDoCount' ], 10, 1 );

		add_action( 'shutdown', [ __CLASS__, 'shutdown' ] );
	}

	public static function shutdown()
	{
		LateChores::termCountSchedule();
	}

	// collecting changed terms
	public static function termCountCollect()
	{
		add_action( 'set_object_terms', [ __CLASS__, 'set_object_terms' ], 20, 6 );
		add_action( 'deleted_term_relationships', [ __CLASS__, 'deleted_term_relationships' ], 20, 3 );
	}

	public static function set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids )
	{
		self::termCount( $tt_ids );
		self::termCount( $old_tt_ids );
	}

	public static function deleted_term_relationships( $object_id, $tt_ids, $taxonomy )
	{
		self::termCount( $tt_ids );
	}

	public static function termCount( $term_ids )
	{
		global $gEditorialLateTerms;

		if ( empty( $gEditorialLateTerms ) )
			$gEditorialLateTerms = [];

		$gEditorialLateTerms = array_merge( $gEditorialLateTerms, (array) $term_ids );
	}

	// @REF: https://github.com/woocommerce/woocommerce/wiki/WC_Queue---WooCommerce-Worker-Queue
	public static function termCountSchedule()
	{
		global $gEditorialLateTerms;

		if ( empty( $gEditorialLateTerms ) )
			return;

		$action_id = FALSE;
		$term_ids  = Core\Arraay::prepNumeral( $gEditorialLateTerms );

		if ( ! empty( $term_ids ) )
			// $action_id = WC()->queue()->add( static::TERMS_COUNT_ACTION, [ $term_ids ], static::BASE );
			$action_id = wp_schedule_single_event( time(), static::TERMS_COUNT_ACTION, [ $term_ids ] );

		$gEditorialLateTerms = []; // reset!

		return $action_id;
	}

	public static function termCountDoCount( $term_ids )
	{
		if ( empty( $term_ids ) )
			return;

		$count = WordPress\Taxonomy::updateTermCount( $term_ids );
		$log   = sprintf( 'LATE TERM COUNT: (%s): %s', $count, implode( ',', $term_ids ) );

		Helper::log( $log, static::BASE, 'NOTICE', $term_ids );
	}
}
