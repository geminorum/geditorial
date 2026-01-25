<?php namespace geminorum\gEditorial\Modules\Team;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Team extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;
	use Internals\PostMeta;

	public static function module()
	{
		return [
			'name'     => 'team',
			'title'    => _x( 'Team', 'Modules: Team', 'geditorial-admin' ),
			'desc'     => _x( 'Profiles for Editorial Teams', 'Modules: Team', 'geditorial-admin' ),
			'icon'     => 'groups',
			'access'   => 'beta',
			'keywords' => [
				'manual-connect',
				'cptmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_supports' => [
				'thumbnail_support',
				$this->settings_supports_option( 'member_posttype', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'member_posttype' => 'team_member',
			'group_taxonomy'  => 'team_member_group',

			'o2o_name' => 'team_member_to_user',
		];
	}

	protected function get_global_strings()
	{
		return [
			'noops' => [
				'member_posttype' => _n_noop( 'Team Member', 'Team Members', 'geditorial-team' ),
				'group_taxonomy'  => _n_noop( 'Team Member Group', 'Team Member Groups', 'geditorial-team' ),
			],
			'labels' => [
				'member_posttype' => [
					'menu_name' => _x( 'The Team', 'Label Menu Name', 'geditorial-team' ),
				],
			],
		];
	}

	public function get_global_fields()
	{
		return [
			'meta' => [
				$this->constant( 'member_posttype' ) => [
					'team_role' => [
						'title'       => _x( 'Role', 'Field Title', 'geditorial-team' ),
						'description' => _x( 'Enter a byline for the team member (for example: "Director of Production").', 'Field Description', 'geditorial-team' ),
						'type'        => 'title_after',
						'icon'        => 'businessman',
					],
					'email_gravatar' => [
						'title'       => _x( 'Gravatar E-mail Address', 'Field Title', 'geditorial-team' ),
						'description' => _x( 'Enter an e-mail address, to use a Gravatar, instead of using the "Featured Image".', 'Field Description', 'geditorial-team' ),
						'type'        => 'code',
						'icon'        => 'admin-users',
					],
					'personal_site' => [
						'title'       => _x( 'Personal Site', 'Field Title', 'geditorial-team' ),
						'description' => _x( 'Enter this team member\'s URL (for example: https://geminorum.ir/).', 'Field Description', 'geditorial-team' ),
						'type'        => 'link',
						'icon'        => 'admin-links',
					],
					'email_contact' => [
						'title'       => _x( 'Contact E-mail Address', 'Field Title', 'geditorial-team' ),
						'description' => _x( 'Enter a contact email address for this team member to be displayed as a link on the frontend.', 'Field Description', 'geditorial-team' ),
						'type'        => 'code',
						'icon'        => 'email',
					],
					'phone' => [
						'title'       => _x( 'Telephone Number', 'Field Title', 'geditorial-team' ),
						'description' => _x( 'Enter a telephone number for this team member to be displayed as a link on the frontend.', 'Field Description', 'geditorial-team' ),
						'type'        => 'code',
						'icon'        => 'phone',
					],
					'twitter' => [
						'title'       => _x( 'Twitter Username', 'Field Title', 'geditorial-team' ),
						'description' => _x( 'Enter this team member\'s Twitter username without the @ (for example: geminorumir).', 'Field Description', 'geditorial-team' ),
						'type'        => 'code',
						'icon'        => 'twitter',
					],
					'username' => [
						'title'       => _x( 'Network Username', 'Field Title', 'geditorial-team' ),
						'description' => _x( 'Map this team member to a user on this site.', 'Field Description', 'geditorial-team' ),
						'type'        => 'code',
						'icon'        => 'nametag',
					],
				],
			],
		];
	}

	protected function posttypes_excluded( $extra = [] )
	{
		return $this->filters( 'posttypes_excluded',
			gEditorial\Settings::posttypesExcluded( $extra + [
				$this->constant( 'member_posttype' ),
			], $this->keep_posttypes )
		);
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'member_posttype' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'group_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'member_posttype', [
			'custom_icon' => 'groups',
		] );

		$this->register_posttype( 'member_posttype', [
			'menu_position'     => 65,
			'show_in_admin_bar' => FALSE,
		], [
			'custom_icon' => $this->module->icon,
		] );
	}

	public function o2o_init()
	{
		$this->_o2o = Services\O2O\API::registerConnectionType( [
			'name' => $this->constant( 'o2o_name' ),
			'from' => $this->constant( 'member_posttype' ),
			'to'   => 'user',

			'to_query_vars' => [
				'role' => 'contributor' // FIXME: get setting for this
			],
		] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'member_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->posttypes__media_register_headerbutton( 'member_posttype' );
				$this->_hook_post_updated_messages( 'member_posttype' );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_bulk_post_updated_messages( 'member_posttype' );
				$this->corerestrictposts__hook_screen_taxonomies( 'group_taxonomy' );
				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );
			}
		}
	}

	public function prep_meta_row_module( $value, $field_key = NULL, $field = [], $raw = NULL )
	{
		switch ( $field_key ) {
			case 'email_gravatar': return Core\HTML::mailto( $raw ?: $value );
			case 'email_contact' : return Core\HTML::mailto( $raw ?: $value );
			case 'personal_site' : return Core\HTML::link( $raw ?: $value );
		}

		return $value;
	}

	public function meta_init()
	{
		$this->add_posttype_fields_for( 'meta', 'member_posttype' );

		$this->filter( 'prep_meta_row', 2, 12, 'module', $this->base );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'member_posttype' ) )
			$items[] = $glance;

		return $items;
	}
}
