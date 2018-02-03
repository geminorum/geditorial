<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Core\HTML;
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
		return [
			'_supports' => [
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
					'excerpt_box_title' => _x( 'Question', 'Modules: Inquire: Labels: Excerpt Box Title', GEDITORIAL_TEXTDOMAIN ),
					'menu_name'         => _x( 'Inquiries', 'Modules: Inquire: Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
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

		$this->register_post_type( 'inquiry_cpt', [
			// 'publicly_queryable' => FALSE,
		] );
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
					$this->remove_meta_box( $screen->post_type, $screen->post_type, 'excerpt' );
					$this->action( 'edit_form_after_title' );
				}

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );
			}
		}
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

	public function edit_form_after_title( $post )
	{
		echo $this->wrap_open( '-edit-form-after-title' );
			MetaBox::fieldPostExcerpt( $post, $this->get_string( 'excerpt_box_title', 'inquiry_cpt', 'misc' ) );
		echo '</div>';
	}
}
