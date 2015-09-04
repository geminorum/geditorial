<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialSpecs extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'specs';
	var $meta_key    = '_ge_specs';
	var $pre_term    = 'gXsPsP-';

	public function __construct()
	{
		global $gEditorial;

		$args = array(
			'title'                => __( 'Specifications', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Post Specifications Management', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( 'Adding Post Specifications Functionality to WordPress With Taxonomies', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'             => 'editor-ul',
			'slug'                 => 'specs',
			'load_frontend'        => TRUE,
			'constants'            => array(
				'specs_tax'                => 'specs',
				'specs_shortcode'          => 'specs',
				'multiple_specs_shortcode' => 'multiple_specs',
			),
			'default_options' => array(
				'enabled'    => FALSE,
				'settings'   => array(),
				'post_types' => array(
					'post' => TRUE,
					'page' => FALSE,
				),
				'post_fields' => array(
					'spec_title' => TRUE,
					'spec_order' => TRUE,
					'spec_value' => TRUE,
				),
			),
			'settings' => array(
				'_general' => array(
					array(
						'field'       => 'multiple',
						'title'       => __( 'Multiple Specifications', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Using multiple specs for each post.', GEDITORIAL_TEXTDOMAIN ),
						'default'     => 0,
					),
					array(
						'field'       => 'editor_button',
						'title'       => _x( 'Editor Button', 'Specifications Editor Button', GEDITORIAL_TEXTDOMAIN ),
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
						'spec_title' => __( 'Title', GEDITORIAL_TEXTDOMAIN ),
						'spec_order' => __( 'Order', GEDITORIAL_TEXTDOMAIN ),
						'spec_value' => __( 'Description', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'descriptions' => array(
					'post' => array(
						'spec_title' => __( 'In Specifications Title', GEDITORIAL_TEXTDOMAIN ),
						'spec_order' => __( 'In Specifications Order', GEDITORIAL_TEXTDOMAIN ),
						'spec_value' => __( 'In Specifications Description', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'misc' => array(
					'post' => array(
						'meta_box_title' => __( 'Specifications', GEDITORIAL_TEXTDOMAIN ),
						'column_title'   => __( 'Specifications', GEDITORIAL_TEXTDOMAIN ),
						'select_specs'   => __( '&mdash; Choose a Specification &mdash;', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'labels' => array(
					'specs_tax' => array(
						'name'                       => __( 'Specifications', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => __( 'Specifications', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => __( 'Search Specifications', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => null,
						'all_items'                  => __( 'All Specifications', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => __( 'Parent Specifications', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => __( 'Parent Specifications:', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => __( 'Edit Specifications', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => __( 'Update Specifications', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => __( 'Add New Specifications', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => __( 'New Specifications Name', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => __( 'Separate specs with commas', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => __( 'Add or remove specs', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => __( 'Choose from the most used specs', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => __( 'Specifications', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
			'configure_page_cb' => 'print_configure_view',
			'settings_help_tab' => array(
				'id'      => 'geditorial-specs-overview',
				'title'   => __( 'help-tab-title', GEDITORIAL_TEXTDOMAIN ),
				'content' => __( '<p>help-tab-content</p>', GEDITORIAL_TEXTDOMAIN ),
				),
			'settings_help_sidebar' => sprintf(
				__( '<p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p>', GEDITORIAL_TEXTDOMAIN ),
				'http://geminorum.ir/wordpress/geditorial/modules/specs',
				__( 'Editorial Specifications Documentations', GEDITORIAL_TEXTDOMAIN ),
				'https://github.com/geminorum/gEditorial' ),

		);

		$gEditorial->register_module( $this->module_name, $args );
	}

	public function setup()
	{
		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );

			add_action( 'save_post', array( $this, 'save_post' ), 20, 2 );
		}
	}

	public function init()
	{
		do_action( 'geditorial_specs_init', $this->module );

		$this->do_filters();
		$this->register_taxonomies();

		// add_shortcode( $this->module->constants['specs_shortcode'], array( $this, 'shortcode_specs' ) );
		// add_shortcode( $this->module->constants['multiple_specs_shortcode'], array( $this, 'shortcode_multiple_specs' ) );
	}

	public function admin_init()
	{
		add_action( 'admin_print_styles', array( &$this, 'admin_print_styles' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 12, 2 );
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 20, 2 );

		// internal
		add_action( 'geditorial_specs_meta_box', array( $this, 'geditorial_specs_meta_box' ), 5, 2 );
		// add_action( 'geditorial_specs_meta_box_item', array( $this, 'geditorial_specs_meta_box_item' ), 5, 5 );
	}

	public function admin_print_styles()
	{
		$screen = get_current_screen();

		if ( ! in_array( $screen->post_type, $this->post_types() ) )
			return;

		if ( 'edit' == $screen->base ) {

			add_action( 'admin_head-edit.php', function(){
				?><script type="text/javascript">jQuery(document).ready(function($){
					$('textarea.tax_input_specs').each(function(i){
						$(this).parent().remove();
					});
				});</script> <?php
			} );

		} else if ( 'post' == $screen->base  ) {

			wp_register_script( 'jquery-sortable',
				GEDITORIAL_URL.'assets/packages/jquery-sortable/jquery-sortable-min.js',
				array( 'jquery'),
				'0.9.12',
				true );

			$this->enqueue_asset_js( array(), 'specs.'.$screen->base, array( 'jquery-sortable' ) );
		}
	}

	public function register_taxonomies()
	{
		register_taxonomy( $this->module->constants['specs_tax'],
			$this->post_types(), array(
				'labels'                => $this->module->strings['labels']['specs_tax'],
				'public'                => TRUE,
				'show_in_nav_menus'     => FALSE,
				'show_ui'               => TRUE,
				'show_admin_column'     => FALSE,
				'show_tagcloud'         => FALSE,
				'hierarchical'          => FALSE,
				'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
				'query_var'             => TRUE,
				'rewrite'               => array(
					'slug'         => $this->module->constants['specs_tax'],
					'hierarchical' => FALSE,
					'with_front'   => FALSE,
				),
				'capabilities' => array(
					'manage_terms' => 'edit_others_posts',
					'edit_terms'   => 'edit_others_posts',
					'delete_terms' => 'edit_others_posts',
					'assign_terms' => 'edit_published_posts'
				)
			)
		);
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

	// programatically sets specs for the post
	// it will append new specs to the old ones
	public function set_post_specs( $post_id, $specs, $create = FALSE )
	{
		$post = get_post( $post_id );
		if ( ! $post )
			return FALSE;

		$meta = $this->get_postmeta( $post_id, FALSE, array() );
		$spec_terms = gEditorialHelper::getTerms( $this->module->constants['specs_tax'], FALSE, true, 'slug' );
		$terms = array();

		foreach ( $meta as $meta_row )
			$terms[] = intval( $meta_row['spec_term_id'] );

		$counter = 1;
		foreach ( $specs as $spec ) {
			$row = array();

			if ( isset( $spec_terms[$spec['name']] ) ) {
				$row['spec_term_id'] = $spec_terms[$spec['name']]->term_id;
				$terms[] = (int) $spec_terms[$spec['name']]->term_id;

			} else if ( $create ) { // create new term object
				if ( isset( $spec['title'] ) && $spec['title'] )
					$new_term = wp_insert_term( $spec['title'], $this->module->constants['specs_tax'], array( 'slug' => $spec['name'] ) );
				else
					$new_term = wp_insert_term( $spec['name'], $this->module->constants['specs_tax'] );

				if ( is_wp_error( $new_term ) ) {
					$row['spec_title'] = $this->kses( $spec['name'] );
				} else {

					//$spec_terms[$new_term['term_id']] = get_term_by( 'id', $new_term['term_id'], $this->module->constants['specs_tax'] );
					$new_tetm_obj = get_term_by( 'id', $new_term['term_id'], $this->module->constants['specs_tax'] );
					$spec_terms[$new_tetm_obj->slug] = $new_tetm_obj;

					$row['spec_term_id'] = $spec_terms[$spec['name']]->term_id;
					$terms[] = (int) $spec_terms[$spec['name']]->term_id;
				}

			} else {
				$row['spec_title'] = $this->kses( $spec['name'] );
			}

			if ( isset( $spec['val'] ) && ! empty( $spec['val'] ) )
				//$row['spec_value'] = $this->kses( $spec['val'] );
				$row['spec_value'] = $spec['val'];

			if ( isset( $spec['order'] ) && ! empty( $spec['order'] ) )
				$row['spec_order'] = $this->intval( $spec['order'] )+100;
			else
				$row['spec_order'] = $counter+100;


			if ( isset( $row['spec_term_id'] ) ) {
				foreach ( $meta as $meta_row_key => $meta_row ) {
					if ( isset( $meta_row['spec_term_id'] ) && $row['spec_term_id'] == $meta_row['spec_term_id'] ) {
						unset( $meta[$meta_row_key] );
						break;
					}
				}
			}

			$meta[$row['spec_order']] = $row;
			$counter++;
		}

		if ( count( $meta ) ) {

			//$the_list = wp_list_pluck( $meta, 'spec_order' );
			//$meta = array_combine( $the_list, $meta );
			ksort( $meta );

			$this->set_meta( $post_id, $meta );
			wp_set_object_terms( $post_id, ( count( $terms ) ? $terms : null ), $this->module->constants['specs_tax'], FALSE );

			return $post_id;
		}

		return FALSE;
	}

	private function sanitize_post_meta( $postmeta, $fields, $post_id, $post_type )
	{
		if ( ! wp_verify_nonce( @ $_REQUEST['_geditorial_specs_post_main'], 'geditorial_specs_post_main' ) )
			return $postmeta;

		if ( ! isset( $_POST['geditorial-specs_term_id'] ) )
			return $postmeta;

		$prefix = 'geditorial-specs-';
		$postmeta = $terms = array();

		foreach ( $_POST['geditorial-specs_term_id'] as $offset => $term_id )
			if ( $term_id && '-1' != $term_id )
				$terms[$offset] = intval( $term_id );

		wp_set_object_terms( $post_id, ( count( $terms ) ? $terms : null ), $this->module->constants['specs_tax'], FALSE );

		foreach ( $terms as $offset => $term ) {

			$postmeta[$offset]['spec_term_id'] = $term;

			foreach ( $fields as $field ) {
				switch ( $field ) {
					case 'spec_order' :
						if ( isset( $_POST[$prefix.$field][$offset] ) && '0' != $_POST[$prefix.$field][$offset] )
							$postmeta[$offset][$field] = $this->intval( $_POST[$prefix.$field][$offset] );
						else if ( isset( $postmeta[$offset][$field] ) && isset( $_POST[$prefix.$field][$offset] )  )
							unset( $postmeta[$offset][$field] );
					break;
					case 'spec_title' :
					case 'spec_value' :
						if ( isset( $_POST[$prefix.$field][$offset] )
							&& strlen( $_POST[$prefix.$field][$offset] ) > 0
							&& $this->get_string( $field, $post_type ) !== $_POST[$prefix.$field][$offset] )
								$postmeta[$offset][$field] = $this->kses( $_POST[$prefix.$field][$offset] );
						else if ( isset( $postmeta[$offset][$field] ) && isset( $_POST[$prefix.$field][$offset] ) )
							unset( $postmeta[$offset][$field] );
					break;
				}
			}
		}

		$the_list = wp_list_pluck( $postmeta, 'spec_order' );
		$postmeta = array_combine( $the_list, $postmeta );
		krsort( $postmeta );

		$postmeta = apply_filters( 'geditorial_specs_sanitize_post_meta', $postmeta, $fields, $post_id, $post_type );
		return $postmeta;
	}

	public function remove_meta_boxes( $post_type, $post )
	{
		if ( ! in_array( $post_type, $this->post_types() ) )
			return;

		// if ( ! current_user_can( 'edit_published_posts' ) )
			remove_meta_box( 'tagsdiv-'.$this->module->constants['specs_tax'], $post_type, 'side' );
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( ! in_array( $post_type, $this->post_types() ) )
			return;

		add_meta_box(
			'geditorial-specs',
			$this->get_meta_box_title( $post_type ),
			array( $this, 'do_meta_box' ),
			$post_type,
			'side' );
	}

	public function do_meta_box( $post )
	{
		echo '<div class="geditorial-admin-wrap-metabox specs">';
		$specs = gEditorialHelper::getTerms( $this->module->constants['specs_tax'], $post->ID, true );
		do_action( 'geditorial_specs_meta_box', $post, $specs );
		echo '</div>';
	}

	public function geditorial_specs_meta_box( $post, $the_terms )
	{
		$fields = $this->post_type_fields( $post->post_type );
		$metas  = $this->get_postmeta( $post->ID, false, array() );

		$handle = '<span class="item-handle dashicons dashicons-editor-expand" title="'._x( 'Sort me!', 'Sortable handler title attr', GEDITORIAL_TEXTDOMAIN ).'"></span>';
		$delete = '<span class="item-delete dashicons dashicons-trash" title="'._x( 'Trash me!', 'Sortable trash title attr', GEDITORIAL_TEXTDOMAIN ).'"></span>';

		echo '<ol class="geditorial-specs-list">';
		foreach ( $metas as $order => $meta ) {

			echo '<li><div class="item-head">';

				echo $handle.'<span class="item-excerpt">';
					$title = ( isset( $meta['spec_title'] ) && $meta['spec_title'] ) ? $meta['spec_title'] : ( isset( $meta['spec_term_id'] ) && $meta['spec_term_id'] ? $the_terms[$meta['spec_term_id']]->name : __( 'UnKnown Field',  GEDITORIAL_TEXTDOMAIN ) );
					$title .= ( isset( $meta['spec_value'] ) && $meta['spec_value'] ? ': '.$meta['spec_value'] : '' );
					echo mb_substr( $title, 0, 28, 'UTF-8' );
				echo '</span>'.$delete;

			echo '</div><div class="item-body"><div class="field-wrap-group">';

			$this->geditorial_specs_meta_box_item( $order, $fields, $post, $meta );

			echo '<div class="field-wrap field-wrap-select">';

			wp_dropdown_categories( array(
				'taxonomy'         => $this->module->constants['specs_tax'],
				'selected'         => ( isset( $meta['spec_term_id'] ) ? $the_terms[$meta['spec_term_id']]->term_id : 0 ),
				'show_option_none' => $this->get_string( 'select_specs', $post->post_type, 'misc' ),
				'name'             => 'geditorial-specs_term_id[]',
				// 'id'               => 'geditorial-specs-terms-'.$order,
				'class'            => 'geditorial-admin-dropbown item-dropdown',
				'show_count'       => 0,
				'hide_empty'       => 0,
				'echo'             => 1,
			) );

			echo '</div></div></div></li>';
		}
		echo '</ol>';

		echo '<ul class="geditorial-specs-new">';
			echo '<li>';
			echo '<div class="item-head">';
				echo $handle.'<span class="item-excerpt">';
					// echo '&hellip;';
				echo '</span>'.$delete;
			echo '</div><div class="item-body">';

			echo '<div class="field-wrap-group">';

				$this->geditorial_specs_meta_box_item( '-1', $fields, $post );

				echo '<div class="field-wrap field-wrap-select">';
				wp_dropdown_categories( array(
					'taxonomy'         => $this->module->constants['specs_tax'],
					'selected'         => 0,
					'show_option_none' => $this->get_string( 'select_specs', $post->post_type, 'misc' ),
					'name'             => 'geditorial-specs_term_id[]',
					// 'id'               => 'geditorial-specs-terms--1',
					'id'               => FALSE,
					'class'            => 'geditorial-admin-dropbown item-dropdown item-dropdown-new',
					'show_count'       => 0,
					'hide_empty'       => 0,
					'echo'             => 1,
				) );

		echo '</div></div></div></li></ul>';

		do_action( 'geditorial_specs_box_after', $this->module, $post, $fields );
		wp_nonce_field( 'geditorial_specs_post_main', '_geditorial_specs_post_main' );
	}

	public function geditorial_specs_meta_box_item( $order, $fields, $post, $meta = array() )
	{
		$field = 'spec_value';
		if ( in_array( $field, $fields )
			&& self::user_can( 'view', $field ) ) {

			$title = $this->get_string( $field, $post->post_type );
			$html = gEditorialHelper::html( 'textarea', array(
				'class'        => 'field-textarea textarea-autosize',
				'name'         => 'geditorial-specs-spec_value[]',
				'title'        => $title,
				'placeholder'  => $title,
				'readonly'     => ! $this->user_can( 'edit', $field ),
			), isset( $meta[$field] ) ? esc_textarea( $meta[$field] ) : '' );

			echo gEditorialHelper::html( 'div', array(
				'class' => 'field-wrap field-wrap-textarea',
			), $html );
		}

		$field = 'spec_title';
		if ( in_array( $field, $fields )
			&& self::user_can( 'view', $field ) ) {

			$title = $this->get_string( $field, $post->post_type );
			$html = gEditorialHelper::html( 'input', array(
				'type'         => 'text',
				'class'        => 'field-inputtext',
				'name'         => 'geditorial-specs-spec_title[]',
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

		echo '<input type="hidden" class="item-order" name="geditorial-specs-spec_order[]" value="'.$order.'" />';
	}

	public function shortcode_specs( $atts, $content = null, $tag = '' )
	{
		global $post;
		$error = false;

		$args = shortcode_atts( array(
			'slug'      => '',
			'id'        => '',
			'title'     => '<a href="%2$s" title="%3$s">%1$s</a>',
			'title_tag' => 'h3',
			'list'      => 'ul',
			'limit'     => -1,
			'hide'      => -1, // more than this will be hided
			'future'    => 'on',
			'single'    => 'on',
			'li_before' => '',
			'orderby'   => 'order',
			'order'     => 'ASC',
			'cb'        => FALSE,
			'exclude'   => TRUE, // or array
			'before'    => '',
			'after'     => '',
			'context'   => NULL,
		), $atts, $this->module->constants['specs_shortcode'] );

		if ( FALSE === $args['context'] ) // bailing
			return NULL;

		$the_terms = gEditorialHelper::getTerms( $this->module->constants['specs_tax'], $post->ID, TRUE );
		$metas = $this->get_postmeta( $post->ID, FALSE, array() );
		$html = '';

		// TODO: use table helper
		$html .= '<table class="table table-striped geditorial-specs">';
		foreach ( $metas as $order => $meta ) {
			$html .= '<tr><td>';
				$html .= ( isset( $meta['spec_title'] ) && $meta['spec_title'] ) ? $meta['spec_title'] : ( isset( $meta['spec_term_id'] ) && $meta['spec_term_id'] ? $the_terms[$meta['spec_term_id']]->name : __( 'UnKnown Field',  GEDITORIAL_TEXTDOMAIN ) );
			$html .= '</td><td>';
				// TODO : add filter for each spec
				$html .= isset( $meta['spec_value'] ) ? $this->kses( $meta['spec_value'] ) : '';
			$html .= '</td></tr>';
		}
		$html .= '</table>';

		return $html;
	}

	public function shortcode_multiple_specs( $atts, $content = NULL, $tag = '' )
	{
		global $post;

		$args = shortcode_atts( array(
			'ids'       => array(),
			'title'     => '',
			'title_tag' => 'h3',
			'class'     => '',
			'order'     => 'ASC',
			'orderby'   => 'term_order, name',
			'exclude'   => true, // or array
			'before'    => '',
			'after'     => '',
			'context'   => NULL,
			'args'      => array(),
		), $atts, $this->module->constants['multiple_specs_shortcode'] );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( empty( $args['ids'] ) || ! count( $args['ids'] ) ) {
			$terms = wp_get_object_terms( (int) $post->ID, $this->module->constants['specs_tax'], array(
				'order'   => $args['order'],
				'orderby' => $args['orderby'],
				'fields'  => 'ids',
			) );
			$args['ids'] = is_wp_error( $terms ) ? array() : $terms;
		}

		$output = '';
		foreach ( $args['ids'] as $id )
			$output .= $this->shortcode_specs( array_merge( array(
				'id'        => $id,
				'title_tag' => 'h4',
			), $args['args'] ), NULL, $this->module->constants['specs_shortcode'] );

		if ( ! empty( $output ) ) {
			if( $args['title'] )
				$output = '<'.$args['title_tag'].' class="post-specs-wrap-title">'.$args['title'].'</'.$args['title_tag'].'>'.$output;
			if ( ! is_null( $args['context'] ) )
				$output = '<div class="multiple-specs-'.sanitize_html_class( $args['context'], 'general' ).'">'.$output.'</div>';
			return $args['before'].$output.$args['after'];
		}
		
		return NULL;
	}
}
