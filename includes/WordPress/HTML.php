<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class HTML extends Core\Base
{

	/**
	 * Strips all HTML tags including script and style.
	 *
	 * @source `Yoast\WP\SEO\Helpers\String_Helper::strip_all_tags()`
	 *
	 * @param string $input
	 * @return string
	 */
	public static function stripAllTags( mixed $input ): string
	{
		return \wp_strip_all_tags( Core\Text::force( $input ) );
	}

	// same as `html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) );`
	public static function entityDecode( mixed $input ): string
	{
		return \WP_HTML_Decoder::decode_attribute( Core\Text::force( $input ) );
	}

	public static function extractRootText( mixed $html ): string
	{
		if ( ! $html = Core\Text::force( $html ) )
			return '';

		$processor = new \WP_HTML_Tag_Processor( $html );
		$parts     = [];
		$depth     = 0;

		while ( $processor->next_token() ) {

			$token_type = $processor->get_token_type();

			if ( '#text' === $token_type ) {

				if ( 0 === $depth )
					$parts[] = $processor->get_modifiable_text();

				continue;
			}

			if ( '#tag' !== $token_type )
				continue;

			if ( $processor->is_tag_closer() ) {

				if ( $depth > 0 )
					--$depth;

				continue;
			}

			$token_name = $processor->get_tag();

			if ( $token_name && ! \WP_HTML_Processor::is_void( $token_name ) )
				++$depth;
		}

		return trim( implode( '', $parts ) );
	}

	public static function stripTags( mixed $html ): string
	{
		if ( ! $html = Core\Text::force( $html ) )
			return '';

		$processor = new \WP_HTML_Tag_Processor( $html );
		$text      = '';

		while ( $processor->next_token() ) {

			if ( '#text' === $processor->get_token_name() )
				$text.= $processor->get_modifiable_text();

			$text = $html;

			while ( preg_match( '/<[^>]*>/', $text ) )
				$text = preg_replace( '/<[^>]*>.*?<\/[^>]*>|<[^>]*\/>|<[^>]*>/s', '', $text );
		}

		return $text;
	}

	public static function setAtts( mixed $input, array $attributes ): mixed
	{
		if ( ! $html = Core\Text::force( $input ) )
			return $input;

		$processor = new \WP_HTML_Tag_Processor( $html );

		if ( $processor->next_tag() ) {

			foreach ( $attributes as $attribute_key => $attribute_value )
				$processor->set_attribute( $attribute_key, $attribute_value );

			$html = $processor->get_updated_html();
		}

		return $html;
	}

	public static function extractData( mixed $input, array $lookup = [], ?array $tag_query = NULL, bool $single = FALSE ): mixed
	{
		if ( ! $html = Core\Text::force( $input ) )
			return $input;

		$data      = [];
		$processor = new \WP_HTML_Tag_Processor( $html );
		$tag_query = $tag_query ?? [
			'tag_name'    => 'DIV',
			'breadcrumbs' => [ 'HTML', 'BODY' ],
		];

		while ( $processor->next_tag( $tag_query ) ) {

			foreach ( $lookup as $target_id => $target_attribute ) {

				if ( $target_id !== $processor->get_attribute( 'id' ) )
					continue;

				if ( ! $attribute = $processor->get_attribute( $target_attribute ) )
					continue;

				if ( $single )
					return $attribute;

				$data[$target_id] = $attribute;
			}
		}

		return $single ? FALSE : $data;
	}
}
