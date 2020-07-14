<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Scripts;
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

	protected $partials = [ 'Templates' ];

	public static function module()
	{
		return [
			'name'  => 'magazine',
			'title' => _x( 'Magazine', 'Modules: Magazine', 'geditorial' ),
			'desc'  => _x( 'Magazine Issue Management', 'Modules: Magazine', 'geditorial' ),
			'icon'  => 'book',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'multiple_instances',
				[
					'field'       => 'issue_sections',
					'title'       => _x( 'Issue Sections', 'Setting Title', 'geditorial-magazine' ),
					'description' => _x( 'Section taxonomy for issues and supported posttypes.', 'Setting Description', 'geditorial-magazine' ),
				],
				'comment_status',
			],
			'_editlist' => [
				'admin_ordering',
				'admin_restrict',
			],
			'_frontend' => [
				'insert_cover',
				'insert_priority',
				'posttype_feeds',
				'posttype_pages',
				'redirect_archives',
				[
					'field'       => 'redirect_spans',
					'type'        => 'url',
					'title'       => _x( 'Redirect Spans', 'Setting Title', 'geditorial-magazine' ),
					'description' => _x( 'Redirects all span archives to a URL.', 'Setting Description', 'geditorial-magazine' ),
					'placeholder' => 'http://example.com/archives/',
				],
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'widget_support',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'issue_cpt', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'issue_cpt'         => 'issue',
			'issue_cpt_archive' => 'issues',
			'issue_tax'         => 'issues',
			'span_tax'          => 'issue_span',
			'span_tax_slug'     => 'issue-span',
			'section_tax'       => 'issue_section',
			'section_tax_slug'  => 'issue-section',
			'issue_shortcode'   => 'issue',
			'span_shortcode'    => 'issue-span',
			'cover_shortcode'   => 'issue-cover',
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
		$strings = [
			'noops' => [
				'issue_cpt'   => _nx_noop( 'Issue', 'Issues', 'Noop', 'geditorial-magazine' ),
				'issue_tax'   => _nx_noop( 'Issue', 'Issues', 'Noop', 'geditorial-magazine' ),
				'span_tax'    => _nx_noop( 'Span', 'Spans', 'Noop', 'geditorial-magazine' ),
				'section_tax' => _nx_noop( 'Section', 'Sections', 'Noop', 'geditorial-magazine' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'issue_cpt' => [
				'featured' => _x( 'Cover Image', 'Issue CPT: Featured', 'geditorial-magazine' ),
			],
			'issue_tax' => [
				'meta_box_title' => _x( 'In This Issue', 'MetaBox Title', 'geditorial-magazine' ),
			],
			'span_tax' => [
				'meta_box_title'      => _x( 'Spans', 'MetaBox Title', 'geditorial-magazine' ),
				'tweaks_column_title' => _x( 'Issue Spans', 'Column Title', 'geditorial-magazine' ),
			],
			'section_tax' => [
				'meta_box_title'      => _x( 'Sections', 'MetaBox Title', 'geditorial-magazine' ),
				'tweaks_column_title' => _x( 'Issue Sections', 'Column Title', 'geditorial-magazine' ),
			],
			'meta_box_title'         => _x( 'The Issue', 'MetaBox Title', 'geditorial-magazine' ),
			'tweaks_column_title'    => _x( 'Issues', 'Column Title', 'geditorial-magazine' ),
			'connected_column_title' => _x( 'Connected Items', 'Column Title', 'geditorial-magazine' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			$this->constant( 'issue_cpt' ) => [
				'ot' => [ 'type' => 'title_before' ],
				'st' => [ 'type' => 'title_after' ],

				'issue_number_line' => [
					'title'       => _x( 'Number Line', 'Field Title', 'geditorial-magazine' ),
					'description' => _x( 'The issue number line', 'Field Description', 'geditorial-magazine' ),
					'icon'        => 'menu',
				],
				'issue_total_pages' => [
					'title'       => _x( 'Total Pages', 'Field Title', 'geditorial-magazine' ),
					'description' => _x( 'The issue total pages', 'Field Description', 'geditorial-magazine' ),
					'icon'        => 'admin-page',
				],
			],
			'post' => [
				'in_issue_order' => [
					'title'       => _x( 'Order', 'Field Title', 'geditorial-magazine' ),
					'description' => _x( 'Post order in issue list', 'Field Description', 'geditorial-magazine' ),
					'type'        => 'number',
					'context'     => 'issue',
					'icon'        => 'sort',
				],
				'in_issue_page_start' => [
					'title'       => _x( 'Page Start', 'Field Title', 'geditorial-magazine' ),
					'description' => _x( 'Post start page on issue (printed)', 'Field Description', 'geditorial-magazine' ),
					'type'        => 'number',
					'context'     => 'issue',
					'icon'        => 'media-default',
				],
				'in_issue_pages' => [
					'title'       => _x( 'Total Pages', 'Field Title', 'geditorial-magazine' ),
					'description' => _x( 'Post total pages on issue (printed)', 'Field Description', 'geditorial-magazine' ),
					'context'     => 'issue',
					'icon'        => 'admin-page',
				],
			],
		];
	}

	protected function posttypes_excluded()
	{
		return Settings::posttypesExcluded( $this->constant( 'issue_cpt' ) );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'issue_cpt' );
	}

	public function init()
	{
		parent::init();

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
			], $this->posttypes( 'issue_cpt' ) );

		$this->register_posttype( 'issue_cpt', [
			'hierarchical' => TRUE,
			'rewrite'      => [
				'feeds' => (bool) $this->get_setting( 'posttype_feeds', FALSE ),
				'pages' => (bool) $this->get_setting( 'posttype_pages', FALSE ),
			],
		] );

		$this->register_shortcode( 'issue_shortcode' );
		$this->register_shortcode( 'span_shortcode' );
		$this->register_shortcode( 'cover_shortcode' );

		if ( is_admin() )
			return;

		$this->filter( 'term_link', 3 );
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

		} else if ( is_singular( $this->constant( 'issue_cpt' ) ) ) {

			if ( $this->get_setting( 'insert_cover' ) )
				add_action( $this->base.'_content_before',
					[ $this, 'insert_cover' ],
					$this->get_setting( 'insert_priority', -50 )
				);
		}
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, 'issue_cpt' ) )
			$this->_sync_linked( $_REQUEST['post_type'] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'issue_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'wp_insert_post_data', 2, 9, 'menu_order' );
				$this->filter( 'post_updated_messages' );
				$this->filter( 'get_default_comment_status', 3 );

				$this->filter_false( 'geditorial_meta_box_callback', 12 );
				$this->class_metabox( $screen, 'main' );

				remove_meta_box( 'pageparentdiv', $screen, 'side' );
				add_meta_box( $this->classs( 'main' ),
					$this->get_meta_box_title( 'issue_cpt', FALSE ),
					[ $this, 'render_metabox_main' ],
					$screen,
					'side',
					'high'
				);

				$this->class_metabox( $screen, 'list' );

				add_meta_box( $this->classs( 'list' ),
					$this->get_meta_box_title( 'issue_tax' ),
					[ $this, 'render_metabox_list' ],
					$screen,
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

				$this->action_module( 'meta', 'column_row', 3, 12 );
				$this->action_module( 'tweaks', 'column_attr' );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				$this->class_metabox( $screen, 'supported' );

				add_meta_box( $this->classs( 'supported' ),
					$this->get_meta_box_title_posttype( 'issue_cpt' ),
					[ $this, 'render_metabox_supported' ],
					$screen,
					'side'
				);

				add_action( $this->hook( 'render_metabox_supported' ), [ $this, 'render_metabox' ], 10, 4 );

			} else if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_restrict', FALSE ) )
					add_action( 'restrict_manage_posts', [ $this, 'restrict_manage_posts_supported_cpt' ], 12, 2 );

				$this->action_module( 'meta', 'column_row', 3, 12 );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}

			add_action( 'save_post_'.$screen->post_type, [ $this, 'store_metabox' ], 20, 3 );
		}

		if ( Settings::isDashboard( $screen ) )
			$this->filter_module( 'calendar', 'post_row_title', 4, 12 );
	}

	private function _sync_linked( $posttype )
	{
		$this->action( 'save_post', 3, 20 );
		$this->action( 'post_updated', 3, 20 );

		$this->action( 'wp_trash_post' );
		$this->action( 'untrash_post' );
		$this->action( 'before_delete_post' );
	}

	public function widgets_init()
	{
		$this->require_code( 'Widgets/Issue-Cover' );

		register_widget( '\\geminorum\\gEditorial\\Magazine\\Widgets\\IssueCover' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'issue_cpt' ) );
		$this->add_posttype_fields( $this->constant( 'post_cpt' ) );
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

	public function insert_cover( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		ModuleTemplate::postImage( [
			'size' => $this->get_image_size_key( 'issue_cpt', 'medium' ),
			'link' => 'attachment',
		] );
	}

	public function post_updated( $post_id, $post_after, $post_before )
	{
		if ( ! $this->is_save_post( $post_after, 'issue_cpt' ) )
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
			$this->set_linked_term( $post_id, $term['term_id'], 'issue_cpt', 'issue_tax' );
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

		$term = wp_insert_term( $post->post_title, $this->constant( 'issue_tax' ), $args );

		if ( ! is_wp_error( $term ) )
			$this->set_linked_term( $post_id, $term['term_id'], 'issue_cpt', 'issue_tax' );
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

	public function store_metabox( $post_id, $post, $update, $context = 'main' )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		$name = $this->classs( $this->constant( 'issue_cpt' ) );

		if ( ! isset( $_POST[$name] ) )
			return;

		$terms = [];
		$tax   = $this->constant( 'issue_tax' );

		foreach ( (array) $_POST[$name] as $issue )
			if ( trim( $issue ) && $term = get_term_by( 'slug', $issue, $tax ) )
				$terms[] = intval( $term->term_id );

		wp_set_object_terms( $post_id, ( count( $terms ) ? $terms : NULL ), $tax, FALSE );
	}

	public function pre_get_posts( &$wp_query )
	{
		if ( $this->constant( 'issue_cpt' ) == $wp_query->get( 'post_type' ) ) {

			if ( $wp_query->is_admin ) {

				if ( ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', 'menu_order' );

				if ( ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', 'DESC' );
			}
		}
	}

	public function restrict_manage_posts_main_cpt( $posttype, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'span_tax' );
	}

	public function restrict_manage_posts_supported_cpt( $posttype, $which )
	{
		$this->do_restrict_manage_posts_posts( 'issue_tax', 'issue_cpt' );
	}

	public function parse_query( &$query )
	{
		$this->do_parse_query_taxes( $query, 'span_tax' );
	}

	public function meta_box_cb_span_tax( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function meta_box_cb_section_tax( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function render_metabox_supported( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

		if ( ! Taxonomy::hasTerms( $this->constant( 'issue_tax' ) ) ) {

			MetaBox::fieldEmptyPostType( $this->constant( 'issue_cpt' ) );

		} else {

			$this->actions( 'render_metabox_supported', $post, $box, NULL, 'main' );

			do_action( 'geditorial_meta_render_metabox', $post, $box, NULL, 'issue' );
		}

		echo '</div>';
	}

	public function render_metabox( $post, $box, $fields = NULL, $context = 'main' )
	{
		$dropdowns = $excludes = [];
		$posttype  = $this->constant( 'issue_cpt' );
		$terms     = Taxonomy::getTerms( $this->constant( 'issue_tax' ), $post->ID, TRUE );

		foreach ( $terms as $term ) {
			$dropdowns[$term->slug] = MetaBox::dropdownAssocPosts( $posttype, $term->slug, $this->classs() );
			$excludes[] = $term->slug;
		}

		if ( empty( $dropdowns ) || $this->get_setting( 'multiple_instances' ) )
			$dropdowns[0] = MetaBox::dropdownAssocPosts( $posttype, '0', $this->classs(), $excludes );

		foreach ( $dropdowns as $dropdown )
			if ( $dropdown )
				echo $dropdown;
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
			$this->actions( 'render_metabox_list', $post, $box, NULL, 'main' );

			$term = $this->get_linked_term( $post->ID, 'issue_cpt', 'issue_tax' );

			if ( $list = MetaBox::getTermPosts( $this->constant( 'issue_tax' ), $term ) )
				echo $list;

			else
				HTML::desc( _x( 'No items connected!', 'Message', 'geditorial-magazine' ), FALSE, '-empty' );

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

	public function tweaks_column_attr( $post )
	{
		$posts = $this->get_linked_posts( $post->ID, 'issue_cpt', 'issue_tax' );
		$count = count( $posts );

		if ( ! $count )
			return;

		echo '<li class="-row -magazine -connected">';

			echo $this->get_column_icon( FALSE, NULL, $this->get_column_title( 'connected', 'issue_cpt' ) );

			$posttypes = array_unique( array_map( function( $r ){
				return $r->post_type;
			}, $posts ) );

			$args = [ $this->constant( 'issue_tax' ) => $post->post_name ];

			if ( empty( $this->cache_posttypes ) )
				$this->cache_posttypes = PostType::get( 2 );

			echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

			$list = [];

			foreach ( $posttypes as $posttype )
				$list[] = HTML::tag( 'a', [
					'href'   => WordPress::getPostTypeEditLink( $posttype, 0, $args ),
					'title'  => _x( 'View the connected list', 'Title Attr', 'geditorial-magazine' ),
					'target' => '_blank',
				], $this->cache_posttypes[$posttype] );

			echo Helper::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

		echo '</li>';
	}

	public function display_meta( $value, $key = NULL, $field = [] )
	{
		switch ( $key ) {
			/* translators: %s: order */
			case 'in_issue_order'      : return Helper::getCounted( $value, _x( 'Order in Issue: %s', 'Display', 'geditorial-magazine' ) );
			/* translators: %s: page */
			case 'in_issue_page_start' : return Helper::getCounted( $value, _x( 'Page in Issue: %s', 'Display', 'geditorial-magazine' ) );
			/* translators: %s: total count */
			case 'in_issue_pages'      : return Helper::getCounted( $value, _x( 'Total Pages: %s', 'Display', 'geditorial-magazine' ) );
		}

		return HTML::escape( $value );
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'issue_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'issue_cpt', $counts ) );
	}

	public function calendar_post_row_title( $title, $post, $the_day, $calendar_args )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $title;

		if ( ! $issue = $this->get_assoc_post( $post->ID, TRUE ) )
			return $title;

		return $title.' – '.Helper::getPostTitle( $issue );
	}

	// TODO: migrate to `Shortcode::listPosts( 'associated' );`
	public function issue_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::getAssocPosts(
			$this->constant( 'issue_cpt' ),
			$this->constant( 'issue_tax' ),
			array_merge( [
				'posttypes'   => $this->posttypes(),
				'order_cb'    => NULL, // NULL for default ordering by meta
				'orderby'     => 'order', // order by meta
				'order_start' => 'in_issue_page_start', // meta field for ordering
				'order_order' => 'in_issue_order', // meta field for ordering
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

	public function cover_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = [
			'size' => $this->get_image_size_key( 'issue_cpt', 'medium' ),
			'type' => $this->constant( 'issue_cpt' ),
			'echo' => FALSE,
		];

		if ( is_singular( $args['type'] ) )
			$args['id'] = NULL;

		else if ( is_singular() )
			$args['id'] = 'assoc';

		if ( ! $html = ModuleTemplate::postImage( array_merge( $args, (array) $atts ) ) )
			return $content;

		return ShortCode::wrap( $html,
			$this->constant( 'cover_shortcode' ),
			array_merge( [ 'wrap' => TRUE ], (array) $atts )
		);
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( $this->current_action( 'issue_post_create', TRUE ) ) {

					$terms = Taxonomy::getTerms( $this->constant( 'issue_tax' ), FALSE, TRUE );
					$posts = [];

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! isset( $terms[$term_id] ) )
							continue;

						$post_id = PostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( 'issue_cpt' ) );

						if ( FALSE !== $post_id )
							continue;

						$posts[] = WordPress::newPostFromTerm(
							$terms[$term_id],
							$this->constant( 'issue_tax' ),
							$this->constant( 'issue_cpt' ),
							gEditorial()->user( TRUE )
						);
					}

					WordPress::redirectReferer( [
						'message' => 'created',
						'count'   => count( $posts ),
					] );

				} else if ( $this->current_action( 'issue_resync_images', TRUE ) ) {

					$meta_key = $this->constant( 'metakey_term_image', 'image' );
					$count    = 0;

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! $post_id = $this->get_linked_post_id( $term_id, 'issue_cpt', 'issue_tax' ) )
							continue;

						if ( $thumbnail = get_post_thumbnail_id( $post_id ) )
							update_term_meta( $term_id, $meta_key, $thumbnail );

						else
							delete_term_meta( $term_id, $meta_key );

						$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'synced',
						'count'   => $count,
					] );

				} else if ( $this->current_action( 'issue_resync_desc', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! $post_id = $this->get_linked_post_id( $term_id, 'issue_cpt', 'issue_tax' ) )
							continue;

						if ( ! $post = get_post( $post_id ) )
							continue;

						if ( wp_update_term( $term_id, $this->constant( 'issue_tax' ), [ 'description' => $post->post_excerpt ] ) )
							$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'synced',
						'count'   => $count,
					] );

				} else if ( $this->current_action( 'issue_store_order', TRUE )
					|| $this->current_action( 'issue_store_start', TRUE ) ) {

					if ( ! gEditorial()->enabled( 'meta' ) )
						WordPress::redirectReferer( 'wrong' );

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

				} else if ( $this->current_action( 'issue_empty_desc', TRUE ) ) {

					$args  = [ 'description' => '' ];
					$count = 0;

					foreach ( $_POST['_cb'] as $term_id )
						if ( wp_update_term( $term_id, $this->constant( 'issue_tax' ), $args ) )
							$count++;

					WordPress::redirectReferer( [
						'message' => 'purged',
						'count'   => $count,
					] );

				} else if ( $this->current_action( 'issue_post_connect', TRUE ) ) {

					$terms = Taxonomy::getTerms( $this->constant( 'issue_tax' ), FALSE, TRUE );
					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! isset( $terms[$term_id] ) )
							continue;

						$post_id = PostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( 'issue_cpt' ) );

						if ( FALSE === $post_id )
							continue;

						if ( $this->set_linked_term( $post_id, $terms[$term_id], 'issue_cpt', 'issue_tax' ) )
							$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'updated',
						'count'   => $count,
					] );

				} else if ( $this->current_action( 'issue_tax_delete', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( $this->remove_linked_term( NULL, $term_id, 'issue_cpt', 'issue_tax' ) ) {

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

		Scripts::enqueueThickBox();
	}

	protected function render_tools_html( $uri, $sub )
	{
		HTML::tableList( [
			'_cb'     => 'term_id',
			// 'term_id' => Helper::tableColumnTermID(),
			'name'    => Helper::tableColumnTermName(),
			'linked'  => [
				'title'    => _x( 'Linked Issue Post', 'Table Column', 'geditorial-magazine' ),
				'callback' => function( $value, $row, $column, $index ){

					if ( $post_id = $this->get_linked_post_id( $row, 'issue_cpt', 'issue_tax', FALSE ) )
						return Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';

					return Helper::htmlEmpty();
				},
			],
			'slugged' => [
				'title'    => _x( 'Same Slug Issue Post', 'Table Column', 'geditorial-magazine' ),
				'callback' => function( $value, $row, $column, $index ){

					if ( $post_id = PostType::getIDbySlug( $row->slug, $this->constant( 'issue_cpt' ) ) )
						return Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';

					return Helper::htmlEmpty();
				},
			],
			'count' => [
				'title'    => _x( 'Count', 'Table Column', 'geditorial-magazine' ),
				'callback' => function( $value, $row, $column, $index ){

					if ( $post_id = PostType::getIDbySlug( $row->slug, $this->constant( 'issue_cpt' ) ) )
						return Number::format( $this->get_linked_posts( $post_id, 'issue_cpt', 'issue_tax', TRUE ) );

					return Number::format( $row->count );
				},
			],
			'description' => [
				'title'    => _x( 'Desc. / Exce.', 'Table Column', 'geditorial-magazine' ),
				'class'    => 'html-column',
				'callback' => function( $value, $row, $column, $index ){

					if ( empty( $row->description ) )
						$html = Helper::htmlEmpty();
					else
						$html = Helper::prepDescription( $row->description );

					if ( $post_id = $this->get_linked_post_id( $row, 'issue_cpt', 'issue_tax', FALSE ) ) {

						$html.= '<hr />';

						if ( ! $post = get_post( $post_id ) )
							return $html.gEditorial()->na();

						if ( empty( $post->post_excerpt ) )
							$html.= Helper::htmlEmpty();
						else
							$html.= Helper::prepDescription( $post->post_excerpt );
					}

					return $html;
				},
			],
			'thumb_image' => [
				'title'    => _x( 'Thumbnail', 'Table Column', 'geditorial-magazine' ),
				'class'    => 'image-column',
				'callback' => function( $value, $row, $column, $index ){
					$html = '';

					if ( $post_id = $this->get_linked_post_id( $row, 'issue_cpt', 'issue_tax', FALSE ) )
						$html = PostType::htmlFeaturedImage( $post_id, [ 45, 72 ] );

					return $html ?: Helper::htmlEmpty();
				},
			],
			'term_image' => [
				'title'    => _x( 'Image', 'Table Column', 'geditorial-magazine' ),
				'class'    => 'image-column',
				'callback' => function( $value, $row, $column, $index ){
					$html = Taxonomy::htmlFeaturedImage( $row->term_id, [ 45, 72 ] );
					return $html ?: Helper::htmlEmpty();
				},
			],
		], Taxonomy::getTerms( $this->constant( 'issue_tax' ), FALSE, TRUE ), [
			'title' => HTML::tag( 'h3', _x( 'Magazine Tools', 'Header', 'geditorial-magazine' ) ),
			'empty' => _x( 'No Terms Found!', 'Message', 'geditorial-magazine' ),
			'after' => [ $this, 'table_list_after' ],
		] );
	}

	public function table_list_after( $columns, $data, $args )
	{
		HTML::desc( _x( 'Check for issue terms and create corresponding issue posts.', 'Message', 'geditorial-magazine' ) );
		echo $this->wrap_open_buttons( '-tools' );

		Settings::submitButton( 'issue_post_create',
			_x( 'Create Issue Posts', 'Button', 'geditorial-magazine' ) );

		Settings::submitButton( 'issue_post_connect',
			_x( 'Re-Connect Posts', 'Button', 'geditorial-magazine' ) );

		Settings::submitButton( 'issue_resync_images',
			_x( 'Sync Images', 'Button', 'geditorial-magazine' ) );

		Settings::submitButton( 'issue_resync_desc',
			_x( 'Sync Descriptions', 'Button', 'geditorial-magazine' ) );

		Settings::submitButton( 'issue_store_order',
			_x( 'Store Orders', 'Button', 'geditorial-magazine' ) );

		Settings::submitButton( 'issue_empty_desc',
			_x( 'Empty Term Descriptions', 'Button', 'geditorial-magazine' ), 'danger', TRUE );

		Settings::submitButton( 'issue_tax_delete',
			_x( 'Delete Terms', 'Button', 'geditorial-magazine' ), 'danger', TRUE );

		echo '</p>';
	}
}
