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

		// if ( ! $overview = WordPress\Post::overview( $post, 'hints' ) )
		// 	$overview = WordPress\Post::edit( $post );

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
					// 'link'     => $overview ?: '',
					'title'    => sprintf( '%s :: %s', $field['title'] ?: $field['name'], $field['description'] ?: '' ),
					'class'    => static::classs( $field_key ),
					'source'   => static::MODULE,
					'priority' => 10,
				];
		}

		return $hints;
	}

	public static function getPostTypeFieldKeyMap()
	{
		return [
			// meta currents
			'over_title'   => [ 'over_title', 'ot' ],
			'sub_title'    => [ 'sub_title', 'st' ],
			'byline'       => [ 'byline', 'author', 'as' ],
			'lead'         => [ 'lead', 'le' ],
			'start'        => [ 'start', 'in_issue_page_start' ], // general
			'order'        => [ 'order', 'in_issue_order', 'in_collection_order', 'in_series_order' ], // general
			'number_line'  => [ 'number_line', 'issue_number_line', 'number' ],
			'total_pages'  => [ 'total_pages', 'issue_total_pages', 'pages' ],
			'source_title' => [ 'source_title', 'reshare_source_title' ],
			'source_url'   => [ 'source_url', 'reshare_source_url', 'es', 'ol' ],

			// meta oldies
			'ot' => [ 'over_title', 'ot' ],
			'st' => [ 'sub_title', 'st' ],
			'le' => [ 'lead', 'le' ],
			'as' => [ 'byline', 'author', 'as' ],
			'es' => [ 'source_url', 'es' ],
			'ol' => [ 'source_url', 'ol' ],

			// Labeled: Currents
			'label_string'   => [ 'label_string', 'label', 'ch', 'column_header' ],
			'label_taxonomy' => [ 'label_taxonomy', 'label_tax', 'ct' ],

			// Labeled: DEPRECATED
			'label'     => [ 'label_string', 'label', 'ch', 'column_header' ],
			'label_tax' => [ 'label_taxonomy', 'label_tax', 'ct' ],
			'ch'        => [ 'label_string', 'label', 'ch', 'column_header' ],
			'ct'        => [ 'label_taxonomy', 'label_tax', 'ct' ],

			// book currents
			'publication_edition'   => [ 'publication_edition', 'edition' ],
			'publication_print'     => [ 'publication_print', 'print' ],
			// 'publication_reference' => [ 'publication_reference', 'reference' ],
			'total_volumes'         => [ 'total_volumes', 'volumes' ],
			'publication_size'      => [ 'publication_size', 'size' ],             // term type

			// book oldies
			'edition'          => [ 'publication_edition', 'edition' ],
			'print'            => [ 'publication_print', 'print' ],
			// 'reference'        => [ 'publication_reference', 'reference' ],
			'volumes'          => [ 'total_volumes', 'volumes' ],
			'size'             => [ 'publication_size', 'size' ],             // term type
			'publication_isbn' => [ 'isbn' ],

			// `ISBN` Module
			'isbn'          => [ 'isbn', 'publication_isbn' ],
			'bibliographic' => [ 'bibliographic', 'publication_bib' ],

			// other oldies
			'issue_number_line'    => [ 'number_line', 'issue_number_line' ],
			'issue_total_pages'    => [ 'total_pages', 'issue_total_pages' ],
			'reshare_source_title' => [ 'source_title', 'reshare_source_title' ],
			'reshare_source_url'   => [ 'source_url', 'reshare_source_url' ],

			// fall-backs
			'over-title' => [ 'over_title', 'ot' ],
			'sub-title'  => [ 'sub_title', 'st' ],
			'pages'      => [ 'total_pages', 'pages' ],
			'number'     => [ 'number_line', 'issue_number_line', 'number' ],
		];
	}
}
