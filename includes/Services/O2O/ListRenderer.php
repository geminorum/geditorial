<?php namespace geminorum\gEditorial\Services\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

// `P2P_List_Renderer`
class ListRenderer extends Core\Base
{

	public static function query_and_render( $args )
	{
		$ctype = API::type( $args['ctype'] );

		if ( ! $ctype ) {
			trigger_error( sprintf( "Unregistered connection type '%s'.", $args['ctype'] ), E_USER_WARNING );
			return '';
		}

		if ( ! $directed = $ctype->find_direction( $args['item'] ) )
			return '';

		$context = $args['context'];

		$extra_qv = [
			'o2o:per_page' => -1,
			'o2o:context'  => $context
		];

		$connected = call_user_func( [ __NAMESPACE__.'\\'.$directed, $args['method'] ], $args['item'], $extra_qv, 'abstract' );

		switch ( $args['mode'] ) {

			case 'inline':

				$render_args = [ 'separator' => ', ' ];

			break;
			case 'ol':

				$render_args = [
					'before_list' => '<ol id="'.$ctype->name.'_list">',
					'after_list'  => '</ol>',
				];

			break;
			case 'ul':
			default:

				$render_args = [
					'before_list' => '<ul id="'.$ctype->name.'_list">',
					'after_list'  => '</ul>',
				];
		}

		$render_args['echo'] = FALSE;

		$html = self::render( $connected, $render_args );

		return apply_filters( "o2o_{$context}_html", $html, $connected, $directed, $args['mode'] );
	}

	public static function render( $list, $args = [] )
	{
		if ( empty( $list->items ) )
			return '';

		$args = self::args( $args, [
			'before_list' => '<ul>',
			'after_list'  => '</ul>',
			'before_item' => '<li>',
			'after_item'  => '</li>',
			'separator'   => FALSE,
			'echo'        => TRUE
		] );

		if ( $args['separator'] ) {

			if ( '<ul>' == $args['before_list'] )
				$args['before_list'] = '';

			if ( '</ul>' == $args['after_list'] )
				$args['after_list'] = '';
		}

		if ( ! $args['echo'] )
			ob_start();

		echo $args['before_list'];

		if ( $args['separator'] ) {

			$rendered = [];

			foreach ( $list->items as $item )
				$rendered[] = self::render_item( $item );

			echo implode( $args['separator'], $rendered );

		} else {

			foreach ( $list->items as $item )
				echo $args['before_item'].self::render_item( $item ).$args['after_item'];
		}

		echo $args['after_list'];

		if ( ! $args['echo'] )
			return ob_get_clean();
	}

	private static function render_item( $item )
	{
		return Core\HTML::link( $item->get_title(), $item->get_permalink() );
	}
}
