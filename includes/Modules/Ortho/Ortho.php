<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;

use geminorum\gNetwork\Core\Orthography;

class Ortho extends gEditorial\Module
{

	protected $disable_no_customs = TRUE;

	private $virastar_version      = '0.18.0';
	private $persiantools_version  = '0.1.0';
	private $virastar_enqueued     = FALSE;
	private $persiantools_enqueued = FALSE;

	public static function module()
	{
		return [
			'name'     => 'ortho',
			'title'    => _x( 'Ortho', 'Modules: Ortho', 'geditorial' ),
			'desc'     => _x( 'Persian Orthography Tools', 'Modules: Ortho', 'geditorial' ),
			'icon'     => [ 'old', 'pen' ],
			'frontend' => FALSE,
			'disabled' => 'fa_IR' == get_locale() ? FALSE : _x( 'Only on Persian Locale', 'Modules: Ortho', 'geditorial' ),
		];
	}

	protected function settings_help_tabs( $context = 'settings' )
	{
		$tabs = [
			[
				'id'       => $this->classs( 'virastar' ),
				'title'    => _x( 'Virastar', 'Modules: Ortho: Help Tab Title', 'geditorial' ),
				'content'  => sprintf( '<div class="-info"><p>Virastar is a Persian text cleaner.</p><p class="-from">Virastar v%s installed. For more information, Please see Virastar <a href="%s" target="_blank">home page</a> or <a href="%s" target="_blank">live demo</a>.</p></div>',
					$this->virastar_version, 'https://github.com/brothersincode/virastar', 'https://virastar.brothersincode.ir' ),
			],
			[
				'id'       => $this->classs( 'persiantools' ),
				'title'    => _x( 'PersianTools', 'Modules: Ortho: Help Tab Title', 'geditorial' ),
				'content'  => sprintf( '<div class="-info"><p>PersianTools is a Persian text library.</p><p class="-from">PersianTools v%s installed. For more information, Please see PersianTools <a href="%s" target="_blank">home page</a>.</p></div>',
					$this->persiantools_version, 'https://github.com/Bersam/persiantools' ),
			],
		];

		return array_merge( $tabs, parent::settings_help_tabs( $context ) );
	}

	private function virastar_options()
	{
		return [
			// 'normalize_eol'                                  => _x( 'replace Windows end of lines with Unix EOL (<code>&#92;&#110;</code>)', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'fix_dashes'                                     => _x( 'replace double dash to ndash and triple dash to mdash', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'fix_three_dots'                                 => _x( 'replace three dots with ellipsis', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'fix_english_quotes_pairs'                       => _x( 'replace English quotes pairs (<code>&#8220;&#8221;</code>) with their Persian equivalent (<code>&#171;&#187;</code>)', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'fix_english_quotes'                             => _x( 'replace English quotes, commas and semicolons with their Persian equivalent', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'fix_hamzeh'                                     => _x( 'convert <code>&#1607;&#8204;&#1740;</code> to <code>&#1607;&#1620;</code>', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'cleanup_zwnj'                                   => _x( 'cleanup Zero-Width-Non-Joiners', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'fix_spacing_for_braces_and_quotes'              => _x( 'fix spacing for <code>()</code> <code>[]</code> <code>{}</code> <code>&#8220;&#8221;</code> <code>&#171;&#187;</code> (one space outside, no space inside), correct <code>:;,.?!</code> spacing (one space after and no space before)', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'fix_arabic_numbers'                             => _x( 'replace Arabic numbers with their Persian equivalent', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'fix_english_numbers'                            => _x( 'replace English numbers with their Persian equivalent', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'fix_misc_non_persian_chars'                     => _x( 'replace Arabic kaf and Yeh with its Persian equivalent', 'Modules: Ortho: Setting Option', 'geditorial' ),
			// 'skip_markdown_ordered_lists_numbers_conversion' => _x( 'skip converting English numbers of ordered lists in markdown', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'fix_question_mark'                              => _x( 'replace question marks with its Persian equivalent', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'fix_perfix_spacing'                             => _x( 'put zwnj between word and prefix (mi* nemi*)', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'fix_suffix_spacing'                             => _x( 'put zwnj between word and suffix (*tar *tarin *ha *haye)', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'kashidas_as_parenthetic'                        => _x( 'replace kashidas to ndash in parenthetic', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'cleanup_kashidas'                               => _x( 'remove all kashidas', 'Modules: Ortho: Setting Option', 'geditorial' ),
			'cleanup_extra_marks'                            => _x( 'replace more than one <code>!</code> or <code>?</code> mark with just one', 'Modules: Ortho: Setting Option', 'geditorial' ),
			// 'cleanup_spacing'                                => _x( 'replace more than one space with just a single one', 'Modules: Ortho: Setting Option', 'geditorial' ),
			// 'cleanup_begin_and_end'                          => _x( 'remove spaces, tabs, and new lines from the beginning and end of text', 'Modules: Ortho: Setting Option', 'geditorial' ),
			// 'preserve_HTML'                                  => _x( 'preserve all HTML tags', 'Modules: Ortho: Setting Option', 'geditorial' ),
			// 'preserve_URIs'                                  => _x( 'preserve all URI links in the text', 'Modules: Ortho: Setting Option', 'geditorial' ),
			// 'preserve_brackets'                              => _x( 'preserve strings inside square brackets (<code>[]</code>)', 'Modules: Ortho: Setting Option', 'geditorial' ),
			// 'preserve_braces'                                => _x( 'preserve strings inside curly braces (<code>{}</code>)', 'Modules: Ortho: Setting Option', 'geditorial' ),
		];
	}

