<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialReshare extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'      => 'reshare',
			'title'     => _x( 'Reshare', 'Reshare Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'      => _x( 'Contents from Other Sources', 'Reshare Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'  => 'external',
			'configure' => FALSE,
		);
	}

	protected function get_global_constants()
	{
		return array(
			'reshare_cpt'         => 'reshare',
			'reshare_cpt_archive' => 'reshares',
			'reshare_cat'         => 'reshare_cat',
			'reshare_cat_slug'    => 'reshare-category',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'titles' => array(
				'reshare_cpt' => array(
					'reshare_source_title' => _x( 'Source Title', 'Reshare Module', GEDITORIAL_TEXTDOMAIN ),
					'reshare_source_url'   => _x( 'Source URL', 'Reshare Module', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'descriptions' => array(
				'reshare_cpt' => array(
					'reshare_source_title' => _x( 'Original title of the content', 'Reshare Module', GEDITORIAL_TEXTDOMAIN ),
					'reshare_source_url'   => _x( 'Full URL to the source of the content', 'Reshare Module', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'misc' => array(
				'meta_box_title' => __( 'Metadata', GEDITORIAL_TEXTDOMAIN ),
			),
			'labels' => array(
				'reshare_cpt' => array(
					'name'                  => _x( 'Reshares', 'Reshare Module: Reshare CPT: Name', GEDITORIAL_TEXTDOMAIN ),
					'menu_name'             => _x( 'Reshares', 'Reshare Module: Reshare CPT: Menu Name', GEDITORIAL_TEXTDOMAIN ),
					'singular_name'         => _x( 'Reshare', 'Reshare Module: Reshare CPT: Singular Name', GEDITORIAL_TEXTDOMAIN ),
					'description'           => _x( 'Contents from other sources', 'Reshare Module: Reshare CPT: Description', GEDITORIAL_TEXTDOMAIN ),
					'add_new'               => _x( 'Add New', 'Reshare Module: Reshare CPT', GEDITORIAL_TEXTDOMAIN ),
					'add_new_item'          => _x( 'Add New Reshare', 'Reshare Module: Reshare CPT', GEDITORIAL_TEXTDOMAIN ),
					'edit_item'             => _x( 'Edit Reshare', 'Reshare Module: Reshare CPT', GEDITORIAL_TEXTDOMAIN ),
					'new_item'              => _x( 'New Reshare', 'Reshare Module: Reshare CPT', GEDITORIAL_TEXTDOMAIN ),
					'view_item'             => _x( 'View Reshare', 'Reshare Module: Reshare CPT', GEDITORIAL_TEXTDOMAIN ),
					'search_items'          => _x( 'Search Reshares', 'Reshare Module: Reshare CPT', GEDITORIAL_TEXTDOMAIN ),
					'not_found'             => _x( 'No reshares found.', 'Reshare Module: Reshare CPT', GEDITORIAL_TEXTDOMAIN ),
					'not_found_in_trash'    => _x( 'No reshares found in Trash.', 'Reshare Module: Reshare CPT', GEDITORIAL_TEXTDOMAIN ),
					'all_items'             => _x( 'All Reshares', 'Reshare Module: Reshare CPT', GEDITORIAL_TEXTDOMAIN ),
					'archives'              => _x( 'Reshare Archives', 'Reshare Module: Reshare CPT', GEDITORIAL_TEXTDOMAIN ),
					'insert_into_item'      => _x( 'Insert into reshare', 'Reshare Module: Reshare CPT', GEDITORIAL_TEXTDOMAIN ),
					'uploaded_to_this_item' => _x( 'Uploaded to this reshare', 'Reshare Module: Reshare CPT', GEDITORIAL_TEXTDOMAIN ),
					'filter_items_list'     => _x( 'Filter reshares list', 'Reshare Module: Reshare CPT', GEDITORIAL_TEXTDOMAIN ),
					'items_list_navigation' => _x( 'Reshares list navigation', 'Reshare Module: Reshare CPT', GEDITORIAL_TEXTDOMAIN ),
					'items_list'            => _x( 'Reshares list', 'Reshare Module: Reshare CPT', GEDITORIAL_TEXTDOMAIN ),
				),
				'reshare_cat' => array(
                    'name'                  => _x( 'Reshare Categories', 'Reshare Module: Reshare Category Tax: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'             => _x( 'Reshare Categories', 'Reshare Module: Reshare Category Tax: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'         => _x( 'Reshare Category', 'Reshare Module: Reshare Category Tax: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'          => _x( 'Search Reshare Categories', 'Reshare Module: Reshare Category Tax', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'             => _x( 'All Reshare Categories', 'Reshare Module: Reshare Category Tax', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item'           => _x( 'Parent Reshare Category', 'Reshare Module: Reshare Category Tax', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item_colon'     => _x( 'Parent Reshare Category:', 'Reshare Module: Reshare Category Tax', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'             => _x( 'Edit Reshare Category', 'Reshare Module: Reshare Category Tax', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'             => _x( 'View Reshare Category', 'Reshare Module: Reshare Category Tax', GEDITORIAL_TEXTDOMAIN ),
                    'update_item'           => _x( 'Update Reshare Category', 'Reshare Module: Reshare Category Tax', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'          => _x( 'Add New Reshare Category', 'Reshare Module: Reshare Category Tax', GEDITORIAL_TEXTDOMAIN ),
                    'new_item_name'         => _x( 'New Reshare Category Name', 'Reshare Module: Reshare Category Tax', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'             => _x( 'No reshare categories found.', 'Reshare Module: Reshare Category Tax', GEDITORIAL_TEXTDOMAIN ),
                    'no_terms'              => _x( 'No reshare categories', 'Reshare Module: Reshare Category Tax', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation' => _x( 'Reshare Categories list navigation', 'Reshare Module: Reshare Category Tax', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'            => _x( 'Reshare Categories list', 'Reshare Module: Reshare Category Tax', GEDITORIAL_TEXTDOMAIN ),
				),
			),
		);
	}

	protected function get_global_supports()
	{
		return array(
			'reshare_cpt' => array(
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
			$this->constant( 'reshare_cpt' ) => array(
				'ot'                   => TRUE,
				'st'                   => TRUE,
				'reshare_source_title' => TRUE,
				'reshare_source_url'   => TRUE,
			),
		);
	}

	public function setup( $partials = array() )
	{
		parent::setup( array(
			'templates',
		) );
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'reshare_cpt' );
	}

	public function init()
	{
		do_action( 'geditorial_reshare_init', $this->module );

		$this->do_globals();

		$this->register_post_type( 'reshare_cpt', array(), array( 'post_tag' ) );
		$this->register_taxonomy( 'reshare_cat', array(
			'hierarchical' => TRUE,
		), 'reshare_cpt' );
	}

	public function admin_init()
	{
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( ! $this->geditorial_meta )
			return;

		if ( $post_type == $this->constant( 'reshare_cpt' ) ) {
			add_meta_box( 'geditorial-reshare',
				$this->get_meta_box_title( 'reshare_cpt', FALSE ),
				array( $this, 'do_meta_box' ),
				$post_type,
				'side',
				'high'
			);
		}
	}

	public function meta_init()
	{
		$this->add_post_type_fields( $this->constant( 'reshare_cpt' ) );

		add_filter( 'geditorial_meta_strings', array( $this, 'meta_strings' ) );
		add_filter( 'geditorial_meta_sanitize_post_meta', array( $this, 'meta_sanitize_post_meta' ), 10 , 4 );
		add_filter( 'geditorial_meta_box_callback', array( $this, 'meta_box_callback' ), 10 , 2 );

		$this->geditorial_meta = TRUE;
	}

	public function meta_strings( $strings )
	{
		$reshare_cpt = $this->constant( 'reshare_cpt' );
		$strings['titles'][$reshare_cpt] = $this->strings['titles']['reshare_cpt'];
		$strings['descriptions'][$reshare_cpt] = $this->strings['descriptions']['reshare_cpt'];

		return $strings;
	}

	public function meta_box_callback( $callback, $post_type )
	{
		if ( $post_type == $this->constant( 'reshare_cpt' ) )
			return FALSE;

		return $callback;
	}

	public function tweaks_strings( $strings )
	{
		$new = array(
			'taxonomies' => array(
				$this->constant( 'reshare_cat' ) => array(
					'column'     => 'taxonomy-'.$this->constant( 'reshare_cat' ),
					'dashicon'   => $this->module->dashicon,
					'title_attr' => $this->get_string( 'name', 'reshare_cat', 'labels' ),
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

		gEditorialHelper::meta_admin_field( 'reshare_source_title', $fields, $post );
		gEditorialHelper::meta_admin_field( 'reshare_source_url', $fields, $post, TRUE );

		do_action( 'geditorial_meta_box_after', gEditorial()->meta->module, $post, $fields );

		wp_nonce_field( 'geditorial_reshare_meta_box', '_geditorial_reshare_meta_box' );

		echo '</div>';
	}

	public function meta_sanitize_post_meta( $postmeta, $fields, $post_id, $post_type )
	{
		$reshare_cpt = $this->constant( 'reshare_cpt' );

		if ( $reshare_cpt == $post_type
			&& wp_verify_nonce( @$_REQUEST['_geditorial_reshare_meta_box'], 'geditorial_reshare_meta_box' ) ) {

			foreach ( $this->fields[$reshare_cpt] as $field => $field_enabled ) {
				switch ( $field ) {
					case 'reshare_source_title':

						gEditorialHelper::set_postmeta_field_string( $postmeta, $field );

					break;
					case 'reshare_source_url':

						gEditorialHelper::set_postmeta_field_url( $postmeta, $field );
				}
			}
		}

		return $postmeta;
	}
}
