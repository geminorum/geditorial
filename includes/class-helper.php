<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialHelper
{

	public static function dump( &$var, $htmlSafe = true )
	{
		$result = var_export( $var, true );
		echo '<pre dir="ltr" style="text-align:left;direction:ltr;">'.( $htmlSafe ? htmlspecialchars( $result ) : $result).'</pre>';
	}

	// originally from P2
	public static function excerpted_title( $content, $word_count )
	{
		$content = strip_tags( $content );
		$words = preg_split( '/([\s_;?!\/\(\)\[\]{}<>\r\n\t"]|\.$|(?<=\D)[:,.\-]|[:,.\-](?=\D))/', $content, $word_count + 1, PREG_SPLIT_NO_EMPTY );

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
	public static function parse_terms( $string )
	{
		if ( empty( $string ) || ! $string )
			return false;

		$taxonomies = array();
		foreach ( explode( '|', $string ) as $taxonomy ) {
			list( $tax, $terms ) = explode( ':', $taxonomy );
			$terms = explode( ',', $terms );
			$terms = array_map( 'intval', $terms );
			$taxonomies[$tax] = array_unique( $terms );
		}
		return $taxonomies;
	}

	private static function _tag_open( $tag, $atts, $content = true )
	{
		$html = '<'.$tag;
		foreach( $atts as $key => $att ) {

			if ( is_array( $att ) && count( $att ) )
				$att = implode( ' ', array_unique( $att ) );

			if ( 'selected' == $key )
				$att = ( $att ? 'selected' : false );

			if ( 'checked' == $key )
				$att = ( $att ? 'checked' : false );

			if ( 'readonly' == $key )
				$att = ( $att ? 'readonly' : false );

			if ( 'disabled' == $key )
				$att = ( $att ? 'disabled' : false );

			if ( false === $att )
				continue;

			if ( 'class' == $key )
				//$att = sanitize_html_class( $att, false );
				$att = $att;
			else if ( 'href' == $key || 'src' == $key )
				$att = esc_url( $att );
			//else if ( 'input' == $tag && 'value' == $key )
				//$att = $att;
			else
				$att = esc_attr( $att );

			$html .= ' '.$key.'="'.$att.'"';
		}
		if ( false === $content )
			return $html.' />';
		return $html.'>';
	}

	public static function html( $tag, $atts = array(), $content = false, $sep = '' )
	{
		$html = self::_tag_open( $tag, $atts, $content );

		if ( false === $content )
			return $html.$sep;

		if ( is_null( $content ) )
			return $html.'</'.$tag.'>'.$sep;

		return $html.$content.'</'.$tag.'>'.$sep;
	}

	public static function getCurrentURL( $trailingslashit = false )
	{
		global $wp;

		if ( is_admin() )
			$current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );
		else
			$current_url = home_url( add_query_arg( array(), ( empty( $wp->request ) ? false : $wp->request ) ) );

		if ( $trailingslashit )
			return trailingslashit( $current_url );

		return $current_url;
	}

	public static function getRegisterURL( $register = false )
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


	// http://teleogistic.net/2013/05/a-recursive-sorta-version-of-wp_parse_args/
	// https://gist.github.com/boonebgorges/5510970
	/**
	* Recursive argument parsing
	*
	* This acts like a multi-dimensional version of wp_parse_args() (minus
	* the querystring parsing - you must pass arrays).
	*
	* Values from $a override those from $b; keys in $b that don't exist
	* in $a are passed through.
	*
	* This is different from array_merge_recursive(), both because of the
	* order of preference ($a overrides $b) and because of the fact that
	* array_merge_recursive() combines arrays deep in the tree, rather
	* than overwriting the b array with the a array.
	*
	* The implementation of this function is specific to the needs of
	* BP_Group_Extension, where we know that arrays will always be
	* associative, and that an argument under a given key in one array
	* will be matched by a value of identical depth in the other one. The
	* function is NOT designed for general use, and will probably result
	* in unexpected results when used with data in the wild. See, eg,
	* http://core.trac.wordpress.org/ticket/19888
	*
	* @since BuddyPress (1.8)
	* @arg array $a
	* @arg array $b
	* @return array
	*/
	public static function parse_args_r( &$a, $b )
	{
		$a = (array) $a;
		$b = (array) $b;
		$r = $b;

		foreach ( $a as $k => &$v ) {
			if ( is_array( $v ) && isset( $r[ $k ] ) ) {
				$r[ $k ] = self::parse_args_r( $v, $r[ $k ] );
			} else {
				$r[ $k ] = $v;
			}
		}

		return $r;
	}

	public static function term_description( $term, $echo_att = false )
	{
		if ( ! $term->description )
			return;

		if ( ! $echo_att )
			return $term->name.' :: '.strip_tags( $term->description );

		// Bootstrap 3
		echo ' title="'.esc_attr( $term->name ).'"  data-toggle="popover" data-trigger="hover" data-content="'.$term->description.'"';
	}

	public static function linkStyleSheet( $url, $version = GEDITORIAL_VERSION, $media = false )
	{
		echo "\t".self::html( 'link', array(
			'rel' => 'stylesheet',
			'href' => add_query_arg( 'ver', $version, $url ),
			'type' => 'text/css',
			'media' => $media,
		) )."\n";
	}

	public static function IP()
	{
		if ( getenv( 'HTTP_CLIENT_IP' ) )
			return getenv( 'HTTP_CLIENT_IP' );

		if ( getenv( 'HTTP_X_FORWARDED_FOR' ) )
			return getenv( 'HTTP_X_FORWARDED_FOR' );

		if ( getenv( 'HTTP_X_FORWARDED' ) )
			return getenv( 'HTTP_X_FORWARDED' );

		if ( getenv( 'HTTP_FORWARDED_FOR' ) )
			return getenv( 'HTTP_FORWARDED_FOR' );

		if ( getenv( 'HTTP_FORWARDED' ) )
			return getenv( 'HTTP_FORWARDED' );

		return $_SERVER['REMOTE_ADDR'];
	}

	// http://stackoverflow.com/a/17620260
	public static function search_array( $value, $key, $array )
	{
		foreach ( $array as $k => $val )
			if ( $val[$key] == $value )
				return $k;
		return null;
	}

	public static function header_nav( $settings_uri = '', $active = '', $sub_pages = array(), $class_prefix = 'nav-tab-', $tag = 'h3' )
	{
		if ( ! count( $sub_pages ) )
			return;

		$html = '';

		foreach ( $sub_pages as $page_slug => $sub_page )
			$html .= self::html( 'a', array(
				'class' => 'nav-tab '.$class_prefix.$page_slug.( $page_slug == $active ? ' nav-tab-active' : '' ),
				'href' => add_query_arg( 'sub', $page_slug, $settings_uri ),
			), esc_html( $sub_page ) );

		echo self::html( $tag, array(
			'class' => 'nav-tab-wrapper',
		), $html );
	}

	public static function meta_admin_field( $field, $fields, $post, $ltr = false, $title = null, $key = false )
	{
		global $gEditorial;

		if ( in_array( $field, $fields )
			&& $gEditorial->meta->user_can( 'view', $field )  ) {

				if ( is_null( $title ) )
					$title = $gEditorial->meta->get_string( $field, $post->post_type );

				$atts = array(
					'type'         => 'text',
					'autocomplete' => 'off',
					'class'        => 'field-text geditorial-meta-field-'.$field,
					'name'         => 'geditorial-meta-'.$field.( $key === false ? '' : '['.$key.']' ),
					'id'           => 'geditorial-meta-'.$field.( $key === false ? '' : '-'.$key ),
					'value'        => $gEditorial->meta->get_postmeta( $post->ID, $field ),
					'title'        => $title,
					'placeholder'  => $title,
					'readonly'     => ! $gEditorial->meta->user_can( 'edit', $field ),
				);

				if ( $ltr )
					$atts['dir'] = 'ltr';

				$html = self::html( 'input', $atts );

				echo self::html( 'div', array(
					'class' => 'field-wrap field-wrap-inputtext',
				), $html );
		}
	}

	public static function meta_admin_textarea_field( $field, $fields, $post, $ltr = false, $title = null, $key = false )
	{
		global $gEditorial;

		if ( in_array( $field, $fields )
			&& $gEditorial->meta->user_can( 'view', $field )  ) {

				if ( is_null( $title ) )
					$title = $gEditorial->meta->get_string( $field, $post->post_type );

				$atts = array(
					// 'rows'         => '5',
					// 'cols'         => '40',
					'class'        => 'field-textarea geditorial-meta-field-'.$field,
					'name'         => 'geditorial-meta-'.$field.( $key === false ? '' : '['.$key.']' ),
					'id'           => 'geditorial-meta-'.$field.( $key === false ? '' : '-'.$key ),
					'title'        => $title,
					'placeholder'  => $title,
					'readonly'     => ! $gEditorial->meta->user_can( 'edit', $field ),
				);

				if ( $ltr )
					$atts['dir'] = 'ltr';

				$html = self::html( 'textarea', $atts, esc_textarea( $gEditorial->meta->get_postmeta( $post->ID, $field ) ) );

				echo self::html( 'div', array(
					'class' => 'field-wrap field-wrap-textarea',
				), $html );
		}
	}

	// for meta fields before and after post title
	public static function meta_admin_title_field( $field, $fields, $post, $ltr = false, $title = null, $key = false )
	{
		global $gEditorial;

		if ( in_array( $field, $fields )
			&& $gEditorial->meta->user_can( 'view', $field )  ) {

				if ( is_null( $title ) )
					$title = $gEditorial->meta->get_string( $field, $post->post_type );

				$atts = array(
					'type'         => 'text',
					'autocomplete' => 'off',
					'class'        => 'field-text geditorial-meta-field-'.$field,
					'name'         => 'geditorial-meta-'.$field.( $key === false ? '' : '['.$key.']' ),
					'id'           => 'geditorial-meta-'.$field.( $key === false ? '' : '-'.$key ),
					'value'        => $gEditorial->meta->get_postmeta( $post->ID, $field ),
					'title'        => $title,
					'placeholder'  => $title,
					'readonly'     => ! $gEditorial->meta->user_can( 'edit', $field ),
				);

				if ( $ltr )
					$atts['dir'] = 'ltr';

				echo self::html( 'input', $atts );
		}
	}

	public static function meta_admin_text_field( $field, $fields, $post, $ltr = false, $title = null, $key = false )
	{
		global $gEditorial;

		if ( in_array( $field, $fields )
			&& $gEditorial->meta->user_can( 'view', $field )  ) {

				if ( is_null( $title ) )
					$title = $gEditorial->meta->get_string( $field, $post->post_type );

				$html  = '<div id="geditorial-meta-'.$field.'-wrap" class="postbox geditorial-meta-postbox geditorial-meta-field-'.$field.'">';
				$html .= '<div class="handlediv" title="'.esc_attr__( 'Click to toggle' ).'"><br></div><h3 class="hndle"><span>'.$title.'</span></h3>';
				$html .= '<div class="inside"><label class="screen-reader-text" for="geditorial-meta-'.$field.'">'.$title.'</label>';
				$html .= self::html( 'textarea', array(
					'rows'     => '1',
					'cols'     => '40',
					'name'     => 'geditorial-meta-'.$field.( $key === false ? '' : '['.$key.']' ),
					'id'       => 'geditorial-meta-'.$field.( $key === false ? '' : '-'.$key ),
					'class'    => 'textarea-autosize geditorial-meta-field-'.$field,
					'readonly' => ! $gEditorial->meta->user_can( 'edit', $field ),
				), esc_textarea( $gEditorial->meta->get_postmeta( $post->ID, $field ) ) );
				$html .= '</div></div>';

				echo $html;
		}
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

			//printf( 'Types: %s', join( __( ', ' ), $out ) );
			echo $before.join( __( ', ' ), $out ).$after;
		}
	}

	// Originally From : http://wp.tutsplus.com/tutorials/creative-coding/add-a-custom-column-in-posts-and-custom-post-types-admin-screen/
	public static function get_featured_image_src( $post_id, $size = 'thumbnail', $default = false )
	{
		$post_thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( ! $post_thumbnail_id )
			return $default;

		$post_thumbnail_img = wp_get_attachment_image_src( $post_thumbnail_id, $size );
		return $post_thumbnail_img[0];
	}

	// http://bavotasan.com/2012/trim-characters-using-php/
	public static function trim_characters( $text, $length = 45, $append = '&hellip;' ) {

		$length = (int) $length;
		$text = trim( strip_tags( $text ) );

		if ( strlen( $text ) > $length ) {
			$text = substr( $text, 0, $length + 1 );
			$words = preg_split( "/[\s]|&nbsp;/", $text, -1, PREG_SPLIT_NO_EMPTY );
			preg_match( "/[\s]|&nbsp;/", $text, $lastchar, 0, $length );
			if ( empty( $lastchar ) )
				array_pop( $words );

			$text = implode( ' ', $words ) . $append;
		}

		return $text;
	}

	// DEPRECATED use : gEditorialHelper::isDev()
	public static function is_dev()
	{
		if ( defined( 'GTHEME_DEV_ENVIRONMENT' ) && constant( 'GTHEME_DEV_ENVIRONMENT' ) )
			return true;

		if ( defined( 'WP_STAGE' ) && 'development' == constant( 'WP_STAGE' ) )
			return true;

		// TODO : check stage production and debug constant then true

		return false;
	}

	// debug on developmnet env
	public static function isDev()
	{
		if ( defined( 'WP_STAGE' )
			&& 'development' == constant( 'WP_STAGE' ) )
				return true;

		return false;
	}

	// DEPRECATED use : gEditorialHelper::isDebug()
	public static function is_debug()
	{
		if ( WP_DEBUG && WP_DEBUG_DISPLAY && ! self::is_dev() )
			return true;

		return false;
	}

	// debug on production env
	public static function isDebug()
	{
		if ( WP_DEBUG && WP_DEBUG_DISPLAY && ! self::isDev() )
			return true;

		return false;
	}

	public static function notice( $notice, $class = 'updated fade', $echo = true )
	{
		$html = sprintf( '<div id="message" class="%s"><p>%s</p></div>', $class, $notice );

		if ( ! $echo )
			return $html;

		echo $html;
	}

	// checks for the current post type
	public static function get_current_post_type()
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

		return null;
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

	public static function register_colorbox()
	{
		wp_register_style( 'jquery-colorbox', GEDITORIAL_URL.'assets/css/admin.colorbox.css', array(), '1.6.1', 'screen' );
		wp_register_script( 'jquery-colorbox', GEDITORIAL_URL.'assets/packages/jquery-colorbox/jquery.colorbox-min.js', array( 'jquery'), '1.6.1', true );
	}

	public static function enqueue_colorbox()
	{
		wp_enqueue_style( 'jquery-colorbox' );
		wp_enqueue_script( 'jquery-colorbox' );
	}

	// fall back for meta_term
	public static function get_post_id_by_slug( $slug, $post_type )
	{
		global $wpdb;

		$slug = rawurlencode( urldecode( $slug ) );
		$slug = sanitize_title( basename( $slug ) );

		$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = %s", $slug, $post_type ) );

		if ( is_array( $post_id ) )
			return $post_id[0];
		elseif ( !empty( $post_id ) )
			return $post_id;

		return false;
	}

	public static function the_term( $taxonomy, $post_ID, $object = false )
	{
		$terms = get_the_terms( $post_ID, $taxonomy );
		if ( $terms && ! is_wp_error( $terms ) )
			foreach ( $terms as $term )
				if ( $object )
					return $term;
				else
					return $term->term_id;
		return '0';
	}

	public static function getTerms( $taxonomy = 'category', $post_id = false, $object = false, $key = 'term_id' )
	{
		$the_terms = array();

		if ( false === $post_id ) {
			$terms = get_terms( $taxonomy, array(
				'hide_empty' => false,
				'orderby' => 'name',
				'order' => 'ASC'
			) );
		} else {
			$terms = get_the_terms( $post_id, $taxonomy );
		}

		if ( is_wp_error( $terms ) || false === $terms )
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
			'orderby'        => 'date',
			'order'          => 'DESC',
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
			return false;

		$output = '<div class="field-wrap field-wrap-list"><h4>';
		$output .= sprintf( __( 'Other Posts on <a href="%1$s" target="_blank">%2$s</a>:', GEDITORIAL_TEXTDOMAIN ),
			get_term_link( $term, $term->taxonomy ),
			sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' )
		).'</h4><ol>';

		foreach( $the_posts as $post ) {
			setup_postdata( $post );

			$url = add_query_arg( array(
				'action' => 'edit',
				'post'   => $post->ID,
			), get_admin_url( null, 'post.php' ) );

			$output .= '<li><a href="'.get_permalink( $post->ID ).'">'
					.get_the_title( $post->ID ).'</a>'
					.'&nbsp;<span class="edit">'
					.sprintf( __( '&ndash; <a href="%1$s" target="_blank" title="Edit this post">%2$s</a>', GEDITORIAL_TEXTDOMAIN ),
						esc_url( $url ),
						'<span class="dashicons dashicons-welcome-write-blog"></span>'
					).'</span></li>';
		}
		wp_reset_query();
		$output .= '</ol></div>';

		return $output;
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

		$this->_import = true;
		return wp_insert_post( $new_post );
	}

	public static function table( $columns, $data = array() )
	{
		if ( ! count( $columns ) )
			return false;

		$keys = array();
		echo '<table class="widefat helper-table" width="100%;"><thead><tr>';
		foreach ( $columns as $key => $column ) {
			echo '<th class="helper-column-'.esc_attr( $key ).'">'.$column.'</th>';
			$keys[] = $key;
		}
		echo '</tr></thead>';

		$alt = true;
		foreach ( $data as $index => $row ) {
			echo '<tr class="helper-column-row-'.$index.( $alt ? ' alternate' : '' ).'">';
			foreach ( $columns as $key => $column ) {
				if ( is_array( $row ) && isset( $row[$key] ) ) {
					echo '<td class="helper-column-row-cell-'.$key.'">'.$row[$key].'</td>';
				} else if ( is_object( $row ) && isset( $row->{$key} ) ) {
					echo '<td class="helper-column-row-cell-'.$key.'">'.$row->{$key}.'</td>';
				} else {
					echo '<td class="helper-column-row-cell-empty">&nbsp;</td>';
				}
			}

			echo '</tr>';
			//$alt = $alt ? false : true;
			$alt = ! $alt;
		}
		echo '</table>';
	}

	// from : Custom Field Taxonomies : https://github.com/scribu/wp-custom-field-taxonomies
	public static function getDBPostMetaRows( $meta_key, $limit = false )
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
	public static function getDBPostMetaKeys( $rekey = false )
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
	public static function deleteDBPostMeta( $meta_key, $limit = false )
	{
		global $wpdb;

		if ( $limit )
			$query = $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s LIMIT %d", $meta_key, $limit );
		else
			$query = $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s", $meta_key );

		return $wpdb->query( $query );
	}

	public static function insertDefaultTerms( $taxonomy, $defaults )
	{
		if ( ! taxonomy_exists( $taxonomy ) )
			return false;

		foreach ( $defaults as $term_slug => $term_name )
			if ( ! term_exists( $term_slug, $taxonomy ) )
				wp_insert_term( $term_name, $taxonomy, array( 'slug' => $term_slug ) );

		return true;
	}

	const SETTINGS_SLUG = 'geditorial-settings';
	const TOOLS_SLUG    = 'geditorial-tools';

	public static function settingsURL( $full = true )
	{
		//$relative = current_user_can( 'manage_options' ) ? 'admin.php?page='.self::SETTINGS_SLUG : 'index.php?page='.self::SETTINGS_SLUG;
		$relative = 'admin.php?page='.self::SETTINGS_SLUG;

		if ( $full )
			return get_admin_url( null, $relative );

		return $relative;
	}

	public static function toolsURL( $full = true )
	{
		//$relative = current_user_can( 'manage_options' ) ? 'admin.php?page='.self::TOOLS_SLUG : 'index.php?page='.self::TOOLS_SLUG;
		$relative = 'admin.php?page='.self::TOOLS_SLUG;

		if ( $full )
			return get_admin_url( null, $relative );

		return $relative;
	}

	public static function isSettings( $screen = null )
	{
		if ( is_null( $screen) )
			$screen = get_current_screen();

		if ( isset( $screen->base ) && false !== strripos( $screen->base, self::SETTINGS_SLUG ) )
			return true;

		return false;
	}

	public static function isTools( $screen = null )
	{
		if ( is_null( $screen) )
			$screen = get_current_screen();

		if ( isset( $screen->base ) && false !== strripos( $screen->base, self::TOOLS_SLUG ) )
			return true;

		return false;
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( 'geditorial_tinymce_strings', array() );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.geditorial", '.wp_json_encode( $strings ).');'."\n" : '';
	}

	public static function printJSConfig( $args, $object = 'gEditorial' )
	{
		$args['api'] = defined( 'GNETWORK_AJAX_ENDPOINT' ) && GNETWORK_AJAX_ENDPOINT ? GNETWORK_AJAX_ENDPOINT : admin_url( 'admin-ajax.php' );

	?> <script type="text/javascript">
/* <![CDATA[ */
	var <?php echo $object; ?> = <?php echo wp_json_encode( $args ); ?>;

	<?php if ( gEditorialHelper::isDev() ) echo 'console.log('.$object.');'; ?>

/* ]]> */
</script> <?php
	}
}
