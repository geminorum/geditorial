<?php namespace geminorum\gEditorial\Modules\Switcher;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\WordPress\PostType;

class Switcher extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'switcher',
			'title'    => _x( 'Switcher', 'Modules: Switcher', 'geditorial' ),
			'desc'     => _x( 'Bulk Conversion Utility', 'Modules: Switcher', 'geditorial' ),
			'icon'     => 'randomize',
			'frontend' => FALSE,
		];
	}

	protected function get_global_settings()
	{
		$posttypes = $this->all_posttypes();
		$roles     = $this->get_settings_default_roles( [ 'administrator', 'subscriber' ] );

		return [
			'_bulkactions' => [
				'admin_bulkactions',
				[
					'field'       => 'bulk_roles',
					'type'        => 'checkboxes-values',
					'title'       => _x( 'Bulk Action Roles', 'Setting Title', 'geditorial-switcher' ),
					'description' => _x( 'Roles that can use bulk action to switch post-types.', 'Setting Description', 'geditorial-switcher' ),
					'values'      => $roles,
				],
				[
					'field'       => 'bulk_posttypes_from',
					'type'        => 'posttypes',
					'title'       => _x( 'Bulk Action From', 'Setting Title', 'geditorial-switcher' ),
					'description' => _x( 'Select post-types to be avialable for switch.', 'Setting Description', 'geditorial-switcher' ),
					'values'      => $posttypes,
				],
				[
					'field'       => 'bulk_posttypes_to',
					'type'        => 'posttypes',
					'title'       => _x( 'Bulk Action To', 'Setting Title', 'geditorial-switcher' ),
					'description' => _x( 'Select post-types to be targetted for switch.', 'Setting Description', 'geditorial-switcher' ),
					'values'      => $posttypes,
				],
			],
		];
	}

	public function current_screen( $screen )
	{
		if ( 'edit' == $screen->base
			&& $this->in_setting( $screen->post_type, 'bulk_posttypes_from' ) ) {

			$this->_hook_admin_bulkactions( $screen, (bool) $this->cuc( 'bulk' ) );
		}
	}

	public function bulk_actions( $actions )
	{
		$list      = [];
		$current   = PostType::current();
		$posttypes = PostType::get( 2, [
			'public'  => TRUE,
			'show_ui' => TRUE,
		], 'edit_others_posts' );

		/* translators: %s: posttype */
		$template = _x( 'Switch to %s', 'Bulk Action', 'geditorial-switcher' );

		foreach ( $this->get_setting( 'bulk_posttypes_to', [] ) as $posttype ) {

			if ( $current === $posttype )
				continue;

			if ( ! array_key_exists( $posttype, $posttypes ) )
				continue; // no access

			$list['switch-to-'.$posttype] = sprintf( $template, $posttypes[$posttype] );
		}

		return array_merge( $actions, $list );
	}

	public function handle_bulk_actions( $redirect_to, $doaction, $post_ids )
	{
		foreach ( $this->get_setting( 'bulk_posttypes_to', [] ) as $posttype ) {

			if ( $doaction !== ( 'switch-to-'.$posttype ) )
				continue;

			if ( ! PostType::can( $posttype, 'edit_others_posts' ) )
				continue;

			$switched = 0;

			foreach ( $post_ids as $post_id )
				if ( Helper::switchPostType( $post_id, $posttype ) )
					$switched++;

			return add_query_arg( [
				$this->hook( 'to' )    => $posttype,
				$this->hook( 'count' ) => $switched,
			], $redirect_to );
		}

		return $redirect_to;
	}

	public function admin_notices()
	{
		if ( ! $switched = self::req( $this->hook( 'count' ) ) )
			return;

		$to  = self::req( $this->hook( 'to' ), 'post' );
		$all = PostType::get( 2, [ 'public' => TRUE, 'show_ui' => TRUE ] );

		$_SERVER['REQUEST_URI'] = remove_query_arg( [ $this->hook( 'count' ), $this->hook( 'to' ) ], $_SERVER['REQUEST_URI'] );

		/* translators: %1$s: count, %2$s: posttype */
		$message = _x( '%1$s items(s) switched to %2$s!', 'Message', 'geditorial-switcher' );
		echo HTML::success( sprintf( $message, Number::format( $switched ), $all[$to] ) );
	}
}
