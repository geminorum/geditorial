<?php namespace geminorum\gEditorial\Modules\WcTerms;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class WcTerms extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'wc_terms',
			'title'    => _x( 'WC Terms', 'Modules: WC Terms', 'geditorial-admin' ),
			'desc'     => _x( 'Term Enhancements for WooCommerce', 'Modules: WC Terms', 'geditorial-admin' ),
			'icon'     => 'image-filter',
			'i18n'     => 'adminonly',
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
			'taxonomies_option' => 'taxonomies_option',
			'_tabs'             => [
				[
					'field'  => 'tab_from_taxonomy',
					'type'   => 'object',
					'title'  => _x( 'Tab from Taxonomy', 'Setting Title', 'geditorial-wc-terms' ),
					'values' => [
						[
							'field'       => 'taxonomy',
							'type'        => 'select',
							'title'       => _x( 'Taxonomy', 'Setting Title', 'geditorial-wc-terms' ),
							'description' => _x( 'Target taxonomy for term introduction on product tabs.', 'Setting Description', 'geditorial-wc-terms' ),
							'values'      => $this->list_taxonomies(),
						],
						[
							'field'       => 'heading',
							'type'        => 'text',
							'title'       => _x( 'Heading', 'Setting Title', 'geditorial-wc-terms' ),
							'description' => _x( 'Template for term introduction product tab heading.', 'Setting Description', 'geditorial-wc-terms' ),
						],
						[
							'field'       => 'excludes',
							'type'        => 'text',
							'title'       => _x( 'Excludes Terms', 'Setting Title', 'geditorial-wc-terms' ),
							'description' => _x( 'Strips terms form list of targeted terms. Leave empty to disable.', 'Setting Description', 'geditorial-wc-terms' ),
							'field_class' => [ 'regular-text', 'code-text' ],
						],
						[
							'field'       => 'priority',
							'type'        => 'number',
							'title'       => _x( 'Priority', 'Setting Title', 'geditorial-wc-terms' ),
							'description' => _x( 'Tab priority where the term introduction appears on the tabs.', 'Setting Description', 'geditorial-wc-terms' ),
							'default'     => 10,
						],
						// TODO: heading level inside the tab
					],
				],
				[
					'field'       => 'tab_term_no_default',
					'type'        => 'checkbox',
					'title'       => _x( 'Exclude Default Terms', 'Setting Title', 'geditorial-wc-terms' ),
					'description' => _x( 'Strips default term form list of displayed terms.', 'Setting Description', 'geditorial-wc-terms' ),
				],
				[
					'field'       => 'tab_term_combined',
					'type'        => 'checkbox',
					'title'       => _x( 'Combined Terms', 'Setting Title', 'geditorial-wc-terms' ),
					'description' => _x( 'Displays all assigned terms on single product tab.', 'Setting Description', 'geditorial-wc-terms' ),
				],
				[
					'field'       => 'tab_term_singular',
					'type'        => 'checkbox',
					'title'       => _x( 'Singular Term', 'Setting Title', 'geditorial-wc-terms' ),
					'description' => _x( 'Displays only the first term introduction.', 'Setting Description', 'geditorial-wc-terms' ),
				],
			],
			'_archives' => [
				[
					'field'       => 'term_archive_title',
					'type'        => 'select',
					'title'       => _x( 'Archive Title', 'Setting Title', 'geditorial-wc-terms' ),
					'description' => _x( 'Enhance the product term archive titles.', 'Setting Description', 'geditorial-wc-terms' ),
					'deafult'     => '0',
					// TODO: move up
					'values'      => [
						'0' => _x( 'Disabled', 'Setting Option', 'geditorial-wc-terms' ),
						'1' => _x( 'Heading Level 1', 'Setting Option', 'geditorial-wc-terms' ),
						'2' => _x( 'Heading Level 2', 'Setting Option', 'geditorial-wc-terms' ),
						'3' => _x( 'Heading Level 3', 'Setting Option', 'geditorial-wc-terms' ),
						'4' => _x( 'Heading Level 4', 'Setting Option', 'geditorial-wc-terms' ),
						'5' => _x( 'Heading Level 5', 'Setting Option', 'geditorial-wc-terms' ),
						'6' => _x( 'Heading Level 6', 'Setting Option', 'geditorial-wc-terms' ),
					],
				],
				[
					'field'       => 'term_archive_desc',
					'title'       => _x( 'Archive Descriptions', 'Setting Title', 'geditorial-wc-terms' ),
					'description' => _x( 'Enhance the product term archive descriptions with assigned image.', 'Setting Description', 'geditorial-wc-terms' ),
				],
				[
					'field'       => 'term_archive_subterms',
					'title'       => _x( 'Archive Sub-terms', 'Setting Title', 'geditorial-wc-terms' ),
					'description' => _x( 'Enhance the product term archive with list of sub-terms.', 'Setting Description', 'geditorial-wc-terms' ),
				],
				[
					'field'       => 'term_archive_assigned',
					'title'       => _x( 'Archive Assigned', 'Setting Title', 'geditorial-wc-terms' ),
					'description' => _x( 'Enhance the product term archive with list of assigned posts.', 'Setting Description', 'geditorial-wc-terms' ),
				],
			],
		];
	}

	public function init()
	{
		parent::init();

		if ( is_admin() )
			return;

		$this->filter( 'show_page_title', 20, 1, FALSE, 'woocommerce' );

		if ( $this->get_setting( 'term_archive_desc' ) ) {
			remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
			remove_action( 'woocommerce_archive_description', 'storefront_woocommerce_brands_archive', 5 );
			$this->action( 'archive_description', 10, 0, FALSE, 'woocommerce' );
		}

		if ( $this->get_setting( 'term_archive_subterms' ) )
			$this->action( 'archive_description', 12, 0, 'subterms', 'woocommerce' );

		if ( $this->get_setting( 'term_archive_assigned' ) )
			$this->_init_archive_assigned();

		$this->_init_tab_from_taxonomy();
	}

	public function template_redirect()
	{
		if ( is_robots() || is_favicon() || is_feed() )
			return;

		if ( is_embed() || is_search() )
			return;

		$this->enqueue_styles();
	}

	public function show_page_title( $display )
	{
		if ( ! $display )
			return $display;

		return is_product_taxonomy() ? FALSE : $display;
	}

	public function archive_description()
	{
		if ( ! is_product_taxonomy() )
			return;

		if ( absint( get_query_var( 'paged' ) ) )
			return;

		if ( $term = get_queried_object() )
			gEditorial\Template::renderTermIntro( $term, [
				'context' => 'woocommerce',
				'heading' => $this->get_setting( 'term_archive_title' ),
			], $this->module->name );
	}

	public function archive_description_subterms()
	{
		if ( ! is_product_taxonomy() )
			return;

		if ( absint( get_query_var( 'paged' ) ) )
			return;

		if ( $term = get_queried_object() )
			gEditorial\Template::renderTermSubTerms( $term, [
				'context' => 'woocommerce',
			], $this->module->name );
	}

	/**
	 * Revives template for terms with no products but other assignments.
	 * NOTE: intended targets are `people`/`year_span` taxonomies
	 */
	private function _init_archive_assigned()
	{
		$this->action( 'after_main_content', -8, 0, 'assigned', 'woocommerce' );
		$this->action( 'pre_get_posts', 1, 99, 'assigned' );
		$this->action( 'template_redirect', 1, 99, 'assigned' );
	}

	public function after_main_content_assigned()
	{
		if ( ! is_product_taxonomy() )
			return;

		if ( ! $term = get_queried_object() )
			return;

		if ( in_array( $term->taxonomy, WordPress\WooCommerce::PRODUCT_TAXONOMIES, TRUE ) )
			return;

		$html = gEditorial\ShortCode::listPosts( 'assigned',
			WordPress\WooCommerce::PRODUCT_POSTTYPE,
			$term->taxonomy,
			$this->filters( 'term_listassigned_args', [
				'context' => 'woocommerce',
				'module'  => $this->module->name,
				'term_id' => $term->term_id,

				'future' => 'off',
				'title'  => FALSE,
				'wrap'   => FALSE,
				'after'  => '</div>',
				'before' => $this->wrap_open( [
					'-term-listassigned',
					sprintf( 'columns-%d', wc_get_default_products_per_row() ),
				] ),

				'order'   => 'DESC',
				'orderby' => 'date',
				'paged'   => get_query_var( 'paged' ),
				'limit'   => wc_get_default_product_rows_per_page() * wc_get_default_products_per_row(),

				'exclude_posttypes' => WordPress\WooCommerce::PRODUCT_POSTTYPE,
			], $term ),
		);

		if ( ! $html )
			return;

		echo $html;
		woocommerce_pagination(); // FIXME: WTF: paged posts on limited products
	}

	public function pre_get_posts_assigned( &$wp_query )
	{
		if ( ! $wp_query->is_main_query() )
			return;

		if ( ! is_product_taxonomy() )
			return;

		if ( $wp_query->is_tax( WordPress\WooCommerce::PRODUCT_TAXONOMIES ) )
			return;

		$wp_query->set( 'post_type', WordPress\WooCommerce::PRODUCT_POSTTYPE );
	}

	public function template_redirect_assigned()
	{
		global $wp_query;

		if ( empty( $wp_query ) )
			return;

		// already has products
		if ( $wp_query->have_posts() )
			return;

		if ( $wp_query->is_embed() || $wp_query->is_search() )
			return;

		// NOTE: `is_tax()` is not available on paged

		if ( ! $term = WordPress\Term::get( get_queried_object() ) )
			return;

		if ( ! in_array( $term->taxonomy, get_object_taxonomies( WordPress\WooCommerce::PRODUCT_POSTTYPE ), TRUE ) )
			return;

		if ( in_array( $term->taxonomy, WordPress\WooCommerce::PRODUCT_TAXONOMIES, TRUE ) )
			return;

		$posttypes = Core\Arraay::stripByValue(
			WordPress\Taxonomy::types( $term ),
			WordPress\WooCommerce::PRODUCT_POSTTYPE
		);

		if ( empty( $posttypes ) )
			return;

		$this->filter_append( 'body_class', [ 'no-products-listassigned' ] );
		$this->filter_empty_array( 'woocommerce_catalog_orderby', 99 );
		$this->filter_empty_array( 'woocommerce_catalog_orderedby', 99 );
		remove_filter( 'pre_get_posts', [ $this, 'pre_get_posts_assigned' ], 99 );

		$_query = wp_parse_args( $wp_query->query );

		$wp_query->init();
		$wp_query->set( 'post_type', $posttypes );
		$wp_query->set( 'paged', empty( $_query['paged'] ) ? 0 : $_query['paged'] );
		$wp_query->set( 'tax_query', [ [
				'taxonomy' => $term->taxonomy,
				'terms'    => [ $term->term_id ],
				'field'    => 'term_id',
			] ] );

		$wp_query->get_posts();
	}

	private function _init_tab_from_taxonomy()
	{
		foreach ( $this->get_setting( 'tab_from_taxonomy', [] ) as $offset => $row ) {

			if ( empty( $row['taxonomy'] ) || ! WordPress\Taxonomy::exists( $row['taxonomy'] ) )
				continue;

			$priority = empty( $row['priority'] ) ? ( $offset + 10 ) : ( (int) $row['priority'] );

			add_filter( 'woocommerce_product_tabs',
				function ( $tabs ) use ( $row ) {
					return $this->_append_taxonomy_tab( $tabs, $row );
				}, $priority, 1 );
		}
	}

	public function _append_taxonomy_tab( $tabs, $row )
	{
		global $product;

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) )
			return $tabs;

		$terms = wc_get_product_terms( $product->get_id(), $row['taxonomy'], [ 'fields' => 'all' ] );

		if ( ! $terms || is_wp_error( $terms ) )
			return $tabs;

		$heading  = $this->get_setting( 'term_archive_title' );
		$terms    = Core\Arraay::reKey( $terms, 'term_id' );
		$excludes = [];

		if ( $this->get_setting( 'tab_term_no_default' ) )
			$excludes[] = WordPress\Taxonomy::getDefaultTermID( $row['taxonomy'] );

		if ( $row['excludes'] )
			$excludes = array_merge( $excludes, Services\Markup::getSeparated( $row['excludes'] ) );

		if ( count( $excludes ) )
			$terms = Core\Arraay::stripByKeys( $terms, Core\Arraay::prepNumeral( $excludes ) );

		if ( ! count( $terms ) )
			return $tabs;

		$name = Core\Text::starts( $row['taxonomy'], 'pa_' )
			? wc_attribute_label( $row['taxonomy'], $product )
			: WordPress\Taxonomy::object( $row['taxonomy'] )->labels->name;

		if ( $this->get_setting( 'tab_term_combined' ) ) {

			$key = sprintf( 'taxonomy_%s', $row['taxonomy'] );

			$tabs[$key] = [
				'title'    => trim( sprintf( $row['heading'] ?: '%s', $name ) ),
				'priority' => $row['priority'],
				'callback' => function () use ( $terms, $heading ) {
					foreach ( $terms as $term )
						gEditorial\Template::renderTermIntro( $term, [
							'context'    => 'woocommerce',
							'heading'    => $heading,
							'image_link' => 'attachment',
						], $this->module->name );
				},
			];

			return $tabs;
		}

		if ( $this->get_setting( 'tab_term_singular' ) )
			$terms = [ array_pop( $terms ) ];

		foreach ( $terms as $term ) {

			if ( empty( $term->description ) )
				continue;

			$key = sprintf( 'taxonomy_%s_%s', $term->taxonomy, $term->term_id );

			$tabs[$key] = [
				'title'    => trim( sprintf( $row['heading'] ?: '%s', $term->name, $name ) ),
				'priority' => $row['priority'],
				'callback' => function () use ( $term, $heading ) {
					gEditorial\Template::renderTermIntro( $term, [
						'context'    => 'woocommerce',
						'heading'    => $heading,
						'image_link' => 'attachment',
					], $this->module->name );
				},
			];
		}

		return $tabs;
	}
}
