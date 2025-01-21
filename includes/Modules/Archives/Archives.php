<?php namespace geminorum\gEditorial\Modules\Archives;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

class Archives extends gEditorial\Module
{
	use Internals\TemplatePostType;

	protected $disable_no_customs = TRUE;
	protected $priority_init      = 99;    // after all taxonomies registered

	public static function module()
	{
		return [
			'name'   => 'archives',
			'title'  => _x( 'Archives', 'Modules: Archives', 'geditorial-admin' ),
			'desc'   => _x( 'Content Archives Pages', 'Modules: Archives', 'geditorial-admin' ),
			'icon'   => 'editor-ul',
			'i18n'   => 'adminonly',
			'access' => 'stable',
		];
	}

	protected function get_global_settings()
	{
		$settings  = [];
		$templates = wp_get_theme()->get_page_templates();

		$settings['posttypes_option'] = 'posttypes_option';

		foreach ( $this->list_posttypes() as $posttype_name => $posttype_label ) {

			$settings['_posttypes'][] = [
				'field'       => 'posttype_'.$posttype_name.'_title',
				'type'        => 'text',
				/* translators: %s: supported object label */
				'title'       => sprintf( _x( 'Archives Title for %s', 'Setting Title', 'geditorial-archives' ), '<i>'.$posttype_label.'</i>' ),
				'description' => _x( 'Used as title on the posttype archive pages.', 'Setting Description', 'geditorial-archives' ),
				'placeholder' => $this->_get_posttype_archive_title( $posttype_name, FALSE ),
				'after'       => Settings::fieldAfterIcon( $this->get_posttype_archive_link( $posttype_name ),
					_x( 'View Archives Page', 'Icon Title', 'geditorial-archives' ), 'external' ),
			];

			$settings['_posttypes'][] = [
				'field'       => 'posttype_'.$posttype_name.'_content',
				'type'        => 'textarea-quicktags',
				/* translators: %s: supported object label */
				'title'       => sprintf( _x( 'Archives Content for %s', 'Setting Title', 'geditorial-archives' ), '<i>'.$posttype_label.'</i>' ),
				'description' => _x( 'Used as content on the posttype archive pages.', 'Setting Description', 'geditorial-archives' ),
				'default'     => $this->_get_default_posttype_content( $posttype_name ),
			];

			$settings['_posttypes'][] = [
				'field'       => 'posttype_'.$posttype_name.'_template',
				'type'        => 'select',
				/* translators: %s: supported object label */
				'title'       => sprintf( _x( 'Archives Template for %s', 'Setting Title', 'geditorial-archives' ), '<i>'.$posttype_label.'</i>' ),
				'description' => _x( 'Used as page template on the posttype archive pages.', 'Setting Description', 'geditorial-archives' ),
				'values'      => $templates,
			];
		}

		$settings['taxonomies_option'] = 'taxonomies_option';

		foreach ( $this->list_taxonomies() as $taxonomy_name => $taxonomy_label ) {

			$settings['_taxonomies'][] = [
				'field'       => 'taxonomy_'.$taxonomy_name.'_title',
				'type'        => 'text',
				/* translators: %s: supported object label */
				'title'       => sprintf( _x( 'Archives Title for %s', 'Setting Title', 'geditorial-archives' ), '<i>'.$taxonomy_label.'</i>' ),
				'description' => _x( 'Used as title on the taxonomy archive pages.', 'Setting Description', 'geditorial-archives' ),
				'placeholder' => $this->_get_taxonomy_archive_title( $taxonomy_name, FALSE ),
			];

			$settings['_taxonomies'][] = [
				'field'       => 'taxonomy_'.$taxonomy_name.'_content',
				'type'        => 'textarea-quicktags',
				/* translators: %s: supported object label */
				'title'       => sprintf( _x( 'Archives Content for %s', 'Setting Title', 'geditorial-archives' ), '<i>'.$taxonomy_label.'</i>' ),
				'description' => _x( 'Used as content on the taxonomy archive pages.', 'Setting Description', 'geditorial-archives' ),
				'default'     => $this->_get_default_taxonomy_content( $taxonomy_name ),
			];

			$settings['_taxonomies'][] = [
				'field'       => 'taxonomy_'.$taxonomy_name.'_slug',
				'type'        => 'text',
				/* translators: %s: supported object label */
				'title'       => sprintf( _x( 'Archives Slug for %s', 'Setting Title', 'geditorial-archives' ), '<i>'.$taxonomy_label.'</i>' ),
				'description' => _x( 'Used as slug on the taxonomy archive pages.', 'Setting Description', 'geditorial-archives' ),
				'after'       => Settings::fieldAfterIcon( $this->get_taxonomy_archive_link( $taxonomy_name ),
					_x( 'View Archives Page', 'Icon Title', 'geditorial-archives' ), 'external' ),
				'placeholder' => $this->_taxonomy_archive_slug( $taxonomy_name, FALSE ),
				'field_class' => [ 'regular-text', 'code-text' ],
			];

			$settings['_taxonomies'][] = [
				'field'       => 'taxonomy_'.$taxonomy_name.'_template',
				'type'        => 'select',
				/* translators: %s: supported object label */
				'title'       => sprintf( _x( 'Archives Template for %s', 'Setting Title', 'geditorial-archives' ), '<i>'.$taxonomy_label.'</i>' ),
				'description' => _x( 'Used as page template on the taxonomy archive pages.', 'Setting Description', 'geditorial-archives' ),
				'values'      => $templates,
			];
		}

		$settings['_content']['display_searchform'] = _x( 'Prepends a search form to the posttype archive pages.', 'Setting Description', 'geditorial-archives' );

		return $settings;
	}

