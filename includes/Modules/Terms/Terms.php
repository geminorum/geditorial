<?php namespace geminorum\gEditorial\Modules\Terms;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Listtable;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\WordPress;

class Terms extends gEditorial\Module
{

	// FIXME: quick edit not working on: `dead`/`born`
	// TODO: like `tableColumnPostMeta()` for term meta
	// TODO: `cost`, `price`, 'status`: public/private/protected, `capability`, `icon`, `subtitle`, `phonetic`, `time`, `pseudonym`
	// - for protected @SEE: https://make.wordpress.org/core/2016/10/28/fine-grained-capabilities-for-taxonomy-terms-in-4-7/

	protected $supported = [
		'parent',
		'order',
		'plural',
		// 'singular', // TODO
		'overwrite',
		'fullname',
		'tagline',
		'contact',
		'venue',
		'image',
		'author',
		'color',
		'role',
		'roles',
		'posttype',
		'posttypes',
		'arrow',
		'label',
		'code',
		'barcode',
		'latlng',
		'date',
		'datetime',
		'datestart',
		'dateend',
		'born',
		'dead',
		// 'distance', // TODO
		// 'duration', // TODO
		// 'area',     // TODO
		'days',
		'hours',
		'period',
		'amount',
		'unit',
		'max',
		'min',
		'viewable',
		'source',
		'embed',
		'url',
	];

	private $_roles = [];

