<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

trait TaxonomyOverview
{
	// DEPRECATED: use `modulelinks__register_headerbuttons()`
	protected function taxonomy_overview_register_headerbutton( $context, $link = NULL )
	{
		if ( ! $this->role_can( $context ) && ! $this->cuc( $context ) )
			return FALSE;

		return Services\HeaderButtons::register( 'taxonomy_overview', [
			'text'     => _x( 'Overview', 'Internal: TaxonomyOverview: Header Button', 'geditorial-admin' ),
			'link'     => $link ?? $this->get_module_url( $context ),
			'priority' => 8,
		] );
	}

	// TODO: display term count
	// TODO: display term meta-data
	// TODO: link to support post-type: `coreadmin__hook_taxonomy_multiple_supported_column`
	protected function taxonomy_overview_render_table( $constant, $uri = '', $sub = NULL, $context = 'reports', $title = NULL )
	{
		if ( ! $this->cuc( $context ) )
			return FALSE;

		$query       = $extra = $exports = [];
		$taxonomy    = $this->constant( $constant );
		$list        = $this->list_posttypes();
		$description = TRUE;
		$exports     = method_exists( $this, 'exports_get_export_links' );

		list( $terms, $pagination ) = Tablelist::getTerms( $query, $extra, $taxonomy, $this->get_sub_limit_option( $sub ) );

		$pagination['before'][] = Tablelist::filterSearch( $list );

		$columns = [
			'_cb'  => 'term_id',
			'name' => [
				'title' => _x( 'Name', 'Internal: TaxonomyOverview: Column', 'geditorial-admin' ),
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) use ( $description ) {

					if ( ! $term = WordPress\Term::get( $row ) )
						return Plugin::na( FALSE );

					$html = WordPress\Term::title( $term );

					if ( $description && $term->description )
						$html.= wpautop( WordPress\Strings::prepDescription( $term->description, FALSE, FALSE ), FALSE );

					return $html;
				},
				'actions' => function ( $value, $row, $column, $index, $key, $args ) use ( $exports, $context ) {

					if ( ! $term = WordPress\Term::get( $row ) )
						return [];

					if ( $exports )
						return array_merge(
							Tablelist::getTermRowActions( $term ),
							$this->exports_get_export_links( $term->term_id, $context, 'assigned' )
						);

					return Tablelist::getTermRowActions( $term );
				},
			],
			'desc' => Tablelist::columnTermDesc(),
		];

		if ( is_null( $title ) )
			$title = sprintf(
				/* translators: `%s`: taxonomy label */
				_x( 'Overview of %s', 'Internal: TaxonomyOverview: Header', 'geditorial-admin' ),
				$this->get_taxonomy_label( $constant, 'extended_label', 'name' )
			);

		return Core\HTML::tableList( $columns, $terms, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', $title ),
			'empty'      => $this->get_taxonomy_label( $constant, 'not_found' ),
			'pagination' => $pagination,
			'after'      => [ $this, 'taxonomy_overview_after_table' ],
			'extra'      => [
				'taxonomy' => $taxonomy,
				'constant' => $constant,
				'context'  => $context,
			],
		] );
	}

	public function taxonomy_overview_after_table( $columns, $data, $args )
	{
		if ( ! method_exists( $this, 'exports_get_export_buttons' ) )
			return;

		// already checked
		// if ( ! $this->role_can( $args['extra']['context'], NULL, TRUE ) )
		// 	return;

		echo Core\HTML::wrap(
			$this->exports_get_export_buttons(
				$args['extra']['taxonomy'],
				$args['extra']['context'],
				'taxonomy'
			), 'field-wrap -buttons' );
	}
}
