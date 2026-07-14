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
	use Internals\ObjectsToObjects;
	use Internals\PostMeta;
	use Internals\PostTypeOverview;
	use Internals\TemplatePostType;

	public static function module(): array
	{
		return [
			'name'     => 'symposium',
			'title'    => _x( 'Symposium', 'Modules: Symposium', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Session Management', 'Modules: Symposium', 'geditorial-admin' ),
			'icon'     => 'welcome-learn-more',
			'access'   => 'beta',
			'keywords' => [
				'session',
				'manual-connect',
				'cptmodule',
			],
		];
	}

	protected function get_global_settings(): array
	{
		return [
			'_general' => [
				'comment_status',
			],
			'_connected' => [
				$this->settings_posttypes_for_target( 'o2o', _x( 'Connected Post-types', 'Settings', 'geditorial-symposium' ) ),
				$this->settings_o2o_field_desc(),
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
			'_fields' => [
				$this->settings_posttypes_for_target( 'parent',
					_x( 'Owner Post-types', 'Settings', 'geditorial-symposium' ),
					_x( 'Selected will be available as the post-types of the owner meta-field.', 'Settings', 'geditorial-symposium' )
				),
			],
			'_constants' => [
				'main_posttype_constant'     => [ NULL, 'entry' ],
				'category_taxonomy_constant' => [ NULL, 'entry_section' ],
			],
		];
	}

	protected function get_global_constants(): array
	{
		return [
			'main_posttype'     => 'session',
			'main_posttype_o2o' => 'session_to_posts',
			'category_taxonomy' => 'session_category',
			'type_taxonomy'     => 'session_type',
		];
	}

	protected function get_global_strings(): array
	{
		$strings = [
			'noops' => [
				'main_posttype'     => _n_noop( 'Session', 'Sessions', 'geditorial-symposium' ),
				'category_taxonomy' => _n_noop( 'Session Category', 'Session Categories', 'geditorial-symposium' ),
				'type_taxonomy'     => _n_noop( 'Session Type', 'Session Types', 'geditorial-symposium' ),
			],
			'o2o' => [
				'main_posttype' => [
					'title' => _x( 'Connected Sessions', 'MetaBox Title', 'geditorial-symposium' ),
				],
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

	protected function define_default_terms(): array
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

	public function get_global_fields(): array
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
					'owner_userid' => [
						'title'       => _x( 'Owner', 'Field Title', 'geditorial-symposium' ),
						'description' => _x( 'Determines the user responsible for this session.', 'Field Description', 'geditorial-symposium' ),
						'type'        => 'user',
					],
					'owner_postid' => [
						'title'       => _x( 'Owner', 'Field Title', 'geditorial-symposium' ),
						'description' => _x( 'Determines the individual responsible for this session.', 'Field Description', 'geditorial-symposium' ),
						'type'        => 'parent_post',
						'posttype'    => $this->get_setting_posttypes( 'parent' ),
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

	protected function posttypes_excluded( array $extra = [] ): array
	{
		return $this->filters( 'posttypes_excluded',
			gEditorial\Settings::posttypesExcluded( $extra + [
				$this->constant( 'main_posttype' ),
			], $this->keep_posttypes )
		);
	}

	public function after_setup_theme(): void
	{
		$this->register_posttype_thumbnail( 'main_posttype' );
	}

	public function o2o_init()
	{
		if ( ! $o2o = $this->o2o_register( 'main_posttype' ) )
			return;

		$this->o2o__hook_insert_content( $o2o, 'main_posttype' );
	}

	public function meta_init(): void
	{
		$this->add_posttype_fields_for( 'meta', 'main_posttype' );
	}

	public function init(): void
	{
		parent::init();

		$this->register_taxonomy( 'category_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'main_posttype', [] );

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

	/**
	 * Fires after the current screen has been set.
	 *
	 * @param object $screen
	 * @return void
	 */
	public function current_screen( $screen ): void
	{
		if ( $this->is_screen_posttype( 'main_posttype', $screen ) ) {

			if ( 'post' === $screen->base ) {

				$this->comments__handle_default_status( $screen->post_type );
				$this->posttypes__media_register_headerbutton( 'main_posttype' );
				$this->_hook_post_updated_messages( 'main_posttype' );

			} else if ( 'edit' === $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->_hook_bulk_post_updated_messages( 'main_posttype' );
				$this->corerestrictposts__hook_screen_taxonomies( [
					'type_taxonomy',
					'category_taxonomy',
				] );

				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );
				$this->modulelinks__register_headerbuttons();
			}
		}
	}

	public function template_include( string $template ): string
	{
		return $this->templateposttype__include( $template, $this->constant( 'main_posttype' ), FALSE );
	}

	public function dashboard_glance_items( array $items ): array
	{
		if ( $glance = $this->dashboard_glance_post( 'main_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function reports_settings( string $sub ): void
	{
		$this->check_settings( $sub, 'reports', 'per_page' );
	}

	protected function render_reports_html( string $uri, string $sub, string $action, string $context ): bool
	{
		if ( ! $this->posttype_overview_render_table( 'main_posttype', $uri, $sub ) )
			return gEditorial\Info::renderNoReportsAvailable();

		return TRUE;
	}
}
