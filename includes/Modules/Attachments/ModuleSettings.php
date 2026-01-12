<?php namespace geminorum\gEditorial\Modules\Attachments;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class ModuleSettings extends gEditorial\Settings
{

	const MODULE = 'attachments';

	const ACTION_REATTACH_THUMBNAILS = 'do_tool_reattach_thumbnails';
	const ACTION_EMPTY_RAW_METADATA  = 'do_tool_empty_raw_metadata';
	const ACTION_DELETION_BY_MIME    = 'do_tool_deletion_by_mime';

	public static function renderCard_reattach_thumbnails( $posttypes )
	{
		echo self::toolboxCardOpen( _x( 'Re-attach Un-Parented', 'Card Title', 'geditorial-attachments' ) );

			foreach ( $posttypes as $posttype => $label )
				echo Core\HTML::button( sprintf(
					/* translators: `%s`: post-type label */
					_x( 'On %s', 'Button', 'geditorial-attachments' ),
					$label
				), add_query_arg( [
					'action' => static::ACTION_REATTACH_THUMBNAILS,
					'type'   => $posttype,
				] ) );

			Core\HTML::desc( _x( 'Tries to re-attach un-parented via thumbnail meta-data.', 'Button Description', 'geditorial-attachments' ) );

		echo '</div></div>';
	}

	public static function renderCard_empty_raw_metadata( $posttypes )
	{
		echo self::toolboxCardOpen( _x( 'Empty Meta-data', 'Card Title', 'geditorial-attachments' ) );

			foreach ( $posttypes as $posttype => $label )
				echo Core\HTML::button( sprintf(
					/* translators: `%s`: post-type label */
					_x( 'On %s', 'Button', 'geditorial-attachments' ),
					$label
				), add_query_arg( [
					'action' => static::ACTION_EMPTY_RAW_METADATA,
					'type'   => $posttype,
				] ) );

			Core\HTML::desc( _x( 'Tries to clean attachemnt raw meta-data.', 'Button Description', 'geditorial-attachments' ) );

		echo '</div></div>';
	}

	public static function renderCard_deletion_by_mime( $mimetypes, $extensions = NULL )
	{
		echo self::toolboxCardOpen( _x( 'Deletion by MIME', 'Card Title', 'geditorial-attachments' ) );

			foreach ( $mimetypes as $mimetype )
				echo Core\HTML::button(
					WordPress\Media::getExtension( $mimetype, $extensions ) ?: $mimetype,
					add_query_arg( [
						'action' => static::ACTION_DELETION_BY_MIME,
						'mime'   => $mimetype,
					] )
				);

			Core\HTML::desc( _x( 'Tries to delete attachemnts by MIME types.', 'Button Description', 'geditorial-attachments' ) );

		echo '</div></div>';
	}

	public static function handleTool_empty_raw_metadata( $posttype, $limit = 25 )
	{
		global $wpdb;

		$paged = self::paged();

		$query = $wpdb->prepare( "
			SELECT p1.ID
			FROM {$wpdb->posts} AS p1
			INNER JOIN {$wpdb->posts} AS p2
			ON p1.post_parent = p2.ID
			WHERE p1.post_type = '%s'
			AND p2.post_type = '%s'
			LIMIT %d
			OFFSET %d
		", 'attachment', $posttype, $limit, ( ( $paged - 1 ) * $limit ) );

		$results = $wpdb->get_col( $query );

		if ( empty( $results ) )
			return self::processingAllDone();

		echo self::processingListOpen();

		foreach ( $results as $attachment_id )
			self::attachment_empty_raw_metadata( (int) $attachment_id, TRUE );

		echo '</ul></div>';

		return WordPress\Redirect::doJS( add_query_arg( [
			'action' => static::ACTION_EMPTY_RAW_METADATA,
			'type'   => $posttype,
			'paged'  => $paged + 1,
		] ) );
	}

	public static function handleTool_deletion_by_mime( $mimetype, $limit = 25 )
	{
		list( $attachments, $pagination ) = Tablelist::getAttachments( [
			'post_mime_type' => $mimetype,
		], [], 'attachment', $limit );

		if ( empty( $attachments ) )
			return self::processingAllDone();

		echo self::processingListOpen();

		foreach ( $attachments as $attachment )
			self::attachment_delete_by_mime( $attachment, TRUE );

		echo '</ul></div>';

		return WordPress\Redirect::doJS( add_query_arg( [
			'action' => static::ACTION_DELETION_BY_MIME,
			'mime'   => $mimetype,
		] ) );
	}

	public static function attachment_empty_raw_metadata( $attachment_id, $verbose = FALSE )
	{
		if ( ! $attachment = WordPress\Post::get( $attachment_id ) )
			return self::processingListItem( $verbose,
				/* translators: `%s`: attachment id */
				_x( 'Attachment ID (%s) is not valid!', 'Notice', 'geditorial-attachments' ), [
					Core\HTML::code( $attachment_id ),
				] );

		if ( ! WordPress\Media::emptyAttachmentImageMeta( $attachment->ID ) )
			return self::processingListItem( $verbose,
				/* translators: `%1$s`: attachment title, `%2$s`: attachment id */
				_x( 'There is problem cleaning raw meta-data on &ldquo;%2$s&rdquo; (%1$s).', 'Notice', 'geditorial-attachments' ), [
					WordPress\Post::title( $attachment ),
					Core\HTML::code( $attachment->ID ),
				] );

		return self::processingListItem( $verbose,
			/* translators: `%1$s`: attachment title, `%2$s`: attachment id */
			_x( 'Raw meta-data cleaned on &ldquo;%1$s&rdquo; (%2$s).', 'Notice', 'geditorial-attachments' ), [
				WordPress\Post::title( $attachment ),
				Core\HTML::code( $attachment->ID ),
			], TRUE );
	}

	public static function attachment_delete_by_mime( $attachment, $verbose = FALSE )
	{
		if ( ! $attachment = WordPress\Post::get( $attachment ) )
			return self::processingListItem( $verbose, gEditorial\Plugin::wrong( FALSE ) );

		if ( ! wp_delete_attachment( $attachment->ID, TRUE ) )
			return self::processingListItem( $verbose,
				/* translators: `%1$s`: attachment title, `%2$s`: attachment id */
				_x( 'There is problem deleting attachment &ldquo;%1$s&rdquo; (%2$s).', 'Notice', 'geditorial-attachments' ), [
					WordPress\Post::title( $attachment ),
					Core\HTML::code( $attachment->ID ),
				] );

		return self::processingListItem( $verbose,
			/* translators: `%1$s`: attachment title, `%2$s`: attachment id */
			_x( '&ldquo;%1$s&rdquo; attachemnt (%2$s) successfully deleted!', 'Notice', 'geditorial-attachments' ), [
				WordPress\Post::title( $attachment ),
				Core\HTML::code( $attachment->ID ),
			], TRUE );
	}

	public static function handleTool_reattach_thumbnails( $posttype, $limit = 25 )
	{
		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT p1.ID as attachment, p2.ID as parent
			FROM {$wpdb->posts} AS p1
			INNER JOIN {$wpdb->posts} AS p2
			LEFT JOIN {$wpdb->postmeta} AS pm
			ON p1.ID = pm.meta_value
			AND p2.ID = pm.post_id
			WHERE pm.meta_key = '%s'
			AND p1.post_parent = 0
			AND p2.post_type = '%s'
			LIMIT %d
		", '_thumbnail_id', $posttype, $limit );

		$results = $wpdb->get_results( $query );

		if ( empty( $results ) )
			return self::processingAllDone();

		echo self::processingListOpen();

		foreach ( $results as $data )
			self::attachment_set_parent_data( (int) $data->attachment, (int) $data->parent, TRUE );

		echo '</ul></div>';

		return WordPress\Redirect::doJS( add_query_arg( [
			'action' => static::ACTION_REATTACH_THUMBNAILS,
			'type'   => $posttype,
		] ) );
	}

	public static function attachment_set_parent_data( $attachment_id, $parent_id, $verbose = FALSE )
	{
		if ( ! $attachment = WordPress\Post::get( $attachment_id ) )
			return self::processingListItem( $verbose,
				/* translators: `%s`: attachment id */
				_x( 'Attachment ID (%s) is not valid!', 'Notice', 'geditorial-attachments' ), [
					Core\HTML::code( $attachment_id ),
				] );

		if ( ! $parent = WordPress\Post::get( $parent_id ) )
			return self::processingListItem( $verbose,
				/* translators: `%s`: parent id */
				_x( 'Parent ID (%s) is not valid!', 'Notice', 'geditorial-attachments' ), [
					Core\HTML::code( $parent_id ),
				] );

		if ( ! WordPress\Post::setParent( $attachment->ID, $parent->ID, FALSE ) )
			return self::processingListItem( $verbose,
				/* translators: `%1$s`: attachment id, `%2$s`: parent title */
				_x( 'There is problem setting attachment (%1$s) parent to &ldquo;%2$s&rdquo;.', 'Notice', 'geditorial-attachments' ), [
					Core\HTML::code( $attachment->ID ),
					WordPress\Post::title( $parent ),
				] );

		return self::processingListItem( $verbose,
			/* translators: `%1$s`: attachment title, `%2$s`: attachment id, `%3$s`: parent title */
			_x( '&ldquo;%1$s&rdquo; attachemnt (%2$s) parent is set to &ldquo;%3$s&rdquo;.', 'Notice', 'geditorial-attachments' ), [
				WordPress\Post::title( $attachment ),
				Core\HTML::code( $attachment->ID ),
				WordPress\Post::title( $parent ),
			], TRUE );
	}
}
