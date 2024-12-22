<?php namespace geminorum\gEditorial\Modules\Suited;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;

class ModuleInfo extends gEditorial\Info
{

	const MODULE = 'suited';

	public static function getDemographicProfiles( $context = NULL )
	{
		switch ( Core\L10n::locale( TRUE ) ) {

			case 'fa_IR':

				// @REF: https://fa.wikipedia.org/wiki/%DA%AF%D8%B1%D9%88%D9%87_%D8%B3%D9%86%DB%8C
				return [
					'group-alef' => [
						'slug'        => 'group-alef',
						'name'        => 'گروه الف',
						'description' => 'آمادگی و سال اول دبستان',
					],
					'group-be' => [
						'slug'        => 'group-be',
						'name'        => 'گروه ب',
						'description' => 'سال‌های دوم و سوم دبستان',
					],
					'group-jim' => [
						'slug'        => 'group-jim',
						'name'        => 'گروه ج',
						'description' => 'سال‌های چهارم، پنجم و ششم دبستان',
					],
					'group-daal' => [
						'slug'        => 'group-daal',
						'name'        => 'گروه د',
						'description' => 'سال‌های هفتم و هشتم و نهم راهنمایی',
					],
					'group-he' => [
						'slug'        => 'group-he',
						'name'        => 'گروه ه',
						'description' => 'سال‌های دهم و یازدهم و دوازدهم دبیرستان',
					],
				];

			default:

				return self::getAgeStructure( TRUE );
		}
	}
}
