<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\Helpers\Book as ModuleHelper;
use geminorum\gEditorial\Templates\Book as ModuleTemplate;

class Book extends gEditorial\Module
{

	protected $partials = [ 'Templates', 'Helper', 'Query' ];

	protected $support_meta = FALSE;

	public static function module()
	{
		return [
			'name'  => 'book',
			'title' => _x( 'Book', 'Modules: Book', 'geditorial' ),
			'desc'  => _x( 'Online House of Publications', 'Modules: Book', 'geditorial' ),
			'icon'  => 'book-alt',
		];
	}

	public function settings_intro()
	{
		if ( ! defined( 'P2P_PLUGIN_VERSION' ) )
			/* translators: %1$s: plugin url, %2$s: plugin url */
			HTML::desc( sprintf( _x( 'Please consider installing <a href="%1$s" target="_blank">Posts to Posts</a> or <a href="%2$s" target="_blank">Objects to Objects</a>.', 'Settings Intro', 'geditorial-book' ),
				'https://github.com/scribu/wp-posts-to-posts/', 'https://github.com/voceconnect/objects-to-objects' ) );
	}

	protected function get_global_settings()
	{
		$settings = [
			'_dashboard' => [
				'dashboard_widgets',
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_frontend' => [
				'insert_cover',
				'insert_priority',
			],
			'_content' => [
				'display_searchform',
				'empty_content',
				'archive_title',
			],
			'_supports' => [
				'comment_status',
				'widget_support',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'publication_cpt', TRUE ),
			],
		];

		if ( defined( 'P2P_PLUGIN_VERSION' ) ) {

			$settings['posttypes_option'] = 'posttypes_option';

			$settings['_frontend'][] = 'insert_content';

			$settings['_frontend'][] = [
				'field' => 'p2p_title_from',
				'type'  => 'text',
				'title' => _x( 'Connected From Title', 'Setting Title', 'geditorial-book' ),
			];

			$settings['_frontend'][] = [
				'field' => 'p2p_title_to',
				'type'  => 'text',
				'title' => _x( 'Connected To Title', 'Setting Title', 'geditorial-book' ),
			];
		}

