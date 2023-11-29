<?php namespace geminorum\gEditorial\Modules\Inquire;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Inquire extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;

	public static function module()
	{
		return [
			'name'   => 'inquire',
			'title'  => _x( 'Inquire', 'Modules: Inquire', 'geditorial-admin' ),
			'desc'   => _x( 'Questions and Answers', 'Modules: Inquire', 'geditorial-admin' ),
			'icon'   => 'editor-help',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'_editpost' => [
				[
					'field'       => 'excerpt_roles',
					'type'        => 'checkbox-panel',
					'title'       => _x( 'Question Roles', 'Setting Title', 'geditorial-inquire' ),
					'description' => _x( 'Roles that can change the question.', 'Setting Description', 'geditorial-inquire' ),
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
			'inquiry_cpt'  => 'inquiry',
			'subject_tax'  => 'inquiry_subject',
			'status_tax'   => 'inquire_status',
			'priority_tax' => 'inquire_priority',
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
			'labels' => [
				'inquiry_cpt' => [
					'excerpt_label' => _x( 'Question', 'Label: Excerpt Label', 'geditorial-inquire' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['default_terms'] = [
			'status_tax' => [
				'status_drafted'     => _x( 'Drafted', 'Default Term', 'geditorial-inquire' ),
				'status_approved'    => _x( 'Approved', 'Default Term', 'geditorial-inquire' ),
				'status_pending'     => _x( 'Pending', 'Default Term', 'geditorial-inquire' ),
				'status_maybe_later' => _x( 'Maybe Later', 'Default Term', 'geditorial-inquire' ),
				'status_rejected'    => _x( 'Rejected', 'Default Term', 'geditorial-inquire' ),
				'status_assigned'    => _x( 'Assigned', 'Default Term', 'geditorial-inquire' ),
				'status_answering'   => _x( 'Answering', 'Default Term', 'geditorial-inquire' ),
				'status_reviewing'   => _x( 'Reviewing', 'Default Term', 'geditorial-inquire' ),
				'status_archived'    => _x( 'Archived', 'Default Term', 'geditorial-inquire' ),
				'status_ready'       => _x( 'Ready', 'Default Term', 'geditorial-inquire' ),
				'status_final'       => _x( 'Final', 'Default Term', 'geditorial-inquire' ),
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

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'subject_tax', [
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL, // default meta box
		], 'inquiry_cpt' );

		$this->register_taxonomy( 'status_tax', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'inquiry_cpt' );

		$this->register_taxonomy( 'priority_tax', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
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

	public function after_setup_theme()
	{
		$this->filter_module( 'dashboard', 'pages' );
		$this->action_module( 'dashboard', 'content_page_inquire', 1 );
	}

	protected function get_module_templates()
	{
		return [
			'page_cpt' => [
				'main' => _x( 'Editorial: Inquire: Dashboard', 'Template Title', 'geditorial-inquire' ),
			],
		];
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'inquiry_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'enter_title_here', 2 );

				if ( post_type_supports( $screen->post_type, 'excerpt' ) ) {

					remove_meta_box( 'postexcerpt', $screen, 'normal' );
					MetaBox::classEditorBox( $screen, $this->classs( 'question' ) );

					add_meta_box( $this->classs( 'question' ),
						$this->get_posttype_label( 'inquiry_cpt', 'excerpt_label' ),
						[ $this, 'do_metabox_excerpt' ],
						$screen,
						'after_title'
					);
				}

				$this->_hook_post_updated_messages( 'inquiry_cpt' );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_bulk_post_updated_messages( 'inquiry_cpt' );
				$this->corerestrictposts__hook_screen_taxonomies( [
					'subject_tax',
					'status_tax',
					'priority_tax',
				] );
			}
		}
	}

	public function admin_menu()
	{
		$this->_hack_adminmenu_no_create_posts( $this->constant( 'inquiry_cpt' ) );
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

	public function do_metabox_excerpt( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		if ( $this->role_can( 'excerpt' ) )
			MetaBox::fieldEditorBox(
				$post->post_excerpt,
				'excerpt',
				$this->get_posttype_label( 'inquiry_cpt', 'excerpt_label' )
			);

		else
			echo Core\HTML::wrap( Core\Text::autoP( $post->post_excerpt ), '-excerpt -readonly' );
	}

	public function dashboard_pages( $pages )
	{
		return array_merge( $pages, [
			$this->key => _x( 'Inquiries', 'Dashboard Title', 'geditorial-inquire' ),
		] );
	}

	public function dashboard_content_page_inquire( $page )
	{
		// ModuleTemplate::templateMain();
	}
}
