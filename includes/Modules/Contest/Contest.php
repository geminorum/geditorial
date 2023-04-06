<?php namespace geminorum\gEditorial\Modules\Contest;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\Taxonomy;

class Contest extends gEditorial\Module
{

	// TODO: add span tax

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
				[
					'field'       => 'subterms_support',
					'title'       => _x( 'Contest Sections', 'Settings', 'geditorial-contest' ),
					'description' => _x( 'Section taxonomy for the contests and supported post-types.', 'Settings', 'geditorial-contest' ),
				],
				'comment_status',
				'paired_exclude_terms' => [
					NULL,
					$this->constant( 'contest_cat' ),
					$this->get_taxonomy_label( 'contest_cat', 'no_terms' ),
				],
			],
			'_editlist' => [
				'admin_ordering',
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
				'assign_default_term',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'contest_cpt', TRUE ),
				$this->settings_supports_option( 'apply_cpt', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'contest_cpt' => 'contest',
			'contest_tax' => 'contests',
			'section_tax' => 'contest_section',
			'apply_cpt'   => 'apply',
			'contest_cat' => 'contest_category',
			'apply_cat'   => 'apply_category',
			'status_tax'  => 'apply_status',

			'contest_shortcode' => 'contest',
			'cover_shortcode'   => 'contest-cover',

			'term_abandoned_apply' => 'apply-abandoned',
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
				'contest_cat' => 'category',
				'contest_tax' => 'megaphone',
				'section_tax' => 'category',
				'apply_cat'   => 'category',
				'status_tax'  => 'post-status', // 'portfolio',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'contest_cpt' => _n_noop( 'Contest', 'Contests', 'geditorial-contest' ),
				'contest_tax' => _n_noop( 'Contest', 'Contests', 'geditorial-contest' ),
				'contest_cat' => _n_noop( 'Contest Category', 'Contest Categories', 'geditorial-contest' ),
				'section_tax' => _n_noop( 'Section', 'Sections', 'geditorial-contest' ),
				'apply_cpt'   => _n_noop( 'Apply', 'Applies', 'geditorial-contest' ),
				'apply_cat'   => _n_noop( 'Apply Category', 'Apply Categories', 'geditorial-contest' ),
				'status_tax'  => _n_noop( 'Apply Status', 'Apply Statuses', 'geditorial-contest' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'contest_cpt' => [
				'featured'              => _x( 'Poster Image', 'Posttype Featured', 'geditorial-contest' ),
				'meta_box_title'        => _x( 'Metadata', 'MetaBox Title', 'geditorial-contest' ),
				'children_column_title' => _x( 'Applies', 'Column Title', 'geditorial-contest' ),
				'show_option_none'      => _x( '&ndash; Select Contest &ndash;', 'Select Option None', 'geditorial-contest' ),
			],
			'contest_tax' => [
				'meta_box_title' => _x( 'In This Contest', 'MetaBox Title', 'geditorial-contest' ),
			],
			'contest_cat' => [
				'tweaks_column_title' => _x( 'Contest Categories', 'Column Title', 'geditorial-contest' ),
			],
			'section_tax' => [
				'meta_box_title'      => _x( 'Sections', 'MetaBox Title', 'geditorial-contest' ),
				'tweaks_column_title' => _x( 'Contest Sections', 'Column Title', 'geditorial-contest' ),
				'show_option_none'    => _x( '&ndash; Select Section &ndash;', 'Select Option None', 'geditorial-contest' ),
			],
			'apply_cpt' => [
				'meta_box_title' => _x( 'Contest', 'MetaBox Title', 'geditorial-contest' ),
			],
			'apply_cat' => [
				'tweaks_column_title' => _x( 'Apply Categories', 'Column Title', 'geditorial-contest' ),
			],
			'status_tax' => [
				'meta_box_title'      => _x( 'Apply Statuses', 'MetaBox Title', 'geditorial-contest' ),
				'tweaks_column_title' => _x( 'Apply Statuses', 'Column Title', 'geditorial-contest' ),
			],
			'meta_box_title'      => _x( 'Contests', 'MetaBox Title', 'geditorial-contest' ),
			'tweaks_column_title' => _x( 'Contests', 'Column Title', 'geditorial-contest' ),
		];

		$strings['terms'] = [
			'status_tax' => [
				'status_approved'    => _x( 'Approved', 'Default Term', 'geditorial-contest' ),
				'status_pending'     => _x( 'Pending', 'Default Term', 'geditorial-contest' ),
				'status_maybe_later' => _x( 'Maybe Later', 'Default Term', 'geditorial-contest' ),
				'status_rejected'    => _x( 'Rejected', 'Default Term', 'geditorial-contest' ),
			],
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			$this->constant( 'contest_cpt' ) => [
				'over_title' => [ 'type' => 'title_before' ],
				'sub_title'  => [ 'type' => 'title_after' ],

				'deadline_datetime' => [
					'title'       => _x( 'Deadline Date', 'Field Title', 'geditorial-contest' ),
					'description' => _x( 'The last date for submitting the applications.', 'Field Description', 'geditorial-contest' ),
					'icon'        => 'calendar-alt',
					'type'        => 'date'
				],

				'contact_string' => [ 'type' => 'contact' ], // url/email/phone
				'phone_number'   => [ 'type' => 'phone' ],
				'mobile_number'  => [ 'type' => 'mobile' ],

				'website_url'    => [ 'type' => 'link' ],
				'email_address'  => [ 'type' => 'email' ],
				'postal_address' => [ 'type' => 'note' ],
			],
			'_supported' => [
				'submission_datetime' => [
					'title'       => _x( 'Submission Date', 'Field Title', 'geditorial-contest' ),
					'description' => _x( 'Verified date for the submitted application.', 'Field Description', 'geditorial-contest' ),
					'context'     => 'pairedbox_contest',
					'icon'        => 'calendar-alt',
					'type'        => 'date'
				],
			],
		];
	}

	public function before_settings( $module = FALSE )
	{
		if ( isset( $_POST['install_def_status_tax'] ) )
			$this->insert_default_terms( 'status_tax' );

		$this->help_tab_default_terms( 'status_tax' );
	}

	public function default_buttons( $module = FALSE )
	{
		parent::default_buttons( $module );

		$this->register_button( 'install_def_status_tax', _x( 'Install Default Apply Statuses', 'Button', 'geditorial-contest' ) );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'contest_cpt' );
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'contest_cat', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
		], 'contest_cpt' );

		$this->register_taxonomy( 'apply_cat', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'apply_cpt' );

		$this->register_taxonomy( 'status_tax', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'apply_cpt' );

		$this->paired_register_objects( 'contest_cpt', 'contest_tax', 'section_tax', [
			'primary_taxonomy' => $this->constant( 'contest_cat' ),
		] );

		$this->register_posttype( 'apply_cpt' );

		$this->register_shortcode( 'contest_shortcode' );
		$this->register_shortcode( 'cover_shortcode' );

		$this->register_default_terms( 'status_tax' );

		$this->filter_module( 'audit', 'auto_audit_save_post', 5 );

		$this->register_default_terms( 'status_tax' );

		if ( is_admin() )
			return;

		$this->filter( 'term_link', 3 );
	}

	public function init_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'contest_cpt' ) )
			$this->_hook_paired_to( $posttype );
	}

	public function setup_restapi()
	{
		$this->_hook_paired_to( $this->constant( 'contest_cpt' ) );
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'section_tax' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'contest_cpt' ) ) {

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
					$this->get_meta_box_title( 'contest_cpt', FALSE ),
					[ $this, 'render_mainbox_metabox' ],
					$screen,
					'side',
					'high'
				);

				$this->class_metabox( $screen, 'listbox' );
				add_meta_box( $this->classs( 'listbox' ),
					$this->get_meta_box_title_taxonomy( 'contest_tax', $screen->post_type, FALSE ),
					[ $this, 'render_listbox_metabox' ],
					$screen,
					'advanced',
					'low'
				);

				$this->_hook_paired_to( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );

				if ( $this->get_setting( 'admin_ordering', TRUE ) )
					$this->action( 'pre_get_posts' );

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

				if ( $screen->post_type == $this->constant( 'apply_cpt' ) ) {
					$this->filter( 'post_updated_messages', 1, 10, 'supported' );
					$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
					remove_meta_box( 'pageparentdiv', $screen, 'side' );
				}

				$this->class_metabox( $screen, 'pairedbox' );
				add_meta_box( $this->classs( 'pairedbox' ),
					$this->get_meta_box_title( 'apply_cpt' ),
					[ $this, 'render_pairedbox_metabox' ],
					$screen,
					'side'
				);

				add_action( $this->hook( 'render_pairedbox_metabox' ), [ $this, 'render_metabox' ], 10, 4 );
				$this->_hook_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				if ( $screen->post_type == $this->constant( 'apply_cpt' ) )
					$this->filter( 'bulk_post_updated_messages', 2, 10, 'supported' );

				$this->_hook_screen_restrict_paired();

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
		return [ 'contest_cpt', 'contest_tax', 'section_tax', 'contest_cat' ];
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'contest_cpt' ) );
		$this->add_posttype_fields_supported();
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

		if ( $post_id = $this->paired_get_to_post_id( $term, 'contest_cpt', 'contest_tax' ) )
			return get_permalink( $post_id );

		return $link;
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'contest_tax' ) ) ) {

			$term = get_queried_object();

			if ( $post_id = $this->paired_get_to_post_id( $term, 'contest_cpt', 'contest_tax' ) )
				WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_post_type_archive( $this->constant( 'contest_cpt' ) )
			|| is_post_type_archive( $this->constant( 'apply_cpt' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress::redirect( $redirect, 301 );
		}
	}

	public function render_mainbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'render_metabox', $post, $box, NULL, 'mainbox' );

			do_action( 'geditorial_meta_render_metabox', $post, $box, NULL, 'mainbox' );

			MetaBox::fieldPostMenuOrder( $post );
			MetaBox::fieldPostParent( $post );

		echo '</div>';
	}

	public function render_listbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$this->paired_render_listbox_metabox( $post, $box, 'contest_cpt', 'contest_tax' );
	}

	public function render_pairedbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

		if ( ! Taxonomy::hasTerms( $this->constant( 'contest_tax' ) ) ) {

			MetaBox::fieldEmptyPostType( $this->constant( 'contest_cpt' ) );

		} else {

			$this->actions( 'render_pairedbox_metabox', $post, $box, NULL, 'pairedbox_contest' );

			do_action( $this->base.'_meta_render_metabox', $post, $box, NULL, 'pairedbox_contest' );
		}

		echo '</div>';
	}

	public function render_metabox( $post, $box, $fields = NULL, $context = NULL )
	{
		$this->paired_do_render_metabox( $post, 'contest_cpt', 'contest_tax', 'section_tax' );

		MetaBox::fieldPostMenuOrder( $post );
	}

	public function store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		$this->paired_do_store_metabox( $post, 'contest_cpt', 'contest_tax', 'section_tax' );
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
		$this->paired_do_save_to_post_update( $post_after, $post_before, 'contest_cpt', 'contest_tax' );
	}

	public function save_post( $post_id, $post, $update )
	{
		// we handle updates on another action, see : post_updated()
		if ( ! $update )
			$this->paired_do_save_to_post_new( $post, 'contest_cpt', 'contest_tax' );
	}

	public function wp_trash_post( $post_id )
	{
		$this->paired_do_trash_to_post( $post_id, 'contest_cpt', 'contest_tax' );
	}

	public function untrash_post( $post_id )
	{
		$this->paired_do_untrash_to_post( $post_id, 'contest_cpt', 'contest_tax' );
	}

	public function before_delete_post( $post_id )
	{
		$this->paired_do_before_delete_to_post( $post_id, 'contest_cpt', 'contest_tax' );
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

	public function get_linked_to_posts( $post = NULL, $single = FALSE, $published = TRUE )
	{
		return $this->paired_do_get_to_posts( 'contest_cpt', 'contest_tax', $post, $single, $published );
	}

	public function tweaks_column_attr( $post )
	{
		$this->paired_tweaks_column_attr( $post, 'contest_cpt', 'contest_tax' );
	}

	public function contest_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts( 'paired',
			$this->constant( 'contest_cpt' ),
			$this->constant( 'contest_tax' ),
			array_merge( [
				'posttypes' => $this->posttypes(),
				'orderby'   => 'menu_order',
			], (array) $atts ),
			$content,
			$this->constant( 'contest_shortcode', $tag ),
			$this->key
		);
	}

	public function cover_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$type = $this->constant( 'contest_cpt' );
		$args = [
			'size' => Media::getAttachmentImageDefaultSize( $type, NULL, 'medium' ),
			'type' => $type,
			'echo' => FALSE,
		];

		if ( is_singular( $args['type'] ) )
			$args['id'] = NULL;

		else if ( is_singular() )
			$args['id'] = 'paired';

		if ( ! $html = Template::postImage( array_merge( $args, (array) $atts ), $this->module->name ) )
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
				$this->paired_tools_handle_tablelist( 'contest_cpt', 'contest_tax' );
			}
		}

		Scripts::enqueueThickBox();
	}

	protected function render_tools_html( $uri, $sub )
	{
		return $this->paired_tools_render_tablelist( 'contest_cpt', 'contest_tax', NULL, _x( 'Contest Tools', 'Header', 'geditorial-contest' ) );
	}

	protected function render_tools_html_after( $uri, $sub )
	{
		$this->paired_tools_render_card( 'contest_cpt', 'contest_tax' );
	}

	public function audit_auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $terms;

		if ( $exists = term_exists( $this->constant( 'term_abandoned_apply' ), $taxonomy ) ) {

			if ( Taxonomy::hasTerms( $this->constant( 'contest_tax' ), $post->ID ) )
				$terms = Arraay::stripByValue( $terms, $exists['term_id'] );

			else
				$terms[] = $exists['term_id'];
		}

		return $terms;
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Helper::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_abandoned_apply' ) => _x( 'Apply Abandoned', 'Default Term: Audit', 'geditorial-contest' ),
		] ) : $terms;
	}
}
