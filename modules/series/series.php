<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialSeries extends gEditorialModuleCore
{

	public $meta_key      = '_ge_series';
	protected $field_type = 'series';

	protected $partials = array( 'templates' );

	public static function module()
	{
		return array(
			'name'  => 'series',
			'title' => _x( 'Series', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Post Series Management', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
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
			'series_tax'       => 'series',
			'series_shortcode' => 'series',
		);
	}

	protected function get_module_icons()
	{
		return array(
			'taxonomies' => array(
				'series_tax' => NULL,
			),
		);
	}

	protected function get_global_strings()
	{
		return array(
			'titles' => array(
				'post' => array(
					'in_series_title' => _x( 'Title', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
					'in_series_order' => _x( 'Order', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
					'in_series_desc'  => _x( 'Description', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'descriptions' => array(
				'post' => array(
					'in_series_title' => _x( 'In Series Title', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
					'in_series_order' => _x( 'In Series Order', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
					'in_series_desc'  => _x( 'In Series Description', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'misc' => array(
				'meta_box_title'      => _x( 'Series', 'Modules: Series: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_action'     => _x( 'Management', 'Modules: Series: MetaBox Action', GEDITORIAL_TEXTDOMAIN ),
				'column_title'        => _x( 'Series', 'Modules: Series: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'tweaks_column_title' => _x( 'Series', 'Modules: Series: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'show_option_none'    => _x( '&mdash; Choose a Series &mdash;', 'Modules: Series', GEDITORIAL_TEXTDOMAIN ),
			),
			'noops' => array(
				'series_tax' => _nx_noop( 'Series', 'Series', 'Modules: Series: Noop', GEDITORIAL_TEXTDOMAIN ),
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

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'series_tax', array(
			'show_admin_column' => TRUE,
		) );

		foreach ( $this->post_types() as $post_type )
			$this->add_post_type_fields( $post_type, $this->fields[$this->constant( 'post_cpt' )], 'series' );

		if ( is_admin() )
			add_action( 'save_post', array( $this, 'save_post' ), 20, 2 );

		$this->register_shortcode( 'series_shortcode' );
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'post' == $screen->base ) {

				add_meta_box( 'geditorial-series',
					$this->get_meta_box_title( 'series_tax', $this->get_url_tax_edit( 'series_tax' ), 'edit_others_posts' ),
					array( $this, 'do_meta_box' ),
					$screen->post_type,
					'side' );

				// internal actions:
				add_action( 'geditorial_series_meta_box', array( $this, 'geditorial_series_meta_box' ), 5, 3 );
				add_action( 'geditorial_series_meta_box_item', array( $this, 'geditorial_series_meta_box_item' ), 5, 4 );

			} else if ( 'edit' == $screen->base ) {

				$this->_admin_enabled();

				$this->_tweaks_taxonomy();
			}
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

	private function sanitize_post_meta( $postmeta, $fields, $post_id, $post_type )
	{
		if ( ! wp_verify_nonce( @ $_REQUEST['_geditorial_series_post_main'], 'geditorial_series_post_main' ) )
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
							$postmeta[$pre_term][$field] = gEditorialNumber::intval( $_POST[$prefix.$field][$offset] );
						else if ( isset( $postmeta[$pre_term][$field] ) && isset( $_POST[$prefix.$field][$offset] )  )
							unset( $postmeta[$pre_term][$field] );
					break;
					case 'in_series_title' :
					case 'in_series_desc' :
						if ( isset( $_POST[$prefix.$field][$offset] )
							&& strlen( $_POST[$prefix.$field][$offset] ) > 0
							&& $this->get_string( $field, $post_type ) !== $_POST[$prefix.$field][$offset] )
								$postmeta[$pre_term][$field] = gEditorialHelper::kses( $_POST[$prefix.$field][$offset], 'text' );
						else if ( isset( $postmeta[$pre_term][$field] ) && isset( $_POST[$prefix.$field][$offset] ) )
							unset( $postmeta[$pre_term][$field] );
					break;
				}
			}
		}
		$postmeta = apply_filters( 'geditorial_series_sanitize_post_meta', $postmeta, $fields, $post_id, $post_type );
		return $postmeta;
	}

	public function do_meta_box( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox -series">';

		$series = gEditorialWPTaxonomy::getTerms( $this->constant( 'series_tax' ), $post->ID, TRUE );

		do_action( 'geditorial_series_meta_box', $post, $box, $series );

		echo '</div>';
	}

	public function geditorial_series_meta_box( $post, $box, $series )
	{
		$tax = $this->constant( 'series_tax' );

		if ( ! gEditorialWPTaxonomy::hasTerms( $tax ) )
			return gEditorialMetaBox::fieldEmptyTaxonomy( $tax );

		$dropdowns = $posts = $map = array();
		$fields    = $this->post_type_fields( $post->post_type );
		$i         = 1;

		foreach ( $series as $the_term ) {
			$dropdowns[$i] = wp_dropdown_categories( array(
				'taxonomy'         => $tax,
				'selected'         => $the_term->term_id,
				'show_option_none' => $this->get_string( 'show_option_none', 'post', 'misc' ),
				'name'             => 'geditorial-series-terms['.$i.']',
				'id'               => 'geditorial_series_terms-'.$i,
				'class'            => 'geditorial-admin-dropbown',
				'show_count'       => 1,
				'hide_empty'       => 0,
				'echo'             => 0,
			) );

			$posts[$i] = gEditorialHelper::getTermPosts( $tax, $the_term, array( $post->ID ) );
			$map[$i]   = $the_term->term_id;
			$i++;
		}

		if ( $this->get_setting( 'multiple_instances', FALSE ) || ! count( $series ) )
			$dropdowns[0] = wp_dropdown_categories( array(
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
			$html = gEditorialHTML::tag( 'input', array(
				'type'         => 'text',
				'name'         => 'geditorial-series-'.$field.'['.$counter.']',
				'id'           => 'geditorial-series-'.$field.'-'.$counter,
				'value'        => isset( $meta[$field] ) ? $meta[$field] : '',
				'title'        => $title,
				'placeholder'  => $title,
				'readonly'     => ! $this->user_can( 'edit', $field ),
				'autocomplete' => 'off',
				'data'         => array(
					'ortho' => 'text',
				),
			) );

			echo gEditorialHTML::tag( 'div', array(
				'class' => 'field-wrap field-wrap-inputtext',
			), $html );
		}

		$field = 'in_series_order';
		if ( in_array( $field, $fields )
			&& self::user_can( 'view', $field ) ) {

			$title = $this->get_string( $field, $post->post_type );
			$html = gEditorialHTML::tag( 'input', array(
				'type'         => 'text',
				'name'         => 'geditorial-series-'.$field.'['.$counter.']',
				'id'           => 'geditorial-series-'.$field.'-'.$counter,
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

		$field = 'in_series_desc';
		if ( in_array( $field, $fields )
			&& self::user_can( 'view', $field ) ) {

			$title = $this->get_string( $field, $post->post_type );
			$html = gEditorialHTML::tag( 'textarea', array(
				'rows'        => '1',
				'class'       => 'textarea-autosize',
				'name'        => 'geditorial-series-'.$field.'['.$counter.']',
				'id'          => 'geditorial-series-'.$field.'-'.$counter,
				'title'       => $title,
				'placeholder' => $title,
				'readonly'    => ! $this->user_can( 'edit', $field ),
				'data'        => array(
					'ortho' => 'html',
				),
			), isset( $meta[$field] ) ? esc_textarea( $meta[$field] ) : '' );

			echo gEditorialHTML::tag( 'div', array(
				'class' => 'field-wrap field-wrap-textarea',
			), $html );
		}
	}

	public function series_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorialShortCode::getTermPosts(
			'post',
			$this->constant( 'series_tax' ),
			array_merge( [
				'title_after'  => '<div class="-desc">%3$s</div>',
				'item_after'   => '<h6>%1$s</h6><div class="summary"><p>%2$s</p></div>', // use meta data after
				'item_cb'      => [ $this, 'series_shortcode_item_cb' ],
				'order_cb'     => [ $this, 'series_shortcode_order_cb' ],
				'orderby'      => 'order',
				'posttypes'    => $this->post_types(),
			], $atts ),
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

		return gEditorialShortCode::postItem( $args, $post );
	}
}
