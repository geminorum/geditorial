<?php namespace geminorum\gEditorial\Modules\Lingo;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleSettings extends gEditorial\Settings
{
	const MODULE = 'lingo';

	const ACTION_CREATE_LANG_TAXONOMY = 'do_import_language_taxonomy_create';
	const ACTION_UPDATE_LANG_TAXONOMY = 'do_import_language_taxonomy_update';

	public static function renderCard_imports_identifiers( $taxonomy, $data, $metakeys )
	{
		if ( empty( $data ) )
			return FALSE;

		echo self::toolboxCardOpen(
			_x( 'Language Identifiers', 'Card Title', 'geditorial-lingo' ).
			Core\HTML::code( _x( 'ISO 639-1 Alpha-2', 'Imports', 'geditorial-lingo' ), 'sub' ), FALSE, '-tablelist-card' );

			if ( $data ) {

				Core\HTML::tableList( [
					'_cb'  => 'code',
					'term' => [
						'title'    => _x( 'Term', 'Table Column', 'geditorial-lingo' ),
						'class'    => '-language-term',
						'args'     => [ 'taxonomy' => $taxonomy, 'metakeys' => $metakeys ],
						'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

							if ( $term_id = WordPress\Taxonomy::getIDbyMeta( $column['args']['metakeys']['code'], $row['code'] ) )
								$title = gEditorial\Helper::getTermTitleRow( $term_id );

							else if ( $term_id = WordPress\Term::exists( $row['name'], $column['args']['taxonomy'] ) )
								$title = gEditorial\Helper::getTermTitleRow( $term_id );

							else if ( $term_id = WordPress\Taxonomy::getIDbyMeta( $column['args']['metakeys']['native'], $row['native'] ) )
								$title = gEditorial\Helper::getTermTitleRow( $term_id );

							if ( empty( $title ) )
								return gEditorial\Helper::htmlEmpty();

							Core\HTML::inputHidden( sprintf( 'term_id[%s]', $row['code'] ), $term_id );

							return $title;
						}
					],
					'code' => [
						'title' => _x( 'Code', 'Table Column', 'geditorial-lingo' ),
						'class'    => '-language-code -ltr',
					],
					'name' => [
						'title' => _x( 'English Name', 'Table Column', 'geditorial-lingo' ),
						'class'    => '-language-english-name -ltr',
					],
					'native' => [
						'title' => _x( 'Native Name', 'Table Column', 'geditorial-lingo' ),
						'class'    => '-language-native-name -ltr',
					],

				], $data, [
					'empty' => Core\HTML::warning( _x( 'There are no language identifiers available!', 'Message: Table Empty', 'geditorial-lingo' ), FALSE ),
				] );

			} else {

				echo gEditorial\Plugin::wrong();
			}

		echo '</div>';

		echo self::toolboxAfterOpen(
			_x( 'Check for available language identifiers and create corresponding terms.', 'Message', 'geditorial-lingo' ), TRUE );

			self::submitButton( static::ACTION_CREATE_LANG_TAXONOMY,
				_x( 'Create Language Terms', 'Button', 'geditorial-lingo' ), TRUE );

			self::submitCheckBox( static::ACTION_UPDATE_LANG_TAXONOMY,
				_x( 'Update Existing Terms', 'Button', 'geditorial-lingo' ) );

		echo '</div>';
		return TRUE;
	}
}
