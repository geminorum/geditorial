<?php namespace geminorum\gEditorial\Modules\Iranian;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;

class ModuleInfo extends gEditorial\Info
{

	const MODULE = 'iranian';

	public static function getProvinces( $context = NULL )
	{
		switch ( Core\L10n::locale( TRUE ) ) {

			case 'fa_IR': return [
				'ABZ' => 'البرز',
				'ADL' => 'اردبیل',
				'BHR' => 'بوشهر',
				'CHB' => 'چهارمحال و بختیاری',
				'EAZ' => 'آذربایجان شرقی',
				'ESF' => 'اصفهان',
				'FRS' => 'فارس',
				'GIL' => 'گیلان',
				'GLS' => 'گلستان',
				'GZN' => 'قزوین',
				'HDN' => 'همدان',
				'HRZ' => 'هرمزگان',
				'ILM' => 'ایلام',
				'KBD' => 'کهگیلوییه و بویراحمد',
				'KHZ' => 'خوزستان',
				'KRD' => 'کردستان',
				'KRH' => 'کرمانشاه',
				'KRN' => 'کرمان',
				'LRS' => 'لرستان',
				'MKZ' => 'مرکزی',
				'MZN' => 'مازندران',
				'NKH' => 'خراسان شمالی',
				'QHM' => 'قم',
				'RKH' => 'خراسان رضوی',
				'SBN' => 'سیستان و بلوچستان',
				'SKH' => 'خراسان جنوبی',
				'SMN' => 'سمنان',
				'THR' => 'تهران',
				'WAZ' => 'آذربایجان غربی',
				'YZD' => 'یزد',
				'ZJN' => 'زنجان',
			];

			default: return [
				'ABZ' => _x( 'Alborz', 'Province Name', 'geditorial-iranian' ),
				'ADL' => _x( 'Ardabil', 'Province Name', 'geditorial-iranian' ),
				'BHR' => _x( 'Bushehr', 'Province Name', 'geditorial-iranian' ),
				'CHB' => _x( 'Chaharmahal and Bakhtiari', 'Province Name', 'geditorial-iranian' ),
				'EAZ' => _x( 'East Azarbaijan', 'Province Name', 'geditorial-iranian' ),
				'ESF' => _x( 'Isfahan', 'Province Name', 'geditorial-iranian' ),
				'FRS' => _x( 'Fars', 'Province Name', 'geditorial-iranian' ),
				'GIL' => _x( 'Gilan', 'Province Name', 'geditorial-iranian' ),
				'GLS' => _x( 'Golestan', 'Province Name', 'geditorial-iranian' ),
				'GZN' => _x( 'Ghazvin', 'Province Name', 'geditorial-iranian' ),
				'HDN' => _x( 'Hamadan', 'Province Name', 'geditorial-iranian' ),
				'HRZ' => _x( 'Hormozgan', 'Province Name', 'geditorial-iranian' ),
				'ILM' => _x( 'Ilaam', 'Province Name', 'geditorial-iranian' ),
				'KBD' => _x( 'Kohgiluyeh and BoyerAhmad', 'Province Name', 'geditorial-iranian' ),
				'KHZ' => _x( 'Khuzestan', 'Province Name', 'geditorial-iranian' ),
				'KRD' => _x( 'Kurdistan', 'Province Name', 'geditorial-iranian' ),
				'KRH' => _x( 'Kermanshah', 'Province Name', 'geditorial-iranian' ),
				'KRN' => _x( 'Kerman', 'Province Name', 'geditorial-iranian' ),
				'LRS' => _x( 'Luristan', 'Province Name', 'geditorial-iranian' ),
				'MKZ' => _x( 'Markazi', 'Province Name', 'geditorial-iranian' ),
				'MZN' => _x( 'Mazandaran', 'Province Name', 'geditorial-iranian' ),
				'NKH' => _x( 'North Khorasan', 'Province Name', 'geditorial-iranian' ),
				'QHM' => _x( 'Qom', 'Province Name', 'geditorial-iranian' ),
				'RKH' => _x( 'Razavi Khorasan', 'Province Name', 'geditorial-iranian' ),
				'SBN' => _x( 'Sistan and Baluchestan', 'Province Name', 'geditorial-iranian' ),
				'SKH' => _x( 'South Khorasan', 'Province Name', 'geditorial-iranian' ),
				'SMN' => _x( 'Semnan', 'Province Name', 'geditorial-iranian' ),
				'THR' => _x( 'Tehran', 'Province Name', 'geditorial-iranian' ),
				'WAZ' => _x( 'West Azarbaijan', 'Province Name', 'geditorial-iranian' ),
				'YZD' => _x( 'Yazd', 'Province Name', 'geditorial-iranian' ),
				'ZJN' => _x( 'Zanjan', 'Province Name', 'geditorial-iranian' ),
			];
		}
	}
}
