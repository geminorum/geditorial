<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialSeries extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'series';
	var $meta_key    = '_ge_series';
	var $pre_term    = 'gXsXsE-';

	public function __construct()
	{
		global $gEditorial;

		$args = array(
			'title'                => __( 'Series', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Post Series Management', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( 'Adding Post Series Functionality to WordPress With Taxonomies', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'             => 'smiley',
			'slug'                 => 'series',
			'load_frontend'        => TRUE,

			'constants'            => array(
				'series_tax'                => 'series',
				'series_shortcode'          => 'series',
				'multiple_series_shortcode' => 'multiple_series',
			),

			'default_options' => array(
				'enabled'  => FALSE,
				'settings' => array(),

				'post_types' => array(
					'post' => TRUE,
					'page' => FALSE,
				),
				'post_fields' => array(
					'in_series_title' => TRUE,
					'in_series_order' => TRUE,
					'in_series_desc'  => FALSE,
				),
			),
			'settings' => array(
				'_general' => array(
					array(
						'field'       => 'multiple',
						'title'       => __( 'Multiple Series', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Using multiple series for each post.', GEDITORIAL_TEXTDOMAIN ),
						'default'     => 0,
					),
					array(
						'field'       => 'editor_button',
						'title'       => _x( 'Editor Button', 'Series Editor Button', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Adding an Editor Button to insert shortcodes', GEDITORIAL_TEXTDOMAIN ),
						'default'     => 1,
					),
				),
				'post_types_option' => 'post_types_option',
				'post_types_fields' => 'post_types_fields',
			),
			'strings' => array(
				'titles' => array(
					'post' => array(
						'in_series_title' => __( 'Title', GEDITORIAL_TEXTDOMAIN ),
						'in_series_order' => __( 'Order', GEDITORIAL_TEXTDOMAIN ),
						'in_series_desc'  => __( 'Description', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'descriptions' => array(
					'post' => array(
						'in_series_title' => __( 'In Series Title', GEDITORIAL_TEXTDOMAIN ),
						'in_series_order' => __( 'In Series Order', GEDITORIAL_TEXTDOMAIN ),
						'in_series_desc'  => __( 'In Series Description', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'misc' => array(
					'post' => array(
						'meta_box_title'  => __( 'Series', GEDITORIAL_TEXTDOMAIN ),
						'meta_box_action' => __( 'Management', GEDITORIAL_TEXTDOMAIN ),
						'column_title'    => __( 'Series', GEDITORIAL_TEXTDOMAIN ),
						'select_series'   => __( '&mdash; Choose a Series &mdash;', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'labels' => array(
					'series_tax' => array(
						'name'                       => __( 'Series', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => __( 'Series', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => __( 'Search Series', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
						'all_items'                  => __( 'All Series', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => __( 'Parent Series', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => __( 'Parent Series:', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => __( 'Edit Series', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => __( 'Update Series', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => __( 'Add New Series', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => __( 'New Series Name', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => __( 'Separate series with commas', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => __( 'Add or remove series', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => __( 'Choose from the most used series', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => __( 'Series', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
			'configure_page_cb' => 'print_configure_view',
		);

		$gEditorial->register_module( $this->module_name, $args );
	}

	public function setup()
	{
		add_action( 'init', array( &$this, 'init' ) );
		add_filter( 'geditorial_tinymce_strings', array( &$this, 'tinymce_strings' ) );

		$this->require_code();

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );

			add_action( 'save_post', array( $this, 'save_post' ), 20, 2 );
		}
	}

	public function init()
	{
		do_action( 'geditorial_series_init', $this->module );

		$this->do_filters();
		$this->register_taxonomy( 'series_tax' );
		$this->register_editor_button();

		$this->register_shortcode( 'series_shortcode', array( 'gEditorialSeriesTemplates', 'shortcode_series' ) );
		$this->register_shortcode( 'multiple_series_shortcode', array( 'gEditorialSeriesTemplates', 'shortcode_multiple_series' ) );
	}

	public function admin_init()
	{
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 20, 2 );

		// internal actions:
		add_action( 'geditorial_series_meta_box', array( $this, 'geditorial_series_meta_box' ), 5, 2 );
		add_action( 'geditorial_series_meta_box_item', array( $this, 'geditorial_series_meta_box_item' ), 5, 4 );
	}

	public function tinymce_strings( $strings )
	{
		$new = array(
			'ge_series-title' => __( 'Add Series Shortcode', GEDITORIAL_TEXTDOMAIN ),
		);

		return array_merge( $strings, $new );
	}

	public function save_post( $post_id, $post )
	{
		if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			|| empty( $_POST )
			|| $post->post_type == 'revision' )
				return $post_id;

		if ( ! in_array( $post->post_type, $this->post_types() ) )
			return $post_id;

		$postmeta = $this->sanitize_post_meta(
			$this->get_postmeta( $post_id ),
			$this->post_type_fields( $post->post_type ),
			$post_id,
			$post->post_type
		);

		$this->set_meta( $post_id, $postmeta );
		wp_cache_flush();

		return $post_id;
	}

	private function sanitize_post_meta( $postmeta, $fields, $post_id, $post_type )
	{
		if ( ! isset( $_REQUEST['_geditorial_series_post_main'] ) ||
			! wp_verify_nonce( $_REQUEST['_geditorial_series_post_main'], 'geditorial_series_post_main' ) )
				return $postmeta;

		if ( ! isset( $_POST['geditorial-series-terms'] ) )
			return $postmeta;

		$prefix   = 'geditorial-series-';
		$postmeta = $pre_terms = array();

		foreach ( $_POST['geditorial-series-terms'] as $offset => $term_id )
			if ( $term_id && '-1' != $term_id )
				$pre_terms[$offset] = intval( $term_id );

		wp_set_object_terms( $post_id, ( count( $pre_terms ) ? $pre_terms : NULL ), $this->module->constants['series_tax'], FALSE );

		foreach ( $pre_terms as $offset => $pre_term ) {
			foreach ( $fields as $field ) {
				switch ( $field ) {
					case 'in_series_order' :
						if ( isset( $_POST[$prefix.$field][$offset] ) && '0' != $_POST[$prefix.$field][$offset] )
							$postmeta[$pre_term][$field] = $this->intval( $_POST[$prefix.$field][$offset] );
						elseif ( isset( $postmeta[$pre_term][$field] ) && isset( $_POST[$prefix.$field][$offset] )  )
							unset( $postmeta[$pre_term][$field] );
					break;
					case 'in_series_title' :
					case 'in_series_desc' :
						if ( isset( $_POST[$prefix.$field][$offset] )
							&& strlen( $_POST[$prefix.$field][$offset] ) > 0
							&& $this->get_string( $field, $post_type ) !== $_POST[$prefix.$field][$offset] )
								$postmeta[$pre_term][$field] = $this->kses( $_POST[$prefix.$field][$offset] );
						elseif ( isset( $postmeta[$pre_term][$field] ) && isset( $_POST[$prefix.$field][$offset] ) )
							unset( $postmeta[$pre_term][$field] );
					break;
				}
			}
		}
		$postmeta = apply_filters( 'geditorial_series_sanitize_post_meta', $postmeta, $fields, $post_id, $post_type );
		return $postmeta;
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( ! in_array( $post_type, $this->post_types() ) )
			return;

		remove_meta_box( 'tagsdiv-'.$this->module->constants['series_tax'], $post_type, 'side' );
		add_meta_box(
			'geditorial-series',
			$this->get_meta_box_title( $post_type, $this->get_url_tax_edit( 'series_tax' ), 'edit_others_posts' ),
			array( $this, 'do_meta_box' ),
			$post_type,
			'side' );
	}

	public function do_meta_box( $post )
	{
		echo '<div class="geditorial-admin-wrap-metabox series">';
		$series = gEditorialHelper::getTerms( $this->module->constants['series_tax'], $post->ID, true );
		do_action( 'geditorial_series_meta_box', $post, $series );
		echo '</div>';
	}

	// TODO : list other post in this series by the order and link to their edit pages
	public function geditorial_series_meta_box( $post, $the_terms )
	{
		$fields           = $this->post_type_fields( $post->post_type );
		$series_dropdowns = $series_posts = $map = array();
		$i                = 1;

		foreach ( $the_terms as $the_term ) {
			$series_dropdowns[$i] = wp_dropdown_categories( array(
				'taxonomy'         => $this->module->constants['series_tax'],
				'selected'         => $the_term->term_id,
				'show_option_none' => $this->get_string( 'select_series', 'post', 'misc' ),
				'name'             => 'geditorial-series-terms['.$i.']',
				'id'               => 'geditorial_series_terms-'.$i,
				'class'            => 'geditorial-admin-dropbown',
				'show_count'       => 1,
				'hide_empty'       => 0,
				'echo'             => 0,
			) );

			$series_posts[$i] = gEditorialHelper::getTermPosts( $this->module->constants['series_tax'], $the_term, array( $post->ID ) );
			$map[$i]          = $the_term->term_id;
			$i++;
		}

		if ( $this->get_setting( 'multiple', false ) || ! count( $the_terms ) )
			$series_dropdowns[0] = wp_dropdown_categories( array(
				'taxonomy'         => $this->module->constants['series_tax'],
				'selected'         => 0,
				'show_option_none' => $this->get_string( 'select_series', 'post', 'misc' ),
				'name'             => 'geditorial-series-terms[0]',
				'id'               => 'geditorial_series_terms-0',
				'class'            => 'geditorial-admin-dropbown',
				'show_count'       => 1,
				'hide_empty'       => 0,
				'echo'             => 0,
				'exclude'          => $map,
			) );

		$map[0] = false;

		foreach ( $series_dropdowns as $counter => $series_dropdown ) {
			if ( $series_dropdown ) {
				// echo '<div class="geditorial_series_term_wrap">';
				echo '<div class="field-wrap-group">';
				echo '<div class="field-wrap field-wrap-select">';
				echo $series_dropdown;
				echo '</div>';
				do_action( 'geditorial_series_meta_box_item', $counter, $map[$counter], $fields, $post );

				if ( $counter && $series_posts[$counter] )
					echo $series_posts[$counter];

				echo '</div>';
			}
		}

		do_action( 'geditorial_series_box_after', $this->module, $post, $fields );
		wp_nonce_field( 'geditorial_series_post_main', '_geditorial_series_post_main' );
	}

	public function geditorial_series_meta_box_item( $counter, $term_id, $fields, $post )
	{
		$meta = ( $counter ? $this->get_postmeta( $post->ID, $term_id, array() ) : array() );

		$field = 'in_series_title';
		if ( in_array( $field, $fields )
			&& self::user_can( 'view', $field ) ) {

			$title = $this->get_string( $field, $post->post_type );
			$html = gEditorialHelper::html( 'input', array(
				'type'         => 'text',
				'class'        => 'field-inputtext',
				'name'         => 'geditorial-series-'.$field.'['.$counter.']',
				'id'           => 'geditorial-series-'.$field.'-'.$counter,
				'value'        => isset( $meta[$field] ) ? $meta[$field] : '',
				'title'        => $title,
				'placeholder'  => $title,
				'readonly'     => ! $this->user_can( 'edit', $field ),
				'autocomplete' => 'off',
			) );

			echo gEditorialHelper::html( 'div', array(
				'class' => 'field-wrap field-wrap-inputtext',
			), $html );
		}

		$field = 'in_series_order';
		if ( in_array( $field, $fields )
			&& self::user_can( 'view', $field ) ) {

			$title = $this->get_string( $field, $post->post_type );
			$html = gEditorialHelper::html( 'input', array(
				'type'         => 'text',
				'class'        => 'field-inputtext',
				'name'         => 'geditorial-series-'.$field.'['.$counter.']',
				'id'           => 'geditorial-series-'.$field.'-'.$counter,
				'value'        => isset( $meta[$field] ) ? $meta[$field] : '',
				'title'        => $title,
				'placeholder'  => $title,
				'readonly'     => ! $this->user_can( 'edit', $field ),
				'autocomplete' => 'off',
			) );

			echo gEditorialHelper::html( 'div', array(
				'class' => 'field-wrap field-wrap-inputtext',
			), $html );
		}

		$field = 'in_series_desc';
		if ( in_array( $field, $fields )
			&& self::user_can( 'view', $field ) ) {

			$title = $this->get_string( $field, $post->post_type );
			$html = gEditorialHelper::html( 'textarea', array(
				'class'        => 'field-textarea textarea-autosize',
				'name'         => 'geditorial-series-'.$field.'['.$counter.']',
				'id'           => 'geditorial-series-'.$field.'-'.$counter,
				'title'        => $title,
				'placeholder'  => $title,
				'readonly'     => ! $this->user_can( 'edit', $field ),
			), isset( $meta[$field] ) ? esc_textarea( $meta[$field] ) : '' );

			echo gEditorialHelper::html( 'div', array(
				'class' => 'field-wrap field-wrap-textarea',
			), $html );
		}
	}
}
