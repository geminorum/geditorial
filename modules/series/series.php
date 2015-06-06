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
			'load_frontend'        => true,
			'constants'            => array(
				'series_tax' => 'series',
				'series_shortcode' => 'series',
				'multiple_series_shortcode' => 'multiple_series',
			),
			'default_options' => array(
				'enabled' => 'off',
				'post_types' => array(
					'post' => 'on',
					'page' => 'off',
				),
				'post_fields' => array(
					'in_series_title' => 'on',
					'in_series_order' => 'on',
					'in_series_desc'  => 'off',
				),
				'settings' => array(
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
						'meta_box_title'     => __( 'Series', GEDITORIAL_TEXTDOMAIN ),
						'column_title'  => __( 'Series', GEDITORIAL_TEXTDOMAIN ),
						'select_series' => __( '&mdash; Choose a Series &mdash;', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'labels' => array(
					'series_tax' => array(
						'name'                       => __( 'Series', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => __( 'Series', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => __( 'Search Series', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => null, // to disable tag cloud on edit tag page // __( 'Popular Series', GEDITORIAL_TEXTDOMAIN ),
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
			'settings_help_tab' => array(
				'id'      => 'geditorial-series-overview',
				'title'   => __( 'help-tab-title', GEDITORIAL_TEXTDOMAIN ),
				'content' => __( '<p>help-tab-content</p>', GEDITORIAL_TEXTDOMAIN ),
			),
			'settings_help_sidebar' => sprintf(
				__( '<p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p>', GEDITORIAL_TEXTDOMAIN ),
				'http://geminorum.ir/wordpress/geditorial/modules/series',
				__( 'Editorial Series Documentations', GEDITORIAL_TEXTDOMAIN ),
				'https://github.com/geminorum/geditorial' ),
		);

		$gEditorial->register_module( $this->module_name, $args );
	}

	public function setup()
	{
		add_action( 'init', array( &$this, 'init' ) );
		add_filter( 'geditorial_tinymce_strings', array( &$this, 'tinymce_strings' ) );

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
		$this->register_taxonomies();

		add_shortcode( $this->module->constants['series_shortcode'], array( $this, 'shortcode_series' ) );
		add_shortcode( $this->module->constants['multiple_series_shortcode'], array( $this, 'shortcode_multiple_series' ) );
	}

	public function admin_init()
	{
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 12, 2 );
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 20, 2 );

		if ( $this->get_setting( 'editor_button', true )
			&& current_user_can( 'edit_posts' )
			&& get_user_option( 'rich_editing' ) == 'true' ) {
				add_filter( 'mce_buttons', array( $this, 'mce_buttons' ) );
				add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ) );
		}

		// internal actions:
		add_action( 'geditorial_series_meta_box', array( $this, 'geditorial_series_meta_box' ), 5, 2 );
		add_action( 'geditorial_series_meta_box_item', array( $this, 'geditorial_series_meta_box_item' ), 5, 4 );
	}

	public function register_taxonomies()
	{
		register_taxonomy( $this->module->constants['series_tax'],
			$this->post_types(), array(
				'labels'                => $this->module->strings['labels']['series_tax'],
				'public'                => true,
				'show_in_nav_menus'     => false,
				'show_ui'               => true, // current_user_can( 'update_plugins' ),
				'show_admin_column'     => false,
				'show_tagcloud'         => false,
				'hierarchical'          => false,
				'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
				'query_var'             => true,
				'rewrite'               => array(
					'slug'         => $this->module->constants['series_tax'],
					'hierarchical' => false,
					'with_front'   => false,
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
		if ( ! wp_verify_nonce( @ $_REQUEST['_geditorial_series_post_main'], 'geditorial_series_post_main' ) )
			return $postmeta;

		if ( ! isset( $_POST['geditorial-series-terms'] ) )
			return $postmeta;

		$prefix   = 'geditorial-series-';
		$postmeta = $pre_terms = array();

		foreach( $_POST['geditorial-series-terms'] as $offset => $term_id )
			if ( $term_id && '-1' != $term_id )
				$pre_terms[$offset] = intval( $term_id );

		wp_set_object_terms( $post_id, ( count( $pre_terms ) ? $pre_terms : null ), $this->module->constants['series_tax'], false );

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

	public function remove_meta_boxes( $post_type, $post )
	{
		if ( ! in_array( $post_type, $this->post_types() ) )
			return;

		//if ( ! current_user_can( 'edit_published_posts' ) )
			remove_meta_box( 'tagsdiv-'.$this->module->constants['series_tax'], $post_type, 'side' );
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( ! in_array( $post_type, $this->post_types() ) )
			return;

		add_meta_box(
			'geditorial-series',
			$this->get_meta_box_title( $post_type ),
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

		foreach( $series_dropdowns as $counter => $series_dropdown ) {
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

	// [series]
	// [series slug="wordpress-themes"]
	// [series id="146"]
	// [series title="More WordPress Theme Lists" title_wrap="h4" limit="5" list="ol" future="off" single="off"]
	public function shortcode_series( $atts, $content = null, $tag = '' )
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
			'cb'        => false,
			'exclude'   => true, // or array
			'before'    => '',
			'after'     => '',
			'context'   => null,
		), $atts, $this->module->constants['series_shortcode'] );

		if ( false === $args['context'] ) // bailing
			return null;

		$key = md5( serialize( $args ) );
		$cache = wp_cache_get( $key, $this->module->constants['series_shortcode'] );
		if ( false !== $cache )
			return $cache;

		if ( $args['cb'] && ! is_callable( $args['cb'] ) )
			$args['cb'] = false;

		if ( true === $args['exclude'] )
			$args['exclude'] = array( $post->ID );
		else if ( false === $args['exclude'] )
			$args['exclude'] = array();

		if( $args['id'] ) {
			$tax_query = array( array(
				'taxonomy' => $this->module->constants['series_tax'],
				'field'    => 'id',
				'terms'    => $args['id'],
			) );
		} else if ( $args['slug'] ) {
			$the_term = get_term_by( 'slug', $args['slug'], $this->module->constants['series_tax'] );
			if ( false !== $the_term ) {
				$args['id'] = $the_term->term_id;
				$tax_query = array( array(
					'taxonomy' => $this->module->constants['series_tax'],
					// 'field'    => 'slug',
					'field'    => 'id',
					'terms'    => $args['id'],
				) );
			} else {
				return $content;
			}

		} else { // Use post's own Series tax if neither "id" nor "slug" exist
			$terms = wp_get_object_terms( (int) $post->ID, $this->module->constants['series_tax'], array( 'fields' => 'ids' ) );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$args['id'] = $terms[0];
				$tax_query = array( array(
					'taxonomy' => $this->module->constants['series_tax'],
					'field'    => 'id',
					'terms'    => $terms[0],
				) );

			} else {
				$error = true;
			}
		}

		if( $error == false ) {

			if( $args['title'] ) {
				if ( false !== strpos( $args['title'], '%' ) ) {
					$the_term = get_term_by( 'id', $args['id'], $this->module->constants['series_tax'] );
					if ( false !== $the_term ) {
						$args['title'] = sprintf( $args['title'],
							sanitize_term_field( 'name', $the_term->name, $the_term->term_id, $the_term->taxonomy, 'display' ),
							get_term_link( $the_term, $the_term->taxonomy ),
							gEditorialHelper::term_description( $the_term )
						);
					}
				}
				$args['title'] = '<'.$args['title_tag'].' class="post-series-title">'.$args['title'].'</'.$args['title_tag'].'>';
			}

			if( $args['future'] == 'on' ) {
				$post_status = array( 'publish', 'future' );
			} else {
				$post_status = 'publish';
			}

			$query_args = array(
				'tax_query'      => $tax_query,
				'posts_per_page' => intval( $args['limit'] ),
				'orderby'        => ( $args['orderby'] == 'order' ? 'date' : $args['orderby'] ),
				'order'          => $args['order'],
				'post_status'    => $post_status,
				'post__not_in'   => $args['exclude'],
			);

			$the_posts = get_posts( $query_args );
			$count = count( $the_posts );

			if( $count > 1 || ( $args['single'] == 'on' && $count > 0 ) ) {
				if ( $count > 1 && 'order' == $args['orderby'] && $args['id'] ) {
					$i = 1000;
					$ordered_posts = array();
					foreach( $the_posts as & $the_post ) {
						$the_post->series_meta = $this->get_postmeta( $the_post->ID, $args['id'], array() );
						//$the_post->menu_order = isset( $the_post->series_meta['in_series_order'] ) ? $the_post->series_meta['in_series_order'] : '0';
						if ( isset( $the_post->series_meta['in_series_order'] ) && $the_post->series_meta['in_series_order'] )
							$order_key = intval( $the_post->series_meta['in_series_order'] ) * $i;
						else
							//$order_key = -2 * $i;
							$order_key = strtotime( $the_post->post_date );
						$the_post->menu_order = $order_key;
						$ordered_posts[$order_key] = $the_post;
						$i++;
					}

					if ( $args['order'] == 'DESC' )
						ksort( $ordered_posts, SORT_NUMERIC );
					else
						krsort( $ordered_posts, SORT_NUMERIC );
					$the_posts = $ordered_posts;
					unset( $ordered_posts, $the_post, $i );
				}

				$offset = 1;
				$more = false;
				$output = $args['title'].'<'.$args['list'].' class="post-series-list">';
				foreach( $the_posts as $post ) {
					setup_postdata( $post );
					if ( $args['cb'] ) {
						$output .= call_user_func_array( $args['cb'], array( $post, $args, $offset ) );
					} else {
						if( $post->post_status == 'publish' )
							$link = '<span class="the-title in-series-publish"><a href="'.get_permalink( $post->ID ).'">'.get_the_title( $post->ID ).'</a>';
						else
							$link = '<span class="the-title in-series-future">'.get_the_title( $post->ID ).'</span>';

						if ( $args['hide'] > 1 && $offset > $args['hide'] ) {
							$output .= '<li class="in-series-hidden in-series-hidden-'.$args['id'].'">';
							if ( ! $more ) {
								$output .= '<li class="in-series-more" id="in-series-more-'.$args['id'].'" style="display:none;"><a href="#" title="'._x( 'More in this series', 'series hide link title', GEDITORIAL_TEXTDOMAIN ).'">'._x( 'More&hellip;', 'series hide link', GEDITORIAL_TEXTDOMAIN ).'</li>';
								$more = true;
							}
						} else {
							$output .= '<li>';
						}

						$output .= $args['li_before'].$link;
						if ( isset( $post->series_meta['in_series_title'] ) )
							$output .= '<br /><span class="in-series-title">'.$post->series_meta['in_series_title'] .'</span>';
						if ( isset( $post->series_meta['in_series_desc'] ) )
							$output .= '<div class="in-series-desc summary">'.wpautop( $post->series_meta['in_series_desc'] ).'</div>';
						//$output .= '<br />'.$post->menu_order;
						$output .= '</li>';
					}
					$offset++;
				}
				wp_reset_query();

				// $the_series = get_term_by( 'id', $args['id'], $this->module->constants['series_tax'] );
				// $output .= '<br />'.$the_series->name;

				$output .= '</'.$args['list'].'>';

				if ( $more ) {
					$output .= '<script>
						jQuery(document).ready(function($) {
							$("li.in-series-hidden-'.$args['id'].'").slideUp();
							$("li#in-series-more-'.$args['id'].' a").click(function(e){
								e.preventDefault();
								$(this).slideUp();
								$("li.in-series-hidden-'.$args['id'].'").slideDown();
							});
						});
					</script>';
				}

				if ( ! is_null( $args['context'] ) )
					$output = '<div class="series-'.sanitize_html_class( $args['context'], 'general' ).'">'.$output.'</div>';

				$output = $args['before'].$output.$args['after'];

				wp_cache_set( $key, $output, $this->module->constants['series_shortcode'] );
				return $output;
			}
		}
		return null;
	}

	public function shortcode_multiple_series( $atts, $content = null, $tag = '' )
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
			'context'   => null,
			'args'      => array(),
		), $atts, $this->module->constants['multiple_series_shortcode'] );

		if ( false === $args['context'] )
			return null;

		if ( empty( $args['ids'] ) || ! count( $args['ids'] ) ) {
			$terms = wp_get_object_terms( (int) $post->ID, $this->module->constants['series_tax'], array(
				'order'   => $args['order'],
				'orderby' => $args['orderby'],
				'fields'  => 'ids',
			) );
			$args['ids'] = is_wp_error( $terms ) ? array() : $terms;
		}

		$output = '';
		foreach ( $args['ids'] as $id )
			$output .= $this->shortcode_series( array_merge( array(
				'id' => $id,
				'title_tag' => 'h4',
			), $args['args'] ), null, $this->module->constants['series_shortcode'] );

		if ( ! empty( $output ) ) {
			if( $args['title'] )
				$output = '<'.$args['title_tag'].' class="post-series-wrap-title">'.$args['title'].'</'.$args['title_tag'].'>'.$output;
			if ( ! is_null( $args['context'] ) )
				$output = '<div class="multiple-series-'.sanitize_html_class( $args['context'], 'general' ).'">'.$output.'</div>';
			return $args['before'].$output.$args['after'];
		}
		return null;
	}

	public function mce_buttons( $buttons )
	{
		array_push( $buttons, '|', 'ge_series' );
		return $buttons;
	}

	public function mce_external_plugins( $plugin_array )
	{
		$plugin_array['ge_series'] = GEDITORIAL_URL.'assets/js/geditorial/tinymce.series.js';
		return $plugin_array;
	}
}
