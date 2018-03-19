<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\User;
// use geminorum\gEditorial\Templates\Inquire as ModuleTemplate;

class Inquire extends gEditorial\Module
{

	// protected $partials = [ 'templates' ];

	public static function module()
	{
		return [
			'name'  => 'inquire',
			'title' => _x( 'Inquire', 'Modules: Inquire', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Questions and Answers', 'Modules: Inquire', GEDITORIAL_TEXTDOMAIN ),
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
					'title'       => _x( 'Question Roles', 'Modules: Inquire: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that can change the question.', 'Modules: Inquire: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
				],
			],
			'_supports' => [
				[
					'field'       => 'make_public',
					'title'       => _x( 'Make Public', 'Modules: Inquire: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays Inquiries on the front-end.', 'Modules: Inquire: Setting Description', GEDITORIAL_TEXTDOMAIN ),
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
			// 'status_tax'          => 'inquire_status',
			// 'status_tax_slug'     => 'inquire-status',
			// 'priority_tax'        => 'inquire_priority',
			// 'priority_tax_slug'   => 'inquire-priority',
		];
	}

	// protected function get_module_icons()
	// {
	// 	return [
	// 		'taxonomies' => [
	// 			'status_tax'   => NULL,
	// 			'priority_tax' => 'clipboard',
	// 		],
	// 	];
	// }

	protected function get_global_strings()
	{
		return [
			'noops' => [
				'inquiry_cpt'  => _nx_noop( 'Inquiry', 'Inquiries', 'Modules: Inquire: Noop', GEDITORIAL_TEXTDOMAIN ),
				// 'status_tax'   => _nx_noop( 'Inquiry Status', 'Inquiry Statuses', 'Modules: Inquire: Noop', GEDITORIAL_TEXTDOMAIN ),
				// 'priority_tax' => _nx_noop( 'Inquiry Priority', 'Idea Priorities', 'Modules: Inquire: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
			'misc' => [
				'inquiry_cpt' => [
					'menu_name'       => _x( 'Inquiries', 'Modules: Inquire: Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
					'excerpt_metabox' => _x( 'Question', 'Modules: Inquire: Labels: Excerpt Box Title', GEDITORIAL_TEXTDOMAIN ),
				],
			],
		];
	}

	public function init()
	{
		parent::init();

		// $posttypes = [ $this->constant( 'inquiry_cpt' ) ];

		// $this->register_taxonomy( 'status_tax', [
		// 	'show_admin_column'  => TRUE,
		// 	'show_in_quick_edit' => TRUE,
		// ], $posttypes );

		// $this->register_taxonomy( 'priority_tax', [
		// 	'show_admin_column'  => TRUE,
		// 	'show_in_quick_edit' => TRUE,
		// ], $posttypes );

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
	// 			'main' => _x( 'Editorial: Inquire: Dashboard', 'Modules: Inquire', GEDITORIAL_TEXTDOMAIN ),
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

	public function add_menu_classes ($menu)
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
		return _x( 'Enter question title here', 'Modules: Inquire', GEDITORIAL_TEXTDOMAIN );
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'inquiry_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'inquiry_cpt', $counts ) );
	}

	public function do_metabox_excerpt( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		if ( $this->role_can( 'excerpt' ) )
			MetaBox::fieldEditorBox(
				$post->post_excerpt,
				'excerpt',
				$this->get_string( 'excerpt_metabox', 'inquiry_cpt', 'misc' )
			);

		else
			echo HTML::wrap( Text::autoP( $post->post_excerpt ), '-excerpt' );
	}
}
