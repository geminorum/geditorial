<?php namespace geminorum\gEditorial\Modules\Remoted;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\WordPress;

class Remoted extends gEditorial\Module
{
	use Internals\CoreDashboard;

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
				'upload',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'remote_base',
					'type'        => 'url',
					'title'       => _x( 'Remote Base', 'Setting Title', 'geditorial-remoted' ),
					'description' => _x( 'Full URL into the remote base for receiving uploads.', 'Setting Description', 'geditorial-remoted' ),
				],
				[
					'field'       => 'remote_hash',
					'type'        => 'text',
					'title'       => _x( 'Remote Hash', 'Setting Title', 'geditorial-remoted' ),
					'description' => _x( 'Full URL into the remote base for receiving uploads.', 'Setting Description', 'geditorial-remoted' ),
					'field_class' => [ 'medium-text', 'code-text' ],
				],
			],
			'_roles' => [
				'uploads_roles'  => [ NULL, $this->get_settings_default_roles() ],
			],
		];
	}

	public function dashboard_widgets()
	{
		if ( ! $this->role_can( 'uploads' ) )
			return;

		$this->add_dashboard_widget(
			'remote-uploads',
			_x( 'Remote Uploads', 'Dashboard Widget Title', 'geditorial-remoted' ),
			'refresh'
		);
	}

	public function render_widget_remote_uploads( $object, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		echo $this->wrap_open( [ '-admin-widget', '-remote-uploads' ], TRUE, $this->classs( 'container' ) );

			echo Core\HTML::tag( 'button', [
				'id'    => $this->classs( 'pickfiles' ),
				'type'  => 'button',
				'class' => [ 'upload-button', 'button-add-media' ],
			], _x( 'Upload Files', 'Button', 'geditorial-remoted' ) );

			echo $this->wrap(
				_x( 'Your browser does not support HTML5 upload.', 'Notice', 'geditorial-remoted' ),
				'-file-queue',
				TRUE,
				$this->classs( 'filequeue' )
			);

		echo '</div>';

		$this->enqueue_asset_js( [
			'strings' => [
				'wrong' => gEditorial\Plugin::wrong(),
			],
			'config' => [
				'chunk'   => '200kb',   // '1mb' // TODO: setting
				'maxsize' => '150mb',   // TODO: setting

				'remote'    => $this->_get_remote_upload_url(),
				'mimetypes' => [
					// NOTE: must be js compatible array
					[
						'title'      => 'Audio files',   // TODO: setting
						'extensions' => 'mp3,mp4,pdf',   // TODO: setting
					],
				],
			],
		], 'remote-uploads', [
			'jquery',
			Scripts::pkgPlupload(),
		] );
	}

	private function _get_remote_upload_url()
	{
		if ( ! $base = $this->get_setting( 'remote_base' ) )
			return FALSE;

		if ( ! $hash = $this->get_setting( 'remote_hash' ) )
			return FALSE;

		return sprintf( '%s/%s.php', Core\URL::untrail( $base ), $hash );
	}
}
