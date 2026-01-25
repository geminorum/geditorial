<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Helper extends WordPress\Main
{
	const BASE = 'geditorial';

	public static function factory()
	{
		return gEditorial();
	}

	// override to use plugin version
	// TODO: Move to `AssetRegistry` Service
	public static function linkStyleSheet( $url, $version = GEDITORIAL_VERSION, $media = FALSE, $verbose = TRUE )
	{
		return Core\HTML::linkStyleSheet( $url, $version, $media, $verbose );
	}

	// TODO: Move to `AssetRegistry` Service
	public static function linkStyleSheetAdmin( $page, $verbose = TRUE, $prefix = 'admin' )
	{
		return Core\HTML::linkStyleSheet( GEDITORIAL_URL.'assets/css/'.( $prefix ? $prefix.'.' : '' ).$page.( is_rtl() ? '-rtl' : '' ).'.css', GEDITORIAL_VERSION, 'all', $verbose );
	}

	/**
	 * Prepares data for display as a contact.
	 * TODO: Move to `Contacts` Service
	 *
	 * @param string $value
	 * @param string $title
	 * @param string $empty
	 * @param bool $icon
	 * @return string
	 */
	public static function prepContact( $value, $title = NULL, $empty = '', $icon = FALSE )
	{
		if ( self::empty( $value ) )
			return $empty;

		if ( Core\Email::is( $value ) )
			$prepared = Core\Email::prep(
				$value,
				[ 'title' => $title ?? $value ],
				$icon ? 'icon' : 'display',
				$icon ? Services\Icons::get( [ 'misc-16', 'envelope-fill' ] ) : NULL
			);

		else if ( Core\URL::isValid( $value ) )
			$prepared = Core\HTML::link(
				$icon ? Services\Icons::get( [ 'misc-16', 'link-45deg' ] ) : Core\URL::prepTitle( $value ),
				$value,
				TRUE
			);

		else if ( Core\Phone::is( $value ) )
			$prepared = Core\Phone::prep(
				$value,
				[ 'title' => $title ],
				$icon ? 'icon' : 'display',
				$icon ? Services\Icons::get( [ 'misc-16', 'telephone-fill' ] ) : NULL
			);

		else
			$prepared = $icon
				? Core\HTML::tag( 'span', [ 'title' => $value ], Services\Icons::get( [ 'misc-16', 'patch-question-fill' ] ) )
				: Core\HTML::escape( $value );

		return apply_filters( static::BASE.'_prep_contact', $prepared, $value, $title );
	}

	public static function renderPostTermsEditRow( $post, $taxonomy, $before = '', $after = '' )
	{
		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return;

		if ( ! $terms = WordPress\Taxonomy::getPostTerms( $object->name, $post ) )
			return;

		$list = [];

		foreach ( $terms as $term ) {

			$query = [];

			if ( 'post' != $post->post_type )
				$query['post_type'] = $post->post_type;

			if ( $object->query_var ) {

				$query[$object->query_var] = $term->slug;

			} else {

				$query['taxonomy'] = $object->name;
				$query['term']     = $term->slug;
			}

			$list[] = Core\HTML::tag( 'a', [
				'href'  => add_query_arg( $query, 'edit.php' ),
				'title' => urldecode( $term->slug ),
				'class' => '-term',
			], WordPress\Term::title( $term ) );
		}

		echo WordPress\Strings::getJoined( $list, $before, $after );
	}

	public static function renderTaxonomyTermsEditRow( $object, $taxonomy, $before = '', $after = '' )
	{
		if ( ! $object = WordPress\Term::get( $object ) )
			return;

		if ( ! $taxonomy = WordPress\Taxonomy::object( $taxonomy ) )
			return;

		if ( ! $terms = wp_get_object_terms( $object->term_id, $taxonomy->name, [ 'update_term_meta_cache' => FALSE ] ) )
			return;

		$list = [];
		$link = sprintf( 'edit-tags.php?taxonomy=%s', $object->taxonomy );

		foreach ( $terms as $term )
			$list[] = Core\HTML::tag( 'a', [
				// NOTE: better to pass the `term_id` instead of term `slug`
				'href'  => add_query_arg( [ $term->taxonomy => $term->term_id ], $link ),
				'title' => urldecode( $term->slug ),
				'class' => '-term -taxonomy-term',
			], WordPress\Term::title( $term ) );

		echo WordPress\Strings::getJoined( $list, $before, $after );
	}

	public static function renderUserTermsEditRow( $user_id, $taxonomy, $before = '', $after = '' )
	{
		if ( ! $taxonomy = WordPress\Taxonomy::object( $taxonomy ) )
			return;

		if ( ! $terms = wp_get_object_terms( (int) $user_id, $taxonomy->name, [ 'update_term_meta_cache' => FALSE ] ) )
			return;

		$list = [];
		$link = 'users.php?%1$s=%2$s';

		foreach ( $terms as $term )
			$list[] = Core\HTML::tag( 'a', [
				'href'  => sprintf( $link, $taxonomy->name, $term->slug ),
				'title' => urldecode( $term->slug ),
				'class' => '-term -user-term',
			], WordPress\Term::title( $term ) );

		echo WordPress\Strings::getJoined( $list, $before, $after );
	}

	public static function getAuthorsEditRow( $authors, $posttype = 'post', $before = '', $after = '' )
	{
		if ( empty( $authors ) )
			return;

		$list = [];

		foreach ( $authors as $author )
			if ( $html = WordPress\PostType::authorEditMarkup( $posttype, $author ) )
				$list[] = $html;

		echo WordPress\Strings::getJoined( $list, $before, $after );
	}

	// TODO: move to `Tablelist`
	// NOTE: the output of `the_title()` is `un-escaped`
	// @REF: https://make.wordpress.org/core/handbook/testing/reporting-security-vulnerabilities/#why-are-some-users-allowed-to-post-unfiltered-html
	public static function getPostTitleRow( $post, $link = 'edit', $status = FALSE, $title_attr = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return Plugin::na( FALSE );

		$title = WordPress\Post::title( $post );
		$after = '';

		if ( $status ) {

			$statuses = TRUE === $status ? WordPress\Status::get() : $status;

			if ( 'publish' != $post->post_status ) {

				if ( 'inherit' == $post->post_status && 'attachment' === $post->post_type )
					$status = '';
				else
					$status = $statuses[$post->post_status] ?? $post->post_status;

				if ( $status )
					$after = ' <small class="-status" title="'.Core\HTML::escape( $post->post_status ).'">('.$status.')</small>';
			}
		}

		if ( ! $link )
			return $title.$after;

		if ( 'posttype' === $title_attr )
			$title_attr = Services\CustomPostType::getLabel( $post->post_type, 'extended_label' );

		$edit = WordPress\Post::can( $post, 'edit_post' );

		if ( 'edit' == $link && ! $edit )
			$link = 'view';

		if ( 'edit' == $link )
			return Core\HTML::tag( 'a', [
				'href'   => WordPress\Post::edit( $post->ID ),
				'class'  => '-link -row-link -row-link-edit',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'Edit', 'Helper: Row Action', 'geditorial' ) : $title_attr,
				'data'   => [ 'post' => $post->ID, 'type' => $post->post_type ],
			], $title ).$after;

		if ( 'view' == $link && ! $edit && 'publish' != get_post_status( $post ) )
			return Core\HTML::tag( 'span', [
				'class' => '-row-span',
				'title' => is_null( $title_attr ) ? FALSE : $title_attr,
				// 'data'  => [ 'post' => $post->ID, 'type' => $post->post_type ],
			], $title ).$after;

		if ( 'view' == $link )
			return Core\HTML::tag( 'a', [
				'href'   => WordPress\Post::shortlink( $post->ID ),
				'class'  => '-link -row-link -row-link-view',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'View', 'Helper: Row Action', 'geditorial' ) : $title_attr,
				'data'   => [ 'post' => $post->ID, 'type' => $post->post_type ],
			], $title ).$after;

		return Core\HTML::tag( 'a', [
			'href'   => $link,
			'class'  => '-link -row-link -row-link-custom',
			'target' => '_blank',
			'title'  => is_null( $title_attr ) ? FALSE : $title_attr,
			'data'   => [ 'post' => $post->ID, 'type' => $post->post_type ],
		], $title ).$after;
	}

	// TODO: move to `Tablelist`
	// @SEE: `Tablelist::getTermTitleRow()`
	public static function getTermTitleRow( $term, $link = 'edit', $taxonomy = FALSE, $title_attr = NULL )
	{
		if ( ! $term = WordPress\Term::get( $term ) )
			return Plugin::na( FALSE );

		$title = WordPress\Term::title( $term );
		$after = '';

		if ( $taxonomy )
			$after = ' <small class="-taxonomy" title="'
				.Core\HTML::escape( $term->taxonomy ).'">('
				.Services\CustomTaxonomy::getLabel( $term->taxonomy, 'extended_label' )
				.')</small>';

		if ( ! $link )
			return Core\HTML::escape( $title ).$after;

		$edit = WordPress\Term::edit( $term );

		if ( 'edit' == $link && ! $edit )
			$link = 'view';

		if ( 'edit' == $link )
			return Core\HTML::tag( 'a', [
				'href'   => $edit,
				'class'  => '-link -row-link -row-link-edit',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'Edit', 'Helper: Row Action', 'geditorial' ) : $title_attr,
				'data'   => [ 'term' => $term->term_id, 'taxonomy' => $term->taxonomy ],
			], Core\HTML::escape( $title ) ).$after;

		if ( 'view' == $link && ! $edit && ! WordPress\Taxonomy::viewable( $term->taxonomy ) )
			return Core\HTML::tag( 'span', [
				'class' => '-row-span',
				'title' => is_null( $title_attr ) ? FALSE : $title_attr,
				// 'data'   => [ 'term' => $term->term_id, 'taxonomy' => $term->taxonomy ],
			], Core\HTML::escape( $title ) ).$after;

		if ( 'view' == $link )
			return Core\HTML::tag( 'a', [
				'href'   => WordPress\Term::shortlink( $term->term_id ),
				'class'  => '-link -row-link -row-link-view',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'View', 'Helper: Row Action', 'geditorial' ) : $title_attr,
				'data'   => [ 'term' => $term->term_id, 'taxonomy' => $term->taxonomy ],
			], Core\HTML::escape( $title ) ).$after;

		return Core\HTML::tag( 'a', [
			'href'   => $link,
			'class'  => '-link -row-link -row-link-custom',
			'target' => '_blank',
			'title'  => is_null( $title_attr ) ? FALSE : $title_attr,
			'data'   => [ 'term' => $term->term_id, 'taxonomy' => $term->taxonomy ],
		], Core\HTML::escape( $title ) ).$after;
	}

	// NOTE: DEPRECATED
	public static function getEditorialUserID( $fallback = FALSE )
	{
		self::_dep();
		return gEditorial()->user( $fallback );
	}

	// TODO: `line-count`
	// TODO: Move to `ClassicEditor` Service
	public static function renderEditorStatusInfo( $target )
	{
		echo '<div class="-wrap -editor-status-info">';

			echo '<div data-target="'.$target.'" class="-status-count hide-if-no-js">';

				printf(
					/* translators: `%s`: words count */
					_x( 'Words: %s', 'Helper: WordCount', 'geditorial' ),
					'<span class="word-count">'.Core\Number::format( '0' ).'</span>'
				);

				echo '&nbsp;|&nbsp;';

				printf(
					/* translators: `%s`: chars count */
					_x( 'Chars: %s', 'Helper: WordCount', 'geditorial' ),
					'<span class="char-count">'.Core\Number::format( '0' ).'</span>'
				);

			echo '</div>';

			do_action( static::BASE.'_editor_status_info', $target );

		echo '</div>';
	}

	// TODO: move to `WordPress\Strings`
	public static function htmlEmpty( $class = '', $title_attr = NULL )
	{
		return is_null( $title_attr )
			? '<span class="-empty '.Core\HTML::prepClass( $class ).'">&mdash;</span>'
			: sprintf( '<span title="%s" class="'.Core\HTML::prepClass( '-empty', $class ).'">&mdash;</span>', $title_attr );
	}

	// TODO: move to `WordPress\Strings`
	public static function htmlCount( $count, $title = NULL, $empty = NULL )
	{
		if ( is_array( $count ) )
			$count = count( $count );

		return $count
			? Core\Number::format( $count )
			: $empty ?? self::htmlEmpty( 'column-count-empty', $title ?? _x( 'No Count', 'Helper: Title Attribute', 'geditorial' ) );
	}

	// TODO: move to `WordPress\Strings`
	public static function htmlOrder( $order, $title = NULL )
	{
		return $order
			? Core\Number::localize( $order )
			: $empty ?? self::htmlEmpty( 'column-order-empty', $title ?? _x( 'No Order', 'Helper: Title Attribute', 'geditorial' ) );
	}

	public static function getDateEditRow( $timestamp, $class = FALSE )
	{
		if ( empty( $timestamp ) )
			return self::htmlEmpty();

		if ( ! Core\Date::isTimestamp( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		$formats = Datetime::dateFormats( FALSE );

		$html = '<span class="-date-date" title="'.Core\HTML::escape( Core\Date::get( $formats['timeonly'], $timestamp ) );
		$html.= '" data-time="'.date( 'c', $timestamp ).'">'.Core\Date::get( $formats['default'], $timestamp ).'</span>';

		$html.= '&nbsp;(<span class="-date-diff" title="';
		$html.= Core\HTML::escape( Core\Date::get( $formats['fulltime'], $timestamp ) ).'">';
		$html.= Datetime::humanTimeDiff( $timestamp ).'</span>)';

		return $class ? Core\HTML::wrap( $html, $class, FALSE ) : $html;
	}

	public static function getModifiedEditRow( $post, $class = FALSE )
	{
		$timestamp = strtotime( $post->post_modified );
		$formats   = Datetime::dateFormats( FALSE );

		$html = '<span class="-date-modified" title="'.Core\HTML::escape( Core\Date::get( $formats['default'], $timestamp ) );
		$html.='" data-time="'.date( 'c', $timestamp ).'">'.Datetime::humanTimeDiff( $timestamp ).'</span>';

		$edit_last = get_post_meta( $post->ID, '_edit_last', TRUE );

		if ( $edit_last && $post->post_author != $edit_last )
			$html.= '&nbsp;(<span class="-edit-last">'.WordPress\PostType::authorEditMarkup( $post->post_type, $edit_last ).'</span>)';

		return $class ? Core\HTML::wrap( $html, $class, FALSE ) : $html;
	}

	// @source: `translate_nooped_plural()`
	public static function nooped( $count, $nooped )
	{
		if ( ! array_key_exists( 'domain', $nooped ) )
			$nooped['domain'] = static::BASE;

		if ( empty( $nooped['domain'] ) )
			return _n( $nooped['singular'], $nooped['plural'], $count );

		else if ( empty( $nooped['context'] ) )
			return _n( $nooped['singular'], $nooped['plural'], $count, $nooped['domain'] );

		else
			return _nx( $nooped['singular'], $nooped['plural'], $count, $nooped['context'], $nooped['domain'] );
	}

	public static function noopedCount( $count, $nooped )
	{
		/* translators: singular/plural */
		return 'plural' == _x( 'plural', 'Helper: Nooped Count', 'geditorial' )
			? self::nooped( $count, $nooped )
			: self::nooped( 1, $nooped );
	}

	public static function getLayout( $name, $require = FALSE, $no_cache = FALSE )
	{
		$content = WP_CONTENT_DIR.'/'.$name.'.php';
		$plugin  = GEDITORIAL_DIR.'includes/Layouts/'.$name.'.php';
		$layout  = locate_template( 'editorial/layouts/'.$name );

		if ( ! $layout && Core\File::readable( $content ) )
			$layout = $content;

		if ( ! $layout && Core\File::readable( $plugin ) )
			$layout = $plugin;

		if ( $no_cache && $layout )
			WordPress\Site::doNotCache();

		if ( $require && $layout )
			require_once $layout;
		else
			return $layout;
	}

	/**
	 * Logs a message given agent, level, and context.
	 *
	 * @param string $message
	 * @param null|string $agent
	 * @param null|string $level
	 * @param array $context
	 * @return false
	 */
	public static function log( $message, $agent = NULL, $level = \null, $context = [] )
	{
		do_action( 'gnetwork_logger_site_'.strtolower( $level ?? 'NOTICE' ),
			strtoupper( $agent ?? static::BASE ),
			$message,
			$context
		);

		return FALSE; // to help the caller!
	}
}
