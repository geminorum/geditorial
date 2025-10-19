<?php namespace geminorum\gEditorial\Modules\Isbn;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class ModuleSettings extends gEditorial\Settings
{

	const MODULE = 'isbn';

	const ACTION_FROM_BOOK_MODULE  = 'do_import_from_book_module';
	const METAKEY_BOOK_MODULE_ISBN = '_meta_publication_isbn';

	public static function renderCard_import_from_book_module()
	{
		if ( ! Services\PostTypeFields::getPostMetaKey( 'isbn', 'meta' ) )
			return FALSE;

		if ( ! $count = WordPress\Database::countPostMetaByKey( static::METAKEY_BOOK_MODULE_ISBN ) )
			return FALSE;

		echo self::toolboxCardOpen( _x( 'From Book Module', 'Card Title', 'geditorial-isbn' ) );

			self::submitButton( static::ACTION_FROM_BOOK_MODULE,
				_x( 'Migrate Data', 'Button', 'geditorial-isbn' ) );

			Core\HTML::desc( sprintf(
				/* translators: `%s`: number of rows found */
				_x( 'Converts meta-keys of found %s rows into current module keys.', 'Message', 'geditorial-isbn' ),
				Core\Number::format( $count )
			), FALSE );

		echo '</div></div>';

		return TRUE;
	}

	public static function handleImport_from_book_module()
	{
		if ( ! Tablelist::isAction( static::ACTION_FROM_BOOK_MODULE ) )
			return FALSE;

		if ( ! $metakey = Services\PostTypeFields::getPostMetaKey( 'isbn', 'meta' ) )
			WordPress\Redirect::doReferer( 'wrong' );

		$count = WordPress\Database::changePostMetaKey(
			static::METAKEY_BOOK_MODULE_ISBN,
			$metakey
		);

		if ( $count )
			WordPress\Redirect::doReferer( [
				'message' => 'changed',
				'count'   => $count,
			] );

		else
			WordPress\Redirect::doReferer( 'nochange' );

		return FALSE;
	}
}
