<?php namespace geminorum\gEditorial\Modules\WcImages;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\WooCommerce;

class WcImages extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'      => 'wc_images',
			'title'     => _x( 'WC Images', 'Modules: Wc Images', 'geditorial' ),
			'desc'      => _x( 'Tools for Product Galleries', 'Modules: WC Images', 'geditorial' ),
			'icon'      => 'images-alt2',
			'disabled'  => Helper::moduleCheckWooCommerce(),
			'configure' => 'tools',
			'frontend'  => FALSE,
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
		if ( $this->check_settings( $sub, 'tools' ) ) {

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

					WordPress::redirectReferer( [
						'message' => 'synced',
						'count'   => $count,
					] );
				}

				WordPress::redirectReferer( 'huh' );
			}

			Scripts::enqueueThickBox();
			$this->add_sub_screen_option( $sub );
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

		list( $posts, $pagination ) = Tablelist::getPosts( $query, $extra, WooCommerce::getProductPosttype(), $this->get_sub_limit_option( $sub ) );

		$pagination['actions'] = [
			'shift_thumb_from_gallery' => _x( 'Shift Thumbnail from Gallery', 'Table Action', 'geditorial-wc-images' ),
		];

		$pagination['before'][] = Tablelist::filterSearch();

		return HTML::tableList( [
			'_cb'   => 'ID',
			'ID'    => Tablelist::columnPostID(),
			'title' => Tablelist::columnPostTitle(),

			'thumb' => [
				'title'    => _x( 'Thumbnail', 'Table Column', 'geditorial-wc-images' ),
				'class'    => 'image-column',
				'callback' => function( $value, $row, $column, $index, $key, $args ) {
					$attachment_id = get_post_meta( $row->ID, $this->constant( 'metakey_thumbnail_id' ), TRUE );
					$html = Media::htmlAttachmentImage( $attachment_id, [ 45, 72 ] );
					return $html ?: Helper::htmlEmpty();
				},
			],

			'gallery' => [
				'title'    => _x( 'Gallery', 'Table Column', 'geditorial-wc-images' ),
				'class'    => 'image-column',
				'callback' => function( $value, $row, $column, $index, $key, $args ) {
					$html = '';

					if ( $gallery = get_post_meta( $row->ID, $this->constant( 'metakey_image_gallery' ), TRUE ) )
						foreach ( explode( ',', $gallery ) as $attachment_id )
							$html.= Media::htmlAttachmentImage( $attachment_id, [ 45, 72 ] );

					return $html ?: Helper::htmlEmpty();
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of WooCommerce Products', 'Header', 'geditorial-wc-images' ) ),
			'empty'      => _x( 'No product found.', 'Message', 'geditorial-wc-images' ),
			'pagination' => $pagination,
		] );
	}
}
