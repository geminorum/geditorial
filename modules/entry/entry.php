<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\Theme;

class Entry extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'entry',
			'title' => _x( 'Entry', 'Modules: Entry', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Wiki-like Posts Entries', 'Modules: Entry', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'media-document',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_editlist' => [
				'admin_ordering',
				'admin_restrict',
			],
			'_frontend' => [
				'adminbar_summary',
				'autolink_terms',
				'before_content',
				'after_content',
			],
			'_content' => [
				'display_searchform',
			],
			'_supports' => [
				'comment_status',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'entry_cpt', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'entry_cpt'         => 'entry',
			'entry_cpt_archive' => 'entries',
			'section_tax'       => 'entry_section',
			'section_tax_slug'  => 'entry-section',
			'section_shortcode' => 'entry-section',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'entry_cpt'   => _nx_noop( 'Entry', 'Entries', 'Modules: Entry: Noop', GEDITORIAL_TEXTDOMAIN ),
				'section_tax' => _nx_noop( 'Section', 'Sections', 'Modules: Entry: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'featured'             => _x( 'Cover Image', 'Modules: Entry: Entry CPT: Featured', GEDITORIAL_TEXTDOMAIN ),
			'meta_box_title'       => _x( 'Entry', 'Modules: Entry: Meta Box Title', GEDITORIAL_TEXTDOMAIN ),
			'section_column_title' => _x( 'Section', 'Modules: Entry: Column Title', GEDITORIAL_TEXTDOMAIN ),
		];

		return $strings;
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'entry_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->post_types_excluded = [ 'attachment', $this->constant( 'entry_cpt' ) ];

		$this->register_taxonomy( 'section_tax', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
			'show_in_nav_menus'  => TRUE,
		], 'entry_cpt' );

		$this->register_post_type( 'entry_cpt' );

		if ( ! is_admin() ) {

			if ( $this->get_setting( 'before_content', FALSE ) )
				add_action( 'gnetwork_themes_content_before', [ $this, 'content_before' ], 100 );

			if ( $this->get_setting( 'after_content', FALSE ) )
				add_action( 'gnetwork_themes_content_after', [ $this, 'content_after' ], 1 );

			if ( $this->get_setting( 'autolink_terms', FALSE ) )
				$this->filter( 'the_content', 1, 9 );

			$this->filter( 'template_include', 1, 9 );
		}

		$this->register_shortcode( 'section_shortcode' );
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, 'entry_cpt' ) )
			$this->_edit_screen( $_REQUEST['post_type'] );
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() || ! is_singular( $this->constant( 'entry_cpt' ) ) )
			return;

		$post_id = get_queried_object_id();

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		$nodes[] = [
			'id'     => $this->classs(),
			'title'  => _x( 'Entry Sections', 'Modules: Entry: Adminbar', GEDITORIAL_TEXTDOMAIN ),
			'parent' => $parent,
			'href'   => get_post_type_archive_link( $this->constant( 'entry_cpt' ) ),
		];

		$terms = Taxonomy::getTerms( $this->constant( 'section_tax' ), $post_id, TRUE );

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
		shortcode_ui_register_for_shortcode( $this->constant( 'section_shortcode' ), [
			'label'         => esc_html_x( 'Entry Section', 'Modules: Entry: UI: Label', GEDITORIAL_TEXTDOMAIN ),
			'listItemImage' => $this->get_posttype_icon( 'entry_cpt' ),
			'attrs'         => [
				[
				'label'    => esc_html_x( 'Section', 'Modules: Entry: UI: Label', GEDITORIAL_TEXTDOMAIN ),
				'attr'     => 'id',
				'type'     => 'term_select',
				'taxonomy' => $this->constant( 'section_tax' ),
				],
			],
		] );
	}

	public function current_screen( $screen )
	{
		if ( 'dashboard' == $screen->base ) {

			$this->filter( 'dashboard_recent_drafts_query_args' );

		} else if ( $screen->post_type == $this->constant( 'entry_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'post_updated_messages' );
				$this->filter( 'get_default_comment_status', 3 );

				$this->filter_module( 'markdown', 'linking', 8, 8 );

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );

				if ( $this->get_setting( 'admin_restrict', FALSE ) ) {
					$this->action( 'restrict_manage_posts', 2, 12 );
					$this->filter( 'parse_query' );
				}

				if ( $this->get_setting( 'admin_ordering', TRUE ) )
					$this->action( 'pre_get_posts' );

				$this->filter( 'posts_clauses', 2 );

				$this->_edit_screen( $screen->post_type );
				add_filter( 'manage_edit-'.$screen->post_type.'_sortable_columns', [ $this, 'sortable_columns' ] );
			}
		}
	}

	private function _edit_screen( $post_type )
	{
		add_filter( 'manage_'.$post_type.'_posts_columns', [ $this, 'manage_posts_columns' ] );
	}

	public function dashboard_recent_drafts_query_args( $query_args )
	{
		if ( 'post' == $query_args['post_type'] )
			$query_args['post_type'] = [ 'post', $this->constant( 'entry_cpt' ) ];

		else if ( is_array( $query_args['post_type'] ) )
			$query_args['post_type'][] = $this->constant( 'entry_cpt' );

		return $query_args;
	}

	public function restrict_manage_posts( $post_type, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'section_tax' );
	}

	public function pre_get_posts( &$wp_query )
	{
		if ( $this->constant( 'entry_cpt' ) == $wp_query->get( 'post_type' ) ) {

			if ( $wp_query->is_admin ) {

				if ( ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', 'menu_order' );

				if ( ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', 'DESC' );
			}
		}
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query, 'section_tax' );
	}

	public function posts_clauses( $pieces, $wp_query )
	{
		return $this->do_posts_clauses_taxes( $pieces, $wp_query, 'section_tax' );
	}

	public function manage_posts_columns( $columns )
	{
		return Arraay::insert( $columns, [
			'taxonomy-'.$this->constant( 'section_tax' ) => $this->get_column_title( 'section', 'entry_cpt' ),
		], 'cb', 'after' );
	}

	public function sortable_columns( $columns )
	{
		$tax = $this->constant( 'section_tax' );
		return array_merge( $columns, [ 'taxonomy-'.$tax => 'taxonomy-'.$tax ] );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'entry_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'entry_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'entry_cpt', $counts ) );
	}

	public function meta_box_cb_section_tax( $post, $box )
	{
		MetaBox::checklistTerms( $post, $box );
	}

	public function content_before( $content, $posttypes = NULL )
	{
		parent::content_before( $content, 'entry_cpt' );
	}

	public function content_after( $content, $posttypes = NULL )
	{
		parent::content_after( $content, 'entry_cpt' );
	}

	// FIXME: pattern must ignore within links
	public function the_content( $content )
	{
		if ( $this->is_content_insert( 'entry_cpt' ) ) {

			if ( ! isset( $this->sections ) )
				$this->sections = Taxonomy::prepTerms( $this->constant( 'section_tax' ) );

			foreach ( $this->sections as $section )
				$content = preg_replace(
					'/(?!<[^<>]*?)(?<![?.\/&])\b('.$section->name.')\b(?!:)(?![^<>]*?>)/imsu',
					'<a href="'.$section->link.'" class="-entry-section">$1</a>',
				$content );
		}

		return $content;
	}

	public function template_include( $template )
	{
		if ( is_embed() || is_search()
			|| $this->constant( 'entry_cpt' ) != $GLOBALS['wp_query']->get( 'post_type' ) )
				return $template;

		$posttype = $this->constant( 'entry_cpt' );

		if ( ! is_404() && ! is_post_type_archive( $posttype ) )
			return $template;

		if ( is_404() ) {

			Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->get_title_from_query(),
				'post_type'  => $posttype,
				'is_single'  => TRUE,
			], [ $this, 'empty_content' ] );

			$this->filter_append( 'post_class', 'empty-entry' );

		} else {

			$object = get_post_type_object( $posttype );

			Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $object->labels->all_items,
				'post_type'  => $posttype,
				'is_single'  => TRUE,
			], [ $this, 'archive_content' ] );

			$this->filter_append( 'post_class', 'archive-entry' );
		}

		$this->enqueue_styles();

		defined( 'GNETWORK_DISABLE_CONTENT_ACTIONS' ) or define( 'GNETWORK_DISABLE_CONTENT_ACTIONS', TRUE );

		// look again for template
		return get_single_template();
	}

	public function get_title_from_query()
	{
		return URL::prepTitleQuery( $GLOBALS['wp_query']->get( 'name' ) );
	}

	// TODO: link to search page with list other entries that linked to this title
	public function empty_content( $content )
	{
		$title = $this->get_title_from_query();

		$html = '<p>'._x( 'There are no entry by this title. Search again or create one.', 'Modules: Entry', GEDITORIAL_TEXTDOMAIN ).'</p>';
		$html.= $this->get_search_form( 'entry_cpt', $title );

		if ( $add_new = $this->get_add_new( $title ) )
			$html.= '<p>'.$add_new.'</p>';

		return HTML::wrap( $html, $this->classs( 'empty-content' ) );
	}

	public function archive_content( $content )
	{
		$html = $this->get_search_form( 'entry_cpt' );
		$html.= $this->section_shortcode( [ 'id' => 'all' ] );

		if ( $add_new = $this->get_add_new() )
			$html.= '<p>'.$add_new.'</p>';

		return HTML::wrap( $html, $this->classs( 'archive-content' ) );
	}

	public function get_add_new( $title = FALSE )
	{
		$posttype = get_post_type_object( $this->constant( 'entry_cpt' ) );

		if ( ! current_user_can( $posttype->cap->create_posts ) )
			return '';

		return '<p>'.HTML::tag( 'a', [
			'href'          => WordPress::getPostNewLink( $posttype->name, [ 'post_title' => $title ] ),
			'class'         => [ 'button', '-add-posttype', '-add-posttype-'.$posttype->name ],
			'target'        => '_blank',
			'data-posttype' => $posttype->name,
		], $posttype->labels->add_new_item ).'</p>';
	}

	public function section_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::getTermPosts(
			$this->constant( 'entry_cpt' ),
			$this->constant( 'section_tax' ),
			$atts,
			$content,
			$this->constant( 'section_shortcode' )
		);
	}

	public function markdown_linking( $html, $text, $link, $slug, $post_id, $match, $post, $content )
	{
		if ( $post->post_type != $this->constant( 'entry_cpt' ) )
			return $html;

		if ( $post_id )
			$link = get_permalink( $post_id );

		else
			$link = $slug; // we handle 404s

		return '<a href="'.$link.'" data-slug="'.$slug.'">'.$text.'</a>';
	}
}
