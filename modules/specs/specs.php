<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\WordPress\Taxonomy;

class Specs extends gEditorial\Module
{

	public $meta_key = '_ge_specs';
	protected $field_type = 'specs';

	public static function module()
	{
		return [
			'name'  => 'specs',
			'title' => _x( 'Specifications', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Post Specifications', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'editor-ul',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'fields_option'    => 'fields_option',
		];
	}

	protected function get_global_constants()
	{
		return [
			'specs_tax'                => 'specs',
			'specs_shortcode'          => 'specs',
			'multiple_specs_shortcode' => 'multiple_specs',
		];
	}

	protected function get_global_strings()
	{
		return [
			'titles' => [
				'spec_title' => _x( 'Title', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
				'spec_order' => _x( 'Order', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
				'spec_value' => _x( 'Description', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
			],
			'descriptions' => [
				'spec_title' => _x( 'In Specifications Title', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
				'spec_order' => _x( 'In Specifications Order', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
				'spec_value' => _x( 'In Specifications Description', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
			],
			'misc' => [
				'column_title'     => _x( 'Specifications', 'Modules: Specs: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'show_option_none' => _x( '&mdash; Choose a Specification &mdash;', 'Modules: Specs', GEDITORIAL_TEXTDOMAIN ),
			],
			'noops' => [
				'specs_tax' => _nx_noop( 'Specification', 'Specifications', 'Modules: Specs: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
		];
	}

	protected function get_global_fields()
	{
		return [
			$this->constant( 'post_cpt' ) => [
				'spec_title' => TRUE,
				'spec_order' => TRUE,
				'spec_value' => TRUE,
			],
		];
	}

	public function setup( $partials = [] )
	{
		parent::setup();

		if ( is_admin() )
			$this->action( 'save_post', 2, 20 );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'specs_tax' );

		foreach ( $this->post_types() as $post_type )
			$this->add_post_type_fields( $post_type, $this->fields[$this->constant( 'post_cpt' )], 'specs' );

		// add_shortcode( $this->constant( 'specs_shortcode' ), [ $this, 'shortcode_specs' ] );
		// add_shortcode( $this->constant( 'multiple_specs_shortcode' ), [ $this, 'shortcode_multiple_specs' ] );
	}

	public function current_screen( $screen )
	{
		if ( 'post' == $screen->base
			&& in_array( $screen->post_type, $this->post_types() ) ) {

			add_meta_box( $this->classs(),
				$this->get_meta_box_title_tax( 'specs_tax' ),
				[ $this, 'do_meta_box' ],
				$screen,
				'side',
				'high'
			);

			$sortable = Helper::registerScriptPackage( 'jquery-sortable', NULL, [ 'jquery' ], '0.9.13' );
			$this->enqueue_asset_js( [], $screen, [ $sortable ] );

			// internal
			add_action( 'geditorial_specs_meta_box', [ $this, 'geditorial_specs_meta_box' ], 5, 2 );
			// add_action( 'geditorial_specs_meta_box_item', [ $this, 'geditorial_specs_meta_box_item' ], 5, 5 );
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

		$meta = $this->get_postmeta( $post_id, FALSE, [] );
		$spec_terms = Taxonomy::getTerms( $this->constant( 'specs_tax' ), FALSE, TRUE, 'slug' );
		$terms = [];

		foreach ( $meta as $meta_row )
			$terms[] = intval( $meta_row['spec_term_id'] );

		$counter = 1;
		foreach ( $specs as $spec ) {
			$row = [];

			if ( isset( $spec_terms[$spec['name']] ) ) {
				$row['spec_term_id'] = $spec_terms[$spec['name']]->term_id;
				$terms[] = (int) $spec_terms[$spec['name']]->term_id;

			} else if ( $create ) { // create new term object
				if ( isset( $spec['title'] ) && $spec['title'] )
					$new_term = wp_insert_term( $spec['title'], $this->constant( 'specs_tax' ), [ 'slug' => $spec['name'] ] );
				else
					$new_term = wp_insert_term( $spec['name'], $this->constant( 'specs_tax' ) );

				if ( is_wp_error( $new_term ) ) {
					$row['spec_title'] = Helper::kses( $spec['name'] );
				} else {

					//$spec_terms[$new_term['term_id']] = get_term_by( 'id', $new_term['term_id'], $this->constant( 'specs_tax' ) );
					$new_tetm_obj = get_term_by( 'id', $new_term['term_id'], $this->constant( 'specs_tax' ) );
					$spec_terms[$new_tetm_obj->slug] = $new_tetm_obj;

					$row['spec_term_id'] = $spec_terms[$spec['name']]->term_id;
					$terms[] = (int) $spec_terms[$spec['name']]->term_id;
				}

			} else {
				$row['spec_title'] = Helper::kses( $spec['name'] );
			}

			if ( isset( $spec['val'] ) && ! empty( $spec['val'] ) )
				$row['spec_value'] = Helper::kses( $spec['val'], 'text' );

			if ( isset( $spec['order'] ) && ! empty( $spec['order'] ) )
				$row['spec_order'] = Number::intval( $spec['order'] ) + 100;
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
		if ( ! $this->nonce_verify( 'post_main' ) )
			return $postmeta;

		if ( ! isset( $_POST['geditorial-specs_term_id'] ) )
			return $postmeta;

		$prefix = 'geditorial-specs-';
		$postmeta = $terms = [];

		foreach ( $_POST['geditorial-specs_term_id'] as $offset => $term_id )
			if ( $term_id && '-1' != $term_id )
				$terms[$offset] = intval( $term_id );

		wp_set_object_terms( $post_id, ( count( $terms ) ? $terms : null ), $this->constant( 'specs_tax' ), FALSE );

		foreach ( $terms as $offset => $term ) {

			$postmeta[$offset]['spec_term_id'] = $term;

			foreach ( $fields as $field ) {

				switch ( $field ) {

					case 'spec_order':

						if ( isset( $_POST[$prefix.$field][$offset] ) && '0' != $_POST[$prefix.$field][$offset] )
							$postmeta[$offset][$field] = Number::intval( $_POST[$prefix.$field][$offset] );

						else if ( isset( $postmeta[$offset][$field] ) && isset( $_POST[$prefix.$field][$offset] )  )
							unset( $postmeta[$offset][$field] );

					break;
					case 'spec_title':
					case 'spec_value':

						if ( isset( $_POST[$prefix.$field][$offset] )
							&& strlen( $_POST[$prefix.$field][$offset] ) > 0
							&& $this->get_string( $field, $post_type ) !== $_POST[$prefix.$field][$offset] )
								$postmeta[$offset][$field] = Helper::kses( $_POST[$prefix.$field][$offset], 'text' );

						else if ( isset( $postmeta[$offset][$field] ) && isset( $_POST[$prefix.$field][$offset] ) )
							unset( $postmeta[$offset][$field] );
				}
			}
		}

		$the_list = wp_list_pluck( $postmeta, 'spec_order' );
		$postmeta = array_combine( $the_list, $postmeta );
		krsort( $postmeta );

		return $this->filters( 'sanitize_post_meta', $postmeta, $fields, $post_id, $post_type );
	}

	public function do_meta_box( $post )
	{
		echo '<div class="geditorial-admin-wrap-metabox -specs">';

		$terms = Taxonomy::getTerms( $this->constant( 'specs_tax' ), $post->ID, TRUE );
		$this->actions( 'meta_box', $post, $terms );

		echo '</div>';
	}

	// FIXME: convert into api and move up to MetaBox class
	public function geditorial_specs_meta_box( $post, $the_terms )
	{
		$tax = $this->constant( 'specs_tax' );

		if ( ! Taxonomy::hasTerms( $tax ) )
			return MetaBox::fieldEmptyTaxonomy( $tax );

		$fields = $this->post_type_fields( $post->post_type );
		$metas  = $this->get_postmeta( $post->ID, FALSE, [] );

		$handle = '<span data-icon="dashicons" class="item-handle dashicons dashicons-move" title="'._x( 'Sort me!', 'Modules: Specs: Sortable Handler', GEDITORIAL_TEXTDOMAIN ).'"></span>';
		$delete = '<span data-icon="dashicons" class="item-delete dashicons dashicons-trash" title="'._x( 'Trash me!', 'Modules: Specs: Sortable Trash', GEDITORIAL_TEXTDOMAIN ).'"></span>';

		echo '<ol class="geditorial-specs-list">';
		foreach ( $metas as $order => $meta ) {

			echo '<li><div class="item-head">';

				echo $handle.'<span class="item-excerpt">';
					$title = ( isset( $meta['spec_title'] ) && $meta['spec_title'] ) ? $meta['spec_title'] : ( isset( $meta['spec_term_id'] ) && $meta['spec_term_id'] ? $the_terms[$meta['spec_term_id']]->name : _x( 'Unknown Field', 'Modules: Specs',  GEDITORIAL_TEXTDOMAIN ) );
					$title .= ( isset( $meta['spec_value'] ) && $meta['spec_value'] ? ': '.$meta['spec_value'] : '' );
					echo Text::subStr( $title, 0, 28 );
				echo '</span>'.$delete;

			echo '</div><div class="item-body"><div class="field-wrap-group">';

			$this->geditorial_specs_meta_box_item( $order, $fields, $post, $meta );

			$html = wp_dropdown_categories( [
				'taxonomy'         => $tax,
				'selected'         => ( isset( $meta['spec_term_id'] ) ? $the_terms[$meta['spec_term_id']]->term_id : 0 ),
				'show_option_none' => $this->get_string( 'show_option_none', $post->post_type, 'misc' ),
				'name'             => 'geditorial-specs_term_id[]',
				// 'id'               => 'geditorial-specs-terms-'.$order,
				'class'            => 'geditorial-admin-dropbown item-dropdown',
				'show_count'       => 0,
				'hide_empty'       => 0,
				'echo'             => 0,
			] );

			echo HTML::wrap( $html, 'field-wrap field-wrap-select' );

			echo '</div></div></li>';
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

				// FIXME: we need custom for disabled options
				$html = wp_dropdown_categories( [
					'taxonomy'         => $tax,
					'selected'         => 0,
					'show_option_none' => $this->get_string( 'show_option_none', $post->post_type, 'misc' ),
					'name'             => 'geditorial-specs_term_id[]',
					// 'id'               => 'geditorial-specs-terms--1',
					'id'               => FALSE,
					'class'            => 'geditorial-admin-dropbown item-dropdown item-dropdown-new',
					'show_count'       => 0,
					'hide_empty'       => 0,
					'echo'             => 0,
				] );

				echo HTML::wrap( $html, 'field-wrap field-wrap-select' );

		echo '</div></div></li></ul>';

		$this->actions( 'box_after', $this->module, $post, $fields );
		$this->nonce_field( 'post_main' );
	}

	public function geditorial_specs_meta_box_item( $order, $fields, $post, $meta = [] )
	{
		$field = 'spec_value';
		if ( in_array( $field, $fields ) ) {

			$title = $this->get_string( $field, $post->post_type );

			$html = HTML::tag( 'textarea', [
				'class'       => 'textarea-autosize',
				'name'        => 'geditorial-specs-spec_value[]',
				'title'       => $title,
				'placeholder' => $title,
			], isset( $meta[$field] ) ? esc_textarea( $meta[$field] ) : '' );

			echo HTML::wrap( $html, 'field-wrap field-wrap-textarea' );
		}

		$field = 'spec_title';
		if ( in_array( $field, $fields ) ) {

			$title = $this->get_string( $field, $post->post_type );

			$html = HTML::tag( 'input', [
				'type'         => 'text',
				'name'         => 'geditorial-specs-spec_title[]',
				'value'        => isset( $meta[$field] ) ? $meta[$field] : '',
				'title'        => $title,
				'placeholder'  => $title,
				'autocomplete' => 'off',
			] );

			echo HTML::wrap( $html, 'field-wrap field-wrap-inputtext' );
		}

		echo '<input type="hidden" class="item-order" name="geditorial-specs-spec_order[]" value="'.$order.'" />';
	}

	public function shortcode_specs( $atts, $content = null, $tag = '' )
	{
		global $post;
		$error = FALSE;

		$args = shortcode_atts( [
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
		], $atts, $this->constant( 'specs_shortcode' ) );

		if ( FALSE === $args['context'] ) // bailing
			return NULL;

		$the_terms = Taxonomy::getTerms( $this->constant( 'specs_tax' ), $post->ID, TRUE );
		$metas     = $this->get_postmeta( $post->ID, FALSE, [] );
		$html      = '';

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

		$args = shortcode_atts( [
			'ids'       => [],
			'title'     => '',
			'title_tag' => 'h3',
			'class'     => '',
			'order'     => 'ASC',
			'orderby'   => 'term_order, name',
			'exclude'   => TRUE, // or array
			'before'    => '',
			'after'     => '',
			'context'   => NULL,
			'args'      => [],
		], $atts, $this->constant( 'multiple_specs_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( empty( $args['ids'] ) || ! count( $args['ids'] ) ) {
			$terms = wp_get_object_terms( (int) $post->ID, $this->constant( 'specs_tax' ), [
				'order'   => $args['order'],
				'orderby' => $args['orderby'],
				'fields'  => 'ids',
			] );
			$args['ids'] = is_wp_error( $terms ) ? [] : $terms;
		}

		$output = '';
		foreach ( $args['ids'] as $id )
			$output .= $this->shortcode_specs( array_merge( [
				'id'        => $id,
				'title_tag' => 'h4',
			], $args['args'] ), NULL, $this->constant( 'specs_shortcode' ) );

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
