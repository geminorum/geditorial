<?php namespace geminorum\gEditorial\Modules\Entry;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\WordPress;

class Entry extends gEditorial\Module
{
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;
	use Internals\TemplatePostType;

	protected $priority_template_include = 9;

	public static function module()
	{
		return [
			'name'   => 'entry',
			'title'  => _x( 'Entry', 'Modules: Entry', 'geditorial-admin' ),
			'desc'   => _x( 'Wiki-like Posts Entries', 'Modules: Entry', 'geditorial-admin' ),
			'icon'   => 'media-document',
			'access' => 'stable',
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
			],
			'_frontend' => [
				'adminbar_summary',
				[
					'field'       => 'autolink_terms',
					'title'       => _x( 'Auto-link Sections', 'Settings', 'geditorial-entry' ),
					'description' => _x( 'Tries to linkify the section titles in the entry content.', 'Settings', 'geditorial-entry' ),
				],
				'before_content',
				'after_content',
			],
			'_content' => [
				'archive_override',
				'display_searchform',
				'empty_content',
				'archive_title' => [ NULL, $this->get_posttype_label( 'primary_posttype', 'all_items' ) ],
				'archive_content',
				'archive_template',
			],
			'_supports' => [
				'comment_status',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'primary_posttype', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype' => 'entry',
			'primary_taxonomy' => 'entry_section',
			'main_shortcode'   => 'entry-section',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype' => _n_noop( 'Entry', 'Entries', 'geditorial-entry' ),
				'primary_taxonomy' => _n_noop( 'Section', 'Sections', 'geditorial-entry' ),
			],
			'labels' => [
				'primary_taxonomy' => [
					'featured_image' => _x( 'Cover Image', 'Label: Featured Image', 'geditorial-entry' ),
					'column_title'   => _x( 'Section', 'Label: Column Title', 'geditorial-entry' ),
					'uncategorized'  => _x( 'Unsectioned', 'Label: Uncategorized', 'geditorial-entry' ),
				]
			],
			'defaults' => [
				'primary_taxonomy' => [
					'name'        => _x( '[Unsectioned]', 'Default Term: Name', 'geditorial-entry' ),
					'description' => _x( 'Unsectioned Entries', 'Default Term: Description', 'geditorial-entry' ),
					'slug'        => 'unsectioned',
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		// $strings['misc'] = [];

		return $strings;
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'primary_posttype' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'primary_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
			'show_in_nav_menus'  => TRUE,
			'default_term'       => NULL,
			'meta_box_cb'        => $this->get_setting( 'metabox_advanced' ) ? NULL : '__checklist_terms_callback',
		], 'primary_posttype' );

		$this->register_posttype( 'primary_posttype', [
			'primary_taxonomy' => $this->constant( 'primary_taxonomy' ),
		] );

		$this->register_shortcode( 'main_shortcode' );
	}

