<?php namespace geminorum\gEditorial\Modules\Socialite;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Socialite extends gEditorial\Module
{
	// NOTE: see `Addressed` Module

	protected $supported = [
		'twitter',
		'tiktok',
		'instagram',
		'telegram',
		'facebook',
		'youtube',
		'aparat',
		'behkhaan',
		'eitaa',
		'wikipedia',
	];

	public static function module()
	{
		return [
			'name'     => 'socialite',
			'title'    => _x( 'Socialite', 'Modules: Socialite', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Social Card', 'Modules: Socialite', 'geditorial-admin' ),
			'icon'     => 'money',
			'access'   => 'beta',
			'keywords' => [
				'social',
				'termmeta',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_edittags' => [
				[
					'field'        => 'extra_meta_fields',
					'type'         => 'checkbox-panel',
					'title'        => _x( 'Supported Fields', 'Setting Title', 'geditorial-socialite' ),
					'description'  => _x( 'Appends custom meta fields for social services to taxonomies. Terms module needs to be enabled.', 'Setting Description', 'geditorial-socialite' ),
					'string_empty' => _x( 'The Terms module is not available!', 'Setting String Empty', 'geditorial-socialite' ),
					'values'       => $this->_prep_fields_for_settings(),
				],
			],
			'_general' => [
				[
					'field'  => 'social_icons',
					'type'   => 'object',
					'title'  => _x( 'Social Icons', 'Setting Title', 'geditorial-socialite' ),
					'values' => [
						[
							'field'       => 'url',
							'type'        => 'text',
							'title'       => _x( 'URL', 'Setting Title', 'geditorial-socialite' ),
							'description' => _x( 'Sets as URL of the social icon.', 'Setting Description', 'geditorial-socialite' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'dir'         => 'ltr',
						],
						[
							'field'       => 'icon',
							'type'        => 'text',
							'title'       => _x( 'Icon', 'Setting Title', 'geditorial-socialite' ),
							'description' => _x( 'Sets the icon image of the social icon.', 'Setting Description', 'geditorial-socialite' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'dir'         => 'ltr',
						],
						[
							'field'       => 'priority',
							'type'        => 'number',
							'title'       => _x( 'Priority', 'Setting Title', 'geditorial-socialite' ),
							'description' => _x( 'Sets as the priority where the social icon display on the list.', 'Setting Description', 'geditorial-socialite' ),
							'default'     => 10,
						],
						[
							'field'       => 'name',
							'type'        => 'text',
							'title'       => _x( 'Name', 'Setting Title', 'geditorial-socialite' ),
							'description' => _x( 'Sets as title attribute of the social icon.', 'Setting Description', 'geditorial-socialite' ),
						],
						[
							'field'       => 'before_link',
							'type'        => 'text',
							'title'       => _x( 'Before Link', 'Setting Title', 'geditorial-socialite' ),
							'description' => _x( 'HTML opening before each social icon link.', 'Setting Description', 'geditorial-socialite' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'dir'         => 'ltr',
						],
						[
							'field'       => 'after_link',
							'type'        => 'text',
							'title'       => _x( 'After Link', 'Setting Title', 'geditorial-socialite' ),
							'description' => _x( 'HTML closing after each social icon link.', 'Setting Description', 'geditorial-socialite' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'dir'         => 'ltr',
						],
						[
							'field'       => 'before_image',
							'type'        => 'text',
							'title'       => _x( 'Before Image', 'Setting Title', 'geditorial-socialite' ),
							'description' => _x( 'HTML opening before each social icon image.', 'Setting Description', 'geditorial-socialite' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'dir'         => 'ltr',
						],
						[
							'field'       => 'after_image',
							'type'        => 'text',
							'title'       => _x( 'After Image', 'Setting Title', 'geditorial-socialite' ),
							'description' => _x( 'HTML closing after each social icon image.', 'Setting Description', 'geditorial-socialite' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'dir'         => 'ltr',
						],
					],
				],
			],
			'_supports' => [
				'shortcode_support',
			],
			'_constants' => [
				'main_shortcode_constant' => [ NULL, 'socialite' ],
			],
		];
	}

	protected function get_global_strings()
	{
		return [
			'titles' => [
				'twitter'   => _x( 'Twitter', 'Title', 'geditorial-socialite' ),
				'tiktok'    => _x( 'TikTok', 'Title', 'geditorial-socialite' ),
				'instagram' => _x( 'Instagram', 'Title', 'geditorial-socialite' ),
				'telegram'  => _x( 'Telegram', 'Title', 'geditorial-socialite' ),
				'facebook'  => _x( 'Facebook', 'Title', 'geditorial-socialite' ),
				'youtube'   => _x( 'YouTube', 'Title', 'geditorial-socialite' ),
				'aparat'    => _x( 'Aparat', 'Title', 'geditorial-socialite' ),
				'behkhaan'  => _x( 'Behkhaan', 'Title', 'geditorial-socialite' ),
				'eitaa'     => _x( 'Eitaa', 'Title', 'geditorial-socialite' ),
				'wikipedia' => _x( 'Wikipedia', 'Title', 'geditorial-socialite' ),
			],
			'descriptions' => [
				'twitter'   => _x( 'Handle or URL to an Twitter account.', 'Description', 'geditorial-socialite' ),
				'tiktok'    => _x( 'Handle or URL to an TikTok account.', 'Description', 'geditorial-socialite' ),
				'instagram' => _x( 'Handle or URL to an Instagram account.', 'Description', 'geditorial-socialite' ),
				'telegram'  => _x( 'Handle or URL to a Telegram user or channel.', 'Description', 'geditorial-socialite' ),
				'facebook'  => _x( 'Handle or URL to a Facebook profile or page.', 'Description', 'geditorial-socialite' ),
				'youtube'   => _x( 'Handle or URL to a YouTube channel.', 'Description', 'geditorial-socialite' ),
				'aparat'    => _x( 'Handle or URL to an Aparat channel.', 'Description', 'geditorial-socialite' ),
				'behkhaan'  => _x( 'Handle or URL to an Behkhaan profile.', 'Description', 'geditorial-socialite' ),
				'eitaa'     => _x( 'Handle or URL to a Eitaa user or channel.', 'Description', 'geditorial-socialite' ),
				'wikipedia' => _x( 'Handle or URL to a Wikipedia page.', 'Description', 'geditorial-socialite' ),
			],
			'misc' => [
				'social_column_title' => _x( 'Social', 'Column Title', 'geditorial-socialite' ),
			],
			'metabox' => [
				// 'metabox_title' => _x( 'Social', 'MetaBox: Title', 'geditorial-socialite' ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_shortcode' => 'socialite',
		];
	}

	public function init()
	{
		parent::init();

		$this->register_shortcode( 'main_shortcode' );
	}

	public function terms_init()
	{
		if ( empty( $this->get_setting( 'extra_meta_fields' ) ) )
			return;

		$this->action_module( 'wc_terms', 'introduction_description_after', 2, 8 );
		$this->filter_module( 'terms', 'supported_fields_raw', 1 );
		$this->filter_module( 'terms', 'supported_field_metatype', 3 );
		$this->filter_module( 'terms', 'supported_field_position', 3 );
		$this->filter_module( 'terms', 'manage_columns', 3 );
		$this->filter_module( 'terms', 'sortable_columns', 3 );
		$this->filter_module( 'terms', 'custom_column', 4 );

		foreach ( $this->get_setting( 'extra_meta_fields', [] ) as $field ) {
			add_filter( $this->hook_base( 'terms', 'field', $field, 'title' ), [ $this, 'terms_field_title' ], 12, 4 );
			add_filter( $this->hook_base( 'terms', 'field', $field, 'desc' ), [ $this, 'terms_field_desc' ], 12, 4 );
		}
	}

	private function _prep_fields_for_settings()
	{
		if ( ! gEditorial()->enabled( 'terms' ) )
			return []; // must be array to display `string_empty`

		$supported = [];

		foreach ( $this->supported as $field )
			$supported[$field] = $this->get_string( $field, FALSE, 'titles', $field );

		return $this->filters( 'supported_fields', $supported );
	}

	public function wc_terms_introduction_description_after( $term, $desc )
	{
		echo $this->_get_term_icons( $term, NULL, [
			'-icon-list',
			'-social-links',
		] );
	}

	public function terms_field_title( $string, $taxonomy, $field, $term )
	{
		return $this->get_string( $field, $taxonomy, 'titles', $string );
	}

	public function terms_field_desc( $string, $taxonomy, $field, $term )
	{
		return $this->get_string( $field, $taxonomy, 'descriptions', $string );
	}

	public function terms_supported_fields_raw( $supported )
	{
		return array_merge( $supported, $this->get_setting( 'extra_meta_fields', [] ) );
	}

	public function terms_supported_field_metatype( $metatype, $field, $taxonomy )
	{
		return in_array( $field, $this->supported ) ? 'code' : $metatype;
	}

	public function terms_supported_field_position( $position, $field, $taxonomy )
	{
		return in_array( $field, $this->supported ) ? FALSE : $position;
	}

	public function terms_manage_columns( $columns, $taxonomy, $supported )
	{
		if ( ! array_intersect( $supported, $this->supported ) )
			return $columns;

		return Core\Arraay::insert( $columns, [
			$this->classs() => $this->get_column_title( 'social', $taxonomy ),
		], 'posts', 'before' );
	}

	public function terms_sortable_columns( $columns, $taxonomy, $supported )
	{
		return $columns; // TODO
	}

	public function terms_custom_column( $column_name, $taxonomy, $supported, $term )
	{
		if ( $column_name !== $this->classs() )
			return;

		echo $this->_get_term_icons( $term, $this->supported, [
			'-icon-list',
			'-social-links',
		] );
	}

	private function _get_term_icons( $term, $fields = NULL, $extra = [] )
	{
		$list = [];

		if ( is_null( $fields ) )
			$fields = array_merge( [
				'contact', // adds before the list
			], $this->supported );

		foreach ( $fields as $field )
			if ( $meta = get_term_meta( $term->term_id, $field, TRUE ) )
				$list[$field] = $this->_get_field_link( $field,
					$this->_get_field_url( $meta, $field, $term->taxonomy ), $term->taxonomy );

		return $this->wrap( Core\HTML::renderList( $list ), $extra );
	}

	private function _get_field_url( $value, $key, $taxonomy = FALSE )
	{
		switch ( $key ) {
			case 'twitter'  :
			case 'tiktok'   :
			case 'facebook' :
			case 'instagram':
			case 'telegram' :
			case 'youtube'  :
			case 'aparat'   :
			case 'behkhaan' :
			case 'eitaa'    :
			case 'wikipedia':

				return Core\Third::getHandleURL( $value, $key );

			// Extra support for front-end only.
			case 'contact':
				return Core\Text::trim( $value );
		}

		return Core\HTML::escapeURL( $value );
	}

	// better to define here!
	private function _get_field_icon( $field, $taxonomy = FALSE )
	{
		$default = [ 'gridicons', 'share' ];

		switch ( $field ) {
			case 'twitter'  : return [ 'social-logos', 'x' ];
			case 'tiktok'   : return [ 'social-logos', 'tiktok' ];
			case 'instagram': return [ 'social-logos', 'instagram' ];
			case 'telegram' : return [ 'social-logos', 'telegram' ];
			case 'facebook' : return [ 'social-logos', 'facebook' ];
			case 'youtube'  : return [ 'social-logos', 'youtube' ];
			case 'aparat'   : return [ 'misc-24', 'aparat' ];
			case 'behkhaan' : return [ 'misc-32', 'behkhaan' ];
			case 'eitaa'    : return [ 'misc-48', 'eitaa' ];
			case 'wikipedia': return [ 'misc-16', 'wikipedia' ];
		}

		return Core\Icon::guess( $field, $default );
	}

	private function _get_field_link( $field, $url, $taxonomy = FALSE )
	{
		switch ( $field ) {

			// Extra support for front-end only.
			case 'contact':
				return gEditorial\Helper::prepContact( $url, NULL, '', TRUE );

			default:
				return $this->get_column_icon( $url,
					$this->_get_field_icon( $field, $taxonomy ),
					$this->get_string( $field, $taxonomy, 'titles', $field ),
					$taxonomy,
					[
						$this->classs( 'field' ),
						sprintf( '-field-%s', $field ),
						$taxonomy ? sprintf( '-taxonomy-%s', $taxonomy ) : '',
						Core\URL::isValid( $url ) ? '-valid-url' : '-invalid-url',
					]
				);
		}
	}

	// @SEE: https://codepen.io/geminorum/pen/xxrjYKK
	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '', // html after wrap
			'after'   => '', // html before wrap
			'class'   => '', // wrap css class
		], $atts, $this->constant( 'main_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		$list  = [];
		$icons = $this->get_setting( 'social_icons', [] );

		foreach ( Core\Arraay::sortByPriority( $icons, 'priority' ) as $raw ) {

			$row = self::atts( [
				'url'          => '#',
				'name'         => FALSE,
				'icon'         => '',
				'before_link'  => '',
				'after_link'   => '',
				'before_image' => '',
				'after_image'  => '',
			], $raw );

			if ( empty( $row['icon'] ) )
				continue;

			$src = Core\Text::has( $row['icon'], ':' )
				? Core\Icon::getBase64( ...explode( ':', $row['icon'], 2 ) ) // `icon_name:icon_set`
				: $row['icon']; // raw url

			$list[] = $row['before_link']
				.Core\HTML::tag( 'a', [
					'href'  => $row['url'],
					'title' => $row['name'],
				], $row['before_image']
					.Core\HTML::img( $src, '-socialite', $row['name'] ?: '' )
				.$row['after_image'] )
				.$row['after_link'];
		}

		if ( empty( $list ) )
			return $content;

		return gEditorial\ShortCode::wrap( Core\HTML::renderList( $list ), $tag, $args );
	}
}
