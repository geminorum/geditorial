<?php namespace geminorum\gEditorial\Modules\Educated;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;

class ModuleInfo extends gEditorial\Info
{

	const MODULE = 'educated';

	public static function getEducationDefaultTerms( $context = NULL )
	{
		switch ( Core\L10n::locale( TRUE ) ) {

			case 'fa_IR':

				// @REF: https://fa.wikipedia.org/wiki/%D9%85%D8%AF%D8%B1%DA%A9_%D8%AA%D8%AD%D8%B5%DB%8C%D9%84%DB%8C
				return [
					'bisavad' => [
						'slug'        => 'bisavad',
						'name'        => 'بی‌سواد',
						'description' => 'فاقد مدرکت تحصیلی',
					],
					'ebtedaee' => [
						'slug'        => 'ebtedaee',
						'name'        => 'ابتدایی',
						'description' => 'دورهٔ ابتدایی: پس از موفقیت در امتحانات کلاس ششم ابتدایی',
					],
					'seekle' => [
						'slug'        => 'seekle',
						'name'        => 'سیکل',
						'description' => 'دورهٔ متوسطه اول یا سیکل: پس از موفقیت در امتحانات کلاس نهم (سوم نظام قدیم) دورهٔ متوسطه اول',
					],
					'diplom' => [
						'slug'        => 'diplom',
						'name'        => 'دیپلم',
						'description' => 'دورهٔ متوسطه دوم یا دیپلم: پس از موفقیت در امتحانات نهایی کلاس دوازدهم (سوم نظام قدیم) دورهٔ متوسطه دوم',
					],
					'kaardani' => [
						'slug'        => 'kaardani',
						'name'        => 'کاردانی',
						'description' => 'فوق دیپلم یا کاردانی',
					],
					'kaarshenasi' => [
						'slug'        => 'kaarshenasi',
						'name'        => 'کارشناسی',
						'description' => 'لیسانس یا کارشناسی',
					],
					'arshad' => [
						'slug'        => 'arshad',
						'name'        => 'کارشناسی ارشد',
						'description' => 'فوق لیسانس یا کارشناسی ارشد',
					],
					'doctora' => [
						'slug'        => 'doctora',
						'name'        => 'دکترا',
						'description' => 'دوره دکتری تخصصی',
					],
				];

			default:

				return [
					'illiterate' => _x( 'Illiterate', 'Default Term', 'geditorial-educated' ),
					'primary'    => _x( 'Primary', 'Default Term', 'geditorial-educated' ),
					'secondary'  => _x( 'Secondary', 'Default Term', 'geditorial-educated' ),
					'bachelor'   => _x( 'Bachelor', 'Default Term', 'geditorial-educated' ),
					'master'     => _x( 'Master', 'Default Term', 'geditorial-educated' ),
					'doctorate'  => _x( 'Doctorate', 'Default Term', 'geditorial-educated' ),
				];
		}
	}
}