	public function template_redirect()
	{
		if ( ! is_singular( $this->constant( 'primary_posttype' ) ) )
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

		if ( $this->get_setting( 'autolink_terms' ) )
			$this->filter( 'the_content', 1, 9 );
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'primary_posttype' ) )
			$this->_edit_screen( $posttype );

		$this->filter_module( 'markdown', 'linking', 8, 8 );
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() || ! is_singular( $this->constant( 'primary_posttype' ) ) )
			return;

		$post_id = get_queried_object_id();

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		if ( ! $terms = WordPress\Taxonomy::getPostTerms( $this->constant( 'primary_taxonomy' ), $post_id ) )
			return;

		$nodes[] = [
			'id'     => $this->classs(),
			'title'  => _x( 'Entry Sections', 'Adminbar', 'geditorial-entry' ),
			'parent' => $parent,
			'href'   => WordPress\PostType::getArchiveLink( $this->constant( 'primary_posttype' ) ),
		];

		foreach ( $terms as $term )
			$nodes[] = [
				'id'     => $this->classs( 'section', $term->term_id ),
				'title'  => sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ),
				'parent' => $this->classs(),
				'href'   => get_term_link( $term ), // FIXME: link to the admin list of other posts in this posttype
			];
	}

	public function register_shortcode_ui()
	{
		shortcode_ui_register_for_shortcode( $this->constant( 'main_shortcode' ), [
			'label'         => Core\HTML::escape( _x( 'Entry Section', 'UI: Label', 'geditorial-entry' ) ),
			'listItemImage' => $this->get_posttype_icon( 'primary_posttype' ),
			'attrs'         => [
				[
					'label'    => Core\HTML::escape( _x( 'Section', 'UI: Label', 'geditorial-entry' ) ),
					'attr'     => 'id',
					'type'     => 'term_select',
					'taxonomy' => $this->constant( 'primary_taxonomy' ),
				],
			],
		] );
	}

	public function current_screen( $screen )
	{
		if ( 'dashboard' == $screen->base ) {

			$this->filter( 'dashboard_recent_drafts_query_args' );

		} else if ( $screen->post_type == $this->constant( 'primary_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter_module( 'markdown', 'linking', 8, 8 );
				$this->filter( 'get_default_comment_status', 3 );
				$this->_hook_post_updated_messages( 'primary_posttype' );

			} else if ( 'edit' == $screen->base ) {


				$this->_edit_screen( $screen->post_type );

				$this->coreadmin__hook_admin_ordering( $screen->post_type );
				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );
				$this->corerestrictposts__hook_screen_taxonomies( 'primary_taxonomy' );
				$this->corerestrictposts__hook_sortby_taxonomies( $screen->post_type, 'primary_taxonomy' );
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
			$query_args['post_type'] = [ 'post', $this->constant( 'primary_posttype' ) ];

		else if ( is_array( $query_args['post_type'] ) )
			$query_args['post_type'][] = $this->constant( 'primary_posttype' );

		return $query_args;
	}

	public function manage_posts_columns( $columns )
	{
		return Core\Arraay::insert( $columns, [
			'taxonomy-'.$this->constant( 'primary_taxonomy' ) => $this->get_column_title_taxonomy( 'primary_taxonomy', $this->constant( 'primary_posttype' ) ),
		], 'cb', 'after' );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'primary_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function the_content( $content )
	{
		$sections = WordPress\Taxonomy::prepTerms( $this->constant( 'primary_taxonomy' ), [], NULL, 'name' );

		return Core\Text::replaceWords( Core\Arraay::pluck( $sections, 'name' ), $content, static function ( $matched ) use ( $sections ) {
			return Core\HTML::tag( 'a', [
				'href'  => $sections[$matched]->link,
				'class' => '-entry-section',
				'data'  => [ 'desc' => trim( strip_tags( $sections[$matched]->description ) ) ],
			], $matched );
		} );
	}

	public function template_include( $template )
	{
		return $this->templateposttype__include( $template, $this->constant( 'primary_posttype' ) );
	}

	public function templateposttype_get_archive_content_default( $posttype )
	{
		$html = $this->get_search_form( 'primary_posttype' );

		if ( gEditorial()->enabled( 'alphabet' ) )
			$html.= gEditorial()->module( 'alphabet' )->shortcode_posts( [
				'post_type' => $posttype, // $this->constant( 'primary_posttype' )
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
		return ShortCode::listPosts( 'assigned',
			$this->constant( 'primary_posttype' ),
			$this->constant( 'primary_taxonomy' ),
			$atts,
			$content,
			$this->constant( 'main_shortcode', $tag ),
			$this->key
		);
	}

	public function markdown_linking( $html, $text, $link, $slug, $post_id, $match, $post, $content )
	{
		if ( $post->post_type != $this->constant( 'primary_posttype' ) )
			return $html;

		if ( $post_id )
			$link = get_permalink( $post_id ); // full permalink

		else
			$link = rawurlencode( $slug ); // we handle 404s

		return '<a href="'.$link.'" data-slug="'.$slug.'" class="-wikilink'.( $post_id ? '' : ' -notfound' ).'">'.$text.'</a>';
	}

	// cleanup query arg added by markdown module
	public function redirect_canonical( $redirect_url, $requested_url )
	{
		return remove_query_arg( 'post_type', $redirect_url );
	}
}
