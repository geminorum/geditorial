<?php namespace geminorum\gEditorial\Modules\WasBorn;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Listtable;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class WasBorn extends gEditorial\Module
{
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\DashboardSummary;
	use Internals\LateChores;
	use Internals\PostDate;

	public static function module()
	{
		return [
			'name'     => 'was_born',
			'title'    => _x( 'Was Born', 'Modules: Was Born', 'geditorial-admin' ),
			'desc'     => _x( 'Age Calculation and Statistics', 'Modules: Was Born', 'geditorial-admin' ),
			'icon'     => 'buddicons-community',
			'access'   => 'beta',
			'keywords' => [
				'birthdate',
				'gender',
				'age',
			],
		];
	}

	protected function get_global_settings()
	{
		$settings  = [];
		$roles     = $this->get_settings_default_roles();
		$posttypes = $this->get_settings_posttypes_parents();

		$settings['_general']['parent_posttypes'] = [ NULL, $posttypes ];

		foreach ( $this->get_setting_posttypes( 'parent' ) as $posttype_name ) {

			$default_dob_metakey = $this->filters( 'default_posttype_dob_metakey', '', $posttype_name );

			$settings['_posttypes'][] = [
				'field'       => $posttype_name.'_posttype_dob_metakey',
				'type'        => 'text',
				/* translators: %s: supported object label */
				'title'       => sprintf( _x( 'Date of Birth Meta-key for %s', 'Setting Title', 'geditorial-was-born' ), '<i>'.$posttypes[$posttype_name].'</i>' ),
				'description' => _x( 'Defines date-of-birth meta-key for the post-type.', 'Setting Description', 'geditorial-was-born' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'after'       => Settings::fieldAfterText( $default_dob_metakey, 'code' ),
				'placeholder' => $default_dob_metakey,
				'default'     => $default_dob_metakey,
			];
		}

		$settings['_defaults'] = [
			'override_dates' => [ _x( 'Tries to override post-date with provided date-of-birth on supported post-types.', 'Setting Description', 'geditorial-was-born' ), ],
			'calendar_type',
			'calendar_list',
			[
				'field'       => 'age_of_majority',
				'type'        => 'number',
				'title'       => _x( 'Age of Majority', 'Setting Title', 'geditorial-was-born' ),
				'description' => _x( 'The threshold of legal adulthood as recognized or declared in law.', 'Setting Description', 'geditorial-was-born' ),
				'after'       => Settings::fieldAfterIcon( 'https://en.wikipedia.org/wiki/Age_of_majority' ),
				'default'     => 18,
			],
			[
				'field'       => 'average_round_up',
				'title'       => _x( 'Round-up Average', 'Setting Title', 'geditorial-was-born' ),
				'description' => _x( 'Tries to calculate average numbers rounded.', 'Setting Description', 'geditorial-was-born' ),
				'default'     => 1,
			],
		];

		$settings['_editpost'] = [
			'admin_restrict',
		];

		$settings['_dashboard'] = [
			'dashboard_widgets',
			'summary_scope',
			'summary_drafts',
			'count_not',
		];

		$settings['_roles'] = [
			[
				'field'       => 'manage_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'Manage Roles', 'Setting Title', 'geditorial-was-born' ),
				'description' => _x( 'Roles that can Manage, Edit and Delete Age Groups', 'Setting Description', 'geditorial-was-born' ),
				'values'      => $roles,
			],
			[
				'field'       => 'reports_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'Reports Roles', 'Setting Title', 'geditorial-was-born' ),
				'description' => _x( 'Roles that can view age related reports.', 'Setting Description', 'geditorial-was-born' ),
				'values'      => $roles,
			],
		];

		return $settings;
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy'   => 'year_of_birth',
			'gender_taxonomy' => 'gender',
			'group_taxonomy'  => 'age_group',
			'group_query_var' => 'agefromto',

			'metakey_dob_posttype' => '_meta_date_of_birth',
			'term_empty_dob_data'  => 'date-of-birth-empty',
			'term_is_under_aged'   => 'is-under-aged',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy'   => _n_noop( 'Birth Year', 'Birth Years', 'geditorial-was-born' ),
				'gender_taxonomy' => _n_noop( 'Gender', 'Genders', 'geditorial-was-born' ),
				'group_taxonomy'  => _n_noop( 'Age Group', 'Age Groups', 'geditorial-was-born' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'show_option_all'      => _x( 'Birth Years', 'Label: `show_option_all`', 'geditorial-was-born' ),
					'show_option_no_items' => _x( '(No Birthdays)', 'Label: `show_option_no_items`', 'geditorial-was-born' ),
				],
				'gender_taxonomy' => [
					'show_option_all'      => _x( 'All Genders', 'Label: `show_option_all`', 'geditorial-was-born' ),
					'show_option_no_items' => _x( '(Undefined Gender)', 'Label: `show_option_no_items`', 'geditorial-was-born' ),
				],
				'group_taxonomy' => [
					'show_option_all'      => _x( 'Age Groups', 'Label: `show_option_all`', 'geditorial-was-born' ),
					'show_option_no_items' => _x( '(Empty Birthdays)', 'Label: `show_option_no_items`', 'geditorial-was-born' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Staff Age Summary', 'Dashboard Widget Title', 'geditorial-was-born' ), ],
			'all'     => [ 'widget_title' => _x( 'Editorial Staff Age Summary', 'Dashboard Widget Title', 'geditorial-was-born' ), ],
		];

		$strings['default_terms'] = [
			'main_taxonomy'   => Datetime::getYearsByDecades( '-100 year', 10, TRUE, 'code' ),
			'group_taxonomy'  => Datetime::getAgeStructure( TRUE ),
			'gender_taxonomy' => [
				'male'   => _x( 'Male', 'Default Term', 'geditorial-was-born' ),
				'female' => _x( 'Female', 'Default Term', 'geditorial-was-born' ),
			],
		];

		return $strings;
	}

	public function after_setup_theme()
	{
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$posttypes = $this->get_setting_posttypes( 'parent' );

		if ( empty( $posttypes ) )
			return;

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical' => TRUE,
			'show_in_menu' => FALSE,
		], $posttypes, [
			'manage_terms' => $this->caps['settings'],
			'edit_terms'   => $this->caps['settings'],
			'delete_terms' => $this->caps['settings'],
			'assign_terms' => $this->caps['settings'],
		] );

		$this->register_taxonomy( 'gender_taxonomy', [
			'hierarchical' => TRUE,
			'show_in_menu' => FALSE,
		], $posttypes, [
			'manage_terms' => $this->caps['settings'],
			'edit_terms'   => $this->caps['settings'],
			'delete_terms' => $this->caps['settings'],
			'assign_terms' => $this->caps['settings'],
		] );

		$this->register_taxonomy( 'group_taxonomy', [
			'public'       => FALSE,
			'rewrite'      => FALSE,
			'show_in_menu' => FALSE,
		], FALSE, TRUE );

		if ( $this->get_setting( 'override_dates', TRUE ) )
			$this->latechores__init_post_aftercare( $posttypes );

		$this->corecaps__handle_taxonomy_metacaps_forced( 'group_taxonomy' );

		$this->filter_self( 'mean_age', 4 );
		$this->action_module( 'pointers', 'post', 5, 100 );
		$this->filter_module( 'audit', 'auto_audit_save_post', 5 );
		$this->filter_module( 'tabloid', 'view_data', 3, 9 );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'users.php' );

		} else if ( $this->constant( 'gender_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'users.php' );

		} else if ( $this->constant( 'group_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'users.php' );

		} else if ( $this->in_setting_posttypes( $screen->post_type, 'parent' ) ) {

			if ( 'post' == $screen->base ) {

				add_action( $this->hook_base( 'metabox', 'mainbox', $screen->post_type ),
					function ( $object, $box, $context, $screen ) {
						MetaBox::singleselectTerms( $object->ID, [
							'taxonomy' => $this->constant( 'gender_taxonomy' ),
							'posttype' => $object->post_type,
						] );
					}, 10, 4 );

			} else if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_restrict', TRUE )
					&& $this->role_can( 'reports' ) ) {

					$this->action( 'restrict_manage_posts', 2, 22, 'admin_restrict' );
					$this->action( 'parse_query', 1, 22, 'admin_restrict' );
					$this->filter_append( $this->hook_base( 'screen_restrict_taxonomies' ), [
						$this->constant( 'gender_taxonomy' ),
						$this->constant( 'group_taxonomy' ),
					] );
				}
			}
		}
	}

	public function admin_menu()
	{
		$this->_hook_menu_taxonomy( 'main_taxonomy', 'users.php' );
		$this->_hook_menu_taxonomy( 'gender_taxonomy', 'users.php' );
		$this->_hook_menu_taxonomy( 'group_taxonomy', 'users.php' );
	}

	public function restrict_manage_posts_admin_restrict( $posttype, $which )
	{
		$option = get_user_option( sprintf( '%s_restrict_%s', $this->base, $posttype ) );

		if ( FALSE === $option || in_array( $this->constant( 'gender_taxonomy' ), (array) $option, TRUE ) )
			Listtable::restrictByTaxonomy( $this->constant( 'gender_taxonomy' ) );

		if ( FALSE === $option || in_array( $this->constant( 'group_taxonomy' ), (array) $option, TRUE ) )
			Listtable::restrictByTaxonomy( $this->constant( 'group_taxonomy' ), FALSE, [
				'hide_empty'    => FALSE,
				'hide_if_empty' => FALSE,
			] );
	}

	public function parse_query_admin_restrict( &$query )
	{
		Listtable::parseQueryTaxonomy( $query, $this->constant( 'gender_taxonomy' ) );

		$object    = WordPress\Taxonomy::object( $this->constant( 'group_taxonomy' ) );
		$query_var = empty( $object->query_var ) ? $object->name : $object->query_var;

		if ( ! isset( $query->query_vars[$query_var] ) )
			return;

		if ( ! $posttype = $query->query_vars['post_type'] )
			return;

		if ( '-1' == $query->query_vars[$query_var] ) {

			$meta_query = isset( $query->query_vars['meta_query'] )
				? $query->query_vars['meta_query']
				: [];

			$meta_query[] = [
				'key'     => $this->_get_posttype_dob_metakey( $posttype ),
				'compare' => 'NOT EXISTS',
			];

			$query->set( 'meta_query', $meta_query );
			unset( $query->query_vars[$query_var] );

		} else if ( $query->query_vars[$query_var] ) {

			$term = get_term_by( 'slug', $query->query_vars[$query_var], $object->name );

			if ( ! empty( $term ) && ! is_wp_error( $term ) ) {

				$metakey = $this->_get_posttype_dob_metakey( $posttype );

				if ( $group = $this->_get_age_group_metaquery( $term, $metakey ) ) {

					$meta_query = isset( $query->query_vars['meta_query'] )
					? $query->query_vars['meta_query']
					: [];

					$meta_query[] = [ $group['meta'] ];
					$query->set( 'meta_query', $meta_query );
				}
			}

			unset( $query->query_vars[$query_var] );
		}
	}

	protected function dashboard_widgets()
	{
		if ( ! $posttypes = $this->get_setting_posttypes( 'parent' ) )
			return;

		if ( ! $this->role_can( 'reports' ) )
			return;

		$this->add_dashboard_widget( 'dashboard-summary', NULL, 'refresh' );
	}

	protected function get_dashboard_summary_content( $scope = 'all', $user_id = NULL, $list = 'li' )
	{
		$html      = '';
		$posttypes = $this->get_setting_posttypes( 'parent' );

		foreach ( $this->_summary_age_groups( $posttypes ) as $group_summary )
			$html.= Core\HTML::tag( $list, [ 'class' => '-age-group' ], $group_summary );

		foreach ( $this->_summary_age_empty_dob( $posttypes ) as $empty_dob )
			$html.= Core\HTML::tag( $list, [ 'class' => 'warning -empty-dob' ], $empty_dob );

		foreach ( $this->filters( 'dashboard_summary_main', [], $posttypes, $scope, $user_id ) as $class => $filtered )
			$html.= Core\HTML::tag( $list, [ 'class' => $class ], $filtered );

		if ( $html )
			$html.= '</ul></div><div class="sub"><ul class="-pointers">';

		// TODO: mean-age by gender
		$gender_summary = $this->get_dashboard_term_summary( 'gender_taxonomy',
			$this->get_setting_posttypes( 'parent' ), NULL, $scope, $user_id, $list );

		if ( $gender_summary )
			$html.= $gender_summary;

		foreach ( $this->_summary_mean_age( $posttypes ) as $mean_age )
			$html.= Core\HTML::tag( $list, [ 'class' => '-mean-age' ], $mean_age );

		foreach ( $this->_summary_under_aged( $posttypes ) as $under_aged )
			$html.= Core\HTML::tag( $list, [ 'class' => 'warning -under-aged' ], $under_aged );

		foreach ( $this->_summary_age_invalid_dob( $posttypes ) as $invalid_dob )
			$html.= Core\HTML::tag( $list, [ 'class' => 'warning -invalid-dob' ], $invalid_dob );

		foreach ( $this->filters( 'dashboard_summary_sub', [], $posttypes, $scope, $user_id ) as $class => $filtered )
			$html.= Core\HTML::tag( $list, [ 'class' => $class ], $filtered );

		return $html;
	}

	// TODO: make link for restricted under aged posts
	// @REF: https://stackoverflow.com/a/71815721
	private function _summary_under_aged( $posttypes )
	{
		$nooped    = WordPress\PostType::get( 3 );
		$legal     = $this->get_setting( 'age_of_majority', 18 );
		$query_var = 'underaged';
		$list      = $access = [];

		$timezone = new \DateTimeZone( Core\Date::currentTimeZone() );
		$now      = new \DateTime( 'now', $timezone );
		$then     = new \DateTime( sprintf( '-%s years', $legal ), $timezone );

		$today = $now->format( 'Y-m-d' );
		$limit = $then->format( 'Y-m-d' );

		foreach ( $posttypes as $posttype ) {

			$metakey = $this->_get_posttype_dob_metakey( $posttype );

			$args = [
				'post_type'   => $posttype,
				'post_status' => WordPress\Status::available( $posttype ),
				'meta_query'  => [ [
					'key'     => $metakey,
					'value'   => [ $limit, $today ],
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				] ],

				'orderby'                => 'none',
				'fields'                 => 'ids',
				'posts_per_page'         => -1,
				'no_found_rows'          => TRUE,
				'update_post_meta_cache' => FALSE,
				'update_post_term_cache' => FALSE,
				'lazy_load_term_meta'    => FALSE,
			];

			$query = new \WP_Query();
			$posts = $query->query( $args );

			if ( ! $count = count( $posts ) )
				continue;

			$title = _x( 'Under-Aged', 'Summary', 'geditorial-was-born' );

			if ( count( $posttypes ) > 1 )
				$text = vsprintf( '<b>%3$s</b> %1$s: <span title="%4$s">[%2$s]</span>', [
					Helper::noopedCount( $count, $nooped[$posttype] ),
					WordPress\Strings::trimChars( $title, 35 ),
					Core\Number::format( $count ),
					$title,
				] );

			else
				$text = vsprintf( '<b>%2$s</b> <span>[%1$s]</span>', [
					$title,
					sprintf( Helper::noopedCount( $count, Info::getNoop( 'person' ) ), Core\Number::format( $count ) ),
				] );

			if ( ! array_key_exists( $posttype, $access ) )
				$access[$posttype] = WordPress\PostType::can( $posttype, 'edit_posts' );

			$classes = [
				'geditorial-glance-item',
				'-'.$this->key,
				'-under-aged-'.$posttype.'-count',
			];

			if ( $access[$posttype] )
				$list[] = Core\HTML::tag( 'a', [
					'href'  => Core\WordPress::getPostTypeEditLink( $posttype, 0, [ $query_var => $legal ] ),
					'class' => $classes,
				], $text );

			else
				$list[] = Core\HTML::wrap( $text, $classes, FALSE );
		}

		return $list;
	}

	// @SEE: https://stackoverflow.com/a/35733405
	private function _summary_age_invalid_dob( $posttypes )
	{
		$nooped    = WordPress\PostType::get( 3 );
		$query_var = 'invaliddob';
		$list      = $access = [];

		foreach ( $posttypes as $posttype ) {

			$metakey = $this->_get_posttype_dob_metakey( $posttype );

			$args = [
				'post_type'   => $posttype,
				'post_status' => WordPress\Status::available( $posttype ),
				'meta_query'  => [
					'relation' => 'AND',
					[
						'key'     => $metakey,
						'value'   => '([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})', // @REF: https://regexlib.com/REDetails.aspx?regexp_id=193
						'compare' => 'NOT REGEXP',
					],
					[
						'key'     => $metakey,
						'compare' => 'EXISTS',
					],
				],

				'orderby'                => 'none',
				'fields'                 => 'ids',
				'posts_per_page'         => -1,
				'no_found_rows'          => TRUE,
				'update_post_meta_cache' => FALSE,
				'update_post_term_cache' => FALSE,
				'lazy_load_term_meta'    => FALSE,
			];

			$query = new \WP_Query();
			$posts = $query->query( $args );

			if ( ! $count = count( $posts ) )
				continue;

			$title = _x( 'Invalid Birthday', 'Summary', 'geditorial-was-born' );

			if ( count( $posttypes ) > 1 )
				$text = vsprintf( '<b>%3$s</b> %1$s: <span title="%4$s">[%2$s]</span>', [
					Helper::noopedCount( $count, $nooped[$posttype] ),
					WordPress\Strings::trimChars( $title, 35 ),
					Core\Number::format( $count ),
					$title,
				] );

			else
				$text = vsprintf( '<b>%2$s</b> <span>[%1$s]</span>', [
					$title,
					sprintf( Helper::noopedCount( $count, Info::getNoop( 'person' ) ), Core\Number::format( $count ) ),
				] );

			if ( ! array_key_exists( $posttype, $access ) )
				$access[$posttype] = WordPress\PostType::can( $posttype, 'edit_posts' );

			$classes = [
				'geditorial-glance-item',
				'-'.$this->key,
				'-invalid-dob-'.$posttype.'-count',
			];

			if ( $access[$posttype] )
				$list[] = Core\HTML::tag( 'a', [
					'href'  => Core\WordPress::getPostTypeEditLink( $posttype, 0, [ $query_var => 1 ] ),
					'class' => $classes,
				], $text );

			else
				$list[] = Core\HTML::wrap( $text, $classes, FALSE );
		}

		return $list;
	}

	// @SEE: https://stackoverflow.com/questions/13372395/average-age-from-dob-field-mysql-php
	private function _summary_mean_age( $posttypes )
	{
		global $wpdb;

		$roundup = $this->get_setting( 'average_round_up', TRUE );
		$labels  = WordPress\PostType::get( 0 );
		$list    = $metakey = [];

		foreach ( $posttypes as $posttype ) {

			if ( ! array_key_exists( $posttype, $metakey ) )
				$metakey[$posttype] = $this->_get_posttype_dob_metakey( $posttype );

			// SELECT AVG((TO_DAYS(NOW())-TO_DAYS(DOB)))/365.242199
			// SELECT AVG(DATEDIFF(YEAR(NOW()), YEAR(m.meta_value))) as `Average`

			$query = $wpdb->prepare( "
				SELECT AVG((TO_DAYS(NOW())-TO_DAYS(m.meta_value)))/365.242199
				FROM {$wpdb->postmeta} as m
				WHERE m.meta_key = %s
				AND trim(coalesce(m.meta_value, '')) <> ''
			", $metakey[$posttype] );

			if ( ! $average = $wpdb->get_var( $query ) )
				continue;

			if ( $roundup )
				// $average = ceil( $average );
				$average = round( $average );

			$title = _x( 'Mean-Age', 'Summary', 'geditorial-was-born' );

			if ( count( $posttypes ) > 1 )
				$text = vsprintf( '<b>%3$s</b> (%1$s) <span title="%4$s">[%2$s]</span>', [
					$labels[$posttype],
					WordPress\Strings::trimChars( $title, 35 ),
					sprintf( Helper::noopedCount( $average, Info::getNoop( 'year' ) ), Core\Number::format( $average ) ),
					$title,
				] );

			else
				$text = vsprintf( '<b>%2$s</b> <span>[%1$s]</span>', [
					$title,
					sprintf( Helper::noopedCount( $average, Info::getNoop( 'year' ) ), Core\Number::format( $average ) ),
				] );

			$classes = [
				'geditorial-glance-item',
				'-'.$this->key,
				'-mean-age-'.$posttype,
			];

			$list[] = Core\HTML::wrap( $text, $classes, FALSE );
		}

		return $list;
	}

	private function _summary_age_empty_dob( $posttypes )
	{
		$taxonomy = $this->constant( 'group_taxonomy' );
		$object    = WordPress\Taxonomy::object( $taxonomy );
		$query_var = empty( $object->query_var ) ? $object->name : $object->query_var;
		$nooped    = WordPress\PostType::get( 3 );
		$list      = $access = $metakey = [];

		foreach ( $posttypes as $posttype ) {

			if ( ! array_key_exists( $posttype, $metakey ) )
				$metakey[$posttype] = $this->_get_posttype_dob_metakey( $posttype );

			$args = [
				'post_type'   => $posttype,
				'post_status' => WordPress\Status::available( $posttype ),
				'meta_query'  => [
					'relation' => 'OR',
					[
						'key'     => $metakey[$posttype],
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => $metakey[$posttype],
						'compare' => '=',
						'value'   => '',
					],
					[
						'key'     => $metakey[$posttype],
						'compare' => '=',
						'value'   => '0',
					],
				],
				'orderby'                => 'none',
				'fields'                 => 'ids',
				'posts_per_page'         => -1,
				'no_found_rows'          => TRUE,
				'update_post_meta_cache' => FALSE,
				'update_post_term_cache' => FALSE,
				'lazy_load_term_meta'    => FALSE,
			];

			$query = new \WP_Query();
			$posts = $query->query( $args );

			if ( ! $count = count( $posts ) )
				continue;

			$title = _x( 'No Birthday', 'Summary', 'geditorial-was-born' );

			if ( count( $posttypes ) > 1 )
				$text = vsprintf( '<b>%3$s</b> %1$s: <span title="%4$s">[%2$s]</span>', [
					Helper::noopedCount( $count, $nooped[$posttype] ),
					WordPress\Strings::trimChars( $title, 35 ),
					Core\Number::format( $count ),
					$title,
				] );

			else
				$text = vsprintf( '<b>%2$s</b> <span>[%1$s]</span>', [
					$title,
					sprintf( Helper::noopedCount( $count, Info::getNoop( 'person' ) ), Core\Number::format( $count ) ),
				] );

			if ( ! array_key_exists( $posttype, $access ) )
				$access[$posttype] = WordPress\PostType::can( $posttype, 'edit_posts' );

			$classes = [
				'geditorial-glance-item',
				'-'.$this->key,
				'-nodob-'.$posttype.'-count',
			];

			if ( $access[$posttype] )
				$list[] = Core\HTML::tag( 'a', [
					'href'  => Core\WordPress::getPostTypeEditLink( $posttype, 0, [ $query_var => '-1' ] ),
					'class' => $classes,
				], $text );

			else
				$list[] = Core\HTML::wrap( $text, $classes, FALSE );
		}

		return $list;
	}

	private function _summary_age_groups( $posttypes )
	{
		$taxonomy = $this->constant( 'group_taxonomy' );
		$extra    = []; // TODO: make sure the terms are in order

		if ( ! $terms = WordPress\Taxonomy::listTerms( $taxonomy, 'all', $extra ) )
			return [];

		$object    = WordPress\Taxonomy::object( $taxonomy );
		$query_var = empty( $object->query_var ) ? $object->name : $object->query_var;
		$nooped    = WordPress\PostType::get( 3 );
		$list      = $access = $metakey = [];

		foreach ( $terms as $term ) {

			foreach ( $posttypes as $posttype ) {

				if ( ! array_key_exists( $posttype, $metakey ) )
					$metakey[$posttype] = $this->_get_posttype_dob_metakey( $posttype );

				if ( ! $group = $this->_get_age_group_metaquery( $term, $metakey[$posttype] ) )
					continue;

				$args = [
					'post_type'              => $posttype,
					'post_status'            => [ 'publish', 'future', 'draft', 'pending' ],
					'meta_query'             => [ $group['meta'] ],
					'orderby'                => 'none',
					'fields'                 => 'ids',
					'posts_per_page'         => -1,
					'no_found_rows'          => TRUE,
					'update_post_meta_cache' => FALSE,
					'update_post_term_cache' => FALSE,
					'lazy_load_term_meta'    => FALSE,
				];

				$query = new \WP_Query();
				$posts = $query->query( $args );
				$count = count( $posts );

				$name = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );
				$span = vsprintf(
					/* translators: %1$s: date start, %2$s: date end */
					_x( 'From %1$s till %2$s', 'Age Span Exact Dates', 'geditorial-was-born' ),
					[
						$group['from'] ? wp_date( 'Y/n/j', Core\Date::getObject( $group['from'] )->getTimestamp() ) : _x( 'Begin', 'Age Span Exact Dates', 'geditorial-was-born' ),
						$group['to'] ? wp_date( 'Y/n/j', Core\Date::getObject( $group['to'] )->getTimestamp() ) : _x( 'Now', 'Age Span Exact Dates', 'geditorial-was-born' ),
					]
				);

				if ( count( $posttypes ) > 1 )
					$text = vsprintf( '<b title="%5$s">%3$s</b> %1$s: <span title="%4$s">[%2$s]</span>', [
						Helper::noopedCount( $count, $nooped[$posttype] ),
						WordPress\Strings::trimChars( $name, 35 ),
						Core\Number::format( $count ),
						$name,
						$span,
					] );

				else
					$text = vsprintf( '<b>%2$s</b> <span title="%3$s">[%1$s]</span>', [
						$name,
						sprintf( Helper::noopedCount( $count, Info::getNoop( 'person' ) ), Core\Number::format( $count ) ),
						$span,
					] );

				if ( ! array_key_exists( $posttype, $access ) )
					$access[$posttype] = WordPress\PostType::can( $posttype, 'edit_posts' );

				$classes = [
					'geditorial-glance-item',
					'-'.$this->key,
					'-term',
					'-taxonomy-'.$taxonomy,
					'-term-'.$term->slug.'-'.$posttype.'-count',
				];

				if ( $access[$posttype] )
					$list[$term->slug] = Core\HTML::tag( 'a', [
						'href'  => Core\WordPress::getPostTypeEditLink( $posttype, 0, [ $query_var => $term->slug ] ),
						'class' => $classes,
					], $text );

				else
					$list[$term->slug] = Core\HTML::wrap( $text, $classes, FALSE );
			}
		}

		return $list;
	}

	private function _get_age_group_metaquery( $term, $metakey )
	{
		$max = get_term_meta( $term->term_id, 'max', TRUE );
		$min = get_term_meta( $term->term_id, 'min', TRUE );

		if ( ! $max && ! $min )
			return FALSE;

		$from = $to = FALSE;
		$meta = [
			'key'  => $metakey,
			'type' => 'date',
		];

		if ( $max ) {
			$start = new \DateTime( sprintf( '-%d years', $max ) );
			$from  = $start->format( Core\Date::MYSQL_FORMAT );
		}

		if ( $min ) {
			$end = new \DateTime( sprintf( '-%d years', $min ) );
			$to  = $end->format( Core\Date::MYSQL_FORMAT );
		}

		if ( $min && $max ) {

			$meta['value']   = [ $from, $to ];
			$meta['compare'] = 'between';

		} else if ( $min ) {

			$meta['value']   = $to;
			$meta['compare'] = '<=';

		} else if ( $max ) {

			$meta['value']   = $from;
			$meta['compare'] = '>=';

		} else {

			return FALSE;
		}

		return [
			'meta' => $meta,
			'from' => $from,
			'to'   => $to,
		];
	}

	private function _get_posttype_dob_metakey( $posttype )
	{
		if ( $setting = $this->get_setting( $posttype.'_posttype_dob_metakey' ) )
			return $setting;

		if ( $default = $this->filters( 'default_posttype_dob_metakey', '', $posttype ) )
			return $default;

		return $this->constant( 'metakey_dob_posttype' );
	}

	// Mean age refers to the average of ages of a different person.
	// Mean Age = Total Sum of Ages / Total Number of Members
	// ( ( 14 + 17 + 15 + 19 + 14 + 16 + 16 + 17 + 20 ) / 9 ) = 16.9
	// @REF: https://www.easycalculation.com/statistics/mean-age-calculator.php
	// @FILTER: `geditorial_was_born_mean_age`
	public function mean_age( $null, $parent, $posts, $supported )
	{
		if ( empty( $posts ) || empty( $supported ) )
			return $null;

		$posttypes = $this->get_setting_posttypes( 'parent' );

		if ( ! array_intersect( $posttypes, $supported ) )
			return $null;

		$roundup  = $this->get_setting( 'average_round_up', TRUE );
		$cachekey = $this->hash( 'mean_age', 'posts', Core\Arraay::pluck( $posts, 'ID' ), $roundup );

		if ( Core\WordPress::isFlush() )
			delete_transient( $cachekey );

		if ( FALSE === ( $average = get_transient( $cachekey ) ) ) {

			$list = [];
			$cal  = $this->default_calendar();

			foreach ( $posts as $post ) {

				if ( ! in_array( $post->post_type, $posttypes, TRUE ) )
					continue;

				if ( ! $metakey = $this->_get_posttype_dob_metakey( $post->post_type ) )
					continue;

				if ( ! $dob = get_post_meta( $post->ID, $metakey, TRUE ) )
					continue;

				if ( ! $age = Core\Date::calculateAge( $dob, $cal ) )
					continue;

				if ( $age['year'] > 100 )
					continue; // probably the dob is invalid

				$list[$post->ID] = $age['year'];
			}

			$data    = Core\Arraay::prepNumeral( $list );
			$average = empty( $data ) ? NULL : Core\Number::average( $data, $roundup, FALSE );

			set_transient( $cachekey, $average, 12 * HOUR_IN_SECONDS );
		}

		return $average;
	}

	public function pointers_post( $post, $before, $after, $context, $screen )
	{
		if ( ! $this->in_setting( $post->post_type, 'parent_posttypes' ) )
			return;

		$metakey = $this->_get_posttype_dob_metakey( $post->post_type );
		$legal   = $this->get_setting( 'age_of_majority', 18 );
		$cal     = $this->default_calendar();

		if ( ! $dob = get_post_meta( $post->ID, $metakey, TRUE ) )
			return FALSE;

		echo $before;
		echo $this->get_column_icon();

		echo Datetime::prepDateOfBirth( $dob, 'Y/m/d', TRUE, $cal );

		if ( Core\Date::isUnderAged( $dob, $legal, $cal ) )
			printf( ' (<span class="%s">%s</span>)', '-color-danger -is-under-aged',
				_x( 'Under-Aged!', 'Pointer Notice', 'geditorial-was-born' ) );

		echo $after;

		$this->actions( 'pointers_post_after', $post, $dob, $cal, $before, $after );
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Helper::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_dob_data' ) => _x( 'Empty Date-of-Birth', 'Default Term: Audit', 'geditorial-was-born' ),
			$this->constant( 'term_is_under_aged' )  => _x( 'Is Under-Aged', 'Default Term: Audit', 'geditorial-was-born' ),
		] ) : $terms;
	}

	public function audit_auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( ! $this->in_setting( $post->post_type, 'parent_posttypes' ) )
			return $terms;

		if ( ! $metakey = $this->_get_posttype_dob_metakey( $post->post_type ) )
			return $terms;

		$dob = get_post_meta( $post->ID, $metakey, TRUE );

		if ( $exists = term_exists( $this->constant( 'term_empty_dob_data' ), $taxonomy ) ) {

			if ( ! empty( $dob ) )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

			else
				$terms[] = $exists['term_id'];
		}

		if ( $exists = term_exists( $this->constant( 'term_is_under_aged' ), $taxonomy ) ) {

			if ( ! Core\Date::isUnderAged( $dob, $this->get_setting( 'age_of_majority', 18 ), $this->default_calendar() ) )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

			else
				$terms[] = $exists['term_id'];
		}

		return $terms;
	}

	public function tabloid_view_data( $data, $post, $context )
	{
		if ( ! $this->in_setting( $post->post_type, 'parent_posttypes' ) || empty( $data['terms_rendered'] ) )
			return $data;

		// NOTE: needs to be non-associative array to render via Mustache
		$data['terms_rendered'] = array_values( Core\Arraay::filter( $data['terms_rendered'], [
			'name' => $this->constant( 'gender_taxonomy' ),
		], 'NOT' ) );

		return $data;
	}

	public function imports_settings( $sub )
	{
		if ( ! $this->check_settings( $sub, 'imports', 'per_page' ) )
			return;

		$this->action_self( 'postdate_after_post_override_date', 4 );
	}

	protected function render_imports_html( $uri, $sub )
	{
		echo Settings::toolboxColumnOpen( _x( 'Birthday Tools', 'Header', 'geditorial-was-born' ) );

		$posttypes = $this->get_setting_posttypes( 'parent' );

		if ( ! count( $posttypes ) )
			return Info::renderNoImportsAvailable();

		if ( $this->get_setting( 'override_dates', TRUE ) )
			$this->postdate__render_card_override_dates(
				$uri,
				$sub,
				$posttypes,
				_x( 'Post-Date by Birthday', 'Card Title', 'geditorial-was-born' )
			);

		else
			return Info::renderNoImportsAvailable();

		echo '</div>';
	}

	protected function render_imports_html_before( $uri, $sub )
	{
		return $this->postdate__render_before_override_dates(
			$this->get_setting( 'parent_posttypes', [] ),
			$this->_get_posttype_dob_metakey( self::req( 'type' ) ),
			$uri,
			$sub
		);
	}

	public function postdate_after_post_override_date( $post, $datetime, $metakeys, $verbose )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$taxonomy = $this->constant( 'main_taxonomy' );
		$date     = Core\Date::getObject( $datetime );

		if ( ! $year = wp_date( 'Y', $date->getTimestamp() ) )
			return FALSE;

		if ( ! $term = WordPress\Term::get( sprintf( 'year-%s', Core\Number::intval( $year, FALSE ) ), $taxonomy ) )
			return FALSE;

		$terms  = WordPress\Taxonomy::appendParentTermIDs( $term->term_id, $term->taxonomy );
		$result = wp_set_object_terms( $post->ID, $terms, $taxonomy, FALSE );

		return self::isError( $result ) ? FALSE : $year;

		if ( self::isError( $result ) )
			return ( $verbose ? print( Core\HTML::row( sprintf(
				/* translators: %1$s: year taxonomy, %2$s: post title */
				_x( 'There is problem updating year taxonomy (%1$s) for &ldquo;%2$s&rdquo;', 'Notice', 'geditorial-was-born' ) ),
				Core\HTML::code( $year ), WordPress\Post::title( $post ) ) ) : TRUE ) && FALSE;

		if ( $verbose )
			echo Core\HTML::row( sprintf(
				/* translators: %1$s: date-time, %2$s: year taxonomy, %3$s: post title */
				_x( '&ldquo;%1$s&rdquo; date is set with %2$s year on &ldquo;%3$s&rdquo;', 'Notice', 'geditorial-was-born' ),
				Core\HTML::code( Datetime::prepDateOfBirth( trim( $datetime ), 'Y/m/d' ) ),
				Core\HTML::code( $year ),
				WordPress\Post::title( $post )
			) );

		return TRUE;
	}

	protected function latechores_post_aftercare( $post )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $metakey = $this->_get_posttype_dob_metakey( $post->post_type ) )
			return FALSE;

		return $this->postdate__get_post_data_for_latechores( $post, $metakey );
	}

	private function _raise_resources( $count = 0 )
	{
		gEditorial()->disable_process( 'audit', 'import' );

		WordPress\Media::disableThumbnailGeneration();
		WordPress\Taxonomy::disableTermCounting();
		Services\LateChores::termCountCollect();
		wp_defer_comment_counting( TRUE );

		if ( ! Core\WordPress::isDev() )
			do_action( 'qm/cease' ); // QueryMonitor: Cease data collections

		$this->raise_resources( $count, 60, 'import' );
	}
}
