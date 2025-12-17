<?php namespace geminorum\gEditorial\Modules\Overwrite;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Overwrite extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'   => 'overwrite',
			'title'  => _x( 'Overwrite', 'Modules: Overwrite', 'geditorial-admin' ),
			'desc'   => _x( 'Customized Translation Strings', 'Modules: Overwrite', 'geditorial-admin' ),
			'icon'   => 'editor-strikethrough',
			'i18n'   => 'adminonly',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		$settings = [];

		$settings['posttypes_option'] = 'posttypes_option';

		foreach ( $this->list_posttypes() as $posttype_name => $posttype_label ) {

			$posttype_object = WordPress\PostType::object( $posttype_name );

			$settings['_posttypes'][] = [
				'field' => 'posttype_'.$posttype_name.'_plural',
				'type'  => 'text',
				'title' => sprintf(
					/* translators: `%s`: supported object label */
					_x( 'Plural Name for %s', 'Setting Title', 'geditorial-overwrite' ),
					Core\HTML::tag( 'i', $posttype_label )
				),
				'description' => _x( 'Used as plural name on the posttype labels. Must be in title case.', 'Setting Description', 'geditorial-overwrite' ),
				'placeholder' => $posttype_object->labels->name,
			];

			$settings['_posttypes'][] = [
				'field' => 'posttype_'.$posttype_name.'_singular',
				'type'  => 'text',
				'title' => sprintf(
					/* translators: `%s`: supported object label */
					_x( 'Singular Name for %s', 'Setting Title', 'geditorial-overwrite' ),
					Core\HTML::tag( 'i', $posttype_label )
				),
				'description' => _x( 'Used as singular name on the posttype labels. Must be in title case.', 'Setting Description', 'geditorial-overwrite' ),
				'placeholder' => $posttype_object->labels->singular_name,
			];

			$settings['_posttypes'][] = [
				'field' => 'posttype_'.$posttype_name.'_featured',
				'type'  => 'text',
				'title' => sprintf(
					/* translators: `%s`: supported object label */
					_x( 'Featured Image Title for %s', 'Setting Title', 'geditorial-overwrite' ),
					Core\HTML::tag( 'i', $posttype_label )
				),
				'description' => _x( 'Used as featured image title on the posttype labels. Must be in title case.', 'Setting Description', 'geditorial-overwrite' ),
				'placeholder' => $posttype_object->labels->featured_image,
			];

			$settings['_posttypes'][] = [
				'field'       => 'posttype_'.$posttype_name.'_menuname',
				'type'        => 'text',
				'title'       => sprintf(
					/* translators: `%s`: supported object label */
					_x( 'Menu Name for %s', 'Setting Title', 'geditorial-overwrite' ),
					Core\HTML::tag( 'i', $posttype_label )
				),
				'description' => _x( 'Used as menu name title on the posttype labels. Must be in title case.', 'Setting Description', 'geditorial-overwrite' ),
				'placeholder' => $posttype_object->labels->menu_name,
			];
		}

		$settings['taxonomies_option'] = 'taxonomies_option';

		foreach ( $this->list_taxonomies() as $taxonomy_name => $taxonomy_label ) {

			$taxonomy_object = WordPress\Taxonomy::object( $taxonomy_name );

			$settings['_taxonomies'][] = [
				'field' => 'taxonomy_'.$taxonomy_name.'_plural',
				'type'  => 'text',
				'title' => sprintf(
					/* translators: `%s`: supported object label */
					_x( 'Plural Name for %s', 'Setting Title', 'geditorial-overwrite' ),
					Core\HTML::tag( 'i', $taxonomy_label )
				),
				'description' => _x( 'Used as plural name on the taxonomy labels. Must be in title case.', 'Setting Description', 'geditorial-overwrite' ),
				'placeholder' => $taxonomy_object->labels->name,
			];

			$settings['_taxonomies'][] = [
				'field' => 'taxonomy_'.$taxonomy_name.'_singular',
				'type'  => 'text',
				'title' => sprintf(
					/* translators: `%s`: supported object label */
					_x( 'Singular Name for %s', 'Setting Title', 'geditorial-overwrite' ),
					Core\HTML::tag( 'i', $taxonomy_label )
				),
				'description' => _x( 'Used as singular name on the taxonomy labels. Must be in title case.', 'Setting Description', 'geditorial-overwrite' ),
				'placeholder' => $taxonomy_object->labels->singular_name,
			];

			$settings['_taxonomies'][] = [
				'field' => 'taxonomy_'.$taxonomy_name.'_menuname',
				'type'  => 'text',
				'title' => sprintf(
					/* translators: `%s`: supported object label */
					_x( 'Menu Name for %s', 'Setting Title', 'geditorial-overwrite' ),
					Core\HTML::tag( 'i', $taxonomy_label )
				),
				'description' => _x( 'Used as menu name title on the taxonomy labels. Must be in title case.', 'Setting Description', 'geditorial-overwrite' ),
				'placeholder' => $taxonomy_object->labels->menu_name,
			];
		}

		$strings = [
			[
				'field'       => 'target',
				'type'        => 'text',
				'title'       => _x( 'Target', 'Setting Title', 'geditorial-overwrite' ),
				'description' => _x( 'Replaces with the translation string on the filter. Skips if empty.', 'Setting Description', 'geditorial-overwrite' ),
			],
			[
				'field'       => 'translation',
				'type'        => 'text',
				'title'       => _x( 'Translation', 'Setting Title', 'geditorial-overwrite' ),
				'description' => _x( 'Checks against the translation string on the filter. Leave empty to disable checks.', 'Setting Description', 'geditorial-overwrite' ),
			],
			[
				'field'       => 'text',
				'type'        => 'text',
				'title'       => _x( 'Source', 'Setting Title', 'geditorial-overwrite' ),
				'description' => _x( 'Checks against the original string on the filter. Leave empty to disable checks', 'Setting Description', 'geditorial-overwrite' ),
				'field_class' => [ 'regular-text', 'code-text' ],
			],
			[
				'field'       => 'context',
				'type'        => 'text',
				'title'       => _x( 'Context', 'Setting Title', 'geditorial-overwrite' ),
				'description' => _x( 'Checks against the context of the string on the filter. Leave empty to disable checks', 'Setting Description', 'geditorial-overwrite' ),
				'field_class' => [ 'regular-text', 'code-text' ],
			],
			[
				'field'       => 'domain',
				'type'        => 'text',
				'title'       => _x( 'Domain', 'Setting Title', 'geditorial-overwrite' ),
				'description' => _x( 'Checks against the domain of the string on the filter. Leave empty to disable checks', 'Setting Description', 'geditorial-overwrite' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'default'     => 'default',
			],
		];

		$settings['_backend'] = [
			[
				'field'  => 'backend_strings',
				'type'   => 'object',
				'title'  => _x( 'Back-end Strings', 'Setting Title', 'geditorial-overwrite' ),
				'values' => $strings,
			],
		];

		$settings['_frontend'] = [
			[
				'field'  => 'frontend_strings',
				'type'   => 'object',
				'title'  => _x( 'Front-end Strings', 'Setting Title', 'geditorial-overwrite' ),
				'values' => $strings,
			],
		];

		return $settings;
	}

	protected function taxonomies_excluded( $extra = [] )
	{
		return $this->filters( 'taxonomies_excluded', gEditorial\Settings::taxonomiesExcluded( get_taxonomies( [
			Services\Paired::PAIRED_POSTTYPE_PROP => TRUE,   // NOTE: gEditorial prop
		], 'names', 'or' ) + $extra ) );
	}

	private function _skip_gettext_hook()
	{
		return is_admin()
			? empty( $this->get_setting( 'backend_strings' ) )
			: empty( $this->get_setting( 'frontend_strings' ) );
	}

	public function after_setup_theme()
	{
		$this->_overwrite_posttype_labels();
		$this->_overwrite_taxonomy_labels();

		$this->action( 'paired_registered', 7, 99, FALSE, $this->base );
	}

	public function init()
	{
		parent::init();

		if ( $this->_skip_gettext_hook() )
			return;

		$this->filter( 'gettext', 3, 12, is_admin() ? 'backend' : 'frontend' );
		$this->filter( 'gettext_with_context', 4, 12, is_admin() ? 'backend' : 'frontend' );
	}

	public function gettext_backend( $translation, $text, $domain )
	{
		return $this->_overwrite_no_context( $this->get_setting( 'backend_strings' ), $translation, $text, $domain );
	}

	public function gettext_frontend( $translation, $text, $domain )
	{
		return $this->_overwrite_no_context( $this->get_setting( 'frontend_strings' ), $translation, $text, $domain );
	}

	public function gettext_with_context_backend( $translation, $text, $context, $domain )
	{
		return $this->_overwrite_with_context( $this->get_setting( 'backend_strings' ), $translation, $text, $context, $domain );
	}

	public function gettext_with_context_frontend( $translation, $text, $context, $domain )
	{
		return $this->_overwrite_with_context( $this->get_setting( 'frontend_strings' ), $translation, $text, $context, $domain );
	}

	// FIXME: support escaped entities
	private function _overwrite_no_context( $strings, $translation, $text, $domain )
	{
		foreach ( $strings as $string ) {

			if ( empty( $string['target'] ) )
				continue;

			if ( ! empty( $string['domain'] ) && $domain != $string['domain'] )
				continue;

			if ( ! empty( $string['context'] ) )
				continue;

			if ( ! empty( $string['translation'] ) && $translation == $string['translation'] )
				return $string['target'];

			if ( ! empty( $string['text'] ) && $text == $string['text'] )
				return $string['target'];
		}

		return $translation;
	}

	private function _overwrite_with_context( $strings, $translation, $text, $context, $domain )
	{
		foreach ( $strings as $string ) {

			if ( empty( $string['target'] ) )
				continue;

			if ( ! empty( $string['domain'] ) && $domain != $string['domain'] )
				continue;

			if ( ! empty( $string['context'] ) && $context != $string['context'] )
				continue;

			if ( ! empty( $string['translation'] ) && $translation == $string['translation'] )
				return $string['target'];

			if ( ! empty( $string['text'] ) && $text == $string['text'] )
				return $string['target'];
		}

		return $translation;
	}

	public function current_screen( $screen )
	{
		if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' === $screen->base ) {

				add_filter( 'post_updated_messages', function ( $messages ) use ( $screen ) {

					if ( ! $posttype = WordPress\PostType::object( $screen->post_type ) )
						return $messages;

					return array_merge( $messages, [
						$posttype->name => Services\CustomPostType::generateMessages( [
							'plural'   => $this->get_setting_fallback( 'posttype_'.$posttype->name.'_plural', $posttype->labels->name ),
							'singular' => $this->get_setting_fallback( 'posttype_'.$posttype->name.'_singular', $posttype->labels->singular_name ),
						], $posttype->name ),
					] );
				}, 12 );

			} else if ( 'edit' === $screen->base ) {

				add_filter( 'bulk_post_updated_messages', function ( $messages, $counts ) use ( $screen ) {

					if ( ! $posttype = WordPress\PostType::object( $screen->post_type ) )
						return $messages;

					return array_merge( $messages, [
						$posttype->name => Services\CustomPostType::generateBulkMessages( [
							'plural'   => $this->get_setting_fallback( 'posttype_'.$posttype->name.'_plural', $posttype->labels->name ),
							'singular' => $this->get_setting_fallback( 'posttype_'.$posttype->name.'_singular', $posttype->labels->singular_name ),
						], $counts, $posttype->name ),
					] );
				}, 12, 2 );
			}
		}
	}

	private function _overwrite_posttype_labels()
	{
		$keeps = [
			'extended_label',
			'column_title',
			'metabox_title',
			'author_label',
			'excerpt_label',
		];

		foreach ( $this->posttypes() as $posttype )
			add_filter( "post_type_labels_{$posttype}", function ( $labels ) use ( $posttype, $keeps ) {

				$customs = Core\Arraay::keepByKeys( (array) $labels, $keeps );
				$customs['menu_name'] = $this->get_setting_fallback( 'posttype_'.$posttype.'_menuname', $labels->menu_name );

				return (object) Services\CustomPostType::generateLabels( [
					'plural'   => $this->get_setting_fallback( 'posttype_'.$posttype.'_plural', $labels->name ),
					'singular' => $this->get_setting_fallback( 'posttype_'.$posttype.'_singular', $labels->singular_name ),
				], $this->get_setting_fallback( 'posttype_'.$posttype.'_featured', $labels->featured_image ), $customs, $posttype );

			}, 9 );
	}

	private function _overwrite_taxonomy_labels()
	{
		$keeps = [
			'extended_label',
			'column_title',
			'metabox_title',
			'desc_field_title',
			'uncategorized',
			'no_items_available',
		];

		foreach ( $this->taxonomies() as $taxonomy )
			add_filter( "taxonomy_labels_{$taxonomy}", function ( $labels ) use ( $taxonomy, $keeps ) {

				$customs = Core\Arraay::keepByKeys( (array) $labels, $keeps );
				$customs['menu_name'] = $this->get_setting_fallback( 'taxonomy_'.$taxonomy.'_menuname', $labels->menu_name );

				return (object) Services\CustomTaxonomy::generateLabels( [
					'plural'   => $this->get_setting_fallback( 'taxonomy_'.$taxonomy.'_plural', $labels->name ),
					'singular' => $this->get_setting_fallback( 'taxonomy_'.$taxonomy.'_singular', $labels->singular_name ),
				], $customs, $taxonomy );
			}, 9 );
	}

	public function paired_registered( $posttype, $taxonomy, $subterm, $primary, $hierarchical, $private, $supported )
	{
		global $wp_post_types, $wp_taxonomies;

		if ( ! $this->posttype_supported( $posttype ) )
			return;

		if ( empty( $wp_taxonomies[$taxonomy] )
			|| empty( $wp_taxonomies[$taxonomy]->labels )
			|| empty( $wp_post_types[$posttype]->labels ) )
				return;

		$keeps = [
			'desc_field_title',
			'uncategorized',
			'no_items_available',
		];

		$labels  = $wp_post_types[$posttype]->labels;
		$customs = Core\Arraay::keepByKeys( (array) $wp_taxonomies[$taxonomy]->labels, $keeps );
		$customs['menu_name'] = $this->get_setting_fallback( 'posttype_'.$posttype.'_menuname', $labels->menu_name );

		$wp_taxonomies[$taxonomy]->labels = (object) Services\CustomTaxonomy::generateLabels( [
			'plural'   => $this->get_setting_fallback( 'posttype_'.$posttype.'_plural', $labels->name ),
			'singular' => $this->get_setting_fallback( 'posttype_'.$posttype.'_singular', $labels->singular_name ),
		], $customs, $taxonomy );

		$wp_taxonomies[$taxonomy]->label  = $wp_taxonomies[$taxonomy]->labels->name;
	}
}
