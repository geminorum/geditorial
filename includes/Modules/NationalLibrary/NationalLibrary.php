<?php namespace geminorum\gEditorial\Modules\NationalLibrary;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\WordPress;

class NationalLibrary extends gEditorial\Module
{
	use Internals\Rewrites;

	public static function module()
	{
		return [
			'name'     => 'national_library',
			'title'    => _x( 'National Library', 'Modules: National Library', 'geditorial-admin' ),
			'desc'     => _x( 'Tools for National Library and Archives', 'Modules: National Library', 'geditorial-admin' ),
			'icon'     => [ 'misc-88', 'nlai.ir' ],
			'access'   => 'beta',
			'disabled' => Helper::moduleCheckLocale( 'fa_IR' ),
			'keywords' => [
				'book',
				'publication',
				'woocommerce',
				'persian',
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
					/* translators: %s: supported object label */
					_x( 'Bib Meta-key for %s', 'Setting Title', 'geditorial-national-library' ),
					'<i>'.$posttype_label.'</i>'
				),
				'description' => _x( 'Defines Bib meta-key for the post-type.', 'Setting Description', 'geditorial-national-library' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'after'       => Settings::fieldAfterText( $bib_metakey, 'code' ),
				'placeholder' => $bib_metakey,
				'default'     => $bib_metakey,
			];

			$settings['_posttypes'][] = [
				'field' => $posttype_name.'_posttype_isbn_metakey',
				'type'  => 'text',
				'title' => sprintf(
					/* translators: %s: supported object label */
					_x( 'ISBN Meta-key for %s', 'Setting Title', 'geditorial-national-library' ),
					'<i>'.$posttype_label.'</i>'
				),
				'description' => _x( 'Defines ISBN meta-key for the post-type.', 'Setting Description', 'geditorial-national-library' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'after'       => Settings::fieldAfterText( $isbn_metakey, 'code' ),
				'placeholder' => $isbn_metakey,
				'default'     => $isbn_metakey,
			];
		}

		$settings['_supports'] = [
			'shortcode_support',
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
		];

		$settings['_frontend'] = [
			'tabs_support',
			[
				'field'       => 'front_search',
				'title'       => _x( 'Front-end Search', 'Setting Title', 'geditorial-national-library' ),
				'description' => _x( 'Adds results by Bibliographic information on front-end search.', 'Setting Description', 'geditorial-national-library' ),
			],
			[
				'field'       => 'custom_queries',
				'title'       => _x( 'Custom Queries', 'Setting Title', 'geditorial-national-library' ),
				'description' => _x( 'Appends end-points for Bib/Fipa numbers on front-end.', 'Setting Description', 'geditorial-national-library' ),
			],
			'admin_rowactions',
		];

		return $settings;
	}

	protected function get_global_constants()
	{
		return [
			'fipa_queryvar' => 'fipa',   // NOTE: value is an `ISBN`
			'bib_queryvar'  => 'bib',    // NOTE: value is an `National Bibliographic Number`

			'main_shortcode' => 'fipa',

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

		if ( $this->get_setting( 'front_search' ) && ( ! is_admin() || Core\WordPress::isAJAX() ) )
			$this->filter( 'posts_search', 2, 8, 'front' );

		if ( $this->get_setting( 'woocommerce_support' ) && ! is_admin() )
			$this->filter( 'product_tabs', 1, 99, FALSE, 'woocommerce' );

		$this->action( 'template_newpost_side', 6, 8, FALSE, $this->base );
		$this->filter( 'meta_initial_bibliographic', 4, 8, FALSE, $this->base );
		$this->filter( 'meta_initial_isbn', 4, 8, FALSE, $this->base );
		$this->filter( 'lookup_isbn', 2, 20, FALSE, $this->base );

		if ( $this->get_setting( 'tabs_support', TRUE ) )
			$this->filter_module( 'tabs', 'builtins_tabs', 2 );

		$this->register_shortcode( 'main_shortcode' );
	}

	public function current_screen( $screen )
	{
		if ( 'post' == $screen->base && 'add' === $screen->action
			&& $this->posttype_supported( $screen->post_type ) ) {

			$this->_hook_newpost_hints( $screen->post_type );
		}
	}

	private function _hook_newpost_hints( $posttype )
	{
		if ( ! $this->get_setting( 'newpost_hints' ) )
			return FALSE;

		add_action( 'edit_form_after_title', function ( $post ) use ( $posttype ) {

			$data = FALSE;

			if ( $bib = self::req( 'bib' ) )
				$data = ModuleHelper::getFibaByBib( $bib );

			else if ( $bib = self::req( $this->_get_posttype_bib_metakey( $posttype ) ) )
				$data = ModuleHelper::getFibaByBib( $bib );

			else if ( $isbn = self::req( 'isbn' ) )
				$data = ModuleHelper::getFibaByISBN( $isbn );

			else if ( $isbn = self::req( $this->_get_posttype_isbn_metakey( $posttype ) ) )
				$data = ModuleHelper::getFibaByISBN( $isbn );

			$this->_render_fipa_data( $data );

		}, 1, 9 );
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
	}

	public function template_redirect_custom_queries()
	{
		if ( ( is_home() || is_404() ) ) {

			if ( $bib = get_query_var( $this->constant( 'bib_queryvar' ) ) ) {

				foreach ( $this->posttypes() as $posttype ) {

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

					Core\WordPress::redirect( get_page_link( $post->ID ), 302 );
				}

			} else if ( $fipa = get_query_var( $this->constant( 'fipa_queryvar' ) ) ) {

				// NOTE: `$fipa` is `ISBN`
				if ( ! $isbn = Core\ISBN::sanitize( $fipa ) )
					return; // TODO: maybe redirect to error page

				if ( ! $url = ModuleHelper::scrapeURLFromISBN( $isbn ) )
					return; // TODO: maybe redirect to error page

				Core\WordPress::redirect( $url, 302 );
			}
		}
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

	public function get_fipa( $post = NULL, $fallback = FALSE, $raw = FALSE )
	{
		$key = $this->hash( 'fipa', $post->ID );

		if ( Core\WordPress::isFlush() )
			delete_transient( $key );

		if ( FALSE === ( $data = get_transient( $key ) ) ) {

			if ( $bib = $this->get_bib( $post ) )
				$data = ModuleHelper::getFibaByBib( $bib );

			else if ( $isbn = $this->get_isbn( $post ) )
				$data = ModuleHelper::getFibaByISBN( $isbn );

			else
				$data = NULL; // avoid repeatable requests

			if ( FALSE === $data )
				set_transient( $key, $data, WEEK_IN_SECONDS );
		}

		if ( $raw )
			return $data ?: $fallback;

		return $data
			? Core\HTML::tableSimple( $data, [], FALSE, 'table' )
			: $fallback;
	}

	public function get_fipa_parsed( $post = NULL, $fallback = FALSE )
	{
		if ( ! $data = $this->get_fipa( $post, FALSE, TRUE ) )
			return $fallback;

		return ModuleHelper::parseFipa( $data );
	}

	public function posts_search_front( $search, $wp_query )
	{
		global $wpdb;

		if ( ! $wp_query->is_main_query() )
			return $search;

		if ( ! $wp_query->is_search() || empty( $wp_query->query_vars['s'] ) )
			return $search;

		$meta = $this->_prep_meta_query_for_search( $wp_query->query_vars['post_type'], $wp_query->query_vars['s'] );

		if ( ! count( $meta ) )
			return $search;

		$query = "SELECT post_id FROM {$wpdb->postmeta} WHERE ";
		$where = [];

		foreach ( $meta as $metakey => $criteria )
			$where[] = $wpdb->prepare( "(meta_key = '%s' AND meta_value = '%s')", $metakey, $criteria );

		$posts = Core\Arraay::prepNumeral( $wpdb->get_col( $query.implode( ' OR ', $where ) ) );

		if ( ! empty( $posts ) )
			$search = str_replace( ')))', ") OR ({$wpdb->posts}.ID IN (" . implode( ',', $posts ) . "))))", $search );

		return $search;
	}

	private function _prep_meta_query_for_search( $queried, $search )
	{
		$meta     = [];
		$criteria = Core\Number::translate( Core\Text::trim( $search ) );

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

			$meta[$metakey] = $criteria;
		}

		return $meta;
	}

	// NOTE: `priority` does not applied on this filter!
	public function product_tabs( $tabs )
	{
		global $product;

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) )
			return $tabs;

		if ( ! $html = $this->get_product_fipa( $product ) )
			return $tabs;

		return Core\Arraay::insert( $tabs, [
			$this->classs( 'fipa' ) => [
				'title'    => _x( 'Fipa', 'Tab Title', 'geditorial-national-library' ),
				// 'priority' => 18,
				'callback' => function () use ( $html ) {
					echo $html;
				},
			],
		], 'additional_information', 'after' );
	}

