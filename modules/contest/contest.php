<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;

class Contest extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'contest',
			'title' => _x( 'Contest', 'Modules: Contest', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Contest Management', 'Modules: Contest', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'megaphone',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'multiple_instances',
				'admin_ordering',
				'admin_restrict',
				'redirect_archives',
			],
			'posttypes_option' => 'posttypes_option',
		];
	}

	protected function get_global_constants()
	{
		return [
			'contest_cpt'         => 'contest',
			'contest_cpt_archive' => 'contests',
			'apply_cpt'           => 'apply',
			'apply_cpt_archive'   => 'applies',
			'contest_cat'         => 'contest_cat',
			'contest_tax'         => 'contests',
			'apply_cat'           => 'apply_cat',
			'apply_status_tax'    => 'apply_status',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'contest_cat'      => 'category',
				'contest_tax'      => 'megaphone',
				'apply_cat'        => 'category',
				'apply_status_tax' => 'post-status', // 'portfolio',
			],
		];
	}

	protected function get_global_strings()
	{
		return [
			'misc' => [
				'contest_cpt' => [
					'meta_box_title'        => _x( 'Metadata', 'Modules: Contest: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'cover_box_title'       => _x( 'Poster', 'Modules: Contest: CoverBox Title', GEDITORIAL_TEXTDOMAIN ),
					'cover_column_title'    => _x( 'Poster', 'Modules: Contest: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'order_column_title'    => _x( 'O', 'Modules: Contest: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'children_column_title' => _x( 'Applies', 'Modules: Contest: Column Title', GEDITORIAL_TEXTDOMAIN ),
				],
				'contest_cat' => [
					'tweaks_column_title' => _x( 'Contest Categories', 'Modules: Contest: Column Title', GEDITORIAL_TEXTDOMAIN ),
				],
				'apply_cat' => [
					'tweaks_column_title' => _x( 'Apply Categories', 'Modules: Contest: Column Title', GEDITORIAL_TEXTDOMAIN ),
				],
				'apply_status_tax' => [
					'meta_box_title'      => _x( 'Apply Statuses', 'Modules: Contest: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Apply Statuses', 'Modules: Contest: Column Title', GEDITORIAL_TEXTDOMAIN ),
				],
				'meta_box_title'      => _x( 'Contests', 'Modules: Contest: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
				'tweaks_column_title' => _x( 'Contests', 'Modules: Contest: Column Title', GEDITORIAL_TEXTDOMAIN ),
			],
			'settings' => [
				'install_def_apply_status_tax' => _x( 'Install Default Apply Statuses', 'Modules: Contest: Setting Button', GEDITORIAL_TEXTDOMAIN ),
			],
			'noops' => [
				'contest_cpt'      => _nx_noop( 'Contest', 'Contests', 'Modules: Contest: Noop', GEDITORIAL_TEXTDOMAIN ),
				'contest_tax'      => _nx_noop( 'Contest', 'Contests', 'Modules: Contest: Noop', GEDITORIAL_TEXTDOMAIN ),
				'contest_cat'      => _nx_noop( 'Contest Category', 'Contest Categories', 'Modules: Contest: Noop', GEDITORIAL_TEXTDOMAIN ),
				'apply_cpt'        => _nx_noop( 'Apply', 'Applies', 'Modules: Contest: Noop', GEDITORIAL_TEXTDOMAIN ),
				'apply_cat'        => _nx_noop( 'Apply Category', 'Apply Categories', 'Modules: Contest: Noop', GEDITORIAL_TEXTDOMAIN ),
				'apply_status_tax' => _nx_noop( 'Apply Status', 'Apply Statuses', 'Modules: Contest: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
			'terms' => [
				'apply_status_tax' => [
					'approved' => _x( 'Approved', 'Modules: Contest: Apply Statuses Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'pending'  => _x( 'Pending', 'Modules: Contest: Apply Statuses Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				],
			],
		];
	}

	protected function get_global_supports()
	{
		return [
			'contest_cpt' => [
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
			'apply_cpt' => [
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

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'contest_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->post_types_excluded = [ 'attachment', $this->constant( 'contest_cpt' ) ];

		$this->register_post_type( 'contest_cpt', [
			'hierarchical'  => TRUE,
		] );

		$this->register_post_type( 'apply_cpt', [
			'menu_icon' => 'dashicons-portfolio',
		] );

		$this->register_taxonomy( 'contest_cat', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'contest_cpt' );

		$this->register_taxonomy( 'contest_tax', [
			'show_ui'            => FALSE,
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		] );

		$this->register_taxonomy( 'apply_cat', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'apply_cpt' );

		$this->register_taxonomy( 'apply_status_tax', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'apply_cpt' );

		if ( ! is_admin() ) {
			$this->filter( 'term_link', 3 );
			$this->action( 'template_redirect' );
		}
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, 'contest_cpt' ) ) {

			$this->_edit_screen( $_REQUEST['post_type'] );

			$this->_sync_linked( $_REQUEST['post_type'] );
		}
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'contest_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'wp_insert_post_data', 2, 9 );
				$this->filter( 'post_updated_messages' );

				add_filter( 'geditorial_meta_box_callback', '__return_false', 12 );

				$this->remove_meta_box( $screen->post_type, $screen->post_type, 'parent' );
				add_meta_box( $this->classs( 'main' ),
					$this->get_meta_box_title( 'contest_cpt', FALSE ),
					[ $this, 'do_meta_box_main' ],
					$screen->post_type,
					'side',
					'high'
				);

				add_meta_box( $this->classs( 'list' ),
					$this->get_meta_box_title( 'contest_tax', $this->get_url_post_edit( 'post_cpt' ), 'edit_others_posts' ),
					[ $this, 'do_meta_box_list' ],
					$screen->post_type,
					'advanced',
					'low'
				);

				$this->_sync_linked( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );

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

				if ( $screen->post_type == $this->constant( 'apply_cpt' ) )
					add_filter( 'post_updated_messages', [ $this, 'post_updated_messages_supported' ] );

				$this->remove_meta_box( $screen->post_type, $screen->post_type, 'parent' );
				add_meta_box( $this->classs( 'supported' ),
					$this->get_meta_box_title( $screen->post_type, $this->get_url_post_edit( 'contest_cpt' ), 'edit_others_posts' ),
					[ $this, 'do_meta_box_supported' ],
					$screen->post_type,
					'side'
				);

				// internal actions:
				add_action( 'geditorial_contest_supported_meta_box', [ $this, 'supported_meta_box' ], 5, 2 );

				// TODO: add a thick-box to list the posts with this issue taxonomy

			} else if ( 'edit' == $screen->base ) {

				if ( $screen->post_type == $this->constant( 'apply_cpt' ) )
					add_filter( 'bulk_post_updated_messages', [ $this, 'bulk_post_updated_messages_supported' ], 10, 2 );

				if ( $this->get_setting( 'admin_restrict', FALSE ) )
					add_action( 'restrict_manage_posts', [ $this, 'restrict_manage_posts_supported_cpt' ], 12, 2 );

				$this->_tweaks_taxonomy();
			}

			add_action( 'save_post', [ $this, 'save_post_supported_cpt' ], 20, 3 );
		}
	}

	private function _edit_screen( $post_type )
	{
		add_filter( 'manage_'.$post_type.'_posts_columns', [ $this, 'manage_posts_columns' ] );
		add_filter( 'manage_'.$post_type.'_posts_custom_column', [ $this, 'posts_custom_column' ], 10, 2 );
	}

	private function _sync_linked( $post_type )
	{
		add_action( 'save_post', [ $this, 'save_post_main_cpt' ], 20, 3 );
		$this->action( 'post_updated', 3, 20 );

		$this->action( 'wp_trash_post' );
		$this->action( 'untrash_post' );
		$this->action( 'before_delete_post' );
	}

	public function register_settings( $page = NULL )
	{
		if ( ! $this->is_register_settings( $page ) )
			return;

		if ( isset( $_POST['install_def_apply_status_tax'] ) )
			$this->insert_default_terms( 'apply_status_tax' );

		parent::register_settings( $page );
		$this->register_button( 'install_def_apply_status_tax' );
	}

	public function meta_post_types( $post_types )
	{
		return array_merge( $post_types, [
			$this->constant( 'contest_cpt' ),
			$this->constant( 'apply_cpt' ),
		] );
	}

	public function gpeople_support( $post_types )
	{
		return array_merge( $post_types, [
			$this->constant( 'contest_cpt' ),
			$this->constant( 'apply_cpt' ),
		] );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $contests = $this->dashboard_glance_post( 'contest_cpt' ) )
			$items[] = $contests;

		if ( $applies = $this->dashboard_glance_post( 'apply_cpt' ) )
			$items[] = $applies;

		return $items;
	}

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->constant( 'contest_tax' ) != $taxonomy )
			return $link;

		if ( $post_id = $this->get_linked_post_id( $term, 'contest_cpt', 'contest_tax' ) )
			return get_permalink( $post_id );

		return $link;
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'contest_tax' ) ) ) {

			$term = get_queried_object();

			if ( $post_id = $this->get_linked_post_id( $term, 'contest_cpt', 'contest_tax' ) )
				WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_post_type_archive( $this->constant( 'contest_cpt' ) )
			|| is_post_type_archive( $this->constant( 'apply_cpt' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress::redirect( $redirect, 301 );
		}
	}

	public function do_meta_box_main( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox -contest">';

		// OLD ACTION: 'geditorial_the_contest_meta_box'
		do_action( 'geditorial_contest_main_meta_box', $post );

		do_action( 'geditorial_meta_do_meta_box', $post, $box, NULL );

		MetaBox::fieldPostMenuOrder( $post );
		MetaBox::fieldPostParent( $post->post_type, $post );

		echo '</div>';
	}

	public function do_meta_box_list( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox -contest">';

		do_action( 'geditorial_contest_list_meta_box', $post, $box );

		// TODO: add collapsible button
		if ( $term = $this->get_linked_term( $post->ID, 'contest_cpt', 'contest_tax' ) )
			echo Helper::getTermPosts( $this->constant( 'contest_tax' ), $term );

		echo '</div>';
	}

	public function do_meta_box_supported( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox -contest">';

		$terms = Taxonomy::getTerms( $this->constant( 'contest_tax' ), $post->ID, TRUE );

		// OLD ACTION: 'geditorial_contest_meta_box'
		do_action( 'geditorial_contest_supported_meta_box', $post, $terms );

		echo '</div>';
	}

	public function supported_meta_box( $post, $terms )
	{
		MetaBox::fieldPostMenuOrder( $post );

		$post_type = $this->constant( 'contest_cpt' );
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

	public function meta_box_cb_apply_status_tax( $post, $box )
	{
		MetaBox::checklistTerms( $post, $box );
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, [ $this->constant( 'contest_cpt' ) => $this->get_post_updated_messages( 'contest_cpt' ) ] );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, [ $this->constant( 'contest_cpt' ) => $this->get_bulk_post_updated_messages( 'contest_cpt', $counts ) ] );
	}

	public function post_updated_messages_supported( $messages )
	{
		return array_merge( $messages, [ $this->constant( 'apply_cpt' ) => $this->get_post_updated_messages( 'apply_cpt' ) ] );
	}

	public function bulk_post_updated_messages_supported( $messages, $counts )
	{
		return array_merge( $messages, [ $this->constant( 'apply_cpt' ) => $this->get_bulk_post_updated_messages( 'apply_cpt', $counts ) ] );
	}


	public function wp_insert_post_data( $data, $postarr )
	{
		if ( $this->constant( 'contest_cpt' ) == $postarr['post_type'] && ! $data['menu_order'] )
			$data['menu_order'] = WordPress::getLastPostOrder( $this->constant( 'contest_cpt' ),
				( isset( $postarr['ID'] ) ? $postarr['ID'] : '' ) ) + 1;

		return $data;
	}

	public function post_updated( $post_ID, $post_after, $post_before )
	{
		if ( ! $this->is_save_post( $post_after, 'contest_tax' ) )
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

		$the_term = get_term_by( 'slug', $post_before->post_name, $this->constant( 'contest_tax' ) );

		if ( FALSE === $the_term ) {
			$the_term = get_term_by( 'slug', $post_after->post_name, $this->constant( 'contest_tax' ) );
			if ( FALSE === $the_term )
				$term = wp_insert_term( $post_after->post_title, $this->constant( 'contest_tax' ), $args );
			else
				$term = wp_update_term( $the_term->term_id, $this->constant( 'contest_tax' ), $args );
		} else {
			$term = wp_update_term( $the_term->term_id, $this->constant( 'contest_tax' ), $args );
		}

		if ( ! is_wp_error( $term ) )
			$this->set_linked_term( $post_ID, $term['term_id'], 'contest_cpt', 'contest_tax' );

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

		$term = wp_insert_term( $post->post_title, $this->constant( 'contest_tax' ), $args );

		if ( ! is_wp_error( $term ) )
			$this->set_linked_term( $post_ID, $term['term_id'], 'contest_cpt', 'contest_tax' );

		return $post_ID;
	}

	public function save_post_supported_cpt( $post_ID, $post, $update )
	{
		if ( ! $this->is_save_post( $post, $this->post_types() ) )
			return $post_ID;

		$name = $this->classs( $this->constant( 'contest_cpt' ) );

		if ( ! isset( $_POST[$name] ) )
			return $post_ID;

		$terms = [];
		$tax   = $this->constant( 'contest_tax' );

		foreach ( (array) $_POST[$name] as $issue )
			if ( trim( $issue ) && $term = get_term_by( 'slug', $issue, $tax ) )
				$terms[] = intval( $term->term_id );

		wp_set_object_terms( $post_ID, ( count( $terms ) ? $terms : NULL ), $tax, FALSE );

		return $post_ID;
	}

	public function wp_trash_post( $post_id )
	{
		$this->do_trash_post( $post_id, 'contest_cpt', 'contest_tax' );
	}

	public function untrash_post( $post_id )
	{
		$this->do_untrash_post( $post_id, 'contest_cpt', 'contest_tax' );
	}

	public function before_delete_post( $post_id )
	{
		$this->do_before_delete_post( $post_id, 'contest_cpt', 'contest_tax' );
	}

	public function restrict_manage_posts_supported_cpt( $post_type, $which )
	{
		$this->do_restrict_manage_posts_posts( 'contest_tax', 'contest_cpt' );
	}

	public function pre_get_posts( $wp_query )
	{
		if ( $wp_query->is_admin
			&& isset( $wp_query->query['post_type'] ) ) {

			if ( $this->constant( 'contest_cpt' ) == $wp_query->query['post_type'] ) {

				if ( ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', 'menu_order' );

				if ( ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', 'DESC' );
			}
		}
	}

	public function manage_posts_columns( $posts_columns )
	{
		$new_columns = [];
		foreach ( $posts_columns as $key => $value ) {

			if ( $key == 'title' ) {
				$new_columns['order'] = $this->get_column_title( 'order', 'contest_cpt' );
				$new_columns['cover'] = $this->get_column_title( 'cover', 'contest_cpt' );
				$new_columns[$key] = $value;

			} else if ( 'date' == $key ) {
				$new_columns['children'] = $this->get_column_title( 'children', 'contest_cpt' );

			} else if ( in_array( $key, [ 'author', 'comments' ] ) ) {
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
			$this->column_count( $this->get_linked_posts( $post_id, 'contest_cpt', 'contest_tax', TRUE ) );

		else if ( 'order' == $column_name )
			$this->column_count( get_post( $post_id )->menu_order );

		else if ( 'cover' == $column_name )
			$this->column_thumb( $post_id, $this->get_image_size_key( 'contest_cpt' ) );
	}

	public function sortable_columns( $columns )
	{
		return array_merge( $columns, [ 'order' => 'menu_order' ] );
	}

	public function main_column_attr( $post )
	{
		$posts = $this->get_linked_posts( $post->ID, 'contest_cpt', 'contest_tax' );
		$count = count( $posts );

		if ( ! $count )
			return;

		echo '<li class="-attr -contest -children">';

			echo $this->get_column_icon( FALSE, NULL, $this->get_column_title( 'children', 'contest_cpt' ) );

			$post_types = array_unique( array_map( function( $r ){
				return $r->post_type;
			}, $posts ) );

			$args = [ $this->constant( 'contest_tax' ) => $post->post_name ];

			if ( empty( $this->all_post_types ) )
				$this->all_post_types = PostType::get( 2 );

			echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

			$list = [];

			foreach ( $post_types as $post_type )
				$list[] = HTML::tag( 'a', [
					'href'   => WordPress::getPostTypeEditLink( $post_type, 0, $args ),
					'title'  => _x( 'View the connected list', 'Modules: Contest', GEDITORIAL_TEXTDOMAIN ),
					'target' => '_blank',
				], $this->all_post_types[$post_type] );

			echo Helper::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

		echo '</li>';
	}

	public function tools_sub( $uri, $sub )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', 'tools', FALSE, FALSE );

			HTML::h3( _x( 'Contest Tools', 'Modules: Contest', GEDITORIAL_TEXTDOMAIN ) );

			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'From Terms', 'Modules: Contest', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			if ( ! empty( $_POST ) && isset( $_POST['contest_tax_check'] ) ) {

				HTML::tableList( [
					'_cb'     => 'term_id',
					'term_id' => Helper::tableColumnTermID(),
					'name'    => Helper::tableColumnTermName(),
					'linked'   => [
						'title' => _x( 'Linked Contest Post', 'Modules: Contest: Table Column', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column, $index ){

							if ( $post_id = $this->get_linked_post_id( $row, 'contest_cpt', 'contest_tax', FALSE ) )
								return Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';

							return '&mdash;';
						},
					],
					'slugged'   => [
						'title' => _x( 'Same Slug Contest Post', 'Modules: Contest: Table Column', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column, $index ){

							if ( $post_id = PostType::getIDbySlug( $row->slug, $this->constant( 'contest_cpt' ) ) )
								return Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';

							return '&mdash;';
						},
					],
					'count' => [
						'title'    => _x( 'Count', 'Modules: Contest: Table Column', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column, $index ){
							if ( $post_id = PostType::getIDbySlug( $row->slug, $this->constant( 'contest_cpt' ) ) )
								return Number::format( $this->get_linked_posts( $post_id, 'contest_cpt', 'contest_tax', TRUE ) );
							return Number::format( $row->count );
						},
					],
					'description' => Helper::tableColumnTermDesc(),
				], Taxonomy::getTerms( $this->constant( 'contest_tax' ), FALSE, TRUE ) );

				echo '<br />';
			}

			Settings::submitButton( 'contest_tax_check',
				_x( 'Check Terms', 'Modules: Contest: Setting Button', GEDITORIAL_TEXTDOMAIN ), TRUE );

			Settings::submitButton( 'contest_post_create',
				_x( 'Create Contest Posts', 'Modules: Contest: Setting Button', GEDITORIAL_TEXTDOMAIN ));

			Settings::submitButton( 'contest_post_connect',
				_x( 'Re-Connect Posts', 'Modules: Contest: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

			Settings::submitButton( 'contest_tax_delete',
				_x( 'Delete Terms', 'Modules: Contest: Setting Button', GEDITORIAL_TEXTDOMAIN ), 'delete', TRUE );

			HTML::desc( _x( 'Check for contest terms and create corresponding contest posts.', 'Modules: Contest', GEDITORIAL_TEXTDOMAIN ) );

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
					&& isset( $_POST['contest_post_create'] ) ) {

					$terms = Taxonomy::getTerms( $this->constant( 'contest_tax' ), FALSE, TRUE );
					$posts = [];

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! isset( $terms[$term_id] ) )
							continue;

						$post_id = PostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( 'contest_cpt' ) ) ;

						if ( FALSE !== $post_id )
							continue;

						$posts[] = WordPress::newPostFromTerm(
							$terms[$term_id],
							$this->constant( 'contest_tax' ),
							$this->constant( 'contest_cpt' ),
							Helper::getEditorialUserID()
						);
					}

					WordPress::redirectReferer( [
						'message' => 'created',
						'count'   => count( $posts ),
					] );

				} else if ( isset( $_POST['_cb'] )
					&& isset( $_POST['contest_post_connect'] ) ) {

					$terms = Taxonomy::getTerms( $this->constant( 'contest_tax' ), FALSE, TRUE );
					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! isset( $terms[$term_id] ) )
							continue;

						$post_id = PostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( 'contest_cpt' ) ) ;

						if ( FALSE === $post_id )
							continue;

						if ( $this->set_linked_term( $post_id, $terms[$term_id], 'contest_cpt', 'contest_tax' ) )
							$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'updated',
						'count'   => $count,
					] );

				} else if ( isset( $_POST['_cb'] )
					&& isset( $_POST['contest_tax_delete'] ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( $this->rev_linked_term( NULL, $term_id, 'contest_cpt', 'contest_tax' ) ) {

							$deleted = wp_delete_term( $term_id, $this->constant( 'contest_tax' ) );

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
