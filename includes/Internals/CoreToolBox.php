<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

trait CoreToolBox
{

	// DEFAULT CALLBACK: use in module for descriptions
	// protected function tool_box_content() {}

	public function tool_box()
	{
		echo $this->wrap_open( [ 'card', '-toolbox-card' ] );
			$this->tool_box_title();

			if ( FALSE !== $this->tool_box_content() ) {

				$links = [];

				foreach ( $this->get_module_links() as $link )
					$links[] = Core\HTML::tag( 'a' , [
						'href'  => $link['url'],
						'class' => [ 'button', '-button' ],
					], $link['title'] );

				echo Core\HTML::wrap( Core\HTML::rows( $links ), '-toolbox-links' );
			}

		echo '</div>';
	}

	// DEFAULT CALLBACK
	protected function tool_box_title()
	{
		Core\HTML::h2( sprintf(
			/* translators: `%s`: module title */
			_x( 'Editorial: %s', 'Internal: CoreToolBox', 'geditorial-admin' ),
			$this->module->title
		), 'title' );
	}
}
