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
	use Internals\Deprecated;
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
				'cptmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_editlist' => [
				'admin_ordering',
			],
			'_supports' => [
				'assign_default_term',
				'comment_status',
				'widget_support',
				'thumbnail_support',
				$this->settings_supports_option( 'primary_posttype', TRUE ),
			],
		];
	}

	public function get_global_fields()
	{
		return [
			'meta' => [
				$this->constant( 'primary_posttype' ) => [
					'datestart' => [
						'title'       => _x( 'Event Start', 'Fields', 'geditorial-happening' ),
						'description' => _x( 'Determines the date and time in which the Event is scheduled to commence.', 'Fields', 'geditorial-happening' ),
						'icon'        => 'calendar',
						'type'        => 'datetime',
					],
					'dateend' => [
						'title'       => _x( 'Event End', 'Fields', 'geditorial-happening' ),
						'description' => _x( 'Determines the date and time in which the Event is scheduled to conclude.', 'Fields', 'geditorial-happening' ),
						'icon'        => 'calendar',
						'type'        => 'datetime',
					],
					// FIXME: rename
					'event_allday' => [
						'title'       => _x( 'Event All-Day', 'Fields', 'geditorial-happening' ),
						'description' => _x( 'Determines that is an all-day event.', 'Fields', 'geditorial-happening' ),
						'icon'        => 'calendar-alt',
						'type'        => 'checkbox',
					],
					// FIXME: rename
					'event_repeat' => [
						'title'       => _x( 'Event Repeat', 'Fields', 'geditorial-happening' ),
						'description' => _x( 'Event Repeat', 'Fields', 'geditorial-happening' ),
						'icon'        => 'update',
						'type'        => 'select', // FIXME: support selects!
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
				],
			]
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype'  => 'event',
			'primary_taxonomy'  => 'event_category',
			'type_taxonomy'     => 'event_type',
			'calendar_taxonomy' => 'event_calendar',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'primary_taxonomy'  => 'category',
				'type_taxonomy'     => 'tag',
				'calendar_taxonomy' => 'calendar',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype'  => _n_noop( 'Event', 'Events', 'geditorial-happening' ),
				'primary_taxonomy'  => _n_noop( 'Event Category', 'Event Categories', 'geditorial-happening' ),
				'type_taxonomy'     => _n_noop( 'Event Type', 'Event Types', 'geditorial-happening' ),
				'calendar_taxonomy' => _n_noop( 'Event Calendar', 'Event Calendars', 'geditorial-happening' ),
			],
			'labels' => [
				'primary_taxonomy' => [
					'menu_name'      => _x( 'Categories', 'Menu Title', 'geditorial-happening' ),
					'featured_image' => _x( 'Poster Image', 'Label: Featured Image', 'geditorial-happening' ),
				],
				'type_taxonomy' => [
					'menu_name' => _x( 'Types', 'Menu Title', 'geditorial-happening' ),
				],
				'calendar_taxonomy' => [
					'menu_name' => _x( 'Calendars', 'Menu Title', 'geditorial-happening' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'type_taxonomy' => [
				'holiday' => _x( 'Holiday', 'Default Term', 'geditorial-happening' ),
				'birth'   => _x( 'Birth', 'Default Term', 'geditorial-happening' ),
				'death'   => _x( 'Death', 'Default Term', 'geditorial-happening' ),
				'start'   => _x( 'Start', 'Default Term', 'geditorial-happening' ),
				'end'     => _x( 'End', 'Default Term', 'geditorial-happening' ),
			],
		];
	}

	// needed for fields options
	public function posttypes( $posttypes = NULL )
	{
		return [ $this->constant( 'primary_posttype' ) ];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'primary_posttype' );
	}

	public function widgets_init()
	{
		register_widget( __NAMESPACE__.'\\Widgets\\EventPoster' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'primary_posttype' ) );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'primary_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
		], 'primary_posttype' );

		$this->register_taxonomy( 'type_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'primary_posttype', [
			'auto_parents' => TRUE,
		] );

		$this->register_taxonomy( 'calendar_taxonomy', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => '__checklist_terms_callback',
		], 'primary_posttype' );

		$this->register_posttype( 'primary_posttype', [
			'hierarchical' => TRUE,
		], [
			'primary_taxonomy' => TRUE,
		] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'primary_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );
				$this->_hook_post_updated_messages( 'primary_posttype' );

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );
				$this->coreadmin__hook_admin_ordering( $screen->post_type, 'date' );
				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );
			}
		}
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'primary_posttype' ) )
			$items[] = $glance;

		return $items;
	}
}
