<?php namespace geminorum\gEditorial\Modules\Actions;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;

class Actions extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'       => 'actions',
			'title'      => _x( 'Actions', 'Modules: Actions', 'geditorial' ),
			'textdomain' => FALSE,
			'frontend'   => FALSE,
			'autoload'   => TRUE,
		];
	}

	public function init()
	{
		parent::init();

		if ( is_admin() )
			return;

		add_filter( 'the_content', [ $this, 'the_content' ], 998 );
	}

	public function the_content( $content )
	{
		if ( defined( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS' )
			&& GEDITORIAL_DISABLE_CONTENT_ACTIONS )
				return $content;

		$before = $after = '';

		if ( has_action( $this->base.'_content_before' ) ) {
			ob_start();
				do_action( $this->base.'_content_before', $content );
			$before = ob_get_clean();

			if ( trim( $before ) )
				$before = '<div class="'.$this->base.'-wrap-actions content-before">'.$before.'</div>';
		}

		if ( has_action( $this->base.'_content_after' ) ) {
			ob_start();
				do_action( $this->base.'_content_after', $content );
			$after = ob_get_clean();

			if ( trim( $after ) )
				$after = '<div class="'.$this->base.'-wrap-actions content-after">'.$after.'</div>';
		}

		return $before.$content.$after;
	}
}
