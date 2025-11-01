<?php defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

\geminorum\gEditorial\WordPress\User::superAdminOnly();

$files = [
	// 'Test_Address',
	// 'Test_Country',
	// 'Test_CSV',
	// 'Test_Date',
	// 'Test_Duration',
	// 'Test_IBAN',
	// 'Test_ISBN',
	// 'Test_LatLng',
	// 'Test_Gpx',
	// 'Test_Mime',
	// 'Test_Names',
	// 'Test_Phone',
	// 'Test_Vin',
	// 'Test_HTML',
];

foreach ( $files as $file )
	if ( file_exists( GEDITORIAL_DIR.'.test/'.$file.'.php' ) )
		require_once GEDITORIAL_DIR.'.test/'.$file.'.php';
