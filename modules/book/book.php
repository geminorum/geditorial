<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialBook extends gEditorialModuleCore
{

	protected $partials = array( 'templates', 'helper', 'query' );

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
				'insert_content', // p2p // FIXME
				'insert_content_before', // cover // FIXME
				'insert_priority',
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
					'author_box_title'   => _x( 'Curator', 'Book Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'excerpt_box_title'  => _x( 'Summary', 'Book Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
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
			),
			'settings' => array(
				'post_types_after'     => gEditorialSettingsCore::infoP2P(),
				'install_def_size_tax' => _x( 'Install Default Sizes', 'Book Module: Setting Button', GEDITORIAL_TEXTDOMAIN ),
			),
			'noops' => array(
				'publication_cpt' => _nx_noop( 'Publication', 'Publications', 'Book Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'subject_tax'     => _nx_noop( 'Subject', 'Subjects', 'Book Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'library_tax'     => _nx_noop( 'Library', 'Libraries', 'Book Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'publisher_tax'   => _nx_noop( 'Publisher', 'Publishers', 'Book Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'type_tax'        => _nx_noop( 'Publication Type', 'Publication Types', 'Book Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'status_tax'      => _nx_noop( 'Publication Status', 'Publication Statuses', 'Book Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'size_tax'        => _nx_noop( 'Publication Size', 'Publication Sizes', 'Book Module: Noop', GEDITORIAL_TEXTDOMAIN ),
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
						'desc' => array(
							'title' => _x( 'Description', 'Book Module: P2P', GEDITORIAL_TEXTDOMAIN ),
							'type' => 'text',
							'value' => '%s',
						),
					),
					'admin_column' => FALSE, // adding through tweaks module
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
				'comments',
				'revisions',
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
			'meta_box_cb'  => NULL, // default meta box
		), 'publication_cpt' );

		$this->register_taxonomy( 'library_tax', array(
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL, // default meta box
		), 'publication_cpt' );

		$this->register_taxonomy( 'publisher_tax', array(
			'meta_box_cb' => NULL, // default meta box
		), 'publication_cpt' );

		$this->register_taxonomy( 'type_tax', array(
			'hierarchical' => TRUE,
		), 'publication_cpt' );

		$this->register_taxonomy( 'status_tax', array(
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
		), 'publication_cpt' );

		if ( ! is_admin()
			&& $this->get_setting( 'insert_content_before', FALSE ) )
				add_action( 'gnetwork_themes_content_before',
					array( $this, 'content_before' ),
					$this->get_setting( 'insert_priority', -50 )
				);
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, 'publication_cpt' ) )
			$this->_edit_screen( $_REQUEST['post_type'] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'publication_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
				add_filter( 'get_default_comment_status', array( $this, 'get_default_comment_status' ), 10, 3 );

				add_filter( 'geditorial_meta_box_callback', '__return_true', 12 );
				add_filter( 'geditorial_meta_dbx_callback', '__return_true', 12 );

			} else if ( 'edit' == $screen->base ) {

				add_filter( 'disable_months_dropdown', '__return_true', 12 );
				add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ), 12, 2 );
				add_action( 'parse_query', array( $this, 'parse_query' ) );

				if ( $this->p2p )
					add_action( 'geditorial_tweaks_column_row', array( $this, 'column_row_p2p_to' ), -25 );

				$this->_edit_screen( $screen->post_type );
			}

		} else if ( $this->p2p && 'edit' == $screen->base
			&& in_array( $screen->post_type, $this->post_types() ) ) {

			add_action( 'geditorial_tweaks_column_row', array( $this, 'column_row_p2p_from' ), -25 );
		}
	}

	private function _edit_screen( $post_type )
	{
		add_filter( 'manage_'.$post_type.'_posts_columns', array( $this, 'manage_posts_columns' ) );
		add_action( 'manage_'.$post_type.'_posts_custom_column', array( $this, 'posts_custom_column' ), 10, 2 );
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
		$extra = array( 'p2p:per_page' => -1, 'p2p:context' => 'admin_column' );
		$type  = $this->constant( 'publication_cpt_p2p' );
		$p2p   = p2p_type( $type )->get_connected( $post, $extra, 'abstract' );
		$count = count( $p2p->items );

		if ( ! $count )
			return;

		if ( empty( $this->column_icon ) )
			$this->column_icon = $this->get_column_icon( FALSE,
				NULL, $this->strings['p2p']['publication_cpt']['title']['to'] );

		if ( empty( $this->all_post_types ) )
			$this->all_post_types = gEditorialWordPress::getPostTypes( 2 );

		$post_types = array_unique( array_map( function( $r ){
			return $r->post_type;
		}, $p2p->items ) );

		$args = array(
			'connected_direction' => 'to',
			'connected_type'      => $type,
			'connected_items'     => $post->ID,
		);

		echo '<li class="-row -book -p2p -connected">';

			echo $this->column_icon;

			echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

			$list = array();

			foreach ( $post_types as $post_type )
				$list[] = gEditorialHTML::tag( 'a', array(
					'href'   => gEditorialWordPress::getPostTypeEditLink( $post_type, 0, $args ),
					'title'  => _x( 'View the connected list', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'target' => '_blank',
				), $this->all_post_types[$post_type] );

			echo gEditorialHelper::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

		echo '</li>';
	}

	public function column_row_p2p_from( $post )
	{
		if ( empty( $this->column_icon ) )
			$this->column_icon = $this->get_column_icon( FALSE,
				NULL, $this->strings['p2p']['publication_cpt']['title']['from'] );

		$extra = array( 'p2p:per_page' => -1, 'p2p:context' => 'admin_column' );
		$type  = $this->constant( 'publication_cpt_p2p' );
		$p2p   = p2p_type( $type )->get_connected( $post, $extra, 'abstract' );

		foreach ( $p2p->items as $item ) {
			echo '<li class="-row -book -p2p -connected">';

				if ( current_user_can( 'edit_post', $item->get_id() ) )
					echo $this->get_column_icon( get_edit_post_link( $item->get_id() ),
						NULL, $this->strings['p2p']['publication_cpt']['title']['from'] );
				else
					echo $this->column_icon;

				$args = array(
					'connected_direction' => 'to',
					'connected_type'      => $type,
					'connected_items'     => $item->get_id(),
				);

				echo gEditorialHTML::tag( 'a', array(
					'href'   => gEditorialWordPress::getPostTypeEditLink( $post->post_type, 0, $args ),
					'title'  => _x( 'View all connected', 'Book Module', GEDITORIAL_TEXTDOMAIN ),
					'target' => '_blank',
				), gEditorialHelper::trimChars( $item->get_title() ) );

				echo $this->p2p_get_meta( $item->p2p_id, 'ref', ' &ndash; ', '',
			 		$this->strings['p2p']['publication_cpt']['fields']['ref']['title'] );

				echo $this->p2p_get_meta( $item->p2p_id, 'desc', ' &ndash; ', '',
			 		$this->strings['p2p']['publication_cpt']['fields']['desc']['title'] );

			echo '</li>';
		}
	}

	public function register_settings( $page = NULL )
	{
		if ( ! $this->is_register_settings( $page ) )
			return;

		if ( isset( $_POST['install_def_size_tax'] ) )
			$this->insert_default_terms( 'size_tax' );

		parent::register_settings( $page );
		$this->register_button( 'install_def_size_tax' );
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

	public function restrict_manage_posts( $post_type, $which )
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

		if ( ! in_the_loop() || ! is_main_query() )
			return;

		$this->list_p2p( NULL, '-'.$this->get_setting( 'insert_content', 'none' ) );
	}

	public function list_p2p( $post = NULL, $class = '' )
	{
		if ( is_null( $post ) )
			$post = get_post();

		$connected = new WP_Query( array(
			'connected_type'  => $this->constant( 'publication_cpt_p2p' ),
			'connected_items' => $post,
		) );

		if ( $connected->have_posts() ) {
			echo '<div class="geditorial-wrap -book -p2p '.$class.'"><ul>';

			while ( $connected->have_posts() ) {
				$connected->the_post();
				echo '<li>';

					echo gEditorialHTML::tag( 'a', array(
						'href' => get_permalink(),
					), get_the_title() );

					echo $this->p2p_get_meta( $post->p2p_id, 'ref', ' &ndash; ' );
					echo $this->p2p_get_meta( $post->p2p_id, 'desc', ' &ndash; ' );

				echo '</li>';
			}

			echo '</ul></div>';
			wp_reset_postdata();
		}
	}

	public function content_before( $content, $posttypes = NULL )
	{
		global $page;

		if ( 1 == $page
			&& is_singular( $this->constant( 'publication_cpt' ) )
			&& in_the_loop() && is_main_query() )
				gEditorialBookTemplates::postImage( array(
					'size' => $this->get_image_size_key( 'publication_cpt', 'medium' ),
					'link' => 'attachment',
				) );
	}
}
