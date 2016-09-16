<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialHelper extends gEditorialBaseCore
{

	const MODULE   = FALSE;
	const SETTINGS = 'geditorial-settings';
	const TOOLS    = 'geditorial-tools';

	public static function moduleClass( $module, $check = TRUE, $prefix = 'gEditorial' )
	{
		$class = '';

		foreach ( explode( '-', str_replace( '_', '-', $module ) ) as $word )
			$class .= ucfirst( $word ).'';

		if ( $check && ! class_exists( $prefix.$class ) )
			return FALSE;

		return $prefix.$class;
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
		parent::linkStyleSheet( $url, $version, $media );
	}

	public static function linkStyleSheetAdmin( $page )
	{
		parent::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.'.$page.'.css', GEDITORIAL_VERSION );
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

	public static function getTermsEditRow( $post_id, $post_type, $taxonomy, $before = '', $after = '' )
	{
		$taxonomy_object = get_taxonomy( $taxonomy );

		if ( $terms = get_the_terms( $post_id, $taxonomy ) ) {

			$out = array();

			foreach ( $terms as $t ) {

				$query = array();

				if ( 'post' != $post_type )
					$query['post_type'] = $post_type;

				if ( $taxonomy_object->query_var ) {
					$query[$taxonomy_object->query_var] = $t->slug;

				} else {
					$query['taxonomy'] = $taxonomy;
					$query['term']     = $t->slug;
				}

				$out[] = sprintf( '<a href="%s">%s</a>',
					esc_url( add_query_arg( $query, 'edit.php' ) ),
					esc_html( sanitize_term_field( 'name', $t->name, $t->term_id, $taxonomy, 'display' ) )
				);
			}

			echo $before.join( _x( ', ', 'Module Helper: Term Seperator', GEDITORIAL_TEXTDOMAIN ), $out ).$after;
		}
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

	public static function getTerms( $taxonomy = 'category', $post_id = FALSE, $object = FALSE, $key = 'term_id', $extra = array() )
	{
		$the_terms = array();

		if ( FALSE === $post_id ) {
			$terms = get_terms( $taxonomy, array_merge( array(
				'hide_empty' => FALSE,
				'orderby'    => 'name',
				'order'      => 'ASC'
			), $extra ) );
		} else {
			$terms = get_the_terms( $post_id, $taxonomy );
		}

		if ( is_wp_error( $terms ) || FALSE === $terms )
			return $the_terms;

		$the_list = wp_list_pluck( $terms, $key );
		$terms = array_combine( $the_list, $terms );

		if ( $object )
			return $terms;

		foreach ( $terms as $term )
			$the_terms[] = $term->term_id;

		return $the_terms;
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

	public static function settingsURL( $full = TRUE )
	{
		// $relative = current_user_can( 'manage_options' ) ? 'admin.php?page='.self::SETTINGS : 'index.php?page='.self::SETTINGS;
		$relative = 'admin.php?page='.self::SETTINGS;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	public static function toolsURL( $full = TRUE )
	{
		$relative = 'admin.php?page='.self::TOOLS;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	public static function isSettings( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		if ( isset( $screen->base )
			&& FALSE !== strripos( $screen->base, self::SETTINGS ) )
				return TRUE;

		return FALSE;
	}

	public static function isTools( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		if ( isset( $screen->base )
			&& FALSE !== strripos( $screen->base, self::TOOLS ) )
				return TRUE;

		return FALSE;
	}

	public static function isDashboard( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		if ( isset( $screen->base )
			&& FALSE !== strripos( $screen->base, 'dashboard' ) )
				return TRUE;

		return FALSE;
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( 'geditorial_tinymce_strings', array() );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.geditorial", '.wp_json_encode( $strings ).');'."\n" : '';
	}

	public static function printJSConfig( $args, $object = 'gEditorial' )
	{
		$args['api']   = defined( 'GNETWORK_AJAX_ENDPOINT' ) && GNETWORK_AJAX_ENDPOINT ? GNETWORK_AJAX_ENDPOINT : admin_url( 'admin-ajax.php' );
		$args['nonce'] = wp_create_nonce( 'geditorial' );

	?> <script type="text/javascript">
/* <![CDATA[ */
	var <?php echo $object; ?> = <?php echo wp_json_encode( $args ); ?>;
	<?php if ( self::isDev() ) echo 'console.log('.$object.');'; ?>
/* ]]> */
</script><?php
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

		return self::html( 'div', array(
			'class' => array( 'geditorial-wordcount', 'hide-if-no-js' ),
			'data'  => apply_filters( 'geditorial_helper_wordcount_data', array_merge( $data, $defaults ), $for, $posttype ),
		), sprintf( _x( 'Word count: %s', 'Module Helper', GEDITORIAL_TEXTDOMAIN ), '<span class="-words">0</span>' ) );
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

	private static function getStringsFromName( $name )
	{
		if ( ! is_array( $name ) )
			return array(
				$name.'s',
				$name,
				self::strToLower( $name.'s' ),
				self::strToLower( $name ),
			);

		$strings = array(
			_nx( $name['singular'], $name['plural'], 2, $name['context'], $name['domain'] ),
			_nx( $name['singular'], $name['plural'], 1, $name['context'], $name['domain'] ),
		);

		$strings[2] = self::strToLower( $strings[0] );
		$strings[3] = self::strToLower( $strings[1] );

		return $strings;
	}

	/**
	 *	%1$s => Camel Case / Plural
	 *	%2$s => Camel Case / Singular
	 *	%3$s => Lower Case / Plural
	 *	%4$s => Lower Case / Singular
	 *
	 *	@REF: '_get_custom_object_labels()', `_nx_noop()`, `translate_nooped_plural()`
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
			'search_items'          => _x( 'Search %1$s', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'not_found'             => _x( 'No %3$s found.', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'not_found_in_trash'    => _x( 'No %3$s found in Trash.', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'parent_item_colon'     => _x( 'Parent %2$s:', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'all_items'             => _x( 'All %1$s', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'archives'              => _x( '%2$s Archives', 'Module Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
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
				$pre[$key] = vsprintf( $template, array( $featured, self::strToLower( $featured ) ) );

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
