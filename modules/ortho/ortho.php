<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialOrtho extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'     => 'ortho',
			'title'    => _x( 'Ortho', 'Ortho Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Orthography Tools', 'Ortho Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'     => 'welcome-widgets-menus',
			'frontend' => FALSE,
		);
	}

	private function virastar_options()
	{
		return array(
			// 'normalize_eol'                                  => _x( 'replace Windows end of lines with Unix EOL (<code>&#92;&#110;</code>)', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_dashes'                                     => _x( 'replace double dash to ndash and triple dash to mdash', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_three_dots'                                 => _x( 'replace three dots with ellipsis', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_english_quotes_pairs'                       => _x( 'replace English quotes pairs (<code>&#8220;&#8221;</code>) with their Persian equivalent (<code>&#171;&#187;</code>)', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_english_quotes'                             => _x( 'replace English quotes, commas and semicolons with their Persian equivalent', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_hamzeh'                                     => _x( 'convert <code>&#1607;&#8204;&#1740;</code> to <code>&#1607;&#1620;</code>', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'cleanup_zwnj'                                   => _x( 'cleanup Zero-Width-Non-Joiners', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_spacing_for_braces_and_quotes'              => _x( 'fix spacing for <code>()</code> <code>[]</code> <code>{}</code> <code>&#8220;&#8221;</code> <code>&#171;&#187;</code> (one space outside, no space inside), correct <code>:;,.?!</code> spacing (one space after and no space before)', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_arabic_numbers'                             => _x( 'replace Arabic numbers with their Persian equivalent', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_english_numbers'                            => _x( 'replace English numbers with their Persian equivalent', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_misc_non_persian_chars'                     => _x( 'replace Arabic kaf and Yeh with its Persian equivalent', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			// 'skip_markdown_ordered_lists_numbers_conversion' => _x( 'skip converting English numbers of ordered lists in markdown', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_question_mark'                              => _x( 'replace question marks with its Persian equivalent', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_perfix_spacing'                             => _x( 'put zwnj between word and prefix (mi* nemi*)', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'fix_suffix_spacing'                             => _x( 'put zwnj between word and suffix (*tar *tarin *ha *haye)', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'cleanup_kashidas'                               => _x( 'remove all kashidas', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			'cleanup_extra_marks'                            => _x( 'replace more than one <code>!</code> or <code>?</code> mark with just one', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			// 'cleanup_spacing'                                => _x( 'replace more than one space with just a single one', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			// 'cleanup_begin_and_end'                          => _x( 'remove spaces, tabs, and new lines from the beginning and end of text', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			// 'preserve_HTML'                                  => _x( 'preserve all HTML tags', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			// 'preserve_URIs'                                  => _x( 'preserve all URI links in the text', 'Ortho Module: Setting Option', GEDITORIAL_TEXTDOMAIN ),
		);
	}

	protected function get_global_settings()
	{
		$virastar_options = $this->virastar_options();

		return array(
			'posttypes_option'  => 'posttypes_option',
			// 'taxonomies_option' => 'taxonomies_option',
			'_general' => array(
				array(
					'field'   => 'virastar_options',
					'type'    => 'checkbox',
					'title'   => _x( 'Virastar Options', 'Ortho Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'default' => array_keys( $virastar_options ),
					'values'  => $virastar_options,
				),
			),
		);
	}

	protected function get_global_strings()
	{
		return array(
			'js' => array(
				'button_virastar_title'     => _x( 'Apply Virastar!', 'Ortho Module: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_virastar'             => _x( 'Virastar!', 'Ortho Module: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_virastar_title'       => _x( 'Apply Virastar!', 'Ortho Module: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_swap_quotes'          => _x( 'Swap Quotes', 'Ortho Module: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_swap_quotes_title'    => _x( 'Swap Not-Correct Quotes', 'Ortho Module: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_word_footnotes'       => _x( 'Word Footnotes', 'Ortho Module: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'qtag_word_footnotes_title' => _x( 'MS Word Footnotes to WordPress [ref]', 'Ortho Module: Javascript String', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	public function init()
	{
		do_action( 'geditorial_ortho_init', $this->module );

		$this->do_globals();
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'post' == $screen->base ) {

				wp_register_script( 'geditorial-virastar',
					GEDITORIAL_URL.'assets/packages/virastar/virastar.js',
					array(),
					'0.9.1',
					TRUE );

				$this->enqueue_asset_js( array(
					// 'settings' => $this->options->settings,
					'strings'  => $this->strings['js'],
					'virastar' => $this->negate_virastar_options(),
				), 'ortho.post', array( 'geditorial-virastar' ) );
			}
		}
	}

	private function negate_virastar_options()
	{
		$saved   = array_values( $this->get_setting( 'virastar_options', array() ) );
		$options = array();

		foreach ( array_keys( $this->virastar_options() ) as $option )
			if ( ! in_array( $option, $saved ) )
				$options[$option] = FALSE;

		return $options;
	}
}
