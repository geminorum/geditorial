<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Listtable;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Database;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\Templates\Terms as ModuleTemplate;

class Terms extends gEditorial\Module
{

	protected $partials  = [ 'Templates' ];
	protected $supported = [ 'order', 'tagline', 'contact', 'image', 'author', 'color', 'role', 'roles', 'posttype', 'posttypes', 'arrow', 'label', 'code', 'barcode' ];

	public static function module()
	{
		return [
			'name'  => 'terms',
			'title' => _x( 'Terms', 'Modules: Terms', 'geditorial' ),
			'desc'  => _x( 'Taxonomy & Term Tools', 'Modules: Terms', 'geditorial' ),
			'icon'  => 'image-filter',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general'  => $this->prep_fields_for_settings(),
			'_frontend' => [
				'adminbar_summary',
			],
			'taxonomies_option' => 'taxonomies_option',
		];
	}

	protected function get_global_strings()
	{
		return [
			'titles' => [
				'order'     => _x( 'Order', 'Titles', 'geditorial-terms' ),
				'tagline'   => _x( 'Tagline', 'Titles', 'geditorial-terms' ),
				'contact'   => _x( 'Contact', 'Titles', 'geditorial-terms' ),
				'image'     => _x( 'Image', 'Titles', 'geditorial-terms' ),
				'author'    => _x( 'Author', 'Titles', 'geditorial-terms' ),
				'color'     => _x( 'Color', 'Titles', 'geditorial-terms' ),
				'role'      => _x( 'Role', 'Titles', 'geditorial-terms' ),
				'posttype'  => _x( 'Posttype', 'Titles', 'geditorial-terms' ),
				'roles'     => _x( 'Roles', 'Titles', 'geditorial-terms' ),
				'posttypes' => _x( 'Posttypes', 'Titles', 'geditorial-terms' ),
				'arrow'     => _x( 'Arrow', 'Titles', 'geditorial-terms' ),
				'label'     => _x( 'Label', 'Titles', 'geditorial-terms' ),
				'code'      => _x( 'Code', 'Titles', 'geditorial-terms' ),
				'barcode'   => _x( 'Barcode', 'Titles', 'geditorial-terms' ),
			],
			'descriptions' => [
				'order'     => _x( 'Terms are usually ordered alphabetically, but you can choose your own order by numbers.', 'Descriptions', 'geditorial-terms' ),
				'tagline'   => _x( 'Gives more information about the term in a short phrase.', 'Descriptions', 'geditorial-terms' ),
				'contact'   => _x( 'Adds a way to contact someone about the term, by url, email or phone.', 'Descriptions', 'geditorial-terms' ),
				'image'     => _x( 'Assigns a custom image to visually separate terms from each other.', 'Descriptions', 'geditorial-terms' ),
				'author'    => _x( 'Sets a user as term author to help identify who created or owns each term.', 'Descriptions', 'geditorial-terms' ),
				'color'     => _x( 'Terms can have unique colors to help separate them from each other.', 'Descriptions', 'geditorial-terms' ),
				'role'      => _x( 'Terms can have unique role visibility to help separate them for user roles.', 'Descriptions', 'geditorial-terms' ),
				'roles'     => _x( 'Terms can have unique roles visibility to help separate them for user roles.', 'Descriptions', 'geditorial-terms' ),
				'posttype'  => _x( 'Terms can have unique posttype visibility to help separate them on editing.', 'Descriptions', 'geditorial-terms' ),
				'posttypes' => _x( 'Terms can have unique posttypes visibility to help separate them on editing.', 'Descriptions', 'geditorial-terms' ),
				'arrow'     => _x( 'Terms can have direction arrow to help orginize them.', 'Descriptions', 'geditorial-terms' ),
				'label'     => _x( 'Terms can have text label to help orginize them.', 'Descriptions', 'geditorial-terms' ),
				'code'      => _x( 'Terms can have text code to help orginize them.', 'Descriptions', 'geditorial-terms' ),
				'barcode'   => _x( 'Terms can have barcode to help orginize them.', 'Descriptions', 'geditorial-terms' ),
			],
			'misc' => [
				'order_column_title'     => _x( 'O', 'Column Title: Order', 'geditorial-terms' ),
				'tagline_column_title'   => _x( 'Tagline', 'Column Title: Tagline', 'geditorial-terms' ),
				'contact_column_title'   => _x( 'C', 'Column Title: Contact', 'geditorial-terms' ),
				'image_column_title'     => _x( 'Image', 'Column Title: Image', 'geditorial-terms' ),
				'author_column_title'    => _x( 'Author', 'Column Title: Author', 'geditorial-terms' ),
				'color_column_title'     => _x( 'C', 'Column Title: Color', 'geditorial-terms' ),
				'role_column_title'      => _x( 'Role', 'Column Title: Role', 'geditorial-terms' ),
				'roles_column_title'     => _x( 'Roles', 'Column Title: Roles', 'geditorial-terms' ),
				'posttype_column_title'  => _x( 'Posttype', 'Column Title: Posttype', 'geditorial-terms' ),
				'posttypes_column_title' => _x( 'Posttypes', 'Column Title: Posttypes', 'geditorial-terms' ),
				'arrow_column_title'     => _x( 'A', 'Column Title: Arrow', 'geditorial-terms' ),
				'label_column_title'     => _x( 'Label', 'Column Title: Label', 'geditorial-terms' ),
				'code_column_title'      => _x( 'Code', 'Column Title: Label', 'geditorial-terms' ),
				'barcode_column_title'   => _x( 'BC', 'Column Title: Label', 'geditorial-terms' ),
				'posts_column_title'     => _x( 'P', 'Column Title: Posts', 'geditorial-terms' ),

				'arrow_directions' => [
					'undefined' => _x( 'Undefined', 'Arrow Directions', 'geditorial-terms' ),
					'up'        => _x( 'Up', 'Arrow Directions', 'geditorial-terms' ),
					'right'     => _x( 'Right', 'Arrow Directions', 'geditorial-terms' ),
					'down'      => _x( 'Down', 'Arrow Directions', 'geditorial-terms' ),
					'left'      => _x( 'Left', 'Arrow Directions', 'geditorial-terms' ),
				],
			],
			'js' => [
				'modal_title'  => _x( 'Choose an Image', 'Javascript String', 'geditorial-terms' ),
				'modal_button' => _x( 'Set as image', 'Javascript String', 'geditorial-terms' ),
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
			'ef_usergroup',
			'post_status',
			'rel_people',
			'rel_post',
			'cartable_user',
			'cartable_group',
			'follow_users',
			'follow_groups',
		] );
	}

