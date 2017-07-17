<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\Helpers\Book as ModuleHelper;
use geminorum\gEditorial\Templates\Book as ModuleTemplate;

class Book extends gEditorial\Module
{

	protected $partials = [ 'templates', 'helper', 'query' ];

	public static function module()
	{
		return [
			'name'  => 'book',
			'title' => _x( 'Book', 'Modules: Book', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Online House of Publications', 'Modules: Book', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'book-alt',
		];
	}

	public function settings_intro_after( $module )
	{
		if ( ! defined( 'P2P_PLUGIN_VERSION' ) )
			HTML::desc( sprintf( _x( 'Please consider installing <a href="%s" target="_blank">Posts to Posts</a> or <a href="%s" target="_blank">Objects to Objects</a>.', 'Modules: Book: Settings Intro', GEDITORIAL_TEXTDOMAIN ),
				'https://github.com/scribu/wp-posts-to-posts/', 'https://github.com/voceconnect/objects-to-objects' ) );
	}

	protected function get_global_settings()
	{
		$settings = [
			'_general' => [
				'comment_status',
				'insert_cover',
				'insert_priority',
			],
			'_supports' => [
				$this->settings_supports_option( 'publication_cpt', TRUE ),
			],
		];

		if ( defined( 'P2P_PLUGIN_VERSION' ) ) {

			$settings['posttypes_option'] = 'posttypes_option';

			$settings['_general'][] = 'insert_content';

			$settings['_general'][] = [
				'field' => 'p2p_title_from',
				'type'  => 'text',
				'title' => _x( 'Connected From Title', 'Modules: Book: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			];

			$settings['_general'][] = [
				'field' => 'p2p_title_to',
				'type'  => 'text',
				'title' => _x( 'Connected To Title', 'Modules: Book: Setting Title', GEDITORIAL_TEXTDOMAIN ),
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
			'publisher_tax'           => 'publication_publisher',
			'type_tax'                => 'publication_type',
			'status_tax'              => 'publication_status',
			'size_tax'                => 'publication_size',
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
			],
		];
	}

	protected function get_global_strings()
	{
		return [
			'misc' => [
				'publication_cpt' => [
					'featured'           => _x( 'Cover Image', 'Modules: Book: Publication CPT: Featured', GEDITORIAL_TEXTDOMAIN ),
					'meta_box_title'     => _x( 'Metadata', 'Modules: Book: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'author_box_title'   => _x( 'Curator', 'Modules: Book: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'excerpt_box_title'  => _x( 'Summary', 'Modules: Book: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'cover_column_title' => _x( 'Cover', 'Modules: Book: Column Title', GEDITORIAL_TEXTDOMAIN ),
				],
				'subject_tax' => [
					'meta_box_title'      => _x( 'Subject', 'Modules: Book: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Publication Subject', 'Modules: Book: Column Title', GEDITORIAL_TEXTDOMAIN ),
				],
				'library_tax' => [
					'meta_box_title'      => _x( 'Library', 'Modules: Book: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Publication Library', 'Modules: Book: Column Title', GEDITORIAL_TEXTDOMAIN ),
				],
				'publisher_tax' => [
					'meta_box_title'      => _x( 'Publisher', 'Modules: Book: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Publication Publisher', 'Modules: Book: Column Title', GEDITORIAL_TEXTDOMAIN ),
				],
				'status_tax' => [
					'meta_box_title'      => _x( 'Status', 'Modules: Book: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Publication Status', 'Modules: Book: Column Title', GEDITORIAL_TEXTDOMAIN ),
				],
				'type_tax' => [
					'meta_box_title'      => _x( 'Type', 'Modules: Book: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Publication Type', 'Modules: Book: Column Title', GEDITORIAL_TEXTDOMAIN ),
				],
				'size_tax' => [
					'meta_box_title'      => _x( 'Size', 'Modules: Book: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Publication Size', 'Modules: Book: Column Title', GEDITORIAL_TEXTDOMAIN ),
				],
			],
			'settings' => [
				'post_types_after' => Settings::infoP2P(),
			],
			'noops' => [
				'publication_cpt' => _nx_noop( 'Publication', 'Publications', 'Modules: Book: Noop', GEDITORIAL_TEXTDOMAIN ),
				'subject_tax'     => _nx_noop( 'Subject', 'Subjects', 'Modules: Book: Noop', GEDITORIAL_TEXTDOMAIN ),
				'library_tax'     => _nx_noop( 'Library', 'Libraries', 'Modules: Book: Noop', GEDITORIAL_TEXTDOMAIN ),
				'publisher_tax'   => _nx_noop( 'Publisher', 'Publishers', 'Modules: Book: Noop', GEDITORIAL_TEXTDOMAIN ),
				'type_tax'        => _nx_noop( 'Publication Type', 'Publication Types', 'Modules: Book: Noop', GEDITORIAL_TEXTDOMAIN ),
				'status_tax'      => _nx_noop( 'Publication Status', 'Publication Statuses', 'Modules: Book: Noop', GEDITORIAL_TEXTDOMAIN ),
				'size_tax'        => _nx_noop( 'Publication Size', 'Publication Sizes', 'Modules: Book: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
			'terms' => [
				'size_tax' => [
					'octavo' => _x( 'Octavo', 'Modules: Book: Publication Size: Default Term', GEDITORIAL_TEXTDOMAIN ), // vaziri
					'folio'  => _x( 'Folio', 'Modules: Book: Publication Size: Default Term', GEDITORIAL_TEXTDOMAIN ), // soltani
					'medium' => _x( 'Medium Octavo', 'Modules: Book: Publication Size: Default Term', GEDITORIAL_TEXTDOMAIN ), //roghee
					'quatro' => _x( 'Quatro', 'Modules: Book: Publication Size: Default Term', GEDITORIAL_TEXTDOMAIN ), //rahli
				],
			],
			'p2p' => [
				'publication_cpt' => [
					'title' => [
						'from' => _x( 'Connected Publications', 'Modules: Book: P2P', GEDITORIAL_TEXTDOMAIN ),
						'to'   => _x( 'Connected Posts', 'Modules: Book: P2P', GEDITORIAL_TEXTDOMAIN ),
					],
					'from_labels' => [
						'singular_name' => _x( 'Post', 'Modules: Book: P2P', GEDITORIAL_TEXTDOMAIN ),
						'search_items'  => _x( 'Search Posts', 'Modules: Book: P2P', GEDITORIAL_TEXTDOMAIN ),
						'not_found'     => _x( 'No posts found.', 'Modules: Book: P2P', GEDITORIAL_TEXTDOMAIN ),
						'create'        => _x( 'Connect to a post', 'Modules: Book: P2P', GEDITORIAL_TEXTDOMAIN ),
					],
					'to_labels' => [
						'singular_name' => _x( 'Publications', 'Modules: Book: P2P', GEDITORIAL_TEXTDOMAIN ),
						'search_items'  => _x( 'Search Publication', 'Modules: Book: P2P', GEDITORIAL_TEXTDOMAIN ),
						'not_found'     => _x( 'No publications found.', 'Modules: Book: P2P', GEDITORIAL_TEXTDOMAIN ),
						'create'        => _x( 'Connect to a publication', 'Modules: Book: P2P', GEDITORIAL_TEXTDOMAIN ),
					],
					'fields' => [
						'ref' => [
							'title' => _x( 'Reference', 'Modules: Book: P2P', GEDITORIAL_TEXTDOMAIN ),
							'type'  => 'text',
							'value' => '%s',
						],
						'desc' => [
							'title' => _x( 'Description', 'Modules: Book: P2P', GEDITORIAL_TEXTDOMAIN ),
							'type'  => 'text',
							'value' => '%s',
						],
					],
					'admin_column' => FALSE, // adding through tweaks module
				],
			],
		];
	}

	public function get_global_fields()
	{
		return [
			$this->constant( 'publication_cpt' ) => [
				'collection' => [
					'title'       => _x( 'Collection Title', 'Modules: Book: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'This Publication Is Part of a Collection', 'Modules: Book: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'title_before',
				],
				'sub_title' => [
					'title'       => _x( 'Subtitle', 'Modules: Book: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Subtitle of the Publication', 'Modules: Book: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'title_after',
				],
				'alt_title' => [
					'title'       => _x( 'Alternative Title', 'Modules: Book: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'The Original Title or Title in Another Language', 'Modules: Book: Field Description', GEDITORIAL_TEXTDOMAIN ),
				],
				'edition' => [
					'title'       => _x( 'Edition', 'Modules: Book: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Edition of the Publication', 'Modules: Book: Field Description', GEDITORIAL_TEXTDOMAIN ),
				],
				'print' => [
					'title'       => _x( 'Print', 'Modules: Book: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Specefic Print of the Publication', 'Modules: Book: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'icon'        => 'book',
				],
				'publish_location' => [
					'title'       => _x( 'Publish Location', 'Modules: Book: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Location Published', 'Modules: Book: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'icon'        => 'location-alt',
				],
				'publication_date' => [
					'title'       => _x( 'Publication Date', 'Modules: Book: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Date Published', 'Modules: Book: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'icon'        => 'calendar-alt',
				],
				'isbn' => [
					'title'       => _x( 'ISBN', 'Modules: Book: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'International Standard Book Number', 'Modules: Book: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'code',
					'icon'        => 'menu',
				],
				'pages' => [
					'title'       => _x( 'Pages', 'Modules: Book: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Total Pages of the Publication', 'Modules: Book: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'number',
					'icon'        => 'admin-page',
				],
				'volumes' => [
					'title'       => _x( 'Volumes', 'Modules: Book: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Total Volumes of the Publication', 'Modules: Book: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'number',
					'icon'        => 'book-alt',
				],
				'size' => [
					'title'       => _x( 'Size', 'Modules: Book: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'The Size of the Publication, Mainly Books', 'Modules: Book: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'term',
					'tax'         => $this->constant( 'size_tax' ),
				],
			],
		];
	}

	public function before_settings( $page = NULL )
	{
		if ( isset( $_POST['install_def_size_tax'] ) )
			$this->insert_default_terms( 'size_tax' );
	}

	public function default_buttons( $page = NULL )
	{
		parent::default_buttons( $page );
		$this->register_button( 'install_def_size_tax', _x( 'Install Default Sizes', 'Modules: Book: Setting Button', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'publication_cpt' );
	}

	public function p2p_init()
	{
		$this->p2p_register( 'publication_cpt' );

		if ( is_admin() )
			return;

		$setting = $this->get_setting( 'insert_content', 'none' );

		if ( 'before' == $setting )
			add_action( 'gnetwork_themes_content_before', [ $this, 'insert_content' ], 100 );
		else if ( 'after' == $setting )
			add_action( 'gnetwork_themes_content_after', [ $this, 'insert_content' ], 100 );
	}

	public function widgets_init()
	{
		$this->require_code( 'widgets' );

		register_widget( '\\geminorum\\gEditorial\\Widgets\\Book\\PublicationCover' );
	}

	public function init()
	{
		parent::init();

		$this->post_types_excluded = [ 'attachment', $this->constant( 'publication_cpt' ) ];

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
			'hierarchical' => TRUE,
		], 'publication_cpt' );

		$this->register_taxonomy( 'status_tax', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'publication_cpt' );

		$this->register_post_type( 'publication_cpt' );

		$this->register_shortcode( 'cover_shortcode' );

		if ( is_admin() ) {

			add_filter( 'geditorial_importer_fields', [ $this, 'importer_fields' ], 10, 2 );
			add_filter( 'geditorial_importer_prepare', [ $this, 'importer_prepare' ], 10, 4 );
			add_action( 'geditorial_importer_saved', [ $this, 'importer_saved' ], 10, 5 );

		} else {

			if ( $this->get_setting( 'insert_cover' ) )
				add_action( 'gnetwork_themes_content_before',
					[ $this, 'content_before' ],
					$this->get_setting( 'insert_priority', -50 )
				);
		}
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'publication_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'post_updated_messages' );
				$this->filter( 'get_default_comment_status', 3, 10 );

			} else if ( 'edit' == $screen->base ) {

				add_filter( 'disable_months_dropdown', '__return_true', 12 );

				$this->filter( 'bulk_post_updated_messages', 2 );
				$this->action( 'restrict_manage_posts', 2, 12 );
				$this->action( 'parse_query' );

				if ( $this->p2p )
					add_action( 'geditorial_tweaks_column_row', [ $this, 'column_row_p2p_to' ], -25 );

				add_action( 'geditorial_meta_column_row', [ $this, 'column_row_meta' ], 12, 3 );

				$this->_tweaks_taxonomy();
			}

		} else if ( $this->p2p && 'edit' == $screen->base
			&& in_array( $screen->post_type, $this->post_types() ) ) {

			add_action( 'geditorial_tweaks_column_row', [ $this, 'column_row_p2p_from' ], -25 );
		}
	}

	public function add_meta_box_cb_publication_cpt( $post )
	{
		$post_type = $this->constant( 'publication_cpt' );

		$this->add_meta_box_author( 'publication_cpt' );
		$this->add_meta_box_excerpt( 'publication_cpt' );

		$this->add_meta_box_checklist_terms( 'status_tax', $post_type );
		$this->add_meta_box_checklist_terms( 'type_tax', $post_type );
	}

	public function column_row_p2p_to( $post )
	{
		$this->column_row_p2p_to_posttype( 'publication_cpt', $post );
	}

	public function column_row_p2p_from( $post )
	{
		$this->column_row_p2p_from_posttype( 'publication_cpt', $post );
	}

	public function column_row_meta( $post, $fields, $meta )
	{
		foreach ( $fields as $field => $args ) {

			if ( empty( $meta[$field] ) )
				continue;

			echo '<li class="-row -book -field-'.$field.'">';
				echo $this->get_column_icon( FALSE, $args['icon'], $args['title'] );
				echo $this->display_meta( $meta[$field], $field, $args );
			echo '</li>';
		}
	}

	public function display_meta( $value, $key = NULL, $field = [] )
	{
		switch ( $key ) {
			case 'isbn': return sprintf( _x( 'ISBN: %s', 'Modules: Book: Display', GEDITORIAL_TEXTDOMAIN ), ModuleHelper::ISBN( $value ) );
			case 'edition': return sprintf( _x( '%s Edition', 'Modules: Book: Display', GEDITORIAL_TEXTDOMAIN ), $value );
			case 'print': return sprintf( _x( '%s Print', 'Modules: Book: Display', GEDITORIAL_TEXTDOMAIN ), $value );
			case 'pages': return Helper::getCounted( $value, _x( '%s Pages', 'Modules: Book: Display', GEDITORIAL_TEXTDOMAIN ) );
			case 'volumes': return Helper::getCounted( $value, _x( '%s Volumes', 'Modules: Book: Display', GEDITORIAL_TEXTDOMAIN ) );
		}

		return esc_html( $value );
	}

	public function meta_init()
	{
		$this->register_taxonomy( 'size_tax', [
			'meta_box_cb' => FALSE,
		], 'publication_cpt' );

		$this->add_post_type_fields( $this->constant( 'publication_cpt' ) );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'publication_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function restrict_manage_posts( $post_type, $which )
	{
		$this->do_restrict_manage_posts_taxes( [
			'type_tax',
			'subject_tax',
			'library_tax',
			'status_tax',
			'publisher_tax',
		] );
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query, [
			'type_tax',
			'subject_tax',
			'library_tax',
			'status_tax',
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

	public function cover_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = [
			'size' => $this->get_image_size_key( 'publication_cpt', 'medium' ),
			'type' => $this->constant( 'publication_cpt' ),
			'echo' => FALSE,
		];

		if ( is_singular( $args['type'] ) )
			$args['id'] = NULL;

		else if ( is_singular( $this->post_types() ) )
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

	public function insert_content( $content, $posttypes = NULL )
	{
		if ( ! $this->is_content_insert( $this->post_types( 'publication_cpt' ) ) )
			return;

		$this->list_p2p( NULL, '-'.$this->get_setting( 'insert_content', 'none' ) );
	}

	public function get_assoc_post( $post = NULL, $single = FALSE, $published = TRUE )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		if ( ! in_array( $post->post_type, $this->post_types() ) )
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

	public function list_p2p( $post = NULL, $class = '' )
	{
		if ( is_null( $post ) )
			$post = get_post();

		$connected = new \WP_Query( [
			'connected_type'  => $this->constant( 'publication_cpt_p2p' ),
			'connected_items' => $post,
		] );

		if ( $connected->have_posts() ) {

			echo '<div class="geditorial-wrap -book -p2p '.$class.'">';

			if ( $post->post_type == $this->constant( 'publication_cpt' ) ) {

				if ( $title = $this->get_setting( 'p2p_title_from' ) )
					HTML::h3( $title, '-title -p2p-from' );

			} else if ( $title = $this->get_setting( 'p2p_title_to' ) ) {
				HTML::h3( $title, '-title -p2p-to' );
			}

			echo '<ul>';

			while ( $connected->have_posts() ) {
				$connected->the_post();

				$meta = '';
				$meta .= $this->p2p_get_meta( $post->p2p_id, 'ref', ' &ndash; ' );
				$meta .= $this->p2p_get_meta( $post->p2p_id, 'desc', ' &ndash; ' );

				echo ShortCode::postItem( [ 'item_after' => $meta ] );
			}

			echo '</ul></div>';
			wp_reset_postdata();
		}
	}

	public function content_before( $content, $posttypes = NULL )
	{
		if ( ! $this->is_content_insert( $this->post_types( 'publication_cpt' ) ) )
			return;

		ModuleTemplate::postImage( [
			'size' => $this->get_image_size_key( 'publication_cpt', 'medium' ),
			'link' => 'attachment',
		] );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {}
	}

	public function tools_sub( $uri, $sub )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', 'tools', FALSE, FALSE );

			$this->tableSummary();

		$this->settings_form_after( $uri, $sub );
	}

	// @REF: meta query: http://wordpress.stackexchange.com/a/246358/3687
	private function tableSummary()
	{
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

		list( $posts, $pagination ) = $this->getTablePosts( $query );

		$pagination['before'][] = Helper::tableFilterPostTypes( $this->list_post_types() );

		return HTML::tableList( [
			'_cb'   => 'ID',
			'ID'    => Helper::tableColumnPostID(),
			'date'  => Helper::tableColumnPostDate(),
			'type'  => Helper::tableColumnPostType(),
			'title' => Helper::tableColumnPostTitle(),
			'metas' => [
				'title'    => _x( 'Import Meta', 'Modules: Book: Table Column', GEDITORIAL_TEXTDOMAIN ),
				'args'     => [ 'fields' => $this->get_importer_fields() ],
				'callback' => function( $value, $row, $column, $index ){
					$html = '';

					foreach( $column['args']['fields'] as $key => $title )
						if ( $meta = get_post_meta( $row->ID, $key, TRUE ) )
							$html .= '<div><b>'.$title.'</b>: '.$meta.'</div>';

					return $html ? $html : '&mdash;';
				},
			],
			'related' => [
				'title'    => _x( 'Import Related', 'Modules: Book: Table Column', GEDITORIAL_TEXTDOMAIN ),
				'args'     => [ 'type' => $this->constant( 'publication_cpt' ) ],
				'callback' => function( $value, $row, $column, $index ){

					$html = '';

					if ( $id = get_post_meta( $row->ID, 'book_publication_id', TRUE ) )
						$html .= '<div><b>'._x( 'By ID', 'Modules: Book', GEDITORIAL_TEXTDOMAIN ).'</b>: '.Helper::getPostTitleRow( $id ).'</div>';

					if ( $title = get_post_meta( $row->ID, 'book_publication_title', TRUE ) )
						foreach ( (array) PostType::getIDsByTitle( $title, [ 'post_type' => $column['args']['type'] ] ) as $post_id )
							$html .= '<div><b>'._x( 'By Title', 'Modules: Book', GEDITORIAL_TEXTDOMAIN ).'</b>: '.Helper::getPostTitleRow( $post_id ).'</div>';

					return $html ? $html : '&mdash;';
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Meta Information about Related Publications', 'Modules: Book', GEDITORIAL_TEXTDOMAIN ) ),
			'empty'      => Helper::tableArgEmptyPosts(),
			'pagination' => $pagination,
		] );
	}

	public function set_meta( $post_id, $postmeta, $key_suffix = '' )
	{
		if ( $postmeta )
			update_post_meta( $post_id, $key_suffix, $postmeta );
		else
			delete_post_meta( $post_id, $key_suffix );
	}

	private function get_importer_fields( $post_type = NULL )
	{
		return [
			'book_publication_id'    => _x( 'Book: Publication ID', 'Modules: Book: Import Field', GEDITORIAL_TEXTDOMAIN ),
			'book_publication_title' => _x( 'Book: Publication Title', 'Modules: Book: Import Field', GEDITORIAL_TEXTDOMAIN ),
			'book_publication_ref'   => _x( 'Book: Publication Ref (P2P)', 'Modules: Book: Import Field', GEDITORIAL_TEXTDOMAIN ),
			'book_publication_desc'  => _x( 'Book: Publication Desc (P2P)', 'Modules: Book: Import Field', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public function importer_fields( $fields, $post_type )
	{
		if ( ! in_array( $post_type, $this->post_types() ) )
			return $fields;

		return array_merge( $fields, $this->get_importer_fields( $post_type ) );
	}

	public function importer_prepare( $value, $post_type, $field, $raw )
	{
		if ( ! in_array( $post_type, $this->post_types() ) )
			return $value;

		if ( ! in_array( $field, array_keys( $this->get_importer_fields( $post_type ) ) ) )
			return $value;

		return Helper::kses( $value, 'none' );
	}

	public function importer_saved( $post, $data, $raw, $field_map, $attach_id )
	{
		if ( ! in_array( $post->post_type, $this->post_types() ) )
			return;

		$fields = array_keys( $this->get_importer_fields( $post->post_type ) );

		foreach ( $field_map as $offset => $field ) {

			if ( ! in_array( $field, $fields ) )
				continue;

			if ( $value = trim( Helper::kses( $raw[$offset], 'none' ) ) )
				$this->set_meta( $post->ID, $value, $field );
		}
	}
}
