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
use geminorum\gEditorial\Templates\Collect as ModuleTemplate;

class Collect extends gEditorial\Module
{

	protected $partials = [ 'templates' ];

	protected $caps = [
		'tools' => 'edit_others_posts',
	];

	public static function module()
	{
		return [
			'name'  => 'collect',
			'title' => _x( 'Collect', 'Modules: Collect', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Create and use Collections of Posts', 'Modules: Collect', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'star-filled',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'multiple_instances',
				[
					'field'       => 'collection_parts',
					'title'       => _x( 'Collection Parts', 'Modules: Collect: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Partition taxonomy for collections and supported posttypes.', 'Modules: Collect: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				'comment_status',
			],
			'_editlist' => [
				'admin_ordering',
				'admin_restrict',
			],
			'_frontend' => [
				'insert_poster',
				'insert_priority',
				'posttype_feeds',
				'posttype_pages',
				'redirect_archives',
				[
					'field'       => 'redirect_groups',
					'type'        => 'url',
					'title'       => _x( 'Redirect Groups', 'Modules: Collect: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Redirect all Group Archives to a URL', 'Modules: Collect: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'placeholder' => 'http://example.com/archives/',
				],
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'collection_cpt', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'collection_cpt'           => 'collection',
			'collection_cpt_archive'   => 'collections',
			'collection_cpt_permalink' => '/%postname%',
			'collection_tax'           => 'collections',
			'group_tax'                => 'collection_group',
			'group_tax_slug'           => 'collection-group',
			'part_tax'                 => 'collection_part',
			'part_tax_slug'            => 'collection-part',
			'collection_shortcode'     => 'collection',
			'group_shortcode'          => 'collection-group',
			'poster_shortcode'         => 'collection-poster',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'collection_tax' => 'star-filled',
				'group_tax'      => 'clipboard',
				'part_tax'       => 'exerpt-view',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'collection_cpt' => _nx_noop( 'Collection', 'Collections', 'Modules: Collect: Noop', GEDITORIAL_TEXTDOMAIN ),
				'collection_tax' => _nx_noop( 'Collection', 'Collections', 'Modules: Collect: Noop', GEDITORIAL_TEXTDOMAIN ),
				'group_tax'      => _nx_noop( 'Group', 'Groups', 'Modules: Collect: Noop', GEDITORIAL_TEXTDOMAIN ),
				'part_tax'       => _nx_noop( 'Part', 'Parts', 'Modules: Collect: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'collection_cpt' => [
				'featured' => _x( 'Poster Image', 'Modules: Collect: Collection CPT: Featured', GEDITORIAL_TEXTDOMAIN ),
			],
			'collection_tax' => [
				'meta_box_title' => _x( 'In This Collection', 'Modules: Collect: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
			],
			'group_tax' => [
				'meta_box_title'      => _x( 'Groups', 'Modules: Collect: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
				'tweaks_column_title' => _x( 'Collection Groups', 'Modules: Collect: Column Title', GEDITORIAL_TEXTDOMAIN ),
			],
			'part_tax' => [
				'meta_box_title'      => _x( 'Parts', 'Modules: Collect: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
				'tweaks_column_title' => _x( 'Collection Parts', 'Modules: Collect: Column Title', GEDITORIAL_TEXTDOMAIN ),
			],
			'meta_box_title'         => _x( 'The Collection', 'Modules: Collect: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
			'tweaks_column_title'    => _x( 'Collections', 'Modules: Collect: Column Title', GEDITORIAL_TEXTDOMAIN ),
			'connected_column_title' => _x( 'Connected Items', 'Modules: Collect: Column Title', GEDITORIAL_TEXTDOMAIN ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			$this->constant( 'collection_cpt' ) => [
				'ot' => [ 'type' => 'title_before' ],
				'st' => [ 'type' => 'title_after' ],

				'number' => [
					'title'       => _x( 'Number Line', 'Modules: Collect: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'The collection number line', 'Modules: Collect: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'icon'        => 'menu',
				],
				'total_items' => [
					'title'       => _x( 'Total Items', 'Modules: Collect: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'The collection total items', 'Modules: Collect: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'icon'        => 'admin-page',
				],
			],
			'post' => [
				'in_collection_order' => [
					'title'       => _x( 'Order', 'Modules: Collect: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Post order in the collection', 'Modules: Collect: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'number',
					'context'     => 'collection',
					'icon'        => 'sort',
				],
				'in_collection_title' => [
					'title'       => _x( 'Title', 'Modules: Collect: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Override post title in the collection', 'Modules: Collect: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'context'     => 'collection',
				],
				'in_collection_subtitle' => [
					'title'       => _x( 'Subtitle', 'Modules: Collect: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Post subtitle in the collection', 'Modules: Collect: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'context'     => 'collection',
				],
				'in_collection_collaborator' => [
					'title'       => _x( 'Collaborator', 'Modules: Collect: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Post collaborator in the collection', 'Modules: Collect: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'context'     => 'collection',
				],
			],
		];
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'collection_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->post_types_excluded = [ 'attachment', $this->constant( 'collection_cpt' ) ];

		$this->register_taxonomy( 'collection_tax', [
			'show_ui'      => FALSE,
			'hierarchical' => TRUE,
		] );

		$this->register_taxonomy( 'group_tax', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'collection_cpt' );

		if ( $this->get_setting( 'collection_parts', FALSE ) )
			$this->register_taxonomy( 'part_tax', [
				'hierarchical'       => TRUE,
				'show_admin_column'  => TRUE,
				'show_in_quick_edit' => TRUE,
				'show_in_nav_menus'  => TRUE,
			], $this->post_types( 'collection_cpt' ) );

		$this->register_post_type( 'collection_cpt', [
			'hierarchical' => TRUE,
			'rewrite'      => [
				'feeds' => (bool) $this->get_setting( 'posttype_feeds', FALSE ),
				'pages' => (bool) $this->get_setting( 'posttype_pages', FALSE ),
			],
		] );

		$this->register_shortcode( 'collection_shortcode' );
		$this->register_shortcode( 'group_shortcode' );
		$this->register_shortcode( 'poster_shortcode' );

		if ( is_admin() )
			return;

		if ( $this->get_setting( 'insert_poster' ) )
			add_action( 'gnetwork_themes_content_before',
				[ $this, 'content_before' ],
				$this->get_setting( 'insert_priority', -50 )
			);

		$this->filter( 'term_link', 3 );
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, 'collection_cpt' ) )
			$this->_sync_linked( $_REQUEST['post_type'] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'collection_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'wp_insert_post_data', 2, 9, 'menu_order' );
				$this->filter( 'post_updated_messages' );
				$this->filter( 'get_default_comment_status', 3 );

				$this->filter_false( 'geditorial_meta_box_callback', 12 );
				$this->remove_meta_box( $screen->post_type, $screen->post_type, 'parent' );

				add_meta_box( $this->classs( 'main' ),
					$this->get_meta_box_title( 'collection_cpt', FALSE ),
					[ $this, 'do_meta_box_main' ],
					$screen->post_type,
					'side',
					'high'
				);

				add_meta_box( $this->classs( 'list' ),
					$this->get_meta_box_title( 'collection_tax' ),
					[ $this, 'do_meta_box_list' ],
					$screen->post_type,
					'advanced',
					'low'
				);

				$this->_sync_linked( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );
				$this->filter( 'bulk_post_updated_messages', 2 );

				if ( $this->get_setting( 'admin_restrict', FALSE ) ) {
					add_action( 'restrict_manage_posts', [ $this, 'restrict_manage_posts_main_cpt' ], 12, 2 );
					$this->filter( 'parse_query' );
				}

				if ( $this->get_setting( 'admin_ordering', TRUE ) )
					$this->action( 'pre_get_posts' );

				$this->_sync_linked( $screen->post_type );

				$this->_tweaks_taxonomy();
				add_action( 'geditorial_tweaks_column_attr', [ $this, 'main_column_attr' ] );
				add_action( 'geditorial_meta_column_row', [ $this, 'column_row_meta' ], 12, 3 );
			}

		} else if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'post' == $screen->base ) {

				add_meta_box( $this->classs( 'supported' ),
					$this->get_meta_box_title_posttype( 'collection_cpt' ),
					[ $this, 'do_meta_box_supported' ],
					$screen->post_type,
					'side'
				);

				// internal actions:
				add_action( 'geditorial_collect_supported_meta_box', [ $this, 'supported_meta_box' ], 5, 3 );

				// TODO: add a thick-box to list the posts with this collection taxonomy

			} else if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_restrict', FALSE ) )
					add_action( 'restrict_manage_posts', [ $this, 'restrict_manage_posts_supported_cpt' ], 12, 2 );

				$this->_tweaks_taxonomy();
				add_action( 'geditorial_meta_column_row', [ $this, 'column_row_meta' ], 12, 3 );
			}

			add_action( 'save_post', [ $this, 'save_post_supported_cpt' ], 20, 3 );
		}

		if ( Settings::isDashboard( $screen ) )
			$this->filter_module( 'calendar', 'post_row_title', 4, 12 );

		// $size = apply_filters( 'admin_post_thumbnail_size', $size, $thumbnail_id, $post );
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

		register_widget( '\\geminorum\\gEditorial\\Widgets\\Collect\\CollectionPoster' );
	}

	public function meta_init()
	{
		$this->add_post_type_fields( $this->constant( 'collection_cpt' ) );
		$this->add_post_type_fields( $this->constant( 'post_cpt' ) );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'collection_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->constant( 'collection_tax' ) != $taxonomy )
			return $link;

		if ( $post_id = $this->get_linked_post_id( $term, 'collection_cpt', 'collection_tax' ) )
			return get_permalink( $post_id );

		return $link;
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'collection_tax' ) ) ) {

			$term = get_queried_object();

			if ( $post_id = $this->get_linked_post_id( $term, 'collection_cpt', 'collection_tax' ) )
				WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_tax( $this->constant( 'group_tax' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_groups', FALSE ) )
				WordPress::redirect( $redirect, 301 );

		} else if ( is_post_type_archive( $this->constant( 'collection_cpt' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress::redirect( $redirect, 301 );
		}
	}

	public function content_before( $content, $posttypes = NULL )
	{
		if ( ! $this->is_content_insert( 'collection_cpt' ) )
			return;

		ModuleTemplate::postImage( [
			'size' => $this->get_image_size_key( 'collection_cpt', 'medium' ),
			'link' => 'attachment',
		] );
	}

	public function post_updated( $post_ID, $post_after, $post_before )
	{
		if ( ! $this->is_save_post( $post_after, 'collection_cpt' ) )
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

		$the_term = get_term_by( 'slug', $post_before->post_name, $this->constant( 'collection_tax' ) );

		if ( FALSE === $the_term ) {
			$the_term = get_term_by( 'slug', $post_after->post_name, $this->constant( 'collection_tax' ) );
			if ( FALSE === $the_term )
				$term = wp_insert_term( $post_after->post_title, $this->constant( 'collection_tax' ), $args );
			else
				$term = wp_update_term( $the_term->term_id, $this->constant( 'collection_tax' ), $args );
		} else {
			$term = wp_update_term( $the_term->term_id, $this->constant( 'collection_tax' ), $args );
		}

		if ( ! is_wp_error( $term ) )
			$this->set_linked_term( $post_ID, $term['term_id'], 'collection_cpt', 'collection_tax' );

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

		$term = wp_insert_term( $post->post_title, $this->constant( 'collection_tax' ), $args );

		if ( ! is_wp_error( $term ) )
			$this->set_linked_term( $post_ID, $term['term_id'], 'collection_cpt', 'collection_tax' );

		return $post_ID;
	}

	public function wp_trash_post( $post_id )
	{
		$this->do_trash_post( $post_id, 'collection_cpt', 'collection_tax' );
	}

	public function untrash_post( $post_id )
	{
		$this->do_untrash_post( $post_id, 'collection_cpt', 'collection_tax' );
	}

	public function before_delete_post( $post_id )
	{
		$this->do_before_delete_post( $post_id, 'collection_cpt', 'collection_tax' );
	}

	public function save_post_supported_cpt( $post_ID, $post, $update )
	{
		if ( ! $this->is_save_post( $post, $this->post_types() ) )
			return $post_ID;

		$name = $this->classs( $this->constant( 'collection_cpt' ) );

		if ( ! isset( $_POST[$name] ) )
			return $post_ID;

		$terms = [];
		$tax   = $this->constant( 'collection_tax' );

		foreach ( (array) $_POST[$name] as $collection )
			if ( trim( $collection ) && $term = get_term_by( 'slug', $collection, $tax ) )
				$terms[] = intval( $term->term_id );

		wp_set_object_terms( $post_ID, ( count( $terms ) ? $terms : NULL ), $tax, FALSE );

		return $post_ID;
	}

	public function pre_get_posts( &$wp_query )
	{
		if ( $this->constant( 'collection_cpt' ) == $wp_query->get( 'post_type' ) ) {

			if ( $wp_query->is_admin ) {

				if ( ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', 'menu_order' );

				if ( ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', 'DESC' );
			}
		}
	}

	public function restrict_manage_posts_main_cpt( $post_type, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'group_tax' );
	}

	public function restrict_manage_posts_supported_cpt( $post_type, $which )
	{
		$this->do_restrict_manage_posts_posts( 'collection_tax', 'collection_cpt' );
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query, 'group_tax' );
	}

	public function meta_box_cb_group_tax( $post, $box )
	{
		MetaBox::checklistTerms( $post, $box );
	}

	public function meta_box_cb_part_tax( $post, $box )
	{
		MetaBox::checklistTerms( $post, $box );
	}

	public function do_meta_box_supported( $post, $box )
	{
		if ( $this->check_hidden_metabox( 'supported' ) )
			return;

		echo '<div class="geditorial-admin-wrap-metabox -collect">';

		$terms = Taxonomy::getTerms( $this->constant( 'collection_tax' ), $post->ID, TRUE );

		do_action( 'geditorial_collect_supported_meta_box', $post, $box, $terms );

		do_action( 'geditorial_meta_do_meta_box', $post, $box, NULL, 'collection' );

		echo '</div>';
	}

	public function supported_meta_box( $post, $box, $terms )
	{
		$post_type = $this->constant( 'collection_cpt' );
		$dropdowns = $excludes = [];

		foreach ( $terms as $term ) {
			$dropdowns[$term->slug] = MetaBox::dropdownAssocPosts( $post_type, $term->slug, $this->classs() );
			$excludes[] = $term->slug;
		}

		if ( empty( $terms ) || $this->get_setting( 'multiple_instances', FALSE ) )
			$dropdowns[0] = MetaBox::dropdownAssocPosts( $post_type, '', $this->classs(), $excludes );

		$empty = TRUE;

		foreach ( $dropdowns as $term_slug => $dropdown ) {
			if ( $dropdown ) {
				echo $dropdown;
				$empty = FALSE;
			}
		}

		if ( $empty )
			MetaBox::fieldEmptyPostType( $post_type );
	}

	public function do_meta_box_main( $post, $box )
	{
		if ( $this->check_hidden_metabox( 'main' ) )
			return;

		echo '<div class="geditorial-admin-wrap-metabox -collect">';

		$this->actions( 'main_meta_box', $post, $box );

		do_action( 'geditorial_meta_do_meta_box', $post, $box, NULL );

		MetaBox::fieldPostMenuOrder( $post );
		MetaBox::fieldPostParent( $post->post_type, $post );

		echo '</div>';
	}

	public function do_meta_box_list( $post, $box )
	{
		if ( $this->check_hidden_metabox( 'list' ) )
			return;

		echo '<div class="geditorial-admin-wrap-metabox -collect">';

		$term = $this->get_linked_term( $post->ID, 'collection_cpt', 'collection_tax' );

		$this->actions( 'list_meta_box', $post, $box, $term );

		if ( $list = MetaBox::getTermPosts( $this->constant( 'collection_tax' ), $term, [], FALSE ) )
			echo $list;
		else
			HTML::desc( _x( 'No items connected!', 'Modules: Collect', GEDITORIAL_TEXTDOMAIN ), FALSE, '-empty' );

		echo '</div>';
	}

	public function get_assoc_post( $post = NULL, $single = FALSE, $published = TRUE )
	{
		$posts = [];
		$terms = Taxonomy::getTerms( $this->constant( 'collection_tax' ), $post, TRUE );

		foreach ( $terms as $term ) {

			if ( ! $linked = $this->get_linked_post_id( $term, 'collection_cpt', 'collection_tax' ) )
				continue;

			if ( $single )
				return $linked;

			if ( $published && 'publish' != get_post_status( $linked ) )
				continue;

			$posts[$term->term_id] = $linked;
		}

		return count( $posts ) ? $posts : FALSE;
	}

	public function main_column_attr( $post )
	{
		$posts = $this->get_linked_posts( $post->ID, 'collection_cpt', 'collection_tax' );
		$count = count( $posts );

		if ( ! $count )
			return;

		echo '<li class="-row -collect -connected">';

			echo $this->get_column_icon( FALSE, NULL, $this->get_column_title( 'connected', 'collection_cpt' ) );

			$post_types = array_unique( array_map( function( $r ){
				return $r->post_type;
			}, $posts ) );

			$args = [ $this->constant( 'collection_tax' ) => $post->post_name ];

			if ( empty( $this->all_post_types ) )
				$this->all_post_types = PostType::get( 2 );

			echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

			$list = [];

			foreach ( $post_types as $post_type )
				$list[] = HTML::tag( 'a', [
					'href'   => WordPress::getPostTypeEditLink( $post_type, 0, $args ),
					'title'  => _x( 'View the connected list', 'Modules: Collect', GEDITORIAL_TEXTDOMAIN ),
					'target' => '_blank',
				], $this->all_post_types[$post_type] );

			echo Helper::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

		echo '</li>';
	}

	public function display_meta( $value, $key = NULL, $field = [] )
	{
		switch ( $key ) {
			case 'in_collection_order': return Helper::getCounted( $value, _x( 'Order in Collection: %s', 'Modules: Collect: Display', GEDITORIAL_TEXTDOMAIN ) );
		}

		return esc_html( $value );
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'collection_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'collection_cpt', $counts ) );
	}

	public function calendar_post_row_title( $title, $post, $the_day, $calendar_args )
	{
		if ( ! in_array( $post->post_type, $this->post_types() ) )
			return $title;

		if ( ! $collection = $this->get_assoc_post( $post->ID, TRUE ) )
			return $title;

		return $title.' – '.Helper::getPostTitle( $collection );
	}

	public function collection_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::getAssocPosts(
			$this->constant( 'collection_cpt' ),
			$this->constant( 'collection_tax' ),
			array_merge( [
				'posttypes'   => $this->post_types(),
				'order_cb'    => NULL, // NULL for default ordering by meta
				'orderby'     => 'order', // order by meta
				'order_order' => 'in_collection_order', // meta field for ordering
			], (array) $atts ),
			$content,
			$this->constant( 'collection_shortcode' )
		);
	}

	public function group_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::getTermPosts(
			$this->constant( 'collection_cpt' ),
			$this->constant( 'group_tax' ),
			$atts,
			$content,
			$this->constant( 'group_shortcode' )
		);
	}

	public function poster_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = [
			'size' => $this->get_image_size_key( 'collection_cpt', 'medium' ),
			'type' => $this->constant( 'collection_cpt' ),
			'echo' => FALSE,
		];

		if ( is_singular( $args['type'] ) )
			$args['id'] = NULL;

		else if ( is_singular() )
			$args['id'] = 'assoc';

		if ( ! $html = ModuleTemplate::postImage( array_merge( $args, (array) $atts ) ) )
			return $content;

		return ShortCode::wrap( $html,
			$this->constant( 'poster_shortcode' ),
			array_merge( [ 'wrap' => TRUE ], (array) $atts )
		);
	}

	public function tools_sub( $uri, $sub )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', 'tools', FALSE, FALSE );

			HTML::h3( _x( 'Collect Tools', 'Modules: Collect', GEDITORIAL_TEXTDOMAIN ) );

			echo '<table class="form-table">';
			echo '<tr><th scope="row">'._x( 'From Terms', 'Modules: Collect', GEDITORIAL_TEXTDOMAIN ).'</th><td>';
			echo $this->wrap_open_buttons( '-tools' );

			Settings::submitButton( 'collection_tax_check',
				_x( 'Check Terms', 'Modules: Collect: Setting Button', GEDITORIAL_TEXTDOMAIN ), TRUE );

			Settings::submitButton( 'collection_post_create',
				_x( 'Create Collection Posts', 'Modules: Collect: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

			Settings::submitButton( 'collection_post_connect',
				_x( 'Re-Connect Posts', 'Modules: Collect: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

			Settings::submitButton( 'collection_store_order',
				_x( 'Store Orders', 'Modules: Collect: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

			Settings::submitButton( 'collection_tax_delete',
				_x( 'Delete Terms', 'Modules: Collect: Setting Button', GEDITORIAL_TEXTDOMAIN ), 'danger', TRUE );


			echo '</p>';

			if ( ! empty( $_POST ) && isset( $_POST['collection_tax_check'] ) ) {
				echo '<br />';

				HTML::tableList( [
					'_cb'     => 'term_id',
					'term_id' => Helper::tableColumnTermID(),
					'name'    => Helper::tableColumnTermName(),
					'linked'   => [
						'title'    => _x( 'Linked Collection Post', 'Modules: Collect: Table Column', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column, $index ){

							if ( $post_id = $this->get_linked_post_id( $row, 'collection_cpt', 'collection_tax', FALSE ) )
								return Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';

							return '&mdash;';
						},
					],
					'slugged'   => [
						'title' => _x( 'Same Slug Collection Post', 'Modules: Collect: Table Column', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column, $index ){

							if ( $post_id = PostType::getIDbySlug( $row->slug, $this->constant( 'collection_cpt' ) ) )
								return Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';

							return '&mdash;';
						},
					],
					'count' => [
						'title'    => _x( 'Count', 'Modules: Collect: Table Column', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column, $index ){
							if ( $post_id = PostType::getIDbySlug( $row->slug, $this->constant( 'collection_cpt' ) ) )
								return Number::format( $this->get_linked_posts( $post_id, 'collection_cpt', 'collection_tax', TRUE ) );
							return Number::format( $row->count );
						},
					],
					'description' => Helper::tableColumnTermDesc(),
				], Taxonomy::getTerms( $this->constant( 'collection_tax' ), FALSE, TRUE ), [
					'empty' => HTML::warning( _x( 'No Terms Found!', 'Modules: Collect: Table Empty', GEDITORIAL_TEXTDOMAIN ), FALSE ),
				] );
			}

			HTML::desc( _x( 'Check for collection terms and create corresponding collection posts.', 'Modules: Collect', GEDITORIAL_TEXTDOMAIN ) );

			echo '</td></tr>';
			echo '</table>';

		$this->settings_form_after( $uri, $sub );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( isset( $_POST['_cb'] )
					&& isset( $_POST['collection_post_create'] ) ) {

					$terms = Taxonomy::getTerms( $this->constant( 'collection_tax' ), FALSE, TRUE );
					$posts = [];

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! isset( $terms[$term_id] ) )
							continue;

						$post_id = PostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( 'collection_cpt' ) ) ;

						if ( FALSE !== $post_id )
							continue;

						$posts[] = WordPress::newPostFromTerm(
							$terms[$term_id],
							$this->constant( 'collection_tax' ),
							$this->constant( 'collection_cpt' ),
							Helper::getEditorialUserID( TRUE )
						);
					}

					WordPress::redirectReferer( [
						'message' => 'created',
						'count'   => count( $posts ),
					] );

				} else if ( isset( $_POST['_cb'] )
					&& ( isset( $_POST['collection_store_order'] )
						|| isset( $_POST['collection_store_start'] ) ) ) {

					$meta_key = isset( $_POST['collection_store_order'] ) ? 'in_collection_order' : 'in_collection_page_start';
					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {
						foreach ( $this->get_linked_posts( NULL, 'collection_cpt', 'collection_tax', FALSE, $term_id ) as $post ) {

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
					&& isset( $_POST['collection_post_connect'] ) ) {

					$terms = Taxonomy::getTerms( $this->constant( 'collection_tax' ), FALSE, TRUE );
					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! isset( $terms[$term_id] ) )
							continue;

						$post_id = PostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( 'collection_cpt' ) ) ;

						if ( FALSE === $post_id )
							continue;

						if ( $this->set_linked_term( $post_id, $terms[$term_id], 'collection_cpt', 'collection_tax' ) )
							$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'updated',
						'count'   => $count,
					] );

				} else if ( isset( $_POST['_cb'] )
					&& isset( $_POST['collection_tax_delete'] ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( $this->rev_linked_term( NULL, $term_id, 'collection_cpt', 'collection_tax' ) ) {

							$deleted = wp_delete_term( $term_id, $this->constant( 'collection_tax' ) );

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