	protected function get_taxonomies_support( $field )
	{
		$supported = Taxonomy::get();
		$excluded  = $this->taxonomies_excluded();

		switch ( $field ) {
			case 'role': $excluded[] = 'audit_attribute'; break;
			case 'tagline': $excluded[] = 'post_tag'; break;
			case 'arrow': return Arraay::keepByKeys( $supported, [ 'warehouse_placement' ] ); break; // override!
		}

		return array_diff_key( $supported, array_flip( $excluded ) );
	}

	protected function prep_fields_for_settings( $fields = NULL )
	{
		if ( is_null( $fields ) )
			$fields = $this->supported;

		$list = [];

		foreach ( $fields as $field ) {

			$name = $this->get_string( $field, FALSE, 'titles' );
			$desc = $this->get_string( $field, FALSE, 'descriptions', NULL );

			/* translators: %s: field name */
			$title = sprintf( _x( 'Term %s', 'Setting Title', 'geditorial-terms' ), $name );

			$list[] = [
				'field'       => 'term_'.$field,
				'type'        => 'taxonomies',
				'title'       => $title,
				/* translators: %s: field name */
				'description' => $desc ?: sprintf( _x( 'Supports %s for terms in the selected taxonomies.', 'Setting Description', 'geditorial-terms' ), $name ),
				'values'      => $this->get_taxonomies_support( $field ),
			];
		}

		return $list;
	}

	public function init()
	{
		parent::init();

		$this->register_meta_fields();

		$this->action( [ 'edit_term', 'create_term' ], 3 );

		if ( $this->get_setting( 'term_author' ) )
			$this->filter_self( 'supported_field_edit', 4, 8, 'author' );

		if ( ! is_admin() )
			return;

		add_filter( 'gnetwork_taxonomy_export_term_meta', [ $this, 'taxonomy_export_term_meta' ], 9, 2 );
		add_filter( 'gnetwork_taxonomy_bulk_actions', [ $this, 'taxonomy_bulk_actions' ], 14, 2 );
		add_filter( 'gnetwork_taxonomy_bulk_callback', [ $this, 'taxonomy_bulk_callback' ], 14, 3 );
	}

	public function init_ajax()
	{
		if ( $taxonomy = self::req( 'taxonomy' ) )
			$this->_edit_tags_screen( $taxonomy );
	}

	public function current_screen( $screen )
	{
		$enqueue = FALSE;

		if ( 'dashboard' == $screen->base ) {

			if ( current_user_can( 'edit_others_posts' ) )
				add_filter( 'gnetwork_dashboard_pointers', [ $this, 'dashboard_pointers' ] );

		} else if ( 'edit-tags' == $screen->base ) {

			$fields = $this->get_supported( $screen->taxonomy );

			foreach ( $fields as $field ) {

				if ( $this->filters( 'disable_field_edit', FALSE, $field, $screen->taxonomy ) )
					continue;

				add_action( $screen->taxonomy.'_add_form_fields', function( $taxonomy ) use( $field ){
					$this->add_form_field( $field, $taxonomy );
				}, 8, 1 );

				if ( ! in_array( $field, [ 'roles', 'posttypes' ] ) ) {

					add_action( 'quick_edit_custom_box', function( $column, $screen, $taxonomy ) use( $field ){
						if ( $this->classs( $field ) == $column )
							$this->quick_form_field( $field, $taxonomy );
					}, 10, 3 );

					$enqueue = TRUE;
				}

				if ( 'image' == $field ) {

					Scripts::enqueueThickBox();

				} else if ( 'color' == $field ) {

					Scripts::enqueueColorPicker();
				}
			}

			if ( $enqueue ) {

				$this->_admin_enabled();

				wp_enqueue_media();

				$this->enqueue_asset_js( [
					'strings' => $this->strings['js'],
				], NULL, [ 'jquery', 'media-upload' ] );
			}

			if ( count( $fields ) ) {
				$this->_edit_tags_screen( $screen->taxonomy );
				add_filter( 'manage_edit-'.$screen->taxonomy.'_sortable_columns', [ $this, 'sortable_columns' ] );
			}

		} else if ( 'term' == $screen->base ) {

			$fields = $this->get_supported( $screen->taxonomy );

			foreach ( $fields as $field ) {

				$disabled = $this->filters( 'disable_field_edit', FALSE, $field, $screen->taxonomy );

				add_action( $screen->taxonomy.'_edit_form_fields', function( $term, $taxonomy ) use( $field, $disabled ){
					$this->edit_form_field( $field, $taxonomy, $term, $disabled );
				}, 8, 2 );

				if ( $disabled )
					continue;

				if ( ! in_array( $field, [ 'roles', 'posttypes' ] ) )
					$enqueue = TRUE;

				if ( 'image' == $field ) {

					Scripts::enqueueThickBox();

				} else if ( 'color' == $field ) {

					Scripts::enqueueColorPicker();
				}
			}

			if ( $enqueue ) {

				$this->_admin_enabled();

				wp_enqueue_media();

				$this->enqueue_asset_js( [
					'strings' => $this->strings['js'],
				], NULL, [ 'jquery', 'media-upload' ] );
			}
		}
	}

	private function _edit_tags_screen( $taxonomy )
	{
		add_filter( 'manage_edit-'.$taxonomy.'_columns', [ $this, 'manage_columns' ] );
		add_filter( 'manage_'.$taxonomy.'_custom_column', [ $this, 'custom_column' ], 10, 3 );
	}

	// FALSE for all
	private function get_supported( $taxonomy = FALSE )
	{
		$list = [];

		foreach ( $this->supported as $field )
			if ( FALSE === $taxonomy || $this->in_setting( $taxonomy, 'term_'.$field ) )
				$list[] = $field;

		return $this->filters( 'supported_fields', $list, $taxonomy );
	}

	// FALSE for all
	private function list_supported( $taxonomy = FALSE )
	{
		$list = [];

		foreach ( $this->supported as $field )
			if ( FALSE === $taxonomy || $this->in_setting( $taxonomy, 'term_'.$field ) )
				$list[$field] = $this->strings['titles'][$field];

		return $this->filters( 'list_supported_fields', $list, $taxonomy );
	}

	// by default the metakey is the same as the field
	private function get_supported_metakey( $field, $taxonomy = FALSE )
	{
		return $this->filters( 'supported_field_metakey', $field, $field, $taxonomy );
	}

