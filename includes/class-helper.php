<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialHelper extends gEditorialBaseCore
{

	const MODULE = FALSE;

	protected static function constant( $key, $default = FALSE )
	{
		return gEditorial()->constant( self::MODULE, $key, $default );
	}

	public static function moduleClass( $module, $check = TRUE, $prefix = 'gEditorial' )
	{
		$class = '';

		foreach ( explode( '-', str_replace( '_', '-', $module ) ) as $word )
			$class .= ucfirst( $word ).'';

		if ( $check && ! class_exists( $prefix.$class ) )
			return FALSE;

		return $prefix.$class;
	}

	public static function moduleSlug( $module, $link = TRUE )
	{
		return $link
			? ucwords( str_replace( array( '_', ' ' ), '-', $module ), '-' )
			: ucwords( str_replace( array( '_', '-' ), ' ', $module ) );
	}

	// FIXME: MUST DEPRECATE
	public static function moduleEnabled( $options )
	{
		$enabled = isset( $options->enabled ) ? $options->enabled : FALSE;

		if ( 'off' === $enabled )
			return FALSE;

		if ( 'on' === $enabled )
			return TRUE;

		return $enabled;
	}

	// override to use plugin version
	public static function linkStyleSheet( $url, $version = GEDITORIAL_VERSION, $media = FALSE )
	{
		gEditorialHTML::linkStyleSheet( $url, $version, $media );
	}

	public static function linkStyleSheetAdmin( $page )
	{
		gEditorialHTML::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.'.$page.'.css', GEDITORIAL_VERSION );
	}

	public static function kses( $text, $context = 'none', $allowed = NULL )
	{
		if ( is_null( $allowed ) ) {

			if ( 'text' == $context )
				$allowed = array(
					'a'       => array( 'class' => TRUE, 'title' => TRUE, 'href' => TRUE ),
					'abbr'    => array( 'class' => TRUE, 'title' => TRUE ),
					'acronym' => array( 'class' => TRUE, 'title' => TRUE ),
					'code'    => array( 'class' => TRUE ),
					'em'      => array( 'class' => TRUE ),
					'strong'  => array( 'class' => TRUE ),
					'i'       => array( 'class' => TRUE ),
					'b'       => array( 'class' => TRUE ),
					'span'    => array( 'class' => TRUE ),
					'br'      => array(),
				);

			else if ( 'html' == $context )
				$allowed = wp_kses_allowed_html();

			else if ( 'none' == $context )
				$allowed = array();
		}

		return apply_filters( 'geditorial_kses', wp_kses( $text, $allowed ), $allowed, $context );
	}

	public static function prepDescription( $text )
	{
		if ( ! $text )
			return $text;

		$text = do_shortcode( $text, TRUE );
		$text = apply_filters( 'gnetwork_typography', $text );

		return wpautop( $text );
	}

	// @SOURCE: P2
	public static function excerptedTitle( $content, $word_count )
	{
		$content = strip_tags( $content );
		$words   = preg_split( '/([\s_;?!\/\(\)\[\]{}<>\r\n\t"]|\.$|(?<=\D)[:,.\-]|[:,.\-](?=\D))/', $content, $word_count + 1, PREG_SPLIT_NO_EMPTY );

		if ( count( $words ) > $word_count ) {
			array_pop( $words ); // remove remainder of words
			$content = implode( ' ', $words );
			$content .= '…';
		} else {
			$content = implode( ' ', $words );
		}

		$content = trim( strip_tags( $content ) );

		return $content;
	}

	public static function trimChars( $text, $length = 45, $append = '&nbsp;&hellip;' )
	{
		$append = '<span title="'.esc_attr( $text ).'">'.$append.'</span>';

		return gEditorialCoreText::trimChars( $text, $length, $append );
	}

	public static function getJoined( $items, $before = '', $after = '' )
	{
		if ( count( $items ) )
			return $before.join( _x( ', ', 'Module Helper: Item Seperator', GEDITORIAL_TEXTDOMAIN ), $items ).$after;

		return '';
	}

	public static function getTermsEditRow( $post, $taxonomy, $before = '', $after = '' )
	{
		$object = is_object( $taxonomy ) ? $taxonomy : get_taxonomy( $taxonomy );

		if ( ! $terms = get_the_terms( $post, $object->name ) )
			return;

		$list = array();

		foreach ( $terms as $term ) {

			$query = array();

			if ( 'post' != $post->post_type )
				$query['post_type'] = $post->post_type;

			if ( $object->query_var ) {
				$query[$object->query_var] = $term->slug;

			} else {
				$query['taxonomy'] = $object->name;
				$query['term']     = $term->slug;
			}

			$list[] = gEditorialHTML::tag( 'a', array(
				'href'  => add_query_arg( $query, 'edit.php' ),
				'title' => $term->slug,
				'class' => '-term',
			), esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, $object->name, 'display' ) ) );
		}

		echo self::getJoined( $list, $before, $after );
	}

	public static function getAuthorsEditRow( $authors, $post_type = 'post', $before = '', $after = '' )
	{
		if ( ! count( $authors ) )
			return;

		$list = array();

		foreach ( $authors as $author )
			if ( $html = gEditorialWordPress::getAuthorEditHTML( $post_type, $author ) )
				$list[] = $html;

		echo self::getJoined( $list, $before, $after );
	}

	public static function getPostTitleRow( $post, $link = 'edit' )
	{
		$title = apply_filters( 'the_title', $post->post_title, $post->ID );

		if ( empty( $title ) )
			$title = _x( '(no title)', 'Module Helper: Post Title', GEDITORIAL_TEXTDOMAIN );

		if ( ! $link )
			return esc_html( $title );

		if ( 'edit' == $link && ! current_user_can( 'edit_post', $post->ID ) )
			$link = 'view';

		if ( 'edit' == $link )
			return gEditorialHTML::tag( 'a', array(
				'href'   => gEditorialWordPress::getPostEditLink( $post->ID ),
				'class'  => '-link -row-link -row-link-edit',
				'target' => '_blank',
				'title'  => _x( 'Edit', 'Module Helper: Row Action', GEDITORIAL_TEXTDOMAIN ),
			), esc_html( $title ) );

		if ( 'view' == $link )
			return gEditorialHTML::tag( 'a', array(
				'href'   => gEditorialWordPress::getPostShortLink( $post->ID ),
				'class'  => '-link -row-link -row-link-view',
				'target' => '_blank',
				'title'  => _x( 'View', 'Module Helper: Row Action', GEDITORIAL_TEXTDOMAIN ),
			), esc_html( $title ) );

		return gEditorialHTML::tag( 'a', array(
			'href'   => $link,
			'class'  => '-link -row-link -row-link-custom',
			'target' => '_blank',
		), esc_html( $title ) );
	}

	public static function getPostRowActions( $post_id, $actions = array( 'edit', 'view' ) )
	{
		$list = array();

		foreach ( $actions as $action ) {

			switch ( $action ) {

				case 'edit':

					$list['edit'] = gEditorialHTML::tag( 'a', array(
						'href'   => gEditorialWordPress::getPostEditLink( $post_id ),
						'class'  => '-link -row-link -row-link-edit',
						'target' => '_blank',
					), _x( 'Edit', 'Module Helper: Row Action', GEDITORIAL_TEXTDOMAIN ) );

				break;
				case 'view':

					$list['view'] = gEditorialHTML::tag( 'a', array(
						'href'   => gEditorialWordPress::getPostShortLink( $post_id ),
						'class'  => '-link -row-link -row-link-view',
						'target' => '_blank',
					), _x( 'View', 'Module Helper: Row Action', GEDITORIAL_TEXTDOMAIN ) );

				break;
			}
		}

		return $list;
	}

	public static function getMimeTypeEditRow( $mime_types, $post_parent, $before = '', $after = '' )
	{
		if ( ! count( $mime_types ) )
			return;

		$list = array();
		$extensions = wp_get_mime_types();

		foreach ( $mime_types as $mime_type ) {

			if ( FALSE === ( $key = array_search( $mime_type, $extensions ) ) )
				continue;

			$extension = explode( '|', $key );
			$list[] = strtoupper( $extension[0] );
		}

		echo self::getJoined( $list, $before, $after );
	}

	public static function registerColorBox()
	{
		wp_register_style( 'jquery-colorbox', GEDITORIAL_URL.'assets/css/admin.colorbox.css', array(), '1.6.4', 'screen' );
		wp_register_script( 'jquery-colorbox', GEDITORIAL_URL.'assets/packages/jquery-colorbox/jquery.colorbox-min.js', array( 'jquery' ), '1.6.4', TRUE );
	}

	public static function enqueueColorBox()
	{
		wp_enqueue_style( 'jquery-colorbox' );
		wp_enqueue_script( 'jquery-colorbox' );
	}

	public static function enqueueTimeAgo()
	{
		$callback = array( 'gPersianDateTimeAgo', 'enqueue' );

		if ( ! is_callable( $callback ) )
			return FALSE;

		return call_user_func( $callback );
	}

	public static function getTermPosts( $taxonomy, $term_or_id, $exclude = array() )
	{
		if ( ! $term = gEditorialWPTaxonomy::getTerm( $term_or_id, $taxonomy ) )
			return '';

		$query_args = array(
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order', 'date' ),
			'order'          => 'ASC',
			'post_status'    => array( 'publish', 'future', 'pending', 'draft' ),
			'post__not_in'   => $exclude,
			'tax_query'      => array( array(
				'taxonomy' => $taxonomy,
				'field'    => 'id',
				'terms'    => $term->term_id,
			) ),
		);

		$the_posts = get_posts( $query_args );
		if ( ! count( $the_posts ) )
			return FALSE;

		// FIXME: following must move to MetaBox class

		$output = '<div class="field-wrap field-wrap-list"><h4>';
		$output .= sprintf( _x( 'Other Posts on <a href="%1$s" target="_blank">%2$s</a>', 'Module Helper', GEDITORIAL_TEXTDOMAIN ),
			get_term_link( $term, $term->taxonomy ),
			sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' )
		).'</h4><ol>';

		foreach ( $the_posts as $post ) {
			setup_postdata( $post );

			$output .= '<li><a href="'.get_permalink( $post->ID ).'">'
				.get_the_title( $post->ID ).'</a>'
				.'&nbsp;<span class="edit">'
				.sprintf( _x( '&ndash; <a href="%1$s" target="_blank" title="Edit this Post">%2$s</a>', 'Module Helper', GEDITORIAL_TEXTDOMAIN ),
					esc_url( gEditorialWordPress::getPostEditLink( $post->ID ) ),
					gEditorialHTML::getDashicon( 'welcome-write-blog' )
				).'</span></li>';
		}
		wp_reset_query();
		$output .= '</ol></div>';

		return $output;
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( 'geditorial_tinymce_strings', array() );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.geditorial", '.wp_json_encode( $strings ).');'."\n" : '';
	}

	// TODO: add as general option
	public static function getEditorialUserID( $fallback = TRUE )
	{
		if ( defined( 'GNETWORK_SITE_USER_ID' ) && constant( 'GNETWORK_SITE_USER_ID' ) )
			return GNETWORK_SITE_USER_ID;

		if ( function_exists( 'gtheme_get_option' ) ) {
			$gtheme_user = gtheme_get_option( 'default_user', 0 );
			if ( $gtheme_user )
				return $gtheme_user;
		}

		if ( $fallback )
			return get_current_user_id();

		return 0;
	}

	// WP default sizes from options
	public static function getWPImageSizes()
	{
		global $gEditorial_WPImageSizes;

		if ( ! empty( $gEditorial_WPImageSizes ) )
			return $gEditorial_WPImageSizes;

		$gEditorial_WPImageSizes = array(
			'thumbnail' => array(
				'n' => _x( 'Thumbnail', 'Module Helper', GEDITORIAL_TEXTDOMAIN ),
				'w' => get_option( 'thumbnail_size_w' ),
				'h' => get_option( 'thumbnail_size_h' ),
				'c' => get_option( 'thumbnail_crop' ),
			),
			'medium' => array(
				'n' => _x( 'Medium', 'Module Helper', GEDITORIAL_TEXTDOMAIN ),
				'w' => get_option( 'medium_size_w' ),
				'h' => get_option( 'medium_size_h' ),
				'c' => 0,
			),
			'large' => array(
				'n' => _x( 'Large', 'Module Helper', GEDITORIAL_TEXTDOMAIN ),
				'w' => get_option( 'large_size_w' ),
				'h' => get_option( 'large_size_h' ),
				'c' => 0,
			),
		);

		return $gEditorial_WPImageSizes;
	}

	// CAUTION: must wrap in `.geditorial-wordcount-wrap` along with the textarea
	public static function htmlWordCount( $for = 'excerpt', $posttype = 'post', $data = array() )
	{
		$defaults = array(
			'min' => '0',
			'max' => '0',
		);

		return gEditorialHTML::tag( 'div', array(
			'class' => array( 'geditorial-wordcount', 'hide-if-no-js' ),
			'data'  => apply_filters( 'geditorial_helper_wordcount_data', array_merge( $data, $defaults ), $for, $posttype ),
		), sprintf( _x( 'Word count: %s', 'Module Helper', GEDITORIAL_TEXTDOMAIN ), '<span class="-words">0</span>' ) );
	}

	public static function htmlCount( $count, $title_attr = NULL )
	{
		if ( is_null( $title_attr ) )
			$title_attr = _x( 'No Count', 'Module Helper: No Count Title Attribute', GEDITORIAL_TEXTDOMAIN );

		if ( $count )
			$html = gEditorialNumber::format( $count );
		else
			$html = sprintf( '<span title="%s" class="column-count-empty">&mdash;</span>', $title_attr );

		return $html;
	}

	public static function getDateEditRow( $mysql_time, $wrap_class = FALSE )
	{
		$html = '';

		$date = _x( 'm/d/Y', 'Module Helper: Date Edit Row', GEDITORIAL_TEXTDOMAIN );
		$time = _x( 'H:i', 'Module Helper: Date Edit Row', GEDITORIAL_TEXTDOMAIN );
		$full = _x( 'l, M j, Y @ H:i', 'Module Helper: Date Edit Row', GEDITORIAL_TEXTDOMAIN );

		$html .= '<span class="-date-date" title="'.esc_attr( mysql2date( $time, $mysql_time ) ).'">'.mysql2date( $date, $mysql_time ).'</span>';
		$html .= '&nbsp;(<span class="-date-diff" title="'.esc_attr( mysql2date( $full, $mysql_time ) ).'">'.self::humanTimeDiff( $mysql_time ).'</span>)';

		return $wrap_class ? '<span class="'.$wrap_class.'">'.$html.'</span>' : $html;
	}

	public static function postModified( $post = NULL, $attr = FALSE )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		$gmt   = strtotime( $post->post_modified_gmt );
		$local = strtotime( $post->post_modified );

		$format = _x( 'l, F j, Y', 'Module Helper: Post Modified', GEDITORIAL_TEXTDOMAIN );
		$title  = _x( 'Last Modified on %s', 'Module Helper: Post Modified', GEDITORIAL_TEXTDOMAIN );

		return $attr
			? sprintf( $title, date_i18n( $format, $local ) )
			: gEditorialDate::htmlDateTime( $local, $gmt, $format, self::humanTimeDiffRound( $local, FALSE ) );
	}

	public static function htmlHumanTime( $timestamp )
	{
		$time = strtotime( $timestamp );
		return '<span class="-time" title="'
			.self::humanTimeAgo( $time, current_time( 'timestamp', FALSE ) ).'">'
			.self::humanTimeDiffRound( $time )
		.'</span>';
	}

	public static function humanTimeAgo( $from, $to = '' )
	{
		return sprintf( _x( '%s ago', 'Module Helper: Human Time Ago', GEDITORIAL_TEXTDOMAIN ), human_time_diff( $from, $to ) );
	}

	public static function humanTimeDiffRound( $local, $round = DAY_IN_SECONDS, $format = NULL, $now = NULL )
	{
		$now = is_null( $now ) ? current_time( 'timestamp', FALSE ) : '';

		if ( FALSE === $round )
			return self::humanTimeAgo( $local, $now );

		$diff = $now - $local;

		if ( $diff > 0 && $diff < $round )
			return self::humanTimeAgo( $local, $now );

		if ( is_null( $format ) )
			$format = _x( 'Y/m/d', 'Module Helper: Human Time Diff Round', GEDITORIAL_TEXTDOMAIN );

		return date_i18n( $format, $local, FALSE );
	}

	public static function humanTimeDiff( $timestamp, $now = '' )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = array(
				'now'    => _x( 'Now', 'Module Helper: Human Time Diff', GEDITORIAL_TEXTDOMAIN ),
				'_s_ago' => _x( '%s ago', 'Module Helper: Human Time Diff', GEDITORIAL_TEXTDOMAIN ),
				'in__s'  => _x( 'in %s', 'Module Helper: Human Time Diff', GEDITORIAL_TEXTDOMAIN ),

				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Module Helper: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Module Helper: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_days'    => _nx_noop( '%s day', '%s days', 'Module Helper: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_weeks'   => _nx_noop( '%s week', '%s weeks', 'Module Helper: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_months'  => _nx_noop( '%s month', '%s months', 'Module Helper: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_years'   => _nx_noop( '%s year', '%s years', 'Module Helper: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
			);

		if ( empty( $now ) )
			$now = current_time( 'timestamp', FALSE );

		return gEditorialDate::humanTimeDiff( $timestamp, $now, $strings );
	}

	// not used yet!
	public static function moment( $timestamp, $now = '' )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = array(
				'now'            => _x( 'Now', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'just_now'       => _x( 'Just now', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'one_minute_ago' => _x( 'One minute ago', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'_s_minutes_ago' => _x( '%s minutes ago', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'one_hour_ago'   => _x( 'One hour ago', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'_s_hours_ago'   => _x( '%s hours ago', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'yesterday'      => _x( 'Yesterday', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'_s_days_ago'    => _x( '%s days ago', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'_s_weeks_ago'   => _x( '%s weeks ago', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'last_month'     => _x( 'Last month', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'last_year'      => _x( 'Last year', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'in_a_minute'    => _x( 'in a minute', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'in__s_minutes'  => _x( 'in %s minutes', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'in_an_hour'     => _x( 'in an hour', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'in__s_hours'    => _x( 'in %s hours', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'tomorrow'       => _x( 'Tomorrow', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'next_week'      => _x( 'next week', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'in__s_weeks'    => _x( 'in %s weeks', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'next_month'     => _x( 'next month', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'format_l'       => _x( 'l', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'format_f_y'     => _x( 'F Y', 'Module Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
			);

		if ( empty( $now ) )
			$now = current_time( 'timestamp', FALSE );

		return gEditorialDate::moment( $timestamp, $now, $strings );
	}

	// @REF: [Calendar Classes - ICU User Guide](http://userguide.icu-project.org/datetime/calendar)
	public static function getDefualtCalendars( $filtered = FALSE )
	{
		$calendars = array(
			'gregorian'     => _x( 'Gregorian', 'Module Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			'japanese'      => _x( 'Japanese', 'Module Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			'buddhist'      => _x( 'Buddhist', 'Module Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			'chinese'       => _x( 'Chinese', 'Module Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			'persian'       => _x( 'Persian', 'Module Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			'indian'        => _x( 'Indian', 'Module Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			'islamic'       => _x( 'Islamic', 'Module Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			'islamic-civil' => _x( 'Islamic-Civil', 'Module Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			'coptic'        => _x( 'Coptic', 'Module Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			'ethiopic'      => _x( 'Ethiopic', 'Module Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
		);

		return $filtered ? apply_filters( 'geditorial_default_calendars', $calendars ) : $calendars;
	}

	// @SOURCE: `translate_nooped_plural()`
	public static function nooped( $count, $nooped )
	{
		if ( ! empty( $nooped['domain'] ) )
			$nooped['domain'] = GEDITORIAL_TEXTDOMAIN;

		if ( empty( $nooped['context'] ) )
			return _n( $nooped['singular'], $nooped['plural'], $count, $nooped['domain'] );
		else
			return _nx( $nooped['singular'], $nooped['plural'], $count, $nooped['context'], $nooped['domain'] );
	}

	public static function noopedCount( $count, $nooped )
	{
		$rule = _x( '%2$s', 'Module Helper: Nooped Count', GEDITORIAL_TEXTDOMAIN );

		$singular = self::nooped( 1, $nooped );
		$plural   = self::nooped( $count, $nooped );

		return sprintf( $rule, $singular, $plural );
	}

	private static function getStringsFromName( $name )
	{
		if ( ! is_array( $name ) )
			return array(
				$name.'s',
				$name,
				gEditorialCoreText::strToLower( $name.'s' ),
				gEditorialCoreText::strToLower( $name ),
			);

		$strings = array(
			_nx( $name['singular'], $name['plural'], 2, $name['context'], $name['domain'] ),
			_nx( $name['singular'], $name['plural'], 1, $name['context'], $name['domain'] ),
		);

		$strings[2] = gEditorialCoreText::strToLower( $strings[0] );
		$strings[3] = gEditorialCoreText::strToLower( $strings[1] );

		$strings[5] = '%s';

		return $strings;
	}

	/**
	 *	%1$s => Camel Case / Plural
	 *	%2$s => Camel Case / Singular
	 *	%3$s => Lower Case / Plural
	 *	%4$s => Lower Case / Singular
	 *	%5$s => %s
	 *
	 *	@REF:
	 *		`get_post_type_labels()`
	 *		`_get_custom_object_labels()`
	 *		`_nx_noop()`
	 *		`translate_nooped_plural()`
	 */
	public static function generatePostTypeLabels( $name, $featured = FALSE, $pre = array() )
	{
		$name_templates = array(
			'name'                  => _x( '%1$s', 'Module Helper: CPT Generator: Name', GEDITORIAL_TEXTDOMAIN ),
			// 'menu_name'             => _x( '%1$s', 'Module Helper: CPT Generator: Menu Name', GEDITORIAL_TEXTDOMAIN ),
			// 'description'           => _x( '%1$s', 'Module Helper: CPT Generator: Description', GEDITORIAL_TEXTDOMAIN ),
			'singular_name'         => _x( '%2$s', 'Module Helper: CPT Generator: Singular Name', GEDITORIAL_TEXTDOMAIN ),
			'add_new'               => _x( 'Add New', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'add_new_item'          => _x( 'Add New %2$s', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'edit_item'             => _x( 'Edit %2$s', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'new_item'              => _x( 'New %2$s', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'view_item'             => _x( 'View %2$s', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'view_items'            => _x( 'View %1$s', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'search_items'          => _x( 'Search %1$s', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'not_found'             => _x( 'No %3$s found.', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'not_found_in_trash'    => _x( 'No %3$s found in Trash.', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'parent_item_colon'     => _x( 'Parent %2$s:', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'all_items'             => _x( 'All %1$s', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'archives'              => _x( '%2$s Archives', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'attributes'            => _x( '%2$s Attributes', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'insert_into_item'      => _x( 'Insert into %4$s', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'uploaded_to_this_item' => _x( 'Uploaded to this %4$s', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'filter_items_list'     => _x( 'Filter %3$s list', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'items_list_navigation' => _x( '%1$s list navigation', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'items_list'            => _x( '%1$s list', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
		);

		$featured_templates = array(
			'featured_image'        => _x( '%1$s', 'Module Helper: CPT Generator: Featured', GEDITORIAL_TEXTDOMAIN ),
			'set_featured_image'    => _x( 'Set %2$s', 'Module Helper: CPT Generator: Featured', GEDITORIAL_TEXTDOMAIN ),
			'remove_featured_image' => _x( 'Remove %2$s', 'Module Helper: CPT Generator: Featured', GEDITORIAL_TEXTDOMAIN ),
			'use_featured_image'    => _x( 'Use as %2$s', 'Module Helper: CPT Generator: Featured', GEDITORIAL_TEXTDOMAIN ),
		);

		$strings = self::getStringsFromName( $name );

		foreach ( $name_templates as $key => $template )
			$pre[$key] = vsprintf( $template, $strings );

		if ( ! isset( $pre['menu_name'] ) )
			$pre['menu_name'] = $strings[0];

		if ( ! isset( $pre['name_admin_bar'] ) )
			$pre['name_admin_bar'] = $strings[1];

		if ( $featured )
			foreach ( $featured_templates as $key => $template )
				$pre[$key] = vsprintf( $template, array( $featured, gEditorialCoreText::strToLower( $featured ) ) );

		return $pre;
	}

	/**
	 *	%1$s => Camel Case / Plural   : Posts
	 *	%2$s => Camel Case / Singular : Post
	 *	%3$s => Lower Case / Plural   : posts
	 *	%4$s => Lower Case / Singular : post
	 *	%5$s => %s
	 *
	 *	@REF: `_nx_noop()`, `translate_nooped_plural()`
	 */
	public static function generateTaxonomyLabels( $name, $pre = array() )
	{
		$name_templates = array(
			'name'                       => _x( '%1$s', 'Module Helper: Tax Generator: Name', GEDITORIAL_TEXTDOMAIN ),
			// 'menu_name'                  => _x( '%1$s', 'Module Helper: Tax Generator: Menu Name', GEDITORIAL_TEXTDOMAIN ),
			'singular_name'              => _x( '%2$s', 'Module Helper: Tax Generator: Singular Name', GEDITORIAL_TEXTDOMAIN ),
			'search_items'               => _x( 'Search %1$s', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'popular_items'              => NULL, // _x( 'Popular %1$s', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'all_items'                  => _x( 'All %1$s', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'parent_item'                => _x( 'Parent %2$s', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'parent_item_colon'          => _x( 'Parent %2$s:', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'edit_item'                  => _x( 'Edit %2$s', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'view_item'                  => _x( 'View %2$s', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'update_item'                => _x( 'Update %2$s', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'add_new_item'               => _x( 'Add New %2$s', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'new_item_name'              => _x( 'New %2$s Name', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'separate_items_with_commas' => _x( 'Separate %3$s with commas', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'add_or_remove_items'        => _x( 'Add or remove %3$s', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'choose_from_most_used'      => _x( 'Choose from the most used %3$s', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'not_found'                  => _x( 'No %3$s found.', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'no_terms'                   => _x( 'No %3$s', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'items_list_navigation'      => _x( '%1$s list navigation', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'items_list'                 => _x( '%1$s list', 'Module Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
		);

		$strings = self::getStringsFromName( $name );

		foreach ( $name_templates as $key => $template )
			$pre[$key] = vsprintf( $template, $strings );

		if ( ! isset( $pre['menu_name'] ) )
			$pre['menu_name'] = $strings[0];

		return $pre;
	}

	/**
	 *	%1$s => Camel Case / Plural
	 *	%2$s => Camel Case / Singular
	 *	%3$s => Lower Case / Plural
	 *	%4$s => Lower Case / Singular
	 *	%5$s => %s
	 */
	public static function generatePostTypeMessages( $name )
	{
		global $post_type_object, $post, $post_ID;

		$name_templates = array(
			'view_post'                      => _x( 'View %4$s', 'Module Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'preview_post'                   => _x( 'Preview %4$s', 'Module Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'post_updated'                   => _x( '%2$s updated.', 'Module Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'custom_field_updated'           => _x( 'Custom field updated.', 'Module Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'custom_field_deleted'           => _x( 'Custom field deleted.', 'Module Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'post_restored_to_revision_from' => _x( '%2$s restored to revision from %5$s.', 'Module Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'post_published'                 => _x( '%2$s published.', 'Module Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'post_saved'                     => _x( '%2$s saved.' , 'Module Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'post_submitted'                 => _x( '%2$s submitted.', 'Module Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'post_scheduled_for'             => _x( '%2$s scheduled for: %5$s.', 'Module Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'post_draft_updated'             => _x( '%2$s draft updated.', 'Module Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
		);

		$messages = array();
		$strings  = self::getStringsFromName( $name );

		foreach ( $name_templates as $key => $template )
			$messages[$key] = vsprintf( $template, $strings );

		if ( ! $permalink = get_permalink( $post_ID ) )
			$permalink = '';

		$preview = $scheduled = $view = '';
		$scheduled_date = date_i18n( __( 'M j, Y @ H:i' ), strtotime( $post->post_date ) );

		if ( is_post_type_viewable( $post_type_object ) ) {
			$view      = ' '.gEditorialHTML::link( $messages['view_post'], $permalink );
			$preview   = ' '.gEditorialHTML::link( $messages['preview_post'], get_preview_post_link( $post ), TRUE );
			$scheduled = ' '.gEditorialHTML::link( $messages['preview_post'], $permalink, TRUE );
		}

		return array(
			0  => '', // Unused. Messages start at index 1.
			1  => $messages['post_updated'].$view,
			2  => $messages['custom_field_updated'],
			3  => $messages['custom_field_deleted'],
			4  => $messages['post_updated'],
			5  => isset( $_GET['revision'] ) ? sprintf( $messages['post_restored_to_revision_from'], wp_post_revision_title( (int) $_GET['revision'], FALSE ) ) : FALSE,
			6  => $messages['post_published'].$view,
			7  => $messages['post_saved'],
			8  => $messages['post_submitted'].$preview,
			9  => sprintf( $messages['post_scheduled_for'], '<strong>'.$scheduled_date.'</strong>' ).$scheduled,
			10 => $messages['post_draft_updated'].$preview,
		);
	}

	public static function getPostTypeMonths( $calendar_type, $post_type = 'post', $args = array(), $user_id = 0 )
	{
		$callback = array( 'gEditorialWPDatabase', 'getPostTypeMonths' );

		if ( 'persian' == $calendar_type
			&& is_callable( array( 'gPersianDateWordPress', 'getPostTypeMonths' ) ) )
				$callback = array( 'gPersianDateWordPress', 'getPostTypeMonths' );

		return call_user_func_array( $callback, array( $post_type, $args, $user_id ) );
	}

	public static function monthFirstAndLast( $calendar_type, $year, $month, $format = 'Y-m-d H:i:s' )
	{
		$callback = array( 'gEditorialDate', 'monthFirstAndLast' );

		if ( 'persian' == $calendar_type
			&& is_callable( array( 'gPersianDateDate', 'monthFirstAndLast' ) ) )
				$callback = array( 'gPersianDateDate', 'monthFirstAndLast' );

		return call_user_func_array( $callback, array( $year, $month, $format ) );
	}

	// FIXME: find a better way!
	public static function getMonths( $calendar_type = 'gregorian' )
	{
		if ( is_callable( array( 'gPersianDateStrings', 'month' ) ) ) {

			$map = array(
				'gregorian' => 'Gregorian',
				'persian'   => 'Jalali',
				'islamic'   => 'Hijri',
			);

			if ( ! isset( $map[$calendar_type] ) )
				return array();

			return gPersianDateStrings::month( NULL, TRUE, $map[$calendar_type] );
		}

		global $wp_locale;

		if ( 'gregorian' )
			return $wp_locale->month;

		return array();
	}

	// NOT USED
	// returns array of post date in given cal
	public static function getTheDayByPost( $post, $default_type = 'gregorian' )
	{
		$the_day = array( 'cal' => 'gregorian' );

		// 'post_status' => 'auto-draft',

		switch ( strtolower( $default_type ) ) {

			case 'hijri' :
			case 'islamic' :
				$convertor = array( 'gPersianDateDateTime', 'toHijri' );
				$the_day['cal'] = 'hijri';

			case 'jalali' :
			case 'persian' :
				$convertor = array( 'gPersianDateDateTime', 'toJalali' );
				$the_day['cal'] = 'jalali';

			default:

				if ( class_exists( 'gPersianDateDateTime' )
					&& 'gregorian' != $the_day['cal'] ) {

					list(
						$the_day['year'],
						$the_day['month'],
						$the_day['day']
					) = call_user_func_array( $convertor,
						explode( '-', mysql2date( 'Y-n-j', $post->post_date, FALSE ) ) );

				} else {

					$the_day['cal'] = 'gregorian';
					$the_day['day']   = mysql2date( 'j', $post->post_date, FALSE );
					$the_day['month'] = mysql2date( 'n', $post->post_date, FALSE );
					$the_day['year']  = mysql2date( 'Y', $post->post_date, FALSE );
				}

				// FIXME: add time

		}

		return $the_day;
	}
}

class gEditorial_Walker_PageDropdown extends Walker_PageDropdown
{

	public function start_el( &$output, $page, $depth = 0, $args = array(), $id = 0 ) {
		$pad = str_repeat('&nbsp;', $depth * 3);

		if ( ! isset( $args['value_field'] ) || ! isset( $page->{$args['value_field']} ) ) {
			$args['value_field'] = 'ID';
		}

		$output .= "\t<option class=\"level-$depth\" value=\"" . esc_attr( $page->{$args['value_field']} ) . "\"";
		if ( $page->{$args['value_field']} == $args['selected'] ) // <---- CHANGED
			$output .= ' selected="selected"';
		$output .= '>';

		$title = $page->post_title;
		if ( '' === $title ) {
			$title = sprintf( __( '#%d (no title)' ), $page->ID );
		}

		$title = apply_filters( 'list_pages', $title, $page );
		$output .= $pad . esc_html( $title );
		$output .= "</option>\n";
	}
}
