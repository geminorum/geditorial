<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;

class Team extends gEditorial\Module
{

	// FIXME: needs shortcode/widget

	public static function module()
	{
		return [
			'name'  => 'team',
			'title' => _x( 'Team', 'Modules: Team', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Profiles for Editorial Teams', 'Modules: Team', GEDITORIAL_TEXTDOMAIN ),
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
			'member_cat'         => 'team_member_cat',
			'member_cat_slug'    => 'team-member-category',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'member_cat' => NULL,
			],
		];
	}

	protected function get_global_strings()
	{
		return [
			'misc' => [
				'member_cpt' => [
					'menu_name' => _x( 'Team Members', 'Modules: Team: Menu Name', GEDITORIAL_TEXTDOMAIN ),
				],
				'tweaks_column_title' => _x( 'Team Member Categories', 'Modules: Team: Column Title', GEDITORIAL_TEXTDOMAIN ),
			],
			'noops' => [
				'member_cpt' => _nx_noop( 'Team Member', 'Team Members', 'Modules: Team: Noop', GEDITORIAL_TEXTDOMAIN ),
				'member_cat' => _nx_noop( 'Team Member Category', 'Team Member Categories', 'Modules: Team: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
		];
	}

	public function get_global_fields()
	{
		return [
			$this->constant( 'member_cpt' ) => [
				'team_role' => [
					'title'       => _x( 'Role', 'Modules: Team: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Enter a byline for the team member (for example: "Director of Production").', 'Modules: Team: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'title_after',
					'icon'        => 'businessman',
				],
				'email_gravatar' => [
					'title'       => _x( 'Gravatar E-mail Address', 'Modules: Team: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Enter an e-mail address, to use a Gravatar, instead of using the "Featured Image".', 'Modules: Team: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'code',
					'icon'        => 'admin-users',
				],
				'personal_site' => [
					'title'       => _x( 'Personal Site', 'Modules: Team: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Enter this team member\'s URL (for example: http://geminorum.ir/).', 'Modules: Team: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'link',
					'icon'        => 'admin-links',
				],
				'email_contact' => [
					'title'       => _x( 'Contact E-mail Address', 'Modules: Team: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Enter a contact email address for this team member to be displayed as a link on the frontend.', 'Modules: Team: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'code',
					'icon'        => 'email',
				],
				'phone' => [
					'title'       => _x( 'Telephone Number', 'Modules: Team: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Enter a telephone number for this team member to be displayed as a link on the frontend.', 'Modules: Team: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'code',
					'icon'        => 'phone',
				],
				'twitter' => [
					'title'       => _x( 'Twitter Username', 'Modules: Team: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Enter this team member\'s Twitter username without the @ (for example: geminorumir).', 'Modules: Team: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'code',
					'icon'        => 'twitter',
				],
				'username' => [
					'title'       => _x( 'Network Username', 'Modules: Team: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Map this team member to a user on this site.', 'Modules: Team: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'code',
					'icon'        => 'nametag',
				],
			],
		];
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'member_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'member_cat', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'member_cpt' );

		$this->register_post_type( 'member_cpt', [
			'menu_position' => 65,
		] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'member_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'post_updated_messages' );

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );

				$this->action( 'restrict_manage_posts', 2, 12 );
				$this->action( 'parse_query' );

				add_action( 'geditorial_meta_column_row', [ $this, 'column_row_meta' ], 12, 3 );

				$this->_tweaks_taxonomy();
			}
		}
	}

	public function column_row_meta( $post, $fields, $meta )
	{
		foreach ( $fields as $field => $args ) {

			if ( empty( $meta[$field] ) )
				continue;

			echo '<li class="-row -team -field-'.$field.'">';
				echo $this->get_column_icon( FALSE, $args['icon'], $args['title'] );
				echo $this->display_meta( $meta[$field], $field, $args );
			echo '</li>';
		}
	}

	public function display_meta( $value, $key = NULL, $field = [] )
	{
		switch ( $key ) {
			case 'email_gravatar': return HTML::mailto( $value );
			case 'email_contact': return HTML::mailto( $value );
			case 'personal_site': return HTML::link( $value );
			case 'phone': return HTML::tel( $value );
			case 'twitter': return HTML::link( '@'.$value, sprintf( 'https://twitter.com/intent/user?screen_name=%s', $value ), TRUE ); // FIXME: validate
			case 'username': return '@'.$value; // FIXME
		}

		return esc_html( $value );
	}

	public function meta_init()
	{
		$this->add_post_type_fields( $this->constant( 'member_cpt' ) );
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

	public function restrict_manage_posts( $post_type, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'member_cat' );
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query, 'member_cat' );
	}
}
