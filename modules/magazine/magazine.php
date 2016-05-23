<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMagazine extends gEditorialModuleCore
{

	public $meta_key     = '_ge_magazine';
	protected $root_key  = 'GEDITORIAL_MAGAZINE_ROOT_BLOG';
	protected $tools_cap = 'edit_others_posts';

	public static function module()
	{
		return array(
			'name'      => 'magazine',
			'title'     => _x( 'Magazine', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'      => _x( 'Issue Management for Magazines', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'  => 'book',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'multiple_instances',
				array(
					'field'       => 'issue_sections',
					'title'       => _x( 'Issue Sections', 'Magazine Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Section taxonomy for issue and supported post types', 'Magazine Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '0',
				),
				'posttype_feeds',
				'posttype_pages',
				'redirect_archives',
				array(
					'field'       => 'redirect_spans',
					'type'        => 'text',
					'title'       => _x( 'Redirect Spans', 'Magazine Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Redirect all Span Archives to a URL', 'Magazine Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '',
					'dir'         => 'ltr',
				),
			),
			'posttypes_option' => 'posttypes_option',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'issue_cpt'           => 'issue',
			'issue_cpt_archive'   => 'issues',
			'issue_cpt_permalink' => '/%postname%',
			'issue_cpt_p2p'       => 'related_issues',
			'issue_tax'           => 'issues',
			'span_tax'            => 'issue_span',
			'span_tax_slug'       => 'issue-span',
			'section_tax'         => 'issue_section',
			'section_tax_slug'    => 'issue-section',
			'issue_shortcode'     => 'issue',
			'span_shortcode'      => 'issue-span',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'titles' => array(
				'issue_cpt' => array(
					'issue_number_line' => _x( 'Number Line', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
					'issue_total_pages' => _x( 'Total Pages', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
				),
				'post_cpt' => array(
					'in_issue_order'      => _x( 'Order', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
					'in_issue_page_start' => _x( 'Page Start', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
					'in_issue_pages'      => _x( 'Total Pages', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'descriptions' => array(
				'issue_cpt' => array(
					'issue_number_line' => _x( 'The issue number line', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
					'issue_total_pages' => _x( 'The issue total pages', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
				),
				'post_cpt' => array(
					'in_issue_order'      => _x( 'Post order in issue list', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
					'in_issue_page_start' => _x( 'Post start page on issue (printed)', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
					'in_issue_pages'      => _x( 'Post total pages on issue (printed)', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'misc' => array(
				'issue_cpt' => array(
					'featured'       => _x( 'Cover Image', 'Magazine Module: Issue CPT: Featured', GEDITORIAL_TEXTDOMAIN ),
					'meta_box_title' => _x( 'Metadata',    'Magazine Module: Issue CPT: Meta Box Title', GEDITORIAL_TEXTDOMAIN ),

					'cover_column_title'    => _x( 'Cover', 'Magazine Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'order_column_title'    => _x( 'O', 'Magazine Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'children_column_title' => _x( 'Posts', 'Magazine Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'meta_box_title' => _x( 'Issues', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
			),
			'noops' => array(
				'issue_cpt'   => _nx_noop( 'Issue',   'Issues',   'Magazine Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'issue_tax'   => _nx_noop( 'Issue',   'Issues',   'Magazine Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'span_tax'    => _nx_noop( 'Span',    'Spans',    'Magazine Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'section_tax' => _nx_noop( 'Section', 'Sections', 'Magazine Module: Noop', GEDITORIAL_TEXTDOMAIN ),
			),
			'p2p' => array(
				'issue_cpt' => array(
					'title' => array(
						'from' => _x( 'Connected Issues', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'to'   => _x( 'Connected Posts', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN )
					),
					'from_labels' => array(
						'singular_name' => _x( 'Post', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'search_items'  => _x( 'Search posts', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'not_found'     => _x( 'No posts found.', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'create'        => _x( 'Connect to a post', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
					),
					'to_labels' => array(
						'singular_name' => _x( 'Issue', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'search_items'  => _x( 'Search issues', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'not_found'     => _x( 'No issues found.', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'create'        => _x( 'Connect to an issue', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
		);
	}

	protected function get_global_supports()
	{
		return array(
			'issue_cpt' => array(
				'title',
				'editor',
				'excerpt',
				'author',
				'thumbnail',
				// 'trackbacks',
				// 'custom-fields',
				'comments',
				'revisions',
				'page-attributes',
			),
		);
	}

	protected function get_global_fields()
	{
		return array(
			$this->constant( 'issue_cpt' ) => array(
				'ot'                => FALSE,
				'st'                => TRUE,
				'issue_number_line' => TRUE,
				'issue_total_pages' => TRUE,
			 ),
			$this->constant( 'post_cpt' ) => array(
				'in_issue_order'      => TRUE,
				'in_issue_page_start' => TRUE,
				'in_issue_pages'      => FALSE,
			),
		);
	}

	public function setup( $partials = array() )
	{
		parent::setup( array(
			'templates',
		) );

		if ( is_admin() ) {

			add_filter( 'disable_months_dropdown', array( $this, 'disable_months_dropdown' ), 8, 2 );
			add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
			add_filter( 'parse_query', array( $this, 'parse_query' ) );

		} else {
			add_filter( 'term_link', array( $this, 'term_link' ), 10, 3 );
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );

			// add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 36 );
		}

		add_action( 'split_shared_term', array( $this, 'split_shared_term' ), 10, 4 );
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'issue_cpt' );
	}

	public function p2p_init()
	{
		$this->register_p2p( 'issue_cpt' );
	}

	public function init()
	{
		do_action( 'geditorial_magazine_init', $this->module );

		$this->do_globals();

		$this->post_types_excluded = array( $this->constant( 'issue_cpt' ) );

		$this->register_post_type( 'issue_cpt', array(
			'hierarchical' => TRUE,
			'rewrite'      => array(
				'feeds' => (bool) $this->get_setting( 'posttype_feeds', FALSE ),
				'pages' => (bool) $this->get_setting( 'posttype_pages', FALSE ),
			),
		), array( 'post_tag' ) );

		$this->register_taxonomy( 'issue_tax', array(
			'show_ui'           => FALSE, // self::isDev(),
			'hierarchical'      => TRUE,
			'show_admin_column' => TRUE,
		) );

		$this->register_taxonomy( 'span_tax', array(
			'show_admin_column' => TRUE,
		), 'issue_cpt' );

		if ( $this->get_setting( 'issue_sections', FALSE ) )
			$this->register_taxonomy( 'section_tax', array(
				'hierarchical' => TRUE,
			), $this->post_types( 'issue_cpt' ) );

		$this->register_shortcode( 'issue_shortcode', array( 'gEditorialMagazineTemplates', 'issue_shortcode' ) );
		$this->register_shortcode( 'span_shortcode', array( 'gEditorialMagazineTemplates', 'span_shortcode' ) );
	}

	public function admin_init()
	{
		$issue_cpt = $this->constant( 'issue_cpt' );

		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );

		add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 9, 2 );
		add_action( 'save_post_'.$issue_cpt, array( $this, 'save_post_main_cpt' ), 20, 3 );
		add_action( 'post_updated', array( $this, 'post_updated' ), 20, 3 );
		add_action( 'save_post', array( $this, 'save_post_supported_cpt' ), 20, 3 );
		add_action( 'wp_trash_post', array( $this, 'wp_trash_post' ) );
		add_action( 'untrash_post', array( $this, 'untrash_post' ) );
		add_action( 'before_delete_post', array( $this, 'before_delete_post' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 20, 2 );
		add_filter( 'manage_'.$issue_cpt.'_posts_columns', array( $this, 'manage_posts_columns' ) );
		add_filter( 'manage_'.$issue_cpt.'_posts_custom_column', array( $this, 'posts_custom_column'), 10, 2 );
		add_filter( 'manage_edit-'.$issue_cpt.'_sortable_columns', array( $this, 'sortable_columns' ) );

		// internal actions:
		add_action( 'geditorial_magazine_supported_meta_box', array( $this, 'supported_meta_box' ), 5, 2 );
	}

	public function widgets_init()
	{
		$this->require_code( 'widgets' );

		register_widget( 'gEditorialMagazineWidget_IssueCover' );
	}

	public function meta_init()
	{
		$this->add_post_type_fields( $this->constant( 'issue_cpt' ) );
		$this->add_post_type_fields( $this->constant( 'post_cpt' ) );

		add_filter( 'geditorial_meta_strings', array( $this, 'meta_strings' ), 6, 1 );
		add_filter( 'geditorial_meta_sanitize_post_meta', array( $this, 'meta_sanitize_post_meta' ), 10, 4 );
		add_filter( 'geditorial_meta_box_callback', array( $this, 'meta_box_callback' ), 10, 2 );

		add_action( 'geditorial_magazine_main_meta_box', array( $this, 'meta_main_meta_box' ), 10, 1 );
		add_action( 'geditorial_magazine_supported_meta_box', array( $this, 'meta_supported_meta_box' ), 10, 2 );
	}

	public function meta_box_callback( $callback, $post_type )
	{
		if ( $post_type == $this->constant( 'issue_cpt' ) )
			return FALSE;

		return $callback;
	}

	public function tweaks_strings( $strings )
	{
		$new = array(
			'taxonomies' => array(
				$this->constant( 'issue_tax' ) => array(
					'column'     => 'taxonomy-'.$this->constant( 'issue_tax' ),
					'dashicon'   => 'book',
					'title_attr' => $this->get_string( 'name', 'issue_tax', 'labels' ),
				),
				$this->constant( 'span_tax' ) => array(
					'column'     => 'taxonomy-'.$this->constant( 'span_tax' ),
					'dashicon'   => 'backup',
					'title_attr' => $this->get_string( 'name', 'span_tax', 'labels' ),
				),
			),
		);

		return self::recursiveParseArgs( $new, $strings );
	}

	public function dashboard_glance_items( $items )
	{
		$items[] = $this->dashboard_glance_post( 'issue_cpt' );
		return $items;
	}

	public function disable_months_dropdown( $false, $post_type )
	{
		if ( $this->constant( 'issue_cpt' ) == $post_type )
			return TRUE;

		return $false;
	}

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->constant( 'issue_tax' ) == $taxonomy ) {
			$post_id = '';

			// FIXME: working but disabled
			// if ( function_exists( 'get_term_meta' ) )
			// 	$post_id = get_term_meta( $term->term_id, $this->constant( 'issue_cpt' ).'_linked', TRUE );

			if ( FALSE == $post_id || empty( $post_id ) )
				$post_id = self::getPostIDbySlug( $term->slug, $this->constant( 'issue_cpt' ) );

			if ( ! empty( $post_id ) )
				return get_permalink( $post_id );
		}

		return $link;
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'issue_tax' ) ) ) {

			$term = get_queried_object();
			if ( $post_id = self::getPostIDbySlug( $term->slug, $this->constant( 'issue_cpt' ) ) )
				self::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_tax( $this->constant( 'span_tax' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_spans', FALSE ) )
				self::redirect( $redirect, 301 );

		} else if ( is_post_type_archive( $this->constant( 'issue_cpt' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				self::redirect( $redirect, 301 );
		}
	}

	public function post_updated( $post_ID, $post_after, $post_before )
	{
		if ( ! $this->is_save_post( $post_after, 'issue_cpt' ) )
			return $post_ID;

		if ( 'trash' == $post_after->post_status )
			return $post_ID;

		if ( empty( $post_before->post_name ) )
			$post_before->post_name = sanitize_title( $post_before->post_title );

		if ( empty( $post_after->post_name ) )
			$post_after->post_name = sanitize_title( $post_after->post_title );

		$args = array(
			'name'        => $post_after->post_title,
			'slug'        => $post_after->post_name,
			'description' => $post_after->post_excerpt,
			// 'parent'      => ( isset( $parent_term_id ) ? $parent_term_id : 0 ),
		);

		$the_term = get_term_by( 'slug', $post_before->post_name, $this->constant( 'issue_tax' ) );

		if ( FALSE === $the_term ){
			$the_term = get_term_by( 'slug', $post_after->post_name, $this->constant( 'issue_tax' ) );
			if ( FALSE === $the_term )
				$term = wp_insert_term( $post_after->post_title, $this->constant( 'issue_tax' ), $args );
			else
				$term = wp_update_term( $the_term->term_id, $this->constant( 'issue_tax' ), $args );
		} else {
			$term = wp_update_term( $the_term->term_id, $this->constant( 'issue_tax' ), $args );
		}

		if ( ! is_wp_error( $term ) ) {
			update_post_meta( $post_ID, '_'.$this->constant( 'issue_cpt' ).'_term_id', $term['term_id'] );

			if ( function_exists( 'update_term_meta' ) )
				update_term_meta( $term['term_id'], $this->constant( 'issue_cpt' ).'_linked', $post_ID );
		}

		return $post_ID;
	}

	public function save_post_main_cpt( $post_ID, $post, $update )
	{
		// we handle updates on another action, see : post_updated()
		if ( $update )
			return $post_ID;

		if ( ! $this->is_save_post( $post ) )
			return $post_ID;

		if ( empty( $post->post_name ) )
			$post->post_name = sanitize_title( $post->post_title );

		$args = array(
			'name'        => $post->post_title,
			'slug'        => $post->post_name,
			'description' => $post->post_excerpt,
			// 'parent'      => ( isset( $parent_term_id ) ? $parent_term_id : 0 ),
		);

		$term = wp_insert_term( $post->post_title, $this->constant( 'issue_tax' ), $args );

		if ( ! is_wp_error( $term ) ) {
			update_post_meta( $post_ID, '_'.$this->constant( 'issue_cpt' ).'_term_id', $term['term_id'] );

			if ( function_exists( 'update_term_meta' ) )
				update_term_meta( $term['term_id'], $this->constant( 'issue_cpt' ).'_linked', $post_ID );
		}

		return $post_ID;
	}

	public function wp_trash_post( $post_id )
	{
		if ( $term = $this->get_linked_term( $post_id, 'issue_cpt', 'issue_tax' ) ) {
			wp_update_term( $term->term_id, $this->constant( 'issue_tax' ), array(
				'name' => $term->name.' - '._x( '(Trashed)', 'Suffix for term name linked to trashed post', GEDITORIAL_TEXTDOMAIN ),
				'slug' => $term->slug.'-trashed',
			) );
		}
	}

	public function untrash_post( $post_id )
	{
		if ( $term = $this->get_linked_term( $post_id, 'issue_cpt', 'issue_tax' ) ) {
			wp_update_term( $term->term_id, $this->constant( 'issue_tax' ), array(
				'name' => str_ireplace( ' - '._x( '(Trashed)', 'Suffix for term name linked to trashed post', GEDITORIAL_TEXTDOMAIN ), '', $term->name ),
				'slug' => str_ireplace( '-trashed', '', $term->slug ),
			) );
		}
	}

	public function before_delete_post( $post_id )
	{
		if ( $term = $this->get_linked_term( $post_id, 'issue_cpt', 'issue_tax' ) ) {
			wp_delete_term( $term->term_id, $this->constant( 'issue_tax' ) );
			delete_metadata( 'term', $term->term_id, $this->constant( 'issue_cpt' ).'_linked' );
		}
	}

	public function wp_insert_post_data( $data, $postarr )
	{
		if ( $this->constant( 'issue_cpt' ) == $postarr['post_type'] && ! $data['menu_order'] )
			$data['menu_order'] = self::getLastPostOrder( $this->constant( 'issue_cpt' ),
				( isset( $postarr['ID'] ) ? $postarr['ID'] : '' ) ) + 1;

		return $data;
	}

	// https://gist.github.com/boonebgorges/e873fc9589998f5b07e1
	public function split_shared_term( $term_id, $new_term_id, $term_taxonomy_id, $taxonomy )
	{
		if ( $this->constant( 'issue_tax' ) == $taxonomy ) {

			$post_ids = get_posts( array(
				'post_type'  => $this->constant( 'issue_cpt' ),
				'meta_key'   => '_'.$this->constant( 'issue_cpt' ).'_term_id',
				'meta_value' => $term_id,
				'fields'     => 'ids',
			) );

			if ( $post_ids ) {
				foreach ( $post_ids as $post_id ) {
					update_post_meta( $post_id, '_'.$this->constant( 'issue_cpt' ).'_term_id', $new_term_id, $term_id );
				}
			}
		}
	}

	public function save_post_supported_cpt( $post_ID, $post, $update )
	{
		if ( ! $this->is_save_post( $post, $this->post_types() ) )
			return $post_ID;

		if ( isset( $_POST['geditorial-magazine-issue'] ) ) {
			$terms = array();

			foreach ( $_POST['geditorial-magazine-issue'] as $issue ) {
				if ( trim( $issue ) ) {
					$term = get_term_by( 'slug', $issue, $this->constant( 'issue_tax' ) );
					if ( ! empty( $term ) && ! is_wp_error( $term ) )
						$terms[] = intval( $term->term_id );
				}
			}

			wp_set_object_terms( $post_ID, ( count( $terms ) ? $terms : NULL ), $this->constant( 'issue_tax' ), FALSE );
		}

		return $post_ID;
	}

	// DISABLED
	public function admin_bar_menu( $wp_admin_bar )
	{
		if ( ! is_admin_bar_showing() || is_admin() )
			return;

		if ( current_user_can( 'edit_others_posts' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'site-name',
				'id'     => 'all-issues',
				'title'  => _x( 'Issues', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
				'href'   => admin_url( 'edit.php?post_type='.$this->constant( 'issue_cpt' ) ),
			) );
		}
	}

	public function gpeople_support( $post_types )
	{
		return array_merge( $post_types, array( $this->constant( 'issue_cpt' ) ) );
	}

	public function pre_get_posts( $wp_query )
	{
		if ( $wp_query->is_admin
			&& isset( $wp_query->query['post_type'] ) ) {

			if ( $this->constant( 'issue_cpt' ) == $wp_query->query['post_type'] ) {
				if ( ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', 'menu_order' );
				if ( ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', 'DESC' );
			}
		}
	}

	public function restrict_manage_posts()
	{
		$post_type = self::getCurrentPostType();

		if ( in_array( $post_type, $this->post_types() ) ) {

			$issue_tax = $this->constant( 'issue_tax' );
			$tax_obj   = get_taxonomy( $issue_tax );

			wp_dropdown_pages( array(
				'post_type'        => $this->constant( 'issue_cpt' ),
				'selected'         => isset( $_GET[$issue_tax] ) ? $_GET[$issue_tax] : '',
				'name'             => $issue_tax,
				'class'            => 'geditorial-admin-dropbown',
				'show_option_none' => $tax_obj->labels->all_items,
				'sort_column'      => 'menu_order',
				'sort_order'       => 'desc',
				'post_status'      => 'publish,private,draft',
				'value_field'      => 'post_name',
				'walker'           => new gEditorial_Walker_PageDropdown(),
			));

		} else if ( $this->constant( 'issue_cpt' ) == $post_type ) {

			$span_tax = $this->constant( 'span_tax' );
			$tax_obj   = get_taxonomy( $span_tax );

			wp_dropdown_categories( array(
				'show_option_all' => $tax_obj->labels->all_items,
				'taxonomy'        => $span_tax,
				'name'            => $tax_obj->name,
				// 'orderby'         => 'slug',
				'order'           => 'DESC',
				'selected'        => isset( $_GET[$span_tax] ) ? $_GET[$span_tax] : 0,
				'hierarchical'    => $tax_obj->hierarchical,
				'show_count'      => FALSE,
				'hide_empty'      => TRUE,
			) );
		}
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query->query_vars, array(
			'span_tax',
		), 'issue_cpt' );
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( $post_type == $this->constant( 'issue_cpt' ) ) {

			$this->remove_meta_box( $post_type, $post_type, 'parent' );
			add_meta_box( 'geditorial-magazine-main',
				$this->get_meta_box_title( 'issue_cpt', FALSE ),
				array( $this, 'do_meta_box_main' ),
				$post_type,
				'side',
				'high'
			);

		} else if ( in_array( $post_type, $this->post_types() ) ) {

			add_meta_box( 'geditorial-magazine-supported',
				$this->get_meta_box_title( 'post', $this->get_url_post_edit( 'issue_cpt' ) ),
				array( $this, 'do_meta_box_supported' ),
				$post_type,
				'side'
			);

			// TODO: add a thick-box to list the posts with this issue taxonomy
		}
	}

	public function do_meta_box_supported( $post )
	{
		echo '<div class="geditorial-admin-wrap-metabox magazine">';

		$terms = gEditorialHelper::getTerms( $this->constant( 'issue_tax' ), $post->ID, TRUE );

		do_action( 'geditorial_magazine_supported_meta_box', $post, $terms );

		echo '</div>';
	}

	public function supported_meta_box( $post, $terms )
	{
		$dropdowns = $excludes = array();

		foreach ( $terms as $term ) {

			$dropdowns[$term->slug] = wp_dropdown_pages( array(
				'post_type'        => $this->constant( 'issue_cpt' ),
				'selected'         => $term->slug,
				'name'             => 'geditorial-magazine-issue[]',
				'id'               => 'geditorial-magazine-issue-'.$term->slug,
				'class'            => 'geditorial-admin-dropbown',
				'show_option_none' => _x( '&mdash; Select an Issue &mdash;', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
				'sort_column'      => 'menu_order',
				'sort_order'       => 'desc',
				'post_status'      => 'publish,private,draft',
				'value_field'      => 'post_name',
				'echo'             => 0,
				'walker'           => new gEditorial_Walker_PageDropdown(),
			));

			$excludes[] = $term->slug;
		}

		if ( ! count( $terms ) || $this->get_setting( 'multiple_instances', FALSE ) ) {
			$dropdowns[0] = wp_dropdown_pages( array(
				'post_type'        => $this->constant( 'issue_cpt' ),
				'selected'         => '',
				'name'             => 'geditorial-magazine-issue[]',
				'id'               => 'geditorial-magazine-issue-0',
				'class'            => 'geditorial-admin-dropbown',
				'show_option_none' => _x( '&mdash; Select an Issue &mdash;', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
				'sort_column'      => 'menu_order',
				'sort_order'       => 'desc',
				'post_status'      => 'publish,private,draft',
				'value_field'      => 'post_name',
				'exclude'          => $excludes,
				'echo'             => 0,
				'walker'           => new gEditorial_Walker_PageDropdown(),
			));
		}

		foreach ( $dropdowns as $term_slug => $dropdown ) {
			if ( $dropdown ) {
				echo '<div class="field-wrap">';
					echo $dropdown;
				echo '</div>';
			}
		}
	}

	public function do_meta_box_main( $post )
	{
		echo '<div class="geditorial-admin-wrap-metabox">';

		do_action( 'geditorial_magazine_main_meta_box', $post );

		$this->field_post_order( 'issue_cpt', $post );

		if ( get_post_type_object( $this->constant( 'issue_cpt' ) )->hierarchical )
			$this->field_post_parent( 'issue_cpt', $post );

		// FIXME: WORKING BUT: add collapsible button / even better to display list in a modal
		// $term_id = get_post_meta( $post->ID, '_'.$this->constant( 'issue_cpt' ).'_term_id', TRUE );
		// echo gEditorialHelper::getTermPosts( $this->constant( 'issue_tax' ), intval( $term_id ) );

		echo '</div>';
	}

	public function get_issue_post( $post_id = NULL, $single = FALSE )
	{
		if ( is_null( $post_id ) )
			$post_id = get_the_ID();

		$terms = gEditorialHelper::getTerms( $this->constant( 'issue_tax' ), $post_id, TRUE );
		if ( ! count( $terms ) )
			return FALSE;

		$id  = FALSE;
		$ids = array();
		foreach ( $terms as $term ) {

			// FIXME: working but disabled
			// if ( function_exists( 'get_term_meta' ) )
			// 	$id = get_term_meta( $term->term_id, $this->constant( 'issue_cpt'].'_linked', TRUE );

			if ( FALSE == $id || empty( $id ) )
				$id = self::getPostIDbySlug( $term->slug, $this->constant( 'issue_cpt' ) );

			if ( FALSE != $id && ! empty( $id ) ) {

				if ( $single )
					return $id;

				$status = get_post_status( $id );

				if ( 'publish' == $status )
					$ids[$id] = get_permalink( $id );
				else
					$ids[$id] = FALSE;
			}
		}

		if ( ! count( $ids ) )
			return FALSE;
		return $ids;
	}

	public function manage_posts_columns( $posts_columns )
	{
		$new_columns = array();
		foreach ( $posts_columns as $key => $value ) {

			if ( 'title' == $key || 'geditorial-tweaks-title' == $key ) {
				$new_columns['order'] = $this->get_column_title( 'order', 'issue_cpt' );
				$new_columns['cover'] = $this->get_column_title( 'cover', 'issue_cpt' );

				$new_columns[$key] = $value;

			} else if ( 'date' == $key ){
				$new_columns['children'] = $this->get_column_title( 'children', 'issue_cpt' );

			} else if ( in_array( $key, array( 'author', 'comments' ) ) ) {
				continue; // he he!

			} else {
				$new_columns[$key] = $value;
			}
		}
		return $new_columns;
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( 'children' == $column_name )
			$this->column_count( $this->get_linked_posts( $post_id, 'issue_cpt', 'issue_tax', TRUE ) );

		else if ( 'order' == $column_name )
			$this->column_count( get_post( $post_id )->menu_order );

		else if ( 'cover' == $column_name )
			$this->column_thumb( $post_id );
	}

	public function sortable_columns( $columns )
	{
		$columns['order'] = 'menu_order';
		return $columns;
	}

	public function meta_strings( $strings )
	{
		$issue_cpt = $this->constant( 'issue_cpt' );

		$strings['titles'][$issue_cpt] = $this->strings['titles']['issue_cpt'];
		$strings['descriptions'][$issue_cpt] = $this->strings['descriptions']['issue_cpt'];

		$strings['titles']['post'] = $strings['titles']['post'] + $this->strings['titles']['post_cpt'];
		$strings['descriptions']['post'] = $strings['descriptions']['post'] + $this->strings['descriptions']['post_cpt'];

		return $strings;
	}

	public function meta_sanitize_post_meta( $postmeta, $fields, $post_id, $post_type )
	{
		$issue_cpt = $this->constant( 'issue_cpt' );

		if ( $issue_cpt == $post_type
			&& wp_verify_nonce( @$_REQUEST['_geditorial_magazine_main_box'], 'geditorial_magazine_main_box' ) ) {

			foreach ( $this->fields[$issue_cpt] as $field => $field_enabled ) {
				switch ( $field ) {
					case 'issue_total_pages':
					case 'issue_number_line':

						gEditorialHelper::set_postmeta_field_string( $postmeta, $field );
				}
			}

		} else if ( in_array( $post_type, $this->post_types() )
			&& wp_verify_nonce( @$_REQUEST['_geditorial_magazine_meta_post_raw'], 'geditorial_magazine_meta_post_raw' )  ) {

			foreach ( $this->fields[$post_type] as $field => $field_enabled ) {
				switch ( $field ) {
					case 'in_issue_order':
					case 'in_issue_page_start':

						gEditorialHelper::set_postmeta_field_number( $postmeta, $field );

					break;
					case 'in_issue_pages':

						gEditorialHelper::set_postmeta_field_string( $postmeta, $field );
				}
			}
		}

		return $postmeta;
	}

	public static function meta_main_meta_box( $post )
	{
		$fields = gEditorial()->meta->post_type_fields( $post->post_type );

		do_action( 'geditorial_meta_box_before', gEditorial()->meta->module, $post, $fields );

		gEditorialHelper::meta_admin_field( 'issue_number_line', $fields, $post );
		gEditorialHelper::meta_admin_field( 'issue_total_pages', $fields, $post );

		do_action( 'geditorial_meta_box_after', gEditorial()->meta->module, $post, $fields );

		wp_nonce_field( 'geditorial_magazine_main_box', '_geditorial_magazine_main_box' );
	}

	public static function meta_supported_meta_box( $post, $the_issue_terms )
	{
		// do not display if it's not assigned to any issue
		if ( ! count( $the_issue_terms ) )
			return;

		$fields = gEditorial()->meta->post_type_fields( $post->post_type );

		gEditorialHelper::meta_admin_number_field( 'in_issue_page_start', $fields, $post );
		gEditorialHelper::meta_admin_number_field( 'in_issue_order', $fields, $post );
		gEditorialHelper::meta_admin_field( 'in_issue_pages', $fields, $post );

		wp_nonce_field( 'geditorial_magazine_meta_post_raw', '_geditorial_magazine_meta_post_raw' );
	}

	public function post_updated_messages( $messages )
	{
		if ( $this->is_current_posttype( 'issue_cpt' ) )
			$messages[$this->constant( 'issue_cpt' )] = $this->get_post_updated_messages( 'issue_cpt' );

		return $messages;
	}

	public function tools_messages( $messages, $sub )
	{
		if ( $this->module->name == $sub )
			$messages['created'] = self::counted( _x( '%s Issue Post(s) Created.', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ) );
		return $messages;
	}

	public function tools_sub( $uri, $sub )
	{
		echo '<form method="post" action="">';

			$this->tools_field_referer( $sub );

			echo '<h3>'._x( 'Magazine Tools', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ).'</h3>';
			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'From Terms', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			if ( ! empty( $_POST ) && isset( $_POST['issue_tax_check'] ) ) {

				self::tableList( array(
					'_cb'     => 'term_id',
					'term_id' => _x( 'ID', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
					'name'    => _x( 'Name', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
					'issue'   => array(
						'title' => _x( 'Issue', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column ){
							if ( $post_id = self::getPostIDbySlug( $row->slug, $this->constant( 'issue_cpt' ) ) )
								return $post_id.' &mdash; '.get_post($post_id)->post_title;
							return _x( '&mdash;&mdash;&mdash;&mdash; No Issue', 'Magazine Module', GEDITORIAL_TEXTDOMAIN );
						},
					),
					'count' => array(
						'title'    => _x( 'Count', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column ){
							if ( $post_id = self::getPostIDbySlug( $row->slug, $this->constant( 'issue_cpt' ) ) )
								return number_format_i18n( $this->get_linked_posts( $post_id, 'issue_cpt', 'issue_tax', TRUE ) );
							return number_format_i18n( $row->count );
						},
					),
					'description' => array(
						'title'    => _x( 'Description', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
						'callback' => 'wpautop',
						'class'    => 'description',
					),
				), gEditorialHelper::getTerms( $this->constant( 'issue_tax' ), FALSE, TRUE ) );

				echo '<br />';
			}

			submit_button( _x( 'Check Terms', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'issue_tax_check', FALSE, array( 'default' => 'default' ) ); echo '&nbsp;&nbsp;';
			submit_button( _x( 'Create Issue', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'issue_post_create', FALSE  ); echo '&nbsp;&nbsp;';
			submit_button( _x( 'Store Orders', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'issue_store_order', FALSE  ); //echo '&nbsp;&nbsp;';

			echo self::html( 'p', array(
				'class' => 'description',
			), _x( 'Check for issue terms and create corresponding issue posts.', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ) );

			echo '</td></tr>';
			echo '</table>';
		echo '</form>';
	}

	public function tools_settings( $sub )
	{
		if ( ! current_user_can( $this->tools_cap ) )
			return;

		if ( $this->module->name == $sub ) {
			if ( ! empty( $_POST ) ) {

				$this->tools_check_referer( $sub );

				if ( isset( $_POST['issue_post_create'] ) ) {

					// FIXME: get term_id list from table checkbox

					$terms = gEditorialHelper::getTerms( $this->constant( 'issue_tax' ), FALSE, TRUE );
					$posts = array();

					foreach ( $terms as $term_id => $term ) {
						$issue_post_id = self::getPostIDbySlug( $term->slug, $this->constant( 'issue_cpt' ) ) ;
						if ( FALSE === $issue_post_id )
							$posts[] = self::newPostFromTerm( $term, $this->constant( 'issue_tax' ), $this->constant( 'issue_cpt' ) );
					}

					self::redirect( add_query_arg( array(
						'message' => 'created',
						'count'   => count( $posts ),
					), wp_get_referer() ) );

				} else if ( isset( $_POST['_cb'] )
					&& ( isset( $_POST['issue_store_order'] )
						|| isset( $_POST['issue_store_start'] ) ) ) {

					$meta_key = isset( $_POST['issue_store_order'] ) ? 'in_issue_order' : 'in_issue_page_start';
					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {
						foreach ( $this->get_linked_posts( NULL, 'issue_cpt', 'issue_tax', FALSE, $term_id ) as $post ) {

							if ( $post->menu_order )
								continue;

							if ( $order = gEditorial()->meta->get_postmeta( $post->ID, $meta_key, FALSE ) ) {
								wp_update_post( array(
									'ID'         => $post->ID,
									'menu_order' => $order,
								) );
								$count++;
							}
						}
					}

					self::redirect( add_query_arg( array(
						'message' => 'ordered',
						'count'   => $count,
					), wp_get_referer() ) );
				}
			}

			add_filter( 'geditorial_tools_messages', array( $this, 'tools_messages' ), 10, 2 );
			add_action( 'geditorial_tools_sub_'.$this->module->name, array( $this, 'tools_sub' ), 10, 2 );
		}

		add_filter( 'geditorial_tools_subs', array( $this, 'tools_subs' ) );
	}
}
