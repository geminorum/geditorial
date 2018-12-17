<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\WordPress\Taxonomy;

class Series extends gEditorial\Module
{

	public $meta_key = '_ge_series';

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'  => 'series',
			'title' => _x( 'Series', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'List Posts in Series', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'editor-ol',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'multiple_instances',
			],
			'posttypes_option' => 'posttypes_option',
			'fields_option'    => 'fields_option',
			'_supports' => [
				'shortcode_support',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'series_tax'       => 'series',
			'series_shortcode' => 'series',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'series_tax' => NULL,
			],
		];
	}

	protected function get_global_strings()
	{
		return [
			'titles' => [
				'post' => [
					'in_series_title' => _x( 'Title', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
					'in_series_order' => _x( 'Order', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
					'in_series_desc'  => _x( 'Description', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
				],
			],
			'descriptions' => [
				'post' => [
					'in_series_title' => _x( 'In Series Title', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
					'in_series_order' => _x( 'In Series Order', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
					'in_series_desc'  => _x( 'In Series Description', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
				],
			],
			'misc' => [
				'column_title'        => _x( 'Series', 'Modules: Series: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'tweaks_column_title' => _x( 'Series', 'Modules: Series: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'show_option_none'    => _x( '&mdash; Choose a Series &mdash;', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
			],
			'noops' => [
				'series_tax' => _nx_noop( 'Series', 'Series', 'Modules: Series: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
		];
	}

	protected function get_global_fields()
	{
		return [
			$this->constant( 'post_cpt' ) => [
				'in_series_title' => TRUE,
				'in_series_order' => TRUE,
				'in_series_desc'  => FALSE,
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'series_tax', [
			'show_admin_column' => TRUE,
		] );

		foreach ( $this->posttypes() as $posttype )
			$this->add_posttype_fields( $posttype, $this->fields[$this->constant( 'post_cpt' )], 'series' );

		$this->register_shortcode( 'series_shortcode' );
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->post_type, $this->posttypes() ) ) {

			if ( 'post' == $screen->base ) {

				add_meta_box( $this->classs( 'supported' ),
					$this->get_meta_box_title_tax( 'series_tax' ),
					[ $this, 'render_metabox_supported' ],
					$screen,
					'side'
				);

				add_action( 'save_post_'.$screen->post_type, [ $this, 'store_metabox' ], 20, 3 );
				add_action( $this->hook( 'render_metabox' ), [ $this, 'render_metabox' ], 10, 4 );
				add_action( $this->hook( 'render_metabox_item' ), [ $this, 'render_metabox_item' ], 5, 4 );

			} else if ( 'edit' == $screen->base ) {

				$this->_admin_enabled();

				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}
		}
	}

	public function store_metabox( $post_id, $post, $update, $context = 'box' )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		$postmeta = $this->sanitize_post_meta(
			$this->get_postmeta( $post_id ),
			$this->posttype_fields( $post->post_type ),
			$post_id,
			$post->post_type
		);

		$this->set_meta( $post_id, $postmeta );
		wp_cache_flush();
	}

	private function sanitize_post_meta( $postmeta, $fields, $post_id, $posttype )
	{
		if ( ! $this->nonce_verify( 'post_main' ) )
			return $postmeta;

		if ( ! isset( $_POST['geditorial-series-terms'] ) )
			return $postmeta;

		$prefix   = 'geditorial-series-';
		$postmeta = $pre_terms = [];

		foreach ( $_POST['geditorial-series-terms'] as $offset => $term_id )
			if ( $term_id && '-1' != $term_id )
				$pre_terms[$offset] = intval( $term_id );

		wp_set_object_terms( $post_id, ( count( $pre_terms ) ? $pre_terms : NULL ), $this->constant( 'series_tax' ), FALSE );

		foreach ( $pre_terms as $offset => $pre_term ) {
			foreach ( $fields as $field ) {

				switch ( $field ) {

					case 'in_series_order':

						if ( isset( $_POST[$prefix.$field][$offset] ) && '0' != $_POST[$prefix.$field][$offset] )
							$postmeta[$pre_term][$field] = Number::intval( $_POST[$prefix.$field][$offset] );

						else if ( isset( $postmeta[$pre_term][$field] ) && isset( $_POST[$prefix.$field][$offset] )  )
							unset( $postmeta[$pre_term][$field] );

					break;
					case 'in_series_title':
					case 'in_series_desc':

						if ( isset( $_POST[$prefix.$field][$offset] )
							&& strlen( $_POST[$prefix.$field][$offset] ) > 0
							&& $this->get_string( $field, $posttype ) !== $_POST[$prefix.$field][$offset] )
								$postmeta[$pre_term][$field] = Helper::kses( $_POST[$prefix.$field][$offset], 'text' );

						else if ( isset( $postmeta[$pre_term][$field] ) && isset( $_POST[$prefix.$field][$offset] ) )
							unset( $postmeta[$pre_term][$field] );

				}
			}
		}

		return $this->filters( 'sanitize_post_meta', $postmeta, $fields, $post_id, $posttype );
	}

	// TODO: list other post in this series by the order and link to their edit pages
	public function render_metabox_supported( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$fields = $this->posttype_fields( $post->post_type );

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'render_metabox', $post, $box, $fields, 'box' );
			$this->actions( 'render_metabox_after', $post, $box, $fields, 'box' );
		echo '</div>';
	}

	public function render_metabox( $post, $box, $fields = NULL, $context = 'box' )
	{
		$tax = $this->constant( 'series_tax' );

		if ( ! Taxonomy::hasTerms( $tax ) )
			return MetaBox::fieldEmptyTaxonomy( $tax );

		$series    = Taxonomy::getTerms( $tax, $post->ID, TRUE );
		$dropdowns = $posts = $map = [];
		$i         = 1;

		foreach ( $series as $the_term ) {
			$dropdowns[$i] = wp_dropdown_categories( [
				'taxonomy'         => $tax,
				'selected'         => $the_term->term_id,
				'show_option_none' => $this->get_string( 'show_option_none', 'post', 'misc' ),
				'name'             => 'geditorial-series-terms['.$i.']',
				'id'               => 'geditorial_series_terms-'.$i,
				'class'            => 'geditorial-admin-dropbown',
				'show_count'       => 1,
				'hide_empty'       => 0,
				'echo'             => 0,
			] );

			$posts[$i] = MetaBox::getTermPosts( $tax, $the_term, [ $post->ID ] );
			$map[$i]   = $the_term->term_id;
			$i++;
		}

		if ( $this->get_setting( 'multiple_instances', FALSE ) || empty( $series ) )
			$dropdowns[0] = wp_dropdown_categories( [
				'taxonomy'         => $tax,
				'selected'         => 0,
				'show_option_none' => $this->get_string( 'show_option_none', 'post', 'misc' ),
				'name'             => 'geditorial-series-terms[0]',
				'id'               => 'geditorial_series_terms-0',
				'class'            => 'geditorial-admin-dropbown',
				'show_count'       => 1,
				'hide_empty'       => 0,
				'echo'             => 0,
				'exclude'          => $map,
			] );

		$map[0] = FALSE;

		foreach ( $dropdowns as $counter => $dropdown ) {
			if ( $dropdown ) {

				echo '<div class="field-wrap-group">';

					echo HTML::wrap( $dropdown, 'field-wrap -select' );

					$this->actions( 'render_metabox_item', $counter, $map[$counter], $fields, $post );

					if ( $counter && $posts[$counter] )
						echo $posts[$counter];

				echo '</div>';
			}
		}

		$this->nonce_field( 'post_main' );
	}

	public function render_metabox_item( $counter, $term_id, $fields, $post )
	{
		$meta = ( $counter ? $this->get_postmeta( $post->ID, $term_id, [] ) : [] );

		$field = 'in_series_title';
		if ( in_array( $field, $fields ) ) {

			$title = $this->get_string( $field, $post->post_type );

			$html = HTML::tag( 'input', [
				'type'         => 'text',
				'name'         => 'geditorial-series-'.$field.'['.$counter.']',
				'id'           => 'geditorial-series-'.$field.'-'.$counter,
				'value'        => isset( $meta[$field] ) ? $meta[$field] : '',
				'title'        => $title,
				'placeholder'  => $title,
				'autocomplete' => 'off',
				'data'         => [
					'ortho' => 'text',
				],
			] );

			echo HTML::wrap( $html, 'field-wrap -inputtext' );
		}

		$field = 'in_series_order';
		if ( in_array( $field, $fields ) ) {

			$title = $this->get_string( $field, $post->post_type );

			$html = HTML::tag( 'input', [
				'type'         => 'text',
				'name'         => 'geditorial-series-'.$field.'['.$counter.']',
				'id'           => 'geditorial-series-'.$field.'-'.$counter,
				'value'        => isset( $meta[$field] ) ? $meta[$field] : '',
				'title'        => $title,
				'placeholder'  => $title,
				'autocomplete' => 'off',
			] );

			echo HTML::wrap( $html, 'field-wrap -inputtext' );
		}

		$field = 'in_series_desc';
		if ( in_array( $field, $fields ) ) {

			$title = $this->get_string( $field, $post->post_type );

			$html = HTML::tag( 'textarea', [
				'rows'        => '1',
				'class'       => 'textarea-autosize',
				'name'        => 'geditorial-series-'.$field.'['.$counter.']',
				'id'          => 'geditorial-series-'.$field.'-'.$counter,
				'title'       => $title,
				'placeholder' => $title,
				'data'        => [
					'ortho' => 'html',
				],
			], isset( $meta[$field] ) ? esc_textarea( $meta[$field] ) : '' );

			echo HTML::wrap( $html, 'field-wrap -textarea' );
		}
	}

	public function series_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::getTermPosts(
			'post',
			$this->constant( 'series_tax' ),
			array_merge( [
				'title_after'  => '<div class="-desc">%3$s</div>',
				'item_after'   => '<h6>%1$s</h6><div class="summary"><p>%2$s</p></div>', // use meta data after
				'item_cb'      => [ $this, 'series_shortcode_item_cb' ],
				'order_cb'     => [ $this, 'series_shortcode_order_cb' ],
				'orderby'      => 'order',
				'posttypes'    => $this->posttypes(),
			], (array) $atts ),
			$content,
			$this->constant( 'series_shortcode' )
		);
	}

	public function series_shortcode_order_cb( $posts, $args, $term )
	{
		if ( is_array( $term ) )
			$term = $term[0];

		if ( 1 == count( $posts ) ) {
			$posts[0]->series_meta = $this->get_postmeta( $posts[0]->ID, $term->term_id, [] );
			return $posts;
		}

		$i = 1000;
		$o = [];

		foreach ( $posts as &$post ) {

			$post->series_meta = $this->get_postmeta( $post->ID, $term->term_id, [] );

			if ( isset( $post->series_meta['in_series_order'] )
				&& $post->series_meta['in_series_order'] )
					$key = intval( $post->series_meta['in_series_order'] ) * $i;
			else
				$key = strtotime( $post->post_date );

			$i++;
			// $post->menu_order = $key;

			$o[$key] = $post;
		}

		if ( $args['order'] == 'ASC' )
			ksort( $o, SORT_NUMERIC );
		else
			krsort( $o, SORT_NUMERIC );

		unset( $posts, $post, $i );

		return $o;
	}

	public function series_shortcode_item_cb( $post, $args, $term )
	{
		if ( TRUE === $args['item_after'] )
			$args['item_after'] = '<h6>%1$s</h6><div class="summary"><p>%2$s</p></div>';

		if ( isset( $post->series_meta )
			&& ( isset( $post->series_meta['in_series_title'] )
				|| isset( $post->series_meta['in_series_desc'] ) ) ) {

			$args['item_after'] = sprintf( $args['item_after'],
				isset( $post->series_meta['in_series_title'] ) ? $post->series_meta['in_series_title'] : '',
				isset( $post->series_meta['in_series_desc'] ) ? $post->series_meta['in_series_desc'] : ''
			);
		}

		return ShortCode::postItem( $post, $args );
	}
}