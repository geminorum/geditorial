<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialSeries extends gEditorialModuleCore
{

	public $meta_key      = '_ge_series';
	protected $field_type = 'series';

	public static function module()
	{
		return array(
			'name'  => 'series',
			'title' => _x( 'Series', 'Series Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Post Series Management', 'Series Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'editor-ol',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'multiple_instances',
			),
			'posttypes_option' => 'posttypes_option',
			'fields_option'    => 'fields_option',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'series_tax'                => 'series',
			'series_shortcode'          => 'series',
			'multiple_series_shortcode' => 'multiple_series',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'titles' => array(
				'post' => array(
					'in_series_title' => _x( 'Title', 'Series Module', GEDITORIAL_TEXTDOMAIN ),
					'in_series_order' => _x( 'Order', 'Series Module', GEDITORIAL_TEXTDOMAIN ),
					'in_series_desc'  => _x( 'Description', 'Series Module', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'descriptions' => array(
				'post' => array(
					'in_series_title' => _x( 'In Series Title', 'Series Module', GEDITORIAL_TEXTDOMAIN ),
					'in_series_order' => _x( 'In Series Order', 'Series Module', GEDITORIAL_TEXTDOMAIN ),
					'in_series_desc'  => _x( 'In Series Description', 'Series Module', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'misc' => array(
				'post' => array(
					'meta_box_title'   => _x( 'Series', 'Series Module', GEDITORIAL_TEXTDOMAIN ),
					'meta_box_action'  => _x( 'Management', 'Series Module', GEDITORIAL_TEXTDOMAIN ),
					'column_title'     => _x( 'Series', 'Series Module', GEDITORIAL_TEXTDOMAIN ),
					'show_option_none' => _x( '&mdash; Choose a Series &mdash;', 'Series Module', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'labels' => array(
				'series_tax' => gEditorialHelper::generateTaxonomyLabels(
					_nx_noop( 'Series', 'Series', 'Series Module: Series Tax Labels: Name', GEDITORIAL_TEXTDOMAIN )
				),
			),
		);
	}

	protected function get_global_fields()
	{
		return array(
			$this->constant( 'post_cpt' ) => array(
				'in_series_title' => TRUE,
				'in_series_order' => TRUE,
				'in_series_desc'  => FALSE,
			),
		);
	}

	public function setup( $partials = array() )
	{
		parent::setup( array(
			'templates',
		) );

		if ( is_admin() ) {
			add_action( 'save_post', array( $this, 'save_post' ), 20, 2 );
		}
	}

	public function init()
	{
		do_action( 'geditorial_series_init', $this->module );

		$this->do_globals();

		$this->register_taxonomy( 'series_tax' );

		foreach ( $this->post_types() as $post_type )
			$this->add_post_type_fields( $post_type, $this->fields[$this->constant( 'post_cpt' )], 'series' );

		$this->register_shortcode( 'series_shortcode', array( 'gEditorialSeriesTemplates', 'shortcode_series' ) );
		$this->register_shortcode( 'multiple_series_shortcode', array( 'gEditorialSeriesTemplates', 'shortcode_multiple_series' ) );
	}

	public function admin_init()
	{
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 20, 2 );

		// internal actions:
		add_action( 'geditorial_series_meta_box', array( $this, 'geditorial_series_meta_box' ), 5, 3 );
		add_action( 'geditorial_series_meta_box_item', array( $this, 'geditorial_series_meta_box_item' ), 5, 4 );
	}

	public function tweaks_strings( $strings )
	{
		$this->tweaks = TRUE;

		$new = array(
			'taxonomies' => array(
				$this->constant( 'series_tax' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'series_tax' ),
					'icon'   => $this->module->icon,
					'title'  => $this->get_string( 'name', 'series_tax', 'labels' ),
				),
			),
		);

		return self::recursiveParseArgs( $new, $strings );
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

		wp_set_object_terms( $post_id, ( count( $pre_terms ) ? $pre_terms : NULL ), $this->constant( 'series_tax' ), FALSE );

		foreach ( $pre_terms as $offset => $pre_term ) {
			foreach ( $fields as $field ) {
				switch ( $field ) {
					case 'in_series_order' :
						if ( isset( $_POST[$prefix.$field][$offset] ) && '0' != $_POST[$prefix.$field][$offset] )
							$postmeta[$pre_term][$field] = gEditorialHelper::intval( $_POST[$prefix.$field][$offset] );
						else if ( isset( $postmeta[$pre_term][$field] ) && isset( $_POST[$prefix.$field][$offset] )  )
							unset( $postmeta[$pre_term][$field] );
					break;
					case 'in_series_title' :
					case 'in_series_desc' :
						if ( isset( $_POST[$prefix.$field][$offset] )
							&& strlen( $_POST[$prefix.$field][$offset] ) > 0
							&& $this->get_string( $field, $post_type ) !== $_POST[$prefix.$field][$offset] )
								$postmeta[$pre_term][$field] = gEditorialHelper::kses( $_POST[$prefix.$field][$offset] );
						else if ( isset( $postmeta[$pre_term][$field] ) && isset( $_POST[$prefix.$field][$offset] ) )
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

		$this->remove_meta_box( 'series_tax', $post_type, 'tag' );

		add_meta_box(
			'geditorial-series',
			$this->get_meta_box_title( 'series_tax', $this->get_url_tax_edit( 'series_tax' ), 'edit_others_posts' ),
			array( $this, 'do_meta_box' ),
			$post_type,
			'side' );
	}

	public function do_meta_box( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox series">';

		$series = gEditorialHelper::getTerms( $this->constant( 'series_tax' ), $post->ID, TRUE );

		do_action( 'geditorial_series_meta_box', $post, $box, $series );

		echo '</div>';
	}

	public function geditorial_series_meta_box( $post, $box, $series )
	{
		// bail if no series
		if ( ! count( $series ) )
			return gEditorialMetaBox::fieldEmptyTaxonomy( $this->constant( 'series_tax' ) );

		$dropdowns = $posts = $map = array();
		$fields    = $this->post_type_fields( $post->post_type );
		$i         = 1;

		foreach ( $series as $the_term ) {
			$dropdowns[$i] = wp_dropdown_categories( array(
				'taxonomy'         => $this->constant( 'series_tax' ),
				'selected'         => $the_term->term_id,
				'show_option_none' => $this->get_string( 'show_option_none', 'post', 'misc' ),
				'name'             => 'geditorial-series-terms['.$i.']',
				'id'               => 'geditorial_series_terms-'.$i,
				'class'            => 'geditorial-admin-dropbown',
				'show_count'       => 1,
				'hide_empty'       => 0,
				'echo'             => 0,
			) );

			$posts[$i] = gEditorialHelper::getTermPosts( $this->constant( 'series_tax' ), $the_term, array( $post->ID ) );
			$map[$i]   = $the_term->term_id;
			$i++;
		}

		if ( $this->get_setting( 'multiple_instances', FALSE ) || ! count( $series ) )
			$dropdowns[0] = wp_dropdown_categories( array(
				'taxonomy'         => $this->constant( 'series_tax' ),
				'selected'         => 0,
				'show_option_none' => $this->get_string( 'show_option_none', 'post', 'misc' ),
				'name'             => 'geditorial-series-terms[0]',
				'id'               => 'geditorial_series_terms-0',
				'class'            => 'geditorial-admin-dropbown',
				'show_count'       => 1,
				'hide_empty'       => 0,
				'echo'             => 0,
				'exclude'          => $map,
			) );

		$map[0] = FALSE;

		foreach ( $dropdowns as $counter => $dropdown ) {
			if ( $dropdown ) {

				echo '<div class="field-wrap-group">';

					echo '<div class="field-wrap field-wrap-select">';
						echo $dropdown;
					echo '</div>';

					do_action( 'geditorial_series_meta_box_item', $counter, $map[$counter], $fields, $post );

					if ( $counter && $posts[$counter] )
						echo $posts[$counter];

				echo '</div>';
			}
		}

		// TODO: list other post in this series by the order and link to their edit pages

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
			$html = self::html( 'input', array(
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

			echo self::html( 'div', array(
				'class' => 'field-wrap field-wrap-inputtext',
			), $html );
		}

		$field = 'in_series_order';
		if ( in_array( $field, $fields )
			&& self::user_can( 'view', $field ) ) {

			$title = $this->get_string( $field, $post->post_type );
			$html = self::html( 'input', array(
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

			echo self::html( 'div', array(
				'class' => 'field-wrap field-wrap-inputtext',
			), $html );
		}

		$field = 'in_series_desc';
		if ( in_array( $field, $fields )
			&& self::user_can( 'view', $field ) ) {

			$title = $this->get_string( $field, $post->post_type );
			$html = self::html( 'textarea', array(
				'class'        => 'field-textarea textarea-autosize',
				'name'         => 'geditorial-series-'.$field.'['.$counter.']',
				'id'           => 'geditorial-series-'.$field.'-'.$counter,
				'title'        => $title,
				'placeholder'  => $title,
				'readonly'     => ! $this->user_can( 'edit', $field ),
			), isset( $meta[$field] ) ? esc_textarea( $meta[$field] ) : '' );

			echo self::html( 'div', array(
				'class' => 'field-wrap field-wrap-textarea',
			), $html );
		}
	}
}