	protected function get_global_constants()
	{
		return [
			'taxonomy_query' => 'taxonomy_archives',
		];
	}

	protected function posttypes_excluded( $extra = [] )
	{
		return $this->filters( 'posttypes_excluded', Settings::posttypesExcluded( get_post_types( [
			'public'      => FALSE,
			'has_archive' => FALSE,
		], 'names', 'or' ) + $extra ) );
	}

	protected function taxonomies_excluded( $extra = [] )
	{
		return $this->filters( 'taxonomies_excluded', Settings::taxonomiesExcluded( get_taxonomies( [
			'public'                              => FALSE,
			'has_archive'                         => FALSE,   // NOTE: gEditorial prop
			Services\Paired::PAIRED_POSTTYPE_PROP => TRUE,    // NOTE: gEditorial prop
		], 'names', 'or' ) + $extra ) );
	}

	public function init()
	{
		parent::init();

		$this->_do_add_custom_queries();

		$this->filter_module( 'countables', 'taxonomy_countbox_tokens', 4, 9 );

		$this->filter( 'taxonomy_archive_link', 2, 10, FALSE, $this->base );
		$this->filter( 'taxonomy_archive_link', 2, 10, FALSE, 'gnetwork' );
		$this->filter( 'navigation_taxonomy_archive_link', 2, 9, FALSE, 'gtheme' );
		$this->filter( 'navigation_general_items', 1, 10, FALSE, 'gnetwork' );
	}

