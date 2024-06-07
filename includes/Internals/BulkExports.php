<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\WordPress;

trait BulkExports
{

	protected function exports_get_export_buttons( $post, $context )
	{
		$html = '';

		foreach ( $this->exports_get_types( $context ) as $type => $type_args ) {

			$label = sprintf(
				/* translators: %1$s: icon markup, %2$s: export type title */
				_x( '%1$s Export: %2$s', 'Internal: Exports: Button Label', 'geditorial-admin' ),
				Helper::getIcon( 'download' ),
				$type_args['title']
			);

			$html.= Core\HTML::tag( 'a', [
				'href'  => $this->exports_get_type_download_link( $post->ID, $type, $context, $type_args['target'], $type_args['format'] ),
				'class' => [ 'button', 'button-small', '-button', '-button-icon', '-exportbutton', '-button-download' ],
				'title' => _x( 'Click to Download the Generated File', 'Internal: Exports: Button Title', 'geditorial-admin' ),
			], $label );
		}

		return $html;
	}

	protected function exports_get_types( $context )
	{
		$types = [
			'simple'   => [
				'title'  => _x( 'Simple', 'Internal: Export Type Title', 'geditorial-admin' ),
				'target' => 'paired',
				'format' => 'xlsx',
			],

			'advanced' => [
				'title'  => _x( 'Advanced', 'Internal: Export Type Title', 'geditorial-admin' ),
				'target' => 'paired',
				'format' => 'xlsx',
			],

			'full' => [
				'title'  => _x( 'Full', 'Internal: Export Type Title', 'geditorial-admin' ),
				'target' => 'paired',
				'format' => 'csv',
			],
		];

		return $this->filters( 'export_types', $types, $context );
	}

	protected function exports_get_type_download_link( $reference, $type, $context, $target = 'default', $format = 'xlsx', $extra = [] )
	{
		return add_query_arg( array_merge( [
			'action'  => $this->classs( 'exports' ),
			'ref'     => $reference,
			'target'  => $target,
			'type'    => $type,
			'context' => $context,
			'format'  => $format,
		], $extra ), get_admin_url() );
	}

	// NOTE: only fires on admin
	protected function exports_do_check_requests()
	{
		if ( $this->classs( 'exports' ) != self::req( 'action' ) )
			return FALSE;

		$reference = self::req( 'ref', NULL );
		$target    = self::req( 'target', 'default' );
		$type      = self::req( 'type', 'simple' );
		$context   = self::req( 'context', 'default' );
		$format    = self::req( 'format', 'xlsx' );

		if ( FALSE !== ( $data = $this->exports_get_export_data( $reference, $target, $type, $context, $format ) ) )
			Core\Text::download( $data, Core\File::prepName( $this->exports_get_export_filename( $reference, $target, $type, $context, $format ) ) );

		Core\WordPress::redirectReferer( 'wrong' );
	}

	protected function exports_get_export_filename( $reference, $target, $type, $context, $format )
	{
		if ( ! $post = WordPress\Post::get( $reference ) )
			return sprintf( '%s-%s', $context, $type );

		return vsprintf( '%s-%s-%s.%s', [
			$post->post_name ?: $post->post_title,
			$context,
			$type,
			in_array( $format, [
				'csv',
				'xlsx',
			], TRUE ) ? $format : 'txt',
		] );
	}

	protected function exports_prep_posts_for_export( $posttypes, $reference, $posts, $props, $fields = [], $metas = [], $taxes = [], $format = 'xlsx' )
	{
		$data = [];

		foreach ( $posts as $post ) {

			$row = [];

			foreach ( $props as $prop ) {

				if ( 'post_name' === $prop )
					$row[] = urldecode( $post->{$prop} );

				else if ( property_exists( $post, $prop ) )
					$row[] = trim( $post->{$prop} );

				else
					$row[] = ''; // unknown field!
			}

			foreach ( $fields as $field )
				$row[] = Template::getMetaField( $field, [ 'id' => $post, 'context' => 'export' ], FALSE ) ?: '';

			$saved = get_post_meta( $post->ID );

			foreach ( $metas as $meta )
				$row[] = ( empty( $saved[$meta][0] ) ? '' : trim( $saved[$meta][0] ) ) ?: '';

			foreach ( $taxes as $tax )
				$row[] = WordPress\Strings::getPiped( WordPress\Taxonomy::getPostTerms( $tax, $post, FALSE, 'name' ) );

			$data[] = $row;
		}

		switch ( $format ) {

			case 'csv':

				$headers = array_merge(
					$props,
					Core\Arraay::prefixValues( $fields, 'field__' ),
					Core\Arraay::prefixValues( $metas, 'meta__' ),
					Core\Arraay::prefixValues( $taxes, 'taxonomy__' )
				);


				return Core\Text::toCSV( [ $headers ] + $data );

			case 'xlsx':

				$headers = [];

				foreach ( $props as $prop )
					$headers[] = Info::getPosttypePropTitle( $prop, 'export' ) ?: $prop;

				foreach ( $fields as $field )
					$headers[] = Services\PostTypeFields::isAvailable( $field, $posttypes[0], 'meta' )['title'];

				foreach ( $metas as $meta )
					$headers[] = $this->filters( 'export_get_meta_title', $meta, $meta, $posttypes ); // FIXME: move-up!

				foreach ( $taxes as $taxonomy )
					$headers[] = Helper::getTaxonomyLabel( $taxonomy, 'extended_label', 'name', $taxonomy );

				return Helper::generateXLSX( $data, $headers, WordPress\Post::title( $reference, NULL, FALSE ) );
		}

		return $data;
	}

