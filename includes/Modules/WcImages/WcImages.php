<?php namespace geminorum\gEditorial\Modules\WcImages;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class WcImages extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'      => 'wc_images',
			'title'     => _x( 'WC Images', 'Modules: WC Images', 'geditorial-admin' ),
			'desc'      => _x( 'Tools for Product Galleries', 'Modules: WC Images', 'geditorial-admin' ),
			'icon'      => 'images-alt2',
			'configure' => 'tools',
			'access'    => 'beta',
			'frontend'  => FALSE,
			'disabled'  => gEditorial\Helper::moduleCheckWooCommerce(),
			'keywords'  => [
				'image',
				'woocommerce',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'metakey_thumbnail_id'  => '_thumbnail_id',
			'metakey_image_gallery' => '_product_image_gallery',
		];
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools', 'per_page' ) ) {

			if ( ! empty( $_POST ) ) {
				$this->nonce_check( 'tools', $sub );

				if ( Tablelist::isAction( 'shift_thumb_from_gallery', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $product_id ) {

						if ( ! $product = wc_get_product( $product_id ) )
							continue;

						$gallery = $product->get_gallery_image_ids();

						if ( empty( $gallery ) )
							continue;

						while ( count( $gallery ) ) {

							$attachment_id = array_shift( $gallery );

							if ( get_post( $attachment_id ) )
								break;
						}

						$product->set_image_id( $attachment_id );
						$product->set_gallery_image_ids( $gallery );

						if ( $product->save() )
							$count++;
					}

					WordPress\Redirect::doReferer( [
						'message' => 'synced',
						'count'   => $count,
					] );
				}

				WordPress\Redirect::doReferer( 'huh' );
			}

			gEditorial\Scripts::enqueueThickBox();
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		$query = [
			'meta_query' => [
				[
					'key'     => $this->constant( 'metakey_thumbnail_id' ),
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => $this->constant( 'metakey_image_gallery' ),
					'compare' => 'EXISTS',
				],
			],
		];

		$extra = [];

		list( $posts, $pagination ) = Tablelist::getPosts( $query, $extra, WordPress\WooCommerce::PRODUCT_POSTTYPE, $this->get_sub_limit_option( $sub, 'tools' ) );

		$pagination['actions'] = [
			'shift_thumb_from_gallery' => _x( 'Shift Thumbnail from Gallery', 'Table Action', 'geditorial-wc-images' ),
		];

		$pagination['before'][] = Tablelist::filterSearch();

		return Core\HTML::tableList( [
			'_cb'   => 'ID',
			'ID'    => Tablelist::columnPostID(),
			'title' => Tablelist::columnPostTitle(),

			'thumb' => [
				'title'    => _x( 'Thumbnail', 'Table Column', 'geditorial-wc-images' ),
				'class'    => 'image-column',
				'callback' => function ( $value, $row, $column, $index, $key, $args ) {
					$attachment_id = get_post_meta( $row->ID, $this->constant( 'metakey_thumbnail_id' ), TRUE );
					$html = WordPress\Media::htmlAttachmentImage( $attachment_id, [ 45, 72 ] );
					return $html ?: gEditorial\Helper::htmlEmpty();
				},
			],

			'gallery' => [
				'title'    => _x( 'Gallery', 'Table Column', 'geditorial-wc-images' ),
				'class'    => 'image-column',
				'callback' => function ( $value, $row, $column, $index, $key, $args ) {
					$html = '';

					if ( $gallery = get_post_meta( $row->ID, $this->constant( 'metakey_image_gallery' ), TRUE ) )
						foreach ( explode( ',', $gallery ) as $attachment_id )
							$html.= WordPress\Media::htmlAttachmentImage( $attachment_id, [ 45, 72 ] );

					return $html ?: gEditorial\Helper::htmlEmpty();
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', _x( 'Overview of WooCommerce Products', 'Header', 'geditorial-wc-images' ) ),
			'empty'      => _x( 'No product found.', 'Message', 'geditorial-wc-images' ),
			'pagination' => $pagination,
		] );
	}
}
