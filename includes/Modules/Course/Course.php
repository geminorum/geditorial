<?php namespace geminorum\gEditorial\Modules\Course;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\Taxonomy;

class Course extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'   => 'course',
			'title'  => _x( 'Course', 'Modules: Course', 'geditorial' ),
			'desc'   => _x( 'Course and Lesson Management', 'Modules: Course', 'geditorial' ),
			'icon'   => 'welcome-learn-more',
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
					'title'       => _x( 'Course Topics', 'Settings', 'geditorial-course' ),
					'description' => _x( 'Topic taxonomy for the courses and supported post-types.', 'Settings', 'geditorial-course' ),
				],
				'comment_status',
				'paired_exclude_terms' => [
					NULL,
					$this->constant( 'course_cat' ),
					$this->get_taxonomy_label( 'course_cat', 'no_terms' ),
				],
			],
			'_editlist' => [
				'admin_ordering',
			],
			'_frontend' => [
				[
					'field'       => 'redirect_archives',
					'type'        => 'url',
					'title'       => _x( 'Redirect Archives', 'Settings', 'geditorial-course' ),
					'description' => _x( 'Redirects course and lesson archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-course' ),
					'placeholder' => URL::home( 'archives' ),
				],
				[
					'field'       => 'redirect_spans',
					'type'        => 'url',
					'title'       => _x( 'Redirect Spans', 'Settings', 'geditorial-course' ),
					'description' => _x( 'Redirects all span archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-course' ),
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
				'assign_default_term',
				'shortcode_support',
				'thumbnail_support',
				'thumbnail_fallback',
				$this->settings_supports_option( 'course_cpt', TRUE ),
				$this->settings_supports_option( 'lesson_cpt', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'course_cpt' => 'course',
			'course_tax' => 'courses',
			'lesson_cpt' => 'lesson',
			'course_cat' => 'course_category',
			'span_tax'   => 'course_span',
			'topic_tax'  => 'course_topic',
			'format_tax' => 'lesson_format',
			'status_tax' => 'lesson_status',

			'course_shortcode' => 'course',
			'span_shortcode'   => 'course-span',
			'cover_shortcode'  => 'course-cover',

			'term_abandoned_lesson' => 'lesson-abandoned',
		];
	}

	protected function get_module_icons()
	{
		return [
			'post_types' => [
				'course_cpt' => NULL,
				'lesson_cpt' => 'portfolio',
			],
			'taxonomies' => [
				'course_tax' => 'welcome-learn-more',
				'course_cat' => 'category',
				'span_tax'   => 'backup',
				'topic_tax'  => 'category',
				'format_tax' => 'category',
				'status_tax' => 'post-status',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'course_cpt' => _n_noop( 'Course', 'Courses', 'geditorial-course' ),
				'course_tax' => _n_noop( 'Course', 'Courses', 'geditorial-course' ),
				'course_cat' => _n_noop( 'Course Category', 'Course Categories', 'geditorial-course' ),
				'span_tax'   => _n_noop( 'Course Span', 'Course Spans', 'geditorial-course' ),
				'topic_tax'  => _n_noop( 'Course Topic', 'Course Topics', 'geditorial-course' ),
				'lesson_cpt' => _n_noop( 'Lesson', 'Lessons', 'geditorial-course' ),
				'format_tax' => _n_noop( 'Lesson Format', 'Lesson Formats', 'geditorial-course' ),
				'status_tax' => _n_noop( 'Lesson Status', 'Lesson Statuses', 'geditorial-course' ),
			],
			'labels' => [
				'course_cpt' => [
					'featured_image' => _x( 'Poster Image', 'Label: Featured Image', 'geditorial-course' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'course_cpt' => [
				'metabox_title' => _x( 'The Course', 'MetaBox Title', 'geditorial-course' ),
			],
			'course_tax' => [
				'metabox_title' => _x( 'In This Course', 'MetaBox Title', 'geditorial-course' ),
			],
			'lesson_cpt' => [
				'metabox_title' => _x( 'Course', 'MetaBox Title', 'geditorial-course' ),
			],
		];

		$strings['misc'] = [
			'course_cpt' => [
				'children_column_title' => _x( 'Lessons', 'Column Title', 'geditorial-course' ),
			],
		];

		$strings['default_terms'] = [
			'status_tax' => [
				'ongoing'   => _x( 'Ongoing', 'Default Term', 'geditorial-course' ),
				'planned'   => _x( 'Planned', 'Default Term', 'geditorial-course' ),
				'pending'   => _x( 'Pending', 'Default Term', 'geditorial-course' ),
				'cancelled' => _x( 'Cancelled', 'Default Term', 'geditorial-course' ),
			],
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			$this->constant( 'course_cpt' ) => [
				'sub_title' => [
					'title'       => _x( 'Subtitle', 'Field Title', 'geditorial-course' ),
					'description' => _x( 'Subtitle of the Course', 'Field Description', 'geditorial-course' ),
					'type'        => 'title_after',
				],
				'lead'              => [ 'type' => 'postbox_html' ],
				'content_embed_url' => [ 'type' => 'embed' ],
				'text_source_url'   => [ 'type' => 'text_source' ],
				'audio_source_url'  => [ 'type' => 'audio_source' ],
				'video_source_url'  => [ 'type' => 'video_source' ],
				'image_source_url'  => [ 'type' => 'image_source' ],
			],
			$this->constant( 'lesson_cpt' ) => [
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

				'content_embed_url' => [ 'type' => 'embed' ],
				'text_source_url'   => [ 'type' => 'text_source' ],
				'audio_source_url'  => [ 'type' => 'audio_source' ],
				'video_source_url'  => [ 'type' => 'video_source' ],
				'image_source_url'  => [ 'type' => 'image_source' ],
			],
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'course_cpt' );
		$this->register_posttype_thumbnail( 'lesson_cpt' );

		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'course_cat', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'show_in_nav_menus'  => TRUE,
			'default_term'       => NULL,
		], 'course_cpt' );

		$this->register_taxonomy( 'span_tax', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_reverse_terms_callback',
		], 'course_cpt' );

		$this->register_taxonomy( 'format_tax', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'lesson_cpt' );

		$this->register_taxonomy( 'status_tax', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'lesson_cpt' );

		$this->paired_register_objects( 'course_cpt', 'course_tax', 'topic_tax', 'course_cat' );

		$this->register_posttype( 'lesson_cpt' );

		$this->register_shortcode( 'course_shortcode' );
		$this->register_shortcode( 'span_shortcode' );
		$this->register_shortcode( 'cover_shortcode' );

		$this->_hook_paired_thumbnail_fallback();

		$this->filter_module( 'audit', 'auto_audit_save_post', 5 );

		if ( is_admin() )
			return;

		$this->_hook_paired_exclude_from_subterm();
		$this->_hook_paired_override_term_link();
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save_posttype( 'course_cpt' ) )
			$this->_hook_paired_sync_primary_posttype();
	}

	public function setup_restapi()
	{
		$this->_hook_paired_sync_primary_posttype();
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'topic_tax' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'course_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'wp_insert_post_data', 2, 9, 'menu_order' );
				$this->filter( 'get_default_comment_status', 3 );

				$this->filter_false_module( 'meta', 'mainbox_callback', 12 );
				$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
				$this->filter_false_module( 'tweaks', 'metabox_parent' );
				remove_meta_box( 'pageparentdiv', $screen, 'side' );

				$this->class_metabox( $screen, 'mainbox' );
				add_meta_box( $this->classs( 'mainbox' ),
					$this->get_meta_box_title( 'course_cpt', FALSE ),
					[ $this, 'render_mainbox_metabox' ],
					$screen,
					'side',
					'high'
				);

				$this->_hook_post_updated_messages( 'course_cpt' );
				$this->_hook_paired_listbox( $screen );
				$this->_hook_paired_sync_primary_posttype();

			} else if ( 'edit' == $screen->base ) {

				$this->action_module( 'tweaks', 'column_attr' );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );

				$this->_hook_admin_ordering( $screen->post_type );
				$this->_hook_screen_restrict_taxonomies();
				$this->_hook_bulk_post_updated_messages( 'course_cpt' );
				$this->_hook_paired_sync_primary_posttype();
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( $subterms && $subterms === $screen->taxonomy )
				$this->filter_string( 'parent_file', sprintf( 'edit.php?post_type=%s', $this->constant( 'course_cpt' ) ) );

			if ( 'edit-tags' == $screen->base ) {

				$this->_hook_paired_taxonomy_bulk_actions( $screen->post_type, $screen->taxonomy );

			} else if ( 'post' == $screen->base ) {

				if ( $screen->post_type == $this->constant( 'lesson_cpt' ) ) {
					$this->_hook_post_updated_messages( 'lesson_cpt' );
					$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
					remove_meta_box( 'pageparentdiv', $screen, 'side' );
				}

				$this->_metabox_remove_subterm( $screen, $subterms );
				$this->_hook_paired_pairedbox( $screen, ( $screen->post_type == $this->constant( 'lesson_cpt' ) ) );
				$this->_hook_paired_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				if ( $screen->post_type == $this->constant( 'lesson_cpt' ) )
					$this->_hook_bulk_post_updated_messages( 'lesson_cpt' );

				$this->_hook_screen_restrict_paired();

				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
				$this->_hook_paired_store_metabox( $screen->post_type );
			}
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );

		if ( Settings::isDashboard( $screen ) )
			$this->filter_module( 'calendar', 'post_row_title', 4, 12 );
	}

	protected function paired_get_paired_constants()
	{
		return [ 'course_cpt', 'course_tax', 'topic_tax', 'course_cat' ];
	}

	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [
			'course_cat',
			'span_tax',
		];
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'course_cpt' ) );
		$this->add_posttype_fields( $this->constant( 'lesson_cpt' ) );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $courses = $this->dashboard_glance_post( 'course_cpt' ) )
			$items[] = $courses;

		if ( $lessons = $this->dashboard_glance_post( 'lesson_cpt' ) )
			$items[] = $lessons;

		return $items;
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'course_tax' ) ) ) {

			$term = get_queried_object();

			if ( $post_id = $this->paired_get_to_post_id( $term, 'course_cpt', 'course_tax' ) )
				WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_tax( $this->constant( 'span_tax' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_spans', FALSE ) )
				WordPress::redirect( $redirect, 301 );

		} else if ( is_post_type_archive( $this->constant( 'course_cpt' ) )
			|| is_post_type_archive( $this->constant( 'lesson_cpt' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress::redirect( $redirect, 301 );
		}
	}

	public function template_include( $template )
	{
		return $this->do_template_include( $template, 'course_cpt', NULL, FALSE );
	}

	public function template_get_archive_content_default()
	{
		return ModuleTemplate::spanTiles();
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

	public function get_linked_to_posts( $post = NULL, $single = FALSE, $published = TRUE )
	{
		return $this->paired_do_get_to_posts( 'course_cpt', 'course_tax', $post, $single, $published );
	}

	public function tweaks_column_attr( $post )
	{
		$this->paired_tweaks_column_attr( $post, 'course_cpt', 'course_tax' );
	}

	public function course_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts( 'paired',
			$this->constant( 'course_cpt' ),
			$this->constant( 'course_tax' ),
			array_merge( [
				'posttypes' => $this->posttypes(),
				'orderby'   => 'menu_order',
			], (array) $atts ),
			$content,
			$this->constant( 'course_shortcode', $tag ),
			$this->key
		);
	}

	public function span_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return Shortcode::listPosts( 'assigned',
			$this->constant( 'course_cpt' ),
			$this->constant( 'span_tax' ),
			$atts,
			$content,
			$this->constant( 'span_shortcode' )
		);
	}

	public function cover_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$type = $this->constant( 'course_cpt' );
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

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );
				$this->paired_tools_handle_tablelist( 'course_cpt', 'course_tax' );
			}
		}

		Scripts::enqueueThickBox();
	}

	protected function render_tools_html( $uri, $sub )
	{
		return $this->paired_tools_render_tablelist( 'course_cpt', 'course_tax', NULL, _x( 'Course Tools', 'Header', 'geditorial-course' ) );
	}

	protected function render_tools_html_after( $uri, $sub )
	{
		$this->paired_tools_render_card( 'course_cpt', 'course_tax' );
	}

	public function audit_auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $terms;

		if ( $exists = term_exists( $this->constant( 'term_abandoned_lesson' ), $taxonomy ) ) {

			if ( Taxonomy::hasTerms( $this->constant( 'course_tax' ), $post->ID ) )
				$terms = Arraay::stripByValue( $terms, $exists['term_id'] );

			else
				$terms[] = $exists['term_id'];
		}

		return $terms;
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Helper::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_abandoned_lesson' ) => _x( 'Lesson Abandoned', 'Default Term: Audit', 'geditorial-course' ),
		] ) : $terms;
	}
}
