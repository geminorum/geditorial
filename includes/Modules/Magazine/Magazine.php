<?php namespace geminorum\gEditorial\Modules\Magazine;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Strings;
use geminorum\gEditorial\WordPress\Taxonomy;

class Magazine extends gEditorial\Module
{

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
					'field'       => 'subterms_support',
					'title'       => _x( 'Issue Sections', 'Settings', 'geditorial-magazine' ),
					'description' => _x( 'Section taxonomy for the issues and supported post-types.', 'Settings', 'geditorial-magazine' ),
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
					'title'       => _x( 'Redirect Archives', 'Settings', 'geditorial-magazine' ),
					'description' => _x( 'Redirects issue archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-magazine' ),
					'placeholder' => URL::home( 'archives' ),
				],
				[
					'field'       => 'redirect_spans',
					'type'        => 'url',
					'title'       => _x( 'Redirect Spans', 'Settings', 'geditorial-magazine' ),
					'description' => _x( 'Redirects all span archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-magazine' ),
					'placeholder' => URL::home( 'archives' ),
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
			'issue_cpt'          => 'issue',
			'issue_cpt_archive'  => 'issues',
			'issue_tax'          => 'issues',
			'issue_tax_slug'     => 'issues',
			'span_tax'           => 'issue_span',
			'section_tax'        => 'issue_section',
			'issue_shortcode'    => 'issue',
			'span_shortcode'     => 'issue-span',
			'cover_shortcode'    => 'issue-cover',
			'field_paired_order' => 'in_issue_order',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'issue_tax'   => NULL,
				'span_tax'    => 'backup',
				'section_tax' => 'category',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'issue_cpt'   => _n_noop( 'Issue', 'Issues', 'geditorial-magazine' ),
				'issue_tax'   => _n_noop( 'Issue', 'Issues', 'geditorial-magazine' ),
				'span_tax'    => _n_noop( 'Span', 'Spans', 'geditorial-magazine' ),
				'section_tax' => _n_noop( 'Section', 'Sections', 'geditorial-magazine' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'issue_cpt' => [
				'featured'         => _x( 'Cover Image', 'Posttype Featured', 'geditorial-magazine' ),
				'show_option_none' => _x( '&ndash; Select Issue &ndash;', 'Select Option None', 'geditorial-magazine' ),
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
				'show_option_none'    => _x( '&ndash; Select Section &ndash;', 'Select Option None', 'geditorial-magazine' ),
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
				'over_title' => [ 'type' => 'title_before' ],
				'sub_title'  => [ 'type' => 'title_after' ],

				'number_line' => [
					'title'       => _x( 'Number Line', 'Field Title', 'geditorial-magazine' ),
					'description' => _x( 'The issue number line', 'Field Description', 'geditorial-magazine' ),
					'icon'        => 'menu',
				],
				'total_pages' => [
					'title'       => _x( 'Total Pages', 'Field Title', 'geditorial-magazine' ),
					'description' => _x( 'The issue total pages', 'Field Description', 'geditorial-magazine' ),
					'icon'        => 'admin-page',
				],

				'highlight'    => [ 'type' => 'note' ],
				'source_title' => [ 'type' => 'text' ],
				'source_url'   => [ 'type' => 'link' ],
				'action_title' => [ 'type' => 'text' ],
				'action_url'   => [ 'type' => 'link' ],
				'cover_blurb'  => [ 'type' => 'note' ],
				'cover_price'  => [ 'type' => 'price' ],
			],
			'_supported' => [
				'in_issue_order' => [
					'title'       => _x( 'Order', 'Field Title', 'geditorial-magazine' ),
					'description' => _x( 'Post order in issue list', 'Field Description', 'geditorial-magazine' ),
					'type'        => 'number',
					'context'     => 'pairedbox_issue',
					'icon'        => 'sort',
				],
				'in_issue_page_start' => [
					'title'       => _x( 'Page Start', 'Field Title', 'geditorial-magazine' ),
					'description' => _x( 'Post start page on issue (printed)', 'Field Description', 'geditorial-magazine' ),
					'type'        => 'number',
					'context'     => 'pairedbox_issue',
					'icon'        => 'media-default',
				],
				'in_issue_pages' => [
					'title'       => _x( 'Total Pages', 'Field Title', 'geditorial-magazine' ),
					'description' => _x( 'Post total pages on issue (printed)', 'Field Description', 'geditorial-magazine' ),
					'context'     => 'pairedbox_issue',
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

		$this->register_taxonomy( 'span_tax', [
			'hierarchical'       => TRUE, // required by `MetaBox::checklistTerms()`
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'issue_cpt' );

		$this->paired_register_objects( 'issue_cpt', 'issue_tax', 'section_tax' );

		$this->register_shortcode( 'issue_shortcode' );
		$this->register_shortcode( 'span_shortcode' );
		$this->register_shortcode( 'cover_shortcode' );

		if ( is_admin() )
			return;

		$this->filter( 'term_link', 3 );
	}

	public function template_redirect()
	{
		if ( $this->_paired && is_tax( $this->constant( 'issue_tax' ) ) ) {

			$term = get_queried_object();

			if ( $post_id = $this->paired_get_to_post_id( $term, 'issue_cpt', 'issue_tax' ) )
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
		if ( $posttype = $this->is_inline_save_posttype( 'issue_cpt' ) )
			$this->_hook_paired_to( $posttype );
	}

	public function setup_restapi()
	{
		$this->_hook_paired_to( $this->constant( 'issue_cpt' ) );
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'section_tax' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'issue_cpt' ) ) {

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
					$this->get_meta_box_title( 'issue_cpt', FALSE ),
					[ $this, 'render_mainbox_metabox' ],
					$screen,
					'side',
					'high'
				);

				$this->class_metabox( $screen, 'listbox' );
				add_meta_box( $this->classs( 'listbox' ),
					$this->get_meta_box_title( 'issue_tax' ),
					[ $this, 'render_listbox_metabox' ],
					$screen,
					'advanced',
					'low'
				);

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );
				$this->filter( 'bulk_post_updated_messages', 2 );

				$this->_hook_screen_restrict_taxonomies();
				$this->action( 'restrict_manage_posts', 2, 20, 'restrict_taxonomy' );
				$this->action( 'parse_query', 1, 12, 'restrict_taxonomy' );

				if ( $this->get_setting( 'admin_ordering', TRUE ) )
					$this->action( 'pre_get_posts' );

				$this->action_module( 'meta', 'column_row', 3 );
				$this->action_module( 'tweaks', 'column_attr' );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}

			$this->_hook_paired_to( $screen->post_type );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				if ( $subterms )
					remove_meta_box( $subterms.'div', $screen->post_type, 'side' );

				$this->class_metabox( $screen, 'pairedbox' );
				add_meta_box( $this->classs( 'pairedbox' ),
					$this->get_meta_box_title_posttype( 'issue_cpt' ),
					[ $this, 'render_pairedbox_metabox' ],
					$screen,
					'side'
				);

				add_action( $this->hook( 'render_pairedbox_metabox' ), [ $this, 'render_metabox' ], 10, 4 );

				if ( $this->get_setting( 'quick_newpost' ) )
					Scripts::enqueueThickBox();

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_screen_restrict_paired();
				$this->action( 'restrict_manage_posts', 2, 12, 'restrict_paired' );

				$this->action_module( 'meta', 'column_row', 3 );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}

			$this->_hook_store_metabox( $screen->post_type );
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );

		if ( Settings::isDashboard( $screen ) )
			$this->filter_module( 'calendar', 'post_row_title', 4, 12 );
	}

	protected function paired_get_paired_constants()
	{
		return [ 'issue_cpt', 'issue_tax', 'section_tax' ];
	}

	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [ 'span_tax' ];
	}

	public function widgets_init()
	{
		register_widget( __NAMESPACE__.'\\Widgets\\IssueCover' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'issue_cpt' ) );
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

		if ( $post_id = $this->paired_get_to_post_id( $term, 'issue_cpt', 'issue_tax' ) )
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
		$this->paired_do_save_to_post_update( $post_after, $post_before, 'issue_cpt', 'issue_tax' );
	}

	public function save_post( $post_id, $post, $update )
	{
		// we handle updates on another action, @SEE: `post_updated()`
		if ( ! $update )
			$this->paired_do_save_to_post_new( $post, 'issue_cpt', 'issue_tax' );
	}

	public function wp_trash_post( $post_id )
	{
		$this->paired_do_trash_to_post( $post_id, 'issue_cpt', 'issue_tax' );
	}

	public function untrash_post( $post_id )
	{
		$this->paired_do_untrash_to_post( $post_id, 'issue_cpt', 'issue_tax' );
	}

	public function before_delete_post( $post_id )
	{
		$this->paired_do_before_delete_to_post( $post_id, 'issue_cpt', 'issue_tax' );
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

	public function meta_box_cb_span_tax( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, [ 'taxonomy' => $box['args']['taxonomy'], 'posttype' => $post->post_type ] );
		echo '</div>';
	}

	public function render_pairedbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

		if ( $this->get_setting( 'quick_newpost' ) ) {

			$this->actions( 'render_pairedbox_metabox', $post, $box, NULL, 'pairedbox_issue' );

		} else {

			if ( ! Taxonomy::hasTerms( $this->constant( 'issue_tax' ) ) )
				MetaBox::fieldEmptyPostType( $this->constant( 'issue_cpt' ) );

			else
				$this->actions( 'render_pairedbox_metabox', $post, $box, NULL, 'pairedbox_issue' );
		}

		do_action( $this->base.'_meta_render_metabox', $post, $box, NULL, 'pairedbox_issue' );

		echo '</div>';
	}

	public function render_metabox( $post, $box, $fields = NULL, $context = NULL )
	{
		if ( $newpost = $this->get_setting( 'quick_newpost' ) )
			$this->do_render_thickbox_newpostbutton( $post, 'issue_cpt', 'newpost', [ 'target' => 'paired' ] );

		$this->paired_do_render_metabox( $post, 'issue_cpt', 'issue_tax', 'section_tax', $newpost );
	}

	public function store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		$this->paired_do_store_metabox( $post, 'issue_cpt', 'issue_tax', 'section_tax' );
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

		$this->paired_render_listbox_metabox( $post, $box, 'issue_cpt', 'issue_tax' );
	}

	public function get_linked_to_posts( $post = NULL, $single = FALSE, $published = TRUE )
	{
		return $this->paired_do_get_to_posts( 'issue_cpt', 'issue_tax', $post, $single, $published );
	}

	public function tweaks_column_attr( $post )
	{
		$this->paired_tweaks_column_attr( $post, 'issue_cpt', 'issue_tax' );
	}

	public function display_meta_row( $value, $key = NULL, $field = [] )
	{
		switch ( $key ) {
			/* translators: %s: order */
			case 'in_issue_order'      : return Strings::getCounted( $value, _x( 'Order in Issue: %s', 'Display', 'geditorial-magazine' ) );
			/* translators: %s: page */
			case 'in_issue_page_start' : return Strings::getCounted( $value, _x( 'Page in Issue: %s', 'Display', 'geditorial-magazine' ) );
			/* translators: %s: total count */
			case 'in_issue_pages'      : return Strings::getCounted( $value, _x( 'Total Pages: %s', 'Display', 'geditorial-magazine' ) );
		}

		return parent::display_meta_row( $value, $key, $field );
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
		return ShortCode::listPosts( 'paired',
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
		return Shortcode::listPosts( 'assigned',
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
			$args['id'] = 'paired';

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
				$this->paired_tools_handle_tablelist( 'issue_cpt', 'issue_tax' );
			}
		}

		Scripts::enqueueThickBox();
	}

	protected function render_tools_html( $uri, $sub )
	{
		return $this->paired_tools_render_tablelist( 'issue_cpt', 'issue_tax', NULL, _x( 'Magazine Tools', 'Header', 'geditorial-magazine' ) );
	}

	protected function render_tools_html_after( $uri, $sub )
	{
		$this->paired_tools_render_card( 'issue_cpt', 'issue_tax' );
	}
}
