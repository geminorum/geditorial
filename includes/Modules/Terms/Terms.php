<?php namespace geminorum\gEditorial\Modules\Terms;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Terms extends gEditorial\Module
{
	protected $supported = [
		'parent',
		'order',
		'plural',
		// 'singular', // TODO
		'overwrite',
		'fullname',
		'tagline',
		'subtitle',
		'contact',
		'venue',
		'image',
		// 'icon', // TODO
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
		'establish',
		'abolish',
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
		// 'identity',  // TODO
		// 'address'    // TODO
		// 'email'      // TODO
		// 'plate',     // TODO
	];

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
		$fields   = $this->_get_supported_raw();
		$settings = [
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
		];

		foreach ( $fields as $field )
			$settings[sprintf( '_field_%s', $field )] = [
				[
					'field'  => 'term_'.$field,
					'type'   => 'checkboxes-values',
					'title'  => _x( 'Supported Taxonomies', 'Setting Title', 'geditorial-terms' ),
					'values' => $this->get_taxonomies_support( $field ),
				]
			];

		$settings['_misc'] = [
			[
				'field'       => 'prevent_deletion',
				'title'       => _x( 'Prevent Deletion', 'Setting Title', 'geditorial-terms' ),
				'description' => _x( 'Exempts the terms with meta-data from bulk empty deletions.', 'Setting Description', 'geditorial-terms' ),
				'default'     => TRUE,
			],
		];

		$settings['_frontend'] = [
			'frontend_search' => [ _x( 'Adds results by field information on front-end search.', 'Setting Description', 'geditorial-terms' ), TRUE ],
			'adminbar_summary',
		];

		$settings['taxonomies_option'] = 'taxonomies_option';

		return $settings;
	}

	protected function settings_section_titles( $suffix )
	{
		$field = Core\Text::stripPrefix( $suffix, '_field_' );

		if ( in_array( $field, $this->_get_supported_raw(), TRUE ) )
			return [
				$this->get_supported_field_title( $field, FALSE ),
				Core\HTML::code( $field, '-field-key' )."\n\n".$this->get_supported_field_desc( $field, FALSE ),
			];

		return FALSE;
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
				'subtitle'  => _x( 'Subtitle', 'Titles', 'geditorial-terms' ),
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
				'latlng'    => _x( 'Coordinates', 'Titles', 'geditorial-terms' ),
				'date'      => _x( 'Date', 'Titles', 'geditorial-terms' ),
				'datetime'  => _x( 'Date-Time', 'Titles', 'geditorial-terms' ),
				'datestart' => _x( 'Date-Start', 'Titles', 'geditorial-terms' ),
				'dateend'   => _x( 'Date-End', 'Titles', 'geditorial-terms' ),
				'days'      => _x( 'Days', 'Titles', 'geditorial-terms' ),
				'born'      => _x( 'Born', 'Titles', 'geditorial-terms' ),
				'dead'      => _x( 'Dead', 'Titles', 'geditorial-terms' ),
				'establish' => _x( 'Establish', 'Titles', 'geditorial-terms' ),
				'abolish'   => _x( 'Abolish', 'Titles', 'geditorial-terms' ),
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
				'parent'    => _x( 'Terms can have parents from other taxonomies.', 'Descriptions', 'geditorial-terms' ),
				'order'     => _x( 'Terms are usually ordered alphabetically, but you can choose your own order by numbers.', 'Descriptions', 'geditorial-terms' ),
				'plural'    => _x( 'Defines the plural form of the term.', 'Descriptions', 'geditorial-terms' ),
				'overwrite' => _x( 'Replaces the term name on the front-page display.', 'Descriptions', 'geditorial-terms' ),
				'fullname'  => _x( 'Defines the full-name form of the term.', 'Descriptions', 'geditorial-terms' ),
				'tagline'   => _x( 'Gives more information about the term in a short phrase.', 'Descriptions', 'geditorial-terms' ),
				'subtitle'  => _x( 'Gives more information about the term in a subtitle.', 'Descriptions', 'geditorial-terms' ),
				'contact'   => _x( 'Adds a way to contact someone about the term, by url, email or phone.', 'Descriptions', 'geditorial-terms' ),
				'venue'     => _x( 'Defines a string as venue for the term.', 'Descriptions', 'geditorial-terms' ),
				'image'     => _x( 'Assigns a custom image to visually separate terms from each other.', 'Descriptions', 'geditorial-terms' ),
				'author'    => _x( 'Sets a user as term author to help identify who created or owns each term.', 'Descriptions', 'geditorial-terms' ),
				'color'     => _x( 'Assigns a custom color to visually separate terms from each other.', 'Descriptions', 'geditorial-terms' ),
				'role'      => _x( 'Terms can have unique role visibility to help separate them for user roles.', 'Descriptions', 'geditorial-terms' ),
				'roles'     => _x( 'Terms can have unique roles visibility to help separate them for user roles.', 'Descriptions', 'geditorial-terms' ),
				'posttype'  => _x( 'Terms can have unique posttype visibility to help separate them on editing.', 'Descriptions', 'geditorial-terms' ),
				'posttypes' => _x( 'Terms can have unique posttypes visibility to help separate them on editing.', 'Descriptions', 'geditorial-terms' ),
				'arrow'     => _x( 'Terms can have direction arrows to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'label'     => _x( 'Terms can have text labels to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'code'      => _x( 'Terms can have text code to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'barcode'   => _x( 'Terms can have barcodes to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'latlng'    => _x( 'Terms can have latitude and longitude to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'date'      => _x( 'Terms can have dates to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'datetime'  => _x( 'Terms can have a date-time to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'datestart' => _x( 'Terms can have a start date to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'dateend'   => _x( 'Terms can have an end date to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'born'      => _x( 'Defines the date on which the person was born.', 'Descriptions', 'geditorial-terms' ),
				'dead'      => _x( 'Defines the date on which the person has died.', 'Descriptions', 'geditorial-terms' ),
				'establish' => _x( 'Defines the date on which the entity was established.', 'Descriptions', 'geditorial-terms' ),
				'abolish'   => _x( 'Defines the date on which the entity was terminated, disbanded, inactivated, or superseded.', 'Descriptions', 'geditorial-terms' ),
				'days'      => _x( 'Terms can have days number to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'hours'     => _x( 'Terms can have hour number to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'period'    => _x( 'The length of time of the term.', 'Descriptions', 'geditorial-terms' ),
				'amount'    => _x( 'The quantity number of the term.', 'Descriptions', 'geditorial-terms' ),
				'unit'      => _x( 'Terms can have unit number to help organize them.', 'Descriptions', 'geditorial-terms' ),
				'min'       => _x( 'Defines the minimum threshold for the term.', 'Descriptions', 'geditorial-terms' ),
				'max'       => _x( 'Defines the maximum threshold for the term.', 'Descriptions', 'geditorial-terms' ),
				'viewable'  => _x( 'Determines whether the term is publicly viewable.', 'Descriptions', 'geditorial-terms' ),
				'source'    => _x( 'Defines a source URL for the term.', 'Descriptions', 'geditorial-terms' ),
				'embed'     => _x( 'Defines an embedable URL for the term.', 'Descriptions', 'geditorial-terms' ),
				'url'       => _x( 'Defines a custom URL for the term.', 'Descriptions', 'geditorial-terms' ),
			],
			'misc' => [
				'posts_column_title' => _x( 'Posts', 'Column Title', 'geditorial-terms' ),

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

	protected function get_taxonomies_support( $field )
	{
		$supported = WordPress\Taxonomy::get();
		$excluded  = Core\Arraay::prepString( $this->taxonomies_excluded() );

		switch ( $field ) {
			case 'role'     : $excluded = array_merge( $excluded, [ 'audit_attribute' ] ); break;
			case 'plural'   : $excluded = array_merge( $excluded, [ 'post_tag', 'product_tag' ] ); break;
			case 'overwrite': $excluded = array_merge( $excluded, [ 'post_tag', 'product_tag' ] ); break;
			case 'fullname' : $excluded = array_merge( $excluded, [ 'post_tag', 'product_tag' ] ); break;
			case 'tagline'  : $excluded = array_merge( $excluded, [ 'post_tag', 'product_tag' ] ); break;
			case 'subtitle' : $excluded = array_merge( $excluded, [ 'post_tag', 'product_tag' ] ); break;
			case 'image'    : $excluded = array_merge( $excluded, [ 'post_tag', 'product_tag', 'product_cat', 'product_brand' ] ); break;
			case 'barcode'  : $excluded = array_merge( $excluded, [ 'warehouse_placement' ] ); break;

			// NOTE: override!
			case 'arrow'    : return Core\Arraay::keepByKeys( $supported, [ 'warehouse_placement' ] );
			case 'born'     : return Core\Arraay::keepByKeys( $supported, [ 'people' ] );
			case 'dead'     : return Core\Arraay::keepByKeys( $supported, [ 'people' ] );
			case 'establish': return Core\Arraay::keepByKeys( $supported, [ 'drone_manufacturer', 'publication_publisher', 'provider_brand', 'vehicle_manufacturer' ] );
			case 'abolish'  : return Core\Arraay::keepByKeys( $supported, [ 'drone_manufacturer', 'publication_publisher', 'provider_brand', 'vehicle_manufacturer' ] );
		}

		return array_unique( array_diff_key( $supported, array_flip( $excluded ) ) );
	}

	public function init()
	{
		parent::init();

		$this->register_meta_fields();

		$this->action( [ 'edit_term', 'create_term' ], 3 );
		$this->action( 'delete_attachment', 2, 12 );

		if ( $this->get_setting( 'frontend_search', TRUE ) )
			$this->filter( 'terms_search_append_meta_frontend', 4, 8, FALSE, $this->base );

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

		$this->filter_module( 'alphabet', 'term_title_metakeys', 2, 8 );
		$this->filter( 'searchselect_result_image_for_term', 3, 12, FALSE, $this->base );
		$this->filter( 'term_intro_title_suffix', 5, 8, FALSE, $this->base );
		$this->action( 'term_intro_description_before', 5, 2, FALSE, $this->base );
		$this->action( 'term_intro_description_after', 5, 5, FALSE, $this->base );
		$this->filter( 'calendars_sanitize_ical_context', 3, 8, FALSE, $this->base );
		$this->filter( 'calendars_term_link', 3, 8, FALSE, $this->base );
		$this->filter( 'calendars_term_events', 3, 8, FALSE, $this->base );

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

				add_action( $screen->taxonomy.'_add_form_fields',
					function ( $taxonomy ) use ( $field ) {
						$this->add_form_field( $field, $taxonomy );
					}, 8, 1 );

				if ( ! in_array( $field, [ 'roles', 'posttypes' ] ) ) {

					// TODO: see `taxtax__hook_screen()` for multiple checkbox support

					add_action( 'quick_edit_custom_box',
						function ( $column, $screen, $taxonomy ) use ( $field ) {
							if ( $this->classs( $field ) == $column )
								$this->quick_form_field( $field, $taxonomy );
						}, 10, 3 );

					$enqueue = TRUE;
				}

				if ( 'image' == $field ) {

					gEditorial\Scripts::enqueueThickBox();

				} else if ( 'color' == $field ) {

					gEditorial\Scripts::enqueueColorPicker();
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

				$this->filter( 'taxonomy_export_term_meta_data', 4, 8, FALSE, 'gnetwork' );
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

				add_action( $screen->taxonomy.'_edit_form_fields',
					function ( $term, $taxonomy ) use ( $field, $disabled ) {
						$this->edit_form_field( $field, $taxonomy, $term, $disabled );
					}, 8, 2 );

				if ( $disabled )
					continue;

				if ( ! in_array( $field, [ 'roles', 'posttypes' ] ) )
					$enqueue = TRUE;

				if ( 'image' == $field ) {

					gEditorial\Scripts::enqueueThickBox();

				} else if ( 'color' == $field ) {

					gEditorial\Scripts::enqueueColorPicker();
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
	public function get_supported( $taxonomy = FALSE )
	{
		$fields = [];

		foreach ( $this->_get_supported_raw() as $field )
			if ( FALSE === $taxonomy || $this->in_setting( $taxonomy, 'term_'.$field ) )
				$fields[] = $field;

		return $this->filters( 'supported_fields',
			$fields,
			$taxonomy
		);
	}

	// FALSE for all
	public function list_supported( $taxonomy = FALSE )
	{
		$list = [];

		foreach ( $this->_get_supported_raw() as $field )
			if ( FALSE === $taxonomy || $this->in_setting( $taxonomy, 'term_'.$field ) )
				$list[$field] = $this->strings['titles'][$field];

		return $this->filters( 'list_supported_fields',
			$list,
			$taxonomy
		);
	}

	// NOTE: globally available via: `Services\TaxonomyFields::getTermMetaKey()`
	public function get_supported_metakey( $field, $taxonomy = FALSE )
	{
		return $this->filters( 'supported_field_metakey',
			$field,  // NOTE: by default the meta-key is the same as the field
			$field,
			$taxonomy // TODO: handle if `$taxonomy` was an array
		);
	}

	public function get_supported_field_metatype( $field, $taxonomy )
	{
		return $this->filters( 'supported_field_metatype',
			$field, // NOTE: by default the meta-type is the same as the field
			$field,
			$taxonomy
		);
	}

	public function get_supported_taxonomies( $field )
	{
		return $this->filters( 'supported_field_taxonomies',
			$this->get_setting( 'term_'.$field, [] ),
			$field
		);
	}

	public function get_supported_field_title( $field, $taxonomy, $term = FALSE )
	{
		return $this->filters( 'field_'.$field.'_title',
			$this->get_string( $field, $taxonomy, 'titles', $field ),
			$taxonomy,
			$field,
			$term
		);
	}

	public function get_supported_field_desc( $field, $taxonomy, $term = FALSE )
	{
		return $this->filters( 'field_'.$field.'_desc',
			$this->get_string( $field, $taxonomy, 'descriptions', '' ),
			$taxonomy,
			$field,
			$term
		);
	}

	public function get_supported_position( $field, $taxonomy = FALSE )
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

			case 'abolish':
				$position = [ $this->classs( 'establish' ), 'after' ];
				break;

			default:
				$position = [ 'name', 'after' ];
		}

		return $this->filters( 'supported_field_position',
			$position,
			$field,
			$taxonomy
		);
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
			else if ( in_array( $field, [ 'date', 'datetime', 'datestart', 'dateend', 'born', 'dead', 'establish', 'abolish' ] ) )
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

			// registers general field for prepared meta-data
			// mainly for display purposes only
			if ( in_array( $field, [ 'image' ] ) )
				register_rest_field( $taxonomies, $field, [
					'get_callback'  => [ $this, 'attribute_get_callback' ],
					// 'auth_callback' => [ $this, 'attribute_auth_callback' ], // FIXME
				] );
		}
	}

	public function attribute_get_callback( $params, $attr, $request, $object_type )
	{
		switch ( $attr ) {

			case 'image':

				$metakey = $this->get_supported_metakey( 'image', $object_type );

				return WordPress\Media::prepAttachmentData( get_term_meta( (int) $params['id'], $metakey, TRUE ) );

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
			'establish',
			'abolish',
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
			'subtitle',
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
			'establish',
			'abolish',
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

	public function custom_column( $string, $column, $term_id )
	{
		if ( ! $taxonomy = self::req( 'taxonomy' ) )
			return $string;

		$term      = get_term_by( 'id', $term_id, $taxonomy );
		$supported = $this->get_supported( $taxonomy );

		foreach ( $supported as $field ) {

			if ( $this->classs( $field ) != $column )
				continue;

			$this->display_form_field( $field, $taxonomy, $term, TRUE );
			break;
		}

		if ( $this->check_hidden_column( $column ) )
			return $string;

		// NOTE: here for custom column with multiple fields support
		$this->actions( 'custom_column', $column, $taxonomy, $supported, $term );
	}

	// TODO: use read-only inputs on non-columns
	private function display_form_field( $field, $taxonomy, $term, $column = TRUE )
	{
		$html     = $meta = '';
		$metakey  = $this->get_supported_metakey( $field, $taxonomy );
		$metatype = $this->get_supported_field_metatype( $field, $taxonomy );

		switch ( $metatype ) {

			case 'order':

				$meta = get_term_meta( $term->term_id, $metakey, TRUE );

				if ( $meta || '0' === $meta ) {

					$html = gEditorial\Listtable::columnOrder( $meta );

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

					$html = $meta ? Core\Number::format( $meta ) : gEditorial\Helper::htmlEmpty( '-'.$metakey );

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

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$icon = Core\HTML::getDashicon( 'tagcloud', $meta, 'icon-barcode' );
					$html = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $meta ).'">'.$icon.'</span>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'latlng':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$html = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $meta ).'">';
					$html.= Core\HTML::link( Core\HTML::getDashicon( 'admin-site-alt3', $meta, '-icon-'.$field ), gEditorial\Info::lookupURLforLatLng( $meta ), TRUE ).'</span>';

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
			case 'subtitle':
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
						.gEditorial\Helper::prepContact( $meta, NULL, '', TRUE ).'</span>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'image':

				$size = NULL; // TODO: maybe filter for this module?!
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

				if ( empty( $this->cache['roles'] ) )
					$this->cache['roles'] = $this->get_settings_default_roles();

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
					$html = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $meta ).'">'
						.( empty( $this->cache['roles'][$meta] )
							? Core\HTML::escape( $meta )
							: $this->cache['roles'][$meta] )
						.'</span>';

				else
					$html = $this->field_empty( 'role', '0', $column );

				break;

			case 'roles':

				if ( empty( $this->cache['roles'] ) )
					$this->cache['roles'] = $this->get_settings_default_roles();

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$list = [];

					foreach ( (array) $meta as $role )
						$list[] = '<span class="-field field-'.$field.'" data-'.$field.'="'.Core\HTML::escape( $role ).'">'
							.( empty( $this->cache['roles'][$role] )
								? Core\HTML::escape( $role )
								: $this->cache['roles'][$role] )
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
			case 'establish':
			case 'abolish':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$cal   = $this->default_calendar();
					$date  = gEditorial\Datetime::prepForDisplay( trim( $meta ), 'Y/m/d', $cal );
					$input = gEditorial\Datetime::prepForInput( trim( $meta ), 'Y/m/d', $cal );
					$html  = '<span class="-field field-'.$field.'" data-'.$field.'="'.$input.'">'.$date.'</span>';

				} else {

					$html = $this->field_empty( $field, '', $column );
				}

				break;

			case 'datetime':
			case 'datestart':
			case 'dateend':

				if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) ) {

					$cal   = $this->default_calendar();
					$date  = gEditorial\Datetime::prepForDisplay( trim( $meta ), 'Y/m/d H:i', $cal );
					$input = gEditorial\Datetime::prepForInput( trim( $meta ), 'Y/m/d H:i', $cal );
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

				} else if ( in_array( $field, [ 'date', 'born', 'dead', 'establish', 'abolish' ] ) ) {

					$meta = Core\Text::trim( Core\Number::translate( $meta ) );

					// accepts year only
					if ( strlen( $meta ) > 4 )
						$meta = gEditorial\Datetime::makeMySQLFromInput( $meta, 'Y-m-d', $calendar, NULL, $meta );

				} else if ( in_array( $field, [ 'datetime', 'datestart', 'dateend' ] ) ) {

					$meta = Core\Text::trim( Core\Number::translate( $meta ) );
					$meta = gEditorial\Datetime::makeMySQLFromInput( $meta, NULL, $calendar, NULL, $meta );

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

	/**
	 * Deletes all attachment images for any term.
	 * @ref: `_delete_attachment_theme_mod()`
	 *
	 * @param int $post_id
	 * @param mixed $post
	 * @return void
	 */
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
	 * Renders form HTML mark-up for the given field.
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
					'class' => Core\HTML::buttonClass( TRUE, [ 'button-secondary', '-modal' ] ),
				], _x( 'Choose', 'Button', 'geditorial-terms' ) );

				$html.= '&nbsp;'.Core\HTML::tag( 'a', [
					'class' => Core\HTML::buttonClass( TRUE, [ 'button-link-delete', '-remove' ] ),
					'style' => empty( $meta ) ? 'display:none' : FALSE,
				], _x( 'Remove', 'Button', 'geditorial-terms' ) );

			break;

			case 'parent': // Must input the `term_id`, due to different parent taxonomy support!
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

				// Selected value on add new term form
				if ( FALSE === $term )
					$meta = get_current_user_id();

				$html.= gEditorial\Listtable::restrictByAuthor( empty( $meta ) ? '0' : $meta, 'term-'.$field, [
					'echo'            => FALSE,
					'show_option_all' => gEditorial\Settings::showOptionNone(),
				] );

			break;
			case 'role':

				$html.= Core\HTML::dropdown( $this->get_settings_default_roles(), [
					'id'         => $this->classs( $field, 'id' ),
					'name'       => 'term-'.$field,
					'selected'   => empty( $meta ) ? '0' : $meta,
					'none_title' => gEditorial\Settings::showOptionNone(),
				] );

			break;
			case 'roles':

				$html.= gEditorial\Settings::tabPanelOpen();

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
					'none_title' => gEditorial\Settings::showOptionNone(),
				] );

			break;
			case 'posttypes':

				$html.= gEditorial\Settings::tabPanelOpen();

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
			case 'subtitle':
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
			case 'establish':
			case 'abolish':

				$html.= Core\HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => empty( $meta ) ? '' : ( strlen( $meta ) > 4 ? gEditorial\Datetime::prepForInput( $meta, 'Y/m/d', $this->default_calendar() ) : $meta ),
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
					'value' => empty( $meta ) ? '' : gEditorial\Datetime::prepForInput( $meta, 'Y/m/d H:i', $this->default_calendar() ),
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
					'class' => Core\HTML::buttonClass( TRUE, [ 'button-secondary', '-modal', '-quick' ] ),
				], _x( 'Choose', 'Button', 'geditorial-terms' ) );

				$html.= '&nbsp;'.Core\HTML::tag( 'a', [
					'href'  => '',
					'class' => Core\HTML::buttonClass( TRUE, [ 'button-link-delete', '-remove', '-quick' ] ),
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

				$html.= gEditorial\Listtable::restrictByAuthor( 0, 'term-'.$field, [
					'echo'            => FALSE,
					'show_option_all' => gEditorial\Settings::showOptionNone(),
				] );

			break;
			case 'color':

				// NOTE: better not to use `input[typ=color]` since there is noway to leave it empty!
				// @REF: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/color#value
				$html.= Core\HTML::tag( 'input', [
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => '',
					'class' => [ 'small-text', 'code-text' ],
					'data'  => [ 'ortho' => 'color' ],
					'style' => 'width:85px;', // to override forced width within the quickedit
				] );

			break;
			case 'role':

				$html.= Core\HTML::dropdown( $this->get_settings_default_roles(), [
					'name'       => 'term-'.$field,
					'selected'   => '0',
					'none_title' => gEditorial\Settings::showOptionNone(),
				] );

			break;
			case 'posttype':

				$html.= Core\HTML::dropdown( WordPress\PostType::get( 2, [ 'show_ui' => TRUE ] ), [
					'name'       => 'term-'.$field,
					'selected'   => '0',
					'none_title' => gEditorial\Settings::showOptionNone(),
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
					'class' => [ 'ptitle', 'code-text' ],
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
			case 'establish':
			case 'abolish':

				$html.= Core\HTML::tag( 'input', [
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => '',
					'class' => [ 'ptitle', 'code-text' ],
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
					'style' => 'width:100%;',
					'class' => [ 'ptitle', 'code-text' ],
				] );

				break;

			case 'label':
			case 'plural':
			case 'overwrite':
			case 'fullname':
			case 'tagline':
			case 'subtitle':
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
					'title'  => sprintf(
						/* translators: `%s`: meta title */
						_x( 'Meta: %s', 'Adminbar', 'geditorial-terms' ),
						$this->get_string( $field, $term->taxonomy, 'titles', $field )
					),
				];

				$child = [
					'id'     => $this->classs( $field, 'html' ),
					'parent' => $node['id'],
				];

				$metakey  = $this->get_supported_metakey( $field, $term->taxonomy );
				$metatype = $this->get_supported_field_metatype( $field, $term->taxonomy );

				switch ( $metatype ) {

					case 'order':

						$node['title'].= ': '.gEditorial\Helper::htmlOrder( get_term_meta( $term->term_id, $metakey, TRUE ) );
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
					case 'subtitle':
					case 'venue':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.WordPress\Strings::prepTitle( $meta );
						else
							$node['title'].= ': '.gEditorial\Plugin::na();

					break;
					case 'contact':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.gEditorial\Helper::prepContact( $meta );
						else
							$node['title'].= ': '.gEditorial\Plugin::na();

						break;

					case 'date':
					case 'born':
					case 'dead':
					case 'establish':
					case 'abolish':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.gEditorial\Datetime::prepForDisplay( trim( $meta ), 'Y/m/d', $this->default_calendar() );
						else
							$node['title'].= ': '.gEditorial\Plugin::na();

						break;

					case 'datetime':
					case 'datestart':
					case 'dateend':

						if ( $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
							$node['title'].= ': '.gEditorial\Datetime::prepForDisplay( trim( $meta ), 'Y/m/d H:i', $this->default_calendar() );
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

	public function display_media_states( $states, $post )
	{
		// NOTE: in some cases we assign featured image of a post-type into paired taxonomy
		if ( $post->post_parent )
			return $states;

		if ( ! $term_id = WordPress\Taxonomy::getIDbyMeta( $this->get_supported_metakey( 'image' ), $post->ID ) )
			return $states;

		if ( WordPress\IsIt::ajax() ) {

			// NOTE: on media modal HTML tags will escaped

			$states[] = sprintf(
				/* translators: `%s`: term name */
				_x( 'Term Image for “%s”', 'Media State', 'geditorial-terms' ),
				WordPress\Term::title( (int) $term_id, FALSE ) ?: _x( 'Missing Term', 'Media State', 'geditorial-terms' )
			);

		} else {

			$states[] = sprintf(
				/* translators: `%s`: term link */
				_x( 'Term Image for &ldquo;%s&rdquo;', 'Media State', 'geditorial-terms' ),
				WordPress\Term::htmlLink( (int) $term_id ) ?: _x( 'Missing Term', 'Media State', 'geditorial-terms' )
			);
		}

		return $states;
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

	// NOTE: converts image attachment id to full URL
	public function taxonomy_export_term_meta_data( $data, $metakey, $taxonomy, $term )
	{
		if ( $metakey !== $this->get_supported_metakey( 'image', $taxonomy ) )
			return $data;

		// if ( ! in_array( 'image', $this->get_supported( $taxonomy ) ) )
		// 	return $data;

		if ( ! $img = wp_get_attachment_image_src( (int) $data, 'full' ) )
			return $data;

		return empty( $img[0] ) ? $data : $img[0];
	}

	// TODO: check access
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
				++$count;
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
				++$count;
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

	public function terms_search_append_meta_frontend( $meta, $search, $taxonomies, $args )
	{
		if ( empty( $taxonomies ) )
			return $meta;

		foreach ( $taxonomies as $taxonomy ) {

			foreach ( $this->get_supported( $taxonomy ) as $field ) {

				if ( ! $metakey = $this->get_supported_metakey( $field, $taxonomy ) )
					continue;

				switch ( $field ) {

					// TODO: check discovery before special fields: `latlng`/`identity`

					case 'fullname':
					case 'tagline':
					case 'subtitle':
					case 'contact':
					case 'venue':
					case 'label':
					case 'code':
					case 'barcode':

						// NOTE: search must *not* be exact!
						$meta[] = [ $metakey, $search, TRUE ];
				}
			}
		}

		return $meta;
	}

	/**
	 * Changes `get_terms()` defaults for supported taxonomies to order by meta.
	 * @source `wc_change_get_terms_defaults()`
	 *
	 * @param array $defaults
	 * @param array $taxonomies
	 * @return array
	 */
	public function get_terms_defaults_ordering( $defaults, $taxonomies )
	{
		if ( is_array( $taxonomies ) && count( $taxonomies ) > 1 )
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

		// Put back valid `orderby` values.
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
	 * @param array $clauses
	 * @param array $taxonomies
	 * @param array $args
	 * @return array
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

	/**
	 * Filters proper field as title for terms on the `alphabet` short-code.
	 *
	 * @param array $meta-keys
	 * @param array $taxonomies
	 * @return false|string
	 */
	public function alphabet_term_title_metakeys( $metakeys, $taxonomies )
	{
		foreach ( $taxonomies as $taxonomy ) {

			if ( ! empty( $metakeys[$taxonomy] ) )
				continue;

			$supported = $this->get_supported( $taxonomy );

			if ( in_array( 'tagline', $supported, TRUE ) )
				$metakeys[$taxonomy] = $this->get_supported_metakey( 'tagline', $taxonomy );

			else if ( in_array( 'subtitle', $supported, TRUE ) )
				$metakeys[$taxonomy] = $this->get_supported_metakey( 'subtitle', $taxonomy );
		}

		return $metakeys;
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

		if ( $src = WordPress\Media::getAttachmentSrc( $attachment, [ 45, 72 ], FALSE ) )
			return $src;

		return $data;
	}

	public function term_intro_title_suffix( $suffix, $term, $desc, $args, $module )
	{
		if ( ! $taxonomy = WordPress\Term::taxonomy( $term ) )
			return $suffix;

		$supported = $this->get_supported( $taxonomy );

		if ( in_array( 'born', $supported, TRUE )
			&& in_array( 'dead', $supported, TRUE ) ) {

			$html = gEditorial\Datetime::prepBornDeadForDisplay(
				get_term_meta( $term->term_id, $this->get_supported_metakey( 'born', $taxonomy ), TRUE ),
				get_term_meta( $term->term_id, $this->get_supported_metakey( 'dead', $taxonomy ), TRUE ),
				NULL,
				$this->default_calendar()
			);

			return $html ? sprintf( '%s %s', $suffix, $html ) : $suffix;

		} else if ( in_array( 'establish', $supported, TRUE )
			&& in_array( 'abolish', $supported, TRUE ) ) {

			$html = gEditorial\Datetime::prepBornDeadForDisplay(
				get_term_meta( $term->term_id, $this->get_supported_metakey( 'establish', $taxonomy ), TRUE ),
				get_term_meta( $term->term_id, $this->get_supported_metakey( 'abolish', $taxonomy ), TRUE ),
				NULL,
				$this->default_calendar()
			);

			return $html ? sprintf( '%s %s', $suffix, $html ) : $suffix;
		}

		return $suffix;
	}

	public function term_intro_description_before( $term, $desc, $image, $args, $module )
	{
		if ( ! $desc && ! $image && empty( $args['heading'] ) )
			return;

		if ( ! $taxonomy = WordPress\Term::taxonomy( $term ) )
			return;

		$supported = $this->get_supported( $taxonomy );

		if ( in_array( 'subtitle', $supported, TRUE ) )
			echo Core\HTML::wrap( get_term_meta( $term->term_id, $this->get_supported_metakey( 'subtitle', $taxonomy ), TRUE ) ?: '', '-term-subtitle' );
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
			echo gEditorial\Template::doMediaShortCode( get_term_meta( $term->term_id, $this->get_supported_metakey( 'embed', $taxonomy ), TRUE ) ?: '' );
	}

	private function _render_source_link( $term, $field = 'source' )
	{
		if ( ! $title = $this->get_setting_fallback( $field.'_link_title', _x( 'Source', 'Setting Default', 'geditorial-terms' ) ) )
			return;

		if ( $meta = get_term_meta( $term->term_id, $this->get_supported_metakey( $field, $term->taxonomy ), TRUE ) )
			echo Core\HTML::wrap( Core\HTML::link( $title, $meta, TRUE ), '-term-'.$field );
	}

	// NOTE: `timespan` only if taxonomy supported.
	public function calendars_sanitize_ical_context( $context, $target, $object )
	{
		if ( 'term' !== $target )
			return $context;

		if ( ! $taxonomy = WordPress\Term::taxonomy( $object ) )
			return $context;

		if ( ! $supported = $this->get_supported( $taxonomy ) )
			return $context;

		$fields = [
			'born',
			'dead',
			'establish',
			'abolish',
		];

		if ( Core\Arraay::exists( $fields, $supported ) )
			return Services\Calendars::ICAL_TIMESPAN_CONTEXT;

		return $context;
	}

	// NOTE: `timespan` only if terms has data.
	public function calendars_term_link( $url, $term, $context )
	{
		if ( Services\Calendars::ICAL_TIMESPAN_CONTEXT !== $context )
			return $url;

		if ( ! $taxonomy = WordPress\Term::taxonomy( $term ) )
			return $url;

		if ( ! $supported = $this->get_supported( $taxonomy ) )
			return FALSE;

		$fields = [
			'born',
			'establish',
			'dead',
			'abolish',
		];

		if ( ! Core\Arraay::exists( $fields, $supported ) )
			return FALSE;

		foreach ( $fields as $field )
			if ( get_term_meta( $term->term_id, $this->get_supported_metakey( $field, $taxonomy ), TRUE ) )
				return $url;

		return FALSE;
	}

	// NOTE: applies only if the context is `timespan` e.g. `?ical=timespan`
	public function calendars_term_events( $null, $term, $context )
	{
		if ( $null || Services\Calendars::ICAL_TIMESPAN_CONTEXT !== $context )
			return $null;

		if ( ! $taxonomy = WordPress\Term::taxonomy( $term ) )
			return $null;

		if ( ! $supported = $this->get_supported( $taxonomy ) )
			return $null;

		$events  = [];
		$default = $this->default_calendar();

		if ( in_array( 'born', $supported, TRUE )
			&& in_array( 'dead', $supported, TRUE ) ) {

			foreach ( [ 'born', 'dead' ] as $field )
				if ( $event = $this->_calendars_term_event( $term, $field, $context, $default ) )
					$events[] = $event;

			return $events;

		} else if ( in_array( 'establish', $supported, TRUE )
			&& in_array( 'abolish', $supported, TRUE ) ) {

			foreach ( [ 'establish', 'abolish' ] as $field )
				if ( $event = $this->_calendars_term_event( $term, $field, $context, $default ) )
					$events[] = $event;

			return $events;
		}

		return $null;
	}

	private function _calendars_term_event( $term, $field, $context, $default_calendar = NULL )
	{
		$date = Services\TaxonomyFields::getFieldDate(
			$field,
			$term->term_id,
			'terms', // NOTE: better to be hard-coded
			FALSE,
			FALSE,
			$default_calendar ?? $this->default_calendar()
		);

		if ( $date )
			return Services\Calendars::getTermEvent(
				$term,
				$context,
				$date,
				sprintf( '%s (%s)',
					'{{title}}', // NOTE: see `WordPress\Term::summary()`
					$this->get_supported_field_title( $field, $term->taxonomy, $term )
				)
			);

		return FALSE;
	}
}
