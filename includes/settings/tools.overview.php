<?php defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

if ( function_exists( 'gnetwork_update_notice' ) )
	gnetwork_update_notice( GEDITORIAL_FILE );

if ( function_exists( 'gnetwork_github_readme' ) )
	gnetwork_github_readme( 'geminorum/geditorial' );