	// TODO: export target: `paired_by_term`
	protected function exports_get_export_data( $reference, $target, $type, $context, $format )
	{
		$data = FALSE;

		switch ( $target ) {

			case 'paired':

				if ( ! $constants = $this->paired_get_constants() )
					break;

				if ( ! $posttypes = $this->posttypes() )
					break;

				if ( ! $paired = $this->paired_get_to_term( (int) $reference, $constants[0], $constants[1] ) )
					break;

				$args = $this->filters( 'export_query_args', [
					'posts_per_page' => -1,
					'orderby'        => [ 'menu_order', 'date' ],
					'order'          => 'ASC',
					'post_type'      => $posttypes,
					'post_status'    => [ 'publish', 'future' ],
					'tax_query'      => [ [
						'taxonomy' => $this->constant( $constants[1] ),
						'field'    => 'id',
						'terms'    => [ $paired->term_id ],
					] ],
				], $reference, $target, $type, $context );

				$posts = get_posts( $args );

				if ( empty( $posts ) )
					break;

				// TODO: support field meta fro paired

				$props  = $this->exports_get_post_props( $posttypes, $reference, $target, $type, $context, $format );
				$fields = $this->exports_get_post_fields( $posttypes, $reference, $target, $type, $context, $format );
				$metas  = $this->exports_get_post_metas( $posttypes, $reference, $target, $type, $context, $format );
				$taxes  = $this->exports_get_post_taxonomies( $posttypes, $reference, $target, $type, $context, $format );
				$data   = $this->exports_prep_posts_for_export( $posttypes, $reference, $posts, $props, $fields, $metas, $taxes, $format );

				break;
		}

		return $this->filters( 'export_get_data', $data, $reference, $target, $type, $context, $format );
	}

	protected function exports_get_post_props( $posttypes, $reference, $target, $type, $context, $format )
	{
		$list = [
			'ID',
			'post_title',
		];

		switch ( $type ) {

			case 'simple':

				break;

			case 'advanced':

				$list = array_merge( $list, [
					'post_date',
					'post_content',
					'post_excerpt',
					'post_type',
				] );
				break;

			case 'full':

				$list = array_merge( $list, [
					'post_author',
					'post_date',
					'post_content',
					'post_excerpt',
					'post_status',
					'post_name',
					'post_parent',
					'menu_order',
					'post_type',
				] );
				break;
		}

		return $this->filters( 'export_post_props', Core\Arraay::prepString( $list ), $posttypes, $reference, $target, $type, $context, $format );
	}

	protected function exports_get_post_fields( $posttypes, $reference, $target, $type, $context, $format )
	{
		$list = [];

		foreach ( $posttypes as $posttype ) {

			if ( ! WordPress\PostType::exists( $posttype ) )
				continue;

			$fields = WordPress\PostType::supports( $posttype, 'meta_fields' );

			if ( empty( $fields ) )
				continue;

			switch ( $type ) {

				case 'simple':

					$keys = [
						'first_name',
						'last_name',
						'fullname',
						'identity_number',
					];

					$keeps = Core\Arraay::keepByKeys( $fields, $keys );
					$list  = array_merge( $list, array_keys( $keeps ) );

					break;

				case 'advanced':

					$keys = [
						'first_name',
						'last_name',
						'fullname',
						'father_name',
						'identity_number',
						'mobile_number',
						'date_of_birth',
					];

					$keeps = Core\Arraay::keepByKeys( $fields, $keys );
					$list  = array_merge( $list, array_keys( $keeps ) );

					break;

				case 'full':

					$list = array_merge( $list, array_keys( $fields ) );

					break;
			}
		}

		return $this->filters( 'export_post_fields', Core\Arraay::prepString( $list ), $posttypes, $reference, $target, $type, $context, $format );
	}

	protected function exports_get_post_metas( $posttypes, $reference, $target, $type, $context, $format )
	{
		$list = [];

		foreach ( $posttypes as $posttype ) {

			switch ( $type ) {

				case 'simple':

					break;

				case 'advanced':

					$list = array_merge( $list, [] );
					break;

				case 'full':

					$list = array_merge( $list, [] );
					break;
			}
		}

		return $this->filters( 'export_post_metas', Core\Arraay::prepString( $list ), $posttypes, $reference, $target, $type, $context, $format );
	}

	protected function exports_get_post_taxonomies( $posttypes, $reference, $target, $type, $context, $format )
	{
		$list = [];

		foreach ( $posttypes as $posttype ) {

			if ( ! WordPress\PostType::exists( $posttype ) )
				continue;

			switch ( $type ) {

				case 'simple':

					break;

				case 'advanced':

					$list = array_merge( $list, [
						'post_tag',
					] );

					break;

				case 'full':

					$list = array_merge( $list, get_object_taxonomies( $posttype ) );
					break;
			}
		}

		return $this->filters( 'export_post_taxonomies', Core\Arraay::prepString( $list ), $posttypes, $reference, $target, $type, $context, $format );
	}
}
