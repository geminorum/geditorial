<?php namespace geminorum\gEditorial\Modules\Remoted;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Remoted extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\ViewEngines;

	protected $deafults  = [ 'dashboard_widgets' => TRUE ];

	public static function module()
	{
		return [
			'name'     => 'remoted',
			'title'    => _x( 'Remoted', 'Modules: Remoted', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Remote Uploads', 'Modules: Remoted', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'cloud-upload-fill' ],
			'access'   => 'beta',
			'frontend' => FALSE,
			'keywords' => [
				'file-upload',
				'admin-widget',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_setup' => [
				[
					'field'       => 'remote_base',
					'type'        => 'url',
					'title'       => _x( 'Remote Base', 'Setting Title', 'geditorial-remoted' ),
					'description' => _x( 'Defines the full URL of the remote base for receiving uploads.', 'Setting Description', 'geditorial-remoted' ),
				],
				[
					'field'       => 'remote_target',
					'type'        => 'text',
					'title'       => _x( 'Remote Target', 'Setting Title', 'geditorial-remoted' ),
					'description' => _x( 'Defines the relative path for the receiving uploads. Leave empty for the base.', 'Setting Description', 'geditorial-remoted' ),
					'field_class' => [ 'regular-text', 'code-text' ],
				],
				[
					'field'       => 'remote_identifier',
					'type'        => 'text',
					'title'       => _x( 'Remote Identifier', 'Setting Title', 'geditorial-remoted' ),
					'description' => _x( 'Defines a unique string for the receiver. Must start with a letter.', 'Setting Description', 'geditorial-remoted' ),
					'field_class' => [ 'regular-text', 'code-text' ],
					'default'     => $this->_generate_identifier(),
				],
				[
					'field'       => 'remote_token',
					'type'        => 'text',
					'title'       => _x( 'Remote Token', 'Setting Title', 'geditorial-remoted' ),
					'description' => _x( 'Defines the communication key for the receiver. Only letters and numbers.', 'Setting Description', 'geditorial-remoted' ),
					'field_class' => [ 'regular-text', 'code-text' ],
					'default'     => $this->_generate_token(),
				],
				[
					'field'       => 'remote_home',
					'type'        => 'url',
					'title'       => _x( 'Remote Home', 'Setting Title', 'geditorial-remoted' ),
					'description' => _x( 'Defines the full URL of the home of uploads.', 'Setting Description', 'geditorial-remoted' ),
					'default'     => Core\URL::home(),
				],
				[
					'field'       => 'remote_overwrite',
					'title'       => _x( 'Remote Overwrite', 'Setting Title', 'geditorial-remoted' ),
					'description' => _x( 'Whether to replace already existing file with the uploaded.', 'Setting Description', 'geditorial-remoted' ),
				],
			],
			'_config' => [
				[
					'field'       => 'mimetype_extensions',
					'type'        => 'text',
					'title'       => _x( 'Mime-type Extensions', 'Setting Title', 'geditorial-remoted' ),
					'description' => _x( 'Defines the list of extensions for accepted mime-types. Separate with latin comma.', 'Setting Description', 'geditorial-remoted' ),
					'field_class' => [ 'regular-text', 'code-text' ],
					'placeholder' => 'mp3,mp4,pdf,jpg,png,zip',
				],
				[
					'field'       => 'chunk_size',
					'type'        => 'text',
					'title'       => _x( 'Chunk Size', 'Setting Title', 'geditorial-remoted' ),
					'description' => _x( 'Slices the file into number of bytes. Supports b, kb, mb, gb, tb suffixes also.', 'Setting Description', 'geditorial-remoted' ),
					'field_class' => [ 'medium-text', 'code-text' ],
					'placeholder' => '200kb',
				],
				[
					'field'       => 'max_file_size',
					'type'        => 'text',
					'title'       => _x( 'Max File Size', 'Setting Title', 'geditorial-remoted' ),
					'description' => _x( 'Defines the maximum file size in bytes. Supports b, kb, mb, gb, tb suffixes also.', 'Setting Description', 'geditorial-remoted' ),
					'field_class' => [ 'medium-text', 'code-text' ],
					'placeholder' => '150mb',
				],
			],
			'_tweaks' => [
				[
					'field'  => 'destinations',
					'type'   => 'object',
					'title'  => _x( 'Destinetions', 'Setting Title', 'geditorial-remoted' ),
					'values' => [
						[
							'field'       => 'title',
							'type'        => 'text',
							'title'       => _x( 'Option Title', 'Setting Title', 'geditorial-remoted' ),
							'description' => _x( 'Defines the title on the widget drop-down.', 'Setting Description', 'geditorial-remoted' ),
						],
						[
							'field'       => 'path',
							'type'        => 'text',
							'title'       => _x( 'Relative Path', 'Setting Title', 'geditorial-remoted' ),
							'description' => _x( 'Defines the path relative to the base directory.', 'Setting Description', 'geditorial-remoted' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'ortho'       => 'slug',
						],
						[
							'field'       => 'priority',
							'type'        => 'number',
							'title'       => _x( 'Sort Priority', 'Setting Title', 'geditorial-remoted' ),
							'description' => _x( 'Sets as the priority where the path display on the list.', 'Setting Description', 'geditorial-remoted' ),
						],
					],
				],
				[
					'field'       => 'root_title',
					'type'        => 'text',
					'title'       => _x( 'Root Title', 'Setting Title', 'geditorial-remoted' ),
					'description' => _x( 'Displays as title of the root destination on the widget select.', 'Setting Description', 'geditorial-remoted' ),
					'placeholder' => _x( 'Root Destination', 'Setting Default', 'geditorial-remoted' ),
				],
				[
					'field'       => 'default_dest',
					'type'        => 'text',
					'title'       => _x( 'Default Destination', 'Setting Title', 'geditorial-remoted' ),
					'description' => _x( 'Sets as the default path option on the list. Leave empty for the root.', 'Setting Description', 'geditorial-remoted' ),
					'field_class' => [ 'regular-text', 'code-text' ],
					'ortho'       => 'slug',
				],
			],
			'_roles' => [
				'uploads_roles' => [ NULL, $this->get_settings_default_roles() ],
			],
		];
	}

	public function setup_disabled()
	{
		$settings = [
			'remote_base',
			// 'remote_target', // targets can be empty
			'remote_identifier',
			'remote_token',
		];

		foreach ( $settings as $setting )
			if ( ! $this->get_setting( $setting ) )
				return TRUE;

		return FALSE;
	}

	public function dashboard_widgets()
	{
		if ( ! $this->role_can( 'uploads' ) )
			return;

		$context = 'upload';
		$remote  = $this->_get_remote_upload_url();

		$this->add_dashboard_widget(
			$context,
			_x( 'Remote Uploads', 'Dashboard Widget Title', 'geditorial-remoted' ),
			Core\HTTP::htmlStatus(
				Core\HTTP::getStatus( add_query_arg( 'ready', '', $remote ), ! WordPress\IsIt::dev() ),
				NULL,
				'<code class="postbox-title-action -status" title="%s" style="color:%s">%s</code>'
			), [
				'context' => $context,
				'remote'  => $remote,
			]
		);
	}

	public function render_widget_upload( $object, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		if ( empty( $box['args']['remote'] ) || empty( $box['args']['context'] ) )
			return gEditorial\Info::renderSomethingIsWrong();

		echo $this->wrap_open(
			[
				'-admin-widget',
				'-remote-uploads',
			],
			TRUE,
			$this->classs(
				$box['args']['context'],
				'container'
			)
		);

			if ( $data = $this->get_setting( 'destinations', [] ) )
				echo Core\HTML::dropdown(
					Core\Arraay::pluck( Core\Arraay::sortByPriority( $data, 'priority' ), 'title', 'path' ),
					[
						'id'          => $this->classs( $box['args']['context'], 'destination' ),
						'class'       => [ 'upload-destination', 'gnetwork-do-chosen' ],
						'selected'    => $this->get_setting( 'default_dest' ) ?: '',
						'none_title'  => $this->get_setting_fallback( 'root_title', _x( 'Root Destination', 'Setting Default', 'geditorial-remoted' ) ),
						'none_value'  => '',
						'value_title' => TRUE,
					]
				);

			else
				Core\HTML::inputHidden( $this->classs( $box['args']['context'], 'destination' ) );

			echo Core\HTML::tag( 'button', [
				'id'    => $this->classs( $box['args']['context'], 'pickfiles' ),
				'type'  => 'button',
				'class' => [ 'upload-button', 'button-add-media' ],
			], _x( 'Upload Files', 'Button', 'geditorial-remoted' ) );

			echo $this->wrap(
				_x( 'Your browser does not support HTML5 upload.', 'Notice', 'geditorial-remoted' ),
				'-file-queue',
				TRUE,
				$this->classs( $box['args']['context'], 'filequeue' )
			);

		echo '</div>';

		$this->enqueue_asset_js( [
			'strings' => [
				'wrong' => gEditorial\Plugin::wrong(),
			],
			'config' => [
				'remote'    => $box['args']['remote'],
				'chunk'     => $this->get_setting( 'chunk_size' )    ?: '200kb',
				'maxsize'   => $this->get_setting( 'max_file_size' ) ?: '150mb',
				'mimetypes' => [
					[
						'title'      => _x( 'Supported Extensions', 'Filter Title', 'geditorial-remoted' ),
						'extensions' => $this->get_setting( 'mimetype_extensions' ) ?: 'mp3,mp4,pdf,jpg,png,zip',
					],
				],
			],
		], $this->dotted( $box['args']['context'] ), [
			'jquery',
			gEditorial\Scripts::pkgPlupload(),
		] );
	}

	private function _get_remote_upload_url()
	{
		if ( ! $base = $this->get_setting( 'remote_base' ) )
			return FALSE;

		if ( ! $identifier = $this->get_setting( 'remote_identifier' ) )
			return FALSE;

		if ( ! $token = $this->get_setting( 'remote_token' ) )
			return FALSE;

		return add_query_arg( [
			'token' => $token,
		], sprintf(
			'%s/%s/%s.php',
			Core\URL::untrail( $base ),
			$identifier,
			$identifier
		) );
	}

	// NOTE: must start with a letter.
	private function _generate_identifier( $length = NULL )
	{
		return sprintf( 'ph%s', strtoupper( wp_generate_password( $length ?? 8, FALSE ) ) );
	}

	private function _generate_token( $length = NULL )
	{
		return wp_generate_password( $length ?? 20, FALSE );
	}

	protected function handle_settings_extra_buttons( $module )
	{
		if ( isset( $_POST['generate_receiver'] ) ) {

			if ( $this->_do_generate_receiver() )
				WordPress\Redirect::doReferer( 'maked' );

		} else if ( isset( $_POST['cleanup_receiver'] ) ) {

			if ( $this->_do_cleanup_receiver() )
				WordPress\Redirect::doReferer( 'purged' );
		}
	}

	protected function register_settings_extra_buttons( $module )
	{
		if ( ! $identifier = $this->get_setting( 'remote_identifier' ) )
			return;

		$destination = WP_CONTENT_DIR;
		$filename    = sprintf( '%s.zip', $identifier );

		if ( Core\File::exists( $filename, $destination ) ) {

			$this->register_button(
				sprintf( '%s/%s', Core\URL::fromPath( $destination ), $filename ),
				_x( 'Download Receiver', 'Button', 'geditorial-remoted' ),
				'link'
			);

			$this->register_button(
				'cleanup_receiver',
				_x( 'Cleanup Receiver', 'Button', 'geditorial-remoted' ),
				'danger'
			);

		} else {

			$this->register_button(
				'generate_receiver',
				_x( 'Generate Receiver', 'Button', 'geditorial-remoted' )
			);
		}
	}

	private function _do_cleanup_receiver( $destination = NULL, $template = NULL )
	{
		if ( ! $identifier = $this->get_setting( 'remote_identifier' ) )
			return FALSE;

		Core\File::removeDir( Core\File::join( get_temp_dir(), $identifier ) );

		@unlink( Core\File::join(
			$destination ?? WP_CONTENT_DIR,
			sprintf( '%s.zip', $identifier )
		) );

		return TRUE;
	}

	private function _do_generate_receiver( $destination = NULL, $template = NULL )
	{
		if ( ! $this->_do_cleanup_receiver( $destination, $template ) )
			WordPress\Redirect::doReferer( 'wrong' );

		if ( ! $filename = Core\File::tempName() )
			WordPress\Redirect::doReferer( 'error' );

		if ( ! $view = $this->viewengine__view_by_template( $template ?? 'default', 'receiver' ) )
			WordPress\Redirect::doReferer( 'error' );

		$data = [
			'base'       => $this->get_setting( 'remote_base', '' ),
			'identifier' => $this->get_setting( 'remote_identifier', '' ),
			'token'      => $this->get_setting( 'remote_token', '' ),
			'target'     => $this->get_setting( 'remote_target', '' ),
			'home'       => $this->get_setting( 'remote_home', Core\URL::home() ),
			'overwrite'  => $this->get_setting( 'remote_overwrite' ) ? 'TRUE' : 'FALSE',

			'lang'      => Core\L10n::getISO639(),
			'locale'    => Core\L10n::locale( TRUE ),
			'direction' => Core\L10n::rtl() ? 'rtl' : 'ltr',
			'logo'      => Services\ContentBrand::siteIcon(),
			'name'      => $this->get_setting_fallback( 'redirect_title', get_option( 'blogname' ) ),
			'message'   => $this->get_setting_fallback( 'redirect_message', _x( 'You will be returned to the main site.', 'Setting Default', 'geditorial-remoted' ) ),
		];

		if ( ! $html = $this->viewengine__render( $view, $data, FALSE ) )
			WordPress\Redirect::doReferer( 'error' );

		if ( ! @file_put_contents( $filename, $html ) )
			WordPress\Redirect::doReferer( 'error' );

		$path = Core\File::join( get_temp_dir(), $data['identifier'] );

		if ( ! wp_mkdir_p( $path ) )
			WordPress\Redirect::doReferer( 'error' );

		if ( ! copy( $filename, Core\File::join( $path, sprintf( '%s.php', $data['identifier'] ) ) ) )
			WordPress\Redirect::doReferer( 'error' );

		Core\File::putContents( '.htaccess', Core\File::htaccessProtectLogs(), $path, FALSE );
		Core\File::putDoNotBackup( $path );

		if ( $view = $this->viewengine__view_by_template( $template ?? 'default', 'index' ) ) {

			if ( $index = $this->viewengine__render( $view, $data, FALSE ) )
				Core\File::putContents( 'index.html', $index, $path, FALSE );

			else
				Core\File::putIndexHTML( $path, GEDITORIAL_DIR.'index.html' );

		} else {

			Core\File::putIndexHTML( $path, GEDITORIAL_DIR.'index.html' );
		}

		$zipped = Core\File::join(
			$destination ?? WP_CONTENT_DIR,
			sprintf( '%s.zip', $data['identifier'] )
		);

		if ( ! Core\Zip::zipDir( $path, $zipped ) )
			WordPress\Redirect::doReferer( 'error' );

		return Core\File::normalize( $zipped );
	}
}
