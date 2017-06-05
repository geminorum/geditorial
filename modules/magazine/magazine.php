<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\Templates\Magazine as ModuleTemplate;

class Magazine extends gEditorial\Module
{

	protected $partials = [ 'templates' ];

	protected $caps = [
		'tools' => 'edit_others_posts',
	];

	public static function module()
	{
		return [
			'name'  => 'magazine',
			'title' => _x( 'Magazine', 'Modules: Magazine', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Issue Management for Magazines', 'Modules: Magazine', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'book',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'multiple_instances',
				'shortcode_support',
				'admin_ordering',
				'admin_restrict',
				[
					'field'       => 'issue_sections',
					'title'       => _x( 'Issue Sections', 'Modules: Magazine: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Section taxonomy for issue and supported post types', 'Modules: Magazine: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '0',
				],
				'posttype_feeds',
				'posttype_pages',
				'redirect_archives',
				[
					'field'       => 'redirect_spans',
					'type'        => 'text',
					'title'       => _x( 'Redirect Spans', 'Modules: Magazine: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Redirect all Span Archives to a URL', 'Modules: Magazine: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '',
					'dir'         => 'ltr',
				],
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				$this->settings_supports_option( 'issue_cpt', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
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
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'issue_tax'   => 'book',
				'span_tax'    => 'backup',
				'section_tax' => 'category',
			],
		];
	}

	protected function get_global_strings()
	{
		return [
			'misc' => [
				'issue_cpt' => [
					'featured'              => _x( 'Cover Image', 'Modules: Magazine: Issue CPT: Featured', GEDITORIAL_TEXTDOMAIN ),
					'cover_column_title'    => _x( 'Cover', 'Modules: Magazine: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'order_column_title'    => _x( 'O', 'Modules: Magazine: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'children_column_title' => _x( 'Posts', 'Modules: Magazine: Column Title', GEDITORIAL_TEXTDOMAIN ),
				],
				'issue_tax' => [
					'meta_box_title' => _x( 'In This Issue', 'Modules: Magazine: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
				],
				'span_tax' => [
					'meta_box_title'      => _x( 'Spans', 'Modules: Magazine: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Issue Spans', 'Modules: Magazine: Column Title', GEDITORIAL_TEXTDOMAIN ),
				],
				'section_tax' => [
					'meta_box_title'      => _x( 'Sections', 'Modules: Magazine: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Issue Sections', 'Modules: Magazine: Column Title', GEDITORIAL_TEXTDOMAIN ),
				],
				'meta_box_title'      => _x( 'The Issue', 'Modules: Magazine: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
				'tweaks_column_title' => _x( 'Issues', 'Modules: Magazine: Column Title', GEDITORIAL_TEXTDOMAIN ),
			],
			'noops' => [
				'issue_cpt'   => _nx_noop( 'Issue', 'Issues', 'Modules: Magazine: Noop', GEDITORIAL_TEXTDOMAIN ),
				'issue_tax'   => _nx_noop( 'Issue', 'Issues', 'Modules: Magazine: Noop', GEDITORIAL_TEXTDOMAIN ),
				'span_tax'    => _nx_noop( 'Span', 'Spans', 'Modules: Magazine: Noop', GEDITORIAL_TEXTDOMAIN ),
				'section_tax' => _nx_noop( 'Section', 'Sections', 'Modules: Magazine: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
			'p2p' => [
				'issue_cpt' => [
					'title' => [
						'from' => _x( 'Connected Issues', 'Modules: Magazine: P2P', GEDITORIAL_TEXTDOMAIN ),
						'to'   => _x( 'Connected Posts', 'Modules: Magazine: P2P', GEDITORIAL_TEXTDOMAIN ),
					],
					'from_labels' => [
						'singular_name' => _x( 'Post', 'Modules: Magazine: P2P', GEDITORIAL_TEXTDOMAIN ),
						'search_items'  => _x( 'Search posts', 'Modules: Magazine: P2P', GEDITORIAL_TEXTDOMAIN ),
						'not_found'     => _x( 'No posts found.', 'Modules: Magazine: P2P', GEDITORIAL_TEXTDOMAIN ),
						'create'        => _x( 'Connect to a post', 'Modules: Magazine: P2P', GEDITORIAL_TEXTDOMAIN ),
					],
					'to_labels' => [
						'singular_name' => _x( 'Issue', 'Modules: Magazine: P2P', GEDITORIAL_TEXTDOMAIN ),
						'search_items'  => _x( 'Search issues', 'Modules: Magazine: P2P', GEDITORIAL_TEXTDOMAIN ),
						'not_found'     => _x( 'No issues found.', 'Modules: Magazine: P2P', GEDITORIAL_TEXTDOMAIN ),
						'create'        => _x( 'Connect to an issue', 'Modules: Magazine: P2P', GEDITORIAL_TEXTDOMAIN ),
					],
				],
			],
		];
	}

	protected function get_global_supports()
	{
		return [
			'issue_cpt' => [
				'title',
				'editor',
				'excerpt',
				'author',
				'thumbnail',
				'comments',
				'revisions',
				'page-attributes',
				'date-picker', // gPersianDate
			],
		];
	}

	protected function get_global_fields()
	{
		return [
			$this->constant( 'issue_cpt' ) => [
				'ot' => [ 'type' => 'title_before' ],
				'st' => [ 'type' => 'title_after' ],

				'issue_number_line' => [
					'title'       => _x( 'Number Line', 'Modules: Magazine: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'The issue number line', 'Modules: Magazine: Field Description', GEDITORIAL_TEXTDOMAIN ),
				],
				'issue_total_pages' => [
					'title'       => _x( 'Total Pages', 'Modules: Magazine: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'The issue total pages', 'Modules: Magazine: Field Description', GEDITORIAL_TEXTDOMAIN ),
				],
			],
			'post' => [
				'in_issue_order' => [
					'title'       => _x( 'Order', 'Modules: Magazine: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Post order in issue list', 'Modules: Magazine: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'number',
					'context'     => 'issue',
				],
				'in_issue_page_start' => [
					'title'       => _x( 'Page Start', 'Modules: Magazine: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Post start page on issue (printed)', 'Modules: Magazine: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'number',
					'context'     => 'issue',
				],
				'in_issue_pages' => [
					'title'       => _x( 'Total Pages', 'Modules: Magazine: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Post total pages on issue (printed)', 'Modules: Magazine: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'context'     => 'issue',
				],
			],
		];
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'issue_cpt' );
	}

	public function p2p_init()
	{
		$this->p2p_register( 'issue_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->post_types_excluded = [ 'attachment', $this->constant( 'issue_cpt' ) ];

		$this->register_post_type( 'issue_cpt', [
			'hierarchical' => TRUE,
			'rewrite'      => [
				'feeds' => (bool) $this->get_setting( 'posttype_feeds', FALSE ),
				'pages' => (bool) $this->get_setting( 'posttype_pages', FALSE ),
			],
		] );

		$this->register_taxonomy( 'issue_tax', [
			'show_ui'      => FALSE,
			'hierarchical' => TRUE,
		] );

		$this->register_taxonomy( 'span_tax', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'issue_cpt' );

		if ( $this->get_setting( 'issue_sections', FALSE ) )
			$this->register_taxonomy( 'section_tax', [
				'hierarchical'       => TRUE,
				'show_admin_column'  => TRUE,
				'show_in_quick_edit' => TRUE,
				'show_in_nav_menus'  => TRUE,
			], $this->post_types( 'issue_cpt' ) );

		$this->register_shortcode( 'issue_shortcode' );
		$this->register_shortcode( 'span_shortcode' );

		if ( ! is_admin() ) {
			$this->filter( 'term_link', 3 );
			$this->action( 'template_redirect' );
		}
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, 'issue_cpt' ) ) {

			$this->_edit_screen( $_REQUEST['post_type'] );

			$this->_sync_linked( $_REQUEST['post_type'] );
		}
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'issue_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'wp_insert_post_data', 2, 9 );
				$this->filter( 'post_updated_messages' );

				add_filter( 'geditorial_meta_box_callback', '__return_false', 12 );

				$this->remove_meta_box( $screen->post_type, $screen->post_type, 'parent' );
				add_meta_box( $this->classs( 'main' ),
					$this->get_meta_box_title( 'issue_cpt', FALSE ),
					[ $this, 'do_meta_box_main' ],
					$screen->post_type,
					'side',
					'high'
				);

				add_meta_box( $this->classs( 'list' ),
					$this->get_meta_box_title( 'issue_tax', $this->get_url_post_edit( 'post_cpt' ), 'edit_others_posts' ),
					[ $this, 'do_meta_box_list' ],
					$screen->post_type,
					'advanced',
					'low'
				);

				$this->_sync_linked( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );

				add_filter( 'disable_months_dropdown', '__return_true', 12 );

				if ( $this->get_setting( 'admin_restrict', FALSE ) ) {
					add_action( 'restrict_manage_posts', [ $this, 'restrict_manage_posts_main_cpt' ], 12, 2 );
					$this->filter( 'parse_query' );
				}

				if ( $this->get_setting( 'admin_ordering', TRUE ) )
					$this->action( 'pre_get_posts' );

				$this->_sync_linked( $screen->post_type );
				$this->_edit_screen( $screen->post_type );
				add_filter( 'manage_edit-'.$screen->post_type.'_sortable_columns', [ $this, 'sortable_columns' ] );
				add_thickbox();

				$this->_tweaks_taxonomy();
				add_action( 'geditorial_tweaks_column_attr', [ $this, 'main_column_attr' ] );
			}

		} else if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'post' == $screen->base ) {

				add_meta_box( $this->classs( 'supported' ),
					$this->get_meta_box_title( $screen->post_type, $this->get_url_post_edit( 'issue_cpt' ), 'edit_others_posts' ),
					[ $this, 'do_meta_box_supported' ],
					$screen->post_type,
					'side'
				);

				// internal actions:
				add_action( 'geditorial_magazine_supported_meta_box', [ $this, 'supported_meta_box' ], 5, 3 );

				// TODO: add a thick-box to list the posts with this issue taxonomy

			} else if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_restrict', FALSE ) )
					add_action( 'restrict_manage_posts', [ $this, 'restrict_manage_posts_supported_cpt' ], 12, 2 );

				$this->_tweaks_taxonomy();
			}

			add_action( 'save_post', [ $this, 'save_post_supported_cpt' ], 20, 3 );
		}

		// $size = apply_filters( 'admin_post_thumbnail_size', $size, $thumbnail_id, $post );
	}

	// FIXME: make this api
	private function _edit_screen( $post_type )
	{
		add_filter( 'manage_'.$post_type.'_posts_columns', [ $this, 'manage_posts_columns' ] );
		add_filter( 'manage_'.$post_type.'_posts_custom_column', [ $this, 'posts_custom_column' ], 10, 2 );
	}

	// FIXME: make this api
	private function _sync_linked( $post_type )
	{
		add_action( 'save_post', [ $this, 'save_post_main_cpt' ], 20, 3 );
		$this->action( 'post_updated', 3, 20 );

		$this->action( 'wp_trash_post' );
		$this->action( 'untrash_post' );
		$this->action( 'before_delete_post' );
	}

	public function widgets_init()
	{
		$this->require_code( 'widgets' );

		register_widget( '\\geminorum\\gEditorial\\Widgets\\Magazine\\IssueCover' );
	}

	public function meta_init()
	{
		$this->add_post_type_fields( $this->constant( 'issue_cpt' ) );
		$this->add_post_type_fields( $this->constant( 'post_cpt' ) );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'issue_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->constant( 'issue_tax' ) != $taxonomy )
			return $link;

		if ( $post_id = $this->get_linked_post_id( $term, 'issue_cpt', 'issue_tax' ) )
			return get_permalink( $post_id );

		return $link;
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'issue_tax' ) ) ) {

			$term = get_queried_object();

			if ( $post_id = $this->get_linked_post_id( $term, 'issue_cpt', 'issue_tax' ) )
				WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_tax( $this->constant( 'span_tax' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_spans', FALSE ) )
				WordPress::redirect( $redirect, 301 );

		} else if ( is_post_type_archive( $this->constant( 'issue_cpt' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress::redirect( $redirect, 301 );
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

		$args = [
			'name'        => $post_after->post_title,
			'slug'        => $post_after->post_name,
			'description' => $post_after->post_excerpt,
			// 'parent'      => ( isset( $parent_term_id ) ? $parent_term_id : 0 ),
		];

		$the_term = get_term_by( 'slug', $post_before->post_name, $this->constant( 'issue_tax' ) );

		if ( FALSE === $the_term ) {
			$the_term = get_term_by( 'slug', $post_after->post_name, $this->constant( 'issue_tax' ) );
			if ( FALSE === $the_term )
				$term = wp_insert_term( $post_after->post_title, $this->constant( 'issue_tax' ), $args );
			else
				$term = wp_update_term( $the_term->term_id, $this->constant( 'issue_tax' ), $args );
		} else {
			$term = wp_update_term( $the_term->term_id, $this->constant( 'issue_tax' ), $args );
		}

		if ( ! is_wp_error( $term ) )
			$this->set_linked_term( $post_ID, $term['term_id'], 'issue_cpt', 'issue_tax' );

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

		$args = [
			'name'        => $post->post_title,
			'slug'        => $post->post_name,
			'description' => $post->post_excerpt,
			// 'parent'      => ( isset( $parent_term_id ) ? $parent_term_id : 0 ),
		];

		$term = wp_insert_term( $post->post_title, $this->constant( 'issue_tax' ), $args );

		if ( ! is_wp_error( $term ) )
			$this->set_linked_term( $post_ID, $term['term_id'], 'issue_cpt', 'issue_tax' );

		return $post_ID;
	}

	public function wp_trash_post( $post_id )
	{
		$this->do_trash_post( $post_id, 'issue_cpt', 'issue_tax' );
	}

	public function untrash_post( $post_id )
	{
		$this->do_untrash_post( $post_id, 'issue_cpt', 'issue_tax' );
	}

	public function before_delete_post( $post_id )
	{
		$this->do_before_delete_post( $post_id, 'issue_cpt', 'issue_tax' );
	}

	// FIXME: make this api
	public function wp_insert_post_data( $data, $postarr )
	{
		if ( $this->constant( 'issue_cpt' ) == $postarr['post_type'] && ! $data['menu_order'] )
			$data['menu_order'] = WordPress::getLastPostOrder( $this->constant( 'issue_cpt' ),
				( isset( $postarr['ID'] ) ? $postarr['ID'] : '' ) ) + 1;

		return $data;
	}

	public function save_post_supported_cpt( $post_ID, $post, $update )
	{
		if ( ! $this->is_save_post( $post, $this->post_types() ) )
			return $post_ID;

		$name = $this->classs( $this->constant( 'issue_cpt' ) );

		if ( ! isset( $_POST[$name] ) )
			return $post_ID;

		$terms = [];
		$tax   = $this->constant( 'issue_tax' );

		foreach ( (array) $_POST[$name] as $issue )
			if ( trim( $issue ) && $term = get_term_by( 'slug', $issue, $tax ) )
				$terms[] = intval( $term->term_id );

		wp_set_object_terms( $post_ID, ( count( $terms ) ? $terms : NULL ), $tax, FALSE );

		return $post_ID;
	}

	public function gpeople_support( $post_types )
	{
		return array_merge( $post_types, [ $this->constant( 'issue_cpt' ) ] );
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

	public function restrict_manage_posts_main_cpt( $post_type, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'span_tax' );
	}

	public function restrict_manage_posts_supported_cpt( $post_type, $which )
	{
		$this->do_restrict_manage_posts_posts( 'issue_tax', 'issue_cpt' );
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query, 'span_tax' );
	}

	public function meta_box_cb_span_tax( $post, $box )
	{
		MetaBox::checklistTerms( $post, $box );
	}

	public function meta_box_cb_section_tax( $post, $box )
	{
		MetaBox::checklistTerms( $post, $box );
	}

	public function do_meta_box_supported( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox -magazine">';

		$terms = Taxonomy::getTerms( $this->constant( 'issue_tax' ), $post->ID, TRUE );

		do_action( 'geditorial_magazine_supported_meta_box', $post, $box, $terms );

		do_action( 'geditorial_meta_do_meta_box', $post, $box, NULL, 'issue' );

		echo '</div>';
	}

	public function supported_meta_box( $post, $box, $terms )
	{
		$post_type = $this->constant( 'issue_cpt' );
		$dropdowns = $excludes = [];

		foreach ( $terms as $term ) {
			$dropdowns[$term->slug] = MetaBox::dropdownAssocPosts( $post_type, $term->slug, $this->classs() );
			$excludes[] = $term->slug;
		}

		if ( ! count( $terms ) || $this->get_setting( 'multiple_instances', FALSE ) )
			$dropdowns[0] = MetaBox::dropdownAssocPosts( $post_type, '', $this->classs(), $excludes );

		$empty = TRUE;

		foreach ( $dropdowns as $term_slug => $dropdown ) {
			if ( $dropdown ) {
				echo '<div class="field-wrap">';
					echo $dropdown;
				echo '</div>';

				$empty = FALSE;
			}
		}

		if ( $empty )
			return MetaBox::fieldEmptyPostType( $post_type );
	}

	public function do_meta_box_main( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox -magazine">';

		do_action( 'geditorial_magazine_main_meta_box', $post, $box );

		do_action( 'geditorial_meta_do_meta_box', $post, $box, NULL );

		MetaBox::fieldPostMenuOrder( $post );
		MetaBox::fieldPostParent( $post->post_type, $post );

		echo '</div>';
	}

	public function do_meta_box_list( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox -magazine">';

		do_action( 'geditorial_magazine_list_meta_box', $post, $box );

		// TODO: add collapsible button
		if ( $term = $this->get_linked_term( $post->ID, 'issue_cpt', 'issue_tax' ) )
			echo MetaBox::getTermPosts( $this->constant( 'issue_tax' ), $term );

		echo '</div>';
	}

	public function get_assoc_post( $post = NULL, $single = FALSE, $published = TRUE )
	{
		$posts = [];
		$terms = Taxonomy::getTerms( $this->constant( 'issue_tax' ), $post, TRUE );

		foreach ( $terms as $term ) {

			if ( ! $linked = $this->get_linked_post_id( $term, 'issue_cpt', 'issue_tax' ) )
				continue;

			if ( $single )
				return $linked;

			if ( $published && 'publish' != get_post_status( $linked ) )
				continue;

			$posts[$term->term_id] = $linked;
		}

		return count( $posts ) ? $posts : FALSE;
	}

	public function manage_posts_columns( $columns )
	{
		$new = [];

		foreach ( $columns as $key => $value ) {

			if ( 'title' == $key ) {
				$new['order'] = $this->get_column_title( 'order', 'issue_cpt' );
				$new['cover'] = $this->get_column_title( 'cover', 'issue_cpt' );

				$new[$key] = $value;

			} else if ( 'date' == $key ) {
				$new['children'] = $this->get_column_title( 'children', 'issue_cpt' );

			} else if ( in_array( $key, [ 'author', 'comments' ] ) ) {
				continue; // he he!

			} else {
				$new[$key] = $value;
			}
		}

		return $new;
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( 'children' == $column_name )
			$this->column_count( $this->get_linked_posts( $post_id, 'issue_cpt', 'issue_tax', TRUE ) );

		else if ( 'order' == $column_name )
			$this->column_count( get_post( $post_id )->menu_order );

		else if ( 'cover' == $column_name )
			$this->column_thumb( $post_id, $this->get_image_size_key( 'issue_cpt' ) );
	}

	public function sortable_columns( $columns )
	{
		$span    = $this->constant( 'span_tax' );
		$section = $this->constant( 'section_tax' );

		return array_merge( $columns, [
			'order'              => 'menu_order',
			'taxonomy-'.$span    => 'taxonomy-'.$span,
			'taxonomy-'.$section => 'taxonomy-'.$section,
		] );
	}

	public function main_column_attr( $post )
	{
		$posts = $this->get_linked_posts( $post->ID, 'issue_cpt', 'issue_tax' );
		$count = count( $posts );

		if ( ! $count )
			return;

		echo '<li class="-attr -magazine -children">';

			echo $this->get_column_icon( FALSE, NULL, $this->get_column_title( 'children', 'issue_cpt' ) );

			$post_types = array_unique( array_map( function( $r ){
				return $r->post_type;
			}, $posts ) );

			$args = [ $this->constant( 'issue_tax' ) => $post->post_name ];

			if ( empty( $this->all_post_types ) )
				$this->all_post_types = PostType::get( 2 );

			echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

			$list = [];

			foreach ( $post_types as $post_type )
				$list[] = HTML::tag( 'a', [
					'href'   => WordPress::getPostTypeEditLink( $post_type, 0, $args ),
					'title'  => _x( 'View the connected list', 'Modules: Magazine', GEDITORIAL_TEXTDOMAIN ),
					'target' => '_blank',
				], $this->all_post_types[$post_type] );

			echo Helper::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

		echo '</li>';
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'issue_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'issue_cpt', $counts ) );
	}

	public function issue_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::getAssocPosts(
			$this->constant( 'issue_cpt' ),
			$this->constant( 'issue_tax' ),
			array_merge( [
				'posttypes' => $this->post_types(),
				'order_cb'  => NULL, // NULL for default ordering by meta
				'orderby'   => 'order', // order by meta
			], (array) $atts ),
			$content,
			$this->constant( 'issue_shortcode' )
		);
	}

	public function span_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::getTermPosts(
			$this->constant( 'issue_cpt' ),
			$this->constant( 'span_tax' ),
			$atts,
			$content,
			$this->constant( 'span_shortcode' )
		);
	}

	public function tools_sub( $uri, $sub )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', 'tools', FALSE, FALSE );

			HTML::h3( _x( 'Magazine Tools', 'Modules: Magazine', GEDITORIAL_TEXTDOMAIN ) );

			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'From Terms', 'Modules: Magazine', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			echo '<p>';

			Settings::submitButton( 'issue_tax_check',
				_x( 'Check Terms', 'Modules: Magazine: Setting Button', GEDITORIAL_TEXTDOMAIN ), TRUE );

			Settings::submitButton( 'issue_post_create',
				_x( 'Create Issue Posts', 'Modules: Magazine: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

			Settings::submitButton( 'issue_post_connect',
				_x( 'Re-Connect Posts', 'Modules: Magazine: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

			Settings::submitButton( 'issue_store_order',
				_x( 'Store Orders', 'Modules: Magazine: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

			Settings::submitButton( 'issue_tax_delete',
				_x( 'Delete Terms', 'Modules: Magazine: Setting Button', GEDITORIAL_TEXTDOMAIN ), 'delete', TRUE );


			echo '</p>';

			if ( ! empty( $_POST ) && isset( $_POST['issue_tax_check'] ) ) {
				echo '<br />';

				HTML::tableList( [
					'_cb'     => 'term_id',
					'term_id' => Helper::tableColumnTermID(),
					'name'    => Helper::tableColumnTermName(),
					'linked'   => [
						'title' => _x( 'Linked Issue Post', 'Modules: Magazine: Table Column', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column, $index ){

							if ( $post_id = $this->get_linked_post_id( $row, 'issue_cpt', 'issue_tax', FALSE ) )
								return Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';

							return '&mdash;';
						},
					],
					'slugged'   => [
						'title' => _x( 'Same Slug Issue Post', 'Modules: Magazine: Table Column', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column, $index ){

							if ( $post_id = PostType::getIDbySlug( $row->slug, $this->constant( 'issue_cpt' ) ) )
								return Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';

							return '&mdash;';
						},
					],
					'count' => [
						'title'    => _x( 'Count', 'Modules: Magazine: Table Column', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column, $index ){
							if ( $post_id = PostType::getIDbySlug( $row->slug, $this->constant( 'issue_cpt' ) ) )
								return Number::format( $this->get_linked_posts( $post_id, 'issue_cpt', 'issue_tax', TRUE ) );
							return Number::format( $row->count );
						},
					],
					'description' => Helper::tableColumnTermDesc(),
				], Taxonomy::getTerms( $this->constant( 'issue_tax' ), FALSE, TRUE ), [
					'empty' => HTML::warning( _x( 'No Terms Found!', 'Modules: Magazine: Table Empty', GEDITORIAL_TEXTDOMAIN ) ),
				] );
			}

			HTML::desc( _x( 'Check for issue terms and create corresponding issue posts.', 'Modules: Magazine', GEDITORIAL_TEXTDOMAIN ) );

			echo '</td></tr>';
			echo '</table>';

		$this->settings_form_after( $uri, $sub );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->settings_check_referer( $sub, 'tools' );

				if ( isset( $_POST['_cb'] )
					&& isset( $_POST['issue_post_create'] ) ) {

					$terms = Taxonomy::getTerms( $this->constant( 'issue_tax' ), FALSE, TRUE );
					$posts = [];

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! isset( $terms[$term_id] ) )
							continue;

						$post_id = PostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( 'issue_cpt' ) ) ;

						if ( FALSE !== $post_id )
							continue;

						$posts[] = WordPress::newPostFromTerm(
							$terms[$term_id],
							$this->constant( 'issue_tax' ),
							$this->constant( 'issue_cpt' ),
							Helper::getEditorialUserID( TRUE )
						);
					}

					WordPress::redirectReferer( [
						'message' => 'created',
						'count'   => count( $posts ),
					] );

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
								wp_update_post( [
									'ID'         => $post->ID,
									'menu_order' => $order,
								] );
								$count++;
							}
						}
					}

					WordPress::redirectReferer( [
						'message' => 'ordered',
						'count'   => $count,
					] );

				} else if ( isset( $_POST['_cb'] )
					&& isset( $_POST['issue_post_connect'] ) ) {

					$terms = Taxonomy::getTerms( $this->constant( 'issue_tax' ), FALSE, TRUE );
					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! isset( $terms[$term_id] ) )
							continue;

						$post_id = PostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( 'issue_cpt' ) ) ;

						if ( FALSE === $post_id )
							continue;

						if ( $this->set_linked_term( $post_id, $terms[$term_id], 'issue_cpt', 'issue_tax' ) )
							$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'updated',
						'count'   => $count,
					] );

				} else if ( isset( $_POST['_cb'] )
					&& isset( $_POST['issue_tax_delete'] ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( $this->rev_linked_term( NULL, $term_id, 'issue_cpt', 'issue_tax' ) ) {

							$deleted = wp_delete_term( $term_id, $this->constant( 'issue_tax' ) );

							if ( $deleted && ! is_wp_error( $deleted ) )
								$count++;
						}
					}

					WordPress::redirectReferer( [
						'message' => 'deleted',
						'count'   => $count,
					] );
				}
			}
		}
	}
}
