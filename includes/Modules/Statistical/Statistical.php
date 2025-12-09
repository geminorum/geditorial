<?php namespace geminorum\gEditorial\Modules\Statistical;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Statistical extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'statistical',
			'title'    => _x( 'Statistical', 'Modules: Statistical', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Content Reports', 'Modules: Statistical', 'geditorial-admin' ),
			'icon'     => 'chart-pie',
			'i18n'     => 'adminonly',
			'access'   => 'beta',
			'frontend' => FALSE,
			'keywords' => [
				'user',
				'content-report',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'posttypes_option' => 'posttypes_option',
			'_reports'         => [
				'reports_roles' => [ NULL, $roles ],
				'calendar_type',
			],
		];
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc( $context, $fallback, [
			'reports',
		] );
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		$args = $this->get_current_form( [
			'post_type'  => 'post',
			'user_id'    => '0',
			'year_month' => '',
		], 'reports' );

		Core\HTML::h3( _x( 'Statistical Reports', 'Header', 'geditorial-statistical' ) );

		echo '<table class="form-table">';

		echo '<tr><th scope="row">'._x( 'By PostType', 'Header', 'geditorial-statistical' ).'</th><td>';

		$this->do_settings_field( [
			'type'         => 'select',
			'field'        => 'post_type',
			'values'       => $this->list_posttypes(),
			'default'      => $args['post_type'],
			'option_group' => 'reports',
		] );

		echo '&nbsp;';

		$this->do_settings_field( [
			'type'         => 'user',
			'field'        => 'user_id',
			'none_title'   => _x( 'All Users', 'None Title', 'geditorial-statistical' ),
			'default'      => $args['user_id'],
			'option_group' => 'reports',
		] );

		echo '&nbsp;';

		$this->do_settings_field( [
			'type'         => 'select',
			'field'        => 'year_month',
			'none_title'   => _x( 'All Months', 'None Title', 'geditorial-statistical' ),
			'values'       => gEditorial\Datetime::getPostTypeMonths( $this->default_calendar(), $args['post_type'], [], $args['user_id'] ),
			'default'      => $args['year_month'],
			'option_group' => 'reports',
		] );

		echo '&nbsp;';

		gEditorial\Settings::submitButton( 'posttype_stats', _x( 'Query Stats', 'Button', 'geditorial-statistical' ) );

		if ( ! empty( $_POST ) && isset( $_POST['posttype_stats'] ) ) {

			$period = $args['year_month']
				? gEditorial\Datetime::monthFirstAndLast(
					$this->default_calendar(),
					substr( $args['year_month'], 0, 4 ),
					substr( $args['year_month'], 4, 2 )
				) : [];

			echo Core\HTML::tableCode(
				WordPress\Database::countPostsByPosttype(
					$args['post_type'],
					$args['user_id'],
					$period
				)
			);
		}

		echo '</td></tr>';
		echo '</table>';
	}
}
