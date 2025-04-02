<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

// TODO: Network Taxonomy Bulk Action: assign target taxonomy to selected terms (via secondary input)
// TODO: Network Taxonomy Bulk Action: empty target taxonomy to selected terms (remove all)

trait TaxonomyTaxonomy
{
	// NOTE: use on `init`
	protected function taxtax__hook_init( $subjects, $constant, $column = TRUE )
	{
		if ( ! $target = $this->constant( $constant, $constant ) )
			return FALSE;

		// NOTE: no need to check `manage` capability on `edit_term`/`create_term` hooks
		$callback = function ( $term_id, $tt_id, $taxonomy ) use ( $subjects, $target ) {

			if ( ! in_array( $taxonomy, (array) $subjects, TRUE ) )
				return;

			$name = sprintf( Services\TaxonomyTaxonomy::FIELD_NAME_TEMPLATE, $this->base, $target );

			if ( FALSE === ( $value = self::req( $name, FALSE ) ) )
				return;

			// term-ids stored as keys
			if ( is_array( $value ) )
				$value = array_keys( $value );

			wp_set_object_terms(
				$term_id,
				Core\Arraay::prepNumeral( $value ) ?: NULL,
				$target,
				FALSE
			);
		};

		add_action( 'edit_term', $callback, 10, 3 );
		add_action( 'create_term', $callback, 10, 3 );

		if ( ! $column || ! Core\WordPress::isAJAX() )
			return TRUE;

		if ( ! $taxonomy = $this->is_inline_save_taxonomy( (array) $subjects ) )
			return TRUE;

		if ( ! WordPress\Taxonomy::can( $taxonomy, 'manage_terms' ) )
			return TRUE;

		return $this->taxtax__edit_tags_screen( $taxonomy, $target );
	}

	// TODO: auto-hook this via `taxtax__hook_init()`
	// NOTE: use on `current_screen`
	protected function taxtax__hook_screen( $screen, $constant, $column = TRUE, $priority = NULL )
	{
		if ( ! $target = $this->constant( $constant, $constant ) )
			return FALSE;

		if ( 'edit-tags' === $screen->base ) {

			if ( ! WordPress\Taxonomy::can( $screen->taxonomy, 'manage_terms' ) )
				return FALSE;

			add_action( $screen->taxonomy.'_add_form_fields',
				function ( $taxonomy ) use ( $target ) {
					Settings::fieldType(
						Services\TaxonomyTaxonomy::getSettingsFieldArgs( $target, 'addnew' ),
						$this->scripts
					);
				}, $priority ?? 8, 1 );

			if ( $column ) {

				// NOTE: cannot use as `sortable`: currently no `tax_query` for `TermQuery`
				$this->taxtax__edit_tags_screen( $screen->taxonomy, $target );

				add_action( 'quick_edit_custom_box',
					function ( $column, $screen, $taxonomy ) use ( $target ) {
						if ( $this->classs( $target ) !== $column )
							return;

						Settings::fieldType(
							Services\TaxonomyTaxonomy::getSettingsFieldArgs( $target, 'quickedit' ),
							$this->scripts
						);
					}, 10, 3 );

				$this->enqueue_asset_js( [], $screen, [ 'jquery' ], 'taxtax' );
			}

		} else if ( 'term' === $screen->base ) {

			if ( ! WordPress\Taxonomy::can( $screen->taxonomy, 'manage_terms' ) )
				return FALSE;

			add_action( $screen->taxonomy.'_edit_form_fields',
				function ( $term, $taxonomy ) use ( $target ) {
					Settings::fieldType(
						Services\TaxonomyTaxonomy::getSettingsFieldArgs( $target, 'editterm', $term ),
						$this->scripts
					);
				}, $priority ?? 8, 2 );

		} else {

			return FALSE;
		}

		return TRUE;
	}

	protected function taxtax__edit_tags_screen( $taxonomy, $target )
	{
		$singleselect = Services\TermHierarchy::isSingleTerm( $target );

		add_filter( sprintf( 'manage_edit-%s_columns', $taxonomy ),
			function ( $columns ) use ( $target ) {
				return Core\Arraay::insert( $columns, [
					$this->classs( $target ) => Services\CustomTaxonomy::getLabel( $target, 'column_title' ),
				], 'name', 'after' );
			} );

		add_filter( sprintf( 'manage_%s_custom_column', $taxonomy ),
			function ( $string, $column_name, $term_id ) use ( $target, $singleselect ) {

				if ( $this->classs( $target ) !== $column_name )
					return $string;

				if ( ! $term = WordPress\Term::get( (int) $term_id ) )
					return;

				$terms = wp_get_object_terms( $term->term_id, $target );
				$name  = sprintf( Services\TaxonomyTaxonomy::FIELD_NAME_TEMPLATE, $this->base, $target );

				if ( empty( $terms ) || self::isError( $terms ) ) {

					echo Helper::htmlEmpty();
					$this->hidden( '0', [
						'name'         => $name,
						'target'       => 'quickedit',
						'taxtax'       => $target,
						'singleselect' => $singleselect ? 'true' : 'false',
					] );

				} else {

					$rows = array_map( function ( $term ) {
						return Core\HTML::row( WordPress\Term::htmlLink( $term ) );
					}, $terms );

					echo Core\HTML::tag( 'ul', [
						'class' => '-rows',
					], implode( "\n", $rows ) );

					$this->hidden( implode( ',', Core\Arraay::pluck( $terms, 'term_id' ) ), [
						'name'         => $name,
						'target'       => 'quickedit',
						'taxtax'       => $target,
						'singleselect' => $singleselect ? 'true' : 'false',
					] );
				}

			}, 10, 3 );

		return TRUE;
	}
}
