<?php namespace geminorum\gEditorial\Modules\DeadDrops;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class DeadDrops extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\Rewrites;

	protected $priority_template_redirect = 5;

	public static function module()
	{
		return [
			'name'     => 'dead_drops',
			'title'    => _x( 'Dead Drops', 'Modules: Dead Drops', 'geditorial-admin' ),
			'desc'     => _x( 'Anonymous Clandestine Uploads', 'Modules: Dead Drops', 'geditorial-admin' ),
			'icon'     => 'airplane',
			'access'   => 'beta',
			'keywords' => [
				'upload',
				'publicaccess',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'posttypes_option' => 'posttypes_option',
			'_general'         => [
				[
					'field'       => 'instructions',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Signal Instructions', 'Setting Title', 'geditorial-dead-drops' ),
					'description' => _x( 'Displays beside the qr-code on the signalling pop-up. Leave blank for default.', 'Setting Description', 'geditorial-dead-drops' ),
					'placeholder' => _x( 'Scan this with your phone and open it to upload files anonymously.', 'Message', 'geditorial-dead-drops' ),
				],
			],
			'_roles' => [
				'public_roles' => [ NULL, $roles ],
			],
		];
	}

	// NOTE: WTF: `drop` not working!
	protected function get_global_constants()
	{
		return [
			'main_endpoint' => 'deaddrop',
			'main_queryvar' => 'deaddrop',

			'metakey_secret' => '_deaddrop_secret',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'metabox' => [
				/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
				'heading_title' => _x( 'Dead Drop for %1$s', 'Button Title', 'geditorial-dead-drops' ),
			],
		];

		return $strings;
	}

	public function init()
	{
		parent::init();

		$this->filter( 'determine_current_user' );
		$this->rewrites__add_endpoint( 'main' );
	}

	public function setup_restapi()
	{
		$this->filter( 'wp_handle_sideload_prefilter', 1, 8 );
	}

	public function current_screen( $screen )
	{
		if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' == $screen->base ) {

			} else if ( 'post' === $screen->base ) {

				if ( $this->role_can( 'public' ) )
					$this->_register_header_button();
			}
		}
	}

	public function admin_menu()
	{
		if ( $this->role_can( 'public' ) )
			$this->_hook_submenu_adminpage( 'signal', 'exist' );
	}

	public function render_submenu_adminpage()
	{
		$this->render_default_mainpage( 'signal', 'update' );
	}

	protected function render_signal_content()
	{
		if ( ! $linked = self::req( 'linked' ) )
			return gEditorial\Info::renderNoPostsAvailable();

		if ( ! $post = WordPress\Post::get( $linked ) )
			return gEditorial\Info::renderNoPostsAvailable();

		$this->_render_view_for_post( $post, 'signal' );
		gEditorial\Scripts::enqueueQRCodeSVG();
	}

	private function _render_view_for_post( $post, $context )
	{
		// $drop = $this->make_private( $post );
		$drop = $this->make_public( $post );

		echo $this->wrap_open( '-view -'.$context );

			echo '<div class="-first-row"><div class="-desc-deaddrop">';
				Core\HTML::desc( $this->get_setting_fallback( 'instructions',
					_x( 'Scan this with your phone and open it to upload files anonymously.', 'Message', 'geditorial-dead-drops' ) ) );
				echo '</div>';
				echo Core\HTML::wrap( gEditorial\Scripts::markupQRCodeSVG( $drop ), '-qrcode-deaddrop' );
			echo '</div>';

			echo '<div class="-second-row">';
				echo Core\HTML::wrap( Core\HTML::inputForCopy( $drop ), '-input-deaddrop' );
			echo '</div>';
		echo '</div>';
	}

	// decodes the Unicode filename encoded with `encodeURI`/`encodeURIComponent`
	public function wp_handle_sideload_prefilter( $file )
	{
		if ( ! empty( $file['name'] )
			&& $file['name'] !== ( $decoded = rawurldecode( $file['name'] ) ) )
			$file['name'] = $decoded;

		return $file;
	}

	// NOTE: the author must have upload cap to use the core endpoint.
	public function determine_current_user( $user_id )
	{
		if ( $user_id || ! WordPress\IsIt::rest() )
			return $user_id;

		$user = self::req( 'author' );
		$post = self::req( 'post' );
		$hash = self::req( 'dropzone' );

		if ( ! $user || ! $post || ! $hash )
			return $user_id;

		if ( ! $secret = get_post_meta( (int) $post, $this->constant( 'metakey_secret' ), TRUE ) )
			return $user_id;

		if ( $this->_check_hash( rawurldecode( $hash ), $secret, $user, $post ) )
			return (int) $user;

		return $user_id;
	}

	// TODO: maybe use `base64_encode()`/`base64_decode()`
	// @SEE: https://en.wikipedia.org/wiki/Cryptographic_hash_function
	private function _get_hash( $secret, $user_id, $post_id )
	{
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$wp_hasher = new \PasswordHash( 16, FALSE, FALSE );
		$wp_salt   = wp_salt();

		return $wp_hasher->HashPassword( trim( sprintf( '%s|%s|%s|%s', $secret, $user_id, $post_id, $wp_salt ) ) );
	}

	private function _check_hash( $hash, $secret, $user_id, $post_id )
	{
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$wp_hasher = new \PasswordHash( 16, FALSE, FALSE );
		$wp_salt   = wp_salt();

		$plain = trim( sprintf( '%s|%s|%s|%s', $secret, $user_id, $post_id, $wp_salt ) );

		return $wp_hasher->CheckPassword( $plain, $hash );
	}

	public function template_redirect()
	{
		if ( ! $secret = get_query_var( $this->constant( 'main_queryvar' ) ) )
			return;

		// NOTE: checks for `p` with fall-backs to wp-queried!
		if ( ! $post = WordPress\Post::get( get_query_var( 'p', get_queried_object_id() ) ) )
			return;

		if ( $secret !== get_post_meta( $post->ID, $this->constant( 'metakey_secret' ), TRUE ) )
			self::cheatin();

		if ( ! $user_id = self::req( 'u' ) )
			$user_id = gEditorial()->user();

		if ( ! $user = WordPress\User::user( (int) $user_id ) )
			self::cheatin();

		$this->_render_page_dropzone( [
			'user'   => $user,
			'post'   => $post,
			'secret' => $secret,
			'hash'   => rawurlencode( $this->_get_hash( $secret, $user->ID, $post->ID ) ),
		] );

		exit();
	}

	private function _register_header_button( $post = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$link = WordPress\IsIt::mobile()
			? $this->_get_deaddrop_link( $post )
			: $this->_get_signal_link( $post );

		if ( ! $link )
			return FALSE;

		$button = Services\HeaderButtons::register( $this->key, [
			'text'     => _x( 'Dead Drop', 'Header Button', 'geditorial-dead-drops' ),
			'title'    => $this->strings_metabox_title_via_posttype( $post->post_type, 'heading', NULL, $post ),
			'link'     => $link,
			'icon'     => $this->module->icon,
			'priority' => 99,
			'newtab'   => TRUE,
			'class'    => 'do-colorbox-iframe',
			'data'     => [
				'module'     => $this->key,
				'linked'     => $post->ID,
				'target'     => 'none',
				'max-width'  => '420',
				'max-height' => '310',
			],
		] );

		gEditorial\Scripts::enqueueColorBox();

		return $button;
	}

	private function _get_signal_link( $post = NULL, $context = 'signal', $extra = [] )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		return $this->get_adminpage_url( TRUE, array_merge( [
			'linked'   => $post->ID,
			'noheader' => 1,
			// 'width'    => 600,
		], $extra ), $context );
	}

	public function make_private( $post )
	{
		return delete_post_meta( $post->ID, $this->constant( 'metakey_secret' ) );
	}

	public function make_public( $post )
	{
		if ( $secret = get_post_meta( $post->ID, $this->constant( 'metakey_secret' ), TRUE ) )
			return $this->_get_deaddrop_link( $post, $secret );

		$secret = wp_generate_password( 24, FALSE, FALSE );
		$added  = add_post_meta( $post->ID, $this->constant( 'metakey_secret' ), $secret, TRUE );

		return $added ? $this->_get_deaddrop_link( $post, $secret ) : FALSE;
	}

	private function _get_deaddrop_link( $post = NULL, $secret = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$secret   = $secret ?? get_post_meta( $post->ID, $this->constant( 'metakey_secret' ), TRUE );
		$endpoint = $this->constant( 'main_endpoint' );

		if ( $GLOBALS['wp_rewrite']->using_permalinks() && WordPress\Post::viewable( $post ) )
			$deaddrop = sprintf( '%s/%s/%s', Core\URL::untrail( get_permalink( $post ) ), $endpoint, $secret );

		else
			$deaddrop = WordPress\Post::shortlink( $post->ID, [ $endpoint => $secret ] );

		if ( $user_id = get_current_user_id() )
			$deaddrop = add_query_arg( [ 'u' => $user_id ], $deaddrop );

		return $this->filters( 'deaddrop_link', $deaddrop, $post, $endpoint, $secret );
	}

	private function _render_page_dropzone( $atts = [] )
	{
		$args = self::atts( [
			'user'   => 0,
			'post'   => NULL,
			'secret' => NULL,
			'hash'   => FALSE,
		], $atts );

		$rest = add_query_arg( [
			'post'     => $args['post']->ID,
			'author'   => $args['user']->ID,
			'dropzone' => $args['hash'],
		], rest_url( 'wp/v2/media' ) );

		$title = WordPress\Post::title( $args['post'] );

		// @REF: https://github.com/dropzone/dropzone/blob/main/src/options.js
		$options = array_merge( gEditorial\Info::getDropzoneStrings(), [

			// An optional object to send additional headers to the server.
			'headers' => [
				'X-WP-Nonce' => wp_create_nonce( 'wp_rest' ),
			],

			/**
			 * Has to be specified on elements other than form (or when the form doesn't
			 * have an `action` attribute).
			 *
			 * You can also provide a function that will be called with `files` and
			 * `dataBlocks`  and must return the url as string.
			 */
			'url' => $rest,

			 /**
			 * Sends the file as binary blob in body instead of form data.
			 * If this is set, the `params` option will be ignored.
			 * It's an error to set this to `true` along with `uploadMultiple` since
			 * multiple files cannot be in a single binary body.
			 */
			// 'binaryBody' => TRUE, // NOTE: wont work with filename as title/caption on formData

			// TODO: settings for this
			// 'acceptedFiles' => 'image/*,application/pdf,.psd',

			/**
			 * If null, no capture type will be specified
			 * If camera, mobile devices will skip the file selection and choose camera
			 * If microphone, mobile devices will skip the file selection and choose the microphone
			 * If camcorder, mobile devices will skip the file selection and choose the camera in video mode
			 * On apple devices multiple must be set to false.  AcceptedFiles may need to
			 * be set to an appropriate mime type (e.g. "image/*", "audio/*", or "video/*").
			 */
			// 'capture' => 'camera', // WTF?!

			/**
			 * A function that is invoked before the file is uploaded to the server and renames the file.
			 * This function gets the `File` as argument and can use the `file.name`. The actual name of the
			 * file that gets used during the upload can be accessed through `file.upload.filename`.
			 */
			// renameFile: null,

			'previewsContainer' => 'div.-wrap.dropzone-previews',   // Define the container to display the previews
			// 'clickable'         => '#clickable',                    // Define the element that should be used as click trigger to select files.
		] );

		// TODO: move up the following!

		status_header( 200 );

		// https://stackoverflow.com/questions/1036941/setup-http-expires-headers-using-php-and-apache
		header( 'Expires: '.gmdate( 'D, d M Y H:i:s \G\M\T', time() + HOUR_IN_SECONDS ) ); // 1 hour

		echo '<!doctype html><html '.get_language_attributes( 'html' ).'><head>';

		// @SEE: https://make.wordpress.org/core/2020/02/19/enhancements-to-favicon-handling-in-wordpress-5-4/
		if ( file_exists( ABSPATH.'favicon.ico' ) )
			printf( '<link rel="shortcut icon" href="%s">', Core\URL::home( 'favicon.ico' ) );

		echo '<meta charset="UTF-8">';
		echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
		echo '<meta name="robots" content="noindex, nofollow">';

		printf( '<title>%s</title>', $title );

		if ( Core\L10n::rtl() ) gEditorial\Scripts::linkVazirMatn();
		gEditorial\Scripts::linkDropzone();
		gEditorial\Helper::linkStyleSheetAdmin( 'dead-drops', TRUE, 'dropzone' );

		// @REF: https://gitlab.com/meno/dropzone/-/wikis/make-the-whole-body-a-dropzone
		echo '</head><body class="dropzone">';

		// FIXME: `Scripts may close only the windows that were opened by them.`
		echo Core\HTML::wrap( Core\HTML::tag( 'button', [
			'type' => 'button',
			// 'onclick' => 'window.close();',
			// 'onclick' => 'window.top.close();',
			'onclick' => 'window.open("", "_self", ""); window.close();',
			// 'onclick' => 'window.open("","_self").close();',
			'title'   => _x( 'Discard', 'Button', 'geditorial-dead-drops' ),
		], '&times;' ), '-close-button' );

		Core\HTML::h2( $title, '-title' );

		// Core\HTML::inputHidden( 'secret', $args['secret'] );
		// Core\HTML::inputHidden( 'post', $args['post']->ID );

		// Don't forget to give this container the `dropzone-previews` class so the previews are formatted correctly.
		echo Core\HTML::wrap( gEditorial\Scripts::noScriptMessage( FALSE ), 'dropzone-previews', TRUE, [], 'previews' );
		// echo '<button id="clickable">Click me to select files</button>';

		// FIXME: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/encodeURIComponent#encoding_for_content-disposition_and_link_headers
		// TODO: append `content_md5` data: see: `WP_REST_Attachments_Controller::upload_from_data()`

		$encoded = wp_json_encode( $options, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		$script  = <<<JS

			const DDDZ = new Dropzone(
				document.body,
				{$encoded}
			);

			// add headers with `xhr.setRequestHeader()` or
			// form data with `formData.append(name, value);`
			// https://stackoverflow.com/a/17548081
			DDDZ.on('sending', function (file, xhr, formData) {
				// xhr.setRequestHeader( 'Authorization', 'BEARER ' + access_token );
				xhr.setRequestHeader('Content-Disposition', 'attachment; filename="' + encodeURIComponent(file.name) + '"');

				const name = file.name.replace(/\.[^/.]+$/, '');

				if (formData === undefined) {
					formData = new FormData();
				}

				formData.append('title', name);
				formData.append('caption', name);
			});

JS;
		// echo '<script>'.$script.'</script>';
		wp_print_inline_script_tag( $script );
		echo '</body></html>';
	}
}
