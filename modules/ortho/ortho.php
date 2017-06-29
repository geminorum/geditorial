<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Core\HTML;

class Ortho extends gEditorial\Module
{

	private $virastar_version  = '0.12.0';
	private $persiantools_version  = '0.1.0';
	private $virastar_enqueued = FALSE;
	private $persiantools_enqueued = FALSE;

	public static function module()
	{
		return [
			'name'     => 'ortho',
			'title'    => _x( 'Ortho', 'Modules: Ortho', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Persian Orthography Tools', 'Modules: Ortho', GEDITORIAL_TEXTDOMAIN ),
			'icon'     => 'filter',
			'frontend' => FALSE,
		];
	}

	public function settings_intro_after( $module )
	{
		HTML::desc( sprintf( _x( 'Current installed Virastar version is: <code>%s</code>', 'Modules: Ortho: Settings Intro', GEDITORIAL_TEXTDOMAIN ), $this->virastar_version ) );
		HTML::desc( sprintf( _x( 'For more information, Please see Virastar <a href="%s" target="_blank">home page</a> or <a href="%s" target="_blank">live demo</a>.', 'Modules: Ortho: Settings Intro', GEDITORIAL_TEXTDOMAIN ),
			'https://github.com/juvee/virastar', 'http://juvee.github.io/virastar/' ) );
	}

	private function virastar_options()
	{
		return [
			// 'normalize_eol'                                  => _x( 'replace Windows end of lines with Unix EOL (<code>&#92;&#110;</code>)', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_dashes'                                     => _x( 'replace double dash to ndash and triple dash to mdash', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_three_dots'                                 => _x( 'replace three dots with ellipsis', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_english_quotes_pairs'                       => _x( 'replace English quotes pairs (<code>&#8220;&#8221;</code>) with their Persian equivalent (<code>&#171;&#187;</code>)', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_english_quotes'                             => _x( 'replace English quotes, commas and semicolons with their Persian equivalent', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_hamzeh'                                     => _x( 'convert <code>&#1607;&#8204;&#1740;</code> to <code>&#1607;&#1620;</code>', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'cleanup_zwnj'                                   => _x( 'cleanup Zero-Width-Non-Joiners', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_spacing_for_braces_and_quotes'              => _x( 'fix spacing for <code>()</code> <code>[]</code> <code>{}</code> <code>&#8220;&#8221;</code> <code>&#171;&#187;</code> (one space outside, no space inside), correct <code>:;,.?!</code> spacing (one space after and no space before)', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_arabic_numbers'                             => _x( 'replace Arabic numbers with their Persian equivalent', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_english_numbers'                            => _x( 'replace English numbers with their Persian equivalent', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_misc_non_persian_chars'                     => _x( 'replace Arabic kaf and Yeh with its Persian equivalent', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			// 'skip_markdown_ordered_lists_numbers_conversion' => _x( 'skip converting English numbers of ordered lists in markdown', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_question_mark'                              => _x( 'replace question marks with its Persian equivalent', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_perfix_spacing'                             => _x( 'put zwnj between word and prefix (mi* nemi*)', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_suffix_spacing'                             => _x( 'put zwnj between word and suffix (*tar *tarin *ha *haye)', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'kashidas_as_parenthetic'                        => _x( 'replace kashidas to ndash in parenthetic', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'cleanup_kashidas'                               => _x( 'remove all kashidas', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'cleanup_extra_marks'                            => _x( 'replace more than one <code>!</code> or <code>?</code> mark with just one', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			// 'cleanup_spacing'                                => _x( 'replace more than one space with just a single one', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			// 'cleanup_begin_and_end'                          => _x( 'remove spaces, tabs, and new lines from the beginning and end of text', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			// 'preserve_HTML'                                  => _x( 'preserve all HTML tags', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			// 'preserve_URIs'                                  => _x( 'preserve all URI links in the text', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			// 'preserve_brackets'                              => _x( 'preserve strings inside square brackets (<code>[]</code>)', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			// 'preserve_braces'                                => _x( 'preserve strings inside curly braces (<code>{}</code>)', 'Modules: Ortho: Setting Option', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	protected function get_global_settings()
	{
		$virastar_options = $this->virastar_options();

		return [
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
			'_general' => [
				[
					'field'   => 'virastar_options',
					'type'    => 'checkbox',
					'title'   => _x( 'Virastar Options', 'Modules: Ortho: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'default' => array_keys( $virastar_options ),
					'values'  => $virastar_options,
				],
			],
		];
	}

	protected function get_global_strings()
	{
		return [
			'js' => [
				// 'button_virastar'        => HTML::getDashicon( 'filter' ),
				'button_virastar_title'  => _x( 'Apply Virastar!', 'Modules: Ortho: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_virastar'          => _x( 'Virastar!', 'Modules: Ortho: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_virastar_title'    => _x( 'Apply Virastar!', 'Modules: Ortho: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_swapquotes'        => _x( 'Swap Quotes', 'Modules: Ortho: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_swapquotes_title'  => _x( 'Swap Not-Correct Quotes', 'Modules: Ortho: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_mswordnotes'       => _x( 'Word Footnotes', 'Modules: Ortho: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_mswordnotes_title' => _x( 'MS Word Footnotes to WordPress [ref]', 'Modules: Ortho: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_download'          => _x( 'Download', 'Modules: Ortho: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_download_title'    => _x( 'Download text as markdown', 'Modules: Ortho: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_nbsp'              => _x( 'nbsp', 'Modules: Ortho: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_nbsp_title'        => _x( 'Non-Breaking SPace', 'Modules: Ortho: Javascript String', GEDITORIAL_TEXTDOMAIN ),
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->taxonomies_excluded = [
			'nav_menu',
			'post_format',
			'link_category',
			'bp_member_type',
			'bp_group_type',
			'bp-email-type',
			'ef_editorial_meta',
			'following_users',
			'ef_usergroup',
			'post_status',
			'flamingo_contact_tag',
			'flamingo_inbound_channel',
		];
	}

	public function current_screen( $screen )
	{
		if ( 'post' == $screen->base ) {

			if ( in_array( $screen->post_type, $this->post_types() ) )
				$this->enqueueVirastar();

		} else if ( 'edit-tags' == $screen->base || 'term' == $screen->base ) {

			if ( in_array( $screen->taxonomy, $this->taxonomies() ) )
				$this->enqueueVirastar();
		}
	}

	private function enqueueVirastar()
	{
		if ( $this->virastar_enqueued )
			return;

		$virastar = Helper::registerScriptPackage( 'virastar',
			NULL, [], $this->virastar_version );

		$this->enqueue_asset_js( [
			'strings'  => $this->strings['js'],
			'virastar' => $this->negate_virastar_options(),
		], NULL, [ 'jquery', $virastar ] );

		$this->virastar_enqueued = TRUE;
	}

	private function enqueuePersianTools()
	{
		if ( $this->persiantools_enqueued )
			return;

		$persiantools = Helper::registerScriptPackage( 'persiantools',
			NULL, [], $this->persiantools_version );

		$this->enqueue_asset_js( 'persiantools', NULL, [ 'jquery', $persiantools ] );

		$this->persiantools_enqueued = TRUE;
	}

	private function negate_virastar_options()
	{
		$saved   = array_values( $this->get_setting( 'virastar_options', [] ) );
		$options = [];

		foreach ( array_keys( $this->virastar_options() ) as $option )
			if ( ! in_array( $option, $saved ) )
				$options[$option] = FALSE;

		return $options;
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {
			$this->enqueueVirastar();
			$this->enqueuePersianTools();
		}
	}

	public function tools_sub( $uri, $sub )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', 'tools', FALSE, FALSE );

			HTML::h3( _x( 'Orthography Sandbox', 'Modules: Ortho', GEDITORIAL_TEXTDOMAIN ) );

			$this->do_settings_field( [
				'type'         => 'textarea-quicktags',
				'field'        => 'sandbox',
				'dir'          => 'rtl',
				'option_group' => 'tools',
			] );

		$this->settings_form_after( $uri, $sub );
	}
}
