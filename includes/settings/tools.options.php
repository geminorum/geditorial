<?php namespace geminorum\gEditorial\Settings;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gNetwork\Core\HTML;
use geminorum\gNetwork\WordPress\User;

User::superAdminOnly();

HTML::tableSide( get_option( 'geditorial_options' ) );
