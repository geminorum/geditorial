<?php namespace geminorum\gEditorial\Modules\Banking;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

class Banking extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\CoreAdmin;
	use Internals\CoreRowActions;
	use Internals\FramePage;
	use Internals\MetaBoxSupported;
	use Internals\PostMeta;
	use Internals\RestAPI;
	use Internals\SubContents;

	public static function module()
	{
		return [
			'name'     => 'banking',
			'title'    => _x( 'Banking', 'Modules: Banking', 'geditorial-admin' ),
			'desc'     => _x( 'Bank and Fiscal Management', 'Modules: Banking', 'geditorial-admin' ),
			'icon'     => 'bank',
			'access'   => 'beta',
			'keywords' => [
				'account',
				'subcontent',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'_subcontent' => [
				'subcontent_posttypes' => [ NULL, $this->get_settings_posttypes_parents() ],
				'subcontent_fields'    => [ NULL, Core\Arraay::stripByKeys(
					$this->subcontent_define_fields(),
					$this->subcontent_get_required_fields( 'settings' )
				) ],
				'force_sanitize',
			],
			'_roles' => [
				'reports_roles' => [ _x( 'Roles that can view bank accounts.', 'Setting Description', 'geditorial-banking' ), $roles ],
				'assign_roles'  => [ _x( 'Roles that can assign bank accounts.', 'Setting Description', 'geditorial-banking' ), $roles ],
			],
			'_editpost' => [
				'admin_rowactions',
			],
			'_supports' => [
				'shortcode_support',
			],
			'posttypes_option' => 'posttypes_option',
		];
	}

	protected function get_global_constants()
	{
		return [
			'restapi_namespace' => 'bank-accounts',
			'subcontent_type'   => 'bank_account',
			'subcontent_status' => 'private',
			'main_shortcode'    => 'bank-accounts',

			'term_empty_subcontent_data' => 'banking-data-empty',

			'term_empty_iban'     => 'iban-empty',
			'term_invalid_iban'   => 'iban-invalid',
			'term_duplicate_iban' => 'iban-duplicate',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				/* translators: %s: count number */
				'bank_account' => _n_noop( '%s Account', '%s Accounts', 'geditorial-banking' ),
			],
			'fields' => [
				'subcontent' => [
					'iban'     => _x( 'IBAN', 'Field Label', 'geditorial-banking' ),
					'card'     => _x( 'Card Number', 'Field Label', 'geditorial-banking' ),
					'account'  => _x( 'Account Number', 'Field Label', 'geditorial-banking' ),
					'bankname' => _x( 'Bank Name', 'Field Label', 'geditorial-banking' ),
					'fullname' => _x( 'Account Owner', 'Field Label', 'geditorial-banking' ),
					'relation' => _x( 'Relation', 'Field Label', 'geditorial-banking' ),
					'type'     => _x( 'Type', 'Field Label', 'geditorial-banking' ),
					'status'   => _x( 'Status', 'Field Label', 'geditorial-banking' ),
					'desc'     => _x( 'Description', 'Field Label', 'geditorial-banking' ),
				],
			],
		];

		$strings['notices'] = [
			'empty'    => _x( 'There is no account information available!', 'Notice', 'geditorial-banking' ),
			'noaccess' => _x( 'You have not necessary permission to manage the bank accounts.', 'Notice', 'geditorial-banking' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Banking', 'MetaBox Title', 'geditorial-banking' ),
			// 'metabox_action' => _x( 'Directory', 'MetaBox Action', 'geditorial-banking' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'mainbutton_title' => _x( 'Bank Accounts of %1$s', 'Button Title', 'geditorial-banking' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Accounts of %2$s', 'Button Text', 'geditorial-banking' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'rowaction_title' => _x( 'Bank Accounts of %1$s', 'Action Title', 'geditorial-banking' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'rowaction_text'  => _x( 'Bank Accounts', 'Action Text', 'geditorial-banking' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'columnrow_title' => _x( 'Bank Accounts of %1$s', 'Row Title', 'geditorial-banking' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'columnrow_text'  => _x( 'Bank Accounts', 'Row Text', 'geditorial-banking' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [ 'meta' => [
			'_supported' => [
				'iban' => [
					'title'       => _x( 'IBAN', 'Field Title', 'geditorial-banking' ),
					'description' => _x( 'International Bank Account Number', 'Field Description', 'geditorial-banking' ),
					'type'        => 'iban',
					'order'       => 200,
				],
				'bank_card_number' => [
					'title'       => _x( 'Card Number', 'Field Title', 'geditorial-banking' ),
					'description' => _x( 'Bank Card Number', 'Field Description', 'geditorial-banking' ),
					'type'        => 'bankcard',
					'order'       => 200,
				],
			],
		] ];
	}

	protected function subcontent_get_data_mapping()
	{
		return array_merge( $this->subcontent_base_data_mapping(), [
			'comment_content'      => 'desc',       // `text`
			'comment_author'       => 'fullname',   // `tinytext`
			'comment_author_url'   => 'card',       // `varchar(200)`
			'comment_author_email' => 'country',    // `varchar(100)`
			'comment_author_IP'    => 'account',    // `varchar(100)`
			'comment_agent'        => 'iban',       // `varchar(255)`
			'comment_karma'        => 'ref',        // `int(11)`
		] );
	}

	protected function subcontent_get_meta_mapping()
	{
		return [
			'bank'     => 'bank',
			'bankname' => 'bankname',
			'type'     => 'type',
			'status'   => 'status',
			'relation' => 'relation',
		];
	}

	protected function subcontent_define_hidden_fields()
	{
		return [
			'bank',
			'country',
			'ref',
			'order',
		];
	}

	protected function subcontent_define_unique_fields()
	{
		return [
			'iban',
			'card',
		];
	}

	protected function subcontent_define_required_fields()
	{
		return [
			'fullname',
		];
	}

	public function after_setup_theme()
	{
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$this->filter( 'subcontent_provide_summary', 4, 8, FALSE, $this->base );
		$this->filter_self( 'subcontent_pre_prep_data', 4, 10 );
		$this->filter_module( 'audit', 'auto_audit_save_post', 5 );
		$this->register_shortcode( 'main_shortcode' );

		if ( ! is_admin() )
			return;

		$this->filter_module( 'tabloid', 'post_summaries', 4, 40, 'subcontent' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_supported();
		$this->filter_module( 'personage', 'editform_meta_summary', 2, 20 );
	}

	public function current_screen( $screen )
	{
		if ( $this->in_setting( $screen->post_type, 'subcontent_posttypes' ) ) {

			if ( 'post' == $screen->base ) {

				if ( $this->role_can( [ 'reports', 'assign' ] ) )
					$this->_hook_general_supportedbox( $screen, NULL, 'advanced', 'low', '-subcontent-grid-metabox' );

				if ( $this->role_can( 'assign' ) )
					Scripts::enqueueColorBox();

			} else if ( 'edit' == $screen->base ) {

				if ( $this->role_can( [ 'reports', 'assign' ] ) ) {

					if ( ! $this->rowactions__hook_mainlink_for_post( $screen->post_type ) )
						$this->coreadmin__hook_tweaks_column_row( $screen->post_type, 18 );

					Scripts::enqueueColorBox();
				}
			}
		}
	}

	public function tweaks_column_row( $post, $before, $after )
	{
		printf( $before, '-bank-grid' );

			echo $this->get_column_icon( FALSE, NULL, NULL, $post->post_type );

			echo $this->framepage_get_mainlink_for_post( $post, [
				'context' => 'columnrow',
			] );

			if ( $count = $this->subcontent_get_data_count( $post ) )
				printf( ' <span class="-counted">(%s)</span>', $this->nooped_count( 'bank_account', $count ) );

		echo $after;
	}

	protected function rowaction_get_mainlink_for_post( $post )
	{
		return [
			$this->classs().' hide-if-no-js' => $this->framepage_get_mainlink_for_post( $post, [
				'context' => 'rowaction',
			] ),
		];
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'supportedbox';

		echo $this->main_shortcode( [
			'id'      => $object,
			'context' => $context,
			'wrap'    => FALSE,
		], $this->subcontent_get_empty_notice( $context ) );

		if ( $this->role_can( 'assign' ) )
			echo Core\HTML::wrap( $this->framepage_get_mainlink_for_post( $object, [
				'context' => 'mainbutton',
				'target'  => 'grid',
			] ), 'field-wrap -buttons' );

		else
			echo $this->subcontent_get_noaccess_notice();
	}

	public function admin_menu()
	{
		if ( $this->role_can( [ 'assign', 'reports' ] ) )
			$this->_hook_submenu_adminpage( 'framepage', 'read' );
	}

	public function load_framepage_adminpage( $context = 'framepage' )
	{
		$this->_load_submenu_adminpage( $context );
		$this->subcontent_do_enqueue_app( 'bank-grid' );
	}

	// TODO: on close thickbox must refresh the metabox
	public function render_framepage_adminpage()
	{
		if ( ! $post = self::req( 'linked' ) )
			return Info::renderNoPostsAvailable();

		if ( ! $post = WordPress\Post::get( $post ) )
			return Info::renderNoPostsAvailable();

		$context = 'framepage';

		if ( $this->role_can( 'assign' ) ) {

			/* translators: %s: post title */
			$title = sprintf( _x( 'Bank Grid for %s', 'Page Title', 'geditorial-banking' ), WordPress\Post::title( $post ) );

			Settings::wrapOpen( $this->key, $context, $title );

				Scripts::renderAppMounter( 'bank-grid', $this->key );
				Scripts::noScriptMessage();

			Settings::wrapClose();

		} else if ( $this->role_can( 'reports' ) ) {

			/* translators: %s: post title */
			$title = sprintf( _x( 'Bank Overview for %s', 'Page Title', 'geditorial-banking' ), WordPress\Post::title( $post ) );

			Settings::wrapOpen( $this->key, $context, $title );

				echo $this->main_shortcode( [
					'id'      => $post,
					'context' => $context,
					'class'   => '-table-content',
				], $this->subcontent_get_empty_notice( $context ) );

			Settings::wrapClose();

		} else {

			Core\HTML::desc( gEditorial\Plugin::denied( FALSE ), TRUE, '-denied' );
		}
	}

	public function setup_restapi()
	{
		$this->subcontent_restapi_register_routes();
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return $this->subcontent_do_main_shortcode( $atts, $content, $tag );
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Helper::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_subcontent_data' ) => _x( 'Empty Banking Data', 'Default Term: Audit', 'geditorial-banking' ),

			$this->constant( 'term_empty_iban' )     => _x( 'Empty IBAN', 'Default Term: Audit', 'geditorial-banking' ),
			$this->constant( 'term_invalid_iban' )   => _x( 'Invalid IBAN', 'Default Term: Audit', 'geditorial-banking' ),
			$this->constant( 'term_duplicate_iban' ) => _x( 'Duplicate IBAN', 'Default Term: Audit', 'geditorial-banking' ),
		] ) : $terms;
	}

	public function audit_auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( $this->in_setting( $post->post_type, 'subcontent_posttypes' ) ) {

			if ( $exists = term_exists( $this->constant( 'term_empty_subcontent_data' ), $taxonomy ) ) {

				if ( $this->subcontent_get_data_count( $post ) )
					$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

				else
					$terms[] = $exists['term_id'];
			}
		}

		if ( $this->posttype_supported( $post->post_type ) ) {

			$metakey = Services\PostTypeFields::getPostMetaKey( 'iban' );
			$iban    = get_post_meta( $post->ID, $metakey, TRUE );

			if ( $exists = term_exists( $this->constant( 'term_empty_iban' ), $taxonomy ) ) {

				if ( $iban )
					$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

				else
					$terms[] = $exists['term_id'];
			}

			if ( $exists = term_exists( $this->constant( 'term_invalid_iban' ), $taxonomy ) ) {

				if ( ! $iban || Core\Validation::isIBAN( $iban ) )
					$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

				else
					$terms[] = $exists['term_id'];
			}

			if ( $exists = term_exists( $this->constant( 'term_duplicate_iban' ), $taxonomy ) ) {

				$matches = WordPress\PostType::getIDbyMeta( $metakey, $iban, FALSE );

				if ( ! $iban || count( $matches ) < 2 )
					$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

				else
					$terms[] = $exists['term_id'];
			}
		}

		return $terms;
	}

	public function subcontent_provide_summary( $data, $item, $parent, $context )
	{
		if ( ! is_null( $data ) )
			return $data;

		if ( ! empty( $item['iban'] ) ) {

			if ( FALSE === ( $iban = Info::fromIBAN( $item['iban'] ) ) )
				return $data;

			return ModuleHelper::generateSummary( $iban );

		} else if ( ! empty( $item['card'] ) ) {

			if ( FALSE === ( $card = Info::fromCardNumber( $item['card'] ) ) )
				return $data;

			return ModuleHelper::generateSummary( $card );
		}

		return $data;
	}

	// TODO: force sanitize: `type`/`status`/`relation`
	public function subcontent_pre_prep_data( $raw, $post, $mapping, $metas )
	{
		$sanitize = $this->get_setting( 'force_sanitize' );
		$data     = [];

		foreach ( $raw as $key => $value ) {

			$value = Core\Text::trim( $value );

			if ( WordPress\Strings::isEmpty( $value ) ) {

				if ( empty( $data[$key] ) )
					$data[$key] = '';

				continue;
			}

			switch ( $key ) {

				case 'card':

					if ( FALSE === ( $card = Info::fromCardNumber( $value ) ) ) {

						if ( $sanitize )
							$data[$key] = '';

					} else {

						if ( ! empty( $card['raw'] ) )
							$data[$key] = $card['raw'];

						if ( ! empty( $card['country'] ) && empty( $data['country'] ) )
							$data['country'] = $card['country'];

						if ( ! empty( $card['bank'] ) && empty( $data['bank'] ) )
							$data['bank'] = $card['bank'];

						if ( ! empty( $card['bankname'] ) && empty( $data['bankname'] ) )
							$data['bankname'] = $card['bankname'];

						if ( ! empty( $card['account'] ) && empty( $data['account'] ) )
							$data['account'] = $card['account'];
					}

					break;

				case 'iban':

					if ( FALSE === ( $iban = Info::fromIBAN( $value ) ) ) {

						if ( $sanitize )
							$data[$key] = '';

					} else {

						if ( ! empty( $iban['raw'] ) )
							$data[$key] = $iban['raw'];

						if ( ! empty( $iban['country'] ) && empty( $data['country'] ) )
							$data['country'] = $iban['country'];

						if ( ! empty( $iban['bank'] ) && empty( $data['bank'] ) )
							$data['bank'] = $iban['bank'];

						if ( ! empty( $iban['bankname'] ) && empty( $data['bankname'] ) )
							$data['bankname'] = $iban['bankname'];

						if ( ! empty( $iban['account'] ) && empty( $data['account'] ) )
							$data['account'] = $iban['account'];
					}

					break;

				case 'fullname':
				case 'bankname':
				case 'relation':
				case 'status':
				case 'type':

					$data[$key] = apply_filters( 'string_format_i18n',
						$sanitize ? WordPress\Strings::cleanupChars( $value ) : $value );

					break;

				case 'desc':

					$data[$key] = apply_filters( 'html_format_i18n',
						$sanitize ? WordPress\Strings::cleanupChars( $value, TRUE ) : $value );

					break;

				case 'account':

					$data[$key] = Core\Number::translate( $value );

					break;

				default:

					$data[$key] = $value;
			}
		}

		return $data;
	}

	public function personage_editform_meta_summary( $fields, $post )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $fields;

		$fields['iban']             = NULL;
		$fields['bank_card_number'] = NULL;

		return $fields;
	}
}
