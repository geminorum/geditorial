<?php namespace geminorum\gEditorial\Modules\WcMeta;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class WcMeta extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'wc_meta',
			'title'    => _x( 'WC Meta', 'Modules: WC Meta', 'geditorial-admin' ),
			'desc'     => _x( 'Curated Meta-data for WooCommerce', 'Modules: WC Meta', 'geditorial-admin' ),
			'icon'     => 'tag',
			'i18n'     => 'adminonly',
			'access'   => 'beta',
			'disabled' => Services\Modulation::moduleCheckWooCommerce(),
			'keywords' => [
				'metafields',
				'woocommerce',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype' => WordPress\WooCommerce::PRODUCT_POSTTYPE,
		];
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
				$this->constant( 'primary_posttype' ) => [
					'tagline' => [
						'title'       => _x( 'Tagline', 'Field Title', 'geditorial-wc-meta' ),
						'description' => _x( 'Promotional Text on the Cover of the Product', 'Field Description', 'geditorial-wc-meta' ),
						'type'        => 'title_before',
					],
					'sub_title' => [
						'title'       => _x( 'Subtitle', 'Field Title', 'geditorial-wc-meta' ),
						'description' => _x( 'Subtitle of the Product', 'Field Description', 'geditorial-wc-meta' ),
						'type'        => 'title_after',
					],
					'alt_title' => [
						'title'       => _x( 'Alternative Title', 'Field Title', 'geditorial-wc-meta' ),
						'description' => _x( 'The Original Title or Title in Another Language', 'Field Description', 'geditorial-wc-meta' ),
					],
					'collection' => [
						'title'       => _x( 'Collection Title', 'Field Title', 'geditorial-wc-meta' ),
						'description' => _x( 'The Product Is Part of a Collection', 'Field Description', 'geditorial-wc-meta' ),
					],
					'byline' => [
						'title'       => _x( 'By-Line', 'Field Title', 'geditorial-wc-meta' ),
						'description' => _x( 'Text To Override the Product Author', 'Field Description', 'geditorial-wc-meta' ),
						'type'        => 'note',
						'icon'        => 'businessperson',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
					],
					'production_location' => [
						'title'       => _x( 'Production Location', 'Field Title', 'geditorial-wc-meta' ),
						'description' => _x( 'The Space Where Goods Roll Out', 'Field Description', 'geditorial-wc-meta' ),
						'type'        => 'venue',
						'quickedit'   => TRUE,
					],
					'production_date' => [
						'title'       => _x( 'Production Date', 'Field Title', 'geditorial-wc-meta' ),
						'description' => _x( 'The Date Determined by the Manufacturer', 'Field Description', 'geditorial-wc-meta' ),
						'type'        => 'datestring',
						'quickedit'   => TRUE,
					],

					'lead'        => [ 'type' => 'postbox_html' ],
					'highlight'   => [ 'type' => 'note' ],
					'cover_blurb' => [ 'type' => 'note' ],

					'venue_string'   => [ 'type' => 'venue' ],
					'contact_string' => [ 'type' => 'contact' ],   // url/email/phone
					'website_url'    => [ 'type' => 'link' ],
					'wiki_url'       => [ 'type' => 'link' ],
					'email_address'  => [ 'type' => 'email' ],

					'content_embed_url' => [ 'type' => 'embed' ],
					'text_source_url'   => [ 'type' => 'text_source' ],
					'audio_source_url'  => [ 'type' => 'audio_source' ],
					'video_source_url'  => [ 'type' => 'video_source' ],
					'image_source_url'  => [ 'type' => 'image_source' ],
					'main_download_url' => [ 'type' => 'downloadable' ],
					'main_download_id'  => [ 'type' => 'attachment' ],
				],
			],
			// 'units' => [
			// 	$this->constant( 'primary_posttype' ) => [],
			// ],
		];
	}

	public function meta_init()
	{
		$this->add_posttype_fields_for( 'meta', 'primary_posttype' );
	}

	// public function units_init()
	// {
	// 	$this->add_posttype_fields_for( 'units', 'primary_posttype' );
	// }
}
