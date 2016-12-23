<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialEntry extends gEditorialModuleCore
{

	protected $partials = array( 'templates', 'helper' );

	private $terms = NULL;

	public static function module()
	{
		return array(
			'name'  => 'entry',
			'title' => _x( 'Entry', 'Entry Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Wiki-like Posts Entries', 'Entry Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'media-document',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'shortcode_support',
				'admin_ordering',
				'admin_restrict',
				'editor_button',
				'comment_status',
				'autolink_terms',
				// 'rewrite_prefix', // FIXME: working but needs prem link rewrites
				'before_content',
				'after_content',
			),
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
				'comments',
				'revisions',
				'date-picker', // gPersianDate
			),
		);
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
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'show_in_nav_menus'  => TRUE,
			'meta_box_cb'        => NULL, // default meta box
		), 'entry_cpt' );

		// add_action( 'generate_rewrite_rules', array( $this, 'generate_rewrite_rules' ) );

		if ( is_admin() ) {

		} else {

			if ( $this->get_setting( 'before_content', FALSE ) )
				add_action( 'gnetwork_themes_content_before', array( $this, 'content_before' ), 100 );

			if ( $this->get_setting( 'after_content', FALSE ) )
				add_action( 'gnetwork_themes_content_after', array( $this, 'content_after' ), 1 );

			if ( $this->get_setting( 'autolink_terms', FALSE ) )
				add_filter( 'the_content', array( $this, 'the_content' ), 9 );
		}

		$this->register_shortcode( 'section_shortcode', array( 'gEditorialEntryTemplates', 'section_shortcode' ) );
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, 'entry_cpt' ) )
			$this->_edit_screen( $_REQUEST['post_type'] );
	}

	public function current_screen( $screen )
	{
		if ( 'dashboard' == $screen->base ) {

			add_filter( 'dashboard_recent_drafts_query_args', array( $this, 'dashboard_recent_drafts_query_args' ) );

		} else if ( $screen->post_type == $this->constant( 'entry_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
				add_filter( 'get_default_comment_status', array( $this, 'get_default_comment_status' ), 10, 3 );

			} else if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_restrict', FALSE ) ) {
					add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ), 12, 2 );
					add_filter( 'parse_query', array( $this, 'parse_query' ) );
				}

				if ( $this->get_setting( 'admin_ordering', TRUE ) )
					add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

				$this->_edit_screen( $screen->post_type );
				add_filter( 'manage_edit-'.$screen->post_type.'_sortable_columns', array( $this, 'sortable_columns' ) );
			}
		}
	}

	private function _edit_screen( $post_type )
	{
		add_filter( 'manage_'.$post_type.'_posts_columns', array( $this, 'manage_posts_columns' ) );
		add_action( 'manage_'.$post_type.'_posts_custom_column', array( $this, 'posts_custom_column'), 10, 2 );
	}

	public function dashboard_recent_drafts_query_args( $query_args )
	{
		if ( 'post' == $query_args['post_type'] )
			$query_args['post_type'] = array( 'post', $this->constant( 'entry_cpt' ) );

		else if ( is_array( $query_args['post_type'] ) )
			$query_args['post_type'][] = $this->constant( 'entry_cpt' );

		return $query_args;
	}

	public function restrict_manage_posts( $post_type, $which )
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

			if ( 'title' == $key ) {

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
		return array_merge( $columns, array( 'order' => 'menu_order' ) );
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
		return array_merge( $messages, array(
			$this->constant( 'entry_cpt' ) => $this->get_post_updated_messages( 'entry_cpt' ),
		) );
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
		if ( is_singular( $this->constant( 'entry_cpt' ) )
			&& in_the_loop() && is_main_query() )
				parent::content_before( $content, FALSE );
	}

	public function content_after( $content, $posttypes = NULL )
	{
		if ( is_singular( $this->constant( 'entry_cpt' ) )
			&& in_the_loop() && is_main_query() )
				parent::content_after( $content, FALSE );
	}

	public function the_content( $content )
	{
		if ( is_singular( $this->constant( 'entry_cpt' ) )
			&& in_the_loop()
			&& is_main_query() ) {

			if ( is_null( $this->terms ) )
				$this->terms = gEditorialWPTaxonomy::prepTerms( $this->constant( 'section_tax' ) );

			foreach ( $this->terms as $term )
				$content = preg_replace(
					"|(?!<[^<>]*?)(?<![?./&])\b($term->name)\b(?!:)(?![^<>]*?>)|imsU",
					"<a href=\"$term->link\" class=\"-entry-section\">$1</a>",
				$content );
		}

		return $content;
	}
}
