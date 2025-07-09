<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

// TODO: restrict taxonomy by taxonomy, @SEE: `restrict_manage_posts` filter

class TaxonomyTaxonomy extends gEditorial\Service
{
	const TARGET_TAXONOMIES_PROP = 'target_taxonomies';

	// OLD: `WordPress\Taxonomy::getTargetTaxonomies()`
	public static function getTargets( $taxonomy, $fallback = FALSE )
	{
		$targets = [];

		if ( $object = WordPress\Taxonomy::object( $taxonomy ) ) {

			if ( ! empty( $object->{self::TARGET_TAXONOMIES_PROP} ) ) {

				foreach ( (array) $object->{self::TARGET_TAXONOMIES_PROP} as $target )

					if ( WordPress\Taxonomy::exists( $target ) )
						$targets[] = $target;
			}
		}

		return $targets ?: $fallback;
	}

	const FIELD_FIELD_TEMPLATE = 'taxtax-%s';
	const FIELD_NAME_TEMPLATE  = '%s-taxtax-%s';
	const FIELD_ID_TEMPLATE    = '%s-taxtax-%s-%s';

	public static function getSettingsFieldArgs( $target, $context = NULL, $term = FALSE )
	{
		if ( ! $object = WordPress\Taxonomy::object( $target ) )
			return FALSE;

		if ( FALSE !== $term && ! ( $term = WordPress\Term::get( $term ) ) )
			return FALSE;

		$data      = [];
		$default   = $selected = $wrap = FALSE;
		$field     = sprintf( static::FIELD_FIELD_TEMPLATE, static::BASE, $object->name );
		$html_name = sprintf( static::FIELD_NAME_TEMPLATE, static::BASE, $object->name );
		$html_id   = sprintf( static::FIELD_ID_TEMPLATE, static::BASE, $object->name, $context );

		switch ( $context ) {

			case 'addnew':
				$wrap = TRUE;
				$default = WordPress\Taxonomy::getDefaultTermID( $object->name );
				break;

			case 'editterm':
				$wrap = 'tr';
				$selected = $term ? wp_get_object_terms( $term->term_id, $object->name, [ 'fields' => 'ids' ] ) : FALSE;
				break;

			case 'quickedit':
				$wrap = 'fieldset';
				$data['quickedit'] = $html_name;
		}

		if ( ! TermHierarchy::isSingleTerm( $object ) )
			return [
				'wrap'         => $wrap,
				'data'         => $data,
				'class'        => 'form-field',
				'cap'          => TRUE, // already checked
				'field'        => $field,
				'label_for'    => $html_id,
				'id_attr'      => $html_id,
				'name_attr'    => $html_name,
				'hidden'       => [ $html_name.'[0]' => '1' ],
				'type'         => 'checkbox-panel',
				'values'       => WordPress\Taxonomy::listTerms( $object->name ),
				'default'      => (array) ( ( $term ? $selected : $default ) ?: [] ),
				'title'        => CustomTaxonomy::getLabel( $object, 'extended_label' ),
				'description'  => CustomTaxonomy::getLabel( $object, 'assign_description' ),
				'string_empty' => CustomTaxonomy::getLabel( $object, 'extended_no_items' ),
			];

		else
			return [
				'wrap'         => $wrap,
				'data'         => $data,
				'class'        => 'form-field',
				'cap'          => TRUE, // already checked
				'field'        => $field,
				'label_for'    => $html_id,
				'id_attr'      => $html_id,
				'name_attr'    => $html_name,
				'type'         => 'select',
				'values'       => WordPress\Taxonomy::listTerms( $object->name ) ?: FALSE,
				'default'      => ( $term ? ( $selected ? $selected[0] : '0' ) : ( $default ?: '0' ) ),
				'title'        => CustomTaxonomy::getLabel( $object, 'extended_label' ),
				'description'  => CustomTaxonomy::getLabel( $object, 'assign_description' ),
				'string_empty' => CustomTaxonomy::getLabel( $object, 'extended_no_items' ),
				'none_title'   => CustomTaxonomy::getLabel( $object, 'show_option_none' ),
				'none_value'   => '0',
			];
	}
}
