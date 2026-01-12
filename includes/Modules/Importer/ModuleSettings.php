<?php namespace geminorum\gEditorial\Modules\Importer;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class ModuleSettings extends gEditorial\Settings
{

	const MODULE = 'importer';

	const ACTION_CLEANUP_RAW_DATA = 'do_tool_cleanup_raw_data';

	public static function renderCard_cleanup_raw_data( $posttypes )
	{
		echo self::toolboxCardOpen( _x( 'Clean-up Raw Data', 'Card Title', 'geditorial-importer' ) );

		foreach ( $posttypes as $posttype => $label )
			echo Core\HTML::button( sprintf(
				/* translators: `%s`: post-type label */
				_x( 'On %s', 'Button', 'geditorial-importer' ),
				$label
			), add_query_arg( [
				'action' => static::ACTION_CLEANUP_RAW_DATA,
				'type'   => $posttype,
			] ) );

			Core\HTML::desc( _x( 'Tries to clean imported raw meta-data.', 'Button Description', 'geditorial-importer' ) );

		echo '</div></div>';
	}

	public static function handleTool_cleanup_raw_data( $posttype, $metakeys, $limit = 25 )
	{
		list( $posts, $pagination ) = Tablelist::getPosts( [ 'orderby' => 'none' ], [], $posttype, $limit );

		if ( empty( $posts ) )
			return self::processingAllDone();

		echo self::processingListOpen();

		foreach ( $posts as $post )
			self::post_cleanup_raw_data( $post, $metakeys, TRUE );

		echo '</ul></div>';

		return WordPress\Redirect::doJS( add_query_arg( [
			'action' => static::ACTION_CLEANUP_RAW_DATA,
			'type'   => $posttype,
			'paged'  => self::paged() + 1,
		] ) );
	}

	public static function post_cleanup_raw_data( $post, $metakeys, $verbose = FALSE )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return self::processingListItem( $verbose, gEditorial\Plugin::wrong( FALSE ) );

		if ( ! $meta = WordPress\Post::getMeta( $post, FALSE ) )
			return self::processingListItem( $verbose,
				/* translators: `%1$s`: post title, `%2$s`: post id */
				_x( 'No meta-data available on &ldquo;%1$s&rdquo; (%2$s).', 'Notice', 'geditorial-importer' ), [
					WordPress\Post::title( $post ),
					Core\HTML::code( $post->ID ),
				] );

		foreach ( $metakeys as $metakey ) {

			$keys = Core\Arraay::prepString(
				$metakey, // original key
				array_keys( Core\Arraay::getByKeyLike( $meta, sprintf( '/^%s_+/', $metakey ) ) )
			);

			foreach ( $keys as $key )
				delete_post_meta( $post->ID, $key );
		}

		return self::processingListItem( $verbose,
			/* translators: `%1$s`: post title, `%2$s`: post id */
			_x( 'Imported raw meta-data cleaned on &ldquo;%1$s&rdquo; (%2$s).', 'Notice', 'geditorial-importer' ), [
				WordPress\Post::title( $post ),
				Core\HTML::code( $post->ID ),
			], TRUE );
	}
}
