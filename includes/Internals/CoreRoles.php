<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait CoreRoles
{

	protected function get_blog_users( $fields = NULL, $list = FALSE, $admins = FALSE )
	{
		if ( is_null( $fields ) )
			$fields = [
				'ID',
				'display_name',
				'user_login',
				'user_email',
			];

		$excludes = $this->get_setting( 'excluded_roles', [] );

		if ( $admins )
			$excludes[] = 'administrator';

		$args = [
			'number'       => -1,
			'orderby'      => 'post_count',
			'fields'       => $fields,
			'role__not_in' => $excludes,
			'count_total'  => FALSE,
		];

		if ( $list )
			$args['include'] = (array) $list;

		$query = new \WP_User_Query( $args );

		return $query->get_results();
	}
}