	public static function module()
	{
		return [
			'name'     => 'terms',
			'title'    => _x( 'Terms', 'Modules: Terms', 'geditorial-admin' ),
			'desc'     => _x( 'Taxonomy & Term Tools', 'Modules: Terms', 'geditorial-admin' ),
			'icon'     => 'image-filter',
			'access'   => 'stable',
			'keywords' => [
				'termmeta',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_fields'   => $this->prep_fields_for_settings(),
			'_edittags' => [
				[
					'field'       => 'prevent_deletion',
					'title'       => _x( 'Prevent Deletion', 'Setting Title', 'geditorial-terms' ),
					'description' => _x( 'Exempts the terms with meta-data from bulk empty deletions.', 'Setting Description', 'geditorial-terms' ),
					'default'     => TRUE,
				],
			],
			'_general' => [
				[
					'field'       => 'apply_ordering',
					'title'       => _x( 'Apply Ordering', 'Setting Title', 'geditorial-terms' ),
					'description' => _x( 'Changes internal Wordpress core defaults to use custom ordering.', 'Setting Description', 'geditorial-terms' ),
				],
				[
					'field'       => 'auto_current_author',
					'title'       => _x( 'Current Author', 'Setting Title', 'geditorial-terms' ),
					'description' => _x( 'Automatically fills current user as term author if not set for supported taxonomies.', 'Setting Description', 'geditorial-terms' ),
				],
				[
					'field'       => 'auto_term_overwrite',
					'title'       => _x( 'Term Overwrite', 'Setting Title', 'geditorial-terms' ),
					'description' => _x( 'Automatically overwrites term name with custom field if set for supported taxonomies.', 'Setting Description', 'geditorial-terms' ),
				],
				[
					'field'       => 'source_link_title',
					'type'        => 'text',
					'title'       => _x( 'Source Title', 'Setting Title', 'geditorial-terms' ),
					'placeholder' => _x( 'Source', 'Setting Default', 'geditorial-terms' ),
					'description' => sprintf(
						/* translators: `%s`: zero placeholder */
						_x( 'Defines the title string on source link for supported taxonomies. Leave blank for default or %s to disable.', 'Setting Description', 'geditorial-terms' ),
						Core\HTML::code( '0' )
					),
				],
				'calendar_type',
				// 'calendar_list',
			],
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
				'parent'    => _x( 'Parent', 'Titles', 'geditorial-terms' ),
				'order'     => _x( 'Order', 'Titles', 'geditorial-terms' ),
				'plural'    => _x( 'Plural', 'Titles', 'geditorial-terms' ),
				'overwrite' => _x( 'Overwrite', 'Titles', 'geditorial-terms' ),
				'fullname'  => _x( 'Fullname', 'Titles', 'geditorial-terms' ),
				'tagline'   => _x( 'Tagline', 'Titles', 'geditorial-terms' ),
				'contact'   => _x( 'Contact', 'Titles', 'geditorial-terms' ),
				'venue'     => _x( 'Venue', 'Titles', 'geditorial-terms' ),
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
				'latlng'    => _x( 'Lat/Lng', 'Titles', 'geditorial-terms' ),
				'date'      => _x( 'Date', 'Titles', 'geditorial-terms' ),
				'datetime'  => _x( 'Date-Time', 'Titles', 'geditorial-terms' ),
				'datestart' => _x( 'Date-Start', 'Titles', 'geditorial-terms' ),
				'dateend'   => _x( 'Date-End', 'Titles', 'geditorial-terms' ),
				'days'      => _x( 'Days', 'Titles', 'geditorial-terms' ),
				'born'      => _x( 'Born', 'Titles', 'geditorial-terms' ),
				'dead'      => _x( 'Dead', 'Titles', 'geditorial-terms' ),
				'hours'     => _x( 'Hours', 'Titles', 'geditorial-terms' ),
				'period'    => _x( 'Period', 'Titles', 'geditorial-terms' ),
				'amount'    => _x( 'Amount', 'Titles', 'geditorial-terms' ),
				'unit'      => _x( 'Unit', 'Titles', 'geditorial-terms' ),
				'min'       => _x( 'Minimum', 'Titles', 'geditorial-terms' ),
				'max'       => _x( 'Maximum', 'Titles', 'geditorial-terms' ),
				'viewable'  => _x( 'Viewable', 'Titles', 'geditorial-terms' ),
				'source'    => _x( 'Source', 'Titles', 'geditorial-terms' ),
				'embed'     => _x( 'Embed', 'Titles', 'geditorial-terms' ),
				'url'       => _x( 'URL', 'Titles', 'geditorial-terms' ),
			],
			'descriptions' => [
				'parent'    => _x( 'Terms can have parents from another taxonomies.', 'Descriptions', 'geditorial-terms' ),
				'order'     => _x( 'Terms are usually ordered alphabetically, but you can choose your own order by numbers.', 'Descriptions', 'geditorial-terms' ),
				'plural'    => _x( 'Defines the plural form of the term.', 'Descriptions', 'geditorial-terms' ),
				'overwrite' => _x( 'Replaces the term name on front-page display.', 'Descriptions', 'geditorial-terms' ),
				'fullname'  => _x( 'Defines the full-name form of the term.', 'Descriptions', 'geditorial-terms' ),
				'tagline'   => _x( 'Gives more information about the term in a short phrase.', 'Descriptions', 'geditorial-terms' ),
				'contact'   => _x( 'Adds a way to contact someone about the term, by url, email or phone.', 'Descriptions', 'geditorial-terms' ),
				'venue'     => _x( 'Defines a string as venue for the term.', 'Descriptions', 'geditorial-terms' ),
				'image'     => _x( 'Assigns a custom image to visually separate terms from each other.', 'Descriptions', 'geditorial-terms' ),
				'author'    => _x( 'Sets a user as term author to help identify who created or owns each term.', 'Descriptions', 'geditorial-terms' ),
				'color'     => _x( 'Assigns a custom color to visually separate terms from each other.', 'Descriptions', 'geditorial-terms' ),
				'role'      => _x( 'Terms can have unique role visibility to help separate them for user roles.', 'Descriptions', 'geditorial-terms' ),
				'roles'     => _x( 'Terms can have unique roles visibility to help separate them for user roles.', 'Descriptions', 'geditorial-terms' ),
				'posttype'  => _x( 'Terms can have unique posttype visibility to help separate them on editing.', 'Descriptions', 'geditorial-terms' ),
				'posttypes' => _x( 'Terms can have unique posttypes visibility to help separate them on editing.', 'Descriptions', 'geditorial-terms' ),
				'arrow'     => _x( 'Terms can have direction arrow to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'label'     => _x( 'Terms can have text label to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'code'      => _x( 'Terms can have text code to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'barcode'   => _x( 'Terms can have barcode to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'latlng'    => _x( 'Terms can have Latitude and Longitude to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'date'      => _x( 'Terms can have date to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'datetime'  => _x( 'Terms can have date-time to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'datestart' => _x( 'Terms can have date-start to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'dateend'   => _x( 'Terms can have date-end to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'born'      => _x( 'Terms can have born-date to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'dead'      => _x( 'Terms can have dead-date to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'days'      => _x( 'Terms can have days number to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'hours'     => _x( 'Terms can have hours number to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'period'    => _x( 'The length of time about the term.', 'Descriptions', 'geditorial-terms' ),
				'amount'    => _x( 'The quantity number about the term.', 'Descriptions', 'geditorial-terms' ),
				'unit'      => _x( 'Terms can have unit number to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'min'       => _x( 'Defines the minimum threshold for the term.', 'Descriptions', 'geditorial-terms' ),
				'max'       => _x( 'Defines the maximum threshold for the term.', 'Descriptions', 'geditorial-terms' ),
				'viewable'  => _x( 'Determines whether the term is publicly viewable.', 'Descriptions', 'geditorial-terms' ),
				'source'    => _x( 'Defines a source URL for the term.', 'Descriptions', 'geditorial-terms' ),
				'embed'     => _x( 'Defines a embed-able URL for the term.', 'Descriptions', 'geditorial-terms' ),
				'url'       => _x( 'Defines a custom URL for the term.', 'Descriptions', 'geditorial-terms' ),
			],
			'misc' => [
				// NOTE: Only the difference from titles
				// - filters are able to customize by `{$field}_column_title` key
				'order_column_title'    => _x( 'Ordering', 'Column Title: Order', 'geditorial-terms' ),
				'arrow_column_title'    => _x( 'Arrow Directions', 'Column Title: Arrow', 'geditorial-terms' ),
				'viewable_column_title' => _x( 'Visibility', 'Column Title: Date-End', 'geditorial-terms' ),

				'visibility' => [
					'0' => _x( 'Undefined', 'Visibility', 'geditorial-terms' ),
					'1' => _x( 'Nonviewable', 'Visibility', 'geditorial-terms' ),
					'2' => _x( 'Viewable', 'Visibility', 'geditorial-terms' ),
				],
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

	protected function taxonomies_excluded( $extra = [] )
	{
		return $this->filters( 'taxonomies_excluded', Settings::taxonomiesExcluded( [
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
		] + $extra ) );
	}

	protected function get_taxonomies_support( $field )
	{
		$supported = WordPress\Taxonomy::get();
		$excluded  = $this->taxonomies_excluded();

		switch ( $field ) {
			case 'role'     : $excluded[] = 'audit_attribute'; break;
			case 'plural'   : $excluded[] = 'post_tag'; break;
			case 'overwrite': $excluded[] = 'post_tag'; break;
			case 'fullname' : $excluded[] = 'post_tag'; break;
			case 'tagline'  : $excluded[] = 'post_tag'; break;
			case 'arrow'    : return Core\Arraay::keepByKeys( $supported, [ 'warehouse_placement' ] ); break;  // override!
		}

		return array_diff_key( $supported, array_flip( $excluded ) );
	}

	protected function prep_fields_for_settings( $fields = NULL )
	{
		if ( is_null( $fields ) )
			$fields = $this->_get_supported_raw();

		$list = [];

		foreach ( $fields as $field ) {

			$name = $this->get_supported_field_title( $field, FALSE );
			$desc = $this->get_supported_field_desc( $field, FALSE );

			$title = sprintf(
				/* translators: `%1$s`: field name, `%2$s`: field key */
				_x( 'Term %1$s %2$s', 'Setting Title', 'geditorial-terms' ),
				$name,
				Core\HTML::code( $field, '-field-key' )
			);

			$list[] = [
				'field'       => 'term_'.$field,
				'type'        => 'taxonomies',
				'title'       => $title,
				/* translators: `%s`: field name */
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
		$this->action( 'delete_attachment', 2, 12 );

		if ( $this->get_setting( 'apply_ordering' ) ) {

			$this->action( 'pre_get_terms', 1, 10, 'ordering' );
			$this->filter( 'terms_clauses', 3, 99, 'ordering' );
			$this->filter( 'get_terms_defaults', 2, 10, 'ordering' );

		} else {

			// fallback using WooCommerce
			$this->filter( 'woocommerce_sortable_taxonomies' );
		}

		// fills current user as term author if not set for supported taxonomy
		if ( ! empty( $this->get_setting( 'term_author', [] ) )
			&& $this->get_setting( 'auto_current_author' ) )
			$this->filter_self( 'supported_field_edit', 4, 8, 'author' );

		if ( $this->get_setting( 'auto_term_overwrite' ) )
			$this->_hook_overwrite_titles( $this->get_setting( 'term_overwrite', [] ) );

		$this->filter( 'searchselect_result_image_for_term', 3, 12, FALSE, $this->base );
		$this->filter( 'term_intro_title_suffix', 5, 8, FALSE, $this->base );
		$this->action( 'term_intro_description_after', 5, 5, FALSE, $this->base );

		if ( ! is_admin() )
			return;

		$this->filter( 'display_media_states', 2, 12 );
		$this->filter_module( 'datacodes', 'print_template_data', 4, 8 );
	}

	public function setup_ajax()
	{
		if ( $taxonomy = $this->is_inline_save_taxonomy() )
			$this->_edit_tags_screen( $taxonomy );
	}

	public function current_screen( $screen )
	{
		$enqueue = FALSE;

		if ( 'edit-tags' == $screen->base ) {

			$fields = $this->get_supported( $screen->taxonomy );

			foreach ( $fields as $field ) {

				if ( $this->filters( 'disable_field_edit', FALSE, $field, $screen->taxonomy ) )
					continue;

				add_action( $screen->taxonomy.'_add_form_fields', function ( $taxonomy ) use ( $field ) {
					$this->add_form_field( $field, $taxonomy );
				}, 8, 1 );

				if ( ! in_array( $field, [ 'roles', 'posttypes' ] ) ) {

					add_action( 'quick_edit_custom_box', function ( $column, $screen, $taxonomy ) use ( $field ) {
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
					'customs' => array_values( array_diff( $fields, $this->_get_supported_raw( FALSE ) ) ),
					'strings' => $this->strings['js'],
				], NULL, [ 'jquery', 'media-upload' ] );
			}

			if ( count( $fields ) ) {
				$this->_edit_tags_screen( $screen->taxonomy );
				add_filter( 'manage_edit-'.$screen->taxonomy.'_sortable_columns', [ $this, 'sortable_columns' ] );

				// TODO: add empty all term meta card for each field
				// $this->action( 'taxonomy_tab_extra_content', 2, 9, FALSE, 'gnetwork' );
				// $this->action( 'taxonomy_handle_tab_content_actions', 1, 8, FALSE, 'gnetwork' );

				$this->filter( 'taxonomy_export_term_meta', 2, 8, FALSE, 'gnetwork' );
				$this->filter( 'taxonomy_bulk_actions', 2, 14, FALSE, 'gnetwork' );
				$this->filter( 'taxonomy_bulk_callback', 3, 14, FALSE, 'gnetwork' );

				if ( $this->get_setting( 'prevent_deletion', TRUE ) ) {
					$this->filter( 'taxonomy_delete_empty_term', 3, 99, FALSE, 'gnetwork' );
					$this->filter( 'taxonomy_delete_term', 4, 99, FALSE, 'gnetwork' );
				}
			}

		} else if ( 'term' == $screen->base ) {

			$fields = $this->get_supported( $screen->taxonomy );

			foreach ( $fields as $field ) {

				$disabled = $this->filters( 'disable_field_edit', FALSE, $field, $screen->taxonomy );

				add_action( $screen->taxonomy.'_edit_form_fields', function ( $term, $taxonomy ) use ( $field, $disabled ) {
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

	private function _get_supported_raw( $filtered = TRUE )
	{
		return $filtered ? $this->filters( 'supported_fields_raw', $this->supported ) : $this->supported;
	}

	// FALSE for all
	private function get_supported( $taxonomy = FALSE )
	{
		$list = [];

		foreach ( $this->_get_supported_raw() as $field )
			if ( FALSE === $taxonomy || $this->in_setting( $taxonomy, 'term_'.$field ) )
				$list[] = $field;

		return $this->filters( 'supported_fields', $list, $taxonomy );
	}

	// FALSE for all
	private function list_supported( $taxonomy = FALSE )
	{
		$list = [];

		foreach ( $this->_get_supported_raw() as $field )
			if ( FALSE === $taxonomy || $this->in_setting( $taxonomy, 'term_'.$field ) )
				$list[$field] = $this->strings['titles'][$field];

		return $this->filters( 'list_supported_fields', $list, $taxonomy );
	}

	// TODO: handle `$taxonomy` if is an array
	// NOTE: by default the meta-key is the same as the field
	private function get_supported_metakey( $field, $taxonomy = FALSE )
	{
		return $this->filters( 'supported_field_metakey', $field, $field, $taxonomy );
	}

	// NOTE: by default the meta-type is the same as the field
	private function get_supported_field_metatype( $field, $taxonomy )
	{
		return $this->filters( 'supported_field_metatype', $field, $field, $taxonomy );
	}

	private function get_supported_taxonomies( $field )
	{
		return $this->filters( 'supported_field_taxonomies', $this->get_setting( 'term_'.$field, [] ), $field );
	}

	private function get_supported_field_title( $field, $taxonomy, $term = FALSE )
	{
		return $this->filters( 'field_'.$field.'_title', $this->get_string( $field, $taxonomy, 'titles', $field ), $taxonomy, $field, $term );
	}

	private function get_supported_field_desc( $field, $taxonomy, $term = FALSE )
	{
		return $this->filters( 'field_'.$field.'_desc', $this->get_string( $field, $taxonomy, 'descriptions', '' ), $taxonomy, $field, $term );
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
			case 'latlng':
			case 'arrow':
			case 'viewable':

				$position = [ 'name', 'before' ];
				break;

			case 'dateend':
				$position = [ $this->classs( 'datestart' ), 'after' ];
				break;

			case 'dead':
				$position = [ $this->classs( 'born' ), 'after' ];
				break;

			default:
				$position = [ 'name', 'after' ];
		}

		return $this->filters( 'supported_field_position', $position, $field, $taxonomy );
	}

	// TODO: `meta_rendered` just like meta module
	// @REF: https://make.wordpress.org/core/2018/07/27/registering-metadata-in-4-9-8/
	// @REF: https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/
	protected function register_meta_fields()
	{
		foreach ( $this->get_supported() as $field ) {

			if ( ! $taxonomies = $this->get_supported_taxonomies( $field ) )
				continue;

			$prepare = 'register_prepare_callback_'.$field;

			// 'string', 'boolean', 'integer', 'number', 'array', and 'object'
			if ( in_array( $field, [ 'parent', 'order', 'author', 'image', 'days', 'hours', 'amount', 'unit', 'min', 'max', 'viewable' ] ) )
				$defaults = [ 'type'=> 'integer', 'single' => TRUE, 'default' => 0 ];

			else if ( in_array( $field, [ 'roles', 'posttypes' ] ) )
				$defaults = [ 'type'=> 'array', 'single' => FALSE, 'default' => [] ];

			// NOTE: WordPress not yet support for `date` type
			else if ( in_array( $field, [ 'date', 'datetime', 'datestart', 'dateend', 'born', 'dead' ] ) )
				$defaults = [ 'type'=> 'string', 'single' => TRUE, 'default' => '' ];

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

				$filtered = $this->filters( 'register_field_args', $args, $field, $taxonomy );

				if ( FALSE !== $filtered )
					register_meta( 'term', $field, $filtered );
			}

			// register general field for prepared meta data
			// mainly for display purposes
			if ( in_array( $field, [ 'image' ] ) )
				register_rest_field( $taxonomies, $field, [
					'get_callback'  => [ $this, 'attribute_get_callback' ],
					// 'auth_callback' => [ $this, 'attribute_auth_callback' ], // FIXME
				] );
		}
	}

	public function attribute_get_callback( $term, $attr, $request, $object_type )
	{
		switch ( $attr ) {

			case 'image':
				$metakey = $this->get_supported_metakey( 'image', $object_type );
				return WordPress\Media::prepAttachmentData( get_term_meta( $term['id'], $metakey, TRUE ) );

				break;
		}
	}

	public function attribute_auth_callback( $allowed, $meta_key, $object_id, $user_id, $cap, $caps )
	{
		return $allowed;
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

		$supported = $this->get_supported( $taxonomy );
		$icons     = [
			'order',
			'contact',
			'venue',
			'image',
			'author',
			'color',
			'role',
			'roles',
			'posttype',
			'posttypes',
			'arrow',
			'label',
			'code',
			'barcode',
			'latlng',
			'date',
			'datetime',
			'datestart',
			'dateend',
			'born',
			'dead',
			'days',
			'hours',
			'period',
			'amount',
			'unit',
			'min',
			'max',
			'viewable',
			'source',
			'embed',
			'url',
		];

		foreach ( $supported as $field ) {

			if ( FALSE === ( $position = $this->get_supported_position( $field, $taxonomy ) ) )
				continue;

			$fallback = $this->get_supported_field_title( $field, $taxonomy );
			$title    = in_array( $field, $icons, TRUE )
				? $this->get_column_title_icon( $field, $taxonomy, $fallback )
				: $this->get_column_title( $field, $taxonomy, $fallback );

			$columns = Core\Arraay::insert( $columns, [
				$this->classs( $field ) => $title,
			], $position[0], $position[1] );
		}

		// icon for posts column
		if ( array_key_exists( 'posts', $columns ) )
			$columns['posts'] = $this->get_column_title_icon( 'posts', $taxonomy );

		return $this->filters( 'manage_columns', $columns, $taxonomy, $supported );
	}

	public function sortable_columns( $columns )
	{
		if ( ! $taxonomy = self::req( 'taxonomy' ) )
			return $columns;

		$supported = $this->get_supported( $taxonomy );
		$sortables = [
			'plural',
			'overwrite',
			'fullname',
			'tagline',
			'contact',
			'venue',
			'image',
			'roles',
			'posttypes',
			'arrow',
			'label',
			'code',
			'barcode',
			'latlng',
			'date',
			'datetime',
			'datestart',
			'dateend',
			'born',
			'dead',
			'days',
			'hours',
			'period',
			'amount',
			'unit',
			'min',
			'max',
			'viewable',
			'source',
			'embed',
			'url',
		];

		foreach ( $supported as $field )
			if ( ! in_array( $field, $sortables, TRUE ) )
				$columns[$this->classs( $field )] = 'meta_'.$field;

		return $this->filters( 'sortable_columns', $columns, $taxonomy, $supported );
	}

	public function custom_column( $string, $column_name, $term_id )
	{
		if ( ! $taxonomy = self::req( 'taxonomy' ) )
			return $string;

		$term      = get_term_by( 'id', $term_id, $taxonomy );
		$supported = $this->get_supported( $taxonomy );

		foreach ( $supported as $field ) {

			if ( $this->classs( $field ) != $column_name )
				continue;

			$this->display_form_field( $field, $taxonomy, $term, TRUE );
		}

		// NOTE: here for custom column with multiple fields support
		$this->actions( 'custom_column', $column_name, $taxonomy, $supported, $term );
	}

	// TODO: use readonly inputs on non-columns
	private function display_form_field( $field, $taxonomy, $term, $column = TRUE )
	{
		$html     = $meta = '';
		$metakey  = $this->get_supported_metakey( $field, $taxonomy );
		$metatype = $this->get_supported_field_metatype( $field, $taxonomy );

		switch ( $metatype ) {

			case 'order':

				$meta = get_term_meta( $term->term_id, $metakey, TRUE );

				if ( $meta || '0' === $meta ) {

					$html = Listtable::columnOrder( $meta );

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'days':
			case 'hours':
			case 'amount':
			case 'unit':
			case 'min':
			case 'max':

				$meta = get_term_meta( $term->term_id, $metakey, TRUE );

				if ( $meta || '0' === $meta ) {

					$html = $meta ? Core\Number::format( $meta ) : Helper::htmlEmpty( '-'.$metakey );

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'viewable':

				$meta = get_term_meta( $term->term_id, $metakey, TRUE );

				if ( $meta ) {

					$visibility = $this->get_string( 'visibility', FALSE, 'misc', [] );

					if ( '2' == $meta )
						$icon = Core\HTML::getDashicon( 'visibility', $visibility[$meta], '-icon-danger' );
					else
						$icon = Core\HTML::getDashicon( 'hidden', $visibility[$meta], '-icon-warning' );

					$html = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $meta ).'">'.$icon.'</span>';

				} else {

					$html = $this->field_empty( $field, '0', $column );
				}

				break;

			case 'arrow':

				$meta = get_term_meta( $term->term_id, $metakey, TRUE );

				if ( $meta || 'undefined' === $meta ) {

					$dirs = $this->get_string( 'arrow_directions', FALSE, 'misc', [] );

					if ( array_key_exists( $meta, $dirs ) )
						$icon = Core\HTML::getDashicon( sprintf( 'arrow-%s-alt', $meta ), $dirs[$meta], 'icon-arrow' );
					else
						$icon = Core\HTML::getDashicon( 'warning', $meta, '-icon-warning' );

					$html = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $meta ).'">'.$icon.'</span>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'barcode':

				$meta = get_term_meta( $term->term_id, $metakey, TRUE );

				if ( $meta ) {

					$icon = Core\HTML::getDashicon( 'tagcloud', $meta, 'icon-barcode' );
					$html = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $meta ).'">'.$icon.'</span>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'latlng':

				$meta = get_term_meta( $term->term_id, $metakey, TRUE );

				if ( $meta ) {

					$html = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $meta ).'">';
					$html.= Core\HTML::link( Core\HTML::getDashicon( 'admin-site-alt3', $meta, 'icon-latlng' ), Info::lookupURLforLatLng( $meta ), TRUE ).'</span>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'code':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$html = '<code class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $meta ).'">'.$meta.'</code>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'label':
			case 'plural':
			case 'overwrite':
			case 'fullname':
			case 'tagline':
			case 'period':
			case 'venue':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$html = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $meta ).'">'
						.WordPress\Strings::prepTitle( $meta ).'</span>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'contact':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$html = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $meta )
						.'" title="'.Core\HTML::wrapLTR( Core\HTML::escape( $meta ) ).'">'
						.Helper::prepContact( $meta, NULL, '', TRUE ).'</span>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'image':

				$size = NULL; // maybe filter fo this module?!
				$html = $this->filters( 'column_image', WordPress\Taxonomy::htmlFeaturedImage( $term->term_id, $size, TRUE, $metakey ), $term->term_id, $size );

				break;

			case 'parent':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$parent = get_term( (int) $meta ); // allows for empty taxonomy
					$html   = '<span class="-field field-'.$field.'" data-'.$field.'="'.$meta.'">'.WordPress\Term::title( $parent ).'</span>';

				} else {

					$html = $this->field_empty( $field, '0', $column );
				}

				break;

			case 'author':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$user = get_user_by( 'id', $meta );
					$html = '<span class="-field field-'.$field.'" data-'.$field.'="'.$meta.'">'.$user->display_name.'</span>';

				} else {

					$html = $this->field_empty( $field, '0', $column );
				}

				break;

			case 'color':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
					$html = '<i class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $meta )
						.'" style="background-color:'.Core\HTML::escape( $meta )
						.'" title="'.Core\HTML::wrapLTR( Core\HTML::escape( $meta ) ).'"></i>';

				break;

			case 'role':

				if ( empty( $this->_roles ) )
					$this->_roles = $this->get_settings_default_roles();

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
					$html = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $meta ).'">'
						.( empty( $this->_roles[$meta] )
							? Core\HTML::escape( $meta )
							: $this->_roles[$meta] )
						.'</span>';

				else
					$html = $this->field_empty( 'role', '0', $column );

				break;

			case 'roles':

				if ( empty( $this->_roles ) )
					$this->_roles = $this->get_settings_default_roles();

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$list = [];

					foreach ( (array) $meta as $role )
						$list[] = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $role ).'">'
							.( empty( $this->_roles[$role] )
								? Core\HTML::escape( $role )
								: $this->_roles[$role] )
							.'</span>';

					$html = WordPress\Strings::getJoined( $list );

				} else {

					$html = $this->field_empty( $field, '0', $column );
				}

				break;

			case 'posttype':

				if ( empty( $this->cache['posttypes'] ) )
					$this->cache['posttypes'] = WordPress\PostType::get( 2, [ 'show_ui' => TRUE ] );

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
					$html = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $meta ).'">'
						.( empty( $this->cache['posttypes'][$meta] )
							? Core\HTML::escape( $meta )
							: $this->cache['posttypes'][$meta] )
						.'</span>';

				else
					$html = $this->field_empty( $field, '0', $column );

				break;

			case 'posttypes':

				if ( empty( $this->cache['posttypes'] ) )
					$this->cache['posttypes'] = WordPress\PostType::get( 2, [ 'show_ui' => TRUE ] );

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$list = [];

					foreach ( (array) $meta as $posttype )
						$list[] = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $posttype ).'">'
							.( empty( $this->cache['posttypes'][$posttype] )
								? Core\HTML::escape( $posttype )
								: $this->cache['posttypes'][$posttype] )
							.'</span>';

					$html = WordPress\Strings::getJoined( $list );

				} else {

					$html = $this->field_empty( $field, '0', $column );
				}

				break;

			case 'date':
			case 'born':
			case 'dead':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$date  = Datetime::prepForDisplay( trim( $meta ), 'Y/m/d' );
					$input = Datetime::prepForInput( trim( $meta ), 'Y/m/d', 'gregorian' );
					$html  = '<span class="-field field-'.$field.'" data-'.$field.'="'.$input.'">'.$date.'</span>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'datetime':
			case 'datestart':
			case 'dateend':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$date  = Datetime::prepForDisplay( trim( $meta ), 'Y/m/d H:i' );
					$input = Datetime::prepForInput( trim( $meta ), 'Y/m/d H:i', 'gregorian' );
					$html  = '<span class="-field field-'.$field.'" data-'.$field.'="'.$input.'">'.$date.'</span>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'source':
			case 'embed':
			case 'url':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$icons = [
						'source' => 'external',
						'embed'  => 'embed-generic',
						'url'    => 'admin-links',
					];

					$html = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $meta ).'">';
						$html.= Core\HTML::link(
							Core\HTML::getDashicon(
								$icons[$field],
								Core\URL::prepTitle( $meta ),
								'-icon-'.$field
							),
							$meta,
							TRUE
						);
					$html.= '</span>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			default:

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
					$html = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $meta ).'">'
						.WordPress\Strings::prepTitle( $meta ).'</span>';

				else
					$html = $this->field_empty( $field, '', $column );

		}

		echo $this->filters( 'supported_field_column', $html, $field, $taxonomy, $term, $meta, $metakey, $metatype );
	}

	private function field_empty( $field, $value = '0', $column = TRUE )
	{
		if ( $column )
			return '<span class="column-'.$field.'-empty -empty">&mdash;</span>'
				.'<span class="-field field-'.$field.'" data-'.$field.'="'.$value.'"></span>';

		return gEditorial()->na();
	}

	public function edit_term( $term_id, $tt_id, $taxonomy )
	{
		$calendar = $this->default_calendar();

		foreach ( $this->get_supported( $taxonomy ) as $field ) {

			if ( ! array_key_exists( 'term-'.$field, $_REQUEST ) )
				continue;

			$metakey = $this->get_supported_metakey( $field, $taxonomy );
			$meta    = empty( $_REQUEST['term-'.$field] ) ? FALSE : $_REQUEST['term-'.$field];
			$meta    = $this->filters( 'supported_field_edit', $meta, $field, $taxonomy, $term_id, $metakey );

			if ( $meta ) {

				$meta = is_array( $meta ) ? array_filter( $meta ) : trim( Core\HTML::escape( $meta ) );

				if ( 'image' == $field ) {

					update_post_meta( (int) $meta, '_wp_attachment_is_term_image', $taxonomy );
					do_action( 'clean_term_attachment_cache', (int) $meta, $taxonomy, $term_id );

				} else if ( in_array( $field, [ 'days', 'hours', 'amount', 'unit', 'min', 'max' ] ) ) {

					$meta = Core\Text::trim( Core\Number::translate( $meta ) );

				} else if ( in_array( $field, [ 'viewable' ] ) ) {

					$meta = Core\Text::trim( Core\Number::translate( $meta ) );

				} else if ( in_array( $field, [ 'date', 'born', 'dead' ] ) ) {

					$meta = Core\Text::trim( Core\Number::translate( $meta ) );

					// accepts year only
					if ( strlen( $meta ) > 4 )
						$meta = Datetime::makeMySQLFromInput( $meta, 'Y-m-d', $calendar, NULL, $meta );

				} else if ( in_array( $field, [ 'datetime', 'datestart', 'dateend' ] ) ) {

					$meta = Core\Text::trim( Core\Number::translate( $meta ) );
					$meta = Datetime::makeMySQLFromInput( $meta, NULL, $calendar, NULL, $meta );

				} else if ( in_array( $field, [ 'source', 'embed', 'url' ] ) ) {

					$meta = Core\URL::sanitize( $meta );
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

	// delete all attachment images for any term.
	public function delete_attachment( $post_id, $post )
	{
		delete_metadata( 'term', NULL, $this->get_supported_metakey( 'image' ), $post_id, TRUE );
	}

	private function quick_form_field( $field, $taxonomy )
	{
		echo '<fieldset><div class="inline-edit-col"><label><span class="title">';

			echo Core\HTML::escape( $this->get_supported_field_title( $field, $taxonomy ) );

		echo '</span><span class="input-text-wrap">';

			$this->quickedit_field( $field, $taxonomy );

		echo '</span></label></div></fieldset>';
	}

	private function add_form_field( $field, $taxonomy, $term = FALSE )
	{
		echo '<div class="form-field term-'.$field.'-wrap">';
		echo '<label for="term-'.$field.'">';

			echo Core\HTML::escape( $this->get_supported_field_title( $field, $taxonomy, $term ) );

		echo '</label>';

			$this->form_field( $field, $taxonomy, $term );
			Core\HTML::desc( $this->get_supported_field_desc( $field, $taxonomy, $term ) );

		echo '</div>';
	}

	private function edit_form_field( $field, $taxonomy, $term, $disabled = FALSE )
	{
		echo '<tr class="form-field term-'.$field.'-wrap"><th scope="row" valign="top">';
		echo '<label for="term-'.$field.'">';

			echo Core\HTML::escape( $this->get_supported_field_title( $field, $taxonomy, $term ) );

		echo '</label></th><td>';

			if ( $disabled )
				$this->display_form_field( $field, $taxonomy, $term, FALSE );
			else
				$this->form_field( $field, $taxonomy, $term );

			Core\HTML::desc( $this->get_supported_field_desc( $field, $taxonomy, $term ) );

		echo '</td></tr>';
	}

	/**
	 * Renders form html mark-up for the given field.
	 *
	 * @param string $field
	 * @param string $taxonomy
	 * @param mixed $term
	 * @return void
	 */
	private function form_field( $field, $taxonomy, $term = FALSE )
	{
		$html     = '';
		$term_id  = empty( $term->term_id ) ? 0 : $term->term_id;
		$metakey  = $this->get_supported_metakey( $field, $taxonomy );
		$metatype = $this->get_supported_field_metatype( $field, $taxonomy );
		$meta     = get_term_meta( $term_id, $metakey, TRUE );

		switch ( $metatype ) {

			case 'image':

				$html.= '<div>'.Core\HTML::tag( 'img', [
					'id'       => $this->classs( $field, 'img' ),
					'src'      => empty( $meta ) ? '' : wp_get_attachment_image_url( $meta, 'thumbnail' ),
					'loading'  => 'lazy',
					'decoding' => 'async',
					'style'    => empty( $meta ) ? 'display:none' : FALSE,
				] ).'</div>';

				$html.= Core\HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => $meta,
					'style' => 'display:none',
				] );

				$html.= Core\HTML::tag( 'a', [
					'class' => [ 'button', 'button-small', 'button-secondary', '-modal' ],
				], _x( 'Choose', 'Button', 'geditorial-terms' ) );

				$html.= '&nbsp;'.Core\HTML::tag( 'a', [
					'class' => [ 'button', 'button-small', 'button-link-delete', '-remove' ],
					'style' => empty( $meta ) ? 'display:none' : FALSE,
				], _x( 'Remove', 'Button', 'geditorial-terms' ) );

			break;

			case 'parent': //  must input the term_id, due to diffrent parent taxonomy support!
			case 'days':
			case 'hours':
			case 'amount':
			case 'unit':
			case 'min':
			case 'max':
			case 'order':

				$html.= Core\HTML::tag( 'input', [
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
				if ( FALSE === $term )
					$meta = get_current_user_id();

				$html.= Listtable::restrictByAuthor( empty( $meta ) ? '0' : $meta, 'term-'.$field, [
					'echo'            => FALSE,
					'show_option_all' => Settings::showOptionNone(),
				] );

			break;
			case 'role':

				$html.= Core\HTML::dropdown( $this->get_settings_default_roles(), [
					'id'         => $this->classs( $field, 'id' ),
					'name'       => 'term-'.$field,
					'selected'   => empty( $meta ) ? '0' : $meta,
					'none_title' => Settings::showOptionNone(),
				] );

			break;
			case 'roles':

				$html.= Settings::tabPanelOpen();

				foreach ( $this->get_settings_default_roles() as $role => $name ) {

					$checkbox = Core\HTML::tag( 'input', [
						'type'    => 'checkbox',
						'name'    => 'term-'.$field.'[]',
						'id'      => $this->classs( $field, 'id', $role ),
						'value'   => $role,
						'checked' => empty( $meta ) ? FALSE : in_array( $role, (array) $meta ),
					] );

					$html.= '<li>'.Core\HTML::tag( 'label', [
						'for' => $this->classs( $field, 'id', $role ),
					], $checkbox.'&nbsp;'.Core\HTML::escape( $name ) ).'</li>';
				}

				$html.= '</ul></div>';

			break;
			case 'posttype':

				$html.= Core\HTML::dropdown( WordPress\PostType::get( 2, [ 'show_ui' => TRUE ] ), [
					'id'         => $this->classs( $field, 'id' ),
					'name'       => 'term-'.$field,
					'selected'   => empty( $meta ) ? '0' : $meta,
					'none_title' => Settings::showOptionNone(),
				] );

			break;
			case 'posttypes':

				$html.= Settings::tabPanelOpen();

				foreach ( WordPress\PostType::get( 2 ) as $posttype => $name ) {

					$checkbox = Core\HTML::tag( 'input', [
						'type'    => 'checkbox',
						'name'    => 'term-'.$field.'[]',
						'id'      => $this->classs( $field, 'id', $posttype ),
						'value'   => $posttype,
						'checked' => empty( $meta ) ? FALSE : in_array( $posttype, (array) $meta ),
					] );

					$html.= '<li>'.Core\HTML::tag( 'label', [
						'for' => $this->classs( $field, 'id', $posttype ),
					], $checkbox.'&nbsp;'.Core\HTML::escape( $name ) ).'</li>';
				}

				$html.= '</ul></div>';

			break;
			case 'color':

				$html.= Core\HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => empty( $meta ) ? '' : $meta,
					'data'  => [ 'ortho' => 'color' ],
				] );

			break;
			case 'plural':
			case 'overwrite':
			case 'fullname':
			case 'tagline':
			case 'venue':

				$html.= Core\HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => empty( $meta ) ? '' : $meta,
					'data'  => [ 'ortho' => 'text' ],
				] );

				break;

			case 'code':
			case 'barcode':
			case 'latlng':
			case 'contact':

				$html.= Core\HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => empty( $meta ) ? '' : $meta,
					'class' => [ 'code' ],
					'data'  => [ 'ortho' => 'code' ],
				] );

				break;

			case 'viewable':

				$html.= Core\HTML::dropdown( $this->get_string( 'visibility', FALSE, 'misc', [] ), [
					'id'       => $this->classs( $field, 'id' ),
					'name'     => 'term-'.$field,
					'selected' => empty( $meta ) ? '0' : $meta,
				] );

				break;

			case 'arrow':

				$html.= Core\HTML::dropdown( $this->get_string( 'arrow_directions', FALSE, 'misc', [] ), [
					'id'       => $this->classs( $field, 'id' ),
					'name'     => 'term-'.$field,
					'selected' => empty( $meta ) ? 'undefined' : $meta,
				] );

				break;

			case 'date':
			case 'born':
			case 'dead':

				$html.= Core\HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => empty( $meta ) ? '' : ( strlen( $meta ) > 4 ? Datetime::prepForInput( $meta, 'Y/m/d', 'gregorian' ) : $meta ),
					'class' => [ 'code' ],
					'data'  => [ 'ortho' => 'date' ],
				] );

				break;

			case 'datetime':
			case 'datestart':
			case 'dateend':

				$html.= Core\HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => empty( $meta ) ? '' : Datetime::prepForInput( $meta, 'Y/m/d H:i', 'gregorian' ),
					'class' => [ 'code' ],
					'data'  => [ 'ortho' => 'date' ],
				] );

				break;

			case 'source':
			case 'embed':
			case 'url':

				$html.= Core\HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'url',
					'value' => empty( $meta ) ? '' : $meta,
					'class' => [ 'code' ],
				] );

