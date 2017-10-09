<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
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

	protected $partials = [ 'metabox', 'templates' ];

	protected $disable_no_posttypes = TRUE;

	protected $caps = [
		'tools' => 'import',
	];

	public static function module()
	{
		return [
			'name'  => 'meta',
			'title' => _x( 'Meta', 'Modules: Meta', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Curated Metadata', 'Modules: Meta', GEDITORIAL_TEXTDOMAIN ),
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
					'title'       => _x( 'Author Meta Row', 'Modules: Meta: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays author display name as meta row', 'Modules: Meta: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'overwrite_author',
					'title'       => _x( 'Overwrite Author', 'Modules: Meta: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Replace author display name with author meta data.', 'Modules: Meta: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'before_source',
					'type'        => 'text',
					'title'       => _x( 'Before Source', 'Modules: Meta: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Default text before the source link', 'Modules: Meta: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => _x( 'Source:', 'Modules: Meta: Setting Default', GEDITORIAL_TEXTDOMAIN ),
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
					'ot'          => _x( 'OverTitle', 'Modules: Meta: Titles', GEDITORIAL_TEXTDOMAIN ),
					'st'          => _x( 'SubTitle', 'Modules: Meta: Titles', GEDITORIAL_TEXTDOMAIN ),
					'as'          => _x( 'Byline', 'Modules: Meta: Titles', GEDITORIAL_TEXTDOMAIN ),
					'le'          => _x( 'Lead', 'Modules: Meta: Titles', GEDITORIAL_TEXTDOMAIN ),
					'ch'          => _x( 'Column Header', 'Modules: Meta: Titles', GEDITORIAL_TEXTDOMAIN ),
					'ct'          => _x( 'Column Header Taxonomy', 'Modules: Meta: Titles', GEDITORIAL_TEXTDOMAIN ),
					'ch_override' => _x( 'Column Header Override', 'Modules: Meta: Titles', GEDITORIAL_TEXTDOMAIN ),

					'source_title' => _x( 'Source Title', 'Modules: Meta: Titles', GEDITORIAL_TEXTDOMAIN ),
					'source_url'   => _x( 'Source URL', 'Modules: Meta: Titles', GEDITORIAL_TEXTDOMAIN ),
					'highlight'    => _x( 'Highlight', 'Modules: Meta: Titles', GEDITORIAL_TEXTDOMAIN ),
				],
				'author' => _x( 'Author', 'Modules: Meta: Titles', GEDITORIAL_TEXTDOMAIN ),
				'source' => _x( 'Source', 'Modules: Meta: Titles', GEDITORIAL_TEXTDOMAIN ),
			],
			'descriptions' => [
				'post' => [
					'ot'          => _x( 'String to place over the post title', 'Modules: Meta: Descriptions', GEDITORIAL_TEXTDOMAIN ),
					'st'          => _x( 'String to place under the post title', 'Modules: Meta: Descriptions', GEDITORIAL_TEXTDOMAIN ),
					'as'          => _x( 'String to override the post author', 'Modules: Meta: Descriptions', GEDITORIAL_TEXTDOMAIN ),
					'le'          => _x( 'Editorial paragraph presented before post content', 'Modules: Meta: Descriptions', GEDITORIAL_TEXTDOMAIN ),
					'ch'          => _x( 'String to reperesent that the post is on a column or section', 'Modules: Meta: Descriptions', GEDITORIAL_TEXTDOMAIN ),
					'ct'          => _x( 'Taxonomy for better categorizing columns', 'Modules: Meta: Descriptions', GEDITORIAL_TEXTDOMAIN ),
					'ch_override' => _x( 'Column Header Override', 'Modules: Meta: Descriptions', GEDITORIAL_TEXTDOMAIN ),

					'source_title' => _x( 'Original Title of Source Content', 'Modules: Meta: Descriptions', GEDITORIAL_TEXTDOMAIN ),
					'source_url'   => _x( 'Full URL to the Source of the Content', 'Modules: Meta: Descriptions', GEDITORIAL_TEXTDOMAIN ),
					'highlight'    => _x( 'A Short Note Highlighted About the Post', 'Modules: Meta: Descriptions', GEDITORIAL_TEXTDOMAIN ),
				],
			],
			'noops' => [
				'ct_tax' => _nx_noop( 'Column Header', 'Column Headers', 'Modules: Meta: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'meta_column_title'   => _x( 'Metadata', 'Modules: Meta: Column Title', GEDITORIAL_TEXTDOMAIN ),
			'author_column_title' => _x( 'Author', 'Modules: Meta: Column Title', GEDITORIAL_TEXTDOMAIN ),
			'meta_box_title'      => _x( 'Metadata', 'Modules: Meta: Meta Box Title', GEDITORIAL_TEXTDOMAIN ),
			'meta_box_action'     => _x( 'Configure', 'Modules: Meta: Meta Box Action Title', GEDITORIAL_TEXTDOMAIN ),
		];

		$strings['terms'] = [
			'ct_tax' => [
				'introduction' => _x( 'Introduction', 'Modules: Meta: Column Headers Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				'interview'    => _x( 'Interview', 'Modules: Meta: Column Headers Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				'review'       => _x( 'Review', 'Modules: Meta: Column Headers Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				'report'       => _x( 'Report', 'Modules: Meta: Column Headers Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
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
				'le' => [ 'type' => 'box' ],
				'as' => [ 'type' => 'text' ],
				'ct' => [
					'type' => 'term',
					'tax'  => $this->constant( 'ct_tax' ),
				],
				'ch' => [ 'type' => 'text' ],

				'source_title' => [ 'type' => 'text' ],
				'source_url'   => [ 'type' => 'link' ],
				'highlight'    => [ 'type' => 'note' ],
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

		return $this->filters( 'support_post_types', $list );
	}

	public function before_settings( $module = FALSE )
	{
		if ( isset( $_POST['install_def_ct_tax'] ) )
			$this->insert_default_terms( 'ct_tax' );
	}

	public function default_buttons( $module = FALSE )
	{
		parent::default_buttons( $module );

		$this->register_button( 'install_def_ct_tax', _x( 'Install Default Column Headers', 'Modules: Meta: Setting Button', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function init()
	{
		parent::init();

		$ct_tax_posttypes = [];

		foreach ( $this->post_types() as $post_type )
			if ( in_array( 'ct', $this->post_type_fields( $post_type ) ) )
				$ct_tax_posttypes[] = $post_type;

		if ( count( $ct_tax_posttypes ) )
			$this->register_taxonomy( 'ct_tax', [], $ct_tax_posttypes );

		// default fields for custom post types
		foreach ( $this->get_posttypes_support_meta() as $post_type )
			$this->add_post_type_fields( $post_type, $this->fields['post'] );

		$this->add_post_type_fields( 'page' );

		if ( is_admin() ) {

			$this->filter_module( 'importer', 'fields', 2 );
			$this->filter_module( 'importer', 'prepare', 4 );
			$this->filter_module( 'importer', 'saved', 5 );

		} else {

			add_action( 'gnetwork_themes_content_before', [ $this, 'content_before' ], 50 );
			add_action( 'gnetwork_themes_content_after', [ $this, 'content_after' ], 50 );

			if ( $this->get_setting( 'overwrite_author', FALSE ) )
				$this->filter( 'the_author', 1, 9 );
		}
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, $this->post_types() ) ) {
			$this->_edit_screen( $_REQUEST['post_type'] );
			$this->_default_rows();
			$this->action( 'save_post', 2 );
		}
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->post_type, $this->post_types() ) ) {

			// bail if no fields enabled for this posttype
			if ( ! count( $this->post_type_fields( $screen->post_type ) ) )
				return;

			if ( 'post' == $screen->base ) {

				$fields   = $this->post_type_field_types( $screen->post_type );
				$contexts = Arraay::column( $fields, 'context' );

				$box_callback = $this->filters( 'box_callback', in_array( 'box', $contexts ), $screen->post_type );

				if ( TRUE === $box_callback )
					$box_callback = [ $this, 'default_meta_box' ];

				if ( $box_callback && is_callable( $box_callback ) )
					add_meta_box( $this->classs( $screen->post_type ),
						$this->get_meta_box_title(),
						$box_callback,
						$screen->post_type,
						'side',
						'high'
					);

				$dbx_callback = $this->filters( 'dbx_callback', in_array( 'dbx', $contexts ), $screen->post_type );

				if ( TRUE === $dbx_callback )
					add_action( 'dbx_post_sidebar', [ $this, 'default_meta_raw' ], 10, 1 );

				else if ( $dbx_callback && is_callable( $dbx_callback ) )
					add_action( 'dbx_post_sidebar', $dbx_callback, 10, 1 );

				add_action( 'geditorial_meta_do_meta_box', [ $this, 'do_meta_box' ], 10, 4 );

			} else if ( 'edit' == $screen->base ) {

				$this->action( 'pre_get_posts' );

				$this->_admin_enabled();
				$this->_edit_screen( $screen->post_type );
				$this->_default_rows();
			}

			if ( 'post' == $screen->base
				|| 'edit' == $screen->base ) {

				$this->action( 'save_post', 2 );

				$localize = [ 'fields' => $this->post_type_fields( $screen->post_type, TRUE ) ];

				// foreach ( $this->post_type_fields( $screen->post_type ) as $field )
				// 	$localize[$field] = $this->get_string( $field, $screen->post_type );

				$this->enqueue_asset_js( $localize, $screen );
			}
		}
	}

	private function _edit_screen( $post_type )
	{
		add_filter( 'manage_posts_columns', [ $this, 'manage_posts_columns' ], 5, 2 );
		add_filter( 'manage_pages_columns', [ $this, 'manage_pages_columns' ], 5, 1 );
		add_action( 'manage_'.$post_type.'_posts_custom_column', [ $this, 'posts_custom_column' ], 10, 2 );

		add_action( 'quick_edit_custom_box', [ $this, 'quick_edit_custom_box' ], 10, 2 );
	}

	private function _default_rows()
	{
		add_action( $this->hook( 'column_row' ), [ $this, 'column_row_default' ], 8, 3 );
		add_action( $this->hook( 'column_row' ), [ $this, 'column_row_extra' ], 12, 3 );
	}

	public function pre_get_posts( &$wp_query )
	{
		if ( ! $wp_query->is_search() )
			return;

		$wp_query->set( 'meta_query', [ [
			'key'     => $this->meta_key,
			'value'   => $wp_query->query['s'],
			'compare' => 'LIKE',
		] ] );

		$this->filter_once( 'get_meta_sql' );
	}

	public function get_meta_sql( $sql )
	{
		$sql['where'] = ' OR '.ltrim( $sql['where'], ' AND ' );
		return $sql;
	}

	public function do_meta_box( $post, $box, $fields = NULL, $context = 'box' )
	{
		if ( is_null( $fields ) )
			$fields = $this->post_type_field_types( $post->post_type );

		foreach ( $fields as $field => $args ) {

			if ( $context != $args['context'] )
				continue;

			switch ( $args['type'] ) {

				case 'text':

					ModuleMetaBox::fieldString( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );

				break;
				case 'note':

					ModuleMetaBox::fieldTextarea( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );

				break;
				case 'code':
				case 'link':

					ModuleMetaBox::fieldString( $field, [ $field ], $post, TRUE, $args['title'], FALSE, $args['type'] );

				break;
				case 'number':

					ModuleMetaBox::fieldNumber( $field, [ $field ], $post, TRUE, $args['title'], FALSE, $args['type'] );

				break;
				case 'textarea':

					ModuleMetaBox::fieldTextarea( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );

				break;
				case 'term':

					if ( $args['tax'] )
						ModuleMetaBox::fieldTerm( $field, [ $field ], $post, $args['tax'], $args['ltr'], $args['title'] );
					else
						ModuleMetaBox::fieldString( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );
			}
		}

		$this->nonce_field( 'post_main' );
	}

	public function default_meta_box( $post, $box )
	{
		$fields = $this->post_type_field_types( $post->post_type );

		echo '<div class="geditorial-admin-wrap-metabox">';

		do_action( 'geditorial_meta_box_before', $this->module, $post, $fields );

		if ( count( $fields ) )
			do_action( 'geditorial_meta_do_meta_box', $post, $box, $fields, 'box' );

		else
			echo HTML::wrap( _x( 'No Meta Fields', 'Modules: Meta', GEDITORIAL_TEXTDOMAIN ), 'field-wrap field-wrap-empty' );

		do_action( 'geditorial_meta_box_after', $this->module, $post, $fields );

		echo '</div>';
	}

	public function default_meta_raw( $post )
	{
		$fields = $this->post_type_field_types( $post->post_type );

		if ( count( $fields ) ) {

			echo '&nbsp;'; // workaround for weird css bug on no-js!

			foreach ( $fields as $field => $args ) {

				switch ( $args['type'] ) {

					case 'title_before':
					case 'title_after':
						ModuleMetaBox::fieldTitle( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );
					break;

					case 'box':
						ModuleMetaBox::fieldBox( $field, [ $field ], $post, $args['ltr'], $args['title'] );
					break;
				}
			}
		}

		$this->actions( 'box_raw', $this->module, $post, $fields );
		$this->nonce_field( 'post_raw' );
	}

	public function sanitize_post_meta( $postmeta, $fields, $post_id, $post_type )
	{
		if ( $this->nonce_verify( 'post_main' ) || $this->nonce_verify( 'post_raw' ) ) {

			$post = get_post( $post_id );
			$cap  = empty( $post->cap->edit_post ) ? 'edit_post' : $post->cap->edit_post;

			if ( ! current_user_can( $cap, $post_id ) )
				return $postmeta;

			$fields = $this->post_type_field_types( $post_type );

			if ( count( $fields ) ) {

				foreach ( $fields as $field => $args ) {

					switch ( $args['type'] ) {

						case 'term':

							ModuleMetaBox::setPostMetaField_Term( $post_id, $field, $args['tax'] );

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
						case 'textarea':
						case 'note':
						case 'box':

							ModuleMetaBox::setPostMetaField_Text( $postmeta, $field );
					}
				}
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

	public function save_post( $post_id, $post )
	{
		if ( ! $this->is_save_post( $post, $this->post_types() ) )
			return $post_id;

		// NOUNCES MUST CHECKED BY FILTERS
		// CAPABILITIES MUST CHECKED BY FILTERS : if (current_user_can($post->cap->edit_post, $post_id))

		$this->set_meta( $post_id,
			$this->sanitize_post_meta(
				(array) $this->get_postmeta( $post->ID ),
				$this->post_type_fields( $post->post_type ),
				$post->ID,
				$post->post_type
			)
		);

		return $post_id;
	}

	public function manage_pages_columns( $columns )
	{
		return $this->manage_posts_columns( $columns, 'page' );
	}

	public function manage_posts_columns( $columns, $post_type )
	{
		if ( in_array( 'as', $this->post_type_fields( $post_type ) ) )
			unset( $columns['author'] );

		return Arraay::insert( $columns, [
			$this->hook() => $this->get_column_title( 'meta', $post_type ),
		], 'title', 'after' );
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( $this->hook() != $column_name )
			return;

		if ( ! $post = get_post( $post_id ) )
			return;

		$meta    = (array) $this->get_postmeta( $post->ID );
		$fields  = $this->post_type_field_types( $post->post_type );
		$exclude = [ 'ot', 'st', 'highlight', 'as', 'ch', 'le', 'source_title', 'source_url' ];

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

						echo esc_html( $value );

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

	public function quick_edit_custom_box( $column_name, $post_type )
	{
		if ( $this->hook() != $column_name )
			return FALSE;

		$fields = $this->post_type_fields( $post_type );

		foreach ( [ 'ot', 'st', 'as' ] as $field ) {
			if ( in_array( $field, $fields ) ) {
				$selector = 'geditorial-meta-'.$field;
				echo '<label class="'.$selector.'">';
					echo '<span class="title">'.$this->get_string( $field, $post_type ).'</span>';
					echo '<span class="input-text-wrap"><input type="text" name="'.$selector.'" class="'.$selector.'" value=""></span>';
				echo '</label>';
			}
		}

		$this->nonce_field( 'post_raw' );
	}

	public function content_before( $content, $posttypes = NULL )
	{
		if ( ! $this->is_content_insert( NULL ) )
			return;

		ModuleTemplate::metaLead( [
			'before' => $this->wrap_open( '-before entry-lead' ),
			'after'  => '</div>',
		] );
	}

	public function content_after( $content, $posttypes = NULL )
	{
		if ( ! $this->is_content_insert( NULL, FALSE ) )
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

		if ( ! in_array( $post->post_type, $this->post_types() ) )
			return $display_name;

		$fields = $this->post_type_fields( $post->post_type );

		if ( ! in_array( 'as', $fields ) )
			return $display_name;

		if ( $value = $this->get_postmeta( $post->ID, 'as', '' ) )
			$display_name = $value;

		return $display_name;
	}

	public function tools_sub( $uri, $sub )
	{
		$args = $this->settings_form_req( [
			'custom_field'       => '',
			'custom_field_limit' => '',
			'custom_field_type'  => 'post',
			'custom_field_into'  => '',
		], 'tools' );

		$this->settings_form_before( $uri, $sub, 'bulk', 'tools', FALSE, FALSE );

			HTML::h3( _x( 'Meta Tools', 'Modules: Meta', GEDITORIAL_TEXTDOMAIN ) );

			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'Import Custom Fields', 'Modules: Meta', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

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
				'values'       => $this->list_post_types(),
				'default'      => $args['custom_field_type'],
				'option_group' => 'tools',
			] );

			$this->do_settings_field( [
				'type'         => 'select',
				'field'        => 'custom_field_into',
				'values'       => $this->post_type_fields_list( $args['custom_field_type'] ),
				'default'      => $args['custom_field_into'],
				'option_group' => 'tools',
			] );

			echo '&nbsp;&nbsp;';

			Settings::submitButton( 'custom_fields_check',
				_x( 'Check', 'Modules: Meta: Setting Button', GEDITORIAL_TEXTDOMAIN ), TRUE );

			Settings::submitButton( 'custom_fields_convert',
				_x( 'Covert', 'Modules: Meta: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

			Settings::submitButton( 'custom_fields_delete',
				_x( 'Delete', 'Modules: Meta: Setting Button', GEDITORIAL_TEXTDOMAIN ), 'danger', TRUE );

			HTML::desc( _x( 'Check for Custom Fields and import them into Meta', 'Modules: Meta', GEDITORIAL_TEXTDOMAIN ) );

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
					'empty' => HTML::warning( _x( 'No Meta Found!', 'Modules: Meta: Table Empty', GEDITORIAL_TEXTDOMAIN ), FALSE ),
				] );
			}

			echo '</td></tr>';
			echo '</table>';

		$this->settings_form_after( $uri, $sub );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( isset( $_POST['custom_fields_convert'] ) ) {

					$post = $this->settings_form_req( [
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

				} else if ( isset( $_POST['custom_fields_delete'] ) ) {

					$post = $this->settings_form_req( [
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

	protected function import_from_meta( $post_meta_key, $field, $limit = FALSE )
	{
		$rows = Database::getPostMetaRows( $post_meta_key, $limit );

		foreach ( $rows as $row )
			$this->import_to_meta( explode( ',', $row->meta ), $row->post_id, $field, $post_meta_key );

		return count( $rows );
	}

	protected function import_to_meta( $meta, $post_id, $field, $form_key = NULL )
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
				$final .= ', ';

			$final .= $kses ? Helper::kses( $formatted, 'text' ) : $formatted;
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

	private function get_importer_fields( $post_type = NULL, $object = FALSE )
	{
		$fields = [];

		foreach ( $this->post_type_field_types( $post_type ) as $field => $args )
			$fields['meta_'.$field] = $object ? $args : sprintf( _x( 'Meta: %s', 'Modules: Meta: Import Field', GEDITORIAL_TEXTDOMAIN ), $args['title'] );

		return $fields;
	}

	public function importer_fields( $fields, $post_type )
	{
		if ( ! in_array( $post_type, $this->post_types() ) )
			return $fields;

		return array_merge( $fields, $this->get_importer_fields( $post_type ) );
	}

	public function importer_prepare( $value, $post_type, $field, $raw )
	{
		if ( ! in_array( $post_type, $this->post_types() ) )
			return $value;

		$fields = $this->get_importer_fields( $post_type, TRUE );

		if ( ! in_array( $field, array_keys( $fields ) ) )
			return $value;

		// FIXME: check for field type filter
		return Helper::kses( $value, 'none' );
	}

	public function importer_saved( $post, $data, $raw, $field_map, $attach_id )
	{
		if ( ! in_array( $post->post_type, $this->post_types() ) )
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
