<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\WordPress;

trait PostTypeOverview
{
	// DEPRECATED: use `modulelinks__register_headerbuttons()`
	protected function posttype_overview_register_headerbutton( $context, $link = NULL )
	{
		if ( ! $this->role_can( $context ) && ! $this->cuc( $context ) )
			return FALSE;

		return Services\HeaderButtons::register( 'posttype_overview', [
			'text'     => _x( 'Overview', 'Internal: PostTypeOverview: Header Button', 'geditorial-admin' ),
			'link'     => $link ?? $this->get_module_url( $context ),
			'priority' => 8,
		] );
	}

	protected function posttype_overview_render_table( $constant, $uri = '', $sub = NULL, $context = 'reports', $title = NULL )
	{
		if ( ! $this->role_can( $context ) && ! $this->cuc( $context ) )
			return FALSE;

		$query   = $extra = [];
		$type    = $this->constant( $constant );
		$list    = $this->list_posttypes();
		$fields  = $this->posttype_overview_get_available_fields( $type, 'meta' );
		$units   = $this->posttype_overview_get_available_fields( $type, 'units' );
		$taxes   = $this->posttype_overview_get_available_taxonomies( $type );
		$columns = [ '_cb' => 'ID' ];

		list( $posts, $pagination ) = Tablelist::getPosts( $query, $extra, $type, $this->get_sub_limit_option( $sub ) );

		// TODO: filter by fields
		$pagination['before'][] = Tablelist::filterAuthors( $list );
		$pagination['before'][] = Tablelist::filterSearch( $list );

		if ( ! $this->get_setting( 'override_dates', TRUE ) )
			$columns['date'] = Tablelist::columnPostDate();

		$columns['title'] = Tablelist::columnPostTitle();

		foreach ( $taxes as $taxonomy => $object )
			$columns['tax__'.$taxonomy] = [
				'title'    => $object->label,
				'class'    => sprintf( '-field-%s-%s', 'tax', $taxonomy ),
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) use ( $taxonomy, $object ) {
					Helper::renderPostTermsEditRow( $row, $object );
					return '';
				},
			];

		foreach ( $fields as $field_key => $field )
			$columns['meta__'.$field_key] = [
				'title'    => $field['title'],
				'class'    => sprintf( '-field-%s-%s', 'meta', $field_key ),
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) use ( $field_key, $field, $context ) {
					return Template::getMetaField( $field_key, [
						'id'      => $row->ID,
						'default' => $field['default'],
						'context' => $context,
					], FALSE, 'meta' ) ?: Helper::htmlEmpty();
				},
			];

		foreach ( $units as $unit_key => $unit )
			$columns['unit__'.$unit_key] = [
				'title'    => $unit['title'],
				'class'    => sprintf( '-field-%s-%s', 'unit', $unit_key ),
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) use ( $unit_key, $unit, $context ) {
					return Template::getMetaField( $unit_key, [
						'id'      => $row->ID,
						'default' => $unit['default'],
						'context' => $context,
					], FALSE, 'units' ) ?: Helper::htmlEmpty();
				},
			];

		if ( $this->_paired && ( $constants = $this->paired_get_constants() ) ) {

			$columns['paired_connected'] = [
				'title'    => _x( 'Connected', 'Internal: PostTypeOverview: Column Header', 'geditorial-admin' ),
				'class'    => '-paired-connected-to',
				'callback' => function ( $value, $row, $column, $index, $key, $args ) use ( $context ) {

					if ( FALSE === ( $connected = $this->paired_all_connected_to( $row, $context ) ) )
						return Helper::htmlEmpty();

					return $this->nooped_count( 'paired_item', count( $connected ) );
				},
			];
		}

		if ( is_null( $title ) )
			$title = sprintf(
				/* translators: %s: posttype label */
				_x( 'Overview of %s', 'Header', 'geditorial-admin' ),
				$this->get_posttype_label( $constant, 'extended_label', 'name' )
			);

		return Core\HTML::tableList( $columns, $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', $title ),
			'empty'      => $this->get_posttype_label( $constant, 'not_found' ),
			'pagination' => $pagination,
			'after'      => [ $this, 'posttype_overview_after_table' ],
			'extra'      => [
				'posttype' => $type,
				'constant' => $constant,
				'context'  => $context,
			],
		] );
	}

	public function posttype_overview_after_table( $columns, $data, $args )
	{
		if ( ! method_exists( $this, 'exports_get_export_buttons' ) )
			return;

		if ( ! $this->role_can( 'exports', NULL, TRUE ) )
			return;

		echo Core\HTML::wrap(
			$this->exports_get_export_buttons(
				$args['extra']['posttype'],
				$args['extra']['context'],
				'posttype'
			), 'field-wrap -buttons' );
	}

	protected function posttype_overview_get_available_fields( $posttype, $field_module = 'meta', $option_key = NULL )
	{
		return Core\Arraay::keepByKeys(
			Services\PostTypeFields::getEnabled( $posttype, $field_module ),
			$this->get_setting( $option_key ?? ( 'meta' === $field_module ? 'overview_fields' : 'overview_'.$field_module ), [] )
		);
	}

	protected function posttype_overview_get_available_taxonomies( $posttype, $option_key = NULL )
	{
		return Core\Arraay::keepByKeys(
			WordPress\Taxonomy::get( 4, [ 'show_ui' => TRUE ], $posttype ),
			$this->get_setting( $option_key ?? 'overview_taxonomies', [] )
		);
	}
}
