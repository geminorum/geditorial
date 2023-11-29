<?php namespace geminorum\gEditorial\Modules\Identified;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

class Identified extends gEditorial\Module
{
	use Internals\PostTypeFields;
	use Internals\RestAPI;

	public static function module()
	{
		return [
			'name'   => 'identified',
			'title'  => _x( 'Identified', 'Modules: Identified', 'geditorial-admin' ),
			'desc'   => _x( 'Content Identification Management', 'Modules: Identified', 'geditorial-admin' ),
			'icon'   => [ 'misc-32', 'barcode' ],
			'access' => 'beta',
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
				'field'       => $posttype_name.'_posttype_metakey',
				'type'        => 'text',
				/* translators: %s: supported object label */
				'title'       => sprintf( _x( 'Identifier Meta-key for %s', 'Setting Title', 'geditorial-identified' ), '<i>'.$posttype_label.'</i>' ),
				'description' => _x( 'Defines identifier meta-key for the post-type.', 'Setting Description', 'geditorial-identified' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'after'       => Settings::fieldAfterText( $default_metakey, 'code' ),
				'placeholder' => $default_metakey,
				'default'     => $default_metakey,
			];

			$settings['_posttypes'][] = [
				'field'        => $posttype_name.'_posttype_type',
				'type'         => 'select',
				/* translators: %s: supported object label */
				'title'        => sprintf( _x( 'Identifier Type for %s', 'Setting Title', 'geditorial-identified' ), '<i>'.$posttype_label.'</i>' ),
				'description'  => _x( 'Defines available identifier type for the post-type.', 'Setting Description', 'geditorial-identified' ),
				'string_empty' => _x( 'There are no identifier types available!', 'Setting', 'geditorial-identified' ),
				'default'      => $this->filters( 'default_posttype_identifier_type', 'code', $posttype_name, $types ),
				'values'       => $types ?: FALSE,
			];
		}

		$settings['_general'] = [
			'add_audit_attribute' => [
				/* translators: %s: audit attribute placeholder */
				sprintf( _x( 'Appends %s audit attribute to each created item via identifier.', 'Setting Description', 'geditorial-identified' ),
					Core\HTML::code( $this->constant( 'term_newpost_by_identifier' ) ) ),
			],
		];

		$settings['_defaults'] = [
			'post_status',
			'comment_status',
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
					 * - UPC: this is the primary GTIN in North America.
					 * - EAN/UCC: the major GTIN used outside of North America
					 * - JAN: the Japanese GTIN
					 * - ISBN: a GTIN for books
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
					'identity' => _x( 'Identity', 'Identifier Type', 'geditorial-identified' ),
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
				'identity' => _x( 'National Identity Number', 'Identifier Type Description', 'geditorial-identified' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['settings'] = [
			'post_types_after' => _x( 'Supports identifiers for the selected post-types.', 'Settings Description', 'geditorial-identified' ),
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

		$this->filter( 'pairedrest_prepped_post', 3, 99, FALSE, $this->base );
		$this->filter( 'linediscovery_data_for_post', 5, 8, FALSE, $this->base );
		$this->filter( 'searchselect_pre_query_posts', 3, 8, FALSE, $this->base );

		$this->filter_module( 'audit', 'auto_audit_save_post', 5 );
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

		if ( ! $queried = $this->get_identified( $code, $type, $params['posttype'] ?: NULL ) )
			return Services\RestAPI::getErrorNotFound( NULL, NULL, [ 'code' => $code, 'type' => $type ] );

		$response = NULL;

		switch ( $params['expect'] ) {

			case 'raw':

				$response = $queried;
				break;

			case 'id':

				if ( $post = WordPress\Post::get( $queried ) )
					$response = [ 'id' => $post->ID ];

				break;

			default:
			case 'post':

				if ( ! $post = WordPress\Post::get( $queried ) )
					break;

				if ( ! WordPress\Post::can( $post, 'read_post' ) )
					$response = Services\RestAPI::getErrorForbidden();

				else if ( $data = Services\RestAPI::getPostResponse( $post ) )
					$response = $data;
		}

		return rest_ensure_response( $response ?? Services\RestAPI::getErrorSomethingIsWrong() );
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

	public function get_identifier_possible_keys( $posttype, $extra = [] )
	{
		return array_change_key_case( array_unique(
			$this->filters( 'possible_keys_for_identifier', array_merge( [
				'identifier' => 'code',
				_x( 'Identifier', 'Possible Identifier Key', 'geditorial-identified' ) => 'code',
			], $extra ), $posttype
		) ), CASE_LOWER );
	}

	public function sanitize_identifier( $value, $type = 'code', $post = FALSE )
	{
		if ( FALSE === $value )
			return FALSE;

		switch ( $type ) {

			case 'isbn':
			case 'gtin':
				$sanitized = Core\ISBN::sanitize( $value, TRUE );
				break;

			case 'identity':
				$sanitized = Core\Validation::sanitizeIdentityNumber( $value );
				break;

			case 'code':
			default:
				$sanitized = Core\Number::intval( trim( $value ), FALSE );
		}

		return $this->filters( 'sanitize_identifier', $sanitized, $value, $type, $post );
	}

	public function linediscovery_data_for_post( $discovered, $row, $posttypes, $insert, $raw )
	{
		if ( ! is_null( $discovered ) )
			return $discovered;

		$supported  = $this->posttypes();
		$identifier = FALSE;

		if ( ! array_intersect( $supported, (array) $posttypes ) )
			return $discovered;

		foreach ( (array) $posttypes as $posttype ) {

			if ( ! in_array( $posttype, $supported, TRUE ) )
				continue;

			$type    = $this->_get_posttype_identifier_type( $posttype );
			$metakey = $this->_get_posttype_identifier_metakey( $posttype );
			$keys    = array_merge( [ $metakey => $type ], $this->get_identifier_possible_keys( $posttype ) );

			foreach ( $keys as $key => $key_type ) {

				if ( ! array_key_exists( $key, $row ) )
					continue;

				if ( $identifier = $this->sanitize_identifier( $row[$key], $key_type ) )
					break 2;
			}
		}

		if ( ! $identifier )
			return new \WP_Error( 'invalid_identifier', gEditorial\Plugin::invalid( FALSE ) );

		if ( $matches = WordPress\PostType::getIDbyMeta( $metakey, $identifier, FALSE ) )
			foreach ( $matches as $match )
				if ( $posttype === get_post_type( intval( $match ) ) )
					return intval( $match );

		if ( ! WordPress\PostType::can( $posttype, 'create_posts' ) )
			return new \WP_Error( 'create_noaccess', gEditorial\Plugin::denied( FALSE ) );

		unset( $row[$key] ); // avoid passsing into meta action

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

		$post_id = wp_insert_post( $this->filters( 'insert_target_post', $data, $identifier, $raw ) );

		if ( self::isError( $post_id ) )
			return $post_id;

		if ( ! $post_id )
			return new \WP_Error( 'cannot_create_post', gEditorial\Plugin::wrong( FALSE ) );

		if ( $this->get_setting( 'add_audit_attribute' ) )
			Helper::setTaxonomyAudit( $post_id, $this->constant( 'term_newpost_by_identifier' ) );

		// NOTE: `$override` is false because it's new post
		$this->posttypefields__do_action_import_data( $post_id, $raw, FALSE, FALSE, 'meta' );

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

		if ( ! array_intersect( $supported, (array) $queried['posttype'] ) )
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
		return Helper::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
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

			if ( $matches = WordPress\PostType::getIDbyMeta( $metakey, $sanitized, FALSE ) )
				foreach ( $matches as $match )
					if ( $posttype === get_post_type( intval( $match ) ) )
						return intval( $match );
		}

		return FALSE;
	}
}
