<?php namespace geminorum\gEditorial\Modules\Lingo;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleSettings extends gEditorial\Settings
{
	const MODULE = 'lingo';

	const ACTION_CONVERT_INTO_LANG     = 'do_tool_convert_language';
	const ACTION_CONVERT_OVERRIDE_LANG = 'do_tool_convert_override_existing';
	const ACTION_INVESTIGATE_TERMS     = 'do_tool_investigate_terms';
	const ACTION_INVESTIGATE_NO_EMPTY  = 'do_tool_investigate_no_empty';

	const ACTION_CREATE_LANG_TAXONOMY = 'do_import_language_taxonomy_create';
	const ACTION_UPDATE_LANG_TAXONOMY = 'do_import_language_taxonomy_update';

	public static function renderCard_import_identifiers( $taxonomy, $rawdata, $metakeys )
	{
		if ( empty( $rawdata ) )
			return FALSE;

		echo self::toolboxCardOpen(
			_x( 'Language Identifiers', 'Card Title', 'geditorial-lingo' ).
			Core\HTML::code( _x( 'ISO 639-1 Alpha-2', 'Imports', 'geditorial-lingo' ), 'sub' ), FALSE, '-tablelist-card' );

				Core\HTML::tableList( [
					'_cb'  => 'alpha2code',
					'term' => [
						'title'    => _x( 'Term', 'Table Column', 'geditorial-lingo' ),
						'class'    => '-language-term',
						'args'     => [ 'taxonomy' => $taxonomy, 'metakeys' => $metakeys ],
						'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

							if ( $term_id = WordPress\Taxonomy::getIDbyMeta( $column['args']['metakeys']['alpha2code'], $row['alpha2code'] ) )
								$title = gEditorial\Helper::getTermTitleRow( $term_id );

							else if ( $term_id = WordPress\Term::exists( $row['en_name'], $column['args']['taxonomy'] ) )
								$title = gEditorial\Helper::getTermTitleRow( $term_id );

							else if ( $term_id = WordPress\Taxonomy::getIDbyMeta( $column['args']['metakeys']['endonym'], $row['endonym'] ) )
								$title = gEditorial\Helper::getTermTitleRow( $term_id );

							if ( empty( $title ) )
								return gEditorial\Helper::htmlEmpty();

							Core\HTML::inputHidden(
								sprintf( 'mapped[%s]', $row['alpha2code'] ),
								$term_id
							);

							return $title;
						}
					],
					'alpha2code' => [
						'title' => _x( 'Alpha-2', 'Table Column', 'geditorial-lingo' ),
						'class'    => '-language-alpha2code -ltr',
					],
					'rtl' => [
						'title' => _x( '<abbr title="Direction">Dir</abbr>', 'Table Column', 'geditorial-lingo' ),
						'class'    => '-language-direction',
						'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
							return $value
								? Core\HTML::getDashicon( 'arrow-left-alt', _x( 'Right-to-Left', 'Table Column', 'geditorial-lingo' ), '-direction-icon' )
								: Core\HTML::getDashicon( 'arrow-right-alt', _x( 'Left-to-Right', 'Table Column', 'geditorial-lingo' ), '-direction-icon' );
						},
					],
					'en_name' => [
						'title' => _x( 'English Name', 'Table Column', 'geditorial-lingo' ),
						'class'    => '-language-english-name -ltr',
					],
					'endonym' => [
						'title' => _x( 'Native Name', 'Table Column', 'geditorial-lingo' ),
						'class'    => '-language-endonym -ltr',
					],

				], $rawdata, [
					'empty' => _x( 'There are no language identifiers available!', 'Message: Table Empty', 'geditorial-lingo' ),
				] );

		echo '</div>';

		echo self::toolboxAfterOpen(
			_x( 'Check for available language identifiers and create corresponding terms.', 'Message', 'geditorial-lingo' ), TRUE );

			self::submitButton( static::ACTION_CREATE_LANG_TAXONOMY,
				_x( 'Create Language Terms', 'Button', 'geditorial-lingo' ), TRUE );

			self::submitCheckBox( static::ACTION_UPDATE_LANG_TAXONOMY,
				_x( 'Update Existing Terms', 'CheckBox', 'geditorial-lingo' ) );

		echo '</div>';
		return TRUE;
	}

	public static function renderCard_tool_convert_terms_step2( $queried, $rawdata, $metakeys )
	{
		if ( ! isset( $_POST[static::ACTION_INVESTIGATE_TERMS] ) )
			return FALSE;

		if ( empty( $queried ) )
			return ! gEditorial\Info::renderEmptyTaxonomy( gEditorial\Settings::goBackButton() );

		$label = Services\CustomTaxonomy::getLabel( $queried, 'extended_label' );
		$terms = get_terms( [
			'taxonomy'   => $queried,
			'orderby'    => 'none',
			'hide_empty' => self::req( static::ACTION_INVESTIGATE_NO_EMPTY, FALSE ),

			'suppress_filter'        => TRUE,
			'update_term_meta_cache' => FALSE,
		] );

		echo gEditorial\Settings::toolboxCardOpen(
			_x( 'Queried Taxonomy Terms', 'Card Title', 'geditorial-lingo' ).
			Core\HTML::small( $label, 'sub' ), FALSE, '-tablelist-card' );

			$empty = Core\HTML::tableList( [
				'_cb'   => 'term_id',
				'_lang' => gEditorial\Tablelist::columnGeneralCode( '_lang', _x( '<abbr title="Language">Lang</abbr>', 'Table Column', 'geditorial-lingo' ) ),
				'name'  => gEditorial\Tablelist::columnTermName( [] ),
				'slug'  => gEditorial\Tablelist::columnTermSlug( FALSE ),
				'_code' => gEditorial\Tablelist::columnGeneralCode( '_code' ),

			], $terms, [
				'empty'    => _x( 'There are no terms available!', 'Table Empty', 'geditorial-lingo' ),
				'row_prep' => [ __CLASS__, 'rowPrep_convert_terms' ],
				'extra'    => [
					'rawdata'  => Core\Arraay::reKey( $rawdata, 'alpha2code' ),
					'en_names' => Core\Arraay::pluck( $rawdata, 'en_name', 'alpha2code' ),
					'endonyms' => Core\Arraay::pluck( $rawdata, 'endonym', 'alpha2code' ),
					'metakeys' => $metakeys,
				],
				'return_empty' => TRUE,
			] );

			gEditorial\Scripts::enqueueClickToClip();

		echo '</div>';

		if ( $empty )
			return gEditorial\Settings::processingAllDone( FALSE );

		echo self::toolboxAfterOpen(
			_x( 'Check for available language identifiers and create corresponding terms.', 'Message', 'geditorial-lingo' ), TRUE );

			self::submitButton( static::ACTION_CONVERT_INTO_LANG,
				_x( 'Convert to Language Terms', 'Button', 'geditorial-lingo' ), TRUE );

			self::submitCheckBox( static::ACTION_CONVERT_OVERRIDE_LANG,
				_x( 'Override The Name of Language', 'CheckBox', 'geditorial-lingo' ) );

		echo '</div>';

		return TRUE;
	}

	public static function rowPrep_convert_terms( $row, $index, $args )
	{
		if ( ! $term = WordPress\Term::get( $row ) )
			return FALSE;

		$lang = FALSE;
		$row->_code = get_metadata( 'term', $term->term_id, $args['extra']['metakeys']['alpha2code'], TRUE );

		if ( $row->_code && isset( $args['extra']['rawdata'][$row->_code] ) )
			$lang = $row->_code;

		else if ( FALSE !== ( $code = array_search( $term->name, $args['extra']['en_names'], FALSE ) ) )
			$lang =  $code;

		else if ( FALSE !== ( $code = array_search( $term->name, $args['extra']['endonyms'], FALSE ) ) )
			$lang = $code;

		// TODO: search the term name strip-prefixed

		if ( empty( $lang ) )
			return FALSE;

		$row->_lang = $lang;

		Core\HTML::inputHidden(
			sprintf( 'mapped[%d]', $term->term_id ),
			$lang
		);

		return $row;
	}

	public static function renderCard_tool_convert_terms( $taxonomy, $rawdata, $metakeys, $supported )
	{
		if ( empty( $rawdata ) || empty( $supported ) )
			return FALSE;

		$selected = self::req( 'taxonomy', '' );

		if ( self::renderCard_tool_convert_terms_step2( $selected, $rawdata, $metakeys ) )
			return TRUE;

		echo self::toolboxCardOpen( _x( 'Terms for Languages', 'Card Title', 'geditorial-lingo' ), FALSE );

			Core\HTML::desc( _x( 'Imports already tagged taxonomy data into language identifiers.', 'Message', 'geditorial-lingo' ) );

			echo Core\HTML::wrap( Core\HTML::dropdown( $supported, [
				'name'       => 'taxonomy',
				'selected'   => $selected,
				'none_title' => self::showOptionNone(),
			] ), 'field-wrap -select' );

			echo '<div class="-wrap -wrap-button-row">';

			self::submitButton( static::ACTION_INVESTIGATE_TERMS,
				_x( 'Investigate Terms', 'Button', 'geditorial-lingo' ), TRUE );

			self::submitCheckBox( static::ACTION_INVESTIGATE_NO_EMPTY,
				_x( 'Skip Empty Terms', 'CheckBox', 'geditorial-lingo' ) );

		echo '</div></div>';
		return TRUE;
	}
}
