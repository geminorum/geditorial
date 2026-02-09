<?php namespace geminorum\gEditorial\Modules\Addressed;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleSettings extends gEditorial\Settings
{
	const MODULE = 'addressed';

	public static function renderCard_address_type_report( $type, $data = FALSE )
	{
		echo self::toolboxCardOpen( $type['title'] ?? _x( 'Untitled Address Type', 'Card Title', 'geditorial-addressed' ), FALSE );

			Core\HTML::desc( $type['description'] ?? '' );

			if ( empty( $data ) || ! ( $formatted = Services\Locations::formatAddress( $data ) ) )
				$formatted = gEditorial\Plugin::noinfo();

			// NOTE: intentionally avoid styles for `address` HTML tag
			echo Core\HTML::tag( 'pre', [
				'dir' => Core\HTML::dir(),
			], Core\HTML::tag( 'address', $formatted ) );

			if ( ! empty( $data['embed'] ) )
				echo Core\HTML::wrap( gEditorial\Template::doMediaShortCode( $data['embed'], 'embed', FALSE, 'reports' ), '-wrap-embed' );

			$buttons = [];

			if ( ! empty( $data['site'] ) ) {

				if ( Core\URL::isValid( $data['site'] ) )
					$buttons[] = Core\HTML::tag( 'a', [
						'href'   => $data['site'],
						'class'  => Core\HTML::buttonClass( FALSE ),
						'target' => '_blank',
					], sprintf(
						/* translators: `%s`: site URL title */
						_x( 'Visit the website on &#8220;%s&#8221;', 'Button', 'geditorial-addressed' ),
						Core\URL::prepTitle( $data['site'] )
					) );

				else
					$buttons[] = Core\HTML::tag( 'span', [
						'class' => Core\HTML::buttonClass( FALSE, '-is-not-valid' ),
						'title' => gEditorial\Plugin::invalid( FALSE ),
					], $data['site'] );
			}

			if ( ! empty( $data['latlng'] ) ) {

				if ( Core\LatLng::is( $data['latlng'] ) )
					$buttons[] = gEditorial\Info::lookupLatLng(
						$data['latlng'],
						Core\HTML::buttonClass( FALSE, '-is-valid' )
					);

				else
					$buttons[] = Core\HTML::tag( 'span', [
						'class' => Core\HTML::buttonClass( FALSE, '-is-not-valid' ),
						'title' => gEditorial\Plugin::invalid( FALSE ),
					], $data['latlng'] );
			}

			echo Core\HTML::wrap( implode( "\n", $buttons ), '-wrap-button-row' );

			if ( WordPress\IsIt::dev() || WordPress\IsIt::debug() ) {
				Core\HTML::h4( 'DEBUG: Defined Address Type', '-title-debug' );
				Core\HTML::tableSide( $type );
				Core\HTML::h4( 'DEBUG: Current Address Data', '-title-debug' );
				Core\HTML::tableSide( $data );
			}

		echo '</div>';
		return TRUE;
	}
}