	protected function get_global_settings()
	{
		$virastar_options = $this->virastar_options();

		return [
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
			'_virastar' => [
				[
					'field'       => 'virastar_options',
					'type'        => 'checkboxes',
					'title'       => _x( 'Virastar Options', 'Modules: Ortho: Setting Title', 'geditorial' ),
					'description' => _x( 'For more information, please see the library documentations.', 'Modules: Ortho: Setting Description', 'geditorial' ),
					'default'     => array_keys( $virastar_options ),
					'values'      => $virastar_options,
				],
				[
					'field'       => 'virastar_on_paste',
					'title'       => _x( 'Virastar on Paste', 'Modules: Ortho: Setting Title', 'geditorial' ),
					'description' => _x( 'Applies Virastar to pasted texts on targeted inputs.', 'Modules: Ortho: Setting Description', 'geditorial' ),
				],
			],
		];
	}

	public function settings_section_virastar()
	{
		Settings::fieldSection(
			_x( 'Virastar!', 'Modules: Ortho: Setting Section Title', 'geditorial' )
		);
	}

	protected function get_global_strings()
	{
		return [
			'js' => [
				// 'button_virastar'        => HTML::getDashicon( 'text' ),
				'button_virastar_title'  => _x( 'Apply Virastar!', 'Modules: Ortho: Javascript String', 'geditorial' ),
				'qtag_virastar'          => _x( 'Virastar!', 'Modules: Ortho: Javascript String', 'geditorial' ),
				'qtag_virastar_title'    => _x( 'Apply Virastar!', 'Modules: Ortho: Javascript String', 'geditorial' ),
				'qtag_swapquotes'        => _x( 'Swap Quotes', 'Modules: Ortho: Javascript String', 'geditorial' ),
				'qtag_swapquotes_title'  => _x( 'Swap Not-Correct Quotes', 'Modules: Ortho: Javascript String', 'geditorial' ),
				'qtag_mswordnotes'       => _x( 'Word Footnotes', 'Modules: Ortho: Javascript String', 'geditorial' ),
				'qtag_mswordnotes_title' => _x( 'MS Word Footnotes to WordPress [ref]', 'Modules: Ortho: Javascript String', 'geditorial' ),
				'qtag_download'          => _x( 'Download', 'Modules: Ortho: Javascript String', 'geditorial' ),
				'qtag_download_title'    => _x( 'Download text as markdown', 'Modules: Ortho: Javascript String', 'geditorial' ),
				'qtag_nbsp'              => _x( 'nbsp', 'Modules: Ortho: Javascript String', 'geditorial' ),
				'qtag_nbsp_title'        => _x( 'Non-Breaking SPace', 'Modules: Ortho: Javascript String', 'geditorial' ),
			],
		];
	}

	protected function taxonomies_excluded()
	{
		return Settings::taxonomiesExcluded( [
			'system_tags',
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
			'rel_people',
			'rel_post',
		] );
	}

	public function init()
	{
		parent::init();

		if ( ! is_admin() )
			return;

		if ( class_exists( 'geminorum\\gNetwork\\Core\\Orthography' ) )
			$this->filter_module( 'importer', 'prepare', 4, 8 );
	}

