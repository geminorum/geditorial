<?php namespace geminorum\gEditorial\Modules\Widgets\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ProfileSummary extends gEditorial\Widget
{

	const MODULE = 'widgets';
	const WIDGET = 'profile_summary';

	public static function setup()
	{
		return [
			'title' => _x( 'Editorial: Profile Summary', 'Widget Title', 'geditorial-widgets' ),
			'desc'  => _x( 'Displays arbitrary profile info for current logged-in user.', 'Widget Description', 'geditorial-widgets' ),
		];
	}

	public function widget( $args, $instance )
	{
		if ( ! $user = wp_get_current_user() )
			return;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );

			// TODO: add option for display/size
			echo Core\HTML::wrap( get_avatar( $user->user_email, 125 ), '-avatar float-start me-3 mt-2' );

			echo '<ul class="-rows w-auto list-group list-group-flush">';

			if ( $user->first_name || $user->last_name ) {
				echo '<li class="-row -name list-group-item">';
					Info::renderIcon( 'fullname' );
					echo ' ';
					echo "$user->first_name $user->last_name";
				echo '</li>';
			}

			if ( $user->user_email ) {
				echo '<li class="-row -email list-group-item">';
					Info::renderIcon( 'email' );
					echo ' ';
					echo Core\HTML::mailto( $user->user_email );
				echo '</li>';
			}

			if ( $user->user_url ) {
				echo '<li class="-row -url list-group-item">';
					Info::renderIcon( 'url' );
					echo ' ';
					echo Core\HTML::link( Core\URL::prepTitle( $user->user_url ), $user->user_url );
				echo '</li>';
			}

			// TODO: add option for display/list
			foreach ( wp_get_user_contact_methods( $user ) as $method => $title ) {

				if ( ! $value = get_user_meta( $user->ID, $method, TRUE ) )
					continue;

				echo '<li class="-row -contact -contact-'.$method.' list-group-item">';
					Info::renderIcon( $method, $title );
					echo ' ';
					echo Services\PostTypeFields::prepFieldRow( $value, $method, [ 'type' => 'contact_method', 'title' => $title ], $value );
				echo '</li>';
			}

			if ( $user->user_registered ) {
				echo '<li class="-row -registered list-group-item">';
					Info::renderIcon( 'registered' );
					echo ' ';
					Info::renderRegistered( $user->user_registered );
				echo '</li>';
			}

			// TODO: add option for display
			echo WordPress\Strings::getJoined( WordPress\User::getRoleList( $user ),
				'<li class="-row -roles list-group-item">'.Info::renderIcon( 'roles', NULL, FALSE, FALSE ).' ',
				'</li>'
			);

			echo '</ul>';

		$this->after_widget( $args, $instance );
	}

	public function form( $instance )
	{
		$this->before_form( $instance );

		// $this->form_content( $instance ); // TODO

		$this->form_open_group( 'heading' );
		$this->form_title( $instance );
		$this->form_title_link( $instance );
		$this->form_title_image( $instance );
		$this->form_class( $instance );
		$this->form_close_group();

		// $this->form_open_group( 'config' );
		// $this->form_checkbox( $instance, FALSE, 'shortcodes', _x( 'Process Shortcodes', 'Widget: Profile Summary', 'geditorial-widgets' ) );
		// $this->form_close_group();

		$this->form_open_group( 'customs' );
		$this->form_open_widget( $instance );
		$this->form_after_title( $instance );
		$this->form_close_widget( $instance );
		$this->form_close_group();

		$this->after_form( $instance );
	}

	public function update( $new, $old )
	{
		$this->flush_widget_cache();

		return $this->handle_update( $new, $old, [
			'embeds',
			'shortcodes',
			'filters',
			'legacy',
			'autop',
			'bypasscache',
		] );
	}
}