	private function get_supported_position( $field, $taxonomy = FALSE )
	{
		switch ( $field ) {
			case 'order':

				$position = [ 'cb', 'after' ];

			break;
			case 'image':
			case 'color':
			case 'role':
			case 'posttype':
			case 'code':
			case 'barcode':
			case 'arrow':

				$position = [ 'name', 'before' ];

			break;
			case 'label':
			case 'tagline':
			case 'contact':
			default:
				$position = [ 'name', 'after' ];
		}

		return $this->filters( 'supported_field_position', $position, $field, $taxonomy );
	}

	// @REF: https://make.wordpress.org/core/2018/07/27/registering-metadata-in-4-9-8/
	// @REF: https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/
	protected function register_meta_fields()
	{
		foreach ( $this->supported as $field ) {

			if ( ! $taxonomies = $this->get_setting( 'term_'.$field ) )
				continue;

			$prepare = 'register_prepare_callback_'.$field;

			// 'string', 'boolean', 'integer', 'number', 'array', and 'object'
			if ( in_array( $field, [ 'order', 'author', 'image' ] ) )
				$defaults = [ 'type'=> 'integer', 'single' => TRUE, 'default' => 0 ];

			else if ( in_array( $field, [ 'roles', 'posttypes' ] ) )
				$defaults = [ 'type'=> 'array', 'single' => FALSE, 'default' => [] ];

			else
				$defaults = [ 'type'=> 'string', 'single' => TRUE, 'default' => '' ];

			$defaults = array_merge( $defaults, [
				'sanitize_callback' => [ $this, 'register_sanitize_callback' ],
				'auth_callback'     => [ $this, 'register_auth_callback' ],
				'show_in_rest'      => TRUE,
			] );

			if ( 'array' === $defaults['type'] )
				$defaults['show_in_rest'] = [
					'schema' => [

						// @REF: https://developer.wordpress.org/reference/functions/register_term_meta/#comment-3969
						// 'type'     => 'string',
						// 'format'   => 'url',
						// 'context'  => [ 'view', 'edit' ],
						// 'readonly' => TRUE,

						'type'  => 'array',
						'items' => [],
					],
					'prepare_callback' => method_exists( $this, $prepare ) ? [ $this, $prepare ] : NULL,
				];

			foreach ( $taxonomies as $taxonomy ) {

				$args = array_merge( $defaults, [
					'object_subtype' => $taxonomy,
					'description'    => $this->get_string( $field, $taxonomy, 'descriptions', '' ),
				] );

				$filtred = $this->filters( 'register_field_args', $args, $field, $taxonomy );

				if ( FALSE !== $filtred )
					register_meta( 'term', $field, $filtred );
			}

			// register general field for prepared meta data
			// mainly for display purposes
			if ( in_array( $field, [ 'image' ] ) )
				register_rest_field( $taxonomies, $field, [
					'get_callback' => [ $this, 'register_rest_get_callback' ],
				] );
		}
	}

	public static function register_rest_get_callback( $term, $attr, $request, $object_type )
	{
		switch ( $attr ) {

			case 'image':
				$metakey = $this->get_supported_metakey( 'image', $object_type );
				return Media::prepAttachmentData( get_term_meta( $term['id'], $metakey, TRUE ) );

				break;
		}
	}

	public function register_auth_callback( $allowed, $meta_key, $object_id, $user_id, $cap, $caps )
	{
		return $this->filters( 'disable_field_edit', FALSE, $meta_key, get_object_subtype( 'term', $object_id ) )
			? FALSE
			: $allowed;
	}

	public function register_sanitize_callback( $meta_value, $meta_key, $object_type )
	{
		return $this->filters( 'supported_field_edit', $meta_value, $meta_key, $object_type, NULL );
	}

	public function manage_columns( $columns )
	{
		if ( ! $taxonomy = self::req( 'taxonomy' ) )
			return $columns;

		foreach ( $this->get_supported( $taxonomy ) as $field ) {

			$position = $this->get_supported_position( $field, $taxonomy );

			$columns = Arraay::insert( $columns, [
				$this->classs( $field ) => $this->get_column_title( $field, $taxonomy ),
			], $position[0], $position[1] );
		}

		// smaller name for posts column
		if ( array_key_exists( 'posts', $columns ) )
			$columns['posts'] = $this->get_column_title( 'posts', $taxonomy );

		return $columns;
	}

	public function sortable_columns( $columns )
	{
		if ( ! $taxonomy = self::req( 'taxonomy' ) )
			return $columns;

		foreach ( $this->get_supported( $taxonomy ) as $field )
			if ( ! in_array( $field, [ 'tagline', 'contact', 'image', 'roles', 'posttypes', 'arrow', 'label', 'code', 'barcode' ] ) )
				$columns[$this->classs( $field )] = 'meta_'.$field;

		return $columns;
	}

	public function custom_column( $display, $column, $term_id )
	{
		if ( ! $taxonomy = self::req( 'taxonomy' ) )
			return;

		$term = get_term_by( 'id', $term_id, $taxonomy );

		foreach ( $this->get_supported( $taxonomy ) as $field ) {

			if ( $this->classs( $field ) != $column )
				continue;

			$this->display_form_field( $field, $taxonomy, $term, TRUE );
		}
	}

