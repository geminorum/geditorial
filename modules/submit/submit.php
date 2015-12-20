<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialSubmit extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'     => 'submit',
			'title'    => _x( 'Submit', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'FrontPage Submissions', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon' => 'welcome-add-page',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'editor_button',
			),
			'posttypes_option' => 'posttypes_option',
			'fields_option'    => 'fields_option',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'submit_form_shortcode' => 'submit-form',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'titles' => array(
				'post' => array(
					'post_title'   => _x( 'Title', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
					'post_author'  => _x( 'Author', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
					'post_email'   => _x( 'Email', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
					'post_url'     => _x( 'URL', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
					'post_content' => _x( 'Content', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
					'post_submit'  => _x( 'Submit', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'descriptions' => array(
				'post' => array(
					'post_title'   => _x( 'Title of the application', 'Submit Module', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
					'post_author'  => _x( 'Author of the application', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
					'post_email'   => _x( 'Contact email', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
					'post_url'     => _x( 'URL for more information', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
					'post_content' => _x( 'Content of the application', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
					'post_submit'  => _x( 'Submit the application', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
				),
			),
		);
	}

	protected function get_global_fields()
	{
		return array(
			$this->constant( 'post_cpt' ) => array(
				'post_title'   => TRUE,
				'post_content' => TRUE,
				'post_author'  => TRUE,
			),
		);
	}

	public function init()
	{
		do_action( 'geditorial_submit_init', $this->module );

		$this->do_globals();

		add_shortcode( $this->constant( 'submit_form_shortcode' ), array( $this, 'shortcode_submit_form' ) );
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
			'user_cant_text'     => _x( 'You can not post here!', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
			'default_user'       => gEditorialHelper::getEditorialUserID(), // user id if not logged in
			'default_post_type'  => 'post',
			'default_status'     => 'pending',
			'default_title'      => FALSE,
			'default_terms'      => FALSE, // if set then like : category:12,11|post_tag:3|people:58
			'notification_email' => FALSE, // if set then : set of recepients
			'allow_html'         => FALSE,
			'class'              => 'editorial-submit',
			'title'              => _x( 'Submit Your Application', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
			'title_wrap'         => 'h3',
			'field_wrap'         => 'div',
			'field_wrap_class'   => 'editorial-submit-field',

			'horizontal' => 3, // FALSE to disable, number of bootstrap col on horizontal form
			'size'       => 'sm', // bootstrap size

		), $atts, $this->constant( 'submit_form_shortcode' ) );

		if ( ! is_user_logged_in() && $args['must_logged_in'] ) {

			if ( FALSE !== $args['must_logged_in'] )
				self::redirect( $args['must_logged_in'] );

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
							), sprintf( _x( 'Your post (%s) submitted successfully.', 'Submit Module', GEDITORIAL_TEXTDOMAIN ), $current['post_title'] ) );

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
					), _x( 'Please try again.', 'Submit Module', GEDITORIAL_TEXTDOMAIN ) );

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
			'type'  => 'hidden',
			'name'  => 'stage',
			'value' => 'editorial-submit-new',
		), FALSE );

		$fields_hidden .= gEditorialHelper::html( 'input', array(
			'type'  => 'hidden',
			'name'  => 'editorial-submit',
			'value' => '1',
		), FALSE );

		$id_nonce = mt_rand();
		$fields_hidden .= gEditorialHelper::html( 'input', array(
			'type'  => 'hidden',
			'name'  => 'editorial_submit_form_id',
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
		foreach ( $form_fields as $form_field ) {

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
					'aria-hidden' => 'true',
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
		), $this->get_string( 'post_submit', $args['default_post_type'], 'titles', _x( 'Submit', 'Submit Module', GEDITORIAL_TEXTDOMAIN ) ) );

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
			$current['errors']->add( 'post_content',  _x( 'Content can not be empty.', 'Submit Module', GEDITORIAL_TEXTDOMAIN ) );

		$current['post_content'] = wp_kses( $current['post_content'], ( $args['allow_html'] ? NULL : array() ) );

		if ( empty( $current['post_content'] ) )
			$current['errors']->add( 'post_content',  _x( 'Sorry, we can not accept this.', 'Submit Module', GEDITORIAL_TEXTDOMAIN ) );

		if ( ! is_user_logged_in() ) {
			if ( empty( $current['post_author'] ) )
				$current['errors']->add( 'post_author',  _x( 'Please enter your name', 'Submit Module', GEDITORIAL_TEXTDOMAIN ) );

			$current['post_author'] = wp_strip_all_tags( $current['post_author'] );

			if ( empty( $current['post_author'] ) )
				$current['errors']->add( 'post_author',  _x( 'Sorry, we can not accept this.', 'Submit Module', GEDITORIAL_TEXTDOMAIN ) );

			$current['post_email'] = sanitize_email( $current['post_email'] );

			if ( empty( $current['post_email'] ) )
				$current['errors']->add( 'post_email',  _x( 'Please enter your name', 'Submit Module', GEDITORIAL_TEXTDOMAIN ) );

			if ( is_email_address_unsafe( $current['post_email'] ) )
				$current['errors']->add( 'user_email',  _x( 'You cannot use that email address to submit posts. We are having problems with them blocking some of our email. Please use another email provider.', 'Submit Module', GEDITORIAL_TEXTDOMAIN ) );

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

		foreach ( $post_meta as $meta_key => $meta_value )
			update_post_meta( $post_id, $this->meta_key.'_'.$meta_key, $meta_value );

		return $post_id;
	}

	// printed on settings page
	public function intro_after()
	{
		echo '<p>';
			printf( _x( 'Use these short codes:<br /><span dir="ltr"><code>[%1$s]</code></span> for submit for on any post.', 'Submit Module', GEDITORIAL_TEXTDOMAIN ),
				$this->constant( 'submit_form_shortcode' )
			);
		echo '</p>';
	}

	// FIXME: COPY
	// Change the upload directory based on post type and file name.
	// https://gist.github.com/chrisguitarguy/4638936
	function cgg_upload_dir($dir)
	{
		// Lots of $_REQUEST usage in here, not a great idea.

		// Are we where we want to be?
		if ( ! isset( $_REQUEST['action']) || 'upload-attachment' !== $_REQUEST['action'] ) {
			return $dir;
		}

		// post types match up?
		if (!isset($_REQUEST['post_id']) || 'ml_resource' !== get_post_type($_REQUEST['post_id'] ) ) {
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
