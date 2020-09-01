<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Database;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\MetaBoxes\Meta as ModuleMetaBox;
use geminorum\gEditorial\Templates\Meta as ModuleTemplate;

class Meta extends gEditorial\Module
{
	public $meta_key = '_gmeta';

	protected $priority_init           = 12;
	protected $priority_current_screen = 12;

	protected $partials = [ 'Metabox', 'Templates' ];

	protected $disable_no_posttypes = TRUE;

	protected $caps = [
		'tools' => 'import',
	];

	public static function module()
	{
		return [
			'name'  => 'meta',
			'title' => _x( 'Meta', 'Modules: Meta', 'geditorial' ),
			'desc'  => _x( 'Curated Metadata', 'Modules: Meta', 'geditorial' ),
			'icon'  => 'tag',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'fields_option'    => 'fields_option',
			'_general' => [
				'insert_content_enabled',
				[
					'field'       => 'overwrite_author',
					'title'       => _x( 'Overwrite Author', 'Setting Title', 'geditorial-meta' ),
					'description' => _x( 'Replaces user display name with author meta field data.', 'Setting Description', 'geditorial-meta' ),
				],
				[
					'field'       => 'before_source',
					'type'        => 'text',
					'title'       => _x( 'Before Source', 'Setting Title', 'geditorial-meta' ),
					'description' => _x( 'Used as default text before the source links.', 'Setting Description', 'geditorial-meta' ),
					'default'     => _x( 'Source:', 'Setting Default', 'geditorial-meta' ),
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'label_tax' => 'label',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'titles' => [
				'over_title' => _x( 'OverTitle', 'Titles', 'geditorial-meta' ),
				'sub_title'  => _x( 'SubTitle', 'Titles', 'geditorial-meta' ),
				'byline'     => _x( 'Byline', 'Titles', 'geditorial-meta' ),
				'lead'       => _x( 'Lead', 'Titles', 'geditorial-meta' ),
				'label'      => _x( 'Label', 'Titles', 'geditorial-meta' ),
				'label_tax'  => _x( 'Label Taxonomy', 'Titles', 'geditorial-meta' ),

				'published'    => _x( 'Published', 'Titles', 'geditorial-meta' ),
				'source_title' => _x( 'Source Title', 'Titles', 'geditorial-meta' ),
				'source_url'   => _x( 'Source URL', 'Titles', 'geditorial-meta' ),
				'highlight'    => _x( 'Highlight', 'Titles', 'geditorial-meta' ),
				'dashboard'    => _x( 'Dashboard', 'Titles', 'geditorial-meta' ),
				'abstract'     => _x( 'Abstract', 'Titles', 'geditorial-meta' ),

				// combined fields
				'source' => _x( 'Source', 'Titles', 'geditorial-meta' ),
			],
			'descriptions' => [
				'over_title' => _x( 'Text to place over the content title', 'Descriptions', 'geditorial-meta' ),
				'sub_title'  => _x( 'Text to place under the content title', 'Descriptions', 'geditorial-meta' ),
				'byline'     => _x( 'Text to override the content author', 'Descriptions', 'geditorial-meta' ),
				'lead'       => _x( 'Notes to place before the content text', 'Descriptions', 'geditorial-meta' ),
				'label'      => _x( 'Text to indicate that the content is part of a column', 'Descriptions', 'geditorial-meta' ),
				'label_tax'  => _x( 'Taxonomy for better categorizing columns', 'Descriptions', 'geditorial-meta' ),

				'published'    => _x( 'Text to indicate the original date of the content', 'Descriptions', 'geditorial-meta' ),
				'source_title' => _x( 'Custom title for the source of the content', 'Descriptions', 'geditorial-meta' ),
				'source_url'   => _x( 'Custom URL to the source of the content', 'Descriptions', 'geditorial-meta' ),
				'highlight'    => _x( 'Notes highlighted about the content', 'Descriptions', 'geditorial-meta' ),
				'dashboard'    => _x( 'Custom HTML content on the dashboard', 'Descriptions', 'geditorial-meta' ),
				'abstract'     => _x( 'Brief summary of the content', 'Descriptions', 'geditorial-meta' ),

				'source' => _x( 'Source of the content', 'Descriptions', 'geditorial-meta' ),
			],
			'noops' => [
				'label_tax' => _n_noop( 'Column Header', 'Column Headers', 'geditorial-meta' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'meta_column_title'   => _x( 'Metadata', 'Column Title', 'geditorial-meta' ),
			'author_column_title' => _x( 'Author', 'Column Title', 'geditorial-meta' ),
			'meta_box_title'      => _x( 'Metadata', 'Meta Box Title', 'geditorial-meta' ),
			'meta_box_action'     => _x( 'Configure', 'Meta Box Action Title', 'geditorial-meta' ),
		];

		$strings['terms'] = [
			'label_tax' => [
				'introduction' => _x( 'Introduction', 'Default Term', 'geditorial-meta' ),
				'interview'    => _x( 'Interview', 'Default Term', 'geditorial-meta' ),
				'review'       => _x( 'Review', 'Default Term', 'geditorial-meta' ),
				'report'       => _x( 'Report', 'Default Term', 'geditorial-meta' ),
			],
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'post' => [
				'over_title' => [ 'type' => 'title_before' ],
				'sub_title'  => [ 'type' => 'title_after' ],
				'byline'     => [ 'type' => 'text', 'quickedit' => TRUE ],
				'lead'       => [ 'type' => 'postbox_html' ], // OLD: 'postbox_legacy'
				'label'      => [ 'type' => 'text' ],
				'label_tax'  => [ 'type' => 'term', 'tax' => $this->constant( 'label_tax' ) ],

				'published'    => [ 'type' => 'text', 'quickedit' => TRUE ],
				'source_title' => [ 'type' => 'text' ],
				'source_url'   => [ 'type' => 'link' ],
				'highlight'    => [ 'type' => 'note' ],
				'dashboard'    => [ 'type' => 'postbox_html' ], // or 'postbox_tiny'
				'abstract'     => [ 'type' => 'postbox_html' ], // or 'postbox_tiny'
			],
			'page' => [
				'over_title' => [ 'type' => 'title_before' ],
				'sub_title'  => [ 'type' => 'title_after' ],
			],
		];
	}

	private function get_posttypes_support_meta()
	{
		$posttypes = [ 'post' ];
		$supported = get_post_types_by_support( 'editorial-meta' );
		$excludes  = [
			'attachment',
			'page',
		];

		$list = array_diff( array_merge( $posttypes, $supported ), $excludes );

		return $this->filters( 'support_posttypes', $list );
	}

	public function before_settings( $module = FALSE )
	{
		if ( isset( $_POST['install_def_label_tax'] ) )
			$this->insert_default_terms( 'label_tax' );

		$this->help_tab_default_terms( 'label_tax' );
	}

	public function default_buttons( $module = FALSE )
	{
		parent::default_buttons( $module );

		$this->register_button( 'install_def_label_tax', _x( 'Install Default Column Headers', 'Button', 'geditorial-meta' ) );
	}

	public function init()
	{
		parent::init();

		$label_tax_tax_posttypes = [];

		foreach ( $this->posttypes() as $posttype )
			if ( in_array( 'label_tax', $this->posttype_fields( $posttype ) ) )
				$label_tax_tax_posttypes[] = $posttype;

		if ( count( $label_tax_tax_posttypes ) )
			$this->register_taxonomy( 'label_tax', [
				'show_in_rest' => FALSE, // temporarily disable in block editor
			], $label_tax_tax_posttypes );

		// default fields for custom posttypes
		foreach ( $this->get_posttypes_support_meta() as $posttype )
			$this->add_posttype_fields( $posttype, $this->fields['post'] );

		$this->add_posttype_fields( 'page' );

		if ( ! is_admin() )
			return;

		$this->filter_module( 'importer', 'fields', 2 );
		$this->filter_module( 'importer', 'prepare', 4 );
		$this->action_module( 'importer', 'saved', 5 );
	}

	public function template_redirect()
	{
		if ( ! is_singular( $this->posttypes() ) )
			return;

		if ( $this->get_setting( 'insert_content_enabled' ) ) {
			add_action( $this->base.'_content_before', [ $this, 'content_before' ], 50 );
			add_action( $this->base.'_content_after', [ $this, 'content_after' ], 50 );
		}

		if ( $this->get_setting( 'overwrite_author', FALSE ) )
			$this->filter( 'the_author', 1, 9 );
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, $this->posttypes() ) ) {
			$this->_edit_screen( $_REQUEST['post_type'] );
			$this->_hook_default_rows();
			$this->_hook_store_metabox( $_REQUEST['post_type'] );
		}
	}

	public function current_screen( $screen )
	{
		if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( ! in_array( $screen->base, [ 'post', 'edit' ] ) )
				return;

			$fields = $this->get_posttype_fields( $screen->post_type );

			// bail if no fields enabled for this posttype
			if ( ! count( $fields ) )
				return;

			if ( 'post' == $screen->base ) {

				$contexts   = Arraay::column( $fields, 'context' );
				$metabox_id = $this->classs( $screen->post_type );

				$mainbox = $this->filters( 'mainbox_callback', in_array( 'mainbox', $contexts ), $screen->post_type );

				if ( TRUE === $mainbox )
					$mainbox = [ $this, 'render_mainbox_metabox' ];

				if ( $mainbox && is_callable( $mainbox ) )
					add_meta_box( $metabox_id,
						$this->get_meta_box_title(),
						$mainbox,
						$screen,
						'side',
						'high',
						[
							'posttype'   => $screen->post_type,
							'metabox_id' => $metabox_id,
						]
					);

				$nobox = $this->filters( 'nobox_callback', in_array( 'nobox', $contexts ), $screen->post_type );

				if ( TRUE === $nobox )
					add_action( 'dbx_post_sidebar', [ $this, 'render_nobox_fields' ], 10, 1 );

				else if ( $nobox && is_callable( $nobox ) )
					add_action( 'dbx_post_sidebar', $nobox, 10, 1 );

				$lonebox = $this->filters( 'lonebox_callback', in_array( 'lonebox', $contexts ), $screen->post_type );

				if ( TRUE === $lonebox )
					call_user_func_array( [ $this, 'register_lonebox_fields' ], [ $screen ] );

				else if ( $lonebox && is_callable( $lonebox ) )
					call_user_func_array( $lonebox, [ $screen ] );

				add_action( 'geditorial_meta_render_metabox', [ $this, 'render_posttype_fields' ], 10, 4 );

				$asset = [
					// 'fields' => $fields, // not used yet!
				];

				$this->enqueue_asset_js( $asset, $screen );
				$this->_hook_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->_admin_enabled();
				$this->_edit_screen( $screen->post_type );
				$this->_hook_default_rows();

				$asset = [
					// 'fields' => $fields, // not used yet!
					'fields' => array_filter( Arraay::column( wp_list_filter( $fields, [ 'quickedit' => TRUE ] ), 'type', 'name' ) ),
				];

				$this->enqueue_asset_js( $asset, $screen );
				$this->_hook_store_metabox( $screen->post_type );
			}
		}
	}

	private function _edit_screen( $posttype )
	{
		$this->filter( 'manage_posts_columns', 2, 5 );
		$this->filter( 'manage_pages_columns', 1, 5 );

		add_action( 'manage_'.$posttype.'_posts_custom_column', [ $this, 'posts_custom_column' ], 10, 2 );

		$this->action( 'quick_edit_custom_box', 2 );
	}

	// early and late actions to make room for other modules
	private function _hook_default_rows()
	{
		$this->action_self( 'column_row', 3, 5, 'default' );
		$this->action_self( 'column_row', 3, 15, 'extra' );
		$this->action_self( 'column_row', 3, 20, 'excerpt' );
	}

	public function render_posttype_fields( $post, $box, $fields = NULL, $context = 'mainbox' )
	{
		if ( is_null( $fields ) )
			$fields = $this->get_posttype_fields( $post->post_type );

		foreach ( $fields as $field => $args ) {

			if ( $context != $args['context'] )
				continue;

			switch ( $args['type'] ) {

				case 'text':

					ModuleMetaBox::legacy_fieldString( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );

				break;
				case 'float':
				case 'code':
				case 'link':

					ModuleMetaBox::legacy_fieldString( $field, [ $field ], $post, TRUE, $args['title'], FALSE, $args['type'] );

				break;
				case 'number':

					ModuleMetaBox::legacy_fieldNumber( $field, [ $field ], $post, TRUE, $args['title'], FALSE, $args['type'] );

				break;
				case 'note':
				case 'textarea':

					ModuleMetaBox::legacy_fieldTextarea( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );

				break;
				case 'term':

					if ( $args['tax'] )
						ModuleMetaBox::legacy_fieldTerm( $field, [ $field ], $post, $args['tax'], $args['ltr'], $args['title'] );
					else
						ModuleMetaBox::legacy_fieldString( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );
			}
		}
	}

	public function render_mainbox_metabox( $post, $box )
	{
		if ( ! empty( $box['args']['metabox_id'] ) && MetaBox::checkHidden( $box['args']['metabox_id'], $post->post_type ) )
			return;

		$fields = $this->get_posttype_fields( $post->post_type );

		echo $this->wrap_open( '-admin-metabox' );

			if ( count( $fields ) )
				$this->actions( 'render_metabox', $post, $box, $fields, 'mainbox' );

			else
				echo HTML::wrap( _x( 'No Meta Fields', 'Message', 'geditorial-meta' ), 'field-wrap -empty' );

			$this->actions( 'render_metabox_after', $post, $box, $fields, 'mainbox' );
		echo '</div>';

		$this->nonce_field( 'mainbox' );
	}

	public function render_nobox_fields( $post )
	{
		$fields = $this->get_posttype_fields( $post->post_type );

		if ( count( $fields ) ) {

			echo '&nbsp;'; // workaround for weird css bug on no-js!

			foreach ( $fields as $field => $args ) {

				switch ( $args['type'] ) {

					case 'title_before':
					case 'title_after':
						ModuleMetaBox::legacy_fieldTitle( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );
					break;

					case 'postbox_legacy':
						ModuleMetaBox::legacy_fieldBox( $field, [ $field ], $post, $args['ltr'], $args['title'] );
					break;
				}
			}
		}

		$this->actions( 'box_raw', $this->module, $post, $fields );
		$this->nonce_field( 'nobox' );
	}

	public function register_lonebox_fields( $screen )
	{
		$fields = $this->get_posttype_fields( $screen->post_type );

		if ( count( $fields ) ) {

			foreach ( $fields as $field => $args ) {

				switch ( $args['type'] ) {

					case 'postbox_html':
					case 'postbox_tiny':

						$metabox = $this->classs( $screen->post_type, $field );
						$title   = empty( $args['title'] ) ? $field : $args['title'];

						if ( ! empty( $args['description'] ) )
							$title.= ' <span class="postbox-title-info" data-title="info" title="'
								.HTML::escape( $args['description'] ).'">'
								.HTML::getDashicon( 'editor-help' ).'</span>';

						MetaBox::classEditorBox( $screen, $metabox );

						add_meta_box( $metabox,
							$title,
							[ $this, 'render_lonebox_metabox' ],
							$screen,
							'after_title', // TODO: must defined on field args
							'high',
							[
								'posttype'   => $screen->post_type,
								'metabox'    => $metabox,
								'field_name' => $field,
								'field_args' => $args,
							]
						);

					break;
				}
			}
		}
	}

	public function render_lonebox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		ModuleMetaBox::legacy_fieldEditorBox(
			$box['args']['field_name'],
			$post,
			$box['args']['field_args']['ltr'],
			$box['args']['field_args']['title'],
			FALSE,
			$box['args']['field_args']['type']
		);
	}

