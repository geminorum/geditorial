<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\WordPress;

trait CoreAdmin
{

	/**
	 * Hooks action for custom default admin ordering.
	 * NOTE: default settings here is `TRUE`
	 * @old: `_hook_admin_ordering()`
	 *
	 * @param  string $posttype
	 * @param  string $orderby
	 * @param  string $order
	 * @return bool   $hooked
	 */
	protected function coreadmin__hook_admin_ordering( $posttype, $orderby = 'menu_order', $order = 'DESC' )
	{
		if ( ! $this->get_setting( 'admin_ordering', TRUE ) )
			return FALSE;

		add_action( 'pre_get_posts',
			function ( &$wp_query ) use ( $posttype, $orderby, $order ) {

				if ( ! $wp_query->is_admin )
					return;

				if ( $posttype !== $wp_query->get( 'post_type' ) )
					return;

				if ( $orderby && ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', $orderby );

				if ( $order && ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', $order );
			} );

		return TRUE;
	}

	/**
	 * Hooks filter to unset given columns.
	 *
	 * @param  string     $posttype
	 * @param  null|array $list
	 * @return bool       $hooked
	 */
	protected function coreadmin__unset_columns( $posttype, $list = NULL )
	{
		if ( is_null( $list ) )
			$list = [
				'author',
			];

		add_filter( sprintf( 'manage_%s_posts_columns', $posttype ),
			static function ( $columns ) use ( $list ) {
				return Core\Arraay::stripByKeys( $columns, (array) $list );
			} );

		return TRUE;
	}

	/**
	 * Hooks `views_{$this->screen->id}` filter to unset given views.
	 *
	 * @param  string     $posttype
	 * @param  null|array $list
	 * @return bool       $hooked
	 */
	protected function coreadmin__unset_views( $posttype, $list = NULL )
	{
		if ( is_null( $list ) )
			$list = [
				'mine',
			];

		add_filter( sprintf( 'views_edit-%s', $posttype ),
			static function ( $views ) use ( $list ) {
				return Core\Arraay::stripByKeys( $views, (array) $list );
			} );

		return TRUE;
	}

	protected function coreadmin__hook_tweaks_column_row( $posttype, $priority = 20, $callback_suffix = FALSE )
	{
		$method = $callback_suffix ? sprintf( 'tweaks_column_row_%s', $callback_suffix ) : 'tweaks_column_row';

		if ( ! method_exists( $this, $method ) )
			return FALSE;

		add_action( $this->hook_base( 'tweaks', 'column_row', $posttype ),
			function ( $post, $before, $after, $module ) use ( $method ) {
				call_user_func_array( [ $this, $method ], [ $post, $before, $after, $module ] );
			}, $priority, 4 );
	}

	protected function coreadmin__hook_tweaks_column_attr( $posttype, $priority = 20, $callback_suffix = FALSE )
	{
		$method = $callback_suffix ? sprintf( 'tweaks_column_attr_%s', $callback_suffix ) : 'tweaks_column_attr';

		if ( ! method_exists( $this, $method ) )
			return FALSE;

		add_action( $this->hook_base( 'tweaks', 'column_attr', $posttype ),
			function ( $post, $before, $after ) use ( $method ) {
				call_user_func_array( [ $this, $method ], [ $post, $before, $after ] );
			}, $priority, 3 );
	}

	// NOTE: on target posttype screen only
	protected function coreadmin__hook_taxonomy_display_states( $constants, $setting = 'admin_displaystates', $default_setting = FALSE, $priority = 20 )
	{
		if ( TRUE !== $setting && ! $this->get_setting( $setting, $default_setting ) )
			return FALSE;

		add_filter( 'display_post_states',
			function ( $states, $post ) use ( $constants ) {

				foreach ( $this->constants( $constants ) as $taxonomy ) {

					if ( ! $terms = WordPress\Taxonomy::getPostTerms( $taxonomy, $post ) )
						continue;

					$label   = Helper::getTaxonomyLabel( $taxonomy, 'extended_label', 'name' );
					$default = WordPress\Taxonomy::getDefaultTermID( $taxonomy );
					$metakey = 'color';

					foreach ( $terms as $term ) {

						// TODO: custom list of excludes or default
						if ( $term->term_id == $default )
							continue;

						$color = get_term_meta( $term->term_id, $metakey, TRUE );

						// TODO: cache each state by tax/id
						$states[$this->classs( $term->slug )] = sprintf(
							'<span class="%s" title="%s" style="color:%s;background-color:%s">%s</span>',
							'-custom-post-state',
							Core\HTML::escapeAttr( $label ),
							Core\HTML::escapeAttr( $color ?: 'inherit' ),
							Core\HTML::escapeAttr( $color ? Core\Color::lightOrDark( $color ) : 'none' ),
							Core\HTML::escapeAttr( WordPress\Term::title( $term ) )
						);
					}
				}

				return $states;

			}, $priority, 2 );

		return TRUE;
	}

	protected function coreadmin__hook_taxonomy_multiple_supported_column( $screen, $supported = NULL )
	{
		if ( 'edit-tags' !== $screen->base )
			return FALSE;

		if ( ! $object = WordPress\Taxonomy::object( $screen->taxonomy ) )
			return FALSE;

		if ( ! $object->query_var )
			return FALSE; // If false, a taxonomy cannot be loaded at `?{query_var}={term_slug}`

		if ( is_null( $supported ) )
			$supported = $this->posttypes();

		$posttypes = WordPress\PostType::get( 4, [], 'read' );

		add_filter( sprintf( 'manage_edit-%s_columns', $object->name ),
			function ( $columns ) {
				return Core\Arraay::insert( $columns, [
					// NOTE: globalized: bc no-way to pass class for the column header!
					$this->hook_base( 'multiplesupported' )
						=> _x( 'Connected', 'Internal: CoreAdmin: Column', 'geditorial-admin' ),
				], 'posts', 'before' );
			} );

		add_filter( sprintf( 'manage_%s_custom_column', $object->name ),
			function ( $display, $column, $term_id ) use ( $object, $supported, $posttypes ) {

				if ( $this->hook_base( 'multiplesupported' ) !== $column )
					return;

				if ( ! $term = WordPress\Term::get( $term_id, $object->name ) )
					return;

				foreach ( $supported as $posttype ) {

					if ( ! array_key_exists( $posttype, $posttypes ) )
						continue; // no cap

					$html = Helper::getPostTypeLabel( $posttypes[$posttype], 'extended_label' );

					if ( $link = WordPress\PostType::edit( $posttype, [ $object->query_var => $term->slug ] ) )
						$html = Core\HTML::link( $html, $link, TRUE );

					echo Core\HTML::wrap( $html, '-supported-posttype' );
				}

			}, 10, 3 );
	}
}
