<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;

class Contest extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'contest',
			'title' => _x( 'Contest', 'Modules: Contest', 'geditorial' ),
			'desc'  => _x( 'Contest Management', 'Modules: Contest', 'geditorial' ),
			'icon'  => 'megaphone',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'multiple_instances',
				'comment_status',
			],
			'_editlist' => [
				'admin_ordering',
				'admin_restrict',
			],
			'_frontend' => [
				[
					'field'       => 'redirect_archives',
					'type'        => 'url',
					'title'       => _x( 'Redirect Archives', 'Settings', 'geditorial-contest' ),
					'description' => _x( 'Redirects contest and apply archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-contest' ),
					'placeholder' => URL::home( 'archives' ),
				],
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'thumbnail_support',
				$this->settings_supports_option( 'contest_cpt', TRUE ),
				$this->settings_supports_option( 'apply_cpt', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'contest_cpt'         => 'contest',
			'contest_cpt_archive' => 'contests',
			'contest_tax'         => 'contests',
			'apply_cpt'           => 'apply',
			'apply_cpt_archive'   => 'applies',
			'contest_cat'         => 'contest_cat',
			'apply_cat'           => 'apply_cat',
			'apply_status_tax'    => 'apply_status',
		];
	}

	protected function get_module_icons()
	{
		return [
			'post_types' => [
				'contest_cpt' => NULL,
				'apply_cpt'   => 'portfolio',
			],
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
		$strings = [
			'noops' => [
				'contest_cpt'      => _n_noop( 'Contest', 'Contests', 'geditorial-contest' ),
				'contest_tax'      => _n_noop( 'Contest', 'Contests', 'geditorial-contest' ),
				'contest_cat'      => _n_noop( 'Contest Category', 'Contest Categories', 'geditorial-contest' ),
				'apply_cpt'        => _n_noop( 'Apply', 'Applies', 'geditorial-contest' ),
				'apply_cat'        => _n_noop( 'Apply Category', 'Apply Categories', 'geditorial-contest' ),
				'apply_status_tax' => _n_noop( 'Apply Status', 'Apply Statuses', 'geditorial-contest' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'contest_cpt' => [
				'featured'              => _x( 'Poster Image', 'Posttype Featured', 'geditorial-contest' ),
				'meta_box_title'        => _x( 'Metadata', 'MetaBox Title', 'geditorial-contest' ),
				'children_column_title' => _x( 'Applies', 'Column Title', 'geditorial-contest' ),
			],
			'contest_tax' => [
				'meta_box_title' => _x( 'In This Contest', 'MetaBox Title', 'geditorial-contest' ),
			],
			'contest_cat' => [
				'tweaks_column_title' => _x( 'Contest Categories', 'Column Title', 'geditorial-contest' ),
			],
			'apply_cpt' => [
				'meta_box_title' => _x( 'Contest', 'MetaBox Title', 'geditorial-contest' ),
			],
			'apply_cat' => [
				'tweaks_column_title' => _x( 'Apply Categories', 'Column Title', 'geditorial-contest' ),
			],
			'apply_status_tax' => [
				'meta_box_title'      => _x( 'Apply Statuses', 'MetaBox Title', 'geditorial-contest' ),
				'tweaks_column_title' => _x( 'Apply Statuses', 'Column Title', 'geditorial-contest' ),
			],
			'meta_box_title'      => _x( 'Contests', 'MetaBox Title', 'geditorial-contest' ),
			'tweaks_column_title' => _x( 'Contests', 'Column Title', 'geditorial-contest' ),
		];

		$strings['terms'] = [
			'apply_status_tax' => [
				'approved' => _x( 'Approved', 'Default Term', 'geditorial-contest' ),
				'pending'  => _x( 'Pending', 'Default Term', 'geditorial-contest' ),
			],
		];

		return $strings;
	}

	protected function posttypes_excluded()
	{
		return Settings::posttypesExcluded( $this->constant( 'contest_cpt' ) );
	}

	public function before_settings( $module = FALSE )
	{
		if ( isset( $_POST['install_def_apply_status_tax'] ) )
			$this->insert_default_terms( 'apply_status_tax' );

		$this->help_tab_default_terms( 'apply_status_tax' );
	}

	public function default_buttons( $module = FALSE )
	{
		parent::default_buttons( $module );

		$this->register_button( 'install_def_apply_status_tax', _x( 'Install Default Apply Statuses', 'Button', 'geditorial-contest' ) );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'contest_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'contest_cat', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'contest_cpt' );

		$this->register_taxonomy( 'contest_tax', [
			'show_ui'            => TRUE,
			'show_in_menu'       => FALSE,
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

		$this->register_posttype( 'contest_cpt', [
			'hierarchical' => TRUE,
		] );

		$this->register_posttype( 'apply_cpt' );

		if ( is_admin() )
			return;

		$this->filter( 'term_link', 3 );
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, 'contest_cpt' ) )
			$this->_sync_linked( $_REQUEST['post_type'] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'contest_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'wp_insert_post_data', 2, 9, 'menu_order' );
				$this->filter( 'post_updated_messages' );
				$this->filter( 'get_default_comment_status', 3 );

				$this->filter_false( 'geditorial_meta_box_callback', 12 );
				$this->class_metabox( $screen, 'main' );

				remove_meta_box( 'pageparentdiv', $screen, 'side' );
				add_meta_box( $this->classs( 'main' ),
					$this->get_meta_box_title( 'contest_cpt', FALSE ),
					[ $this, 'render_metabox_main' ],
					$screen,
					'side',
					'high'
				);

				$this->class_metabox( $screen, 'list' );

				add_meta_box( $this->classs( 'list' ),
					$this->get_meta_box_title( 'contest_tax' ),
					[ $this, 'render_metabox_list' ],
					$screen,
					'advanced',
					'low'
				);

				$this->_sync_linked( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );

				if ( $this->get_setting( 'admin_ordering', TRUE ) )
					$this->action( 'pre_get_posts' );

				$this->_sync_linked( $screen->post_type );

				$this->action_module( 'tweaks', 'column_attr' );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				if ( $screen->post_type == $this->constant( 'apply_cpt' ) )
					add_filter( 'post_updated_messages', [ $this, 'post_updated_messages_supported' ] );

				$this->class_metabox( $screen, 'supported' );

				remove_meta_box( 'pageparentdiv', $screen, 'side' );
				add_meta_box( $this->classs( 'supported' ),
					$this->get_meta_box_title( 'apply_cpt' ),
					[ $this, 'render_metabox_supported' ],
					$screen,
					'side'
				);

				add_action( $this->hook( 'render_metabox_supported' ), [ $this, 'render_metabox' ], 10, 4 );

			} else if ( 'edit' == $screen->base ) {

				if ( $screen->post_type == $this->constant( 'apply_cpt' ) )
					add_filter( 'bulk_post_updated_messages', [ $this, 'bulk_post_updated_messages_supported' ], 10, 2 );

				if ( $this->get_setting( 'admin_restrict', FALSE ) )
					add_action( 'restrict_manage_posts', [ $this, 'restrict_manage_posts_supported_cpt' ], 12, 2 );

				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}

			// interferes with default behavior of metabox save
			// $this->_hook_store_metabox( $screen->post_type );
		}
	}

	private function _sync_linked( $posttype )
	{
		$this->action( 'save_post', 3, 20 );
		$this->action( 'post_updated', 3, 20 );

		$this->action( 'wp_trash_post' );
		$this->action( 'untrash_post' );
		$this->action( 'before_delete_post' );
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

	public function render_metabox_main( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'render_metabox', $post, $box, NULL, 'main' );

			do_action( 'geditorial_meta_render_metabox', $post, $box, NULL );

			MetaBox::fieldPostMenuOrder( $post );
			MetaBox::fieldPostParent( $post );

		echo '</div>';
	}

	public function render_metabox_list( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'render_metabox_list', $post, $box, NULL, NULL );

			$term = $this->get_linked_term( $post->ID, 'contest_cpt', 'contest_tax' );

			if ( $list = MetaBox::getTermPosts( $this->constant( 'contest_tax' ), $term, $this->posttypes() ) )
				echo $list;

			else
				HTML::desc( _x( 'No items connected!', 'Message', 'geditorial-contest' ), FALSE, '-empty' );

		echo '</div>';
	}

	public function render_metabox_supported( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

		if ( ! Taxonomy::hasTerms( $this->constant( 'contest_tax' ) ) ) {

			MetaBox::fieldEmptyPostType( $this->constant( 'contest_cpt' ) );

		} else {

			$this->actions( 'render_metabox_supported', $post, $box, NULL, NULL );

			do_action( 'geditorial_meta_render_metabox', $post, $box, NULL, 'contest' );
		}

		echo '</div>';
	}

	public function render_metabox( $post, $box, $fields = NULL, $context = 'main' )
	{
		$dropdowns = $excludes = [];
		$posttype  = $this->constant( 'contest_cpt' );
		$terms     = Taxonomy::getTerms( $this->constant( 'contest_tax' ), $post->ID, TRUE );

		foreach ( $terms as $term ) {
			$dropdowns[$term->slug] = MetaBox::dropdownAssocPosts( $posttype, $term->slug, $this->classs() );
			$excludes[] = $term->slug;
		}

		if ( empty( $dropdowns ) || $this->get_setting( 'multiple_instances' ) )
			$dropdowns[0] = MetaBox::dropdownAssocPosts( $posttype, '0', $this->classs(), $excludes );

		foreach ( $dropdowns as $dropdown )
			echo $dropdown ?: '';

		MetaBox::fieldPostMenuOrder( $post );
	}

	public function meta_box_cb_apply_status_tax( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'contest_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'contest_cpt', $counts ) );
	}

	public function post_updated_messages_supported( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'apply_cpt' ) );
	}

	public function bulk_post_updated_messages_supported( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'apply_cpt', $counts ) );
	}

	public function post_updated( $post_id, $post_after, $post_before )
	{
		if ( ! $this->is_save_post( $post_after, 'contest_cpt' ) )
			return;

		if ( 'trash' == $post_after->post_status )
			return;

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
			$this->set_linked_term( $post_id, $term['term_id'], 'contest_cpt', 'contest_tax' );
	}

	public function save_post( $post_id, $post, $update )
	{
		// we handle updates on another action, see : post_updated()
		if ( $update )
			return;

		if ( ! $this->is_save_post( $post ) )
			return;

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
			$this->set_linked_term( $post_id, $term['term_id'], 'contest_cpt', 'contest_tax' );
	}

	// FIXME: not used
	public function store_metabox( $post_id, $post, $update, $context = 'main' )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		$name = $this->classs( $this->constant( 'contest_cpt' ) );

		if ( ! isset( $_POST[$name] ) )
			return;

		$terms = [];
		$tax   = $this->constant( 'contest_tax' );

		foreach ( (array) $_POST[$name] as $contest )
			if ( trim( $contest ) && $term = get_term_by( 'slug', $contest, $tax ) )
				$terms[] = intval( $term->term_id );

		wp_set_object_terms( $post_id, ( count( $terms ) ? $terms : NULL ), $tax, FALSE );
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

	public function restrict_manage_posts_supported_cpt( $posttype, $which )
	{
		$this->do_restrict_manage_posts_posts( 'contest_tax', 'contest_cpt' );
	}

	public function pre_get_posts( &$wp_query )
	{
		if ( $this->constant( 'contest_cpt' ) == $wp_query->get( 'post_type' ) ) {

			if ( $wp_query->is_admin ) {

				if ( ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', 'menu_order' );

				if ( ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', 'DESC' );
			}
		}
	}

	public function tweaks_column_attr( $post )
	{
		$posts = $this->get_linked_posts( $post->ID, 'contest_cpt', 'contest_tax' );
		$count = count( $posts );

		if ( ! $count )
			return;

		echo '<li class="-row -contest -children">';

			echo $this->get_column_icon( FALSE, NULL, $this->get_column_title( 'children', 'contest_cpt' ) );

			$posttypes = array_unique( array_map( function( $r ){
				return $r->post_type;
			}, $posts ) );

			$args = [ $this->constant( 'contest_tax' ) => $post->post_name ];

			if ( empty( $this->cache_posttypes ) )
				$this->cache_posttypes = PostType::get( 2 );

			echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

			$list = [];

			foreach ( $posttypes as $posttype )
				$list[] = HTML::tag( 'a', [
					'href'   => WordPress::getPostTypeEditLink( $posttype, 0, $args ),
					'title'  => _x( 'View the connected list', 'Title Attr', 'geditorial-contest' ),
					'target' => '_blank',
				], $this->cache_posttypes[$posttype] );

			echo Helper::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

		echo '</li>';
	}

	// FIXME: update from magazine module
	protected function render_tools_html( $uri, $sub )
	{
		HTML::h3( _x( 'Contest Tools', 'Header', 'geditorial-contest' ) );

		echo '<table class="form-table">';

		echo '<tr><th scope="row">'._x( 'From Terms', 'Tools', 'geditorial-contest' ).'</th><td>';

		if ( ! empty( $_POST ) && isset( $_POST['contest_tax_check'] ) ) {

			HTML::tableList( [
				'_cb'     => 'term_id',
				'term_id' => Helper::tableColumnTermID(),
				'name'    => Helper::tableColumnTermName(),
				'linked'   => [
					'title' => _x( 'Linked Contest Post', 'Table Column', 'geditorial-contest' ),
					'callback' => function( $value, $row, $column, $index ){

						if ( $post_id = $this->get_linked_post_id( $row, 'contest_cpt', 'contest_tax', FALSE ) )
							return Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';

						return Helper::htmlEmpty();
					},
				],
				'slugged'   => [
					'title' => _x( 'Same Slug Contest Post', 'Table Column', 'geditorial-contest' ),
					'callback' => function( $value, $row, $column, $index ){

						if ( $post_id = PostType::getIDbySlug( $row->slug, $this->constant( 'contest_cpt' ) ) )
							return Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';

						return Helper::htmlEmpty();
					},
				],
				'count' => [
					'title'    => _x( 'Count', 'Table Column', 'geditorial-contest' ),
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
			_x( 'Check Terms', 'Button', 'geditorial-contest' ), TRUE );

		Settings::submitButton( 'contest_post_create',
			_x( 'Create Contest Posts', 'Button', 'geditorial-contest' ) );

		Settings::submitButton( 'contest_post_connect',
			_x( 'Re-Connect Posts', 'Button', 'geditorial-contest' ) );

		Settings::submitButton( 'contest_tax_delete',
			_x( 'Delete Terms', 'Button', 'geditorial-contest' ), 'danger', TRUE );

		HTML::desc( _x( 'Check for contest terms and create corresponding contest posts.', 'Message', 'geditorial-contest' ) );

		echo '</td></tr>';
		echo '</table>';
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( $this->current_action( 'contest_post_create', TRUE ) ) {

					$terms = Taxonomy::getTerms( $this->constant( 'contest_tax' ), FALSE, TRUE );
					$posts = [];

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! isset( $terms[$term_id] ) )
							continue;

						$post_id = PostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( 'contest_cpt' ) );

						if ( FALSE !== $post_id )
							continue;

						$posts[] = WordPress::newPostFromTerm(
							$terms[$term_id],
							$this->constant( 'contest_tax' ),
							$this->constant( 'contest_cpt' ),
							gEditorial()->user( TRUE )
						);
					}

					WordPress::redirectReferer( [
						'message' => 'created',
						'count'   => count( $posts ),
					] );

				} else if ( $this->current_action( 'contest_post_connect', TRUE ) ) {

					$terms = Taxonomy::getTerms( $this->constant( 'contest_tax' ), FALSE, TRUE );
					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! isset( $terms[$term_id] ) )
							continue;

						$post_id = PostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( 'contest_cpt' ) );

						if ( FALSE === $post_id )
							continue;

						if ( $this->set_linked_term( $post_id, $terms[$term_id], 'contest_cpt', 'contest_tax' ) )
							$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'updated',
						'count'   => $count,
					] );

				} else if ( $this->current_action( 'contest_tax_delete', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( $this->remove_linked_term( NULL, $term_id, 'contest_cpt', 'contest_tax' ) ) {

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
