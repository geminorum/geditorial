<?php namespace geminorum\gEditorial\Modules\Symposium;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;

class Symposium extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'   => 'symposium',
			'title'  => _x( 'Symposium', 'Modules: Symposium', 'geditorial' ),
			'desc'   => _x( 'Editorial Session Management', 'Modules: Symposium', 'geditorial' ),
			'icon'   => 'welcome-learn-more',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'comment_status',
			],
			'_content' => [
				'archive_title',
				'archive_content',
				'archive_template',
			],
			'_supports' => [
				'assign_default_term',
				'thumbnail_support',
				$this->settings_supports_option( 'session_posttype', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'session_posttype'  => 'session',
			'category_taxonomy' => 'session_category',
			'type_taxonomy'     => 'session_type',
			'audience_taxonomy' => 'session_audience',
			'subject_taxonomy'  => 'session_subject',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'session_posttype'  => _n_noop( 'Session', 'Sessions', 'geditorial-symposium' ),
				'category_taxonomy' => _n_noop( 'Session Category', 'Session Categories', 'geditorial-symposium' ),
				'type_taxonomy'     => _n_noop( 'Session Type', 'Session Types', 'geditorial-symposium' ),
				'audience_taxonomy' => _n_noop( 'Session Audience', 'Session Audiences', 'geditorial-symposium' ),
				'subject_taxonomy'  => _n_noop( 'Session Subject', 'Session Subjects', 'geditorial-symposium' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'session_posttype' => [
				'featured' => _x( 'Poster Image', 'Session: Featured', 'geditorial-symposium' ),
			],
		];

		$strings['default_terms'] = [
			'type_taxonomy' => [
				'lecture'     => _x( 'Lecture', 'Session Type: Default Term', 'geditorial-symposium' ),
				'conference'  => _x( 'Conference', 'Session Type: Default Term', 'geditorial-symposium' ),
				'colloquium'  => _x( 'Colloquium', 'Session Type: Default Term', 'geditorial-symposium' ),
				'book-review' => _x( 'Book Review', 'Session Type: Default Term', 'geditorial-symposium' ),
			],
		];

		return $strings;
	}

	public function get_global_fields()
	{
		return [
			$this->constant( 'session_posttype' ) => [
				'over_title' => [ 'type' => 'title_before' ],
				'sub_title'  => [ 'type' => 'title_after' ],
				'lead'       => [ 'type' => 'postbox_html' ],

				'featured_people' => [
					'title'       => _x( 'Featured People', 'Field Title', 'geditorial-symposium' ),
					'description' => _x( 'Featured People in the Session', 'Field Description', 'geditorial-symposium' ),
					'icon'        => 'groups',
					'quickedit'   => TRUE,
				],

				'published'    => [ 'type' => 'text', 'quickedit' => TRUE ],
				'source_title' => [ 'type' => 'text' ],
				'source_url'   => [ 'type' => 'link' ],
				'highlight'    => [ 'type' => 'note' ],
				'dashboard'    => [ 'type' => 'postbox_html' ],
				'abstract'     => [ 'type' => 'postbox_html' ],

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
		$this->register_posttype_thumbnail( 'session_posttype' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'session_posttype' ) );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'category_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'session_posttype' );

		$this->register_taxonomy( 'subject_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL,
		], 'session_posttype' );

		$this->register_taxonomy( 'type_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => '__checklist_terms_callback',
		], 'session_posttype' );

		$this->register_taxonomy( 'audience_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => '__checklist_terms_callback',
		], 'session_posttype' );

		$this->register_posttype( 'session_posttype', [
			'primary_taxonomy' => $this->constant( 'category_taxonomy' ),
		] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'session_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );
				$this->_hook_post_updated_messages( 'session_posttype' );

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->_hook_screen_restrict_taxonomies();
				$this->_hook_bulk_post_updated_messages( 'session_posttype' );
			}
		}
	}

	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [
			'type_taxonomy',
			'category_taxonomy',
			'subject_taxonomy',
			'audience_taxonomy',
		];
	}

	public function template_include( $template )
	{
		return $this->do_template_include( $template, 'session_posttype', NULL, FALSE );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'session_posttype' ) )
			$items[] = $glance;

		return $items;
	}
}
