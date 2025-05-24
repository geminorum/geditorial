<?php namespace geminorum\gEditorial\Modules\Tabs;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'tabs';

	/**
	 * Renders a Bootstrap 5 tabbed interface.
	 *
	 * TODO: version for wp-admin
	 *
	 * @param  array $items
	 * @param  bool   $verbose
	 * @return string|true $html
	 */
	public static function bootstrap5Tabs( $items, $post = NULL, $callback_args = [], $verbose = TRUE )
	{
		if ( empty( $items ) )
			return '';

		if ( ! Core\Arraay::isAssoc( $items ) )
			$items = Core\Arraay::reKey( $items, 'name' );

		$preset = array_filter( Core\Arraay::column( $items, 'active', 'name' ) );
		$active = Core\Arraay::keyFirst( count( $preset ) ? $preset : $items );
		$html   = '';

		foreach ( $items as $item_name => $item_args ) {

			$title = apply_filters( sprintf( '%s_%s_%s', static::BASE, static::MODULE, 'item_title' ),
				empty( $item_args['title'] ) ? $item_name : $item_args['title'],
				$item_name,
				$item_args,
				$post
			);

			$html.= '<li class="nav-item" role="presentation">';

			$html.= Core\HTML::tag( 'button', [
				'title' => empty( $item_args['description'] ) ? FALSE : $item_args['description'],
				'id'    => $item_name,
				'type'  => 'button',
				'role'  => 'tab',
				'class' => [
					'nav-link',
					$active === $item_name ? 'active' : '',
				],
				'data'  => [
					'bs-toggle' => 'tab',
					'bs-target' => '#'.$item_name.'-tab-pane',
				],
				'aria-controls' => $item_name.'-tab-pane',
				'aria-selected' => 'true',
			], $title );

			$html.= '</li>';
		}

		$html = Core\HTML::tag( 'ul', [
			'class' => [ 'nav', 'nav-tabs' ],
			'role'  => 'tablist',
		], $html );

		$html = '<div class="clearfix"></div>'.Core\HTML::tag( 'nav', $html );
		$html.= '<div class="tab-content">';

		$before_hook = sprintf( '%s_%s_render_content_before', static::BASE, static::MODULE );
		$after_hook  = sprintf( '%s_%s_render_content_after',  static::BASE, static::MODULE );

		if ( ! has_action( $before_hook ) ) $before_hook = FALSE;
		if ( ! has_action( $after_hook ) )  $after_hook  = FALSE;

		foreach ( $items as $item_name => $item_args ) {

			$before = $after = '';

			if ( ! empty( $item_args['content'] ) )
				$content = $item_args['content'];

			else if ( isset( $item_args['callback'] ) )
				$content = self::buffer( $item_args['callback'],
					array_merge( [ $post, $item_name, $item_args ], (array) $callback_args ) );

			else
				$content = '';

			if ( $before_hook ) {
				ob_start();
					do_action( $before_hook, $post, $item_name, $item_args, (array) $callback_args, $content );
				$before = ob_get_clean();
			}

			if ( $after_hook ) {
				ob_start();
					do_action( $after_hook, $post, $item_name, $item_args, (array) $callback_args, $content );
				$after = ob_get_clean();
			}

			$html.= Core\HTML::tag( 'div', [
				'id'    => $item_name.'-tab-pane',
				'role'  => 'tabpanel',
				'class' => [
					'tab-pane',
					'fade',
					'py-3',
					$active === $item_name ? 'show active' : '',
				],
				'tabindex'        => '0',
				'aria-labelledby' => $item_name,
			], $before.$content.$after );
		}

		$html.= '</div>';

		if ( ! $verbose )
			return $html;

		echo $html;
		return TRUE;
	}
}
