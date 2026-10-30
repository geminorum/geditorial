<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Listtable extends WordPress\Main
{
	const BASE = 'geditorial';

	public static function factory()
	{
		return gEditorial();
	}

	#[\Deprecated( 'USE `Services\Listtable::checkHidden()`' )]
	public static function checkHidden( mixed $column_id, string $after = '' ): bool
	{
		return Services\Listtable::checkHidden( $column_id, $after );
	}

	#[\Deprecated( 'USE `Services\Listtable::columnCount()`' )]
	public static function columnCount( int|array $count, ?string $title_attr = NULL ): string
	{
		return Services\Listtable::columnCount( $count, $title_attr );
	}

	#[\Deprecated( 'USE `Services\Listtable::columnOrder()`' )]
	public static function columnOrder( int $order, ?string $title_attr = NULL ): string
	{
		return Services\Listtable::columnOrder( $order, $title_attr );
	}

	#[\Deprecated( 'USE `Services\Listtable::columnTerm()`' )]
	public static function columnTerm( int $object_id, string $taxonomy, ?string $title_attr = NULL, bool $single = TRUE ): string
	{
		return Services\Listtable::columnTerm( $object_id, $taxonomy, $title_attr, $single );
	}

	#[\Deprecated( 'USE `Services\Listtable::parseQueryTaxonomy()`' )]
	public static function parseQueryTaxonomy( object &$query, string $taxonomy ): void
	{
		Services\Listtable::parseQueryTaxonomy( $query, $taxonomy );
	}

	#[\Deprecated( 'USE `Services\Listtable::orderClausesByTaxonomy()`' )]
	public static function orderClausesByTaxonomy( array $pieces, object $query, string $taxonomy ): array
	{
		return Services\Listtable::orderClausesByTaxonomy( $pieces, $query, $taxonomy );
	}

	#[\Deprecated( 'USE `Services\Listtable::restrictByTaxonomy()`' )]
	public static function restrictByTaxonomy( string|object $taxonomy, false|string $paired_posttype = FALSE, array $extra = [] ): string
	{
		return Services\Listtable::restrictByTaxonomy( $taxonomy, $paired_posttype, $extra );
	}

	#[\Deprecated( 'USE `Services\Listtable::restrictByPosttype()`' )]
	public static function restrictByPosttype( string|object $taxonomy, string $posttype, ?string $option_all = NULL ): string
	{
		return Services\Listtable::restrictByPosttype( $taxonomy, $posttype, $option_all );
	}

	#[\Deprecated( 'USE `Services\Listtable::restrictByAuthor()`' )]
	public static function restrictByAuthor( int $selected = 0, string $name = 'author', array $extra = [] ): string
	{
		return Services\Listtable::restrictByAuthor( $selected, $name, $extra );
	}

	#[\Deprecated( 'USE `Services\Listtable::restrictByPostMeta()`' )]
	public static function restrictByPostMeta( string $meta_key, ?string $none = NULL, array $extra = [] ): void
	{
		Services\Listtable::restrictByPostMeta( $meta_key, $none, $extra );
	}

	#[\Deprecated( 'USE `Services\Listtable::parseQueryPostMeta()`' )]
	public static function parseQueryPostMeta( object &$query, string $meta_key ): void
	{
		Services\Listtable::parseQueryPostMeta( $query, $meta_key );
	}
}
