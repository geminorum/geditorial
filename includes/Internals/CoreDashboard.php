<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait CoreDashboard
{
	protected function dashboard_glance_post( $constant, $roles = NULL )
	{
		if ( ! $this->role_can( $roles ) )
			return FALSE;

		$posttype = $this->constant( $constant );

		return gEditorial\MetaBox::glancePosttype(
			$posttype,
			Services\CustomPostType::getLabel( $posttype, 'noop' ),
			'-'.$this->slug()
		);
	}

	protected function dashboard_glance_taxonomy( $constant, $roles = NULL )
	{
		if ( ! $this->role_can( $roles ) )
			return FALSE;

		$taxonomy = $this->constant( $constant );

		return gEditorial\MetaBox::glanceTaxonomy(
			$taxonomy,
			Services\CustomTaxonomy::getLabel( $taxonomy, 'noop' ),
			'-'.$this->slug()
		);
	}

	// @REF: `wp_add_dashboard_widget()`
	protected function add_dashboard_widget( $name, $title = NULL, $action = FALSE, $extra = [], $callback = NULL, $context = 'dashboard' )
	{
		// FIXME: test this
		// if ( ! $this->cuc( $context ) )
		// 	return FALSE;

		$title = $title ?? $this->get_string(
			'widget_title',
			$this->get_setting( 'summary_scope', 'all' ),
			$context,
			_x( 'Editorial Content Summary', 'Module: Dashboard Widget Title', 'geditorial-admin' )
		);

		$screen  = get_current_screen();
		$hook    = Core\Text::sanitizeHook( $name );
		$metabox = $this->classs( $name );
		$title   = $this->filters( 'dashboard_widget_title', $title, $name, $context );
		$args    = array_merge( [
			'__widget_basename' => $title, // passing title without extra markup
		], $extra );

		if ( is_array( $action ) ) {

			$title.= WordPress\MetaBox::markupTitleAction( $action );

		} else if ( $action ) {

			switch ( $action ) {

				case 'refresh':

					$title.= gEditorial\MetaBox::titleActionRefresh( $hook );

					break;

				case 'info' :

					$info_callback = sprintf( 'get_widget_%s_info', $hook );

					if ( method_exists( $this, $info_callback ) )
						$title.= WordPress\MetaBox::markupTitleInfo(
							call_user_func( [ $this, $info_callback ] )
						);

					break;

				default:

					$title.= $action;
			}
		}

		add_meta_box(
			$metabox,
			$title,
			$callback ?? [ $this, sprintf( 'render_widget_%s', $hook ) ],
			$screen,
			'normal',
			'default',
			$args
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ),
			function ( $classes ) use ( $name, $context ) {
				return Core\Arraay::prepString( $classes, [
					$this->base.'-wrap',
					'-admin-postbox',
					'-admin-postbox'.'-'.$name,
					'-'.$this->key,
					'-'.$this->key.'-'.$name,
					'-context-'.$context,
				] );
			} );

		if ( in_array( $metabox, get_hidden_meta_boxes( $screen ) ) )
			return FALSE; // prevent scripts

		return TRUE;
	}
}
