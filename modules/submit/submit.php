<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialSubmit extends gEditorialModuleCore
{

	var $module_name = 'submit';
	var $meta_key    = '_ge_submit';

	function __construct()
	{
		global $gEditorial;

		$args = array(
			'title'                => __( 'Submit', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'FrontPage Submissions', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( 'Set of tools to accept post and images from frontpage', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'             => 'smiley',
			'slug'                 => 'submit',
			'load_frontend'        => TRUE,

			'constants' => array(
				'submit_form_shortcode' => 'submit-form',
			),
			'default_options' => array(
				'enabled' => 'off',
				'post_types' => array(
					'post' => 'on',
					'page' => 'off',
				),
				'post_fields' => array(
					'post_title' => 'on',
					'post_content' => 'on',
					'post_author' => 'on',
				),
				'settings' => array(
				),
			),
			'settings' => array(
				'_general' => array(
					array(
						'field'       => 'editor_button',
						'title'       => _x( 'Editor Button', 'Series Editor Button', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Adding an Editor Button to insert shortcodes', GEDITORIAL_TEXTDOMAIN ),
						'default'     => '1',
					),
				),
				'post_types_option' => 'post_types_option',
				'post_types_fields' => 'post_types_fields',
			),
			'strings' => array(
				'titles' => array(
					'post' => array(
						'post_title'   => __( 'Title', GEDITORIAL_TEXTDOMAIN ),
						'post_author'  => __( 'Author', GEDITORIAL_TEXTDOMAIN ),
						'post_email'   => __( 'Email', GEDITORIAL_TEXTDOMAIN ),
						'post_url'     => __( 'URL', GEDITORIAL_TEXTDOMAIN ),
						'post_content' => __( 'Content', GEDITORIAL_TEXTDOMAIN ),
						'post_submit'  => __( 'Submit', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'descriptions' => array(
					'post' => array(
						'post_title'   => __( 'Title of your application', GEDITORIAL_TEXTDOMAIN ),
						'post_author'  => __( 'Author of the application', GEDITORIAL_TEXTDOMAIN ),
						'post_email'   => __( 'Contact email', GEDITORIAL_TEXTDOMAIN ),
						'post_url'     => __( 'URL for more information', GEDITORIAL_TEXTDOMAIN ),
						'post_content' => __( 'Content of the application', GEDITORIAL_TEXTDOMAIN ),
						'post_submit'  => __( 'Submit the application', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
			'configure_page_cb' => 'print_configure_view',
			'settings_help_tab' => array(
				'id'      => 'geditorial-submit-overview',
				'title'   => __( 'help-tab-title', GEDITORIAL_TEXTDOMAIN ),
				'content' => __( '<p>help-tab-content</p>', GEDITORIAL_TEXTDOMAIN ),
			),
			'settings_help_sidebar' => sprintf(
				__( '<p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p>', GEDITORIAL_TEXTDOMAIN ),
				'http://geminorum.ir/wordpress/geditorial/modules/submit',
				__( 'Editorial Submit Documentations', GEDITORIAL_TEXTDOMAIN ),
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

		} else {

		}
	}

	public function init()
	{
		do_action( 'geditorial_submit_init', $this->module );

		$this->do_filters();

		add_shortcode( $this->module->constants['submit_form_shortcode'], array( &$this, 'shortcode_submit_form' ) );
	}

	public function admin_init()
	{
		if ( $this->get_setting( 'editor_button', TRUE )
			&& current_user_can( 'edit_posts' )
			&& get_user_option( 'rich_editing' ) == 'true' ) {
				// add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ) );
				// add_filter( 'mce_buttons', array( $this, 'mce_buttons' ) );
		}
	}

	public function shortcode_submit_form( $atts, $content = NULL, $tag = '' )
	{
		// http://wordpress.org/support/topic/plugin-contact-form-7-forms-not-sending-with-super-cache-enabled
		defined( 'DONOTCACHEPAGE' ) or define( 'DONOTCACHEPAGE', TRUE );
		defined( 'GPERSIANDATE_SKIP' ) or define( 'GPERSIANDATE_SKIP', TRUE );

		$args = shortcode_atts( array(
			'context'            => 'default', // for filtering the atts
			'must_logged_in'     => FALSE, // if must then URL to redirect
			'user_must_can'      => 'read', // capability of the logged in user to submit a post
			'user_cant_text'     => __( 'You can not post here!', GEDITORIAL_TEXTDOMAIN ),
			'default_user'       => gEditorialHelper::getEditorialUserID(), // user id if not logged in
			'default_post_type'  => 'post',
			'default_status'     => 'pending',
			'default_title'      => FALSE,
			'default_terms'      => FALSE, // if set then like : category:12,11|post_tag:3|people:58
			'notification_email' => FALSE, // if set then : set of recepients
			'allow_html'         => FALSE,
			'class'              => 'editorial-submit',
			'title'              => __( 'Submit your Application', GEDITORIAL_TEXTDOMAIN ),
			'title_wrap'         => 'h3',
			'field_wrap'         => 'div',
			'field_wrap_class'   => 'editorial-submit-field',

			'horizontal' => 3, // false to disable, number of bootstrap col on horizontal form
			'size'       => 'sm', // bootstrap size

		), $atts, $this->module->constants['submit_form_shortcode'] );

		if ( ! is_user_logged_in() && $args['must_logged_in'] ) {

			if ( FALSE !== $args['must_logged_in'] ) {
				wp_redirect( $args['must_logged_in'] );
				die();
			}

			return $content;
		}

		if ( is_user_logged_in() && ! current_user_can( $args['user_must_can'] ) ) {

			$cant = gEditorialHelper::html( 'p', array(
				'class' => 'editorial-submit-cant error',
			), $args['user_cant_text'] );

			return gEditorialHelper::html( 'div', array(
				'class' => $args['class'],
			), $cant );
		}

		$current = array(
			'stage'  => isset( $_POST['stage'] ) ? $_POST['stage'] : 'default',
			'errors' => new WP_Error(),
		);

		$fields = array( 'post_title', 'post_content', 'post_author', 'post_email', 'post_url' );
		foreach ( $fields as $field )
			$current[$field] = isset( $_POST[$field] ) ? $_POST[$field] : '';

		switch ( $current['stage'] ) {

			case 'editorial-submit-new' :

				if ( wp_create_nonce( 'editorial_submit_form_'.$_POST['editorial_submit_form_id'] ) == $_POST['_editorial_submit_form'] ) {

					$current = $this->validate_submit_form( $current, $args );

					if ( ! $current['errors']->get_error_code() ) {

						$args['default_terms'] = gEditorialHelper::parseTerms( $args['default_terms'] );
						$post_id = $this->insert_submit_form( $current, $args, apply_filters( 'editorial_submit_add_meta', array() ) );

						if ( $post_id ) {
							$confirm = gEditorialHelper::html( $args['title_wrap'], array(
								'class' => 'editorial-submit-title editorial-submit-title-confirm',
							), sprintf( __( 'Your post (%s) submitted successfully.' ), $current['post_title'] ) );

							ob_start();
							do_action( 'editorial_submit_finished', $current, $args );
							$confirm .= ob_get_clean();

							return gEditorialHelper::html( 'div', array(
								'class' => $args['class'].' editorial-submit-confirm',
							), $confirm );
						}
					} else {

						// FIXME: show errors!
						gnetwork_dump( $current['errors'] );
					}


					$error = gEditorialHelper::html( 'p', array(
						'class' => 'editorial-submit-error',
					), __( 'Please try again.' ) );

					return gEditorialHelper::html( 'div', array(
						'class' => $args['class'],
					), $error );
				}

			break;
		}

		$current = apply_filters( 'geditorial_submit_form_current', $current, $args ); // allow definition of default variables

		ob_start();
		do_action( 'editorial_submit_preprocess_form', $args );
		$pre = ob_get_clean();

		$header = gEditorialHelper::html( $args['title_wrap'], array(
			'class' => 'editorial-submit-title',
		), $args['title'] );

		$fields_hidden = gEditorialHelper::html( 'input', array(
			'type' => 'hidden',
			'name' => 'stage',
			'value' => 'editorial-submit-new',
		), FALSE );

		$fields_hidden .= gEditorialHelper::html( 'input', array(
			'type' => 'hidden',
			'name' => 'editorial-submit',
			'value' => '1',
		), FALSE );

		$id_nonce = mt_rand();
		$fields_hidden .= gEditorialHelper::html( 'input', array(
			'type' => 'hidden',
			'name' => 'editorial_submit_form_id',
			'value' => $id_nonce,
		), FALSE );
		$fields_hidden .= wp_nonce_field( 'editorial_submit_form_'.$id_nonce, '_editorial_submit_form', FALSE, FALSE );

		ob_start();
		do_action( 'editorial_submit_hidden_fields', $args );
		$fields_hidden .= ob_get_clean();

		if ( is_user_logged_in() ) {
			$form_fields = array(
				'post_title',
				'post_url',
				'post_content',
			);
		} else {
			$form_fields = array(
				'post_title',
				'post_author',
				'post_email',
				'post_url',
				'post_content',
			);
		}

		// TODO: apply filter on $form_fields

		$fields_html = '';
		foreach( $form_fields as $form_field ) {

			$form_field_label = gEditorialHelper::html( 'label', array(
				'class' => 'control-label'.( $args['horizontal'] ? ' col-'.$args['size'].'-'.$args['horizontal'] : '' ),
				'for'   => $form_field,
			), $this->get_string( $form_field, $args['default_post_type'], 'titles', '' ) );


			if ( 'post_content' == $form_field ) {

				$form_field_html = gEditorialHelper::html( 'textarea', array(
					'aria-describedby' => ( 'default' == $current['stage'] ? FALSE : $form_field.'-status' ) ,
					'name'             => $form_field,
					'id'               => $form_field,
					'class'            => 'form-control input-'.$args['size'],
					'rows'             => 3,
				), esc_textarea( $current[$form_field] ) );

			} else {

				$form_field_html = gEditorialHelper::html( 'input', array(
					'aria-describedby' => ( 'default' == $current['stage'] ? FALSE : $form_field.'-status' ) ,
					'name'             => $form_field,
					'id'               => $form_field,
					'class'            => 'form-control input-'.$args['size'],
					'type'             => 'text',
					'value'            => esc_attr( $current[$form_field] ),
				), FALSE );

			}

			$form_field_desc = $this->get_string( $form_field, $args['default_post_type'], 'descriptions', '' );

			if ( $form_field_error = $current['errors']->get_error_message( $form_field ) )
				$form_field_desc = $form_field_error;

			if ( 'default' != $current['stage'] ) {

				$form_field_html .= gEditorialHelper::html( 'span', array(
					'class'       => 'glyphicon glyphicon-'.( $form_field_error ? 'remove' : 'ok' ).' form-control-feedback',
					'aria-hidden' => true,
				), NULL );

				$form_field_html .= gEditorialHelper::html( 'span', array(
					'id'    => $form_field.'-status',
					'class' => 'sr-only',
				), ( $form_field_error ? '(error)' : '(success)' ) ); //TODO: localize!

			}

			$form_field_html .= gEditorialHelper::html( 'p', array(
				'class' => 'help-block',
			), $form_field_desc );

			if ( $args['horizontal'] )
				$form_field_html = gEditorialHelper::html( 'div', array(
					'class' => 'col-'.$args['size'].'-'.( 12 - $args['horizontal'] ),
				), $form_field_html );

			$fields_html .= gEditorialHelper::html( 'div', array(
				'class' => 'form-group form-group-'.$args['size'].( 'default' == $current['stage'] ? '' : ( $form_field_error ? ' has-error' : ' has-success' ).' has-feedback' ),
			), $form_field_label.$form_field_html );

		}

		ob_start();
		do_action( 'editorial_submit_extra_fields', $current['errors'], $args );
		$fields_extra = ob_get_clean();

		$submit = gEditorialHelper::html( 'button', array(
			'class' => 'btn btn-primary',
			'type'  => 'submit',
		), $this->get_string( 'post_submit', $args['default_post_type'], 'titles', __( 'Submit', GEDITORIAL_TEXTDOMAIN ) ) );

		if ( $args['horizontal'] )
			$submit = '<div class="col-'.$args['size'].'-'.$args['horizontal'].'">&nbsp;</div>'
				.gEditorialHelper::html( 'div', array(
					'class' => 'col-'.$args['size'].'-'.( 12 - $args['horizontal'] ),
			), $submit );

		$form = gEditorialHelper::html( 'form', array(
			'id'     => 'editorial_submit_form',
			'method' => 'post',
			'class'  => $args['horizontal'] ? 'form-horizontal' : FALSE,
			'action' => '', // TODO : get current url
		), $fields_hidden.$fields_html.$fields_extra.$submit );

		return gEditorialHelper::html( 'div', array(
			'class' => $args['class'],
		), $pre.$header.$form );
	}

	private function validate_submit_form( $current, $args )
	{
		$current['post_title'] = wp_strip_all_tags( $current['post_title'] );

		if ( empty( $current['post_content'] ) )
			$current['errors']->add( 'post_content',  __( 'Content can not be empty.', GEDITORIAL_TEXTDOMAIN ) );

		$current['post_content'] = wp_kses( $current['post_content'], ( $args['allow_html'] ? NULL : array() ) );

		if ( empty( $current['post_content'] ) )
			$current['errors']->add( 'post_content',  __( 'Sorry, we can not accept this.', GEDITORIAL_TEXTDOMAIN ) );

		if ( ! is_user_logged_in() ) {
			if ( empty( $current['post_author'] ) )
				$current['errors']->add( 'post_author',  __( 'Please enter your name', GEDITORIAL_TEXTDOMAIN ) );

			$current['post_author'] = wp_strip_all_tags( $current['post_author'] );

			if ( empty( $current['post_author'] ) )
				$current['errors']->add( 'post_author',  __( 'Sorry, we can not accept this.', GEDITORIAL_TEXTDOMAIN ) );

			$current['post_email'] = sanitize_email( $current['post_email'] );

			if ( empty( $current['post_email'] ) )
				$current['errors']->add( 'post_email',  __( 'Please enter your name', GEDITORIAL_TEXTDOMAIN ) );

			if ( is_email_address_unsafe( $current['post_email'] ) )
				$current['errors']->add( 'user_email',  __( 'You cannot use that email address to submit posts. We are having problems with them blocking some of our email. Please use another email provider.' ) );

			$current['post_url'] = esc_url( stripslashes( $current['post_url'] ) );
		}

		return $current;
	}

	private function insert_submit_form( $current, $args, $post_meta = array() )
	{
		if ( empty( $current['post_title'] ) ) {
			if ( $args['default_title'] )
				$current['post_title'] = $args['default_title'];
			else
				$current['post_title'] = gEditorialHelper::excerptedTitle( $current['post_content'], 8 );
		}

		if ( is_user_logged_in() ) {
			$current['post_author'] = get_current_user_id();
		} else {
			$post_meta['author_name']  = $current['post_author'];
			$post_meta['author_email'] = $current['post_email'];
			$post_meta['author_url']   = $current['post_url'];
			$post_meta['author_ip']    = $_SERVER['REMOTE_ADDR'];
			$current['post_author']    = $args['default_user'];
		}

		$post_data = array(
			'post_title'   => $current['post_title'],
			'post_content' => $current['post_content'],
			'post_author'  => $current['post_author'],
			'post_status'  => $args['default_status'],
			'post_type'    => $args['default_post_type'],
			'tax_input'    => ( $args['default_terms'] ? $args['default_terms'] : array() ),
		);

		$post_id = wp_insert_post( $post_data, $current['errors'] );

		if ( is_wp_error( $post_id ) )
			return FALSE;

		foreach( $post_meta as $meta_key => $meta_value )
			update_post_meta( $post_id, $this->meta_key.'_'.$meta_key, $meta_value );

		return $post_id;
	}

	// printed on congiguration page
	public function extended_description_after()
	{
		echo '<p>';
			printf( __( 'Use these short codes:<br /><span dir="ltr"><code>[%1$s]</code></span> for submit for on any post.', GEDITORIAL_TEXTDOMAIN ),
				$this->module->constants['submit_form_shortcode']
			);
		echo '</p>';
	}

	// DRAFT
	// Change the upload directory based on post type and file name.
	// https://gist.github.com/chrisguitarguy/4638936
	function cgg_upload_dir($dir)
	{
		// xxx Lots of $_REQUEST usage in here, not a great idea.

		// Are we where we want to be?
		if (!isset($_REQUEST['action']) || 'upload-attachment' !== $_REQUEST['action']) {
			return $dir;
		}

		// post types match up?
		if (!isset($_REQUEST['post_id']) || 'ml_resource' !== get_post_type($_REQUEST['post_id'])) {
			return $dir;
		}

		$exts = apply_filters('ml_resource_whitelist_ext', array('jpe?g', 'png', 'gif', 'bmp'));

		// let images to do their own thing
		if (!isset($_REQUEST['name']) || preg_match('/(' . implode('|', $exts) .')$/ui', $_REQUEST['name'])) {
			return $dir;
		}

		// modify the path and url for other files.
		$resources = apply_filters('ml_resource_directory', 'resources');
		$dir['path'] = path_join($dir['basedir'], $resources);
		$dir['url'] = path_join($dir['baseurl'], $resources);

		return $dir;
	}	// add_filter('upload_dir', 'cgg_upload_dir');
}
