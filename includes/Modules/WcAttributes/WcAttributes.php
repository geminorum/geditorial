<?php namespace geminorum\gEditorial\Modules\WcAttributes;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class WcAttributes extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'wc_attributes',
			'title'    => _x( 'WC Attributes', 'Modules: WC Attributes', 'geditorial-admin' ),
			'desc'     => _x( 'Product Attribute Enhancements', 'Modules: WC Attributes', 'geditorial-admin' ),
			'icon'     => 'store',
			'access'   => 'beta',
			'disabled' => Services\Modulation::moduleCheckWooCommerce(),
			'keywords' => [
				'woocommerce',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'linkable_attributes',
					'title'       => _x( 'Linkable Attributes', 'Setting Title', 'geditorial-wc-attributes' ),
					'description' => _x( 'Makes the product attributes clickable with pre-defined URL.', 'Setting Description', 'geditorial-wc-attributes' ),
				],
				[
					'field'       => 'localize_join_values',
					'title'       => _x( 'Localize Join', 'Setting Title', 'geditorial-wc-attributes' ),
					'description' => _x( 'Tries to join attribute values with a localized separator.', 'Setting Description', 'geditorial-wc-attributes' ),
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'metakey_attribute_url' => 'url',
		];
	}

	public function init()
	{
		parent::init();

		// $this->filter( 'display_product_attributes', 2, 8, FALSE, 'woocommerce' );

		if ( $this->get_setting( 'linkable_attributes' ) )
			$this->_init_linkable_attributes();

		if ( $this->get_setting( 'localize_join_values' ) )
			$this->filter( 'attribute', 3, 20, 'localize_join_values', 'woocommerce' );
	}

	public function display_product_attributes( $attributes, $product )
	{
		$before = $after = [];
		return $before + $attributes + $after;
	}

	// NOTE: double used!
	public function attribute_localize_join_values( $filtered, $attribute, $values )
	{
		return wpautop( wptexturize( WordPress\Strings::getJoined( $values ) ) );
	}

	/**
	 * Registers term fields for Woo Commerce attributes.
	 * @source https://nicolamustone.blog/2016/03/11/make-product-attributes-linkable/
	 * @source https://gist.github.com/SiR-DanieL/317b77c110016b1a3b3d
	 *
	 * @return void
	 */
	private function _init_linkable_attributes()
	{
		foreach ( wc_get_attribute_taxonomies() as $attribute ) {

			$name = wc_attribute_taxonomy_name( $attribute->attribute_name );

			add_action( "{$name}_add_form_fields", [ $this, 'add_attribute_url_meta_field' ] );
			add_action( "{$name}_edit_form_fields", [ $this, 'edit_attribute_url_meta_field' ], 10 );
			add_action( "edited_{$name}", [ $this, 'save_attribute_url' ] );
			add_action( "create_{$name}", [ $this, 'save_attribute_url' ] );
		}

		$this->filter( 'attribute', 3, 99, 'linkable_attributes', 'woocommerce' );
	}

	/**
	 * Adds URL field to the new attribute term form.
	 *
	 * @return void
	 */
	public function add_attribute_url_meta_field()
	{
		$field = 'url';

		echo '<div class="form-field term-'.$field.'-wrap">';
		echo '<label for="term-'.$field.'">';

			echo Core\HTML::escape( _x( 'URL', 'Field Label', 'geditorial-wc-attributes' ) );

		echo '</label>';

			echo Core\HTML::tag( 'input', [
				'id'    => $this->classs( $field, 'id' ),
				'name'  => 'attribute-'.$field,
				'type'  => 'url',
				// 'value' => empty( $meta ) ? '' : $meta,
				'class' => [ 'code' ],
				// 'data'  => [ 'type' => 'url' ],
			] );

			Core\HTML::desc( _x( 'External link for this attribute.', 'Field Description', 'geditorial-wc-attributes' ) );

			$this->nonce_field( $field );

		echo '</div>';
	}

	/**
	 * Adds the URL field to the edit attribute term form.
	 *
	 * @param  object $term
	 * @return void
	 */
	public function edit_attribute_url_meta_field( $term )
	{
		$field = 'url';
		$data  = get_term_meta( $term->term_id, $this->constant( 'metakey_attribute_url' ), TRUE );

		echo '<tr class="form-field term-'.$field.'-wrap"><th scope="row" valign="top">';
		echo '<label for="term-'.$field.'">';

			echo Core\HTML::escape( _x( 'URL', 'Field Label', 'geditorial-wc-attributes' ) );

		echo '</label></th><td>';

			echo Core\HTML::tag( 'input', [
				'id'    => $this->classs( $field, 'id' ),
				'name'  => 'attribute-'.$field,
				'type'  => 'url',
				'value' => empty( $data ) ? '' : $data,
				'class' => [ 'code' ],
				// 'data'  => [ 'type' => 'url' ],
			] );

			Core\HTML::desc( _x( 'External link for this attribute.', 'Field Description', 'geditorial-wc-attributes' ) );

			$this->nonce_field( $field );

		echo '</td></tr>';
	}

	/**
	 * Saves the URL field for attribute terms.
	 *
	 * @param  int $term_id
	 * @return void
	 */
	public function save_attribute_url( $term_id )
	{
		$field = 'url';
		$key   = 'attribute-'.$field;

		if ( ! isset( $_POST[$key] ) )
			return;

		$this->nonce_check( $field );

		if ( $data = self::req( $key ) )
			update_term_meta(
				$term_id,
				$this->constant( 'metakey_attribute_url' ),
				esc_url_raw( $data )
			);

		else
			delete_term_meta(
				$term_id,
				$this->constant( 'metakey_attribute_url' )
			);
	}

	/**
	 * Filters to make product attributes link-able.
	 *
	 * @param string $filtered
	 * @param object $attribute
	 * @param array  $values
	 * @return void
	 */
	public function attribute_linkable_attributes( $filtered, $attribute, $values )
	{
		$linked  = [];
		$metakey = $this->constant( 'metakey_attribute_url' );

		if ( $attribute->is_taxonomy() ) {

			foreach ( $attribute->get_terms() as $term ) {

				if ( $data = get_term_meta( $term->term_id, $metakey, TRUE ) )
					$linked[] = Core\HTML::tag( 'a', [
						'href' => $data,
					], WordPress\Term::title( $term ) );

				else
					$linked[] = WordPress\Term::title( $term );
			}

		} else {

			foreach ( $values as $value ) {

				// NOTE: Markdown syntax: `[text](url)`
				if ( preg_match( '/\[(.*?)\]\((.*?)\)/', $value, $matches ) ) {

					if ( WordPress\Strings::isEmpty( $matches[1] ) )
						continue;

					if ( Core\URL::isValid( $matches[2] ) )
						$linked[] = Core\HTML::tag( 'a', [
							'href' => $matches[2],
						], Core\HTML::escape( $matches[1] ) );

					else
						$linked[] = Core\HTML::escape( $matches[1] );

				} else if ( ! WordPress\Strings::isEmpty( $value ) ) {

					$linked[] = Core\HTML::escape( $value );
				}
			}
		}

		return $this->attribute_localize_join_values( $filtered, $attribute, $linked );
	}
}
