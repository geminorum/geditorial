<?php namespace geminorum\gEditorial\Modules\WcIdentify;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleSettings extends gEditorial\Settings
{

	const MODULE = 'wc_identify';

	const ACTION_MIGRATE_UNIQUEID = 'do_tool_migrate_uniqueid';

	public static function renderCard_tool_migrate_uniqueid()
	{
		echo self::toolboxCardOpen( _x( 'Attribute to Unique-ID', 'Card Title', 'geditorial-wc-identify' ) );

			echo Core\Link::button(
				_x( 'Migrate Data', 'Button', 'geditorial-wc-identify' ),
				add_query_arg( [
					'action' => static::ACTION_MIGRATE_UNIQUEID,
				] )
			);

			Core\HTML::desc( _x( 'Imports Unique-ID product attributes into built-in global unique ID fields.', 'Message', 'geditorial-wc-identify' ), FALSE );

		echo '</div></div>';

		return TRUE;
	}

	public static function handleTool_migrate_uniqueid( $posttype, $limit = 25 )
	{
		$products = wc_get_products( [
			'limit'   => $limit,
			'page'    => self::paged(),
			'orderby' => 'id',
		] );

		if ( empty( $products ) )
			return self::processingAllDone();

		echo self::processingListOpen();

		foreach ( $products as $product )
			self::_product_migrate_uniqueid( $product, TRUE );

		echo '</ul></div>';

		return WordPress\Redirect::doJS( add_query_arg( [
			'action' => static::ACTION_MIGRATE_UNIQUEID,
			'paged'  => self::paged() + 1,
		] ) );
	}

	private static function _product_migrate_uniqueid( mixed $product, bool $verbose = FALSE ): bool
	{
		if ( ! $product = wc_get_product( $product ) )
			return FALSE;

		if ( ! method_exists( $product, 'get_global_unique_id' ) )
			return FALSE;

		if ( $saved = $product->get_global_unique_id( 'edit' ) )
			return self::processingListItem( $verbose,
				/* translators: `%1$s`: unique-id placeholder, `%2$s`: product title */
				_x( '%1$s Unique-ID already saved for &ldquo;%2$s&rdquo;.', 'Notice', 'geditorial-wc-identify' ), [
					Core\HTML::code( $saved ),
					$product->get_name(),
				] );

		foreach ( $product->get_attributes() as $offset => $attribute ) {

			if ( ! $uniqueid = ModuleHelper::possibleAttributeForUniqueID( $attribute ) )
				continue;

			$product->set_global_unique_id( $uniqueid );

			if ( count( $attribute->get_options() ) > 1 ) {

				$product->save();

				return self::processingListItem( $verbose,
					/* translators: `%1$s`: unique-id placeholder, `%2$s`: attribute name, `%3$s`: product title */
					_x( '%1$s Unique-ID migrated from &ldquo;%2$s&rdquo; attribute for &ldquo;%3$s&rdquo;. The attribute has more than one option!', 'Notice', 'geditorial-wc-identify' ), [
						Core\HTML::code( $uniqueid ),
						$attribute->get_name(),
						$product->get_name(),
					], TRUE );
			}

			$atts = $product->get_attributes();
			unset( $atts[$offset] );
			$product->set_attributes( $atts );
			$product->save();

			return self::processingListItem( $verbose,
				/* translators: `%1$s`: unique-id placeholder, `%2$s`: attribute name, `%3$s`: product title */
				_x( '%1$s Unique-ID migrated from &ldquo;%2$s&rdquo; attribute for &ldquo;%3$s&rdquo;. The attribute successfully deleted!', 'Notice', 'geditorial-wc-identify' ), [
					Core\HTML::code( $uniqueid ),
					$attribute->get_name(),
					$product->get_name(),
				], TRUE );
		}

		return self::processingListItem( $verbose,
			/* translators: `%s`: product title */
			_x( 'No relevant Unique-ID attribute found for &ldquo;%s&rdquo;.', 'Notice', 'geditorial-wc-identify' ), [
				$product->get_name(),
			] );
	}
}