	// TODO: use readonly inputs on non-columns
	private function display_form_field( $field, $taxonomy, $term, $column = TRUE )
	{
		$html    = $meta = '';
		$metakey = $this->get_supported_metakey( $field, $taxonomy );

		switch ( $field ) {
			case 'order':

				$meta = get_term_meta( $term->term_id, $metakey, TRUE );

				if ( $meta || '0' === $meta ) {

					$html = Listtable::columnOrder( $meta );

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'arrow':

				$meta = get_term_meta( $term->term_id, $metakey, TRUE );

				if ( $meta || 'undefined' === $meta ) {

					$dirs = $this->get_string( 'arrow_directions', FALSE, 'misc', [] );

					if ( array_key_exists( $meta, $dirs ) )
						$icon = HTML::getDashicon( sprintf( 'arrow-%s-alt', $meta ), $dirs[$meta], 'icon-arrow' );
					else
						$icon = HTML::getDashicon( 'warning', $meta, 'icon-warning' );

					$html = '<span class="field-'.$field.'" data-'.$field.'="'.HTML::escape( $meta ).'">'.$icon.'</span>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'barcode':

				$meta = get_term_meta( $term->term_id, $metakey, TRUE );

				if ( $meta ) {

					$icon = HTML::getDashicon( 'tagcloud', $meta, 'icon-barcode' );
					$html = '<span class="field-'.$field.'" data-'.$field.'="'.HTML::escape( $meta ).'">'.$icon.'</span>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'code':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$html = '<code class="field-'.$field.'" data-'.$field.'="'.HTML::escape( $meta ).'">'.$meta.'</code>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'label':
			case 'tagline':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$html = '<span class="field-'.$field.'" data-'.$field.'="'.HTML::escape( $meta ).'">'
						.Helper::prepTitle( $meta ).'</span>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

			break;
			case 'contact':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$html = '<span class="field-'.$field.'" data-'.$field.'="'.HTML::escape( $meta )
						.'" title="'.HTML::wrapLTR( HTML::escape( $meta ) ).'">'
						.Helper::prepContact( $meta, HTML::getDashicon( 'phone' ) ).'</span>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

			break;
			case 'image':

				// $sizes = Media::getPosttypeImageSizes( $post->post_type );
				// $size  = isset( $sizes[$post->post_type.'-thumbnail'] ) ? $post->post_type.'-thumbnail' : 'thumbnail';
				$size = [ 45, 72 ]; // FIXME

				$html = $this->filters( 'column_image', Taxonomy::htmlFeaturedImage( $term->term_id, $size, TRUE, $metakey ), $term->term_id, $size );

			break;
			case 'author':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$user = get_user_by( 'id', $meta );
					$html = '<span class="field-'.$field.'" data-'.$field.'="'.$meta.'">'.$user->display_name.'</span>';

				} else {

					$html = $this->field_empty( $field, '0', $column );
				}

			break;
			case 'color':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
					$html = '<i class="field-color" data-'.$field.'="'.HTML::escape( $meta )
						.'" style="background-color:'.HTML::escape( $meta )
						.'" title="'.HTML::wrapLTR( HTML::escape( $meta ) ).'"></i>';

			break;
			case 'role':

				if ( empty( $this->all_roles ) )
					$this->all_roles = $this->get_settings_default_roles();

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
					$html = '<span class="field-'.$field.'" data-'.$field.'="'.HTML::escape( $meta ).'">'
						.( empty( $this->all_roles[$meta] )
							? HTML::escape( $meta )
							: $this->all_roles[$meta] )
						.'</span>';

				else
					$html = $this->field_empty( 'role', '0', $column );

			break;
			case 'roles':

				if ( empty( $this->all_roles ) )
					$this->all_roles = $this->get_settings_default_roles();

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$list = [];

					foreach ( (array) $meta as $role )
						$list[] = '<span class="field-'.$field.'" data-'.$field.'="'.HTML::escape( $role ).'">'
							.( empty( $this->all_roles[$role] )
								? HTML::escape( $role )
								: $this->all_roles[$role] )
							.'</span>';

					$html = Helper::getJoined( $list );

				} else {

					$html = $this->field_empty( $field, '0', $column );
				}

			break;
			case 'posttype':

				if ( empty( $this->all_posttypes ) )
					$this->all_posttypes = PostType::get( 2 );

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
					$html = '<span class="field-'.$field.'" data-'.$field.'="'.HTML::escape( $meta ).'">'
						.( empty( $this->all_posttypes[$meta] )
							? HTML::escape( $meta )
							: $this->all_posttypes[$meta] )
						.'</span>';

				else
					$html = $this->field_empty( $field, '0', $column );

			break;
			case 'posttypes':

				if ( empty( $this->all_posttypes ) )
					$this->all_posttypes = PostType::get( 2 );

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$list = [];

					foreach ( (array) $meta as $posttype )
						$list[] = '<span class="field-'.$field.'" data-'.$field.'="'.HTML::escape( $posttype ).'">'
							.( empty( $this->all_posttypes[$posttype] )
								? HTML::escape( $posttype )
								: $this->all_posttypes[$posttype] )
							.'</span>';

					$html = Helper::getJoined( $list );

				} else {

					$html = $this->field_empty( $field, '0', $column );
				}
		}

		echo $this->filters( 'supported_field_column', $html, $field, $taxonomy, $term, $meta, $metakey );
	}

	private function field_empty( $field, $value = '0', $column = TRUE )
	{
		if ( $column )
			return '<span class="column-'.$field.'-empty -empty">&mdash;</span>'
				.'<span class="field-'.$field.'" data-'.$field.'="'.$value.'"></span>';

		return gEditorial()->na();
	}

	public function edit_term( $term_id, $tt_id, $taxonomy )
	{
		foreach ( $this->get_supported( $taxonomy ) as $field ) {

			if ( ! array_key_exists( 'term-'.$field, $_REQUEST ) )
				continue;

			$metakey = $this->get_supported_metakey( $field, $taxonomy );
			$meta    = empty( $_REQUEST['term-'.$field] ) ? FALSE : $_REQUEST['term-'.$field];
			$meta    = $this->filters( 'supported_field_edit', $meta, $field, $taxonomy, $term_id, $metakey );

			if ( $meta ) {

				$meta = is_array( $meta ) ? array_filter( $meta ) : trim( HTML::escape( $meta ) );

				if ( 'image' == $field ) {
					update_post_meta( (int) $meta, '_wp_attachment_is_term_image', $taxonomy );
					do_action( 'clean_term_attachment_cache', (int) $meta, $taxonomy, $term_id );
				}

				update_term_meta( $term_id, $metakey, $meta );

			} else {

				if ( 'image' == $field && $meta = get_term_meta( $term_id, $metakey, TRUE ) ) {
					delete_post_meta( (int) $meta, '_wp_attachment_is_term_image' );
					do_action( 'clean_term_attachment_cache', (int) $meta, $taxonomy, $term_id );
				}

				delete_term_meta( $term_id, $metakey );
			}

			// FIXME: experiment: since the action may trigger twice
			unset( $_REQUEST['term-'.$field] );
		}
	}

	private function quick_form_field( $field, $taxonomy )
	{
		echo '<fieldset><div class="inline-edit-col"><label><span class="title">';

			$title = $this->get_string( $field, $taxonomy, 'titles', $field );
			echo HTML::escape( $this->filters( 'field_'.$field.'_title', $title, $taxonomy, $field, FALSE ) );

		echo '</span><span class="input-text-wrap">';

			$this->quickedit_field( $field, $taxonomy );

		echo '</span></label></div></fieldset>';
	}

