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

	public static function intval( $text, $intval = TRUE )
	{
		// converts back number chars into english
		$number = apply_filters( 'number_format_i18n_back', $text );

		return $intval ? intval( $number ) : $number;
	}

	public static function kses( $text, $allowed = array(), $context = 'store' )
	{
		return apply_filters( 'geditorial_kses', wp_kses( $text, $allowed ), $allowed, $context );
	}

	public static function getTermsEditRow( $post, $post_type, $taxonomy, $before = '', $after = '' )
	{
		$object = is_object( $taxonomy ) ? $taxonomy : get_taxonomy( $taxonomy );

		if ( ! $terms = get_the_terms( $post, $object->name ) )
			return;

		$list = array();

		foreach ( $terms as $term ) {

			$query = array();

			if ( 'post' != $post_type )
				$query['post_type'] = $post_type;

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

		if ( count( $list ) )
			echo $before.join( _x( ', ', 'Module Helper: Term Seperator', GEDITORIAL_TEXTDOMAIN ), $list ).$after;
	}

	public static function getAuthorsEditRow( $authors, $post_type = 'post', $before = '', $after = '' )
	{
		if ( ! count( $authors ) )
			return;

		$list = array();

		foreach ( $authors as $author )
			if ( $html = gEditorialWordPress::getAuthorEditHTML( $post_type, $author ) )
				$list[] = $html;

		if ( count( $list ) )
			echo $before.join( _x( ', ', 'Module Helper: Author Seperator', GEDITORIAL_TEXTDOMAIN ), $list ).$after;
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

		if ( count( $list ) )
			echo $before.join( _x( ', ', 'Module Helper: Mime Type Seperator', GEDITORIAL_TEXTDOMAIN ), $list ).$after;
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

	public static function getTermPosts( $taxonomy, $term_or_id, $exclude = array() )
	{
		if ( is_object( $term_or_id ) )
			$term = $term_or_id;
		else if ( is_numeric( $term_or_id ) )
			$term = get_term_by( 'id', $term_or_id, $taxonomy );
		else
			$term = get_term_by( 'slug', $term_or_id, $taxonomy );

		if ( ! $term )
			return '';

		$query_args = array(
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order', 'date' ),
			'order'          => 'ASC',
			'post_status'    => array( 'publish', 'pending', 'draft' ),
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

			$url = add_query_arg( array(
				'action' => 'edit',
				'post'   => $post->ID,
			), get_admin_url( NULL, 'post.php' ) );

			$output .= '<li><a href="'.get_permalink( $post->ID ).'">'
					.get_the_title( $post->ID ).'</a>'
					.'&nbsp;<span class="edit">'
					.sprintf( _x( '&ndash; <a href="%1$s" target="_blank" title="Edit this Post">%2$s</a>', 'Module Helper', GEDITORIAL_TEXTDOMAIN ),
						esc_url( $url ),
						'<span class="dashicons dashicons-welcome-write-blog"></span>'
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

	public static function printJSConfig( $args, $object = 'gEditorial' )
	{
		$args['api']   = defined( 'GNETWORK_AJAX_ENDPOINT' ) && GNETWORK_AJAX_ENDPOINT ? GNETWORK_AJAX_ENDPOINT : admin_url( 'admin-ajax.php' );
		$args['dev']   = gEditorialWordPress::isDev();
		$args['nonce'] = wp_create_nonce( 'geditorial' );

	?><script type="text/javascript">
/* <![CDATA[ */
	var <?php echo $object; ?> = <?php echo wp_json_encode( $args ); ?>;
	<?php if ( $args['dev'] ) echo 'console.log('.$object.');'; ?>
/* ]]> */
</script><?php
	}

	public static function checkAjaxReferer( $action = 'geditorial', $key = 'nonce' )
	{
		check_ajax_referer( $action, $key );
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

	public static function postModified( $post = NULL, $attr = FALSE )
	{
		$gmt   = get_post_modified_time( 'U', TRUE,  $args['id'], FALSE );
		$local = get_post_modified_time( 'U', FALSE, $args['id'], FALSE );

		$format = _x( 'l, F j, Y', 'Module Helper: Post Modified', GEDITORIAL_TEXTDOMAIN );
		$title  = _x( 'Last Modified on %s', 'Module Helper: Post Modified', GEDITORIAL_TEXTDOMAIN );

		return $attr
			? sprintf( $title, date_i18n( $format, $local ) )
			: gEditorialDate::htmlDateTime( $local, $gmt, $format, self::humanTimeDiff( $local, FALSE ) );
	}

	public static function humanTimeDiff( $time, $round = TRUE, $format = NULL, $now = NULL )
	{
		$ago = _x( '%s ago', 'Module Helper: Human Time Diff', GEDITORIAL_TEXTDOMAIN );
		$now = is_null( $now ) ? current_time( 'timestamp' ) : '';

		if ( ! $round )
			return sprintf( $ago, human_time_diff( $time, $now ) );

		$time_diff = time() - $time;

		if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS )
			return sprintf( $ago, human_time_diff( $time, $now ) );

		if ( is_null( $format ) )
			$format = _x( 'Y/m/d', 'Module Helper: Human Time Diff', GEDITORIAL_TEXTDOMAIN );

		return date_i18n( $format, $time );
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

		return $strings;
	}

	/**
	 *	%1$s => Camel Case / Plural
	 *	%2$s => Camel Case / Singular
	 *	%3$s => Lower Case / Plural
	 *	%4$s => Lower Case / Singular
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
	 *	%1$s => Camel Case / Plural
	 *	%2$s => Camel Case / Singular
	 *	%3$s => Lower Case / Plural
	 *	%4$s => Lower Case / Singular
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

	public static function getPosttypeMonths( $calendar_type, $post_type = 'post', $args = array(), $user_id = 0 )
	{
		$callback = array( 'gEditorialWordPress', 'getPosttypeMonths' );

		if ( 'persian' == $calendar_type
			&& is_callable( array( 'gPersianDateDate', 'getPosttypeMonths' ) ) )
				$callback = array( 'gPersianDateDate', 'getPosttypeMonths' );

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
