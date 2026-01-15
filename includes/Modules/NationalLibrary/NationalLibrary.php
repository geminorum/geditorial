<?php namespace geminorum\gEditorial\Modules\NationalLibrary;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class NationalLibrary extends gEditorial\Module
{
	use Internals\Rewrites;
	use Internals\RestAPI;

	public static function module()
	{
		return [
			'name'     => 'national_library',
			'title'    => _x( 'National Library', 'Modules: National Library', 'geditorial-admin' ),
			'desc'     => _x( 'Tools for National Library and Archives', 'Modules: National Library', 'geditorial-admin' ),
			'icon'     => [ 'misc-88', 'nlai.ir' ],
			'access'   => 'beta',
			'disabled' => Services\Modulation::moduleCheckLocale( 'fa_IR' ),
			'keywords' => [
				'book',
				'publication',
				'has-public-api',
				'persian',
				'woocommerce',
				'tabmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		$settings    = [];
		$posttypes   = $this->list_posttypes();
		$woocommerce = WordPress\WooCommerce::isActive();

		$settings['posttypes_option'] = 'posttypes_option';

		foreach ( $posttypes as $posttype_name => $posttype_label ) {

			$bib_metakey  = $this->filters( 'default_posttype_bib_metakey', '', $posttype_name );
			$isbn_metakey = $this->filters( 'default_posttype_isbn_metakey', '', $posttype_name );

			$settings['_posttypes'][] = [
				'field' => $posttype_name.'_posttype_bib_metakey',
				'type'  => 'text',
				'title' => sprintf(
					/* translators: `%s`: supported object label */
					_x( 'Bib Meta-key for %s', 'Setting Title', 'geditorial-national-library' ),
					Core\HTML::tag( 'i', $posttype_label )
				),
				'description' => _x( 'Defines Bib meta-key for the post-type.', 'Setting Description', 'geditorial-national-library' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'after'       => gEditorial\Settings::fieldAfterText( $bib_metakey, 'code' ),
				'placeholder' => $bib_metakey,
				'default'     => $bib_metakey,
			];

			$settings['_posttypes'][] = [
				'field' => $posttype_name.'_posttype_isbn_metakey',
				'type'  => 'text',
				'title' => sprintf(
					/* translators: `%s`: supported object label */
					_x( 'ISBN Meta-key for %s', 'Setting Title', 'geditorial-national-library' ),
					Core\HTML::tag( 'i', $posttype_label )
				),
				'description' => _x( 'Defines ISBN meta-key for the post-type.', 'Setting Description', 'geditorial-national-library' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'after'       => gEditorial\Settings::fieldAfterText( $isbn_metakey, 'code' ),
				'placeholder' => $isbn_metakey,
				'default'     => $isbn_metakey,
			];
		}

		$settings['_supports'] = [
			'shortcode_support',
			'restapi_restricted',
		];

		if ( $woocommerce )
			$settings['_supports']['woocommerce_support'] = [
				_x( 'Select to display data as tab for the products.', 'Setting Description', 'geditorial-national-library' ),
			];

		$settings['_editpost'] = [
			[
				'field'       => 'newpost_hints',
				'title'       => _x( 'New-Post Hints', 'Setting Title', 'geditorial-national-library' ),
				'description' => _x( 'Displays Bibliographic information on new post edit screen.', 'Setting Description', 'geditorial-national-library' ),
			],
			'metabox_advanced',
		];

		$settings['_frontend'] = [
			'tabs_support',
			'tab_title'       => [ NULL , _x( 'Fipa', 'Setting Default', 'geditorial-national-library' ) ],
			'tab_priority'    => [ NULL , 90 ],
			'frontend_search' => [ _x( 'Adds results by Bibliographic information on front-end search.', 'Setting Description', 'geditorial-national-library' ), TRUE ],
			[
				'field'       => 'custom_queries',
				'title'       => _x( 'Custom Queries', 'Setting Title', 'geditorial-national-library' ),
				'description' => _x( 'Appends end-points for Bib/Fipa numbers on front-end.', 'Setting Description', 'geditorial-national-library' ),
			],
			'admin_rowactions',
		];

		$settings['_constants'] = [
			'main_shortcode_constant'       => [ NULL, 'fipa' ],
		];

		return $settings;
	}

	protected function get_global_constants()
	{
		return [
			'restapi_namespace' => 'national-library',

			'fipa_queryvar' => 'fipa',   // NOTE: value is an `ISBN`
			'bib_queryvar'  => 'bib',    // NOTE: value is an `National Bibliographic Number`

			'main_shortcode'       => 'fipa',

			'metakey_bib_posttype'  => 'nali_bib',        // FALLBACK
			'metakey_isbn_posttype' => 'isbn',            // FALLBACK
			'metakey_opac_id'       => '_nlai_opac_id',
		];
	}

	private function _get_posttype_bib_metakey( $posttype, $fallback = NULL )
	{
		if ( $setting = $this->get_setting( $posttype.'_posttype_bib_metakey' ) )
			return $setting;

		if ( $default = $this->filters( 'default_posttype_bib_metakey', '', $posttype ) )
			return $default;

		return $fallback ?? $this->constant( 'metakey_bib_posttype' );
	}

	private function _get_posttype_isbn_metakey( $posttype, $fallback = NULL )
	{
		if ( $setting = $this->get_setting( $posttype.'_posttype_isbn_metakey' ) )
			return $setting;

		if ( $default = $this->filters( 'default_posttype_isbn_metakey', '', $posttype ) )
			return $default;

		return $fallback ?? $this->constant( 'metakey_isbn_posttype' );
	}

	public function init()
	{
		parent::init();

		$this->_init_custom_queries();

		if ( $this->get_setting( 'frontend_search', TRUE ) )
			$this->filter( 'posts_search_append_meta_frontend', 3, 8, FALSE, $this->base );

		if ( $this->get_setting( 'woocommerce_support' ) && ! is_admin() )
			$this->filter( 'product_tabs', 1, $this->get_setting( 'tab_priority', 90 ), FALSE, 'woocommerce' );

		if ( $this->get_setting( 'newpost_hints' ) ) {

			$this->filter( 'template_newpost_title', 6, 8, FALSE, $this->base );
			$this->action( 'template_newpost_buttons', 6, 12, FALSE, $this->base );

			if ( is_admin() )
				$this->action( 'template_newpost_aftercontent', 6, 12, FALSE, $this->base );
			else
				$this->action( 'template_newpost_side', 6, 8, FALSE, $this->base );
		}

		$this->filter( 'objecthints_tips_for_post', 5, 12, FALSE, $this->base );
		$this->filter( 'meta_initial_bibliographic', 4, 8, FALSE, $this->base );
		$this->filter( 'meta_initial_isbn', 4, 8, FALSE, $this->base );
		$this->filter( 'lookup_isbn', 2, 20, FALSE, $this->base );

		if ( $this->get_setting( 'tabs_support', TRUE ) )
			$this->filter_module( 'tabs', 'builtins_tabs', 2 );

		$this->register_shortcode( 'main_shortcode' );
	}

	public function current_screen( $screen )
	{
		if ( 'post' === $screen->base
			&& $this->posttype_supported( $screen->post_type ) ) {

			if ( 'add' === $screen->action )
				$this->_hook_newpost_hints( $screen->post_type );

			else
				$this->_hook_savedpost_hints( $screen->post_type );
		}
	}

	public function setup_restapi()
	{
		$this->restapi_register_route( 'query', 'get', '(?P<code>.+)' );
	}

	public function restapi_query_get_arguments()
	{
		return [
			'code' => [
				'required'    => TRUE,
				'description' => esc_html_x( 'The ISBN or Biblio Code to process.', 'RestAPI: Arg Description', 'geditorial-national-library' ),

				'validate_callback' => [ $this, 'restapi_query_get_code_validate_callback' ],
			],
		];
	}

	public function restapi_query_get_code_validate_callback( $param, $request, $key )
	{
		if ( empty( $param ) )
			return Services\RestAPI::getErrorArgNotEmpty( $key );

		return TRUE;
	}

	public function restapi_query_get_callback( $request )
	{
		return rest_ensure_response(
			$this->get_query_summary(
				urldecode( $request['code'] ),
				$request['context']
			)
		);
	}

	public function get_query_summary( $code, $context = 'markup' )
	{
		switch ( $context ) {

			default:
			case 'view':
			case 'markup':

				$data = $this->get_fipa_by_code( $code, FALSE, FALSE, $context );

				if ( self::isError( $data ) )
					return $data;

				return [ 'html' => $data ];

			case 'edit':
			case 'parsed':

				if ( ! $data = $this->get_fipa_by_code( $code, FALSE, TRUE, $context ) )
					return Services\RestAPI::getErrorInvalidData();

				return [ 'data' => ModuleHelper::parseFipa( $data ) ];

			case 'title':

				if ( ! $data = $this->get_fipa_by_code( $code, FALSE, TRUE, $context ) )
					return Services\RestAPI::getErrorInvalidData();

				return [ 'data' => ModuleHelper::getTitle( $data, '' ) ];
		}

		return Services\RestAPI::getErrorSomethingIsWrong();
	}

	public function get_fipa_by_code( $code, $fallback = FALSE, $raw = FALSE, $context = NULL )
	{
		$code = Core\Number::translate( $code );
		$code = Core\Text::stripAllSpaces( $code );
		$code = Core\Text::trim( str_ireplace( [ 'isbn', '-', ':', ' ' ], '', $code ) );

		if ( WordPress\Strings::isEmpty( $code ) )
			return Services\RestAPI::getErrorInvalidData();

		$key = $this->hash( 'fipa', 'code', $code );

		if ( WordPress\IsIt::flush() )
			delete_transient( $key );

		if ( FALSE === ( $data = get_transient( $key ) ) ) {

			if ( $isbn = Core\ISBN::sanitize( $code ) )
				$data = ModuleHelper::getFibaByISBN( $isbn );

			else if ( Core\Validation::isBibliographic( $code ) )
				$data = ModuleHelper::getFibaByBib( $code );

			else
				$data = NULL; // avoid repeatable requests

			if ( FALSE !== $data )
				set_transient( $key, $data, Core\Date::WEEK_IN_SECONDS );
		}

		if ( $raw )
			return $data ?: $fallback;

		return $data
			? Core\HTML::tableSimple( $data['rows'], [], FALSE, $this->_get_table_css_class( $context ) )
			: $fallback;
	}

	private function _hook_savedpost_hints( $screen )
	{
		add_filter( 'enter_title_here',
			function ( $title, $post ) {

				if ( ! $data = $this->get_fipa_by_post( $post, FALSE, TRUE ) )
					return $title;

				if ( ! empty( $data['title'] ) )
					return $data['title'];

				return $title;

			}, 2, 8 );

		if ( $this->get_setting( 'metabox_advanced' ) )
			add_meta_box(
				$this->classs(),
				// $this->strings_metabox_title_via_posttype( $screen->post_type, 'mainbox' ),
				_x( 'Fipa', 'Meta-Box Title', 'geditorial-national-library' ),
				[ $this, 'render_metabox_fipa' ],
				$screen,
				'advanced',
				'low'
			);

		add_action( 'admin_enqueue_scripts',
			function ( $hook_suffix ) {

				if ( ! $data = $this->get_fipa_by_post( WordPress\Post::get(), FALSE, TRUE ) )
					return;

				if ( ! empty( $data['biblio'] ) )
					Services\HeaderButtons::register( $this->hook_key( 'biblio' ), [
						'text'     => _x( 'National Library', 'Button Text', 'geditorial-national-library' ),
						'title'    => _x( 'Book Page on Opac.Nali.ir', 'Button Title Attr', 'geditorial-national-library' ),
						'link'     => ModuleHelper::linkBib( $data['biblio'], FALSE ),
						'newtab'   => TRUE,
						'priority' => 80,
					] );

				else if ( ! empty( $data['isbn'] ) )
					Services\HeaderButtons::register( $this->hook_key( 'isbn' ), [
						'text'     => _x( 'National Library', 'Button Text', 'geditorial-national-library' ),
						'title'    => _x( 'Book Page on Opac.Nali.ir', 'Button Title Attr', 'geditorial-national-library' ),
						'link'     => ModuleHelper::linkISBN( $data['isbn'], FALSE ),
						'newtab'   => TRUE,
						'priority' => 85,
					] );

			}, 4, 1 );
	}

	private function _hook_newpost_hints( $posttype )
	{
		if ( ! $this->get_setting( 'newpost_hints' ) )
			return FALSE;

		add_filter( 'default_title',
			function ( $title, $post ) use ( $posttype ) {

				if ( $title )
					return $title;

				if ( ! $this->_prime_current_request( $posttype ) )
					return $title;

				if ( ! empty( $this->cache[$posttype]['parsed']['title'][0] ) )
					return $this->cache[$posttype]['parsed']['title'][0];

				return $title;

			}, 2, 8 );

		add_filter( 'enter_title_here',
			function ( $title, $post ) use ( $posttype ) {

				if ( ! $this->_prime_current_request( $posttype ) )
					return $title;

				if ( ! empty( $this->cache[$posttype]['parsed']['title'][0] ) )
					return $this->cache[$posttype]['parsed']['title'][0];

				return $title;

			}, 2, 8 );

		add_action( 'edit_form_after_title', function ( $post ) use ( $posttype ) {

			if ( ! $this->_prime_current_request( $posttype ) )
				return;

			if ( ! empty( $this->cache[$posttype]['raw'] ) )
				$this->_render_fipa_data( $this->cache[$posttype]['raw'], 'hints' );

		}, 1, 9 );

		add_action( 'admin_enqueue_scripts',
			function ( $hook_suffix ) use ( $posttype ) {

				if ( ! $this->_prime_current_request( $posttype ) )
					return;

				if ( ! empty( $this->cache[$posttype]['raw']['biblio'] ) )
					Services\HeaderButtons::register( $this->hook_key( 'biblio' ), [
						'text'     => _x( 'National Library', 'Button Text', 'geditorial-national-library' ),
						'title'    => _x( 'Book Page on Opac.Nali.ir', 'Button Title Attr', 'geditorial-national-library' ),
						'link'     => ModuleHelper::linkBib( $this->cache[$posttype]['raw']['biblio'], FALSE ),
						'newtab'   => TRUE,
						'priority' => 80,
					] );

				else if ( ! empty( $this->cache[$posttype]['raw']['isbn'] ) )
					Services\HeaderButtons::register( $this->hook_key( 'isbn' ), [
						'text'     => _x( 'National Library', 'Button Text', 'geditorial-national-library' ),
						'title'    => _x( 'Book Page on Opac.Nali.ir', 'Button Title Attr', 'geditorial-national-library' ),
						'link'     => ModuleHelper::linkISBN( $this->cache[$posttype]['raw']['isbn'], FALSE ),
						'newtab'   => TRUE,
						'priority' => 85,
					] );

			}, 4, 1 );
	}

	private function _init_custom_queries()
	{
		if ( ! $this->get_setting( 'custom_queries' ) )
			return FALSE;

		$this->rewrites__add_tag( 'bib', FALSE );
		$this->rewrites__add_tag( 'fipa', FALSE );

		if ( is_admin() )
			return;

		$this->action( 'template_redirect', 0, 9, 'custom_queries' );
		$this->filter_self( 'is_post_viewable', 2, 10, 'custom_queries' );
	}

	public function template_redirect_custom_queries()
	{
		if ( ( is_home() || is_404() ) ) {

			if ( $bib = get_query_var( $this->constant( 'bib_queryvar' ) ) ) {

				$supported = $this->posttypes();

				foreach ( $supported as $posttype ) {

					if ( ! $metakey = $this->_get_posttype_bib_metakey( $posttype ) )
						continue;

					if ( ! $post_id = WordPress\PostType::getIDbyMeta( $metakey, $bib ) )
						return;

					if ( ! $post = WordPress\Post::get( $post_id ) )
						return;

					if ( $post->post_type !== $posttype )
						return;

					if ( ! $this->is_post_viewable( $post ) )
						return;

					if ( ! $link = WordPress\Post::link( $post, FALSE, WordPress\Status::acceptable( $post->post_type ) ) )
						continue;

					WordPress\Redirect::doWP( $link, 302 );
				}

				$this->actions( 'bib_notfound', $bib, $supported );

				WordPress\Theme::set404();

			} else if ( $fipa = get_query_var( $this->constant( 'fipa_queryvar' ) ) ) {

				// NOTE: `$fipa` is `ISBN`
				if ( ! Core\ISBN::validate( $fipa ) )
					return WordPress\Theme::set404();

				if ( ! $url = ModuleHelper::scrapeURLFromISBN( Core\ISBN::sanitize( $fipa ) ) )
					return WordPress\Theme::set404();

				WordPress\Redirect::doWP( $url, 302 );
			}
		}
	}

	public function is_post_viewable_custom_queries( $viewable, $post )
	{
		if ( $viewable )
			return $viewable;

		// The type is not viewable so letting go!
		if ( ! WordPress\PostType::viewable( $post->post_type ) )
			return $viewable;

		return WordPress\Post::can( $post, 'read_post' );
	}

	public function lookup_isbn( $url, $isbn )
	{
		return ModuleHelper::linkISBN( $isbn, FALSE );
	}

	public function get_bib( $post = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $metakey = $this->_get_posttype_bib_metakey( $post->post_type ) )
			return FALSE;

		if ( ! $bib = get_post_meta( $post->ID, $metakey, TRUE ) )
			return FALSE;

		return $bib;
	}

	public function get_isbn( $post = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $metakey = $this->_get_posttype_isbn_metakey( $post->post_type ) )
			return FALSE;

		if ( ! $isbn = get_post_meta( $post->ID, $metakey, TRUE ) )
			return FALSE;

		return $isbn;
	}

	public function get_fipa_by_post( $post, $fallback = FALSE, $raw = FALSE, $context = NULL )
	{
		$key = $this->hash( 'fipa', 'post', $post->ID );

		if ( WordPress\IsIt::flush() )
			delete_transient( $key );

		if ( FALSE === ( $data = get_transient( $key ) ) ) {

			if ( $bib = $this->get_bib( $post ) )
				$data = ModuleHelper::getFibaByBib( $bib );

			else if ( $isbn = $this->get_isbn( $post ) )
				$data = ModuleHelper::getFibaByISBN( $isbn );

			else
				$data = NULL; // avoid repeatable requests

			if ( FALSE !== $data )
				set_transient( $key, $data, Core\Date::WEEK_IN_SECONDS );
		}

		if ( $raw )
			return $data ?: $fallback;

		return $data
			? Core\HTML::tableSimple( $data['rows'], [], FALSE, $this->_get_table_css_class( $context ) )
			: $fallback;
	}

	public function get_fipa_by_post_parsed( $post = NULL, $fallback = FALSE )
	{
		if ( ! $data = $this->get_fipa_by_post( $post, FALSE, TRUE ) )
			return $fallback;

		return ModuleHelper::parseFipa( $data );
	}

	public function posts_search_append_meta_frontend( $meta, $search, $queried )
	{
		$criteria = Core\Number::translate( $search );

		// only numbers
		if ( ! preg_match( '/^[0-9]+$/', $criteria ) )
			return $meta;

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

			if ( ! $metakey = $this->_get_posttype_bib_metakey( $posttype ) )
				continue;

			$meta[] = [ $metakey, $criteria ];
		}

		return $meta;
	}

	public function product_tabs( $tabs )
	{
		global $product;

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) )
			return $tabs;

		if ( ! $data = $this->get_product_fipa( $product, FALSE, TRUE ) )
			return $tabs;

		return Core\Arraay::insert( $tabs, [
			$this->classs( 'fipa' ) => [
				'title'    => $this->get_setting_fallback( 'tab_title', _x( 'Fipa', 'Setting Default', 'geditorial-national-library' ) ),
				'priority' => $this->get_setting( 'tab_priority', 90 ), // NOTE: `priority` does not applied on this filter!
				'callback' => function () use ( $data ) {
					$this->_render_fipa_data( $data, 'tabs' );
				},
			],
		], 'additional_information', 'after' );
	}

	public function get_product_fipa( $product, $fallback = FALSE, $raw = FALSE, $context = NULL )
	{
		$key = $this->hash( 'fipa', 'product', $product->get_id() );

		if ( WordPress\IsIt::flush() )
			delete_transient( $key );

		if ( FALSE === ( $data = get_transient( $key ) ) ) {

			if ( $bib = $this->get_product_bib( $product ) )
				$data = ModuleHelper::getFibaByBib( $bib );

			else if ( $isbn = $this->get_product_isbn( $product ) )
				$data = ModuleHelper::getFibaByISBN( $isbn );

			else
				$data = NULL; // avoid repeatable requests

			if ( FALSE !== $data )
				set_transient( $key, $data, Core\Date::WEEK_IN_SECONDS );
		}

		if ( $raw )
			return $data ?: $fallback;

		return $data
			? Core\HTML::tableSimple( $data['rows'], [], FALSE, $this->_get_table_css_class( $context, 'product-fipa' ) )
			: $fallback;
	}

	public function get_product_bib( $product = NULL, $type = NULL )
	{
		if ( ! $product = wc_get_product( $product ) )
			return FALSE;

		if ( ! $metakey = $this->_get_posttype_bib_metakey( $type ?? WordPress\WooCommerce::PRODUCT_POSTTYPE ) )
			return FALSE;

		if ( ! $bib = $product->get_meta( $metakey, TRUE, 'edit' ) )
			return FALSE;

		return $bib;
	}

	// NOTE: falls back to product GTIN
	public function get_product_isbn( $product = NULL, $type = NULL )
	{
		if ( ! $product = wc_get_product( $product ) )
			return FALSE;

		$metakey = $this->_get_posttype_isbn_metakey( $type ?? WordPress\WooCommerce::PRODUCT_POSTTYPE );

		// Woo-Commerce nags about direct use of it's internal meta-keys
		if ( $metakey && $metakey === WordPress\WooCommerce::GTIN_METAKEY )
			return $product->get_global_unique_id( 'edit' ) ?: FALSE;

		if ( $metakey && ( $isbn = $product->get_meta( $metakey, TRUE, 'edit' ) ) )
			return $isbn;

		return $product->get_global_unique_id( 'edit' ) ?: FALSE;
	}

	public function tabs_builtins_tabs( $tabs, $posttype )
	{
		if ( $this->posttype_supported( $posttype ) )
			$tabs[] = [
				'name'        => $this->hook( 'fipa' ),
				'title'       => _x( 'Fipa', 'Tab Title', 'geditorial-national-library' ),
				'description' => _x( 'Exact Fipa data from The National Library.', 'Tab Description', 'geditorial-national-library' ),
				'callback'    => [ $this, 'tab_callback_fipa_summary' ],
				'viewable'    => [ $this, 'tab_viewable_fipa_summary' ],
				'priority'    => 60,
			];

		return $tabs;
	}

	public function tab_viewable_fipa_summary( $post = NULL, $item_name = '', $item_args = [] )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! ( $bib = $this->get_bib( $post ) ) && ! ( $isbn = $this->get_isbn( $post ) ) )
			return FALSE;

		if ( $bib )
			return (bool) Core\Validation::isBibliographic( $bib );

		return (bool) Core\ISBN::validate( $isbn );
	}

	public function tab_callback_fipa_summary( $post = NULL, $item_name = '', $item_args = [] )
	{
		if ( $html = $this->get_fipa_by_post( $post ) )
			echo $this->wrap( $html, '-fipa-summary' );
	}

	private function _get_table_css_class( $context = NULL, $extra = [] )
	{
		return $this->filters( 'fipa_table_css_class', array_merge( [
			'fipa-table'       ,  // GENERAL
			'base-table-double',  // WP ADMIN
			'table'            ,  // BS CLASS
			// 'table-bordered'   ,  // BS CLASS
			sprintf( 'table-context-%s', $context ?? 'default' ),
		], (array) $extra ), $context );
	}

	private function _render_fipa_data( $data, $context = NULL )
	{
		echo $this->wrap(
			Core\HTML::tableSimple( $data['rows'], [], FALSE, $this->_get_table_css_class( $context ) ),
			'-fipa-summary'
		);
	}

	// NOTE: supports for:
	// - `{$isbn_metakey}={$data}`
	// - `meta[{$isbn_metakey}]={$data}`
	// - `isbn={$data}`
	// - `barcode={$data}`
	// - `{$bib_metakey}={$data}`
	// - `meta[{$bib_metakey}]={$data}`
	// - `bib={$data}`
	// - `biblio={$data}`
	private function _prime_current_request( $posttype )
	{
		if ( ! empty( $this->cache[$posttype]['raw'] ) )
			return TRUE;

		$bib_metakey  = $this->_get_posttype_bib_metakey( $posttype );
		$isbn_metakey = $this->_get_posttype_isbn_metakey( $posttype );

		if ( $data = ModuleHelper::getFibaByBib( self::req( $bib_metakey ) ) )
			$this->cache[$posttype]['raw'] = $data;

		else if ( $data = ModuleHelper::getFibaByBib( self::req( 'meta', FALSE, $bib_metakey ) ) )
			$this->cache[$posttype]['raw'] = $data;

		else if ( $data = ModuleHelper::getFibaByBib( self::req( 'biblio' ) ) )
			$this->cache[$posttype]['raw'] = $data;

		else if ( $data = ModuleHelper::getFibaByBib( self::req( 'bib' ) ) )
			$this->cache[$posttype]['raw'] = $data;

		else if ( $data = ModuleHelper::getFibaByISBN( self::req( $isbn_metakey ) ) )
			$this->cache[$posttype]['raw'] = $data;

		else if ( $data = ModuleHelper::getFibaByISBN( self::req( 'meta', FALSE, $isbn_metakey ) ) )
			$this->cache[$posttype]['raw'] = $data;

		else if ( $data = ModuleHelper::getFibaByISBN( self::req( 'isbn' ) ) )
			$this->cache[$posttype]['raw'] = $data;

		else if ( $data = ModuleHelper::getFibaByISBN( self::req( 'barcode' ) ) )
			$this->cache[$posttype]['raw'] = $data;

		else
			return FALSE;

		$this->cache[$posttype]['parsed'] = ModuleHelper::parseFipa( $this->cache[$posttype]['raw'] );

		return TRUE;
	}

	public function template_newpost_title( $title, $posttype, $target, $linked, $status, $meta )
	{
		if ( $title )
			return $title; // already generated!

		if ( ! $this->posttype_supported( $posttype ) )
			return $title;

		if ( ! $this->_prime_current_request( $posttype ) )
			return $title;

		if ( ! empty( $this->cache[$posttype]['parsed']['title'][0] ) )
			return $this->cache[$posttype]['parsed']['title'][0];

		return $title;
	}

	public function template_newpost_buttons( $posttype, $post, $target, $linked, $status, $meta )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return;

		if ( ! $this->_prime_current_request( $posttype ) )
			return;

		if ( ! empty( $this->cache[$posttype]['raw']['biblio'] ) )
			echo ModuleHelper::linkBib( $this->cache[$posttype]['raw']['biblio'], TRUE, NULL, 'button btn btn-info' ).'&nbsp;&nbsp;';

		if ( ! empty( $this->cache[$posttype]['raw']['isbn'] ) )
			echo ModuleHelper::linkISBN( $this->cache[$posttype]['raw']['isbn'], TRUE, NULL, 'button btn btn-info' ).'&nbsp;&nbsp;';
	}

	public function template_newpost_aftercontent( $posttype, $post, $target, $linked, $status, $meta )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return;

		if ( ! $this->_prime_current_request( $posttype ) )
			return;

		if ( ! empty( $this->cache[$posttype]['raw'] ) )
			$this->_render_fipa_data( $this->cache[$posttype]['raw'], 'newpost' );

		if ( ! WordPress\IsIt::dev() )
			return;

		if ( ! empty( $this->cache[$posttype]['parsed'] ) && ! is_admin() )
			self::dump( $this->cache[$posttype]['parsed'] );
	}

	public function template_newpost_side( $posttype, $post, $target, $linked, $status, $meta )
	{
		$this->template_newpost_aftercontent( $posttype, $post, $target, $linked, $status, $meta );
	}

	public function meta_initial_bibliographic( $meta, $field, $post, $module )
	{
		if ( $meta )
			return $meta;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return $meta;

		if ( ! $this->_prime_current_request( $post->post_type ) )
			return $meta;

		if ( ! empty( $this->cache[$post->post_type]['raw']['biblio'] ) )
			return $this->cache[$post->post_type]['raw']['biblio'];

		return $meta;
	}

	public function meta_initial_isbn( $meta, $field, $post, $module )
	{
		if ( $meta )
			return $meta;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return $meta;

		if ( ! $this->_prime_current_request( $post->post_type ) )
			return $meta;

		if ( ! empty( $this->cache[$post->post_type]['raw']['isbn'] ) )
			return Core\ISBN::convertToISBN13( $this->cache[$post->post_type]['raw']['isbn'] );

		return $meta;
	}

	public function objecthints_tips_for_post( $tips, $post, $extend, $context, $queried )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $tips;

		$support = [
			'default',
			'byline',
			'author',
			'translator',
			'subject',
		];

		if ( ! $extend || ! in_array( $extend, $support, TRUE  ) )
			return $tips;

		if ( ! $fipa = $this->get_fipa_by_post( $post, FALSE, TRUE ) )
			return $tips;

		return array_merge( $tips,
			ModuleHelper::generateHints( $fipa, $post, $context, $queried ) );
	}

	public function render_metabox_fipa( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		if ( $html = $this->get_fipa_by_post( $post ) )
			echo $this->wrap( $html, '-fipa-summary' );
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'      => get_queried_object_id(),
			'bib'     => NULL,
			'isbn'    => NULL,
			'context' => NULL,
			'wrap'    => TRUE,
			'class'   => '',
			'before'  => '',
			'after'   => '',
		], $atts, $tag ?: $this->constant( 'main_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = '';

		if ( $args['bib'] && $data = ModuleHelper::getFibaByBib( $args['bib'] ) )
			$html = Core\HTML::tableSimple( $data['rows'], [], FALSE, $this->_get_table_css_class( $args['context'] ) ); // not cached!

		else if ( $args['isbn'] && $data = ModuleHelper::getFibaByISBN( $args['isbn'] ) )
			$html = Core\HTML::tableSimple( $data['rows'], [], FALSE, $this->_get_table_css_class( $args['context'] ) ); // not cached!

		else if ( $post = WordPress\Post::get( $args['id'] ) )
			$html = $this->get_fipa_by_post( $post ); // cached

		if ( ! $html )
			return $content;

		return gEditorial\ShortCode::wrap( $html, $this->constant( 'main_shortcode' ), $args );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( gEditorial\Tablelist::isAction( ModuleSettings::ACTION_SCRAPE_POOL ) ) {

					if ( ! ModuleSettings::handleTool_scrape_pool() )
						WordPress\Redirect::doReferer( 'huh' );

				} else {

					WordPress\Redirect::doReferer( 'huh' );
				}
			}
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo ModuleSettings::toolboxColumnOpen( _x( 'National Library Tools', 'Header', 'geditorial-national-library' ) );

			ModuleSettings::renderCard_scrape_pool();

		echo '</div>';
	}
}
