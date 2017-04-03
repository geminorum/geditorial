<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialSpecs extends gEditorialModuleCore
{

	public $meta_key = '_ge_specs';
	protected $field_type = 'specs';

	public static function module()
	{
		return array(
			'name'  => 'specs',
			'title' => _x( 'Specifications', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Post Specifications Management', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'editor-ul',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'posttypes_option' => 'posttypes_option',
			'fields_option'    => 'fields_option',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'specs_tax'                => 'specs',
			'specs_shortcode'          => 'specs',
			'multiple_specs_shortcode' => 'multiple_specs',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'titles' => array(
				'post' => array(
					'spec_title' => _x( 'Title', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
					'spec_order' => _x( 'Order', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
					'spec_value' => _x( 'Description', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'descriptions' => array(
				'post' => array(
					'spec_title' => _x( 'In Specifications Title', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
					'spec_order' => _x( 'In Specifications Order', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
					'spec_value' => _x( 'In Specifications Description', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'misc' => array(
				'post' => array(
					'meta_box_title'   => _x( 'Specifications', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
					'column_title'     => _x( 'Specifications', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
					'show_option_none' => _x( '&mdash; Choose a Specification &mdash;', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'noops' => array(
				'specs_tax' => _nx_noop( 'Specification', 'Specifications', 'Modules: Specs: Noop', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	protected function get_global_fields()
	{
		return array(
			$this->constant( 'post_cpt' ) => array(
				'spec_title' => TRUE,
				'spec_order' => TRUE,
				'spec_value' => TRUE,
			),
		);
	}

	public function setup( $partials = array() )
	{
		parent::setup();

		if ( is_admin() ) {
			add_action( 'save_post', array( $this, 'save_post' ), 20, 2 );
		}
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'specs_tax' );

		foreach ( $this->post_types() as $post_type )
			$this->add_post_type_fields( $post_type, $this->fields[$this->constant( 'post_cpt' )], 'specs' );

		// add_shortcode( $this->constant( 'specs_shortcode' ), array( $this, 'shortcode_specs' ) );
		// add_shortcode( $this->constant( 'multiple_specs_shortcode' ), array( $this, 'shortcode_multiple_specs' ) );
	}

	public function current_screen( $screen )
	{
		if ( 'post' == $screen->base
			&& in_array( $screen->post_type, $this->post_types() ) ) {

			add_meta_box(
				'geditorial-specs',
				$this->get_meta_box_title( 'specs_tax', $this->get_url_tax_edit( 'specs_tax' ), 'edit_others_posts' ),
				array( $this, 'do_meta_box' ),
				$screen,
				'side',
				'high'
			);

			wp_register_script( 'jquery-sortable',
				GEDITORIAL_URL.'assets/packages/jquery-sortable/jquery-sortable-min.js',
				array( 'jquery' ),
				'0.9.13',
				TRUE );

			$this->enqueue_asset_js( array(), 'specs.'.$screen->base, array( 'jquery-sortable' ) );

			// internal
			add_action( 'geditorial_specs_meta_box', array( $this, 'geditorial_specs_meta_box' ), 5, 2 );
			// add_action( 'geditorial_specs_meta_box_item', array( $this, 'geditorial_specs_meta_box_item' ), 5, 5 );
		}
	}

	public function save_post( $post_id, $post )
	{
		if ( ! $this->is_save_post( $post, $this->post_types() ) )
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
		if ( ! $post = get_post( $post_id ) )
			return FALSE;

		$meta = $this->get_postmeta( $post_id, FALSE, array() );
		$spec_terms = gEditorialWPTaxonomy::getTerms( $this->constant( 'specs_tax' ), FALSE, TRUE, 'slug' );
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
					$new_term = wp_insert_term( $spec['title'], $this->constant( 'specs_tax' ), array( 'slug' => $spec['name'] ) );
				else
					$new_term = wp_insert_term( $spec['name'], $this->constant( 'specs_tax' ) );

				if ( is_wp_error( $new_term ) ) {
					$row['spec_title'] = gEditorialHelper::kses( $spec['name'] );
				} else {

					//$spec_terms[$new_term['term_id']] = get_term_by( 'id', $new_term['term_id'], $this->constant( 'specs_tax' ) );
					$new_tetm_obj = get_term_by( 'id', $new_term['term_id'], $this->constant( 'specs_tax' ) );
					$spec_terms[$new_tetm_obj->slug] = $new_tetm_obj;

					$row['spec_term_id'] = $spec_terms[$spec['name']]->term_id;
					$terms[] = (int) $spec_terms[$spec['name']]->term_id;
				}

			} else {
				$row['spec_title'] = gEditorialHelper::kses( $spec['name'] );
			}

			if ( isset( $spec['val'] ) && ! empty( $spec['val'] ) )
				$row['spec_value'] = gEditorialHelper::kses( $spec['val'], 'text' );

			if ( isset( $spec['order'] ) && ! empty( $spec['order'] ) )
				$row['spec_order'] = gEditorialNumber::intval( $spec['order'] ) + 100;
			else
				$row['spec_order'] = $counter + 100;

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

			ksort( $meta );

			$this->set_meta( $post_id, $meta );
			wp_set_object_terms( $post_id, ( count( $terms ) ? $terms : null ), $this->constant( 'specs_tax' ), FALSE );

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

		wp_set_object_terms( $post_id, ( count( $terms ) ? $terms : null ), $this->constant( 'specs_tax' ), FALSE );

		foreach ( $terms as $offset => $term ) {

			$postmeta[$offset]['spec_term_id'] = $term;

			foreach ( $fields as $field ) {
				switch ( $field ) {
					case 'spec_order' :
						if ( isset( $_POST[$prefix.$field][$offset] ) && '0' != $_POST[$prefix.$field][$offset] )
							$postmeta[$offset][$field] = gEditorialNumber::intval( $_POST[$prefix.$field][$offset] );
						else if ( isset( $postmeta[$offset][$field] ) && isset( $_POST[$prefix.$field][$offset] )  )
							unset( $postmeta[$offset][$field] );
					break;
					case 'spec_title' :
					case 'spec_value' :
						if ( isset( $_POST[$prefix.$field][$offset] )
							&& strlen( $_POST[$prefix.$field][$offset] ) > 0
							&& $this->get_string( $field, $post_type ) !== $_POST[$prefix.$field][$offset] )
								$postmeta[$offset][$field] = gEditorialHelper::kses( $_POST[$prefix.$field][$offset], 'text' );
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

	public function do_meta_box( $post )
	{
		echo '<div class="geditorial-admin-wrap-metabox -specs">';

		$specs = gEditorialWPTaxonomy::getTerms( $this->constant( 'specs_tax' ), $post->ID, TRUE );

		do_action( 'geditorial_specs_meta_box', $post, $specs );

		echo '</div>';
	}

	// FIXME: convert into api and move up to MetaBox class
	public function geditorial_specs_meta_box( $post, $the_terms )
	{
		$tax = $this->constant( 'specs_tax' );

		if ( ! gEditorialWPTaxonomy::hasTerms( $tax ) )
			return gEditorialMetaBox::fieldEmptyTaxonomy( $tax );

		$fields = $this->post_type_fields( $post->post_type );
		$metas  = $this->get_postmeta( $post->ID, FALSE, array() );

		$handle = '<span class="item-handle dashicons dashicons-editor-expand" title="'._x( 'Sort Me!', 'Modules: Specs: Sortable handler title attr', GEDITORIAL_TEXTDOMAIN ).'"></span>';
		$delete = '<span class="item-delete dashicons dashicons-trash" title="'._x( 'Trash Me!', 'Modules: Specs: Sortable trash title attr', GEDITORIAL_TEXTDOMAIN ).'"></span>';

		echo '<ol class="geditorial-specs-list">';
		foreach ( $metas as $order => $meta ) {

			echo '<li><div class="item-head">';

				echo $handle.'<span class="item-excerpt">';
					$title = ( isset( $meta['spec_title'] ) && $meta['spec_title'] ) ? $meta['spec_title'] : ( isset( $meta['spec_term_id'] ) && $meta['spec_term_id'] ? $the_terms[$meta['spec_term_id']]->name : _x( 'Unknown Field', 'Modules: Specs',  GEDITORIAL_TEXTDOMAIN ) );
					$title .= ( isset( $meta['spec_value'] ) && $meta['spec_value'] ? ': '.$meta['spec_value'] : '' );
					echo gEditorialCoreText::subStr( $title, 0, 28 );
				echo '</span>'.$delete;

			echo '</div><div class="item-body"><div class="field-wrap-group">';

			$this->geditorial_specs_meta_box_item( $order, $fields, $post, $meta );

			echo '<div class="field-wrap field-wrap-select">';

			wp_dropdown_categories( array(
				'taxonomy'         => $tax,
				'selected'         => ( isset( $meta['spec_term_id'] ) ? $the_terms[$meta['spec_term_id']]->term_id : 0 ),
				'show_option_none' => $this->get_string( 'show_option_none', $post->post_type, 'misc' ),
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
					'taxonomy'         => $tax,
					'selected'         => 0,
					'show_option_none' => $this->get_string( 'show_option_none', $post->post_type, 'misc' ),
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
			$html = gEditorialHTML::tag( 'textarea', array(
				'class'        => 'field-textarea textarea-autosize',
				'name'         => 'geditorial-specs-spec_value[]',
				'title'        => $title,
				'placeholder'  => $title,
				'readonly'     => ! $this->user_can( 'edit', $field ),
			), isset( $meta[$field] ) ? esc_textarea( $meta[$field] ) : '' );

			echo gEditorialHTML::tag( 'div', array(
				'class' => 'field-wrap field-wrap-textarea',
			), $html );
		}

		$field = 'spec_title';
		if ( in_array( $field, $fields )
			&& self::user_can( 'view', $field ) ) {

			$title = $this->get_string( $field, $post->post_type );
			$html = gEditorialHTML::tag( 'input', array(
				'type'         => 'text',
				'class'        => 'field-inputtext',
				'name'         => 'geditorial-specs-spec_title[]',
				'value'        => isset( $meta[$field] ) ? $meta[$field] : '',
				'title'        => $title,
				'placeholder'  => $title,
				'readonly'     => ! $this->user_can( 'edit', $field ),
				'autocomplete' => 'off',
			) );

			echo gEditorialHTML::tag( 'div', array(
				'class' => 'field-wrap field-wrap-inputtext',
			), $html );
		}

		echo '<input type="hidden" class="item-order" name="geditorial-specs-spec_order[]" value="'.$order.'" />';
	}

	public function shortcode_specs( $atts, $content = null, $tag = '' )
	{
		global $post;
		$error = FALSE;

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
		), $atts, $this->constant( 'specs_shortcode' ) );

		if ( FALSE === $args['context'] ) // bailing
			return NULL;

		$the_terms = gEditorialWPTaxonomy::getTerms( $this->constant( 'specs_tax' ), $post->ID, TRUE );
		$metas = $this->get_postmeta( $post->ID, FALSE, array() );
		$html = '';

		// FIXME: use table helper
		$html .= '<table class="table table-striped geditorial-specs">';
		foreach ( $metas as $order => $meta ) {
			$html .= '<tr><td>';
				$html .= ( isset( $meta['spec_title'] ) && $meta['spec_title'] ) ? $meta['spec_title'] : ( isset( $meta['spec_term_id'] ) && $meta['spec_term_id'] ? $the_terms[$meta['spec_term_id']]->name : _x( 'Unknown Field', 'Modules: Specs',  GEDITORIAL_TEXTDOMAIN ) );
			$html .= '</td><td>';
				// FIXME: add filter for each spec
				$html .= isset( $meta['spec_value'] ) ? $meta['spec_value'] : '';
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
			'exclude'   => TRUE, // or array
			'before'    => '',
			'after'     => '',
			'context'   => NULL,
			'args'      => array(),
		), $atts, $this->constant( 'multiple_specs_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( empty( $args['ids'] ) || ! count( $args['ids'] ) ) {
			$terms = wp_get_object_terms( (int) $post->ID, $this->constant( 'specs_tax' ), array(
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
			), $args['args'] ), NULL, $this->constant( 'specs_shortcode' ) );

		if ( ! empty( $output ) ) {
			if ( $args['title'] )
				$output = '<'.$args['title_tag'].' class="post-specs-wrap-title">'.$args['title'].'</'.$args['title_tag'].'>'.$output;
			if ( ! is_null( $args['context'] ) )
				$output = '<div class="multiple-specs-'.sanitize_html_class( $args['context'], 'general' ).'">'.$output.'</div>';
			return $args['before'].$output.$args['after'];
		}

		return NULL;
	}
}
