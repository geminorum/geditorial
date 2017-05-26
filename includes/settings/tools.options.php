<?php namespace geminorum\gEditorial\Settings;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\WordPress\User;

User::superAdminOnly();

HTML::tableSide( get_option( 'geditorial_options' ) );
