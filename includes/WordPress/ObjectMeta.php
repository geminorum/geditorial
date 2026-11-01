<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class ObjectMeta extends Core\Base
{
	const META_TYPE       = '';
	const META_DB_TABLE   = '';
	const OBJECT_ID_PROP  = '';
	const TABLE_ID_COLUMN = '';

	protected static function _get( mixed $object ): mixed
	{
		return FALSE;
	}

	protected static function _dbtable(): string
	{
		return $GLOBALS['wpdb']->{static::META_DB_TABLE} ?? '';
	}

	protected static function _initRegistryGlobal(): int
	{
		global $NucleusObjectMetaRegistry;

		if ( empty( $NucleusObjectMetaRegistry ) )
			$NucleusObjectMetaRegistry = [];

		if ( empty( $NucleusObjectMetaRegistry[static::META_TYPE] ) )
			$NucleusObjectMetaRegistry[static::META_TYPE] = [];

		return count( $NucleusObjectMetaRegistry[static::META_TYPE] );
	}

	public static function get( string $key = '', mixed $object = NULL, mixed $default = NULL ): mixed
	{
		if ( ! $object = self::_get( $object ) )
			return FALSE;

		if ( FALSE === ( $value = get_metadata( static::META_TYPE, $object->{static::OBJECT_ID_PROP}, $key, TRUE ) ) )
			return $default;

		return $value;
	}

	public static function delete( string $key = '', mixed $object = NULL, mixed $default = '' ): bool
	{
		if ( ! $key )
			return FALSE;

		if ( ! $object = self::_get( $object ) )
			return FALSE;

		return (bool) delete_metadata( static::META_TYPE, $object->{static::OBJECT_ID_PROP}, $key, $default, TRUE );
	}

	public static function deleteAll( string $key, mixed $value ): bool
	{
		if ( ! $key )
			return FALSE;

		return (bool) delete_metadata( static::META_TYPE, NULL, $key, $value, TRUE );
	}

	public static function store( string $key, mixed $value, mixed $object = NULL, mixed $default = FALSE ): bool
	{
		if ( ! $key )
			return FALSE;

		if ( ! $object = self::_get( $object ) )
			return FALSE;

		if ( $value === $default )
			return (bool) delete_metadata( static::META_TYPE, $object->{static::OBJECT_ID_PROP}, $key );

		return (bool) update_metadata( static::META_TYPE, $object->{static::OBJECT_ID_PROP}, $key, $value );
	}

	// @REF: https://github.com/scribu/wp-custom-field-taxonomies
	// FIXME: must limit to the selected post-types
	// @OLD: `WordPress\Database::getPostMetaKeys()`
	public static function listAvailable( bool $include_private = FALSE ): array
	{
		global $wpdb;

		if ( ! $table = self::_dbtable() )
			return [];

		if ( $include_private )
			return $wpdb->get_col( "
				SELECT meta_key
				FROM {$table}
				GROUP BY meta_key
				ORDER BY meta_key ASC
			" );

		else
			return $wpdb->get_col( "
				SELECT meta_key
				FROM {$table}
				GROUP BY meta_key
				HAVING meta_key NOT LIKE '\_%'
				ORDER BY meta_key ASC
			" );
	}

	// @REF: https://github.com/scribu/wp-custom-field-taxonomies
	// FIXME: must limit to the selected post-types
	// @OLD: `WordPress\Database::getPostMetaRows()`
	// NOTE: makes multiple values flat by comma
	public static function listByKey( string $meta_key, ?int $limit = NULL ): array
	{
		global $wpdb;

		if ( ! $table = self::_dbtable() )
			return [];

		if ( absint( $limit ?: 0 ) )
			$query = $wpdb->prepare( "
				SELECT {static::TABLE_ID_COLUMN}, GROUP_CONCAT( meta_value ) as meta
				FROM {$table}
				WHERE meta_key = %s
				GROUP BY {static::TABLE_ID_COLUMN}
				LIMIT %d
			", $meta_key, (int) $limit );

		else
			$query = $wpdb->prepare( "
				SELECT {static::TABLE_ID_COLUMN}, GROUP_CONCAT( meta_value ) as meta
				FROM {$table}
				WHERE meta_key = %s
				GROUP BY {static::TABLE_ID_COLUMN}
			", $meta_key );

		return $wpdb->get_results( $query );
	}

	// @ALT: `delete_post_meta_by_key( $meta_key )`
	// @OLD: `WordPress\Database::deletePostMeta()`
	public static function deleteByKey( string $meta_key, ?int $limit = 0 ): int|bool
	{
		global $wpdb;

		if ( ! $table = self::_dbtable() )
			return FALSE;

		if ( absint( $limit ?: 0 ) )
			$query = $wpdb->prepare( "
				DELETE FROM {$table}
				WHERE meta_key = %s
				LIMIT %d
			", $meta_key, $limit );

		else
			$query = $wpdb->prepare( "
				DELETE FROM {$table}
				WHERE meta_key = %s
			", $meta_key );

		return $wpdb->query( $query );
	}

	// @OLD: `WordPress\Database::deleteEmptyMeta()`
	public static function deleteEmpty( string $meta_key ): array
	{
		global $wpdb;

		if ( ! $table = self::_dbtable() )
			return [];

		$query = $wpdb->prepare( "
			DELETE FROM {$table}
			WHERE meta_key = %s
			AND meta_value = ''
		" , $meta_key );

		return $wpdb->get_results( $query, ARRAY_A );
	}

	// @OLD: `WordPress\Database::changePostMetaKey()`
	public static function changeKey( string $from, string $to ): int|false
	{
		global $wpdb;

		if ( ! $from || ! $to || $from === $to )
			return FALSE;

		if ( ! $table = self::_dbtable() )
			return FALSE;

		return $wpdb->update(
			$table,
			[ 'meta_key' => $to   ],
			[ 'meta_key' => $from ],
			[ '%s' ],
			[ '%s' ],
		);
	}

	// @REF https://anchor.host/bulk-renaming-meta-fields/
	public static function updateKey( string $old_key, string $new_key ): array
	{
		global $wpdb;

		if ( ! $table = self::_dbtable() )
			return [];

		$query = $wpdb->prepare( "
			UPDATE {$table}
			SET meta_key = %s
			WHERE meta_key = %s
		", $new_key, $old_key );

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Retrieves the post-id given meta-key and value.
	 * @old `WordPress\PostType::getIDbyMeta()`
	 * @SEE: https://tommcfarlin.com/get-post-id-by-meta-value/
	 * TODO: support regex on meta-keys
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param bool $single
	 * @return false|int|array
	 */
	public static function getID( string $key, mixed $value, bool $single = TRUE ): false|int|array
	{
		global $wpdb, $NucleusObjectMetaRegistry;

		if ( self::empty( $value ) )
			return FALSE;

		if ( ! $key = Core\Text::force( $key ) )
			return FALSE;

		if ( ! $table = self::_dbtable() )
			return FALSE;

		self::_initRegistryGlobal();

		$group = $single ? 'single' : 'all';

		if ( isset( $NucleusObjectMetaRegistry[static::META_TYPE][$key][$group][$value] ) )
			return $NucleusObjectMetaRegistry[static::META_TYPE][$key][$group][$value];

		$query = $wpdb->prepare( "
			SELECT {static::TABLE_ID_COLUMN}
			FROM {$table}
			WHERE meta_key = %s
			AND meta_value = %s
		", $key, $value );

		$results = $single
			? $wpdb->get_var( $query )
			: $wpdb->get_col( $query );

		return $NucleusObjectMetaRegistry[static::META_TYPE][$key][$group][$value] = $results;
	}

	/**
	 * Retrieves a list of post-ids given meta-key and values.
	 * @OLD: `WordPress\PostType::getIDListbyMeta()`
	 *
	 * @param string $meta_key
	 * @param array $values
	 * @return false|array
	 */
	public static function getIDList( string $meta_key, array $values ): false|array
	{
		global $wpdb, $NucleusObjectMetaRegistry;

		if ( ! $meta_key = Core\Text::force( $meta_key ) )
			return FALSE;

		$filtered = array_filter( (array) $values );

		if ( empty( $filtered ) )
			return FALSE;

		if ( ! $table = self::_dbtable() )
			return FALSE;

		$query = $wpdb->prepare( "
			SELECT {static::TABLE_ID_COLUMN}, meta_value
			FROM {$table}
			WHERE meta_key = %s
			AND meta_value IN ( '".implode( "', '", esc_sql( $filtered ) )."' )
		", $meta_key );

		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( empty( $results ) )
			return [];

		$list = Core\Arraay::pluck( $results, static::TABLE_ID_COLUMN, 'meta_value' );

		self::_initRegistryGlobal();

		// update cache
		foreach ( $filtered as $value )
			$NucleusObjectMetaRegistry[static::META_TYPE][$meta_key]['single'][$value] = array_key_exists( $value, $list ) ? $list[$value] : FALSE;

		return $list;
	}

	// OLD: `WordPress\PostType::invalidateIDbyMeta()`
	public static function invalidateID( string|array $meta_keys, mixed $value = FALSE ): bool
	{
		global $NucleusObjectMetaRegistry;

		// already empty
		if ( ! self::_initRegistryGlobal() )
			return TRUE;

		if ( empty( $meta_keys ) )
			return FALSE;

		if ( FALSE === $value ) {

			// clear all meta by key
			foreach ( (array) $meta_keys as $key ) {
				unset( $NucleusObjectMetaRegistry[static::META_TYPE][$key]['all'] );
				unset( $NucleusObjectMetaRegistry[static::META_TYPE][$key]['single'] );
			}

		} else {

			foreach ( (array) $meta_keys as $key ) {
				unset( $NucleusObjectMetaRegistry[static::META_TYPE][$key]['all'][$value] );
				unset( $NucleusObjectMetaRegistry[static::META_TYPE][$key]['single'][$value] );
			}
		}

		return TRUE;
	}
}
