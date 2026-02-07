<?php namespace geminorum\gEditorial\Modules\Views;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Views extends gEditorial\Module
{
	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'views',
			'title'    => _x( 'Views', 'Modules: Views', 'geditorial-admin' ),
			'desc'     => _x( 'Customized Page Views', 'Modules: Views', 'geditorial-admin' ),
			'icon'     => 'admin-views',
			'access'   => 'beta',
			'keywords' => [
				'has-adminbar',
			],
		];
	}

	public function settings_intro()
	{
		Core\HTML::desc( _x( 'Note that the logged-out users are counted by default.', 'Message', 'geditorial-views' ) );
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'posttypes_option' => 'posttypes_option',
			'_roles'           => [
				'excluded_roles' => [ NULL, $this->get_settings_default_roles( [], 'subscriber' ) ],
				// 'manage_roles'   => [ NULL, $roles ], // TODO!
				'reports_roles'  => [ NULL, $roles ],
				'reports_post_edit',
			],
			'_frontend'         => [
				'adminbar_summary',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'metakey_post_template' => '_ge_views_%s',
		];
	}

	private function events()
	{
		return [
			'entryview' => _x( 'Entry Views', 'Event Name', 'geditorial-views' ),
		];
	}

	public function setup_ajax()
	{
		$this->_hook_ajax( NULL, NULL, 'do_ajax_public' );
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( ! $post = $this->adminbar__check_singular_post( NULL, 'edit_post' ) )
			return;

		$node_id = $this->classs();
		$icon    = $this->adminbar__get_icon();
		$reports = $this->role_can_post( $post, 'reports' );

		$nodes[] = [
			'parent' => $parent,
			'id'     => $node_id,
			'title'  => $icon._x( 'View Summary', 'Node: Title', 'geditorial-views' ),
			'href'   => $reports ? $this->get_module_url( 'reports', NULL, [ 'id' => $post->ID ] ) : FALSE,
			'meta'   => [
				'class' => $this->adminbar__get_css_class(),
			],
		];

		foreach ( $this->events() as $event => $title )
			$nodes[] = [
				'parent' => $node_id,
				'id'     => $this->classs( 'event', $event ),
				'title'  => WordPress\Strings::getCounted(
					$this->_report_views_for_post( $post->ID, $event ),
					$title.' %s'
				),
				'meta' => [
					'class' => $this->adminbar__get_css_class(),
				],
			];
	}

	public function template_redirect()
	{
		if ( is_robots() || is_favicon() || is_feed() )
			return;

		if ( ! is_singular( $this->posttypes() ) )
			return;

		if ( is_user_logged_in() && $this->role_can( 'excluded' ) )
			return;

		$this->current_queried = get_queried_object_id();

		$this->action( 'wp_footer' );

		wp_enqueue_script( 'jquery' );
	}

	public function wp_footer()
	{
		Core\HTML::wrapjQueryReady( '$.post("'.admin_url( 'admin-ajax.php' ).'",{action:"'.$this->hook().'",_ajax_nonce:"'.wp_create_nonce( $this->classs( $this->current_queried ) ).'",post_id:'.$this->current_queried.',what:"entryview"});' );
	}

	public function do_ajax_public()
	{
		$post = self::unslash( $_POST );
		$what = $post['what'] ?? 'nothing';

		if ( empty( $post['post_id'] ) )
			gEditorial\Ajax::errorMessage();

		gEditorial\Ajax::checkReferer( $this->classs( $post['post_id'] ) );

		switch ( $what ) {

			case 'entryview':

				if ( $this->_update_views_for_post( $post['post_id'], $what ) )
					gEditorial\Ajax::successMessage();
				else
					gEditorial\Ajax::errorMessage();
		}

		gEditorial\Ajax::errorWhat();
	}

	private function _update_views_for_post( $post_id, $event )
	{
		if ( ! $post_id )
			return FALSE;

		$key = sprintf( $this->constant( 'metakey_post_template' ), $event );
		$old = get_post_meta( $post_id, $key, TRUE );
		$new = absint( $old ) + 1;

		return update_post_meta( $post_id, $key, $new, $old );
	}

	private function _report_views_for_post( $post_id, $event )
	{
		return (int) get_post_meta( $post_id, sprintf( $this->constant( 'metakey_post_template' ), $event ), TRUE );
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc( $context, $fallback );
	}
}
