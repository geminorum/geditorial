<?php namespace geminorum\gEditorial\Modules\Course;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Course extends gEditorial\Module
{
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\LateChores;
	use Internals\MetaBoxMain;
	use Internals\PairedAdmin;
	use Internals\PairedCore;
	use Internals\PairedFront;
	use Internals\PairedMetaBox;
	use Internals\PairedRest;
	use Internals\PairedRowActions;
	use Internals\PairedThumbnail;
	use Internals\PairedTools;
	use Internals\PostDate;
	use Internals\PostTypeOverview;
	use Internals\TemplatePostType;

	public static function module()
	{
		return [
			'name'     => 'course',
			'title'    => _x( 'Course', 'Modules: Course', 'geditorial-admin' ),
			'desc'     => _x( 'Course and Lesson Management', 'Modules: Course', 'geditorial-admin' ),
			'icon'     => 'welcome-learn-more',
			'access'   => 'beta',
			'keywords' => [
				'course',
				'lesson',
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
					'title'       => _x( 'Course Topics', 'Settings', 'geditorial-course' ),
					'description' => _x( 'Topic taxonomy for the courses and supported post-types.', 'Settings', 'geditorial-course' ),
				],
				'comment_status',
				'paired_exclude_terms' => [
					NULL,
					$this->constant( 'category_taxonomy' ),
					$this->get_taxonomy_label( 'category_taxonomy', 'no_terms' ),
				],
			],
			'_editlist' => [
				'admin_ordering',
				'admin_bulkactions',
				'show_in_quickedit' => [ $this->get_taxonomy_show_in_quickedit_desc( 'status_taxonomy' ), '1' ],
			],
			'_frontend' => [
				[
					'field'       => 'redirect_archives',
					'type'        => 'url',
					'title'       => _x( 'Redirect Archives', 'Settings', 'geditorial-course' ),
					'description' => _x( 'Redirects course and lesson archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-course' ),
					'placeholder' => Core\URL::home( 'archives' ),
				],
				[
					'field'       => 'redirect_spans',
					'type'        => 'url',
					'title'       => _x( 'Redirect Spans', 'Settings', 'geditorial-course' ),
					'description' => _x( 'Redirects all span archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-course' ),
					'placeholder' => Core\URL::home( 'archives' ),
				],
			],
			'_content' => [
				'archive_override',
				'archive_title',
				'archive_content',
				'archive_template',
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'override_dates',
				'assign_default_term',
				'shortcode_support',
				'thumbnail_support',
				'thumbnail_fallback',
				$this->settings_supports_option( 'course_posttype', TRUE ),
				$this->settings_supports_option( 'lesson_posttype', TRUE ),
			],
			'_reports' => [
				'overview_taxonomies' => [ NULL, $this->get_posttype_taxonomies_list( 'course_posttype' ) ],
				'overview_fields'     => [ NULL, $this->get_posttype_fields_list( 'course_posttype', 'meta' ) ],
				'overview_units'      => [ NULL, $this->get_posttype_fields_list( 'course_posttype', 'units' ) ],
			],
			'_constants' => [
				'main_shortcode_constant'  => [ NULL, 'course' ],
				'span_shortcode_constant'  => [ NULL, 'course-span' ],
				'cover_shortcode_constant' => [ NULL, 'course-cover' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'course_posttype'   => 'course',
			'course_paired'     => 'courses',
			'lesson_posttype'   => 'lesson',
			'category_taxonomy' => 'course_category',
			'span_taxonomy'     => 'course_span',
			'topic_taxonomy'    => 'course_topic',
			'format_taxonomy'   => 'lesson_format',
			'status_taxonomy'   => 'lesson_status',

			'main_shortcode'  => 'course',
			'span_shortcode'  => 'course-span',
			'cover_shortcode' => 'course-cover',

			'term_abandoned_lesson' => 'lesson-abandoned',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'course_posttype'   => _n_noop( 'Course', 'Courses', 'geditorial-course' ),
				'course_paired'     => _n_noop( 'Course', 'Courses', 'geditorial-course' ),
				'category_taxonomy' => _n_noop( 'Course Category', 'Course Categories', 'geditorial-course' ),
				'span_taxonomy'     => _n_noop( 'Course Span', 'Course Spans', 'geditorial-course' ),
				'topic_taxonomy'    => _n_noop( 'Course Topic', 'Course Topics', 'geditorial-course' ),
				'lesson_posttype'   => _n_noop( 'Lesson', 'Lessons', 'geditorial-course' ),
				'format_taxonomy'   => _n_noop( 'Lesson Format', 'Lesson Formats', 'geditorial-course' ),
				'status_taxonomy'   => _n_noop( 'Lesson Status', 'Lesson Statuses', 'geditorial-course' ),
			],
			'labels' => [
				'course_posttype' => [
					'featured_image' => _x( 'Poster Image', 'Label: Featured Image', 'geditorial-course' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'course_posttype' => [
				'metabox_title' => _x( 'The Course', 'MetaBox Title', 'geditorial-course' ),
				'listbox_title' => _x( 'In This Course', 'MetaBox Title', 'geditorial-course' ),
			],
			'lesson_posttype' => [
				'metabox_title' => _x( 'Course', 'MetaBox Title', 'geditorial-course' ),
			],
		];

		$strings['misc'] = [
			'course_posttype' => [
				'children_column_title' => _x( 'Lessons', 'Column Title', 'geditorial-course' ),
			],
			'course_paired' => [
				'column_icon_title' => _x( 'Course', 'Misc: `column_icon_title`', 'geditorial-course' ),
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'status_taxonomy' => [
				'ongoing'   => _x( 'Ongoing', 'Default Term', 'geditorial-course' ),
				'planned'   => _x( 'Planned', 'Default Term', 'geditorial-course' ),
				'pending'   => _x( 'Pending', 'Default Term', 'geditorial-course' ),
				'cancelled' => _x( 'Cancelled', 'Default Term', 'geditorial-course' ),
			],
		];
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
				$this->constant( 'course_posttype' ) => [
					'sub_title' => [
						'title'       => _x( 'Subtitle', 'Field Title', 'geditorial-course' ),
						'description' => _x( 'Subtitle of the Course', 'Field Description', 'geditorial-course' ),
						'type'        => 'title_after',
					],
					'lead'              => [ 'type' => 'postbox_html' ],
					'date'              => [ 'type' => 'date',     'quickedit' => TRUE ],
					'datetime'          => [ 'type' => 'datetime', 'quickedit' => TRUE ],
					'datestart'         => [ 'type' => 'datetime', 'quickedit' => TRUE ],
					'dateend'           => [ 'type' => 'datetime', 'quickedit' => TRUE ],
					'content_fee'       => [ 'type' => 'price' ],
					'content_embed_url' => [ 'type' => 'embed' ],
					'text_source_url'   => [ 'type' => 'text_source' ],
					'audio_source_url'  => [ 'type' => 'audio_source' ],
					'video_source_url'  => [ 'type' => 'video_source' ],
					'image_source_url'  => [ 'type' => 'image_source' ],
				],
				$this->constant( 'lesson_posttype' ) => [
					'over_title' => [ 'type' => 'title_before' ],
					'sub_title'  => [
						'title'       => _x( 'Subtitle', 'Field Title', 'geditorial-course' ),
						'description' => _x( 'Subtitle of the Lesson', 'Field Description', 'geditorial-course' ),
						'type'        => 'title_after',
					],
					'lead'         => [ 'type' => 'postbox_html' ],
					'byline'       => [ 'type' => 'text', 'quickedit' => TRUE ],
					'published'    => [ 'type' => 'text', 'quickedit' => TRUE ],
					'source_title' => [ 'type' => 'text' ],
					'source_url'   => [ 'type' => 'link' ],
					'highlight'    => [ 'type' => 'note' ],

					'event_summary' => [ 'type' => 'text' ],
					'date'          => [ 'type' => 'date',     'quickedit' => TRUE ],
					'datetime'      => [ 'type' => 'datetime', 'quickedit' => TRUE ],
					'datestart'     => [ 'type' => 'datetime', 'quickedit' => TRUE ],
					'dateend'       => [ 'type' => 'datetime', 'quickedit' => TRUE ],

					'content_embed_url' => [ 'type' => 'embed' ],
					'text_source_url'   => [ 'type' => 'text_source' ],
					'audio_source_url'  => [ 'type' => 'audio_source' ],
					'video_source_url'  => [ 'type' => 'video_source' ],
					'image_source_url'  => [ 'type' => 'image_source' ],
				],
			],
		];
	}

	protected function paired_get_paired_constants()
	{
		return [
			'course_posttype',
			'course_paired',
			'topic_taxonomy',
			'category_taxonomy',
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'course_posttype' );
		$this->register_posttype_thumbnail( 'lesson_posttype' );

		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'category_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'show_in_nav_menus'  => TRUE,
			'default_term'       => NULL,
		], 'course_posttype', [
			'custom_icon' => 'category',
		] );

		$this->register_taxonomy( 'span_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_reverse_terms_callback',
		], 'course_posttype', [] );

		$this->register_taxonomy( 'format_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'lesson_posttype', [
			'custom_icon' => 'category',
		] );

		$this->register_taxonomy( 'status_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit', TRUE ),
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'lesson_posttype', [
			'admin_managed'   => TRUE,
			'single_selected' => TRUE,
		] );

		$this->paired_register( [], [
			'primary_taxonomy' => $this->constant( 'category_taxonomy' ),
			'ical_source'      => 'paired',
		] );

		$this->register_posttype( 'lesson_posttype', [], [
			'status_taxonomy' => TRUE,
		] );

		$this->register_shortcode( 'main_shortcode' );
		$this->register_shortcode( 'span_shortcode' );
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
		if ( $posttype = $this->is_inline_save_posttype( 'course_posttype' ) ) {
			$this->pairedadmin__hook_tweaks_column_connected( $posttype );
		}
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'topic_taxonomy' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'course_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->comments__handle_default_status( $screen->post_type );
				$this->posttypes__increase_menu_order( $screen->post_type );
				$this->posttypes__media_register_headerbutton( 'course_posttype' );
				$this->_hook_post_updated_messages( 'course_posttype' );
				$this->_hook_paired_mainbox( $screen );
				$this->_hook_paired_listbox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->modulelinks__register_headerbuttons();
				$this->latechores__hook_admin_bulkactions( $screen );
				$this->coreadmin__hook_admin_ordering( $screen->post_type );
				$this->_hook_bulk_post_updated_messages( 'course_posttype' );
				$this->pairedadmin__hook_tweaks_column_connected( $screen->post_type );
				$this->pairedcore__hook_sync_paired();
				$this->corerestrictposts__hook_screen_taxonomies( [
					'category_taxonomy',
					'span_taxonomy',
				] );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( $subterms && $subterms === $screen->taxonomy )
				$this->filter_string( 'parent_file', sprintf( 'edit.php?post_type=%s', $this->constant( 'course_posttype' ) ) );

			if ( 'edit-tags' == $screen->base ) {

				$this->_hook_paired_taxonomy_bulk_actions( $screen->post_type, $screen->taxonomy );

			} else if ( 'post' == $screen->base ) {

				if ( $screen->post_type == $this->constant( 'lesson_posttype' ) ) {
					$this->posttypes__media_register_headerbutton( 'lesson_posttype' );
					$this->_hook_post_updated_messages( 'lesson_posttype' );
					$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
					remove_meta_box( 'pageparentdiv', $screen, 'side' );
				}

				$this->_metabox_remove_subterm( $screen, $subterms );
				$this->_hook_paired_pairedbox( $screen, ( $screen->post_type == $this->constant( 'lesson_posttype' ) ) );
				$this->_hook_paired_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				if ( $screen->post_type == $this->constant( 'lesson_posttype' ) )
					$this->_hook_bulk_post_updated_messages( 'lesson_posttype' );

				$this->latechores__hook_admin_bulkactions( $screen );
				$this->_hook_paired_store_metabox( $screen->post_type );
				$this->paired__hook_tweaks_column( $screen->post_type, 12 );
				$this->paired__hook_screen_restrictposts();
			}
		}

		// only for supported post-types
		$this->remove_taxonomy_submenu( $subterms );

		$this->modulelinks__hook_calendar_linked_post( $screen );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_for( 'meta', 'course_posttype' );
		$this->add_posttype_fields_for( 'meta', 'lesson_posttype' );

		if ( $this->get_setting( 'override_dates', TRUE ) )
			$this->latechores__init_post_aftercare( [
				$this->constant( 'course_posttype' ),
				$this->constant( 'lesson_posttype' ),
			] );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $courses = $this->dashboard_glance_post( 'course_posttype' ) )
			$items[] = $courses;

		if ( $lessons = $this->dashboard_glance_post( 'lesson_posttype' ) )
			$items[] = $lessons;

		return $items;
	}

	public function template_redirect()
	{
		if ( is_robots() || is_favicon() || is_feed() )
			return;

		if ( is_tax( $this->constant( 'course_paired' ) ) ) {

			if ( $post_id = $this->paired_get_to_post_id( get_queried_object(), 'course_posttype', 'course_paired' ) )
				WordPress\Redirect::doWP( get_permalink( $post_id ), 301 );

		} else if ( is_tax( $this->constant( 'span_taxonomy' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_spans', FALSE ) )
				WordPress\Redirect::doWP( $redirect, 301 );

		} else if ( is_post_type_archive( $this->constant( 'course_posttype' ) )
			|| is_post_type_archive( $this->constant( 'lesson_posttype' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress\Redirect::doWP( $redirect, 301 );
		}
	}

	public function template_include( $template )
	{
		return $this->templateposttype__include( $template, $this->constant( 'course_posttype' ), FALSE );
	}

	public function templateposttype_get_archive_content_default( $posttype )
	{
		return ModuleTemplate::spanTiles();
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'paired',
			$this->constant( 'course_posttype' ),
			$this->constant( 'course_paired' ),
			array_merge( [
				'post_id'   => NULL,
				'posttypes' => $this->posttypes(),
				'orderby'   => 'menu_order',
			], (array) $atts ),
			$content,
			$this->constant( 'main_shortcode', $tag ),
			$this->key
		);
	}

	public function span_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'assigned',
			$this->constant( 'course_posttype' ),
			$this->constant( 'span_taxonomy' ),
			array_merge( [
				'post_id' => NULL,
			], (array) $atts ),
			$content,
			$this->constant( 'span_shortcode' )
		);
	}

	public function cover_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$type = $this->constant( 'course_posttype' );
		$args = [
			'size' => WordPress\Media::getAttachmentImageDefaultSize( $type, NULL, 'medium' ),
			'type' => $type,
			'echo' => FALSE,
		];

		if ( is_singular( $args['type'] ) )
			$args['id'] = NULL;

		else if ( is_singular() )
			$args['id'] = 'paired';

		if ( ! $html = ModuleTemplate::postImage( array_merge( $args, (array) $atts ) ) )
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
			_x( 'Course Tools', 'Header', 'geditorial-course' ) );

			$this->paired_tools_render_card( $uri, $sub );

			if ( $this->get_setting( 'override_dates', TRUE ) )
				$this->postdate__render_card_override_dates(
					$uri,
					$sub,
					[
						$this->constant( 'course_posttype' ),
						$this->constant( 'lesson_posttype' ),
					],
					_x( 'Course/Lesson Date from Meta-data', 'Card', 'geditorial-course' )
				);

		echo '</div>';
	}

	protected function render_tools_html_before( $uri, $sub )
	{
		if ( FALSE === $this->postdate__render_before_override_dates(
			[
				$this->constant( 'course_posttype' ),
				$this->constant( 'lesson_posttype' ),
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
		if ( ! $this->posttype_overview_render_table( 'course_posttype', $uri, $sub ) )
			return gEditorial\Info::renderNoReportsAvailable();
	}

	public function audit_auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $terms;

		if ( $exists = term_exists( $this->constant( 'term_abandoned_lesson' ), $taxonomy ) ) {

			if ( WordPress\Taxonomy::hasTerms( $this->constant( 'course_paired' ), $post->ID ) )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

			else
				$terms[] = $exists['term_id'];
		}

		return $terms;
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Services\Modulation::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_abandoned_lesson' ) => _x( 'Lesson Abandoned', 'Default Term: Audit', 'geditorial-course' ),
		] ) : $terms;
	}
}