	// FIXME: DROP THIS!
	public function sanitize_post_meta( $postmeta, $fields, $post )
	{
		if ( ! count( $fields ) )
			return $postmeta;

		if ( ! $post = Helper::getPost( $post ) )
			return $postmeta;

		if ( ! $this->nonce_verify( 'mainbox' )
			&& ! $this->nonce_verify( 'nobox' ) )
				return $postmeta;

		// MAYBE: check for `edit_post_meta`
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return $postmeta;

		foreach ( $fields as $field => $args ) {

			switch ( $args['type'] ) {

				case 'term':

					ModuleMetaBox::setPostMetaField_Term( $post->ID, $field, $args['tax'] );

				break;
				case 'link':

					ModuleMetaBox::setPostMetaField_URL( $postmeta, $field );

				break;
				case 'code':

					ModuleMetaBox::setPostMetaField_Code( $postmeta, $field );

				break;
				case 'number':

					ModuleMetaBox::setPostMetaField_Number( $postmeta, $field );

				break;
				case 'text':
				case 'title_before':
				case 'title_after':

					ModuleMetaBox::setPostMetaField_String( $postmeta, $field );

				break;
				case 'note':
				case 'textarea':
				case 'postbox_legacy':
				case 'postbox_html':

					ModuleMetaBox::setPostMetaField_Text( $postmeta, $field );
			}
		}

		return $postmeta;
	}

