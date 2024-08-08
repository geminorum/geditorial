<?php namespace geminorum\gEditorial\Modules\Switcher;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Switcher extends gEditorial\Module
{
	use Internals\CoreRowActions;

	protected $deafults  = [ 'admin_bulkactions' => TRUE ];

	public static function module()
	{
		return [
			'name'     => 'switcher',
			'title'    => _x( 'Switcher', 'Modules: Switcher', 'geditorial-admin' ),
			'desc'     => _x( 'Bulk Conversion Utilities', 'Modules: Switcher', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'toggles' ],
			'access'   => 'beta',
			'frontend' => FALSE,
			'keywords' => [
				'conversion',
				'adminonly',
			],
		];
	}

	protected function get_global_settings()
	{
		$posttypes = $this->all_posttypes();
		$roles     = $this->get_settings_default_roles();

		return [
			'_bulkactions' => [
				[
					'field'       => 'bulk_roles',
					'type'        => 'checkboxes-values',
					'title'       => _x( 'Bulk Action Roles', 'Setting Title', 'geditorial-switcher' ),
					'description' => _x( 'Roles that can use bulk-actions to switch post-types.', 'Setting Description', 'geditorial-switcher' ),
					'values'      => $roles,
				],
				[
					'field'       => 'bulk_posttypes_from',
					'type'        => 'posttypes',
					'title'       => _x( 'Bulk Action From', 'Setting Title', 'geditorial-switcher' ),
					'description' => _x( 'Select post-types to be available for the switch.', 'Setting Description', 'geditorial-switcher' ),
					'values'      => $posttypes,
				],
				[
					'field'       => 'bulk_posttypes_to',
					'type'        => 'posttypes',
					'title'       => _x( 'Bulk Action To', 'Setting Title', 'geditorial-switcher' ),
					'description' => _x( 'Select post-types to be targeted for the switch.', 'Setting Description', 'geditorial-switcher' ),
					'values'      => $posttypes,
				],
			],
		];
	}

	public function current_screen( $screen )
	{
		if ( 'edit' == $screen->base
			&& $this->in_setting( $screen->post_type, 'bulk_posttypes_from' ) ) {

			$this->rowactions__hook_admin_bulkactions( $screen, (bool) $this->cuc( 'bulk' ) );
		}
	}

	public function rowactions_bulk_actions( $actions )
	{
		$list      = [];
		$current   = WordPress\PostType::current();
		$posttypes = WordPress\PostType::get( 2, [
			// 'public'  => TRUE,
			'show_ui' => TRUE,
		], 'edit_others_posts' );

		/* translators: %1$s: module title, %2$s: posttype label */
		$template = _x( '[%1$s] Switch to %2$s', 'Bulk Action', 'geditorial-switcher' );

		foreach ( $this->get_setting( 'bulk_posttypes_to', [] ) as $posttype ) {

			if ( $current === $posttype )
				continue;

			if ( ! array_key_exists( $posttype, $posttypes ) )
				continue; // no access

			$list['switch-to-'.$posttype] = sprintf( $template, $this->module->title, $posttypes[$posttype] );
		}

		return array_merge( $actions, $list );
	}

	public function rowactions_handle_bulk_actions( $redirect_to, $doaction, $post_ids )
	{
		foreach ( $this->get_setting( 'bulk_posttypes_to', [] ) as $posttype ) {

			if ( $doaction !== ( 'switch-to-'.$posttype ) )
				continue;

			if ( ! WordPress\PostType::can( $posttype, 'edit_others_posts' ) )
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

	public function rowactions_admin_notices()
	{
		if ( ! $switched = self::req( $this->hook( 'count' ) ) )
			return;

		$to  = self::req( $this->hook( 'to' ), 'post' );
		$all = WordPress\PostType::get( 2, [ 'public' => TRUE, 'show_ui' => TRUE ] );

		$_SERVER['REQUEST_URI'] = remove_query_arg( [ $this->hook( 'count' ), $this->hook( 'to' ) ], $_SERVER['REQUEST_URI'] );

		/* translators: %1$s: count, %2$s: posttype */
		$message = _x( '%1$s items(s) switched to %2$s!', 'Message', 'geditorial-switcher' );
		echo Core\HTML::success( sprintf( $message, Core\Number::format( $switched ), $all[$to] ) );
	}
}
