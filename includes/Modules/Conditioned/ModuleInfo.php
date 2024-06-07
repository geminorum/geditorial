<?php namespace geminorum\gEditorial\Modules\Conditioned;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;

class ModuleInfo extends gEditorial\Info
{

	const MODULE = 'conditioned';

	// @REF: https://brandshop-reference-jp.com/pages/condition-guide
	public static function getConditionRanks( $context = NULL )
	{
		return [
			'brand-new' => [
				'slug'        => 'brand-new',
				'name'        => _x( 'Brand New', 'Condition Rank: Name', 'geditorial-conditioned' ),
				// 'description' => _x( '', 'Condition Rank: Description', 'geditorial-conditioned' ),
				'code'        => 'N',
				'order'       => '1',
			],
			'unused' => [
				'slug'        => 'unused',
				'name'        => _x( 'Unused', 'Condition Rank: Name', 'geditorial-conditioned' ),
				'description' => _x( 'Unused items on display that may be slightly scratched or sun-bleached etc and/or items with considerable period of time since its manufacturing date.', 'Condition Rank: Description', 'geditorial-conditioned' ),
				'code'        => 'NS',
				'order'       => '2',
			],
			'almost-new' => [
				'slug'        => 'almost-new',
				'name'        => _x( 'Almost New', 'Condition Rank: Name', 'geditorial-conditioned' ),
				'description' => _x( 'A pre-owned item used several times.', 'Condition Rank: Description', 'geditorial-conditioned' ),
				'code'        => 'S',
				'order'       => '3',
			],
			'used-sa' => [
				'slug'        => 'used-sa',
				'name'        => _x( 'Used: SA', 'Condition Rank: Name', 'geditorial-conditioned' ),
				'description' => _x( 'An item with small scratches and/or stains but near an Almost New item.', 'Condition Rank: Description', 'geditorial-conditioned' ),
				'code'        => 'SA',
				'order'       => '4',
			],
			'used-a' => [
				'slug'        => 'used-a',
				'name'        => _x( 'Used: A', 'Condition Rank: Name', 'geditorial-conditioned' ),
				'description' => _x( 'An used item with damage from general usage but still in good condition.', 'Condition Rank: Description', 'geditorial-conditioned' ),
				'code'        => 'A',
				'order'       => '5',
			],
			'used-ab' => [
				'slug'        => 'used-ab',
				'name'        => _x( 'Used: AB', 'Condition Rank: Name', 'geditorial-conditioned' ),
				'description' => _x( 'A frequently used item with damage, recommendable for those unaffected by minor flaws.', 'Condition Rank: Description', 'geditorial-conditioned' ),
				'code'        => 'AB',
				'order'       => '6',
			],
			'used-b' => [
				'slug'        => 'used-b',
				'name'        => _x( 'Used: B', 'Condition Rank: Name', 'geditorial-conditioned' ),
				'description' => _x( 'A considerably used item with some noticeable damage.', 'Condition Rank: Description', 'geditorial-conditioned' ),
				'code'        => 'B',
				'order'       => '7',
			],
			'used-c' => [
				'slug'        => 'used-c',
				'name'        => _x( 'Used: C', 'Condition Rank: Name', 'geditorial-conditioned' ),
				'description' => _x( 'A heavily used item with very noticeable damage.', 'Condition Rank: Description', 'geditorial-conditioned' ),
				'code'        => 'C',
				'order'       => '8',
			],
			'used-d' => [
				'slug'        => 'used-d',
				'name'        => _x( 'Used: D', 'Condition Rank: Name', 'geditorial-conditioned' ),
				'description' => _x( 'An item with broken parts etc. A non-functional junk item.', 'Condition Rank: Description', 'geditorial-conditioned' ),
				'code'        => 'D',
				'order'       => '9',
			],
		];
	}
}
