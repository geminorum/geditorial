<?php namespace geminorum\gEditorial\Modules\WcIdentity;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class WcIdentity extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'wc_identity',
			'title'    => _x( 'WC Identity', 'Modules: WC Identity', 'geditorial-admin' ),
			'desc'     => _x( 'Identity Data Management for WooCommerce', 'Modules: WC Identity', 'geditorial-admin' ),
			'icon'     => 'id',
			'access'   => 'beta',
			'disabled' => Services\Modulation::moduleCheckWooCommerce(),
			'keywords' => [
				'woocommerce',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'number_field',
					'title'       => _x( 'Identity Number Field', 'Setting Title', 'geditorial-wc-identity' ),
					'description' => _x( 'Adds extra required field for national identity number after checkout form.', 'Setting Description', 'geditorial-wc-identity' ),
				],
				[
					'field'       => 'number_validation',
					'title'       => _x( 'Identity Number Validation', 'Setting Title', 'geditorial-wc-identity' ),
					'description' => _x( 'Adds extra checks for national identity number.', 'Setting Description', 'geditorial-wc-identity' ),
				],
				[
					'field'       => 'form_row_cssclass',
					'type'        => 'select',
					'title'       => _x( 'Form Row', 'Setting Title', 'geditorial-wc-identity' ),
					'description' => _x( 'Defines the positioning of the identity field.', 'Setting Description', 'geditorial-wc-identity' ),
					'default'     => 'form-row-default',
					'values'      => [
						'form-row-default' => _x( 'Default', 'Setting Option', 'geditorial-wc-identity' ),
						'form-row-wide'    => _x( 'Wide', 'Setting Option', 'geditorial-wc-identity' ),
						'form-row-first'   => _x( 'First', 'Setting Option', 'geditorial-wc-identity' ),
						'form-row-lase'    => _x( 'Last', 'Setting Option', 'geditorial-wc-identity' ),
					],
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'metakey_user_identity_number'  => 'identity_number',
			'metakey_order_identity_number' => '_customer_identity_number',
		];
	}

	public function init()
	{
		parent::init();

		if ( $this->get_setting( 'number_field' ) )
			$this->filter( 'email_order_meta_fields', 3, 10, 'numberfield', 'woocommerce' );

		if ( is_admin() )
			return;

		if ( $this->get_setting( 'number_field' ) ) {

			$this->filter( 'checkout_fields', 1, 10, 'numberfield', 'woocommerce' );
			$this->filter( 'checkout_posted_data', 1, 10, 'numberfield', 'woocommerce' );
			$this->action( 'after_checkout_validation', 2, 10, 'numberfield', 'woocommerce' );
			$this->action( 'checkout_update_customer', 2, 10, 'numberfield', 'woocommerce' );
			$this->action( 'checkout_create_order', 2, 10, 'numberfield', 'woocommerce' );

			$this->action( 'edit_account_form_start', 1, 10, 'numberfield', 'woocommerce' );
			$this->action( 'save_account_details', 1, 10, 'numberfield', 'woocommerce' );
			$this->action( 'save_account_details_errors', 2, 10, 'numberfield', 'woocommerce' );
			$this->filter( 'save_account_details_required_fields', 1, 10, 'numberfield', 'woocommerce' );
			$this->action( 'register_form', 1, 10, 'numberfield', 'woocommerce' );
			$this->action( 'created_customer', 1, 10, 'numberfield', 'woocommerce' );
		}
	}

	private function sanitize_identity_number_field( $input )
	{
		return preg_replace( '/[^\d]/', '', Core\Number::translate( $input ) );
	}

	// @REF: https://docs.woocommerce.com/document/add-a-custom-field-in-an-order-to-the-emails/
	// @SEE: https://rudrastyh.com/woocommerce/order-meta-in-emails.html
	// MAYBE: for better control/linking: use `woocommerce_email_order_meta` hook
	public function email_order_meta_fields_numberfield( $fields, $sent_to_admin, $order )
	{
		if ( $meta = get_post_meta( $order->get_id(), $this->constant( 'metakey_order_identity_number' ), TRUE ) )
			$fields[] = [
				'label' => _x( 'Identity Number', 'Email Field Label', 'geditorial-wc-identity' ),
				'value' => Core\Number::localize( $meta ),
			];

		return $fields;
	}

	// @REF: https://docs.woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/
	// NOTE: wc auto-stores meta with `billing` or `shipping` prefixes, we use `customer` to prevent this
	public function checkout_fields_numberfield( $fields )
	{
		$identity_number = is_user_logged_in()
			? get_user_meta( get_current_user_id(), $this->constant( 'metakey_user_identity_number' ), TRUE )
			: FALSE;

		$fields['billing']['customer_identity_number'] = [
			'type'        => 'text',
			'class'       => [ 'identity_number', $this->get_setting( 'form_row_cssclass', 'form-row-default' ) ],
			'input_class' => Core\L10n::rtl() ? [ 'ltr', 'rtl-placeholder' ] : [],
			'label'       => _x( 'Identity Number', 'Checkout Field Label', 'geditorial-wc-identity' ),
			// 'placeholder' => _x( 'National Identity Number', 'Checkout Field Placeholder', 'geditorial-wc-identity' ),
			'priority'    => 25, // before the `company` with priority `30`
			'required'    => TRUE,
			'default'     => $identity_number ?: '',
		];

		if ( $this->get_setting( 'number_validation' ) ) {
			$fields['billing']['customer_identity_number']['class'][] = 'validate-required';
			$fields['billing']['customer_identity_number']['maxlength'] = 10;
			$fields['billing']['customer_identity_number']['custom_attributes']['pattern'] = Core\Validation::getIdentityNumberHTMLPattern();
		}

		return $fields;
	}

	// NOTE: alternatively we can use `woocommerce_process_checkout_field_{$key}` filter
	public function checkout_posted_data_numberfield( $data )
	{
		if ( ! empty( $data['customer_identity_number'] ) )
			$data['customer_identity_number'] = $this->sanitize_identity_number_field( $data['customer_identity_number'] );

		return $data;
	}

	public function after_checkout_validation_numberfield( $data, $errors )
	{
		if ( empty( $data['customer_identity_number'] ) )
			$errors->add( 'identity_number_empty',
				_x( 'Identity Number cannot be empty.', 'Checkout Error Message', 'geditorial-wc-identity' ) );

		else if ( $this->get_setting( 'number_validation' ) && ! Core\Validation::isIdentityNumber( $data['customer_identity_number'] ) )
			$errors->add( 'identity_number_invalid',
				_x( 'Identity Number is not valid.', 'Checkout Error Message', 'geditorial-wc-identity' ) );

		else if ( ! is_user_logged_in() && WordPress\User::getIDbyMeta( $this->constant( 'metakey_user_identity_number' ), $data['customer_identity_number'] ) )
			$errors->add( 'identity_number_registered',
				_x( 'Identity Number is already registered.', 'Checkout Error Message', 'geditorial-wc-identity' ) );
	}

	public function checkout_update_customer_numberfield( $customer, $data )
	{
		$customer->update_meta_data( $this->constant( 'metakey_user_identity_number' ), $data['customer_identity_number'] );
	}

	public function checkout_create_order_numberfield( $order, $data )
	{
		$order->update_meta_data( $this->constant( 'metakey_order_identity_number' ), $data['customer_identity_number'] );
	}

	// @REF: https://rudrastyh.com/woocommerce/edit-account-fields.html
	public function edit_account_form_start_numberfield()
	{
		$identity_number = [
			'type'        => 'text',
			'class'       => [ 'identity_number', $this->get_setting( 'form_row_cssclass', 'form-row-default' ) ],
			'input_class' => Core\L10n::rtl() ? [ 'ltr', 'rtl-placeholder' ] : [],
			'label'       => _x( 'Identity Number', 'Account Field Label', 'geditorial-wc-identity' ),
			// 'placeholder' => _x( 'National Identity Number', 'Account Field Placeholder', 'geditorial-wc-identity' ),
			'required'    => TRUE,
			'clear'       => TRUE,
		];

		if ( $this->get_setting( 'number_validation' ) ) {
			$identity_number['class'][] = 'validate-required';
			$identity_number['maxlength'] = 10;
			$identity_number['custom_attributes']['pattern'] = Core\Validation::getIdentityNumberHTMLPattern();
		}

		woocommerce_form_field( 'account_identity_number', $identity_number,
			get_user_meta( get_current_user_id(),
				$this->constant( 'metakey_user_identity_number' ), TRUE ) );

		wc_enqueue_js( "$('p#account_identity_number_field').insertAfter($('input#account_display_name').parent());" );
	}

	public function save_account_details_numberfield( $user_id )
	{
		if ( array_key_exists( 'account_identity_number', $_POST ) )
			update_user_meta( $user_id, $this->constant( 'metakey_user_identity_number' ),
				$this->sanitize_identity_number_field(
					sanitize_text_field( $_POST['account_identity_number'] ) ) );
	}

	public function save_account_details_errors_numberfield( &$errors, &$user )
	{
		$identity_number = sanitize_text_field( self::unslash( $_POST['account_identity_number'] ) );

		if ( empty( $identity_number ) ) {

			$errors->add( 'identity_number_empty',
				_x( 'The Identity Number cannot be empty.', 'Account Error Message', 'geditorial-wc-identity' ) );

		} else if ( $this->get_setting( 'number_validation' )
			&& ! Core\Validation::isIdentityNumber( $identity_number ) ) {

			$errors->add( 'identity_number_invalid',
				_x( 'The Identity Number is not valid.', 'Account Error Message', 'geditorial-wc-identity' ) );

		} else if ( $already = WordPress\User::getIDbyMeta( $this->constant( 'metakey_user_identity_number' ), $identity_number ) ) {

			if ( $already != get_current_user_id() )
				$errors->add( 'identity_number_registered',
					_x( 'The Identity Number is already registered.', 'Account Error Message', 'geditorial-wc-identity' ) );
		}
	}

	public function save_account_details_required_fields_numberfield( $fields )
	{
		return array_merge( $fields, [
			'account_identity_number' => _x( 'Identity Number', 'Account Field Required', 'geditorial-wc-identity' ),
		] );
	}

	public function register_form_numberfield()
	{
		$identity_number = [
			'type'        => 'text',
			'class'       => [ 'identity_number', $this->get_setting( 'form_row_cssclass', 'form-row-default' ) ],
			'input_class' => Core\L10n::rtl() ? [ 'ltr', 'rtl-placeholder' ] : [],
			'label'       => _x( 'Identity Number', 'Register Field Label', 'geditorial-wc-identity' ),
			'placeholder' => _x( 'National Identity Number', 'Register Field Placeholder', 'geditorial-wc-identity' ),
			'required'    => TRUE,
			'clear'       => TRUE,
		];

		if ( $this->get_setting( 'number_validation' ) ) {
			$identity_number['maxlength'] = 10;
			$identity_number['custom_attributes']['pattern'] = Core\Validation::getIdentityNumberHTMLPattern();
		}

		woocommerce_form_field( 'account_identity_number', $identity_number );
	}

	public function created_customer_numberfield( $customer_id )
	{
		$this->save_account_details_numberfield( $customer_id );
	}
}
