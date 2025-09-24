<?php defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

\geminorum\gEditorial\WordPress\User::superAdminOnly();

$files = [
	// 'Address',
	// 'Country',
	// 'CSV',
	// 'Date',
	// 'Duration',
	// 'IBAN',
	// 'ISBN',
	// 'LatLng',
	// 'Mime',
	// 'Names',
	// 'Phone',
	// 'Vin',
];

foreach ( $files as $file )
	if ( file_exists( GEDITORIAL_DIR.'.test/'.$file.'.php' ) )
		require_once GEDITORIAL_DIR.'.test/'.$file.'.php';
