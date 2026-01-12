<?php namespace geminorum\gEditorial\Modules\Audit;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleSettings extends gEditorial\Settings
{

	const MODULE = 'audit';

	const ACTION_FORCE_AUTO_AUDIT   = 'do_tools_force_auto_audit';
	const ACTION_EMPTY_FIELDS_AUDIT = 'do_tools_empty_fields_audit';

	public static function renderCard_force_auto_audit( $posttypes, $taxonomy )
	{
		if ( empty( $posttypes ) )
			return FALSE;

		echo self::toolboxCardOpen( _x( 'Force Auto Audit', 'Card Title', 'geditorial-audit' ) );

			foreach ( $posttypes as $posttype => $label )
				echo Core\HTML::button( sprintf(
					/* translators: `%s`: post-type label */
					_x( 'On %s', 'Button', 'geditorial-audit' ),
					$label
				), add_query_arg( [
					'action' => static::ACTION_FORCE_AUTO_AUDIT,
					'type'   => $posttype,
				] ) );

			Core\HTML::desc( _x( 'Tries to auto-set the attributes on supported posts.', 'Button Description', 'geditorial-audit' ) );

		echo '</div></div>';

		return TRUE;
	}

	public static function handleTool_force_auto_audit( $posttype, $taxonomy, $limit = 25 )
	{
		$query = [];

		list( $posts, $pagination ) = gEditorial\Tablelist::getPosts( $query, [], $posttype, $limit );

		if ( empty( $posts ) )
			return self::processingAllDone();

		echo self::processingListOpen();

		foreach ( $posts as $post )
			self::_post_force_auto_audit( $post, $taxonomy, TRUE );

		echo '</ul></div>';

		return WordPress\Redirect::doJS( add_query_arg( [
			'action' => static::ACTION_FORCE_AUTO_AUDIT,
			'type'   => $posttype,
			'paged'  => self::paged() + 1,
		] ) );
	}

	private static function _post_force_auto_audit( $post, $taxonomy = NULL, $verbose = FALSE )
	{
		if ( ! $result = ModuleHelper::doAutoAuditPost( $post, TRUE, $taxonomy ) )
			return self::processingListItem( $verbose,
				/* translators: `%s`: post title */
				_x( 'No Audits applied for &ldquo;%s&rdquo;.', 'Notice', 'geditorial-audit' ), [
					WordPress\Post::title( $post ),
				] );

		return self::processingListItem( $verbose,
			/* translators: `%1$s`: count terms, `%2$s`: post title */
			_x( '%1$s attributes set for &ldquo;%2$s&rdquo;.', 'Notice', 'geditorial-audit' ), [
				Core\HTML::code( count( $result ) ),
				WordPress\Post::title( $post ),
			], TRUE );
	}

	// TODO: auto-audit: mark no thumbnail with selected attribute
	public static function renderToolsEmptyFields( $list, $taxonomy, $lite = FALSE )
	{
		$posttypes = array_keys( $list );
		$empty     = TRUE;

		echo self::toolboxCardOpen( '', FALSE );

		if ( term_exists( ModuleHelper::getAttributeSlug( 'empty_title' ), $taxonomy ) ) {

			Core\HTML::h4( _x( 'Posts with Empty Title', 'Card Title', 'geditorial-audit' ), 'title' );
			if ( ! $lite ) self::renderToolsEmptyFieldsSummary( $posttypes, $taxonomy, 'empty_title' );

			echo '<div class="-wrap -wrap-button-row">';
			echo Core\HTML::dropdown( $list, [
				'name'       => 'posttype-empty-title',
				'none_title' => _x( 'All Supported Post-Types', 'Card: None-Title', 'geditorial-audit' ),
			] );

			// FIXME: use `Settings::actionButton()`
			self::submitButton( static::ACTION_EMPTY_FIELDS_AUDIT.'[mark_empty_title]', _x( 'Mark Posts', 'Card: Button', 'geditorial-audit' ) );
			self::submitButton( static::ACTION_EMPTY_FIELDS_AUDIT.'[flush_empty_title]', _x( 'Flush Attribute', 'Card: Button', 'geditorial-audit' ), 'danger', TRUE, '' );
			Core\HTML::desc( _x( 'Tries to set the attribute on supported posts with no title.', 'Card: Description', 'geditorial-audit' ) );

			echo '</div>';
			$empty = FALSE;
		}

		if ( term_exists( ModuleHelper::getAttributeSlug( 'empty_content' ), $taxonomy ) ) {

			Core\HTML::h4( _x( 'Posts with Empty Content', 'Card Title', 'geditorial-audit' ), 'title' );
			if ( ! $lite ) self::renderToolsEmptyFieldsSummary( $posttypes, $taxonomy, 'empty_content' );

			echo '<div class="-wrap -wrap-button-row">';
			echo Core\HTML::dropdown( $list, [
				'name'       => 'posttype-empty-content',
				'none_title' => _x( 'All Supported Post-Types', 'Card: None-Title', 'geditorial-audit' ),
			] );

			self::submitButton( static::ACTION_EMPTY_FIELDS_AUDIT.'[mark_empty_content]', _x( 'Mark Posts', 'Card: Button', 'geditorial-audit' ) );
			self::submitButton( static::ACTION_EMPTY_FIELDS_AUDIT.'[flush_empty_content]', _x( 'Flush Attribute', 'Card: Button', 'geditorial-audit' ), 'danger', TRUE, '' );
			Core\HTML::desc( _x( 'Tries to set the attribute on supported posts with no content.', 'Card: Description', 'geditorial-audit' ) );

			echo '</div>';
			$empty = FALSE;
		}

		if ( term_exists( ModuleHelper::getAttributeSlug( 'empty_excerpt' ), $taxonomy ) ) {

			Core\HTML::h4( _x( 'Posts with Empty Excerpt', 'Card Title', 'geditorial-audit' ), 'title' );
			if ( ! $lite ) self::renderToolsEmptyFieldsSummary( $posttypes, $taxonomy, 'empty_excerpt' );

			echo '<div class="-wrap -wrap-button-row">';
			echo Core\HTML::dropdown( $list, [
				'name'       => 'posttype-empty-excerpt',
				'none_title' => _x( 'All Supported Post-Types', 'Card: None-Title', 'geditorial-audit' ),
			] );

			self::submitButton( static::ACTION_EMPTY_FIELDS_AUDIT.'[mark_empty_excerpt]', _x( 'Mark Posts', 'Card: Button', 'geditorial-audit' ) );
			self::submitButton( static::ACTION_EMPTY_FIELDS_AUDIT.'[flush_empty_excerpt]', _x( 'Flush Attribute', 'Card: Button', 'geditorial-audit' ), 'danger', TRUE, '' );
			Core\HTML::desc( _x( 'Tries to set the attribute on supported posts with no excerpt.', 'Card: Description', 'geditorial-audit' ) );

			echo '</div>';
			$empty = FALSE;
		}

		if ( $empty ) {

			Core\HTML::h4( _x( 'Posts with Empty Fields', 'Card Title', 'geditorial-audit' ), 'title' );
			Core\HTML::desc( _x( 'No empty attribute available. Please install the default attributes.', 'Message', 'geditorial-audit' ), TRUE, '-empty' );
		}

		echo '</div>';
	}

	public static function renderToolsEmptyFieldsSummary( $posttypes, $taxonomy, $for )
	{
		if ( ! $attribute = ModuleHelper::getAttributeSlug( $for ) )
			return;

		$posts = ModuleHelper::getPostsEmpty( $for, $attribute, $posttypes, FALSE );
		$count = WordPress\Taxonomy::countTermObjects( $attribute, $taxonomy );

		Core\HTML::desc( vsprintf(
			/* translators: `%1$s`: empty post count, `%2$s`: assigned term count */
			_x( 'Currently found %1$s empty posts and %2$s assigned to the attribute.', 'Card: Description', 'geditorial-audit' ),
			[
				FALSE === $posts ? gEditorial()->na() : Core\Number::format( count( $posts ) ),
				FALSE === $count ? gEditorial()->na() : Core\Number::format( $count ),
			]
		) );
	}

	public static function handleToolsEmptyFields( $action, $taxonomy )
	{
		switch ( Core\Arraay::keyFirst( $action ) ) {

			case 'flush_empty_title':

				$attribute = ModuleHelper::getAttributeSlug( 'empty_title' );

				if ( FALSE === ( $count = WordPress\Taxonomy::removeTermObjects( $attribute, $taxonomy ) ) )
					WordPress\Redirect::doReferer( 'wrong' );

				WordPress\Term::updateCount( $attribute, $taxonomy );

				WordPress\Redirect::doReferer( [
					'message' => 'emptied',
					'count'   => $count,
				] );
				break;

			case 'flush_empty_content':

				$attribute = ModuleHelper::getAttributeSlug( 'empty_content' );

				if ( FALSE === ( $count = WordPress\Taxonomy::removeTermObjects( $attribute, $taxonomy ) ) )
					WordPress\Redirect::doReferer( 'wrong' );

				WordPress\Term::updateCount( $attribute, $taxonomy );

				WordPress\Redirect::doReferer( [
					'message' => 'emptied',
					'count'   => $count,
				] );
				break;

			case 'flush_empty_excerpt':

				$attribute = ModuleHelper::getAttributeSlug( 'empty_excerpt' );

				if ( FALSE === ( $count = WordPress\Taxonomy::removeTermObjects( $attribute, $taxonomy ) ) )
					WordPress\Redirect::doReferer( 'wrong' );

				WordPress\Term::updateCount( $attribute, $taxonomy );

				WordPress\Redirect::doReferer( [
					'message' => 'emptied',
					'count'   => $count,
				] );
				break;

			case 'mark_empty_title':

				$posttypes = self::req( 'posttype-empty-title' ) ?: NULL;
				$attribute = ModuleHelper::getAttributeSlug( 'empty_title' );

				if ( FALSE === ( $posts = ModuleHelper::getPostsEmpty( 'title', $attribute, $posttypes ) ) )
					WordPress\Redirect::doReferer( 'wrong' );

				if ( empty( $posts ) )
					WordPress\Redirect::doReferer( 'nochange' );

				if ( FALSE === ( $count = WordPress\Taxonomy::setTermObjects( $posts, $attribute, $taxonomy ) ) )
					WordPress\Redirect::doReferer( 'wrong' );

				WordPress\Term::updateCount( $attribute, $taxonomy );

				WordPress\Redirect::doReferer( [
					'message' => 'synced',
					'count'   => $count,
				] );
				break;

			case 'mark_empty_content':

				$posttypes = self::req( 'posttype-empty-content' ) ?: NULL;
				$attribute = ModuleHelper::getAttributeSlug( 'empty_content' );

				if ( FALSE === ( $posts = ModuleHelper::getPostsEmpty( 'content', $attribute, $posttypes ) ) )
					WordPress\Redirect::doReferer( 'wrong' );

				if ( empty( $posts ) )
					WordPress\Redirect::doReferer( 'nochange' );

				if ( FALSE === ( $count = WordPress\Taxonomy::setTermObjects( $posts, $attribute, $taxonomy ) ) )
					WordPress\Redirect::doReferer( 'wrong' );

				WordPress\Term::updateCount( $attribute, $taxonomy );

				WordPress\Redirect::doReferer( [
					'message' => 'synced',
					'count'   => $count,
				] );
				break;

			case 'mark_empty_excerpt':

				$posttypes = self::req( 'posttype-empty-excerpt' ) ?: NULL;
				$attribute = ModuleHelper::getAttributeSlug( 'empty_excerpt' );

				if ( FALSE === ( $posts = ModuleHelper::getPostsEmpty( 'excerpt', $attribute, $posttypes ) ) )
					WordPress\Redirect::doReferer( 'wrong' );

				if ( empty( $posts ) )
					WordPress\Redirect::doReferer( 'nochange' );

				if ( FALSE === ( $count = WordPress\Taxonomy::setTermObjects( $posts, $attribute, $taxonomy ) ) )
					WordPress\Redirect::doReferer( 'wrong' );

				WordPress\Term::updateCount( $attribute, $taxonomy );

				WordPress\Redirect::doReferer( [
					'message' => 'synced',
					'count'   => $count,
				] );
				break;
		}

		WordPress\Redirect::doReferer( 'huh' );
	}
}
