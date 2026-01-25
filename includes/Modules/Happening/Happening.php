<?php namespace geminorum\gEditorial\Modules\Happening;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Happening extends gEditorial\Module
{
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;
	use Internals\LateChores;
	use Internals\PostDate;
	use Internals\PostTypeFields;

	public static function module()
	{
		return [
			'name'     => 'happening',
			'title'    => _x( 'Happening', 'Modules: Happening', 'geditorial-admin' ),
			'desc'     => _x( 'Integrated Events', 'Modules: Happening', 'geditorial-admin' ),
			'icon'     => 'calendar-alt',
			'access'   => 'beta',
			'keywords' => [
				'event',
				'calendar',
				'has-widgets',
				'cptmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_editlist' => [
				'admin_ordering',
				'admin_bulkactions',
				'show_in_quickedit' => [ $this->get_taxonomy_show_in_quickedit_desc( 'status_taxonomy' ), '1' ],
			],
			'_supports' => [
				'override_dates',
				'assign_default_term',
				'comment_status',
				'widget_support',
				'thumbnail_support',
				$this->settings_supports_option( 'main_posttype', TRUE ),
			],
			'_constants' => [
				'main_posttype_constant'     => [ NULL, 'event' ],
				'category_taxonomy_constant' => [ NULL, 'event_category' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_posttype'     => 'event',
			'category_taxonomy' => 'event_category',
			'type_taxonomy'     => 'event_type',
			'calendar_taxonomy' => 'event_calendar',
			'status_taxonomy'   => 'event_status',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_posttype'     => _n_noop( 'Event', 'Events', 'geditorial-happening' ),
				'category_taxonomy' => _n_noop( 'Event Category', 'Event Categories', 'geditorial-happening' ),
				'type_taxonomy'     => _n_noop( 'Event Type', 'Event Types', 'geditorial-happening' ),
				'calendar_taxonomy' => _n_noop( 'Event Calendar', 'Event Calendars', 'geditorial-happening' ),
				'status_taxonomy'   => _n_noop( 'Event Status', 'Event Statuses', 'geditorial-happening' ),
			],
			'labels' => [
				'category_taxonomy' => [
					'menu_name'      => _x( 'Categories', 'Menu Title', 'geditorial-happening' ),
					'featured_image' => _x( 'Poster Image', 'Label: Featured Image', 'geditorial-happening' ),
				],
				'type_taxonomy' => [
					'menu_name' => _x( 'Types', 'Menu Title', 'geditorial-happening' ),
				],
				'calendar_taxonomy' => [
					'menu_name' => _x( 'Calendars', 'Menu Title', 'geditorial-happening' ),
				],
				'status_taxonomy' => [
					'menu_name' => _x( 'Statuses', 'Label: Menu Name', 'geditorial-happening' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			// TODO: move to `ModuleInfo`
			'type_taxonomy' => [
				'holiday' => _x( 'Holiday', 'Default Term', 'geditorial-happening' ),
				'birth'   => _x( 'Birth', 'Default Term', 'geditorial-happening' ),
				'death'   => _x( 'Death', 'Default Term', 'geditorial-happening' ),
				'start'   => _x( 'Start', 'Default Term', 'geditorial-happening' ),
				'end'     => _x( 'End', 'Default Term', 'geditorial-happening' ),
			],
			'status_taxonomy' => ModuleInfo::getStatuses(),
		];
	}

	public function get_global_fields()
	{
		$posttype = $this->constant( 'main_posttype' );

		return [
			'meta' => [
				$posttype => [
					'over_title' => [ 'type' => 'title_before' ],
					'sub_title'  => [ 'type' => 'title_after' ],
					'alt_title'  => [ 'type' => 'text' ],

					'date' => [
						'title'       => _x( 'Event Date', 'Fields', 'geditorial-happening' ),
						'description' => _x( 'Determines the date in which the Event is scheduled.', 'Fields', 'geditorial-happening' ),
						'type'        => 'date',
						'quickedit'   => TRUE,
					],
					'datetime' => [
						'title'       => _x( 'Date-Time', 'Fields', 'geditorial-happening' ),
						'description' => _x( 'Determines the date and time in which the Event is scheduled.', 'Fields', 'geditorial-happening' ),
						'type'        => 'datetime',
						'quickedit'   => TRUE,
					],
					'datestart' => [
						'title'       => _x( 'Event Start', 'Fields', 'geditorial-happening' ),
						'description' => _x( 'Determines the date and time in which the Event is scheduled to commence.', 'Fields', 'geditorial-happening' ),
						'type'        => 'datetime',
						'quickedit'   => TRUE,
					],
					'dateend' => [
						'title'       => _x( 'Event End', 'Fields', 'geditorial-happening' ),
						'description' => _x( 'Determines the date and time in which the Event is scheduled to conclude.', 'Fields', 'geditorial-happening' ),
						'type'        => 'datetime',
						'quickedit'   => TRUE,
					],
					// FIXME: rename
					'event_allday' => [
						'title'       => _x( 'Event All-Day', 'Fields', 'geditorial-happening' ),
						'description' => _x( 'Determines that is an all-day event.', 'Fields', 'geditorial-happening' ),
						'icon'        => 'calendar-alt',
						'type'        => 'checkbox',
						// 'quickedit'   => TRUE, // FIXME: type `checkbox` not supported on quick-edit yet!
					],
					// FIXME: rename
					'event_repeat' => [
						'title'       => _x( 'Event Repeat', 'Fields', 'geditorial-happening' ),
						'description' => _x( 'Event Repeat', 'Fields', 'geditorial-happening' ),
						'icon'        => 'update',
						'type'        => 'select', // FIXME: support selects!
						// 'quickedit'   => TRUE, // FIXME: type `select` not supported on quick-edit yet!
						'values'      => [
							'0'    => _x( 'Never', 'Fields', 'geditorial-happening' ),
							'10'   => _x( 'Weekly', 'Fields', 'geditorial-happening' ),
							'100'  => _x( 'Monthly', 'Fields', 'geditorial-happening' ),
							'1000' => _x( 'Yearly', 'Fields', 'geditorial-happening' ),
						],
					],
					'dateexpire' => [
						'title'       => _x( 'Event Expire', 'Fields', 'geditorial-happening' ),
						'description' => _x( 'Event Expire', 'Fields', 'geditorial-happening' ),
						'icon'        => 'thumbs-down',
						'type'        => 'datetime',
					],

					'venue_string'   => [ 'type' => 'venue', 'quickedit' => TRUE ],
					'contact_string' => [ 'type' => 'contact' ], // url/email/phone
					'website_url'    => [ 'type' => 'link' ],
					'wiki_url'       => [ 'type' => 'link' ],
					'email_address'  => [ 'type' => 'email', 'quickedit' => TRUE ],

				],
			],
			'units' => [
				$posttype => [
					'total_days'   => [ 'type' => 'day',    'data_unit' => 'day'    ],
					'total_hours'  => [ 'type' => 'hour',   'data_unit' => 'hour'   ],
					'total_people' => [ 'type' => 'person', 'data_unit' => 'person' ],
				],
			],
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'main_posttype' );
	}

	public function widgets_init()
	{
		register_widget( __NAMESPACE__.'\\Widgets\\EventPoster' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_for( 'meta', 'main_posttype' );

		if ( $this->get_setting( 'override_dates', TRUE ) )
			$this->latechores__init_post_aftercare( $this->constant( 'main_posttype' ) );
	}

	public function units_init()
	{
		$this->add_posttype_fields_for( 'units', 'main_posttype' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'category_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
		], 'main_posttype', [
			'custom_icon' => 'category',
		] );

		$this->register_taxonomy( 'type_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'main_posttype', [
			'custom_icon'     => 'screenoptions',
			'auto_parents'    => TRUE,
			'admin_managed'   => TRUE,
			'single_selected' => TRUE,
		] );

		$this->register_taxonomy( 'calendar_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => '__checklist_terms_callback',
		], 'main_posttype', [
			'custom_icon' => 'calendar',
		] );

		$this->register_taxonomy( 'status_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit', TRUE ),
		], 'primary_posttype', [
			'admin_managed'   => TRUE,
			'single_selected' => TRUE,
		] );

		$this->register_posttype( 'main_posttype', [
			'hierarchical' => TRUE,
		], [
			'category_taxonomy' => TRUE,
			'status_taxonomy'   => TRUE,
		] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'main_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->comments__handle_default_status( $screen->post_type );
				$this->posttypes__media_register_headerbutton( 'main_posttype' );
				$this->_hook_post_updated_messages( 'main_posttype' );

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->modulelinks__register_headerbuttons();
				$this->latechores__hook_admin_bulkactions( $screen );
				$this->coreadmin__hook_admin_ordering( $screen->post_type, 'date' );
				$this->_hook_bulk_post_updated_messages( 'main_posttype' );
				$this->corerestrictposts__hook_screen_taxonomies( [
					'calendar_taxonomy',
					'category_taxonomy',
					'type_taxonomy',
					'status_taxonomy',
				] );
			}
		}
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'main_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	protected function latechores_post_aftercare( $post )
	{
		return $this->postdate__get_post_data_for_latechores(
			$post,
			Services\PostTypeFields::getPostDateMetaKeys()
		);
	}

	public function tools_settings( $sub )
	{
		$this->check_settings( $sub, 'tools' );
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo gEditorial\Settings::toolboxColumnOpen(
			_x( 'Happening Tools', 'Header', 'geditorial-happening' ) );

		$available = FALSE;

		if ( $this->get_setting( 'override_dates', TRUE ) )
			$available = $this->postdate__render_card_override_dates(
				$uri,
				$sub,
				$this->constant( 'main_posttype' ),
				_x( 'Event Date from Meta-data', 'Card', 'geditorial-happening' )
			);

		if ( ! $available )
			gEditorial\Info::renderNoToolsAvailable();

		echo '</div>';
	}

	protected function render_tools_html_before( $uri, $sub )
	{
		if ( FALSE === $this->postdate__render_before_override_dates(
			$this->constant( 'main_posttype' ),
			Services\PostTypeFields::getPostDateMetaKeys(),
			$uri,
			$sub,
			'tools'
		) )
			return FALSE;
	}
}
