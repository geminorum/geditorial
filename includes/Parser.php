<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Parser extends WordPress\Main
{

	const BASE = 'geditorial';

	/**
	 * Data parsing is the process of extracting relevant information
	 * from unstructured datasources and transforming it into a structured
	 * format that can be easily analyzed. A data parser is a software
	 * program or tool used to automate this process.
	 *
	 * @source https://www.bureauworks.com/blog/what-is-data-parsing
	 */

	/**
	 * What's the difference between a 'tokenizer', 'lexer' and 'parser'?
	 *
	 * - A tokenizer just splits text into smaller units such as words.
	 * Tokenizing into letters, syllables, sentences etc. is also possible.
	 *
	 * - A lexer does the same plus attaches extra information to each token.
	 * If we tokenize into words, a lexer would attach tags like number, word,
	 * punctuation etc.
	 *
	 * - A parser usually uses the output of a lexer and constructs a parse tree.
	 *
	 * @source https://www.quora.com/Whats-the-difference-between-a-tokenizer-lexer-and-parser
	 */

	public static function factory()
	{
		return gEditorial();
	}

	// public static function attachment( $post, $mimetype = NULL ) {}
	// public static function file( $path, $mimetype = NULL ) {}

	// OLD: `Helper::parseCSV()`
	public static function fromCSV( $path, $atts = [] )
	{
		$args = self::atts( [
			'headers'     => FALSE,   // headers only
			'mapping'     => NULL,
			'sheet_name'  => NULL,
			'sheet_index' => 0,       // starts @ `0`
			'extra_url'   => FALSE,   // Returns full URL to the file.
			'extra_size'  => FALSE,   // Returns file size of the file.
		], $atts );

		$data = [
			'file_path' => Core\File::normalize( $path ),
			'file_ext'  => 'csv',

			'readable'    => FALSE,   // initial
			'file_url'    => NULL,
			'file_size'   => NULL,
			'sheet_name'  => NULL,
			'sheet_index' => NULL,
			'headers'     => NULL,
			'items'       => [],      // starts @ `1`
		];

		if ( ! Core\File::readable( $data['file_path'] ) )
			return $data;

		$data['readable'] = TRUE;

		if ( $args['extra_url'] )
			$data['file_url'] = Core\URL::fromPath( $data['file_path'] );

		if ( $args['extra_size'] )
			$data['file_size'] = Core\File::getSize( $data['file_path'] );

		/**
		 * @package `jwage/easy-csv`
		 * @link https://github.com/jwage/easy-csv
		 */
		$reader = new \EasyCSV\Reader( $data['file_path'] );

		$data['headers'] = $reader->getHeaders();

		if ( $args['headers'] )
			return $data;

		if ( is_null( $args['mapping'] ) ) {

			$data['items'] = $reader->getAll();

		} else {

			while ( $raw = $reader->getRow() )
				$data['items'][$reader->getLineNumber()-1] = Core\Arraay::reKeyByMap_ALT( $raw, $args['mapping'] );
		}

		// FIXME: close the file stream

		return $data;
	}

	// NOTE: DEPRECATED: migrate to: https://github.com/jwage/easy-csv
	// OLD: `Helper::parseCSV_Legacy()`
	public static function fromCSV_Legacy( $file_path, $limit = NULL )
	{
		if ( empty( $file_path ) )
			return FALSE;

		// $iterator = new \SplFileObject( Core\File::normalize( $file_path ) );
		// $parser   = new \KzykHys\CsvParser\CsvParser( $iterator, [ 'encoding' => 'UTF-8' ] );

		$list = [];
		$args = [ 'encoding' => 'UTF-8' ];

		if ( ! is_null( $limit ) )
			$args['limit'] =  (int) $limit;

		// @REF: https://github.com/kzykhys/PHPCsvParser
		$parser  = \KzykHys\CsvParser\CsvParser::fromFile( Core\File::normalize( $file_path ), $args );
		$items   = $parser->parse();
		$headers = $items[0];

		unset( $parser, $items[0] );

		foreach ( $items as $index => $data )
			if ( ! empty( $data ) )
				$list[] = array_combine( $headers, $data );

		unset( $headers, $items );

		return $list;
	}

	// OLD: `Helper::parseXLSX()`
	public static function fromXLSX( $path, $atts = [] )
	{
		$args = self::atts( [
			'headers'     => FALSE,   // headers only
			'mapping'     => NULL,
			'sheet_name'  => NULL,
			'sheet_index' => 0,       // starts @ `0`
			'extra_url'   => FALSE,   // Returns full URL to the file.
			'extra_size'  => FALSE,   // Returns file size of the file.
		], $atts );

		$data = [
			'file_path' => Core\File::normalize( $path ),
			'file_ext'  => 'xlsx',

			'readable'    => FALSE,   // initial
			'file_url'    => NULL,
			'file_size'   => NULL,
			'sheet_name'  => NULL,
			'sheet_index' => NULL,
			'headers'     => NULL,
			'items'       => [],      // starts @ `1`
		];

		if ( ! Core\File::readable( $data['file_path'] ) )
			return $data;

		$data['readable'] = TRUE;

		if ( $args['extra_url'] )
			$data['file_url'] = Core\URL::fromPath( $data['file_path'] );

		if ( $args['extra_size'] )
			$data['file_size'] = Core\File::getSize( $data['file_path'] );

		/**
		 * @package `openspout/openspout`
		 * @link https://opensource.box.com/spout/docs/
		 * @link https://github.com/openspout/openspout/blob/3.x/docs/index.md
		 */
		$reader = \OpenSpout\Reader\Common\Creator\ReaderEntityFactory::createXLSXReader();
		$reader->open( $data['file_path'] );

		foreach ( $reader->getSheetIterator() as $sheet ) {

			$sheet_name  = $sheet->getName();
			$sheet_index = $sheet->getIndex();

			if ( $args['sheet_name'] ) {

				if ( (string) $args['sheet_name'] !== (string) $sheet_name )
					continue;

			} else if ( FALSE !== $args['sheet_index'] ) {

				if ( (int) $args['sheet_index'] !== $sheet_index )
					continue;
			}

			$data['sheet_name']  = $sheet_name;
			$data['sheet_index'] = $sheet_index;

			foreach ( $sheet->getRowIterator() as $index => $row ) {

				if ( 1 === $index ) {
					$data['headers'] = $row->toArray();

					if ( $args['headers'] )
						break;

					continue;
				}

				$raw = array_combine( $data['headers'], $row->toArray() );

				if ( is_null( $args['mapping'] ) )
					$data['items'][$index] = $raw;

				else
					$data['items'][$index] = Core\Arraay::reKeyByMap_ALT(
						$raw,
						$args['mapping']
					);
			}

			break; // no need to do iterate through other sheets
		}

		$reader->close();

		return $data;
	}

	// FIXME: migrate!
	public static function fromTXT( $file_path, $atts = [] )
	{
		$data = [
			'items' => self::fromTXT_Legacy( $file_path ),
		];

		return $data;
	}

	public static function fromTXT_Legacy( $file_path )
	{
		return Core\Text::splitLines( Core\File::getContents( $file_path ) );
	}

	// FIXME: migrate!
	public static function fromJSON( $file_path, $atts = [] )
	{
		$data = [
			'items' => self::fromJSON_Legacy( $file_path ),
		];

		return $data;
	}

	// OLD: `Helper::parseJSON()`
	public static function fromJSON_Legacy( $file_path )
	{
		if ( empty( $file_path ) )
			return FALSE;

		return json_decode( Core\File::getContents( $file_path ), TRUE );
	}

	// FIXME: migrate!
	public static function fromXML( $file_path, $atts = [] )
	{
		$data = [
			'items' => self::fromXML_Legacy( $file_path ),
		];

		return $data;
	}

	// OLD: `Helper::parseXML()`
	public static function fromXML_Legacy( $file_path )
	{
		if ( empty( $file_path ) || ! function_exists( 'xml_parser_create' ) )
			return FALSE;

		if ( ! $contents = Core\File::getContents( $file_path ) )
			return $contents;

		$parser = xml_parser_create();

		xml_parse_into_struct( $parser, $contents, $values, $index );

		// `xml_parser_free()` has no effect as of PHP 8.0.0
		if ( PHP_VERSION_ID < 80000 )
			xml_parser_free( $parser );

		return [ $values, $index ];
	}

	/**
	 * Generates simple `XLSX` data string from given data.
	 * @package `maksimovic/php-xlsx-writer`
	 * @link: https://github.com/maksimovic/PHP_XLSXWriter
	 *
	 * OLD: `Helper::generateXLSX()`
	 *
	 * @param array $data
	 * @param array $headers
	 * @param string $sheet
	 * @param array $widths
	 * @param array $options
	 * @param array $styles
	 * @param string $title
	 * @param string $description
	 * @return string
	 */
	public static function toXLSX_Legacy( $data, $headers = [], $sheet = NULL, $widths = NULL, $options = NULL, $styles = NULL, $title = NULL, $description = NULL )
	{
		$writer = new \XLSXWriter();
		$writer->setTempDir( get_temp_dir() );

		if ( Core\L10n::rtl() )
			$writer->setRightToLeft( TRUE );

		if ( ! is_null( $title ) )
			$writer->setTitle( $title );

		if ( ! is_null( $description ) )
			$writer->setDescription( $description );

		if ( $user = gEditorial()->user() )
			$writer->setAuthor( get_userdata( $user )->display_name );

		$sheet = $sheet ?? 'Sheet1';

		if ( is_null( $options ) )
			$options = [
				'border'      => 'left,right,top,bottom',
				'fill'        => '#eee',
				'font'        => 'Arial,Tahoma,sans-serif',
				'font-style'  => 'bold',
				'freeze_rows' => TRUE,
				'widths'      => array_fill( 0, count( $headers ), 20 ),
			];

		if ( ! is_null( $widths ) )
			foreach ( $widths as $offset => $width )
				$options['widths'][$offset] = $width + 2; // override all with padding

		if ( is_null( $styles ) )
			$styles = [
				'border'    => 'left,right,top,bottom',
				'font'      => 'Segoe UI,Tahoma,sans-serif',
				'font-size' => 10,
			];

		if ( Core\Arraay::isList( $headers ) )
			$headers = array_combine( $headers, array_fill( 0, count( $headers ), 'string' ) );

		$writer->writeSheetHeader( $sheet, $headers, $options );

		foreach ( $data as $row )
			$writer->writeSheetRow( $sheet, $row, $styles );

		return $writer->writeToString();
	}

	/**
	 * Check if a CSV file is valid.
	 * @source `wc_is_file_valid_csv()`
	 *
	 * @param string $file
	 * @param bool $check_path
	 * @return bool
	 */
	public static function fileIsValid_CSV( $file, $check_path = TRUE )
	{
		if ( $check_path && ! Core\Text::has( $file, '://' ) )
			return FALSE;

		$mimetypes = apply_filters( sprintf( '%s_%s_csv_valid_filetypes', static::BASE, 'parser' ), [
			'csv' => 'text/csv',
			'txt' => 'text/plain',
		] );

		$filetype = wp_check_filetype( $file, $mimetypes );

		return in_array( $filetype['type'], $mimetypes, TRUE );
	}

}
