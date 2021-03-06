<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\Templates\Course as ModuleTemplate;

class Course extends gEditorial\Module
{

	protected $partials = [ 'Templates' ];

	public static function module()
	{
		return [
			'name'  => 'course',
			'title' => _x( 'Course', 'Modules: Course', 'geditorial' ),
			'desc'  => _x( 'Course and Lesson Management', 'Modules: Course', 'geditorial' ),
			'icon'  => 'welcome-learn-more',
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
			],
			'_editlist' => [
				'admin_ordering',
				'admin_restrict',
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
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'course_cpt', TRUE ),
				$this->settings_supports_option( 'lesson_cpt', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'course_cpt'         => 'course',
			'course_cpt_archive' => 'courses',
			'course_tax'         => 'courses',
			'lesson_cpt'         => 'lesson',
			'lesson_cpt_archive' => 'lessons',
			'course_cat'         => 'course_category',
			'course_cat_slug'    => 'course-categories',
			'span_tax'           => 'course_span',
			'topic_tax'          => 'course_topic',
			'format_tax'         => 'lesson_format',
			'status_tax'         => 'lesson_status',
			'status_tax_slug'    => 'lesson-statuses',
			'course_shortcode'   => 'course',
			'span_shortcode'     => 'course-span',
			'cover_shortcode'    => 'course-cover',
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
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'course_cpt' => [
				'featured'              => _x( 'Poster Image', 'Posttype Featured', 'geditorial-course' ),
				'meta_box_title'        => _x( 'Metadata', 'MetaBox Title', 'geditorial-course' ),
				'children_column_title' => _x( 'Lessons', 'Column Title', 'geditorial-course' ),
				'show_option_none'      => _x( '&ndash; Select Course &ndash;', 'Select Option None', 'geditorial-course' ),
			],
			'course_tax' => [
				'meta_box_title' => _x( 'In This Course', 'MetaBox Title', 'geditorial-course' ),
			],
			'course_cat' => [
				'tweaks_column_title' => _x( 'Course Categories', 'Column Title', 'geditorial-course' ),
			],
			'span_tax' => [
				'meta_box_title'      => _x( 'Spans', 'MetaBox Title', 'geditorial-course' ),
				'tweaks_column_title' => _x( 'Course Spans', 'Column Title', 'geditorial-course' ),
			],
			'topic_tax' => [
				'meta_box_title'      => _x( 'Topics', 'MetaBox Title', 'geditorial-course' ),
				'tweaks_column_title' => _x( 'Course Topics', 'Column Title', 'geditorial-course' ),
				'show_option_none'    => _x( '&ndash; Select Topic &ndash;', 'Select Option None', 'geditorial-course' ),
			],
			'lesson_cpt' => [
				'meta_box_title' => _x( 'Course', 'MetaBox Title', 'geditorial-course' ),
			],
			'format_tax' => [
				'meta_box_title'      => _x( 'Lesson Format', 'MetaBox Title', 'geditorial-course' ),
				'tweaks_column_title' => _x( 'Lesson Format', 'Column Title', 'geditorial-course' ),
			],
			'status_tax' => [
				'meta_box_title'      => _x( 'Lesson Statuses', 'MetaBox Title', 'geditorial-course' ),
				'tweaks_column_title' => _x( 'Lesson Statuses', 'Column Title', 'geditorial-course' ),
			],
			'meta_box_title'      => _x( 'Courses', 'MetaBox Title', 'geditorial-course' ),
			'tweaks_column_title' => _x( 'Courses', 'Column Title', 'geditorial-course' ),
		];

		$strings['terms'] = [
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
			],
			$this->constant( 'lesson_cpt' ) => [
				'over_title' => [ 'type' => 'title_before' ],
				'sub_title'  => [
					'title'       => _x( 'Subtitle', 'Field Title', 'geditorial-course' ),
					'description' => _x( 'Subtitle of the Lesson', 'Field Description', 'geditorial-course' ),
					'type'        => 'title_after',
				],
				'byline'       => [ 'type' => 'text', 'quickedit' => TRUE ],
				'published'    => [ 'type' => 'text', 'quickedit' => TRUE ],
				'source_title' => [ 'type' => 'text' ],
				'source_url'   => [ 'type' => 'link' ],
				'highlight'    => [ 'type' => 'note' ],
			],
		];
	}

	protected function posttypes_excluded()
	{
		return Settings::posttypesExcluded( $this->constant( 'course_cpt' ) );
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

		$this->register_button( 'install_def_status_tax', _x( 'Install Default Lesson Statuses', 'Button', 'geditorial-course' ) );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'course_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'course_cat', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'course_cpt' );

		$this->register_taxonomy( 'span_tax', [
			'hierarchical'       => TRUE, // required by `MetaBox::checklistTerms()`
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'course_cpt' );

		if ( $this->get_setting( 'subterms_support' ) )
			$this->register_taxonomy( 'topic_tax', [
				'hierarchical'       => TRUE,
				'meta_box_cb'        => NULL,
				'show_admin_column'  => FALSE,
				'show_in_quick_edit' => FALSE,
				'show_in_nav_menus'  => TRUE,
			], $this->posttypes( 'course_cpt' ) );

		$this->register_taxonomy( 'course_tax', [
			'show_ui'            => FALSE,
			'show_in_menu'       => FALSE,
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => FALSE,
		] );

		$this->register_taxonomy( 'format_tax', [
			'hierarchical'       => TRUE, // required by `MetaBox::checklistTerms()`
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'lesson_cpt' );

		$this->register_taxonomy( 'status_tax', [
			'hierarchical'       => TRUE, // required by `MetaBox::checklistTerms()`
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'lesson_cpt' );

		$this->register_posttype( 'course_cpt', [
			'hierarchical' => TRUE,
		] );

		$this->register_posttype( 'lesson_cpt' );

		$this->register_shortcode( 'course_shortcode' );
		$this->register_shortcode( 'span_shortcode' );
		$this->register_shortcode( 'cover_shortcode' );

		$this->register_default_terms( 'status_tax' );

		if ( is_admin() )
			return;

		$this->filter( 'term_link', 3 );
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, 'course_cpt' ) )
			$this->_sync_linked( $_REQUEST['post_type'] );
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'topic_tax' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'course_cpt' ) ) {

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
					$this->get_meta_box_title( 'course_cpt', FALSE ),
					[ $this, 'render_mainbox_metabox' ],
					$screen,
					'side',
					'high'
				);

				$this->class_metabox( $screen, 'listbox' );
				add_meta_box( $this->classs( 'listbox' ),
					$this->get_meta_box_title( 'course_tax' ),
					[ $this, 'render_listbox_metabox' ],
					$screen,
					'advanced',
					'low'
				);

				$this->_sync_linked( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );

				if ( $this->get_setting( 'admin_restrict', FALSE ) ) {
					$this->action( 'restrict_manage_posts', 2, 12 );
					$this->filter( 'parse_query' );
				}

				if ( $this->get_setting( 'admin_ordering', TRUE ) )
					$this->action( 'pre_get_posts' );

				$this->_sync_linked( $screen->post_type );

				$this->action_module( 'tweaks', 'column_attr' );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				if ( $screen->post_type == $this->constant( 'lesson_cpt' ) )
					$this->filter( 'post_updated_messages', 1, 10, 'supported' );

				if ( $subterms )
					remove_meta_box( $subterms.'div', $screen->post_type, 'side' );

				$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
				remove_meta_box( 'pageparentdiv', $screen, 'side' );

				$this->class_metabox( $screen, 'linkedbox' );
				add_meta_box( $this->classs( 'linkedbox' ),
					$this->get_meta_box_title( 'lesson_cpt' ),
					[ $this, 'render_linkedbox_metabox' ],
					$screen,
					'side'
				);

				add_action( $this->hook( 'render_linkedbox_metabox' ), [ $this, 'render_metabox' ], 10, 4 );

			} else if ( 'edit' == $screen->base ) {

				if ( $screen->post_type == $this->constant( 'lesson_cpt' ) )
					$this->filter( 'bulk_post_updated_messages', 2, 10, 'supported' );

				if ( $this->get_setting( 'admin_restrict', FALSE ) )
					$this->action( 'restrict_manage_posts', 2, 12, 'supported' );

				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}

			$this->_hook_store_metabox( $screen->post_type );
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );

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

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->constant( 'course_tax' ) != $taxonomy )
			return $link;

		if ( $post_id = $this->get_linked_post_id( $term, 'course_cpt', 'course_tax' ) )
			return get_permalink( $post_id );

		return $link;
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'course_tax' ) ) ) {

			$term = get_queried_object();

			if ( $post_id = $this->get_linked_post_id( $term, 'course_cpt', 'course_tax' ) )
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

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'render_listbox_metabox', $post, $box, NULL, 'listbox_course' );

			$term = $this->get_linked_term( $post->ID, 'course_cpt', 'course_tax' );

			if ( $list = MetaBox::getTermPosts( $this->constant( 'course_tax' ), $term, $this->posttypes() ) )
				echo $list;

			else
				HTML::desc( _x( 'No items connected!', 'Message', 'geditorial-course' ), FALSE, '-empty' );

		echo '</div>';
	}

	public function render_linkedbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

		if ( ! Taxonomy::hasTerms( $this->constant( 'course_tax' ) ) ) {

			MetaBox::fieldEmptyPostType( $this->constant( 'course_cpt' ) );

		} else {

			$this->actions( 'render_linkedbox_metabox', $post, $box, NULL, 'linkedbox_course' );

			do_action( 'geditorial_meta_render_metabox', $post, $box, NULL, 'linkedbox_course' );
		}

		echo '</div>';
	}

	public function render_metabox( $post, $box, $fields = NULL, $context = NULL )
	{
		$this->do_render_metabox_assoc( $post, 'course_cpt', 'course_tax', 'topic_tax' );

		MetaBox::fieldPostMenuOrder( $post );
	}

	public function store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		$this->do_store_metabox_assoc( $post, 'course_cpt', 'course_tax', 'topic_tax' );
	}

	public function meta_box_cb_lesson_format( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, [ 'taxonomy' => $box['args']['taxonomy'], 'posttype' => $post->post_type ] );
		echo '</div>';
	}

	public function meta_box_cb_status_tax( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, [ 'taxonomy' => $box['args']['taxonomy'], 'posttype' => $post->post_type ] );
		echo '</div>';
	}

	public function meta_box_cb_span_tax( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, [ 'taxonomy' => $box['args']['taxonomy'], 'posttype' => $post->post_type ] );
		echo '</div>';
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'course_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'course_cpt', $counts ) );
	}

	public function post_updated_messages_supported( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'lesson_cpt' ) );
	}

	public function bulk_post_updated_messages_supported( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'lesson_cpt', $counts ) );
	}

	public function post_updated( $post_id, $post_after, $post_before )
	{
		if ( ! $this->is_save_post( $post_after, 'course_cpt' ) )
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

		$the_term = get_term_by( 'slug', $post_before->post_name, $this->constant( 'course_tax' ) );

		if ( FALSE === $the_term ) {
			$the_term = get_term_by( 'slug', $post_after->post_name, $this->constant( 'course_tax' ) );
			if ( FALSE === $the_term )
				$term = wp_insert_term( $post_after->post_title, $this->constant( 'course_tax' ), $args );
			else
				$term = wp_update_term( $the_term->term_id, $this->constant( 'course_tax' ), $args );
		} else {
			$term = wp_update_term( $the_term->term_id, $this->constant( 'course_tax' ), $args );
		}

		if ( ! is_wp_error( $term ) )
			$this->set_linked_term( $post_id, $term['term_id'], 'course_cpt', 'course_tax' );
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

		$term = wp_insert_term( $post->post_title, $this->constant( 'course_tax' ), $args );

		if ( ! is_wp_error( $term ) )
			$this->set_linked_term( $post_id, $term['term_id'], 'course_cpt', 'course_tax' );
	}

	public function wp_trash_post( $post_id )
	{
		$this->do_trash_post( $post_id, 'course_cpt', 'course_tax' );
	}

	public function untrash_post( $post_id )
	{
		$this->do_untrash_post( $post_id, 'course_cpt', 'course_tax' );
	}

	public function before_delete_post( $post_id )
	{
		$this->do_before_delete_post( $post_id, 'course_cpt', 'course_tax' );
	}

	public function restrict_manage_posts( $posttype, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'span_tax' );
	}

	public function restrict_manage_posts_supported( $posttype, $which )
	{
		$this->do_restrict_manage_posts_posts( 'course_tax', 'course_cpt' );
	}

	public function parse_query( &$query )
	{
		$this->do_parse_query_taxes( $query, 'span_tax' );
	}

	public function pre_get_posts( &$wp_query )
	{
		if ( $this->constant( 'course_cpt' ) == $wp_query->get( 'post_type' ) ) {

			if ( $wp_query->is_admin ) {

				if ( ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', 'menu_order' );

				if ( ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', 'DESC' );
			}
		}
	}

	public function get_assoc_post( $post = NULL, $single = FALSE, $published = TRUE )
	{
		$posts = [];
		$terms = Taxonomy::getTerms( $this->constant( 'course_tax' ), $post, TRUE );

		foreach ( $terms as $term ) {

			if ( ! $linked = $this->get_linked_post_id( $term, 'course_cpt', 'course_tax' ) )
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
		$posts = $this->get_linked_posts( $post->ID, 'course_cpt', 'course_tax' );
		$count = count( $posts );

		if ( ! $count )
			return;

		echo '<li class="-row -course -children">';

			echo $this->get_column_icon( FALSE, NULL, $this->get_column_title( 'children', 'course_cpt' ) );

			$posttypes = array_unique( array_map( function( $r ){
				return $r->post_type;
			}, $posts ) );

			$args = [ $this->constant( 'course_tax' ) => $post->post_name ];

			if ( empty( $this->cache_posttypes ) )
				$this->cache_posttypes = PostType::get( 2 );

			echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

			$list = [];

			foreach ( $posttypes as $posttype )
				$list[] = HTML::tag( 'a', [
					'href'   => WordPress::getPostTypeEditLink( $posttype, 0, $args ),
					'title'  => _x( 'View the connected list', 'Title Attr', 'geditorial-course' ),
					'target' => '_blank',
				], $this->cache_posttypes[$posttype] );

			echo Helper::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

		echo '</li>';
	}

	// TODO: migrate to `Shortcode::listPosts( 'associated' );`
	public function course_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::getAssocPosts(
			$this->constant( 'course_cpt' ),
			$this->constant( 'course_tax' ),
			array_merge( [
				'posttypes' => $this->posttypes(),
				'orderby'   => 'menu_order', // order by meta
			], (array) $atts ),
			$content,
			$this->constant( 'course_shortcode' )
		);
	}

	public function span_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::getTermPosts(
			$this->constant( 'course_cpt' ),
			$this->constant( 'span_tax' ),
			$atts,
			$content,
			$this->constant( 'span_shortcode' )
		);
	}

	public function cover_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = [
			'size' => $this->get_image_size_key( 'course_cpt', 'medium' ),
			'type' => $this->constant( 'course_cpt' ),
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
}
