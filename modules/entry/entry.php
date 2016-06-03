<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialEntry extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'     => 'entry',
			'title'    => _x( 'Entry', 'Entry Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Wiki-like Posts Entries', 'Entry Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon' => 'media-document',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'shortcode_support',
				'admin_ordering',
				'editor_button',
				'comment_status',
				// 'rewrite_prefix', // FIXME: working but needs prem link rewrites
				'before_content',
				'after_content',
			),
			'posttypes_option' => 'posttypes_option',
			'fields_option'    => 'fields_option',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'entry_cpt'         => 'entry',
			'entry_cpt_archive' => 'entries',
			'rewrite_prefix'    => 'entry', // wiki
			'section_tax'       => 'entry_section',
			'section_tax_slug'  => 'entry-section',
			'section_shortcode' => 'entry-section',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'featured'             => _x( 'Cover Image', 'Entry Module: Entry CPT: Featured', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_title'       => _x( 'Entry', 'Entry Module: Meta Box Title', GEDITORIAL_TEXTDOMAIN ),
				'section_column_title' => _x( 'Section', 'Entry Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'order_column_title'   => _x( 'O', 'Entry Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
			),
			'noops' => array(
				'entry_cpt'   => _nx_noop( 'Entry', 'Entries', 'Entry Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'section_tax' => _nx_noop( 'Section', 'Sections', 'Entry Module: Noop', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	protected function get_global_supports()
	{
		return array(
			'entry_cpt' => array(
				'title',
				'editor',
				'excerpt',
				'author',
				'thumbnail',
				// 'trackbacks',
				// 'custom-fields',
				'comments',
				'revisions',
				// 'page-attributes',
			),
		);
	}

	public function setup( $partials = array() )
	{
		parent::setup( array(
			'templates',
			'helper',
		) );
	}

	public function meta_post_types( $post_types )
	{
		return array_merge( $post_types, array( $this->constant( 'entry_cpt' ) ) );
	}

	public function gpeople_support( $post_types )
	{
		return array_merge( $post_types, array( $this->constant( 'entry_cpt' ) ) );
	}

	public function init()
	{
		do_action( 'geditorial_entry_init', $this->module );

		$this->do_globals();

		$this->post_types_excluded = array( $this->constant( 'entry_cpt' ) );

		$this->register_post_type( 'entry_cpt', array(), array( 'post_tag' ) );
		$this->register_taxonomy( 'section_tax', array(
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
		), 'entry_cpt' );

		// add_action( 'generate_rewrite_rules', array( $this, 'generate_rewrite_rules' ) );

		if ( is_admin() ) {

		} else {

			if ( $this->get_setting( 'before_content', FALSE ) )
				add_action( 'gnetwork_themes_content_before', array( $this, 'content_before' ), 100 );

			if ( $this->get_setting( 'after_content', FALSE ) )
				add_action( 'gnetwork_themes_content_after', array( $this, 'content_after' ), 1 );
		}

		$this->register_shortcode( 'section_shortcode', array( 'gEditorialEntryTemplates', 'section_shortcode' ) );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'entry_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
				add_filter( 'get_default_comment_status', array( $this, 'get_default_comment_status' ), 10, 3 );

				// FIXME: default will be true / DROP THIS
				add_filter( 'geditorial_meta_box_callback', '__return_true', 12 );
				add_filter( 'geditorial_meta_dbx_callback', '__return_true', 12 );

			} else if ( 'edit' == $screen->base ) {

				add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
				add_filter( 'parse_query', array( $this, 'parse_query' ) );

				if ( $this->get_setting( 'admin_ordering', TRUE ) )
					add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

				add_filter( 'manage_'.$screen->post_type.'_posts_columns', array( $this, 'manage_posts_columns' ) );
				add_filter( 'manage_edit-'.$screen->post_type.'_sortable_columns', array( $this, 'sortable_columns' ) );
				add_action( 'manage_'.$screen->post_type.'_posts_custom_column', array( $this, 'posts_custom_column'), 10, 2 );
			}
		}
	}

	public function restrict_manage_posts()
	{
		$this->do_restrict_manage_posts_taxes( array(
			'section_tax',
		), 'entry_cpt' );
	}

	public function pre_get_posts( $wp_query )
	{
		if ( $wp_query->is_admin
			&& isset( $wp_query->query['post_type'] ) ) {

			if ( $this->constant( 'entry_cpt' ) == $wp_query->query['post_type'] ) {
				if ( ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', 'menu_order' );
				if ( ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', 'DESC' );
			}
		}
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query->query_vars, array(
			'section_tax',
		), 'entry_cpt' );
	}

	public function manage_posts_columns( $posts_columns )
	{
		$new_columns = array();

		$section = $this->constant( 'section_tax' );

		foreach ( $posts_columns as $key => $value ) {

			if ( 'title' == $key || 'geditorial-tweaks-title' == $key ) {

				$new_columns['taxonomy-'.$section] = $this->get_column_title( 'section', 'entry_cpt' );
				$new_columns['order'] = $this->get_column_title( 'order', 'entry_cpt' );
				$new_columns[$key] = $value;

			} else if ( in_array( $key, array( 'author', 'taxonomy-'.$section ) ) ) {
				continue; // he he!

			} else {
				$new_columns[$key] = $value;
			}
		}
		return $new_columns;
	}

	public function sortable_columns( $columns )
	{
		$columns['order'] = 'menu_order';
		return $columns;
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( 'order' == $column_name )
			$this->column_count( get_post( $post_id )->menu_order );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'entry_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function post_updated_messages( $messages )
	{
		$messages[$this->constant( 'entry_cpt' )] = $this->get_post_updated_messages( 'entry_cpt' );
		return $messages;
	}

	public function generate_rewrite_rules( $wp_rewrite )
	{
		$prefix = $this->get_setting( 'rewrite_prefix', FALSE );

		if ( ! $prefix )
			$prefix = $this->constant( 'rewrite_prefix' );

		$new_rules = array(
			$prefix.'/(.*)/(.*)' => 'index.php'
				.'?post_type='.$this->constant( 'entry_cpt' )
				.'&'.$this->constant( 'section_tax' ).'='.$wp_rewrite->preg_index( 1 )
				.'&'.$this->constant( 'entry_cpt' ).'='.$wp_rewrite->preg_index( 2 ),
		);

		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
	}

	public function content_before( $content, $posttypes = NULL )
	{
		parent::content_before( $content, array( $this->constant( 'entry_cpt' ) ) );
	}

	public function content_after( $content, $posttypes = NULL )
	{
		parent::content_after( $content, array( $this->constant( 'entry_cpt' ) ) );
	}
}
