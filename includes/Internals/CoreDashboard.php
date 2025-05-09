<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait CoreDashboard
{

	protected function dashboard_glance_post( $constant, $roles = NULL )
	{
		if ( ! $this->role_can( $roles ) )
			return FALSE;

		$posttype = $this->constant( $constant );

		return MetaBox::glancePosttype(
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

		return MetaBox::glanceTaxonomy(
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

		if ( is_null( $title ) )
			$title = $this->get_string( 'widget_title',
				$this->get_setting( 'summary_scope', 'all' ),
				$context,
				_x( 'Editorial Content Summary', 'Module: Dashboard Widget Title', 'geditorial-admin' )
			);

		$screen = get_current_screen();
		$hook   = self::sanitize_hook( $name );
		$id     = $this->classs( $name );
		$title  = $this->filters( 'dashboard_widget_title', $title, $name, $context );
		$args   = array_merge( [
			'__widget_basename' => $title, // passing title without extra markup
		], $extra );

		if ( is_array( $action ) ) {

			$title.= MetaBox::getTitleAction( $action );

		} else if ( $action ) {

			switch ( $action ) {

				case 'refresh':
					$title.= MetaBox::titleActionRefresh( $hook );
					break;

				case 'info' :

					if ( method_exists( $this, 'get_widget_'.$hook.'_info' ) )
						$title.= MetaBox::titleActionInfo( call_user_func( [ $this, 'get_widget_'.$hook.'_info' ] ) );

					break;

				default:
					$title.= $action;
			}
		}

		if ( is_null( $callback ) )
			$callback = [ $this, 'render_widget_'.$hook ];

		add_meta_box( $id, $title, $callback, $screen, 'normal', 'default', $args );

		add_filter( 'postbox_classes_'.$screen->id.'_'.$id, function ( $classes ) use ( $name, $context ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-admin-postbox'.'-'.$name,
				'-'.$this->key,
				'-'.$this->key.'-'.$name,
				'-context-'.$context,
			] );
		} );

		if ( in_array( $id, get_hidden_meta_boxes( $screen ) ) )
			return FALSE; // prevent scripts

		return TRUE;
	}
}
