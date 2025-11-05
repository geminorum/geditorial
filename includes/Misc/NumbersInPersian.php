<?php namespace geminorum\gEditorial\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class NumbersInPersian extends Core\Base
{
	// TODO: convert to static class

	// @REF: https://camelcase.ir/?p=2151
	// @SEE: https://snippets.ir/1331/convert-number-to-words-in-php.html

	protected $digit1 = [
		0 => 'صفر',
		1 => 'یک',
		2 => 'دو',
		3 => 'سه',
		4 => 'چهار',
		5 => 'پنج',
		6 => 'شش',
		7 => 'هفت',
		8 => 'هشت',
		9 => 'نه',
	];

	protected $digit1_5 = [
		0 => 'ده',
		1 => 'یازده',
		2 => 'دوازده',
		3 => 'سیزده',
		4 => 'چهارده',
		5 => 'پانزده',
		6 => 'شانزده',
		7 => 'هفده',
		8 => 'هجده',
		9 => 'نوزده',
	];

	protected $digit2 = [
		1 => 'ده',
		2 => 'بیست',
		3 => 'سی',
		4 => 'چهل',
		5 => 'پنجاه',
		6 => 'شصت',
		7 => 'هفتاد',
		8 => 'هشتاد',
		9 => 'نود'
	];

	protected $digit3 = [
		1 => 'صد',
		2 => 'دویست',
		3 => 'سیصد',
		4 => 'چهارصد',
		5 => 'پانصد',
		6 => 'ششصد',
		7 => 'هفتصد',
		8 => 'هشتصد',
		9 => 'نهصد',
	];

	protected $steps = [
		1  => 'هزار',
		2  => 'میلیون',
		3  => 'بیلیون',
		4  => 'تریلیون',
		5  => 'کادریلیون',
		6  => 'کوینتریلیون',
		7  => 'سکستریلیون',
		8  => 'سپتریلیون',
		9  => 'اکتریلیون',
		10 => 'نونیلیون',
		11 => 'دسیلیون',
	];

	protected $misc = [
		'zero'    => 'صفر',
		'first'   => 'اول',
		'one'     => 'یکم',
		'ordinal' => 'م',
		'and'     => 'و',
	];

	protected $words_replace = [
		' و صفر' => '',
	];

	protected $ordinal_replace = [
		'سهم' => 'سوم',
		'سیم' => 'سی‌ام',
	];

	public function number_format( $number, $precision = 0, $decimals_separator = '.', $thousands_separator = ',' )
	{
		$number    = explode( '.', str_replace( ' ', '', $number ) );
		$number[0] = str_split( strrev( $number[0] ), 3 );
		$segments  = count( $number[0] );

		for ( $i = 0; $i < $segments; $i++ )
			$number[0][$i] = strrev( $number[0][$i] );

		$number[0] = implode( $thousands_separator, array_reverse( $number[0] ) );

		if ( ! empty( $number[1] ) )
			$number[1] = round( $number[1], $precision );

		return implode( $decimals_separator, $number );
	}

	protected function groupToWords( $group )
	{
		$d3 = floor( $group / 100 );
		$d2 = floor( ( $group - $d3 * 100 ) / 10 );
		$d1 = $group - $d3 * 100 - $d2 * 10;

		$array = [];

		if ( $d3 != 0 )
			$array[] = $this->digit3[$d3];

		if ( $d2 == 1 && $d1 != 0 ) { // 11-19

			$array[] = $this->digit1_5[$d1];

		} else if ( $d2 != 0 && $d1 == 0 ) { // 10-20-...-90

			$array[] = $this->digit2[$d2];

		} else if ( $d2 == 0 && $d1 == 0 ) { // 00

		} else if ( $d2 == 0 && $d1 != 0 ) { // 1-9

			$array[] = $this->digit1[$d1];

		} else { // Others

			$array[] = $this->digit2[$d2];
			$array[] = $this->digit1[$d1];
		}

		return count( $array ) ? $array : FALSE;
	}

	public function number_to_words( $number, $and_with_zwnj = FALSE )
	{
		if ( 0 === $number || 0.0 === $number || '0' === $number || empty( $number ) )
			return $this->misc['zero'];

		$formated = $this->number_format( $number, 0, '.', ',' );
		$join     = $and_with_zwnj ? ( '‌'.$this->misc['and'].'‌' ) : ( ' '.$this->misc['and'].' ' );

		$groups = explode( ',', $formated );
		$steps  = count( $groups );
		$parts  = [];

		foreach ( $groups as $step => $group ) {

			if ( $group_words = self::groupToWords( $group ) ) {

				$part = implode( $join, $group_words );

				if ( isset( $this->steps[$steps - $step - 1] ) )
					$part.= ' '.$this->steps[$steps - $step - 1];

				$parts[] = $part;
			}
		}

		return str_replace(
			array_keys( $this->words_replace ),
			array_values( $this->words_replace ),
			implode( $join, $parts )
		);
	}

	public function number_to_ordinal( $number, $and_with_zwnj = FALSE )
	{
		if ( 0 == $number )
			return $this->misc['zero'];

		if ( 1 == $number )
			return $this->misc['first'];

		return str_replace(
			array_keys( $this->ordinal_replace ),
			array_values( $this->ordinal_replace ),
			sprintf( '%s%s', $this->number_to_words( $number, $and_with_zwnj ), $this->misc['ordinal'] )
		);
	}

	// usually used for replace
	public function get_range_ordinal_reverse( $from, $to, $step = 1, $include_one = TRUE )
	{
		$list = [];

		if ( $include_one )
			$list[$this->misc['one']] = 1;

		foreach ( range( $from, $to, $step ) as $number ) {
			$ordinal= $this->number_to_ordinal( $number );
			$list[$ordinal] = $number;
		}

		return array_reverse( $list );
	}

	public function test( $max = 1000, $start = 0 )
	{
		echo '<ol>';

		for ( $start = 0; $start < $max; $start++ ) {

			$number = random_int( $start, $max * $start );

			echo '<li>'.$this->number_to_words( $number ).' &mdash; '.$this->number_to_ordinal( $number ).' &mdash; '.$number.'</li>';
		}

		echo '</ol>';
	}

	/**
	 * Converts back occurrences of the ordinals to numerals.
	 *
	 * @param string $input
	 * @param int $range
	 * @return string
	 */
	public static function textOrdinalToNumbers( $input, $range = 100 )
	{
		$instance = new static();
		$data     = sprintf( ' %s ', $input ); // padding with space
		$template = '/[\s,،|\\/](%s|%s)[\s,،|\\/]/mu';
		// $template = '/[\s,،|](%s|%s)[\s,،|]/mu';

		foreach ( $instance->get_range_ordinal_reverse( 1, $range ) as $ordinal => $index ) {

			$pattern = sprintf( $template,
				preg_quote( $ordinal ),
				preg_quote( str_ireplace( ' ', '', $ordinal ) )
			);

			$data = preg_replace_callback( $pattern,
				static function ( $matches ) use ( $index ) {
					return sprintf( ' %s ', $index ); // padding with space
				}, $data );
		}

		return trim( $data );
	}

	public static function numberToOrdinal( $number )
	{
		$instance = new static();
		return $instance->number_to_ordinal( $number );
	}

	public static function numberToWords( $number )
	{
		$instance = new static();
		return $instance->number_to_words( $number );
	}
}