	public function current_screen( $screen )
	{
		if ( $this->taxonomy_supported( $screen->taxonomy ) ) {

			$screen->set_help_sidebar( Settings::helpSidebar( [ [
				/* translators: %s: supported object label */
				'title' => sprintf( _x( '%s Archives', 'Help Sidebar', 'geditorial-archives' ),
					WordPress\Taxonomy::object( $screen->taxonomy )->label ),
				'url'   => $this->get_taxonomy_archive_link( $screen->taxonomy ),
			] ] ) );

			if ( 'edit-tags' == $screen->base ) {

				$this->action( 'taxonomy_tab_extra_content', 2, 9, FALSE, 'gnetwork' );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			$screen->set_help_sidebar( Settings::helpSidebar( [ [
				/* translators: %s: supported object label */
				'title' => sprintf( _x( '%s Archives', 'Help Sidebar', 'geditorial-archives' ),
					WordPress\PostType::object( $screen->post_type )->label ),
				'url'   => $this->get_posttype_archive_link( $screen->post_type ),
			] ] ) );
		}
	}

	private function _get_default_posttype_content( $posttype = NULL )
	{
		$default = '';

		if ( shortcode_exists( 'alphabet-posts' ) )
			$default = '[alphabet-posts post_type="%s" /]';

		return $this->filters( 'default_posttype_content', $default, $posttype );
	}

	private function _get_default_taxonomy_content( $taxonomy = NULL )
	{
		$default = '';

		if ( shortcode_exists( 'alphabet-terms' ) )
			$default = '[alphabet-terms taxonomy="%s" /]';

		else if ( shortcode_exists( 'display-terms' ) )
			$default = '[display-terms taxonomy="%s" /]';

		return $this->filters( 'default_taxonomy_content', $default, $taxonomy );
	}

	private function _do_add_custom_queries()
	{
		$query = $this->constant( 'taxonomy_query' );

		$this->filter_append( 'query_vars', $query );

		foreach ( $this->taxonomies() as $taxonomy )
			if ( $slug = $this->_taxonomy_archive_slug( $taxonomy ) )
				// add_rewrite_rule( $slug.'/?$', sprintf( 'index.php?%s=%s', $query, $taxonomy ), 'top' );
				add_rewrite_rule( '^'.$slug.'/?$', sprintf( 'index.php?%s=%s', $query, $taxonomy ), 'top' );
	}

	// not used yet!
	private function _posttype_archive_slug( $posttype )
	{
		if ( ! $object = WordPress\PostType::object( $posttype ) )
			return FALSE;

		if ( ! empty( $object->has_archive ) )
			return $object->has_archive;

		if ( ! empty( $object->rest_base ) )
			return $object->rest_base;

		if ( ! empty( $object->rewrite['slug'] ) )
			return $object->rewrite['slug'];

		return $posttype;
	}

	private function _taxonomy_archive_slug( $taxonomy, $settings = TRUE )
	{
		if ( $settings && ( $custom = $this->get_setting( 'taxonomy_'.$taxonomy.'_slug' ) ) )
			return trim( $custom );

		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return FALSE;

		// NOTE: it's gEditorial Prop
		if ( ! empty( $object->has_archive ) )
			return $object->has_archive;

		if ( ! empty( $object->rest_base ) )
			return $object->rest_base;

		if ( ! empty( $object->rewrite['slug'] ) )
			return $object->rewrite['slug'];

		return $taxonomy;
	}

	public function template_include( $template )
	{
		// no need to check for supported taxonomies, since we using `query_vars` filter
		if ( $taxonomy = get_query_var( $this->constant( 'taxonomy_query' ) ) ) {

			$this->current_queried = $taxonomy;

			WordPress\Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->_get_taxonomy_archive_title( $taxonomy ),
				'post_type'  => 'page',
				'is_page'    => TRUE,
				'is_archive' => TRUE,
			], [ $this, 'template_taxonomy_archives' ] );

			$this->template_include_extra( [ 'taxonomy-archives', 'taxonomy-archives-'.$taxonomy ] );

			$this->filter( 'get_the_archive_title', 1, 12, 'taxonomy' );
			$this->filter( 'document_title_parts', 1, 12, 'taxonomy' );
			$this->filter_false( 'gtheme_navigation_crumb_archive' );

			return WordPress\Theme::getTemplate( $this->get_setting( 'taxonomy_'.$taxonomy.'_template' ) );

		} else if ( is_embed() || is_search() || ! ( $posttype = $GLOBALS['wp_query']->get( 'post_type' ) ) ) {

			return $template;
		}

