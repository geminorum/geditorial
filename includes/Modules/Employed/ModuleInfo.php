<?php namespace geminorum\gEditorial\Modules\Employed;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;

class ModuleInfo extends gEditorial\Info
{

	const MODULE = 'employed';

	public static function getEmploymentDefaultTerms( $context = NULL )
	{
		switch ( Core\L10n::locale( TRUE ) ) {

			case 'fa_IR':

				return [
					'corporate' => [
						'slug'        => 'corporate',
						'name'        => 'شرکتی',
						'description' => 'از طرف شرکت ثالث مشغول به کار است.',
					],
					'contracted' => [
						'slug'        => 'contracted',
						'name'        => 'قراردادی',
						'description' => 'قرارداد کاری در بازه‌های زمانی مشخص تمدید می‌شود.',
					],
					'fulltime' => [
						'slug'        => 'fulltime',
						'name'        => 'رسمی',
						'description' => 'استخدام رسمی و دارای حقوق و مزایا است.',
					],
					'parttime' => [
						'slug'        => 'parttime',
						'name'        => 'نیمه‌وقت',
						'description' => 'استخدام رسمی و دارای حقوق و بدون مزایا است.',
					],
					'labor' => [
						'slug'        => 'labor',
						'name'        => 'کارگری',
						'description' => 'دستمزد مصوب اداره کار را دریافت می‌کند.',
					],
					'daily' => [
						'slug'        => 'daily',
						'name'        => 'روزمزد',
						'description' => 'بر اساس کارکرد روزانه دستمزد می‌گیرد.',
					],
					'unemployed' => [
						'slug'        => 'unemployed',
						'name'        => 'بی‌کار',
						'description' => 'مشغول به شغل دارای درآمد نیست.',
					],
					'fired' => [
						'slug'        => 'fired',
						'name'        => 'اخراجی',
						'description' => 'از محل کار اخراج شده است.',
					],
					'entrepreneurship' => [
						'slug'        => 'entrepreneurship',
						'name'        => 'کارآفرین',
						'description' => 'صاحب شغل است و حقوق دریافت نمی‌کند.',
					],
					'selfemployed' => [
						'slug'        => 'selfemployed',
						'name'        => 'خوداشتغالی',
						'description' => 'صاحب شغل است و درآمد را خود تأمین می‌کند.',
					],
					'volunteer' => [
						'slug'        => 'volunteer',
						'name'        => 'داوطلب',
						'description' => 'بدون توقع درآمد مشغول است.',
					],
					'intern' => [
						'slug'        => 'intern',
						'name'        => 'کارآموز',
						'description' => 'جهت آموزش مشغول است و درآمد ندارد.',
					],
				];

			default:

				// @REF: https://www.bamboohr.com/resources/hr-glossary/employment-status
				return [
					'contracted' => [
						'slug'        => 'contracted',
						'name'        => _x( 'Contracted', 'Default Term Title', 'geditorial-employed' ),
						'description' => _x( 'Employed for a predefined period to provide work according to contract terms.', 'Default Term Description', 'geditorial-employed' ),
					],
					'fulltime' => [
						'slug'        => 'fulltime',
						'name'        => _x( 'Full-Time', 'Default Term Title', 'geditorial-employed' ),
						'description' => _x( 'Employed for 40 hours or more per week with salary and benefits.', 'Default Term Description', 'geditorial-employed' ),
					],
					'independent' => [
						'slug'        => 'independent',
						'name'        => _x( 'Independent Contractor', 'Default Term Title', 'geditorial-employed' ),
						'description' => _x( 'Non-employee providing labor according to contract terms.', 'Default Term Description', 'geditorial-employed' ),
					],
					'intern' => [
						'slug'        => 'intern',
						'name'        => _x( 'Intern', 'Default Term Title', 'geditorial-employed' ),
						'description' => _x( 'Temporary employee providing labor for educational benefit.', 'Default Term Description', 'geditorial-employed' ),
					],
					'parttime' => [
						'slug'        => 'parttime',
						'name'        => _x( 'Part-Time', 'Default Term Title', 'geditorial-employed' ),
						'description' => _x( 'Employed at hourly wage for fewer than 40 hours per week.', 'Default Term Description', 'geditorial-employed' ),
					],
					'selfemployed' => [
						'slug'        => 'selfemployed',
						'name'        => _x( 'Self-Employed', 'Default Term Title', 'geditorial-employed' ),
						'description' => _x( 'The employer and employee are the same person.', 'Default Term Description', 'geditorial-employed' ),
					],
					'temporary' => [
						'slug'        => 'temporary',
						'name'        => _x( 'Temporary', 'Default Term Title', 'geditorial-employed' ),
						'description' => _x( 'Short-term employee or contractor with predefined work dates.', 'Default Term Description', 'geditorial-employed' ),
					],
					'seasonal' => [
						'slug'        => 'seasonal',
						'name'        => _x( 'Seasonal', 'Default Term Title', 'geditorial-employed' ),
						'description' => _x( 'Short-term employee or contractor with predefined work dates.', 'Default Term Description', 'geditorial-employed' ),
					],
					'unemployed' => [
						'slug'        => 'unemployed',
						'name'        => _x( 'Unemployed', 'Default Term Title', 'geditorial-employed' ),
						'description' => _x( 'Former employee no longer providing work for the employer.', 'Default Term Description', 'geditorial-employed' ),
					],
					'volunteer' => [
						'slug'        => 'volunteer',
						'name'        => _x( 'Volunteer', 'Default Term Title', 'geditorial-employed' ),
						'description' => _x( 'Non-employee voluntarily providing labor with no expectation of payment', 'Default Term Description', 'geditorial-employed' ),
					],
				];
		}
	}
}
