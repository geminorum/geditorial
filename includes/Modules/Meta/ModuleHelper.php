<?php namespace geminorum\gEditorial\Modules\Meta;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{
	const MODULE = 'meta';

	const HINT_EXTENDS = [
		'default',
		'byline',
		'author',
		'translator',
		'subject',
	];

	public static function generateHints( $post, $extend, $context, $queried )
	{
		$hints = [];

		if ( ! $extend || ! in_array( $extend, static::HINT_EXTENDS, TRUE ) )
			return $hints;

		$fields = Core\Arraay::prepString(
			static::filters( 'hint_fields',
				[
					'publication_byline',
					'featured_people',
					'byline',
				],
				$post->post_type,
				$extend,
				$context,
				$queried
			)
		);

		if ( ! $overview = WordPress\Post::overview( $post, 'hints' ) )
			$overview = WordPress\Post::edit( $post );

		foreach ( $fields as $field_key ) {

			if ( ! $field = Services\PostTypeFields::isAvailable( $field_key, $post->post_type, static::MODULE ) )
				continue;

			$meta = ModuleTemplate::getMetaField( $field, [
				'id'      => $post,
				'context' => $context,   // maybe `FALSE`
			], FALSE, static::MODULE );

			if ( $meta )
				$hints[] = [
					'text'     => $meta,
					'link'     => $overview ?: '',
					'title'    => sprintf( '%s :: %s', $field['title'] ?: $field['name'], $field['description'] ?: '' ),
					'class'    => static::classs( $field_key ),
					'source'   => static::MODULE,
					'priority' => 10,
				];
		}

		return $hints;
	}
}
