<?php defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

WordPress\User::superAdminOnly();

$files = [
	//'Test_Array-Merge',

	// 'Test_Avatars',

	// 'Test_Address',
	// 'Test_Country',
	// 'Test_CSV',
	// 'Test_Date',
	// 'Test_Date_2',
	// 'Test_Date_3',
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
	// 'Test_Barcode_1',
	// 'Test_Color_1',
	// 'Test_circle-progress',
];

foreach ( $files as $file )
	if ( file_exists( GEDITORIAL_DIR.'.test/'.$file.'.php' ) )
		require_once GEDITORIAL_DIR.'.test/'.$file.'.php';
