<?php namespace geminorum\gEditorial\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class DateFormat extends Core\Base
{
	const PHP_TIME_TO_ISO = [
		'%a' => 'ccc',
		'%A' => 'cccc',
		'%d' => 'dd',
		'%e' => 'd',
		'%j' => 'D',
		'%u' => 'e',// not 100% correct
		'%w' => 'c',// not 100% correct
		'%U' => 'w',
		'%V' => 'ww',// not 100% correct
		'%W' => 'w',// not 100% correct
		'%b' => 'LLL',
		'%B' => 'LLLL',
		'%h' => 'LLL',// alias of %b
		'%m' => 'LL',
		'%C' => '\'{{century}}\'',// no replace for this
		'%g' => 'yy',// no replace for this
		'%G' => 'Y',// not 100% correct
		'%y' => 'yy',
		'%Y' => 'yyyy',
		'%H' => 'HH',
		'%k' => 'H',
		'%I' => 'hh',
		'%l' => 'h',
		'%M' => 'mm',
		'%p' => 'a',
		'%P' => 'a',// no replace for this
		'%r' => 'hh:mm:ss a',
		'%R' => 'HH:mm',
		'%S' => 'ss',
		'%T' => 'HH:mm:ss',
		'%X' => 'HH:mm:ss',// no replace for this
		'%z' => 'ZZ',
		'%Z' => 'zzz',// no replace for this
		'%c' => 'ccc d LLL YYYY HH:mm:ss zzz',// Buddhist era not converted.
		'%D' => 'MM/dd/yy',
		'%F' => 'yyyy-MM-dd',
		'%s' => '\'{{timestamp}}\'',// no replace for this
		'%x' => 'd.MM.yyyy',// Buddhist era not converted.
		'%n' => "\n",
		'%t' => "\t",
		'%%' => '%',
	];

	/**
	 * Converts `strftime()` format to `IntlDateFormatter` pattern.
	 * @source https://gist.github.com/ve3/a3b7924a85c3286b554f9e636b919883
	 *
	 * PHP `strftime()` is deprecated since v 8.1. They recommended to use `IntlDateFormatter()` instead.
	 * However, `IntlDateFormatter()` pattern does not fully supported all format that `strftime()` does.
	 *
	 * @param string $format
	 * @return string
	 */
	public static function strftime_format_to_intl_pattern( string $format )
	{
		$pattern = $format;

		// Replace 1 single quote that is not following visible character or single quote and not follow by single quote or word or number.
		// Example: '
		// replace with 2 single quotes. Example: ''
		$pattern = preg_replace( '/(?<![\'\S])(\')(?![\'\w])/u', "'$1", $pattern );

		// Replace 1 single quote that is not following visible character or single quote and follow by word.
		// Example: 'xx
		// replace with 2 single quotes. Example: ''xx
		$pattern = preg_replace( '/(?<![\'\S])(\')(\w+)/u', "'$1$2", $pattern );

		// Replace 1 single quote that is following word (a-z 0-9) and not follow by single quote.
		// Example: xx'
		// replace with 2 single quotes. Example: xx''
		$pattern = preg_replace( '/([\w]+)(\')(?!\')/u', "$1'$2", $pattern );

		// Replace a-z (include upper case) that is not following %. Example xxx.
		// Replace with wrap single quote. Example: 'xxx'.
		$pattern = preg_replace( '/(?<![%a-zA-Z])([a-zA-Z]+)/u', "'$1$2'", $pattern );

		// Escape %%x with '%%x'.
		$pattern = preg_replace( '/(%%[a-zA-Z]+)/u', "'$1'", $pattern );

		foreach ( static::PHP_TIME_TO_ISO as $strftime => $intl )
			$pattern = preg_replace( '/(?<!%)('.$strftime.')/u', $intl, $pattern );

		return $pattern;
	}

	/**
	 * Converts `strftime` format to PHP date format.
	 * @source https://stackoverflow.com/a/62781773
	 *
	 * It is important to note that some do not translate accurately
	 * i.e. lowercase `L` is supposed to convert to number with a preceding
	 * space if it is under `10`, there is no accurate conversion so we just use `g`.
	 *
	 * @param string $format
	 * @return string
	 * @throws Exception
	 */
	public static function strftime_format_to_date_format( string $format )
	{
		$unsupported = [
			'%U',
			'%V',
			'%C',
			'%g',
			'%G',
		];

		$found = [];

		foreach ( $unsupported as $unsup )
			if ( FALSE !== strpos( $format, $unsup ) )
				$found[] = $unsup;

		if ( ! empty( $found ) )
			throw new \Exception( "Found these unsupported chars: ".implode( ',', $found ).' in '.$format );

		return str_replace( [
			'%a',
			'%A',
			'%d',
			'%e',
			'%u',
			'%w',
			'%W',
			'%b',
			'%h',
			'%B',
			'%m',
			'%y',
			'%Y',
			'%D',
			'%F',
			'%x',
			'%n',
			'%t',
			'%H',
			'%k',
			'%I',
			'%l',
			'%M',
			'%p',
			'%P',
			'%r',  // `%I:%M:%S %p`
			'%R',  // `%H:%M`
			'%S',
			'%T',  // `%H:%M:%S`
			'%X',
			'%z',
			'%Z',
			'%c',
			'%s',
			'%%',
		], [
			'D',
			'l',
			'd',
			'j',
			'N',
			'w',
			'W',
			'M',
			'M',
			'F',
			'm',
			'y',
			'Y',
			'm/d/y',
			'Y-m-d',
			'm/d/y',
			"\n",
			"\t",
			'H',
			'G',
			'h',
			'g',
			'i',
			'A',
			'a',
			'h:i:s A',
			'H:i',
			's',
			'H:i:s',
			'H:i:s',
			'O',
			'T',
			'D M j H:i:s Y', // `Tue Feb 5 00:45:10 2009`
			'U',
			'%',
		], $format );
	}
}
