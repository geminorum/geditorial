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
use geminorum\gEditorial\MetaBoxes\Meta as ModuleMetaBox;
use geminorum\gEditorial\Templates\Meta as ModuleTemplate;

class Meta extends gEditorial\Module
{
	public $meta_key = '_gmeta';

	protected $priority_init = 12;

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
				[
					'field'       => 'author_row',
					'title'       => _x( 'Author Meta Row', 'Modules: Meta: Setting Title', 'geditorial' ),
					'description' => _x( 'Displays author display name as meta row', 'Modules: Meta: Setting Description', 'geditorial' ),
				],
				[
					'field'       => 'overwrite_author',
					'title'       => _x( 'Overwrite Author', 'Modules: Meta: Setting Title', 'geditorial' ),
					'description' => _x( 'Replace author display name with author meta data.', 'Modules: Meta: Setting Description', 'geditorial' ),
				],
				[
					'field'       => 'before_source',
					'type'        => 'text',
					'title'       => _x( 'Before Source', 'Modules: Meta: Setting Title', 'geditorial' ),
					'description' => _x( 'Default text before the source link', 'Modules: Meta: Setting Description', 'geditorial' ),
					'default'     => _x( 'Source:', 'Modules: Meta: Setting Default', 'geditorial' ),
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'ct_tax' => 'label',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'titles' => [
				'post' => [
					'ot'          => _x( 'OverTitle', 'Modules: Meta: Titles', 'geditorial' ),
					'st'          => _x( 'SubTitle', 'Modules: Meta: Titles', 'geditorial' ),
					'as'          => _x( 'Byline', 'Modules: Meta: Titles', 'geditorial' ),
					'le'          => _x( 'Lead', 'Modules: Meta: Titles', 'geditorial' ),
					'ch'          => _x( 'Column Header', 'Modules: Meta: Titles', 'geditorial' ),
					'ct'          => _x( 'Column Header Taxonomy', 'Modules: Meta: Titles', 'geditorial' ),
					'ch_override' => _x( 'Column Header Override', 'Modules: Meta: Titles', 'geditorial' ),

					'source_title' => _x( 'Source Title', 'Modules: Meta: Titles', 'geditorial' ),
					'source_url'   => _x( 'Source URL', 'Modules: Meta: Titles', 'geditorial' ),
					'highlight'    => _x( 'Highlight', 'Modules: Meta: Titles', 'geditorial' ),
					'dashboard'    => _x( 'Dashboard', 'Modules: Meta: Titles', 'geditorial' ),
					'abstract'     => _x( 'Abstract', 'Modules: Meta: Titles', 'geditorial' ),
				],
				'author' => _x( 'Author', 'Modules: Meta: Titles', 'geditorial' ),
				'source' => _x( 'Source', 'Modules: Meta: Titles', 'geditorial' ),
			],
			'descriptions' => [
				'post' => [
					'ot'          => _x( 'String to place over the post title', 'Modules: Meta: Descriptions', 'geditorial' ),
					'st'          => _x( 'String to place under the post title', 'Modules: Meta: Descriptions', 'geditorial' ),
					'as'          => _x( 'String to override the post author', 'Modules: Meta: Descriptions', 'geditorial' ),
					'le'          => _x( 'Editorial paragraph presented before post content', 'Modules: Meta: Descriptions', 'geditorial' ),
					'ch'          => _x( 'String to represent that the post is part of a column or a section', 'Modules: Meta: Descriptions', 'geditorial' ),
					'ct'          => _x( 'Taxonomy for better categorizing columns', 'Modules: Meta: Descriptions', 'geditorial' ),
					'ch_override' => _x( 'Column Header Override', 'Modules: Meta: Descriptions', 'geditorial' ),

					'source_title' => _x( 'Original Title of Source Content', 'Modules: Meta: Descriptions', 'geditorial' ),
					'source_url'   => _x( 'Full URL to the Source of the Content', 'Modules: Meta: Descriptions', 'geditorial' ),
					'highlight'    => _x( 'A Short Note Highlighted About the Post', 'Modules: Meta: Descriptions', 'geditorial' ),
					'dashboard'    => _x( 'Custom HTML Content on the Dashboard', 'Modules: Meta: Descriptions', 'geditorial' ),
					'abstract'     => _x( 'A summary of the content', 'Modules: Meta: Descriptions', 'geditorial' ),
				],
			],
			'noops' => [
				'ct_tax' => _nx_noop( 'Column Header', 'Column Headers', 'Modules: Meta: Noop', 'geditorial' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'meta_column_title'   => _x( 'Metadata', 'Modules: Meta: Column Title', 'geditorial' ),
			'author_column_title' => _x( 'Author', 'Modules: Meta: Column Title', 'geditorial' ),
			'meta_box_title'      => _x( 'Metadata', 'Modules: Meta: Meta Box Title', 'geditorial' ),
			'meta_box_action'     => _x( 'Configure', 'Modules: Meta: Meta Box Action Title', 'geditorial' ),
		];

		$strings['terms'] = [
			'ct_tax' => [
				'introduction' => _x( 'Introduction', 'Modules: Meta: Column Headers Tax Defaults', 'geditorial' ),
				'interview'    => _x( 'Interview', 'Modules: Meta: Column Headers Tax Defaults', 'geditorial' ),
				'review'       => _x( 'Review', 'Modules: Meta: Column Headers Tax Defaults', 'geditorial' ),
				'report'       => _x( 'Report', 'Modules: Meta: Column Headers Tax Defaults', 'geditorial' ),
			],
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'post' => [
				'ot' => [ 'type' => 'title_before' ],
				'st' => [ 'type' => 'title_after' ],
				'le' => [ 'type' => 'postbox_html' ], // OLD: 'postbox_legacy'
				'as' => [ 'type' => 'text' ],
				'ct' => [
					'type' => 'term',
					'tax'  => $this->constant( 'ct_tax' ),
				],
				'ch' => [ 'type' => 'text' ],

				'source_title' => [ 'type' => 'text' ],
				'source_url'   => [ 'type' => 'link' ],
				'highlight'    => [ 'type' => 'note' ],
				'dashboard'    => [ 'type' => 'postbox_html' ], // or 'postbox_tiny'
				'abstract'     => [ 'type' => 'postbox_html' ], // or 'postbox_tiny'
			],
			'page' => [
				'ot' => [ 'type' => 'title_before' ],
				'st' => [ 'type' => 'title_after' ],
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
		if ( isset( $_POST['install_def_ct_tax'] ) )
			$this->insert_default_terms( 'ct_tax' );

		$this->help_tab_default_terms( 'ct_tax' );
	}

	public function default_buttons( $module = FALSE )
	{
		parent::default_buttons( $module );

		$this->register_button( 'install_def_ct_tax', _x( 'Install Default Column Headers', 'Modules: Meta: Setting Button', 'geditorial' ) );
	}

	public function init()
	{
		parent::init();

		$ct_tax_posttypes = [];

		foreach ( $this->posttypes() as $posttype )
			if ( in_array( 'ct', $this->posttype_fields( $posttype ) ) )
				$ct_tax_posttypes[] = $posttype;

		if ( count( $ct_tax_posttypes ) )
			$this->register_taxonomy( 'ct_tax', [
				'show_in_rest' => FALSE, // disables in block editor, temporarily!
			], $ct_tax_posttypes );

		// default fields for custom post types
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

		add_action( $this->base.'_content_before', [ $this, 'content_before' ], 50 );
		add_action( $this->base.'_content_after', [ $this, 'content_after' ], 50 );

		if ( $this->get_setting( 'overwrite_author', FALSE ) )
			$this->filter( 'the_author', 1, 9 );
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, $this->posttypes() ) ) {
			$this->_edit_screen( $_REQUEST['post_type'] );
			$this->_default_rows();
			add_action( 'save_post_'.$_REQUEST['post_type'], [ $this, 'store_metabox' ], 20, 3 );
		}
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->post_type, $this->posttypes() ) ) {

			// bail if no fields enabled for this posttype
			if ( ! count( $this->posttype_fields( $screen->post_type ) ) )
				return;

			if ( 'post' == $screen->base ) {

				$fields   = $this->get_posttype_fields( $screen->post_type );
				$contexts = Arraay::column( $fields, 'context' );
				$metabox  = $this->classs( $screen->post_type );

				$main_callback = $this->filters( 'box_callback', in_array( 'main', $contexts ), $screen->post_type );

				if ( TRUE === $main_callback )
					$main_callback = [ $this, 'render_metabox_main' ];

				if ( $main_callback && is_callable( $main_callback ) )
					add_meta_box( $metabox,
						$this->get_meta_box_title(),
						$main_callback,
						$screen,
						'side',
						'high',
						[
							'posttype' => $screen->post_type,
							'metabox'  => $metabox,
						]
					);

				$raw_callback = $this->filters( 'raw_callback', in_array( 'raw', $contexts ), $screen->post_type );

				if ( TRUE === $raw_callback )
					add_action( 'dbx_post_sidebar', [ $this, 'render_raw_default' ], 10, 1 );

				else if ( $raw_callback && is_callable( $raw_callback ) )
					add_action( 'dbx_post_sidebar', $raw_callback, 10, 1 );

				$lone_callback = $this->filters( 'lone_callback', in_array( 'lone', $contexts ), $screen->post_type );

				if ( TRUE === $lone_callback )
					call_user_func_array( [ $this, 'register_lone_default' ], [ $screen ] );

				else if ( $lone_callback && is_callable( $lone_callback ) )
					call_user_func_array( $lone_callback, [ $screen ] );

				add_action( 'geditorial_meta_render_metabox', [ $this, 'render_metabox' ], 10, 4 );

			} else if ( 'edit' == $screen->base ) {

				$this->_admin_enabled();
				$this->_edit_screen( $screen->post_type );
				$this->_default_rows();
			}

			if ( 'post' == $screen->base
				|| 'edit' == $screen->base ) {

				$localize = [ 'fields' => $this->posttype_fields( $screen->post_type, TRUE ) ];

				// foreach ( $this->posttype_fields( $screen->post_type ) as $field )
				// 	$localize[$field] = $this->get_string( $field, $screen->post_type );

				$this->enqueue_asset_js( $localize, $screen );

				add_action( 'save_post_'.$screen->post_type, [ $this, 'store_metabox' ], 20, 3 );
			}
		}
	}

	private function _edit_screen( $posttype )
	{
		add_filter( 'manage_posts_columns', [ $this, 'manage_posts_columns' ], 5, 2 );
		add_filter( 'manage_pages_columns', [ $this, 'manage_pages_columns' ], 5, 1 );
		add_action( 'manage_'.$posttype.'_posts_custom_column', [ $this, 'posts_custom_column' ], 10, 2 );

		add_action( 'quick_edit_custom_box', [ $this, 'quick_edit_custom_box' ], 10, 2 );
	}

	private function _default_rows()
	{
		add_action( $this->hook( 'column_row' ), [ $this, 'column_row_default' ], 8, 3 );
		add_action( $this->hook( 'column_row' ), [ $this, 'column_row_extra' ], 12, 3 );
	}

	public function render_metabox( $post, $box, $fields = NULL, $context = 'main' )
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

		$this->nonce_field( 'post_main' );
	}

	public function render_metabox_main( $post, $box )
	{
		if ( ! empty( $box['args']['metabox'] ) && MetaBox::checkHidden( $box['args']['metabox'], $post->post_type ) )
			return;

		$fields = $this->get_posttype_fields( $post->post_type );

		echo $this->wrap_open( '-admin-metabox' );

			if ( count( $fields ) )
				$this->actions( 'render_metabox', $post, $box, $fields, 'main' );

			else
				echo HTML::wrap( _x( 'No Meta Fields', 'Modules: Meta', 'geditorial' ), 'field-wrap -empty' );

			$this->actions( 'render_metabox_after', $post, $box, $fields );
		echo '</div>';
	}

	public function render_raw_default( $post )
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
		$this->nonce_field( 'post_raw' );
	}

	public function register_lone_default( $screen )
	{
		$fields = $this->get_posttype_fields( $screen->post_type );

		if ( count( $fields ) ) {

			foreach ( $fields as $field => $args ) {

				switch ( $args['type'] ) {

					case 'postbox_html':
					case 'postbox_tiny':

						$metabox = $this->classs( $screen->post_type, $field );

						MetaBox::classEditorBox( $screen, $metabox );

						add_meta_box( $metabox,
							$args['title'],
							[ $this, 'lone_postbox_html_callback' ],
							$screen,
							'after_title',
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

	public function lone_postbox_html_callback( $post, $box )
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

	public function sanitize_post_meta( $postmeta, $fields, $post )
	{
		if ( ! count( $fields ) )
			return $postmeta;

		if ( ! $post = Helper::getPost( $post ) )
			return $postmeta;

		if ( ! $this->nonce_verify( 'post_main' ) && ! $this->nonce_verify( 'post_raw' ) )
			return $postmeta;

		$cap = empty( $post->cap->edit_post ) ? 'edit_post' : $post->cap->edit_post;

		// MAYBE: check for `edit_post_meta` cap
		if ( ! current_user_can( $cap, $post->ID ) )
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

	public function sanitize_meta_field( $field )
	{
		if ( is_array( $field ) )
			return $field;

		$fields = [
			'ot'                   => [ 'over_title', 'ot' ],
			'st'                   => [ 'sub_title', 'st' ],
			'over_title'           => [ 'over_title', 'ot' ],
			'sub_title'            => [ 'sub_title', 'st' ],
			'issue_number_line'    => [ 'number_line', 'issue_number_line' ],
			'issue_total_pages'    => [ 'total_pages', 'issue_total_pages' ],
			'number_line'          => [ 'number_line', 'issue_number_line' ],
			'total_pages'          => [ 'total_pages', 'issue_total_pages' ],
			'reshare_source_title' => [ 'source_title', 'reshare_source_title' ],
			'reshare_source_url'   => [ 'source_url', 'reshare_source_url' ],
			'source_title'         => [ 'source_title', 'reshare_source_title' ],
			'source_url'           => [ 'source_url', 'reshare_source_url', 'es', 'ol' ],
			'es'                   => [ 'source_url', 'es' ],
			'ol'                   => [ 'source_url', 'ol' ],
		];

		if ( isset( $fields[$field] ) )
			return $fields[$field];

		return [ $field ];
	}

	public function store_metabox( $post_id, $post, $update, $context = 'main' )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		// NOUNCES MUST CHECKED BY FILTERS
		// CAPABILITIES MUST CHECKED BY FILTERS : if (current_user_can($post->cap->edit_post, $post_id))

		$this->set_meta( $post_id,
			$this->sanitize_post_meta(
				(array) $this->get_postmeta( $post->ID ),
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
		if ( in_array( 'as', $this->posttype_fields( $posttype ) ) )
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

		$meta    = (array) $this->get_postmeta( $post->ID );
		$fields  = $this->get_posttype_fields( $post->post_type );
		$exclude = [ 'ot', 'st', 'highlight', 'as', 'ch', 'le', 'source_title', 'source_url', 'abstract' ];

		echo '<div class="geditorial-admin-wrap-column -meta"><ul class="-rows">';
			$this->actions( 'column_row', $post, $fields, array_diff_key( $meta, array_flip( $exclude ) ) );
		echo '</ul></div>';
	}

	public function column_row_default( $post, $fields, $meta )
	{
		$author = $this->get_setting( 'author_row', FALSE )
			? WordPress::getAuthorEditHTML( $post->post_type, $post->post_author )
			: FALSE;

		$rows = [
			'ot'        => 'arrow-up-alt2',
			'st'        => 'arrow-down-alt2',
			'highlight' => 'pressthis',
			'as'        => 'admin-users',
		];

		foreach ( $rows as $field => $icon ) {

			if ( array_key_exists( $field, $fields ) ) {

				if ( $value = $this->get_postmeta( $post->ID, $field, '' ) ) {

					echo '<li class="-row meta-'.$field.'">';

						echo $this->get_column_icon( FALSE, $icon, $this->get_string( $field, $post->post_type, 'titles', $field ) );

						echo HTML::escape( $value );

						if ( 'as' == $field && $author ) {
							echo ' <small>('.$author.')</small>';
							$author = FALSE;
						}

						echo '<div class="hidden geditorial-meta-'.$field.'-value">'.$value.'</div>';

					echo '</li>';
				}
			}
		}

		if ( $author ) {
			echo '<li class="-row meta-author">';
				echo $this->get_column_icon( FALSE, $rows['as'], $this->get_string( 'author', $post->post_type, 'titles', 'author' ) );
				echo $author;
			echo '</li>';
		}
	}

	public function column_row_extra( $post, $fields, $meta )
	{
		$label = $this->get_column_icon( FALSE, 'megaphone', $this->get_string( 'ch', $post->post_type, 'titles', 'label' ) );
		ModuleTemplate::metaLabel( [
			'before' => '<li class="-row meta-label">'.$label,
			'after'  => '</li>',
		], 'meta', FALSE );

		$source = $this->get_column_icon( FALSE, 'external', $this->get_string( 'source', $post->post_type, 'titles', 'source' ) );
		ModuleTemplate::metaSource( [
			'before' => '<li class="-row meta-source">'.$source,
			'after'  => '</li>',
		] );

		if ( 'excerpt' == $GLOBALS['mode'] && array_key_exists( 'le', $fields ) ) {
			$lead = $this->get_column_icon( FALSE, 'editor-paragraph', $this->get_string( 'le', $post->post_type, 'titles', 'lead' ) );
			ModuleTemplate::metaLead( [
				'before' => '<li class="-row meta-lead">'.$lead,
				'after'  => '</li>',
				'filter' => FALSE,
				'trim'   => 450,
			] );
		}
	}

	public function tableColumnPostMeta( $author = NULL )
	{
		$this->_default_rows();

		if ( ! is_null( $author ) ) // force the author row
			$this->options->settings['author_row'] = $author;

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

		$fields = $this->posttype_fields( $posttype );

		foreach ( [ 'ot', 'st', 'as' ] as $field ) {
			if ( in_array( $field, $fields ) ) {
				$selector = 'geditorial-meta-'.$field;
				echo '<label class="'.$selector.'">';
					echo '<span class="title">'.$this->get_string( $field, $posttype ).'</span>';
					echo '<span class="input-text-wrap"><input type="text" name="'.$selector.'" class="'.HTML::prepClass( $selector ).'" value=""></span>';
				echo '</label>';
			}
		}

		$this->nonce_field( 'post_raw' );
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

		if ( ! in_array( 'as', $this->posttype_fields( $post->post_type ) ) )
			return $display_name;

		if ( $value = $this->get_postmeta( $post->ID, 'as', '' ) )
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

		HTML::h3( _x( 'Meta Tools', 'Modules: Meta', 'geditorial' ) );

		echo '<table class="form-table">';

		echo '<tr><th scope="row">'._x( 'Import Custom Fields', 'Modules: Meta', 'geditorial' ).'</th><td>';

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
			_x( 'Check', 'Modules: Meta: Setting Button', 'geditorial' ), TRUE );

		Settings::submitButton( 'custom_fields_convert',
			_x( 'Covert', 'Modules: Meta: Setting Button', 'geditorial' ) );

		Settings::submitButton( 'custom_fields_delete',
			_x( 'Delete', 'Modules: Meta: Setting Button', 'geditorial' ), 'danger', TRUE );

		HTML::desc( _x( 'Check for Custom Fields and import them into Meta', 'Modules: Meta', 'geditorial' ) );

		if ( isset( $_POST['custom_fields_check'] )
			&& $args['custom_field'] ) {

			echo '<br />';
			HTML::tableList( [
				'post_id' => Helper::tableColumnPostID(),
				'meta'    => 'Meta :'.$args['custom_field'],
			], Database::getPostMetaRows(
				stripslashes( $args['custom_field'] ),
				stripslashes( $args['custom_field_limit'] )
			), [
				'empty' => HTML::warning( _x( 'No Meta Found!', 'Modules: Meta: Table Empty', 'geditorial' ), FALSE ),
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
						$result = $this->import_from_meta(
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

	public function import_from_meta( $post_meta_key, $field, $limit = FALSE )
	{
		$rows = Database::getPostMetaRows( $post_meta_key, $limit );

		foreach ( $rows as $row )
			$this->import_to_meta( explode( ',', $row->meta ), $row->post_id, $field, $post_meta_key );

		return count( $rows );
	}

	// used in gImporter
	public function import_to_meta( $meta, $post_id, $field, $form_key = NULL )
	{
		$meta = (array) $this->filters( 'import_pre', $meta, $post_id, $field, $form_key );

		switch ( $field ) {
			case 'ct': $this->import_to_terms( $meta, $post_id, $this->constant( 'ct_tax' ) ); break;
			default  : $this->import_to_fields( $meta, $post_id, $field ); break;
		}

		return $meta;
	}

	public function import_to_fields( $meta, $post_id, $field, $kses = TRUE )
	{
		$final = '';

		foreach ( $meta as $val ) {
			$val = trim( $val );

			if ( empty( $val ) )
				continue;

			$formatted = apply_filters( 'string_format_i18n', $val );

			if ( $final )
				$final.= ', ';

			$final.= $kses ? Helper::kses( $formatted, 'text' ) : $formatted;
		}

		if ( $final ) {
			$postmeta = (array) $this->get_postmeta( $post_id );
			$postmeta[$field] = $final;
			$this->set_meta( $post_id, $postmeta );
		}
	}

	protected function import_to_terms( $meta, $post_id, $taxonomy )
	{
		$terms = [];

		foreach ( $meta as $term_name ) {
			$term_name = trim( strip_tags( $term_name ) );

			if ( empty( $term_name ) )
				continue;

			$formatted = apply_filters( 'string_format_i18n', $term_name );
			$term = get_term_by( 'name', $formatted, $taxonomy, ARRAY_A );

			if ( ! $term ) {
				$term = wp_insert_term( $formatted, $taxonomy );

				if ( is_wp_error( $term ) ) {
					$this->errors[$term_name] = $term->get_error_message();
					continue;
				}
			}

			$terms[] = (int) $term['term_id'];
		}

		return wp_set_post_terms( $post_id, $terms, $taxonomy, TRUE );
	}

	private function get_importer_fields( $posttype = NULL, $object = FALSE )
	{
		$fields = [];

		foreach ( $this->get_posttype_fields( $posttype ) as $field => $args )
			/* translators: %s: title */
			$fields['meta_'.$field] = $object ? $args : sprintf( _x( 'Meta: %s', 'Modules: Meta: Import Field', 'geditorial' ), $args['title'] );

		return $fields;
	}

	public function importer_fields( $fields, $posttype )
	{
		if ( ! in_array( $posttype, $this->posttypes() ) )
			return $fields;

		return array_merge( $fields, $this->get_importer_fields( $posttype ) );
	}

	public function importer_prepare( $value, $posttype, $field, $raw )
	{
		if ( ! in_array( $posttype, $this->posttypes() ) )
			return $value;

		$fields = $this->get_importer_fields( $posttype, TRUE );

		if ( ! in_array( $field, array_keys( $fields ) ) )
			return $value;

		// FIXME: check for field type filter
		return Helper::kses( $value, 'none' );
	}

	public function importer_saved( $post, $data, $raw, $field_map, $attach_id )
	{
		if ( ! in_array( $post->post_type, $this->posttypes() ) )
			return;

		$fields = array_keys( $this->get_importer_fields( $post->post_type ) );

		foreach ( $field_map as $offset => $field ) {

			if ( ! in_array( $field, $fields ) )
				continue;

			if ( $value = trim( Helper::kses( $raw[$offset], 'none' ) ) )
				$this->import_to_meta( $value, $post->ID, str_ireplace( 'meta_', '', $field ) );
		}
	}
}
