<?php defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\WordPress;

WordPress\User::superAdminOnly();

WordPress\User::dump( gEditorial() );
