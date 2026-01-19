<?php namespace geminorum\gEditorial\Modules\Ortho;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

use geminorum\gNetwork\Core\Orthography;

class Ortho extends gEditorial\Module
{

	protected $disable_no_customs      = TRUE;
	protected $priority_current_screen = 20;

	private $virastar_enqueued     = FALSE;
	private $persiantools_enqueued = FALSE;

	protected $keep_posttypes = [
		'attachment',
	];

	public static function module()
	{
		return [
			'name'     => 'ortho',
			'title'    => _x( 'Ortho', 'Modules: Ortho', 'geditorial-admin' ),
			'desc'     => _x( 'Persian Orthography Tools', 'Modules: Ortho', 'geditorial-admin' ),
			'icon'     => [ 'misc-32', 'pen' ],
			'frontend' => FALSE,
			'access'   => 'stable',
			'disabled' => Services\Modulation::moduleCheckLocale( 'fa_IR' ),
		];
	}

	protected function settings_help_tabs( $context = 'settings' )
	{
		return array_merge(
			ModuleInfo::getHelpTabs( $context ),
			parent::settings_help_tabs( $context )
		);
	}

	private function virastar_options()
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

	private static function keyLabels( $keys )
	{
		$list  = [];
		$words = [ 'nbsp', 'html', 'eol', 'rlm', 'zwnj', 'uri' ];
		$upper = array_map( 'strtoupper', $words );

		foreach ( (array) $keys as $key )
			$list[$key] = str_ireplace( $words, $upper, Core\Text::titleCase( str_replace( '_', ' ', $key ) ) );

		return $list;
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
					'type'        => 'checkboxes-panel-expanded',
					'title'       => _x( 'Virastar Options', 'Setting Title', 'geditorial-ortho' ),
					'description' => _x( 'For more information, please see the library documentations.', 'Setting Description', 'geditorial-ortho' ),
					'default'     => array_keys( array_filter( $virastar_options ) ),
					'values'      => self::keyLabels( array_keys( $virastar_options ) ),
				],
				[
					'field'       => 'virastar_on_paste',
					'title'       => _x( 'Virastar on Paste', 'Setting Title', 'geditorial-ortho' ),
					'description' => _x( 'Applies Virastar to pasted texts on targeted inputs.', 'Setting Description', 'geditorial-ortho' ),
				],
			],
		];
	}

	protected function settings_section_titles( $suffix )
	{
		switch ( $suffix ) {

			case '_virastar': return [ _x( 'Virastar!', 'Setting Section Title', 'geditorial-ortho' ),
				_x( 'Customize the behavior of Virastar library.', 'Setting Section Description', 'geditorial-ortho' ) ];
		}

		return FALSE;
	}

	protected function get_global_strings()
	{
		return [
			'js' => [
				'virastar' => [
					// 'button_virastar'        => Core\HTML::getDashicon( 'text' ),
					'button_virastar_title'  => _x( 'Apply Virastar!', 'Javascript String', 'geditorial-ortho' ),
					'qtag_virastar'          => _x( 'Virastar!', 'Javascript String', 'geditorial-ortho' ),
					'qtag_virastar_title'    => _x( 'Apply Virastar!', 'Javascript String', 'geditorial-ortho' ),
					'qtag_swapquotes'        => _x( 'Swap Quotes', 'Javascript String', 'geditorial-ortho' ),
					'qtag_swapquotes_title'  => _x( 'Swap Not-Correct Quotes', 'Javascript String', 'geditorial-ortho' ),
					'qtag_mswordnotes'       => _x( 'Word Footnotes', 'Javascript String', 'geditorial-ortho' ),
					'qtag_mswordnotes_title' => _x( 'MS Word Footnotes to WordPress [ref]', 'Javascript String', 'geditorial-ortho' ),
					'qtag_download'          => _x( 'Download', 'Javascript String', 'geditorial-ortho' ),
					'qtag_download_title'    => _x( 'Download text as markdown', 'Javascript String', 'geditorial-ortho' ),
					'qtag_nbsp'              => _x( 'nbsp', 'Javascript String', 'geditorial-ortho' ),
					'qtag_nbsp_title'        => _x( 'Non-Breaking SPace', 'Javascript String', 'geditorial-ortho' ),
				],
			],
		];
	}

	public function init()
	{
		parent::init();

		if ( ! is_admin() )
			return;

		if ( class_exists( 'geminorum\\gNetwork\\Core\\Orthography' ) )
			$this->filter_module( 'importer', 'prepare', 7, 8 );
	}

	public function current_screen( $screen )
	{
		if ( 'post' == $screen->base ) {

			if ( WordPress\PostType::supportBlocks( $screen->post_type ) )
				return;

			if ( $this->posttype_supported( $screen->post_type ) )
				$this->enqueueVirastar();

		} else if ( 'edit-tags' == $screen->base || 'term' == $screen->base ) {

			if ( $this->taxonomy_supported( $screen->taxonomy ) )
				$this->enqueueVirastar();
		}
	}

	public function enqueueVirastar()
	{
		if ( $this->virastar_enqueued )
			return TRUE;

		$virastar = gEditorial\Scripts::registerPackage( 'virastar',
			NULL, [], ModuleInfo::VIRASTAR_VERSION );

		// cleanup
		$settings = $this->options->settings;
		unset( $settings['virastar_options'] );

		$this->enqueue_asset_js( [
			'settings' => $settings,
			'strings'  => $this->get_strings( 'virastar', 'js' ),
			'virastar' => $this->prepare_virastar_options(),
		], NULL, [ 'jquery', $virastar ] );

		return $this->virastar_enqueued = TRUE;
	}

	public function enqueuePersianTools()
	{
		if ( $this->persiantools_enqueued )
			return TRUE;

		$persiantools = gEditorial\Scripts::registerPackage( 'persiantools',
			NULL, [], ModuleInfo::PERSIANTOOLS_VERSION );

		$this->enqueue_asset_js( 'persiantools', NULL, [ 'jquery', $persiantools ] );

		return $this->persiantools_enqueued = TRUE;
	}

	private function prepare_virastar_options()
	{
		$saved   = $this->get_setting( 'virastar_options', [] );
		$options = [];

		foreach ( array_keys( $this->virastar_options() ) as $option )
			$options[$option] = (bool) in_array( $option, $saved );

		return $options;
	}

	public function importer_prepare( $value, $posttype, $field, $header, $raw, $source_id, $all_taxonomies )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $value;

		switch ( $field ) {

			case 'importer_post_title'  : return $this->cleanup_chars( trim( $value ), FALSE );
			case 'importer_post_content': return $this->cleanup_chars( trim( $value ), TRUE );
			case 'importer_post_excerpt': return $this->cleanup_chars( trim( $value ), TRUE );
			case 'importer_custom_meta' : return $this->cleanup_chars( trim( $value ), FALSE );
		}

		return $value;
	}

	public function cleanup_post( $post )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$update = FALSE;
		$data   = [];
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

		// return $html
		// 	? Orthography::cleanupPersianHTML( $string )
		// 	: Orthography::cleanupPersian( $string );
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
		Core\HTML::h3( _x( 'Orthography Sandbox', 'Header', 'geditorial-ortho' ) );

		$this->do_settings_field( [
			'type'         => 'textarea-quicktags',
			'field'        => 'sandbox-text',
			'dir'          => 'rtl',
			'field_class'  => [ 'large-text', 'textarea-autosize' ],
			'option_group' => 'tools',
		] );

		echo '<br />';
		echo '<br />';

		$this->do_settings_field( [
			'type'         => 'text',
			'field'        => 'sandbox-identity',
			'dir'          => 'rtl',
			'field_class'  => [ 'large-text', 'code-text' ],
			'option_group' => 'tools',
			'placeholder' => 'xxxxxxxxxx',
			'data' => [
				'ortho' => 'identity',
			],
		] );

		echo '<br />';
		echo '<br />';

		$this->do_settings_field( [
			'type'         => 'text',
			'field'        => 'sandbox-iban',
			'dir'          => 'rtl',
			'field_class'  => [ 'large-text', 'code-text' ],
			'option_group' => 'tools',
			'placeholder' => 'IRxxxxxxxxxxxxxxxxxxxxxxx',
			'data' => [
				'ortho' => 'iban',
			],
		] );

		echo '<br />';
		echo '<br />';

		$this->do_settings_field( [
			'type'         => 'text',
			'field'        => 'sandbox-vin',
			'dir'          => 'rtl',
			'field_class'  => [ 'large-text', 'code-text' ],
			'option_group' => 'tools',
			'placeholder' => 'IRXXXXXXXXXXXXXXX',
			'data' => [
				'ortho' => 'vin',
			],
		] );

		echo '<br />';
		echo '<br />';

		$this->do_settings_field( [
			'type'         => 'text',
			'field'        => 'sandbox-plate',
			'dir'          => 'rtl',
			'field_class'  => [ 'large-text', 'code-text' ],
			'option_group' => 'tools',
			// 'placeholder' => '',
			'data' => [
				'ortho' => 'plate',
			],
		] );
	}

	public function reports_settings( $sub )
	{
		if ( ! class_exists( 'geminorum\\gNetwork\\Core\\Orthography' ) )
			return FALSE;

		if ( $this->check_settings( $sub, 'reports', 'per_page' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'reports', $sub );

				if ( gEditorial\Tablelist::isAction( 'cleanup_chars', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id )
						$count += $this->cleanup_post( $post_id );

					WordPress\Redirect::doReferer( [
						'message' => 'cleaned',
						'count'   => $count,
					] );
				}

				WordPress\Redirect::doReferer( 'huh' );
			}

			$this->register_button( 'cleanup_chars', _x( 'Cleanup Chars', 'Button', 'geditorial-ortho' ), TRUE );
		}
	}

	protected function render_reports_html( $uri, $sub )
	{
		$list  = $this->list_posttypes();
		$query = $extra = [];
		$char  = self::req( 'char', 'none' );

		if ( 'none' != $char )
			$query['s'] = $extra['char'] = $char;

		list( $posts, $pagination ) = gEditorial\Tablelist::getPosts( $query, $extra, array_keys( $list ), $this->get_sub_limit_option( $sub, 'reports' ) );

		$pagination['before'][] = Core\HTML::dropdown( [
			'ي' => _x( 'Arabic Yeh U+064A', 'Char Title', 'geditorial-ortho' ),
			'ك' => _x( 'Arabic Kaf U+0643', 'Char Title', 'geditorial-ortho' ),
		], [
			'name'       => 'char',
			'selected'   => self::req( 'char', 'none' ),
			'none_value' => 'none',
			'none_title' => gEditorial\Settings::showOptionNone(),
		] );

		$pagination['before'][] = gEditorial\Tablelist::filterPostTypes( $list );
		$pagination['before'][] = gEditorial\Tablelist::filterAuthors( $list );
		$pagination['before'][] = gEditorial\Tablelist::filterSearch( $list );

		$columns = [
			'_cb'     => 'ID',
			'ID'      => gEditorial\Tablelist::columnPostID(),
			'date'    => gEditorial\Tablelist::columnPostDate(),
			'type'    => gEditorial\Tablelist::columnPostType(),
			'title'   => gEditorial\Tablelist::columnPostTitle(),
			'excerpt' => gEditorial\Tablelist::columnPostExcerpt(),
		];

		if ( gEditorial()->enabled( 'meta' ) )
			$columns['meta'] = gEditorial()->module( 'meta' )->tableColumnPostMeta( array_keys( $list ) );

		return Core\HTML::tableList( $columns, $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', _x( 'Overview of Post Orthography', 'Header', 'geditorial-ortho' ) ),
			'empty'      => Services\CustomPostType::getLabel( 'post', 'not_found' ),
			'pagination' => $pagination,
		] );
	}
}
