<?php namespace geminorum\gEditorial\Modules\Ortho;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;

class ModuleInfo extends gEditorial\Info
{
	const MODULE = 'ortho';

	const VIRASTAR_VERSION     = '0.22.1';
	const PERSIANTOOLS_VERSION = '0.1.0';

	public static function virastarOptions( $context = NULL )
	{
		return [
			// 'cleanup_begin_and_end'                          => TRUE,
			'cleanup_extra_marks'                            => TRUE,
			'cleanup_kashidas'                               => TRUE,
			'cleanup_line_breaks'                            => TRUE,
			'cleanup_rlm'                                    => TRUE,
			'cleanup_spacing'                                => TRUE,
			'cleanup_zwnj'                                   => TRUE,
			'decode_htmlentities'                            => TRUE,
			'fix_arabic_numbers'                             => TRUE,
			'fix_dashes'                                     => TRUE,
			'fix_diacritics'                                 => TRUE,
			'fix_english_numbers'                            => TRUE,
			'fix_english_quotes_pairs'                       => TRUE,
			'fix_english_quotes'                             => TRUE,
			'fix_hamzeh'                                     => TRUE,
			'fix_hamzeh_arabic'                              => FALSE,
			'fix_misc_non_persian_chars'                     => TRUE,
			'fix_misc_spacing'                               => TRUE,
			'fix_numeral_symbols'                            => TRUE,
			'fix_perfix_spacing'                             => TRUE,
			'fix_persian_glyphs'                             => TRUE,
			'fix_punctuations'                               => TRUE,
			'fix_question_mark'                              => TRUE,
			'fix_spacing_for_braces_and_quotes'              => TRUE,
			'fix_spacing_for_punctuations'                   => TRUE,
			'fix_suffix_misc'                                => TRUE,
			'fix_suffix_spacing'                             => TRUE,
			'fix_three_dots'                                 => TRUE,
			'kashidas_as_parenthetic'                        => TRUE,
			// 'markdown_normalize_braces'                      => TRUE,
			// 'markdown_normalize_lists'                       => TRUE,
			'normalize_dates'                                => TRUE,
			'normalize_ellipsis'                             => TRUE,
			'remove_spaces_before_ellipsis'                  => TRUE,
			// 'normalize_eol'                                  => TRUE,
			// 'preserve_braces'                                => FALSE,
			// 'preserve_brackets'                              => FALSE,
			// 'preserve_comments'                              => TRUE,
			// 'preserve_entities'                              => TRUE,
			// 'preserve_frontmatter'                           => TRUE,
			// 'preserve_HTML'                                  => TRUE,
			'preserve_nbsps'                                 => TRUE,
			// 'preserve_URIs'                                  => TRUE,
			// 'remove_diacritics'                              => FALSE,
			// 'skip_markdown_ordered_lists_numbers_conversion' => FALSE,
		];
	}

	public static function getHelpTabs( $context = NULL )
	{
		return [
			[
				'title'    => _x( 'Virastar', 'Help Tab Title', 'geditorial-ortho' ),
				'id'      => static::classs( 'virastar' ),
				'content' => self::buffer( [ __CLASS__, 'renderrenderHelpTab_virastar' ] ),
			],
			[
				'title'   => _x( 'PersianTools', 'Help Tab Title', 'geditorial-ortho' ),
				'id'      => static::classs( 'persiantools' ),
				'content' => self::buffer( [ __CLASS__, 'renderrenderHelpTab_persiantools' ] ),
			],
		];
	}

	public static function renderrenderHelpTab_virastar()
	{
		printf( '<div class="-info"><p>Virastar is a Persian text cleaner.</p><p class="-from">Virastar v%s installed. For more information, Please see Virastar <a href="%s" target="_blank">home page</a> or <a href="%s" target="_blank">live demo</a>.</p></div>',
			static::VIRASTAR_VERSION, 'https://github.com/brothersincode/virastar', 'https://virastar.brothersincode.ir' );
	}

	public static function renderrenderHelpTab_persiantools()
	{
		printf( '<div class="-info"><p>PersianTools is a Persian text library.</p><p class="-from">PersianTools v%s installed. For more information, Please see PersianTools <a href="%s" target="_blank">home page</a>.</p></div>',
			static::PERSIANTOOLS_VERSION, 'https://github.com/Bersam/persiantools' );
	}
}