	private function add_form_field( $field, $taxonomy, $term = FALSE )
	{
		echo '<div class="form-field term-'.$field.'-wrap">';
		echo '<label for="term-'.$field.'">';

			$title = $this->get_string( $field, $taxonomy, 'titles', $field );
			echo HTML::escape( $this->filters( 'field_'.$field.'_title', $title, $taxonomy, $field, $term ) );

		echo '</label>';

			$this->form_field( $field, $taxonomy, $term );

			$desc = $this->get_string( $field, $taxonomy, 'descriptions', '' );
			HTML::desc( $this->filters( 'field_'.$field.'_desc', $desc, $taxonomy, $field, $term ) );

		echo '</div>';
	}

	private function edit_form_field( $field, $taxonomy, $term, $disabled = FALSE )
	{
		echo '<tr class="form-field term-'.$field.'-wrap"><th scope="row" valign="top">';
		echo '<label for="term-'.$field.'">';

			$title = $this->get_string( $field, $taxonomy, 'titles', $field );
			echo HTML::escape( $this->filters( 'field_'.$field.'_title', $title, $taxonomy, $field, $term ) );

		echo '</label></th><td>';

			if ( $disabled )
				$this->display_form_field( $field, $taxonomy, $term, FALSE );
			else
				$this->form_field( $field, $taxonomy, $term );

			$desc = $this->get_string( $field, $taxonomy, 'descriptions', '' );
			HTML::desc( $this->filters( 'field_'.$field.'_desc', $desc, $taxonomy, $field, $term ) );

		echo '</td></tr>';
	}

	private function form_field( $field, $taxonomy, $term = FALSE )
	{
		$html    = '';
		$term_id = empty( $term->term_id ) ? 0 : $term->term_id;
		$metakey = $this->get_supported_metakey( $field, $taxonomy );
		$meta    = get_term_meta( $term_id, $metakey, TRUE );

		switch ( $field ) {

			case 'image':

				$html.= '<div>'.HTML::tag( 'img', [
					'id'      => $this->classs( $field, 'img' ),
					'src'     => empty( $meta ) ? '' : wp_get_attachment_image_url( $meta, 'thumbnail' ),
					'loading' => 'lazy',
					'style'   => empty( $meta ) ? 'display:none' : FALSE,
				] ).'</div>';

				$html.= HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => $meta,
					'style' => 'display:none',
				] );

				$html.= HTML::tag( 'a', [
					'class' => [ 'button', 'button-small', 'button-secondary', '-modal' ],
				], _x( 'Choose', 'Button', 'geditorial-terms' ) );

				$html.= '&nbsp;'.HTML::tag( 'a', [
					'class' => [ 'button', 'button-small', 'button-link-delete', '-remove' ],
					'style' => empty( $meta ) ? 'display:none' : FALSE,
				], _x( 'Remove', 'Button', 'geditorial-terms' ) );

			break;
			case 'order':