	public function get_product_fipa( $product, $fallback = FALSE, $raw = FALSE )
	{
		$key = $this->hash( 'fipa', 'product', $product->get_id() );

		if ( Core\WordPress::isFlush() )
			delete_transient( $key );

		if ( FALSE === ( $data = get_transient( $key ) ) ) {

			$type = WordPress\WooCommerce::getProductPosttype();

			if ( $bib = $this->get_product_bib( $product, $type ) )
				$data = ModuleHelper::getFibaByBib( $bib );

			else if ( $isbn = $this->get_product_isbn( $product, $type ) )
				$data = ModuleHelper::getFibaByISBN( $isbn );

			else
				$data = NULL; // avoid repeatable requests

			if ( FALSE !== $data )
				set_transient( $key, $data, WEEK_IN_SECONDS );
		}

		if ( $raw )
			return $data ?: $fallback;

		return $data
			? Core\HTML::tableSimple( $data, [], FALSE, 'table' )
			: $fallback;
	}

	public function get_product_bib( $product = NULL, $type = NULL )
	{
		if ( ! $product = wc_get_product( $product ) )
			return FALSE;

		if ( ! $metakey = $this->_get_posttype_bib_metakey( $type ?? WordPress\WooCommerce::getProductPosttype() ) )
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

		if ( ! $metakey = $this->_get_posttype_isbn_metakey( $type ?? WordPress\WooCommerce::getProductPosttype() ) )
			return $product->get_global_unique_id() ?: FALSE;

		if ( ! $isbn = $product->get_meta( $metakey, TRUE, 'edit' ) )
			return FALSE;

		return $isbn;
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

		if ( ! $this->get_bib( $post ) && ! $this->get_isbn( $post ) )
			return FALSE;

		return TRUE;
	}

