<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\WordPress;

trait PrintPage
{

	// USAGE: `$print = $this->_hook_submenu_adminpage( 'printpage' );`
	public function render_printpage_adminpage()
	{
		$this->render_content_printpage();
	}

	public function render_content_printpage()
	{
		$head_callback = [ $this, 'render_print_head' ];
		$head_title    = $this->get_print_layout_pagetitle();
		$body_class    = $this->get_print_layout_bodyclass();
		$rtl           = is_rtl();

		if ( $header = Helper::getLayout( 'print.header' ) )
			require_once $header; // to expose scope vars

		$this->actions( 'print_contents' );

		if ( $footer = Helper::getLayout( 'print.footer' ) )
			require_once $footer; // to expose scope vars

		exit; // avoiding query monitor output
	}

	protected function render_print_head()
	{
		$this->actions( 'print_head' );
	}

	protected function get_print_layout_pagetitle()
	{
		return $this->filters( 'print_layout_pagetitle',
			_x( 'Print Me!', 'Module', 'geditorial' ) );
	}

	protected function get_print_layout_bodyclass( $extra = [] )
	{
		return $this->filters( 'print_layout_bodyclass',
			Core\HTML::prepClass( 'printpage', ( is_rtl() ? 'rtl' : 'ltr' ), $extra ) );
	}

	protected function get_printpage_url( $extra = [], $context = 'printpage' )
	{
		$extra['noheader'] = 1;
		return $this->get_adminpage_url( TRUE, $extra, $context );
	}

	// @SEE: https://stackoverflow.com/questions/819416/adjust-width-and-height-of-iframe-to-fit-with-content-in-it
	protected function render_print_iframe( $printpage = NULL )
	{
		if ( is_null( $printpage ) )
			$printpage = $this->get_printpage_url( [ 'single' => '1' ] );

		// prefix to avoid conflicts
		$func = $this->hook( 'resizeIframe' );
		$html = Core\HTML::tag( 'iframe', [
			'src'    => $printpage,
			'width'  => '100%',
			'height' => '0',
			'border' => '0',
			'onload' => $func.'(this)',
		], _x( 'Print Preview', 'Module', 'geditorial' ) );

		echo Core\HTML::wrap( $html, 'field-wrap -iframe -print-iframe' );

		// @REF: https://stackoverflow.com/a/9976309
		echo '<script>function '.$func.'(obj){obj.style.height=obj.contentWindow.document.documentElement.scrollHeight+"px";}</script>';
	}

	protected function render_print_button( $printpage = NULL, $button_class = '' )
	{
		if ( is_null( $printpage ) )
			$printpage = $this->get_printpage_url( [ 'single' => '1' ] );

		// prefix to avoid conflicts
		$func = $this->hook( 'printIframe' );
		$id   = $this->classs( 'printiframe' );

		echo Core\HTML::tag( 'iframe', [
			'id'     => $id,
			'src'    => $printpage,
			'class'  => '-hidden-print-iframe',
			'width'  => '0',
			'height' => '0',
			'border' => '0',
		], '' );

		echo Core\HTML::tag( 'a', [
			'href'    => '#',
			'class'   => [ 'button', $button_class ], //  button-small',
			'onclick' => $func.'("'.$id.'")',
		], _x( 'Print', 'Module', 'geditorial' ) );

		// @REF: https://hdtuto.com/article/print-iframe-content-using-jquery-example
		echo '<script>function '.$func.'(id){var frm=document.getElementById(id).contentWindow;frm.focus();frm.print();return false;}</script>';
	}

	public function settings_section_printpage()
	{
		Settings::fieldSection(
			_x( 'Printing', 'Internal: PrintPage: Setting Section Title', 'geditorial' )
		);
	}
}
