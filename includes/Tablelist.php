<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Tablelist extends WordPress\Main
{
	const BASE = 'geditorial';

	public static function factory()
	{
		return gEditorial();
	}

	#[\Deprecated( 'USE `Services\Tablelist::isAction()`' )]
	public static function isAction( string|array $actions, bool $check_cb = FALSE ): bool
	{
		return Services\Tablelist::isAction( $actions, $check_cb );
	}

	#[\Deprecated( 'USE `Services\Tablelist::getTerms()`' )]
	public static function getTerms( array $atts = [], array $extra = [], string|array $taxonomy = '', int|false $perpage = 25 ): array
	{
		return Services\Tablelist::getTerms( $atts, $extra, $taxonomy, $perpage );
	}

	#[\Deprecated( 'USE `Services\Tablelist::getAttachments()`' )]
	public static function getAttachments( array $atts = [], array $extra = [], string|array $posttypes = 'attachment', int|false $perpage = 25 ): array
	{
		return Services\Tablelist::getAttachments( $atts, $extra, $posttypes, $perpage );
	}

	#[\Deprecated( 'USE `Services\Tablelist::getPosts()`' )]
	public static function getPosts( array $atts = [], array $extra = [], string|array $posttypes = 'any', int|false $perpage = 25 ): array
	{
		return Services\Tablelist::getPosts( $atts, $extra, $posttypes, $perpage );
	}

	#[\Deprecated( 'USE `Services\Tablelist::filterPostTypes()`' )]
	public static function filterPostTypes( ?array $list = NULL, string $name = 'type' ): string
	{
		return Services\Tablelist::filterPostTypes( $list, $name );
	}

	#[\Deprecated( 'USE `Services\Tablelist::filterAuthors()`' )]
	public static function filterAuthors( ?array $list = NULL, string $name = 'author' ): string
	{
		return Services\Tablelist::filterAuthors( $list, $name );
	}

	#[\Deprecated( 'USE `Services\Tablelist::filterSearch()`' )]
	public static function filterSearch( ?array $list = NULL, string $name = 's' ): string
	{
		return Services\Tablelist::filterSearch( $list, $name );
	}

	#[\Deprecated( 'USE `Services\Tablelist::getPostRowActions()`' )]
	public static function getPostRowActions( int $post_id, ?array $actions = NULL ): array
	{
		return Services\Tablelist::getPostRowActions( $post_id, $actions );
	}

	#[\Deprecated( 'USE `Services\Tablelist::getTermTitleRow()`' )]
	public static function getTermTitleRow( mixed $term, null|bool|string $link = 'edit' ): string
	{
		return Services\Tablelist::getTermTitleRow( $term, $link );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnPostID()`' )]
	public static function columnPostID( bool $icon = TRUE, ?string $column_title = NULL ): string
	{
		return Services\Tablelist::columnPostID( $icon, $column_title );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnPostDate()`' )]
	public static function columnPostDate( ?string $column_title = NULL ): array
	{
		return Services\Tablelist::columnPostDate( $column_title );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnPostDateModified()`' )]
	public static function columnPostDateModified( ?string $column_title = NULL ): array
	{
		return Services\Tablelist::columnPostDateModified( $column_title );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnPostType()`' )]
	public static function columnPostType( ?string $post_id_prop = NULL, ?string $column_title = NULL ): array
	{
		return Services\Tablelist::columnPostType( $post_id_prop, $column_title );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnPostMime()`' )]
	public static function columnPostMime( ?string $column_title = NULL ): array
	{
		return Services\Tablelist::columnPostMime( $column_title );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnPostTitle()`' )]
	public static function columnPostTitle( ?array $actions = NULL, bool $excerpt = FALSE, $custom = [] ): array
	{
		return Services\Tablelist::columnPostTitle( $actions, $excerpt, $custom );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnPostExcerpt()`' )]
	public static function columnPostExcerpt( ?string $column_title = NULL ): array
	{
		return Services\Tablelist::columnPostExcerpt( $column_title );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnPostTitleSummary()`' )]
	public static function columnPostTitleSummary( ?string $column_title = NULL ): array
	{
		return Services\Tablelist::columnPostTitleSummary( $column_title );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnPostStatusSummary()`' )]
	public static function columnPostStatusSummary( ?string $column_title = NULL ): array
	{
		return Services\Tablelist::columnPostStatusSummary( $column_title );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnPostAuthorSummary()`' )]
	public static function columnPostAuthorSummary( ?string $column_title = NULL ): array
	{
		return Services\Tablelist::columnPostAuthorSummary( $column_title );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnPostTerms()`' )]
	public static function columnPostTerms( ?array $taxonomies = NULL, ?string $column_title = NULL ): array
	{
		return Services\Tablelist::columnPostTerms( $taxonomies, $column_title );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnTermID()`' )]
	public static function columnTermID( ?string $column_title = NULL ): string
	{
		return Services\Tablelist::columnTermID( $column_title );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnTermTaxonomy()`' )]
	public static function columnTermTaxonomy( ?string $taxonomy_prop = NULL, ?string $column_title = NULL ): array
	{
		return Services\Tablelist::columnTermTaxonomy( $taxonomy_prop, $column_title );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnTermTaxonomyCode()`' )]
	public static function columnTermTaxonomyCode( bool $link = TRUE, $column_title = NULL ): array
	{
		return Services\Tablelist::columnTermTaxonomyCode( $link, $column_title );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnTermName()`' )]
	public static function columnTermName( ?array $actions = NULL, bool $description = FALSE, array $custom = [], ?string $title = NULL ): array
	{
		return Services\Tablelist::columnTermName( $actions, $description, $custom, $title );
	}

	#[\Deprecated( 'USE `Services\Tablelist::getTermRowActions()`' )]
	public static function getTermRowActions( mixed $row, ?array $actions = NULL ): array
	{
		return Services\Tablelist::getTermRowActions( $row, $actions );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnTermSlug()`' )]
	public static function columnTermSlug( bool $linked = FALSE, ?string $column_title = NULL, array $extra_arguments = [], ?string $empty_attribute = '' ): array
	{
		return Services\Tablelist::columnTermSlug( $linked, $column_title, $extra_arguments, $empty_attribute );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnTermDesc()`' )]
	public static function columnTermDesc( ?string $title = NULL, array $extra = [] ): array
	{
		return Services\Tablelist::columnTermDesc( $title, $extra );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnTermMetaList()`' )]
	public static function columnTermMetaList( ?string $title = NULL, array $extra = [] ): array
	{
		return Services\Tablelist::columnTermMetaList( $title, $extra );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnTermMetaDateStart()`' )]
	public static function columnTermMetaDateStart( ?string $metakey = NULL, ?string $calendar = NULL ): array
	{
		return Services\Tablelist::columnTermMetaDateStart( $metakey, $calendar );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnTermMetaDateEnd()`' )]
	public static function columnTermMetaDateEnd( ?string $metakey = NULL, ?string $calendar = NULL ): array
	{
		return Services\Tablelist::columnTermMetaDateEnd( $metakey, $calendar );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnGeneralCode()`' )]
	public static function columnGeneralCode( string $data_key, ?string $column_title = NULL, array $extra_arguments = [], ?string $empty_attribute = '' ): array
	{
		return Services\Tablelist::columnGeneralCode( $data_key, $column_title, $extra_arguments, $empty_attribute );
	}

	#[\Deprecated( 'USE `Services\Tablelist::columnGeneralDirection()`' )]
	public static function columnGeneralDirection( ?string $column_title = NULL, array $extra_arguments = [] ): array
	{
		return Services\Tablelist::columnGeneralDirection( $column_title, $extra_arguments );
	}
}
