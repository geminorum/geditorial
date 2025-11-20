<?php namespace geminorum\gEditorial\Services\O2O\Forms;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

// Dropdown field.
// OLD: `scbSelectField`
class SelectField extends SingleChoiceField
{

	/**
	 * Generate the corresponding HTML for a field.
	 *
	 * @param array $args
	 * @return string
	 */
	protected function _render_specific( $args )
	{
		$args = wp_parse_args( $args, [
			'text'  => FALSE,
			'extra' => [],
		] );

		$options = [];

		if ( FALSE !== $args['text'] )
			$options[] = [
				'value'    => '',
				'selected' => [ 'foo' ] === $args['selected'],
				'title'    => $args['text'],
			];

		foreach ( $args['choices'] as $value => $title ) {
			$value = (string) $value;

			$options[] = [
				'title'    => $title,
				'value'    => $value,
				'selected' => $value == $args['selected'],
			];
		}

		$opts = '';

		foreach ( $options as $option )
			$opts .= Core\HTML::tag( 'option', [
				'value'    => $option['value'],
				'selected' => $option['selected'],
			], $option['title'] );


		$args['extra']['name'] = $args['name'];

		$input = Core\HTML::tag( 'select', $args['extra'], $opts );

		return FormField::add_label( $input, $args['desc'], $args['desc_pos'] );
	}
}
