<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\WordPress;

trait PrintPage
{

	// USAGE: `$print = $this->_hook_submenu_adminpage( 'printpage' );`
	public function render_printpage_adminpage()
	{
		$this->render_content_printpage();
	}

	public function load_printpage_adminpage()
	{
		$this->action_self( 'printpage_render_head', 1 );
		$this->action_self( 'printpage_render_contents', 1 );

		$this->filter_false( 'show_admin_bar', 999999 );
	}

	public function printpage_render_head( $profile = FALSE ) {}
	public function printpage_render_contents( $profile = FALSE ) {}

	public function render_content_printpage()
	{
		$profile = WordPress\Post::get( self::req( 'profile', FALSE ) );

		$head_callback = [ $this, 'printpage__render_head' ];
		$foot_callback = [ $this, 'printpage__render_foot' ];
		$head_title    = $this->printpage__get_layout_pagetitle( $profile );
		$body_class    = $this->printpage__get_layout_bodyclass( $profile );
		$wrap_class    = $this->printpage__get_layout_wrapclass( $profile );

		$rtl  = is_rtl();
		$lang = get_bloginfo( 'language', 'display' );

		if ( $header = Helper::getLayout( 'print.header' ) )
			require_once $header; // to expose scope vars

		$this->actions( 'printpage_render_contents', $profile );

		if ( $footer = Helper::getLayout( 'print.footer' ) )
			require_once $footer; // to expose scope vars

		exit; // avoiding query monitor output
	}

	protected function printpage__render_head( $profile = FALSE )
	{
		wp_print_head_scripts();

		// https://github.com/graphicore/librebarcode
		// https://graphicore.github.io/librebarcode/
		if ( $this->get_setting( 'printpage_enqueue_librefonts' ) ) {
			Helper::linkStyleSheet( GEDITORIAL_URL.'assets/packages/libre-barcode-ean13-text/index.css', GEDITORIAL_VERSION, 'all' );
			Helper::linkStyleSheet( GEDITORIAL_URL.'assets/packages/libre-barcode-128-text/index.css', GEDITORIAL_VERSION, 'all' );
			Helper::linkStyleSheet( GEDITORIAL_URL.'assets/packages/libre-barcode-128/index.css', GEDITORIAL_VERSION, 'all' );
		}

		$this->actions( 'printpage_render_head', $profile );
	}

	protected function printpage__render_foot( $profile = FALSE )
	{
		$this->actions( 'printpage_render_foot', $profile );

		wp_print_footer_scripts();
	}

	protected function printpage__get_layout_pagetitle( $profile = FALSE )
	{
		if ( method_exists( $this, 'printpage_get_layout_pagetitle' ) )
			$pagettitle = $this->printpage_get_layout_pagetitle( $profile );

		else
			$pagettitle = WordPress\Post::title( $profile );

		return $this->filters( 'printpage_layout_pagetitle',
			$pagettitle ?? _x( 'Print Me!', 'Internal: PrintPage: Page Title', 'geditorial' ), $profile );
	}

	protected function printpage__get_layout_bodyclass( $profile = FALSE, $extra = [] )
	{
		if ( method_exists( $this, 'printpage_get_layout_bodyclass' ) )
			$list = $this->printpage_get_layout_bodyclass( $profile );
		else
			$list = [];

		return $this->filters( 'printpage_layout_bodyclass',
			Core\HTML::prepClass( 'printpage', ( is_rtl() ? 'rtl' : 'ltr' ), $list, $extra ), $profile );
	}

	// FIXME: handle padding
	protected function printpage__get_layout_wrapclass( $profile = FALSE, $extra = [] )
	{
		if ( method_exists( $this, 'printpage_get_layout_wrapclass' ) )
			$list = $this->printpage_get_layout_wrapclass( $profile );
		else
			$list = [];

		return $this->filters( 'printpage_layout_wrapclass',
			Core\HTML::prepClass( 'wrap', $list, $extra ), $profile );
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
