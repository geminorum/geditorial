<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialBook extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'  => 'book',
			'title' => _x( 'Book', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Online House of Publications', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'book-alt',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'comment_status',
				'insert_content',
			),
			'posttypes_option' => 'posttypes_option',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'publication_cpt'         => 'publication',
			'publication_cpt_archive' => 'publications',
			'publication_cpt_p2p'     => 'publications_to_posts',
			'subject_tax'             => 'publication_subject',
			'library_tax'             => 'publication_library',
			'publisher_tax'           => 'publication_publisher',
			'type_tax'                => 'publication_type',
			'status_tax'              => 'publication_status',
			'size_tax'                => 'publication_size',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'publication_cpt' => array(
					'featured'           => _x( 'Cover Image', 'Book Module: Publication CPT: Featured', GEDITORIAL_TEXTDOMAIN ),
					'meta_box_title'     => _x( 'Metadata', 'Book Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'cover_column_title' => _x( 'Cover', 'Book Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'subject_tax' => array(
					'meta_box_title'      => _x( 'Subject', 'Book Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Publication Subject', 'Book Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'library_tax' => array(
					'meta_box_title'      => _x( 'Library', 'Book Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Publication Library', 'Book Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'publisher_tax' => array(
					'meta_box_title'      => _x( 'Publisher', 'Book Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Publication Publisher', 'Book Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'status_tax' => array(
					'meta_box_title'      => _x( 'Status', 'Book Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Publication Status', 'Book Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'type_tax' => array(
					'meta_box_title'      => _x( 'Type', 'Book Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Publication Type', 'Book Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'size_tax' => array(
					'meta_box_title'      => _x( 'Size', 'Book Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Publication Size', 'Book Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'author' => array(
					'meta_box_title' => _x( 'Curator', 'Book Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'excerpt' => array(
					'meta_box_title' => _x( 'Summary', 'Book Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'settings' => array(
				'post_types_after'     => gEditorialSettingsCore::infoP2P(),
				'install_def_size_tax' => _x( 'Install Default Sizes', 'Book Module: Setting Button', GEDITORIAL_TEXTDOMAIN ),
			),
			'noops' => array(
				'publication_cpt' => _nx_noop( 'Publication',        'Publications',         'Book Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'subject_tax'     => _nx_noop( 'Subject',            'Subjects',             'Book Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'library_tax'     => _nx_noop( 'Library',            'Libraries',            'Book Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'publisher_tax'   => _nx_noop( 'Publisher',          'Publishers',           'Book Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'type_tax'        => _nx_noop( 'Publication Type',   'Publication Types',    'Book Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'status_tax'      => _nx_noop( 'Publication Status', 'Publication Statuses', 'Book Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'size_tax'        => _nx_noop( 'Publication Size',   'Publication Sizes',    'Book Module: Noop', GEDITORIAL_TEXTDOMAIN ),
			),
			'terms' => array(
				'size_tax' => array(
					'octavo' => _x( 'Octavo', 'Book Module: Publication Size: Default Term', GEDITORIAL_TEXTDOMAIN ), // vaziri
					'folio'  => _x( 'Folio', 'Book Module: Publication Size: Default Term', GEDITORIAL_TEXTDOMAIN ), // soltani
					'medium' => _x( 'Medium Octavo', 'Book Module: Publication Size: Default Term', GEDITORIAL_TEXTDOMAIN ), //roghee
					'quatro' => _x( 'Quatro', 'Book Module: Publication Size: Default Term', GEDITORIAL_TEXTDOMAIN ), //rahli
				),
			),
			'p2p' => array(
				'publication_cpt' => array(
					'title' => array(
						'from' => _x( 'Connected Publications', 'Book Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'to'   => _x( 'Connected Posts', 'Book Module: P2P', GEDITORIAL_TEXTDOMAIN )
					),
					'from_labels' => array(
						'singular_name' => _x( 'Post', 'Book Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'search_items'  => _x( 'Search Posts', 'Book Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'not_found'     => _x( 'No posts found.', 'Book Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'create'        => _x( 'Connect to a post', 'Book Module: P2P', GEDITORIAL_TEXTDOMAIN ),
					),
					'to_labels' => array(
						'singular_name' => _x( 'Publications', 'Book Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'search_items'  => _x( 'Search Publication', 'Book Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'not_found'     => _x( 'No publications found.', 'Book Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'create'        => _x( 'Connect to an publication', 'Book Module: P2P', GEDITORIAL_TEXTDOMAIN ),
					),
					'fields' => array(
						'ref' => array(
							'title' => _x( 'Reference', 'Book Module: P2P', GEDITORIAL_TEXTDOMAIN ),
							'type' => 'text',
							'value' => '%s',
						),
					),
				),
			),
		);
	}

	protected function get_global_supports()
	{
		return array(
			'publication_cpt' => array(
				'title',
				'editor',
				'excerpt',
				'author',
				'thumbnail',
				// 'trackbacks',
				// 'custom-fields',
				'comments',
				'revisions',
				// 'page-attributes',
			),
		);
	}

	public function get_global_fields()
	{
		return array(
			$this->constant( 'publication_cpt' ) => array (
				'collection' => array(
					'title'       => _x( 'Collection Title', 'Book Module: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'This Publication Is Part of a Collection', 'Book Module: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'title_before',
				),
				'sub_title' => array(
					'title'       => _x( 'Subtitle', 'Book Module: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Subtitle of the Publication', 'Book Module: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'title_after',
				),
				'alt_title' => array(
					'title'       => _x( 'Alternative Title', 'Book Module: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'The Original Title or Title in Another Language', 'Book Module: Field Description', GEDITORIAL_TEXTDOMAIN ),
				),
				'isbn' => array(
					'title'       => _x( 'ISBN', 'Book Module: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'International Standard Book Number', 'Book Module: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'code',
				),
				'size' => array(
					'title'       => _x( 'Size', 'Book Module: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'The Size of the Publication, Mainly Books', 'Book Module: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'term',
					'tax'         => $this->constant( 'size_tax' ),
				),
				'publication_date' => array(
					'title'       => _x( 'Publication Date', 'Book Module: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Date Published', 'Book Module: Field Description', GEDITORIAL_TEXTDOMAIN ),
				),
				'edition' => array(
					'title'       => _x( 'Edition', 'Book Module: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Edition of the Publication', 'Book Module: Field Description', GEDITORIAL_TEXTDOMAIN ),
				),
				'print' => array(
					'title'       => _x( 'Print', 'Book Module: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Specefic Print of the Publication', 'Book Module: Field Description', GEDITORIAL_TEXTDOMAIN ),
				),
				'pages' => array(
					'title'       => _x( 'Pages', 'Book Module: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Total Pages of the Publication', 'Book Module: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'number',
				),
			),
		);
	}

	public function setup( $partials = array() )
	{
		parent::setup( array(
			'templates',
			'helper',
			'query',
		) );
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'publication_cpt' );
	}

	public function p2p_init()
	{
		$this->register_p2p( 'publication_cpt' );

		if ( is_admin() )
			return;

		$setting = $this->get_setting( 'insert_content', 'none' );

		if ( 'before' == $setting )
			add_action( 'gnetwork_themes_content_before', array( $this, 'insert_content' ), 100 );
		else if ( 'after' == $setting )
			add_action( 'gnetwork_themes_content_after', array( $this, 'insert_content' ), 100 );
	}

	public function init()
	{
		do_action( 'geditorial_book_init', $this->module );

		$this->do_globals();

		$this->post_types_excluded = array( $this->constant( 'publication_cpt' ) );

		$this->register_post_type( 'publication_cpt', array(), array( 'post_tag' ) );

		$this->register_taxonomy( 'subject_tax', array(
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL,
		), 'publication_cpt' );

		$this->register_taxonomy( 'library_tax', array(
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL,
		), 'publication_cpt' );

		$this->register_taxonomy( 'publisher_tax', array(
			'meta_box_cb' => NULL,
		), 'publication_cpt' );

		$this->register_taxonomy( 'type_tax', array(
			'hierarchical' => TRUE,
			'meta_box_cb'  => FALSE,
		), 'publication_cpt' );

		$this->register_taxonomy( 'status_tax', array(
			'hierarchical' => TRUE,
			'meta_box_cb'  => FALSE,
		), 'publication_cpt' );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'publication_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
				add_filter( 'get_default_comment_status', array( $this, 'get_default_comment_status' ), 10, 3 );

				add_filter( 'geditorial_meta_box_callback', '__return_true', 12 );
				add_filter( 'geditorial_meta_dbx_callback', '__return_true', 12 );

				$this->add_meta_box_choose_tax( 'status_tax', $screen->post_type, 'cat', FALSE );
				$this->add_meta_box_choose_tax( 'type_tax', $screen->post_type, 'cat', FALSE );

				$post_type_object = get_post_type_object( $screen->post_type );
				if ( is_super_admin() || current_user_can( $post_type_object->cap->edit_others_posts ) ) {
					$this->remove_meta_box( 'publication_cpt', $screen->post_type, 'author' );
					add_meta_box( 'authordiv',
						$this->get_string( 'meta_box_title', 'author', 'misc' ),
						'post_author_meta_box',
						$screen->post_type,
						'side'
					);
				}

				$this->remove_meta_box( 'publication_cpt', $screen->post_type, 'excerpt' );
				add_meta_box( 'postexcerpt',
					$this->get_string( 'meta_box_title', 'excerpt', 'misc' ),
					'post_excerpt_meta_box',
					$screen->post_type,
					'normal',
					'high'
				);

			} else if ( 'edit' == $screen->base ) {

				add_filter( 'disable_months_dropdown', '__return_true', 12 );
				add_filter( 'manage_'.$screen->post_type.'_posts_columns', array( $this, 'manage_posts_columns' ) );
				add_action( 'manage_'.$screen->post_type.'_posts_custom_column', array( $this, 'posts_custom_column' ), 10, 2 );
				add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
				add_action( 'parse_query', array( $this, 'parse_query' ) );
			}
		}
	}

	public function register_settings( $page = NULL )
	{
		if ( ! $this->is_register_settings( $page ) )
			return;

		if ( isset( $_POST['install_def_size_tax'] ) )
			$this->insert_default_terms( 'size_tax' );

		parent::register_settings( $page );
		$this->register_settings_button( 'install_def_size_tax' );
	}

	public function gpeople_support( $post_types )
	{
		return array_merge( $post_types, array( $this->constant( 'publication_cpt' ) ) );
	}

	public function meta_init( $meta_module )
	{
		$this->register_taxonomy( 'size_tax', array(
			'meta_box_cb' => FALSE,
		), 'publication_cpt' );

		$this->add_post_type_fields( $this->constant( 'publication_cpt' ) );
	}

	public function tweaks_strings( $strings )
	{
		$this->tweaks = TRUE;

		$new = array(
			'taxonomies' => array(
				$this->constant( 'subject_tax' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'subject_tax' ),
					'icon'   => 'tag',
					'title'  => $this->get_column_title( 'tweaks', 'subject_tax' ),
				),
				$this->constant( 'library_tax' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'library_tax' ),
					'icon'   => 'book-alt',
					'title'  => $this->get_column_title( 'tweaks', 'library_tax' ),
				),
				$this->constant( 'publisher_tax' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'publisher_tax' ),
					'icon'   => 'book',
					'title'  => $this->get_column_title( 'tweaks', 'publisher_tax' ),
				),
				$this->constant( 'type_tax' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'type_tax' ),
					'icon'   => 'admin-media',
					'title'  => $this->get_column_title( 'tweaks', 'type_tax' ),
				),
				$this->constant( 'status_tax' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'status_tax' ),
					'icon'   => 'post-status',
					'title'  => $this->get_column_title( 'tweaks', 'status_tax' ),
				),
				$this->constant( 'size_tax' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'size_tax' ),
					'icon'   => 'image-crop',
					'title'  => $this->get_column_title( 'tweaks', 'size_tax' ),
				),
			),
		);

		return self::recursiveParseArgs( $new, $strings );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'publication_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function restrict_manage_posts()
	{
		$this->do_restrict_manage_posts_taxes( array(
			'type_tax',
			'subject_tax',
			'library_tax',
			'status_tax',
			'publisher_tax',
		), 'publication_cpt' );
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query->query_vars, array(
			'type_tax',
			'subject_tax',
			'library_tax',
			'status_tax',
			'publisher_tax',
		), 'publication_cpt' );
	}

	public function manage_posts_columns( $posts_columns )
	{
		$new_columns = array();

		foreach ( $posts_columns as $key => $value ) {

			if ( 'title' == $key ) {
				$new_columns['cover'] = $this->get_column_title( 'cover', 'publication_cpt' );
				$new_columns[$key]    = $value;

			} else if ( in_array( $key, array( 'author', 'date', 'comments' ) ) ) {
				continue; // he he!

			} else {
				$new_columns[$key] = $value;
			}
		}

		return $new_columns;
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( 'cover' == $column_name )
			$this->column_thumb( $post_id, $this->get_image_size_key( 'publication_cpt' ) );
	}

	public function post_updated_messages( $messages )
	{
		$messages[$this->constant( 'publication_cpt' )] = $this->get_post_updated_messages( 'publication_cpt' );
		return $messages;
	}

	public function insert_content( $content, $posttypes = NULL )
	{
		if ( ! is_singular( $this->post_types( 'publication_cpt' ) ) )
			return;

		$connected = new WP_Query( array(
			'connected_type'  => $this->constant( 'publication_cpt_p2p' ),
			'connected_items' => get_post(),
		) );

		if ( $connected->have_posts() ) {
			echo '<div class="geditorial-wrap -book -p2p -'.$this->get_setting( 'insert_content', 'none' ).'"><ul>';

			while ( $connected->have_posts() ) {
				$connected->the_post();
				echo '<li>';

					echo gEditorialHTML::tag( 'a', array(
						'href' => get_permalink(),
					), get_the_title() );

					if ( $ref = p2p_get_meta( get_post()->p2p_id, 'ref', TRUE ) )
						echo ' &ndash; '.$ref;

				echo '</li>';
			}

			echo '</ul></div>';
			wp_reset_postdata();
		}
	}
}
