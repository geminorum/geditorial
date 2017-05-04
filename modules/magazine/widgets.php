<?php namespace geminorum\gEditorial\Widgets\Magazine;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\Templates\Magazine as ModuleTemplate;

class IssueCover extends gEditorial\Widget
{

	const MODULE = 'magazine';

	protected function setup()
	{
		return [
			'module' => 'magazine',
			'name'   => 'magazine_issue_cover',
			'class'  => 'magazine-issue-cover',
			'title'  => _x( 'Editorial Magazine: Issue Cover', 'Modules: Magazine: Widget Title', GEDITORIAL_TEXTDOMAIN ),
			'desc'   => _x( 'Displays selected issue cover', 'Modules: Magazine: Widget Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public function widget( $args, $instance )
	{
		if ( ! $instance['latest_issue']
			&& ! $instance['issue_id']
			&& ! is_singular() )
				return;

		if ( ! empty( $instance['latest_issue'] ) )
			$prefix = 'latest_issue';

		else if ( ! empty( $instance['issue_id'] ) )
			$prefix = 'issue_'.$instance['issue_id'];

		else
			$prefix = 'queried_'.get_queried_object_id();

		$this->widget_cache( $args, $instance, '_'.$prefix );
	}

	public function widget_html( $args, $instance )
	{
		$atts = [
			'type'  => self::constant( 'issue_cpt', 'issue' ),
			'size'  => empty( $instance['image_size'] ) ? NULL : $instance['image_size'],
			'echo'  => FALSE,
			'title' => 'number',
		];

		if ( ! empty( $instance['latest_issue'] ) )
			$atts['id'] = (int) WordPress::getLastPostOrder( $atts['type'], '', 'ID', 'publish' );

		else if ( ! empty( $instance['issue_id'] ) )
			$atts['id'] = (int) $instance['issue_id'];

		else if ( is_singular( $atts['type'] ) )
			$atts['id'] = NULL;

		else if ( is_singular() )
			$atts['id'] = 'assoc';

		if ( ! $html = ModuleTemplate::cover( $atts ) )
			return FALSE;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );
			echo $html;
		$this->after_widget( $args, $instance );

		return TRUE;
	}

	public function form( $instance )
	{
		$cpt = self::constant( 'issue_cpt', 'issue' );

		echo '<div class="geditorial-admin-wrap-widgetform">';

		$this->form_title( $instance );
		$this->form_title_link( $instance );

		$this->form_post_id( $instance, '0', 'issue_id', 'posttype', $cpt, _x( 'The Issue:', 'Modules: Magazine: Widget: Issue Cover', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_image_size( $instance, $cpt.'-thumbnail', 'image_size', $cpt );

		$this->form_checkbox( $instance, FALSE, 'latest_issue', _x( 'Always the latest issue', 'Modules: Magazine: Widget: Issue Cover', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_checkbox( $instance, FALSE, 'link_issue', _x( 'Link to the issue', 'Modules: Magazine: Widget: Issue Cover', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_checkbox( $instance, FALSE, 'number_line', _x( 'Display the Number Meta', 'Modules: Magazine: Widget: Issue Cover', GEDITORIAL_TEXTDOMAIN ) );

		$this->form_context( $instance );
		$this->form_class( $instance );

		echo '</div>';
	}

	public function update( $new_instance, $old_instance )
	{
		$instance = $old_instance;

		$instance['title']        = strip_tags( $new_instance['title'] );
		$instance['title_link']   = strip_tags( $new_instance['title_link'] );
		$instance['issue_id']     = intval( $new_instance['issue_id'] );
		$instance['image_size']   = isset( $new_instance['image_size'] ) ? strip_tags( $new_instance['image_size'] ) : 'thumbnail';
		$instance['latest_issue'] = isset( $new_instance['latest_issue'] );
		$instance['link_issue']   = isset( $new_instance['link_issue'] );
		$instance['number_line']  = isset( $new_instance['number_line'] );
		$instance['context']      = strip_tags( $new_instance['context'] );
		$instance['class']        = strip_tags( $new_instance['class'] );

		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );

		if ( isset( $alloptions[$this->alt_option_name] ) )
			delete_option( $this->alt_option_name );

		return $instance;
	}
}