		return $settings;
	}

	protected function get_global_constants()
	{
		return [
			'publication_cpt'         => 'publication',
			'publication_cpt_archive' => 'publications',
			'publication_cpt_p2p'     => 'related_publications',
			'subject_tax'             => 'publication_subject',
			'library_tax'             => 'publication_library',
			'library_tax_slug'        => 'publication-libraries',
			'publisher_tax'           => 'publication_publisher',
			'type_tax'                => 'publication_type',
			'status_tax'              => 'publication_status',
			'status_tax_slug'         => 'publication-statuses',
			'size_tax'                => 'publication_size',
			'audience_tax'            => 'publication_audience',
			'publication_shortcode'   => 'publication',
			'cover_shortcode'         => 'publication-cover',
			'metakey_import_id'       => 'book_publication_id',
			'metakey_import_title'    => 'book_publication_title',
			'metakey_import_ref'      => 'book_publication_ref',
			'metakey_import_desc'     => 'book_publication_desc',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'subject_tax'   => 'tag',
				'library_tax'   => 'book-alt',
				'publisher_tax' => 'book',
				'type_tax'      => 'admin-media',
				'status_tax'    => 'post-status',
				'size_tax'      => 'image-crop',
				'audience_tax'  => 'groups',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'publication_cpt' => _n_noop( 'Publication', 'Publications', 'geditorial-book' ),
				'subject_tax'     => _n_noop( 'Subject', 'Subjects', 'geditorial-book' ),
				'library_tax'     => _n_noop( 'Library', 'Libraries', 'geditorial-book' ),
				'publisher_tax'   => _n_noop( 'Publisher', 'Publishers', 'geditorial-book' ),
				'type_tax'        => _n_noop( 'Publication Type', 'Publication Types', 'geditorial-book' ),
				'status_tax'      => _n_noop( 'Publication Status', 'Publication Statuses', 'geditorial-book' ),
				'size_tax'        => _n_noop( 'Publication Size', 'Publication Sizes', 'geditorial-book' ),
				'audience_tax'    => _n_noop( 'Publication Audience', 'Publication Audiences', 'geditorial-book' ),
			],
			'p2p' => [
				'publication_cpt' => [
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

		$strings['misc'] = [
			'publication_cpt' => [
				'featured'           => _x( 'Cover Image', 'Posttype Featured', 'geditorial-book' ),
				'meta_box_title'     => _x( 'Metadata', 'MetaBox Title', 'geditorial-book' ),
				'author_metabox'     => _x( 'Curator', 'MetaBox Title', 'geditorial-book' ),
				'excerpt_metabox'    => _x( 'Summary', 'MetaBox Title', 'geditorial-book' ),
				'cover_column_title' => _x( 'Cover', 'Column Title', 'geditorial-book' ),
			],
			'subject_tax' => [
				'meta_box_title'      => _x( 'Subject', 'MetaBox Title', 'geditorial-book' ),
				'tweaks_column_title' => _x( 'Publication Subject', 'Column Title', 'geditorial-book' ),
			],
			'library_tax' => [
				'meta_box_title'      => _x( 'Library', 'MetaBox Title', 'geditorial-book' ),
				'tweaks_column_title' => _x( 'Publication Library', 'Column Title', 'geditorial-book' ),
			],
			'publisher_tax' => [
				'meta_box_title'      => _x( 'Publisher', 'MetaBox Title', 'geditorial-book' ),
				'tweaks_column_title' => _x( 'Publication Publisher', 'Column Title', 'geditorial-book' ),
			],
			'status_tax' => [
				'meta_box_title'      => _x( 'Status', 'MetaBox Title', 'geditorial-book' ),
				'tweaks_column_title' => _x( 'Publication Status', 'Column Title', 'geditorial-book' ),
			],
			'type_tax' => [
				'meta_box_title'      => _x( 'Type', 'MetaBox Title', 'geditorial-book' ),
				'tweaks_column_title' => _x( 'Publication Type', 'Column Title', 'geditorial-book' ),
			],
			'size_tax' => [
				'meta_box_title'      => _x( 'Size', 'MetaBox Title', 'geditorial-book' ),
				'tweaks_column_title' => _x( 'Publication Size', 'Column Title', 'geditorial-book' ),
			],
			'audience_tax' => [
				'meta_box_title'      => _x( 'Audience', 'MetaBox Title', 'geditorial-book' ),
				'tweaks_column_title' => _x( 'Publication Audience', 'Column Title', 'geditorial-book' ),
			],
		];

		$strings['settings'] = [
			'post_types_after' => Settings::infoP2P(),
		];

		$strings['terms'] = [
			'type_tax' => [
				'paperback' => _x( 'Paperback', 'Publication Type: Default Term', 'geditorial-book' ), // shomiz
				'hardcover' => _x( 'Hardcover', 'Publication Type: Default Term', 'geditorial-book' ), // gallingor
				'ebook'     => _x( 'E-Book', 'Publication Type: Default Term', 'geditorial-book' ),
				'disc'      => _x( 'Disc', 'Publication Type: Default Term', 'geditorial-book' ),
			],
			'size_tax' => [
				'octavo'      => _x( 'Octavo', 'Publication Size: Default Term', 'geditorial-book' ), // vaziri
				'folio'       => _x( 'Folio', 'Publication Size: Default Term', 'geditorial-book' ), // soltani
				'medium'      => _x( 'Medium Octavo', 'Publication Size: Default Term', 'geditorial-book' ), // roghee
				'quatro'      => _x( 'Quatro', 'Publication Size: Default Term', 'geditorial-book' ), // rahli
				'duodecimo'   => _x( 'Duodecimo', 'Publication Size: Default Term', 'geditorial-book' ), // paltoyee
				'sextodecimo' => _x( 'Sextodecimo', 'Publication Size: Default Term', 'geditorial-book' ), // jibi
			],
		];

		$strings['p2p']['publication_cpt']['title'] = [
			'from' => _x( 'Connected Publications', 'P2P', 'geditorial-book' ),
			'to'   => _x( 'Connected Posts', 'P2P', 'geditorial-book' ),
		];

		$strings['p2p']['publication_cpt']['from_labels'] = [
			'singular_name' => _x( 'Post', 'P2P', 'geditorial-book' ),
			'search_items'  => _x( 'Search Posts', 'P2P', 'geditorial-book' ),
			'not_found'     => _x( 'No posts found.', 'P2P', 'geditorial-book' ),
			'create'        => _x( 'Connect to a post', 'P2P', 'geditorial-book' ),
		];

		$strings['p2p']['publication_cpt']['to_labels'] = [
			'singular_name' => _x( 'Publications', 'P2P', 'geditorial-book' ),
			'search_items'  => _x( 'Search Publications', 'P2P', 'geditorial-book' ),
			'not_found'     => _x( 'No publications found.', 'P2P', 'geditorial-book' ),
			'create'        => _x( 'Connect to a publication', 'P2P', 'geditorial-book' ),
		];

		$strings['p2p']['publication_cpt']['admin_column'] = FALSE; // adding through tweaks module

		return $strings;
	}

	public function get_global_fields()
	{
		return [
			$this->constant( 'publication_cpt' ) => [
				'collection' => [ // FIXME: must prefixed
					'title'       => _x( 'Collection Title', 'Field Title', 'geditorial-book' ),
					'description' => _x( 'This Publication Is Part of a Collection', 'Field Description', 'geditorial-book' ),
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
				'publication_byline' => [
					'title'       => _x( 'Publication By-Line', 'Field Title', 'geditorial-book' ),
					'description' => _x( 'Text to override the publication author', 'Field Description', 'geditorial-book' ),
					'type'        => 'note',
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
					'icon'        => 'calendar-alt',
				],
				'publication_isbn' => [
					'title'       => _x( 'ISBN', 'Field Title', 'geditorial-book' ),
					'description' => _x( 'International Standard Book Number', 'Field Description', 'geditorial-book' ),
					'type'        => 'code',
					'icon'        => 'menu',
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
				'publication_size' => [
					'title'       => _x( 'Size', 'Field Title', 'geditorial-book' ),
					'description' => _x( 'The Size of the Publication, Mainly Books', 'Field Description', 'geditorial-book' ),
					'type'        => 'term',
					'tax'         => $this->constant( 'size_tax' ),
				],
				'publication_reference' => [
					'title'       => _x( 'Reference', 'Field Title', 'geditorial-book' ),
					'description' => _x( 'Full reference to this publication', 'Field Description', 'geditorial-book' ),
					'type'        => 'note',
				],
			],
		];
	}

	protected function posttypes_excluded()
	{
		return Settings::posttypesExcluded( $this->constant( 'publication_cpt' ) );
	}

	public function before_settings( $module = FALSE )
	{
		if ( isset( $_POST['install_def_type_tax'] ) )
			$this->insert_default_terms( 'type_tax' );

		if ( isset( $_POST['install_def_size_tax'] ) )
			$this->insert_default_terms( 'size_tax' );

		$this->help_tab_default_terms( 'type_tax' );
		$this->help_tab_default_terms( 'size_tax' );
	}

	public function default_buttons( $module = FALSE )
	{
		parent::default_buttons( $module );

		$this->register_button( 'install_def_type_tax', _x( 'Install Default Types', 'Button', 'geditorial-book' ) );

		if ( $this->support_meta )
			$this->register_button( 'install_def_size_tax', _x( 'Install Default Sizes', 'Button', 'geditorial-book' ) );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'publication_cpt' );
	}

	public function p2p_init()
	{
		$this->p2p_register( 'publication_cpt' );

		if ( is_admin() )
			return;

		$this->hook_insert_content( 100 );
	}

	public function widgets_init()
	{
		$this->require_code( 'Widgets/Publication-Cover' );

		register_widget( '\\geminorum\\gEditorial\\Book\\Widgets\\PublicationCover' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'subject_tax', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL, // default meta box
		], 'publication_cpt' );

		$this->register_taxonomy( 'library_tax', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL, // default meta box
		], 'publication_cpt' );

		$this->register_taxonomy( 'publisher_tax', [
			'meta_box_cb' => NULL, // default meta box
		], 'publication_cpt' );

		$this->register_taxonomy( 'type_tax', [
			'hierarchical' => TRUE, // required by `MetaBox::checklistTerms()`
		], 'publication_cpt' );

		$this->register_taxonomy( 'status_tax', [
			'hierarchical'       => TRUE, // required by `MetaBox::checklistTerms()`
			'show_in_quick_edit' => TRUE,
		], 'publication_cpt' );

		$this->register_taxonomy( 'audience_tax', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL, // default meta box
		], 'publication_cpt' );

		$this->register_posttype( 'publication_cpt' );

		$this->register_shortcode( 'publication_shortcode' );
		$this->register_shortcode( 'cover_shortcode' );

		if ( ! is_admin() )
			return;

		$this->filter_module( 'importer', 'fields', 2 );
		$this->filter_module( 'importer', 'prepare', 4 );
		$this->action_module( 'importer', 'saved', 5 );
	}

	public function template_redirect()
	{
		if ( ! is_singular( $this->constant( 'publication_cpt' ) ) )
			return;

		if ( $this->get_setting( 'insert_cover' ) )
			add_action( $this->base.'_content_before',
				[ $this, 'insert_cover' ],
				$this->get_setting( 'insert_priority', -50 )
			);
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'publication_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'post_updated_messages' );
				$this->filter( 'get_default_comment_status', 3 );

				if ( post_type_supports( $screen->post_type, 'author' ) )
					$this->add_meta_box_author( 'publication_cpt' );

				if ( post_type_supports( $screen->post_type, 'excerpt' ) )
					$this->add_meta_box_excerpt( 'publication_cpt' );

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );
				$this->filter( 'bulk_post_updated_messages', 2 );
				$this->action( 'restrict_manage_posts', 2, 12 );
				$this->action( 'parse_query' );

				if ( $this->p2p )
					$this->action_module( 'tweaks', 'column_row', 1, -25, 'p2p_to' );

				$this->action_module( 'meta', 'column_row', 3 );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}

		} else if ( $this->p2p && 'edit' == $screen->base
			&& $this->posttype_supported( $screen->post_type ) ) {

			$this->action_module( 'tweaks', 'column_row', 1, -25, 'p2p_from' );
		}
	}

	protected function dashboard_widgets()
	{
		$title = 'current' == $this->get_setting( 'summary_scope', 'all' )
			? _x( 'Your Publications Summary', 'Dashboard Widget Title', 'geditorial-book' )
			: _x( 'Editorial Publications Summary', 'Dashboard Widget Title', 'geditorial-book' );

		$this->add_dashboard_widget( 'term-summary', $title, 'refresh' );
	}

	public function render_widget_term_summary( $object, $box )
	{
		$this->do_dashboard_term_summary( 'status_tax', $box, [ $this->constant( 'publication_cpt' ) ] );
	}

	public function tweaks_column_row_p2p_to( $post )
	{
		$this->column_row_p2p_to_posttype( 'publication_cpt', $post );
	}

	public function tweaks_column_row_p2p_from( $post )
	{
		$this->column_row_p2p_from_posttype( 'publication_cpt', $post );
	}

	public function display_meta_row( $value, $key = NULL, $field = [] )
	{
		switch ( $key ) {
			/* translators: %s: isbn placeholder */
			case 'isbn'    : return sprintf( _x( 'ISBN: %s', 'Display', 'geditorial-book' ), ModuleHelper::ISBN( $value ) );
			/* translators: %s: edition placeholder */
			case 'edition' : return sprintf( _x( '%s Edition', 'Display', 'geditorial-book' ), $value );
			/* translators: %s: print placeholder */
			case 'print'   : return sprintf( _x( '%s Print', 'Display', 'geditorial-book' ), $value );
			/* translators: %s: pages count placeholder */
			case 'pages'   : return Helper::getCounted( $value, _x( '%s Pages', 'Display', 'geditorial-book' ) );
			/* translators: %s: volumes count placeholder */
			case 'volumes' : return Helper::getCounted( $value, _x( '%s Volumes', 'Display', 'geditorial-book' ) );
		}

		return parent::display_meta_row( $value, $key, $field );
	}

	public function meta_init()
	{
		$this->register_taxonomy( 'size_tax', [
			'meta_box_cb' => FALSE,
		], 'publication_cpt' );

		$this->add_posttype_fields( $this->constant( 'publication_cpt' ) );
		$this->filter_module( 'meta', 'sanitize_posttype_field', 4 );
		$this->filter_module( 'meta', 'field', 4 ); // @SEE: `Template::getMetaField()`

		$this->support_meta = TRUE;
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'publication_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function restrict_manage_posts( $posttype, $which )
	{
		$this->do_restrict_manage_posts_taxes( [
			'type_tax',
			'subject_tax',
			'library_tax',
			'status_tax',
			'audience_tax',
			'publisher_tax',
		] );
	}

	public function parse_query( &$query )
	{
		$this->do_parse_query_taxes( $query, [
			'type_tax',
			'subject_tax',
			'library_tax',
			'status_tax',
			'audience_tax',
			'publisher_tax',
		] );
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'publication_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'publication_cpt', $counts ) );
	}

	public function meta_box_cb_status_tax( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function meta_box_cb_type_tax( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function template_include( $template )
	{
		return $this->do_template_include( $template, 'publication_cpt' );
	}

	public function template_get_archive_content()
	{
		$html = $this->get_search_form( 'publication_cpt' );

		if ( gEditorial()->enabled( 'alphabet' ) )
			$html.= gEditorial()->alphabet->shortcode_posts( [ 'post_type' => $this->constant( 'publication_cpt' ) ] );

		else
			$html.= $this->publication_shortcode( [ 'title' => FALSE, 'wrap' => FALSE ] );

		return $html;
	}

	public function publication_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		if ( ! $this->p2p )
			return $content;

		return ShortCode::listPosts(
			'connected',
			$this->constant( 'publication_cpt' ),
			'',
			array_merge( [
				'connection'    => $this->p2p,
				'posttypes'     => $this->posttypes(),
				'title_cb'      => [ $this, 'shortcode_title_cb' ],
				'item_after_cb' => [ $this, 'shortcode_item_after_cb' ],
				'title_anchor'  => 'publications',
				'title_link'    => FALSE,
			], (array) $atts ),
			$content,
			$this->constant( 'publication_shortcode' )
		);
	}

	public function shortcode_title_cb( $post, $args, $text, $link )
	{
		if ( FALSE === $args['title'] )
			return FALSE;

		if ( $post->post_type == $this->constant( 'publication_cpt' ) ) {

			if ( $title = $this->get_setting( 'p2p_title_from' ) )
				return $title;

		} else if ( $title = $this->get_setting( 'p2p_title_to' ) ) {

			return $title;
		}

		return FALSE;
	}

	public function shortcode_item_after_cb( $post, $args, $item )
	{
		return $this->p2p ? $this->p2p_get_meta_row( 'publication_cpt', $post->p2p_id, ' &ndash; ', '' ) : '';
	}

	public function cover_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = [
			'size' => $this->get_image_size_key( 'publication_cpt', 'medium' ),
			'type' => $this->constant( 'publication_cpt' ),
			'echo' => FALSE,
		];

		if ( is_singular( $args['type'] ) )
			$args['id'] = NULL;

		else if ( is_singular( $this->posttypes() ) )
			$args['id'] = 'assoc';

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
		if ( ! $this->p2p )
			return;

		if ( ! $this->is_content_insert( $this->posttypes( 'publication_cpt' ) ) )
			return;

		$this->list_p2p( NULL, '-'.$this->get_setting( 'insert_content', 'none' ) );
	}

	public function get_assoc_post( $post = NULL, $single = FALSE, $published = TRUE )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return FALSE;

		$posts = [];
		$extra = [ 'p2p:per_page' => -1, 'p2p:context' => 'admin_column' ];

		if ( ! $p2p_type = p2p_type( $this->constant( 'publication_cpt_p2p' ) ) )
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
		if ( ! $this->p2p )
			return;

		if ( ! $post = get_post( $post ) )
			return;

		$connected = new \WP_Query( [
			'connected_type'  => $this->constant( 'publication_cpt_p2p' ),
			'connected_items' => $post,
		] );

		if ( $connected->have_posts() ) {

			echo $this->wrap_open( '-p2p '.$class );

			if ( $post->post_type == $this->constant( 'publication_cpt' ) )
				HTML::h3( $this->get_setting( 'p2p_title_from' ), '-title -p2p-from' );

			else
				HTML::h3( $this->get_setting( 'p2p_title_to' ), '-title -p2p-to' );

			echo '<ul>';

			while ( $connected->have_posts() ) {
				$connected->the_post();

				echo ShortCode::postItem( $GLOBALS['post'], [
					'item_after' => $this->p2p_get_meta_row( 'publication_cpt', $GLOBALS['post']->p2p_id, ' &ndash; ', '' ),
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
			'size' => $this->get_image_size_key( 'publication_cpt', 'medium' ),
			'link' => 'attachment',
		] );
	}

	public function tools_settings( $sub )
	{
		$this->check_settings( $sub, 'tools' );
	}

	// @REF: http://wordpress.stackexchange.com/a/246358/3687
	protected function render_tools_html( $uri, $sub )
	{
		$list  = $this->list_posttypes();
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

		return HTML::tableList( [
			'_cb'   => 'ID',
			'ID'    => Tablelist::columnPostID(),
			'date'  => Tablelist::columnPostDate(),
			'type'  => Tablelist::columnPostType(),
			'title' => Tablelist::columnPostTitle(),
			'metas' => [
				'title'    => _x( 'Import Meta', 'Table Column', 'geditorial-book' ),
				'args'     => [ 'fields' => $this->get_importer_fields() ],
				'callback' => function( $value, $row, $column, $index ){
					$html = '';

					foreach ( $column['args']['fields'] as $key => $title )
						if ( $meta = get_post_meta( $row->ID, $key, TRUE ) )
							$html.= '<div><b>'.$title.'</b>: '.$meta.'</div>';

					return $html ?: Helper::htmlEmpty();
				},
			],
			'related' => [
				'title'    => _x( 'Import Related', 'Table Column', 'geditorial-book' ),
				'args'     => [ 'type' => $this->constant( 'publication_cpt' ) ],
				'callback' => function( $value, $row, $column, $index ){

					$html = '';

					if ( $id = get_post_meta( $row->ID, 'book_publication_id', TRUE ) )
						$html.= '<div><b>'._x( 'By ID', 'Tools', 'geditorial-book' ).'</b>: '.Helper::getPostTitleRow( $id ).'</div>';

					if ( $title = get_post_meta( $row->ID, 'book_publication_title', TRUE ) )
						foreach ( (array) PostType::getIDsByTitle( $title, [ 'post_type' => $column['args']['type'] ] ) as $post_id )
							$html.= '<div><b>'._x( 'By Title', 'Tools', 'geditorial-book' ).'</b>: '.Helper::getPostTitleRow( $post_id ).'</div>';

					return $html ?: Helper::htmlEmpty();
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Meta Information about Related Publications', 'Header', 'geditorial-book' ) ),
			'empty'      => $this->get_posttype_label( 'publication_cpt', 'not_found' ),
			'pagination' => $pagination,
		] );
	}

	public function meta_sanitize_posttype_field( $sanitized, $field, $post, $data )
	{
		switch ( $field['name'] ) {
			case 'publication_isbn': return trim( ModuleHelper::sanitizeISBN( $data, TRUE ) );
		}

		return $sanitized;
	}

	public function meta_field( $meta, $field, $post, $args )
	{
		switch ( $field ) {
			case 'publication_isbn': return ModuleHelper::ISBN( $meta );
			case 'total_pages': return Number::format( $meta );
			case 'total_volumes': return Number::format( $meta );
			case 'collection': return HTML::link( $meta, WordPress::getSearchLink( $meta ) );
		}

		return $meta;
	}

	private function get_importer_fields( $posttype = NULL )
	{
		if ( $posttype == $this->constant( 'publication_cpt' ) )
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

	public function importer_prepare( $value, $posttype, $field, $raw )
	{
		$fields = array_keys( $this->get_importer_fields( $posttype ) );

		if ( ! in_array( $field, $fields ) )
			return $value;

		return Helper::kses( $value, 'none' );
	}

	public function importer_saved( $post, $data, $raw, $field_map, $attach_id )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		$fields = array_keys( $this->get_importer_fields( $post->post_type ) );

		foreach ( $field_map as $offset => $field ) {

			if ( ! in_array( $field, $fields ) )
				continue;

			if ( $value = trim( Helper::kses( $raw[$offset], 'none' ) ) )
				$this->store_postmeta( $post->ID, $value, $field );
		}
	}
}
