<?php namespace geminorum\gEditorial\Settings;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\WordPress\User;

User::superAdminOnly();

echo '<br />';

if ( $options = get_option( 'geditorial_options' ) )
	HTML::tableSide( $options );
else
	HTML::desc( \geminorum\gEditorial\Plugin::na() );