	// TODO: report error button on front-end
	public function tab_callback_fipa_summary( $post = NULL, $item_name = '', $item_args = [] )
	{
		if ( $html = $this->get_fipa( $post ) )
			echo $this->wrap( $html, '-fipa-summary' );
	}

	private function _render_fipa_data( $data )
	{
		echo $this->wrap(
			Core\HTML::tableSimple( $data, [], FALSE, 'base-table-double table table-bordered' ),
			'-fipa-summary'
		);
	}

	private function _render_primed_cache( $posttype )
	{
		if ( ! $this->_prime_current_request( $posttype ) )
			return;

		if ( ! empty( $this->cache[$posttype]['raw'] ) )
			$this->_render_fipa_data( $this->cache[$posttype]['raw'] );

		if ( ! Core\WordPress::isDev() )
			return;

		if ( empty( $this->cache[$posttype]['parsed'] ) )
			return;

		self::dump( $this->cache[$posttype]['parsed'] );

		if ( ! empty( $this->cache[$posttype]['parsed']['bibliographic'] ) )
			echo ModuleHelper::linkBib( $this->cache[$posttype]['parsed']['bibliographic'] );

		echo '<br />';

		if ( ! empty( $this->cache[$posttype]['parsed']['isbn'] ) )
			echo ModuleHelper::linkISBN( $this->cache[$posttype]['parsed']['isbn'] );
	}

	private function _prime_current_request( $posttype )
	{
		if ( ! empty( $this->cache[$posttype]['raw'] ) )
			return TRUE;

		if ( $data = ModuleHelper::getFibaByBib( self::req( $this->_get_posttype_bib_metakey( $posttype ) ) ) )
			$this->cache[$posttype]['raw'] = $data;

		else if ( $data = ModuleHelper::getFibaByISBN( self::req( $this->_get_posttype_isbn_metakey( $posttype ) ) ) )
			$this->cache[$posttype]['raw'] = $data;

		else
			return FALSE;

		$this->cache[$posttype]['parsed'] = ModuleHelper::parseFipa( $this->cache[$posttype]['raw'] );

		return TRUE;
	}

	public function template_newpost_side( $posttype, $post, $target, $linked, $status, $meta )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return;

		$this->_render_primed_cache( $posttype );
	}

	public function meta_initial_bibliographic( $meta, $field, $post, $module )
	{
		if ( $meta )
			return $meta;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return $meta;

		if ( ! $this->_prime_current_request( $post->post_type ) )
			return $meta;

		if ( ! empty( $this->cache[$post->post_type]['parsed']['bibliographic'] ) )
			return $this->cache[$post->post_type]['parsed']['bibliographic'];

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

		if ( ! empty( $this->cache[$post->post_type]['parsed']['isbn'] ) )
			return $this->cache[$post->post_type]['parsed']['isbn'];

		return $meta;
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
			$html = Core\HTML::tableSimple( $data, [], FALSE, 'table' ); // not cached!

		else if ( $args['isbn'] && $data = ModuleHelper::getFibaByISBN( $args['isbn'] ) )
			$html = Core\HTML::tableSimple( $data, [], FALSE, 'table' ); // not cached!

		else if ( $post = WordPress\Post::get( $args['id'] ) )
			$html = $this->get_fipa( $post ); // cached

		if ( ! $html )
			return $content;

		return ShortCode::wrap( $html, $this->constant( 'main_shortcode' ), $args );
	}
}
