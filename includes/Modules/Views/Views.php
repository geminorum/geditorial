<?php namespace geminorum\gEditorial\Modules\Views;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;

class Views extends gEditorial\Module
{

	public $meta_key = '_ge_views';

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'  => 'views',
			'title' => _x( 'Views', 'Modules: Views', 'geditorial' ),
			'desc'  => _x( 'Customized Page Views', 'Modules: Views', 'geditorial' ),
			'icon'  => 'admin-views',
		];
	}

	public function settings_intro()
	{
		HTML::desc( _x( 'Note that the logget-out users count by default.', 'Message', 'geditorial-views' ) );
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				'excluded_roles',
				'adminbar_summary',
			],
		];
	}

	private function events()
	{
		return [
			'entryview' => _x( 'Entry Views', 'Event Name', 'geditorial-views' ),
		];
	}

	public function init_ajax()
	{
		$this->_hook_ajax( NULL );
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() || ! is_singular( $this->posttypes() ) )
			return;

		$post_id = get_queried_object_id();

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		$nodes[] = [
			'id'     => $this->classs(),
			'title'  => _x( 'View Summary', 'Title Attr', 'geditorial-views' ),
			'parent' => $parent,
			'href'   => $this->get_module_url( 'reports', NULL, [ 'id' => $post_id ] ),
		];

		foreach ( $this->events() as $event => $title )
			$nodes[] = [
				'id'     => $this->classs( 'event', $event ),
				'title'  => Helper::getCounted( $this->report( $post_id, $event ), $title.' %s' ),
				'parent' => $this->classs(),
				'href'   => FALSE,
			];
	}

	public function template_redirect()
	{
		if ( is_embed() )
			return;

		if ( ! is_singular( $this->posttypes() ) )
			return;

		if ( is_user_logged_in() && $this->role_can( 'excluded' ) )
			return;

		$this->post_id = get_queried_object_id();

		$this->action( 'wp_footer' );

		wp_enqueue_script( 'jquery' );
	}

	public function wp_footer()
	{
		HTML::wrapjQueryReady( '$.post("'.admin_url( 'admin-ajax.php' ).'",{action:"'.$this->hook().'",_ajax_nonce:"'.wp_create_nonce( $this->classs( $this->post_id ) ).'",post_id:'.$this->post_id.',what:"entryview"});' );
	}

	public function ajax()
	{
		$post = self::unslash( $_POST );
		$what = isset( $post['what'] ) ? $post['what'] : 'nothing';

		if ( empty( $post['post_id'] ) )
			Ajax::errorMessage();

		Ajax::checkReferer( $this->classs( $post['post_id'] ) );

		switch ( $what ) {

			case 'entryview':

				if ( $this->update( $post['post_id'], $what ) )
					Ajax::successMessage();
				else
					Ajax::errorMessage();
		}

		Ajax::errorWhat();
	}

	private function update( $post_id, $event )
	{
		if ( ! $post_id )
			return FALSE;

		$key = $this->meta_key.'_'.$event;
		$old = get_post_meta( $post_id, $key, TRUE );
		$new = absint( $old ) + 1;

		return update_post_meta( $post_id, $key, $new, $old );
	}

	private function report( $post_id, $event )
	{
		return (int) get_post_meta( $post_id, $this->meta_key.'_'.$event, TRUE );
	}
}
