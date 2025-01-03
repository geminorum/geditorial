<?php namespace geminorum\gEditorial\Modules\Personage;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class ModuleSettings extends gEditorial\Settings
{

	const MODULE = 'personage';

	const ACTION_PARSE_FULLNAME  = 'do_import_parse_fullname';
	const ACTION_DELETE_FULLNAME = 'do_import_delete_fullname';
	const ACTION_FROM_FULLNAME   = 'do_import_from_fullname';
	const ACTION_PARSE_POOL      = 'do_tool_parse_pool';
	const INPUT_PARSE_POOL       = 'parse_pool_raw_data';

	public static function renderCard_from_fullname( $field )
	{
		echo self::toolboxCardOpen( _x( 'Full-name Operations', 'Card Title', 'geditorial-personage' ) );

			self::submitButton( add_query_arg( [
					'action' => static::ACTION_FROM_FULLNAME,
				] ), _x( 'From Full-name', 'Button', 'geditorial-personage' ), 'link' );

			self::submitButton( add_query_arg( [
					'action' => static::ACTION_PARSE_FULLNAME,
				] ), _x( 'Parse Full-name', 'Button', 'geditorial-personage' ), 'link' );

			self::submitButton( add_query_arg( [
					'action' => static::ACTION_DELETE_FULLNAME,
				] ), _x( 'Delete Full-name', 'Button', 'geditorial-personage' ), 'link' );

			Core\HTML::desc( sprintf(
				/* translators: %s: field key placeholder */
				_x( 'Tries to fill the name fields base on %s field data.', 'Message', 'geditorial-personage' ),
				Core\HTML::code( $field['name'] )
			) );
		echo '</div></div>';
	}

	public static function handleImport_from_fullname( $posttype, $fullname, $first_name, $middle_name, $last_name, $limit = 25 )
	{
		$action = self::req( 'action' );
		$query  = [ 'meta_query' => [] ];

		switch ( $action ) {

			case static::ACTION_PARSE_FULLNAME:

				$query['meta_query'][] = [
					'key'     => $first_name['metakey'],
					'compare' => 'EXISTS',
				];

				$query['meta_query'][] = [
					'key'     => $last_name['metakey'],
					'compare' => 'EXISTS',
				];

				break;

			case static::ACTION_DELETE_FULLNAME:

				$query['meta_query'][] = [
					'key'     => $first_name['metakey'],
					'compare' => 'EXISTS',
				];

				$query['meta_query'][] = [
					'key'     => $last_name['metakey'],
					'compare' => 'EXISTS',
				];

				$query['meta_query'][] = [
					'key'     => $fullname['metakey'],
					'compare' => 'EXISTS',
				];

				break;

			case static::ACTION_FROM_FULLNAME:

				$query['meta_query'][] = [
					'key'     => $first_name['metakey'],
					'compare' => 'NOT EXISTS',
				];

				$query['meta_query'][] = [
					'key'     => $last_name['metakey'],
					'compare' => 'NOT EXISTS',
				];

				$query['meta_query'][] = [
					'key'     => $fullname['metakey'],
					'compare' => 'EXISTS',
				];

				break;

			default:
				return FALSE;
		}

		list( $posts, $pagination ) = Tablelist::getPosts( $query, [], $posttype, $limit );

		if ( empty( $posts ) )
			return self::processingAllDone();

		echo self::processingListOpen();

		foreach ( $posts as $post ) {
			switch ( $action ) {

				case static::ACTION_PARSE_FULLNAME:

					self::post_set_parse_fullname(
						$post,
						$fullname,
						$first_name,
						$middle_name,
						$last_name,
						TRUE
					);

					break;

				case static::ACTION_DELETE_FULLNAME:

					self::post_set_delete_fullname(
						$post,
						$fullname,
						$first_name,
						$middle_name,
						$last_name,
						TRUE
					);

					break;

				case static::ACTION_FROM_FULLNAME:

					self::post_set_from_fullname(
						$post,
						$fullname,
						$first_name,
						$middle_name,
						$last_name,
						TRUE
					);
			}
		}

		echo '</ul></div>';

		return Core\WordPress::redirectJS( add_query_arg( [
			'action' => $action,
			'paged'  => self::paged() + 1,
		] ) );
	}

	public static function post_set_parse_fullname( $post, $fullname, $first_name, $middle_name, $last_name, $verbose = FALSE )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$_first_name  = $first_name  ? get_post_meta( $post->ID, $first_name['metakey'],  TRUE ) : FALSE;
		$_middle_name = $middle_name ? get_post_meta( $post->ID, $middle_name['metakey'], TRUE ) : FALSE;
		$_last_name   = $last_name   ? get_post_meta( $post->ID, $last_name['metakey'],   TRUE ) : FALSE;

		if ( $first_name && WordPress\Strings::isEmpty( $_first_name ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: first_name string */
				_x( 'Can not parse first-name data available for %s', 'Notice', 'geditorial-personage' ) ),
				Core\HTML::code( $_first_name ) ) : TRUE ) && FALSE;

		if ( $last_name && WordPress\Strings::isEmpty( $_last_name ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: last_name string */
				_x( 'Can not parse last-name data available for %s', 'Notice', 'geditorial-personage' ) ),
				Core\HTML::code( $_last_name ) ) : TRUE ) && FALSE;

		$_fullname = Core\Text::normalizeWhitespace(
			sprintf( '%s %s %s',
				$_first_name,
				$_middle_name ?: '',
				$_last_name
			)
		);

		// bail if cannot parese the data
		if ( ! $parsed = ModuleHelper::parseFullname( $_fullname ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: fullname string */
				_x( 'Can not parse fullname data available for %s', 'Notice', 'geditorial-personage' ) ),
				Core\HTML::code( $_fullname ) ) : TRUE ) && FALSE;

		// bail if no first name
		if ( $first_name && WordPress\Strings::isEmpty( $parsed['first_name'] ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: first_name string */
				_x( 'Can not parse first-name data available for %s', 'Notice', 'geditorial-personage' ) ),
				Core\HTML::code( $_fullname ) ) : TRUE ) && FALSE;

		// bail if no last name
		if ( $last_name && WordPress\Strings::isEmpty( $parsed['last_name'] ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: last_name string */
				_x( 'Can not parse last-name data available for %s', 'Notice', 'geditorial-personage' ) ),
				Core\HTML::code( $_fullname ) ) : TRUE ) && FALSE;

		// bail if cannot store first name meta
		if ( $parsed['first_name'] !== $_first_name && ! update_post_meta( $post->ID, $first_name['metakey'], $parsed['first_name'] ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: fullname string */
				_x( 'There is problem updating first-name for &ldquo;%s&rdquo;', 'Notice', 'geditorial-personage' ) ),
				Core\HTML::code( $_fullname ) ) : TRUE ) && FALSE;

		// bail if cannot store last name meta
		if ( $parsed['last_name'] !== $_last_name && ! update_post_meta( $post->ID, $last_name['metakey'], $parsed['last_name'] ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: fullname string */
				_x( 'There is problem updating last-name for &ldquo;%s&rdquo;', 'Notice', 'geditorial-personage' ) ),
				Core\HTML::code( $_fullname ) ) : TRUE ) && FALSE;

		// override middle name only if not exists
		if ( $middle_name && ! WordPress\Strings::isEmpty( $parsed['middle_name'] )
			&& ! get_post_meta( $post->ID, $middle_name['metakey'], TRUE ) )
				update_post_meta( $post->ID, $middle_name['metakey'], $parsed['middle_name'] );

		if ( $verbose )
			echo Core\HTML::tag( 'li', sprintf(
				/* translators: %1$s: parsed fullname, %2$s: fullname string, %3$s: post title */
				_x( '&ldquo;%1$s&rdquo; names are set by %2$s on &ldquo;%3$s&rdquo;', 'Notice', 'geditorial-personage' ),
				Core\HTML::escape( $parsed['fullname'] ),
				Core\HTML::code( $_fullname ),
				WordPress\Post::title( $post )
			) );

		return TRUE;
	}

	public static function post_set_delete_fullname( $post, $fullname, $first_name, $middle_name, $last_name, $verbose = FALSE )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $_fullname = get_post_meta( $post->ID, $fullname['metakey'], TRUE ) )
			return FALSE;

		// bail if problem removing the original fullname meta
		if ( ! delete_post_meta( $post->ID, $fullname['metakey'] ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %1$s: fullname string, %2$s: post title */
				_x( 'There is problem removing full-name data (%1$s) for &ldquo;%2$s&rdquo;', 'Notice', 'geditorial-personage' ) ),
				Core\HTML::code( $_fullname ), WordPress\Post::title( $post ) ) : TRUE ) && FALSE;

		if ( $verbose )
			echo Core\HTML::tag( 'li', sprintf(
				/* translators: %1$s: fullname string, %2$s: post title */
				_x( '&ldquo;%1$s&rdquo; full-name data deleted on &ldquo;%2$s&rdquo;', 'Notice', 'geditorial-personage' ),
				Core\HTML::escape( $_fullname ),
				WordPress\Post::title( $post )
			) );

		return TRUE;
	}

	public static function post_set_from_fullname( $post, $fullname, $first_name, $middle_name, $last_name, $verbose = FALSE )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $_fullname = get_post_meta( $post->ID, $fullname['metakey'], TRUE ) )
			return FALSE;

		// bail if cannot parese the data
		if ( ! $parsed = ModuleHelper::parseFullname( $_fullname ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: fullname string */
				_x( 'Can not parse fullname data available for %s', 'Notice', 'geditorial-personage' ) ),
				Core\HTML::code( $_fullname ) ) : TRUE ) && FALSE;

		// bail if no first name
		if ( $first_name && WordPress\Strings::isEmpty( $parsed['first_name'] ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: first_name string */
				_x( 'Can not parse first-name data available for %s', 'Notice', 'geditorial-personage' ) ),
				Core\HTML::code( $_fullname ) ) : TRUE ) && FALSE;

		// bail if no last name
		if ( $last_name && WordPress\Strings::isEmpty( $parsed['last_name'] ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: last_name string */
				_x( 'Can not parse last-name data available for %s', 'Notice', 'geditorial-personage' ) ),
				Core\HTML::code( $_fullname ) ) : TRUE ) && FALSE;

		// bail if cannot store first name meta
		if ( ! update_post_meta( $post->ID, $first_name['metakey'], $parsed['first_name'] ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: fullname string */
				_x( 'There is problem updating first-name for &ldquo;%s&rdquo;', 'Notice', 'geditorial-personage' ) ),
				Core\HTML::code( $_fullname ) ) : TRUE ) && FALSE;

		// bail if cannot store last name meta
		if ( ! update_post_meta( $post->ID, $last_name['metakey'], $parsed['last_name'] ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: fullname string */
				_x( 'There is problem updating last-name for &ldquo;%s&rdquo;', 'Notice', 'geditorial-personage' ) ),
				Core\HTML::code( $_fullname ) ) : TRUE ) && FALSE;

		// override middle name only if not exists
		if ( $middle_name && ! WordPress\Strings::isEmpty( $parsed['middle_name'] )
			&& ! get_post_meta( $post->ID, $middle_name['metakey'], TRUE ) )
				update_post_meta( $post->ID, $middle_name['metakey'], $parsed['middle_name'] );

		// bail if problem removing the original fullname meta
		if ( ! delete_post_meta( $post->ID, $fullname['metakey'] ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %1$s: fullname string, %2$s: post title */
				_x( 'There is problem removing full-name data (%1$s) for &ldquo;%2$s&rdquo;', 'Notice', 'geditorial-personage' ) ),
				Core\HTML::code( $_fullname ), WordPress\Post::title( $post ) ) : TRUE ) && FALSE;

		if ( $verbose )
			echo Core\HTML::tag( 'li', sprintf(
				/* translators: %1$s: parsed fullname, %2$s: fullname string, %3$s: post title */
				_x( '&ldquo;%1$s&rdquo; names are set by %2$s on &ldquo;%3$s&rdquo;', 'Notice', 'geditorial-personage' ),
				Core\HTML::escape( $parsed['fullname'] ),
				Core\HTML::code( $_fullname ),
				WordPress\Post::title( $post )
			) );

		return TRUE;
	}

	public static function handleTool_parse_pool()
	{
		if ( ! $pool = self::req( static::INPUT_PARSE_POOL ) )
			return FALSE;

		$parsed = [];

		foreach ( Core\Text::splitLines( $pool ) as $row )
			$parsed[] = ModuleHelper::parseFullname( $row );

		if ( ! $parsed = array_values( array_filter( $parsed ) ) )
			return FALSE;

		$headers = array_keys( $parsed[0] );

		if ( FALSE !== ( $data = Core\Text::toCSV( array_merge( [ $headers ], $parsed ) ) ) )
			Core\Text::download( $data, Core\File::prepName( 'parsed-pool.csv' ) );

		return TRUE;
	}

	public static function renderCard_parse_pool()
	{
		echo self::toolboxCardOpen( _x( 'People Parser', 'Card Title', 'geditorial-personage' ), FALSE );

		echo Core\HTML::wrap( Core\HTML::tag( 'textarea', [
			'name'         => static::INPUT_PARSE_POOL,
			'rows'         => 5,
			'class'        => 'textarea-autosize',
			'style'        => 'width:100%;',
			'autocomplete' => 'off',
			'placeholder'  => _x( 'One person per line', 'Placeholder', 'geditorial-personage' ),
		], NULL ), 'textarea-wrap' );

		echo '<div class="-wrap -wrap-button-row">';
			self::submitButton( static::ACTION_PARSE_POOL,
				_x( 'Parse Lines', 'Button', 'geditorial-personage' ) );

			Core\HTML::desc( sprintf(
				/* translators: %s: file ext placeholder */
				_x( 'Generates a %s file with parsed parts of each name.', 'Message', 'geditorial-personage' ),
				Core\HTML::code( 'csv' )
			), FALSE );
		echo '</div></div>';
	}
}
