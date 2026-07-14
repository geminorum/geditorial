<?php namespace geminorum\gEditorial\Modules\Cine;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Cine extends gEditorial\Module
{
	use Internals\AdminEditForm;
	use Internals\BulkExports;
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;
	use Internals\DashboardSummary;
	use Internals\MetaBoxMain;
	use Internals\ObjectsToObjects;
	use Internals\PostMeta;
	use Internals\PostTypeOverview;

	public static function module(): array
	{
		return [
			'name'     => 'cine',
			'title'    => _x( 'Cine', 'Modules: Cine', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Library of Films', 'Modules: Cine', 'geditorial-admin' ),
			'icon'     => 'tickets-alt',
			'access'   => 'beta',
			'keywords' => [
				'film',
				'movie',
				'cinema',
				'manual-connect',
				'cptmodule',
			],
		];
	}

	protected function get_global_settings(): array
	{
		$roles = $this->get_settings_default_roles();

		return [
			'_connected' => [
				$this->settings_posttypes_for_target( 'o2o', _x( 'Connected Post-types', 'Settings', 'geditorial-cine' ) ),
				$this->settings_o2o_field_desc(),
			],
			'_roles' => [
				'custom_captype',
				'reports_roles' => [ NULL, $roles ],
			],
			'_supports' => [
				'thumbnail_support',
				$this->settings_supports_option( 'main_posttype', [
					'title',
					'excerpt',
					'thumbnail',
					'author',
					'comments',
					'date-picker',
					'editorial-roles'
				] ),
			],
			'_editpost' => [
				'assign_default_term',
				'metabox_advanced',
			],
			'_editlist' => [
				'admin_ordering',
				'show_in_quickedit' => [ $this->get_taxonomy_show_in_quickedit_desc( 'category_taxonomy' ), '1' ],

			],
			'_frontend' => [
				'contents_viewable',
				'show_in_navmenus' => [ $this->get_taxonomy_show_in_navmenus_desc( 'category_taxonomy' ), '1' ],
			],
			'_dashboard' => [
				'dashboard_widgets',
				'summary_parents',
				'summary_excludes' => [
					NULL,
					WordPress\Taxonomy::listTerms( $this->constant( 'status_taxonomy' ) ),
					$this->get_taxonomy_label( 'status_taxonomy', 'no_items_available', NULL, 'no_terms' ),
				],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_reports' => [
				'overview_taxonomies' => [ NULL, $this->get_posttype_taxonomies_list( 'main_posttype' ) ],
				'overview_fields'     => [ NULL, $this->get_posttype_fields_list( 'main_posttype', 'meta' ) ],
				'overview_units'      => [ NULL, $this->get_posttype_fields_list( 'main_posttype', 'units' ) ],
			],
			'_fields' => [
				$this->settings_posttypes_for_target( 'parent',
					_x( 'Owner Post-types', 'Settings', 'geditorial-cine' ),
					_x( 'Selected will be available as the post-types of the owner meta-field.', 'Settings', 'geditorial-cine' )
				),
			],
			'_misc' => [
				[
					'field'       => 'title_append_year',
					'title'       => _x( 'Append Year', 'Setting Title', 'geditorial-cine' ),
					'description' => _x( 'Automatically adds a suffix of assigned year to the title of the film.', 'Settings', 'geditorial-cine' ),
				],
				[
					'field'       => 'title_append_template',
					'type'        => 'text',
					'title'       => _x( 'Append Template', 'Setting Title', 'geditorial-cine' ),
					'description' => sprintf(
						/* translators: `%s`: modifier placeholder */
						_x( 'Defines the template for adding the year to the title of the film. %s will be replaced by the formatted year.', 'Settings', 'geditorial-cine' ),
						Core\HTML::code( '%s' )
					),
					'field_class' => [ 'medium-text', 'code-text' ],
					'placeholder' => '[%s]',
				],
			],
			'_constants' => [
				'main_posttype_constant'     => [ NULL, 'film' ],
				'category_taxonomy_constant' => [ NULL, 'film_category' ],
			],
		];
	}

	protected function get_global_constants(): array
	{
		return [
			'main_posttype'     => 'film',
			'main_posttype_o2o' => 'film_to_posts',
			'category_taxonomy' => 'film_category',
			'rating_taxonomy'   => 'film_rating',
			'year_taxonomy'     => 'film_year',         // NOTE: **avoid** using `Yearly` for Films!
			'status_taxonomy'   => 'film_status',
		];
	}

	protected function get_global_strings(): array
	{
		$strings = [
			'noops' => [
				'main_posttype'     => _n_noop( 'Film', 'Films', 'geditorial-cine' ),
				'category_taxonomy' => _n_noop( 'Film Category', 'Film Categories', 'geditorial-cine' ),
				'rating_taxonomy'   => _n_noop( 'Film Rating', 'Film Ratings', 'geditorial-cine' ),
				'year_taxonomy'     => _n_noop( 'Film Year', 'Film Years', 'geditorial-cine' ),
				'status_taxonomy'   => _n_noop( 'Film Status', 'Film Statuses', 'geditorial-cine' ),
			],
			'labels' => [
				'main_posttype' => [
					'menu_name'      => _x( 'Cine', 'Label: Menu Name', 'geditorial-cine' ),
					'featured_image' => _x( 'Movie Poster', 'Label: Featured Image', 'geditorial-cine' ),
				],
				'rating_taxonomy' => [
					'show_option_all'      => _x( 'Ratings', 'Label: Show Option All', 'geditorial-cine' ),
					'show_option_no_items' => _x( '(Unrated)', 'Label: Show Option No Terms', 'geditorial-cine' ),
				],
				'status_taxonomy' => [
					'menu_name'            => _x( 'Statuses', 'Label: Menu Name', 'geditorial-cine' ),
					'show_option_all'      => _x( 'Statuses', 'Label: Show Option All', 'geditorial-cine' ),
					'show_option_no_items' => _x( '(Unknown)', 'Label: Show Option No Terms', 'geditorial-cine' ),
				],
			],
			'o2o' => [
				'main_posttype' => [
					'title' => _x( 'Connected Films', 'MetaBox Title', 'geditorial-cine' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms(): array
	{
		return [
			'rating_taxonomy' => [
				'g'     => _x( 'G', 'Default Term', 'geditorial-cine' ),
				'pg'    => _x( 'PG', 'Default Term', 'geditorial-cine' ),
				'pg-13' => _x( 'PG-13', 'Default Term', 'geditorial-cine' ),
				'r'     => _x( 'R', 'Default Term', 'geditorial-cine' ),
				'nc-17' => _x( 'NC-17', 'Default Term', 'geditorial-cine' ),
				'x'     => _x( 'X', 'Default Term', 'geditorial-cine' ),
				'gp'    => _x( 'GP', 'Default Term', 'geditorial-cine' ),
				'm'     => _x( 'M', 'Default Term', 'geditorial-cine' ),
				'mpg'   => _x( 'M/PG', 'Default Term', 'geditorial-cine' ),
			],
			'genre_taxonomy' => [
				'drama'         => _x( 'Drama', 'Default Term', 'geditorial-cine' ),
				'melodrama'     => _x( 'Melodrama', 'Default Term', 'geditorial-cine' ),
				'action'        => _x( 'Action', 'Default Term', 'geditorial-cine' ),
				'horror'        => _x( 'Horror', 'Default Term', 'geditorial-cine' ),
				'sci-fi'        => _x( 'Science Fiction', 'Default Term', 'geditorial-cine' ),
				'war'           => _x( 'War', 'Default Term', 'geditorial-cine' ),
				'crime'         => _x( 'Crime', 'Default Term', 'geditorial-cine' ),
				'romance'       => _x( 'Romance', 'Default Term', 'geditorial-cine' ),
				'thriller'      => _x( 'Thriller', 'Default Term', 'geditorial-cine' ),
				'adventure'     => _x( 'Adventure', 'Default Term', 'geditorial-cine' ),
				'superhero'     => _x( 'Superhero', 'Default Term', 'geditorial-cine' ),
				'mystery'       => _x( 'Mystery', 'Default Term', 'geditorial-cine' ),
				'psychological' => _x( 'Psychological', 'Default Term', 'geditorial-cine' ),
				'musical'       => _x( 'Musical', 'Default Term', 'geditorial-cine' ),
				'comedy'        => _x( 'Comedy', 'Default Term', 'geditorial-cine' ),
				'political'     => _x( 'Political', 'Default Term', 'geditorial-cine' ),
				'historical'    => _x( 'Historical', 'Default Term', 'geditorial-cine' ),
				'western'       => _x( 'Western', 'Default Term', 'geditorial-cine' ),
				'biography'     => _x( 'Biography', 'Default Term', 'geditorial-cine' ),
				'family'        => _x( 'Family', 'Default Term', 'geditorial-cine' ),
				'sport'         => _x( 'Sport', 'Default Term', 'geditorial-cine' ),
				'documentary'   => _x( 'Documentary', 'Default Term', 'geditorial-cine' ),
				'animation'     => _x( 'Animation', 'Default Term', 'geditorial-cine' ),
				'disaster'      => _x( 'Disaster', 'Default Term', 'geditorial-cine' ),
			],
		];
	}

	public function get_global_fields(): array
	{
		$posttype = $this->constant( 'main_posttype' );

		return [
			'meta' => [
				$posttype => [
					'sub_title'  => [
						'title' => _x( 'Tagline', 'Field Title', 'geditorial-cine' ),
						'type'  => 'title_after',
					],
					'lead'       => [
						'title' => _x( 'Synopsis', 'Field Title', 'geditorial-cine' ),
						'type'  => 'postbox_html',
					],

					'alt_title' => [ 'type' => 'text' ],
					'released'  => [
						'title'       => _x( 'Release Date', 'Field Title', 'geditorial-cine' ),
						'description' => _x( 'The Date on Which the Movie was Released', 'Field Description', 'geditorial-cine' ),
						'type'        => 'datestring',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
					],
					'owner_userid' => [
						'title'       => _x( 'Owner', 'Field Title', 'geditorial-cine' ),
						'description' => _x( 'Determines the user responsible for this film.', 'Field Description', 'geditorial-cine' ),
						'type'        => 'user',
					],
					'owner_postid' => [
						'title'       => _x( 'Owner', 'Field Title', 'geditorial-cine' ),
						'description' => _x( 'Determines the individual responsible for this film.', 'Field Description', 'geditorial-cine' ),
						'type'        => 'parent_post',
						'posttype'    => $this->get_setting_posttypes( 'parent' ),
					],
					'imdb_link' => [
						'title'       => _x( 'IMDb Link', 'Field Title', 'geditorial-cine' ),
						'description' => _x( 'Internet Movie Database URL.', 'Field Description', 'geditorial-cine' ),
						'type'        => 'link',
					],

					'venue_string'   => [ 'type' => 'venue' ],
					'contact_string' => [ 'type' => 'contact' ],   // url/email/phone
					'website_url'    => [ 'type' => 'link' ],
					'wiki_url'       => [ 'type' => 'link' ],
					'email_address'  => [ 'type' => 'email' ],

					'content_embed_url' => [ 'type' => 'embed' ],
					'text_source_url'   => [ 'type' => 'text_source' ],
					'audio_source_url'  => [ 'type' => 'audio_source' ],
					'video_source_url'  => [ 'type' => 'video_source' ],
					'image_source_url'  => [ 'type' => 'image_source' ],
				],
			],
			'units' => [
				$posttype => [
					'duration' => [
						'title'       => _x( 'Film Duration', 'Field Title', 'geditorial-cine' ),
						'description' => _x( 'How long is the film.', 'Field Description', 'geditorial-cine' ),
						'type'        => 'duration',
					],
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
		$this->filter_module( 'genres', 'get_default_terms', 2 );
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

	public function units_init()
	{
		$this->add_posttype_fields_for( 'units', 'main_posttype' );
	}

	public function init(): void
	{
		parent::init();

		$viewable = $this->get_setting( 'contents_viewable', TRUE );
		$captype  = $this->get_setting( 'custom_captype', FALSE )
			? $this->constant_plural( 'main_posttype' )
			: FALSE;

		$this->register_taxonomy( 'category_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit', TRUE ),
			'show_in_nav_menus'  => (bool) $this->get_setting( 'show_in_navmenus', TRUE ),
			'meta_box_cb'        => $this->get_setting( 'metabox_advanced' ) ? NULL : '__checklist_terms_callback',
		], 'main_posttype', [
			'is_viewable'    => $viewable,
			'custom_captype' => $captype,
		] );

		$this->register_taxonomy( 'rating_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => '__singleselect_terms_callback',
		], 'main_posttype', [
			'is_viewable'     => $viewable,
			'custom_icon'     => 'superhero-alt',
			'custom_captype'  => $captype,
			'admin_managed'   => TRUE,
			'single_selected' => TRUE,
		] );

		$this->register_taxonomy( 'year_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => '__singleselect_terms_callback',
		], 'main_posttype', [
			'is_viewable'     => $viewable,
			'custom_captype'  => $captype,
			'admin_managed'   => TRUE,
			'single_selected' => TRUE,
		] );

		$this->register_taxonomy( 'status_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'main_posttype', [
			'is_viewable'     => $viewable,
			'custom_captype'  => $captype,
			'admin_managed'   => TRUE,
			'single_selected' => TRUE,
		] );

		$this->register_posttype( 'main_posttype', [
			'hierarchical' => FALSE,

			gEditorial\MetaBox::POSTTYPE_MAINBOX_PROP => TRUE,
		], [
			'is_viewable'      => $viewable,
			'custom_captype'   => $captype,
			'primary_taxonomy' => $this->constant( 'category_taxonomy' ),
			'status_taxonomy'  => TRUE,
		] );

		if ( $this->get_setting( 'title_append_year' ) )
			$this->filter( 'the_title', 2, 8 );
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

				$this->_hook_editform_meta_summary( [
					'released' => NULL,
				] );

				$this->comments__handle_default_status( $screen->post_type );
				$this->posttypes__media_register_headerbutton( 'main_posttype' );
				$this->_hook_post_updated_messages( 'main_posttype' );
				$this->_hook_general_mainbox( $screen, 'main_posttype' );

			} else if ( 'edit' === $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->_hook_bulk_post_updated_messages( 'main_posttype' );
				$this->corerestrictposts__hook_screen_taxonomies( [
					'status_taxonomy',
					'category_taxonomy',
					'rating_taxonomy',
					'year_taxonomy',
				] );

				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );
				$this->modulelinks__register_headerbuttons();
			}

		} else if ( $this->in_setting_posttypes( $screen->post_type, 'o2o' ) ) {

			if ( 'post' === $screen->base ) {

			} else if ( 'edit' === $screen->base ) {

			}
		}
	}

	public function dashboard_widgets(): void
	{
		if ( $this->role_can( [ 'reports' ] ) )
			$this->add_dashboard_term_summary( 'status_taxonomy', [ $this->constant( 'main_posttype' ) ], FALSE );
	}

	public function dashboard_glance_items( array $items ): array
	{
		if ( $glance = $this->dashboard_glance_post( 'main_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	protected function _render_mainbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		gEditorial\MetaBox::singleselectTerms( $object->ID, [
			'taxonomy'   => $this->constant( 'rating_taxonomy' ),
			'posttype'   => $object->post_type,
			'empty_link' => FALSE,
		] );

		gEditorial\MetaBox::singleselectTerms( $object->ID, [
			'taxonomy'   => $this->constant( 'year_taxonomy' ),
			'posttype'   => $object->post_type,
			'empty_link' => FALSE,
		] );

		gEditorial\MetaBox::singleselectTerms( $object->ID, [
			'taxonomy'   => $this->constant( 'status_taxonomy' ),
			'posttype'   => $object->post_type,
			'empty_link' => FALSE,
		] );
	}

	public function genres_get_default_terms( $terms, $taxonomy )
	{
		return Services\Modulation::isTaxonomyGenre( $taxonomy ) ? array_merge( $terms,
			$this->get_default_terms( 'genre_taxonomy' ),
		) : $terms;
	}

	public function the_title( $post_title, $post_id = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post_id ) )
			return $post_title;

		if ( $this->constant( 'main_posttype' ) !== $post->post_type )
			return $post_title;

		if ( ! $terms = WordPress\Taxonomy::getPostTerms( $this->constant( 'year_taxonomy' ), $post ) )
			return $post_title;

		$template = $this->get_setting_fallback( 'title_append_template', '[%s]' );

		foreach ( $terms as $term ) {

			if ( ! $name = WordPress\Term::title( $term, FALSE ) )
				continue;

			$post_title.= ' '.sprintf( $template, apply_filters( 'string_format_i18n', $name ) );
		}

		return $post_title;
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
