<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Helper extends WordPress\Main
{
	const BASE = 'geditorial';

	public static function factory()
	{
		return gEditorial();
	}

	public static function moduleClass( $module, $check = TRUE )
	{
		$class = '';

		foreach ( explode( '-', str_replace( '_', '-', $module ) ) as $word )
			$class.= ucfirst( $word ).'';

		$class = __NAMESPACE__.'\\Modules\\'.$class.'\\'.$class;

		if ( $check && ! class_exists( $class ) )
			return FALSE;

		return $class;
	}

	public static function moduleSlug( $module, $link = TRUE )
	{
		return $link
			? ucwords( str_replace( [ '_', ' ' ], '-', $module ), '-' )
			: ucwords( str_replace( [ '_', '-' ], ' ', $module ) );
	}

	public static function moduleLoading( $module, $stage = NULL )
	{
		if ( 'private' === $module->access && ! GEDITORIAL_LOAD_PRIVATES )
			return FALSE;

		if ( 'beta' === $module->access && ! GEDITORIAL_BETA_FEATURES )
			return FALSE;

		$stage = $stage ?? self::const( 'WP_STAGE', 'production' ); // 'development'

		if ( 'production' !== $stage )
			return TRUE;

		if ( in_array( $module->access, [ 'alpha', 'experimental', 'unknown' ], TRUE ) )
			return FALSE;

		return TRUE;
	}

	public static function moduleEnabled( $options )
	{
		$enabled = isset( $options->enabled ) ? $options->enabled : FALSE;

		if ( 'off' === $enabled )
			return FALSE;

		if ( 'on' === $enabled )
			return TRUE;

		return $enabled;
	}

	// TODO: must check for minimum version of WooCommerce
	// TODO: move to `Info`
	public static function moduleCheckWooCommerce( $message = NULL )
	{
		return WordPress\WooCommerce::isActive()
			? FALSE
			: ( is_null( $message ) ? _x( 'Needs WooCommerce', 'Helper', 'geditorial-admin' ) : $message );
	}

	// TODO: move to `Info`
	public static function moduleCheckLocale( $locale, $message = NULL )
	{
		$current = Core\L10n::locale( TRUE );

		foreach ( (array) $locale as $code )
			if ( $current === $code )
				return FALSE;

		return $message ?? _x( 'Not Available on Current Locale', 'Helper', 'geditorial-admin' );
	}

	public static function isTaxonomyGenre( $taxonomy, $fallback = 'genre' )
	{
		return $taxonomy === gEditorial()->constant( 'genres', 'main_taxonomy', $fallback );
	}

	public static function isTaxonomyAudit( $taxonomy, $fallback = 'audit_attribute' )
	{
		return $taxonomy === gEditorial()->constant( 'audit', 'main_taxonomy', $fallback );
	}

	public static function setTaxonomyAudit( $post, $term_ids, $append = TRUE, $fallback = 'audit_attribute' )
	{
		if ( ! gEditorial()->enabled( 'audit' ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! gEditorial()->module( 'audit' )->posttype_supported( $post->post_type ) )
			return FALSE;

		if ( $append && empty( $term_ids ) )
			return FALSE;

		else if ( empty( $term_ids ) )
			$terms = NULL;

		else if ( is_string( $term_ids ) )
			$terms = $term_ids;

		else
			$terms = Core\Arraay::prepNumeral( $term_ids );

		$taxonomy = gEditorial()->constant( 'audit', 'main_taxonomy', $fallback );
		$result   = wp_set_object_terms( $post->ID, $terms, $taxonomy, $append );

		if ( is_wp_error( $result ) )
			return FALSE;

		clean_object_term_cache( $post->ID, $taxonomy );

		return $result;
	}

	// override to use plugin version
	public static function linkStyleSheet( $url, $version = GEDITORIAL_VERSION, $media = FALSE, $verbose = TRUE )
	{
		return Core\HTML::linkStyleSheet( $url, $version, $media, $verbose );
	}

	public static function linkStyleSheetAdmin( $page, $verbose = TRUE, $prefix = 'admin' )
	{
		return Core\HTML::linkStyleSheet( GEDITORIAL_URL.'assets/css/'.( $prefix ? $prefix.'.' : '' ).$page.( is_rtl() ? '-rtl' : '' ).'.css', GEDITORIAL_VERSION, 'all', $verbose );
	}

	/**
	 * Prepares data for display as a contact.
	 *
	 * @param string $value
	 * @param string $title
	 * @param string $empty
	 * @param bool $icon
	 * @return string $contact
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
				$icon ? Visual::getIcon( [ 'misc-16', 'envelope-fill' ] ) : NULL
			);

		else if ( Core\URL::isValid( $value ) )
			$prepared = Core\HTML::link(
				$icon ? Visual::getIcon( [ 'misc-16', 'link-45deg' ] ) : Core\URL::prepTitle( $value ),
				$value,
				TRUE
			);

		else if ( Core\Phone::is( $value ) )
			$prepared = Core\Phone::prep(
				$value,
				[ 'title' => $title ],
				$icon ? 'icon' : 'display',
				$icon ? Visual::getIcon( [ 'misc-16', 'telephone-fill' ] ) : NULL
			);

		else
			$prepared = $icon
				? Core\HTML::tag( 'span', [ 'title' => $value ], Visual::getIcon( [ 'misc-16', 'patch-question-fill' ] ) )
				: Core\HTML::escape( $value );

		return apply_filters( static::BASE.'_prep_contact', $prepared, $value, $title );
	}

	public static function prepPeople( $value, $empty = '', $separator = NULL )
	{
		if ( self::empty( $value ) )
			return $empty;

		$list = [];

		foreach ( self::getSeparated( $value ) as $individual )
			if ( $prepared = apply_filters( static::BASE.'_prep_individual', $individual, $individual, $value ) )
				$list[] = $prepared;

		return WordPress\Strings::getJoined( $list, '', '', $empty, $separator );
	}

	public static function prepVenue( $value, $empty = '', $separator = NULL )
	{
		if ( self::empty( $value ) )
			return $empty;

		$list = [];

		foreach ( self::getSeparated( $value ) as $location )
			if ( $prepared = apply_filters( static::BASE.'_prep_location', $location, $location, $value ) )
				$list[] = $prepared;

		return WordPress\Strings::getJoined( $list, '', '', $empty, $separator );
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
				// better to pass the term_id instead of term slug
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
			if ( $html = Core\WordPress::getAuthorEditHTML( $posttype, $author ) )
				$list[] = $html;

		echo WordPress\Strings::getJoined( $list, $before, $after );
	}

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

				if ( 'inherit' == $post->post_status && 'attachment' == $post->post_type )
					$status = '';
				else if ( isset( $statuses[$post->post_status] ) )
					$status = $statuses[$post->post_status];
				else
					$status = $post->post_status;

				if ( $status )
					$after = ' <small class="-status" title="'.Core\HTML::escape( $post->post_status ).'">('.$status.')</small>';
			}
		}

		if ( ! $link )
			return $title.$after;

		if ( 'posttype' === $title_attr )
			$title_attr = Services\CustomPostType::getLabel( $post->post_type, 'extended_label' );

		// $edit = current_user_can( 'edit_post', $post->ID );
		$edit = WordPress\Post::can( $post, 'edit_post' );

		if ( 'edit' == $link && ! $edit )
			$link = 'view';

		if ( 'edit' == $link )
			return Core\HTML::tag( 'a', [
				'href'   => Core\WordPress::getPostEditLink( $post->ID ),
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
				'href'   => Core\WordPress::getPostShortLink( $post->ID ),
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
				'href'   => Core\WordPress::getTermShortLink( $term->term_id ),
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
	// TODO: move to `Info`
	public static function renderEditorStatusInfo( $target )
	{
		echo '<div class="-wrap -editor-status-info">';

			echo '<div data-target="'.$target.'" class="-status-count hide-if-no-js">';

				/* translators: `%s`: words count */
				printf( _x( 'Words: %s', 'Helper: WordCount', 'geditorial' ),
					'<span class="word-count">'.Core\Number::format( '0' ).'</span>' );

				echo '&nbsp;|&nbsp;';

				/* translators: `%s`: chars count */
				printf( _x( 'Chars: %s', 'Helper: WordCount', 'geditorial' ),
					'<span class="char-count">'.Core\Number::format( '0' ).'</span>' );

			echo '</div>';

			do_action( static::BASE.'_editor_status_info', $target );

		echo '</div>';
	}

	// TODO: move to `WordPress\Strings`
	public static function htmlEmpty( $class = '', $title_attr = NULL )
	{
		return is_null( $title_attr )
			? '<span class="-empty '.$class.'">&mdash;</span>'
			: sprintf( '<span title="%s" class="'.Core\HTML::prepClass( '-empty', $class ).'">&mdash;</span>', $title_attr );
	}

	// TODO: move to `WordPress\Strings`
	public static function htmlCount( $count, $title_attr = NULL, $empty = NULL )
	{
		if ( is_null( $title_attr ) )
			$title_attr = _x( 'No Count', 'Helper: No Count Title Attribute', 'geditorial' );

		if ( is_array( $count ) )
			$count = count( $count );

		return $count
			? Core\Number::format( $count )
			: ( is_null( $empty ) ? self::htmlEmpty( 'column-count-empty', $title_attr ) : $empty );
	}

	// TODO: move to `WordPress\Strings`
	public static function htmlOrder( $order, $title_attr = NULL )
	{
		if ( is_null( $title_attr ) )
			$title_attr = _x( 'No Order', 'Helper: No Order Title Attribute', 'geditorial' );

		if ( $order )
			$html = Core\Number::localize( $order );
		else
			$html = sprintf( '<span title="%s" class="column-order-empty -empty">&mdash;</span>', $title_attr );

		return $html;
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
			$html.= '&nbsp;(<span class="-edit-last">'.Core\WordPress::getAuthorEditHTML( $post->post_type, $edit_last ).'</span>)';

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
			Core\WordPress::doNotCache();

		if ( $require && $layout )
			require_once $layout;
		else
			return $layout;
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki
	public static function getMustache( $base = GEDITORIAL_DIR )
	{
		global $gEditorialMustache;

		if ( ! empty( $gEditorialMustache ) )
			return $gEditorialMustache;

		$gEditorialMustache = @new \Mustache_Engine( [
			'template_class_prefix' => '__'.static::BASE.'_',
			'cache_file_mode'       => FS_CHMOD_FILE,
			// 'cache'                 => $base.'assets/views/cache',
			'cache'                 => get_temp_dir(),

			'loader'          => new \Mustache_Loader_FilesystemLoader( $base.'assets/views' ),
			'partials_loader' => new \Mustache_Loader_FilesystemLoader( $base.'assets/views/partials' ),
			'escape'          => static function ( $value ) {
				return htmlspecialchars( $value, ENT_COMPAT, 'UTF-8' );
			},
		] );

		return $gEditorialMustache;
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags
	public static function renderMustache( $part, $data = [], $verbose = TRUE )
	{
		$engine = self::getMustache();
		$html   = $engine->loadTemplate( $part )->render( $data );

		if ( ! $verbose )
			return $html;

		echo $html;
	}

	public static function mdExtra( $markdown )
	{
		global $gEditorialMarkdownExtra;

		if ( empty( $markdown ) || ! class_exists( '\Michelf\MarkdownExtra' ) )
			return $markdown;

		if ( empty( $gEditorialMarkdownExtra ) )
			$gEditorialMarkdownExtra = new \Michelf\MarkdownExtra();

		return $gEditorialMarkdownExtra->defaultTransform( $markdown );
	}

	// TODO: move to services: `FileCache`
	public static function getCacheDIR( $sub, $base = NULL )
	{
		if ( ! GEDITORIAL_CACHE_DIR )
			return FALSE;

		if ( is_null( $base ) )
			$base = self::BASE;

		$path = Core\File::normalize( GEDITORIAL_CACHE_DIR.( $base ? '/'.$base.'/' : '/' ).$sub );

		if ( file_exists( $path ) )
			return Core\URL::untrail( $path );

		if ( ! wp_mkdir_p( $path ) )
			return FALSE;

		Core\File::putIndexHTML( $path, GEDITORIAL_DIR.'index.html' );

		return Core\URL::untrail( $path );
	}

	// TODO: move to services: `FileCache`
	public static function getCacheURL( $sub, $base = NULL )
	{
		if ( ! GEDITORIAL_CACHE_DIR ) // correct, we check for path constant
			return FALSE;

		if ( is_null( $base ) )
			$base = self::BASE;

		return Core\URL::untrail( GEDITORIAL_CACHE_URL.( $base ? '/'.$base.'/' : '/' ).$sub );
	}

	/**
	 * Separates given string by set of delimiters into an array.
	 * NOTE: applies the plugin filter on default delimiters
	 *
	 * @param string $string
	 * @param null|string|array $delimiters
	 * @param null|int $limit
	 * @param string $delimiter
	 * @return array $separated
	 */
	public static function getSeparated( $string, $delimiters = NULL, $limit = NULL, $delimiter = '|' )
	{
		return WordPress\Strings::getSeparated(
			$string,
			$delimiters ?? self::getDelimiters( $delimiter ),
			$limit,
			$delimiter
		);
	}

	/**
	 * Retrieves the list of string delimiters.
	 *
	 * @param string $default
	 * @return null|array $delimiters
	 */
	public static function getDelimiters( $default = '|' )
	{
		return apply_filters( static::BASE.'_string_delimiters',
			Core\Arraay::prepSplitters( GEDITORIAL_STRING_DELIMITERS, $default ) );
	}

	/**
	 * Generates a Security Token for authorization.
	 * TODO move to services
	 *
	 * @param string $context
	 * @param string $subject
	 * @param string $fullname
	 * @param int $expires
	 * @param mixed $fallback
	 * @return string $token
	 */
	public static function generateSecurityToken( $context, $subject, $fullname, $expires = NULL, $fallback = FALSE )
	{
		if ( ! $subject || ! $fullname )
			return $fallback;

		$algo = apply_filters( static::BASE.'_securitytoken_algorithm', 'RS256', $context );
		$rsa  = apply_filters( static::BASE.'_securitytoken_rsakey_path', NULL, $context );
		$age  = apply_filters( static::BASE.'_securitytoken_expires', $expires ?? Core\Date::YEAR_IN_SECONDS, $context );

		if ( ! $algo || ! $rsa )
			return $fallback;

		/**
		 * @package `adhocore/jwt`
		 * @link https://github.com/adhocore/php-jwt
		 */
		$jwt = new \Ahc\Jwt\JWT(
			$rsa,
			$algo,
			$age ?: Core\Date::YEAR_IN_SECONDS
		);

		return $jwt->encode( [
			'name' => $fullname,
			'sub'  => $subject,
		] );
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