				break;

			case 'label':
			case 'period':
			default:

				$html.= Core\HTML::tag( 'input', [
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
		$html     = '';
		$metatype = $this->get_supported_field_metatype( $field, $taxonomy );

		switch ( $metatype ) {

			case 'image':

				$html.= '<input type="hidden" name="term-'.$field.'" value="" />';

				$html.= Core\HTML::tag( 'button', [
					'class' => [ 'button', 'button-small', 'button-secondary', '-modal', '-quick' ],
				], _x( 'Choose', 'Button', 'geditorial-terms' ) );

				$html.= '&nbsp;'.Core\HTML::tag( 'a', [
					'href'  => '',
					'class' => [ 'button', 'button-small', 'button-link-delete', '-remove', '-quick' ],
					'style' => 'display:none',
				], _x( 'Remove', 'Button', 'geditorial-terms' ) ).'&nbsp;';

				$html.= Core\HTML::tag( 'img', [
					// 'src'   => '',
					'class' => '-img',
					'style' => 'display:none',
				] );

			break;

			case 'parent':
			case 'days':
			case 'hours':
			case 'amount':
			case 'unit':
			case 'min':
			case 'max':
			case 'order':

				$html.= Core\HTML::tag( 'input', [
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

				// NOTE: better not to use `input[typ=color]` since there is noway to leave it empty!
				// @REF: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/color#value
				$html.= Core\HTML::tag( 'input', [
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => '',
					'class' => [ 'small-text', 'code' ],
					'data'  => [ 'ortho' => 'color' ],
					'style' => 'width:85px;', // to override forced width within the quickedit
				] );

			break;
			case 'role':

				$html.= Core\HTML::dropdown( $this->get_settings_default_roles(), [
					'name'       => 'term-'.$field,
					'selected'   => '0',
					'none_title' => Settings::showOptionNone(),
				] );

			break;
			case 'posttype':

				$html.= Core\HTML::dropdown( WordPress\PostType::get( 2, [ 'show_ui' => TRUE ] ), [
					'name'       => 'term-'.$field,
					'selected'   => '0',
					'none_title' => Settings::showOptionNone(),
				] );

				break;

			case 'code':
			case 'barcode':
			case 'latlng':
			case 'contact':

				$html.= Core\HTML::tag( 'input', [
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => '',
					'class' => [ 'ptitle', 'code' ],
					'data'  => [ 'ortho' => 'code' ],
				] );

				break;

			case 'viewable':

				$html.= Core\HTML::dropdown( $this->get_string( 'visibility', FALSE, 'misc', [] ), [
					'name'     => 'term-'.$field,
					'selected' => '0',
				] );

				break;

			case 'arrow':

				$html.= Core\HTML::dropdown( $this->get_string( 'arrow_directions', FALSE, 'misc', [] ), [
					'name'     => 'term-'.$field,
					'selected' => 'undefined',
				] );

				break;

			case 'date':
			case 'datetime':
			case 'datestart':
			case 'dateend':
			case 'born':
			case 'dead':

				$html.= Core\HTML::tag( 'input', [
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => '',
					'class' => [ 'ptitle', 'code' ],
					'data'  => [ 'ortho' => 'date' ],
				] );

				break;

			case 'source':
			case 'embed':
			case 'url':

				$html.= Core\HTML::tag( 'input', [
					'name'  => 'term-'.$field,
					'type'  => 'url',
					'value' => '',
					'class' => [ 'ptitle', 'code' ],
				] );

				break;

			case 'label':
			case 'plural':
			case 'overwrite':
			case 'fullname':
			case 'tagline':
			case 'period':
			case 'venue':
			default:
				$html.= '<input type="text" class="ptitle" name="term-'.$field.'" value="" />';
		}

		echo $this->filters( 'supported_field_quickedit', $html, $field, $taxonomy, $metatype );
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
				'title'  => _x( 'Post Count', 'Adminbar', 'geditorial-terms' ).': '.WordPress\Strings::getCounted( $term->count ),
				'parent' => $this->classs(),
				'href'   => FALSE,
			];

			// TODO: display `$term->parent`

			if ( trim( $term->description ) ) {

				$nodes[] = [
					'id'     => $this->classs( 'desc' ),
					'title'  => _x( 'Description', 'Adminbar', 'geditorial-terms' ),
					'parent' => $this->classs(),
					'href'   => WordPress\Term::edit( $term ),
				];

				$nodes[] = [
					'id'     => $this->classs( 'desc', 'html' ),
					'parent' => $this->classs( 'desc' ),
					'href'   => FALSE,
					'meta'   => [
						'html'  => WordPress\Strings::prepDescription( $term->description ),
						'class' => 'geditorial-adminbar-desc-wrap -wrap '.$this->classs(),
					],
				];

			} else {

				$nodes[] = [
					'id'     => $this->classs( 'desc', 'empty' ),
					'title'  => _x( 'Description', 'Adminbar', 'geditorial-terms' ).': '.gEditorial\Plugin::na(),
					'parent' => $this->classs(),
					'href'   => WordPress\Term::edit( $term ),
				];
			}

			foreach ( $this->get_supported( $term->taxonomy ) as $field ) {

				$node = [
					'id'     => $this->classs( $field ),
					'parent' => $this->classs(),
					/* translators: `%s`: meta title */
					'title'  => sprintf( _x( 'Meta: %s', 'Adminbar', 'geditorial-terms' ),
						$this->get_string( $field, $term->taxonomy, 'titles', $field ) ),
				];

				$child = [
					'id'     => $this->classs( $field, 'html' ),
					'parent' => $node['id'],
				];

				$metakey  = $this->get_supported_metakey( $field, $term->taxonomy );
				$metatype = $this->get_supported_field_metatype( $field, $term->taxonomy );

				switch ( $metatype ) {
					case 'order':

						$node['title'].= ': '.Helper::htmlOrder( get_term_meta( $term->term_id, $metakey, TRUE ) );
						break;

					case 'days':
					case 'hours':
					case 'amount':
					case 'unit':
					case 'min':
					case 'max':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.Core\Number::format( $meta );
						else
							$node['title'].= ': &mdash;';

						break;

					case 'image':

						$image = WordPress\Taxonomy::htmlFeaturedImage( $term->term_id, [ 45, 72 ], TRUE, $metakey );

						$child['meta'] = [
							'html'  => $image ?: gEditorial()->na( FALSE ),
							'class' => 'geditorial-adminbar-image-wrap',
						];

					break;
					case 'parent':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.WordPress\Term::title( (int) $meta );
						else
							$node['title'].= ': '.gEditorial\Plugin::na();

						break;

					case 'author':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.get_user_by( 'id', $meta )->display_name;
						else
							$node['title'].= ': '.gEditorial\Plugin::na();

					break;
					case 'color':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.'<i class="field-color" style="background-color:'.Core\HTML::escape( $meta ).'"></i>';
						else
							$node['title'].= ': '.gEditorial\Plugin::na();

					break;
					case 'plural':
					case 'overwrite':
					case 'fullname':
					case 'tagline':
					case 'venue':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.WordPress\Strings::prepTitle( $meta );
						else
							$node['title'].= ': '.gEditorial\Plugin::na();

					break;
					case 'contact':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.Helper::prepContact( $meta );
						else
							$node['title'].= ': '.gEditorial\Plugin::na();

						break;

					case 'date':
					case 'born':
					case 'dead':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.Datetime::prepForDisplay( trim( $meta ), 'Y/m/d' );
						else
							$node['title'].= ': '.gEditorial\Plugin::na();

						break;

					case 'datetime':
					case 'datestart':
					case 'dateend':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.Datetime::prepForDisplay( trim( $meta ), 'Y/m/d H:i' );
						else
							$node['title'].= ': '.gEditorial\Plugin::na();

						break;

					case 'source':
					case 'embed':
					case 'url':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

							$node['title'].= ': '.Core\URL::prepTitle( $meta );
							$node['href'] = $meta;

						} else {

							$node['title'].= ': '.gEditorial\Plugin::na();
						}

						break;

					// TODO: add the rest!

					default:

						$node['title'] = _x( 'Meta: Uknonwn', 'Adminbar', 'geditorial-terms' );
						break;
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
		$node_id = $this->classs();

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		$nodes[] = [
			'id'     => $node_id,
			'title'  => _x( 'Summary of Terms', 'Adminbar', 'geditorial-terms' ),
			'parent' => $parent,
			'href'   => $this->get_module_url( 'reports' ),
		];

		foreach ( $this->taxonomies() as $taxonomy ) {

			if ( ! $object = get_taxonomy( $taxonomy ) )
				continue;

			$terms = WordPress\Taxonomy::getPostTerms( $taxonomy, $post_id );

			if ( ! $terms || is_wp_error( $terms ) )
				continue;

			$nodes[] = [
				'parent' => $node_id,
				'id'     => $this->classs( 'tax', $taxonomy ),
				'title'  => $object->labels->name.':',
				'href'   => WordPress\Taxonomy::edit( $taxonomy ),
			];

			foreach ( $terms as $term )
				$nodes[] = [
					'parent' => $node_id,
					'id'     => $this->classs( 'term', $term->term_id ),
					'title'  => '&ndash; '.WordPress\Term::title( $term ),
					'href'   => WordPress\Term::link( $term ),
				];
		}
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

	public function display_media_states( $media_states, $post )
	{
		if ( $term_id = WordPress\Taxonomy::getIDbyMeta( $post->ID, 'image' ) )
			/* translators: `%s`: term name */
			$media_states[] = sprintf( _x( 'Term Image for &ldquo;%s&rdquo;', 'Media State', 'geditorial-terms' ), get_term( $term_id )->name );

		return $media_states;
	}

	public function datacodes_print_template_data( $data, $type, $term, $metakey )
	{
		if ( 'term' != $type )
			return $data;

		if ( empty( $data['barcode'] ) && in_array( 'barcode', $this->get_supported( $term->taxonomy ) ) ) {

			$barcode_metakey = $this->get_supported_metakey( 'barcode', $term->taxonomy );

			if ( $barcode = get_term_meta( $term->term_id, $barcode_metakey, TRUE ) )
				$data['barcode'] = $barcode;
		}

		if ( empty( $data['data']['code'] ) && in_array( 'code', $this->get_supported( $term->taxonomy ) ) ) {

			$code_metakey = $this->get_supported_metakey( 'code', $term->taxonomy );

			if ( $code = get_term_meta( $term->term_id, $code_metakey, TRUE ) )
				$data['data']['code'] = $code;
		}

		if ( empty( $data['data']['arrow'] ) && in_array( 'arrow', $this->get_supported( $term->taxonomy ) ) ) {

			$arrow_metakey = $this->get_supported_metakey( 'arrow', $term->taxonomy );

			if ( $arrow = get_term_meta( $term->term_id, $arrow_metakey, TRUE ) )
				$data['data']['arrow'] = $arrow;
		}

		if ( empty( $data['data']['label'] ) && in_array( 'label', $this->get_supported( $term->taxonomy ) ) ) {

			$label_metakey = $this->get_supported_metakey( 'label', $term->taxonomy );

			if ( $label = get_term_meta( $term->term_id, $label_metakey, TRUE ) )
				$data['data']['label'] = $label;
		}

		if ( empty( $data['data']['image'] ) && in_array( 'image', $this->get_supported( $term->taxonomy ) ) ) {

			$image_metakey = $this->get_supported_metakey( 'image', $term->taxonomy );

			if ( $image = get_term_meta( $term->term_id, $image_metakey, TRUE ) )
				$data['data']['image'] = $image;
		}

		return $data;
	}

	public function taxonomy_export_term_meta( $metas, $taxonomy )
	{
		return array_merge( $metas, $this->list_supported( $taxonomy ) );
	}

	public function taxonomy_bulk_actions( $actions, $taxonomy )
	{
		$additions = [];
		$supported = $this->get_supported( $taxonomy );

		if ( in_array( 'image', $supported, TRUE ) )
			$additions['sync_image_titles'] = _x( 'Sync Image Titles', 'Bulk Actions', 'geditorial-terms' );

		if ( in_array( 'tagline', $supported, TRUE ) )
			$additions['move_tagline_to_desc'] = _x( 'Move Tagline to Description', 'Bulk Actions', 'geditorial-terms' );

		return array_merge( $actions, $additions );
	}

	public function taxonomy_bulk_callback( $callback, $action, $taxonomy )
	{
		$actions = [
			'sync_image_titles',
			// 'move_desc_to_fullname', // TODO
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
		if ( ! in_array( 'tagline', $this->get_supported( $taxonomy, TRUE ) ) )
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

	public function taxonomy_delete_empty_term( $delete, $term_id, $taxonomy )
	{
		if ( ! $delete || ! $term_id || ! $taxonomy )
			return $delete;

		foreach ( $this->get_supported( $taxonomy ) as $field )
			if ( FALSE !== get_term_meta( $term_id, $this->get_supported_metakey( $field, $taxonomy ), TRUE ) )
				return FALSE;

		return $delete;
	}

	public function taxonomy_delete_term( $delete, $term, $taxonomy, $force )
	{
		if ( $force || ! $delete || !$taxonomy || ! $term || is_wp_error( $term ) )
			return $delete;

		foreach ( $this->get_supported( $taxonomy ) as $field )
			if ( FALSE !== get_term_meta( $term->term_id, $this->get_supported_metakey( $field, $taxonomy ), TRUE ) )
				return FALSE;

		return $delete;
	}

	/**
	 * Changes `get_terms()` defaults for supported taxonomies to order by meta.
	 * @source `wc_change_get_terms_defaults()`
	 *
	 * @param array  $defaults
	 * @param array  $taxonomies
	 * @return array $defaults
	 */
	public function get_terms_defaults_ordering( $defaults, $taxonomies )
	{
		if ( is_array( $taxonomies ) && 1 < count( $taxonomies ) )
			return $defaults;

		$taxonomy = is_array( $taxonomies ) ? (string) current( $taxonomies ) : $taxonomies;
		$orderby  = 'name';

		if ( in_array( 'order', $this->get_supported( $taxonomy, TRUE ) ) )
			$orderby = 'menu_order';

		// Change defaults. Invalid values will be changed later @see `pre_get_terms_ordering()`.
		// These are in place so we know if a specific order was requested.
		switch ( $orderby ) {
			case 'menu_order':
			// case 'name_num':
			case 'parent':
				$defaults['orderby'] = $orderby;
				break;
		}

		return $defaults;
	}

	/**
	 * Adds support to `get_terms()` for order by `menu_order`.
	 * @source `wc_change_pre_get_terms()`
	 *
	 * @param WP_Term_Query $terms_query
	 */
	public function pre_get_terms_ordering( $terms_query )
	{
		$args = &$terms_query->query_vars;

		// Put back valid orderby values.
		if ( 'menu_order' === $args['orderby'] ) {
			$args['orderby']               = 'name';
			$args['force_menu_order_sort'] = TRUE;
		}

		// if ( 'name_num' === $args['orderby'] ) {
		// 	$args['orderby']            = 'name';
		// 	$args['force_numeric_name'] = TRUE;
		// }

		// When COUNTING, disable custom sorting.
		if ( 'count' === $args['fields'] )
			return;

		// Support menu_order arg used in previous versions.
		// if ( ! empty( $args['menu_order'] ) ) {
		// 	$args['order']                 = 'DESC' === strtoupper( $args['menu_order'] ) ? 'DESC' : 'ASC';
		// 	$args['force_menu_order_sort'] = TRUE;
		// }

		if ( ! empty( $args['force_menu_order_sort'] )
			&& ( $metakey = $this->get_supported_metakey( 'order', $args['taxonomy'] ) ) ) {

			$args['meta_key'] = $metakey;
			$args['orderby']  = 'meta_value_num';

			$terms_query->meta_query->parse_query_vars( $args );
		}
	}

	/**
	 * Adjusts term query to handle custom sorting parameters.
	 * @source: `wc_terms_clauses()`
	 *
	 * @param array  $clauses
	 * @param array  $taxonomies
	 * @param array  $args
	 * @return array $clauses
	 */
	public function terms_clauses_ordering( $clauses, $taxonomies, $args )
	{
		global $wpdb;

		// No need to filter when counting.
		if ( Core\Text::has( $clauses['fields'], 'COUNT(*)' ) )
			return $clauses;

		// Force numeric sort if using name_num custom sorting param.
		// if ( ! empty( $args['force_numeric_name'] ) )
		// 	$clauses['orderby'] = str_replace( 'ORDER BY t.name', 'ORDER BY t.name+0', $clauses['orderby'] );

		// For sorting, force left join in case order meta is missing.
		if ( ! empty( $args['force_menu_order_sort'] )
			&& ( $metakey = $this->get_supported_metakey( 'order', $args['taxonomy'] ) ) ) {

			$clauses['join'] = str_replace(
				"INNER JOIN {$wpdb->termmeta} ON ( t.term_id = {$wpdb->termmeta}.term_id )",
				"LEFT JOIN {$wpdb->termmeta} ON ( t.term_id = {$wpdb->termmeta}.term_id AND {$wpdb->termmeta}.meta_key='{$metakey}')",
			$clauses['join'] );

			$clauses['where'] = str_replace(
				"{$wpdb->termmeta}.meta_key = '{$metakey}'",
				"( {$wpdb->termmeta}.meta_key = '{$metakey}' OR {$wpdb->termmeta}.meta_key IS NULL )",
			$clauses['where'] );

			$clauses['orderby'] = 'DESC' === $args['order']
				? str_replace( 'meta_value+0', 'meta_value+0 DESC, t.name', $clauses['orderby'] )
				: str_replace( 'meta_value+0', 'meta_value+0 ASC, t.name', $clauses['orderby'] );
		}

		return $clauses;
	}

	public function woocommerce_sortable_taxonomies( $taxonomies )
	{
		return array_merge( $taxonomies, $this->get_supported_taxonomies( 'order' ) );
	}

	private function _hook_overwrite_titles( $taxonomies )
	{
		if ( is_admin() || empty( $taxonomies ))
			return FALSE;

		$this->filter_self( 'sanitize_name', 3, 9 );

		add_filter( 'single_term_title', function ( $name ) use ( $taxonomies ) {

			if ( ! is_tax( $taxonomies ) )
				return $name;

			if ( ! $term = get_queried_object() )
				return $name;

			$metakey = $this->get_supported_metakey( 'overwrite', $term->taxonomy );
			$meta    = get_term_meta( $term->term_id, $metakey, TRUE );

			return $meta ?: $name; // TODO: pass through filters
		}, 8 );

		foreach ( $taxonomies as $taxonomy )
			add_filter( $taxonomy.'_name', function ( $value, $term_id, $context ) {

				if ( 'display' !== $context )
					return $value;

				$metakey = $this->get_supported_metakey( 'overwrite' );
				$meta    = get_term_meta( $term_id, $metakey, TRUE );

				return $meta ?: $value; // TODO: pass through filters
			}, 8, 3 );

		return count( $taxonomies );
	}

	// @FILTER: `geditorial_terms_sanitize_name`
	public function sanitize_name( $name, $term, $action )
	{
		if ( ! in_array( 'overwrite', $this->get_supported( $term->taxonomy ), TRUE ) )
			return $name;

		$metakey = $this->get_supported_metakey( 'overwrite', $term->taxonomy );
		$meta    = get_term_meta( $term->term_id, $metakey, TRUE );

		return $meta ?: $name; // TODO: pass through filters
	}

	public function searchselect_result_image_for_term( $data, $term, $queried )
	{
		if ( empty( $queried['context'] )
			|| in_array( $queried['context'], [ 'select2', 'pairedimports' ], TRUE ) )
			return $data;

		if ( ! $term = WordPress\Term::get( $term ) )
			return $data;

		if ( ! in_array( 'image', $this->get_supported( $term->taxonomy ), TRUE ) )
			return $data;

		$metakey    = $this->get_supported_metakey( 'image', $term->taxonomy );
		$attachment = WordPress\Taxonomy::getThumbnailID( $term->term_id, $metakey );

		if ( $src = WordPress\Media::htmlAttachmentSrc( $attachment, [ 45, 72 ], FALSE ) )
			return $src;

		return $data;
	}

	public function term_intro_title_suffix( $suffix, $term, $desc, $args, $module )
	{
		if ( ! $taxonomy = WordPress\Term::taxonomy( $term ) )
			return $suffix;

		$supported = $this->get_supported( $taxonomy );

		if ( ! in_array( 'born', $supported, TRUE )
			&& ! in_array( 'dead', $supported, TRUE ) )
			return $suffix;

		$html = Datetime::prepBornDeadForDisplay(
			get_term_meta( $term->term_id, $this->get_supported_metakey( 'born', $taxonomy ), TRUE ),
			get_term_meta( $term->term_id, $this->get_supported_metakey( 'dead', $taxonomy ), TRUE )
		);

		return $html ? sprintf( '%s %s', $suffix, $html ) : $suffix;
	}

	public function term_intro_description_after( $term, $desc, $image, $args, $module )
	{
		if ( ! $desc && ! $image && empty( $args['heading'] ) )
			return;

		if ( ! $taxonomy = WordPress\Term::taxonomy( $term ) )
			return;

		$supported = $this->get_supported( $taxonomy );

		if ( in_array( 'source', $supported, TRUE ) )
			$this->_render_source_link( $term, 'source' );

		if ( in_array( 'embed', $supported, TRUE ) )
			echo Template::doMediaShortCode( get_term_meta( $term->term_id, $this->get_supported_metakey( 'embed', $taxonomy ), TRUE ) ?: '' );
	}

	private function _render_source_link( $term, $field = 'source' )
	{
		if ( ! $title = $this->get_setting_fallback( $field.'_link_title', _x( 'Source', 'Setting Default', 'geditorial-terms' ) ) )
			return;

		if ( $meta = get_term_meta( $term->term_id, $this->get_supported_metakey( $field, $term->taxonomy ), TRUE ) )
			echo Core\HTML::wrap( Core\HTML::link( $title, $meta, TRUE ), '-term-'.$field );
	}
}
