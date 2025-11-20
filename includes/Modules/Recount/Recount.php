<?php namespace geminorum\gEditorial\Modules\Recount;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Recount extends gEditorial\Module
{

	protected $disable_no_taxonomies = TRUE;

	public static function module()
	{
		return [
			'name'   => 'recount',
			'title'  => _x( 'Recount', 'Modules: Recount', 'geditorial-admin' ),
			'desc'   => _x( 'Custom Counts Interface', 'Modules: Recount', 'geditorial-admin' ),
			'icon'   => 'database',
			'i18n'   => 'adminonly',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		return [
			'taxonomies_option' => 'taxonomies_option',
			'_general' => [
				'thrift_mode',
				[
					'field'       => 'count_on_display',
					'title'       => _x( 'Count on Display', 'Setting Title', 'geditorial-recount' ),
					'description' => _x( 'Tries to re-count posts on empty data display.', 'Setting Description', 'geditorial-recount' ),
				],
			],
		];
	}

	public function init()
	{
		parent::init();

		if ( ! $this->is_thrift_mode() )
			$this->action( 'edit_term_taxonomy', 10, 2 );

		$this->filter( 'taxonomy_term_count', 3, 10, FALSE, 'gnetwork' );
	}

	public function setup_ajax()
	{
		if ( $this->taxonomy_supported( $taxonomy = $this->is_inline_save_taxonomy() ) )
			$this->_edit_tags_screen( $taxonomy );
	}

	public function current_screen( $screen )
	{
		if ( 'edit-tags' == $screen->base && $this->taxonomy_supported( $screen->taxonomy ) ) {

			$this->_edit_tags_screen( $screen->taxonomy );
			add_filter( 'manage_edit-'.$screen->taxonomy.'_sortable_columns', [ $this, 'sortable_taxonomy_columns' ] );
			$this->filter( 'request' );

			$this->filter( 'taxonomy_bulk_actions', 2, 14, FALSE, 'gnetwork' );
			$this->filter( 'taxonomy_bulk_callback', 3, 14, FALSE, 'gnetwork' );
			$this->action( 'taxonomy_tab_maintenance_content', 2, 9, FALSE, 'gnetwork' );
			$this->action( 'taxonomy_handle_tab_content_actions', 1, 8, FALSE, 'gnetwork' );
		}
	}

	private function _edit_tags_screen( $taxonomy )
	{
		add_filter( 'manage_edit-'.$taxonomy.'_columns', [ $this, 'manage_taxonomy_columns' ], 12 );
		add_filter( 'manage_'.$taxonomy.'_custom_column', [ $this, 'custom_taxonomy_column' ], 20, 3 );
	}

	// TODO: maybe it's better to override the taxonomy callback for count
	// `clean_term_cache( $term_id, $taxonomy, FALSE );`
	public function edit_term_taxonomy( $term_id, $taxonomy )
	{
		if ( $this->taxonomy_supported( $taxonomy ) )
			$this->_do_recount_term( $term_id );
	}

	// FIXME: use `countTermObjects( $term, $taxonomy )`
	public function _do_recount_term( $term_id )
	{
		global $wpdb;

		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d", $term_id ) );

		update_term_meta( $term_id, 'count', $count );

		return $count;
	}

	public function taxonomy_term_count( $count, $term, $taxonomy )
	{
		if ( FALSE !== ( $meta = get_term_meta( $term->term_id, 'count', TRUE ) ) )
			return $meta;

		return $count;
	}

	public function manage_taxonomy_columns( $columns )
	{
		if ( ! $taxonomy = self::req( 'taxonomy' ) )
			return $columns;

		$columns = Core\Arraay::insert( $columns, [
			$this->classs() => $this->get_column_title_icon( 'posts', $taxonomy, ( empty( $columns['posts'] ) ? _x( 'Count', 'Number/count of items' ) : $columns['posts'] ) ),
		], 'posts', 'after' );

		unset( $columns['posts'] );

		return $columns;
	}

	public function sortable_taxonomy_columns( $columns )
	{
		return array_merge( $columns, [
			$this->classs() => $this->key,
		] );
	}

	// @REF: https://gist.github.com/scribu/906872
	public function request( $query_vars )
	{
		if ( isset( $query_vars['orderby'] ) && $this->key === $query_vars['orderby'] ) {
			$query_vars = array_merge( $query_vars, [
				'meta_key' => 'count',
				'orderby'  => 'meta_value_num'
			] );
		}

		return $query_vars;
	}

	public function custom_taxonomy_column( $string, $column, $term_id )
	{
		if ( $column != $this->classs() )
			return $string;

		if ( $this->check_hidden_column( $column ) )
			return $string;

		$count = get_term_meta( (int) $term_id, 'count', TRUE );

		if ( FALSE === $count && $this->get_setting( 'count_on_display' ) )
			$count = $this->_do_recount_term( (int) $term_id );

		if ( FALSE === $count )
			return gEditorial\Helper::htmlEmpty();

		$html = gEditorial\Helper::htmlCount( (int) $count );
		$edit = WordPress\PostType::getEditLinkByTerm(
			(int) $term_id,
			self::req( 'post_type', 'post' ),
			self::req( 'taxonomy', '' )
		);

		// WTF: must print here, wierd bug on category tax!
		echo ( $edit ? Core\HTML::link( $html, $edit, TRUE ) : $html );
	}

	public function taxonomy_bulk_actions( $actions, $taxonomy )
	{
		return array_merge( $actions, [
			'recount_recount_items' => _x( 'Re-count Items', 'Bulk Actions', 'geditorial-recount' ),
		] );
	}

	public function taxonomy_bulk_callback( $callback, $action, $taxonomy )
	{
		$actions = [
			'recount_recount_items',
		];

		return in_array( $action, $actions )
			? [ $this, 'bulk_action_'.$action ]
			: $callback;
	}

	public function bulk_action_recount_recount_items( $term_ids, $taxonomy, $action )
	{
		if ( ! $this->taxonomy_supported( $taxonomy ) )
			return FALSE;

		foreach ( $term_ids as $term_id ) {

			$term = get_term( (int) $term_id, $taxonomy );

			if ( self::isError( $term ) )
				continue;

			$this->_do_recount_term( (int) $term_id );
		}

		return TRUE;
	}

	public function taxonomy_tab_maintenance_content( $taxonomy, $object )
	{
		echo gEditorial\Settings::toolboxCardOpen( _x( 'Re-count Items', 'Tab Tools', 'geditorial-recount' ), FALSE );

			$this->render_form_start( NULL, 'recount-items', 'maintenance', 'tabs' );
				$this->nonce_field( 'do-recount-items' );

				echo $this->wrap_open_buttons( '-toolbox-buttons' );
					gEditorial\Settings::submitButton( $this->classs( 'do-recount-items' ), _x( 'Re-count Items for all Terms', 'Tab Tools', 'geditorial-recount' ), 'small button-primary' );
				echo '</p>';

			$this->render_form_end( NULL, 'recount-items', 'maintenance', 'tabs' );

		echo '</div>';
	}

	public function taxonomy_handle_tab_content_actions( $taxonomy )
	{
		if ( self::req( $this->classs( 'do-recount-items' ) ) ) {

			$this->nonce_check( 'do-recount-items' );

			$count = 0;
			$terms = get_terms( [
				'taxonomy'               => $taxonomy,
				'fields'                 => 'ids',
				'hide_empty'             => FALSE,
				'update_term_meta_cache' => FALSE,
			] );

			foreach ( $terms as $term_id ) {
				$this->_do_recount_term( (int) $term_id );
				++$count;
			}

			WordPress\Redirect::doReferer( [
				'message' => 'synced',
				'count'   => $count,
			] );
		}
	}
}
