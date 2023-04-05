<?php namespace geminorum\gEditorial\Modules\Dossier;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\Strings;
use geminorum\gEditorial\WordPress\Taxonomy;

class Dossier extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'dossier',
			'title' => _x( 'Dossier', 'Modules: Dossier', 'geditorial' ),
			'desc'  => _x( 'Collection of Contents', 'Modules: Dossier', 'geditorial' ),
			'icon'  => 'portfolio',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'multiple_instances',
				'paired_force_parents',
				[
					'field'       => 'subterms_support',
					'title'       => _x( 'Dossier Sections', 'Settings', 'geditorial-dossier' ),
					'description' => _x( 'Section taxonomy for the dossiers and supported post-types.', 'Settings', 'geditorial-dossier' ),
				],
				'quick_newpost',
				'comment_status',
			],
			'_editlist' => [
				'admin_ordering',
			],
			'_frontend' => [
				'insert_cover',
				'insert_priority',
				'posttype_feeds',
				'posttype_pages',
				[
					'field'       => 'redirect_archives',
					'type'        => 'url',
					'title'       => _x( 'Redirect Archives', 'Settings', 'geditorial-dossier' ),
					'description' => _x( 'Redirects dossier archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-dossier' ),
					'placeholder' => URL::home( 'archives' ),
				],
				[
					'field'       => 'redirect_spans',
					'type'        => 'url',
					'title'       => _x( 'Redirect Spans', 'Settings', 'geditorial-dossier' ),
					'description' => _x( 'Redirects all span archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-dossier' ),
					'placeholder' => URL::home( 'archives' ),
				],
			],
			'_content' => [
				'archive_title',
				'archive_content',
				'archive_template',
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'dossier_posttype', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'dossier_posttype' => 'dossier',
			'dossier_paired'   => 'dossiers',
			'span_taxonomy'    => 'dossier_span',
			'section_taxonomy' => 'dossier_section',

			'dossier_shortcode' => 'dossier',
			'span_shortcode'    => 'dossier-span',
			'cover_shortcode'   => 'dossier-cover',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'dossier_paired'   => NULL,
				'span_taxonomy'    => 'backup',
				'section_taxonomy' => 'category',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'dossier_posttype' => _n_noop( 'Dossier', 'Dossiers', 'geditorial-dossier' ),
				'dossier_paired'   => _n_noop( 'Dossier', 'Dossiers', 'geditorial-dossier' ),
				'span_taxonomy'    => _n_noop( 'Span', 'Spans', 'geditorial-dossier' ),
				'section_taxonomy' => _n_noop( 'Section', 'Sections', 'geditorial-dossier' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'dossier_posttype' => [
				'featured'         => _x( 'Cover Image', 'Posttype Featured', 'geditorial-dossier' ),
				'show_option_none' => _x( '&ndash; Select Dossier &ndash;', 'Select Option None', 'geditorial-dossier' ),
			],
			'dossier_paired' => [
				'meta_box_title' => _x( 'In This Dossier', 'MetaBox Title', 'geditorial-dossier' ),
			],
			'span_taxonomy' => [
				'meta_box_title'      => _x( 'Spans', 'MetaBox Title', 'geditorial-dossier' ),
				'tweaks_column_title' => _x( 'Dossier Spans', 'Column Title', 'geditorial-dossier' ),
			],
			'section_taxonomy' => [
				'meta_box_title'      => _x( 'Sections', 'MetaBox Title', 'geditorial-dossier' ),
				'tweaks_column_title' => _x( 'Dossier Sections', 'Column Title', 'geditorial-dossier' ),
				'show_option_none'    => _x( '&ndash; Select Section &ndash;', 'Select Option None', 'geditorial-dossier' ),
			],
			'meta_box_title'         => _x( 'The Dossier', 'MetaBox Title', 'geditorial-dossier' ),
			'tweaks_column_title'    => _x( 'Dossiers', 'Column Title', 'geditorial-dossier' ),
			'connected_column_title' => _x( 'Connected Items', 'Column Title', 'geditorial-dossier' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			$this->constant( 'dossier_posttype' ) => [
				'over_title' => [ 'type' => 'title_before' ],
				'sub_title'  => [ 'type' => 'title_after' ],

				'number_line' => [
					'title'       => _x( 'Number Line', 'Field Title', 'geditorial-dossier' ),
					'description' => _x( 'The dossier number line', 'Field Description', 'geditorial-dossier' ),
					'icon'        => 'menu',
				],

				'highlight'    => [ 'type' => 'note' ],
				'source_title' => [ 'type' => 'text' ],
				'source_url'   => [ 'type' => 'link' ],
				'action_title' => [ 'type' => 'text' ],
				'action_url'   => [ 'type' => 'link' ],
			],
			'_supported' => [
				'in_dossier_order' => [
					'title'       => _x( 'Order', 'Field Title', 'geditorial-dossier' ),
					'description' => _x( 'Post order in dossier list', 'Field Description', 'geditorial-dossier' ),
					'type'        => 'number',
					'context'     => 'pairedbox_dossier',
					'icon'        => 'sort',
					'order'       => 400,
				],
			],
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'dossier_posttype' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'span_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => '__checklist_reverse_terms_callback',
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'dossier_posttype' );

		$this->paired_register_objects( 'dossier_posttype', 'dossier_paired', 'section_taxonomy' );

		$this->register_shortcode( 'dossier_shortcode' );
		$this->register_shortcode( 'span_shortcode' );
		$this->register_shortcode( 'cover_shortcode' );

		if ( is_admin() )
			return;

		$this->filter( 'term_link', 3 );
	}

	public function init_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'dossier_posttype' ) )
			$this->_hook_paired_to( $posttype );
	}

	public function setup_restapi()
	{
		$this->_hook_paired_to( $this->constant( 'dossier_posttype' ) );
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'section_taxonomy' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'dossier_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'wp_insert_post_data', 2, 9, 'menu_order' );
				$this->filter( 'post_updated_messages' );
				$this->filter( 'get_default_comment_status', 3 );

				$this->filter_false_module( 'meta', 'mainbox_callback', 12 );
				$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
				$this->filter_false_module( 'tweaks', 'metabox_parent' );
				remove_meta_box( 'pageparentdiv', $screen, 'side' );

				$this->class_metabox( $screen, 'mainbox' );
				add_meta_box( $this->classs( 'mainbox' ),
					$this->get_meta_box_title( 'dossier_posttype', FALSE ),
					[ $this, 'render_mainbox_metabox' ],
					$screen,
					'side',
					'high'
				);

				$this->class_metabox( $screen, 'listbox' );
				add_meta_box( $this->classs( 'listbox' ),
					$this->get_meta_box_title_taxonomy( 'dossier_paired', $screen->post_type, FALSE ),
					[ $this, 'render_listbox_metabox' ],
					$screen,
					'advanced',
					'low'
				);

				$this->_hook_paired_to( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );
				$this->filter( 'bulk_post_updated_messages', 2 );

				$this->_hook_screen_restrict_taxonomies();

				if ( $this->get_setting( 'admin_ordering', TRUE ) )
					$this->action( 'pre_get_posts' );

				$this->action_module( 'meta', 'column_row', 3 );
				$this->action_module( 'tweaks', 'column_attr' );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );

				$this->_hook_paired_to( $screen->post_type );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit-tags' == $screen->base ) {

				$this->_hook_paired_taxonomy_bulk_actions( $screen->post_type, $screen->taxonomy );

			} else if ( 'post' == $screen->base ) {

				if ( $subterms )
					remove_meta_box( $subterms.'div', $screen->post_type, 'side' );

				$this->class_metabox( $screen, 'pairedbox' );
				add_meta_box( $this->classs( 'pairedbox' ),
					$this->get_meta_box_title_posttype( 'dossier_posttype' ),
					[ $this, 'render_pairedbox_metabox' ],
					$screen,
					'side'
				);

				add_action( $this->hook( 'render_pairedbox_metabox' ), [ $this, 'render_metabox' ], 10, 4 );

				$this->_hook_store_metabox( $screen->post_type );

				if ( $this->get_setting( 'quick_newpost' ) )
					Scripts::enqueueThickBox();

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_screen_restrict_paired();
				$this->action( 'restrict_manage_posts', 2, 12, 'restrict_paired' );

				$this->action_module( 'meta', 'column_row', 3 );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );

				$this->_hook_store_metabox( $screen->post_type );
			}
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );

		if ( Settings::isDashboard( $screen ) )
			$this->filter_module( 'calendar', 'post_row_title', 4, 12 );
	}

	protected function paired_get_paired_constants()
	{
		return [ 'dossier_posttype', 'dossier_paired', 'section_taxonomy' ];
	}

	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [ 'span_taxonomy' ];
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'dossier_posttype' ) );
		$this->add_posttype_fields_supported();
	}

	public function admin_menu()
	{
		if ( $this->get_setting( 'quick_newpost' ) ) {
			$this->_hook_submenu_adminpage( 'newpost' );
			$this->action_self( 'newpost_content', 4, 10, 'menu_order' );
		}
	}

	public function get_adminmenu( $page = TRUE, $extra = [] )
	{
		return FALSE;
	}

	public function template_redirect()
	{
		if ( $this->_paired && is_tax( $this->constant( 'dossier_paired' ) ) ) {

			$term = get_queried_object();

			if ( $post_id = $this->paired_get_to_post_id( $term, 'dossier_posttype', 'dossier_paired' ) )
				WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_tax( $this->constant( 'span_tax' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_spans', FALSE ) )
				WordPress::redirect( $redirect, 301 );

		} else if ( is_post_type_archive( $this->constant( 'dossier_posttype' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress::redirect( $redirect, 301 );

		} else if ( is_singular( $this->constant( 'dossier_posttype' ) ) ) {

			if ( $this->get_setting( 'insert_cover' ) )
				add_action( $this->base.'_content_before',
					[ $this, 'insert_cover' ],
					$this->get_setting( 'insert_priority', -50 )
				);
		}
	}

	public function template_include( $template )
	{
		return $this->do_template_include( $template, 'dossier_posttype', NULL, FALSE );
	}

	public function template_get_archive_content_default()
	{
		return ModuleTemplate::spanTiles();
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'dossier_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->constant( 'dossier_paired' ) != $taxonomy )
			return $link;

		if ( $post_id = $this->paired_get_to_post_id( $term, 'dossier_posttype', 'dossier_paired' ) )
			return get_permalink( $post_id );

		return $link;
	}

	public function insert_cover( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		ModuleTemplate::postImage( [
			'size' => Media::getAttachmentImageDefaultSize( $this->constant( 'dossier_posttype' ), NULL, 'medium' ),
			'link' => 'attachment',
		] );
	}

	public function post_updated( $post_id, $post_after, $post_before )
	{
		$this->paired_do_save_to_post_update( $post_after, $post_before, 'dossier_posttype', 'dossier_paired' );
	}

	public function save_post( $post_id, $post, $update )
	{
		// we handle updates on another action, @SEE: `post_updated()`
		if ( ! $update )
			$this->paired_do_save_to_post_new( $post, 'dossier_posttype', 'dossier_paired' );
	}

	public function wp_trash_post( $post_id )
	{
		$this->paired_do_trash_to_post( $post_id, 'dossier_posttype', 'dossier_paired' );
	}

	public function untrash_post( $post_id )
	{
		$this->paired_do_untrash_to_post( $post_id, 'dossier_posttype', 'dossier_paired' );
	}

	public function before_delete_post( $post_id )
	{
		$this->paired_do_before_delete_to_post( $post_id, 'dossier_posttype', 'dossier_paired' );
	}

	public function pre_get_posts( &$wp_query )
	{
		if ( $this->constant( 'dossier_posttype' ) == $wp_query->get( 'post_type' ) ) {

			if ( $wp_query->is_admin ) {

				if ( ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', 'menu_order' );

				if ( ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', 'DESC' );
			}
		}
	}

	public function render_pairedbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

		if ( $this->get_setting( 'quick_newpost' ) ) {

			$this->actions( 'render_pairedbox_metabox', $post, $box, NULL, 'pairedbox_dossier' );

		} else {

			if ( ! Taxonomy::hasTerms( $this->constant( 'dossier_paired' ) ) )
				MetaBox::fieldEmptyPostType( $this->constant( 'dossier_posttype' ) );

			else
				$this->actions( 'render_pairedbox_metabox', $post, $box, NULL, 'pairedbox_dossier' );
		}

		do_action( $this->base.'_meta_render_metabox', $post, $box, NULL, 'pairedbox_dossier' );

		echo '</div>';
	}

	public function render_metabox( $post, $box, $fields = NULL, $context = NULL )
	{
		if ( $newpost = $this->get_setting( 'quick_newpost' ) )
			$this->do_render_thickbox_newpostbutton( $post, 'dossier_posttype', 'newpost', [ 'target' => 'paired' ] );

		$this->paired_do_render_metabox( $post, 'dossier_posttype', 'dossier_paired', 'section_taxonomy', $newpost );
	}

	public function store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		$this->paired_do_store_metabox( $post, 'dossier_posttype', 'dossier_paired', 'section_taxonomy' );
	}

	public function render_mainbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'render_metabox', $post, $box, NULL, 'mainbox' );

			do_action( 'geditorial_meta_render_metabox', $post, $box, NULL );

			MetaBox::fieldPostMenuOrder( $post );
			MetaBox::fieldPostParent( $post );

		echo '</div>';
	}

	public function render_listbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$this->paired_render_listbox_metabox( $post, $box, 'dossier_posttype', 'dossier_paired' );
	}

	public function get_linked_to_posts( $post = NULL, $single = FALSE, $published = TRUE )
	{
		return $this->paired_do_get_to_posts( 'dossier_posttype', 'dossier_paired', $post, $single, $published );
	}

	public function tweaks_column_attr( $post )
	{
		$this->paired_tweaks_column_attr( $post, 'dossier_posttype', 'dossier_paired' );
	}

	public function prep_meta_row( $value, $key = NULL, $field = [] )
	{
		switch ( $key ) {
			/* translators: %s: order */
			case 'in_dossier_order' : return Strings::getCounted( $value, _x( 'Order in Dossier: %s', 'Display', 'geditorial-dossier' ) );
		}

		return parent::prep_meta_row( $value, $key, $field );
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'dossier_posttype' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'dossier_posttype', $counts ) );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );
				$this->paired_tools_handle_tablelist( 'dossier_posttype', 'dossier_paired' );
			}

			Scripts::enqueueThickBox();
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		return $this->paired_tools_render_tablelist( 'dossier_posttype', 'dossier_paired', NULL, _x( 'Dossiers Tools', 'Header', 'geditorial-dossier' ) );
	}

	protected function render_tools_html_after( $uri, $sub )
	{
		$this->paired_tools_render_card( 'dossier_posttype', 'dossier_paired' );
	}

	public function dossier_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts( 'paired',
			$this->constant( 'dossier_posttype' ),
			$this->constant( 'dossier_paired' ),
			array_merge( [
				'posttypes'   => $this->posttypes(),
				'order_cb'    => NULL, // NULL for default ordering by meta
				'orderby'     => 'order', // order by meta
				// 'order_start' => 'in_dossier_page_start', // meta field for ordering
				'order_order' => 'in_dossier_order', // meta field for ordering
			], (array) $atts ),
			$content,
			$this->constant( 'dossier_shortcode', $tag ),
			$this->key
		);
	}

	public function span_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return Shortcode::listPosts( 'assigned',
			$this->constant( 'dossier_posttype' ),
			$this->constant( 'span_tax' ),
			$atts,
			$content,
			$this->constant( 'span_shortcode' )
		);
	}

	public function cover_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$type = $this->constant( 'dossier_posttype' );
		$args = [
			'size' => Media::getAttachmentImageDefaultSize( $type, NULL, 'medium' ),
			'type' => $type,
			'echo' => FALSE,
		];

		if ( is_singular( $args['type'] ) )
			$args['id'] = NULL;

		else if ( is_singular() )
			$args['id'] = 'paired';

		if ( ! $html = ModuleTemplate::postImage( array_merge( $args, (array) $atts ) ) )
			return $content;

		return ShortCode::wrap( $html,
			$this->constant( 'cover_shortcode' ),
			array_merge( [ 'wrap' => TRUE ], (array) $atts )
		);
	}
}
