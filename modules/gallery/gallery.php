<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialGallery extends gEditorialModuleCore
{

	public static function module()
	{
		if ( ! self::isDev() )
			return FALSE;

		return array(
			'name'     => 'gallery',
			'title'    => _x( 'Gallery', 'Gallery Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Photo Directory and Gallery for WordPress Editorial', 'Gallery Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon' => 'format-gallery',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'posttypes_option' => 'posttypes_option', // add p2p to connect with selected post types
		);
	}

	protected function get_global_constants()
	{
		return array(
			'album_cpt'         => 'photo_album',
			'album_cpt_slug'    => 'album',
			'album_cpt_archive' => 'albums',
			'album_cat_tax'     => 'photo_gallery',
			'album_tag_tax'     => 'album_tag',
			'photo_tag_tax'     => 'photo_tag',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'labels' => array(
				'album_cpt' => array(
					'name'                  => _x( 'Photo Albums', 'Gallery Module: Photo Album CPT Labels: Name', GEDITORIAL_TEXTDOMAIN ),
					'menu_name'             => _x( 'Gallery', 'Gallery Module: Photo Album CPT Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
					'singular_name'         => _x( 'Photo Album', 'Gallery Module: Photo Album CPT Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
					'description'           => _x( 'Photo Album Post Type', 'Gallery Module: Photo Album CPT Labels: Description', GEDITORIAL_TEXTDOMAIN ),
					'add_new'               => _x( 'Add New', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'add_new_item'          => _x( 'Add New Photo Album', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'edit_item'             => _x( 'Edit Photo Album', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'new_item'              => _x( 'New Photo Album', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'view_item'             => _x( 'View Photo Album', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'search_items'          => _x( 'Search Photo Albums', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'not_found'             => _x( 'No photo albums found.', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'not_found_in_trash'    => _x( 'No photo albums found in Trash.', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'all_items'             => _x( 'All Photo Albums', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'archives'              => _x( 'Photo Album Archives', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'insert_into_item'      => _x( 'Insert into photo album', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'uploaded_to_this_item' => _x( 'Uploaded to this photo album', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'featured_image'        => _x( 'Featured Photo', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'set_featured_image'    => _x( 'Set featured photo', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'remove_featured_image' => _x( 'Remove featured photo', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'use_featured_image'    => _x( 'Use as featured photo', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'filter_items_list'     => _x( 'Filter photo albums list', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'items_list_navigation' => _x( 'Photo Albums list navigation', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'items_list'            => _x( 'Photo Albums list', 'Gallery Module: Photo Album CPT Labels', GEDITORIAL_TEXTDOMAIN ),
				),
				'album_cat_tax' => array(
                    'name'                  => _x( 'Album Categories', 'Gallery Module: Album Category Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'             => _x( 'Album Categories', 'Gallery Module: Album Category Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'         => _x( 'Album Category', 'Gallery Module: Album Category Tax Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'          => _x( 'Search Album Categories', 'Gallery Module: Album Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'             => _x( 'All Album Categories', 'Gallery Module: Album Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item'           => _x( 'Parent Album Category', 'Gallery Module: Album Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item_colon'     => _x( 'Parent Album Category:', 'Gallery Module: Album Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'             => _x( 'Edit Album Category', 'Gallery Module: Album Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'             => _x( 'View Album Category', 'Gallery Module: Album Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'update_item'           => _x( 'Update Album Category', 'Gallery Module: Album Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'          => _x( 'Add New Album Category', 'Gallery Module: Album Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'new_item_name'         => _x( 'New Album Category Name', 'Gallery Module: Album Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'             => _x( 'No album categories found.', 'Gallery Module: Album Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'no_terms'              => _x( 'No album categories', 'Gallery Module: Album Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation' => _x( 'Album Categories list navigation', 'Gallery Module: Album Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'            => _x( 'Album Categories list', 'Gallery Module: Album Category Tax Labels', GEDITORIAL_TEXTDOMAIN ),
				),
				'photo_tag_tax' => array(
                    'name'                       => _x( 'Photo Tags', 'Gallery Module: Photo Tag Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'                  => _x( 'Photo Tags', 'Gallery Module: Photo Tag Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'              => _x( 'Photo Tag', 'Gallery Module: Photo Tag Tax Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'               => _x( 'Search Photo Tags', 'Gallery Module: Photo Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'popular_items'              => NULL, // _x( 'Popular Photo Tags', 'Gallery Module: Photo Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'                  => _x( 'All Photo Tags', 'Gallery Module: Photo Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'                  => _x( 'Edit Photo Tag', 'Gallery Module: Photo Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'                  => _x( 'View Photo Tag', 'Gallery Module: Photo Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'update_item'                => _x( 'Update Photo Tag', 'Gallery Module: Photo Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'               => _x( 'Add New Photo Tag', 'Gallery Module: Photo Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'new_item_name'              => _x( 'New Photo Tag Name', 'Gallery Module: Photo Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'separate_items_with_commas' => _x( 'Separate photo tags with commas', 'Gallery Module: Photo Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_or_remove_items'        => _x( 'Add or remove photo tags', 'Gallery Module: Photo Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'choose_from_most_used'      => _x( 'Choose from the most used photo tags', 'Gallery Module: Photo Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'                  => _x( 'No photo tags found.', 'Gallery Module: Photo Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'no_terms'                   => _x( 'No photo tags', 'Gallery Module: Photo Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation'      => _x( 'Photo Tags list navigation', 'Gallery Module: Photo Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'                 => _x( 'Photo Tags list', 'Gallery Module: Photo Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
				),
				'album_tag_tax' => array(
                    'name'                       => _x( 'Album Tags', 'Gallery Module: Album Tag Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'                  => _x( 'Album Tags', 'Gallery Module: Album Tag Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'              => _x( 'Album Tag', 'Gallery Module: Album Tag Tax Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'               => _x( 'Search Album Tags', 'Gallery Module: Album Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'popular_items'              => NULL, // _x( 'Popular Album Tags', 'Gallery Module: Album Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'                  => _x( 'All Album Tags', 'Gallery Module: Album Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'                  => _x( 'Edit Album Tag', 'Gallery Module: Album Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'                  => _x( 'View Album Tag', 'Gallery Module: Album Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'update_item'                => _x( 'Update Album Tag', 'Gallery Module: Album Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'               => _x( 'Add New Album Tag', 'Gallery Module: Album Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'new_item_name'              => _x( 'New Album Tag Name', 'Gallery Module: Album Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'separate_items_with_commas' => _x( 'Separate album tags with commas', 'Gallery Module: Album Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_or_remove_items'        => _x( 'Add or remove album tags', 'Gallery Module: Album Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'choose_from_most_used'      => _x( 'Choose from the most used album tags', 'Gallery Module: Album Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'                  => _x( 'No album tags found.', 'Gallery Module: Album Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'no_terms'                   => _x( 'No album tags', 'Gallery Module: Album Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation'      => _x( 'Album Tags list navigation', 'Gallery Module: Album Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'                 => _x( 'Album Tags list', 'Gallery Module: Album Tag Tax Labels', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'terms' => array(
				'album_cat_tax' => array(),
			),
		);
	}

	protected function get_global_supports()
	{
		return array(
			'album_cpt' => array(
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

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'album_cpt' );
	}

	public function init()
	{
		do_action( 'geditorial_gallery_init', $this->module );

		$this->do_globals();

		$this->post_types_excluded = array( $this->constant( 'album_cpt' ) );

		$this->register_post_type( 'album_cpt', array(), array( 'post_tag' ) );
		$this->register_taxonomy( 'album_cat_tax', array(), 'album_cpt' );

		$this->register_taxonomy( 'album_tag_tax', array(
			'hierarchical' => FALSE,
		), 'album_cpt' );

		$this->register_taxonomy( 'photo_tag_tax', array(
			'hierarchical' => FALSE,
		), array( 'attachments' ) );

		// FIXME: just a copy
		// http://www.johanbrook.com/writings/adding-custom-url-endpoints-in-wordpress/
		// add_rewrite_endpoint('photos', EP_PERMALINK);
		// add_filter( 'single_template', 'project_attachments_template' );
		// add_filter( 'query_vars', 'add_query_vars' );

		// NO NEED!!
		// add_filter( 'attachment_fields_to_edit', array( $this, 'attachment_fields_to_edit' ), 7, 2 );
		// add_filter( 'attachment_fields_to_save', array( $this, 'attachment_fields_to_save' ), 7, 2 );

	}

	public function current_screen( $screen )
	{
		// if ( 'post' == $screen->base
		// 	&& in_array( $screen->post_type, $this->post_types() ) )
		// 		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 20, 2 );

	}


	public function register_settings( $page = NULL )
	{
		if ( ! $this->is_register_settings( $page ) )
			return;

		if ( isset( $_POST['install_def_album_cats'] ) )
			$this->insert_default_terms( 'album_cat_tax' );

		parent::register_settings( $page );
		$this->register_settings_button( 'install_def_album_cats', _x( 'Install Default Album Cats', 'Gallery Module', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function attachment_fields_to_edit( $form_fields, $post )
	{
		$form_fields['geditorial_gallery_order'] = array(
            'label' => _x( 'Order', 'Gallery Module', GEDITORIAL_TEXTDOMAIN ),
            'value' => isset( $post->menu_order ) ? $post->menu_order : '0',
		);

		return $form_fields;
	}

	public function attachment_fields_to_save( $post, $attachment )
	{
		// error_log( print_r( compact( 'attachment' ), TRUE ) );

		if ( isset( $attachment['geditorial_gallery_order'] ) )
			$post['menu_order'] = $attachment['geditorial-gallery-order'];

		return $post;
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

	/**
	*   Add the 'photos' query variable so Wordpress
	*   won't mangle it.
	*/
	function add_query_vars($vars){
		$vars[] = "photos";
		return $vars;
	}

	/**
	*    From http://codex.wordpress.org/Template_Hierarchy
	*
	*    Adds a custom template to the query queue.
	*/
	function project_attachments_template($templates = ""){
		global $wp_query;

		// If the 'photos' endpoint isn't appended to the URL,
		// don't do anything and return
		if(!isset( $wp_query->query['photos'] ))
			return $templates;

		// .. otherwise, go ahead and add the 'photos.php' template
		// instead of 'single-{$type}.php'.
		if(!is_array($templates) && !empty($templates)) {
			$templates = locate_template(array("photos.php", $templates),false);
		}
		elseif(empty($templates)) {
			$templates = locate_template("photos.php",false);
		}
		else {
			$new_template = locate_template(array("photos.php"));
			if(!empty($new_template)) array_unshift($templates,$new_template);
		}

		return $templates;
	}
}
