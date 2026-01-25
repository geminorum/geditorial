<?php namespace geminorum\gEditorial\Modules\Contest;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Contest extends gEditorial\Module
{
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\LateChores;
	use Internals\MetaBoxMain;
	use Internals\PairedAdmin;
	use Internals\PairedCore;
	use Internals\PairedFront;
	use Internals\PairedMetaBox;
	use Internals\PairedRowActions;
	use Internals\PairedThumbnail;
	use Internals\PairedTools;
	use Internals\PostDate;
	use Internals\PostMeta;
	use Internals\PostTypeOverview;

	public static function module()
	{
		return [
			'name'     => 'contest',
			'title'    => _x( 'Contest', 'Modules: Contest', 'geditorial-admin' ),
			'desc'     => _x( 'Contest Management', 'Modules: Contest', 'geditorial-admin' ),
			'icon'     => 'megaphone',
			'access'   => 'beta',
			'keywords' => [
				'apply',
				'double-paired',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'multiple_instances',
				'paired_force_parents',
				'paired_manage_restricted',
				[
					'field'       => 'subterms_support',
					'title'       => _x( 'Contest Sections', 'Settings', 'geditorial-contest' ),
					'description' => _x( 'Section taxonomy for the contests and supported post-types.', 'Settings', 'geditorial-contest' ),
				],
				'comment_status',
				'paired_exclude_terms' => [
					NULL,
					$this->constant( 'category_contest' ),
					$this->get_taxonomy_label( 'category_contest', 'no_terms' ),
				],
			],
			'_editlist' => [
				'admin_ordering',
				'admin_bulkactions',
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
				'override_dates',
				'assign_default_term',
				'shortcode_support',
				'thumbnail_support',
				'thumbnail_fallback',
				$this->settings_supports_option( 'contest_posttype', TRUE ),
				$this->settings_supports_option( 'apply_posttype', TRUE ),
			],
			'_reports' => [
				'overview_taxonomies' => [ NULL, $this->get_posttype_taxonomies_list( 'contest_posttype' ) ],
				'overview_fields'     => [ NULL, $this->get_posttype_fields_list( 'contest_posttype', 'meta' ) ],
				'overview_units'      => [ NULL, $this->get_posttype_fields_list( 'contest_posttype', 'units' ) ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'contest_posttype' => 'contest',
			'contest_paired'   => 'contests',
			'section_taxonomy' => 'contest_section',
			'apply_posttype'   => 'apply',
			'category_contest' => 'contest_category',
			'category_apply'   => 'apply_category',
			'status_taxonomy'  => 'apply_status',

			'contest_shortcode' => 'contest',
			'cover_shortcode'   => 'contest-cover',

			'term_abandoned_apply' => 'apply-abandoned',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'contest_posttype' => _n_noop( 'Contest', 'Contests', 'geditorial-contest' ),
				'contest_paired'   => _n_noop( 'Contest', 'Contests', 'geditorial-contest' ),
				'category_contest' => _n_noop( 'Contest Category', 'Contest Categories', 'geditorial-contest' ),
				'section_taxonomy' => _n_noop( 'Section', 'Sections', 'geditorial-contest' ),
				'apply_posttype'   => _n_noop( 'Apply', 'Applies', 'geditorial-contest' ),
				'category_apply'   => _n_noop( 'Apply Category', 'Apply Categories', 'geditorial-contest' ),
				'status_taxonomy'  => _n_noop( 'Apply Status', 'Apply Statuses', 'geditorial-contest' ),
			],
			'labels' => [
				'contest_posttype' => [
					'featured_image' => _x( 'Poster Image', 'Label: Featured Image', 'geditorial-contest' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'contest_posttype' => [
				'metabox_title' => _x( 'The Contest', 'MetaBox Title', 'geditorial-contest' ),
				'listbox_title' => _x( 'In This Contest', 'MetaBox Title', 'geditorial-contest' ),
			],
			'apply_posttype' => [
				'metabox_title' => _x( 'Contest', 'MetaBox Title', 'geditorial-contest' ),
			],
		];

		$strings['misc'] = [
			'contest_posttype' => [
				'children_column_title' => _x( 'Applies', 'Column Title', 'geditorial-contest' ),
			],
			'contest_paired' => [
				'column_icon_title' => _x( 'Contest', 'Misc: `column_icon_title`', 'geditorial-contest' ),
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'status_taxonomy' => [
				'status_approved'    => _x( 'Approved', 'Default Term', 'geditorial-contest' ),
				'status_pending'     => _x( 'Pending', 'Default Term', 'geditorial-contest' ),
				'status_maybe_later' => _x( 'Maybe Later', 'Default Term', 'geditorial-contest' ),
				'status_rejected'    => _x( 'Rejected', 'Default Term', 'geditorial-contest' ),
			],
		];
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
				$this->constant( 'contest_posttype' ) => [
					'over_title' => [ 'type' => 'title_before' ],
					'sub_title'  => [ 'type' => 'title_after' ],

					'deadline_datetime' => [
						'title'       => _x( 'Deadline Date', 'Field Title', 'geditorial-contest' ),
						'description' => _x( 'The last date for submitting the applications.', 'Field Description', 'geditorial-contest' ),
						'icon'        => 'calendar-alt',
						'type'        => 'date'
					],

					'date'           => [ 'type' => 'date',     'quickedit' => TRUE ],
					'datetime'       => [ 'type' => 'datetime', 'quickedit' => TRUE ],
					'datestart'      => [ 'type' => 'datetime', 'quickedit' => TRUE ],
					'dateend'        => [ 'type' => 'datetime', 'quickedit' => TRUE ],
					'venue_string'   => [ 'type' => 'venue' ],
					'contact_string' => [ 'type' => 'contact' ],   // `url`/`email`/`phone`
					'website_url'    => [ 'type' => 'link' ],
					'email_address'  => [ 'type' => 'email' ],
				],
				'_supported' => [
					'submission_datetime' => [
						'title'       => _x( 'Submission Date', 'Field Title', 'geditorial-contest' ),
						'description' => _x( 'Verified date for the submitted application.', 'Field Description', 'geditorial-contest' ),
						'context'     => 'pairedbox_contest',
						'icon'        => 'calendar-alt',
						'type'        => 'date'
					],

					'event_summary' => [ 'type' => 'text' ],
					'date'          => [ 'type' => 'date',     'quickedit' => TRUE ],
					'datetime'      => [ 'type' => 'datetime', 'quickedit' => TRUE ],
					'datestart'     => [ 'type' => 'datetime', 'quickedit' => TRUE ],
					'dateend'       => [ 'type' => 'datetime', 'quickedit' => TRUE ],
				],
			],
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'contest_posttype' );
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'category_contest', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
		], 'contest_posttype', [
			'custom_icon' => 'category',
		] );

		$this->register_taxonomy( 'category_apply', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'apply_posttype', [
			'custom_icon' => 'category',
		] );

		$this->register_taxonomy( 'status_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'apply_posttype', [
			'admin_managed'   => TRUE,
			'single_selected' => TRUE,
		] );

		$this->paired_register( [], [
			'custom_icon'     => $this->module->icon,
			'status_taxonomy' => TRUE,
			'ical_source'     => 'paired',
		], [
			'custom_icon' => 'category',
		] );

		$this->register_posttype( 'apply_posttype', [], [
			'custom_icon' => 'portfolio',
		] );

		$this->register_shortcode( 'contest_shortcode' );
		$this->register_shortcode( 'cover_shortcode' );

		$this->_hook_paired_thumbnail_fallback();

		$this->filter_module( 'audit', 'auto_audit_save_post', 5 );

		if ( is_admin() )
			return;

		$this->_hook_paired_exclude_from_subterm();
		$this->_hook_paired_override_term_link();
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'contest_posttype' ) ) {
			$this->pairedadmin__hook_tweaks_column_connected( $posttype );
		}
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'section_taxonomy' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'contest_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->comments__handle_default_status( $screen->post_type );
				$this->posttypes__increase_menu_order( $screen->post_type );
				$this->posttypes__media_register_headerbutton( 'contest_posttype' );
				$this->_hook_post_updated_messages( 'contest_posttype' );
				$this->_hook_paired_mainbox( $screen );
				$this->_hook_paired_listbox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->modulelinks__register_headerbuttons();
				$this->latechores__hook_admin_bulkactions( $screen );
				$this->coreadmin__hook_admin_ordering( $screen->post_type );
				$this->_hook_bulk_post_updated_messages( 'contest_posttype' );
				$this->pairedcore__hook_sync_paired();
				$this->pairedadmin__hook_tweaks_column_connected( $screen->post_type );
				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( $subterms && $subterms === $screen->taxonomy )
				$this->filter_string( 'parent_file', sprintf( 'edit.php?post_type=%s', $this->constant( 'contest_posttype' ) ) );

			if ( 'edit-tags' == $screen->base ) {

				$this->_hook_paired_taxonomy_bulk_actions( $screen->post_type, $screen->taxonomy );

			} else if ( 'post' == $screen->base ) {

				if ( $screen->post_type == $this->constant( 'apply_posttype' ) ) {
					$this->posttypes__media_register_headerbutton( 'apply_posttype' );
					$this->_hook_post_updated_messages( 'apply_posttype' );
					$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
					remove_meta_box( 'pageparentdiv', $screen, 'side' );
				}

				$this->_metabox_remove_subterm( $screen, $subterms );
				$this->_hook_paired_pairedbox( $screen, ( $screen->post_type == $this->constant( 'apply_posttype' ) ) );
				$this->_hook_paired_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				if ( $screen->post_type == $this->constant( 'apply_posttype' ) )
					$this->_hook_bulk_post_updated_messages( 'apply_posttype' );

				$this->_hook_paired_store_metabox( $screen->post_type );
				$this->paired__hook_tweaks_column( $screen->post_type, 12 );
				$this->paired__hook_screen_restrictposts();
				$this->postmeta__hook_meta_column_row( $screen->post_type, [
					'submission_datetime',
				] );
			}
		}

		// only for supported post-types
		$this->remove_taxonomy_submenu( $subterms );

		$this->modulelinks__hook_calendar_linked_post( $screen );
	}

	protected function paired_get_paired_constants()
	{
		return [
			'contest_posttype',
			'contest_paired',
			'section_taxonomy',
			'category_contest',
		];
	}

	public function meta_init()
	{
		$this->add_posttype_fields_for( 'meta', 'contest_posttype' );
		$this->add_posttype_fields_supported();

		if ( $this->get_setting( 'override_dates', TRUE ) )
			$this->latechores__init_post_aftercare( [
				$this->constant( 'contest_posttype' ),
				$this->constant( 'apply_posttype' ),
			] );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $contests = $this->dashboard_glance_post( 'contest_posttype' ) )
			$items[] = $contests;

		if ( $applies = $this->dashboard_glance_post( 'apply_posttype' ) )
			$items[] = $applies;

		return $items;
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'contest_paired' ) ) ) {

			if ( $post_id = $this->paired_get_to_post_id( get_queried_object(), 'contest_posttype', 'contest_paired' ) )
				WordPress\Redirect::doWP( get_permalink( $post_id ), 301 );

		} else if ( is_post_type_archive( $this->constant( 'contest_posttype' ) )
			|| is_post_type_archive( $this->constant( 'apply_posttype' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress\Redirect::doWP( $redirect, 301 );
		}
	}

	public function contest_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'paired',
			$this->constant( 'contest_posttype' ),
			$this->constant( 'contest_paired' ),
			array_merge( [
				'post_id'   => NULL,
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
		$type = $this->constant( 'contest_posttype' );
		$args = [
			'size' => WordPress\Media::getAttachmentImageDefaultSize( $type, NULL, 'medium' ),
			'type' => $type,
			'echo' => FALSE,
		];

		if ( is_singular( $args['type'] ) )
			$args['id'] = NULL;

		else if ( is_singular() )
			$args['id'] = 'paired';

		if ( ! $html = gEditorial\Template::postImage( array_merge( $args, (array) $atts ), $this->module->name ) )
			return $content;

		return gEditorial\ShortCode::wrap( $html,
			$this->constant( 'cover_shortcode' ),
			array_merge( [ 'wrap' => TRUE ], (array) $atts )
		);
	}

	protected function latechores_post_aftercare( $post )
	{
		return $this->postdate__get_post_data_for_latechores(
			$post,
			Services\PostTypeFields::getPostDateMetaKeys()
		);
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );
				$this->paired_tools_handle_tablelist( $sub );
			}

			gEditorial\Scripts::enqueueThickBox();
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo gEditorial\Settings::toolboxColumnOpen(
			_x( 'Contest Tools', 'Header', 'geditorial-contest' ) );

			$this->paired_tools_render_card( $uri, $sub );

			if ( $this->get_setting( 'override_dates', TRUE ) )
				$this->postdate__render_card_override_dates(
					$uri,
					$sub,
					[
						$this->constant( 'contest_posttype' ),
						$this->constant( 'apply_posttype' ),
					],
					_x( 'Contest/Apply Date from Meta-data', 'Card', 'geditorial-contest' )
				);

		echo '</div>';
	}

	protected function render_tools_html_before( $uri, $sub )
	{
		if ( FALSE === $this->postdate__render_before_override_dates(
			[
				$this->constant( 'contest_posttype' ),
				$this->constant( 'apply_posttype' ),
			],
			Services\PostTypeFields::getPostDateMetaKeys(),
			$uri,
			$sub,
			'tools'
		) )
			return FALSE;

		return $this->paired_tools_render_before( $uri, $sub );
	}

	public function imports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'imports', 'per_page' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'imports', $sub );
				$this->paired_imports_handle_tablelist( $sub );
			}

			gEditorial\Scripts::enqueueThickBox();
		}
	}

	protected function render_imports_html( $uri, $sub )
	{
		if ( ! $this->paired_imports_render_tablelist( $uri, $sub ) )
			return gEditorial\Info::renderNoImportsAvailable();
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', 'per_page' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->posttype_overview_render_table( 'contest_posttype', $uri, $sub ) )
			return gEditorial\Info::renderNoReportsAvailable();
	}

	public function audit_auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $terms;

		if ( $exists = term_exists( $this->constant( 'term_abandoned_apply' ), $taxonomy ) ) {

			if ( WordPress\Taxonomy::hasTerms( $this->constant( 'contest_paired' ), $post->ID ) )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

			else
				$terms[] = $exists['term_id'];
		}

		return $terms;
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Services\Modulation::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_abandoned_apply' ) => _x( 'Apply Abandoned', 'Default Term: Audit', 'geditorial-contest' ),
		] ) : $terms;
	}
}
