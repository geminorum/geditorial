<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

trait SettingsHelp
{
	protected function settings_help_tabs( $context = 'settings' )
	{
		return Settings::helpContent( $this->module );
	}

	protected function settings_help_sidebar( $context = 'settings' )
	{
		return Settings::helpSidebar( $this->get_module_links() );
	}

	protected function register_help_tabs( $screen = NULL, $context = 'settings' )
	{
		if ( ! Core\WordPress::mustRegisterUI( FALSE ) || self::req( 'noheader' ) )
			return;

		if ( is_null( $screen ) )
			$screen = get_current_screen();

		foreach ( $this->settings_help_tabs( $context ) as $tab )
			$screen->add_help_tab( $tab );

		if ( $sidebar = $this->settings_help_sidebar( $context ) )
			$screen->set_help_sidebar( $sidebar );

		if ( ! in_array( $context, [ 'settings' ], TRUE ) )
			return;

		if ( ! empty( $this->strings['default_terms'] ) )
			foreach ( array_keys( $this->strings['default_terms'] ) as $taxonomy_constant )
				$this->help_tab_default_terms( $taxonomy_constant );
	}

	protected function help_tab_default_terms( $constant )
	{
		if ( ! $taxonomy = WordPress\Taxonomy::object( $this->constant( $constant ) ) )
			return;

		/* translators: %s: taxonomy object label */
		$title  = sprintf( _x( 'Default Terms for %s', 'Module', 'geditorial-admin' ), $taxonomy->label );
		/* translators: %s: taxonomy object label */
		$edit   = sprintf( _x( 'Edit Terms for %s', 'Module', 'geditorial-admin' ), $taxonomy->label );
		$terms  = $this->get_default_terms( $constant );
		$link   = Core\WordPress::getEditTaxLink( $taxonomy->name );
		$before = Core\HTML::tag( 'p', $title );
		$after  = Core\HTML::tag( 'p', Core\HTML::link( $edit, $link, TRUE ) );
		$args   = [ 'title' => $taxonomy->label, 'id' => $this->classs( 'help-default-terms', '-'.$taxonomy->name ) ];

		if ( empty( $terms ) )
			$args['content'] = $before.Core\HTML::wrap( _x( 'No Default Terms', 'Module', 'geditorial-admin' ), '-info' ).$after;

		else if ( Core\Arraay::allStringValues( $terms ) )
			$args['content'] = $before.Core\HTML::wrap( Core\HTML::tableCode( $terms, TRUE ), '-info' ).$after;

		else
			$args['content'] = $before.Core\HTML::wrap( Core\HTML::tableCode( Core\Arraay::pluck( $terms, 'name', 'slug' ), TRUE ), '-info' ).$after;

		get_current_screen()->add_help_tab( $args );
	}
}
