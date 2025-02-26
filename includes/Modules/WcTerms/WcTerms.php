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
			'icon'     => 'store',
			'i18n'     => 'adminonly',
			'access'   => 'beta',
			'disabled' => gEditorial\Helper::moduleCheckWooCommerce(),
			'keywords' => [
				'woocommerce',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_archives' => [
				[
					'field'       => 'term_archive_title',
					'type'        => 'select',
					'title'       => _x( 'Archive Title', 'Setting Title', 'geditorial-wc-terms' ),
					'description' => _x( 'Enhance the term archive titles.', 'Setting Description', 'geditorial-wc-terms' ),
					'deafult'     => '0',
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
			$this->action( 'archive_description', 10, 0, FALSE, 'woocommerce' );
		}
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

		if ( ! $term = get_queried_object() )
			return;

		$wrap  = $this->wrap_open( 'row -term-archive-intro' );
		$title = $this->get_setting( 'term_archive_title' );

		/**
		 * Filters the archive's raw description on taxonomy archives.
		 *
		 * @since WC 6.7.0
		 *
		 * @param string $desc Raw description text.
		 * @param WP_Term $term Term object for this taxonomy archive.
		 */
		$desc = apply_filters( 'woocommerce_taxonomy_archive_description_raw', $term->description, $term );

		$image = gEditorial\Template::termImage( [
			'id'       => $term,
			'taxonomy' => $term->taxonomy,
			'field'    => WordPress\WooCommerce::TERM_IMAGE_METAKEY,
			'link'     => 'attachment',
			'before'   => $wrap.'<div class="col-sm-4 text-center -term-thumbnail">',
			'after'    => '</div>',
		], $this->module->name );

		if ( ! $image && ! $desc && ! $title )
			return;

		if ( ! $image && $desc )
			echo $wrap;

		echo '<div class="'.( $image ? 'col-sm-8 -term-has-image' : 'col -term-no-image' ).' -term-details">';

			$this->actions( 'archive_title_before', $term, $title );

			if ( $title && ( $image || $desc ) )
				Core\HTML::heading( $title, WordPress\Term::title( $term, FALSE ) );

			$this->actions( 'archive_description_before', $term, $desc );

			if ( ! WordPress\Strings::isEmpty( $desc ) )
				echo Core\HTML::wrap( wc_format_content( WordPress\Strings::kses( $desc, 'html' ) ), 'term-description -term-description' );

			$this->actions( 'archive_description_after', $term, $desc );

		echo '</div>';

		if ( ! $image && $desc )
			echo '</div>'; // `.row`
	}
}
