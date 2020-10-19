<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\User;
// use geminorum\gEditorial\Templates\Inquire as ModuleTemplate;

class Inquire extends gEditorial\Module
{

	// protected $partials = [ 'Templates' ];

	public static function module()
	{
		return [
			'name'  => 'inquire',
			'title' => _x( 'Inquire', 'Modules: Inquire', 'geditorial' ),
			'desc'  => _x( 'Questions and Answers', 'Modules: Inquire', 'geditorial' ),
			'icon'  => 'editor-help',
		];
	}

	protected function get_global_settings()
	{
		$roles   = User::getAllRoleList();
		$exclude = [ 'administrator', 'subscriber' ];

		return [
			'_editpost' => [
				[
					'field'       => 'excerpt_roles',
					'type'        => 'checkbox-panel',
					'title'       => _x( 'Question Roles', 'Setting Title', 'geditorial-inquire' ),
					'description' => _x( 'Roles that can change the question.', 'Setting Description', 'geditorial-inquire' ),
					'exclude'     => $exclude,
					'values'      => $roles,
				],
			],
			'_supports' => [
				[
					'field'       => 'make_public',
					'title'       => _x( 'Make Public', 'Setting Title', 'geditorial-inquire' ),
					'description' => _x( 'Displays Inquiries on the front-end.', 'Setting Description', 'geditorial-inquire' ),
				],
				$this->settings_supports_option( 'inquiry_cpt', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'inquiry_cpt'         => 'inquiry',
			'inquiry_cpt_archive' => 'inquiries',
			'subject_tax'         => 'inquiry_subject',
			'status_tax'          => 'inquire_status',
			'status_tax_slug'     => 'inquire-statuses',
			'priority_tax'        => 'inquire_priority',
			'priority_tax_slug'   => 'inquire-priorities',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'subject_tax'  => NULL,
				'status_tax'   => 'tag',
				'priority_tax' => 'clipboard',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'inquiry_cpt'  => _n_noop( 'Inquiry', 'Inquiries', 'geditorial-inquire' ),
				'subject_tax'  => _n_noop( 'Inquiry Subject', 'Inquiry Subjects', 'geditorial-inquire' ),
				'status_tax'   => _n_noop( 'Inquiry Status', 'Inquiry Statuses', 'geditorial-inquire' ),
				'priority_tax' => _n_noop( 'Inquiry Priority', 'Inquiry Priorities', 'geditorial-inquire' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'inquiry_cpt' => [
				'menu_name'       => _x( 'Inquiries', 'Posttype Menu', 'geditorial-inquire' ),
				'excerpt_metabox' => _x( 'Question', 'MetaBox Title', 'geditorial-inquire' ),
			],
			'subject_tax' => [
				'menu_name'           => _x( 'Subjects', 'Menu Title', 'geditorial-inquire' ),
				'meta_box_title'      => _x( 'Subject', 'MetaBox Title', 'geditorial-inquire' ),
				'tweaks_column_title' => _x( 'Inquiry Subject', 'Column Title', 'geditorial-inquire' ),
			],
			'status_tax' => [
				'menu_name'           => _x( 'Statuses', 'Menu Title', 'geditorial-inquire' ),
				'meta_box_title'      => _x( 'Status', 'MetaBox Title', 'geditorial-inquire' ),
				'tweaks_column_title' => _x( 'Inquiry Status', 'Column Title', 'geditorial-inquire' ),
			],
			'priority_tax' => [
				'menu_name'           => _x( 'Priorities', 'Menu Title', 'geditorial-inquire' ),
				'meta_box_title'      => _x( 'Priority', 'MetaBox Title', 'geditorial-inquire' ),
				'tweaks_column_title' => _x( 'Inquiry Priority', 'Column Title', 'geditorial-inquire' ),
			],
		];

		$strings['terms'] = [
			'status_tax' => [
				'status_approved'    => _x( 'Approved', 'Default Term', 'geditorial-inquire' ),
				'status_pending'     => _x( 'Pending', 'Default Term', 'geditorial-inquire' ),
				'status_maybe_later' => _x( 'Maybe Later', 'Default Term', 'geditorial-inquire' ),
				'status_rejected'    => _x( 'Rejected', 'Default Term', 'geditorial-inquire' ),
			],
			'priority_tax' => [
				'priority_immediate' => _x( 'Immediate', 'Default Term', 'geditorial-inquire' ),
				'priority_high'      => _x( 'High', 'Default Term', 'geditorial-inquire' ),
				'priority_normal'    => _x( 'Normal', 'Default Term', 'geditorial-inquire' ),
				'priority_low'       => _x( 'Low', 'Default Term', 'geditorial-inquire' ),
				'priority_zero'      => _x( 'Zero', 'Default Term', 'geditorial-inquire' ),
			],
		];

		return $strings;
	}

	public function before_settings( $module = FALSE )
	{
		if ( isset( $_POST['install_def_status_tax'] ) )
			$this->insert_default_terms( 'status_tax' );

		else if ( isset( $_POST['install_def_priority_tax'] ) )
			$this->insert_default_terms( 'priority_tax' );

		$this->help_tab_default_terms( 'status_tax' );
		$this->help_tab_default_terms( 'priority_tax' );
	}

	public function default_buttons( $module = FALSE )
	{
		parent::default_buttons( $module );

		$this->register_button( 'install_def_status_tax', _x( 'Install Default Inquiry Statuses', 'Button', 'geditorial-inquire' ) );
		$this->register_button( 'install_def_priority_tax', _x( 'Install Default Inquiry Priorities', 'Button', 'geditorial-inquire' ) );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'subject_tax', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL, // default meta box
		], 'inquiry_cpt' );

		$this->register_taxonomy( 'status_tax', [
			'hierarchical'       => TRUE, // required by `MetaBox::checklistTerms()`
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'inquiry_cpt' );

		$this->register_taxonomy( 'priority_tax', [
			'hierarchical'       => TRUE, // required by `MetaBox::checklistTerms()`
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'inquiry_cpt' );

		$args = [];

		if ( ! $this->get_setting( 'make_public' ) )
			$args = [
				'public'              => FALSE,
				'show_ui'             => TRUE,
				'exclude_from_search' => TRUE,
				'publicly_queryable'  => FALSE,
				'show_in_nav_menus'   => FALSE,
				'show_in_admin_bar'   => FALSE,
				'show_in_rest'        => FALSE,
			];

		$this->register_posttype( 'inquiry_cpt', $args );
	}

	// protected function get_module_templates()
	// {
	// 	return [
	// 		'page_cpt' => [
	// 			'main' => _x( 'Editorial: Inquire: Dashboard', 'Template Title', 'geditorial-inquire' ),
	// 		],
	// 	];
	// }

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'inquiry_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'enter_title_here', 2 );
				$this->filter( 'post_updated_messages' );

				if ( post_type_supports( $screen->post_type, 'excerpt' ) ) {

					remove_meta_box( 'postexcerpt', $screen, 'normal' );
					MetaBox::classEditorBox( $screen, $this->classs( 'question' ) );

					add_meta_box( $this->classs( 'question' ),
						$this->get_string( 'excerpt_metabox', 'inquiry_cpt', 'misc' ),
						[ $this, 'do_metabox_excerpt' ],
						$screen,
						'after_title'
					);
				}

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );
				$this->action( 'restrict_manage_posts', 2, 12 );
				$this->action( 'parse_query' );
			}
		}
	}

	// FIXME: this is a hack for users with no `create_posts` cap
	// @REF: https://core.trac.wordpress.org/ticket/22895
	// @REF: https://wordpress.stackexchange.com/a/178059
	// @REF: https://herbmiller.me/2014/09/21/wordpress-capabilities-restrict-add-new-allowing-edit/
	public function admin_menu()
	{
		$posttype = PostType::object( $this->constant( 'inquiry_cpt' ) );
		add_submenu_page( 'edit.php?post_type='.$posttype->name, '', '', $posttype->cap->edit_posts, $this->classs() );
		$this->filter( 'add_menu_classes' );
	}

	public function add_menu_classes( $menu )
	{
		remove_submenu_page( 'edit.php?post_type='.$this->constant( 'inquiry_cpt' ), $this->classs() );
		return $menu;
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'inquiry_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function enter_title_here( $title, $post )
	{
		return _x( 'Enter question title here', 'Placeholder', 'geditorial-inquire' );
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'inquiry_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'inquiry_cpt', $counts ) );
	}

	public function restrict_manage_posts( $posttype, $which )
	{
		$this->do_restrict_manage_posts_taxes( [
			'subject_tax',
			'status_tax',
			'priority_tax',
		] );
	}

	public function parse_query( &$query )
	{
		$this->do_parse_query_taxes( $query, [
			'subject_tax',
			'status_tax',
			'priority_tax',
		] );
	}

	public function meta_box_cb_status_tax( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function meta_box_cb_priority_tax( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function do_metabox_excerpt( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		if ( $this->role_can( 'excerpt' ) )
			MetaBox::fieldEditorBox(
				$post->post_excerpt,
				'excerpt',
				$this->get_string( 'excerpt_metabox', 'inquiry_cpt', 'misc' )
			);

		else
			echo HTML::wrap( Text::autoP( $post->post_excerpt ), '-excerpt -readonly' );
	}
}
