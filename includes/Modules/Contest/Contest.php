<?php namespace geminorum\gEditorial\Modules\Contest;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\WordPress;

class Contest extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\PairedAdmin;

	// TODO: add span tax

	public static function module()
	{
		return [
			'name'   => 'contest',
			'title'  => _x( 'Contest', 'Modules: Contest', 'geditorial' ),
			'desc'   => _x( 'Contest Management', 'Modules: Contest', 'geditorial' ),
			'icon'   => 'megaphone',
			'access' => 'beta',
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
					'placeholder' => Core\URL::home( 'archives' ),
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
			'labels' => [
				'contest_cpt' => [
					'featured_image' => _x( 'Poster Image', 'Label: Featured Image', 'geditorial-contest' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'contest_cpt' => [
				'metabox_title' => _x( 'The Contest', 'MetaBox Title', 'geditorial-contest' ),
				'listbox_title' => _x( 'In This Contest', 'MetaBox Title', 'geditorial-contest' ),
			],
			'apply_cpt' => [
				'metabox_title' => _x( 'Contest', 'MetaBox Title', 'geditorial-contest' ),
			],
		];

		$strings['misc'] = [
			'contest_cpt' => [
				'children_column_title' => _x( 'Applies', 'Column Title', 'geditorial-contest' ),
			],
			'contest_tax' => [
				'column_icon_title' => _x( 'Contest', 'Misc: `column_icon_title`', 'geditorial-contest' ),
			],
		];

		$strings['default_terms'] = [
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

		$this->paired_register_objects( 'contest_cpt', 'contest_tax', 'section_tax', 'contest_cat' );

		$this->register_posttype( 'apply_cpt' );

		$this->register_shortcode( 'contest_shortcode' );
		$this->register_shortcode( 'cover_shortcode' );

		$this->filter_module( 'audit', 'auto_audit_save_post', 5 );

		if ( is_admin() )
			return;

		$this->_hook_paired_exclude_from_subterm();
		$this->_hook_paired_override_term_link();
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save_posttype( 'contest_cpt' ) )
			$this->_hook_paired_sync_primary_posttype();
	}

	public function setup_restapi()
	{
		$this->_hook_paired_sync_primary_posttype();
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'section_tax' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'contest_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'wp_insert_post_data', 2, 9, 'menu_order' );
				$this->filter( 'get_default_comment_status', 3 );

				$this->_hook_post_updated_messages( 'contest_cpt' );
				$this->_hook_paired_mainbox( $screen );
				$this->_hook_paired_listbox( $screen );
				$this->_hook_paired_sync_primary_posttype();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );

				$this->_hook_admin_ordering( $screen->post_type );
				$this->_hook_bulk_post_updated_messages( 'contest_cpt' );
				$this->_hook_paired_sync_primary_posttype();
				$this->_hook_paired_tweaks_column_attr();
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( $subterms && $subterms === $screen->taxonomy )
				$this->filter_string( 'parent_file', sprintf( 'edit.php?post_type=%s', $this->constant( 'contest_cpt' ) ) );

			if ( 'edit-tags' == $screen->base ) {

				$this->_hook_paired_taxonomy_bulk_actions( $screen->post_type, $screen->taxonomy );

			} else if ( 'post' == $screen->base ) {

				if ( $screen->post_type == $this->constant( 'apply_cpt' ) ) {
					$this->_hook_post_updated_messages( 'apply_cpt' );
					$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
					remove_meta_box( 'pageparentdiv', $screen, 'side' );
				}

				$this->_metabox_remove_subterm( $screen, $subterms );
				$this->_hook_paired_pairedbox( $screen, ( $screen->post_type == $this->constant( 'apply_cpt' ) ) );
				$this->_hook_paired_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				if ( $screen->post_type == $this->constant( 'apply_cpt' ) )
					$this->_hook_bulk_post_updated_messages( 'apply_cpt' );

				$this->_hook_screen_restrict_paired();
				$this->_hook_paired_store_metabox( $screen->post_type );
				$this->paired__hook_tweaks_column( $screen->post_type, 12 );

				$this->action_module( 'meta', 'column_row', 3 );

				if ( $subterms )
					$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );

		if ( Settings::isDashboard( $screen ) )
			$this->filter_module( 'calendar', 'post_row_title', 4, 12 );
	}

	protected function paired_get_paired_constants()
	{
		return [
			'contest_cpt',
			'contest_tax',
			'section_tax',
			'contest_cat',
		];
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

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'contest_tax' ) ) ) {

			if ( $post_id = $this->paired_get_to_post_id( get_queried_object(), 'contest_cpt', 'contest_tax' ) )
				Core\WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_post_type_archive( $this->constant( 'contest_cpt' ) )
			|| is_post_type_archive( $this->constant( 'apply_cpt' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				Core\WordPress::redirect( $redirect, 301 );
		}
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
			'size' => WordPress\Media::getAttachmentImageDefaultSize( $type, NULL, 'medium' ),
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

			if ( WordPress\Taxonomy::hasTerms( $this->constant( 'contest_tax' ), $post->ID ) )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

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
