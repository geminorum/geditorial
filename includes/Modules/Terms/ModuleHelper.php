<?php namespace geminorum\gEditorial\Modules\Terms;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'terms';

	public static function getFieldDefaults( $field, $module = NULL )
	{
		return gEditorial\MetaBox::getFieldDefaults( $field, $module );
	}

	public static function htmlFieldAuthor( $field, $meta = FALSE, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return '';

		$html = gEditorial\Listtable::restrictByAuthor(
			empty( $meta ) ? 0 : (int) $meta,
			Core\Text::dashed( 'term', $field['name'] ),
			[
				'echo'            => FALSE,
				'show_option_all' => gEditorial\Settings::showOptionNone(),
			]
		);

		if ( ! $html )
			return $html;

		// fallback to search-select if `isLargeCount()`
		$field['role'] = 'author';

		return self::htmlFieldUser( $field, $meta, $module );
	}

	public static function htmlFieldUser( $field, $meta = FALSE, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return '';

		$html   = '';
		$module = $module ?? static::MODULE;
		$args   = self::atts( self::getFieldDefaults( $field['name'], $module ), $field );

		// NOTE: no need for `Select2` but passing in case JavaScript-disabled.
		$html.= Core\HTML::tag( 'option', [
			'selected' => empty( $meta ),
			'value'    => '0',
		], gEditorial\Settings::showOptionNone() );

		if ( $meta )
			$html.= Core\HTML::tag( 'option', [
				'selected' => TRUE,
				'value'    => $meta,
			], WordPress\User::getTitleRow( (int) $meta,
				sprintf(
					/* translators: `%s`: user id number */
					_x( 'Unknown User #%s', 'Helper', 'geditorial-terms' ),
					$meta
				) )
			);

		$atts = [
			'id'   => self::classs( $args['name'], 'id' ),
			'name' => Core\Text::dashed( 'term', $args['name'] ),

			'class' => [
				Core\Text::dashed( static::BASE, 'searchselect', 'select2' ),
				Core\Text::dashed( static::BASE, $module, 'field', $args['name'] ),
				Core\Text::dashed( static::BASE, $module, 'type', $args['type'] ),
			],

			'data' => [
				'query-target'  => 'user',
				'query-exclude' => FALSE, // NOTE: `exclude` in post-type-fields are only for posts
				'query-role'    => $args['role'] ? implode( ',', (array) $args['role'] ) : FALSE,
				'query-minimum' => 3,

				'searchselect-placeholder' => _x( 'Select a User', 'Helper', 'geditorial-terms' ),
			],
		];

		Services\SearchSelect::enqueueSelect2();

		return Core\HTML::tag( 'select', $atts, $html );
	}
}
