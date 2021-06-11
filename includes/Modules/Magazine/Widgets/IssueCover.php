<?php namespace geminorum\gEditorial\Modules\Magazine\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Modules\Magazine\ModuleTemplate;

class IssueCover extends gEditorial\Widget
{

	const MODULE = 'magazine';

	public static function setup()
	{
		return [
			'module' => 'magazine',
			'name'   => 'magazine_issue_cover',
			'class'  => 'magazine-issue-cover',
			'title'  => _x( 'Editorial: Issue Cover', 'Widget Title', 'geditorial-magazine' ),
			'desc'   => _x( 'Displays latest, selected, connected or current issue cover.', 'Widget Description', 'geditorial-magazine' ),
		];
	}

	public function widget( $args, $instance )
	{
		if ( ! $instance['latest_issue']
			&& ! $instance['page_id']
			&& ! is_singular() )
				return;

		if ( ! empty( $instance['latest_issue'] ) )
			$prefix = '_latest_issue';

		else if ( ! empty( $instance['page_id'] ) )
			$prefix = '_issue_'.$instance['page_id'];

		else
			$prefix = '_queried_'.get_queried_object_id();

		$this->widget_cache( $args, $instance, $prefix );
	}

	public function widget_html( $args, $instance )
	{
		$link = 'parent';

		if ( ! empty( $instance['custom_link'] ) )
			$link = $instance['custom_link'];

		else if ( empty( $instance['link_issue'] ) )
			$link = FALSE;

		$atts = [
			'type'  => self::constant( 'issue_cpt', 'issue' ),
			'size'  => empty( $instance['image_size'] ) ? NULL : $instance['image_size'],
			'title' => empty( $instance['number_line'] ) ? 'title' : 'number',
			'link'  => $link,
			'echo'  => FALSE,
		];

		if ( ! empty( $instance['latest_issue'] ) )
			$atts['id'] = (int) ModuleTemplate::getLatestIssueID();

		else if ( ! empty( $instance['page_id'] ) )
			$atts['id'] = (int) $instance['page_id'];

		else if ( is_singular( $atts['type'] ) )
			$atts['id'] = NULL;

		else if ( is_singular() )
			$atts['id'] = 'paired';

		else
			return FALSE;

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

		$this->before_form( $instance );

		$this->form_title( $instance );
		$this->form_title_link( $instance );
		$this->form_title_image( $instance );

		$this->form_page_id( $instance, '0', 'page_id', 'posttype', $cpt, _x( 'The Issue:', 'Widget: Issue Cover', 'geditorial-magazine' ) );
		$this->form_image_size( $instance, $cpt.'-thumbnail', 'image_size', $cpt );

		$this->form_checkbox( $instance, FALSE, 'latest_issue', _x( 'Always the latest issue', 'Widget: Issue Cover', 'geditorial-magazine' ) );
		$this->form_checkbox( $instance, TRUE, 'number_line', _x( 'Display the Number Line', 'Widget: Issue Cover', 'geditorial-magazine' ) );
		$this->form_checkbox( $instance, TRUE, 'link_issue', _x( 'Link to the issue', 'Widget: Issue Cover', 'geditorial-magazine' ) );
		$this->form_custom_link( $instance );

		$this->form_context( $instance );
		$this->form_class( $instance );

		$this->after_form( $instance );
	}

	public function update( $new_instance, $old_instance )
	{
		$instance = $old_instance;

		$instance['title']        = strip_tags( $new_instance['title'] );
		$instance['title_link']   = strip_tags( $new_instance['title_link'] );
		$instance['title_image']  = strip_tags( $new_instance['title_image'] );
		$instance['page_id']      = (int) $new_instance['page_id'];
		$instance['image_size']   = isset( $new_instance['image_size'] ) ? strip_tags( $new_instance['image_size'] ) : 'thumbnail';
		$instance['latest_issue'] = isset( $new_instance['latest_issue'] );
		$instance['number_line']  = isset( $new_instance['number_line'] );
		$instance['link_issue']   = isset( $new_instance['link_issue'] );
		$instance['custom_link']  = strip_tags( $new_instance['custom_link'] );
		$instance['context']      = strip_tags( $new_instance['context'] );
		$instance['class']        = strip_tags( $new_instance['class'] );

		$this->flush_widget_cache();

		return $instance;
	}
}
