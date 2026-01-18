<?php namespace geminorum\gEditorial\Modules\Identified;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Identified extends gEditorial\Module
{
	use Internals\PostTypeFields;
	use Internals\RestAPI;

	public static function module()
	{
		return [
			'name'     => 'identified',
			'title'    => _x( 'Identified', 'Modules: Identified', 'geditorial-admin' ),
			'desc'     => _x( 'Content Identification Management', 'Modules: Identified', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'fingerprint' ],
			'access'   => 'beta',
			'keywords' => [
				'identifier',
				'has-public-api',
			],
		];
	}

	protected function get_global_settings()
	{
		$settings  = [];
		$posttypes = $this->list_posttypes();
		$types     = $this->get_strings( 'types', 'fields' );

		$settings['posttypes_option'] = 'posttypes_option';

		foreach ( $posttypes as $posttype_name => $posttype_label ) {

			$default_metakey = $this->filters( 'default_posttype_identifier_metakey', '', $posttype_name );

			$settings['_posttypes'][] = [
				'field' => $posttype_name.'_posttype_metakey',
				'type'  => 'text',
				'title' => sprintf(
					/* translators: `%s`: supported object label */
					_x( 'Identifier Meta-key for %s', 'Setting Title', 'geditorial-identified' ),
					Core\HTML::tag( 'i', $posttype_label )
				),
				'description' => _x( 'Defines identifier meta-key for the post-type.', 'Setting Description', 'geditorial-identified' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'after'       => gEditorial\Settings::fieldAfterText( $default_metakey, 'code' ),
				'placeholder' => $default_metakey,
				'default'     => $default_metakey,
			];

			$settings['_posttypes'][] = [
				'field' => $posttype_name.'_posttype_type',
				'type'  => 'select',
				'title' => sprintf(
					/* translators: `%s`: supported object label */
					_x( 'Identifier Type for %s', 'Setting Title', 'geditorial-identified' ),
					Core\HTML::tag( 'i', $posttype_label )
				),
				'description'  => _x( 'Defines available identifier type for the post-type.', 'Setting Description', 'geditorial-identified' ),
				'string_empty' => _x( 'There are no identifier types available!', 'Setting', 'geditorial-identified' ),
				'default'      => $this->filters( 'default_posttype_identifier_type', 'code', $posttype_name, $types ),
				'values'       => $types ?: FALSE,
			];
		}

		$settings['_frontend'] = [
			[
				'field'       => 'adminbar_summary',
				'title'       => _x( 'Barcode Scanner', 'Setting Title', 'geditorial-identified' ),
				'description' => sprintf(
					/* translators: `%s`: application name */
					_x( 'Provides a link to %s mobile app for barcode scan of the queryable identifiers.', 'Setting Description', 'geditorial-identified' ),
					Core\HTML::code( 'BinaryEye' )
				),
			],
			'frontend_search' => [ _x( 'Adds results by Identifier information on front-end search.', 'Setting Description', 'geditorial-identified' ), TRUE ],
			[
				'field'       => 'queryable_types',
				'type'        => 'checkboxes-values',
				'title'       => _x( 'Queryable Types', 'Setting Title', 'geditorial-identified' ),
				'description' => _x( 'Appends end-points for Identifier types on front-end.', 'Setting Description', 'geditorial-identified' ),
				'values'      => $types ?: FALSE,
			],
		];

		foreach ( $this->get_setting( 'queryable_types', [] ) as $queryable ) {

			$default_template = $this->filters( 'default_type_notfound_template', '', $queryable );

			$settings['_frontend'][] = [
				'field' => $queryable.'_type_notfound_template',
				'type'  => 'text',
				'title' => sprintf(
					/* translators: `%s`: supported type label */
					_x( 'URL Template for %s', 'Setting Title', 'geditorial-identified' ),
					Core\HTML::tag( 'i', $types[$queryable] )
				),
				'description' => sprintf(
					/* translators: `%s`: token list placeholder */
					_x( 'Defines a not-found url template for the identifier type. Available tokens are %s.', 'Setting Description', 'geditorial-identified' ),
					WordPress\Strings::getJoined( [ Core\HTML::code( 'type' ), Core\HTML::code( 'data' ) ] )
				),
				'field_class' => [ 'regular-text', 'code-text' ],
				'after'       => gEditorial\Settings::fieldAfterText( $default_template, 'code' ),
				'placeholder' => $default_template,
				'default'     => $default_template,
			];
		}

		$settings['_frontend'][] = 'admin_rowactions';
		$settings['_supports'][] = 'restapi_restricted';

		$settings['_import'] = [
			'post_status',
			'comment_status',
			'add_audit_attribute' => [
				sprintf(
					/* translators: `%s`: audit attribute placeholder */
					_x( 'Appends %s audit attribute to each created item via identifier.', 'Setting Description', 'geditorial-identified' ),
					Core\HTML::code( $this->constant( 'term_newpost_by_identifier' ) )
				),
			],
		];

		return $settings;
	}

	protected function get_global_constants()
	{
		return [
			'restapi_attribute' => 'identifier',
			'restapi_namespace' => 'identified',

			'metakey_identifier_posttype' => 'identifier',
			'metakey_identifier_taxonomy' => 'identifier',

			'term_newpost_by_identifier'    => 'identified',
			'term_empty_identification'     => 'identification-empty',
			'term_duplicate_identification' => 'identification-duplicate',
			'term_invalid_identification'   => 'identification-invalid',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'fields' => [
				'types' => [

					/**
					 * - UPC: Universal Product Code: this is the primary GTIN in North America.
					 * - EAN/UCC: European Article Number: the major GTIN used outside of North America
					 * - JAN: the Japanese GTIN
					 * - ISBN: International Standard Book Number: a GTIN for books
					 */

					'code'     => _x( 'Code', 'Identifier Type', 'geditorial-identified' ),
					'gtin'     => _x( 'GTIN', 'Identifier Type', 'geditorial-identified' ),
					'isbn'     => _x( 'ISBN', 'Identifier Type', 'geditorial-identified' ),
					'issn'     => _x( 'ISSN', 'Identifier Type', 'geditorial-identified' ),
					'sku'      => _x( 'SKU', 'Identifier Type', 'geditorial-identified' ),
					'ddc'      => _x( 'DDC', 'Identifier Type', 'geditorial-identified' ),
					'vin'      => _x( 'VIN', 'Identifier Type', 'geditorial-identified' ),
					'plate'    => _x( 'Plate', 'Identifier Type', 'geditorial-identified' ),
					'tin'      => _x( 'TIN', 'Identifier Type', 'geditorial-identified' ),
					'ein'      => _x( 'EIN', 'Identifier Type', 'geditorial-identified' ),
					'mobile'   => _x( 'Mobile', 'Identifier Type', 'geditorial-identified' ),
					'email'    => _x( 'Email', 'Identifier Type', 'geditorial-identified' ),
					'identity' => _x( 'Identity', 'Identifier Type', 'geditorial-identified' ),
					'latlng'   => _x( 'Coordinates', 'Identifier Type', 'geditorial-identified' ),
				],
			],
			'descriptions' => [
				'code'     => _x( 'Arbitrary Identifier Code', 'Identifier Type Description', 'geditorial-identified' ),
				'gtin'     => _x( 'Global Trade Item Number', 'Identifier Type Description', 'geditorial-identified' ),
				'isbn'     => _x( 'International Standard Book Number', 'Identifier Type Description', 'geditorial-identified' ),
				'issn'     => _x( 'International Standard Serial Number', 'Identifier Type Description', 'geditorial-identified' ),
				'sku'      => _x( 'Stock Keeping Unit', 'Identifier Type Description', 'geditorial-identified' ),
				'ddc'      => _x( 'Dewey Decimal Classification', 'Identifier Type Description', 'geditorial-identified' ),
				'vin'      => _x( 'Vehicle Identification Number', 'Identifier Type Description', 'geditorial-identified' ),
				'plate'    => _x( 'Vehicle Registration Plate', 'Identifier Type Description', 'geditorial-identified' ),
				'tin'      => _x( 'Taxpayer Identification Number', 'Identifier Type Description', 'geditorial-identified' ),
				'ein'      => _x( 'Employer Identification Number', 'Identifier Type Description', 'geditorial-identified' ),
				'mobile'   => _x( 'Mobile Phone Number', 'Identifier Type Description', 'geditorial-identified' ),
				'email'    => _x( 'Electronic Mail', 'Identifier Type Description', 'geditorial-identified' ),
				'identity' => _x( 'National Identity Number', 'Identifier Type Description', 'geditorial-identified' ),
				'latlng'   => _x( 'Latitude and Longitude', 'Identifier Type Description', 'geditorial-identified' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['settings'] = [
			'post_types_after' => _x( 'Supports identifiers for the selected post-types.', 'Setting Description', 'geditorial-identified' ),
		];

		return $strings;
	}

	public function after_setup_theme()
	{
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$this->_init_queryable_types();

		if ( $this->get_setting( 'frontend_search', TRUE ) )
			$this->filter( 'posts_search_append_meta_frontend', 3, 8, FALSE, $this->base );

		$this->action( 'template_newpost_beforetitle', 6, 8, FALSE, $this->base );
		$this->filter( 'pairedrest_prepped_post', 3, 99, FALSE, $this->base );
		$this->filter( 'subcontent_provide_summary', 4, 8, FALSE, $this->base );
		$this->filter( 'linediscovery_data_for_post', 5, 8, FALSE, $this->base );
		$this->filter( 'searchselect_pre_query_posts', 3, 8, FALSE, $this->base );

		$this->filter_module( 'audit', 'auto_audit_save_post', 5 );
	}

	public function current_screen( $screen )
	{
		if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' === $screen->base ) {

				$this->_hook_not_found_posts( $screen->post_type );

				if ( $this->get_setting( 'admin_rowactions' ) )
					$this->filter( 'post_row_actions', 2 );

				Services\Barcodes::binaryEyeHeaderButton();

			} else if ( 'post' === $screen->base
				&& 'add' === $screen->action ) {

				$this->action( 'edit_form_after_title', 1, 1 );
			}
		}
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() )
			return;

		$types = $this->get_strings( 'types', 'fields' );

		foreach ( $this->get_setting( 'queryable_types', [] ) as $type ) {

			$supported = $this->_get_supported_by_identifier_type( $type );

			// TODO: check for reading cap!
			if ( ! count( $supported ) )
				continue;

			$nodes[] = [
				'parent' => 'top-secondary',
				'id'     => $this->classs( $type ),
				'href'   => Services\Barcodes::binaryEyeLink( $type, Core\URL::home() ),
				'title'  => Services\Icons::adminBarMarkup( 'camera' ),
				'meta'   => [
					'class' => Core\HTML::prepClass( $this->classs_base( 'adminbar', 'node', 'icononly' ), '-binary-eye', '-'.$type ),
					'title' => sprintf(
						/* translators: `%s`: identifier type */
						_x( 'Scan %s', 'Node Title', 'geditorial-identified' ),
						$types[$type]
					),
				],
			];
		}
	}

	public function setup_restapi()
	{
		$this->restapi_register_route( 'query', 'get', '(?P<type>.+)/(?P<code>[a-z0-9 .\-]+)' );
	}

	public function restapi_query_get_arguments()
	{
		return [
			'type' => [
				'required'    => TRUE,
				'description' => esc_html_x( 'The identifier type to process.', 'RestAPI: Arg Description', 'geditorial-identified' ),

				'validate_callback' => [ $this, 'restapi_query_get_type_validate_callback' ],
			],
			'code' => [
				'required'    => TRUE,
				'description' => esc_html_x( 'The identifier code to process.', 'RestAPI: Arg Description', 'geditorial-identified' ),

				'validate_callback' => [ $this, 'restapi_query_get_code_validate_callback' ],
			],
		];
	}

	public function restapi_query_get_type_validate_callback( $param, $request, $key )
	{
		if ( empty( $param ) )
			return Services\RestAPI::getErrorArgNotEmpty( $key );

		if ( ! array_key_exists( $param, $this->get_strings( 'types', 'fields' ) ) )
			return Services\RestAPI::getErrorInvalidData( NULL, NULL, [ $key => $param ] );

		return TRUE;
	}

	public function restapi_query_get_code_validate_callback( $param, $request, $key )
	{
		if ( empty( $param ) )
			return Services\RestAPI::getErrorArgNotEmpty( $key );

		return TRUE;
	}

	public function restapi_query_get_callback( $request )
	{
		$code = urldecode( $request['code'] );
		$type = urldecode( $request['type'] );

		$params = self::atts( [
			'context'  => NULL,
			'posttype' => '',
			'expect'   => '',
		], $request->get_query_params() );

		if ( ! empty( $params['posttype'] ) ) {

			if ( ! is_array( $params['posttype'] ) )
				$params['posttype'] = explode( ',', $params['posttype'] );

			$params['posttype'] = Core\Arraay::keepByValue( $params['posttype'], $this->posttypes() );
		}

		if ( ! $post = $this->get_identified( $code, $type, $params['posttype'] ?: NULL ) )
			return Services\RestAPI::getErrorNotFound( NULL, NULL, [ 'code' => $code, 'type' => $type ] );

		$response = NULL;

		switch ( $params['expect'] ) {

			case 'raw':

				$response = $post->ID;
				break;

			case 'id':

				$response = [ 'id' => $post->ID ];
				break;

			case 'post':
			default:

				if ( ! WordPress\Post::can( $post, 'read_post' ) )
					$response = Services\RestAPI::getErrorForbidden();

				else if ( $data = Services\RestAPI::getPostResponse( $post ) )
					$response = $data;
		}

		return rest_ensure_response( $response ?? Services\RestAPI::getErrorSomethingIsWrong() );
	}

	private function _get_supported_by_identifier_type( $type )
	{
		$list = [];

		foreach ( $this->posttypes() as $supported )
			if ( $type === $this->_get_posttype_identifier_type( $supported ) )
				$list[$supported] = $this->_get_posttype_identifier_metakey( $supported );

		return $list;
	}

	private function _get_posttype_identifier_type( $posttype )
	{
		if ( $setting = $this->get_setting( sprintf( '%s_posttype_type', $posttype ) ) )
			return $setting;

		$types = $this->get_strings( 'types', 'fields' );

		if ( $default = $this->filters( 'default_posttype_identifier_type', '', $posttype, $types ) )
			return $default;

		return 'code';
	}

	private function _get_posttype_identifier_metakey( $posttype )
	{
		if ( $setting = $this->get_setting( $posttype.'_posttype_identifier_metakey' ) )
			return $setting;

		if ( $default = $this->filters( 'default_posttype_identifier_metakey', '', $posttype ) )
			return $default;

		return $this->constant( 'metakey_identifier_posttype' );
	}

	public function edit_form_after_title( $post )
	{
		$this->template_newpost_beforetitle( $post->post_type, $post, NULL, FALSE, NULL, [] );
	}

	public function template_newpost_beforetitle( $posttype, $post, $target, $linked, $status, $meta )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return;

		$already = FALSE;
		$type    = $this->_get_posttype_identifier_type( $posttype );
		$metakey = $this->_get_posttype_identifier_metakey( $posttype );

		if ( $sanitized = $this->sanitize_identifier( self::req( $type ), $type ) )
			$already = $this->_get_post_identified( $sanitized, $metakey, $posttype );

		else if ( $sanitized = $this->sanitize_identifier( self::req( $metakey ), $type ) )
			$already = $this->_get_post_identified( $sanitized, $metakey, $posttype );

		else if ( $sanitized = $this->sanitize_identifier( self::req( 'meta', FALSE, $metakey ), $type ) )
			$already = $this->_get_post_identified( $sanitized, $metakey, $posttype );

		if ( ! $already )
			return;

		echo $this->wrap_open( '-already-identified' );

			Core\HTML::desc( sprintf(
				/* translators: `%s`: supported object label */
				_x( 'Warning: Already an entry identified: %s', 'Message', 'geditorial-identified' ),
				WordPress\Post::fullTitle( $already, 'edit' )
			), TRUE, '-already alert alert-warning' );

		echo '</div>';
	}

	public function pairedrest_prepped_post( $prepped, $post, $parent )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $prepped;

		return array_merge( $prepped, [
			$this->constant( 'restapi_attribute' ) => $this->get_identifier( $post ),
		] );
	}

	public function get_identifier( $post, $metakey = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( is_null( $metakey ) )
			$metakey = $this->_get_posttype_identifier_metakey( $post->post_type );

		return $this->filters( 'get_posttype_identifier',
			get_post_meta( $post->ID, $metakey, TRUE ),
			$post,
			$metakey
		);
	}

	public function get_identifier_link( $data, $type = 'code', $extra = [] )
	{
		return get_option( 'permalink_structure' )
			? add_query_arg( $extra, sprintf( '%s/%s/%s', Core\URL::untrail( get_bloginfo( 'url' ) ), $type, $data ) )
			: add_query_arg( array_merge( [ $type => $data ], $extra ), get_bloginfo( 'url' ) );
	}

	private function _get_posttype_identifier_possible_keys( $posttype, $extra = [] )
	{
		$type    = $this->_get_posttype_identifier_type( $posttype );
		$metakey = $this->_get_posttype_identifier_metakey( $posttype );

		$keys = [
			Core\Text::stripPrefix( $metakey, '_meta_' )  => $type,
			Core\Text::stripPrefix( $metakey, '_units_' ) => $type,

			$metakey     => $type,
			'identifier' => 'code',
			$posttype    => 'code',

			WordPress\PostType::object( $posttype )->label => 'code',

			_x( 'Identifier', 'Possible Identifier Key', 'geditorial-identified' ) => 'code',
		];

		$list = $this->filters( 'possible_keys_for_identifier',
			array_merge( $keys, $extra ),
			$posttype
		);

		return array_change_key_case( $list, CASE_LOWER );
	}

	public function sanitize_identifier( $value, $type = 'code', $post = FALSE, $strict = FALSE )
	{
		if ( WordPress\Strings::isEmpty( $value ) )
			return FALSE;

		switch ( $type ) {

			case 'isbn':

				// $sanitized = Core\ISBN::convertToISBN13( Core\ISBN::sanitize( $value ) );
				$sanitized = Core\ISBN::discovery( $value );

				if ( $strict && ! Core\ISBN::validate( $sanitized ) )
					$sanitized = '';

				break;

			case 'gtin':

				// TODO: research this?!
				$sanitized = Core\ISBN::sanitize( $value );
				break;

			case 'identity':

				// NOTE: this is strict
				$sanitized = Core\Validation::sanitizeIdentityNumber( $value );
				break;

			case 'code':
			default:

				$sanitized = Core\Number::translate( trim( $value ) );
		}

		return $this->filters( 'sanitize_identifier', $sanitized, $value, $type, $post, $strict );
	}

	public function subcontent_provide_summary( $data, $item, $parent, $context )
	{
		if ( ! is_null( $data ) )
			return $data;

		if ( ! empty( $item['identity'] ) ) {

			if ( ! $identifier = $this->sanitize_identifier( $item['identity'], 'identity' ) )
				return $data;

			$supported = $this->_get_supported_by_identifier_type( 'identity' );

			foreach ( $supported as $posttype => $metakey )
				if ( $matches = WordPress\PostType::getIDbyMeta( $metakey, $identifier, FALSE ) )
					foreach ( $matches as $match )
						if ( array_key_exists( get_post_type( intval( $match ) ), $supported ) )
							return WordPress\Post::summary( $match );
		}

		return $data;
	}

	public function linediscovery_data_for_post( $discovered, $row, $posttypes, $insert, $raw )
	{
		if ( ! is_null( $discovered ) )
			return $discovered;

		$supported  = $this->posttypes();
		$identifier = FALSE;

		if ( ! Core\Arraay::exists( $supported, (array) $posttypes ) )
			return $discovered;

		foreach ( (array) $posttypes as $posttype ) {

			if ( ! in_array( $posttype, $supported, TRUE ) )
				continue;

			$metakey = $this->_get_posttype_identifier_metakey( $posttype );
			$keys    = $this->_get_posttype_identifier_possible_keys( $posttype );

			foreach ( $keys as $key => $key_type ) {

				if ( ! array_key_exists( $key, $row ) )
					continue;

				if ( $identifier = $this->sanitize_identifier( $row[$key], $key_type ) )
					break 2;
			}
		}

		if ( ! $identifier )
			return NULL;

		if ( $matches = WordPress\PostType::getIDbyMeta( $metakey, $identifier, FALSE ) )
			foreach ( $matches as $match )
				if ( $posttype === get_post_type( intval( $match ) ) )
					return intval( $match );

		if ( ! WordPress\PostType::can( $posttype, 'create_posts' ) )
			return new \WP_Error( 'create_noaccess', gEditorial\Plugin::denied( FALSE ) );

		unset( $row[$key] ); // avoid passing into meta action

		return $insert
			? $this->_insert_post_by_identifier( $posttype, $identifier, $metakey, $row )
			: TRUE;
	}

	private function _insert_post_by_identifier( $posttype, $identifier, $metakey, $raw = [] )
	{
		$title = empty( $raw['post_title'] )
			? sprintf( '[%s]', $identifier )
			: $raw['post_title'];

		$data = [
			'post_type'      => $posttype,
			'post_title'     => $this->filters( 'insert_target_name', $title, $raw ),
			'post_status'    => $this->get_setting( 'post_status', 'pending' ),
			'comment_status' => $this->get_setting( 'comment_status', 'closed' ),
			'meta_input'     => [
				$metakey => $identifier,
			],
		];

		$post_id = wp_insert_post(
			$this->filters( 'insert_target_post',
				$data,
				$identifier,
				$raw
			),
			FALSE,
			FALSE
		);

		if ( self::isError( $post_id ) )
			return $post_id;

		if ( ! $post_id )
			return new \WP_Error( 'cannot_create_post', gEditorial\Plugin::wrong( FALSE ) );

		if ( $this->get_setting( 'add_audit_attribute' ) )
			Services\Modulation::setTaxonomyAudit( $post_id, $this->constant( 'term_newpost_by_identifier' ) );

		// NOTE: `$override` is false because it's new post
		$this->posttypefields__do_action_import_data( $post_id, $raw, FALSE, FALSE, 'meta' );

		// @REF: https://make.wordpress.org/core/2020/11/20/new-action-wp_after_insert_post-in-wordpress-5-6/
		wp_after_insert_post( $post_id, FALSE, NULL );

		return $post_id;
	}

	public function searchselect_pre_query_posts( $null, $args, $queried )
	{
		if ( ! is_null( $null ) )
			return $null;

		if ( empty( $queried['posttype'] ) || empty( $args['s'] ) )
			return $null;

		$supported  = $this->posttypes();
		$identifier = FALSE;

		if ( ! Core\Arraay::exists( $supported, (array) $queried['posttype'] ) )
			return $null;

		foreach ( (array) $queried['posttype'] as $posttype ) {

			if ( ! in_array( $posttype, $supported, TRUE ) )
				continue;

			$type    = $this->_get_posttype_identifier_type( $posttype );
			$metakey = $this->_get_posttype_identifier_metakey( $posttype );

			if ( $identifier = $this->sanitize_identifier( $args['s'], $type ) ) {

				if ( $matches = WordPress\PostType::getIDbyMeta( $metakey, $identifier, FALSE ) )
					foreach ( $matches as $match )
						if ( $posttype === get_post_type( intval( $match ) ) )
							return intval( $match );
			}
		}

		return $null;
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Services\Modulation::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_identification' )     => _x( 'Empty Identification', 'Default Term: Audit', 'geditorial-identified' ),
			$this->constant( 'term_duplicate_identification' ) => _x( 'Duplicate Identification', 'Default Term: Audit', 'geditorial-identified' ),
			$this->constant( 'term_invalid_identification' )   => _x( 'Invalid Identification', 'Default Term: Audit', 'geditorial-identified' ),
		] ) : $terms;
	}

	public function audit_auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $terms;

		$type       = $this->_get_posttype_identifier_type( $post->post_type );
		$metakey    = $this->_get_posttype_identifier_metakey( $post->post_type );
		$identifier = get_post_meta( $post->ID, $metakey, TRUE );

		if ( $exists = term_exists( $this->constant( 'term_empty_identification' ), $taxonomy ) ) {

			if ( $identifier )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

			else
				$terms[] = $exists['term_id'];
		}

		if ( $exists = term_exists( $this->constant( 'term_invalid_identification' ), $taxonomy ) ) {

			if ( ! $identifier || $this->sanitize_identifier( $identifier, $type, $post ) )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

			else
				$terms[] = $exists['term_id'];
		}

		if ( $exists = term_exists( $this->constant( 'term_duplicate_identification' ), $taxonomy ) ) {

			$matches = WordPress\PostType::getIDbyMeta( $metakey, $identifier, FALSE );

			if ( ! $identifier || count( $matches ) < 2 )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

			else
				$terms[] = $exists['term_id'];
		}

		return $terms;
	}

	public function get_identified( $code, $type, $posttypes = NULL )
	{
		foreach ( $posttypes ?? $this->posttypes() as $posttype ) {

			if ( $type !== $this->_get_posttype_identifier_type( $posttype ) )
				continue;

			if ( ! $sanitized = $this->sanitize_identifier( $code, $type ) )
				continue;

			$metakey = $this->_get_posttype_identifier_metakey( $posttype );

			if ( $matched = $this->_get_post_identified( $sanitized, $metakey, $posttype ) )
				return $matched;
		}

		return FALSE;
	}

	private function _get_post_identified( $code, $metakey, $posttype = NULL )
	{
		if ( ! $matches = WordPress\PostType::getIDbyMeta( $metakey, $code, FALSE ) )
			return FALSE;

		foreach ( $matches as $match ) {

			if ( ! $post = WordPress\Post::get( intval( $match ) ) )
				continue;

			if ( is_null( $posttype ) )
				return $post;

			if ( $posttype === WordPress\Post::type( $post ) )
				return $post;
		}

		return FALSE;
	}

	private function _hook_not_found_posts( $posttype )
	{
		if ( ! WordPress\PostType::can( $posttype, 'create_posts' ) )
			return FALSE;

		if ( ! $criteria = self::req( 's' ) )
			return FALSE;

		add_filter( 'the_posts',
			function ( $posts, $query ) use ( $posttype, $criteria ) {

				if ( ! $query->is_main_query() )
					return $posts;

				// already founded!
				if ( count( $posts ) )
					return $posts;

				if ( ! $type = $this->_get_posttype_identifier_type( $posttype ) )
					return $posts;

				if ( ! $sanitized = $this->sanitize_identifier( $criteria, $type ) )
					return $posts;

				if ( ! $metakey = $this->_get_posttype_identifier_metakey( $posttype ) )
					return $posts;

				Services\HeaderButtons::register( $this->key, [
					'link' => WordPress\PostType::newLink( $posttype, [ $metakey => $sanitized ] ),
					'text' => sprintf(
						/* translators: `%1$s`: add new label, `%2$s`: identifier code */
						_x( '%1$s with %2$s', 'Header Button', 'geditorial-identified' ),
						Services\CustomPostType::getLabel( $posttype, 'add_new' ),
						Core\HTML::code( $sanitized )
					),

					'hide_in_search' => FALSE,
				] );

				return $posts;
			}, 999, 2 );
	}

	private function _search_criteria_discovery( $search, $type )
	{
		switch ( $type ) {
			case 'gtin'    : return Core\ISBN::discovery( $search );
			case 'isbn'    : return Core\ISBN::discovery( $search );
			case 'mobile'  : return Core\Mobile::sanitize( $search );
			case 'email'   : return Core\Email::sanitize( $search );
			case 'identity': return Core\Validation::sanitizeIdentityNumber( $search );
			case 'vin'     : return Core\Validation::sanitizeVIN( $search );
			case 'code'    : return Core\Number::translate( Core\Text::trim( $search ) );
		}

		return $search;
	}

	public function posts_search_append_meta_frontend( $meta, $search, $queried )
	{
		if ( 'any' === $queried )
			$posttypes = $this->posttypes();

		else if ( is_array( $queried ) )
			$posttypes = $queried;

		else if ( ! self::empty( $queried ) )
			$posttypes = WordPress\Strings::getSeparated( $queried );

		else
			return $meta;

		foreach ( $posttypes as $posttype ) {

			if ( ! $this->posttype_supported( $posttype ) )
				continue;

			if ( ! WordPress\PostType::viewable( $posttype ) )
				continue;

			if ( ! $metakey = $this->_get_posttype_identifier_metakey( $posttype ) )
				continue;

			$type = $this->_get_posttype_identifier_type( $posttype );

			if ( ! $criteria = $this->_search_criteria_discovery( $search, $type ) )
				continue;

			$meta[] = [ $metakey, $criteria ];
		}

		return $meta;
	}

	// @REF: https://gist.github.com/carlodaniele/1ca4110fa06902123349a0651d454057
	private function _init_queryable_types()
	{
		$queryable = FALSE;

		foreach ( $this->get_setting( 'queryable_types', [] ) as $type ) {

			$supported = $this->_get_supported_by_identifier_type( $type );

			if ( ! count( $supported ) )
				continue;

			$this->filter_append( 'query_vars', $type );

			add_rewrite_tag( '%'.$type.'%', '([^&]+)' );
			add_rewrite_rule( '^'.$type.'/([^/]*)/?', 'index.php?'.$type.'=$matches[1]', 'top' );

			foreach ( $supported as $posttype => $metakey )
				add_rewrite_rule(
					sprintf( '^%s/%s/([^/]*)/?', $posttype, $type ),
					sprintf( 'index.php?%s=$matches[1]&post_type=%s', $type, $posttype ),
					'top'
				);

			$queryable = TRUE;
		}

		if ( ! $queryable || is_admin() )
			return;

		$this->action( 'pre_get_posts', 1, 10, 'queryable_types' );
		$this->action( 'template_redirect', 0, 9, 'queryable_types' );
		$this->filter_self( 'is_post_viewable', 2, 10, 'queryable_types' );
	}

	public function pre_get_posts_queryable_types( &$query )
	{
		if ( $query->is_main_query() || ! $query->is_post_type_archive() )
			return;

		if ( ! $types = $this->get_setting( 'queryable_types', [] ) )
			return;

		foreach ( $this->posttypes() as $posttype ) {

			if ( ! $query->is_post_type_archive( $posttype ) )
				continue;

			if ( ! $type = $this->_get_posttype_identifier_type( $posttype ) )
				continue;

			if ( ! in_array( $type, $types, TRUE ) )
				continue;

			if ( ! $criteria = $query->get( $type, FALSE ) )
				continue;

			if ( ! $sanitized = $this->sanitize_identifier( $criteria, $type ) )
				continue;

			if ( ! $metakey = $this->_get_posttype_identifier_metakey( $posttype ) )
				continue;

			$query->set( 'meta_key', $metakey );
			$query->set( 'meta_value', $sanitized );
			// $query->set( 'meta_compare', 'LIKE' );

			break;
		}
	}

	public function template_redirect_queryable_types()
	{
		if ( is_home() || is_404() ) {

			foreach ( $this->get_setting( 'queryable_types', [] ) as $type ) {

				if ( ! $criteria = get_query_var( $type ) )
					continue;

				if ( ! $sanitized = $this->sanitize_identifier( $criteria, $type ) )
					continue;

				$supported = $this->_get_supported_by_identifier_type( $type );

				foreach ( $supported as $posttype => $metakey ) {

					if ( ! $post_id = WordPress\PostType::getIDbyMeta( $metakey, $sanitized ) )
						continue;

					if ( ! $post = WordPress\Post::get( $post_id ) )
						continue;

					if ( $post->post_type !== $posttype )
						continue;

					if ( ! $this->is_post_viewable( $post ) )
						continue;

					if ( ! $link = WordPress\Post::link( $post, FALSE, WordPress\Status::acceptable( $post->post_type ) ) )
						continue;

					WordPress\Redirect::doWP( $link, 302 );
				}

				if ( $tokenized = $this->_get_url_for_identifier_notfound( $type, $sanitized, $supported ) )
					WordPress\Redirect::doWP( $tokenized, 307 );

				$this->actions( 'identifier_notfound', $type, $sanitized, $supported );

				WordPress\Theme::set404();
			}

		} else if ( is_search() && ! have_posts() ) {

			// avoid core filtering
			if ( ! $criteria = trim( get_query_var( 's' ) ) )
				return;

			foreach ( $this->get_setting( 'queryable_types', [] ) as $type ) {

				if ( ! $sanitized = $this->sanitize_identifier( $criteria, $type, FALSE, TRUE ) )
					continue;

				$supported = $this->_get_supported_by_identifier_type( $type );

				foreach ( $supported as $posttype => $metakey ) {

					if ( ! $post_id = WordPress\PostType::getIDbyMeta( $metakey, $sanitized ) )
						continue;

					if ( ! $post = WordPress\Post::get( $post_id ) )
						continue;

					if ( $post->post_type !== $posttype )
						continue;

					if ( ! $this->is_post_viewable( $post ) )
						continue;

					if ( ! $link = WordPress\Post::link( $post, FALSE, WordPress\Status::acceptable( $post->post_type ) ) )
						continue;

					WordPress\Redirect::doWP( $link, 302 );
				}

				if ( $tokenized = $this->_get_url_for_identifier_notfound( $type, $sanitized, $supported ) )
					WordPress\Redirect::doWP( $tokenized, 307 );

				$this->actions( 'identifier_notfound', $type, $sanitized, $supported );
			}
		}
	}

	public function is_post_viewable_queryable_types( $viewable, $post )
	{
		if ( $viewable )
			return $viewable;

		// The type is not viewable so letting go!
		if ( ! WordPress\PostType::viewable( $post->post_type ) )
			return $viewable;

		return WordPress\Post::can( $post, 'read_post' );
	}

	private function _get_url_for_identifier_notfound( $type, $data, $supported = [] )
	{
		if ( ! is_user_logged_in() )
			return FALSE;

		if ( ! $template = $this->get_setting( sprintf( '%s_type_notfound_template', $type ) ) )
			return FALSE;

		$tokens = [
			'type' => $type,
			'data' => $data,
		];

		return $this->filters( 'identifier_notfound_template',
			Core\Text::replaceTokens( $template, $tokens ),
			$type,
			$data,
			$supported
		);
	}

	public function post_row_actions( $actions, $post )
	{
		if ( ! $this->is_post_viewable( $post ) )
			return $actions;

		$type = $this->_get_posttype_identifier_type( $post->post_type );

		if ( ! $this->in_setting( $type, 'queryable_types' ) )
			return $actions;

		if ( ! $identifier = $this->get_identifier( $post ) )
			return $actions;

		if ( ! $link = $this->get_identifier_link( $identifier, $type ) )
			return $actions;

		$label = $this->get_string( $type, 'types', 'fields', $type );

		return Core\Arraay::insert( $actions, [
			$this->classs() => Core\HTML::tag( 'a', [
				'href'   => $link,
				'class'  => '-identifier-link',
				'target' => '_blank',
				'title'  => sprintf(
					/* translators: `%1$s`: identifier type label, `%2$s`: posttype singular label */
					_x( '%1$s Link to this %2$s', 'Title Attr', 'geditorial-identified' ),
					$label,
					Services\CustomPostType::getLabel( $post->post_type, 'singular_name' )
				),
			], $label ),
		], 'view', 'after' );
	}
}
