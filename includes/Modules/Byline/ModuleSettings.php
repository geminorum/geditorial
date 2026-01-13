<?php namespace geminorum\gEditorial\Modules\Byline;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class ModuleSettings extends gEditorial\Settings
{
	const MODULE = 'byline';

	const ACTION_FROM_SIMPLE_META     = 'do_import_from_simple_meta';
	const SELECTOR_FROM_SIMPLE_META   = 'fromsimplemeta';
	const ACTION_FROM_PEOPLE_PLUGIN   = 'do_import_from_people_plugin';
	const METAKEY_FROM_PEOPLE_PLUGIN  = '_gpeople_remote';
	const TAXONOMY_FROM_PEOPLE_PLUGIN = 'people';                        // NOTE: as set on the old `People` plugin

	public static function renderCard_import_from_people_plugin( $posttypes )
	{
		if ( empty( $posttypes ) )
			return FALSE;

		if ( ! $count = WordPress\Database::countPostMetaByKey( static::METAKEY_FROM_PEOPLE_PLUGIN ) )
			return FALSE;

		echo self::toolboxCardOpen( _x( 'From People Plugin', 'Card Title', 'geditorial-byline' ) );

			foreach ( $posttypes as $posttype => $label )
				echo Core\HTML::button( sprintf(
					/* translators: `%s`: post-type label */
					_x( 'On %s', 'Button', 'geditorial-byline' ),
					$label
				), add_query_arg( [
					'action' => static::ACTION_FROM_PEOPLE_PLUGIN,
					'type'   => $posttype,
				] ) );

			Core\HTML::desc( sprintf(
				/* translators: `%s`: number of rows found */
				_x( 'Migrate meta-data of found %s rows into current module system.', 'Message', 'geditorial-byline' ),
				Core\Number::format( $count )
			) );

		echo '</div></div>';

		return TRUE;
	}

	public static function handleImport_from_people_plugin( $posttype, $limit = 25 )
	{
		$query = [
			'meta_query' => [
				[
					'key'     => static::METAKEY_FROM_PEOPLE_PLUGIN,
					'compare' => 'EXISTS',
				],
			],
		];

		list( $posts, $pagination ) = Tablelist::getPosts( $query, [], $posttype, $limit );

		if ( empty( $posts ) )
			return self::processingAllDone();

		echo self::processingListOpen();

		foreach ( $posts as $post )
			self::post_set_byline_from_people_plugin( $post, TRUE );

		echo '</ul></div>';

		return WordPress\Redirect::doJS( add_query_arg( [
			'action' => static::ACTION_FROM_PEOPLE_PLUGIN,
			'type'   => $posttype,
			'paged'  => self::paged() + 1,
		] ) );
	}

	// 'o'        => 0,          // order
	// 'id'       => 0,          // term id
	// 'feat'     => 0,          // featured
	// 'vis'      => 'tagged',   // visibility string
	// 'filter'   => '',         // filter
	// 'override' => '',         // override
	// 'rel'      => 'none',     // rel tax term
	// 'temp'     => '',         // temporary title, in case there's no people term available.
	public static function post_set_byline_from_people_plugin( $post, $verbose = FALSE )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return self::processingListItem( $verbose, gEditorial\Plugin::wrong( FALSE ) );

		if ( ! $legacy = get_post_meta( $post->ID, static::METAKEY_FROM_PEOPLE_PLUGIN, TRUE ) )
			return self::processingListItem( $verbose, gEditorial\Plugin::wrong( FALSE ) );

		$data = [];

		foreach ( $legacy as $row ) {

			if ( ! empty( $row['id'] ) )
				$term = WordPress\Term::get( (int) $row['id'] );

			else if ( ! empty( $row['temp'] ) && ! WordPress\Strings::isEmpty( $row['temp'] ) )
				$term = WordPress\Taxonomy::getTargetTerm( $row['temp'], static::TAXONOMY_FROM_PEOPLE_PLUGIN );

			else
				continue;

			if ( ! $term )
				continue;

			$relation = [ 'id' => $term->term_id ];

			if ( ! empty( $row['o'] ) && intval( $row['o'] ) > 1 )
				$relation['_order'] = $row['o'] - 1;

			if ( ! empty( $row['rel'] ) && 'none' !== $row['rel'] && ! WordPress\Strings::isEmpty( $row['rel'] ) )
				$relation['relation'] = Core\Text::trim( $row['rel'] );

			if ( ! empty( $row['filter'] ) && ! WordPress\Strings::isEmpty( $row['filter'] ) )
				$relation['filter'] = Core\Text::trim( $row['filter'] );

			if ( ! empty( $row['override'] ) && ! WordPress\Strings::isEmpty( $row['override'] ) )
				$relation['overwrite'] = Core\Text::trim( $row['override'] );

			if ( ! empty( $row['feat'] ) && ! WordPress\Strings::isEmpty( $row['feat'] ) )
				$relation['featured'] = TRUE;

			if ( ! empty( $row['vis'] ) && in_array( $row['vis'], [ 'hidden', 'none' ], TRUE ) )
				$relation['hidden'] = TRUE;

			$data[] = $relation;
		}

		if ( ! count( $data ) )
			return self::processingListItem( $verbose,
				/* translators: `%s`: post title */
				_x( 'No byline meta-data available for &ldquo;%s&rdquo;.', 'Notice', 'geditorial-byline' ), [
					WordPress\Post::title( $post ),
				] );

		if ( ! Services\TermRelations::updatePostData( $post, $data ) )
			return self::processingListItem( $verbose,
				/* translators: `%s`: post title */
				_x( 'There is problem updating byline meta-data for &ldquo;%s&rdquo;.', 'Notice', 'geditorial-byline' ), [
					WordPress\Post::title( $post ),
				] );

		if ( ! delete_post_meta( $post->ID, static::METAKEY_FROM_PEOPLE_PLUGIN ) )
			return self::processingListItem( $verbose,
				/* translators: `%s`: post title */
				_x( 'There is problem removing byline meta-data for &ldquo;%s&rdquo;.', 'Notice', 'geditorial-byline' ), [
					WordPress\Post::title( $post ),
				] );

		return self::processingListItem( $verbose,
			/* translators: `%1$s`: byline string, `%2$s`: post title */
			_x( 'Byline %1$s migrated for &ldquo;%2$s&rdquo;.', 'Notice', 'geditorial-byline' ), [
				ModuleTemplate::renderDefault( [ 'echo' => FALSE ], $post ),
				WordPress\Post::title( $post ),
			], TRUE );
	}

	public static function renderCard_import_from_simple_meta( $posttypes, $taxonomies, $metakeys )
	{
		if ( empty( $posttypes ) || empty( $taxonomies ) || empty( $metakeys ) )
			return FALSE;

		if ( ! $count = WordPress\Database::countPostMetaByKey( array_keys( $metakeys ) ) )
			return FALSE;

		// WTF
		$defaults = [
			'metakey'  => 'author',
			'taxonomy' => 'people',
		];

		echo self::toolboxCardOpen( _x( 'From Simple Meta', 'Card Title', 'geditorial-byline' ), FALSE );

			echo Core\HTML::wrap( Core\HTML::dropdown( $metakeys, [
				'name'       => 'metakey',
				'selected'   => self::req( 'metakey', $defaults['metakey'] ) ?: '',
				'none_title' => self::showOptionNone(),
				'class'      => 'do-dynamicsubmit',
				'data'       => [
					'dynamicsubmit-modifier' => static::SELECTOR_FROM_SIMPLE_META,
				],
			] ), 'field-wrap -select' );

			echo Core\HTML::wrap( Core\HTML::dropdown( $taxonomies, [
				'name'       => 'taxonomy',
				'selected'   => self::req( 'taxonomy', $defaults['taxonomy'] ) ?: '',
				'none_title' => self::showOptionNone(),
				'class'      => 'do-dynamicsubmit',
				'data'       => [
					'dynamicsubmit-modifier' => static::SELECTOR_FROM_SIMPLE_META,
				],
			] ), 'field-wrap -select' );

			echo '<div class="-wrap -wrap-button-row">';

			foreach ( $posttypes as $posttype => $label )
				echo Core\HTML::button( sprintf(
					/* translators: `%s`: post-type label */
					_x( 'On %s', 'Button', 'geditorial-byline' ),
					$label
				), add_query_arg( [
					'action'   => static::ACTION_FROM_SIMPLE_META,
					'type'     => $posttype,
					'metakey'  => $defaults['metakey'],
					'taxonomy' => $defaults['taxonomy'],
				] ), FALSE, FALSE, [
					'dynamicsubmit-target' => static::SELECTOR_FROM_SIMPLE_META,
				] );

			echo gEditorial\Ajax::spinner( TRUE, [
				'dynamicsubmit-loading' => static::SELECTOR_FROM_SIMPLE_META,
			] );

			Core\HTML::desc( sprintf(
				/* translators: `%s`: number of rows found */
				_x( 'Migrate meta-data of found %s rows into current module system.', 'Message', 'geditorial-byline' ),
				Core\Number::format( $count )
			) );

		echo '</div></div></div>';

		gEditorial\Scripts::enqueueDynamicSubmit();

		return TRUE;
	}

	public static function handleImport_from_simple_meta( $posttype, $taxonomy, $metakey, $limit = 25 )
	{
		if ( empty( $metakey ) || empty( $taxonomy ) )
			return self::processingAllDone();

		$query = [
			'meta_query' => [ [
				'key'     => $metakey,
				'compare' => 'EXISTS',
			] ],
		];

		list( $posts, $pagination ) = Tablelist::getPosts( $query, [], $posttype, $limit );

		if ( empty( $posts ) )
			return self::processingAllDone();

		if ( Services\Individuals::isParserAvailable() )
			$filters = gEditorial\Misc\NamesInPersian::mapRelationPrefixes();

		echo self::processingListOpen();

		foreach ( $posts as $post )
			self::post_set_byline_from_simple_meta(
				$post,
				$taxonomy,
				$metakey,
				$filters ?? FALSE,
				TRUE
			);

		echo '</ul></div>';

		return WordPress\Redirect::doJS( add_query_arg( [
			'action'   => static::ACTION_FROM_SIMPLE_META,
			'type'     => $posttype,
			'taxonomy' => $taxonomy,
			'metakey'  => $metakey,
			'paged'    => self::paged() + 1,
		] ) );
	}

	public static function post_set_byline_from_simple_meta( $post, $taxonomy, $metakey, $filters, $verbose = FALSE )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return self::processingListItem( $verbose, gEditorial\Plugin::wrong( FALSE ) );

		if ( ! $legacy = get_post_meta( $post->ID, $metakey, TRUE ) )
			return self::processingListItem( $verbose, gEditorial\Plugin::wrong( FALSE ) );

		if ( WordPress\Strings::isEmpty( $legacy ) )
			return self::processingListItem( $verbose, gEditorial\Plugin::invalid( FALSE ) );

		$data     = [];
		$count    = WordPress\Taxonomy::theTermCount( $taxonomy, $post );
		$prefixes = $filters ? gEditorial\Misc\NamesInPersian::getFullnamePrefixes() : [];

		foreach ( Services\Markup::getSeparated( $legacy ) as $offset => $raw ) {

			if ( $filters && ! gEditorial\Misc\NamesInPersian::isValidFullname( $raw ) )
				continue;

			else if ( ! $filters && WordPress\Strings::isEmpty( $raw ) )
				continue;

			$fullname = Core\Text::trimQuotes( Core\Text::stripPrefix( $raw, $prefixes ) );

			if ( ! $term = WordPress\Taxonomy::getTargetTerm( $fullname, $taxonomy ) )
				continue;

			$relation = [
				'id'       => $term->term_id,
				'relation' => $metakey,
			];

			// NOTE: first one is empty
			if ( $count )
				$relation[Services\TermRelations::FIELD_ORDER] = $count;

			// NOTE: only for the first one with offset `0`
			if ( ! $offset && $filters && ! empty( $filters[$metakey] ) )
				$relation['filter'] = $filters[$metakey];

			$data[] = $relation;
			$count++;
		}

		if ( ! count( $data ) )
			return self::processingListItem( $verbose,
				/* translators: `%s`: post title */
				_x( 'No byline meta-data available for &ldquo;%s&rdquo;.', 'Notice', 'geditorial-byline' ), [
					WordPress\Post::title( $post ),
				] );

		if ( ! Services\TermRelations::updatePostData( $post, $data ) )
			return self::processingListItem( $verbose,
				/* translators: `%s`: post title */
				_x( 'There is problem updating byline meta-data for &ldquo;%s&rdquo;.', 'Notice', 'geditorial-byline' ), [
					WordPress\Post::title( $post ),
				] );

		if ( ! delete_post_meta( $post->ID, $metakey ) )
			return self::processingListItem( $verbose,
				/* translators: `%s`: post title */
				_x( 'There is problem removing byline meta-data for &ldquo;%s&rdquo;.', 'Notice', 'geditorial-byline' ), [
					WordPress\Post::title( $post ),
				] );

		return self::processingListItem( $verbose,
			/* translators: `%1$s`: byline string, `%2$s`: post title */
			_x( 'Byline %1$s migrated for &ldquo;%2$s&rdquo;.', 'Notice', 'geditorial-byline' ), [
				ModuleTemplate::renderDefault( [ 'echo' => FALSE ], $post ),
				WordPress\Post::title( $post ),
			], TRUE );
	}
}
