<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialModified extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'  => 'modified',
			'title' => _x( 'Modified', 'Modules: Modified', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Last modifications to the site', 'Modules: Modified', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'update',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'posttypes_option' => 'posttypes_option',
			'_dashboard' => array(
				'dashboard_widgets',
				array(
					'field'       => 'dashboard_authors',
					'title'       => _x( 'Dashboard Authors', 'Modules: Modified: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays authors column on dashboard widget', 'Modules: Modified: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'dashboard_count',
					'title'       => _x( 'Dashboard Authors', 'Modules: Modified: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays authors column on dashboard widget', 'Modules: Modified: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'number',
					'default'     => 10,
				),

			),
			'_content' => array(
				'insert_content',
				array(
					'field'       => 'insert_prefix',
					'type'        => 'text',
					'title'       => _x( 'Content Prefix', 'Modules: Modified: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'String before the modified time on the content', 'Modules: Modified: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => _x( 'Last modified on', 'Modules: Modified: Setting Default', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'insert_format',
					'type'        => 'text',
					'title'       => _x( 'Insert Format', 'Modules: Modified: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays last modified in this format on the content', 'Modules: Modified: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => get_option( 'date_format' ), // TODO: add new setting type to select format
				),
				'insert_priority',
				array(
					'field'       => 'display_after',
					'type'        => 'select',
					'title'       => _x( 'Display After', 'Modules: Modified: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Skip displaying modified time since original content published', 'Modules: Modified: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '60',
					'values'      => gEditorialSettingsCore::minutesOptions(),
				),
			),
		);
	}

	public function init()
	{
		do_action( 'geditorial_modified_init', $this->module );

		$this->do_globals();

		if ( is_blog_admin() && $this->get_setting( 'dashboard_widgets', FALSE ) )
			$this->action( 'wp_dashboard_setup' );

		if ( ! is_admin() && count( $this->post_types() ) ) {

			$insert = $this->get_setting( 'insert_content', 'none' );

			if ( 'none' != $insert ) {

				add_action( 'gnetwork_themes_content_'.$insert, array( $this, 'insert_content' ),
					$this->get_setting( 'insert_priority', 30 ) );

				$this->enqueue_styles();
			}

			$this->filter( 'wp_nav_menu_items', 2 );
		}
	}

	public function wp_dashboard_setup()
	{
		wp_add_dashboard_widget( 'geditorial-modified-latests',
			_x( 'Latest Changes', 'Modules: Modified: Dashboard Widget Title', GEDITORIAL_TEXTDOMAIN ),
			array( $this, 'dashboard_latests' )
		);
	}

	public function dashboard_latests()
	{
		$args = array(
			'orderby'     => 'modified',
			'post_type'   => $this->post_types(),
			'post_status' => array( 'publish', 'future', 'draft', 'pending' ),

			'posts_per_page'      => $this->get_setting( 'dashboard_count', 10 ),
			'ignore_sticky_posts' => TRUE,
			'suppress_filters'    => TRUE,
			'no_found_rows'       => TRUE,

			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		);

		$query = new \WP_Query;

		$columns = array(
			'modified'   => array(
				'title' => _x( 'On', 'Modules: Modified', GEDITORIAL_TEXTDOMAIN ),
				'callback' => function( $value, $row, $column, $index ){
					return '<small class="-date-diff" title="'
						.esc_attr( mysql2date( 'l, M j, Y @ H:i', $row->post_modified ) ).'">'
						.gEditorialHelper::humanTimeDiff( $row->post_modified )
					.'</small>';
				},
			),
		);

		if ( $this->get_setting( 'dashboard_authors', FALSE ) )
			$columns['author'] = array(
				'title'    => _x( 'Author', 'Modules: Modified', GEDITORIAL_TEXTDOMAIN ),
				'callback' => function( $value, $row, $column, $index ){

					if ( current_user_can( 'edit_post', $row->ID ) )
						return gEditorialWordPress::getAuthorEditHTML( $row->post_type, $row->post_author );

					if ( $author_data = get_user_by( 'id', $row->post_author ) )
						return esc_html( $author_data->display_name );

					return '<span class="-empty">&mdash;</span>';
				},
			);

		$columns['title'] = array(
			'title'    => _x( 'Title', 'Modules: Modified', GEDITORIAL_TEXTDOMAIN ),
			'callback' => function( $value, $row, $column, $index ){
				return gEditorialHelper::getPostTitleRow( $row, 'edit' );
			},
		);

		gEditorialHTML::tableList( $columns, $query->query( $args ), array(
			'empty' => _x( 'No Posts?!', 'Modules: Modified', GEDITORIAL_TEXTDOMAIN ),
		) );
	}

	public function insert_content( $content )
	{
		if ( is_singular() && in_the_loop() && is_main_query() ){

			if ( $modified = $this->get_post_modified() )
				echo '<div class="geditorial-wrap -modified -content-'
					.$this->get_setting( 'insert_content', 'none' ).'"><small>'.$modified.'</small></div>';
		}
	}

	public function post_modified( $format = NULL, $posttypes = NULL )
	{
		// TODO: add last modified shortcode
		// `Posted on 22nd May 2014 This post was last updated on 23rd April 2016`
	}

	public function get_post_modified( $format = NULL, $post = NULL )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		$gmt     = strtotime( $post->post_modified_gmt );
		$local   = strtotime( $post->post_modified );
		$publish = strtotime( $post->post_date_gmt );

		if ( is_null( $format ) )
			$format = $this->get_setting( 'insert_format', get_option( 'date_format' ) );

		$minutes = $this->get_setting( 'display_after', '60' );
		$prefix  = $this->get_setting( 'insert_prefix', '' );

		if ( $gmt >= $publish + ( absint( $minutes ) * MINUTE_IN_SECONDS ) )
			return $prefix.' '.gEditorialDate::htmlDateTime( $local, $gmt, $format,
					gEditorialHelper::humanTimeDiffRound( $local, FALSE ) );

		return FALSE;
	}

	// just put {SITE_LAST_MODIFIED} on a menu item text!
	public function wp_nav_menu_items( $items, $args )
	{
		if ( ! gEditorialCoreText::has( $items, '{SITE_LAST_MODIFIED}' ) )
			return $items;

		if ( ! isset( $this->site_modified ) )
			$this->site_modified = $this->get_site_modified();

		return preg_replace( '%{SITE_LAST_MODIFIED}%', $this->site_modified, $items );
	}

	public function site_modified( $format = NULL, $posttypes = NULL )
	{
		// TODO: add last modified shortcode
		// This website was last updated:
	}

	public function get_site_modified( $format = NULL, $posttypes = NULL )
	{
		global $wpdb;

		if ( is_null( $format ) )
			$format = get_option( 'date_format' );

		if ( is_null( $posttypes ) )
			$posttypes = $this->post_types();

		else if ( FALSE === $posttypes )
			$posttypes = array();

		else if ( ! is_array( $posttypes ) )
			$posttypes = array( $posttypes );

		if ( count( $posttypes ) ) {

			$post_types_in = implode( ',', array_map( function( $v ){
				return "'".esc_sql( $v )."'";
			}, $posttypes ) );

			$query = "
				SELECT post_modified
				FROM {$wpdb->posts}
				WHERE post_status = 'publish'
				AND post_type IN ( {$post_types_in} )
				ORDER BY post_modified DESC
			";

		} else {

			$query = "
				SELECT post_modified
				FROM {$wpdb->posts}
				WHERE post_status = 'publish'
				ORDER BY post_modified DESC
			";
		}

		$modified = $wpdb->get_var( $query );

		if ( FALSE === $format )
			return $modified;

		return date_i18n( $format, strtotime( $modified ), FALSE );
	}
}