				$html.= HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'number',
					'value' => empty( $meta ) ? '' : $meta,
					'class' => 'small-text',
					'data'  => [ 'ortho' => 'number' ],
				] );

			break;
			case 'author':

				// selected value on add new term form
				if ( empty( $meta ) && FALSE === $term )
					$meta = get_current_user_id();

				$html.= Listtable::restrictByAuthor( empty( $meta ) ? '0' : $meta, 'term-'.$field, [
					'echo'            => FALSE,
					'show_option_all' => Settings::showOptionNone(),
				] );

			break;
			case 'role':

				$html.= HTML::dropdown( $this->get_settings_default_roles(), [
					'id'         => $this->classs( $field, 'id' ),
					'name'       => 'term-'.$field,
					'selected'   => empty( $meta ) ? '0' : $meta,
					'none_title' => Settings::showOptionNone(),
				] );

			break;
			case 'roles':

				$html.= '<div class="wp-tab-panel"><ul>';

				foreach ( $this->get_settings_default_roles() as $role => $name ) {

					$checkbox = HTML::tag( 'input', [
						'type'    => 'checkbox',
						'name'    => 'term-'.$field.'[]',
						'id'      => $this->classs( $field, 'id', $role ),
						'value'   => $role,
						'checked' => empty( $meta ) ? FALSE : in_array( $role, (array) $meta ),
					] );

					$html.= '<li>'.HTML::tag( 'label', [
						'for' => $this->classs( $field, 'id', $role ),
					], $checkbox.'&nbsp;'.HTML::escape( $name ) ).'</li>';
				}

				$html.= '</ul></div>';

			break;
			case 'posttype':

				$html.= HTML::dropdown( PostType::get( 2 ), [
					'id'         => $this->classs( $field, 'id' ),
					'name'       => 'term-'.$field,
					'selected'   => empty( $meta ) ? '0' : $meta,
					'none_title' => Settings::showOptionNone(),
				] );

			break;
			case 'posttypes':

				$html.= '<div class="wp-tab-panel"><ul>';

				foreach ( PostType::get( 2 ) as $posttype => $name ) {

					$checkbox = HTML::tag( 'input', [
						'type'    => 'checkbox',
						'name'    => 'term-'.$field.'[]',
						'id'      => $this->classs( $field, 'id', $posttype ),
						'value'   => $posttype,
						'checked' => empty( $meta ) ? FALSE : in_array( $posttype, (array) $meta ),
					] );

					$html.= '<li>'.HTML::tag( 'label', [
						'for' => $this->classs( $field, 'id', $posttype ),
					], $checkbox.'&nbsp;'.HTML::escape( $name ) ).'</li>';
				}

				$html.= '</ul></div>';

			break;
			case 'color':

				$html.= HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => empty( $meta ) ? '' : $meta,
					'data'  => [ 'ortho' => 'color' ],
				] );

			break;
			case 'tagline':

				$html.= HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => empty( $meta ) ? '' : $meta,
					'data'  => [ 'ortho' => 'text' ],
				] );

				break;

			case 'code':
			case 'barcode':
			case 'contact':

				$html.= HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => empty( $meta ) ? '' : $meta,
					'class' => [ 'code' ],
					'data'  => [ 'ortho' => 'code' ],
				] );

				break;

			case 'arrow':

				$html.= HTML::dropdown( $this->get_string( 'arrow_directions', FALSE, 'misc', [] ), [
					'id'       => $this->classs( $field, 'id' ),
					'name'     => 'term-'.$field,
					'selected' => empty( $meta ) ? 'undefined' : $meta,
				] );

				break;

			case 'label':
			default:

				$html.= HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => empty( $meta ) ? '' : $meta,
				] );
		}

		echo $this->filters( 'supported_field_form', $html, $field, $taxonomy, $term_id, $meta );
	}

	private function quickedit_field( $field, $taxonomy )
	{
		$html = '';

		switch ( $field ) {

			case 'image':

				$html.= '<input type="hidden" name="term-'.$field.'" value="" />';

				$html.= HTML::tag( 'button', [
					'class' => [ 'button', 'button-small', 'button-secondary', '-modal', '-quick' ],
				], _x( 'Choose', 'Button', 'geditorial-terms' ) );

				$html.= '&nbsp;'.HTML::tag( 'a', [
					'href'  => '',
					'class' => [ 'button', 'button-small', 'button-link-delete', '-remove', '-quick' ],
					'style' => 'display:none',
				], _x( 'Remove', 'Button', 'geditorial-terms' ) ).'&nbsp;';

				$html.= HTML::tag( 'img', [
					// 'src'   => '',
					'class' => '-img',
					'style' => 'display:none',
				] );

			break;
			case 'order':

				$html.= HTML::tag( 'input', [
					'name'  => 'term-'.$field,
					'type'  => 'number',
					'value' => '',
					'class' => [ 'ptitle', 'small-text' ],
					// 'data'  => [ 'ortho' => 'number' ],
				] );

			break;
			case 'author':

				$html.= Listtable::restrictByAuthor( 0, 'term-'.$field, [
					'echo'            => FALSE,
					'show_option_all' => Settings::showOptionNone(),
				] );

			break;
			case 'color':

				$html.= HTML::tag( 'input', [
					'name'  => 'term-'.$field,
					'type'  => 'color',
					// 'value' => '', // do not set default value: @REF: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/color#value
					'class' => [ 'small-text' ],
					'data'  => [ 'ortho' => 'color' ],
				] );

			break;
			case 'role':

				$html.= HTML::dropdown( $this->get_settings_default_roles(), [
					'name'       => 'term-'.$field,
					'selected'   => '0',
					'none_title' => Settings::showOptionNone(),
				] );

			break;
			case 'posttype':

				$html.= HTML::dropdown( PostType::get( 2 ), [
					'name'       => 'term-'.$field,
					'selected'   => '0',
					'none_title' => Settings::showOptionNone(),
				] );

				break;

			case 'code':
			case 'barcode':
			case 'contact':

				$html.= HTML::tag( 'input', [
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => '',
					'class' => [ 'ptitle', 'code' ],
					'data'  => [ 'ortho' => 'code' ],
				] );

				break;

			case 'arrow':

				$html.= HTML::dropdown( $this->get_string( 'arrow_directions', FALSE, 'misc', [] ), [
					'name'     => 'term-'.$field,
					'selected' => 'undefined',
				] );

				break;

			case 'label':
			case 'tagline':
			default:
				$html.= '<input type="text" class="ptitle" name="term-'.$field.'" value="" />';
		}

		echo $this->filters( 'supported_field_quickedit', $html, $field, $taxonomy );
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() )
			return;

		if ( is_tax() || is_tag() || is_category() ) {

			if ( ! $term = get_queried_object() )
				return;

			if ( ! current_user_can( 'assign_term', $term->term_id ) )
				return;

			$nodes[] = [
				'id'     => $this->classs(),
				'title'  => _x( 'Term Summary', 'Adminbar', 'geditorial-terms' ),
				'parent' => $parent,
				'href'   => $this->get_module_url( 'reports' ),
			];

			$nodes[] = [
				'id'     => $this->classs( 'count' ),
				'title'  => _x( 'Post Count', 'Adminbar', 'geditorial-terms' ).': '.Helper::getCounted( $term->count ),
				'parent' => $this->classs(),
				'href'   => FALSE,
			];

			// TODO: display `$term->parent`

			if ( trim( $term->description ) ) {

				$nodes[] = [
					'id'     => $this->classs( 'desc' ),
					'title'  => _x( 'Description', 'Adminbar', 'geditorial-terms' ),
					'parent' => $this->classs(),
					'href'   => WordPress::getEditTaxLink( $term->taxonomy, $term->term_id ),
				];

				$nodes[] = [
					'id'     => $this->classs( 'desc', 'html' ),
					'parent' => $this->classs( 'desc' ),
					'href'   => FALSE,
					'meta'   => [
						'html'  => Helper::prepDescription( $term->description ),
						'class' => 'geditorial-adminbar-desc-wrap -wrap '.$this->classs(),
					],
				];

			} else {

				$nodes[] = [
					'id'     => $this->classs( 'desc', 'empty' ),
					'title'  => _x( 'Description', 'Adminbar', 'geditorial-terms' ).': '.gEditorial\Plugin::na(),
					'parent' => $this->classs(),
					'href'   => WordPress::getEditTaxLink( $term->taxonomy, $term->term_id ),
				];
			}

			foreach ( $this->get_supported( $term->taxonomy ) as $field ) {

				$node = [
					'id'     => $this->classs( $field ),
					'parent' => $this->classs(),
					/* translators: %s: meta title */
					'title'  => sprintf( _x( 'Meta: %s', 'Adminbar', 'geditorial-terms' ),
						$this->get_string( $field, $term->taxonomy, 'titles', $field ) ),
				];

				$child = [
					'id'     => $this->classs( $field, 'html' ),
					'parent' => $node['id'],
				];

				$metakey = $this->get_supported_metakey( $field, $term->taxonomy );

				switch ( $field ) {
					case 'order':

						$node['title'].= ': '.Helper::htmlOrder( get_term_meta( $term->term_id, $metakey, TRUE ) );

					break;
					case 'image':

						$image = Taxonomy::htmlFeaturedImage( $term->term_id, [ 45, 72 ], TRUE, $metakey );

						$child['meta'] = [
							'html'  => $image ?: gEditorial()->na( FALSE ),
							'class' => 'geditorial-adminbar-image-wrap',
						];

					break;
					case 'author':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.get_user_by( 'id', $meta )->display_name;
						else
							$node['title'].= ': '.gEditorial\Plugin::na();

					break;
					case 'color':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.'<i class="field-color" style="background-color:'.HTML::escape( $meta ).'"></i>';
						else
							$node['title'].= ': '.gEditorial\Plugin::na();

					break;
					case 'tagline':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.Helper::prepTitle( $meta );
						else
							$node['title'].= ': '.gEditorial\Plugin::na();

					break;
					case 'contact':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.Helper::prepContact( $meta );
						else
							$node['title'].= ': '.gEditorial\Plugin::na();

					// TODO: add the rest!

					break;
					default: $node['title'] = _x( 'Meta: Uknonwn', 'Adminbar', 'geditorial-terms' ); break;
				}

				$nodes[] = $node;

				if ( in_array( $field, [ 'image' ] ) )
					$nodes[] = $child;
			}

			return;
		}

		if ( ! is_singular() )
			return;

		$post_id = get_queried_object_id();

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		$nodes[] = [
			'id'     => $this->classs(),
			'title'  => _x( 'Summary of Terms', 'Adminbar', 'geditorial-terms' ),
			'parent' => $parent,
			'href'   => $this->get_module_url( 'reports' ),
		];

		foreach ( $this->taxonomies() as $taxonomy ) {

			if ( ! $object = get_taxonomy( $taxonomy ) )
				continue;

			$terms = get_the_terms( $post_id, $taxonomy );

			if ( ! $terms || is_wp_error( $terms ) )
				continue;

			$nodes[] = [
				'id'     => $this->classs( 'tax', $taxonomy ),
				'title'  => $object->labels->name.':',
				'parent' => $this->classs(),
				'href'   => WordPress::getEditTaxLink( $taxonomy ),
			];

			foreach ( $terms as $term )
				$nodes[] = [
					'id'     => $this->classs( 'term', $term->term_id ),
					'title'  => '&ndash; '.sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ),
					'parent' => $this->classs(),
					'href'   => get_term_link( $term ),
				];
		}
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				$count = 0;

				if ( Tablelist::isAction( 'clean_uncategorized', TRUE ) ) {

					$uncategorized = get_option( 'default_category' );

					foreach ( $_POST['_cb'] as $post_id ) {

						if ( ! $post = get_post( $post_id ) )
							continue;

						if ( ! in_array( 'category', get_object_taxonomies( $post ) ) )
							continue;

						$terms = wp_get_object_terms( $post->ID, 'category', [ 'fields' => 'ids' ] );
						$diff  = array_diff( $terms, [ $uncategorized ] );

						if ( empty( $diff ) )
							continue;

						$results = wp_set_object_terms( $post->ID, $diff, 'category' );

						if ( ! self::isError( $results ) )
							$count++;
					}

					if ( $count )
						WordPress::redirectReferer( [
							'message' => 'cleaned',
							'count'   => $count,
						] );

				} else if ( Tablelist::isAction( 'orphaned_terms' ) ) {

					$post = $this->get_current_form( [
						'dead_tax' => FALSE,
						'live_tax' => FALSE,
					], 'tools' );

					if ( $post['dead_tax'] && $post['live_tax'] ) {

						global $wpdb;

						$result = $wpdb->query( $wpdb->prepare( "
							UPDATE {$wpdb->term_taxonomy} SET taxonomy = %s WHERE taxonomy = %s
						", trim( $post['live_tax'] ), trim( $post['dead_tax'] ) ) );

						if ( count( $result ) )
							WordPress::redirectReferer( [
								'message' => 'changed',
								'count'   => count( $result ),
							] );
					}
				}

				WordPress::redirectReferer( 'nochange' );
			}
		}
	}

	// TODO: option to delete orphaned terms
	protected function render_tools_html( $uri, $sub )
	{
		$available     = FALSE;
		$uncategorized = $this->get_uncategorized_count();
		$db_taxes      = Database::getTaxonomies( TRUE );
		$live_taxes    = Taxonomy::get( 6 );
		$dead_taxes    = array_diff_key( $db_taxes, $live_taxes );

		HTML::h3( _x( 'Term Tools', 'Header', 'geditorial-terms' ) );

		echo '<table class="form-table">';

		if ( count( $dead_taxes ) ) {

			echo '<tr><th scope="row">'._x( 'Orphaned Terms', 'Tools', 'geditorial-terms' ).'</th><td>';

				$this->do_settings_field( [
					'type'         => 'select',
					'field'        => 'dead_tax',
					'values'       => $dead_taxes,
					'default'      => ( isset( $post['dead_tax'] ) ? $post['dead_tax'] : 'post_tag' ),
					'option_group' => 'tools',
				] );

				$this->do_settings_field( [
					'type'         => 'select',
					'field'        => 'live_tax',
					'values'       => $live_taxes,
					'default'      => ( isset( $post['live_tax'] ) ? $post['live_tax'] : 'post_tag' ),
					'option_group' => 'tools',
				] );

				echo '&nbsp;&nbsp;';

				Settings::submitButton( 'orphaned_terms', _x( 'Convert', 'Button', 'geditorial-terms' ) );

				HTML::desc( _x( 'Converts orphaned terms into currently registered taxonomies.', 'Message', 'geditorial-terms' ) );

			echo '</td></tr>';

			$available = TRUE;
		}

		if ( count( $uncategorized ) ) {

			echo '<tr><th scope="row">'._x( 'Uncategorized Posts', 'Tools', 'geditorial-terms' ).'</th><td>';

			Settings::submitButton( 'clean_uncategorized', _x( 'Cleanup Uncategorized', 'Button', 'geditorial-terms' ) );

			HTML::desc( _x( 'Checks for posts in uncategorized category and removes the unnecessaries.', 'Message', 'geditorial-terms' ) );

			echo '<br />';

			HTML::tableList( [
				'_cb'   => 'ID',
				'ID'    => Tablelist::columnPostID(),
				'title' => Tablelist::columnPostTitle(),
				'terms' => Tablelist::columnPostTerms(),
			], $uncategorized );

			echo '</td></tr>';

			$available = TRUE;
		}

		if ( ! $available )
			HTML::desc( _x( 'Currently no tool available.', 'Message', 'geditorial-terms' ) );

		echo '</table>';
	}

	public function reports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'reports' ) ) {
			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'reports', $sub );

				if ( Tablelist::isAction( 'purge_unregistered', TRUE ) ) {

					// FIXME: only purges no-longer-attached taxes, not orphaned

					$count      = 0;
					$registered = Taxonomy::get();

					foreach ( $_POST['_cb'] as $post_id ) {

						if ( ! $post = get_post( $post_id ) )
							continue;

						$diff = array_diff_key( $registered, array_flip( get_object_taxonomies( $post ) ) );

						if ( empty( $diff ) )
							continue;

						foreach ( $diff as $taxonomy => $title )
							wp_set_object_terms( $post->ID, NULL, $taxonomy );

						$count++;
					}

					if ( $count )
						WordPress::redirectReferer( [
							'message' => 'cleaned',
							'count'   => $count,
						] );
				}

				WordPress::redirectReferer( 'nochange' );
			}

			$this->add_sub_screen_option( $sub );
		}
	}

	protected function render_reports_html( $uri, $sub )
	{
		list( $posts, $pagination ) = Tablelist::getPosts( [], [], 'any', $this->get_sub_limit_option( $sub ) );

		$pagination['actions']['purge_unregistered'] = _x( 'Purge Unregistered', 'Table Action', 'geditorial-terms' );

		$pagination['before'][] = Tablelist::filterPostTypes();
		$pagination['before'][] = Tablelist::filterAuthors();
		$pagination['before'][] = Tablelist::filterSearch();

		return HTML::tableList( [
			'_cb'   => 'ID',
			'ID'    => Tablelist::columnPostID(),
			'date'  => Tablelist::columnPostDate(),
			'type'  => Tablelist::columnPostType(),
			'title' => Tablelist::columnPostTitle(),
			'terms' => Tablelist::columnPostTerms(),
			'raw' => [
				'title'    => _x( 'Raw', 'Table Column', 'geditorial-terms' ),
				'callback' => function( $value, $row, $column, $index ){

					$query = new \WP_Term_Query( [ 'object_ids' => $row->ID, 'get' => 'all' ] );

					if ( empty( $query->terms ) )
						return Helper::htmlEmpty();

					$list = [];

					foreach ( $query->terms as $term )
						$list[] = '<span title="'.$term->taxonomy.'">'.$term->name.'</span>';

					return Helper::getJoined( $list );
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Posts with Terms', 'Header', 'geditorial-terms' ) ),
			'empty'      => $this->get_posttype_label( 'post', 'not_found' ),
			'pagination' => $pagination,
		] );
	}

	public function supported_field_edit_author( $meta, $field, $taxonomy, $term_id )
	{
		if ( 'author' !== $field )
			return $meta;

		// already set by form input
		if ( array_key_exists( 'term_author', $_REQUEST ) )
			return $meta;

		return empty( $meta ) ? get_current_user_id() : $meta;
	}

	public function taxonomy_export_term_meta( $metas, $taxonomy )
	{
		return array_merge( $metas, $this->list_supported( $taxonomy ) );
	}

	public function taxonomy_bulk_actions( $actions, $taxonomy )
	{
		$additions = [];
		$supported = $this->get_supported( $taxonomy );

		if ( in_array( 'image', $supported ) )
			$additions['sync_image_titles'] = _x( 'Sync Image Titles', 'Bulk Actions', 'geditorial-terms' );

		if ( in_array( 'tagline', $supported ) )
			$additions['move_tagline_to_desc'] = _x( 'Move Tagline to Description', 'Bulk Actions', 'geditorial-terms' );

		return array_merge( $actions, $additions );
	}

	public function taxonomy_bulk_callback( $callback, $action, $taxonomy )
	{
		$actions = [
			'sync_image_titles',
			'move_tagline_to_desc',
		];

		return in_array( $action, $actions )
			? [ $this, 'bulk_action_'.$action ]
			: $callback;
	}

	public function bulk_action_sync_image_titles( $term_ids, $taxonomy, $action )
	{
		if ( ! in_array( 'image', $this->get_supported( $taxonomy ) ) )
			return FALSE;

		$count   = 0;
		$metakey = $this->get_supported_metakey( 'image', $taxonomy );

		foreach ( $term_ids as $term_id ) {

			$term = get_term( (int) $term_id, $taxonomy );

			if ( self::isError( $term ) )
				continue;

			if ( ! $attachment_id = get_term_meta( $term->term_id, $metakey, TRUE ) )
				continue;

			if ( ! wp_attachment_is_image( $attachment_id ) )
				continue;

			$name = $this->filters( 'sanitize_name', trim( $term->name ), $term, $action );
			$desc = $this->filters( 'sanitize_description', trim( $term->description ), $term, $action );

			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $name );

			$updated = wp_update_post( [
				'ID'           => $attachment_id,
				'post_title'   => $name, // image title
				'post_excerpt' => $name, // image caption (excerpt) // TODO: MAYBE: use 'tagline' data
				'post_content' => $desc, // image description (content)
			], TRUE );

			if ( ! self::isError( $updated ) )
				$count++;
		}

		return TRUE;
	}

	public function bulk_action_move_tagline_to_desc( $term_ids, $taxonomy, $action )
	{
		if ( ! in_array( 'tagline', $this->get_supported( $taxonomy ) ) )
			return FALSE;

		$count   = 0;
		$metakey = $this->get_supported_metakey( 'tagline', $taxonomy );

		foreach ( $term_ids as $term_id ) {

			$term = get_term( (int) $term_id, $taxonomy );

			if ( self::isError( $term ) )
				continue;

			if ( ! $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
				continue;

			if ( ! empty( $taxonomy->description ) )
				$meta.= "\n\n".trim( $taxonomy->description );

			$updated = wp_update_term( $term->term_id, $term->taxonomy, [
				'description' => $this->filters( 'sanitize_description', trim( $meta ), $term, $action ),
			] );

			if ( self::isError( $updated ) )
				continue;

			if ( delete_term_meta( $term->term_id, $metakey ) )
				$count++;
		}

		return TRUE;
	}

	// already cap checked!
	public function dashboard_pointers( $items )
	{
		if ( ! $list = $this->get_uncategorized_count( TRUE ) )
			return $items;

		/* translators: %s: posts count */
		$noopd = _nx_noop( '%s Uncategorized Post', '%s Uncategorized Posts', 'Noop', 'geditorial-terms' );
		$link  = $this->cuc( 'tools' ) ? $this->get_module_url( 'tools' ) : add_query_arg( [ 'cat' => get_option( 'default_category' ) ], admin_url( 'edit.php' ) );

		$items[] = HTML::tag( 'a', [
			'href'  => $link,
			'title' => _x( 'You need to assign categories to some posts!', 'Title Attr', 'geditorial-terms' ),
			'class' => '-uncategorized-count',
		], sprintf( Helper::noopedCount( count( $list ), $noopd ), Number::format( count( $list ) ) ) );

		return $items;
	}

	private function get_uncategorized_count( $lite = FALSE )
	{
		$args = [
			'fields'         => $lite ? 'ids' : 'all',
			'posts_per_page' => -1,
			'tax_query'      => [ [
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => [ (int) get_option( 'default_category' ) ],
			] ],
		];

		$query = new \WP_Query;
		return $query->query( $args );
	}
}
