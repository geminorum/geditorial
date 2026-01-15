<?php namespace geminorum\gEditorial\Modules\Byline;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Misc;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{
	const MODULE = 'byline';

	const HINT_EXTENDS = [
		'default',
		'byline',
		'author',
		'translator',
		'subject',
	];

	// EXAMPLE: `Pam Durban, Mary Hood (Foreword)`
	// EXAMPLE: `John Updike (Editor, Contributor), Katrina Kenison (Editor)`
	public static function bylineDefaultWalker( $list, $atts = [] )
	{
		$args = self::atts( static::filters( 'walker_default', [
			'post'    => NULL,
			'default' => '',
			'before'  => '',
			'after'   => '',
			'context' => NULL,
			'columns' => '',

			'featured' => NULL,    // only `featured`
			'link'     => TRUE,
			'notes'    => TRUE,
			'hidden'   => FALSE,

			'_fields'    => [],
			'_relations' => [],
			'_legacy'    => [],
			'_extra'     => [],
			'_strings'   => [
				'between'      => _x( ', ', 'Walker: Default: Between Delimiter', 'geditorial-byline' ),
				'between_last' => _x( ' and ', 'Walker: Default: Between Last Delimiter', 'geditorial-byline' ),
				'pre'          => _x( 'Written by', 'Walker: Default: Pre', 'geditorial-byline' ),
			],
		], $list, $atts['post'] ?? NULL ), $atts );

		if ( ! $list || ! count( $list ) )
			return $args['default'];

		$parts = [];

		foreach ( $list as $item ) {

			if ( empty( $item['term'] ) || self::isError( $item['term'] ) )
				continue;

			if ( ! empty( $item['relation']['hidden'] ) && ! $args['hidden'] )
				continue;

			$part = empty( $item['relation']['overwrite'] )
				? WordPress\Term::title( $item['term'] )
				: $item['relation']['overwrite'];

			$title = empty( $item['relation']['relation'] )
				? ''
				: ( empty( $args['_relations'][$item['relation']['relation']] )
					? $item['relation']['relation']
					: $args['_relations'][$item['relation']['relation']]
				);

			if ( $args['notes'] && ! empty( $item['relation']['notes'] ) )
				$title.= sprintf( ':: %s', $item['relation']['notes'] );

			if ( $args['link'] && ( $link = WordPress\Term::link( $item['term'], FALSE ) ) )
				$part = Core\HTML::tag( 'a', [
					'href'  => $link,
					'title' => $title ?: FALSE,
				], $part );

			else
				$part = Core\HTML::tag( 'span', [
					'title' => $title ?: FALSE,
				], $part );

			if ( ! empty( $item['relation']['filter'] ) )
				$part = Core\Text::has( $item['relation']['filter'], '%s' )
					? Core\Text::trim( sprintf( $item['relation']['filter'], $part ) )
					: Core\Text::trim( sprintf( '%s %s', $item['relation']['filter'], $part ) );

			$parts[] = $part;
		}

		$count = count( $parts );

		if ( ! $count )
			return $args['default'];

		if ( 'rawdata' === $args['context'] )
			return $parts;

		if ( $count > 1 )
			$html = WordPress\Strings::joinWithLast( $parts, $args['_strings']['between'], $args['_strings']['between_last'] );

		else
			$html = $parts[0];

		return $args['before'].Core\Text::trim( $args['_strings']['pre'].' '.$html ).$args['after'];
	}

	public static function bylineTemplateWalker( $list, $atts = [] )
	{
		$args = self::atts( static::filters( 'walker_template', [
			'post'     => NULL,
			'default'  => '',
			'before'   => '',
			'after'    => '',
			'context'  => NULL,
			'template' => NULL,
			'columns'  => '',     // NOTE: only appends a wrap class `columns-%d`

			'featured' => NULL,    // only `featured`
			'link'     => TRUE,
			'notes'    => TRUE,
			'hidden'   => FALSE,

			'_fields'    => [],
			'_relations' => [],
			'_legacy'    => [],
			'_extra'     => [],
			'_strings'   => [
				'hint'       => _x( 'View full profile', 'Walker: Default: Hint', 'geditorial-byline' ),
				'norelation' => _x( '[Unrelated]', 'Walker: Default: No-Relation', 'geditorial-byline' ),
			],
		], $list, $atts['post'] ?? NULL ), $atts );

		if ( ! $list || ! count( $list ) )
			return $args['default'];

		$data = [];

		foreach ( $list as $item ) {

			if ( empty( $item['term'] ) || self::isError( $item['term'] ) )
				continue;

			if ( ! empty( $item['relation']['hidden'] ) && ! $args['hidden'] )
				continue;

			if ( empty( $item['relation']['featured'] ) && $args['featured'] )
				continue;

			$row = [
				'tax'   => WordPress\Term::taxonomy( $item['term'] ),
				'name'  => WordPress\Term::title( $item['term'], '' ),
				'link'  => WordPress\Term::link( $item['term'], '' ),
				'edit'  => WordPress\Term::edit( $item['term'], [], '' ),
				'desc'  => $item['term']->description ? WordPress\Strings::prepDescription( $item['term']->description ) : '',
				'notes' => empty( $item['relation']['notes'] ) ? '' : $item['relation']['notes'],
				'rel'   => empty( $item['relation']['relation'] ) ? '' : $item['relation']['relation'],
				'img'   => ModuleTemplate::getTermImageSrc( 'thumbnail', $item['term'] ),
			];

			$row['label'] = empty( $item['relation']['overwrite'] )
				? $row['name']
				: sprintf( '%s (%s)', $row['name'], $item['relation']['overwrite'] );

			$row['relation'] = ( $row['rel'] && ! empty( $args['_relations'][$row['rel']] ) )
				? $args['_relations'][$row['rel']]
				: '';

			$data[] = $row;
		}

		if ( empty( $data ) )
			return $args['default'];

		if ( 'rawdata' === $args['context'] )
			return $data;

		if ( ! $view = static::factory()->module( static::MODULE )->viewengine__view_by_template( $args['template'] ?? 'default', $args['context'] ?? 'walker' ) )
			return $args['default'];

		if ( ! $html = static::factory()->module( static::MODULE )->viewengine__render( $view, [ 'data' => $data, 'args' => $args ], FALSE ) )
			return $args['default'];

		if ( ! $html )
			return $args['default'];

		return $args['before'].$html.$args['after'];
	}

	public static function bylineIntroWalker( $list, $atts = [] )
	{
		$args = self::atts( static::filters( 'walker_intro', [
			'post'     => NULL,
			'default'  => '',
			'before'   => '',
			'after'    => '',
			'context'  => NULL,
			'callback' => NULL,
			'columns'  => '',     // MAYBE: pass into `Intro`

			'featured'    => TRUE,    // only `featured`
			'link'        => TRUE,
			'description' => TRUE,
			'hidden'      => FALSE,

			'_fields'    => [],
			'_relations' => [],
			'_legacy'    => [],
			'_extra'     => [],   // intro arguments
			'_strings'   => [],
		], $list, $atts['post'] ?? NULL ), $atts );

		if ( ! $list || ! count( $list ) )
			return $args['default'];

		if ( is_null( $args['callback'] ) )
			$args['callback'] = [ __NAMESPACE__.'\\ModuleTemplate', 'renderTermIntro' ];

		$html = '';

		foreach ( $list as $item ) {

			if ( empty( $item['relation']['featured'] ) && $args['featured'] )
				continue;

			$html.= self::buffer( $args['callback'], [
				$item['term'],
				$args['_extra'],
				static::MODULE,
			] );
		}

		if ( ! $html )
			return $args['default'];

		if ( 'rawdata' === $args['context'] )
			return $html;

		return $args['before'].$html.$args['after'];
	}

	public static function generateHints( $post, $extend, $context, $queried, $metakeys )
	{
		$hints = [];

		if ( ! $extend || ! in_array( $extend, static::HINT_EXTENDS, TRUE ) )
			return $hints;

		if ( ! $metas  = WordPress\Post::getMeta( $post ) )
			return $hints;

		$parser     = Services\Individuals::isParserAvailable();
		$delimiters = $parser ? Misc\NamesInPersian::FULLNAME_DELIMITERS   : Service\Individuals::FULLNAME_DELIMITERS;
		$prefixes   = $parser ? Misc\NamesInPersian::getFullnamePrefixes() : [];

		foreach ( $metakeys as $metakey => $label ) {

			if ( empty( $metas[$metakey] ) )
				continue;

			foreach ( Services\Markup::getSeparated( $metas[$metakey], $delimiters ) as $offset => $raw ) {

				if ( $parser ) {

					if ( ! Misc\NamesInPersian::isValidFullname( $raw ) )
						continue;

				} else {

					if ( WordPress\Strings::isEmpty( $raw ) )
						continue;
				}

				if ( ! $fullname = Core\Text::trimQuotes( Core\Text::stripPrefix( $raw, $prefixes ) ) )
					continue;

				$hints[] = [
					'text'     => $fullname,
					'title'    => sprintf( '%s :: %s', $label, $metakey ),
					'class'    => static::classs( $metakey ),
					'source'   => static::MODULE,
					'priority' => 20,
				];
			}
		}

		return $hints;
	}
}
