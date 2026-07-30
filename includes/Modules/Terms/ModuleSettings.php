<?php namespace geminorum\gEditorial\Modules\Terms;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleSettings extends gEditorial\Settings
{
	const MODULE = 'terms';

	const ACTION_CUSTOM_FIELDS_CHECK   = 'custom_fields_check';
	const ACTION_CUSTOM_FIELDS_CONVERT = 'custom_fields_convert';
	const ACTION_CUSTOM_FIELDS_DELETE  = 'custom_fields_delete';

	const ACTION_UPDATE_EXISTING_METADATA = 'custom_fields_update_existsing';
	const ACTION_CLEANUP_AFTER_IMPORT     = 'custom_fields_cleanup_adter_import';

	const CURRENT_FORM = [
		'imports' => [
			'custom_field'       => '',
			'custom_field_limit' => '',
			'custom_field_tax'   => 'post_tag',
			'custom_field_into'  => '',
		],
	];

	public static function renderCard_import_custom_fields( array $metakeys, array $supported, array $taxonomies, ?string $context = NULL ): bool
	{
		$context = $context ?? 'imports';
		$form    = self::getCurrentForm( static::CURRENT_FORM[$context] , $context );

		if ( isset( $_POST[static::ACTION_CUSTOM_FIELDS_CHECK] ) ) {

			if ( empty( $form['custom_field'] ) )
				return ! gEditorial\Info::renderNoDataAvailable( self::goBackButton() );

			echo self::toolboxCardOpen(
				_x( 'Queried Custom Fields', 'Card Title', 'geditorial-terms' ).
				Core\HTML::code( $form['custom_field'], 'sub' ), FALSE, '-tablelist-card' );

			Core\HTML::tableList( [
				'type'  => gEditorial\Tablelist::columnTermTaxonomy( 'term_id' ),
				'title' => [
					'title'    => _x( 'Title', 'Table Column', 'geditorial-terms' ),
					'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
						return Core\HTML::span( WordPress\Term::title( $row->term_id ), FALSE, $row->term_id, $row->term_id );
					},
				],
				'meta' => [
					'title' => _x( 'Raw Data', 'Table Column', 'geditorial-terms' ),
					'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
						return Core\HTML::sanitizeDisplay( $value );
					},
				],
			], WordPress\TermMeta::listByKey(
				stripslashes( $form['custom_field'] ),
				stripslashes( $form['custom_field_limit'] )
			), [
				'empty' => Core\HTML::warning( _x( 'There are no meta-data available!', 'Table Empty', 'geditorial-terms' ), FALSE ),
			] );

			echo '</div>';

			gEditorial\Scripts::enqueueClickToClip();
		}

		if ( empty( $metakeys ) )
			return FALSE;

		echo self::toolboxCardOpen(
			_x( 'Import Custom Fields', 'Card Title', 'geditorial-terms' ), FALSE );

			echo '<div class="-wrap -wrap-button-row">';

				self::fieldCurrentForm( [
					'type'         => 'select',
					'field'        => 'custom_field',
					'values'       => $metakeys,
					'default'      => $form['custom_field'],
					'none_title'   => self::showOptionNone(),
					'option_group' => $context,
				] );

				self::fieldCurrentForm( [
					'type'         => 'number',
					'field'        => 'custom_field_limit',
					'default'      => $form['custom_field_limit'],
					'option_group' => $context,
					'field_class'  => 'small-text',
					'placeholder'  => 'limit',
				] );

				self::fieldCurrentForm( [
					'type'         => 'select',
					'field'        => 'custom_field_tax',
					'values'       => $taxonomies,
					'default'      => $form['custom_field_tax'],
					'option_group' => $context,
				] );

				Core\HTML::desc( _x( 'Non-protect custom fields limited by taxonomy and total count.', 'Message', 'geditorial-terms' ) );

			echo '</div><div class="-wrap -wrap-button-row">';

				self::fieldCurrentForm( [
					'type'         => 'select',
					'field'        => 'custom_field_into',
					'values'       => Core\Arraay::sameKey( $form['custom_field_tax']
						? array_keys( array_filter( @Core\Arraay::pluck( $supported, $form['custom_field_tax'] ) ) )
						: array_keys( $supported ) ),
					'default'      => $form['custom_field_into'],
					'option_group' => $context,
				] );

				Core\HTML::desc( _x( 'Import into any of available meta fields.', 'Message', 'geditorial-terms' ) );

			echo '</div><br /><div class="-wrap field-wrap -checkboxes">';

				self::submitCheckBox( static::ACTION_UPDATE_EXISTING_METADATA,
					_x( 'Upon conversion also update <b>existing</b> meta-data.', 'CheckBox', 'geditorial-terms' ), [], '<div>', '</div>' );

				self::submitCheckBox( static::ACTION_CLEANUP_AFTER_IMPORT,
					_x( 'After conversion <b>cleanup</b> old residual meta-data.', 'CheckBox', 'geditorial-terms' ), [], '<div>', '</div>' );

			echo '</div><div class="-wrap -wrap-button-row">';

				self::submitButton( static::ACTION_CUSTOM_FIELDS_CHECK,
					_x( 'Check', 'Button', 'geditorial-terms' ), TRUE );

				self::submitButton( static::ACTION_CUSTOM_FIELDS_CONVERT,
					_x( 'Covert', 'Button', 'geditorial-terms' ) );

				self::submitButton( static::ACTION_CUSTOM_FIELDS_DELETE,
					_x( 'Delete', 'Button', 'geditorial-terms' ), 'danger', TRUE );

				Core\HTML::desc( _x( 'Check for custom fields and import them into meta fields.', 'Message', 'geditorial-terms' ) );

		echo '</div></div>';
		return TRUE;
	}

	public static function handlePost_import_custom_fields( ?string $context = NULL ): bool
	{
		if ( gEditorial\Tablelist::isAction( static::ACTION_CUSTOM_FIELDS_CHECK ) )
			return TRUE; // will be handled by card renderer

		$context = $context ?? 'imports';

		if ( gEditorial\Tablelist::isAction( static::ACTION_CUSTOM_FIELDS_CONVERT ) ) {

			self::raiseResources();

			$form    = self::getCurrentForm( static::CURRENT_FORM[$context] , $context );
			$update  = self::req( static::ACTION_UPDATE_EXISTING_METADATA, FALSE );
			$cleanup = self::req( static::ACTION_CLEANUP_AFTER_IMPORT, FALSE );
			$count   = 0;

			if ( ! $form['custom_field'] || !$form['custom_field_into'] )
				return ! WordPress\Redirect::doReferer( 'wrong' );

			$rows = WordPress\TermMeta::listByKey(
				$form['custom_field'],
				$form['custom_field_limit'],
			);

			foreach ( $rows as $row ) {

				$term_id = (int) $row->term_id;

				$result = self::factory()->module( static::MODULE )->store_supported_field(
					$term_id,
					$form['custom_field_into'],
					Services\Markup::getSeparated( $row->meta ),
					WordPress\Term::taxonomy( $term_id ),
					$update,
					$context,
				);

				if ( $result && $cleanup )
					delete_term_meta( $term_id, $form['custom_field'] );

				if ( $result )
					$count++;
			}

			if ( $count )
				return WordPress\Redirect::doReferer( [
					'message' => 'converted',
					'count'   => $count,

					'custom_field'       => $form['custom_field'],
					'custom_field_limit' => $form['custom_field_limit'],
				] );

		} else if ( gEditorial\Tablelist::isAction( static::ACTION_CUSTOM_FIELDS_DELETE ) ) {

			self::raiseResources();

			$form = self::getCurrentForm( static::CURRENT_FORM[$context] , $context );

			if ( ! $form['custom_field'] )
				return ! WordPress\Redirect::doReferer( 'wrong' );

			$count = WordPress\TermMeta::deleteByKey(
				$form['custom_field'],
				$form['custom_field_limit'],
			);

			if ( FALSE === $count )
				return ! WordPress\Redirect::doReferer( 'wrong' );

			return WordPress\Redirect::doReferer( [
				'message' => 'deleted',
				'count'   => $count,

				'custom_field'       => $form['custom_field'],
				'custom_field_limit' => $form['custom_field_limit'],
			] );
		}

		return FALSE; // unknown action!
	}
}
