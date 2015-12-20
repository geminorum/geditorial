<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialBook extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'      => 'book',
			'title'     => _x( 'Book', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'      => _x( 'Online House of Publications', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'  => 'book-alt',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'publication_cpt'         => 'publication',
			'publication_cpt_archive' => 'publications',
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
			'titles' => array(
				'publication_cpt' => array(
					'ot'        => _x( 'Collection Title', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'st'        => _x( 'Second Title', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'alt_title' => _x( 'Alternative Title', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'isbn'      => _x( 'ISBN', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'size'      => _x( 'Size', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'year'      => _x( 'Year', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'edition'   => _x( 'Edition', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'print'     => _x( 'Print', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'pages'     => _x( 'Pages', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'descriptions' => array(
				'publication_cpt' => array(
					'ot'        => _x( 'The publication is part of a collection', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'st'        => _x( 'Second title of the publication', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'alt_title' => _x( 'The original title or title in another language', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'isbn'      => _x( 'International Standard Book Number', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'size'      => _x( 'The size of the publication, mainly books', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'year'      => _x( 'Year of publish', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'edition'   => _x( 'Edition of the publication', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'print'     => _x( 'Specefic print of the publication', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'pages'     => _x( 'Total pages of the publication', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'misc' => array(
				'publication_cpt' => array(
					'meta_box_title'     => _x( 'Metadata', 'Book Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'cover_column_title' => _x( 'Cover', 'Book Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'status_tax' => array(
					'meta_box_title' => _x( 'Status', 'Book Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'type_tax' => array(
					'meta_box_title' => _x( 'Type', 'Book Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'labels' => array(
				'publication_cpt' => array(
                    'name'                  => _x( 'Publications', 'Book Module: Publication CPT: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'             => _x( 'Publications', 'Book Module: Publication CPT: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'         => _x( 'Publication', 'Book Module: Publication CPT: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'description'           => _x( 'Publication List', 'Book Module: Publication CPT: Description', GEDITORIAL_TEXTDOMAIN ),
                    'add_new'               => _x( 'Add New', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'          => _x( 'Add New Publication', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'             => _x( 'Edit Publication', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'new_item'              => _x( 'New Publication', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'             => _x( 'View Publication', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'          => _x( 'Search Publications', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'             => _x( 'No publications found.', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'not_found_in_trash'    => _x( 'No publications found in Trash.', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'             => _x( 'All Publications', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'archives'              => _x( 'Publication Archives', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'insert_into_item'      => _x( 'Insert into publication', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'uploaded_to_this_item' => _x( 'Uploaded to this publication', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'featured_image'        => _x( 'Cover Image', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'set_featured_image'    => _x( 'Set cover image', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'remove_featured_image' => _x( 'Remove cover image', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'use_featured_image'    => _x( 'Use as cover image', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'filter_items_list'     => _x( 'Filter publications list', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation' => _x( 'Publications list navigation', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'            => _x( 'Publications list', 'Book Module: Publication CPT', GEDITORIAL_TEXTDOMAIN ),
				),
				'subject_tax' => array(
                    'name'                  => _x( 'Subjects', 'Book Module: Subject Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'             => _x( 'Subjects', 'Book Module: Subject Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'         => _x( 'Subject', 'Book Module: Subject Tax Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'          => _x( 'Search Subjects', 'Book Module: Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'             => _x( 'All Subjects', 'Book Module: Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item'           => _x( 'Parent Subject', 'Book Module: Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item_colon'     => _x( 'Parent Subject:', 'Book Module: Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'             => _x( 'Edit Subject', 'Book Module: Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'             => _x( 'View Subject', 'Book Module: Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'update_item'           => _x( 'Update Subject', 'Book Module: Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'          => _x( 'Add New Subject', 'Book Module: Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'new_item_name'         => _x( 'New Subject Name', 'Book Module: Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'             => _x( 'No subjects found.', 'Book Module: Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'no_terms'              => _x( 'No subjects', 'Book Module: Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation' => _x( 'Subjects list navigation', 'Book Module: Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'            => _x( 'Subjects list', 'Book Module: Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
				),
				'library_tax' => array(
                    'name'                  => _x( 'Libraries', 'Book Module: Library Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'             => _x( 'Libraries', 'Book Module: Library Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'         => _x( 'Library', 'Book Module: Library Tax Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'          => _x( 'Search Libraries', 'Book Module: Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'             => _x( 'All Libraries', 'Book Module: Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item'           => _x( 'Parent Library', 'Book Module: Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item_colon'     => _x( 'Parent Library:', 'Book Module: Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'             => _x( 'Edit Library', 'Book Module: Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'             => _x( 'View Library', 'Book Module: Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'update_item'           => _x( 'Update Library', 'Book Module: Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'          => _x( 'Add New Library', 'Book Module: Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'new_item_name'         => _x( 'New Library Name', 'Book Module: Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'             => _x( 'No libraries found.', 'Book Module: Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'no_terms'              => _x( 'No libraries', 'Book Module: Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation' => _x( 'Libraries list navigation', 'Book Module: Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'            => _x( 'Libraries list', 'Book Module: Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
				),
				'publisher_tax' => array(
                    'name'                       => _x( 'Publishers', 'Book Module: Publisher Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'                  => _x( 'Publishers', 'Book Module: Publisher Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'              => _x( 'Publisher', 'Book Module: Publisher Tax Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'               => _x( 'Search Publishers', 'Book Module: Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'popular_items'              => NULL, // _x( 'Popular Publishers', 'Book Module: Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'                  => _x( 'All Publishers', 'Book Module: Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'                  => _x( 'Edit Publisher', 'Book Module: Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'                  => _x( 'View Publisher', 'Book Module: Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'update_item'                => _x( 'Update Publisher', 'Book Module: Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'               => _x( 'Add New Publisher', 'Book Module: Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'new_item_name'              => _x( 'New Publisher Name', 'Book Module: Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'separate_items_with_commas' => _x( 'Separate publishers with commas', 'Book Module: Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_or_remove_items'        => _x( 'Add or remove publishers', 'Book Module: Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'choose_from_most_used'      => _x( 'Choose from the most used publishers', 'Book Module: Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'                  => _x( 'No publishers found.', 'Book Module: Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'no_terms'                   => _x( 'No publishers', 'Book Module: Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation'      => _x( 'Publishers list navigation', 'Book Module: Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'                 => _x( 'Publishers list', 'Book Module: Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
				),
				'type_tax' => array(
                    'name'                  => _x( 'Publication Types', 'Book Module: Publication Type Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'             => _x( 'Publication Types', 'Book Module: Publication Type Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'         => _x( 'Publication Type', 'Book Module: Publication Type Tax Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'          => _x( 'Search Publication Types', 'Book Module: Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'             => _x( 'All Publication Types', 'Book Module: Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item'           => _x( 'Parent Publication Type', 'Book Module: Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item_colon'     => _x( 'Parent Publication Type:', 'Book Module: Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'             => _x( 'Edit Publication Type', 'Book Module: Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'             => _x( 'View Publication Type', 'Book Module: Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'update_item'           => _x( 'Update Publication Type', 'Book Module: Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'          => _x( 'Add New Publication Type', 'Book Module: Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'new_item_name'         => _x( 'New Publication Type Name', 'Book Module: Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'             => _x( 'No publication types found.', 'Book Module: Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'no_terms'              => _x( 'No publication types', 'Book Module: Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation' => _x( 'Publication Types list navigation', 'Book Module: Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'            => _x( 'Publication Types list', 'Book Module: Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
				),
				'status_tax' => array(
                    'name'                  => _x( 'Publication Statuses', 'Book Module: Publication Status Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'             => _x( 'Publication Statuses', 'Book Module: Publication Status Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'         => _x( 'Publication Status', 'Book Module: Publication Status Tax Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'          => _x( 'Search Publication Statuses', 'Book Module: Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'             => _x( 'All Publication Statuses', 'Book Module: Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item'           => _x( 'Parent Publication Status', 'Book Module: Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item_colon'     => _x( 'Parent Publication Status:', 'Book Module: Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'             => _x( 'Edit Publication Status', 'Book Module: Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'             => _x( 'View Publication Status', 'Book Module: Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'update_item'           => _x( 'Update Publication Status', 'Book Module: Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'          => _x( 'Add New Publication Status', 'Book Module: Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'new_item_name'         => _x( 'New Publication Status Name', 'Book Module: Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'             => _x( 'No publication statuses found.', 'Book Module: Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'no_terms'              => _x( 'No publication statuses', 'Book Module: Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation' => _x( 'Publication Statuses list navigation', 'Book Module: Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'            => _x( 'Publication Statuses list', 'Book Module: Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
				),
				'size_tax' => array(
                    'name'                       => _x( 'Publication Sizes', 'Book Module: Publication Size Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'                  => _x( 'Publication Sizes', 'Book Module: Publication Size Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'              => _x( 'Publication Size', 'Book Module: Publication Size Tax Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'               => _x( 'Search Publication Sizes', 'Book Module: Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'popular_items'              => NULL, // _x( 'Popular Publication Sizes', 'Book Module: Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'                  => _x( 'All Publication Sizes', 'Book Module: Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'                  => _x( 'Edit Publication Size', 'Book Module: Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'                  => _x( 'View Publication Size', 'Book Module: Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'update_item'                => _x( 'Update Publication Size', 'Book Module: Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'               => _x( 'Add New Publication Size', 'Book Module: Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'new_item_name'              => _x( 'New Publication Size Name', 'Book Module: Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'separate_items_with_commas' => _x( 'Separate publication sizes with commas', 'Book Module: Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_or_remove_items'        => _x( 'Add or remove publication sizes', 'Book Module: Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'choose_from_most_used'      => _x( 'Choose from the most used publication sizes', 'Book Module: Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'                  => _x( 'No publication sizes found.', 'Book Module: Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'no_terms'                   => _x( 'No publication sizes', 'Book Module: Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation'      => _x( 'Publication Sizes list navigation', 'Book Module: Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'                 => _x( 'Publication Sizes list', 'Book Module: Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'terms' => array(
				'size_tax' => array(
					'octavo'        => _x( 'Octavo', 'Publication Sizes Tax Defaults', GEDITORIAL_TEXTDOMAIN ), // vaziri
					'folio'         => _x( 'Folio', 'Publication Sizes Tax Defaults', GEDITORIAL_TEXTDOMAIN ), // soltani
					'medium-octavo' => _x( 'Medium Octavo', 'Publication Sizes Tax Defaults', GEDITORIAL_TEXTDOMAIN ), //roghee
					'quatro'        => _x( 'Quatro', 'Publication Sizes Tax Defaults', GEDITORIAL_TEXTDOMAIN ), //rahli
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
				'trackbacks',
				'custom-fields',
				'comments',
				'revisions',
				'page-attributes',
			),
		);
	}

	public function get_global_fields()
	{
		return array(
			$this->constant( 'publication_cpt' ) => array (
				'ot'        => FALSE,
				'st'        => TRUE,
				'alt_title' => FALSE,
				'isbn'      => TRUE,
				'size'      => TRUE,
				'year'      => TRUE,
				'edition'   => TRUE,
				'print'     => FALSE,
				'pages'     => TRUE,
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

	public function init()
	{
		do_action( 'geditorial_book_init', $this->module );

		$this->do_globals();

		$this->register_post_type( 'publication_cpt', array(), array( 'post_tag' ) );
		$this->register_taxonomy( 'subject_tax', array( 'hierarchical' => TRUE, ), 'publication_cpt' );
		$this->register_taxonomy( 'library_tax', array( 'hierarchical' => TRUE, ), 'publication_cpt' );

		$this->register_taxonomy( 'publisher_tax', array(), 'publication_cpt' );
		$this->register_taxonomy( 'type_tax', array( 'hierarchical' => TRUE, ), 'publication_cpt' );
		$this->register_taxonomy( 'status_tax', array( 'hierarchical' => TRUE, ), 'publication_cpt' );

		// FIXME: check not working!
		// if ( $this->geditorial_meta )
			$this->register_taxonomy( 'size_tax', array(), 'publication_cpt' );
	}

	public function admin_init()
	{
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 20, 2 );
		add_filter( 'manage_'.$this->constant( 'publication_cpt' ).'_posts_columns', array( $this, 'manage_posts_columns' ) );
		add_action( 'manage_'.$this->constant( 'publication_cpt' ).'_posts_custom_column', array( $this, 'posts_custom_column' ), 10, 2 );
		add_filter( 'disable_months_dropdown', array( $this, 'disable_months_dropdown' ), 8, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
		add_action( 'parse_query', array( $this, 'parse_query' ) );
	}

	public function register_settings( $page = NULL )
	{
		if ( ! $this->is_register_settings( $page ) )
			return;

		if ( isset( $_POST['install_def_size_tax'] ) )
			$this->insert_default_terms( 'size_tax' );

		parent::register_settings( $page );
		$this->register_settings_button( 'install_def_size_tax', __( 'Install Default Sizes', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( $post_type == $this->constant( 'publication_cpt' ) ) {

			$post_type_object = get_post_type_object( $this->constant( 'publication_cpt' ) );

			if ( $this->geditorial_meta ) {
				add_meta_box( 'geditorial-book-metadata',
					$this->get_meta_box_title( 'publication_cpt', FALSE ),
					array( $this, 'do_meta_box' ),
					$post_type,
					'side',
					'high'
				);
			}

			$this->remove_meta_box( 'size_tax', $post_type, 'tag' );
			$this->add_meta_box_choose_tax( 'status_tax', $post_type );
			$this->add_meta_box_choose_tax( 'type_tax', $post_type );

			if ( is_super_admin() || current_user_can( $post_type_object->cap->edit_others_posts ) ) {
				$this->remove_meta_box( 'publication_cpt', $post_type, 'author' );
				add_meta_box( 'authordiv',
					__( 'Curator', GEDITORIAL_TEXTDOMAIN ),
					'post_author_meta_box',
					$this->constant( 'publication_cpt' ),
					'side'
				);
			}

			$this->remove_meta_box( 'publication_cpt', $post_type, 'excerpt' );
			add_meta_box( 'postexcerpt',
				__( 'Summary', GEDITORIAL_TEXTDOMAIN ),
				'post_excerpt_meta_box',
				$this->constant( 'publication_cpt' ),
				'normal',
				'high'
			);
		}
	}

	public function gpeople_support( $post_types )
	{
		return array_merge( $post_types, array( $this->constant( 'publication_cpt' ) ) );
	}

	public function meta_init( $meta_module )
	{
		$this->add_post_type_fields( $this->constant( 'publication_cpt' ) );

		add_filter( 'geditorial_meta_strings', array( $this, 'meta_strings' ) );
		add_filter( 'geditorial_meta_sanitize_post_meta', array( $this, 'meta_sanitize_post_meta' ), 10 , 4 );
		add_filter( 'geditorial_meta_box_callback', array( $this, 'meta_box_callback' ), 10 , 2 );

		$this->geditorial_meta = TRUE;
	}

	public function meta_strings( $strings )
	{
		$publication_cpt = $this->constant( 'publication_cpt' );
		$strings['titles'][$publication_cpt] = $this->strings['titles']['publication_cpt'];
		$strings['descriptions'][$publication_cpt] = $this->strings['descriptions']['publication_cpt'];

		return $strings;
	}

	public function meta_box_callback( $callback, $post_type )
	{
		if ( $post_type == $this->constant( 'publication_cpt' ) )
			return FALSE;

		return $callback;
	}

	public function tweaks_strings( $strings )
	{
		$new = array(
			'taxonomies' => array(
				$this->constant( 'subject_tax' ) => array(
					'column'     => 'taxonomy-'.$this->constant( 'subject_tax' ),
					'dashicon'   => $this->module->dashicon,
					'title_attr' => $this->get_string( 'name', 'subject_tax', 'labels' ),
				),
			),
		);

		return self::parse_args_r( $new, $strings );
	}

	public function do_meta_box( $post )
	{
		$fields = gEditorial()->meta->post_type_fields( $post->post_type );

		echo '<div class="geditorial-admin-wrap-metabox">';

		do_action( 'geditorial_meta_box_before', gEditorial()->meta->module, $post, $fields );

		gEditorialHelper::meta_admin_field( 'alt_title', $fields, $post );
		gEditorialHelper::meta_admin_field( 'edition', $fields, $post );
		gEditorialHelper::meta_admin_field( 'year', $fields, $post );
		gEditorialHelper::meta_admin_field( 'print', $fields, $post );
		gEditorialHelper::meta_admin_field( 'pages', $fields, $post );
		gEditorialHelper::meta_admin_field( 'isbn', $fields, $post, TRUE );
		gEditorialHelper::meta_admin_tax_field( 'size', $fields, $post, $this->constant( 'size_tax' ) );

		do_action( 'geditorial_meta_box_after', gEditorial()->meta->module, $post, $fields );

		wp_nonce_field( 'geditorial_book_meta_box', '_geditorial_book_meta_box' );

		echo '</div>';
	}

	public function meta_sanitize_post_meta( $postmeta, $fields, $post_id, $post_type )
	{
		$publication_cpt = $this->constant( 'publication_cpt' );

		if ( $publication_cpt == $post_type
			&& wp_verify_nonce( @$_REQUEST['_geditorial_book_meta_box'], 'geditorial_book_meta_box' ) ) {

			$prefix = 'geditorial-meta-';

			foreach ( $this->fields[$publication_cpt] as $field => $field_enabled ) {
				switch ( $field ) {
					case 'size':

						gEditorialHelper::set_postmeta_field_term( $post_id, $field, $this->constant( 'size_tax' ), $prefix );

					break;
					case 'pages':

						gEditorialHelper::set_postmeta_field_number( $postmeta, $field, $prefix );

					break;
					case 'alt_title':
					case 'edition':
					case 'year':
					case 'print':
						gEditorialHelper::set_postmeta_field_string( $postmeta, $field, $prefix );

					break;
					case 'isbn':

						if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
							$postmeta[$field] = gEditorialBookHelper::getISBN( $_POST[$prefix.$field] );

						else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
							unset( $postmeta[$field] );
				}
			}
		}

		return $postmeta;
	}

	public function disable_months_dropdown( $false, $post_type )
	{
		if ( $this->constant( 'publication_cpt' ) == $post_type )
			return TRUE;

		return $false;
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
			$this->column_thumb( $post_id );
	}

	public function post_updated_messages( $messages )
	{
		if ( $this->is_current_posttype( 'publication_cpt' ) )
			$messages[$this->constant( 'publication_cpt' )] = $this->get_post_updated_messages( 'publication_cpt' );

		return $messages;
	}
}
