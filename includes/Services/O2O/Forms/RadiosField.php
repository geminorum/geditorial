<?php namespace geminorum\gEditorial\Services\O2O\Forms;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

// OLD: `scbRadiosField`
class RadiosField extends SelectField
{
	/**
	 * Generate the corresponding HTML for a field.
	 *
	 * @param array $args
	 * @return string
	 */
	protected function _render_specific( $args )
	{
		if ( [ 'foo' ] === $args['selected'] )
			// radio buttons should always have one option selected
			$args['selected'] = key( $args['choices'] );

		$opts = '';

		foreach ( $args['choices'] as $value => $title ) {

			$value = (string) $value;

			$single_input = FormField::_checkbox( [
				'name'     => $args['name'],
				'type'     => 'radio',
				'value'    => $value,
				'checked'  => $value == $args['selected'],
				'desc'     => $title,
				'desc_pos' => 'after',
			] );

			$opts.= str_replace( API::TOKEN, $single_input, $args['wrap_each'] );
		}

		return FormField::add_desc( $opts, $args['desc'], $args['desc_pos'] );
	}
}
