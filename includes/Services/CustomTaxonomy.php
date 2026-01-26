<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class CustomTaxonomy extends gEditorial\Service
{
	/**
	 * Generates labels for given taxonomy.
	 *
	 * @REF: `_nx_noop()`
	 * @REF: `translate_nooped_plural()`
	 * @REF: `WP_Taxonomy::get_default_labels()`
	 * NOTE: OLD: `Helper::generateTaxonomyLabels()`
	 *
	 * `%1$s` => Camel Case / Plural  : `Tags`
	 * `%2$s` => Camel Case / Singular: `Tag`
	 * `%3$s` => Lower Case / Plural  : `tags`
	 * `%4$s` => Lower Case / Singular: `tag`
	 * `%5$s` => ~
	 *
	 * @param mixed $name
	 * @param array $pre
	 * @param string $taxonomy
	 * @return array
	 */
	public static function generateLabels( $name, $pre = [], $taxonomy = NULL )
	{
		$strings = WordPress\Strings::getNameForms( $name );

		$templates = apply_filters( static::BASE.'_taxonomy_labels_templates', [
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'name'                       => _x( '%1$s', 'CustomTaxonomy: Label for `name`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			// 'menu_name'                  => _x( '%1$s', 'CustomTaxonomy: Label for `menu_name`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'singular_name'              => _x( '%2$s', 'CustomTaxonomy: Label for `singular_name`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'search_items'               => _x( 'Search %1$s', 'CustomTaxonomy: Label for `search_items`', 'geditorial' ),
			'popular_items'              => NULL, // _x( 'Popular %1$s', 'CustomTaxonomy: Label for `popular_items`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'all_items'                  => _x( 'All %1$s', 'CustomTaxonomy: Label for `all_items`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'parent_item'                => _x( 'Parent %2$s', 'CustomTaxonomy: Label for `parent_item`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'parent_item_colon'          => _x( 'Parent %2$s:', 'CustomTaxonomy: Label for `parent_item_colon`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'edit_item'                  => _x( 'Edit %2$s', 'CustomTaxonomy: Label for `edit_item`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'view_item'                  => _x( 'View %2$s', 'CustomTaxonomy: Label for `view_item`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'update_item'                => _x( 'Update %2$s', 'CustomTaxonomy: Label for `update_item`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'add_new_item'               => _x( 'Add New %2$s', 'CustomTaxonomy: Label for `add_new_item`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'new_item_name'              => _x( 'New %2$s Name', 'CustomTaxonomy: Label for `new_item_name`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'separate_items_with_commas' => _x( 'Separate %3$s with commas', 'CustomTaxonomy: Label for `separate_items_with_commas`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'add_or_remove_items'        => _x( 'Add or remove %3$s', 'CustomTaxonomy: Label for `add_or_remove_items`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'choose_from_most_used'      => _x( 'Choose from the most used %3$s', 'CustomTaxonomy: Label for `choose_from_most_used`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'not_found'                  => _x( 'No %3$s found.', 'CustomTaxonomy: Label for `not_found`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'no_terms'                   => _x( 'No %3$s', 'CustomTaxonomy: Label for `no_terms`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'filter_by_item'             => _x( 'Filter by %4$s', 'CustomTaxonomy: Label for `filter_by_item`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'items_list_navigation'      => _x( '%1$s list navigation', 'CustomTaxonomy: Label for `items_list_navigation`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'items_list'                 => _x( '%1$s list', 'CustomTaxonomy: Label for `items_list`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'back_to_items'              => _x( '&larr; Back to %1$s', 'CustomTaxonomy: Label for `back_to_items`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'item_link'                  => _x( '%2$s Link', 'CustomTaxonomy: Label for `item_link`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'item_link_description'      => _x( 'A link to a %4$s.', 'CustomTaxonomy: Label for `item_link_description`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			// 'name_field_description'     => _x( 'The name is how it appears on your site.', 'CustomTaxonomy: Label for `name_field_description`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			// 'slug_field_description'     => _x( 'The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.', 'CustomTaxonomy: Label for `slug_field_description`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			// 'parent_field_description'   => _x( 'Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.', 'CustomTaxonomy: Label for `parent_field_description`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			// 'desc_field_description'     => _x( 'The description is not prominent by default; however, some themes may show it.', 'CustomTaxonomy: Label for `desc_field_description`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
			'template_name'              => _x( '%2$s Archives', 'CustomTaxonomy: Label for `template_name`', 'geditorial' ),
		], $taxonomy, $strings, $name );

		foreach ( $templates as $key => $template )
			if ( $template && ! array_key_exists( $key, $pre ) )
				$pre[$key] = vsprintf( $template, $strings );

		if ( ! array_key_exists( 'menu_name', $pre ) )
			$pre['menu_name'] = $strings[0];

		if ( ! array_key_exists( 'most_used', $pre ) )
			$pre['most_used'] = vsprintf(
				/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
				_x( 'Most Used', 'Helper: Tax Generator', 'geditorial' ),
				$strings
			);

		// NOTE: this is the plugin custom
		// WTF: better to be dynamic
		// if ( ! array_key_exists( 'extended_label', $pre ) )
		// 	$pre['extended_label'] = $pre['name'];

		// NOTE: this is the plugin custom
		if ( ! array_key_exists( 'column_title', $pre ) )
			$pre['column_title'] = $strings[0];

		// NOTE: this is the plugin custom
		if ( ! array_key_exists( 'metabox_title', $pre ) )
			$pre['metabox_title'] = $strings[0];

		if ( ! array_key_exists( 'desc_field_title', $pre ) )
			$pre['desc_field_title'] = vsprintf(
				/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
				_x( 'Description', 'CustomTaxonomy: Label for `desc_field_title`', 'geditorial' ),
				$strings
			);

		// NOTE: this is the plugin custom
		if ( ! array_key_exists( 'uncategorized', $pre ) )
			$pre['uncategorized'] = vsprintf(
				/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
				_x( 'Uncategorized', 'CustomTaxonomy: Label for `uncategorized`', 'geditorial' ),
				$strings
			);

		// NOTE: this is the plugin custom
		if ( ! array_key_exists( 'no_items_available', $pre ) )
			$pre['no_items_available'] = vsprintf(
				/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
				_x( 'There are no %3$s available!', 'CustomTaxonomy: Label for `no_items_available`', 'geditorial' ),
				$strings
			);

		return $pre;
	}

	// NOTE: OLD: `Helper::getTaxonomyLabel()`
	public static function getLabel( $term_or_taxonomy, $label, $fallback_key = NULL, $fallback = '' )
	{
		if ( ! $object = WordPress\Taxonomy::object( $term_or_taxonomy ) )
			return $fallback ?? gEditorial\Plugin::na();

		if ( ! empty( $object->labels->{$label} ) )
			return $object->labels->{$label};

		$name = [
			'plural'   => $object->labels->name,
			'singular' => $object->labels->singular_name,
		];

		switch ( $label ) {

			case 'noop':
				return $name;

			case 'extended_label':

				if ( TermHierarchy::isSingleTerm( $object ) )
					return $object->labels->singular_name;

				return $object->labels->name;

			case 'extended_no_items':

				if ( ! empty( $object->labels->no_items_available ) )
					$html = $object->labels->no_items_available;

				else
					$html = vsprintf(
						/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
						_x( 'There are no %3$s available!', 'CustomTaxonomy: Label for `no_items_available`', 'geditorial' ),
						WordPress\Strings::getNameForms( $name )
					);

				if ( ! $edit = WordPress\Taxonomy::edit( $object ) )
					return $html;

				return Core\HTML::link( $html, $edit, TRUE );

			case 'desc':
			case 'description':

				if ( ! empty( $object->description ) )
					return $object->description;

				break; // go to fall-backs

			// case 'assign_description': // TODO: display on meta-box after hook
			// case 'manage_description': // TODO
			// case 'extended_description': // TODO
			// case 'archives_description': // TODO

			case 'show_option_no_items':
				return sprintf( '(%s)', $object->labels->no_terms );

			case 'show_option_select':
				return vsprintf(
					/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
					_x( '&ndash; Select %2$s &ndash;', 'CustomTaxonomy: Label for `show_option_select`', 'geditorial' ),
					WordPress\Strings::getNameForms( $name )
				);

			case 'show_option_all':
				return $object->labels->all_items;

			case 'show_option_none':
				return sprintf( '&ndash; %s &ndash;', gEditorial\Settings::showRadioNone() );

			case 'show_option_parent':
				return sprintf( '&ndash; %s &ndash;', $object->labels->parent_item );

			case 'import_items':
				return vsprintf(
					/* translators: `%1$s`: camel case / plural taxonomy, `%2$s`: camel case / singular taxonomy, `%3$s`: lower case / plural taxonomy, `%4$s`: lower case / singular taxonomy, `%5$s`: `%s` placeholder */
					_x( 'Import %1$s', 'CustomTaxonomy: Label for `import_items`', 'geditorial' ),
					WordPress\Strings::getNameForms( $name )
				);
		}

		if ( $fallback_key && isset( $object->labels->{$fallback_key} ) )
			return $object->labels->{$fallback_key};

		return $fallback;
	}
}
