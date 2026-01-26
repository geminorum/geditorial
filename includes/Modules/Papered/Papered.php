<?php namespace geminorum\gEditorial\Modules\Papered;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Papered extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\CoreMenuPage;
	use Internals\FramePage;
	use Internals\MetaBoxSupported;
	use Internals\PairedAdmin;
	use Internals\PairedCore;
	use Internals\PairedMetaBox;
	use Internals\PostMeta;
	use Internals\PrintPage;
	use Internals\ViewEngines;

	protected $deafults = [
		'multiple_instances' => TRUE,
		'subterms_support'   => TRUE,
	];

	public static function module()
	{
		return [
			'name'     => 'papered',
			'title'    => _x( 'Papered', 'Modules: Papered', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Print Profiles', 'Modules: Papered', 'geditorial-admin' ),
			'icon'     => 'printer',
			'access'   => 'beta',
			'keywords' => [
				'print',
				'pairedmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_general'         => [
				[
					'field'        => 'directed_posttypes',
					'type'         => 'checkboxes-values',
					'title'        => _x( 'Direct Post-types', 'Setting Title', 'geditorial-papered' ),
					'description'  => _x( 'Supported post-types to use profiles directly.', 'Setting Description', 'geditorial-papered' ),
					'string_empty' => _x( 'There are no directed post-types available!', 'Setting Empty', 'geditorial-papered' ),
					'values'       => $this->get_settings_posttypes_parents(),
				],
				'paired_exclude_terms' => [
					NULL,
					$this->constant( 'primary_taxonomy' ),
					$this->get_taxonomy_label( 'primary_taxonomy', 'no_terms' ),
				],
			],
			'_roles' => [
				'prints_roles',
			],
			'_supports' => [
				$this->settings_supports_option( 'primary_posttype', [
					'title',
					'editor',
					'custom-fields',
					// 'excerpt',
				] ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype' => 'print_profile',
			'primary_paired'   => 'print_profiles',
			'primary_subterm'  => 'print_profile_type',
			'primary_taxonomy' => 'print_profile_category',
			'flag_taxonomy'    => 'print_profile_flag',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype' => _n_noop( 'Print Profile', 'Print Profiles', 'geditorial-papered' ),
				'primary_paired'   => _n_noop( 'Print Profile', 'Print Profiles', 'geditorial-papered' ),
				'primary_subterm'  => _n_noop( 'Print Profile Type', 'Print Profile Types', 'geditorial-papered' ),
				'primary_taxonomy' => _n_noop( 'Print Profile Category', 'Print Profile Categories', 'geditorial-papered' ),
				'flag_taxonomy'    => _n_noop( 'Print Profile Flag', 'Print Profile Flags', 'geditorial-papered' ),
			],
			'labels' => [
				'flag_taxonomy' => [
					'menu_title' => _x( 'Flags', 'Menu Title', 'geditorial-papered' ),
				],
			],
			'fields' => [
				'papersizes' => [
					'a3__portrait'      => _x( 'A3 Portrait (297&times;420)', 'PaperSize', 'geditorial-papered' ),
					'a3__landscape'     => _x( 'A3 Landscape (420&times;297)', 'PaperSize', 'geditorial-papered' ),
					'a4__portrait'      => _x( 'A4 Portrait (210&times;297)', 'PaperSize', 'geditorial-papered' ),
					'a4__landscape'     => _x( 'A4 Landscape (297&times;210)', 'PaperSize', 'geditorial-papered' ),
					'a5__portrait'      => _x( 'A5 Portrait (148&times;210)', 'PaperSize', 'geditorial-papered' ),
					'a5__landscape'     => _x( 'A5 Landscape (210&times;148)', 'PaperSize', 'geditorial-papered' ),
					'letter__portrait'  => _x( 'Letter Portrait (216&times;280)', 'PaperSize', 'geditorial-papered' ),
					'letter__landscape' => _x( 'Letter Landscape (280&times;216)', 'PaperSize', 'geditorial-papered' ),
					'legal__portrait'   => _x( 'Legal Portrait (216&times;357)', 'PaperSize', 'geditorial-papered' ),
					'legal__landscape'  => _x( 'Legal Landscape (357&times;216)', 'PaperSize', 'geditorial-papered' ),
				],
				'sheetpaddings' => [
					'padding_5mm'  => _x( '5mm Sheet Padding', 'Sheet Padding', 'geditorial-papered' ),
					'padding_10mm' => _x( '10mm Sheet Padding', 'Sheet Padding', 'geditorial-papered' ),
					'padding_15mm' => _x( '15mm Sheet Padding', 'Sheet Padding', 'geditorial-papered' ),
					'padding_20mm' => _x( '20mm Sheet Padding', 'Sheet Padding', 'geditorial-papered' ),
					'padding_25mm' => _x( '25mm Sheet Padding', 'Sheet Padding', 'geditorial-papered' ),
				],
			],
		];

		return $strings;
	}

	public function get_global_fields()
	{
		$primary = $this->constant( 'primary_posttype' );

		return [
			'meta' => [
				$primary => [
					'over_title'  => [ 'type' => 'text' ],
					'print_title' => [ 'type' => 'text' ],
					'sub_title'   => [ 'type' => 'text' ],
					'print_date'  => [ 'type' => 'date' ],

					'date'      => [ 'type' => 'date' ],
					'datetime'  => [ 'type' => 'datetime' ],
					'datestart' => [ 'type' => 'datetime' ],
					'dateend'   => [ 'type' => 'datetime' ],

					'venue_string'   => [ 'type' => 'venue' ],
					'contact_string' => [ 'type' => 'contact' ], // url/email/phone
					'phone_number'   => [ 'type' => 'phone' ],
					'mobile_number'  => [ 'type' => 'mobile' ],

					// 'first_name' => [
					// 	'title'       => _x( 'First Name', 'Field Title', 'geditorial-papered' ),
					// 	'description' => _x( 'Given Name of the Person', 'Field Description', 'geditorial-papered' ),
					// ],
				],
			]
		];
	}


	protected function define_default_terms()
	{
		return [
			'primary_taxonomy' => [
				'featured'   => _x( 'Featured', 'Primary Taxonomy: Default Term', 'geditorial-papered' ), // TODO: display profile with `featured` on Tabloid Overviews
				'deprecated' => _x( 'Deprecated', 'Primary Taxonomy: Default Term', 'geditorial-papered' ),
			],
			'flag_taxonomy' => [
				'needs-libre-fonts'    => _x( 'Needs Libre Fonts', 'Flag Taxonomy: Default Term', 'geditorial-papered' ),  // FIXME: add support
				'needs-vazir-fonts'    => _x( 'Needs Vazir Fonts', 'Flag Taxonomy: Default Term', 'geditorial-papered' ),
				'needs-nastaliq-fonts' => _x( 'Needs Nastaliq Fonts', 'Flag Taxonomy: Default Term', 'geditorial-papered' ),  // FIXME: add support
				'needs-bootstrap-5'    => _x( 'Needs Bootstrap 5', 'Flag Taxonomy: Default Term', 'geditorial-papered' ),
				'needs-titr-fonts'     => _x( 'Needs Titr Fonts', 'Flag Taxonomy: Default Term', 'geditorial-papered' ), // FIXME: add support
				'needs-barcode'        => _x( 'Needs Barcode', 'Flag Taxonomy: Default Term', 'geditorial-papered' ),
				'needs-qrcode'         => _x( 'Needs QR-Code', 'Flag Taxonomy: Default Term', 'geditorial-papered' ),
				'needs-globalsummary'  => _x( 'Needs Global Summary', 'Flag Taxonomy: Default Term', 'geditorial-papered' ),
				'needs-securitytoken'  => _x( 'Needs Security Token', 'Flag Taxonomy: Default Term', 'geditorial-papered' ),
			],
		];
	}

	protected function paired_get_paired_constants()
	{
		return [
			'primary_posttype',
			'primary_paired',
			'primary_subterm',
			'primary_taxonomy',
			FALSE,  // hierarchical!
			TRUE,   // private
		];
	}

	public function init()
	{
		parent::init();

		// FIXME: handle caps
		$this->register_taxonomy( 'primary_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
			'show_in_menu'       => FALSE,
		], 'primary_posttype' );

		// FIXME: handle caps
		$this->register_taxonomy( 'flag_taxonomy', [
			'hierarchical' => TRUE,
			'public'       => FALSE,
			'rewrite'      => FALSE,
			'show_in_menu' => FALSE,
			'meta_box_cb'  => '__checklist_terms_callback',
		], 'primary_posttype' );

		$this->paired_register( [
			'public'       => FALSE,
			'rewrite'      => FALSE,
			'show_in_menu' => FALSE, // NOTE: better to be `FALSE`, adding parent-slug will override the `mainpage`
		], [
			'tinymce_disabled' => TRUE,
		] );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_for( 'meta', 'primary_posttype' );
	}

	public function admin_menu()
	{
		$this->_hook_menu_posttype( 'primary_posttype', 'themes.php' );
		$this->_hook_menu_taxonomy( 'primary_taxonomy', 'themes.php' );
		$this->_hook_menu_taxonomy( 'flag_taxonomy', 'options-general.php' );

		if ( $this->role_can( 'prints' ) )
			$this->_hook_submenu_adminpage( 'printpage', 'exist' );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'primary_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'themes.php' );

		} else if ( $this->constant( 'flag_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'options-general.php' );
			$this->modulelinks__register_headerbuttons();

		} else if ( $screen->post_type == $this->constant( 'primary_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter_string( 'parent_file', 'themes.php' );
				$this->filter_string( 'wp_default_editor', 'html' );

				$this->_hook_paired_mainbox( $screen );
				$this->_hook_paired_listbox( $screen );
				$this->_register_lonebox_fields( $screen );

			} else if ( 'edit' == $screen->base ) {

				// TODO: add row action for preview/print
			}

		} else if ( 'post' == $screen->base ) {

			if ( $this->posttype_supported( $screen->post_type ) ) {

				// display main pairedbox to connect with selected print profile
				// display secondary metaboxbox with action hook
				// - to display print profile for each connected profiles,
				// - likely also paired to this posts

				// WTF: or print profiles can expand for more than one page: loop with paired items and pagebreak

				// $this->_hook_paired_pairedbox( $screen );
				// $this->_hook_paired_store_metabox( $screen->post_type );
			}

			if ( $this->in_setting( $screen->post_type, 'directed_posttypes' ) ) {

				if ( $this->role_can( 'prints' ) ) {

					$this->_hook_general_supportedbox( $screen, 'printingbox', 'side', 'low' );

					$this->enqueue_asset_js( [
						'strings' => $this->get_strings( $screen->base, 'js' ),
						'link'    => $this->get_printpage_url(),
					], $screen, [ Scripts::enqueueColorBox() ] );
				}
			}
		}
	}

	// TODO: use static cache
	private function _get_profile_config( $post, $default = NULL )
	{
		if ( is_null( $default ) )
			$default = [
				'name'          => 'A4',
				'title'         => 'A4 Portrait',
				'page_size'     => 'A4',
				'orientation'   => 'portrait',
				'sheet_padding' => '10mm',
				'row_per_sheet' => '0',
				'body_class'    => [
					'A4',
				],
			];

		if ( ! $post = WordPress\Post::get( $post ) )
			return $default;

		$field        = 'sheetpadding';
		$sheetpadding = $this->fetch_postmeta( $post->ID, 'undefined', $this->get_postmeta_key( $field ) );
		$field        = 'rowpersheet';
		$rowpersheet  = $this->fetch_postmeta( $post->ID, '0', $this->get_postmeta_key( $field ) );
		$paddings     = [
			'padding_5mm'  => '5mm',
			'padding_10mm' => '10mm',
			'padding_15mm' => '15mm',
			'padding_20mm' => '20mm',
			'padding_25mm' => '25mm',
		];

		$padding = array_key_exists( $sheetpadding, $paddings ) ? $paddings[$sheetpadding] : '10mm';

		$field   = 'papersize';
		// $default = 'undefined';
		// $papersize = WordPress\Taxonomy::getPostTerms( $this->constant( 'size_taxonomy' ), $post );
		$papersize = $this->fetch_postmeta( $post->ID, 'undefined', $this->get_postmeta_key( $field ) );
		$sizes     = $this->_get_papersizes();

		if ( empty( $papersize ) || ! array_key_exists( $papersize, $sizes ) )
			return $default;

		switch ( $papersize ) {

			case 'legal__portrait': return [
				'name'          => $papersize,
				'title'         => $sizes[$papersize],
				'sheet_padding' => $padding,
				'row_per_sheet' => $rowpersheet,
				'page_size'     => 'legal',
				'orientation'   => 'portrait',
				'body_class'    => [
					'legal',
				],
			];

			case 'legal__landscape': return [
				'name'          => $papersize,
				'title'         => $sizes[$papersize],
				'sheet_padding' => $padding,
				'row_per_sheet' => $rowpersheet,
				'page_size'     => 'legal landscape',
				'orientation'   => 'landscape',
				'body_class'    => [
					'legal',
					'landscape',
				],
			];

			case 'letter__portrait': return [
				'name'          => $papersize,
				'title'         => $sizes[$papersize],
				'sheet_padding' => $padding,
				'row_per_sheet' => $rowpersheet,
				'page_size'     => 'letter',
				'orientation'   => 'portrait',
				'body_class'    => [
					'letter',
				],
			];

			case 'letter__landscape': return [
				'name'          => $papersize,
				'title'         => $sizes[$papersize],
				'sheet_padding' => $padding,
				'row_per_sheet' => $rowpersheet,
				'page_size'     => 'letter landscape',
				'orientation'   => 'landscape',
				'body_class'    => [
					'letter',
					'landscape',
				],
			];

			case 'a3__portrait': return [
				'name'          => $papersize,
				'title'         => $sizes[$papersize],
				'sheet_padding' => $padding,
				'row_per_sheet' => $rowpersheet,
				'orientation'   => 'portrait',
				'page_size'     => 'A3',
				'body_class'    => [
					'A3',
				],
			];

			case 'a3__landscape': return [
				'name'          => $papersize,
				'title'         => $sizes[$papersize],
				'sheet_padding' => $padding,
				'row_per_sheet' => $rowpersheet,
				'orientation'   => 'landscape',
				'page_size'     => 'A3 landscape',
				'body_class'    => [
					'A3',
					'landscape',
				],
			];

			case 'a4__portrait': return [
				'name'          => $papersize,
				'title'         => $sizes[$papersize],
				'sheet_padding' => $padding,
				'row_per_sheet' => $rowpersheet,
				'orientation'   => 'portrait',
				'page_size'     => 'A4',
				'body_class'    => [
					'A4',
				],
			];

			case 'a4__landscape': return [
				'name'          => $papersize,
				'title'         => $sizes[$papersize],
				'sheet_padding' => $padding,
				'row_per_sheet' => $rowpersheet,
				'orientation'   => 'landscape',
				'page_size'     => 'A4 landscape',
				'body_class'    => [
					'A4',
					'landscape',
				],
			];

			case 'a5__landscape': return [
				'name'          => $papersize,
				'title'         => $sizes[$papersize],
				'sheet_padding' => $padding,
				'row_per_sheet' => $rowpersheet,
				'orientation'   => 'landscape',
				'page_size'     => 'A5 landscape',
				'body_class'    => [
					'A5',
					'landscape',
				],
			];

			default:
			case 'a5__portrait': return [
				'name'          => $papersize,
				'title'         => $sizes[$papersize],
				'sheet_padding' => $padding,
				'row_per_sheet' => $rowpersheet,
				'orientation'   => 'portrait',
				'page_size'     => 'A5',
				'body_class'    => [
					'A5',
				],
			];
		}

		return $default;
	}

	protected function printpage_get_layout_bodyclass( $profile = FALSE )
	{
		$list   = [];
		$config = $this->_get_profile_config( $profile );

		return array_merge( $list, (array) $config['body_class'] );
	}

	protected function printpage_get_layout_wrapclass( $profile = FALSE )
	{
		$config = $this->_get_profile_config( $profile );

		$list = [
			'sheet',
			'padding-'.$config['sheet_padding'],
		];

		return $list;
	}

	public function _load_printpage_adminpage()
	{
		if ( ! $profile = WordPress\Post::get( self::req( 'profile', FALSE ) ) )
			return;

		$taxonomy = $this->constant( 'flag_taxonomy' );

		if ( has_term( 'needs-barcode', $taxonomy, $profile ) )
			Scripts::enqueueJSBarcode();

		if ( has_term( 'needs-qrcode', $taxonomy, $profile ) )
			Scripts::enqueueQRCodeSVG();
	}

	public function printpage_render_head( $profile = FALSE )
	{
		Core\HTML::linkStyleSheet( GEDITORIAL_URL.'assets/packages/paper-css/paper-0.4.1.css', GEDITORIAL_VERSION, 'all' );

		if ( $post = WordPress\Post::get( $profile ) ) {

			$taxonomy = $this->constant( 'flag_taxonomy' );

			if ( has_term( 'needs-vazir-fonts', $taxonomy, $post ) )
				Scripts::linkVazirMatn();

			if ( has_term( 'needs-bootstrap-5', $taxonomy, $post ) )
				Scripts::linkBootstrap5();

			if ( $styles = $this->_get_template_styles( $post, 'display' ) )
				printf( '<style>%s</style>', $styles );
		}

		$config = $this->_get_profile_config( $profile );

		printf( '<style>@page { size: %s }</style>', $config['page_size'] );
	}

	// NOTE: prepend source title before print-page HTML title
	public function printpage_get_layout_pagetitle( $profile = FALSE )
	{
		$title = WordPress\Post::title( $profile );

		if ( $source = WordPress\Post::get( self::req( 'source', FALSE ) ) )
			$title = sprintf( '%s - %s', WordPress\Post::title( $source ), $title );

		return $title;
	}

	public function printpage_render_contents( $profile = FALSE )
	{
		if ( ! $post = WordPress\Post::get( $profile ) )
			return Core\HTML::desc( _x( 'There are no print profiles available!', 'Message', 'geditorial-papered' ) );

		$source = WordPress\Post::get( self::req( 'source', FALSE ) );
		$config = $this->_get_profile_config( $profile );

		$this->_render_view_for_post( $post, $source, 'printpage', $config );
	}

	// NOTE: better to not have any wrapper!
	private function _render_view_for_post( $profile, $source, $context, $config )
	{
		$before = $this->_get_view_part_before( $profile, $context );
		$after  = $this->_get_view_part_after( $profile, $context );
		$rows   = $this->_get_view_part_for_rows( $profile, $context );
		$sheet  = $this->_get_view_part_for_sheet( $profile, $context );
		$data   = $this->_get_view_data_for_post( $profile, $source, $context, $config );
		$list   = $this->_get_view_list_for_post( $profile, $source, $context, $config, empty( $data['profile']['flags'] ) ? [] : $data['profile']['flags'] );

		if ( ! empty( $config['row_per_sheet'] ) && ! empty( $list ) ) {

			$this->viewengine__render_string( $before, $data );

			$chunks = array_chunk( $list, (int) $config['row_per_sheet'] );
			$pages  = count( $chunks );

			foreach ( $chunks as $offset => $chunk ) {

				$data_sheet = array_merge( $data, [
					'list'  => $chunk,
					'paged' => Core\Number::format( $offset + 1 ),
					'rows'  => $this->viewengine__render_string( $rows, [
						'data'  => $data,
						'list'  => $chunk,
						'paged' => Core\Number::format( $offset + 1 ),
					], FALSE ),
				] );

				$this->viewengine__render_string( $sheet, $data_sheet );

				if ( $pages > 1 && $pages !== ( $offset + 1 ) )
					$this->printpage__render_pagebreak( $profile );
			}

			$this->viewengine__render_string( $after, $data );

		} else {

			$data_sheet = array_merge( $data, [
				'list'  => $list ?: [],
				'paged' => 1,
				'rows'  => $this->viewengine__render_string( $rows, [
					'data'  => $data,
					'list'  => $list ?: [],
					'paged' => 1,
				], FALSE ),
			] );

			$this->viewengine__render_string( $before, $data_sheet );
			$this->viewengine__render_string( $sheet, $data_sheet );
			$this->viewengine__render_string( $after, $data_sheet );
		}
	}

	private function _get_view_list_for_post( $profile, $source, $context, $config, $flags = [] )
	{
		$data  = [];
		$meta  = gEditorial()->enabled( 'meta' );
		$units = gEditorial()->enabled( 'units' );
		$list  = $this->filters( 'view_list', [], $source, $profile, $context, $data );
		$index = 1;

		foreach ( $list as $item ) {

			$row = [
				'rawpost'  => ModuleHelper::getPostProps( $item ),
				'rawmeta'  => ModuleHelper::getPostMetas( $item ),
				'tokens'   => ModuleHelper::getGeneralTokens( $item ),
				'flags'    => $flags,
				'rendered' => [
					'index'     => Core\Number::format( $index ),
					'posttitle' => WordPress\Post::title( $item ),
					'fulltitle' => WordPress\Post::fullTitle( $item ),
				],
			];

			if ( $meta )
				$row['metadata'] = ModuleHelper::getPosttypeFieldsData( $item, 'meta' );

			if ( $units )
				$row['unitsdata'] = ModuleHelper::getPosttypeFieldsData( $item, 'units' );

			$data[] = $this->filters( 'view_list_item', $row, $item, $index, $source, $profile, $context, $list );

			++$index;
		}

		return $data;
	}

	// TODO: token for page-break
	private function _get_view_data_for_post( $profile, $source, $context, $config )
	{
		$data  = [];
		$meta  = gEditorial()->enabled( 'meta' );
		$units = gEditorial()->enabled( 'units' );

		if ( $profile ) {

			$data['profile'] = [
				'rawpost'  => ModuleHelper::getPostProps( $profile ),
				'rawmeta'  => ModuleHelper::getPostMetas( $profile ),
				'tokens'   => ModuleHelper::getGeneralTokens( $profile ),
				'flags'    => WordPress\Taxonomy::getPostTerms( $this->constant( 'flag_taxonomy' ), $profile, FALSE, 'slug' ),
				'rendered' => [
					'posttitle' => WordPress\Post::title( $profile ),
					'fulltitle' => WordPress\Post::fullTitle( $profile ),
				],
			];

			if ( $meta )
				$data['profile']['metadata'] = ModuleHelper::getPosttypeFieldsData( $profile, 'meta' );

			if ( $units )
				$data['profile']['unitsdata'] = ModuleHelper::getPosttypeFieldsData( $profile, 'units' );
		}

		if ( $source ) {

			$data['source'] = [
				'rawpost'  => ModuleHelper::getPostProps( $source ),
				'rawmeta'  => ModuleHelper::getPostMetas( $source ),
				'tokens'   => ModuleHelper::getGeneralTokens( $source ),
				'rendered' => [
					'posttitle' => WordPress\Post::title( $source ),
					'fulltitle' => WordPress\Post::fullTitle( $source ),
				],
			];

			if ( $meta )
				$data['source']['metadata'] = ModuleHelper::getPosttypeFieldsData( $source, 'meta' );

			if ( $units )
				$data['source']['unitsdata'] = ModuleHelper::getPosttypeFieldsData( $source, 'units' );
		}

		return $this->filters( 'view_data_for_post', $data, $profile, $source, $context );
	}

	private function _get_view_part_for_sheet( $profile, $context )
	{
		return $profile->post_content;
	}

	private function _get_view_part_for_rows( $profile, $context )
	{
		return $this->fetch_postmeta( $profile->ID, '', $this->get_postmeta_key( 'template_rows' ) ) ?: '';
	}

	private function _get_view_part_before( $profile, $context )
	{
		return $this->fetch_postmeta( $profile->ID, '', $this->get_postmeta_key( 'template_before' ) ) ?: '';
	}

	private function _get_view_part_after( $profile, $context )
	{
		return $this->fetch_postmeta( $profile->ID, '', $this->get_postmeta_key( 'template_after' ) ) ?: '';
	}

	private function _get_template_styles( $profile, $context )
	{
		return $this->fetch_postmeta( $profile->ID, '', $this->get_postmeta_key( 'template_styles' ) ) ?: '';
	}

	protected function _render_mainbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'mainbox';

		// MetaBox::fieldPostMenuOrder( $object );
		// MetaBox::fieldPostParent( $object );

		$field   = 'papersize';
		$default = 'undefined';
		$html    = Core\HTML::dropdown( $this->_get_papersizes(), [
			'none_title' => _x( '&ndash; Select Peper Size &ndash;', 'None Title', 'geditorial-papered' ),
			'none_value' => $default,
			'name'       => $this->classs( $context, $field ),
			'selected'   => $this->fetch_postmeta( $object->ID, $default, $this->get_postmeta_key( $field ) ),
		] );

		echo Core\HTML::wrap( $html, 'field-wrap -select' );

		$field   = 'sheetpadding';
		$default = 'undefined';
		$html    = Core\HTML::dropdown( $this->_get_sheetpaddings(), [
			'none_title' => _x( '&ndash; Select Sheet Padding &ndash;', 'None Title', 'geditorial-papered' ),
			'none_value' => $default,
			'name'       => $this->classs( $context, $field ),
			'selected'   => $this->fetch_postmeta( $object->ID, $default, $this->get_postmeta_key( $field ) ),
		] );

		echo Core\HTML::wrap( $html, 'field-wrap -select' );

		$field   = 'rowpersheet';
		$default = '0';
		$html    = Core\HTML::tag( 'input', [
			'title'       => _x( 'Row per Sheet', 'Input Title', 'geditorial-papered' ),
			'type'        => 'number',
			// 'dir'         => 'ltr',
			'name'        => $this->classs( $context, $field ),
			'value'       => $this->fetch_postmeta( $object->ID, $default, $this->get_postmeta_key( $field ) ) ?: '',
			'placeholder' => $default,
			'data-ortho'  => 'number',
		] );

		echo Core\HTML::wrap( $html, 'field-wrap -inputnumber' );

		$posttypes = $this->get_setting( 'directed_posttypes', [] );

		if ( count( $posttypes ) ) {

			$field   = 'posttype';
			$default = 'undefined';
			$html    = Core\HTML::dropdown( Core\Arraay::keepByKeys( $this->all_posttypes(), $posttypes ), [
				'none_title' => _x( '&ndash; Select Direct Post-Type &ndash;', 'None Title', 'geditorial-papered' ),
				'none_value' => $default,
				'name'       => $this->classs( $context, $field ),
				'selected'   => $this->fetch_postmeta( $object->ID, $default, $this->get_postmeta_key( $field ) ),
			] );

			echo Core\HTML::wrap( $html, 'field-wrap -select' );
		}

		$this->_render_printbuttons( $object );
	}

	public function store_mainbox_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, 'primary_posttype' ) || empty( $_POST ) )
			return;

		if ( is_null( $context ) )
			$context = 'mainbox';

		if ( ! $this->nonce_verify( $context ) )
			return;

		$fields = [
			'sheetpadding'    => 'undefined',
			'rowpersheet'     => '0',
			'papersize'       => 'undefined',
			'posttype'        => 'undefined',
			'template_rows'   => '',
			'template_before' => '',
			'template_after'  => '',
			'template_styles' => '',
		];

		foreach ( $fields as $field => $default ) {

			$name = $this->classs( $context, $field );

			if ( ! array_key_exists( $name, $_POST ) )
				continue;

			if ( in_array( $field, [ 'sheetpadding', 'papersize', 'posttype' ], TRUE ) )
				$value = sanitize_title( $_POST[$name] ); // FIXME: better sanitization!

			else
				$value = Core\Text::normalizeWhitespace( $_POST[$name] );

			if ( in_array( $value, [ $default, 'none', 'undefined', '0' ], TRUE ) )
				$value = FALSE;

			$this->store_postmeta( $post->ID, $value, $this->get_postmeta_key( $field ) );
		}
	}

	protected function _register_lonebox_fields( $screen )
	{
		$selectors = [];
		$fields    = [
			'template_rows'   => _x( 'Template for Rows', 'MetaBox Title', 'geditorial-papered' ),
			'template_before' => _x( 'Template Before', 'MetaBox Title', 'geditorial-papered' ),
			'template_after'  => _x( 'Template After', 'MetaBox Title', 'geditorial-papered' ),
			'template_styles' => _x( 'Template Styles', 'MetaBox Title', 'geditorial-papered' ),
		];

		foreach ( $fields as $field => $title ) {

			$metabox = $this->classs( $screen->post_type, $field );

			MetaBox::classEditorBox( $screen, $metabox );

			add_meta_box( $metabox,
				$title,
				[ $this, 'render_lonebox_metabox' ],
				$screen,
				'advanced',
				'high',
				[
					'posttype'    => $screen->post_type,
					'metabox'     => $metabox,
					'field_name'  => $field,
					'field_title' => $title,
					'context'     => 'mainbox',            // to save via `store_mainbox_metabox()`
				]
			);

			$selectors[] = sprintf( '#qt_geditorial-papered-lonebox-%s_textdirection', str_replace( '_', '-', $field ) );
		}

		if ( ! Core\L10n::rtl() )
			return;

		$selectors[] = '#qt_content_textdirection'; // default content editor

		Scripts::inlineScript( $this->classs( 'quicktags' ), 'jQuery(function($){$(window).on("load",function(){$("'.implode( ',', $selectors ).'").click();});});' );
	}

	public function render_lonebox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$field = $box['args']['field_name'];
		$title = $box['args']['field_title'];

		$atts = [
			'textarea_name' => $this->classs( $box['args']['context'], $field ),
			'editor_class'  => 'editor-status-counts textarea-autosize',
			'tinymce'       => FALSE,
		];

		$value = $this->fetch_postmeta( $post->ID, '', $this->get_postmeta_key( $field ) ) ?: '';

		MetaBox::fieldEditorBox( $value, $this->classs( 'lonebox', $field ), $title, $atts );
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'printingbox';

		$posttype = $this->constant( 'primary_posttype' );
		$link     = $this->get_printpage_url();
		$name     = Services\CustomPostType::getLabel( $posttype, 'singular_name' );
		$post     = WordPress\Post::title( $object, '' );

		echo Core\HTML::wrap( Core\HTML::dropdown( $this->_get_profiles_for_posttype( $object ), [
			'id'         => $this->classs( 'printprofile' ),
			'prop'       => 'post_title',
			'value'      => 'ID',
			'none_title' => Services\CustomPostType::getLabel( $posttype, 'show_option_select' ),
		] ), 'field-wrap -select' );

		/* translators: `%1$s`: icon markup, `%2$s`: posttype singular name */
		$default = _x( '%1$s Preview Profile', 'Button', 'geditorial-papered' );
		$title   = $this->get_string( $context.'_preview_title', $object->post_type, 'metabox', NULL );
		$text    = $this->get_string( $context.'_preview_text', $object->post_type, 'metabox', $default );

		$html = Core\HTML::tag( 'a', [
			'id'       => $this->classs( 'printpreview' ),
			'rel'      => add_query_arg( [ 'target' => 'preview', 'source' => $object->ID ], $link ),
			'title'    => $title ? sprintf( $title, $post, $name ) : FALSE,
			'class'    => [ '-button', 'button', '-button-icon', 'do-colorbox-iframe' ],
			'disabled' => TRUE,
		], sprintf( $text, Services\Icons::get( 'welcome-view-site' ), $name ) );

		/* translators: `%1$s`: icon markup, `%2$s`: posttype singular name */
		$default = _x( '%1$s Print Profile', 'Button', 'geditorial-papered' );
		$title   = $this->get_string( $context.'_print_title', $object->post_type, 'metabox', NULL );
		$text    = $this->get_string( $context.'_print_text', $object->post_type, 'metabox', $default );

		$html.= Core\HTML::tag( 'a', [
			'id'       => $this->classs( 'printprint' ),
			'rel'      => add_query_arg( [ 'target' => 'print', 'source' => $object->ID ], $link ),
			'title'    => $title ? sprintf( $title, $post, $name ) : FALSE,
			'class'    => [ '-button', 'button', '-button-icon' ],
			'disabled' => TRUE,
		], sprintf( $text, Services\Icons::get( 'printer' ), $name ) );

		$html.= Core\HTML::tag( 'iframe', [
			'id'     => $this->classs( 'printiframe' ),
			'src'    => '',
			'class'  => '-hidden-print-iframe',
			'width'  => '0',
			'height' => '0',
			'border' => '0',
		], '' );

		echo Core\HTML::wrap( $html, 'field-wrap -buttons -buttons-half' );
	}

	// @REF: `render_print_button()`
	private function _render_printbuttons( $profile, $source = FALSE, $context = NULL )
	{
		if ( is_null( $context ) )
			$context = 'printingbox';

		$args = [
			'profile'  => $profile->ID,
			'target'   => 'preview',
			'noheader' => 1,
		];

		if ( $source = WordPress\Post::get( $source ) )
			$args['source'] = $source->ID;

		$link = $this->get_adminpage_url( TRUE, $args, 'printpage' );
		$name = Services\CustomPostType::getLabel( $source ? $source->post_type : $profile->post_type, 'singular_name' );
		$post = WordPress\Post::title( $source, '' );

		/* translators: `%1$s`: icon markup, `%2$s`: posttype singular name */
		$default = _x( '%1$s Preview Profile', 'Button', 'geditorial-papered' );
		$title   = $this->get_string( $context.'_preview_title', $source ? $source->post_type : $profile->post_type, 'metabox', NULL );
		$text    = $this->get_string( $context.'_preview_text', $source ? $source->post_type : $profile->post_type, 'metabox', $default );

		$html = $this->framepage_get_mainlink_for_post( $profile, [
			'link'         => add_query_arg( [ 'target' => 'preview' ], $link ),
			'context'      => $context,
			'link_context' => 'printpage',
			'refkey'       => 'profile',
			'target'       => 'preview',
			'maxwidth'     => '920px',
			'title'        => $title,
			'text'         => $text,
			'icon'         => 'welcome-view-site',
			'extra'        => [
				'button',
				'-button',
				'-button-icon',
			],
		] );

		/* translators: `%1$s`: icon markup, `%2$s`: posttype singular name */
		$default = _x( '%1$s Print Profile', 'Button', 'geditorial-papered' );
		$title   = $this->get_string( $context.'_print_title', $source ? $source->post_type : $profile->post_type, 'metabox', NULL );
		$text    = $this->get_string( $context.'_print_text', $source ? $source->post_type : $profile->post_type, 'metabox', $default );

		// prefix to avoid conflicts
		$func = $this->hook( 'printIframe' );
		$id   = $this->classs( 'printiframe' );

		$html.= Core\HTML::tag( 'a', [
			'href'  => '#',
			'title' => $title ? sprintf( $title, $post, $name ) : FALSE,
			'class' => [
				'button',
				'-button',
				'-button-icon',
			],
			'onclick' => $func.'("'.$id.'")',
		], sprintf( $text, Services\Icons::get( 'printer' ), $name ) );

		echo Core\HTML::wrap( $html, 'field-wrap -buttons -buttons-half' );

		echo Core\HTML::tag( 'iframe', [
			'id'     => $id,
			'src'    => add_query_arg( [ 'target' => 'print' ], $link ),
			'class'  => '-hidden-print-iframe',
			'width'  => '0',
			'height' => '0',
			'border' => '0',
			'style'  => 'display:none',
		], '' );

		// @REF: https://hdtuto.com/article/print-iframe-content-using-jquery-example
		echo '<script>function '.$func.'(id){var frm=document.getElementById(id).contentWindow;frm.focus();frm.print();return false;}</script>';
	}

	private function _get_profiles_for_posttype( $post )
	{
		$args = [
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => [ [
				'key'     => $this->get_postmeta_key( 'posttype' ),
				'value'   => $post->post_type,
				'compare' => '=',
			] ],
			'update_post_meta_cache' => TRUE,
		];

		$profiles = WordPress\PostType::getIDs( $this->constant( 'primary_posttype' ), $args, '' );

		return $this->filters( 'profiles_for_posttype', $profiles, $post );
	}

	private function _get_papersizes()
	{
		return $this->get_strings( 'papersizes', 'fields' );
	}

	private function _get_sheetpaddings()
	{
		return $this->get_strings( 'sheetpaddings', 'fields' );
	}
}
