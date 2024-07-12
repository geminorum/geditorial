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
	// NOTE: @SEE: `paired_reports_render_overview_table()`
	protected function posttype_overview_render_table( $constant, $uri = '', $sub = NULL, $title = NULL )
	{
		if ( ! $this->role_can( 'reports' ) && ! $this->cuc( 'reports' ) )
			return FALSE;

		$module  = 'meta'; // NOTE: the fields module
		$query   = $extra = [];
		$type    = $this->constant( $constant );
		$list    = $this->list_posttypes();
		$fields  = $this->posttype_overview_get_available_fields( $type, $module );
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
				'posttype'     => $type,
				'constant'     => $constant,
				'field_module' => $module,
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
				'reports',
				'posttype'
			), 'field-wrap -buttons' );
	}

	protected function posttype_overview_get_available_fields( $posttype, $field_module = 'meta', $option_key = NULL )
	{
		return Core\Arraay::keepByKeys(
			Services\PostTypeFields::getEnabled( $posttype, $field_module ),
			$this->get_setting( $option_key ?? 'overview_fields', [] )
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
