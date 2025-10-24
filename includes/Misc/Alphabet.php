<?php namespace geminorum\gEditorial\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Alphabet extends Core\Base
{
	/**
	 * Retrieves the current locale.
	 *
	 * @param bool $site
	 * @return string
	 */
	public static function locale( $site = FALSE )
	{
		return $site ? get_locale() : determine_locale();
	}

	/**
	 * Retrieves the alphabet data given a locale.
	 * @old: `Core\L10n::getAlphabet()`
	 *
	 * @param string $locale
	 * @return array
	 */
	public static function get( $locale = NULL )
	{
		switch ( $locale ?? self::locale() ) {

			case 'fa':
			case 'fa_AF':
			case 'fa_IR':

				// @REF: [Help:IPA for Persian](https://en.wikipedia.org/wiki/Help:IPA_for_Persian)
				// @REF: [Persian alphabet](https://en.wikipedia.org/wiki/Persian_alphabet)
				return [
					// [ 'letter' => 'ء', 'key' => 'hamza', 'ipa' => '[ʔ]', 'name' => 'ء (همزه)' ],
					[ 'letter' => 'آ', 'key' => 'alef', 'ipa' => '[ɒ]', 'name' => 'الف', 'search' => [ 'آ', 'ا', 'ء', 'أ', 'إ' ] ],
					[ 'letter' => 'ب', 'key' => 'be', 'ipa' => '[b]', 'name' => 'بِ' ],
					[ 'letter' => 'پ', 'key' => 'pe', 'ipa' => '[p]', 'name' => 'پِ' ],
					[ 'letter' => 'ت', 'key' => 'te', 'ipa' => '[t]', 'name' => 'تِ' ],
					[ 'letter' => 'ث', 'key' => 'se', 'ipa' => '[s]', 'name' => 'ثِ' ],
					[ 'letter' => 'ج', 'key' => 'jim', 'ipa' => '[d͡ʒ]', 'name' => 'جیم' ],
					[ 'letter' => 'چ', 'key' => 'che', 'ipa' => '[t͡ʃ]', 'name' => 'چِ' ],
					[ 'letter' => 'ح', 'key' => 'he_jimi', 'ipa' => '[h]', 'name' => 'حِ' ],
					[ 'letter' => 'خ', 'key' => 'khe', 'ipa' => '[x]', 'name' => 'خِ' ],
					[ 'letter' => 'د', 'key' => 'dal', 'ipa' => '[d]', 'name' => 'دال' ],
					[ 'letter' => 'ذ', 'key' => 'zal', 'ipa' => '[z]', 'name' => 'ذال' ],
					[ 'letter' => 'ر', 'key' => 're', 'ipa' => '[ɾ]', 'name' => 'ر' ],
					[ 'letter' => 'ز', 'key' => 'ze', 'ipa' => '[z]', 'name' => 'زِ' ],
					[ 'letter' => 'ژ', 'key' => 'je', 'ipa' => '[ʒ]', 'name' => 'ژِ' ],
					[ 'letter' => 'س', 'key' => 'sin', 'ipa' => '[s]', 'name' => 'سین' ],
					[ 'letter' => 'ش', 'key' => 'shin', 'ipa' => '[ʃ]', 'name' => 'شین' ],
					[ 'letter' => 'ص', 'key' => 'sad', 'ipa' => '[s]', 'name' => 'صاد' ],
					[ 'letter' => 'ض', 'key' => 'zad', 'ipa' => '[z]', 'name' => 'ضاد' ],
					[ 'letter' => 'ط', 'key' => 'ta', 'ipa' => '[t]', 'name' => 'طا' ],
					[ 'letter' => 'ظ', 'key' => 'za', 'ipa' => '[z]', 'name' => 'ظا' ],
					[ 'letter' => 'ع', 'key' => 'eyn', 'ipa' => '[ʔ]', 'name' => 'عین' ],
					[ 'letter' => 'غ', 'key' => 'qeyn', 'ipa' => '[ɣ] / [ɢ]', 'name' => 'غین' ],
					[ 'letter' => 'ف', 'key' => 'fe', 'ipa' => '[f]', 'name' => 'فِ' ],
					[ 'letter' => 'ق', 'key' => 'qaf', 'ipa' => '[ɢ] / [ɣ] / [q] (in some dialects)', 'name' => 'قاف' ],
					[ 'letter' => 'ک', 'key' => 'kaf', 'ipa' => '[k]', 'name' => 'کاف', 'search' => [ 'ك', 'ک' ] ],
					[ 'letter' => 'گ', 'key' => 'gaf', 'ipa' => '[ɡ]', 'name' => 'گاف' ],
					[ 'letter' => 'ل', 'key' => 'lam', 'ipa' => '[l]', 'name' => 'لام' ],
					[ 'letter' => 'م', 'key' => 'mim', 'ipa' => '[m]', 'name' => 'میم' ],
					[ 'letter' => 'ن', 'key' => 'nun', 'ipa' => '[n]', 'name' => 'نون' ],
					[ 'letter' => 'و', 'key' => 'vav', 'ipa' => '[v] / [uː] / [o] / [ow] / ([w] / [aw] / [oː] in Dari)', 'name' => 'واو' ],
					[ 'letter' => 'ه', 'key' => 'he_docesm', 'ipa' => '[h]', 'name' => 'هِ' ],
					[ 'letter' => 'ی', 'key' => 'ye', 'ipa' => '[j] / [i] / [ɒː] / ([aj] / [eː] in Dari)', 'name' => 'یِ', 'search' => [ 'ي', 'ی' ] ],
				];

			default:

				// @REF: [Help:IPA for English - Wikipedia](https://en.wikipedia.org/wiki/Help:IPA_for_English)
				// @REF: [English alphabet - Wikipedia](https://en.wikipedia.org/wiki/English_alphabet)
				return [
					[ 'letter' => 'A', 'key' => 'a', 'ipa' => '[ˈeɪ] / [æ]', 'name' => 'ā' ],
					[ 'letter' => 'B', 'key' => 'bee', 'ipa' => '[ˈbiː]', 'name' => 'bē' ],
					[ 'letter' => 'C', 'key' => 'cee', 'ipa' => '[ˈsiː]', 'name' => 'cē' ],
					[ 'letter' => 'D', 'key' => 'dee', 'ipa' => '[ˈdiː]', 'name' => 'dē' ],
					[ 'letter' => 'E', 'key' => 'e', 'ipa' => '[ˈiː]', 'name' => 'ē' ],
					[ 'letter' => 'F', 'key' => 'ef', 'ipa' => '[ˈɛf]', 'name' => 'ef' ],
					[ 'letter' => 'G', 'key' => 'gee', 'ipa' => '[ˈdʒiː]', 'name' => 'gē' ],
					[ 'letter' => 'H', 'key' => 'aitch', 'ipa' => '[ˈeɪtʃ] / [ˈheɪtʃ]', 'name' => 'hā' ],
					[ 'letter' => 'I', 'key' => 'i', 'ipa' => '[ˈaɪ]', 'name' => 'ī' ],
					[ 'letter' => 'J', 'key' => 'jay', 'ipa' => '[ˈdʒeɪ] / [ˈdʒaɪ]', 'name' => '' ],
					[ 'letter' => 'K', 'key' => 'kay', 'ipa' => '[ˈkeɪ]', 'name' => 'kā' ],
					[ 'letter' => 'L', 'key' => 'el', 'ipa' => '[ˈɛl]', 'name' => 'el' ],
					[ 'letter' => 'M', 'key' => 'em', 'ipa' => '[ˈɛm]', 'name' => 'em' ],
					[ 'letter' => 'N', 'key' => 'en', 'ipa' => '[ˈɛn]', 'name' => 'en' ],
					[ 'letter' => 'O', 'key' => 'o', 'ipa' => '[ˈoʊ]', 'name' => 'ō' ],
					[ 'letter' => 'P', 'key' => 'pee', 'ipa' => '[ˈpiː]', 'name' => 'pē' ],
					[ 'letter' => 'Q', 'key' => 'cue', 'ipa' => '[ˈkjuː]', 'name' => 'qū' ],
					[ 'letter' => 'R', 'key' => 'ar', 'ipa' => '[ˈɑːr] / [ˈɔːr]', 'name' => 'er' ],
					[ 'letter' => 'S', 'key' => 'ess', 'ipa' => '[ˈɛs]', 'name' => 'es' ],
					[ 'letter' => 'T', 'key' => 'tee', 'ipa' => '[ˈtiː]', 'name' => 'tē' ],
					[ 'letter' => 'U', 'key' => 'u', 'ipa' => '[ˈjuː]', 'name' => 'ū' ],
					[ 'letter' => 'V', 'key' => 'vee', 'ipa' => '[ˈviː]', 'name' => '' ],
					[ 'letter' => 'W', 'key' => 'double-u', 'ipa' => '[ˈdʌbəl.juː]', 'name' => '' ],
					[ 'letter' => 'X', 'key' => 'ex', 'ipa' => '[ˈɛks]', 'name' => 'ex' ],
					[ 'letter' => 'Y', 'key' => 'wy', 'ipa' => '[ˈwaɪ]', 'name' => 'hȳ' ],
					[ 'letter' => 'Z', 'key' => 'zed', 'ipa' => '[ˈzɛd]', 'name' => 'zēta' ],
				];
		}
	}

	/**
	 * Sorts the given array by value based on locale.
	 * @source https://stackoverflow.com/a/7096937
	 * @see `wp_list_sort()`
	 * @see `WP_List_Util::sort_callback()`
	 * @old: `Core\L10n::sortAlphabet()`
	 *
	 * @param array $array
	 * @param string|array $orderby
	 * @param string $order
	 * @param bool $preserve_keys
	 * @param string $locale
	 * @return array
	 */
	public static function sort( $array, $orderby = NULL, $order = 'ASC', $preserve_keys = FALSE, $locale = NULL )
	{
		if ( is_null( $orderby ) )
			return self::sortSimple( $array, $order, $preserve_keys, $locale );

		$alphabet = self::get( $locale );
		$letters  = Core\Arraay::column( $alphabet, 'letter' );
		$sort     = $preserve_keys ? 'uasort' : 'usort';

		if ( is_string( $orderby ) )
			$orderby = [ $orderby => $order ];

		foreach ( $orderby as $field => $direction )
			$orderby[$field] = 'DESC' === strtoupper( $direction ) ? 'DESC' : 'ASC';

		$sort( $array, static function ( $a, $b ) use ( $orderby, $alphabet, $letters ) {

			$a = (array) $a;
			$b = (array) $b;

			foreach ( $orderby as $field => $direction ) {

				if ( ! isset( $a[$field] ) || ! isset( $b[$field] ) )
					continue;

				if ( $a[$field] == $b[$field] )
					continue;

				$results = 'DESC' === $direction ? [ 1, -1 ] : [ -1, 1 ];

				if ( is_numeric( $a[$field] ) && is_numeric( $b[$field] ) )
					return ( $a[$field] < $b[$field] ) ? $results[0] : $results[1];

				$a_order = array_search( self::firstLetter( $a[$field], $alphabet ), $letters );
				$b_order = array_search( self::firstLetter( $b[$field], $alphabet ), $letters );

				// not in this locale
				if ( FALSE === $a_order || FALSE === $b_order )
					return 0 > strcmp( $a[$field], $b[$field] ) ? $results[0] : $results[1];

				if ( $a_order < $b_order )
					return $results[0];

				if ( $a_order > $b_order )
					return $results[1];
			}

			return 0;
		} );

		return $array;
	}

	// @old: `Core\L10n::sortAlphabetSimple()`
	public static function sortSimple( $array, $order = 'ASC', $preserve_keys = FALSE, $locale = NULL )
	{
		$alphabet = self::get( $locale );
		$letters  = Core\Arraay::column( $alphabet, 'letter' );
		$sort     = $preserve_keys ? 'uasort' : 'usort';

		$sort( $array, static function ( $a, $b ) use ( $order, $alphabet, $letters ) {

			$results = 'DESC' === strtoupper( $order ) ? [ 1, -1 ] : [ -1, 1 ];

			$a_order = array_search( self::firstLetter( $a, $alphabet ), $letters );
			$b_order = array_search( self::firstLetter( $b, $alphabet ), $letters );

			// not in this locale
			if ( FALSE === $a_order || FALSE === $b_order )
				return 0 > strcmp( $a, $b ) ? $results[0] : $results[1];

			if ( $a_order < $b_order )
				return $results[0];

			if ( $a_order > $b_order )
				return $results[1];

			return 0;
		} );

		return $array;
	}

	public static function firstLetter( $string, $alphabet, $alternative = FALSE )
	{
		$first = strtoupper( Core\Text::subStr( $string, 0, 1 ) );

		// NOTE: must be non-strict comparison
		if ( in_array( Core\Number::translate( $first ), range( 0, 9 ) ) )
			return '#';

		foreach ( Core\Arraay::column( $alphabet, 'search', 'letter' ) as $letter => $searchs )
			if ( FALSE !== array_search( $first, $searchs ) )
				return $letter;

		if ( ! $alternative )
			return $first;

		foreach ( Core\Arraay::column( $alternative, 'search', 'letter' ) as $letter => $searchs )
			if ( FALSE !== array_search( $first, $searchs ) )
				return $letter;

		return $first;
	}

	// @old: `Core\L10n::getAlphabetKeysByNumber()`
	public static function getKeysByNumber( $slice = FALSE, $locale = NULL )
	{
		switch ( $locale ?? self::locale() ) {

			case 'en':

				$alphabet = range( 'A', 'Z' );
				break;

			default:

				$alphabet = Core\Arraay::pluck( self::get( $locale ), 'letter' );
				break;
		}

		return $slice ? array_slice( $alphabet, 0, $slice ) : $alphabet;
	}
}