	public function sanitize_postmeta_field( $field )
	{
		if ( is_array( $field ) )
			return $field;

		$fields = [
			// meta currents
			'over_title'   => [ 'over_title', 'ot' ],
			'sub_title'    => [ 'sub_title', 'st' ],
			'byline'       => [ 'byline', 'author', 'as' ],
			'lead'         => [ 'lead', 'le' ],
			'label'        => [ 'label', 'ch', 'column_header' ],
			'label_tax'    => [ 'label_tax', 'ct' ],  // term type
			'start'        => [ 'start', 'in_issue_page_start' ], // general
			'order'        => [ 'order', 'in_issue_order', 'in_collection_order', 'in_series_order' ], // general
			'number_line'  => [ 'number_line', 'issue_number_line', 'number' ],
			'total_pages'  => [ 'total_pages', 'issue_total_pages', 'pages' ],
			'source_title' => [ 'source_title', 'reshare_source_title' ],
			'source_url'   => [ 'source_url', 'reshare_source_url', 'es', 'ol' ],

			// meta oldies
			'ot' => [ 'over_title', 'ot' ],
			'st' => [ 'sub_title', 'st' ],
			'le' => [ 'lead', 'le' ],
			'as' => [ 'byline', 'author', 'as' ],
			'ch' => [ 'label', 'ch', 'column_header' ],
			'ct' => [ 'label_tax', 'ct' ],
			'es' => [ 'source_url', 'es' ],
			'ol' => [ 'source_url', 'ol' ],

			// book currents
			'publication_edition'   => [ 'publication_edition', 'edition' ],
			'publication_print'     => [ 'publication_print', 'print' ],
			'publication_isbn'      => [ 'publication_isbn', 'isbn' ],
			'publication_reference' => [ 'publication_reference', 'reference' ],
			'total_volumes'         => [ 'total_volumes', 'volumes' ],
			'publication_size'      => [ 'publication_size', 'size' ], // term type

			// book oldies
			'edition'   => [ 'publication_edition', 'edition' ],
			'print'     => [ 'publication_print', 'print' ],
			'isbn'      => [ 'publication_isbn', 'isbn' ],
			'reference' => [ 'publication_reference', 'reference' ],
			'volumes'   => [ 'total_volumes', 'volumes' ],
			'size'      => [ 'publication_size', 'size' ], // term type

			// other oldies
			'issue_number_line'    => [ 'number_line', 'issue_number_line' ],
			'issue_total_pages'    => [ 'total_pages', 'issue_total_pages' ],
			'reshare_source_title' => [ 'source_title', 'reshare_source_title' ],
			'reshare_source_url'   => [ 'source_url', 'reshare_source_url' ],

			// fallbacks
			'over-title' => [ 'over_title', 'ot' ],
			'sub-title'  => [ 'sub_title', 'st' ],
			'pages'      => [ 'total_pages', 'pages' ],
			'number'     => [ 'number_line', 'issue_number_line', 'number' ],
		];

		if ( isset( $fields[$field] ) )
			return $fields[$field];

		return [ $field ];
	}

