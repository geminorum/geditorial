<?php namespace geminorum\gEditorial\Modules\Iranian;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleSettings extends gEditorial\Settings
{

	const MODULE = 'iranian';

	const ACTION_IDENTITY_CERTIFICATE = 'do_tool_identity_certificate';
	const ACTION_LOCATION_BY_IDENTITY = 'do_import_location_by_identity';
	const ACTION_COUNTRY_SUMMARY      = 'do_report_country_summary';
	const ACTION_CITY_SUMMARY         = 'do_report_city_summary';

	public static function renderCard_identity_certificate( $posttypes )
	{
		if ( empty( $posttypes ) )
			return FALSE;

		echo self::toolboxCardOpen( _x( 'Compare Identity to Birth Certificate', 'Card Title', 'geditorial-iranian' ) );

			foreach ( $posttypes as $posttype => $label )
				echo Core\HTML::button( sprintf(
					/* translators: `%s`: post-type label */
					_x( 'Compare Identity for %s', 'Button', 'geditorial-iranian' ),
					$label
				), add_query_arg( [
					'action' => static::ACTION_IDENTITY_CERTIFICATE,
					'type'   => $posttype,
				] ) );

			Core\HTML::desc( _x( 'Tries to un-set the certificate duplicated from identity data.', 'Button Description', 'geditorial-iranian' ) );
		echo '</div></div>';

		return TRUE;
	}

	public static function handleTool_identity_certificate( $posttype, $identity_metakey, $certificate, $limit = 25 )
	{
		$query = [
			'meta_query' => [
				[
					'key'     => $identity_metakey,
					'compare' => 'EXISTS',
				],
				[
					'key'     => $certificate['metakey'],
					'compare' => 'EXISTS',
				],
			],
		];

		list( $posts, $pagination ) = gEditorial\Tablelist::getPosts( $query, [], $posttype, $limit );

		if ( empty( $posts ) )
			return self::processingAllDone();

		echo self::processingListOpen();

		foreach ( $posts as $post )
			self::post_compare_identity_certificate( $post, $identity_metakey, $certificate, TRUE );

		echo '</ul></div>';

		return WordPress\Redirect::doJS( add_query_arg( [
			'action' => static::ACTION_IDENTITY_CERTIFICATE,
			'type'   => $posttype,
			'paged'  => self::paged() + 1,
		] ) );
	}

	public static function post_compare_identity_certificate( $post, $identity_metakey, $certificate_field, $verbose = FALSE )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return self::processingListItem( $verbose, gEditorial\Plugin::wrong( FALSE ) );

		if ( ! $certificate = get_post_meta( $post->ID, $certificate_field['metakey'], TRUE ) )
			return self::processingListItem( $verbose, gEditorial\Plugin::wrong( FALSE ) );

		$cleaned = Core\Text::stripNonNumeric( Core\Text::trim( $certificate ) );

		if ( WordPress\Strings::isEmpty( $cleaned ) ) {

			if ( ! delete_post_meta( $post->ID, $certificate_field['metakey'] ) )
				return self::processingListItem( $verbose,
					/* translators: `%s`: post title */
					_x( 'There is problem removing Birth Certificate Number for &ldquo;%s&rdquo;.', 'Notice', 'geditorial-iranian' ), [
						WordPress\Post::title( $post ),
					] );

			return self::processingListItem( $verbose,
				/* translators: `%1$s`: birth certificate number, `%2$s`: post title */
				_x( 'Birth Certificate Number %1$s removed for &ldquo;%2$s&rdquo;.', 'Notice', 'geditorial-iranian' ), [
					Core\HTML::code( $certificate ),
					WordPress\Post::title( $post ),
				], TRUE );
		}

		if ( ! $identity = get_post_meta( $post->ID, $identity_metakey, TRUE ) )
			return self::processingListItem( $verbose, gEditorial\Plugin::wrong( FALSE ) );

		if ( $identity !== Core\Validation::sanitizeIdentityNumber( $cleaned ) )
			return self::processingListItem( $verbose,
				/* translators: `%1$s`: identity code, `%2$s`: birth certificate number */
				_x( 'Identity (%1$s) and Birth Certificate Number (%2$s) are diffrent!', 'Notice', 'geditorial-iranian' ), [
					Core\HTML::code( $identity ),
					Core\HTML::code( $certificate ),
				] );

		if ( ! delete_post_meta( $post->ID, $certificate_field['metakey'] ) )
			return self::processingListItem( $verbose,
				/* translators: `%s`: post title */
				_x( 'There is problem removing Birth Certificate Number for &ldquo;%s&rdquo;.', 'Notice', 'geditorial-iranian' ), [
					WordPress\Post::title( $post ),
				] );

		return self::processingListItem( $verbose,
			/* translators: `%1$s`: birth certificate number, `%2$s`: post title */
			_x( 'Birth Certificate Number %1$s removed for &ldquo;%2$s&rdquo;.', 'Notice', 'geditorial-iranian' ), [
				Core\HTML::code( $certificate ),
				WordPress\Post::title( $post ),
			], TRUE );
	}

	public static function renderCard_location_by_identity( $posttypes )
	{
		if ( empty( $posttypes ) )
			return FALSE;

		echo self::toolboxCardOpen( _x( 'Location by Identity', 'Card Title', 'geditorial-iranian' ) );

			foreach ( $posttypes as $posttype => $label )
				echo Core\HTML::button( sprintf(
					/* translators: `%s`: post-type label */
					_x( 'On %s', 'Button', 'geditorial-iranian' ),
					$label
				), add_query_arg( [
					'action' => static::ACTION_LOCATION_BY_IDENTITY,
					'type'   => $posttype,
				] ) );

			Core\HTML::desc( _x( 'Tries to set the location based on identity data.', 'Button Description', 'geditorial-iranian' ) );

		echo '</div></div>';

		return TRUE;
	}

	// NOTE: until the JSON city *data* is not complete, the query must be `paged`
	public static function handleImport_location_by_identity( $posttype, $data, $identity_metakey, $location_metakey, $limit = 25 )
	{
		$query = [
			'meta_query' => [
				[
					'key'     => $location_metakey,
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => $identity_metakey,
					'compare' => 'EXISTS',
				],
			],
		];

		list( $posts, $pagination ) = gEditorial\Tablelist::getPosts( $query, [], $posttype, $limit );

		if ( empty( $posts ) )
			return self::processingAllDone();

		echo self::processingListOpen();

		foreach ( $posts as $post )
			self::post_set_location_from_identity( $post, $data, $identity_metakey, $location_metakey, TRUE );

		echo '</ul></div>';

		return WordPress\Redirect::doJS( add_query_arg( [
			'action' => static::ACTION_LOCATION_BY_IDENTITY,
			'type'   => $posttype,
			'paged'  => self::paged() + 1,
		] ) );
	}

	// TODO: display current location data from post
	public static function post_set_location_from_identity( $post, $data, $identity_metakey, $location_metakey, $verbose = FALSE )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return self::processingListItem( $verbose, gEditorial\Plugin::wrong( FALSE ) );

		// TODO: add setting for override
		if ( $location = get_post_meta( $post->ID, $location_metakey, TRUE ) )
			return FALSE;

		if ( ! $identity = get_post_meta( $post->ID, $identity_metakey, TRUE ) )
			return self::processingListItem( $verbose, gEditorial\Plugin::wrong( FALSE ) );

		$sanitized = Core\Number::zeroise( Core\Number::translate( trim( $identity ) ), 10 );

		if ( ! $location = ModuleHelper::getLocationFromIdentity( $sanitized, $data ) )
			return self::processingListItem( $verbose,
				/* translators: `%s`: identity code */
				_x( 'No location data available for &ldquo;%s&rdquo;!', 'Notice', 'geditorial-iranian' ), [
					Core\HTML::code( $sanitized ),
				] );

		if ( ! isset( $location['city'] ) )
			return self::processingListItem( $verbose,
				/* translators: `%s`: identity code */
				_x( 'No city data available for &ldquo;%s&rdquo;!', 'Notice', 'geditorial-iranian' ), [
					Core\HTML::code( $sanitized ),
				] );

		if ( WordPress\Strings::isEmpty( $location['city'] ) )
			return self::processingListItem( $verbose,
				/* translators: `%1$s`: city data, `%2$s`: identity code */
				_x( 'City data is empty for %1$s: %2$s!', 'Notice', 'geditorial-iranian' ), [
					Core\HTML::code( $sanitized ),
					Core\HTML::code( $location['city'] ),
				] );

		if ( ! update_post_meta( $post->ID, $location_metakey, $location['city'] ) )
			return self::processingListItem( $verbose,
				/* translators: `%s`: post title */
				_x( 'There is problem updating location for &ldquo;%s&rdquo;.', 'Notice', 'geditorial-iranian' ), [
					WordPress\Post::title( $post ),
				] );

		return self::processingListItem( $verbose,
			/* translators: `%1$s`: city data, `%2$s`: identity code, `%3$s`: post title */
			_x( '&ldquo;%1$s&rdquo; city is set by %2$s on &ldquo;%3$s&rdquo;.', 'Notice', 'geditorial-iranian' ), [
				Core\HTML::escape( $location['city'] ),
				Core\HTML::code( $identity ),
				WordPress\Post::title( $post ),
			], TRUE );
	}

	public static function renderCard_country_summary( $posttypes )
	{
		if ( empty( $posttypes ) )
			return FALSE;

		echo self::toolboxCardOpen( _x( 'Country Summary', 'Card Title', 'geditorial-iranian' ) );

			foreach ( $posttypes as $posttype => $label )
				echo Core\HTML::button( sprintf(
					/* translators: `%s`: post-type label */
					_x( 'On %s', 'Button', 'geditorial-iranian' ),
					$label
				), add_query_arg( [
					'action' => static::ACTION_COUNTRY_SUMMARY,
					'type'   => $posttype,
				] ) );

			Core\HTML::desc( _x( 'Tries to summarize the country info based on the raw meta-data.', 'Button Description', 'geditorial-iranian' ) );

		echo '</div></div>';

		return TRUE;
	}

	public static function renderCard_city_summary( $posttypes )
	{
		if ( empty( $posttypes ) )
			return FALSE;

		echo self::toolboxCardOpen( _x( 'City Summary', 'Card Title', 'geditorial-iranian' ) );

			foreach ( $posttypes as $posttype => $label )
				echo Core\HTML::button( sprintf(
					/* translators: `%s`: post-type label */
					_x( 'On %s', 'Button', 'geditorial-iranian' ),
					$label
				), add_query_arg( [
					'action' => static::ACTION_CITY_SUMMARY,
					'type'   => $posttype,
				] ) );

			Core\HTML::desc( _x( 'Tries to summarize the city info based on the raw meta-data.', 'Button Description', 'geditorial-iranian' ) );

		echo '</div></div>';

		return TRUE;
	}

	public static function handleReport_country_summary( $posttype, $identity_metakey, $location_metakey, $data = [] )
	{
		$chartname = 'countrysummary';
		$summary   = ModuleHelper::getCountrySummary( $posttype, $identity_metakey, $data );

		echo self::toolboxColumnOpen( _x( 'Iranian Reports', 'Header', 'geditorial-iranian' ) );
			echo self::toolboxCardOpen( _x( 'Country Summary', 'Card Title', 'geditorial-iranian' ), FALSE );
				echo gEditorial\Scripts::markupChartJS( $chartname, static::MODULE );
			echo '</div>';
		echo '</div>';

		gEditorial\Scripts::enqueueChartJS_Bar( $chartname, $summary, [
			'label' => Services\CustomPostType::getLabel( $posttype, 'extended_label' ),
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

			echo self::toolboxAfterOpen( '', TRUE );

				echo Core\HTML::button(
					_x( 'Next Batch', 'Button', 'geditorial-iranian' ),
					add_query_arg( [
						'action' => static::ACTION_CITY_SUMMARY,
						'type'   => $posttype,
						'paged'  => self::paged() + 1,
					] )
				);

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

		$posts = gEditorial\Tablelist::getPosts( $query, [], $posttype, FALSE );
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

		$caption = sprintf( '%s ', Core\HTML::code( $zeroise ) );

		if ( array_key_exists( $zeroise, $data ) ) {

			if ( isset( $data[$zeroise]['province'] ) )
				$caption.= sprintf( '[%s]', $data[$zeroise]['province'] );

			if ( isset( $data[$zeroise]['city'] ) )
				$caption.= sprintf( '[%s]', $data[$zeroise]['city'] );

			if ( isset( $data[$zeroise]['maybe'] ) )
				$caption.= sprintf( '[%s]', $data[$zeroise]['maybe'] );
		}

		foreach ( $list as $city => $codes )
			$table[$city] = implode( ' | ', $codes );

		echo self::toolboxCardOpen( trim( $caption ), FALSE );

			Core\HTML::tableSide( $table, FALSE );

		echo '</div>';
	}
}
