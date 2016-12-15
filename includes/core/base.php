<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialBaseCore
{

	public static function req( $key, $default = '' )
	{
		return isset( $_REQUEST[$key] ) ? $_REQUEST[$key] : $default;
	}

	public static function limit( $default = 25, $key = 'limit' )
	{
		return intval( self::req( $key, $default ) );
	}

	public static function paged( $default = 1, $key = 'paged' )
	{
		return intval( self::req( $key, $default ) );
	}

	public static function orderby( $default = 'title', $key = 'orderby' )
	{
		return self::req( $key, $default );
	}

	public static function order( $default = 'desc', $key = 'order' )
	{
		return self::req( $key, $default );
	}

	public static function dump( $var, $safe = TRUE, $echo = TRUE )
	{
		$export = var_export( $var, TRUE );
		if ( $safe ) $export = htmlspecialchars( $export );
		$export = '<pre dir="ltr" style="text-align:left;direction:ltr;">'.$export.'</pre>';
		if ( ! $echo ) return $export;
		echo $export;
	}

	public static function kill()
	{
		foreach ( func_get_args() as $arg )
			self::dump( $arg );
		echo self::stat();
		die();
	}

	public static function stat( $format = NULL )
	{
		if ( is_null( $format ) )
			$format = '%d queries in %.3f seconds, using %.2fMB memory.';

		return sprintf( $format,
			@$GLOBALS['wpdb']->num_queries,
			self::timerStop( FALSE, 3 ),
			memory_get_peak_usage() / 1024 / 1024
		);
	}

	// WP core function without number_format_i18n
	public static function timerStop( $echo = FALSE, $precision = 3 )
	{
		global $timestart;
		$total = number_format( ( microtime( TRUE ) - $timestart ), $precision );
		if ( $echo ) echo $total;
		return $total;
	}

	public static function cheatin( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = __( 'Cheatin&#8217; uh?' );

		wp_die( $message, 403 );
	}

	// INTERNAL
	public static function __log( $log )
	{
		if ( defined( 'WP_DEBUG_LOG' ) && ! WP_DEBUG_LOG )
			return;

		if ( is_array( $log ) || is_object( $log ) )
			error_log( print_r( $log, TRUE ) );
		else
			error_log( $log );
	}

	// INTERNAL: used on anything deprecated
	protected static function __dep( $note = '', $prefix = 'DEP: ', $offset = 1 )
	{
		if ( defined( 'WP_DEBUG_LOG' ) && ! WP_DEBUG_LOG )
			return;

		$trace = debug_backtrace();

		$log = $prefix;

		if ( isset( $trace[$offset]['object'] ) )
			$log .= get_class( $trace[$offset]['object'] ).'::';
		else if ( isset( $trace[$offset]['class'] ) )
			$log .= $trace[$offset]['class'].'::';

		$log .= $trace[$offset]['function'].'()';

		$offset++;

		if ( isset( $trace[$offset]['function'] ) ) {
			$log .= '|FROM: ';
			if ( isset( $trace[$offset]['object'] ) )
				$log .= get_class( $trace[$offset]['object'] ).'::';
			else if ( isset( $trace[$offset]['class'] ) )
				$log .= $trace[$offset]['class'].'::';
			$log .= $trace[$offset]['function'].'()';
		}

		if ( $note )
			$log .= '|'.$note;

		error_log( $log );
	}

	// INTERNAL: used on anything deprecated : only on dev mode
	protected static function __dev_dep( $note = '', $prefix = 'DEP: ', $offset = 2 )
	{
		if ( self::isDev() )
			self::__dep( $note, $prefix, $offset );
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

	public static function getCurrentURL( $trailingslashit = FALSE, $forwarded_host = FALSE )
	{
		$ssl  = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' );
		$sp   = strtolower( $_SERVER['SERVER_PROTOCOL'] );
		$prot = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
		$port = $_SERVER['SERVER_PORT'];
		$port = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
		$host = ( $forwarded_host && isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : NULL );
		$host = isset( $host ) ? $host : $_SERVER['SERVER_NAME'].$port;

		return $prot.'://'.$host.$_SERVER['REQUEST_URI'];
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

	public static function getKeys( $options, $if = TRUE )
	{
		$keys = array();

		foreach ( (array) $options as $key => $value )
			if ( $value == $if )
				$keys[] = $key;

		return $keys;
	}

	// @SOURCE: http://wordpress.mfields.org/2011/rekey-an-indexed-array-of-post-objects-by-post-id/
	public static function reKey( $list, $key )
	{
		if ( empty( $list ) )
			return $list;

		$ids = wp_list_pluck( $list, $key );

		return array_combine( $ids, $list );
	}

	public static function sameKey( $old )
	{
		$same = array();

		foreach ( $old as $key => $value )
			if ( FALSE !== $value && NULL !== $value )
				$same[$value] = $value;

		return $same;
	}

	// FIXME: DEPRICATED: use `self::recursiveParseArgs()`
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

	/**
	 * recursive argument parsing
	 * @link: https://gist.github.com/boonebgorges/5510970
	 *
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

	// @SOURCE: http://stackoverflow.com/a/17620260
	public static function search_array( $value, $key, $array )
	{
		foreach ( $array as $k => $val )
			if ( $val[$key] == $value )
				return $k;
		return NULL;
	}

	public static function isDebug()
	{
		if ( WP_DEBUG && WP_DEBUG_DISPLAY && ! self::isDev() )
			return TRUE;

		return FALSE;
	}

	public static function isDev()
	{
		if ( defined( 'WP_STAGE' )
			&& 'development' == constant( 'WP_STAGE' ) )
				return TRUE;

		return FALSE;
	}

	public static function isFlush()
	{
		if ( isset( $_GET['flush'] ) )
			return did_action( 'init' ) && current_user_can( 'publish_posts' );

		return FALSE;
	}

	public static function isAJAX()
	{
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	public static function isCRON()
	{
		return defined( 'DOING_CRON' ) && DOING_CRON;
	}

	public static function isCLI()
	{
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	public static function doNotCache()
	{
		defined( 'DONOTCACHEPAGE' ) or define( 'DONOTCACHEPAGE', TRUE );
	}

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

	// FIXME: MUST DEP
	// EDITED: 8/11/2016, 6:40:18 AM
	public static function getPostTypes( $singular = FALSE, $builtin = NULL )
	{
		$list = array();
		$args = array( 'public' => TRUE );

		if ( ! is_null( $builtin ) )
			$args['_builtin'] = $builtin;

		$post_types = get_post_types( $args, 'objects' );

		foreach ( $post_types as $post_type => $post_type_obj )

			if ( TRUE === $singular )
				$list[$post_type] = $post_type_obj->labels->singular_name;

			else if ( $singular && ! empty( $post_type_obj->labels->{$singular} ) )
				$list[$post_type] = $post_type_obj->labels->{$singular};

			else
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

	public static function tableList( $columns, $data = array(), $args = array() )
	{
		if ( ! count( $columns ) )
			return FALSE;

		if ( ! $data || ! count( $data ) ) {
			if ( isset( $args['empty'] ) && $args['empty'] )
				echo '<div class="base-table-empty description">'.$args['empty'].'</div>';
			return FALSE;
		}

		if ( isset( $args['title'] ) && $args['title'] )
			echo '<div class="base-table-title">'.$args['title'].'</div>';

		$pagination = isset( $args['pagination'] ) ? $args['pagination'] : array();

		if ( isset( $args['before'] )
			|| ( isset( $args['navigation'] ) && 'before' == $args['navigation'] )
			|| ( isset( $args['search'] ) && 'before' == $args['search'] ) )
				echo '<div class="base-table-actions base-table-list-before">';
		else
			echo '<div>';

		if ( isset( $args['navigation'] ) && 'before' == $args['navigation'] )
			self::tableNavigation( $pagination );

		if ( isset( $args['before'] ) && is_callable( $args['before'] ) )
			call_user_func_array( $args['before'], array( $columns, $data, $args ) );

		echo '</div><table class="widefat fixed base-table-list"><thead><tr>';
			foreach ( $columns as $key => $column ) {

				$tag   = 'th';
				$class = '';

				if ( is_array( $column ) ) {
					$title = isset( $column['title'] ) ? $column['title'] : $key;

					if ( isset( $column['class'] ) )
						$class = esc_attr( $column['class'] );

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

		if ( isset( $args['after'] )
			|| ( isset( $args['navigation'] ) && 'after' == $args['navigation'] )
			|| ( isset( $args['search'] ) && 'after' == $args['search'] ) )
				echo '<div class="base-table-actions base-table-list-after">';
		else
			echo '<div>';

		if ( isset( $args['navigation'] ) && 'after' == $args['navigation'] )
			self::tableNavigation( $pagination );

		// FIXME: add search box

		if ( isset( $args['after'] ) && is_callable( $args['after'] ) )
			call_user_func_array( $args['after'], array( $columns, $data, $args ) );

		echo '</div>';

		return TRUE;
	}

	public static function tableNavigation( $pagination = array() )
	{
		$args = self::atts( array(
			'total'    => 0,
			'pages'    => 0,
			'limit'    => self::limit(),
			'paged'    => self::paged(),
			'all'      => FALSE,
			'next'     => FALSE,
			'previous' => FALSE,
		), $pagination );

		$icons = array(
			'next'     => '<span class="dashicons dashicons-redo"></span>', // &rsaquo;
			'previous' => '<span class="dashicons dashicons-undo"></span>', // &lsaquo;
			'refresh'  => '<span class="dashicons dashicons-image-rotate"></span>',
		);

		echo '<div class="base-table-navigation">';

			echo '<input type="number" class="small-text -paged" name="paged" value="'.$args['paged'].'" />';
			echo '<input type="number" class="small-text -limit" name="limit" value="'.$args['limit'].'" />';

			vprintf( '<span class="-total-pages">%s / %s</span>', array(
				number_format_i18n( $args['total'] ),
				number_format_i18n( $args['pages'] ),
			) );

			vprintf( '<span class="-next-previous">%s %s %s</span>', array(
				( FALSE === $args['previous'] ? '<span class="-previous -span" aria-hidden="true">'.$icons['previous'].'</span>' : gEditorialHTML::tag( 'a', array(
					'href'  => add_query_arg( 'paged', $args['previous'] ),
					'class' => '-previous -link',
				), $icons['previous'] ) ),
				gEditorialHTML::tag( 'a', array(
					'href'  => self::getCurrentURL(),
					'class' => '-refresh -link',
				), $icons['refresh'] ),
				( FALSE === $args['next'] ? '<span class="-next -span" aria-hidden="true">'.$icons['next'].'</span>' : gEditorialHTML::tag( 'a', array(
					'href'  => add_query_arg( 'paged', $args['next'] ),
					'class' => '-next -link',
				), $icons['next'] ) ),
			) );

		echo '</div>';
	}

	// @SOURCE: [Custom Field Taxonomies](https://github.com/scribu/wp-custom-field-taxonomies)
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

	// @SOURCE: [Custom Field Taxonomies](https://github.com/scribu/wp-custom-field-taxonomies)
	public static function getDBPostMetaKeys( $same_key = FALSE )
	{
		global $wpdb;

		$meta_keys = $wpdb->get_col( "
			SELECT meta_key
			FROM $wpdb->postmeta
			GROUP BY meta_key
			HAVING meta_key NOT LIKE '\_%'
			ORDER BY meta_key ASC
		" );

		return $same_key ? self::sameKey( $meta_keys ) : $meta_keys;
	}

	// @SEE: delete_post_meta_by_key( 'related_posts' );
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

	// EDITED: 5/2/2016, 9:31:13 AM
	public static function insertDefaultTerms( $taxonomy, $terms )
	{
		if ( ! taxonomy_exists( $taxonomy ) )
			return FALSE;

		$count = 0;

		foreach ( $terms as $slug => $term ) {

			$name = $term;
			$args = array( 'slug' => $slug, 'name' => $term );
			$meta = array();

			if ( is_array( $term ) ) {

				if ( ! empty( $term['name'] ) )
					$name = $args['name'] = $term['name'];
				else
					$name = $slug;

				if ( ! empty( $term['description'] ) )
					$args['description'] = $term['description'];

				if ( ! empty( $term['slug'] ) )
					$args['slug'] = $term['slug'];

				if ( ! empty( $term['parent'] ) ) {
					if ( is_numeric( $term['parent'] ) )
						$args['parent'] = $term['parent'];
					else if ( $parent = term_exists( $term['parent'], $taxonomy ) )
						$args['parent'] = $parent['term_id'];
				}

				if ( ! empty( $term['meta'] ) && is_array( $term['meta'] ) )
					foreach ( $term['meta'] as $term_meta_key => $term_meta_value )
						$meta[$term_meta_key] = $term_meta_value;
			}

			if ( $existed = term_exists( $slug, $taxonomy ) )
				wp_update_term( $existed['term_id'], $taxonomy, $args );
			else
				$existed = wp_insert_term( $name, $taxonomy, $args );

			if ( ! is_wp_error( $existed ) ) {

				foreach ( $meta as $meta_key => $meta_value )
					add_term_meta( $existed['term_id'], $meta_key, $meta_value, TRUE ); // will bail if an entry with the same key is found

				$count++;
			}
		}

		return $count;
	}

	// this must be core's
	// call this late on 'after_setup_theme' hook
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

	// this must be core's
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

	public static function range( $start, $end, $step = 1, $format = TRUE )
	{
		$array = array();

		foreach ( range( $start, $end, $step ) as $number )
			$array[$number] = $format ? number_format_i18n( $number ) : $number;

		return $array;
	}
}
