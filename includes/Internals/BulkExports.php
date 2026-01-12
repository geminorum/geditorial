<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait BulkExports
{
	protected function exports_get_export_buttons( $reference, $context, $target = NULL )
	{
		$html = '';

		foreach ( $this->exports_get_types( $context, $target ) as $type => $type_args )
			$html.= Core\HTML::button(
				sprintf(
					/* translators: `%1$s`: icon markup, `%2$s`: export type title */
					_x( '%1$s Export: %2$s', 'Internal: Exports: Button Label', 'geditorial-admin' ),
					Services\Icons::get( 'download' ),
					$type_args['title']
				),
				$this->exports_get_type_download_link( $reference, $type, $context, $type_args['target'], $type_args['format'] ),
				_x( 'Click to Download the Generated File', 'Internal: Exports: Button Title', 'geditorial-admin' )
			);

		return $html;
	}

	protected function exports_get_export_links( $reference, $context, $target = NULL )
	{
		$links = [];

		foreach ( $this->exports_get_types( $context, $target ) as $type => $type_args ) {

			$name  = sprintf( 'export_%s', $type );
			$label = sprintf(
				/* translators: `%s`: export type title */
				_x( 'Export: %s', 'Internal: Exports: Link Label', 'geditorial-admin' ),
				$type_args['title']
			);

			$links[$name] = Core\HTML::tag( 'a', [
				'href'  => $this->exports_get_type_download_link( $reference, $type, $context, $type_args['target'], $type_args['format'] ),
				'class' => [ '-exportlink' ],
				'title' => _x( 'Click to Download the Generated File', 'Internal: Exports: Link Title', 'geditorial-admin' ),
			], $label );
		}

		return $links;
	}

	// TODO: support `taxonomy` target
	// TODO: support `subcontent` target
	// TODO: support `subcontent_from_paired` target
	protected function exports_get_types( $context, $target = NULL )
	{
		$types = [
			'paired_simple'   => [
				'title'  => _x( 'Simple', 'Internal: Export Type Title', 'geditorial-admin' ),
				'target' => 'paired',
				'format' => 'xlsx',
			],

			'paired_advanced' => [
				'title'  => _x( 'Advanced', 'Internal: Export Type Title', 'geditorial-admin' ),
				'target' => 'paired',
				'format' => 'xlsx',
			],

			'paired_full' => [
				'title'  => _x( 'Full', 'Internal: Export Type Title', 'geditorial-admin' ),
				'target' => 'paired',
				'format' => 'csv',
			],

			'posttype_simple'   => [
				'title'  => _x( 'Simple', 'Internal: Export Type Title', 'geditorial-admin' ),
				'target' => 'posttype',
				'format' => 'xlsx',
			],

			'posttype_advanced' => [
				'title'  => _x( 'Advanced', 'Internal: Export Type Title', 'geditorial-admin' ),
				'target' => 'posttype',
				'format' => 'xlsx',
			],

			'posttype_full' => [
				'title'  => _x( 'Full', 'Internal: Export Type Title', 'geditorial-admin' ),
				'target' => 'posttype',
				'format' => 'csv',
			],

			'assigned_simple'   => [
				'title'  => _x( 'Simple', 'Internal: Export Type Title', 'geditorial-admin' ),
				'target' => 'assigned',
				'format' => 'xlsx',
			],

			'assigned_advanced' => [
				'title'  => _x( 'Advanced', 'Internal: Export Type Title', 'geditorial-admin' ),
				'target' => 'assigned',
				'format' => 'xlsx',
			],

			'assigned_full' => [
				'title'  => _x( 'Full', 'Internal: Export Type Title', 'geditorial-admin' ),
				'target' => 'assigned',
				'format' => 'csv',
			],

			'globalsummary_simple' => [
				'title'  => _x( 'Simple', 'Internal: Export Type Title', 'geditorial-admin' ),
				'target' => 'globalsummary',
				'format' => 'xlsx',
			],
		];

		if ( $target && 'default' !== $target )
			$types = Core\Arraay::filter( $types, [ 'target' => $target ] );

		return $this->filters( 'export_types', $types, $context, $target );
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

		WordPress\Redirect::doReferer( 'wrong' );
	}

	// TODO: apply general filter with token support and override `Core\File::prepName()`
	protected function exports_get_export_filename( $reference, $target, $type, $context, $format )
	{
		$ext = in_array( $format, [
			'csv',
			'xlsx',
		], TRUE ) ? $format : 'txt';

		switch ( $target ) {

			case 'posttype':

				return sprintf( '%s-%s-%s.%s', $reference, $context, $type, $ext );

			case 'assigned':

				if ( ! $term = WordPress\Term::get( (int) $reference ) )
					break;

				return vsprintf( '%s-%s-%s-%s.%s', [
					$term->taxonomy,
					$term->slug ?: $term->name,
					$context,
					$type,
					$ext,
				] );

			case 'globalsummary':
			case 'paired':

				if ( ! $post = WordPress\Post::get( $reference ) )
					break;

				return vsprintf( '%s-%s-%s.%s', [
					$post->post_name ?: $post->post_title,
					$context,
					$type,
					$ext,
				] );
		}

		return sprintf( '%s-%s.%s', $context, $type, $ext );
	}

	protected function exports_prep_posts_for_export( $posttypes, $reference, $target, $type, $posts, $props, $fields = [], $units = [], $metas = [], $taxes = [], $customs = [], $format = 'xlsx' )
	{
		$data = [];

		foreach ( $posts as $post ) {

			$row = [];

			foreach ( $props as $prop => $prop_title ) {

				if ( 'post_name' === $prop )
					$row[] = urldecode( $post->{$prop} );

				if ( 'post_type' === $prop )
					$row[] = Services\CustomPostType::getLabel( $post->post_type, 'extended_label', 'singular', $post->post_type );

				else if ( in_array( $prop, [
					'post_date',
					'post_date_gmt',
					'post_modified',
					'post_modified_gmt',
				], TRUE ) )
					$row[] = $this->exports_generate_date_value( $post->{$prop}, $prop );

				else if ( property_exists( $post, $prop ) )
					$row[] = trim( $post->{$prop} );

				else
					$row[] = ''; // unknown field!
			}

			foreach ( $fields as $field => $field_title )
				$row[] = gEditorial\Template::getMetaField( $field, [ 'id' => $post, 'context' => 'export' ], FALSE, 'meta' ) ?: '';

			foreach ( $units as $unit => $unit_title )
				$row[] = gEditorial\Template::getMetaField( $unit, [ 'id' => $post, 'context' => 'export' ], FALSE, 'units' ) ?: '';

			$saved = get_post_meta( $post->ID );

			foreach ( $metas as $meta => $meta_title )
				$row[] = ( empty( $saved[$meta][0] ) ? '' : trim( $saved[$meta][0] ) ) ?: '';

			foreach ( $taxes as $taxonomy => $taxonomy_title )
				// FIXME: check for `auto_set_parent_terms` on tax object then exclude parents, just like paired box
				$row[] = WordPress\Strings::getPiped( WordPress\Taxonomy::getPostTerms( $taxonomy, $post, FALSE, 'name' ) );

			foreach ( $customs as $custom => $custom_title )
				$row[] = apply_filters( $this->hook_base( 'bulk_exports', 'prep_custom_for_post' ), '', $custom, $post, $reference, $target, $type, $format );

			$data[] = $row;
		}

		switch ( $format ) {

			case 'csv':

				$headers = array_merge(
					array_keys( $props ),
					Core\Arraay::prefixValues( array_keys( $fields ), 'field__' ),
					Core\Arraay::prefixValues( array_keys( $units ), 'unit__' ),
					Core\Arraay::prefixValues( array_keys( $metas ), 'meta__' ),
					Core\Arraay::prefixValues( array_keys( $taxes ), 'taxonomy__' ),
					Core\Arraay::prefixValues( array_keys( $customs ), 'custom__' )
				);

				return Core\Text::toCSV( array_merge( [ $headers ], $data ) );

			case 'xlsx':

				$headers = [];

				foreach ( $props as $prop => $prop_title )
					$headers[$prop] = $this->exports_generate_column_header( $headers,
						$prop_title ?? gEditorial\Info::getPosttypePropTitle( $prop, $posttypes[0], 'export' ) ?: $prop );

				foreach ( $fields as $field => $field_title )
					$headers['field__'.$field] = $this->exports_generate_column_header( $headers,
						$field_title ?? Services\PostTypeFields::getExportTitle( $field, $posttypes[0], 'meta' ) );

				foreach ( $units as $unit => $unit_title )
					$headers['unit__'.$unit] = $this->exports_generate_column_header( $headers,
						$unit_title ?? Services\PostTypeFields::getExportTitle( $unit, $posttypes[0], 'units' ) );

				foreach ( $metas as $meta => $meta_title )
					$headers['meta__'.$meta] = $this->exports_generate_column_header( $headers,
						$meta_title ?? $this->filters( 'export_get_meta_title', $meta, $meta, $posttypes ) ); // FIXME: move-up the filter!

				foreach ( $taxes as $taxonomy => $taxonomy_title )
					$headers['taxonomy__'.$taxonomy] = $this->exports_generate_column_header( $headers,
						$taxonomy_title ?? Services\CustomTaxonomy::getLabel( $taxonomy, 'extended_label', 'name', $taxonomy ) );

				foreach ( $customs as $custom => $custom_title )
					$headers['custom__'.$custom] = $this->exports_generate_column_header( $headers,
						$custom_title ?? $this->filters( 'export_get_custom_title', $custom, $custom, $posttypes ) ); // FIXME: move-up the filter!

				if ( 'assigned' === $target )
					$sheet_title = sprintf( '%s — %s',
						Services\CustomTaxonomy::getLabel( WordPress\Term::get( (int) $reference ), 'extended_label' ),
						WordPress\Term::title( (int) $reference, NULL, FALSE ),
					);

				else if ( 'posttype' === $target )
					$sheet_title = Services\CustomPostType::getLabel( $reference, 'extended_label', 'name', $this->module->title );

				else
					$sheet_title = WordPress\Post::title( $reference, NULL, FALSE );

				return gEditorial\Parser::toXLSX_Legacy(
					$data,
					array_values( $headers ),
					$sheet_title,
					array_values( $this->exports_generate_column_widths( $headers, $posttypes ) )
				);
		}

		return $data;
	}

	protected function exports_generate_date_value( $data, $prop )
	{
		return gEditorial\Datetime::prepForInput( $data,
			gEditorial\Datetime::isDateOnly( $data ) ? 'Y/n/j' : 'Y/n/j H:i',
			$this->default_calendar()
		);
	}

	// Prevents the duplicate headers to avoid messing up the excel exports.
	protected function exports_generate_column_header( $headers, $header )
	{
		static $counter = [];

		if ( ! Core\Arraay::has( $header, $headers ) )
			return $header;

		if ( empty( $counter[$header] ) )
			$counter[$header] = 2;
		else
			++$counter[$header];

		return sprintf(
			/* translators: `%1$s`: column title, `%2$s`: column counter */
			_x( '%1$s (%2$s)', 'Internal: Exports: Column Header', 'geditorial-admin' ),
			$header,
			Core\Number::localize( $counter[$header] )
		);
	}

	protected function exports_generate_column_widths( $headers, $posttypes, $default = 20 )
	{
		$widths = [];
		$fields = Services\PostTypeFields::getEnabled( $posttypes[0], 'meta' );
		$units  = Services\PostTypeFields::getEnabled( $posttypes[0], 'units' );

		foreach ( $headers as $header => $title ) {

			$width = NULL;

			if ( in_array( $header, [
					'post_title',
					'post_excerpt',
				], TRUE ) ) {

				$width = $default * 3;

			} else if ( Core\Text::starts( $header, 'field__' ) ) {

				$field = Core\Text::stripPrefix( $header, 'field__' );

				if ( array_key_exists( $field, $fields ) )
					$width = $fields[$field]['data_length'] ?? $this->exports_generate_column_widths_for_fields( $field, $width, 'meta' );

			} else if ( Core\Text::starts( $header, 'unit__' ) ) {

				$field = Core\Text::stripPrefix( $header, 'unit__' );

				if ( array_key_exists( $field, $units ) )
					$width = $units[$field]['data_length'] ?? $this->exports_generate_column_widths_for_fields( $field, $width, 'units' );

			} else if ( Core\Text::starts( $header, 'taxonomy__' ) ) {

				$taxonomy = WordPress\Taxonomy::object( Core\Text::stripPrefix( $header, 'taxonomy__' ) );

				// TODO: calculate average taxonomy term name length from db for fallback
				if ( ! empty( $taxonomy->data_length ) )
					$width = $taxonomy->data_length;
			}

			$widths[$header] = $width ?? $default;
		}

		return apply_filters( $this->hook_base( 'bulk_exports', 'post_column_widths' ), $widths, $headers, $default );
	}

	protected function exports_generate_column_widths_for_fields( $field, $default = 20, $module = 'meta', $padding = 4 )
	{
		if ( ! $metakey = Services\PostTypeFields::getPostMetaKey( $field, $module ) )
			return $default;

		if ( ! $average = WordPress\PostType::getMetaAverageDataLength( $metakey ) )
			return $default;

		return Core\Number::round( $average + $padding ) ?: $default;
	}

	// TODO: support field meta from paired
	protected function exports_generate_export_data( $posts, $posttypes, $reference, $target, $type, $context, $format )
	{
		if ( empty( $posts ) )
			return FALSE;

		$props   = $this->exports_get_post_props( $posttypes, $reference, $target, $type, $context, $format );
		$fields  = $this->exports_get_post_fields( $posttypes, $reference, $target, $type, $context, $format );
		$units   = $this->exports_get_post_units( $posttypes, $reference, $target, $type, $context, $format );
		$metas   = $this->exports_get_post_metas( $posttypes, $reference, $target, $type, $context, $format );
		$taxes   = $this->exports_get_post_taxonomies( $posttypes, $reference, $target, $type, $context, $format );
		$customs = $this->exports_get_post_customs( $posttypes, $reference, $target, $type, $context, $format );
		$data    = $this->exports_prep_posts_for_export( $posttypes, $reference, $target, $type, $posts, $props, $fields, $units, $metas, $taxes, $customs, $format );

		return $data;
	}

	// TODO: export target: `paired_by_term`
	protected function exports_get_export_data( $reference, $target, $type, $context, $format )
	{
		$data = FALSE;

		switch ( $target ) {

			case 'assigned':

				if ( ! $term = WordPress\Term::get( (int) $reference ) )
					break;

				if ( ! $object = WordPress\Taxonomy::object( $term ) )
					break;

				if ( ! WordPress\Term::can( $term, 'assign_term' ) )
					break;

				$posttypes = (array) $object->object_type;

				$args = $this->filters( 'export_query_args', [
					'posts_per_page' => -1,
					'orderby'        => [ 'menu_order', 'date' ],
					'order'          => 'ASC',
					'post_type'      => $posttypes,
					'post_status'    => WordPress\Status::acceptable( $posttypes, 'query', [ 'pending', 'draft' ] ),
					'tax_query'      => [ [
						'taxonomy' => $object->name,
						'field'    => 'term_id',
						'terms'    => [ $term->term_id ],

						'include_children' => FALSE, // @REF: https://docs.wpvip.com/code-quality/term-queries-should-consider-include_children-false/
					] ],
				], $reference, $target, $type, $context );

				$data = $this->exports_generate_export_data(
					get_posts( $args ),
					$posttypes,
					$reference,
					$target,
					$type,
					$context,
					$format
				);

				break;

			case 'posttype':

				if ( ! $posttype = WordPress\PostType::object( $reference ) )
					break;

				if ( ! WordPress\PostType::can( $posttype, 'read' ) )
					break;

				$posttypes = [ $posttype->name ];

				$args = $this->filters( 'export_query_args', [
					'posts_per_page' => -1,
					'orderby'        => [ 'menu_order', 'date' ],
					'order'          => 'ASC',
					'post_type'      => $posttypes,
					'post_status'    => WordPress\Status::acceptable( $posttypes, 'query', [ 'pending', 'draft' ] ),
				], $reference, $target, $type, $context );

				$data = $this->exports_generate_export_data(
					get_posts( $args ),
					$posttypes,
					$reference,
					$target,
					$type,
					$context,
					$format
				);

				break;

			case 'globalsummary':

				if ( ! $post = WordPress\Post::get( $reference ) )
					break;

				$posts = Services\Paired::getGlobalSummaryForPost( $post, $context );

				$data = $this->exports_generate_export_data(
					$posts,
					Core\Arraay::pluck( $posts, 'post_type' ),
					$reference,
					$target,
					$type,
					$context,
					$format
				);

				break;

			case 'paired':

				if ( ! $this->_paired || ! method_exists( $this, 'paired_get_constants' ) )
					break;

				if ( ! $constants = $this->paired_get_constants() )
					break;

				if ( ! WordPress\PostType::can( $this->constant( $constants[0] ), 'read' ) )
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
					'post_status'    => WordPress\Status::acceptable( $posttypes, 'query', [ 'pending', 'draft' ] ),
					'tax_query'      => [ [
						'taxonomy' => $this->constant( $constants[1] ),
						'field'    => 'term_id',
						'terms'    => [ $paired->term_id ],

						'include_children' => FALSE, // @REF: https://docs.wpvip.com/code-quality/term-queries-should-consider-include_children-false/
					] ],
				], $reference, $target, $type, $context );

				$data = $this->exports_generate_export_data(
					get_posts( $args ),
					$posttypes,
					$reference,
					$target,
					$type,
					$context,
					$format
				);

				break;
		}

		return $this->filters( 'export_get_data',
			$data,
			$reference,
			$target,
			$type,
			$context,
			$format
		);
	}

	protected function exports_get_post_props( $posttypes, $reference, $target, $type, $context, $format )
	{
		$list = [
			'ID',
			'post_title',
		];

		switch ( $type ) {

			case 'simple':
			case 'paired_simple':
			case 'posttype_simple':
			case 'assigned_simple':

				break;

			case 'globalsummary_simple':

				$list = array_merge( $list, [
					'post_date',
				] );

				break;

			case 'advanced':
			case 'paired_advanced':
			case 'assigned_advanced':

				$list = array_merge( $list, [
					'post_date',
					'post_content',
					'post_excerpt',
					'post_type',
				] );

				break;

			case 'posttype_advanced':

				$list = array_merge( $list, [
					'post_date',
					'post_content',
					'post_excerpt',
				] );

				break;

			case 'full':
			case 'paired_full':
			case 'posttype_full':
			case 'assigned_full':

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

		return $this->filters( 'export_post_props',
			array_fill_keys( Core\Arraay::prepString( $list ), NULL ),
			$posttypes,
			$reference,
			$target,
			$type,
			$context,
			$format
		);
	}

	protected function exports_get_post_fields( $posttypes, $reference, $target, $type, $context, $format )
	{
		$list = [];

		foreach ( $posttypes as $posttype ) {

			if ( ! WordPress\PostType::exists( $posttype ) )
				continue;

			$fields = Services\PostTypeFields::getEnabled( $posttype, 'meta' );

			if ( empty( $fields ) )
				continue;

			switch ( $type ) {

				case 'simple':
				case 'paired_simple':
				case 'posttype_simple':
				case 'assigned_simple':

					$keys = [
						'first_name',
						'last_name',
						'fullname',
						'identity_number',
					];

					$keeps = Core\Arraay::keepByKeys( $fields, $keys );
					$list  = array_merge( $list, array_fill_keys( array_keys( $keeps ), NULL ) );

					break;

				case 'advanced':
				case 'paired_advanced':
				case 'posttype_advanced':
				case 'assigned_advanced':

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
					$list  = array_merge( $list, array_fill_keys( array_keys( $keeps ), NULL ) );

					break;

				case 'full':
				case 'paired_full':
				case 'posttype_full':
				case 'assigned_full':

					$list = array_merge( $list, array_fill_keys( array_keys( $fields ), NULL ) );

					break;
			}
		}

		return $this->filters( 'export_post_fields',
			$list,
			$posttypes,
			$reference,
			$target,
			$type,
			$context,
			$format
		);
	}

	protected function exports_get_post_units( $posttypes, $reference, $target, $type, $context, $format )
	{
		$list = [];

		foreach ( $posttypes as $posttype ) {

			if ( ! WordPress\PostType::exists( $posttype ) )
				continue;

			$fields = Services\PostTypeFields::getEnabled( $posttype, 'units' );

			if ( empty( $fields ) )
				continue;

			switch ( $type ) {

				case 'simple':
				case 'paired_simple':
				case 'posttype_simple':
				case 'assigned_simple':

					// $keys  = [];
					// $keeps = Core\Arraay::keepByKeys( $fields, $keys );
					// $list  = array_merge( $list, array_fill_keys( array_keys( $keeps ), NULL ) );

					break;

				case 'advanced':
				case 'paired_advanced':
				case 'posttype_advanced':
				case 'assigned_advanced':

					// $keys  = [];
					// $keeps = Core\Arraay::keepByKeys( $fields, $keys );
					// $list  = array_merge( $list, array_fill_keys( array_keys( $keeps ), NULL ) );

					break;

				case 'full':
				case 'paired_full':
				case 'posttype_full':
				case 'assigned_full':

					$list = array_merge( $list, array_fill_keys( array_keys( $fields ), NULL ) );

					break;
			}
		}

		return $this->filters( 'export_post_units',
			$list,
			$posttypes,
			$reference,
			$target,
			$type,
			$context,
			$format
		);
	}

	protected function exports_get_post_metas( $posttypes, $reference, $target, $type, $context, $format )
	{
		$list = [];

		foreach ( $posttypes as $posttype ) {

			switch ( $type ) {

				case 'simple':
				case 'paired_simple':
				case 'posttype_simple':
				case 'assigned_simple':

					break;

				case 'advanced':
				case 'paired_advanced':
				case 'posttype_advanced':
				case 'assigned_advanced':

					$list = array_merge( $list, [] );

					break;

				case 'full':
				case 'paired_full':
				case 'posttype_full':
				case 'assigned_full':

					$list = array_merge( $list, [] );

					break;
			}
		}

		return $this->filters( 'export_post_metas',
			array_fill_keys( Core\Arraay::prepString( $list ), NULL ),
			$posttypes,
			$reference,
			$target,
			$type,
			$context,
			$format
		);
	}

	protected function exports_get_post_taxonomies( $posttypes, $reference, $target, $type, $context, $format )
	{
		$list = [];

		foreach ( $posttypes as $posttype ) {

			if ( ! $object = WordPress\PostType::object( $posttype ) )
				continue;

			$taxonomies = WordPress\Taxonomy::get( -1, [
				'show_ui'                             => TRUE,
				Services\Paired::PAIRED_POSTTYPE_PROP => FALSE,
			], $posttype );

			$all = apply_filters( $this->hook_base( 'bulk_exports', 'post_taxonomies' ),
				$taxonomies,
				$posttype,
				$reference,
				$target,
				$type,
				$context,
				$format
			);

			$primary = empty( $object->{Services\PrimaryTaxonomy::POSTTYPE_PROP} )
				? FALSE
				: $object->{Services\PrimaryTaxonomy::POSTTYPE_PROP};

			$status = empty( $object->{Services\PrimaryTaxonomy::STATUS_TAX_PROP} )
				? FALSE
				: $object->{Services\PrimaryTaxonomy::STATUS_TAX_PROP};

			switch ( $type ) {

				case 'simple':
				case 'paired_simple':
				case 'posttype_simple':
				case 'assigned_simple':

					$list = array_merge( $list, [
						$primary,
						$status,
					] );

					break;

				case 'advanced':
				case 'paired_advanced':
				case 'posttype_advanced':
				case 'assigned_advanced':

					$list = array_merge( $list, [
						$primary,
						$status,
						'post_tag',
					] );

					break;

				case 'full':
				case 'paired_full':
				case 'posttype_full':
				case 'assigned_full':

					$list = array_merge( $list, $all );

					break;
			}
		}

		return $this->filters( 'export_post_taxonomies',
			array_fill_keys( Core\Arraay::prepString( $list ), NULL ),
			$posttypes,
			$reference,
			$target,
			$type,
			$context,
			$format
		);
	}

	protected function exports_get_post_customs( $posttypes, $reference, $target, $type, $context, $format )
	{
		$list = [];

		foreach ( $posttypes as $posttype ) {

			switch ( $type ) {

				case 'simple':
				case 'paired_simple':
				case 'posttype_simple':
				case 'assigned_simple':

					break;

				case 'advanced':
				case 'paired_advanced':
				case 'posttype_advanced':
				case 'assigned_advanced':

					$list = array_merge( $list, [] );

					break;

				case 'full':
				case 'paired_full':
				case 'posttype_full':
				case 'assigned_full':

					$list = array_merge( $list, [] );

					break;
			}
		}

		return $this->filters( 'export_post_customs',
			array_fill_keys( Core\Arraay::prepString( $list ), NULL ),
			$posttypes,
			$reference,
			$target,
			$type,
			$context,
			$format
		);
	}

	protected function bulkexports__hook_supportedbox_for_term( $constant, $screen, $role_context = NULL )
	{
		if ( 'term' !== $screen->base )
			return FALSE;

		if ( ! method_exists( $this, '_hook_term_supportedbox' ) )
			return FALSE;

		if ( FALSE !== $role_context && ! $this->corecaps_taxonomy_role_can( $constant, $role_context ?? 'reports' ) )
			return FALSE;

		$this->_hook_term_supportedbox( $screen, NULL, 'side', 'high' );

		add_action( $this->hook( 'render_supportedbox_metabox' ),
			function ( $object, $box, $screen, $action_context ) use ( $constant ) {
				echo $this->wrap(
					$this->exports_get_export_buttons(
						$object->term_id,
						$action_context,
						'assigned'
					), 'field-wrap -buttons' );
			}, 20, 4 );

		return TRUE;
	}

	protected function bulkexports__hook_tabloid_term_assigned( $constant, $role_context = NULL )
	{
		if ( FALSE !== $role_context && ! $this->corecaps_taxonomy_role_can( $constant, $role_context ?? 'reports' ) )
			return FALSE;

		add_filter( $this->hook_base( 'tabloid', 'term_summaries' ),
			function ( $list, $data, $term, $context ) use ( $constant ) {

				if ( $term->taxonomy !== $this->constant( $constant ) )
					return $list;

				$default  = _x( 'Export Options', 'Internal: Bulk Export: Term Summary Title', 'geditorial-admin' );
				$template = $this->get_string( 'tabloid_export_buttons', $term->taxonomy, 'misc', $default );

				$list[] = [
					'key'     => $this->classs( 'assigned', 'exports' ),
					'class'   => '-assigned-exports',
					'title'   => $template,
					'content' => Core\HTML::wrap( $this->exports_get_export_buttons( $term->term_id, $context, 'assigned' ), 'field-wrap -buttons' ),
				];

				return $list;

			}, 20, 4 );

		return TRUE;
	}
}