	public function store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		if ( ! $this->nonce_verify( 'mainbox' )
			&& ! $this->nonce_verify( 'nobox' ) )
				return;

		// MAYBE: check for `edit_post_meta`
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		$fields = $this->get_posttype_fields( $post->post_type );

		if ( ! count( $fields ) )
			return;

		$legacy = $this->get_postmeta_legacy( $post->ID );

		foreach ( $fields as $field => $args ) {

			$request = sprintf( '%s-%s-%s', $this->base, $this->module->name, $field );

			if ( FALSE !== ( $data = self::req( $request, FALSE ) ) )
				$this->import_posttype_field( $data, $args, $post );

			// passing not enabled legacy data
			else if ( array_key_exists( $field, $legacy ) )
				$this->set_postmeta_field( $post->ID, $field, $this->sanitize_posttype_field( $legacy[$field], $args, $post ) );
		}

		$this->clean_postmeta_legacy( $post->ID, $fields, $legacy );
	}

	public function import_posttype_field( $data, $field, $post )
	{
		switch ( $field['type'] ) {

			case 'term':

				return wp_set_object_terms( $post->ID, $this->sanitize_posttype_field( $data, $field, $post ), $field['tax'], FALSE );

			break;
			default:

				return $this->set_postmeta_field( $post->ID, $field['name'], $this->sanitize_posttype_field( $data, $field, $post ) );
		}
	}

	// FIXME: DROP THIS!
	public function store_metabox_OLD( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		// NOUNCES MUST CHECKED BY FILTERS
		// CAPABILITIES MUST CHECKED BY FILTERS : if (current_user_can($post->cap->edit_post, $post_id))

		$this->store_postmeta( $post_id,
			$this->sanitize_post_meta(
				$this->get_postmeta_legacy( $post->ID ),
				$this->get_posttype_fields( $post->post_type ),
				$post
			)
		);
	}

	public function manage_pages_columns( $columns )
	{
		return $this->manage_posts_columns( $columns, 'page' );
	}

	public function manage_posts_columns( $columns, $posttype )
	{
		if ( in_array( 'byline', $this->posttype_fields( $posttype ) ) )
			unset( $columns['author'] );

		return Arraay::insert( $columns, [
			$this->classs() => $this->get_column_title( 'meta', $posttype ),
		], 'title', 'after' );
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( $this->classs() != $column_name )
			return;

		if ( ! $post = get_post( $post_id ) )
			return;

		$prefix   = $this->classs().'-';
		$fields   = $this->get_posttype_fields( $post->post_type );
		$excludes = []; // excludes are for other modules

		foreach ( $fields as $field => $args ) {

			if ( $args['quickedit'] )
				$excludes[] = $field;

			else if ( in_array( $args['name'], [ 'label', 'label_tax', 'source_title', 'source_url' ] ) )
				$excludes[] = $field;

			else if ( in_array( $args['type'], [ 'postbox_html', 'postbox_tiny', 'postbox_legacy' ] ) )
				$excludes[] = $field;
		}

		echo '<div class="geditorial-admin-wrap-column -meta"><ul class="-rows">';
			$this->actions( 'column_row', $post, $fields, $excludes );
		echo '</ul></div>';

		// for quick-edit
		foreach ( wp_list_filter( $fields, [ 'quickedit' => TRUE ] ) as $field => $args )
			echo '<div class="hidden '.$prefix.$field.'-value">'.$this->get_postmeta_field( $post->ID, $field ).'</div>';
	}

	public function column_row_default( $post, $fields, $excludes )
	{
		foreach ( $fields as $field => $args ) {

			if ( ! $args['quickedit'] )
				continue;

			if ( ! $value = $this->get_postmeta_field( $post->ID, $field ) )
				continue;

			echo '<li class="-row meta-'.$field.'">';
				echo $this->get_column_icon( FALSE, $args['icon'], $args['title'] );
				echo HTML::escape( $value );
			echo '</li>';
		}
	}

	public function column_row_extra( $post, $fields, $exclude )
	{
		if ( array_key_exists( 'label', $fields ) || array_key_exists( 'label_tax', $fields ) )
			ModuleTemplate::metaLabel( [
				'before' => '<li class="-row meta-label">'
					.$this->get_column_icon( FALSE, $fields['label']['icon'], $fields['label']['title'] ),
				'after'  => '</li>',
			] );

		if ( array_key_exists( 'source_title', $fields ) || array_key_exists( 'source_url', $fields ) )
			ModuleTemplate::metaSource( [
				'before' => '<li class="-row meta-source">'
					.$this->get_column_icon( FALSE, 'external', $this->get_string( 'source', $post->post_type, 'titles', 'source' ) ),
				'after'  => '</li>',
			] );
	}

	// only on excerpt mode
	public function column_row_excerpt( $post, $fields, $exclude )
	{
		if ( 'excerpt' !== $GLOBALS['mode'] )
			return;

		foreach ( $fields as $field => $args ) {

			if ( ! in_array( $args['type'], [ 'postbox_html', 'postbox_tiny', 'postbox_legacy' ] ) )
				continue;

			// skip if empty
			if ( ! $value = $this->get_postmeta_field( $post->ID, $field ) )
				continue;

			$icon = $this->get_column_icon( FALSE, $args['icon'], $args['title'] );

			ModuleTemplate::metaFieldHTML( $field, [
				'before' => '<li class="-row meta-'.$field.'">'.$icon,
				'after'  => '</li>',
				'filter' => FALSE,
				'trim'   => 450,
			] );
		}
	}

	public function tableColumnPostMeta()
	{
		$this->_hook_default_rows();

		if ( empty( $GLOBALS['mode'] ) )
			$GLOBALS['mode'] = 'excerpt';

		return [
			'title'    => $this->get_column_title( 'meta' ),
			'callback' => [ $this, 'tableColumnPostMeta_callback'],
		];
	}

	public function tableColumnPostMeta_callback( $value, $row, $column, $index )
	{
		$this->posts_custom_column( $this->hook(), $row );
	}

	public function quick_edit_custom_box( $column_name, $posttype )
	{
		if ( $this->classs() != $column_name )
			return FALSE;

		$fields = $this->get_posttype_fields( $posttype );

		foreach ( $fields as $field => $args ) {

			if ( ! $args['quickedit'] )
				continue;

			$name  = $this->classs().'-'.$field; // to protect key underlines
			$class = HTML::prepClass( $name );

			echo '<label class="hidden '.$class.'">';
				echo '<span class="title">'.$args['title'].'</span>';
				echo '<span class="input-text-wrap"><input type="text" name="'.$name.'" class="'.$class.'" value=""></span>';
			echo '</label>';
		}

		$this->nonce_field( 'nobox' );
	}

	public function content_before( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		ModuleTemplate::metaLead( [
			'before' => $this->wrap_open( '-before entry-lead' ),
			'after'  => '</div>',
		] );
	}

	public function content_after( $content )
	{
		if ( ! $this->is_content_insert( FALSE, FALSE ) )
			return;

		global $page, $pages;

		// only on the last page
		if ( $page == count( $pages ) )
			ModuleTemplate::metaSource( [
				'before' => $this->wrap_open( '-after entry-source' )
					.$this->get_setting( 'before_source', '' ).' ',
				'after'  => '</div>',
			] );
	}

	public function the_author( $display_name )
	{
		if ( ! $post = get_post() )
			return $display_name;

		// NO NEED
		// if ( ! in_array( 'byline', $this->posttype_fields( $post->post_type ) ) )
		// 	return $display_name;

		if ( $value = $this->get_postmeta_field( $post->ID, 'byline' ) )
			$display_name = $value;

		return $display_name;
	}

	protected function render_tools_html( $uri, $sub )
	{
		$args = $this->get_current_form( [
			'custom_field'       => '',
			'custom_field_limit' => '',
			'custom_field_type'  => 'post',
			'custom_field_into'  => '',
		], 'tools' );

		HTML::h3( _x( 'Meta Tools', 'Header', 'geditorial-meta' ) );

		echo '<table class="form-table">';

		echo '<tr><th scope="row">'._x( 'Import Custom Fields', 'Header', 'geditorial-meta' ).'</th><td>';

		$this->do_settings_field( [
			'type'         => 'select',
			'field'        => 'custom_field',
			'values'       => Database::getPostMetaKeys( TRUE ),
			'default'      => $args['custom_field'],
			'option_group' => 'tools',
		] );

		$this->do_settings_field( [
			'type'         => 'text',
			'field'        => 'custom_field_limit',
			'default'      => $args['custom_field_limit'],
			'option_group' => 'tools',
			'field_class'  => 'small-text',
			'placeholder'  => 'limit',
		] );

		$this->do_settings_field( [
			'type'         => 'select',
			'field'        => 'custom_field_type',
			'values'       => $this->list_posttypes(),
			'default'      => $args['custom_field_type'],
			'option_group' => 'tools',
		] );

		$this->do_settings_field( [
			'type'         => 'select',
			'field'        => 'custom_field_into',
			'values'       => $this->posttype_fields_list( $args['custom_field_type'] ),
			'default'      => $args['custom_field_into'],
			'option_group' => 'tools',
		] );

		echo '&nbsp;&nbsp;';

		Settings::submitButton( 'custom_fields_check',
			_x( 'Check', 'Button', 'geditorial-meta' ), TRUE );

		Settings::submitButton( 'custom_fields_convert',
			_x( 'Covert', 'Button', 'geditorial-meta' ) );

		Settings::submitButton( 'custom_fields_delete',
			_x( 'Delete', 'Button', 'geditorial-meta' ), 'danger', TRUE );

		HTML::desc( _x( 'Check for Custom Fields and import them into Meta', 'Message', 'geditorial-meta' ) );

		if ( isset( $_POST['custom_fields_check'] )
			&& $args['custom_field'] ) {

			echo '<br />';
			HTML::tableList( [
				'post_id' => Helper::tableColumnPostID(),
				'type'   => [
					'title'    => _x( 'Type', 'Table Column', 'geditorial-meta' ),
					'args'     => [ 'types' => PostType::get( 2 ) ],
					'callback' => function( $value, $row, $column, $index ){
						$post = get_post( $row->post_id );
						return isset( $column['args']['types'][$post->post_type] )
							? $column['args']['types'][$post->post_type]
							: $post->post_type;
					},
				],
				'title'   => [
					'title'    => _x( 'Title', 'Table Column', 'geditorial-meta' ),
					'callback' => function( $value, $row, $column, $index ) {
						return Helper::getPostTitle( $row->post_id );
					},
				],
				/* translators: %s: title */
				'meta' => sprintf( _x( 'Meta: %s', 'Table Column', 'geditorial-meta' ), '<code>'.$args['custom_field'].'</code>' ),
			], Database::getPostMetaRows(
				stripslashes( $args['custom_field'] ),
				stripslashes( $args['custom_field_limit'] )
			), [
				'empty' => HTML::warning( _x( 'No Meta Found!', 'Table Empty', 'geditorial-meta' ), FALSE ),
			] );
		}

		echo '</td></tr>';
		echo '</table>';
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( $this->current_action( 'custom_fields_convert' ) ) {

					$post = $this->get_current_form( [
						'custom_field'       => FALSE,
						'custom_field_into'  => FALSE,
						'custom_field_limit' => '25',
					], 'tools' );

					$result = [];

					if ( $post['custom_field'] && $post['custom_field_into'] )
						$result = $this->import_field_meta(
							$post['custom_field'],
							$post['custom_field_into'],
							$post['custom_field_limit'] );

					if ( count( $result ) )
						WordPress::redirectReferer( [
							'message' => 'converted',
							'field'   => $post['custom_field'],
							'limit'   => $post['custom_field_limit'],
							'count'   => count( $result ),
						] );

				} else if ( $this->current_action( 'custom_fields_delete' ) ) {

					$post = $this->get_current_form( [
						'custom_field'       => FALSE,
						'custom_field_limit' => '',
					], 'tools' );

					$result = [];

					if ( $post['custom_field'] )
						$result = Database::deletePostMeta( $post['custom_field'], $post['custom_field_limit'] );

					if ( count( $result ) )
						WordPress::redirectReferer( [
							'message' => 'deleted',
							'field'   => $post['custom_field'],
							'limit'   => $post['custom_field_limit'],
							'count'   => count( $result ),
						] );
				}
			}
		}
	}

	// OLD: `import_from_meta()`
	public function import_field_meta( $post_meta_key, $field, $limit = FALSE )
	{
		$rows = Database::getPostMetaRows( $post_meta_key, $limit );

		foreach ( $rows as $row )
			$this->import_field_raw( explode( ',', $row->meta ), $field, $row->post_id );

		return count( $rows );
	}

	// OLD: `import_to_meta()`
	public function import_field_raw( $data, $field, $post )
	{
		if ( ! $post = Helper::getPost( $post ) )
			return FALSE;

		$field = $this->sanitize_postmeta_field( $field )[0];
		$data  = $this->filters( 'import_field_raw_pre', $data, $field, $post );

		if ( FALSE === $data )
			return FALSE;

		$fields = $this->get_posttype_fields( $post->post_type );

		if ( ! array_key_exists( $field, $fields ) )
			return FALSE;

		switch ( $fields[$field]['type'] ) {

			case 'term':

				$this->import_field_raw_terms( $data, $fields[$field], $post );

			break;
			default:

				$this->import_field_raw_strings( $data, $fields[$field], $post );
		}

		return $post->ID;
	}

	public function import_field_raw_strings( $data, $field, $post )
	{
		$strings = [];

		foreach ( (array) $data as $name ) {

			$sanitized = $this->sanitize_posttype_field( $data, $field, $post );

			if ( empty( $sanitized ) )
				continue;

			$strings[] = apply_filters( 'string_format_i18n', $sanitized );
		}

		return $this->set_postmeta_field( $post->ID, $field['name'], Helper::getJoined( $strings ) );
	}

	public function import_field_raw_terms( $data, $field, $post )
	{
		$terms = [];

		foreach ( (array) $data as $name ) {

			$sanitized = trim( Helper::kses( $name, 'none' ) );

			if ( empty( $sanitized ) )
				continue;

			$formatted = apply_filters( 'string_format_i18n', $sanitized );

			if ( ! $term = get_term_by( 'name', $formatted, $field['tax'] ) ) {

				$term = wp_insert_term( $formatted, $taxonomy );

				if ( ! is_wp_error( $term ) )
					$terms[] = $term->term_id;

			} else {

				$terms[] = $term->term_id;
			}
		}

		return wp_set_object_terms( $post->ID, $this->sanitize_posttype_field( $terms, $field, $post ), $field['tax'], FALSE );
	}

	private function get_importer_fields( $posttype = NULL, $object = FALSE )
	{
		/* translators: %s: field title */
		$template = _x( 'Meta: %s', 'Import Field', 'geditorial-meta' );
		$fields   = [];

		foreach ( $this->get_posttype_fields( $posttype ) as $field => $args )
			if ( ! in_array( $args['type'], [ 'term' ] ) )
				$fields['meta_'.$field] = $object ? $args : sprintf( $template, $args['title'] );

		return $fields;
	}

	public function importer_fields( $fields, $posttype )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $fields;

		return array_merge( $fields, $this->get_importer_fields( $posttype ) );
	}

	public function importer_prepare( $value, $posttype, $field, $raw )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $value;

		$fields = $this->get_importer_fields( $posttype, TRUE );

		if ( ! array_key_exists( $field, $fields ) )
			return $value;

		return $this->sanitize_posttype_field( $value, $fields[$field] );
	}

	public function importer_saved( $post, $data, $raw, $field_map, $attach_id )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		$fields = $this->get_importer_fields( $post->post_type, TRUE );

		foreach ( $field_map as $offset => $field )
			if ( array_key_exists( $field, $fields ) )
				$this->import_posttype_field( $raw[$offset], $fields[$field], $post );
	}
}
