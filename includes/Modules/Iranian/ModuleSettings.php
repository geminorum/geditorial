<?php namespace geminorum\gEditorial\Modules\Iranian;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class ModuleSettings extends gEditorial\Settings
{

	const MODULE = 'iranian';

	const ACTION_COUNTRY_SUMMARY = 'do_report_country_summary';
	const ACTION_CITY_SUMMARY    = 'do_report_city_summary';

	public static function renderCard_country_summary( $posttypes )
	{
		echo self::toolboxCardOpen( _x( 'Country Summary', 'Card Title', 'geditorial-iranian' ) );

			foreach ( $posttypes as $posttype => $label )
				self::submitButton( add_query_arg( [
					'action' => static::ACTION_COUNTRY_SUMMARY,
					'type'   => $posttype,
				] ), sprintf(
					/* translators: %s: posttype label */
					_x( 'On %s', 'Button', 'geditorial-iranian' ),
					$label
				), 'link-small' );

			Core\HTML::desc( _x( 'Tries to summarize the country info based on the raw meta-data.', 'Button Description', 'geditorial-iranian' ) );

		echo '</div></div>'; // buttons + wrap closed
	}

	public static function renderCard_city_summary( $posttypes )
	{
		echo self::toolboxCardOpen( _x( 'City Summary', 'Card Title', 'geditorial-iranian' ) );

			foreach ( $posttypes as $posttype => $label )
				self::submitButton( add_query_arg( [
					'action' => static::ACTION_CITY_SUMMARY,
					'type'   => $posttype,
				] ), sprintf(
					/* translators: %s: posttype label */
					_x( 'On %s', 'Button', 'geditorial-iranian' ),
					$label
				), 'link-small' );

			Core\HTML::desc( _x( 'Tries to summarize the city info based on the raw meta-data.', 'Button Description', 'geditorial-iranian' ) );

		echo '</div></div>'; // buttons + wrap closed
	}

	public static function handleReport_country_summary( $posttype, $identity_metakey, $location_metakey, $data = [] )
	{
		$chartname = 'countrysummary';
		$summary   = ModuleHelper::getCountrySummary( $posttype, $identity_metakey, $data );

		echo self::toolboxColumnOpen( _x( 'Iranian Reports', 'Header', 'geditorial-iranian' ) );
			echo self::toolboxCardOpen( _x( 'Country Summary', 'Card Title', 'geditorial-iranian' ), FALSE );
				echo Scripts::markupChartJS( $chartname, static::MODULE );
			echo '</div>';
		echo '</div>';

		Scripts::enqueueChartJS_Bar( $chartname, $summary, [
			'label' => Helper::getPostTypeLabel( $posttype, 'extended_label' ),
		] );

		return TRUE;
	}

	public static function handleReport_city_summary( $posttype, $identity_metakey, $location_metakey, $data = [] )
	{
		echo self::toolboxColumnOpen();

			$offset = ( self::paged() - 1 ) * 10;

			for ( $i = 1; $i <= 10; $i++ ) {

				$current = $i + $offset;

				if ( $current > 999 )
					break;

				self::renderRow_city_summary(
					$current,
					$posttype,
					$identity_metakey,
					$location_metakey,
					$data
				);
			}

			echo self::toolboxAfterOpen();

				self::submitButton( add_query_arg( [
					'action' => static::ACTION_CITY_SUMMARY,
					'type'   => $posttype,
					'paged'  => self::paged() + 1,
				] ), _x( 'Next Batch', 'Button', 'geditorial-iranian' ), 'link' );

			echo '</div>';
		echo '</div>';

		return TRUE;
	}

	private static function renderRow_city_summary( $number, $posttype, $identity_metakey, $location_metakey, $data = [] )
	{
		$zeroise = Core\Number::zeroise( $number, 3 );
		$query   = [
			'orderby'    => 'none',
			'meta_query' => [
				'relation' => 'AND',
				[
					'key'     => $identity_metakey,
					'value'   => sprintf( '^%s', $zeroise ),
					'compare' => 'REGEXP', // @REF: https://wordpress.stackexchange.com/a/159433
				],
				[
					'key'     => $location_metakey,
					'compare' => 'EXISTS',
				],
			],
		];

		$posts = Tablelist::getPosts( $query, [], $posttype, FALSE );
		$list  = $table = [];

		foreach ( $posts as $post ) {

			if ( ! $location = get_post_meta( $post->ID, $location_metakey, TRUE ) )
				continue;

			if ( ! $identity = get_post_meta( $post->ID, $identity_metakey, TRUE ) )
				continue;

			$list[$location][] = Core\HTML::tag( 'a', [
				'title'  => WordPress\Post::title( $post ),
				'href'   => WordPress\Post::overview( $post ),
				'target' => '_blank',
			], $identity );
		}

		$caption = sprintf( '%s: ', Core\HTML::code( $zeroise ) );

		if ( array_key_exists( $zeroise, $data ) ) {

			$caption.= sprintf( '[%s][%s]', $data[$zeroise]['province'], $data[$zeroise]['city'] );

			if ( isset( $data[$zeroise]['maybe'] ) )
				$caption.= sprintf( '[%s]', $data[$zeroise]['maybe'] );
		}

		foreach ( $list as $city => $codes )
			$table[$city] = implode( ' | ', $codes );

		echo self::toolboxCardOpen( $caption, FALSE );

			Core\HTML::tableSide( $table, FALSE );

		echo '</div>';
	}
}
