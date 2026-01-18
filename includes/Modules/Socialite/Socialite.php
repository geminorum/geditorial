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
		'fidibo',
		'goodreads',
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
				'social-network',
				'site-identity',
				'termmeta',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
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
							'field_class' => [ 'regular-text', 'url-text' ],
							'ortho'       => 'slug',
						],
						[
							'field'       => 'icon',
							'type'        => 'text',
							'title'       => _x( 'Icon', 'Setting Title', 'geditorial-socialite' ),
							'description' => _x( 'Sets the icon image of the social icon.', 'Setting Description', 'geditorial-socialite' ),
							'field_class' => [ 'regular-text', 'code-text' ],
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
						],
						[
							'field'       => 'after_link',
							'type'        => 'text',
							'title'       => _x( 'After Link', 'Setting Title', 'geditorial-socialite' ),
							'description' => _x( 'HTML closing after each social icon link.', 'Setting Description', 'geditorial-socialite' ),
							'field_class' => [ 'regular-text', 'code-text' ],
						],
						[
							'field'       => 'before_image',
							'type'        => 'text',
							'title'       => _x( 'Before Image', 'Setting Title', 'geditorial-socialite' ),
							'description' => _x( 'HTML opening before each social icon image.', 'Setting Description', 'geditorial-socialite' ),
							'field_class' => [ 'regular-text', 'code-text' ],
						],
						[
							'field'       => 'after_image',
							'type'        => 'text',
							'title'       => _x( 'After Image', 'Setting Title', 'geditorial-socialite' ),
							'description' => _x( 'HTML closing after each social icon image.', 'Setting Description', 'geditorial-socialite' ),
							'field_class' => [ 'regular-text', 'code-text' ],
						],
					],
				],
			],
			'posttypes_option' => 'posttypes_option',
			'_taxonomies' => [
				[
					'field'        => 'extra_meta_fields',
					'type'         => 'checkbox-panel',
					'title'        => _x( 'Supported Fields', 'Setting Title', 'geditorial-socialite' ),
					'description'  => _x( 'Appends custom meta fields for social services to taxonomies. <em>Terms</em> module needs to be enabled.', 'Setting Description', 'geditorial-socialite' ),
					'string_empty' => _x( 'The Terms module is not available!', 'Setting String Empty', 'geditorial-socialite' ),
					'values'       => $this->_prep_fields_for_settings(),
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
				'fidibo'    => _x( 'Fidibo', 'Title', 'geditorial-socialite' ),
				'goodreads' => _x( 'Goodreads', 'Title', 'geditorial-socialite' ),
				'eitaa'     => _x( 'Eitaa', 'Title', 'geditorial-socialite' ),
				'wikipedia' => _x( 'Wikipedia', 'Title', 'geditorial-socialite' ),
				'_ical'      => _x( 'iCal', 'Title', 'geditorial-socialite' ),
			],
			'descriptions' => [
				'twitter'   => _x( 'Handle or URL to a Twitter account.', 'Description', 'geditorial-socialite' ),
				'tiktok'    => _x( 'Handle or URL to a TikTok account.', 'Description', 'geditorial-socialite' ),
				'instagram' => _x( 'Handle or URL to an Instagram account.', 'Description', 'geditorial-socialite' ),
				'telegram'  => _x( 'Handle or URL to a Telegram user or channel.', 'Description', 'geditorial-socialite' ),
				'facebook'  => _x( 'Handle or URL to a Facebook profile or page.', 'Description', 'geditorial-socialite' ),
				'youtube'   => _x( 'Handle or URL to a YouTube channel.', 'Description', 'geditorial-socialite' ),
				'aparat'    => _x( 'Handle or URL to an Aparat channel.', 'Description', 'geditorial-socialite' ),
				'behkhaan'  => _x( 'Handle or URL to a Behkhaan profile page.', 'Description', 'geditorial-socialite' ),
				'fidibo'    => _x( 'ID or URL to a Fidibo profile page.', 'Description', 'geditorial-socialite' ),
				'goodreads' => _x( 'URL to a Goodreads profile page.', 'Description', 'geditorial-socialite' ),
				'eitaa'     => _x( 'Handle or URL to an Eitaa user or channel.', 'Description', 'geditorial-socialite' ),
				'wikipedia' => _x( 'Handle or URL to a Wikipedia page.', 'Description', 'geditorial-socialite' ),
			],
			'settings' => [
				'post_types_after' => _x( 'Appends custom meta fields for social services to post-types. <em>Meta</em> module needs to be enabled.', 'Setting Description', 'geditorial-socialite' ),
			],
			'misc' => [
				'social_column_title' => _x( 'Social', 'Column Title', 'geditorial-socialite' ),
			],
			'metabox' => [
				// 'metabox_title' => _x( 'Social', 'MetaBox: Title', 'geditorial-socialite' ),
			],
		];
	}

	protected function get_global_fields()
	{
		// Bail if no post-type supported.
		if ( empty( $this->posttypes() ) )
			return [];

		// NOTE: module strings are not available at this point
		$strings   = $this->filters( 'strings', $this->get_global_strings(), $this->module );
		$supported = [];

		foreach ( $this->supported as $field )
			$supported[$field] = [
				'title'       => $strings['titles'][$field] ?? $field,
				'description' => $strings['descriptions'][$field] ?? '',
				'icon'        => $this->_get_field_icon( $field, '_supported' ),
				'type'        => 'code',
				'order'       => 1800,
			];

		return [
			'meta' => [
				'_supported' => $supported,
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

		$this->action( 'widget_the_term_after', 5, 22, FALSE, 'gtheme' );
		$this->action( 'term_intro_description_after', 5, 8, FALSE, $this->base );
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

	public function meta_init()
	{
		if ( empty( $this->posttypes() ) )
			return;

		$this->add_posttype_fields_supported();

		$this->filter( 'prep_meta_row', 2, 12, 'module', $this->base );
		$this->filter( 'meta_field', 7, 9, FALSE, $this->base );
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

	// @hook `gtheme_widget_the_term_after`
	public function widget_the_term_after( $term, $instance, $name, $desc, $image )
	{
		if ( ! $desc && ! $image && ! empty( $instance['hide_no_desc'] ) )
			return;

		echo $this->_get_term_icons( $term, 'widget', NULL, [
			'-icon-list',
			'-social-links',
		] );
	}

	public function term_intro_description_after( $term, $desc, $image, $args, $module )
	{
		if ( ! $desc && ! $image && empty( $args['heading'] ) )
			return;

		echo $this->_get_term_icons( $term, $args['context'], NULL, [
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
		if ( ! Core\Arraay::exists( $supported, $this->supported ) )
			return $columns;

		return Core\Arraay::insert( $columns, [
			// $this->classs() => $this->get_column_title( 'social', $taxonomy ),
			$this->classs() => $this->get_column_title_icon( 'social', $taxonomy ),
		], 'posts', 'before' );
	}

	public function terms_sortable_columns( $columns, $taxonomy, $supported )
	{
		return $columns; // TODO
	}

	public function terms_custom_column( $column, $taxonomy, $supported, $term )
	{
		if ( $column === $this->classs() )
			echo $this->_get_term_icons( $term, 'column', $this->supported, [
				'-icon-list',
				'-social-links',
			] );
	}

	private function _get_term_icons( $term, $context = NULL, $fields = NULL, $extra = [] )
	{
		$list = [];

		if ( is_null( $fields ) )
			$fields = array_merge( [
				// adds before the list
				'_contact',
			], $this->supported, [
				// adds after the list
				'_ical',
			] );

		foreach ( $fields as $field )
			if ( $url = $this->_get_field_url( $field, $term, $context ) )
				$list[$field] = $this->_get_field_link( $field, $url, $term, $context );

		return $this->wrap( Core\HTML::rows( $list ), $extra );
	}

	private function _get_field_url( $field, $term, $context = NULL )
	{
		if ( '_ical' === $field )
			return Services\Calendars::linkTermCalendar( $term, $context );

		if ( in_array( $field, [ '_contact' ], TRUE ) )
			$field = Core\Text::stripPrefix( $field, '_' );

		if ( ! $metakey = Services\TaxonomyFields::getTermMetaKey( $field, $term->taxonomy ) )
			return FALSE;

		if ( ! $meta = get_term_meta( $term->term_id, $metakey, TRUE ) )
			return FALSE;

		switch ( $field ) {
			case 'twitter'  :
			case 'tiktok'   :
			case 'facebook' :
			case 'instagram':
			case 'telegram' :
			case 'youtube'  :
			case 'aparat'   :
			case 'behkhaan' :
			case 'fidibo'   :
			case 'goodreads':
			case 'eitaa'    :
			case 'wikipedia':

				return Core\Third::getHandleURL( $meta, $field );

			// Extra support for front-end only.
			case 'contact':
				return Core\Text::trim( $meta );
		}

		return Core\HTML::escapeURL( $meta );
	}

	// better to define here!
	private function _get_field_icon( $field, $taxonomy = FALSE, $context = NULL )
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
			case 'fidibo'   : return [ 'misc-16', 'fidibo' ];
			case 'goodreads': return [ 'misc-24', 'goodreads' ];
			case 'eitaa'    : return [ 'misc-48', 'eitaa' ];
			case 'wikipedia': return [ 'misc-16', 'wikipedia' ];
			case '_ical':     return [ 'misc-16', 'calendar-plus-fill' ];
		}

		return Core\Icon::guess( $field, $default );
	}

	private function _get_field_link( $field, $url, $term, $context = NULL )
	{
		switch ( $field ) {

			// Extra support for front-end only.
			case '_contact':
				return gEditorial\Helper::prepContact( $url, NULL, '', TRUE );

			case '_ical':
			default:
				return $this->get_column_icon( $url,
					$this->_get_field_icon( $field, $term->taxonomy, $context ),
					$this->get_string( $field, $term->taxonomy, 'titles', $field ),
					$term->taxonomy,
					[
						$this->classs( 'field' ),
						sprintf( '-field-%s', $field ),
						sprintf( '-taxonomy-%s', $term->taxonomy ),
						$context ? sprintf( '-%s', $context ) : '',
						Core\URL::isValid( $url ) ? '-valid-url' : '-invalid-url',
					]
				);
		}
	}

	public function prep_meta_row_module( $value, $field_key = NULL, $field = [], $raw = NULL )
	{
		switch ( $field_key ) {

			case 'twitter'  :
			case 'tiktok'   :
			case 'facebook' :
			case 'instagram':
			case 'telegram' :
			case 'youtube'  :
			case 'aparat'   :
			case 'behkhaan' :
			case 'fidibo'   :
			case 'goodreads':
			case 'eitaa'    :
			case 'wikipedia':

				$url = Core\Third::getHandleURL( $raw ?: $value, $field_key );

				return Core\HTML::tag( 'a', [
					'href'   => $url,
					'title'  => Core\URL::getDomain( $url ),
					'class'  => Core\URL::isValid( $url ) ? '-is-valid' : '-not-valid',
					'target' => '_blank',
				], Core\File::basename( $url ) );
		}

		return $value;
	}

	// @REF: `Template::getMetaField()`
	public function meta_field( $meta, $field, $post, $args, $raw, $field_args, $context )
	{
		return $this->prep_meta_row_module( $meta, $field, $field_args, $raw );
	}

	// @SEE: https://codepen.io/geminorum/pen/xxrjYKK
	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',
			'after'   => '',
			'class'   => '',
		], $atts, $tag ?: $this->constant( 'main_shortcode' ) );

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
				: $row['icon']; // raw URL

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

		return gEditorial\ShortCode::wrap(
			Core\HTML::rows( $list ),
			$tag,
			$args
		);
	}
}
