<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\WordPress\Taxonomy;

class Specs extends gEditorial\Module
{

	public $meta_key = '_ge_specs';

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'  => 'specs',
			'title' => _x( 'Specifications', 'Modules: Specs', 'geditorial' ),
			'desc'  => _x( 'Post Specifications', 'Modules: Specs', 'geditorial' ),
			'icon'  => 'list-view',
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
				'spec_title' => _x( 'Title', 'Strings: Title', 'geditorial-specs' ),
				'spec_order' => _x( 'Order', 'Strings: Title', 'geditorial-specs' ),
				'spec_value' => _x( 'Description', 'Strings: Title', 'geditorial-specs' ),
			],
			'descriptions' => [
				'spec_title' => _x( 'In Specifications Title', 'Strings: Description', 'geditorial-specs' ),
				'spec_order' => _x( 'In Specifications Order', 'Strings: Description', 'geditorial-specs' ),
				'spec_value' => _x( 'In Specifications Description', 'Strings: Description', 'geditorial-specs' ),
			],
			'misc' => [
				'column_title'     => _x( 'Specifications', 'Column Title', 'geditorial-specs' ),
				'show_option_none' => _x( '&ndash; Choose a Specification &ndash;', 'Show Option None', 'geditorial-specs' ),
			],
			'noops' => [
				'specs_tax' => _nx_noop( 'Specification', 'Specifications', 'Noop', 'geditorial-specs' ),
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

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'specs_tax' );

		foreach ( $this->posttypes() as $posttype )
			$this->add_posttype_fields( $posttype, $this->fields[$this->constant( 'post_cpt' )], 'specs' );

		// add_shortcode( $this->constant( 'specs_shortcode' ), [ $this, 'shortcode_specs' ] );
		// add_shortcode( $this->constant( 'multiple_specs_shortcode' ), [ $this, 'shortcode_multiple_specs' ] );
	}

	public function current_screen( $screen )
	{
		if ( 'post' == $screen->base
			&& $this->posttype_supported( $screen->post_type ) ) {

			$this->class_metabox( $screen, 'linkedbox' );
			add_meta_box( $this->classs( 'linkedbox' ),
				$this->get_meta_box_title_tax( 'specs_tax' ),
				[ $this, 'render_linkedbox_metabox' ],
				$screen,
				'side',
				'high'
			);

			$this->enqueue_asset_js( [], $screen, [ 'jquery', Scripts::pkgSortable() ] );

			$this->_hook_store_metabox( $screen->post_type );
			add_action( $this->hook( 'render_metabox' ), [ $this, 'render_metabox' ], 10, 4 );
			// add_action( $this->hook( 'render_metabox_item' ), [ $this, 'render_metabox_item' ], 5, 5 );
		}
	}

	public function store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		$postmeta = $this->sanitize_post_meta(
			$this->get_postmeta_legacy( $post_id ),
			$this->posttype_fields( $post->post_type ),
			$post_id,
			$post->post_type
		);

		$this->store_postmeta( $post_id, $postmeta );
		wp_cache_flush();
	}

	// programatically sets specs for the post
	// it will append new specs to the old ones
	public function set_post_specs( $post_id, $specs, $create = FALSE )
	{
		if ( ! $post = get_post( $post_id ) )
			return FALSE;

		$meta       = $this->get_postmeta_legacy( $post_id );
		$spec_terms = Taxonomy::getTerms( $this->constant( 'specs_tax' ), FALSE, TRUE, 'slug' );
		$terms      = [];

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

			$this->store_postmeta( $post_id, $meta );
			wp_set_object_terms( $post_id, ( count( $terms ) ? $terms : null ), $this->constant( 'specs_tax' ), FALSE );

			return $post_id;
		}

		return FALSE;
	}

	private function sanitize_post_meta( $postmeta, $fields, $post_id, $posttype )
	{
		if ( ! $this->nonce_verify( 'mainbox' ) )
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
							&& $this->get_string( $field, $posttype ) !== $_POST[$prefix.$field][$offset] )
								$postmeta[$offset][$field] = Helper::kses( $_POST[$prefix.$field][$offset], 'text' );

						else if ( isset( $postmeta[$offset][$field] ) && isset( $_POST[$prefix.$field][$offset] ) )
							unset( $postmeta[$offset][$field] );
				}
			}
		}

		$the_list = wp_list_pluck( $postmeta, 'spec_order' );
		$postmeta = array_combine( $the_list, $postmeta );
		krsort( $postmeta );

		return $this->filters( 'sanitize_post_meta', $postmeta, $fields, $post_id, $posttype );
	}

	public function render_linkedbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$fields = $this->posttype_fields( $post->post_type );

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'render_metabox', $post, $box, $fields, 'linkedbox_specs' );
			$this->actions( 'render_metabox_after', $post, $box, $fields, 'linkedbox_specs' );
		echo '</div>';

		$this->nonce_field( 'mainbox' );
	}

	public function render_metabox( $post, $box, $fields = NULL, $context = NULL )
	{
		$taxonomy = $this->constant( 'specs_tax' );

		if ( ! Taxonomy::hasTerms( $taxonomy ) )
			return MetaBox::fieldEmptyTaxonomy( $taxonomy );

		$terms = Taxonomy::getTerms( $taxonomy, $post->ID, TRUE );
		$metas = $this->get_postmeta_legacy( $post->ID );

		$handle = sprintf( '<span data-icon="dashicons" class="-handle dashicons dashicons-move" title="%s"></span>',
			_x( 'Sort me!', 'Sortable Handler', 'geditorial-specs' ) );

		$delete = sprintf( '<span data-icon="dashicons" class="-delete dashicons dashicons-trash" title="%s"></span>',
			_x( 'Trash me!', 'Sortable Trash', 'geditorial-specs' ) );

		echo '<ol class="geditorial-specs-list -sortable">';

		foreach ( $metas as $order => $meta ) {

			echo '<li><div class="item-head">';
				echo $handle.'<span class="-excerpt">';

					if ( ! empty( $meta['spec_title'] ) )
						$title = $meta['spec_title'];

					else if ( ! empty( $meta['spec_term_id'] ) )
						$title = $terms[$meta['spec_term_id']]->name;

					else
						$title = _x( 'Unknown Field', 'Modules: Specs', 'geditorial-specs' );

					if ( ! empty( $meta['spec_value'] ) )
						$title.= sprintf( ': %s', $meta['spec_value'] );

					echo Text::subStr( $title, 0, 28 );

				echo '</span>'.$delete;
			echo '</div><div class="item-body"><div class="field-wrap-group">';

			$this->render_metabox_item( $order, $fields, $post, $meta );

			$html = wp_dropdown_categories( [
				'taxonomy'         => $taxonomy,
				'selected'         => ( isset( $meta['spec_term_id'] ) ? $terms[$meta['spec_term_id']]->term_id : 0 ),
				'show_option_none' => $this->get_string( 'show_option_none', $post->post_type, 'misc' ),
				'name'             => 'geditorial-specs_term_id[]',
				// 'id'               => 'geditorial-specs-terms-'.$order,
				'class'            => 'geditorial-admin-dropbown item-dropdown no-chosen',
				'show_count'       => 0,
				'hide_empty'       => 0,
				'echo'             => 0,
			] );

			echo HTML::wrap( $html, 'field-wrap -select' );

			echo '</div></div></li>';
		}
		echo '</ol>';

		echo '<ul class="geditorial-specs-new">';
			echo '<li>';
			echo '<div class="item-head">';
				echo $handle.'<span class="-excerpt">';
					// echo '&hellip;';
				echo '</span>'.$delete;
			echo '</div><div class="item-body">';

			echo '<div class="field-wrap-group">';

				$this->render_metabox_item( '-1', $fields, $post );

				// FIXME: we need custom for disabled options
				$html = wp_dropdown_categories( [
					'taxonomy'         => $taxonomy,
					'selected'         => 0,
					'show_option_none' => $this->get_string( 'show_option_none', $post->post_type, 'misc' ),
					'name'             => 'geditorial-specs_term_id[]',
					// 'id'               => 'geditorial-specs-terms--1',
					'id'               => FALSE,
					'class'            => 'geditorial-admin-dropbown item-dropdown item-dropdown-new no-chosen',
					'show_count'       => 0,
					'hide_empty'       => 0,
					'echo'             => 0,
				] );

				echo HTML::wrap( $html, 'field-wrap -select' );

		echo '</div></div></li></ul>';
	}

	public function render_metabox_item( $order, $fields, $post, $meta = [] )
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

			echo HTML::wrap( $html, 'field-wrap -textarea' );
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

			echo HTML::wrap( $html, 'field-wrap -inputtext' );
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
		$metas     = $this->get_postmeta_legacy( $post->ID );
		$html      = '';

		// FIXME: use table helper
		$html.= '<table class="table table-striped geditorial-specs">';
		foreach ( $metas as $order => $meta ) {
			$html.= '<tr><td>';
				$html.= ( isset( $meta['spec_title'] ) && $meta['spec_title'] ) ? $meta['spec_title'] : ( isset( $meta['spec_term_id'] ) && $meta['spec_term_id'] ? $the_terms[$meta['spec_term_id']]->name : _x( 'Unknown Field', 'Modules: Specs', 'geditorial-specs' ) );
			$html.= '</td><td>';
				// FIXME: add filter for each spec
				$html.= isset( $meta['spec_value'] ) ? $meta['spec_value'] : '';
			$html.= '</td></tr>';
		}
		$html.= '</table>';

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

		if ( empty( $args['ids'] ) ) {

			// FIXME: use: `Taxonomy::getTerms()`
			$terms = wp_get_object_terms( (int) $post->ID, $this->constant( 'specs_tax' ), [
				'order'   => $args['order'],
				'orderby' => $args['orderby'],
				'fields'  => 'ids',
			] );

			$args['ids'] = is_wp_error( $terms ) ? [] : $terms;
		}

		$output = '';

		foreach ( $args['ids'] as $id )
			$output.= $this->shortcode_specs( array_merge( [
				'id'        => $id,
				'title_tag' => 'h4',
			], $args['args'] ), NULL, $this->constant( 'specs_shortcode' ) );

		if ( ! empty( $output ) ) {

			if ( $args['title'] )
				$output = '<'.$args['title_tag'].' class="post-specs-wrap-title">'.$args['title'].'</'.$args['title_tag'].'>'.$output;

			if ( ! is_null( $args['context'] ) )
				$output = '<div class="'.HTML::prepClass( 'multiple-specs-'.$args['context'] ).'">'.$output.'</div>';

			return $args['before'].$output.$args['after'];
		}

		return NULL;
	}
}
