<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\WordPress;

trait PairedReports
{
	// NOTE: @SEE: `posttype_overview_render_table()`
	protected function paired_reports_render_overview_table( $uri = '', $sub = NULL, $title = NULL )
	{
		if ( ! $this->role_can( 'reports' ) )
			return FALSE;

		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		$module  = 'meta'; // NOTE: the fields module
		$query   = $extra = [];
		$type    = $this->constant( $constants[0] );
		$list    = $this->list_posttypes();
		$fields  = $this->paired_reports_get_overview_fields( $type, $module );
		$taxes   = $this->posttype_overview_get_available_taxonomies( $type );
		$columns = [ '_cb' => 'ID' ];

		list( $posts, $pagination ) = Tablelist::getPosts( $query, $extra, $type, $this->get_sub_limit_option( $sub ) );

		$pagination['before'][] = Tablelist::filterAuthors( $list );
		$pagination['before'][] = Tablelist::filterSearch( $list );

		if ( ! $this->get_setting( 'override_dates', TRUE ) )
			$columns['date'] = Tablelist::columnPostDate();

		$columns['title'] = Tablelist::columnPostTitle();

		foreach ( $taxes as $taxonomy => $object )
			$columns['tax__'.$taxonomy] = [
				'title'    => $object->label,
				'class'    => sprintf( '-field-%s-%s', $module, $taxonomy ),
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) use ( $taxonomy, $object, $module ) {
					Helper::renderPostTermsEditRow( $row, $object );
					return '';
				},
			];

		foreach ( $fields as $field_key => $field )
			$columns['meta__'.$field_key] = [
				'title'    => $field['title'],
				'class'    => sprintf( '-field-%s-%s', $module, $field_key ),
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) use ( $field_key, $field, $module ) {
					return Template::getMetaField( $field_key, [
						'id'      => $row->ID,
						'default' => $field['default'],
						'context' => 'reports',
					], FALSE, $module ) ?: Helper::htmlEmpty();
				},
			];

		$columns['paired_connected'] = [
			'title'    => _x( 'Connected', 'Internal: PairedReports: Column Header', 'geditorial-admin' ),
			'class'    => '-paired-connected-to',
			'callback' => function ( $value, $row, $column, $index, $key, $args ) {

				if ( FALSE === ( $connected = $this->paired_all_connected_to( $row, 'reports' ) ) )
					return Helper::htmlEmpty();

				return $this->nooped_count( 'paired_item', count( $connected ) );
			},
		];

		if ( is_null( $title ) )
			$title = sprintf(
				/* translators: %s: posttype label */
				_x( 'Overview of %s', 'Header', 'geditorial-admin' ),
				$this->get_posttype_label( $constants[0], 'extended_label', 'name' )
			);

		return Core\HTML::tableList( $columns, $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', $title ),
			'empty'      => $this->get_posttype_label( $constants[0], 'not_found' ),
			'pagination' => $pagination,
			'after'      => [ $this, 'paired_reports_after_overview_table' ],
			'extra'      => [
				'posttype'     => $type,
				'constants'    => $constants,
				'field_module' => $module,
			],
		] );
	}

	public function paired_reports_after_overview_table( $columns, $data, $args )
	{
		if ( ! $this->role_can( 'exports' ) )
			return;

		if ( ! method_exists( $this, 'exports_get_export_buttons' ) )
			return;

		echo Core\HTML::wrap(
			$this->exports_get_export_buttons(
				$args['extra']['posttype'],
				'reports',
				'posttype'
			), 'field-wrap -buttons' );
	}

	protected function paired_reports_get_overview_fields( $posttype, $field_module = 'meta', $option_key = NULL )
	{
		return Core\Arraay::keepByKeys(
			Services\PostTypeFields::getEnabled( $posttype, $field_module ),
			$this->get_setting( $option_key ?? 'overview_fields', [] )
		);
	}

	protected function paired_reports_get_overview_taxonomies( $posttype, $option_key = NULL )
	{
		return Core\Arraay::keepByKeys(
			WordPress\Taxonomy::get( 4, [ 'show_ui' => TRUE ], $posttype ),
			$this->get_setting( $option_key ?? 'overview_taxonomies', [] )
		);
	}
}
