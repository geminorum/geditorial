<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMeta extends gEditorialModuleCore
{

	public $meta_key = '_gmeta';
	protected $priority_init = 12;

	public static function module()
	{
		return array(
			'name'     => 'meta',
			'title'    => _x( 'Meta', 'Meta Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Metadata, magazine style.', 'Meta Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon' => 'tag',
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
			'ct_tax' => 'label',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'titles' => array(
				'post' => array(
					'ot'          => __( 'OverTitle', GEDITORIAL_TEXTDOMAIN ),
					'st'          => __( 'SubTitle', GEDITORIAL_TEXTDOMAIN ),
					'as'          => __( 'Author', GEDITORIAL_TEXTDOMAIN ),
					'le'          => __( 'Lead', GEDITORIAL_TEXTDOMAIN ),
					'ch'          => __( 'Column Header', GEDITORIAL_TEXTDOMAIN ),
					'ct'          => __( 'Column Header Taxonomy', GEDITORIAL_TEXTDOMAIN ),
					'ch_override' => __( 'Column Header Override', GEDITORIAL_TEXTDOMAIN ),

					'source_title' => _x( 'Source Title', 'Meta Module: Titles', GEDITORIAL_TEXTDOMAIN ),
					'source_url'   => _x( 'Source URL', 'Meta Module: Titles', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'descriptions' => array(
				'post' => array(
					'ot'          => __( 'String to place over the post title', GEDITORIAL_TEXTDOMAIN ),
					'st'          => __( 'String to place under the post title', GEDITORIAL_TEXTDOMAIN ),
					'as'          => __( 'String to override the post author', GEDITORIAL_TEXTDOMAIN ),
					'le'          => __( 'Editorial paragraph presented before post content', GEDITORIAL_TEXTDOMAIN ),
					'ch'          => __( 'String to reperesent that the post is on a column or section', GEDITORIAL_TEXTDOMAIN ),
					'ct'          => __( 'Taxonomy for better categorizing columns', GEDITORIAL_TEXTDOMAIN ),
					'ch_override' => __( 'Column Header Override', GEDITORIAL_TEXTDOMAIN ),

					'source_title' => _x( 'Original Title of Source Content', 'Meta Module: Descriptions', GEDITORIAL_TEXTDOMAIN ),
					'source_url'   => _x( 'Full URL to the Source of the Content', 'Meta Module: Descriptions', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'misc' => array(
				'meta_column_title'   => _x( 'Metadata', 'Meta Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'author_column_title' => _x( 'Author', 'Meta Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_title'      => _x( 'Metadata', 'Meta Module: Meta Box Title', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_action'     => _x( 'Configure', 'Meta Module: Meta Box Action Title', GEDITORIAL_TEXTDOMAIN ),
			),
			'noops' => array(
				'ct_tax' => _nx_noop( 'Column Header', 'Column Headers', 'Meta Module: Noop', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	protected function get_global_fields()
	{
		return array(
			'post' => array(
				'ot' => array( 'type' => 'title_before' ),
				'st' => array( 'type' => 'title_after' ),
				'le' => array( 'type' => 'box' ),
				'as' => array( 'type' => 'text' ),
				'ct' => array(
					'type' => 'term',
					'tax'  => $this->constant( 'ct_tax' ),
				),
				'ch' => array( 'type' => 'text' ),

				'source_title' => array( 'type' => 'text' ),
				'source_url'   => array( 'type' => 'link' ),
			),
			'page' => array(
				'ot' => array( 'type' => 'title_before' ),
				'st' => array( 'type' => 'title_after' ),
			),
		);
	}

	public function setup( $partials = array() )
	{
		parent::setup( array(
			'templates',
		) );
	}

	public function setup_ajax( $request )
	{
		if ( ( $post_type = empty( $request['post_type'] ) ? FALSE : $request['post_type'] ) ) {

			if ( in_array( $post_type, $this->post_types() ) ) {

				add_filter( 'manage_'.$post_type.'_posts_columns', array( $this, 'manage_posts_columns' ) );
				add_filter( 'manage_'.$post_type.'_posts_custom_column', array( $this, 'custom_column'), 10, 2 );

				add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
				add_filter( 'geditorial_meta_sanitize_post_meta', array( $this, 'sanitize_post_meta' ), 10, 4 );
			}
		}
	}

	public function tweaks_strings( $strings )
	{
		$this->tweaks = TRUE;

		$new = array(
			'taxonomies' => array(
				$this->constant( 'ct_tax' ) => array(
					'column'     => 'taxonomy-'.$this->constant( 'ct_tax' ),
					'dashicon'   => 'admin-post',
					'title_attr' => $this->get_string( 'name', 'ct_tax', 'labels' ),
				),
			),
		);

		return self::recursiveParseArgs( $new, $strings );
	}

	public function init()
	{
		do_action( 'geditorial_meta_init', $this->module );

		$this->do_globals();

		$ct_tax_posttypes = array();

		foreach ( $this->post_types() as $post_type )
			if ( in_array( 'ct', $this->post_type_fields( $post_type ) ) )
				$ct_tax_posttypes[] = $post_type;

		if ( count( $ct_tax_posttypes ) )
			$this->register_taxonomy( 'ct_tax', array(
				'meta_box_cb' => FALSE,
			), $ct_tax_posttypes );

		$post_cpt = $this->constant( 'post_cpt' );

		// default fields for custom post types
		foreach ( apply_filters( 'geditorial_meta_support_post_types', array( $post_cpt ) ) as $post_type )
			$this->add_post_type_fields( $post_type, $this->fields[$post_cpt] );

		$this->add_post_type_fields( $this->constant( 'page_cpt' ) );
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'post' == $screen->base ) {

				// we use this to override the whole functionality, not just adding the actions
				// $box_func = apply_filters( 'geditorial_meta_box_callback', array( $this, 'default_meta_box_old' ), $screen->post_type );
				$box_func = apply_filters( 'geditorial_meta_box_callback', TRUE, $screen->post_type );

				if ( TRUE === $box_func )
					$box_func = array( $this, 'default_meta_box' );

				if ( is_callable( $box_func ) )
					add_meta_box( 'geditorial-meta-'.$screen->post_type, $this->get_meta_box_title(), $box_func, $screen->post_type, 'side', 'high' );

				// $dbx_func = apply_filters( 'geditorial_meta_dbx_callback', array( $this, 'default_meta_raw_old' ), $screen->post_type );
				$dbx_func = apply_filters( 'geditorial_meta_dbx_callback', TRUE, $screen->post_type );

				if ( TRUE === $dbx_func )
					$dbx_func = array( $this, 'default_meta_raw' );

				if ( is_callable( $dbx_func ) )
					add_action( 'dbx_post_sidebar', $dbx_func, 10, 1 );

				add_filter( 'geditorial_meta_sanitize_post_meta', array( $this, 'sanitize_post_meta' ), 10, 4 );

				add_action( 'geditorial_meta_do_meta_box', array( $this, 'do_meta_box' ), 10, 4 );

			} else if ( 'edit' == $screen->base ) {

				add_filter( 'manage_'.$screen->post_type.'_posts_columns', array( $this, 'manage_posts_columns' ) );
				add_filter( 'manage_'.$screen->post_type.'_posts_custom_column', array( $this, 'custom_column'), 10, 2 );

				add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_custom_box' ), 10, 2 );
			}

			if ( 'post' == $screen->base
				|| 'edit' == $screen->base ) {

				add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );

				$localize = array(
					'constants' => array(
						'ct' => $this->constant( 'ct_tax' ),
					),
				);

				foreach ( $this->post_type_fields( $screen->post_type ) as $field )
					$localize[$field] = $this->get_string( $field, $screen->post_type );

				$this->enqueue_asset_js( $localize, 'meta.'.$screen->base );
			}
		}
	}

	public function do_meta_box( $post, $box, $fields = NULL, $context = 'box' )
	{
		if ( is_null( $fields ) )
			$fields = $this->post_type_field_types( $post->post_type, TRUE );

		foreach ( $fields as $field => $args ) {

			if ( $context != $args['context'] )
				continue;

			switch ( $args['type'] ) {

				case 'text':
					gEditorialHelper::meta_admin_field( $field, array( $field ), $post, $args['ltr'], $args['title'], FALSE, $args['type'] );
				break;

				case 'code':
				case 'link':
					gEditorialHelper::meta_admin_field( $field, array( $field ), $post, TRUE, $args['title'], FALSE, $args['type'] );
				break;

				case 'number':
					gEditorialHelper::meta_admin_number_field( $field, array( $field ), $post, TRUE, $args['title'], FALSE, $args['type'] );
				break;

				case 'textarea':
					gEditorialHelper::meta_admin_textarea_field( $field, array( $field ), $post, $args['ltr'], $args['title'], FALSE, $args['type'] );
				break;

				case 'term':
					if ( $args['tax'] )
						gEditorialHelper::meta_admin_tax_field( $field, array( $field ), $post, $args['tax'], $args['ltr'], $args['title'] );
					else
						gEditorialHelper::meta_admin_field( $field, array( $field ), $post, $args['ltr'], $args['title'], FALSE, $args['type'] );
				break;
			}
		}

		wp_nonce_field( 'geditorial_meta_post_main', '_geditorial_meta_post_main' );
	}

	public function default_meta_box( $post, $box )
	{
		$fields = $this->post_type_field_types( $post->post_type, TRUE );

		echo '<div class="geditorial-admin-wrap-metabox">';

		do_action( 'geditorial_meta_box_before', $this->module, $post, $fields );

		if ( count( $fields ) ) {

			do_action( 'geditorial_meta_do_meta_box', $post, $box, $fields, 'box' );

		} else {

			echo '<div class="field-wrap field-wrap-empty">';
				_ex( 'No Meta Fields', 'Meta Module', GEDITORIAL_TEXTDOMAIN );
			echo '</div>';
		}

		do_action( 'geditorial_meta_box_after', $this->module, $post, $fields );

		echo '</div>';
	}

	public function default_meta_raw( $post )
	{
		$fields = $this->post_type_field_types( $post->post_type, TRUE );

		if ( count( $fields ) ) {

			foreach ( $fields as $field => $args ) {

				switch ( $args['type'] ) {

					case 'title_before':
					case 'title_after':
						gEditorialHelper::meta_admin_title_field( $field, array( $field ), $post, $args['ltr'], $args['title'], FALSE, $args['type'] );
					break;

					case 'box':
						gEditorialHelper::meta_admin_box_field( $field, array( $field ), $post, $args['ltr'], $args['title'] );
					break;
				}
			}
		}

		do_action( 'geditorial_meta_box_raw', $this->module, $post, $fields );

		wp_nonce_field( 'geditorial_meta_post_raw', '_geditorial_meta_post_raw' );
	}

	public function sanitize_post_meta( $postmeta, $fields, $post_id, $post_type )
	{
		if ( ! current_user_can( $post->cap->edit_post, $post_id ) )
			return $postmeta;

		if ( wp_verify_nonce( @$_REQUEST['_geditorial_meta_post_main'], 'geditorial_meta_post_main' )
			|| wp_verify_nonce( @$_REQUEST['_geditorial_meta_post_raw'], 'geditorial_meta_post_raw' ) ) {

			$fields = $this->post_type_field_types( $post_type );

			if ( count( $fields ) ) {

				foreach ( $fields as $field => $args ) {

					switch ( $args['type'] ) {

						case 'term':
							gEditorialHelper::set_postmeta_field_term( $post_id, $field, $args['tax'] );
						break;

						case 'link':
							gEditorialHelper::set_postmeta_field_url( $postmeta, $field );
						break;

						case 'code':
							gEditorialHelper::set_postmeta_field_code( $postmeta, $field );
						break;

						case 'number':
							gEditorialHelper::set_postmeta_field_number( $postmeta, $field );
						break;

						case 'text':
						case 'title_before':
						case 'title_after':

						case 'textarea':
						case 'box':
							gEditorialHelper::set_postmeta_field_string( $postmeta, $field );
						break;
					}
				}
			}
		}

		return $postmeta;
	}

	// FIXME: DEPRECATED
	public function default_meta_box_old( $post, $box )
	{
		$ch_override = FALSE;
		$fields      = $this->post_type_fields( $post->post_type );

		echo '<div class="geditorial-admin-wrap-metabox">';

		do_action( 'geditorial_meta_box_before', $this->module, $post, $fields );

		$ch_wrap = ( ( in_array( 'ct', $fields ) && in_array( 'ch', $fields ) ) ? TRUE : FALSE );

		if ( $ch_wrap ) echo '<div class="field-wrap-wrap">';

		if ( in_array( 'ct', $fields ) && self::user_can( 'view', 'ct' )  ) {
			echo '<div class="field-wrap">';
			wp_dropdown_categories( array(
				'taxonomy'          => $this->constant( 'ct_tax' ),
				'selected'          => gEditorialHelper::theTerm( $this->constant( 'ct_tax' ), $post->ID ),
				'show_option_none'  => __( '&mdash; Select &mdash;', GEDITORIAL_TEXTDOMAIN ),
				'option_none_value' => '0',
				'name'              => 'geditorial-meta-ct',
				'id'                => 'geditorial-meta-ct',
				'class'             => 'geditorial-admin-dropbown',
				'show_count'        => 1,
				'hide_empty'        => 0,
				'hide_if_empty'     => TRUE,
				// 'echo'              => 0,
				// 'exclude'           => $excludes,
			) );
			echo '</div>';
			$ch_override = TRUE;
		}

		$ch_title = $ch_override ? $this->get_string( 'ch_override', $post->post_type ) : $this->get_string( 'ch', $post->post_type );
		gEditorialHelper::meta_admin_field( 'ch', $fields, $post, FALSE, $ch_title );

		if ( $ch_wrap ) echo '</div>';

		gEditorialHelper::meta_admin_field( 'as', $fields, $post );
		// gEditorialHelper::meta_admin_field( 'es', $fields, $post, TRUE, NULL, FALSE, 'link' );
		// gEditorialHelper::meta_admin_field( 'ol', $fields, $post, TRUE, NULL, FALSE, 'link' );

		do_action( 'geditorial_meta_box_after', $this->module, $post, $fields );

		wp_nonce_field( 'geditorial_meta_post_main_old', '_geditorial_meta_post_main_old' );

		echo '</div>';
	}

	// FIXME: DEPRECATED
	public function default_meta_raw_old( $post )
	{
		$fields = $this->post_type_fields( $post->post_type );

		gEditorialHelper::meta_admin_title_field( 'ot', $fields, $post, FALSE, NULL, FALSE, 'title_before' );
		gEditorialHelper::meta_admin_title_field( 'st', $fields, $post, FALSE, NULL, FALSE, 'title_after' );
		gEditorialHelper::meta_admin_box_field( 'le', $fields, $post );

		do_action( 'geditorial_meta_box_raw', $this->module, $post, $fields );

		wp_nonce_field( 'geditorial_meta_post_raw_old', '_geditorial_meta_post_raw_old' );
	}

	public function sanitize_meta_field( $field )
	{
		if ( is_array( $field ) )
			return $field;

		$fields = array(
			'ot'                   => array( 'over_title', 'ot' ),
			'st'                   => array( 'sub_title', 'st' ),
			'over_title'           => array( 'over_title', 'ot' ),
			'sub_title'            => array( 'sub_title', 'st' ),
			'issue_number_line'    => array( 'number_line', 'issue_number_line' ),
			'issue_total_pages'    => array( 'total_pages', 'issue_total_pages' ),
			'number_line'          => array( 'number_line', 'issue_number_line' ),
			'total_pages'          => array( 'total_pages', 'issue_total_pages' ),
			'reshare_source_title' => array( 'source_title', 'reshare_source_title' ),
			'reshare_source_url'   => array( 'source_url', 'reshare_source_url' ),
			'source_title'         => array( 'source_title', 'reshare_source_title' ),
			'source_url'           => array( 'source_url', 'reshare_source_url', 'es', 'ol' ),
			'es'                   => array( 'source_url', 'es' ),
			'ol'                   => array( 'source_url', 'ol' ),
		);

		if ( isset( $fields[$field] ) )
			return $fields[$field];

		return array( $field );
	}

	public function save_post( $post_id, $post )
	{
		if ( ! $this->is_save_post( $post, $this->post_types() ) )
			return $post_id;

		// NOUNCES MUST CHECKED BY FILTERS
		// CAPABILITIES MUST CHECKED BY FILTERS : if (current_user_can($post->cap->edit_post, $post_id))

		$fields = $this->post_type_fields( $post->post_type );

		$postmeta = $this->sanitize_post_meta_old(
			$this->get_postmeta( $post_id ),
			$fields,
			$post_id,
			$post->post_type
		);

		$this->set_meta( $post_id, apply_filters( 'geditorial_meta_sanitize_post_meta', $postmeta, $fields, $post_id, $post->post_type ) );
		wp_cache_flush();

		return $post_id;
	}

	// FIXME: DEPRECATED
	private function sanitize_post_meta_old( $postmeta, $fields, $post_id, $post_type )
	{
		if ( wp_verify_nonce( @$_REQUEST['_geditorial_meta_post_main_old'], 'geditorial_meta_post_main_old' )
			|| wp_verify_nonce( @$_REQUEST['_geditorial_meta_post_raw_old'], 'geditorial_meta_post_raw_old' ) ) {

			foreach ( $fields as $field ) {
				switch ( $field ) {
					case 'ct':

						gEditorialHelper::set_postmeta_field_term( $post_id, $field, $this->constant( 'ct_tax' ) );

					break;
					case 'es':
					case 'ol':

						gEditorialHelper::set_postmeta_field_url( $postmeta, $field );

					break;
					case 'ot':
					case 'st':
					case 'ch':
					case 'as':
					case 'le':

						gEditorialHelper::set_postmeta_field_string( $postmeta, $field );
				}
			}
		}

		return $postmeta;
	}

	public function manage_posts_columns( $posts_columns )
	{
		$new_columns = array();
		$post_type   = gEditorialHelper::getCurrentPostType();
		$fields      = $this->post_type_fields( $post_type );

		foreach ( $posts_columns as $key => $value ) {

			if ( $key == 'author' ) {
				if ( in_array( 'as', $fields ) && self::user_can( 'view', 'as' ) ) {
					$new_columns['geditorial-meta-author'] = $this->get_column_title( 'author', $post_type );
				} else {
					$new_columns[$key] = $value;
				}
			} else {
				$new_columns[$key] = $value;
			}

			if ( $key == 'title' )
				if ( ( in_array( 'ot', $fields ) && self::user_can( 'view', 'ot' ) )
					|| ( in_array( 'st', $fields ) && self::user_can( 'view', 'st' ) ) )
						$new_columns['geditorial-meta-column'] = $this->get_column_title( 'meta', $post_type );
		}

		return $new_columns;
	}

	public function custom_column( $column_name, $post_id )
	{
		global $post;

		$fields = $this->post_type_fields( $post->post_type );

		switch ( $column_name ) {

			case 'author' :
			case 'gpeople' :
			case 'geditorial-meta-author' :

				if ( in_array( 'as', $fields ) && self::user_can( 'view', 'as' )  ) {
					$as = $this->get_postmeta( $post->ID, 'as', '' );
					echo '<small>'.$as.'</small>';
					echo '<div class="hidden geditorial-meta-as-value">'.$as.'</div>';
				}

				if ( 'author' != $column_name && 'gpeople' != $column_name ) {
					if ( isset( $as ) ) echo ' &mdash; ';
					printf( '<small><a href="%s">%s</a></small>',
						esc_url( add_query_arg( array(
							'post_type' => $post->post_type,
							'author' => get_the_author_meta( 'ID' )
						), 'edit.php' ) ),
						get_the_author()
					);
				}

			break;
			case 'geditorial-meta-column' :

				if ( in_array( 'ot', $fields ) && self::user_can( 'view', 'ot' ) ) {
					echo $ot = $this->get_postmeta( $post->ID, 'ot', '' );
					echo '<div class="hidden geditorial-meta-ot-value">'.$ot.'</div>';
				}

				if ( in_array( 'st', $fields ) && self::user_can( 'view', 'st' ) )  {
					if ( isset( $ot ) ) echo '<br />';
					echo $st = $this->get_postmeta( $post->ID, 'st', '' );
					echo '<div class="hidden geditorial-meta-st-value">'.$st.'</div>';
				}
		}
	}

	public function quick_edit_custom_box( $column_name, $screen )
	{
		if ( $column_name != 'geditorial-meta-column' )
			return FALSE;

		$post_type = gEditorialHelper::getCurrentPostType();
		$fields    = $this->post_type_fields( $post_type );

		foreach ( array( 'ot', 'st', 'as' ) as $field ) {
			if ( in_array( $field, $fields ) && self::user_can( 'edit', $field )  ) {
				$selector = 'geditorial-meta-'.$field;
				echo '<label class="'.$selector.'">';
					echo '<span class="title">'.$this->get_string( $field, $post_type ).'</span>';
					echo '<span class="input-text-wrap"><input type="text" name="'.$selector.'" class="ptitle '.$selector.'" value=""></span>';
				echo '</label>';
			}
		}

		wp_nonce_field( 'geditorial_meta_post_raw_old', '_geditorial_meta_post_raw_old' );
	}

	public function tools_messages( $messages, $sub )
	{
		if ( $this->module->name == $sub ) {

			if ( isset( $_REQUEST['field'] ) && $_REQUEST['field'] ) {
				$field = $this->get_string( $_REQUEST['field'] );

				$messages['converted'] = self::success( sprintf( __( 'Field %s Converted', GEDITORIAL_TEXTDOMAIN ), $field ) );
				$messages['deleted']   = self::success( sprintf( __( 'Field %s Deleted', GEDITORIAL_TEXTDOMAIN ), $field ) );

			} else {
				$messages['converted'] = $messages['deleted'] = self::error( __( 'No Field', GEDITORIAL_TEXTDOMAIN ) );
			}
		}

		return $messages;
	}

	public function tools_sub( $uri, $sub )
	{
		echo '<form method="post" action="">';

			$this->tools_field_referer( $sub );

			echo '<h3>'.__( 'Meta Tools', GEDITORIAL_TEXTDOMAIN ).'</h3>';
			echo '<table class="form-table">';

			echo '<tr><th scope="row">'.__( 'Import Custom Fields', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			if ( ! empty( $_POST ) && isset( $_POST['custom_fields_check'] ) ) {
				if ( isset( $_POST[$this->module->group]['tools'] ) ) {
					$post = $_POST[$this->module->group]['tools'];
					$limit = isset( $post['custom_field_limit'] ) ? stripslashes( $post['custom_field_limit'] ) : FALSE;

					if ( isset( $post['custom_field'] ) ) {
						gEditorialHelper::tableList( array(
							'post_id' => 'Post ID',
							'meta'    => 'Meta :'.$post['custom_field'],
						), gEditorialHelper::getDBPostMetaRows( stripslashes( $post['custom_field'] ), $limit ) );
						echo '<br />';
					}
				}
			}

			$this->do_settings_field( array(
				'type'       => 'select',
				'field'      => 'custom_field',
				'values'     => gEditorialHelper::getDBPostMetaKeys( TRUE ),
				'default'    => ( isset( $post['custom_field'] ) ? $post['custom_field'] : '' ),
				'name_group' => 'tools',
			) );

			$this->do_settings_field( array(
				'type'        => 'text',
				'field'       => 'custom_field_limit',
				'default'     => ( isset( $post['custom_field_limit'] ) ? $post['custom_field_limit'] : '' ),
				'name_group'  => 'tools',
				'field_class' => 'small-text',
			) );

			$this->do_settings_field( array(
				'type'       => 'select',
				'field'      => 'custom_field_into',
				// 'values'     => $this->post_type_fields_list( 'post', array( 'ct' => $this->get_string( 'ct', 'post' ) ) ),
				'values'     => $this->post_type_fields_list(),
				'default'    => ( isset( $post['custom_field_into'] ) ? $post['custom_field_into'] : '' ),
				'name_group' => 'tools',
			) );

			echo gEditorialHelper::html( 'p', array(
				'class' => 'description',
			), __( 'Check for Custom Fields and import them into Meta', GEDITORIAL_TEXTDOMAIN ) );

			echo '<p class="submit">';

				submit_button( __( 'Check', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'custom_fields_check', FALSE, array( 'default' => 'default' ) ); echo '&nbsp;&nbsp;';
				submit_button( __( 'Covert', GEDITORIAL_TEXTDOMAIN ), 'primary', 'custom_fields_convert', FALSE ); echo '&nbsp;&nbsp;';
				submit_button( __( 'Delete', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'custom_fields_delete', FALSE ); //echo '&nbsp;&nbsp;';

			echo '</p>';
			echo '</td></tr>';
			echo '</table>';
		echo '</form>';
	}

	public function tools_settings( $sub )
	{
		if ( ! current_user_can( $this->tools_cap ) )
			return;

		if ( $this->module->name == $sub ) {
			if ( ! empty( $_POST ) ) {

				$this->tools_check_referer( $sub );

				if ( isset( $_POST['custom_fields_convert'] ) ) {

					if ( isset( $_POST[$this->module->group]['tools'] ) ) {

						$post   = $_POST[$this->module->group]['tools'];
						$limit  = isset( $post['custom_field_limit'] ) ? $post['custom_field_limit'] : '25';
						$result = FALSE;

						if ( isset( $post['custom_field'] ) && isset( $post['custom_field_into'] ) )
							$result = $this->import_from_meta( $post['custom_field'], $post['custom_field_into'], $limit );

						if ( count( $result ) )
							self::redirect( add_query_arg( array(
								'message' => 'converted',
								'field'   => $post['custom_field'],
								'limit'   => $limit,
								'count'   => count( $result ),
							), wp_get_referer() ) );
					}

				} else if ( isset( $_POST['custom_fields_delete'] ) ) {

					if ( isset( $_POST[$this->module->group]['tools'] ) ) {

						$post   = $_POST[$this->module->group]['tools'];
						$limit  = isset( $post['custom_field_limit'] ) ? $post['custom_field_limit'] : '';
						$result = FALSE;

						if ( isset( $post['custom_field'] ) )
							$result = gEditorialHelper::deleteDBPostMeta( $post['custom_field'], $limit );

						if ( count( $result ) )
							self::redirect( add_query_arg( array(
								'message' => 'deleted',
								'field'   => $post['custom_field'],
								'limit'   => $limit,
								'count'   => count( $result ),
							), wp_get_referer() ) );
					}
				}
			}

			add_filter( 'geditorial_tools_messages', array( $this, 'tools_messages' ), 10, 2 );
			add_action( 'geditorial_tools_sub_'.$this->module->name, array( $this, 'tools_sub' ), 10, 2 );
		}

		add_filter( 'geditorial_tools_subs', array( $this, 'tools_subs' ) );
	}

	protected function import_from_meta( $meta_key, $field, $limit = FALSE )
	{
		$rows = gEditorialHelper::getDBPostMetaRows( $meta_key, $limit );

		foreach ( $rows as $row ) {

			$meta = explode( ',', $row->meta );
			$meta = (array) apply_filters( 'geditorial_meta_import_pre', $meta, $row->post_id, $meta_key, $field );

			switch ( $field ) {
				case 'ct' :
					$this->import_to_terms( $meta, $row->post_id, $this->constant( 'ct_tax' ) );
				break;

				default :
					$this->import_to_meta( $meta, $row->post_id, $field );
				break;
			}
		}

		return count( $rows );
	}

	public function import_to_meta( $meta, $post_id, $field, $kses = TRUE )
	{
		$final = '';

		foreach ( $meta as $i => $val ) {
			$val = trim( $val );

			if ( empty( $val ) )
				continue;

			$formatted = apply_filters( 'string_format_i18n', $val );
			$final .= $kses ? gEditorialHelper::kses( $formatted ) : $formatted;
		}

		if ( $final ) {
			$postmeta = $this->get_postmeta( $post_id );
			$postmeta[$field] = $final;
			$this->set_meta( $post_id, $postmeta );
		}
	}

	protected function import_to_terms( $meta, $post_id, $taxonomy )
	{
		$terms = array();

		foreach ( $meta as $term_name ) {
			$term_name = trim( strip_tags( $term_name ) );

			if ( empty( $term_name ) )
				continue;

			$formatted = apply_filters( 'string_format_i18n', $term_name );
			$term = get_term_by( 'name', $formatted, $taxonomy, ARRAY_A );

			if ( ! $term ) {
				$term = wp_insert_term( $formatted, $taxonomy );

				if ( is_wp_error( $term ) ) {
					$this->errors[$term_name] = $term->get_error_message();
					continue;
				}
			}

			$terms[] = (int) $term['term_id'];
		}

		return wp_set_post_terms( $post_id, $terms, $taxonomy, TRUE );
	}
}
