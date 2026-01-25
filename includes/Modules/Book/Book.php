<?php namespace geminorum\gEditorial\Modules\Book;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Book extends gEditorial\Module
{
	use Internals\AdminEditForm;
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;
	use Internals\CoreThumbnails;
	use Internals\DashboardSummary;
	use Internals\MetaBoxCustom;
	use Internals\MetaBoxMain;
	use Internals\PairedAdmin;
	use Internals\PairedCore;
	use Internals\PairedFront;
	use Internals\PairedMetaBox;
	use Internals\PairedTools;
	use Internals\PostMeta;
	use Internals\PostsToPosts;
	use Internals\PostTypeOverview;
	use Internals\QuickPosts;
	use Internals\TemplatePostType;

	protected $deafults = [ 'multiple_instances' => TRUE ];

	public static function module()
	{
		return [
			'name'     => 'book',
			'title'    => _x( 'Book', 'Modules: Book', 'geditorial-admin' ),
			'desc'     => _x( 'Online House of Publications', 'Modules: Book', 'geditorial-admin' ),
			'icon'     => 'book-alt',
			'access'   => 'stable',
			'keywords' => [
				'publication',
				'literature',
				'has-widgets',
				'pairedmodule',
				'cptmodule',
			],
		];
	}

	public function settings_intro()
	{
		gEditorial\Info::renderNoticeP2P();
	}

	protected function get_global_settings()
	{
		$settings = [
			'posttypes_option' => 'posttypes_option',
			'_general'         => [
				'paired_force_parents',
				'paired_manage_restricted',
				'comment_status',
				'quick_newpost',
				'paired_exclude_terms' => [
					NULL,
					$this->constant( 'category_taxonomy' ),
					$this->get_taxonomy_label( 'category_taxonomy', 'no_terms' ),
				],
			],
			'_dashboard' => [
				'dashboard_widgets',
				'summary_parents',
				'summary_excludes' => [
					NULL,
					WordPress\Taxonomy::listTerms( $this->constant( 'status_taxonomy' ) ),
					$this->get_taxonomy_label( 'status_taxonomy', 'no_terms' ),
				],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_frontend' => [
				'tabs_support',
				'insert_content',
				'insert_cover',
				'insert_priority',
			],
			'_content' => [
				'archive_override',
				'display_searchform',
				'empty_content',
				'newpost_title',
				'newpost_template',
				'post_status',
				'archive_title' => [ NULL, $this->get_posttype_label( 'main_posttype', 'all_items' ) ],
				'archive_content',
				'archive_template',
			],
			'_supports' => [
				'assign_default_term',
				'widget_support',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'main_posttype', TRUE ),
			],
			'_reports' => [
				'overview_taxonomies' => [ NULL, $this->get_posttype_taxonomies_list( 'main_posttype' ) ],
				'overview_fields'     => [ NULL, $this->get_posttype_fields_list( 'main_posttype', 'meta' ) ],
				'overview_units'      => [ NULL, $this->get_posttype_fields_list( 'main_posttype', 'units' ) ],
			],
			'_constants' => [
				'main_posttype_constant'     => [ NULL, 'publication' ],
				'category_taxonomy_constant' => [ NULL, 'publication_category' ],
				'main_shortcode_constant'    => [ NULL, 'publication' ],
			],
		];

		if ( defined( 'P2P_PLUGIN_VERSION' ) )
			$settings['_p2p'] = [
				[
					'field'  => 'p2p_posttypes',
					'type'   => 'posttypes',
					'title'  => _x( 'Connected Post-types', 'Setting Title', 'geditorial-book' ),
					'values' => $this->all_posttypes(),
				],
				[
					'field' => 'p2p_insert_content',
					'title' => _x( 'Insert in Content', 'Setting Title', 'geditorial-book' ),
				],
				[
					'field' => 'p2p_title_from',
					'type'  => 'text',
					'title' => _x( 'Connected From Title', 'Setting Title', 'geditorial-book' ),
				],
				[
					'field' => 'p2p_title_to',
					'type'  => 'text',
					'title' => _x( 'Connected To Title', 'Setting Title', 'geditorial-book' ),
				],
			];

		return $settings;
	}

	protected function get_global_constants()
	{
		return [
			'main_posttype'     => 'publication',
			'main_posttype_p2p' => 'related_publications',
			'main_paired'       => 'related_publication',
			'category_taxonomy' => 'publication_category',
			'serie_taxonomy'    => 'publication_serie',      // TODO: move to new Tax-Module: فروست
			'location_taxonomy' => 'publication_library',
			'type_taxonomy'     => 'publication_type',
			'status_taxonomy'   => 'publication_status',
			'size_taxonomy'     => 'publication_size',       // TODO: move to `Units` Module: `book_cover`

			'main_shortcode'  => 'publication',
			'p2p_shortcode'   => 'publication-p2p',
			'serie_shortcode' => 'publication-serie',   // TODO: move to new Tax-Module: فروست
			'cover_shortcode' => 'publication-cover',

			'metakey_import_id'    => 'book_publication_id',
			'metakey_import_title' => 'book_publication_title',
			'metakey_import_ref'   => 'book_publication_ref',
			'metakey_import_desc'  => 'book_publication_desc',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_posttype'     => _n_noop( 'Publication', 'Publications', 'geditorial-book' ),
				'category_taxonomy' => _n_noop( 'Publication Category', 'Publication Categories', 'geditorial-book' ),
				'serie_taxonomy'    => _n_noop( 'Serie', 'Series', 'geditorial-book' ),
				'location_taxonomy' => _n_noop( 'Library', 'Libraries', 'geditorial-book' ),
				'type_taxonomy'     => _n_noop( 'Publication Type', 'Publication Types', 'geditorial-book' ),
				'status_taxonomy'   => _n_noop( 'Publication Status', 'Publication Statuses', 'geditorial-book' ),
				'size_taxonomy'     => _n_noop( 'Publication Size', 'Publication Sizes', 'geditorial-book' ),
			],
			'labels' => [
				'main_posttype' => [
					'featured_image' => _x( 'Cover Image', 'Label: Featured Image', 'geditorial-book' ),
					'author_label'   => _x( 'Curator', 'Label: Author Label', 'geditorial-book' ),
					'excerpt_label'  => _x( 'Summary', 'Label: Excerpt Label', 'geditorial-book' ),
				],
				'type_taxonomy' => [
					'show_option_all'      => _x( 'Type', 'Label: Show Option All', 'geditorial-book' ),
					'show_option_no_items' => _x( '(Untyped)', 'Label: Show Option No Items', 'geditorial-book' ),
				],
				'serie_taxonomy' => [
					'show_option_all'      => _x( 'Serie', 'Label: Show Option All', 'geditorial-book' ),
					'show_option_no_items' => _x( '(Non-Series)', 'Label: Show Option No Items', 'geditorial-book' ),
				],
			],
			'p2p' => [
				'main_posttype' => [
					'fields' => [
						'page' => [
							'title'    => _x( 'Pages', 'P2P', 'geditorial-book' ),
							'type'     => 'text',
							/* translators: `%s`: pages placeholder */
							'template' => _x( 'P. %s', 'P2P', 'geditorial-book' ),
						],
						'vol' => [
							'title'    => _x( 'Volume', 'P2P', 'geditorial-book' ),
							'type'     => 'text',
							/* translators: `%s`: volumes placeholder */
							'template' => _x( 'Vol. %s', 'P2P', 'geditorial-book' ),
						],
						'ref' => [
							'title'    => _x( 'Reference', 'P2P', 'geditorial-book' ),
							'type'     => 'text',
							'template' => '%s',
						],
						'desc' => [
							'title'    => _x( 'Description', 'P2P', 'geditorial-book' ),
							'type'     => 'text',
							'template' => '%s',
						],
					],
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['p2p']['main_posttype']['title'] = [
			'from' => _x( 'Connected Publications', 'P2P', 'geditorial-book' ),
			'to'   => _x( 'Connected Posts', 'P2P', 'geditorial-book' ),
		];

		$strings['p2p']['main_posttype']['from_labels'] = [
			'singular_name' => _x( 'Post', 'P2P', 'geditorial-book' ),
			'search_items'  => _x( 'Search Posts', 'P2P', 'geditorial-book' ),
			'not_found'     => _x( 'No posts found.', 'P2P', 'geditorial-book' ),
			'create'        => _x( 'Connect to a post', 'P2P', 'geditorial-book' ),
		];

		$strings['p2p']['main_posttype']['to_labels'] = [
			'singular_name' => _x( 'Publications', 'P2P', 'geditorial-book' ),
			'search_items'  => _x( 'Search Publications', 'P2P', 'geditorial-book' ),
			'not_found'     => _x( 'No publications found.', 'P2P', 'geditorial-book' ),
			'create'        => _x( 'Connect to a publication', 'P2P', 'geditorial-book' ),
		];

		$strings['p2p']['main_posttype']['admin_column'] = FALSE; // adding through tweaks module

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'type_taxonomy' => [
				'paperback' => _x( 'Paperback', 'Publication Type: Default Term', 'geditorial-book' ),         // `shomiz`
				'hardcover' => _x( 'Hardcover', 'Publication Type: Default Term', 'geditorial-book' ),         // `gallingor`
				'ebook'     => _x( 'E-Book', 'Publication Type: Default Term', 'geditorial-book' ),
				'pod'       => _x( 'Print on Demand', 'Publication Type: Default Term', 'geditorial-book' ),
				'disc'      => _x( 'Disc', 'Publication Type: Default Term', 'geditorial-book' ),
			],
			'size_taxonomy' => [
				'octavo'      => _x( 'Octavo', 'Publication Size: Default Term', 'geditorial-book' ),          // `vaziri`
				'folio'       => _x( 'Folio', 'Publication Size: Default Term', 'geditorial-book' ),           // `soltani`
				'medium'      => _x( 'Medium Octavo', 'Publication Size: Default Term', 'geditorial-book' ),   // `roghee`
				'quatro'      => _x( 'Quatro', 'Publication Size: Default Term', 'geditorial-book' ),          // `rahli`
				'duodecimo'   => _x( 'Duodecimo', 'Publication Size: Default Term', 'geditorial-book' ),       // `paltoyee`
				'sextodecimo' => _x( 'Sextodecimo', 'Publication Size: Default Term', 'geditorial-book' ),     // `jibi`
			],
			'status_taxonomy' => [
				'not-available-in-print' => _x( 'Not Available in Print', 'Publication Status: Default Term', 'geditorial-book' ),
				'soon-to-be-published'   => _x( 'Soon to be Published', 'Publication Status: Default Term', 'geditorial-book' ),
				'secondary-print'        => _x( 'Secondary Print', 'Publication Status: Default Term', 'geditorial-book' ),
				'repeat-print'           => _x( 'Repeat Print', 'Publication Status: Default Term', 'geditorial-book' ),
				'first-print'            => _x( 'First Print', 'Publication Status: Default Term', 'geditorial-book' ),
			],
			// @source https://www.biblio.com/booksearch
			// TODO: add the taxonomy
			'attribute_taxonomy' => [
				'first-editions'  => _x( 'First Editions', 'Publication Attribute: Default Term', 'geditorial-book' ),
				'signed-books'    => _x( 'Signed Books', 'Publication Attribute: Default Term', 'geditorial-book' ),
				'dust-jacket'     => _x( 'Dust Jacket', 'Publication Attribute: Default Term', 'geditorial-book' ),
				'supplied-photos' => _x( 'with Bookseller-supplied Photos', 'Publication Attribute: Default Term', 'geditorial-book' ),
				'large-print'     => _x( 'Large Print', 'Publication Attribute: Default Term', 'geditorial-book' ),
			],
		];
	}

	public function get_global_fields()
	{
		return [
			'meta' => [
				$this->constant( 'main_posttype' ) => [
					'publication_tagline' => [
						'title'       => _x( 'Cover Tagline', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'Promotional Text on the Cover of this Publication', 'Field Description', 'geditorial-book' ),
						'type'        => 'title_before',
					],
					'sub_title' => [
						'title'       => _x( 'Subtitle', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'Subtitle of the Publication', 'Field Description', 'geditorial-book' ),
						'type'        => 'title_after',
					],
					'alt_title' => [
						'title'       => _x( 'Alternative Title', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'The Original Title or Title in Another Language', 'Field Description', 'geditorial-book' ),
						'icon'        => 'admin-site-alt',
					],
					'collection' => [
						'title'       => _x( 'Collection Title', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'This Publication Is Part of a Collection', 'Field Description', 'geditorial-book' ),
						'icon'        => 'screenoptions',
					],
					'publication_byline' => [
						'title'       => _x( 'Publication By-Line', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'Text to override the publication author', 'Field Description', 'geditorial-book' ),
						'type'        => 'note',
						'icon'        => 'businessperson',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
					],
					'publication_edition' => [
						'title'       => _x( 'Edition', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'Edition of the Publication', 'Field Description', 'geditorial-book' ),
					],
					'publication_print' => [
						'title'       => _x( 'Print', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'Specefic Print of the Publication', 'Field Description', 'geditorial-book' ),
						'icon'        => 'book',
					],
					// @SEE: `Metropolis` Module
					'publish_location' => [
						'title'       => _x( 'Publish Location', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'Location Published', 'Field Description', 'geditorial-book' ),
						'type'        => 'venue',
					],
					'publication_date' => [
						'title'       => _x( 'Publication Date', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'Date Published', 'Field Description', 'geditorial-book' ),
						'type'        => 'datestring',
					],
					'publication_ddc' => [
						// FIXME: move to `DeweyDecimal` Module
						// @REF: https://en.wikipedia.org/wiki/Dewey_Decimal_Classification
						'title'       => _x( 'DDC', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'Dewey Decimal Classification', 'Field Description', 'geditorial-book' ),
						'type'        => 'code',
						'icon'        => 'shortcode',
					],
					'publication_lcc' => [
						// @REF: https://en.wikipedia.org/wiki/Library_of_Congress_Classification
						'title'       => _x( 'LCC', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'Library of Congress Classification', 'Field Description', 'geditorial-book' ),
						'type'        => 'code',
						'icon'        => 'shortcode',
					],
					// TODO: convert to unit via `Units` Module
					'total_pages' => [
						'title'       => _x( 'Pages', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'Total Pages of the Publication', 'Field Description', 'geditorial-book' ),
						'type'        => 'number',
						'icon'        => 'admin-page',
					],
					// TODO: convert to unit via `Units` Module
					'total_volumes' => [
						'title'       => _x( 'Volumes', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'Total Volumes of the Publication', 'Field Description', 'geditorial-book' ),
						'type'        => 'number',
						'icon'        => 'book-alt',
					],
					// TODO: convert to unit via `Units` Module
					'total_discs' => [
						'title'       => _x( 'Discs', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'Total Discs of the Publication', 'Field Description', 'geditorial-book' ),
						'type'        => 'number',
						'icon'        => 'album',
					],
					// TODO: convert to unit via `Units` Module
					'publication_size' => [
						'title'       => _x( 'Size', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'The Size of the Publication, Mainly Books', 'Field Description', 'geditorial-book' ),
						'type'        => 'term',
						'taxonomy'    => $this->constant( 'size_taxonomy' ),
					],
					'publication_reference' => [
						'title'       => _x( 'Reference', 'Field Title', 'geditorial-book' ),
						'description' => _x( 'Full reference to this publication', 'Field Description', 'geditorial-book' ),
						'type'        => 'note',
						'icon'        => 'editor-break',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
					],

					'venue_string'   => [ 'type' => 'venue', 'quickedit' => TRUE ],
					'contact_string' => [ 'type' => 'contact' ],                      // url/email/phone
					'website_url'    => [ 'type' => 'link' ],
					'wiki_url'       => [ 'type' => 'link' ],
					'email_address'  => [ 'type' => 'email', 'quickedit' => TRUE ],
					'sku'            => [ 'type' => 'code', 'quickedit' => TRUE ],

					'highlight'    => [ 'type' => 'note' ],
					'source_title' => [ 'type' => 'text' ],
					'source_url'   => [ 'type' => 'link' ],
					'action_title' => [ 'type' => 'text' ],
					'action_url'   => [ 'type' => 'link' ],
					'cover_blurb'  => [ 'type' => 'note' ],
					'cover_price'  => [ 'type' => 'price' ],
					'content_fee'  => [ 'type' => 'price' ],
				],
			],
		];
	}

	protected function paired_get_paired_constants()
	{
		return [
			'main_posttype',
			'main_paired',
			FALSE,
			'category_taxonomy',
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'main_posttype' );
	}

	public function p2p_init()
	{
		$posttypes = $this->get_setting( 'p2p_posttypes', [] );

		if ( empty( $posttypes ) )
			return FALSE;

		$this->p2p_register( 'main_posttype', $posttypes );

		if ( is_admin() )
			return;

		if ( $this->get_setting( 'p2p_insert_content' ) )
			add_action( $this->hook_base( 'content', 'after' ),
				[ $this, 'insert_content_p2p' ],
				$this->get_setting( 'insert_priority', 100 )
			);
	}

	public function widgets_init()
	{
		register_widget( __NAMESPACE__.'\\Widgets\\PublicationCover' );
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

		$this->register_taxonomy( 'serie_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL, // default meta box
		], 'main_posttype', [
			'custom_icon' => 'tag',
		] );

		$this->register_taxonomy( 'location_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL, // default meta box
		], 'main_posttype', [
			'custom_icon' => 'book-alt',
		] );

		$this->register_taxonomy( 'type_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => '__checklist_terms_callback',
		], 'main_posttype', [
			'custom_icon' => 'screenoptions',
		] );

		$this->register_taxonomy( 'status_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'main_posttype', [
			'admin_managed'   => TRUE,
			'single_selected' => TRUE,
		]  );

		if ( count( $this->posttypes() ) ) {

			$this->register_taxonomy( 'main_paired', [
				'show_ui'      => FALSE,
				'show_in_rest' => FALSE,
			], NULL, [

			] );

			$this->_paired = $this->constant( 'main_paired' );

			$this->pairedfront_hook__post_tabs();
		}

		// TODO: `$this->paired_register()`
		$this->register_posttype( 'main_posttype', [
			gEditorial\MetaBox::POSTTYPE_MAINBOX_PROP => TRUE,
		], [
			'primary_taxonomy' => $this->constant( 'category_taxonomy' ),
			'status_taxonomy'  => TRUE,
		] );

		$this->corethumbnails__hook_tabloid_side_image( 'main_posttype' );

		$this->register_shortcode( 'main_shortcode' );
		$this->register_shortcode( 'p2p_shortcode' );
		$this->register_shortcode( 'serie_shortcode' );
		$this->register_shortcode( 'cover_shortcode' );
	}

	public function importer_init()
	{
		$this->filter_module( 'importer', 'fields', 2 );
		$this->filter_module( 'importer', 'prepare', 7 );
		$this->action_module( 'importer', 'saved', 2 );
	}

	public function template_redirect()
	{
		if ( $this->_paired && is_tax( $this->constant( 'main_paired' ) ) ) {

			if ( $post_id = $this->paired_get_to_post_id( get_queried_object(), 'main_posttype', 'main_paired' ) )
				WordPress\Redirect::doWP( get_permalink( $post_id ), 301 );

		} else if ( is_singular( $this->constant( 'main_posttype' ) ) ) {

			if ( $this->get_setting( 'insert_cover' ) )
				add_action( $this->hook_base( 'content', 'before' ),
					[ $this, 'insert_cover' ],
					$this->get_setting( 'insert_priority', -50 )
				);

		} else if ( $this->_paired && is_singular( $this->posttypes() ) ) {

			$this->hook_insert_content();
		}
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'main_posttype' ) ) {
			$this->pairedadmin__hook_tweaks_column_connected( $posttype );
		}
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'main_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->_hook_editform_meta_summary( [
					'publication_byline'  => NULL,
					'publication_edition' => NULL,
					'publication_print'   => NULL,
					'publish_location'    => NULL,
					'publication_date'    => NULL,
					'publication_size'    => NULL,
				] );

				$this->_hook_general_mainbox( $screen, 'main_posttype' );

				if ( post_type_supports( $screen->post_type, 'author' ) )
					$this->metaboxcustom_add_metabox_author( 'main_posttype' );

				if ( post_type_supports( $screen->post_type, 'excerpt' ) )
					$this->metaboxcustom_add_metabox_excerpt( 'main_posttype' );

				$this->comments__handle_default_status( $screen->post_type );
				$this->posttypes__media_register_headerbutton( 'main_posttype' );
				$this->_hook_post_updated_messages( 'main_posttype' );
				$this->_hook_paired_listbox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				if ( $this->_p2p )
					$this->coreadmin__hook_tweaks_column_row( $screen->post_type, -25, 'p2p_to' );

				$this->modulelinks__register_headerbuttons();
				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );

				$this->_hook_bulk_post_updated_messages( 'main_posttype' );
				$this->pairedadmin__hook_tweaks_column_connected( $screen->post_type );
				$this->pairedcore__hook_sync_paired();
				$this->corerestrictposts__hook_screen_taxonomies( [
					'type_taxonomy',
					'category_taxonomy',
					'serie_taxonomy',
					'location_taxonomy',
					'status_taxonomy',
				] );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				$this->_hook_paired_pairedbox( $screen );
				$this->_hook_paired_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_paired_store_metabox( $screen->post_type );
			}

		} else if ( $this->_p2p && 'edit' == $screen->base
			&& $this->in_setting( $screen->post_type, 'p2p_posttypes' ) ) {

			$this->coreadmin__hook_tweaks_column_row( $screen->post_type, -25, 'p2p_from' );
		}
	}

	public function admin_menu()
	{
		if ( $this->get_setting( 'quick_newpost' ) ) {
			$this->_hook_submenu_adminpage( 'newpost' );
			$this->action_self( 'newpost_aftercontent', 4, 10, 'menu_order' );
		}
	}

	public function dashboard_widgets()
	{
		$this->add_dashboard_term_summary( 'status_taxonomy', [ $this->constant( 'main_posttype' ) ], FALSE );
	}

	public function tweaks_column_row_p2p_to( $post, $before, $after, $module )
	{
		$this->column_row_p2p_to_posttype( 'main_posttype', $post, $before, $after );
	}

	public function tweaks_column_row_p2p_from( $post, $before, $after, $module )
	{
		$this->column_row_p2p_from_posttype( 'main_posttype', $post, $before, $after );
	}

	public function prep_meta_row_module( $value, $field_key = NULL, $field = [], $raw = NULL )
	{
		switch ( $field_key ) {

			case 'publication_date': return Core\Number::localize( $raw ?: $value );

			case 'publication_edition':

				return sprintf(
					/* translators: `%s`: edition placeholder */
					_x( '%s Edition', 'Display', 'geditorial-book' ),
					Core\Number::localize( Core\Number::toOrdinal( $raw ?: $value ) )
				);

			case 'publication_print':

				return sprintf(
					/* translators: `%s`: print placeholder */
					_x( '%s Print', 'Display', 'geditorial-book' ),
					Core\Number::localize( Core\Number::toOrdinal( $raw ?: $value ) )
				);
		}

		return $value;
	}

	public function meta_init()
	{
		// NOTE: DEPRECATED: use `Units` Module
		$this->register_taxonomy( 'size_taxonomy', [
			'meta_box_cb' => FALSE,
		], 'main_posttype', [
			'custom_icon' => 'image-crop',
		] );

		$this->add_posttype_fields_for( 'meta', 'main_posttype' );
		$this->filter( 'prep_meta_row', 2, 12, 'module', $this->base );
		$this->filter( 'meta_field', 7, 9, FALSE, $this->base );

		$this->filter( 'pairedimports_define_import_types', 4, 5, FALSE, $this->base );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'main_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function template_include( $template )
	{
		return $this->templateposttype__include( $template, $this->constant( 'main_posttype' ) );
	}

	public function templateposttype_get_archive_content_default( $posttype )
	{
		$html = $this->get_search_form( 'main_posttype' );

		// checks for taxonomy from `Yearly` Module
		if ( in_array( 'year_span', get_object_taxonomies( $posttype ) ) )
			$html.= ModuleTemplate::getSpanTiles( [
				'taxonomy' => 'year_span',
				'posttype' => $posttype,
			] );

		else if ( gEditorial()->enabled( 'alphabet' ) )
			$html.= gEditorial()->module( 'alphabet' )->shortcode_posts( [
				'posttype'  => $posttype,
				'list_mode' => 'ul',
			] );

		else
			$html.= gEditorial\ShortCode::listPosts( 'assigned',
				$this->constant( 'main_posttype' ),
				$this->constant( 'category_taxonomy' ),
				[
					'id'     => 'all',
					// 'future' => WordPress\PostType::can( $posttype, 'publish_posts' ) ? 'on' : 'off',
					'title'  => FALSE,
					'wrap'   => FALSE,
				]
			);

		return $html;
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'paired',
			$this->constant( 'main_posttype' ),
			$this->constant( 'main_paired' ),
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

	public function serie_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'assigned',
			$this->constant( 'main_posttype' ),
			$this->constant( 'serie_taxonomy' ),
			array_merge( [
				'post_id' => NULL,
			], (array) $atts ),
			$content,
			$this->constant( 'serie_shortcode', $tag ),
			$this->key
		);
	}

	// TODO: use `connected_shortcode` constant
	public function p2p_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		if ( ! $this->_p2p )
			return $content;

		return gEditorial\ShortCode::listPosts( 'objects2objects',
			$this->constant( 'main_posttype' ),
			'',
			array_merge( [
				'post_id'       => NULL,
				'connection'    => $this->_p2p,
				'posttypes'     => $this->get_setting( 'p2p_posttypes', [] ),
				'title_cb'      => [ $this, 'shortcode_title_cb' ],
				'item_after_cb' => [ $this, 'shortcode_item_after_cb' ],
				'title_anchor'  => $this->posttype_anchor( 'main_posttype' ),
				'title_link'    => FALSE,
			], (array) $atts ),
			$content,
			$this->constant( 'p2p_shortcode', $tag ),
			$this->key
		);
	}

	public function shortcode_title_cb( $post, $args, $text, $link )
	{
		if ( FALSE === $args['title'] )
			return FALSE;

		if ( $this->is_posttype( 'main_posttype', $post ) ) {

			if ( $title = $this->get_setting( 'p2p_title_from' ) )
				return $title;

		} else if ( $title = $this->get_setting( 'p2p_title_to' ) ) {

			return $title;
		}

		return FALSE;
	}

	public function shortcode_item_after_cb( $post, $args, $item )
	{
		return $this->_p2p ? $this->p2p_get_meta_row( 'main_posttype', $post->p2p_id, ' &ndash; ', '' ) : '';
	}

	public function cover_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$type = $this->constant( 'main_posttype' );
		$args = [
			'size' => WordPress\Media::getAttachmentImageDefaultSize( $type, NULL, 'medium' ),
			'type' => $type,
			'echo' => FALSE,
		];

		if ( is_singular( $args['type'] ) )
			$args['id'] = NULL;

		else if ( is_singular( $this->posttypes() ) )
			$args['id'] = 'paired';

		else // no publication/no p2p
			return $content;

		if ( ! $html = ModuleTemplate::postImage( array_merge( $args, (array) $atts ) ) )
			return $content;

		return gEditorial\ShortCode::wrap( $html,
			$this->constant( 'cover_shortcode' ),
			array_merge( [ 'wrap' => TRUE ], (array) $atts )
		);
	}

	public function insert_content( $content )
	{
		if ( ! $this->is_content_insert() )
			return;

		echo $this->wrap( ModuleTemplate::cover( [ 'id' => 'paired' ] ) );
	}

	public function insert_content_p2p( $content )
	{
		if ( ! $this->_p2p )
			return;

		if ( ! $this->is_content_insert( $this->get_setting( 'p2p_posttypes', [] ) ) )
			return;

		$this->list_p2p( NULL, '-after' );
	}

	public function get_linked_to_posts_p2p( $post = NULL, $single = FALSE, $published = TRUE )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return FALSE;

		$posts = [];
		$extra = [ 'p2p:per_page' => -1, 'p2p:context' => 'admin_column' ];

		if ( ! $p2p_type = p2p_type( $this->constant( 'main_posttype_p2p' ) ) )
			return FALSE;

		$p2p = $p2p_type->get_connected( $post, $extra, 'abstract' );

		foreach ( $p2p->items as $item ) {

			if ( $single )
				return $item->ID;

			if ( $published && 'publish' != get_post_status( $item ) )
				continue;

			$posts[$item->p2p_id] = $item->ID;
		}

		return count( $posts ) ? $posts : FALSE;
	}

	// TODO: https://github.com/scribu/wp-posts-to-posts/wiki/Related-posts
	// NOTE: DEPRECATED: use `main_shortcode()`
	public function list_p2p( $post = NULL, $class = '' )
	{
		if ( ! $this->_p2p )
			return;

		if ( ! $post = WordPress\Post::get( $post ) )
			return;

		$connected = new \WP_Query( [
			'connected_type'  => $this->constant( 'main_posttype_p2p' ),
			'connected_items' => $post,
			'posts_per_page'  => -1,
		] );

		if ( $connected->have_posts() ) {

			echo $this->wrap_open( '-p2p '.$class );

			if ( $this->is_posttype( 'main_posttype', $post ) )
				Core\HTML::h3( $this->get_setting( 'p2p_title_from' ), '-title -p2p-from' );

			else
				Core\HTML::h3( $this->get_setting( 'p2p_title_to' ), '-title -p2p-to' );

			echo '<ul>';

			while ( $connected->have_posts() ) {
				$connected->the_post();

				echo gEditorial\ShortCode::postItem( $GLOBALS['post'], [
					'item_link'  => WordPress\Post::link( NULL, FALSE ),
					'item_after' => $this->p2p_get_meta_row( 'main_posttype', $GLOBALS['post']->p2p_id, ' &ndash; ', '' ),
				] );
			}

			echo '</ul></div>';
			wp_reset_postdata();
		}
	}

	public function insert_cover( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		ModuleTemplate::postImage( [
			'size' => WordPress\Media::getAttachmentImageDefaultSize( $this->constant( 'main_posttype' ), NULL, 'medium' ),
			'link' => 'attachment',
		] );
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
			_x( 'Publication Tools', 'Header', 'geditorial-book' ) );

			$this->paired_tools_render_card( $uri, $sub );

		echo '</div>';
	}

	protected function render_tools_html_before( $uri, $sub )
	{
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
		if ( ! $this->posttype_overview_render_table( 'main_posttype', $uri, $sub ) )
			return gEditorial\Info::renderNoReportsAvailable();
	}

	// @REF: http://wordpress.stackexchange.com/a/246358/3687
	// NOTE: UNFINISHED: just displays the imported connected data (not handling)
	protected function render_tools_html_OLD( $uri, $sub )
	{
		$list  = Core\Arraay::keepByKeys( WordPress\PostType::get( 0, [ 'show_ui' => TRUE ] ), $this->get_setting( 'p2p_posttypes', [] ) );
		$query = [
			'meta_query' => [
				'relation'         => 'OR',
				'import_id_clause' => [
					'key'     => $this->constant( 'metakey_import_id' ),
					'compare' => 'EXISTS',
				],
				'import_title_clause' => [
					'key'     => $this->constant( 'metakey_import_title' ),
					'compare' => 'EXISTS',
				],
				'orderby' => [
					'import_id_clause'    => 'DESC',
					'import_title_clause' => 'ASC',
				],
			],
		];

		list( $posts, $pagination ) = gEditorial\Tablelist::getPosts( $query, [], array_keys( $list ), $this->get_sub_limit_option( $sub, 'tools' ) );

		$pagination['before'][] = gEditorial\Tablelist::filterPostTypes( $list );
		$pagination['before'][] = gEditorial\Tablelist::filterSearch( $list );

		return Core\HTML::tableList( [
			'_cb'   => 'ID',
			'type'  => gEditorial\Tablelist::columnPostType(),
			'title' => gEditorial\Tablelist::columnPostTitle(),
			'metas' => [
				'title'    => _x( 'Import Meta', 'Table Column', 'geditorial-book' ),
				'args'     => [ 'fields' => $this->get_importer_fields() ],
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

					$html = '';

					foreach ( $column['args']['fields'] as $field => $title )
						if ( $meta = get_post_meta( $row->ID, $field, TRUE ) )
							$html.= '<div><b>'.$title.'</b>: '.$meta.'</div>';

					return $html ?: gEditorial\Helper::htmlEmpty();
				},
			],
			'related' => [
				'title'    => _x( 'Import Related', 'Table Column', 'geditorial-book' ),
				'args'     => [ 'type' => $this->constant( 'main_posttype' ) ],
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

					$html = '';

					if ( $id = get_post_meta( $row->ID, 'book_publication_id', TRUE ) )
						$html.= '<div><b>'._x( 'By ID', 'Tools', 'geditorial-book' ).'</b>: '.gEditorial\Helper::getPostTitleRow( $id ).'</div>';

					if ( $title = get_post_meta( $row->ID, 'book_publication_title', TRUE ) )
						foreach ( (array) WordPress\Post::getByTitle( $title, $column['args']['type'] ) as $post_id )
							$html.= '<div><b>'._x( 'By Title', 'Tools', 'geditorial-book' ).'</b>: '.gEditorial\Helper::getPostTitleRow( $post_id ).'</div>';

					return $html ?: gEditorial\Helper::htmlEmpty();
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', _x( 'Overview of Meta Information about Related Publications', 'Header', 'geditorial-book' ) ),
			'empty'      => $this->get_posttype_label( 'main_posttype', 'not_found' ),
			'pagination' => $pagination,
		] );
	}

	// @REF: `Template::getMetaField()`
	public function meta_field( $meta, $field, $post, $args, $raw, $field_args, $context )
	{
		switch ( $field ) {

			case 'publication_edition': return Core\Number::localize( Core\Number::toOrdinal( $raw ) );         // NOTE: not always a number/fallback localize
			case 'publication_print'  : return Core\Number::localize( Core\Number::toOrdinal( $raw ) );         // NOTE: not always a number/fallback localize
			case 'collection'         : return Core\HTML::link( $raw, WordPress\URL::search( $raw ) );

			case 'total_pages':
				return sprintf( gEditorial\Helper::noopedCount( trim( $raw ), gEditorial\Info::getNoop( 'page' ) ),
					Core\Number::format( trim( $raw ) ) );

			case 'total_volumes':
				return sprintf( gEditorial\Helper::noopedCount( trim( $raw ), gEditorial\Info::getNoop( 'volume' ) ),
					Core\Number::format( trim( $raw ) ) );

			case 'total_discs':
				return sprintf( gEditorial\Helper::noopedCount( trim( $raw ), gEditorial\Info::getNoop( 'disc' ) ),
					Core\Number::format( trim( $raw ) ) );
		}

		return $meta;
	}

	public function pairedimports_define_import_types( $types, $linked, $posttypes, $module_key )
	{
		$posttype = $this->constant( 'main_posttype' );

		if ( ! in_array( $posttype, $posttypes, TURE ) )
			return $types;

		$fields = gEditorial()->module( 'meta' )->get_posttype_fields( $posttype );

		if ( empty( $fields ) )
			return $types;

		return array_merge( $types, Core\Arraay::pluck( $fields, 'title', 'name' ) );
	}

	private function get_importer_fields( $posttype = NULL )
	{
		if ( $posttype == $this->constant( 'main_posttype' ) )
			return [];

		if ( $this->posttype_supported( $posttype ) )
			return [
				'book_publication_id'    => _x( 'Book: Publication ID', 'Import Field', 'geditorial-book' ),
				'book_publication_title' => _x( 'Book: Publication Title', 'Import Field', 'geditorial-book' ),
				'book_publication_ref'   => _x( 'Book: Publication Ref (P2P)', 'Import Field', 'geditorial-book' ),
				'book_publication_desc'  => _x( 'Book: Publication Desc (P2P)', 'Import Field', 'geditorial-book' ),
			];

		return [];
	}

	public function importer_fields( $fields, $posttype )
	{
		return array_merge( $fields, $this->get_importer_fields( $posttype ) );
	}

	public function importer_prepare( $value, $posttype, $field, $header, $raw, $source_id, $all_taxonomies )
	{
		$fields = array_keys( $this->get_importer_fields( $posttype ) );

		if ( ! in_array( $field, $fields ) )
			return $value;

		return WordPress\Strings::kses( $value, 'none' );
	}

	// FIXME: use `$atts['prepared'][$field]`
	public function importer_saved( $post, $atts = [] )
	{
		if ( ! $post || ! $this->posttype_supported( $post->post_type ) )
			return;

		$fields = array_keys( $this->get_importer_fields( $post->post_type ) );

		foreach ( $atts['map'] as $offset => $field ) {

			if ( ! in_array( $field, $fields ) )
				continue;

			if ( $value = WordPress\Strings::kses( $atts['raw'][$offset], 'none' ) )
				$this->store_postmeta( $post->ID, $value, $field );
		}
	}
}
