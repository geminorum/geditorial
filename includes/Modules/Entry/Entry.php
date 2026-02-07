<?php namespace geminorum\gEditorial\Modules\Entry;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Entry extends gEditorial\Module
{
	use Internals\ContentReplace;
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;
	use Internals\TemplatePostType;

	protected $priority_template_include = 9;

	public static function module()
	{
		return [
			'name'     => 'entry',
			'title'    => _x( 'Entry', 'Modules: Entry', 'geditorial-admin' ),
			'desc'     => _x( 'Wiki-like Posts Entries', 'Modules: Entry', 'geditorial-admin' ),
			'icon'     => 'media-document',
			'access'   => 'stable',
			'keywords' => [
				'post',
				'wiki',
				'cptmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_editpost' => [
				'assign_default_term',
				'metabox_advanced',
			],
			'_editlist' => [
				'admin_ordering',
				'show_in_quickedit' => [ $this->get_taxonomy_show_in_quickedit_desc( 'category_taxonomy' ), '1' ],
			],
			'_frontend' => [
				'show_in_navmenus' => [ sprintf(
					/* translators: `%s`: category taxonomy name */
					_x( 'Makes <strong>%s</strong> available for selection in navigation menus.', 'Settings', 'geditorial-entry' ),
					$this->get_taxonomy_label( 'category_taxonomy' )
				), '1' ],
				'autolink_terms' => [ sprintf(
					/* translators: `%s`: category taxonomy name */
					_x( 'Tries to linkify the string of <strong>%s</strong> in the entry content.', 'Settings', 'geditorial-entry' ),
					$this->get_taxonomy_label( 'category_taxonomy' )
				) ],
				'before_content',
				'after_content',
			],
			'_content' => [
				'archive_override',
				'display_searchform',
				'empty_content',
				'archive_title' => [ NULL, $this->get_posttype_label( 'main_posttype', 'all_items' ) ],
				'archive_content',
				'archive_template',
			],
			'_supports' => [
				'comment_status',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'main_posttype', TRUE ),
			],
			'_constants' => [
				'main_posttype_constant'     => [ NULL, 'entry' ],
				'category_taxonomy_constant' => [ NULL, 'entry_section' ],
				'main_shortcode_constant'    => [ NULL, 'entry-section' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_posttype'     => 'entry',
			'category_taxonomy' => 'entry_section',
			'main_shortcode'    => 'entry-section',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_posttype'     => _n_noop( 'Entry', 'Entries', 'geditorial-entry' ),
				'category_taxonomy' => _n_noop( 'Section', 'Sections', 'geditorial-entry' ),
			],
			'labels' => [
				'category_taxonomy' => [
					'featured_image' => _x( 'Cover Image', 'Label: Featured Image', 'geditorial-entry' ),
					'column_title'   => _x( 'Section', 'Label: Column Title', 'geditorial-entry' ),
					'uncategorized'  => _x( 'Unsectioned', 'Label: Uncategorized', 'geditorial-entry' ),
				]
			],
			'defaults' => [
				'category_taxonomy' => [
					'name'        => _x( '[Unsectioned]', 'Default Term: Name', 'geditorial-entry' ),
					'description' => _x( 'Unsectioned Entries', 'Default Term: Description', 'geditorial-entry' ),
					'slug'        => 'unsectioned',
				],
			],
		];

		return $strings;
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'main_posttype' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'category_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit', TRUE ),
			'show_in_nav_menus'  => (bool) $this->get_setting( 'show_in_navmenus', TRUE ),
			'default_term'       => NULL,
			'meta_box_cb'        => $this->get_setting( 'metabox_advanced' ) ? NULL : '__checklist_terms_callback',
		], 'main_posttype', [
			'custom_icon' => 'category',
		] );

		$this->register_posttype( 'main_posttype', [], [
			'primary_taxonomy' => 'category_taxonomy',
		] );

		$this->register_shortcode( 'main_shortcode' );
	}

	public function template_redirect()
	{
		if ( is_robots() || is_favicon() || is_feed() )
			return;

		if ( ! is_singular( $this->constant( 'main_posttype' ) ) )
			return;

		$this->filter( 'redirect_canonical', 2 );

		if ( $before = $this->get_setting( 'before_content' ) )
			add_action( $this->hook_base( 'content', 'before' ),
				function ( $content ) use ( $before ) {

					if ( $this->is_content_insert( FALSE ) )
						echo $this->wrap( WordPress\ShortCode::apply( $before ), '-before' );

				}, 100 );

		if ( $after = $this->get_setting( 'after_content' ) )
			add_action( $this->hook_base( 'content', 'after' ),
				function ( $content ) use ( $after ) {

					if ( $this->is_content_insert( FALSE ) )
						echo $this->wrap( WordPress\ShortCode::apply( $after ), '-after' );

				}, 1 );

		$this->contentreplace__autolink_terms( 'category_taxonomy' );
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'main_posttype' ) )
			$this->_edit_screen( $posttype );

		$this->filter_module( 'markdown', 'linking', 8, 8 );
	}

	public function register_shortcode_ui()
	{
		shortcode_ui_register_for_shortcode( $this->constant( 'main_shortcode' ), [
			'label'         => Core\HTML::escape( _x( 'Entry Section', 'UI: Label', 'geditorial-entry' ) ),
			'listItemImage' => Services\Icons::menu( $this->module->icon ),
			'attrs'         => [
				[
					'label'    => Core\HTML::escape( _x( 'Section', 'UI: Label', 'geditorial-entry' ) ),
					'attr'     => 'id',
					'type'     => 'term_select',
					'taxonomy' => $this->constant( 'category_taxonomy' ),
				],
			],
		] );
	}

	public function current_screen( $screen )
	{
		if ( 'dashboard' == $screen->base ) {

			$this->filter( 'dashboard_recent_drafts_query_args' );

		} else if ( $screen->post_type == $this->constant( 'main_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter_module( 'markdown', 'linking', 8, 8 );

				$this->comments__handle_default_status( $screen->post_type );
				$this->posttypes__media_register_headerbutton( 'main_posttype' );
				$this->_hook_post_updated_messages( 'main_posttype' );

			} else if ( 'edit' == $screen->base ) {

				$this->_edit_screen( $screen->post_type );

				$this->coreadmin__hook_admin_ordering( $screen->post_type );
				$this->_hook_bulk_post_updated_messages( 'main_posttype' );
				$this->corerestrictposts__hook_screen_taxonomies( 'category_taxonomy' );
				$this->corerestrictposts__hook_sortby_taxonomies( $screen->post_type, 'category_taxonomy' );
			}
		}
	}

	private function _edit_screen( $posttype )
	{
		add_filter( 'manage_'.$posttype.'_posts_columns', [ $this, 'manage_posts_columns' ] );
	}

	public function dashboard_recent_drafts_query_args( $query_args )
	{
		if ( 'post' == $query_args['post_type'] )
			$query_args['post_type'] = [ 'post', $this->constant( 'main_posttype' ) ];

		else if ( is_array( $query_args['post_type'] ) )
			$query_args['post_type'][] = $this->constant( 'main_posttype' );

		return $query_args;
	}

	public function manage_posts_columns( $columns )
	{
		return Core\Arraay::insert( $columns, [
			'taxonomy-'.$this->constant( 'category_taxonomy' ) => $this->get_column_title_taxonomy( 'category_taxonomy', $this->constant( 'main_posttype' ) ),
		], 'cb', 'after' );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'main_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function template_include( $template )
	{
		return $this->templateposttype__include( $template, $this->constant( 'main_posttype' ) );
	}

	public function templateposttype_get_archive_content_default( $posttype )
	{
		$html = $this->get_search_form( 'main_posttype' );

		if ( gEditorial()->enabled( 'alphabet' ) )
			$html.= gEditorial()->module( 'alphabet' )->shortcode_posts( [
				'posttype'  => $posttype,
				'list_mode' => 'ul',
			] );

		else
			$html.= $this->main_shortcode( [
				'id'     => 'all',
				'future' => 'off',
				'title'  => FALSE,
				'wrap'   => FALSE,
			] );

		return $html;
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'assigned',
			$this->constant( 'main_posttype' ),
			$this->constant( 'category_taxonomy' ),
			array_merge( [
				'post_id' => NULL,
			], (array) $atts ),
			$content,
			$this->constant( 'main_shortcode', $tag ),
			$this->key
		);
	}

	public function markdown_linking( $html, $text, $link, $slug, $post_id, $match, $post, $content )
	{
		if ( $post->post_type != $this->constant( 'main_posttype' ) )
			return $html;

		if ( $post_id )
			$link = get_permalink( $post_id ); // full permanent-link

		else
			$link = rawurlencode( $slug ); // we handle 404

		return '<a href="'.$link.'" data-slug="'.$slug.'" class="-wikilink'.( $post_id ? '' : ' -notfound' ).'">'.$text.'</a>';
	}

	// Cleans-up query argument added by `Markdown` module
	public function redirect_canonical( $redirect_url, $requested_url )
	{
		return remove_query_arg( 'post_type', $redirect_url );
	}
}
