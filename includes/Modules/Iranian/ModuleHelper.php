<?php namespace geminorum\gEditorial\Modules\Iranian;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'iranian';

	// @REF: https://fa.wikipedia.org/wiki/%D9%81%D9%87%D8%B1%D8%B3%D8%AA_%D8%A8%D8%A7%D9%86%DA%A9%E2%80%8C%D9%87%D8%A7%DB%8C_%D8%A7%DB%8C%D8%B1%D8%A7%D9%86
	public static $banks = [
		'ansar'            => 'بانک انصار',             // ادغام
		'ayandeh'          => 'بانک آینده',
		'central-bank'     => 'بانک مرکزی ایران',
		'dey'              => 'بانک دی',
		'eghtesad-novin'   => 'بانک اقتصاد نوین',
		'gardeshgari'      => 'بانک گردشگری',
		'ghavamin'         => 'بانک قوامین',            // ادغام
		'hekmat-iranian'   => 'بانک حکمت ایرانیان',
		'iran-venezuela'   => 'بانک ایران و ونزوئلا',
		'iran-zamin'       => 'بانک ایران زمین',
		'karafarin'        => 'بانک کارآفرین',
		'keshavarzi'       => 'بانک کشاورزی',
		'kosar'            => 'موسسه کوثر',
		'maskan'           => 'بانک مسکن',
		'mehr-eqtesad'     => 'بانک مهر اقتصاد',        // ادغام
		'mehr-iran'        => 'بانک مهر ایران',
		'melal'            => 'موسسه اعتباری ملل',
		'mellat'           => 'بانک ملت',
		'melli'            => 'بانک ملی',
		'middle-east-bank' => 'بانک خاورمیانه',
		'noor-bank'        => 'موسسه اعتباری نور',      // منحل
		'parsian'          => 'بانک پارسیان',
		'pasargad'         => 'بانک پاسارگاد',
		'post'             => 'پست بانک',
		'refah'            => 'بانک رفاه',
		'resalat'          => 'بانک رسالت',
		'saderat'          => 'بانک صادرات',
		'saman'            => 'بانک سامان',
		'sanat-o-madan'    => 'بانک صنعت و معدن',
		'sarmayeh'         => 'بانک سرمایه',
		'sepah'            => 'بانک سپه',
		'shahr'            => 'بانک شهر',
		'shetab'           => 'شتاب',
		'sina'             => 'بانک سینا',
		'tejarat'          => 'بانک تجارت',
		'toose-taavon'     => 'بانک توسعه تعاون',
		'tosee-saderat'    => 'بانک توسعه صادرات',
		'tosee'            => 'موسسه اعتباری توسعه',    // منحل
	];

	public static $cardPrefixes = [
		'636797' => 'central-bank',
		'606256' => 'melal',
		'505801' => 'kosar',
		'505785' => 'iran-zamin',
		'585949' => 'middle-east-bank',
		'504172' => 'resalat',
		'627381' => 'ansar',
		'636214' => 'ayandeh',
		'502938' => 'dey',
		'627412' => 'eghtesad-novin',
		'628157' => 'tosee',
		'505416' => 'gardeshgari',
		'505426' => 'gardeshgari',
		'639599' => 'ghavamin',
		'627488' => 'karafarin',
		'502910' => 'karafarin',
		'603770' => 'keshavarzi',
		'639217' => 'keshavarzi',
		'628023' => 'maskan',
		'639370' => 'mehr-eqtesad',
		'606373' => 'mehr-iran',
		'603799' => 'melli',
		'170019' => 'melli',
		'610433' => 'melli',
		'991975' => 'mellat',
		'111111' => 'shetab',
		'622106' => 'parsian',
		'627884' => 'parsian',
		'502229' => 'pasargad',
		'639347' => 'pasargad',
		'627760' => 'post',
		'589463' => 'refah',
		'627961' => 'sanat-o-madan',
		'603769' => 'saderat',
		'903769' => 'saderat',
		'621986' => 'saman',
		'639607' => 'sarmayeh',
		'589210' => 'sepah',
		'504706' => 'shahr',
		'502806' => 'shahr',
		'639346' => 'sina',
		'627353' => 'tejarat',
		'585983' => 'tejarat',
		'636949' => 'hekmat-iranian',
		'627648' => 'tosee-saderat',
		'207177' => 'tosee-saderat',
		'502908' => 'toose-taavon',
	];

	public static function sanitizeBankName( $bankname, $bank = NULL )
	{
		$sanitized = Core\Text::trim( WordPress\Strings::cleanupChars( $bankname ) );

		if ( ! self::empty( $bank ) ) {

			$bank = self::sanitizeBank( $bank );

			if ( array_key_exists( $bank, static::$banks ) )
				return static::$banks[$bank];
		}

		if ( self::empty( $sanitized ) )
			return '';

		// already
		if ( in_array( $sanitized, static::$banks, TRUE ) )
			return $sanitized;

		switch ( $sanitized ) {

			case 'انصار': $bank = 'ansar'; break;
			case 'موسسه انصار': $bank = 'ansar'; break;
			case 'آینده': $bank = 'ayandeh'; break;
			case 'مرکزی': $bank = 'central-bank'; break;
			case 'بانک مرکزی': $bank = 'central-bank'; break;
			case 'دی': $bank = 'dey'; break;
			case 'گردشگری': $bank = 'gardeshgari'; break;
			case 'قوامین': $bank = 'ghavamin'; break;
			case 'حکمت ایرانیان': $bank = 'hekmat-iranian'; break;
			case 'حکمت': $bank = 'hekmat-iranian'; break;
			case 'ایران و ونزوئلا': $bank = 'iran-venezuela'; break;
			case 'ونزوئلا': $bank = 'iran-venezuela'; break;
			case 'ایران زمین': $bank = 'iran-zamin'; break;
			case 'کارآفرین': $bank = 'karafarin'; break;
			case 'کار آفرین': $bank = 'karafarin'; break;
			case 'کشاورزی': $bank = 'keshavarzi'; break;
			case 'بانک کوثر': $bank = 'kosar'; break;
			case 'بانک کوثر': $bank = 'kosar'; break;
			case 'کوثر': $bank = 'kosar'; break;
			case 'مسکن': $bank = 'maskan'; break;
			case 'مهر اقتصاد': $bank = 'mehr-eqtesad'; break;
			case 'مهر': $bank = 'mehr-iran'; break;
			case 'بانک مهر': $bank = 'mehr-iran'; break;
			case 'مهر ایران': $bank = 'mehr-iran'; break;
			case 'بانک ملل': $bank = 'melal'; break;
			case 'موسسه ملل': $bank = 'melal'; break;
			case 'ملل': $bank = 'melal'; break;
			case 'ملت': $bank = 'mellat'; break;
			case 'به پرداخت': $bank = 'mellat'; break;
			case 'به‌پرداخت': $bank = 'mellat'; break;
			case 'ملی': $bank = 'melli'; break;
			case 'ملی ایران': $bank = 'melli'; break;
			case 'خاورمیانه': $bank = 'middle-east-bank'; break;
			case 'نور': $bank = 'noor-bank'; break;
			case 'پارسیان': $bank = 'parsian'; break;
			case 'پاسارگاد': $bank = 'pasargad'; break;
			case 'پست': $bank = 'post'; break;
			case 'پست ایران': $bank = 'post'; break;
			case 'پست بانک ایران': $bank = 'post'; break;
			case 'رفاه': $bank = 'refah'; break;
			case 'بانک رفاه کارگران': $bank = 'refah'; break;
			case 'رفاه کارگران': $bank = 'refah'; break;
			case 'رسالت': $bank = 'resalat'; break;
			case 'صادرات': $bank = 'saderat'; break;
			case 'بانک صادرات ایران': $bank = 'saderat'; break;
			case 'سامان': $bank = 'saman'; break;
			case 'صنعت': $bank = 'sanat-o-madan'; break;
			case 'صنعت و معدن': $bank = 'sanat-o-madan'; break;
			case 'بانک صنعت': $bank = 'sanat-o-madan'; break;
			case 'سرمایه': $bank = 'sarmayeh'; break;
			case 'سپاه': $bank = 'sepah'; break;
			case 'سپه': $bank = 'sepah'; break;
			case 'شهر': $bank = 'shahr'; break;
			case 'سینا': $bank = 'sina'; break;
			case 'تجارت': $bank = 'tejarat'; break;
			case 'تجارت ایران': $bank = 'tejarat'; break;
			case 'تعاون': $bank = 'toose-taavon'; break;
			case 'توسعه تعاون': $bank = 'toose-taavon'; break;
			case 'توسعه': $bank = 'tosee'; break;
			case 'موسسه توسعه': $bank = 'tosee'; break;
			case 'بانک توسعه': $bank = 'tosee'; break;
		}

		if ( $bank && array_key_exists( $bank, static::$banks ) )
			return static::$banks[$bank];

		// also checks for `bank`
		$fallback = self::sanitizeBank( $sanitized );

		if ( $fallback && array_key_exists( $fallback, static::$banks ) )
			return static::$banks[$fallback];

		return $sanitized;
	}

	public static function sanitizeBank( $bank, $bankname = NULL )
	{
		$sanitized = Core\Text::trim( WordPress\Strings::cleanupChars( $bank ) );

		if ( ! self::empty( $bankname ) ) {

			$bankname = self::sanitizeBankName( $bankname );

			if ( in_array( $bankname, static::$banks, TRUE ) )
				return array_search( $bankname, static::$banks, TRUE );
		}

		if ( self::empty( $sanitized ) )
			return '';

		// already
		if ( array_key_exists( $sanitized, static::$banks ) )
			return $sanitized;

		switch ( $sanitized ) {

			// TODO: finish the list!
			case 'kowsar': return 'kosar';
			case 'melat': return 'mellat';
		}

		// also checks for `bankname`
		$fallback = self::sanitizeBankName( $sanitized );

		if ( $fallback && in_array( $fallback, static::$banks, TRUE ) )
			return array_search( $fallback, static::$banks, TRUE );

		return $sanitized;
	}

	/**
	 * Tries to extract information based on given bank card number.
	 * @source https://github.com/BaseMax/DetectIranianBankJS
	 * @source https://github.com/AmirhBeigi/persian-data
	 *
	 * @param  string $card
	 * @param  array  $fallback
	 * @param  bool   $check
	 * @return array  $info
	 */
	public static function infoFromCardNumber( $card, $fallback = [], $check = FALSE )
	{
		if ( self::empty( $card ) )
			return $fallback;

		if ( $check ) {

			if ( ! $card = Core\Validation::sanitizeCardNumber( $card ) )
				return $fallback;
		}

		$prefix = substr( $card, 0, 5 );

		if ( array_key_exists( $prefix, static::$cardPrefixes ) )
			return [
				'bank'     => static::$cardPrefixes[$prefix],
				'bankname' => static::$banks[static::$cardPrefixes[$prefix]],
				'country'  => 'IR',
			];


		return $fallback;
	}

	/**
	 * Tries to extract information based on given IBAN.
	 * @source https://github.com/BaseMax/DetectIranianBankJS
	 *
	 * @param  string $iban
	 * @param  array  $fallback
	 * @param  bool   $check
	 * @return array  $info
	 */
	public static function infoFromIBAN( $iban, $fallback = [], $check = FALSE )
	{
		if ( self::empty( $iban ) )
			return $fallback;

		if ( $check ) {

			try {

				$iban = Misc\IBAN::createFromString( Core\Number::translate( $iban ) )->toFormattedString( '' );

			} catch ( \Exception $e ) {

				return $fallback;
			}
		}

		if ( 'IR' !== substr( $iban, 0, 2 ) )
			return $fallback;

		/**
		 * IR 06 017 0 000000100324200001
		 *
		 * IR: کد کشور
		 * 06: ۲ رقم، کنترل صحت ساختار
		 * 017: ۳ رقم شناسه بانک
		 * 0: ۱ رقم، نوع حساب
		 * 000000100324200001: ۱۸ رقم، برگفته از شماره حساب
		 *
		 * @source https://vrgl.ir/JVnRK
		 */

		$control  = substr( $iban, 2, 2 );
		$bankcode = substr( $iban, 4, 3 );
		$account  = substr( $iban, 7 );
		$standard = Core\Number::notZeroise( substr( $account, 1 ) );

		switch ( $bankcode ) {

			case '015':

				/**
				 * بخش سوم از اجزای تشکیل‌دهنده شبا که شامل ۲۲ رقم می‌شود، علاوه بر شماره‌حساب بانکی از کد نوع حساب و پیش‌شماره بانک تشکیل می‌شود.
				 * کد نوع حساب در بانک سپه یک رقم است. درصورتی‌که حساب‌های مشتریان بانک سپه از نوع سپرده باشد این عدد 0 و اگر نوع حساب از نوع تسهیلات باشد، عدد موردنظر 1 است
				 * .پیش‌شماره و کد بانک سپه نیز در این شماره شامل سه رقم می‌شود. برای شماره شبا بانک سپه، این عدد 015 است.
				 * آخرین بخش از شماره شبا، شماره اصلی حساب بانکی است. شماره‌حساب‌ها در بانک سپه ۱۶ رقمی هستند، اما به دلیل اینکه بخش شماره‌حساب در شناسه شبا، ۱۸ رقم است. به‌جای دو رقم باقی‌مانده باید 0 اضافه شود.
				 *
				 * @source https://virgool.io/@vahidnasabi/sepah-sheba-tgwbjbfi0daj
				 */

				return [
					'bankname' => static::$banks['sepah'],
					'country'  => 'IR',
					'bank'     => 'sepah',
					'lookup'   => FALSE,
					'account'  => $standard,
					'national' => TRUE,
				];

			case '017':

				/**
				 * | A1 | A2 | A3 | B1 | B2 | 0 | 0 | 0 | B19 |
				 * |----|----|----|----|----|---|---|---|-----|
				 * | 1  | 2  | 3  | 4  | 5  | 0 | 0 | 0 | 22  |
				 *
				 * - شناسه بانک، که در موقعیت A1 الی A3 به طول سه رقم قرار می گیرد. شناسه بانک یک عدد سه رقمی است که براساس کد بانکها تدوین شده است. باتوجه به این که این کدها درحال حاضر دو رقمی هستند، تا اطلاع ثانوی از صفر در سمت چپ این کد برای تمام بانکها و موسسات اعتباری استفاده شده است.
				 * - شناسه حساب که در موقعیت B1 الی B19 قرار می گیرد. باتوجه به اینکه شماره حسابهای فعلی بانک ملی (سیستم متمرکز- سیبا) 13 رقمی می باشد، با استفاده از راهکارهای ذیل به قالب 19رقمی تبدیل می شود.
				 * - شناسه نوع حساب که در موقعیت B1 قرار می گیرد برای حسابهای سپرده، مقداد صفر و برای حسابهای تسهیلات عدد یک تکمیل می شود.
				 * - برای موقعیتهای B2 الی B19 نیز، به سمت چپ شماره حساب باید به اندازه ای رقم صفر افزوده شود که طول آن برابر 18 رقم شود. بدین ترتیب با ترکیب عدد 18 رقمی حاصله و کد نوع حساب (بند الف)، شناسه حساب (BBAN) به طول 19 رقم تولید می شود.
				 *
				 * @source https://bmi.ir/fa/pages/192/
				 */

				return [
					'bankname' => static::$banks['melli'],
					'country'  => 'IR',
					'bank'     => 'melli',
					'lookup'   => 'https://bmi.ir/fa/sheba/iban/',
					// 'account'  => substr( $account, -13 ),
					'account'  => $standard,
					'national' => TRUE,
				];


			case '057':

				/***
				process(str: string): ShebaProcess {
					str = str.substring(7);
					while (str[0] === "0") {
						str = str.substring(1);
					}
					str = str.substr(0, str.length - 2);
					const formatted =
						str.substr(0, 3) + "-" + str.substr(3, 3) + "-" + str.substr(6, 8) + "-" + str.substr(14, 1);

					return {
						normal: str,
						formatted: formatted,
					};
				},
				*/

				return [
					'bankname' => static::$banks['pasargad'],
					'country'  => 'IR',
					'bank'     => 'pasargad',
					'lookup'   => 'https://vbank.bpi.ir/public/inquiries/iban-to-deposit',
					'account'  => '', // FIXME
					// 'account'  => Core\Text::leftTrim( substr( $account, 1 ), '0' ),
				];

			case '012':

				/**
				 * ۱۹ رقم سمت راست این شماره، شماره حساب شماست
				 * باقی اعداد به ترتیب از چپ به این شرح هستند: ارقام کنترل کننده صحت یا همان Check Digit
				 * کد بانک که برای بانک ملت 012 است
				 * نوع حساب هم در شماره بعدی مشخص است
				 * شماره حساب بانک ملت عددی ۱۰ رقمی است که این رقم ۱۰ رقم آخر شماره شبای بانکی شما را تشکیل می دهد.
				 *
				 * @source https://vrgl.ir/GLJLi
				 */

				return [
					'bankname' => static::$banks['mellat'],
					'country'  => 'IR',
					'bank'     => 'mellat',
					'lookup'   => FALSE,
					'account'  => substr( $account, -10 ),
					// 'account'  => $standard,
				];

			case '055':

				return [
					'bankname' => static::$banks['eghtesad-novin'],
					'country'  => 'IR',
					'bank'     => 'eghtesad-novin',
					'lookup'   => 'https://apps.enbank.ir/iban/',
					'account'  => $standard,
				];

			case '054':

				/***
				process(str: string): ShebaProcess {
					str = str.substring(14);
					const formatted = "0" + str.substr(0, 2) + "-0" + str.substr(2, 7) + "-" + str.substr(9, 3);

					return {
						normal: str,
						formatted: formatted,
					};
				},
				*/

				return [
					'bankname' => static::$banks['parsian'],
					'country'  => 'IR',
					'bank'     => 'parsian',
					'lookup'   => 'https://www.parsian-bank.ir/web_directory/64030',
					'account'  => $standard,
				];

			case '021':

				return [
					'bankname' => static::$banks['post'],
					'country'  => 'IR',
					'bank'     => 'post',
					'lookup'   => 'https://oib.postbank.ir/ib/ibangen.aspx',
					'account'  => $standard,
				];

			case '018':

				return [
					'bankname' => static::$banks['tejarat'],
					'country'  => 'IR',
					'bank'     => 'tejarat',
					'lookup'   => 'https://www.tejaratbank.ir/web_directory/1835-Get-the-sheba-code.html',
					'account'  => $standard,
				];

			case '020':

				return [
					'bankname' => static::$banks['tosee-saderat'],
					'country'  => 'IR',
					'bank'     => 'tosee-saderat',
					'lookup'   => 'https://mbn.edbi.ir/mbackend/#/sheba',
					'account'  => $standard,
					'national' => TRUE,
				];

			case '013':

				return [
					'bankname' => static::$banks['refah'],
					'country'  => 'IR',
					'bank'     => 'refah',
					'lookup'   => 'https://gsh.rb24.ir/sheba_new.aspx',
					'account'  => $standard,
				];

			case '056':

				return [
					'bankname' => static::$banks['saman'],
					'country'  => 'IR',
					'bank'     => 'saman',
					'lookup'   => 'https://www.sb24.ir/e-services/assistant/sheba',
					'account'  => $standard,
				];

			case '058':

				return [
					'bankname' => static::$banks['sarmayeh'],
					'country'  => 'IR',
					'bank'     => 'sarmayeh',
					'lookup'   => 'https://www.sbank.ir/services/ibna/add/_sub_menu_/0/_menu_/0/',
					'account'  => $standard,
				];

			case '019':

				return [
					'bankname' => static::$banks['saderat'],
					'country'  => 'IR',
					'bank'     => 'saderat',
					'lookup'   => 'https://www.bsi.ir/Pages/sheba.aspx',
					'account'  => $standard,
				];

			case '011':

				return [
					'bankname' => static::$banks['sanat-o-madan'],
					'country'  => 'IR',
					'bank'     => 'sanat-o-madan',
					'lookup'   => 'https://www.bim.ir/fa-IR/Portal/4971/',
					'account'  => $standard,
					'national' => TRUE,
				];

			case '053':

				/**
				 * 2 رقم بعدی کد کنترلی است و نهایتا 22 رقم آخر که شماره پایه حساب بانکی را تشکیل می دهد و شناسه بانک صاحب حساب جزئی (053 شناسه بانک کارآفرین که در ابتدای 22 رقم آخر قرار می گیرد) از این 22 رقم می باشد.
				 *
				 * @example `IR27053000000100324200001`
				 */

				return [
					'bankname' => static::$banks['karafarin'],
					'country'  => 'IR',
					'bank'     => 'karafarin',
					'lookup'   => 'https://www.karafarinbank.ir/fa/home/depositservices/estelamsheba',
					'account'  => $standard,
				];

			case '016':

				return [
					'bankname' => static::$banks['keshavarzi'],
					'country'  => 'IR',
					'bank'     => 'keshavarzi',
					'lookup'   => 'https://ib.bki.ir/pid40.lmx',
					'account'  => $standard,
					'national' => TRUE,
				];

			case '010':

				return [
					'bankname' => static::$banks['central-bank'],
					'country'  => 'IR',
					'bank'     => 'central-bank',
					'lookup'   => FALSE,
					'account'  => $standard,
				];

			case '014':

				return [
					'bankname' => static::$banks['maskan'],
					'country'  => 'IR',
					'bank'     => 'maskan',
					'lookup'   => 'https://ecounter.bank-maskan.ir/other-services/sheba-inquiry',
					'account'  => $standard,
					'national' => TRUE,
				];

			case '022':

				return [
					'bankname' => static::$banks['toose-taavon'],
					'country'  => 'IR',
					'bank'     => 'toose-taavon',
					'lookup'   => 'https://ttbank.ir/fa/page/100592',
					'account'  => $standard,
					'national' => TRUE,
				];

			case '051':

				return [
					'bankname' => static::$banks['tosee'],
					'country'  => 'IR',
					'bank'     => 'tosee',
					'lookup'   => FALSE,
					'account'  => $standard,
					'status'   => 'dissolved',
				];

			case '080':

				return [
					'bankname' => static::$banks['noor-bank'],
					'country'  => 'IR',
					'bank'     => 'noor-bank',
					'lookup'   => FALSE,
					'account'  => $standard,
					'status'   => 'dissolved',
				];

			case '059':

				return [
					'bankname' => static::$banks['sina'],
					'country'  => 'IR',
					'bank'     => 'sina',
					'lookup'   => 'https://www.sinabank.ir/web_directory/416',
					'account'  => $standard,
				];

			case '060':
			case '090': // WTF?!

				return [
					'bankname' => static::$banks['mehr-iran'],
					'country'  => 'IR',
					'bank'     => 'mehr-iran',
					'lookup'   => 'https://www.qmb.ir/Index.aspx?page_=form&lang=1&sub=0&tempname=Shaba&PageID=83&isPopUp=False',
					'account'  => $standard,
				];

			case '061':

				/***
					process(str: string): ShebaProcess {
						str = str.substring(7);
						while (str[0] === "0") {
							str = str.substring(1);
						}

						return {
							normal: str,
							formatted: str,
						};
					},
				*/

				return [
					'bankname' => static::$banks['shahr'],
					'country'  => 'IR',
					'bank'     => 'shahr',
					'lookup'   => 'https://www.shahr-bank.ir/web_directory/55265',
					'account'  => $standard,
				];

			case '062':

				return [
					'bankname' => static::$banks['ayandeh'],
					'country'  => 'IR',
					'bank'     => 'ayandeh',
					'lookup'   => 'https://ba24.ir/services/sheba',
					'account'  => $standard,
				];

			case '064':

				return [
					'bankname' => static::$banks['gardeshgari'],
					'country'  => 'IR',
					'bank'     => 'gardeshgari',
					'lookup'   => 'https://www.tourismbank.ir/fa/page/100963',
					'account'  => $standard,
				];

			case '066':

				return [
					'bankname' => static::$banks['dey'],
					'country'  => 'IR',
					'bank'     => 'dey',
					'lookup'   => 'https://day24.ir/%D8%AF%D8%B1%DB%8C%D8%A7%D9%81%D8%AA-%D8%B4%D8%A8%D8%A7-2',
					'account'  => $standard,
				];

			case '069':

				return [
					'bankname' => static::$banks['iran-zamin'],
					'country'  => 'IR',
					'bank'     => 'iran-zamin',
					'lookup'   => 'https://www.izbank.ir/fa/page/100723',
					'account'  => $standard,
				];

			case '070':

				return [
					'bankname' => static::$banks['resalat'],
					'country'  => 'IR',
					'bank'     => 'resalat',
					'lookup'   => 'https://www.rqbank.ir/%D9%85%D8%AD%D8%A7%D8%B3%D8%A8%D9%87-%D8%B4%D8%A8%D8%A7',
					'account'  => $standard,
				];

			case '075':

				return [
					'bankname' => static::$banks['melal'],
					'country'  => 'IR',
					'bank'     => 'melal',
					'lookup'   => 'https://melalbank.ir/sheba',
					'account'  => $standard,
				];

			case '078':

				return [
					'bankname' => static::$banks['middle-east-bank'],
					'country'  => 'IR',
					'bank'     => 'middle-east-bank',
					'lookup'   => 'https://www.middleeastbank.ir/page/IBAN',
					'account'  => $standard,
				];

			case '079':

				return [
					'bankname' => static::$banks['mehr-eqtesad'],
					'country'  => 'IR',
					'bank'     => 'mehr-eqtesad',
					'lookup'   => FALSE,
					'account'  => $standard,
					'status'   => 'merged',
				];

			case '073':

				return [
					'bankname' => static::$banks['kosar'],
					'country'  => 'IR',
					'bank'     => 'kosar',
					'lookup'   => FALSE,
					'account'  => $standard,
					'status'   => 'merged',
				];

			case '065':

				return [
					'bankname' => static::$banks['hekmat-iranian'],
					'country'  => 'IR',
					'bank'     => 'hekmat-iranian',
					'lookup'   => FALSE,
					'account'  => $standard,
					'status'   => 'merged',
				];

			case '063':

				return [
					'bankname' => static::$banks['ansar'],
					'country'  => 'IR',
					'bank'     => 'ansar',
					'lookup'   => FALSE,
					'account'  => $standard,
					'status'   => 'merged',
				];

			case '052':

				return [
					'bankname' => static::$banks['ghavamin'],
					'country'  => 'IR',
					'bank'     => 'ghavamin',
					'lookup'   => FALSE,
					'account'  => $standard,
					'status'   => 'merged',
				];

			case '095':

				return [
					'bankname' => static::$banks['iran-venezuela'],
					'country'  => 'IR',
					'bank'     => 'iran-venezuela',
					'lookup'   => 'https://www.ivbb.ir/fa-IR/DouranPortal/5167',
					'account'  => $standard,
				];
		}

		return $fallback;
	}
}
