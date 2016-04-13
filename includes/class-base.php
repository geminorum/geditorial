<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialBaseCore
{

	public static function dump( &$var, $htmlSafe = TRUE )
	{
		$result = var_export( $var, TRUE );

		echo '<pre dir="ltr" style="text-align:left;direction:ltr;">'
			.( $htmlSafe ? htmlspecialchars( $result ) : $result )
			.'</pre>';
	}

	// INTERNAL: used on anything deprecated
	protected static function __dep( $note = '' )
	{
		if ( defined( 'WP_DEBUG_LOG' ) && ! WP_DEBUG_LOG )
			return;

		$trace = debug_backtrace();

		$log = 'DEP: ';

		if ( isset( $trace[1]['object'] ) )
			$log .= get_class( $trace[1]['object'] ).'::';
		else if ( isset( $trace[1]['class'] ) )
			$log .= $trace[1]['class'].'::';

		$log .= $trace[1]['function'].'()';

		if ( isset( $trace[2]['function'] ) ) {
			$log .= '|FROM: ';
			if ( isset( $trace[2]['object'] ) )
				$log .= get_class( $trace[2]['object'] ).'::';
			else if ( isset( $trace[2]['class'] ) )
				$log .= $trace[2]['class'].'::';
			$log .= $trace[2]['function'].'()';
		}

		if ( $note )
			$log .= '|'.$note;

		error_log( $log );
	}

	public static function atts( $pairs, $atts )
	{
		$atts = (array) $atts;
		$out  = array();

		foreach ( $pairs as $name => $default ) {
			if ( array_key_exists( $name, $atts ) )
				$out[$name] = $atts[$name];
			else
				$out[$name] = $default;
		}

		return $out;
	}

	// BEFORE: term_description()
	public static function termDescription( $term, $echo_attr = FALSE )
	{
		if ( ! $term )
			return;

		if ( ! $term->description )
			return;

		// Bootstrap 3
		$desc = esc_attr( $term->name ).'"  data-toggle="popover" data-trigger="hover" data-content="'.$term->description;

		if ( ! $echo_attr )
			// return $term->name.' :: '.strip_tags( $term->description );
			return $desc;

		echo ' title="'.$desc.'"';
	}

	// @SEE: get_search_link()
	public static function getSearchLink( $query = FALSE )
	{
		if ( defined( 'GNETWORK_SEARCH_REDIRECT' ) && GNETWORK_SEARCH_REDIRECT )
			return $query ? add_query_arg( GNETWORK_SEARCH_QUERYID, urlencode( $query ), GNETWORK_SEARCH_URL ) : GNETWORK_SEARCH_URL;

		return $query ? add_query_arg( 's', urlencode( $query ), get_option( 'home' ) ) : get_option( 'home' );
	}

	// @SEE: get_edit_term_link()
	public static function getEditTaxLink( $taxonomy, $term_id = FALSE )
	{
		if ( $term_id )
			return add_query_arg( array(
				'taxonomy' => $taxonomy,
				'tag_ID'   => $term_id,
			), admin_url( 'term.php' ) );

		else
			return add_query_arg( array(
				'taxonomy' => $taxonomy,
			), admin_url( 'edit-tags.php' ) );
	}

	// originally from P2
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

	// parsing: 'category:12,11|post_tag:3|people:58'
	public static function parseTerms( $string )
	{
		if ( empty( $string ) || ! $string )
			return FALSE;

		$taxonomies = array();

		foreach ( explode( '|', $string ) as $taxonomy ) {

			list( $tax, $terms ) = explode( ':', $taxonomy );

			$terms = explode( ',', $terms );
			$terms = array_map( 'intval', $terms );

			$taxonomies[$tax] = array_unique( $terms );
		}

		return $taxonomies;
	}

	private static function _tag_open( $tag, $atts, $content = TRUE )
	{
		$html = '<'.$tag;
		foreach ( $atts as $key => $att ) {

			if ( is_array( $att ) && count( $att ) ) {
				if ( 'data' == $key ) {
					foreach ( $att as $data_key => $data_val ) {
						if ( is_array( $data_val ) )
							$html .= ' data-'.$data_key.'=\''.wp_json_encode( $data_val ).'\'';
						else
							$html .= ' data-'.$data_key.'="'.esc_attr( $data_val ).'"';
					}
					continue;

				} else {
					$att = implode( ' ', array_unique( $att ) );
				}
			}

			if ( 'selected' == $key )
				$att = ( $att ? 'selected' : FALSE );

			if ( 'checked' == $key )
				$att = ( $att ? 'checked' : FALSE );

			if ( 'readonly' == $key )
				$att = ( $att ? 'readonly' : FALSE );

			if ( 'disabled' == $key )
				$att = ( $att ? 'disabled' : FALSE );

			if ( FALSE === $att )
				continue;

			if ( 'class' == $key )
				// $att = sanitize_html_class( $att, FALSE );
				$att = $att;

			else if ( 'href' == $key && '#' != $att )
				$att = esc_url( $att );

			else if ( 'src' == $key )
				$att = esc_url( $att );

			// else if ( 'input' == $tag && 'value' == $key )
			// 	$att = $att;

			else
				$att = esc_attr( $att );

			$html .= ' '.$key.'="'.$att.'"';
		}

		if ( FALSE === $content )
			return $html.' />';

		return $html.'>';
	}

	public static function html( $tag, $atts = array(), $content = FALSE, $sep = '' )
	{
		if ( is_array( $atts ) )
			$html = self::_tag_open( $tag, $atts, $content );
		else
			return '<'.$tag.'>'.$atts.'</'.$tag.'>'.$sep;

		if ( FALSE === $content )
			return $html.$sep;

		if ( is_null( $content ) )
			return $html.'</'.$tag.'>'.$sep;

		return $html.$content.'</'.$tag.'>'.$sep;
	}

	public static function getCurrentURL( $trailingslashit = FALSE )
	{
		global $wp;

		// if ( is_admin() )
		// 	$current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );
		// else
			$current_url = home_url( add_query_arg( array(), ( empty( $wp->request ) ? FALSE : $wp->request ) ) );

		if ( $trailingslashit )
			return trailingslashit( $current_url );

		return $current_url;
	}

	public static function getRegisterURL( $register = FALSE )
	{
		if ( function_exists( 'buddypress' ) ) {
			if ( bp_get_signup_allowed() )
				return bp_get_signup_page();
		} else if ( get_option( 'users_can_register' ) ) {
			if ( is_multisite() )
				return apply_filters( 'wp_signup_location', network_site_url( 'wp-signup.php' ) );
			else
				return site_url( 'wp-login.php?action=register', 'login' );
		} else if ( 'site' == $register ) {
			return  site_url( '/' );
		}
		return $register;
	}

	// FIXME: DEPRICATED: use self::recursiveParseArgs()
	public static function parse_args_r( &$a, $b )
	{
		self::__dep( 'self::recursiveParseArgs()' );

		$a = (array) $a;
		$b = (array) $b;
		$r = $b;

		foreach ( $a as $k => &$v ) {
			if ( is_array( $v ) && isset( $r[$k] ) ) {
				$r[$k] = self::parse_args_r( $v, $r[$k] );
			} else {
				$r[$k] = $v;
			}
		}

		return $r;
	}

	// recursive argument parsing
	// @REF: https://gist.github.com/boonebgorges/5510970
	/**
	* Values from $a override those from $b; keys in $b that don't exist
	* in $a are passed through.
	*
	* This is different from array_merge_recursive(), both because of the
	* order of preference ($a overrides $b) and because of the fact that
	* array_merge_recursive() combines arrays deep in the tree, rather
	* than overwriting the b array with the a array.
	*/
	public static function recursiveParseArgs( &$a, $b )
	{
		$a = (array) $a;
		$b = (array) $b;
		$r = $b;

		foreach ( $a as $k => &$v )
			if ( is_array( $v ) && isset( $r[$k] ) )
				$r[$k] = self::recursiveParseArgs( $v, $r[$k] );
			else
				$r[$k] = $v;

		return $r;
	}

	public static function linkStyleSheet( $url, $version = NULL, $media = 'all' )
	{
		if ( is_array( $version ) )
			$url = add_query_arg( $version, $url );

		else if ( $version )
			$url = add_query_arg( 'ver', $version, $url );

		echo "\t".self::html( 'link', array(
			'rel'   => 'stylesheet',
			'href'  => $url,
			'type'  => 'text/css',
			'media' => $media,
		) )."\n";
	}

	public static function IP( $pad = FALSE )
	{
		$ip = '';

		if ( getenv( 'HTTP_CLIENT_IP' ) )
			$ip = getenv( 'HTTP_CLIENT_IP' );

		else if ( getenv( 'HTTP_X_FORWARDED_FOR' ) )
			$ip = getenv( 'HTTP_X_FORWARDED_FOR' );

		else if ( getenv( 'HTTP_X_FORWARDED' ) )
			$ip = getenv( 'HTTP_X_FORWARDED' );

		else if ( getenv( 'HTTP_FORWARDED_FOR' ) )
			$ip = getenv( 'HTTP_FORWARDED_FOR' );

		else if ( getenv( 'HTTP_FORWARDED' ) )
			$ip = getenv( 'HTTP_FORWARDED' );

		else
			$ip = getenv( 'REMOTE_ADDR' );

		if ( $pad )
			return str_pad( $ip, 15, ' ', STR_PAD_LEFT );

		return $ip;
	}

	// http://stackoverflow.com/a/17620260
	public static function search_array( $value, $key, $array )
	{
		foreach ( $array as $k => $val )
			if ( $val[$key] == $value )
				return $k;
		return NULL;
	}

	public static function headerNav( $uri = '', $active = '', $subs = array(), $prefix = 'nav-tab-', $tag = 'h3' )
	{
		if ( ! count( $subs ) )
			return;

		$html = '';

		foreach ( $subs as $slug => $page )
			$html .= self::html( 'a', array(
				'class' => 'nav-tab '.$prefix.$slug.( $slug == $active ? ' nav-tab-active' : '' ),
				'href'  => add_query_arg( 'sub', $slug, $uri ),
			), $page );

		echo self::html( $tag, array(
			'class' => 'nav-tab-wrapper',
		), $html );
	}

	public static function getFeaturedImage( $post_id, $size = 'thumbnail', $default = FALSE )
	{
		if ( ! $post_thumbnail_id = get_post_thumbnail_id( $post_id ) )
			return $default;

		$post_thumbnail_img = wp_get_attachment_image_src( $post_thumbnail_id, $size );
		return $post_thumbnail_img[0];
	}

	public static function getFeaturedImageHTML( $post_id, $size = 'thumbnail', $link = TRUE )
	{
		if ( ! $post_thumbnail_id = get_post_thumbnail_id( $post_id ) )
			return '';

		$post_thumbnail_img = wp_get_attachment_image_src( $post_thumbnail_id, $size );

		$image = self::html( 'img', array(
			'src'   => $post_thumbnail_img[0],
			'class' => 'column-cover-img',
		) );

		if ( ! $link )
			return $image;

		return self::html( 'a', array(
			'href'   => wp_get_attachment_url( $post_thumbnail_id ),
			'class'  => 'column-cover-link',
			'target' => '_blank',
		), $image );
	}

	// http://bavotasan.com/2012/trim-characters-using-php/
	public static function trimChars( $text, $length = 45, $append = '&hellip;' )
	{
		$length = (int) $length;
		$text   = trim( strip_tags( $text ) );

		if ( strlen( $text ) > $length ) {

			$text  = substr( $text, 0, $length + 1 );
			$words = preg_split( "/[\s]|&nbsp;/", $text, -1, PREG_SPLIT_NO_EMPTY );

			preg_match( "/[\s]|&nbsp;/", $text, $lastchar, 0, $length );

			if ( empty( $lastchar ) )
				array_pop( $words );

			$text = implode( ' ', $words ).$append;
		}

		return $text;
	}

	public static function isDev()
	{
		if ( defined( 'WP_STAGE' )
			&& 'development' == constant( 'WP_STAGE' ) )
				return TRUE;

		return FALSE;
	}

	public static function isDebug()
	{
		if ( WP_DEBUG && WP_DEBUG_DISPLAY && ! self::isDev() )
			return TRUE;

		return FALSE;
	}

	public static function notice( $notice, $class = 'updated fade', $echo = TRUE )
	{
		$html = sprintf( '<div id="message" class="%s notice is-dismissible"><p>%s</p></div>', $class, $notice );

		if ( ! $echo )
			return $html;

		echo $html;
	}

	public static function error( $message )
	{
		return self::notice( $message, 'error fade', FALSE );
	}

	public static function updated( $message )
	{
		return self::notice( $message, 'updated fade', FALSE );
	}

	public static function counted( $message = NULL, $count = NULL, $class = 'updated' )
	{
		if ( is_null( $message ) )
			$message = _x( '%s Counted!', 'Module Core', GEDITORIAL_TEXTDOMAIN );

		if ( is_null( $count ) )
			$count = isset( $_REQUEST['count'] ) ? $_REQUEST['count'] : 0;

		return self::notice( sprintf( $message, number_format_i18n( $count ) ), $class.' fade', FALSE );
	}

	// checks for the current post type
	// OLD: get_current_post_type()
	public static function getCurrentPostType( $default = NULL )
	{
		global $post, $typenow, $pagenow, $current_screen;

		if ( $post && $post->post_type )
			return $post->post_type;

		if ( $typenow )
			return $typenow;

		if ( $current_screen && isset( $current_screen->post_type ) )
			return $current_screen->post_type;

		if ( isset( $_REQUEST['post_type'] ) )
			return sanitize_key( $_REQUEST['post_type'] );

		return $default;
	}

	public static function update_count_callback( $terms, $taxonomy )
	{
		global $wpdb;
		foreach ( (array) $terms as $term ) {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term ) );
			do_action( 'edit_term_taxonomy', $term, $taxonomy );
			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
			do_action( 'edited_term_taxonomy', $term, $taxonomy );
		}
	}

	// fall back for meta_term
	// before : get_post_id_by_slug()
	public static function getPostIDbySlug( $slug, $post_type, $url = FALSE )
	{
		global $wpdb;

		if ( $url ) {
			$slug = rawurlencode( urldecode( $slug ) );
			$slug = sanitize_title( basename( $slug ) );
		}

		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = %s",
				trim( $slug ),
				$post_type
			)
		);

		if ( is_array( $post_id ) )
			return $post_id[0];

		else if ( ! empty( $post_id ) )
			return $post_id;

		return FALSE;
	}

	// before: the_term()
	public static function theTerm( $taxonomy, $post_ID, $object = FALSE )
	{
		$terms = get_the_terms( $post_ID, $taxonomy );

		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( $object ) {
					return $term;
				} else {
					return $term->term_id;
				}
			}
		}

		return '0';
	}

	public static function getPostTypes( $builtin = NULL )
	{
		$list = array();
		$args = array( 'public' => TRUE );

		if ( ! is_null( $builtin ) )
			$args['_builtin'] = $builtin;

		$post_types = get_post_types( $args, 'objects' );

		foreach ( $post_types as $post_type => $post_type_obj )
			$list[$post_type] = $post_type_obj->labels->name;

		return $list;
	}

	public static function getTaxonomies( $with = FALSE )
	{
		$list = array();

		$taxonomies = get_taxonomies( array(
			// 'public'   => TRUE,
			// '_builtin' => TRUE,
		), 'objects' );

		if ( $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				if ( ! empty( $taxonomy->labels->menu_name )  ) {

					if ( 'type' == $with )
						$list[$taxonomy->name] = $taxonomy->labels->menu_name.' ('.implode( _x( ', ', 'Module Helper: Post Type Seperator', GEDITORIAL_TEXTDOMAIN ), $taxonomy->object_type ).')';

					else if ( 'name' == $with )
						$list[$taxonomy->name] = $taxonomy->labels->menu_name.' ('.$taxonomy->name.')';

					else
						$list[$taxonomy->name] = $taxonomy->labels->menu_name;
				}
			}
		}

		return $list;
	}

	public static function newPostFromTerm( $term, $taxonomy = 'category', $post_type = 'post' )
	{
		if ( ! is_object( $term ) && ! is_array( $term ) )
			$term = get_term( $term, $taxonomy );

		$new_post = array(
			'post_title'   => $term->name,
			'post_name'    => $term->slug,
			'post_content' => $term->description,
			'post_status'  => 'draft',
			'post_author'  => self::getEditorialUserID(),
			'post_type'    => $post_type,
		);

		return wp_insert_post( $new_post );
	}

	// MAYBE: add general options for gEditorial
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

	public static function tableList( $columns, $data = array(), $actions = array() )
	{
		if ( ! count( $columns ) )
			return FALSE;

		echo '<table class="widefat fixed helper-table"><thead><tr>';
			foreach ( $columns as $key => $column ) {

				$tag   = 'th';
				$class = '';

				if ( is_array( $column ) ) {
					$title = isset( $column['title'] ) ? $column['title'] : $key;
				} else if ( '_cb' == $key ) {
					$title = '<input type="checkbox" id="cb-select-all-1" class="-cb-all" />';
					$class = ' check-column';
					$tag   = 'td';
				} else {
					$title = $column;
				}

				echo '<'.$tag.' class="-column -column-'.esc_attr( $key ).$class.'">'.$title.'</'.$tag.'>';
			}
		echo '</tr></thead><tbody>';

		$alt = TRUE;
		foreach ( $data as $index => $row ) {

			echo '<tr class="-row -row-'.$index.( $alt ? ' alternate' : '' ).'">';

			foreach ( $columns as $key => $column ) {

				$class = $callback = '';
				$cell = 'td';

				if ( '_cb' == $key ) {
					if ( '_index' == $column )
						$value = $index;
					else if ( is_array( $column ) && isset( $column['value'] ) )
						$value = call_user_func_array( $column['value'], array( NULL, $row, $column, $index ) );
					else if ( is_array( $row ) && isset( $row[$column] ) )
						$value = $row[$column];
					else if ( is_object( $row ) && isset( $row->{$column} ) )
						$value = $row->{$column};
					else
						$value = '';
					$value = '<input type="checkbox" name="_cb[]" value="'.esc_attr( $value ).'" class="-cb" />';
					$class .= ' check-column';
					$cell = 'th';

				} else if ( is_array( $row ) && isset( $row[$key] ) ) {
					$value = $row[$key];

				} else if ( is_object( $row ) && isset( $row->{$key} ) ) {
					$value = $row->{$key};

				} else {
					$value = NULL;
				}

				if ( is_array( $column ) ) {
					if ( isset( $column['class'] ) )
						$class .= ' '.esc_attr( $column['class'] );

					if ( isset( $column['callback'] ) )
						$callback = $column['callback'];
				}

				echo '<'.$cell.' class="-cell -cell-'.$key.$class.'">';

				if ( $callback ){
					echo call_user_func_array( $callback, array( $value, $row, $column, $index ) );

				} else if ( $value ) {
					echo $value;

				} else {
					echo '&nbsp;';

				}

				echo '</'.$cell.'>';
			}

			$alt = ! $alt;

			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '<div class="clear"></div>';
	}

	// from : Custom Field Taxonomies : https://github.com/scribu/wp-custom-field-taxonomies
	public static function getDBPostMetaRows( $meta_key, $limit = FALSE )
	{
		global $wpdb;

		if ( $limit )
			$query = $wpdb->prepare( "
				SELECT post_id, GROUP_CONCAT( meta_value ) as meta
				FROM $wpdb->postmeta
				WHERE meta_key = %s
				GROUP BY post_id
				 LIMIT %d
			", $meta_key, $limit );
		else
			$query = $wpdb->prepare( "
				SELECT post_id, GROUP_CONCAT( meta_value ) as meta
				FROM $wpdb->postmeta
				WHERE meta_key = %s
				GROUP BY post_id
			", $meta_key );

		return $wpdb->get_results( $query );
	}

	// from : Custom Field Taxonomies : https://github.com/scribu/wp-custom-field-taxonomies
	public static function getDBPostMetaKeys( $rekey = FALSE )
	{
		global $wpdb;

		$keys = $wpdb->get_col( "
			SELECT meta_key
			FROM $wpdb->postmeta
			GROUP BY meta_key
			HAVING meta_key NOT LIKE '\_%'
			ORDER BY meta_key ASC
		" );

		if ( ! $rekey )
			return $keys;

		$re = array();
		foreach ( $keys as $key )
			$re[$key] = $key;

		return $re;
	}

	// SEE : delete_post_meta_by_key( 'related_posts' );
	public static function deleteDBPostMeta( $meta_key, $limit = FALSE )
	{
		global $wpdb;

		if ( $limit )
			$query = $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s LIMIT %d", $meta_key, $limit );
		else
			$query = $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s", $meta_key );

		return $wpdb->query( $query );
	}

	public static function deleteEmptyMeta( $meta_key )
	{
		global $wpdb;

		$query = $wpdb->prepare( "
			DELETE FROM $wpdb->postmeta
			WHERE meta_key = %s
			AND meta_value = ''
		" , $meta_key );

		return $wpdb->get_results( $query );
	}

	public static function insertDefaultTerms( $taxonomy, $defaults )
	{
		if ( ! taxonomy_exists( $taxonomy ) )
			return FALSE;

		foreach ( $defaults as $term_slug => $term_name )
			if ( $term = term_exists( $term_slug, $taxonomy ) )
				wp_update_term( $term['term_id'], $taxonomy, array( 'name' => $term_name ) );
			else
				wp_insert_term( $term_name, $taxonomy, array( 'slug' => $term_slug ) );

		return TRUE;
	}

	public static function getLastPostOrder( $post_type = 'post', $exclude = '', $key = 'menu_order', $status = 'publish,private,draft' )
	{
		$post = get_posts( array(
			'posts_per_page' => 1,
			'orderby'        => 'menu_order',
			'exclude'        => $exclude,
			'post_type'      => $post_type,
			'post_status'    => $status,
		) );

		if ( ! count( $post ) )
			return 0;

		if ( 'menu_order' == $key )
			return intval( $post[0]->menu_order );

		return $post[0]->{$key};
	}

	// this must be wp core future!!
	// call this late on after_setup_theme
	public static function themeThumbnails( $post_types )
	{
		global $_wp_theme_features;
		$feature = 'post-thumbnails';
		// $post_types = (array) $post_types;

		if ( isset( $_wp_theme_features[$feature] ) ) {

			// registered for all types
			if ( TRUE === $_wp_theme_features[$feature] ) {

				// WORKING: but if it is true, it's true!
				// $post_types[] = 'post';
				// $_wp_theme_features[$feature] = array( $post_types );

			} else if ( is_array( $_wp_theme_features[$feature][0] ) ){
				$_wp_theme_features[$feature][0] = array_merge( $_wp_theme_features[$feature][0], $post_types );
			}

		} else {
			$_wp_theme_features[$feature] = array( $post_types );
		}
	}

	// this must be wp core future!!
	// core duplication with post_type & title : add_image_size()
	public static function registerImageSize( $name, $atts = array() )
	{
		global $_wp_additional_image_sizes;

		$args = self::atts( array(
			'n' => _x( 'Undefined Image Size', 'Module Helper', GEDITORIAL_TEXTDOMAIN ),
			'w' => 0,
			'h' => 0,
			'c' => 0,
			'p' => array( 'post' ),
		), $atts );

		$_wp_additional_image_sizes[$name] = array(
			'width'     => absint( $args['w'] ),
			'height'    => absint( $args['h'] ),
			'crop'      => $args['c'],
			'post_type' => $args['p'],
			'title'     => $args['n'],
		);
	}

	public static function getRegisteredImageSizes( $post_type = 'post', $key = 'post_type' )
	{
		global $_wp_additional_image_sizes;

		$sizes = array();

		foreach ( $_wp_additional_image_sizes as $name => $size )
			if ( isset( $size[$key] ) && in_array( $post_type, $size[$key] ) )
				$sizes[$name] = $size;
			else if ( 'post' == $post_type ) // fallback
				$sizes[$name] = $size;

		return $sizes;
	}

	// http://php.net/manual/en/function.str-word-count.php#107363
	/***
	* This simple utf-8 word count function (it only counts)
	* is a bit faster then the one with preg_match_all
	* about 10x slower then the built-in str_word_count
	*
	* If you need the hyphen or other code points as word-characters
	* just put them into the [brackets] like [^\p{L}\p{N}\'\-]
	* If the pattern contains utf-8, utf8_encode() the pattern,
	* as it is expected to be valid utf-8 (using the u modifier).
	**/
	public static function wordCountUTF8( $str )
	{
		return count( preg_split( '~[^\p{L}\p{N}\']+~u', $str ) );
	}

	// http://php.net/manual/en/function.str-word-count.php#85579
	public static function wordCountUTF8alt( $str )
	{
		return preg_match_all( "/\\p{L}[\\p{L}\\p{Mn}\\p{Pd}'\\x{2019}]*/u", $str, $matches );
	}

	// FIXME: not working! probably on utf8
	// http://php.net/manual/en/function.str-word-count.php#79699
	public static function wordCount( $html )
	{
		# strip all html tags
		$wc = strip_tags( preg_replace( array(
			'@<script[^>]*?>.*?</script>@si',
			'@<style[^>]*?>.*?</style>@siU',
			'@<![\s\S]*?--[ \t\n\r]*>@'
		), '', $html ) );

		# remove 'words' that don't consist of alphanumerical characters or punctuation
		$wc = trim( preg_replace( "#[^(\w|\d|\'|\"|\.|\!|\?|;|,|\\|\/|\-|:|\&|@)]+#", ' ', $wc ) );

		# remove one-letter 'words' that consist only of punctuation
		$wc = trim( preg_replace( "#\s*[(\'|\"|\.|\!|\?|;|,|\\|\/|\-|:|\&|@)]\s*#", ' ', $wc ) );

		# remove superfluous whitespace
		$wc = preg_replace( "/\s\s+/", ' ', $wc );

		# split string into an array of words
		$wc = explode( ' ', $wc );

		# remove empty elements
		$wc = array_filter( $wc );

		# return the number of words
		return count( $wc );
	}
}
