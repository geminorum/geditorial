<?php namespace geminorum\gEditorial\Modules\Team;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Third;
use geminorum\gEditorial\O2O;

class Team extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'team',
			'title' => _x( 'Team', 'Modules: Team', 'geditorial' ),
			'desc'  => _x( 'Profiles for Editorial Teams', 'Modules: Team', 'geditorial' ),
			'icon'  => 'groups',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_supports' => [
				'thumbnail_support',
				$this->settings_supports_option( 'member_cpt', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'member_cpt'         => 'team_member',
			'member_cpt_archive' => 'team-members',
			'member_group'       => 'team_member_group',

			'o2o_name' => 'team_member_to_user',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'member_group' => NULL,
			],
		];
	}

	protected function get_global_strings()
	{
		return [
			'misc' => [
				'tweaks_column_title' => _x( 'Team Member Groups', 'Column Title', 'geditorial-team' ),
			],
			'noops' => [
				'member_cpt'   => _n_noop( 'Team Member', 'Team Members', 'geditorial-team' ),
				'member_group' => _n_noop( 'Team Member Group', 'Team Member Groups', 'geditorial-team' ),
			],
			'labels' => [
				'member_cpt' => [
					'menu_name' => _x( 'Team Members', 'Posttype Menu', 'geditorial-team' ),
				],
			],
		];
	}

	public function get_global_fields()
	{
		return [
			$this->constant( 'member_cpt' ) => [
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
		];
	}

	protected function posttypes_excluded()
	{
		return Settings::posttypesExcluded( $this->constant( 'member_cpt' ) );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'member_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'member_group', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'member_cpt' );

		$this->register_posttype( 'member_cpt', [
			'menu_position'     => 65,
			'show_in_admin_bar' => FALSE,
		] );
	}

	public function o2o_init()
	{
		$this->_o2o = O2O\API::registerConnectionType( [
			'name' => $this->constant( 'o2o_name' ),
			'from' => $this->constant( 'member_cpt' ),
			'to'   => 'user',

			'to_query_vars' => [
				'role' => 'contributor' // FIXME: get setting fot this
			],
		] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'member_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'post_updated_messages' );

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );

				$this->_hook_screen_restrict_taxonomies();

				$this->action_module( 'meta', 'column_row', 3 );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}
		}
	}

	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [ 'member_group' ];
	}

	public function display_meta_row( $value, $key = NULL, $field = [] )
	{
		switch ( $key ) {
			case 'email_gravatar' : return HTML::mailto( $value );
			case 'email_contact'  : return HTML::mailto( $value );
			case 'personal_site'  : return HTML::link( $value );
			case 'phone'          : return HTML::tel( $value );
			case 'twitter'        : return Third::htmlTwitterIntent( $value, is_admin() );
			case 'username'       : return '@'.$value; // FIXME
		}

		return parent::display_meta_row( $value, $key, $field );
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'member_cpt' ) );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'member_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'member_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'member_cpt', $counts ) );
	}
}