	public function current_screen( $screen )
	{
		if ( 'post' == $screen->base ) {

			if ( PostType::supportBlocks( $screen->post_type ) )
				return;

			if ( in_array( $screen->post_type, $this->posttypes() ) )
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

		$virastar = Scripts::registerPackage( 'virastar',
			NULL, [], $this->virastar_version );

		// cleanup
		$settings = $this->options->settings;
		unset( $settings['virastar_options'] );

		$this->enqueue_asset_js( [
			'settings' => $settings,
			'strings'  => $this->strings['js'],
			'virastar' => $this->negate_virastar_options(),
		], NULL, [ 'jquery', $virastar ] );

		$this->virastar_enqueued = TRUE;
	}

	private function enqueuePersianTools()
	{
		if ( $this->persiantools_enqueued )
			return;

		$persiantools = Scripts::registerPackage( 'persiantools',
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

	public function importer_prepare( $value, $posttype, $field, $raw )
	{
		if ( ! in_array( $posttype, $this->posttypes() ) )
			return $value;

		switch ( $field ) {

			case 'importer_post_title'  : return $this->cleanup_chars( trim( $value ), FALSE );
			case 'importer_post_content': return $this->cleanup_chars( trim( $value ), TRUE );
			case 'importer_post_excerpt': return $this->cleanup_chars( trim( $value ), TRUE );
		}

		return $value;
	}

	public function cleanup_post( $post )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		$data   = [];
		$update = FALSE;
		$fields = [
			'post_title'   => FALSE,
			// 'post_content' => TRUE, // working but wait for more tests!
			'post_excerpt' => TRUE,
		];

		foreach ( $fields as $field => $html ) {
			if ( ! empty( $post->{$field} ) ) {
				$cleaned = $this->cleanup_chars( $post->{$field}, $html );
				if ( 0 !== strcmp( $post->{$field}, $cleaned ) ) {
					$data[$field] = $cleaned;
					$update = TRUE;
				}
			}
		}

		if ( ! $update )
			return FALSE;

		$data['ID'] = $post->ID;

		return (bool) wp_update_post( $data );
	}

	// FIXME
	public function cleanup_chars( $string, $html = FALSE )
	{
		return Orthography::cleanupPersianChars( $string );

		return $html
			? Orthography::cleanupPersianHTML( $string )
			: Orthography::cleanupPersian( $string );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {
			$this->enqueueVirastar();
			$this->enqueuePersianTools();
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		HTML::h3( _x( 'Orthography Sandbox', 'Modules: Ortho', 'geditorial' ) );

		$this->do_settings_field( [
			'type'         => 'textarea-quicktags',
			'field'        => 'sandbox',
			'dir'          => 'rtl',
			'field_class'  => [ 'large-text', 'textarea-autosize' ],
			'option_group' => 'tools',
		] );
	}

	public function reports_settings( $sub )
	{
		if ( ! class_exists( 'geminorum\\gNetwork\\Core\\Orthography' ) )
			return FALSE;

		if ( $this->check_settings( $sub, 'reports' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'reports', $sub );

				if ( $this->current_action( 'cleanup_chars', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id )
						$count += $this->cleanup_post( $post_id );

					WordPress::redirectReferer( [
						'message' => 'cleaned',
						'count'   => $count,
					] );
				}
			}

			$this->screen_option( $sub );
			$this->register_button( 'cleanup_chars', _x( 'Cleanup Chars', 'Modules: Ortho: Setting Button', 'geditorial' ), TRUE );
		}
	}

	protected function render_reports_html( $uri, $sub )
	{
		$list  = $this->list_posttypes();
		$query = $extra = [];
		$char  = self::req( 'char', 'none' );

		if ( 'none' != $char )
			$query['s'] = $extra['char'] = $char;

		list( $posts, $pagination ) = $this->getTablePosts( $query, $extra );

		$pagination['before'][] = HTML::dropdown( [
			'ي' => _x( 'Arabic Letter Yeh U+064A', 'Modules: Ortho', 'geditorial' ),
			'ك' => _x( 'Arabic Letter Kaf U+0643', 'Modules: Ortho', 'geditorial' ),
		], [
			'name'       => 'char',
			'selected'   => self::req( 'char', 'none' ),
			'none_value' => 'none',
			'none_title' => Settings::showOptionNone(),
		] );

		$pagination['before'][] = Helper::tableFilterPostTypes( $list );
		$pagination['before'][] = Helper::tableFilterAuthors( $list );
		$pagination['before'][] = Helper::tableFilterSearch( $list );

		$columns = [
			'_cb'     => 'ID',
			'ID'      => Helper::tableColumnPostID(),
			'date'    => Helper::tableColumnPostDate(),
			'type'    => Helper::tableColumnPostType(),
			'title'   => Helper::tableColumnPostTitle(),
			'excerpt' => Helper::tableColumnPostExcerpt(),
		];

		if ( gEditorial()->enabled( 'meta' ) )
			$columns['meta'] = gEditorial()->meta->tableColumnPostMeta( FALSE );

		return HTML::tableList( $columns, $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Post Orthography', 'Modules: Ortho', 'geditorial' ) ),
			'empty'      => $this->get_posttype_label( 'post', 'not_found' ),
			'pagination' => $pagination,
		] );
	}
}