		if ( $this->posttype_supported( $posttype ) && is_post_type_archive( $posttype ) ) {

			$this->current_queried = $posttype;

			WordPress\Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->_get_posttype_archive_title( $posttype ),
				'post_type'  => $posttype,
				'is_page'    => TRUE,
				'is_archive' => TRUE,
			], [ $this, 'templateposttype_archive_content' ] );

			$this->template_include_extra( 'archive-entry' );

			$this->filter( 'get_the_archive_title', 1, 12, 'posttype' );
			$this->filter( 'document_title_parts', 1, 12, 'posttype' );
			$this->filter_false( 'gtheme_navigation_crumb_archive' );

			return WordPress\Theme::getTemplate( $this->get_setting( 'posttype_'.$posttype.'_template' ) );
		}

		return $template;
	}

	private function template_include_extra( $classes )
	{
		$this->filter_append( 'post_class', $classes );

		$this->filter_empty_string( 'previous_post_link' );
		$this->filter_empty_string( 'next_post_link' );

		$this->enqueue_styles();

		self::define( 'GNETWORK_DISABLE_CONTENT_ACTIONS', TRUE );
		self::define( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS', TRUE );
	}

	public function get_the_archive_title_posttype( $name )
	{
		return $this->_get_posttype_archive_title( $this->current_queried );
	}

	public function document_title_parts_posttype( $title )
	{
		$title['title'] = $this->_get_posttype_archive_title( $this->current_queried );
		return $title;
	}

	public function templateposttype_get_archive_title( $posttype )
	{
		return _get_posttype_archive_title( $posttype );
	}

	private function _get_posttype_archive_title( $posttype, $settings = TRUE )
	{
		$default = Helper::getPostTypeLabel( $posttype, 'all_items' );
		$custom  = $settings ? $this->get_setting( 'posttype_'.$posttype.'_title', $default ) : $default;

		return $this->filters( 'posttype_archive_title', $custom ?: $default, $posttype );
	}

	public function templateposttype_get_archive_content( $posttype )
	{
		$setting = $this->get_setting( 'posttype_'.$this->current_queried.'_content',
			$this->_get_default_posttype_content( $this->current_queried ) );

		$form = $this->get_search_form( [ 'post_type[]' => $this->current_queried ] );
		$html = WordPress\ShortCode::apply( sprintf( $setting, $this->current_queried ) );
		$html = $this->filters( 'posttype_archive_content', $html, $this->current_queried );

		return Core\HTML::wrap( $form.$html, '-posttype-archives-content' );
	}

	public function get_the_archive_title_taxonomy( $title )
	{
		return $this->_get_taxonomy_archive_title( $this->current_queried );
	}

	public function document_title_parts_taxonomy( $title )
	{
		$title['title'] = $this->_get_taxonomy_archive_title( $this->current_queried );
		return $title;
	}

	private function _get_taxonomy_archive_title( $taxonomy, $settings = TRUE )
	{
		$default = Helper::getTaxonomyLabel( $taxonomy, 'all_items' );
		$custom  = $settings ? $this->get_setting( 'taxonomy_'.$taxonomy.'_title', $default ) : $default;

		return $this->filters( 'taxonomy_archive_title', $custom ?: $default, $taxonomy );
	}

	public function template_taxonomy_archives( $content )
	{
		$setting = $this->get_setting( 'taxonomy_'.$this->current_queried.'_content',
			$this->_get_default_taxonomy_content( $this->current_queried ) );

		$html = WordPress\ShortCode::apply( sprintf( $setting, $this->current_queried ) );
		$html = $this->filters( 'taxonomy_archive_content', $html, $this->current_queried );

		return Core\HTML::wrap( $html, '-taxonomy-archives-content' );
	}

	public function get_posttype_archive_link( $posttype )
	{
		if ( ! in_array( $posttype, $this->posttypes() ) )
			return FALSE;

		$link = WordPress\PostType::link( $posttype );
		$slug = WordPress\PostType::object( $posttype )->has_archive;

		return $this->filters( 'posttype_archive_link', $link, $posttype, $slug );
	}

	public function get_taxonomy_archive_link( $taxonomy )
	{
		if ( ! in_array( $taxonomy, $this->taxonomies() ) )
			return FALSE;

		if ( ! $slug = $this->_taxonomy_archive_slug( $taxonomy ) )
			return FALSE;

		$link = sprintf( '%s/%s', get_bloginfo( 'url' ), $slug );

		return $this->filters( 'taxonomy_archive_link', $link, $taxonomy, $slug );
	}

	public function countables_taxonomy_countbox_tokens( $tokens, $taxonomy, $count, $args )
	{
		if ( $link = $this->get_taxonomy_archive_link( $taxonomy ) )
			$tokens['link'] = $link;

		return $tokens;
	}

	public function taxonomy_archive_link( $false, $taxonomy )
	{
		if ( $link = $this->get_taxonomy_archive_link( $taxonomy ) )
			return $link;

		return $false;
	}

	public function navigation_taxonomy_archive_link( $false, $taxonomy )
	{
		if ( $link = $this->get_taxonomy_archive_link( $taxonomy ) )
			return $link;

		return $false;
	}

	public function navigation_general_items( $items )
	{
		foreach ( $this->list_posttypes() as $posttype_name => $posttype_label )
			$items[] = [
				/* translators: %s: supported object label */
				'name' => sprintf( _x( '%s Archives', 'Navigation MetaBox', 'geditorial-archives' ), $posttype_label ),
				// NOTE: must have `custom-` prefix to whitelist in gNetwork Navigation
				'slug' => sprintf( 'custom-%s_archives', $posttype_name ),
				'link' => $this->get_posttype_archive_link( $posttype_name ),
			];

		foreach ( $this->list_taxonomies() as $taxonomy_name => $taxonomy_label )
			$items[] = [
				/* translators: %s: supported object label */
				'name' => sprintf( _x( '%s Archives', 'Navigation MetaBox', 'geditorial-archives' ), $taxonomy_label ),
				// NOTE: must have `custom-` prefix to whitelist in gNetwork Navigation
				'slug' => sprintf( 'custom-%s_archives', $taxonomy_name ),
				'link' => $this->get_taxonomy_archive_link( $taxonomy_name ),
			];

		return $items;
	}

	// TODO: check cap and link button for the module settings page
	public function taxonomy_tab_extra_content( $taxonomy, $object )
	{
		$link =  $this->get_taxonomy_archive_link( $taxonomy );

		echo $this->wrap_open( [ 'card', '-toolbox-card' ] );

			$icon = $link ? Settings::fieldAfterIcon( $link, _x( 'View Archives Page', 'Icon Title', 'geditorial-archives' ), 'external' ) : '';

			/* translators: %s: taxonomy object label */
			Core\HTML::h4( sprintf( _x( 'Custom Archives Page for &ldquo;%s&rdquo;', 'Card Title', 'geditorial-archives' ), $object->label ).$icon, 'title' );

			if ( $link ) {

				echo Core\HTML::tag( 'input', [
					'type'     => 'url',
					'readonly' => TRUE,
					'class'    => [ 'large-text', 'code-text' ],
					'onclick'  => 'this.focus();this.select()',
					'value'    => $link,
				] );

				Core\HTML::desc( _x( 'Link to the custom archives page generated for terms in this taxonomy.', 'Description', 'geditorial-archives' ) );

			} else {

				Core\HTML::desc( _x( 'There are no no custom archives pages available!', 'Message', 'geditorial-archives' ), TRUE, '-empty' );
			}

		echo '</div>';
	}
}
