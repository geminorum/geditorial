<?php namespace geminorum\gEditorial\Modules\Magazine\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Modules\Magazine\ModuleTemplate;
use geminorum\gEditorial\WordPress;

class IssueCover extends gEditorial\Widget
{

	const MODULE = 'magazine';
	const WIDGET = 'magazine_issue_cover';

	public static function setup()
	{
		return [
			'title' => _x( 'Editorial: Issue Cover', 'Widget Title', 'geditorial-magazine' ),
			'desc'  => _x( 'Displays latest, selected, connected or current issue cover.', 'Widget Description', 'geditorial-magazine' ),
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
		$type = self::constant( 'issue_cpt', 'issue' );

		if ( ! empty( $instance['custom_link'] ) )
			$link = $instance['custom_link'];

		else if ( empty( $instance['link_issue'] ) )
			$link = FALSE;

		$atts = [
			'type'  => $type,
			'size'  => empty( $instance['image_size'] ) ? WordPress\Media::getAttachmentImageDefaultSize( $type ) : $instance['image_size'],
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
		$type = self::constant( 'issue_cpt', 'issue' );

		$this->before_form( $instance );

		$this->form_title( $instance );
		$this->form_title_link( $instance );
		$this->form_title_image( $instance );

		$this->form_page_id( $instance, '0', 'page_id', 'posttype', $type, _x( 'The Issue:', 'Widget: Issue Cover', 'geditorial-magazine' ) );
		$this->form_image_size( $instance, NULL, 'image_size', $type );

		$this->form_checkbox( $instance, FALSE, 'latest_issue', _x( 'Always the latest issue', 'Widget: Issue Cover', 'geditorial-magazine' ) );
		$this->form_checkbox( $instance, TRUE, 'number_line', _x( 'Display the Number Line', 'Widget: Issue Cover', 'geditorial-magazine' ) );
		$this->form_checkbox( $instance, TRUE, 'link_issue', _x( 'Link to the issue', 'Widget: Issue Cover', 'geditorial-magazine' ) );
		$this->form_custom_link( $instance );

		$this->form_context( $instance );
		$this->form_class( $instance );

		echo '<div class="-group">';
		$this->form_open_widget( $instance );
		$this->form_after_title( $instance );
		$this->form_close_widget( $instance );
		echo '</div>';

		$this->after_form( $instance );
	}

	public function update( $new, $old )
	{
		$this->flush_widget_cache();

		return $this->handle_update( $new, $old, [
			'latest_issue',
			'number_line',
			'link_issue',
		] );
	}
}
