<?php namespace geminorum\gEditorial\Modules\Book;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Metabox;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class Book extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;
	use Internals\DashboardSummary;
	use Internals\MetaBoxCustom;
	use Internals\MetaBoxMain;
	use Internals\PairedAdmin;
	use Internals\PairedCore;
	use Internals\PairedMetaBox;
	use Internals\PairedTools;
	use Internals\PostMeta;
	use Internals\PostsToPosts;
	use Internals\TemplatePostType;

	protected $deafults = [ 'multiple_instances' => TRUE ];

	protected $barcode_type = 'ean13';

	public static function module()
	{
		return [
			'name'     => 'book',
			'title'    => _x( 'Book', 'Modules: Book', 'geditorial-admin' ),
			'desc'     => _x( 'Online House of Publications', 'Modules: Book', 'geditorial-admin' ),
			'icon'     => 'book-alt',
			'access'   => 'stable',
			'keywords' => [
				'paired',
				'cptmodule',
			],
		];
	}

	public function settings_intro()
	{
		Info::renderNoticeP2P();
	}

	protected function get_global_settings()
	{
		$settings = [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				'paired_force_parents',
				'paired_manage_restricted',
				'comment_status',
				'paired_exclude_terms' => [
					NULL,
					$this->constant( 'publication_category' ),
					$this->get_taxonomy_label( 'publication_category', 'no_terms' ),
				],
			],
			'_dashboard' => [
				'dashboard_widgets',
				'summary_excludes' => [
					NULL,
					WordPress\Taxonomy::listTerms( $this->constant( 'status_taxonomy' ) ),
					$this->get_taxonomy_label( 'status_taxonomy', 'no_terms' ),
				],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_editlist' => [
				'admin_rowactions',
			],
			'_frontend' => [
				'insert_content',
				'insert_cover',
				'insert_priority',
			],
			'_content' => [
				'archive_override',
				'display_searchform',
				'empty_content',
				'archive_title' => [ NULL, $this->get_posttype_label( 'publication_posttype', 'all_items' ) ],
				'archive_content',
				'archive_template',
			],
			'_supports' => [
				'assign_default_term',
				'widget_support',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'publication_posttype', TRUE ),
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
			'publication_posttype'     => 'publication',
			'publication_posttype_p2p' => 'related_publications',
			'publication_paired'       => 'related_publication',
			'publication_category'     => 'publication_category',
			'subject_taxonomy'         => 'publication_subject',
			'serie_taxonomy'           => 'publication_serie',       // TODO: move to new TaxModule
			'library_taxonomy'         => 'publication_library',
			'publisher_taxonomy'       => 'publication_publisher',
			'type_taxonomy'            => 'publication_type',
			'status_taxonomy'          => 'publication_status',
			'size_taxonomy'            => 'publication_size',        // TODO: move to `Units` Module: `book_cover`
			'audience_taxonomy'        => 'publication_audience',

			'publication_shortcode' => 'publication',
			'subject_shortcode'     => 'publication-subject',
			'serie_shortcode'       => 'publication-serie',
			'cover_shortcode'       => 'publication-cover',

			'metakey_import_id'    => 'book_publication_id',
			'metakey_import_title' => 'book_publication_title',
			'metakey_import_ref'   => 'book_publication_ref',
			'metakey_import_desc'  => 'book_publication_desc',

			'isbn_query' => 'isbn',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'publication_category' => 'category',
				'subject_taxonomy'     => 'tag',
				'serie_taxonomy'       => 'tag',
				'library_taxonomy'     => 'book-alt',
				'publisher_taxonomy'   => 'book',
				'type_taxonomy'        => 'admin-media',
				'status_taxonomy'      => 'post-status',
				'size_taxonomy'        => 'image-crop',
				'audience_taxonomy'    => 'groups',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'publication_posttype' => _n_noop( 'Publication', 'Publications', 'geditorial-book' ),
				'publication_category' => _n_noop( 'Publication Category', 'Publication Categories', 'geditorial-book' ),
				'subject_taxonomy'     => _n_noop( 'Subject', 'Subjects', 'geditorial-book' ),
				'serie_taxonomy'       => _n_noop( 'Serie', 'Series', 'geditorial-book' ),
				'library_taxonomy'     => _n_noop( 'Library', 'Libraries', 'geditorial-book' ),
				'publisher_taxonomy'   => _n_noop( 'Publisher', 'Publishers', 'geditorial-book' ),
				'type_taxonomy'        => _n_noop( 'Publication Type', 'Publication Types', 'geditorial-book' ),
				'status_taxonomy'      => _n_noop( 'Publication Status', 'Publication Statuses', 'geditorial-book' ),
				'size_taxonomy'        => _n_noop( 'Publication Size', 'Publication Sizes', 'geditorial-book' ),
				'audience_taxonomy'    => _n_noop( 'Publication Audience', 'Publication Audiences', 'geditorial-book' ),
			],
			'labels' => [
				'publication_posttype' => [
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
				'publisher_taxonomy' => [
					'show_option_all'      => _x( 'Publisher', 'Label: Show Option All', 'geditorial-book' ),
					'show_option_no_items' => _x( '(Without Publisher)', 'Label: Show Option No Items', 'geditorial-book' ),
				],
			],
			'p2p' => [
				'publication_posttype' => [
					'fields' => [
						'page' => [
							'title'    => _x( 'Pages', 'P2P', 'geditorial-book' ),
							'type'     => 'text',
							/* translators: %s: pages placeholder */
							'template' => _x( 'P. %s', 'P2P', 'geditorial-book' ),
						],
						'vol' => [
							'title'    => _x( 'Volume', 'P2P', 'geditorial-book' ),
							'type'     => 'text',
							/* translators: %s: volumes placeholder */
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

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Publications Summary', 'Dashboard Widget Title', 'geditorial-book' ), ],
			'all'     => [ 'widget_title' => _x( 'Editorial Publications Summary', 'Dashboard Widget Title', 'geditorial-book' ), ],
		];

		$strings['p2p']['publication_posttype']['title'] = [
			'from' => _x( 'Connected Publications', 'P2P', 'geditorial-book' ),
			'to'   => _x( 'Connected Posts', 'P2P', 'geditorial-book' ),
		];

		$strings['p2p']['publication_posttype']['from_labels'] = [
			'singular_name' => _x( 'Post', 'P2P', 'geditorial-book' ),
			'search_items'  => _x( 'Search Posts', 'P2P', 'geditorial-book' ),
			'not_found'     => _x( 'No posts found.', 'P2P', 'geditorial-book' ),
			'create'        => _x( 'Connect to a post', 'P2P', 'geditorial-book' ),
		];

		$strings['p2p']['publication_posttype']['to_labels'] = [
			'singular_name' => _x( 'Publications', 'P2P', 'geditorial-book' ),
			'search_items'  => _x( 'Search Publications', 'P2P', 'geditorial-book' ),
			'not_found'     => _x( 'No publications found.', 'P2P', 'geditorial-book' ),
			'create'        => _x( 'Connect to a publication', 'P2P', 'geditorial-book' ),
		];

		$strings['p2p']['publication_posttype']['admin_column'] = FALSE; // adding through tweaks module

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'type_taxonomy' => [
				'paperback' => _x( 'Paperback', 'Publication Type: Default Term', 'geditorial-book' ), // shomiz
				'hardcover' => _x( 'Hardcover', 'Publication Type: Default Term', 'geditorial-book' ), // gallingor
				'ebook'     => _x( 'E-Book', 'Publication Type: Default Term', 'geditorial-book' ),
				'disc'      => _x( 'Disc', 'Publication Type: Default Term', 'geditorial-book' ),
			],
			'size_taxonomy' => [
				'octavo'      => _x( 'Octavo', 'Publication Size: Default Term', 'geditorial-book' ), // vaziri
				'folio'       => _x( 'Folio', 'Publication Size: Default Term', 'geditorial-book' ), // soltani
				'medium'      => _x( 'Medium Octavo', 'Publication Size: Default Term', 'geditorial-book' ), // roghee
				'quatro'      => _x( 'Quatro', 'Publication Size: Default Term', 'geditorial-book' ), // rahli
				'duodecimo'   => _x( 'Duodecimo', 'Publication Size: Default Term', 'geditorial-book' ), // paltoyee
				'sextodecimo' => _x( 'Sextodecimo', 'Publication Size: Default Term', 'geditorial-book' ), // jibi
			],
			'status_taxonomy' => [
				'not-available-in-print' => _x( 'Not Available in Print', 'Publication Status: Default Term', 'geditorial-book' ),
				'soon-to-be-published'   => _x( 'Soon to be Published', 'Publication Status: Default Term', 'geditorial-book' ),
				'secondary-print'        => _x( 'Secondary Print', 'Publication Status: Default Term', 'geditorial-book' ),
				'repeat-print'           => _x( 'Repeat Print', 'Publication Status: Default Term', 'geditorial-book' ),
				'first-print'            => _x( 'First Print', 'Publication Status: Default Term', 'geditorial-book' ),
			],
		];
	}

	public function get_global_fields()
	{
		return [ 'meta' => [
			$this->constant( 'publication_posttype' ) => [
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
				],
				'collection' => [
					'title'       => _x( 'Collection Title', 'Field Title', 'geditorial-book' ),
					'description' => _x( 'This Publication Is Part of a Collection', 'Field Description', 'geditorial-book' ),
				],
				'publication_byline' => [
					'title'       => _x( 'Publication By-Line', 'Field Title', 'geditorial-book' ),
					'description' => _x( 'Text to override the publication author', 'Field Description', 'geditorial-book' ),
					'type'        => 'note',
					'icon'        => 'businessperson',
					// 'quickedit'   => TRUE, // will lose the line breaks on quick edit
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
				'publish_location' => [
					'title'       => _x( 'Publish Location', 'Field Title', 'geditorial-book' ),
					'description' => _x( 'Location Published', 'Field Description', 'geditorial-book' ),
					'icon'        => 'location-alt',
				],
				'publication_date' => [
					'title'       => _x( 'Publication Date', 'Field Title', 'geditorial-book' ),
					'description' => _x( 'Date Published', 'Field Description', 'geditorial-book' ),
					'type'        => 'datestring',
					'icon'        => 'calendar-alt',
				],
				'publication_isbn' => [
					'title'       => _x( 'ISBN', 'Field Title', 'geditorial-book' ),
					'description' => _x( 'International Standard Book Number', 'Field Description', 'geditorial-book' ),
					'type'        => 'isbn',
					'icon'        => 'menu',
					'quickedit'   => TRUE,
				],
				'publication_ddc' => [
					// @REF: https://en.wikipedia.org/wiki/Dewey_Decimal_Classification
					'title'       => _x( 'DDC', 'Field Title', 'geditorial-book' ),
					'description' => _x( 'Dewey Decimal Classification', 'Field Description', 'geditorial-book' ),
					'type'        => 'code',
					'icon'        => 'shortcode',
					'quickedit'   => TRUE,
				],
				'publication_lcc' => [
					// @REF: https://en.wikipedia.org/wiki/Library_of_Congress_Classification
					'title'       => _x( 'LCC', 'Field Title', 'geditorial-book' ),
					'description' => _x( 'Library of Congress Classification', 'Field Description', 'geditorial-book' ),
					'type'        => 'code',
					'icon'        => 'shortcode',
					'quickedit'   => TRUE,
				],
				'total_pages' => [
					'title'       => _x( 'Pages', 'Field Title', 'geditorial-book' ),
					'description' => _x( 'Total Pages of the Publication', 'Field Description', 'geditorial-book' ),
					'type'        => 'number',
					'icon'        => 'admin-page',
				],
				'total_volumes' => [
					'title'       => _x( 'Volumes', 'Field Title', 'geditorial-book' ),
					'description' => _x( 'Total Volumes of the Publication', 'Field Description', 'geditorial-book' ),
					'type'        => 'number',
					'icon'        => 'book-alt',
				],
				'total_discs' => [
					'title'       => _x( 'Discs', 'Field Title', 'geditorial-book' ),
					'description' => _x( 'Total Discs of the Publication', 'Field Description', 'geditorial-book' ),
					'type'        => 'number',
					'icon'        => 'album',
				],
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
					// 'quickedit'   => TRUE, // will lose the line breaks on quick edit
				],
				'highlight'    => [ 'type' => 'note' ],
				'source_title' => [ 'type' => 'text' ],
				'source_url'   => [ 'type' => 'link' ],
				'action_title' => [ 'type' => 'text' ],
				'action_url'   => [ 'type' => 'link' ],
				'cover_blurb'  => [ 'type' => 'note' ],
				'cover_price'  => [ 'type' => 'price' ],
			],
		] ];
	}

	protected function paired_get_paired_constants()
	{
		return [
			'publication_posttype',
			'publication_paired',
			FALSE,
			'publication_category',
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'publication_posttype' );
	}

	public function p2p_init()
	{
		$posttypes = $this->get_setting( 'p2p_posttypes', [] );

		if ( empty( $posttypes ) )
			return FALSE;

		$this->p2p_register( 'publication_posttype', $posttypes );

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

		$this->register_taxonomy( 'publication_category', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'publication_posttype' );

		$this->register_taxonomy( 'subject_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL, // default meta box
		], 'publication_posttype' );

		$this->register_taxonomy( 'serie_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL, // default meta box
		], 'publication_posttype' );

		$this->register_taxonomy( 'library_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL, // default meta box
		], 'publication_posttype' );

		$this->register_taxonomy( 'publisher_taxonomy', [
			'meta_box_cb'                             => NULL,   // default meta box
			Services\Sitemaps::VIEWABLE_TAXONOMY_PROP => TRUE,   // even empty shows on sitemaps
		], 'publication_posttype' );

		$this->register_taxonomy( 'type_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => '__checklist_terms_callback',
		], 'publication_posttype' );

		$this->register_taxonomy( 'status_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'publication_posttype' );

		$this->register_taxonomy( 'audience_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => '__checklist_terms_callback',
		], 'publication_posttype' );

		if ( count( $this->posttypes() ) ) {

			$this->register_taxonomy( 'publication_paired', [
				'show_ui'      => FALSE,
				'show_in_rest' => FALSE,
			] );

			$this->_paired = $this->constant( 'publication_paired' );
		}

		// FIXME: WTF: use `$this->paired_register()`
		$this->register_posttype( 'publication_posttype', [
			Metabox::POSTTYPE_MAINBOX_PROP            => TRUE,
			WordPress\PostType::PRIMARY_TAXONOMY_PROP => $this->constant( 'publication_category' ),
		] );

		$this->register_shortcode( 'publication_shortcode' );
		$this->register_shortcode( 'subject_shortcode' );
		$this->register_shortcode( 'serie_shortcode' );
		$this->register_shortcode( 'cover_shortcode' );

		$this->_do_add_custom_queries();
		$this->action( 'pre_get_posts' );

	}

	public function importer_init()
	{
		$this->filter_module( 'importer', 'fields', 2 );
		$this->filter_module( 'importer', 'prepare', 7 );
		$this->action_module( 'importer', 'saved', 2 );
	}

	// @REF: https://gist.github.com/carlodaniele/1ca4110fa06902123349a0651d454057
	private function _do_add_custom_queries()
	{
		$query    = $this->constant( 'isbn_query' );
		$posttype = $this->constant( 'publication_posttype' );

		$this->filter_append( 'query_vars', $query );

		add_rewrite_tag( '%'.$query.'%', '([^&]+)' );
		add_rewrite_rule( '^'.$query.'/([^/]*)/?', 'index.php?'.$query.'=$matches[1]', 'top' );
		add_rewrite_rule( '^'.$posttype.'/'.$query.'/([^/]*)/?', 'index.php?post_type='.$posttype.'&'.$query.'=$matches[1]', 'top' );
	}

	public function get_isbn_link( $isbn, $extra = [] )
	{
		return get_option( 'permalink_structure' )
			? add_query_arg( $extra, sprintf( '%s/%s/%s', Core\URL::untrail( get_bloginfo( 'url' ) ), $this->constant( 'isbn_query' ), Core\ISBN::prep( $isbn ) ) )
			: add_query_arg( array_merge( [ $this->constant( 'isbn_query' ) => Core\ISBN::prep( $isbn ) ], $extra ), get_bloginfo( 'url' ) );
	}

	public function get_isbn( $post = NULL )
	{
		return ModuleTemplate::getMetaFieldRaw( 'publication_isbn', $post, 'meta', TRUE );
	}

	public function pre_get_posts( &$query )
	{
		if ( is_admin() || ! $query->is_main_query() )
			return;

		if ( ! is_post_type_archive( $this->constant( 'publication_posttype' ) ) )
			return;

		$isbn = get_query_var( $this->constant( 'isbn_query' ) );

		if ( empty( $isbn ) )
			return;

		$query->set( 'meta_key', '_meta_publication_isbn' );
		$query->set( 'meta_value', $isbn );
		$query->set( 'meta_compare', 'LIKE' );
	}

	public function template_redirect()
	{
		if ( ( is_home() || is_404() ) && ( $isbn = get_query_var( $this->constant( 'isbn_query' ) ) ) ) {

			if ( ! $post_id = WordPress\PostType::getIDbyMeta( '_meta_publication_isbn', $isbn ) )
				return;

			if ( ! $post = WordPress\Post::get( $post_id ) )
				return;

			if ( $post->post_type != $this->constant( 'publication_posttype' ) )
				return;

			if ( ! $this->is_post_viewable( $post ) )
				return;

			Core\WordPress::redirect( get_page_link( $post->ID ), 302 );

		} else if ( $this->_paired && is_tax( $this->constant( 'publication_paired' ) ) ) {

			if ( $post_id = $this->paired_get_to_post_id( get_queried_object(), 'publication_posttype', 'publication_paired' ) )
				Core\WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_singular( $this->constant( 'publication_posttype' ) ) ) {

			if ( $this->get_setting( 'insert_cover' ) )
				add_action( $this->hook_base( 'content', 'before' ),
					[ $this, 'insert_cover' ],
					$this->get_setting( 'insert_priority', -50 )
				);

		} else if ( $this->_paired && is_singular( $this->posttypes() ) ) {

			$this->hook_insert_content();
		}
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'publication_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );

				$this->_hook_general_mainbox( $screen, 'publication_posttype' );

				if ( post_type_supports( $screen->post_type, 'author' ) )
					$this->metaboxcustom_add_metabox_author( 'publication_posttype' );

				if ( post_type_supports( $screen->post_type, 'excerpt' ) )
					$this->metaboxcustom_add_metabox_excerpt( 'publication_posttype' );

				$this->_hook_post_updated_messages( 'publication_posttype' );
				$this->_hook_paired_listbox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				if ( $this->get_setting( 'admin_rowactions' ) )
					$this->filter( 'post_row_actions', 2 );

				if ( $this->_p2p )
					$this->coreadmin__hook_tweaks_column_row( $screen->post_type, -25, 'p2p_to' );

				$this->postmeta__hook_meta_column_row( $screen->post_type );

				$this->_hook_bulk_post_updated_messages( 'publication_posttype' );
				$this->pairedadmin__hook_tweaks_column_connected( $screen->post_type );
				$this->pairedcore__hook_sync_paired();
				$this->corerestrictposts__hook_screen_taxonomies( [
					'type_taxonomy',
					'publication_category',
					'subject_taxonomy',
					'serie_taxonomy',
					'library_taxonomy',
					'status_taxonomy',
					'audience_taxonomy',
					'publisher_taxonomy',
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

	protected function dashboard_widgets()
	{
		$this->add_dashboard_widget( 'term-summary', NULL, 'refresh' );
	}

	public function render_widget_term_summary( $object, $box )
	{
		$this->do_dashboard_term_summary( 'status_taxonomy', $box, [ $this->constant( 'publication_posttype' ) ] );
	}

	public function tweaks_column_row_p2p_to( $post, $before, $after )
	{
		$this->column_row_p2p_to_posttype( 'publication_posttype', $post, $before, $after );
	}

	public function tweaks_column_row_p2p_from( $post, $before, $after )
	{
		$this->column_row_p2p_from_posttype( 'publication_posttype', $post, $before, $after );
	}

	public function prep_meta_row_module( $value, $field_key = NULL, $field = [], $raw = NULL )
	{
		switch ( $field_key ) {
			// FIXME: MUST BE DEPRECATED: use type: `isbn`
			case 'publication_isbn'   : return Core\HTML::link( Core\ISBN::prep( $raw ?: $value, TRUE ), Info::lookupISBN( $raw ?: $value ), TRUE );
			/* translators: %s: edition placeholder */
			case 'publication_edition': return sprintf( _x( '%s Edition', 'Display', 'geditorial-book' ), Core\Number::localize( Core\Number::toOrdinal( $raw ?: $value ) ) );
			/* translators: %s: print placeholder */
			case 'publication_print'  : return sprintf( _x( '%s Print', 'Display', 'geditorial-book' ), Core\Number::localize( Core\Number::toOrdinal( $raw ?: $value ) ) );
			/* translators: %s: pages count placeholder */
			case 'total_pages'        : return WordPress\Strings::getCounted( $raw ?: $value, _x( '%s Pages', 'Display', 'geditorial-book' ) );
			/* translators: %s: volumes count placeholder */
			case 'total_volumes'      : return WordPress\Strings::getCounted( $raw ?: $value, _x( '%s Volumes', 'Display', 'geditorial-book' ) );
		}

		return $value;
	}

	public function meta_init()
	{
		// FIXME: DEPRECATED: use `Units` Module
		$this->register_taxonomy( 'size_taxonomy', [
			'meta_box_cb' => FALSE,
		], 'publication_posttype' );

		$this->add_posttype_fields( $this->constant( 'publication_posttype' ) );
		$this->filter( 'prep_meta_row', 2, 12, 'module', $this->base );
		$this->filter( 'meta_field', 7, 9, FALSE, $this->base );

		$this->filter_module( 'national_library', 'default_posttype_isbn_metakey', 2 );
		$this->filter_module( 'datacodes', 'default_posttype_barcode_metakey', 2 );
		$this->filter_module( 'datacodes', 'default_posttype_barcode_type', 3 );

		// $this->register_default_terms( 'size_taxonomy' );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'publication_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function post_row_actions( $actions, $post )
	{
		if ( in_array( $post->post_status, [ 'trash', 'private', 'auto-draft' ], TRUE ) )
			return $actions;

		if ( ! $isbn = $this->get_isbn( $post ) )
			return $actions;

		if ( ! $link = $this->get_isbn_link( $isbn ) )
			return $actions;

		return Core\Arraay::insert( $actions, [
			$this->classs() => Core\HTML::tag( 'a', [
				'href'   => $link,
				'title'  => _x( 'ISBN Link to this publication', 'Title Attr', 'geditorial-book' ),
				'class'  => '-isbn-link',
				'target' => '_blank',
			], _x( 'ISBN', 'Action', 'geditorial-book' ) ),
		], 'view', 'after' );
	}

	public function template_include( $template )
	{
		return $this->templateposttype__include( $template, $this->constant( 'publication_posttype' ) );
	}

	public function templateposttype_get_archive_content_default( $posttype )
	{
		$html = $this->get_search_form( 'publication_posttype' );

		if ( gEditorial()->enabled( 'alphabet' ) )
			$html.= gEditorial()->module( 'alphabet' )->shortcode_posts( [
				'post_type' => $posttype, // $this->constant( 'publication_posttype' )
			] );

		else
			$html.= $this->subject_shortcode( [
				'id'     => 'all',
				'future' => 'off',
				'title'  => FALSE,
				'wrap'   => FALSE,
			] );

		return $html;
	}

	public function subject_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts( 'assigned',
			$this->constant( 'publication_posttype' ),
			$this->constant( 'subject_taxonomy' ),
			$atts,
			$content,
			$this->constant( 'subject_shortcode', $tag ),
			$this->key
		);
	}

	public function serie_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts( 'assigned',
			$this->constant( 'publication_posttype' ),
			$this->constant( 'serie_taxonomy' ),
			$atts,
			$content,
			$this->constant( 'serie_shortcode', $tag ),
			$this->key
		);
	}

	public function publication_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		if ( ! $this->_p2p )
			return $content;

		return ShortCode::listPosts( 'connected',
			$this->constant( 'publication_posttype' ),
			'',
			array_merge( [
				'connection'    => $this->_p2p,
				'posttypes'     => $this->get_setting( 'p2p_posttypes', [] ),
				'title_cb'      => [ $this, 'shortcode_title_cb' ],
				'item_after_cb' => [ $this, 'shortcode_item_after_cb' ],
				'title_anchor'  => 'publications',
				'title_link'    => FALSE,
			], (array) $atts ),
			$content,
			$this->constant( 'publication_shortcode', $tag ),
			$this->key
		);
	}

	public function shortcode_title_cb( $post, $args, $text, $link )
	{
		if ( FALSE === $args['title'] )
			return FALSE;

		if ( $this->is_posttype( 'publication_posttype', $post ) ) {

			if ( $title = $this->get_setting( 'p2p_title_from' ) )
				return $title;

		} else if ( $title = $this->get_setting( 'p2p_title_to' ) ) {

			return $title;
		}

		return FALSE;
	}

	public function shortcode_item_after_cb( $post, $args, $item )
	{
		return $this->_p2p ? $this->p2p_get_meta_row( 'publication_posttype', $post->p2p_id, ' &ndash; ', '' ) : '';
	}

	public function cover_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$type = $this->constant( 'publication_posttype' );
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

		return ShortCode::wrap( $html,
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

		if ( ! $p2p_type = p2p_type( $this->constant( 'publication_posttype_p2p' ) ) )
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
	// FIXME: DEPRECATED: use `publication_shortcode()`
	public function list_p2p( $post = NULL, $class = '' )
	{
		if ( ! $this->_p2p )
			return;

		if ( ! $post = WordPress\Post::get( $post ) )
			return;

		$connected = new \WP_Query( [
			'connected_type'  => $this->constant( 'publication_posttype_p2p' ),
			'connected_items' => $post,
			'posts_per_page'  => -1,
		] );

		if ( $connected->have_posts() ) {

			echo $this->wrap_open( '-p2p '.$class );

			if ( $this->is_posttype( 'publication_posttype', $post ) )
				Core\HTML::h3( $this->get_setting( 'p2p_title_from' ), '-title -p2p-from' );

			else
				Core\HTML::h3( $this->get_setting( 'p2p_title_to' ), '-title -p2p-to' );

			echo '<ul>';

			while ( $connected->have_posts() ) {
				$connected->the_post();

				echo ShortCode::postItem( $GLOBALS['post'], [
					'item_link'  => WordPress\Post::link( NULL, FALSE ),
					'item_after' => $this->p2p_get_meta_row( 'publication_posttype', $GLOBALS['post']->p2p_id, ' &ndash; ', '' ),
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
			'size' => WordPress\Media::getAttachmentImageDefaultSize( $this->constant( 'publication_posttype' ), NULL, 'medium' ),
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

			Scripts::enqueueThickBox();
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo Settings::toolboxColumnOpen(
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

			Scripts::enqueueThickBox();
		}
	}

	protected function render_imports_html( $uri, $sub )
	{
		return $this->paired_imports_render_tablelist( $uri, $sub, NULL,
			_x( 'Publication Imports', 'Header', 'geditorial-book' ) );
	}

	// @REF: http://wordpress.stackexchange.com/a/246358/3687
	// NOTE: UNFINISHED: just displayes the imported connected data (not handling)
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

		list( $posts, $pagination ) = Tablelist::getPosts( $query, [], array_keys( $list ), $this->get_sub_limit_option( $sub ) );

		$pagination['before'][] = Tablelist::filterPostTypes( $list );
		$pagination['before'][] = Tablelist::filterSearch( $list );

		return Core\HTML::tableList( [
			'_cb'   => 'ID',
			'ID'    => Tablelist::columnPostID(),
			'date'  => Tablelist::columnPostDate(),
			'type'  => Tablelist::columnPostType(),
			'title' => Tablelist::columnPostTitle(),
			'metas' => [
				'title'    => _x( 'Import Meta', 'Table Column', 'geditorial-book' ),
				'args'     => [ 'fields' => $this->get_importer_fields() ],
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
					$html = '';

					foreach ( $column['args']['fields'] as $field => $title )
						if ( $meta = get_post_meta( $row->ID, $field, TRUE ) )
							$html.= '<div><b>'.$title.'</b>: '.$meta.'</div>';

					return $html ?: Helper::htmlEmpty();
				},
			],
			'related' => [
				'title'    => _x( 'Import Related', 'Table Column', 'geditorial-book' ),
				'args'     => [ 'type' => $this->constant( 'publication_posttype' ) ],
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

					$html = '';

					if ( $id = get_post_meta( $row->ID, 'book_publication_id', TRUE ) )
						$html.= '<div><b>'._x( 'By ID', 'Tools', 'geditorial-book' ).'</b>: '.Helper::getPostTitleRow( $id ).'</div>';

					if ( $title = get_post_meta( $row->ID, 'book_publication_title', TRUE ) )
						foreach ( (array) WordPress\Post::getByTitle( $title, $column['args']['type'] ) as $post_id )
							$html.= '<div><b>'._x( 'By Title', 'Tools', 'geditorial-book' ).'</b>: '.Helper::getPostTitleRow( $post_id ).'</div>';

					return $html ?: Helper::htmlEmpty();
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', _x( 'Overview of Meta Information about Related Publications', 'Header', 'geditorial-book' ) ),
			'empty'      => $this->get_posttype_label( 'publication_posttype', 'not_found' ),
			'pagination' => $pagination,
		] );
	}

	// @REF: `Template::getMetaField()`
	public function meta_field( $meta, $field, $post, $args, $raw, $field_args, $context )
	{
		switch ( $field ) {
			case 'publication_isbn': return ModuleHelper::ISBN( $raw );
			// case 'publication_date': return Core\Number::localize( Datetime::stringFormat( $raw ) );
			case 'publication_edition': return Core\Number::localize( Core\Number::toOrdinal( $raw ) ); // NOTE: not always a number/fallback localize
			case 'publication_print': return Core\Number::localize( Core\Number::toOrdinal( $raw ) ); // NOTE: not always a number/fallback localize
			case 'collection': return Core\HTML::link( $raw, Core\WordPress::getSearchLink( $raw ) );

			/* translators: %s: total pages */
			case 'total_pages': return sprintf( _nx( '%s Page', '%s Pages', $raw, 'Noop', 'geditorial-book' ), Core\Number::format( $raw ) );

			/* translators: %s: total volumes */
			case 'total_volumes': return sprintf( _nx( '%s Volume', '%s Volumes', $raw, 'Noop', 'geditorial-book' ), Core\Number::format( $raw ) );

			/* translators: %s: total discs */
			case 'total_discs': return sprintf( _nx( '%s Disc', '%s Discs', $raw, 'Noop', 'geditorial-book' ), Core\Number::format( $raw ) );
		}

		return $meta;
	}

	public function national_library_default_posttype_isbn_metakey( $default, $posttype )
	{
		if ( $posttype == $this->constant( 'publication_posttype' ) )
			return '_meta_publication_isbn';

		return $default;
	}

	public function datacodes_default_posttype_barcode_metakey( $default, $posttype )
	{
		if ( $posttype == $this->constant( 'publication_posttype' ) )
			return '_meta_publication_isbn';

		return $default;
	}

	public function datacodes_default_posttype_barcode_type( $default, $posttype, $types )
	{
		if ( $posttype == $this->constant( 'publication_posttype' ) )
			return $this->barcode_type;

		return $default;
	}

	private function get_importer_fields( $posttype = NULL )
	{
		if ( $posttype == $this->constant( 'publication_posttype' ) )
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

		return Helper::kses( $value, 'none' );
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

			if ( $value = trim( Helper::kses( $atts['raw'][$offset], 'none' ) ) )
				$this->store_postmeta( $post->ID, $value, $field );
		}
	}
}
