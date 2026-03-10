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

	public static function getDefaultArgs( $filepath = NULL )
	{
		return [
			'headers'     => FALSE,   // headers only
			'first_row'   => FALSE,   // first row only
			'by_offset'   => FALSE,   // row by offset
			'mapping'     => NULL,
			'sheet_name'  => NULL,    // Excel
			'sheet_index' => 0,       // Excel: starts @ `0`
			'data_key'    => FALSE,   // JSON: the data key
			'delimiter'   => FALSE,   // CSV: custom delimiter
			'extra_url'   => FALSE,   // Returns full URL to the file.
			'extra_size'  => FALSE,   // Returns file size of the file.
			'extra_type'  => FALSE,   // Returns file ext/mime of the file.
			'keep_alive'  => FALSE,   // Whether to keep parser open for further queries? // TODO!
		];
	}

	public static function getInitialData( $args, $filepath = NULL )
	{
		return [
			'file_path'   => Core\File::normalize( $filepath ),
			'file_ext'    => NULL,
			'file_mime'   => NULL,
			'file_url'    => NULL,
			'file_size'   => NULL,
			'sheet_name'  => NULL,
			'sheet_index' => NULL,

			'error'   => FALSE,
			'total'   => 0,
			'headers' => [],
			'items'   => [],      // starts @ `1`
			'single'  => [],      // for single row queries
		];
	}

	public static function getAdditionalData( $data, $args, $filepath = NULL )
	{
		if ( $args['extra_url'] )
			$data['file_url'] = Core\URL::fromPath( $data['file_path'] );

		if ( $args['extra_size'] )
			$data['file_size'] = Core\File::getSize( $data['file_path'] );

		if ( $args['extra_type'] ) {
			$type = Core\File::type( $data['file_path'] );

			$data['file_ext']  = $type['ext'];
			$data['file_mime'] = $type['type'];
		}

		return $data;
	}

	// `public static function fromFile( $filepath, $arguments = NULL ) {}`

	public static function fromAttachment( $attachment, $arguments = [] )
	{
		if ( ! $post = WordPress\Post::get( $attachment ) )
			return self::bailWithError( [],
				'attachment_is_invalid',
				Plugin::invalid( FALSE )
			);

		if ( ! $filepath = get_attached_file( $post->ID ) )
			return self::bailWithError( [],
				'filepath_is_empty',
				Plugin::wrong( FALSE )
			);

		switch ( WordPress\Attachment::type( $post ) ) {

			case 'text/csv':
			case 'application/csv':
			case 'text/comma-separated-values':
				return self::fromCSV( $filepath, $arguments );

			case 'application/vnd.ms-excel':
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
				return self::fromXLSX( $filepath, $arguments );

			case 'text/json':
			case 'application/json':
				return self::fromJSON( $filepath, $arguments );

			case 'application/xml':
				return self::fromXML( $filepath, $arguments );

			case 'text/plain':
				return self::fromTXT( $filepath, $arguments );
		}

		return self::bailWithError( [],
			'mimetype_is_not_supported',
			Plugin::invalid( FALSE )
		);
	}

	public static function fromCSV_NEW( $filepath, $arguments = [] )
	{
		$args = self::parsed( self::getDefaultArgs( $filepath ), $arguments );
		$data = self::getInitialData( $args, $filepath );

		if ( ! Core\File::readable( $data['file_path'] ) )
			return self::bailWithError( $data,
				'file_not_readable',
				Plugin::notreadable( FALSE )
			);

		$data = self::getAdditionalData( $data, $args, $filepath );

		try {

			/**
			 * @package `league/csv`
			 * @source https://github.com/thephpleague/csv
			 * @link https://csv.thephpleague.com
			 */
			$parser = \League\Csv\Reader::from( $data['file_path'], 'r' );

			$parser->setHeaderOffset( 0 );   // Sets the CSV header offset.
			$parser->setEscape( '' );        // Required in PHP 8.4+ to avoid deprecation notices.

			if ( $args['delimiter'] )
				$parser->setDelimiter( $args['delimiter'] );

			if ( \League\Csv\Bom::tryFromSequence( $parser )->isUtf16() ?? FALSE )
				$parser->appendStreamFilterOnRead( 'convert.iconv.UTF-16/UTF-8' );

			$data['headers'] = $parser->getHeader();

			if ( empty( $data['headers'] ) )
				return self::bailWithError( $data,
					'file_is_empty',
					Plugin::noinfo( FALSE )
				);

			if ( $args['headers'] )
				return $data;

			if ( $args['by_offset'] ) {

			} else if ( $args['first_row'] ) {

			} else if ( is_null( $args['mapping'] ) ) {

			} else {

			}

		} catch ( \Exception | \Error | \RuntimeException $e ) {

			return self::bailWithError( $data,
				'exception_occurred',
				$e->getMessage()
			);
		}

		$data['total'] = count( $data['items'] ); // TODO: better to get from parser

		if ( ! $args['keep_alive'] )
			unset( $parser, $raw );

		return $data;
	}

	// OLD: `Helper::parseCSV()`
	public static function fromCSV( $filepath, $arguments = [] )
	{
		$args = self::parsed( self::getDefaultArgs( $filepath ), $arguments );
		$data = self::getInitialData( $args, $filepath );

		if ( ! Core\File::readable( $data['file_path'] ) )
			return self::bailWithError( $data,
				'file_not_readable',
				Plugin::notreadable( FALSE )
			);

		$data = self::getAdditionalData( $data, $args, $filepath );

		try {

			/**
			 * @package `jwage/easy-csv`
			 * @source https://github.com/jwage/easy-csv
			 * NOTE: This package is abandoned and no longer maintained.
			 * 	- No replacement package was suggested by the author.
			 */
			$parser = new \EasyCSV\Reader( $data['file_path'] );

			$data['headers'] = @$parser->getHeaders();

			if ( empty( $data['headers'] ) )
				return self::bailWithError( $data,
					'file_is_empty',
					Plugin::noinfo( FALSE )
				);

			if ( $args['headers'] )
				return $data;

			if ( $args['by_offset'] ) {

				$parser->advanceTo( (int) $args['by_offset'] );

				$data['single'] = is_null( $args['mapping'] )
					? @$parser->getRow()
					: Core\Arraay::reKeyByMap_ALT( @$parser->getRow(), $args['mapping'] );

				if ( empty( $data['single'] ) )
					return self::bailWithError( $data,
						'file_is_empty',
						Plugin::noinfo( FALSE )
					);

			} else if ( $args['first_row'] ) {

				$data['single'] = is_null( $args['mapping'] )
					? @$parser->getRow()
					: Core\Arraay::reKeyByMap_ALT( @$parser->getRow(), $args['mapping'] );

				if ( empty( $data['single'] ) )
					return self::bailWithError( $data,
						'file_is_empty',
						Plugin::noinfo( FALSE )
					);

			} else if ( is_null( $args['mapping'] ) ) {

				$data['items'] = @$parser->getAll();

				if ( empty( $data['items'] ) )
					return self::bailWithError( $data,
						'file_is_empty',
						Plugin::noinfo( FALSE )
					);

			} else {

				while ( $raw = @$parser->getRow() )
					$data['items'][$parser->getLineNumber()-1] = Core\Arraay::reKeyByMap_ALT( $raw, $args['mapping'] );

				if ( empty( $data['items'] ) )
					return self::bailWithError( $data,
						'file_is_empty',
						Plugin::noinfo( FALSE )
					);
			}

		} catch ( \Exception | \Error $e ) {

			return self::bailWithError( $data,
				'exception_occurred',
				$e->getMessage()
			);
		}

		$data['total'] = count( $data['items'] ); // TODO: better to get from parser

		if ( ! $args['keep_alive'] )
			unset( $parser, $raw );

		return $data;
	}

	// NOTE: DEPRECATED
	// OLD: `Helper::parseCSV_Legacy()`
	public static function fromCSV_Legacy( $file_path, $limit = NULL )
	{
		self::_dep( 'Parser::fromCSV()' );

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
	public static function fromXLSX( $filepath, $arguments = [] )
	{
		$args = self::parsed( self::getDefaultArguments( $filepath ), $arguments );
		$data = self::getInitialData( $args, $filepath );

		if ( ! Core\File::readable( $data['file_path'] ) )
			return self::bailWithError( $data,
				'file_not_readable',
				Plugin::notreadable( FALSE )
			);

		$data = self::getAdditionalData( $data, $args, $filepath );

		/**
		 * @package `openspout/openspout`
		 * @link https://opensource.box.com/spout/docs/
		 * @link https://github.com/openspout/openspout/blob/3.x/docs/index.md
		 */
		$parser = \OpenSpout\Reader\Common\Creator\ReaderEntityFactory::createXLSXReader();
		$parser->open( $data['file_path'] );

		foreach ( $parser->getSheetIterator() as $sheet ) {

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

			break; // No need iterating through other sheets!
		}

		$data['total'] = count( $data['items'] ); // TODO: better to get from parser

		if ( ! $args['keep_alive'] ) {
			$parser->close();
			unset( $parser, $raw );
		}

		return $data;
	}

	// FIXME: implement all!
	public static function fromTXT( $filepath, $arguments = [] )
	{
		$args = self::parsed( self::getDefaultArgs( $filepath ), $arguments );
		$data = self::getInitialData( $args, $filepath );

		if ( ! Core\File::readable( $data['file_path'] ) )
			return self::bailWithError( $data,
				'file_not_readable',
				Plugin::notreadable( FALSE )
			);

		$data = self::getAdditionalData( $data, $args, $filepath );

		// `TXT` source has no headers!

		$data['items'] = Core\Text::splitLines( Core\File::getContents( $filepath ) );

		return $data;
	}

	public static function fromTXT_Legacy( $file_path )
	{
		self::_dep( 'Parser::fromTXT()' );

		return Core\Text::splitLines( Core\File::getContents( $file_path ) );
	}

	public static function fromJSON( $filepath, $arguments = [] )
	{
		static $parsed = [];
		static $items  = [];

		$args = self::parsed( self::getDefaultArgs( $filepath ), $arguments );
		$data = self::getInitialData( $args, $filepath );

		if ( ! Core\File::readable( $data['file_path'] ) )
			return self::bailWithError( $data,
				'file_not_readable',
				Plugin::notreadable( FALSE )
			);

		$hash = self::hash( $args ); // for items only
		$data = self::getAdditionalData( $data, $args, $filepath );

		if ( ! empty( $parsed[$filepath] ) ) {

			$parser = $parsed[$filepath];

		} else {

			$parser = json_decode( Core\File::getContents( $data['file_path'] ), TRUE );

			if ( $args['data_key'] ) {

				// Overrides the data by `data_key` sub-data!

				if ( array_key_exists( $args['data_key'], $parser ) )
					$parser = $parser[$args['data_key']];

				else
					$parser = [];
			}
		}

		if ( empty( $parser ) )
			return self::bailWithError( $data,
				'file_is_empty',
				Plugin::noinfo( FALSE )
			);

		$data['headers'] = array_keys( Core\Arraay::valueFirst( $parser ) );

		if ( $args['headers'] )
			return $data;

		// Makes sure all keys are available for each row!
		$empty = array_fill_keys( $args['headers'], '' );

		if ( $args['by_offset'] ) {

			$data['single'] = is_null( $args['mapping'] )
				? self::parsed( $empty, $parser[( (int) $args['by_offset'] + 1 )] )
				: Core\Arraay::reKeyByMap_ALT( $parser[( (int) $args['by_offset'] + 1 )], $args['mapping'] );

			if ( empty( $data['single'] ) )
				return self::bailWithError( $data,
					'file_is_empty',
					Plugin::noinfo( FALSE )
				);

		} else if ( $args['first_row'] ) {

			$data['single'] = is_null( $args['mapping'] )
				? self::parsed( $empty, $parser[0] )
				: Core\Arraay::reKeyByMap_ALT( $parser[0], $args['mapping'] );

			if ( empty( $data['single'] ) )
				return self::bailWithError( $data,
					'file_is_empty',
					Plugin::noinfo( FALSE )
				);

		} else {

			if ( ! empty( $items[$hash] ) ) {

				$data['items'] = $items[$hash];

			} else {

				foreach ( $parser as $_index => $raw )
					$data['items'][( $_index + 1 )] = is_null( $args['mapping'] ) // starts at `1`
						? self::parsed( $empty, $raw )
						: Core\Arraay::reKeyByMap_ALT( $raw, $args['mapping'] );

				$items[$hash] = $data['items'];
			}

			if ( empty( $data['items'] ) )
				return self::bailWithError( $data,
					'file_is_empty',
					Plugin::noinfo( FALSE )
				);
		}

		$data['total'] = count( $data['items'] ); // TODO: better to get from parser

		if ( $args['keep_alive'] )
			$parsed[$filepath] = $parser;

		return $data;
	}

	// OLD: `Helper::parseJSON()`
	public static function fromJSON_Legacy( $file_path )
	{
		self::_dep( 'Parser::fromJSON()' );

		if ( empty( $file_path ) )
			return FALSE;

		return json_decode( Core\File::getContents( $file_path ), TRUE );
	}

	// FIXME: implement all!
	public static function fromXML( $filepath, $arguments = [] )
	{
		$args = self::parsed( self::getDefaultArgs( $filepath ), $arguments );
		$data = self::getInitialData( $args, $filepath );

		if ( ! Core\File::readable( $data['file_path'] ) )
			return self::bailWithError( $data,
				'file_not_readable',
				Plugin::notreadable( FALSE )
			);

		$data = self::getAdditionalData( $data, $args, $filepath );

		$data['items'] = self::fromXML_Legacy( $filepath );

		return $data;
	}

	// OLD: `Helper::parseXML()`
	public static function fromXML_Legacy( $file_path )
	{
		self::_dep( 'Parser::fromXML()' );

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

		$mimetypes = apply_filters( self::und( static::BASE, 'parser', 'csv', 'valid_filetypes' ), [
			'csv' => 'text/csv',
			'txt' => 'text/plain',
		] );

		$filetype = wp_check_filetype( $file, $mimetypes );

		return in_array( $filetype['type'], $mimetypes, TRUE );
	}
}
