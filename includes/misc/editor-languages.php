<?php defined( 'ABSPATH' ) or exit;

// https://www.gavick.com/blog/wordpress-tinymce-custom-buttons

if ( ! class_exists( '_WP_Editors' ) )
	require( ABSPATH.WPINC.'/class-wp-editor.php' );

$strings = class_exists( 'geminorum\\gEditorial\\Helper' ) ? \geminorum\gEditorial\Helper::getTinyMceStrings( \_WP_Editors::$mce_locale ) : '';
