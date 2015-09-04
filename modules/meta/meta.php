<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMeta extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'meta';
	var $meta_key    = '_gmeta';
	var $_errors     = array();

	public function __construct()
	{
		global $gEditorial;

		// FIXME: MUST DEPRECATE: at this point, there's no way knowing if the module is active or not!
		do_action( 'geditorial_meta_include' );

		$args = array(
			'title'                => __( 'Meta', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Metadata, magazine style.', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( 'Meta extended desc', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'             => 'tag',
			'slug'                 => 'meta',
			'load_frontend'        => TRUE,

			'constants' => array(
				'ct_tax' => 'label',
			),

			// FIXME: MUST DEPRECATE: this filter is causing much trouble!!
			'default_options' => apply_filters( 'geditorial_meta_default_options', array(
				'enabled'  => FALSE,
				'settings' => array(),

				'post_types' => array(
					'post' => TRUE,
					'page' => FALSE,
				),
				'post_fields' => array(
					'ot' => TRUE, // over-title
					'st' => TRUE, // sub-title
					'as' => TRUE, // author simple
					'le' => FALSE, // lead
					'ch' => TRUE, // column header
					'ct' => FALSE, // column header taxonomy
					'es' => TRUE, // external link (source)
					'ol' => TRUE, // old link
				),
			) ),
			'settings' => array(
				'post_types_option' => 'post_types_option',
				'post_types_fields' => 'post_types_fields',
			),
			'strings' => array(
				'titles' => array(
					'post' => array(
						'ot'          => __( 'OverTitle', GEDITORIAL_TEXTDOMAIN ),
						'st'          => __( 'SubTitle', GEDITORIAL_TEXTDOMAIN ),
						'as'          => __( 'Author', GEDITORIAL_TEXTDOMAIN ),
						'le'          => __( 'Lead', GEDITORIAL_TEXTDOMAIN ),
						'ch'          => __( 'Column Header', GEDITORIAL_TEXTDOMAIN ),
						'ct'          => __( 'Column Header Taxonomy', GEDITORIAL_TEXTDOMAIN ),
						'ch_override' => __( 'Column Header Override', GEDITORIAL_TEXTDOMAIN ),
						'es'          => __( 'External Link', GEDITORIAL_TEXTDOMAIN ),
						'ol'          => __( 'Old Link', GEDITORIAL_TEXTDOMAIN ),
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
						'es'          => __( 'URL of the external source of the post', GEDITORIAL_TEXTDOMAIN ),
						'ol'          => __( 'URL of the post on a previous site', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'misc' => array(
					'meta_box_title'    => __( 'Metadata', GEDITORIAL_TEXTDOMAIN ),
					'meta_column_title' => __( 'Metadata', GEDITORIAL_TEXTDOMAIN ),
				),
				'labels' => array(
					// FIXME: MUST DEPRECATE: filter
					'ct_tax' => apply_filters( 'geditorial_meta_ct_labels', array(
						'name'                       => __( 'Column Headers', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => __( 'Column Header', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => __( 'Search Column Headers', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
						'all_items'                  => __( 'All Column Headers', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => __( 'Parent Column Header', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => __( 'Parent Column Header:', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => __( 'Edit Column Header', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => __( 'Update Column Header', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => __( 'Add New Column Header', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => __( 'New Column Header Name', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => __( 'Separate column headers with commas', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => __( 'Add or remove column headers', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => __( 'Choose from the most used column headers', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => __( 'Column Headers', GEDITORIAL_TEXTDOMAIN ),
					) ),
				),

			),
			'configure_page_cb' => 'print_configure_view',
			'settings_help_tab' => array(
				array(
					'id'      => 'geditorial-meta-overview',
					'title'   => __( 'Overview', GEDITORIAL_TEXTDOMAIN ),
					'content' => __( '<p>help-tab-content</p>', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'id'      => 'geditorial-meta-troubleshooting',
					'title'   => __( 'Troubleshooting', GEDITORIAL_TEXTDOMAIN ),
					'content' => __( '<p>help-tab-content</p>', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'settings_help_sidebar' => sprintf(
				__( '<p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p>', GEDITORIAL_TEXTDOMAIN ),
				'https://github.com/geminorum/geditorial/wiki/Modules-Meta',
				__( 'Editorial Meta Documentations', GEDITORIAL_TEXTDOMAIN ),
				'https://github.com/geminorum/geditorial' ),
		);

		$gEditorial->register_module( $this->module_name, $args );
	}

	public function setup()
	{
		add_filter( 'geditorial_tweaks_strings', array( &$this, 'tweaks_strings' ) );

		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );
		} else {
			require_once( GEDITORIAL_DIR.'modules/meta/templates.php' );
		}
	}

	public function tweaks_strings( $strings )
	{
		$new = array(
			'taxonomies' => array(
				$this->module->constants['ct_tax'] => array(
					'column'     => 'taxonomy-'.$this->module->constants['ct_tax'],
					'dashicon'   => 'admin-post',
					'title_attr' => $this->get_string( 'name', 'ct_tax', 'labels' ),
				),
			),
		);

		return gEditorialHelper::parse_args_r( $new, $strings );
	}

	public function init()
	{
		do_action( 'geditorial_meta_init', $this->module );

		$this->do_filters();
		$this->register_taxonomies();
	}

	public function admin_init()
	{
		// tools actions for settings module
		if ( current_user_can( 'import' ) ) {
			add_filter( 'geditorial_tools_subs', array( &$this, 'tools_subs' ) );
			add_filter( 'geditorial_tools_messages', array( &$this, 'tools_messages' ), 10, 2 );
			add_action( 'geditorial_tools_load', array( &$this, 'tools_load' ) );
			add_action( 'geditorial_tools_sub_meta', array( &$this, 'tools_sub' ), 10, 2 );
		}

		add_action( 'admin_print_styles', array( &$this, 'admin_print_styles' ) );

		add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ), 10, 2 );
		add_action( 'add_meta_boxes', array( &$this, 'remove_meta_boxes' ), 20, 2 );
		add_action( 'save_post', array( &$this, 'save_post' ), 10, 2 );

		// SEE: http://make.wordpress.org/core/2012/12/01/more-hooks-on-the-edit-screen/
		// add_action( 'edit_form_after_title', array( $this, 'edit_form_after_title' ) );

		foreach ( $this->post_types() as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", array( &$this, 'manage_posts_columns' ) );
			add_filter( "manage_{$post_type}_posts_custom_column", array( &$this, 'custom_column'), 10, 2 );
		}

		add_action( 'quick_edit_custom_box', array( &$this, 'quick_edit_custom_box' ), 10, 2 );
		// add_action( 'bulk_edit_custom_box', array( &$this, 'bulk_edit_custom_box' ) );
	}

	public function admin_print_styles()
	{
		$screen = get_current_screen();

		if ( 'post' != $screen->base && 'edit' != $screen->base )
			return;

		if ( ! in_array( $screen->post_type, $this->post_types() ) )
			return;

		$localize = array(
			'constants' => array(
				'ct' => $this->module->constants['ct_tax'],
			),
		);

		foreach ( $this->post_type_fields( $screen->post_type ) as $field )
			$localize[$field] = $this->get_string( $field, $screen->post_type );

		$this->enqueue_asset_js( $localize, 'meta.'.$screen->base );
	}

	public function register_taxonomies()
	{
		$register_for = array();

		foreach ( $this->post_types() as $post_type )
			if ( in_array( 'ct', $this->post_type_fields( $post_type ) ) )
				$register_for[] = $post_type;

		if ( count( $register_for ) ) {
			register_taxonomy( $this->module->constants['ct_tax'], $register_for, array(
				'labels'                => $this->module->strings['labels']['ct_tax'],
				'public'                => TRUE,
				'show_in_nav_menus'     => TRUE,
				'show_ui'               => TRUE,
				'show_tagcloud'         => TRUE,
				'hierarchical'          => FALSE,
				'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
				'query_var'             => TRUE,
				'rewrite'               => array(
					'slug' => $this->module->constants['ct_tax'],
				),
				'capabilities' => array(
					'manage_terms' => 'manage_categories',
					'edit_terms'   => 'manage_categories',
					'delete_terms' => 'manage_categories',
					'assign_terms' => 'edit_posts'
				)
			) );
		}
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( ! in_array( $post_type, $this->post_types() ) )
			return;

		// we use filter to override the whole functionality, no just adding the actions
		$box_func = apply_filters( 'geditorial_meta_box_callback', array( &$this, $post_type.'_meta_box' ), $post_type );
		if ( is_callable( $box_func ) )
			add_meta_box( 'geditorial-meta-'.$post_type, $this->get_meta_box_title( $post_type ), $box_func, $post_type, 'side', 'high' );

		$dbx_func = apply_filters( 'geditorial_meta_dbx_callback', array( &$this, $post_type.'_meta_raw' ), $post_type );
		if ( is_callable( $dbx_func ) )
			add_action( 'dbx_post_sidebar', $dbx_func, 10, 1 );
	}

	public function remove_meta_boxes( $post_type, $post )
	{
		// no need to check if supported!
		remove_meta_box( 'tagsdiv-'.$this->module->constants['ct_tax'], $post_type, 'side' );
	}

	public function post_meta_box()
	{
		global $post;

		$ch_override = FALSE;
		$fields = $this->post_type_fields( $post->post_type );

		echo '<div class="geditorial-admin-wrap-metabox">';

		do_action( 'geditorial_meta_box_before', $this->module, $post, $fields );

		$ch_wrap = ( ( in_array( 'ct', $fields ) && in_array( 'ch', $fields ) ) ? TRUE : FALSE );

		if ( $ch_wrap ) echo '<div class="field-wrap-wrap">';

		if ( in_array( 'ct', $fields ) && self::user_can( 'view', 'ct' )  ) {
			echo '<div class="field-wrap">';
			wp_dropdown_categories( array(
				'taxonomy'          => $this->module->constants['ct_tax'],
				'selected'          => gEditorialHelper::theTerm( $this->module->constants['ct_tax'], $post->ID ),
				'show_option_none'  => __( '&mdash; Select a Column Header &mdash;', GEDITORIAL_TEXTDOMAIN ),
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
		gEditorialHelper::meta_admin_field( 'es', $fields, $post, TRUE );
		gEditorialHelper::meta_admin_field( 'ol', $fields, $post, TRUE );

		do_action( 'geditorial_meta_box_after', $this->module, $post, $fields );

		wp_nonce_field( 'geditorial_meta_post_main', '_geditorial_meta_post_main' );

		echo '</div>';
	}

	public function post_meta_raw()
	{
		global $post;
		$fields = $this->post_type_fields( $post->post_type );

		gEditorialHelper::meta_admin_title_field( 'ot', $fields, $post );
		gEditorialHelper::meta_admin_title_field( 'st', $fields, $post );
		gEditorialHelper::meta_admin_text_field( 'le', $fields, $post );

		do_action( 'geditorial_meta_box_raw', $this->module, $post, $fields );

		wp_nonce_field( 'geditorial_meta_post_raw', '_geditorial_meta_post_raw' );
	}

	public function edit_form_after_title()
	{
		echo 'edit_form_after_title';
	}

	public function save_post( $post_id, $post )
	{
		if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			|| empty( $_POST )
			|| $post->post_type == 'revision' )
				return $post_id;

		if ( ! in_array( $post->post_type, $this->post_types() ) )
			return $post_id;

		// NOUNCES MUST CHECKED BY FILTERS
		// CAPABILITIES MUST CHECKED BY FILTERS : if (current_user_can($post->cap->edit_post, $post_id))

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
		if ( wp_verify_nonce( @$_REQUEST['_geditorial_meta_post_main'], 'geditorial_meta_post_main' )
			|| wp_verify_nonce( @$_REQUEST['_geditorial_meta_post_raw'], 'geditorial_meta_post_raw' ) ) {

			foreach ( $fields as $field ) {
				switch ( $field ) {
					case 'ct' :
						if ( isset( $_POST['geditorial-meta-ct'] ) && '0' != $_POST['geditorial-meta-ct'] )
							wp_set_object_terms( $post_id, intval( $_POST['geditorial-meta-ct'] ), $this->module->constants['ct_tax'], FALSE );
						else if ( isset( $_POST['geditorial-meta-ct'] ) && '0' == $_POST['geditorial-meta-ct'] )
							wp_set_object_terms( $post_id, NULL, $this->module->constants['ct_tax'], FALSE );
					break;

					case 'es' :
					case 'ol' :
						if ( isset( $_POST['geditorial-meta-'.$field] )
							&& strlen( $_POST['geditorial-meta-'.$field] ) > 0
							&& $this->get_string( $field, $post_type ) !== $_POST['geditorial-meta-'.$field] )
								$postmeta[$field] = esc_url( $_POST['geditorial-meta-'.$field] );
						else if ( isset( $postmeta[$field] ) && isset( $_POST['geditorial-meta-'.$field] )  )
							unset( $postmeta[$field] );
					break;

					case 'ch' :
						if ( isset( $_POST['geditorial-meta-'.$field] )
							&& strlen( $_POST['geditorial-meta-'.$field] ) > 0
							&& $this->get_string( $field, $post_type ) !== $_POST['geditorial-meta-'.$field]
							&& $this->get_string( $field.'_override', $post_type ) !== $_POST['geditorial-meta-'.$field] )
								$postmeta[$field] = $this->kses( $_POST['geditorial-meta-'.$field] );
						else if ( isset( $postmeta[$field] ) && isset( $_POST['geditorial-meta-'.$field] )  )
							unset( $postmeta[$field] );
					break;

					case 'ot' :
					case 'st' :
					case 'as' :
					case 'le' :
						if ( isset( $_POST['geditorial-meta-'.$field] )
							&& strlen( $_POST['geditorial-meta-'.$field] ) > 0
							&& $this->get_string( $field, $post_type ) !== $_POST['geditorial-meta-'.$field] )
								$postmeta[$field] = $this->kses( $_POST['geditorial-meta-'.$field] );
						else if ( isset( $postmeta[$field] ) && isset( $_POST['geditorial-meta-'.$field] ) )
							unset( $postmeta[$field] );
					break;
				}
			}
		}

		return apply_filters( 'geditorial_meta_sanitize_post_meta', $postmeta, $fields, $post_id, $post_type );
	}

	public function manage_posts_columns( $posts_columns )
	{
		$new_columns = array();
		$post_type   = gEditorialHelper::getCurrentPostType();
		$fields      = $this->post_type_fields( $post_type );

		foreach ( $posts_columns as $key => $value ) {

			if ( $key == 'author' ) {
				if ( in_array( 'as', $fields ) && self::user_can( 'view', 'as' ) ) {
					$new_columns['geditorial-meta-author'] = $this->get_string( 'as', $post_type );
				} else {
					$new_columns[$key] = $value;
				}
			} else {
				$new_columns[$key] = $value;
			}

			if ( $key == 'title' )
				$new_columns['geditorial-meta-column'] = $this->get_string( 'meta_column_title', $post_type, 'misc' );
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

				if ( 'author' != $column_name && 'gpeople' != $column_name )
					printf( '<small><a href="%s">%s</a></small>',
						esc_url( add_query_arg( array(
							'post_type' => $post->post_type,
							'author' => get_the_author_meta( 'ID' )
						), 'edit.php' ) ),
						get_the_author()
					);

			break;
			case 'geditorial-meta-column' :

				echo $ot = $this->get_postmeta( $post->ID, 'ot', '' );
				echo '<br />';
				echo $st = $this->get_postmeta( $post->ID, 'st', '' );
				echo '<div class="hidden geditorial-meta-ot-value">'.$ot.'</div>';
				echo '<div class="hidden geditorial-meta-st-value">'.$st.'</div>';
			break;
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

		wp_nonce_field( 'geditorial_meta_post_raw', '_geditorial_meta_post_raw' );
	}

	public function tools_subs( $subs )
	{
		$subs['meta'] = _x( 'Meta', 'Meta: tools tab title', GEDITORIAL_TEXTDOMAIN );
		return $subs;
	}

	public function tools_messages( $messages, $sub )
	{
		if ( 'meta' == $sub ) {
			if ( isset( $_GET['field'] ) && $_GET['field'] ) {
				$field = $this->get_string( $_GET['field'] );
				$messages['converted'] = gEditorialHelper::notice( sprintf( __( 'Field %s Converted', GEDITORIAL_TEXTDOMAIN ), $field ), 'updated fade', FALSE );
				$messages['deleted'] = gEditorialHelper::notice( sprintf( __( 'Field %s Deleted', GEDITORIAL_TEXTDOMAIN ), $field ), 'updated fade', FALSE );
			} else {
				$messages['converted'] = $messages['deleted'] = gEditorialHelper::notice( __( 'No Field', GEDITORIAL_TEXTDOMAIN ), 'error', FALSE );
			}
		}
		return $messages;
	}

	public function tools_sub( $settings_uri, $sub )
	{
		echo '<form method="post" action="">';
			echo '<h3>'.__( 'Meta Tools', GEDITORIAL_TEXTDOMAIN ).'</h3>';
			echo '<table class="form-table">';

			echo '<tr><th scope="row">'.__( 'Maintenance Tasks', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

				echo '<p class="submit">';
					submit_button( __( 'Empty', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'custom_fields_empty', FALSE ); echo '&nbsp;&nbsp;';

					echo gEditorialHelper::html( 'span', array(
						'class' => 'description',
					), __( 'Will delete empty meta values, solves common problems with imported posts.', GEDITORIAL_TEXTDOMAIN ) );
				echo '</p>';

			echo '</td></tr>';
			echo '<tr><th scope="row">'.__( 'Import Custom Fields', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			if ( ! empty( $_POST ) && isset( $_POST['custom_fields_check'] ) ) {
				if ( isset( $_POST[$this->module->options_group_name]['tools'] ) ) {
					$post = $_POST[$this->module->options_group_name]['tools'];
					$limit = isset( $post['custom_field_limit'] ) ? stripslashes( $post['custom_field_limit'] ) : FALSE;

					if ( isset( $post['custom_field'] ) ) {
						gEditorialHelper::table( array(
							'post_id' => 'Post ID',
							'meta' => 'Meta :'.$post['custom_field'],
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
				'type'       => 'text',
				'field'      => 'custom_field_limit',
				'default'    => ( isset( $post['custom_field_limit'] ) ? $post['custom_field_limit'] : '' ),
				'name_group' => 'tools',
				'class'      => 'small-text',
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
			wp_referer_field();
		echo '</form>';
	}

	public function tools_load( $sub )
	{
		if ( 'meta' == $sub ) {
			if ( ! empty( $_POST ) ) {

				// check_admin_referer( 'geditorial_'.$sub.'-options' );

				if ( isset( $_POST['custom_fields_empty'] ) ) {

					$result = gEditorialHelper::deleteEmptyMeta( $this->meta_key );

					if ( $result ) {
						wp_redirect( add_query_arg( array(
							'message' => 'emptied',
						), wp_get_referer() ) );
						exit();
					}

				} else if ( isset( $_POST['custom_fields_convert'] ) ) {

					if ( isset( $_POST[$this->module->options_group_name]['tools'] ) ) {

						$post   = $_POST[$this->module->options_group_name]['tools'];
						$limit  = isset( $post['custom_field_limit'] ) ? $post['custom_field_limit'] : '25';
						$result = FALSE;

						if ( isset( $post['custom_field'] ) && isset( $post['custom_field_into'] ) )
							$result = $this->import_from_meta( $post['custom_field'], $post['custom_field_into'], $limit );

						if ( $result ) {
							wp_redirect( add_query_arg( array(
								'message' => 'converted',
								'field'   => $post['custom_field'],
								'limit'   => $limit,
							), wp_get_referer() ) );
							exit();
						}
					}
				} else if ( isset( $_POST['custom_fields_delete'] ) ) {
					if ( isset( $_POST[$this->module->options_group_name]['tools'] ) ) {

						$post   = $_POST[$this->module->options_group_name]['tools'];
						$limit  = isset( $post['custom_field_limit'] ) ? $post['custom_field_limit'] : '';
						$result = FALSE;

						if ( isset( $post['custom_field'] ) )
							$result = gEditorialHelper::deleteDBPostMeta( $post['custom_field'], $limit );

						if ( $result ) {
							wp_redirect( add_query_arg( array(
								'message' => 'deleted',
								'field'   => $post['custom_field'],
								'limit'   => $limit,
							), wp_get_referer() ) );
							exit();
						}
					}
				}
			}
		}
	}

	protected function import_from_meta( $meta_key, $field, $limit = FALSE )
	{
		foreach ( gEditorialHelper::getDBPostMetaRows( $meta_key, $limit ) as $row ) {

			$meta = explode( ',', $row->meta );
			$meta = (array) apply_filters( 'geditorial_meta_import_pre', $meta, $row->post_id, $meta_key, $field );

			switch ( $field ) {
				case 'ct' :
					$this->import_to_terms( $meta, $row->post_id, $this->module->constants['ct_tax'] );
				break;

				default :
					$this->import_to_meta( $meta, $row->post_id, $field );
				break;
			}
		}

		return TRUE;
	}

	public function import_to_meta( $meta, $post_id, $field, $kses = TRUE )
	{
		$final = '';

		foreach ( $meta as $i => $val ) {
			$val = trim( $val );

			if ( empty( $val ) )
				continue;

			$formatted = apply_filters( 'string_format_i18n', $val );
			$final .= $kses ? $this->kses( $formatted ) : $formatted;
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
					$this->_errors[$term_name] = $term->get_error_message();
					continue;
				}
			}

			$terms[] = (int) $term['term_id'];
		}

		return wp_set_post_terms( $post_id, $terms, $taxonomy, TRUE );
	}
}
