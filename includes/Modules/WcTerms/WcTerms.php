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
					'description' => _x( 'Enhance the term archive titles.', 'Setting Description', 'geditorial-wc-terms' ),
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
					'description' => _x( 'Enhance the term archive descriptions with assigned image.', 'Setting Description', 'geditorial-wc-terms' ),
				],
				[
					'field'       => 'term_archive_subterms',
					'title'       => _x( 'Archive Sub-terms', 'Setting Title', 'geditorial-wc-terms' ),
					'description' => _x( 'Enhance the term archive with list of sub-terms.', 'Setting Description', 'geditorial-wc-terms' ),
				],
				[
					'field'       => 'term_archive_assigned',
					'title'       => _x( 'Archive Assigned', 'Setting Title', 'geditorial-wc-terms' ),
					'description' => _x( 'Enhance the term archive with list of assigned posts.', 'Setting Description', 'geditorial-wc-terms' ),
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
			// $this->action( 'archive_description', 22, 0, 'assigned', 'woocommerce' );
			$this->action( 'after_main_content', -8, 0, 'assigned', 'woocommerce' );

		$this->_init_tab_from_taxonomy();
	}

	public function template_redirect()
	{
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

	// public function archive_description_assigned()
	public function after_main_content_assigned()
	{
		if ( ! is_product_taxonomy() )
			return;

		if ( absint( get_query_var( 'paged' ) ) )
			return;

		if ( ! $term = get_queried_object() )
			return;

		if ( in_array( $term->taxonomy, WordPress\WooCommerce::PRODUCT_TAXONOMIES, TRUE ) )
			return;

		echo gEditorial\ShortCode::listPosts( 'assigned',
			'',
			$term->taxonomy,
			$this->filters( 'term_listassigned_args', [
				'context' => 'woocommerce',
				'term_id' => $term->term_id,
				'future'  => 'off',
				'title'   => FALSE,
				'wrap'    => FALSE,
				'before'  => $this->wrap_open( '-term-listassigned' ),
				'after'   => '</div>',
				'module'  => $this->module->name,

				'exclude_posttypes' => WordPress\WooCommerce::PRODUCT_POSTTYPE,
			], $term ),
		);
	}

	private function _init_tab_from_taxonomy()
	{
		if ( is_admin() )
			return;

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
