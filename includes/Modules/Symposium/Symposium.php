<?php namespace geminorum\gEditorial\Modules\Symposium;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Symposium extends gEditorial\Module
{
	use Internals\BulkExports;
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;
	use Internals\PostTypeOverview;
	use Internals\TemplatePostType;

	public static function module()
	{
		return [
			'name'     => 'symposium',
			'title'    => _x( 'Symposium', 'Modules: Symposium', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Session Management', 'Modules: Symposium', 'geditorial-admin' ),
			'icon'     => 'welcome-learn-more',
			'access'   => 'beta',
			'keywords' => [
				'session',
				'cptmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'comment_status',
			],
			'_content' => [
				'archive_override',
				'archive_title',
				'archive_content',
				'archive_template',
			],
			'_supports' => [
				'assign_default_term',
				'thumbnail_support',
				$this->settings_supports_option( 'main_posttype', TRUE ),
			],
			'_reports' => [
				'overview_taxonomies' => [ NULL, $this->get_posttype_taxonomies_list( 'main_posttype' ) ],
				'overview_fields'     => [ NULL, $this->get_posttype_fields_list( 'main_posttype', 'meta' ) ],
				'overview_units'      => [ NULL, $this->get_posttype_fields_list( 'main_posttype', 'units' ) ],
			],
			'_constants' => [
				'main_posttype_constant'     => [ NULL, 'entry' ],
				'category_taxonomy_constant' => [ NULL, 'entry_section' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_posttype'     => 'session',
			'category_taxonomy' => 'session_category',
			'type_taxonomy'     => 'session_type',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_posttype'     => _n_noop( 'Session', 'Sessions', 'geditorial-symposium' ),
				'category_taxonomy' => _n_noop( 'Session Category', 'Session Categories', 'geditorial-symposium' ),
				'type_taxonomy'     => _n_noop( 'Session Type', 'Session Types', 'geditorial-symposium' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'main_posttype' => [
				'featured' => _x( 'Poster Image', 'Session: Featured', 'geditorial-symposium' ),
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'type_taxonomy' => [
				'lecture'     => _x( 'Lecture', 'Session Type: Default Term', 'geditorial-symposium' ),
				'conference'  => _x( 'Conference', 'Session Type: Default Term', 'geditorial-symposium' ),
				'colloquium'  => _x( 'Colloquium', 'Session Type: Default Term', 'geditorial-symposium' ),
				'book-review' => _x( 'Book Review', 'Session Type: Default Term', 'geditorial-symposium' ),
			],
		];
	}

	public function get_global_fields()
	{
		return [
			'meta' => [
				$this->constant( 'main_posttype' ) => [
					'over_title' => [ 'type' => 'title_before' ],
					'sub_title'  => [ 'type' => 'title_after' ],
					'lead'       => [ 'type' => 'postbox_html' ],

					'featured_people' => [
						'title'       => _x( 'Featured People', 'Field Title', 'geditorial-symposium' ),
						'description' => _x( 'People Who Featured in This Session', 'Field Description', 'geditorial-symposium' ),
						'type'        => 'people',
						'quickedit'   => TRUE,
					],

					'published'    => [ 'type' => 'text', 'quickedit' => TRUE ],
					'source_title' => [ 'type' => 'text' ],
					'source_url'   => [ 'type' => 'link' ],
					'action_title' => [ 'type' => 'text' ],
					'action_url'   => [ 'type' => 'link' ],
					'highlight'    => [ 'type' => 'note' ],
					'dashboard'    => [ 'type' => 'postbox_html' ],
					'abstract'     => [ 'type' => 'postbox_html' ],

					'content_embed_url' => [ 'type' => 'embed' ],
					'text_source_url'   => [ 'type' => 'text_source' ],
					'audio_source_url'  => [ 'type' => 'audio_source' ],
					'video_source_url'  => [ 'type' => 'video_source' ],
					'image_source_url'  => [ 'type' => 'image_source' ],

					'datetime' => [ 'type' => 'datetime' ],
				],
			],
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'main_posttype' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_for( 'meta', 'main_posttype' );
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
		], 'main_posttype', [
			'custom_icon' => 'category',
		] );

		$this->register_taxonomy( 'type_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => '__checklist_terms_callback',
		], 'main_posttype', [
			'custom_icon' => 'screenoptions',
		] );

		$this->register_posttype( 'main_posttype', [], [
			'primary_taxonomy' => $this->constant( 'category_taxonomy' ),
		] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'main_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->comments__handle_default_status( $screen->post_type );
				$this->posttype__media_register_headerbutton( 'main_posttype' );
				$this->_hook_post_updated_messages( 'main_posttype' );

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->_hook_bulk_post_updated_messages( 'main_posttype' );
				$this->corerestrictposts__hook_screen_taxonomies( [
					'type_taxonomy',
					'category_taxonomy',
				] );
			}
		}
	}

	public function template_include( $template )
	{
		return $this->templateposttype__include( $template, $this->constant( 'main_posttype' ), FALSE );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'main_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', 'per_page' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->posttype_overview_render_table( 'main_posttype', $uri, $sub ) )
			return gEditorial\Info::renderNoReportsAvailable();
	}
}
