<?php namespace geminorum\gEditorial\Controls;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Welcome extends gEditorial\Control
{
	const CONTROL = 'welcome';

	public function render_content()
	{
		$system = gEditorial\Plugin::system();

		echo '<label class="'.static::BASE.'-customize-control-label">';

			echo Core\HTML::span( $this->label, static::BASE.'-customize-control-title customize-control-title' );

			Core\HTML::desc( Core\Text::wordWrap( sprintf(
				/* translators: 1: Storefront, 2: start <a> tag, 3: Storefront, 4: end <a> tag */
				_x( 'There\'s a range of %1$s modules available to put additional power in your hands. Check out the %2$s%3$s%4$s page in your dashboard for more information.', 'Control: Welcome', 'geditorial-admin' ),
				$system,
				'<a href="'.Core\HTML::escapeURL( gEditorial\Settings::getURLbyContext( 'settings', TRUE ) ).'">',
				$system,
				'</a>'
			) ) );

			self::actions( 'moreinfo', $system );

			echo Core\HTML::span( sprintf(
				/* translators: `%s`: Editorial System Title */
				_x( 'Enjoying %s?', 'Control: Welcome', 'geditorial-admin' ),
				$system
			), 'customize-control-title' );

			Core\HTML::desc( Core\Text::wordWrap( sprintf(
				/* translators: `%1$s`: start `<a>` tag, `%2$s`: end `<a>` tag */
				_x( 'Why not leave us a review on %1$sWordPress.org%2$s?  We\'d really appreciate it!', 'Control: Welcome', 'geditorial-admin' ),
				'<a href="https://wordpress.org/plugins/geditorial">',
				'</a>'
			) ) );

			self::actions( 'moreinfo_after', $system );

		echo '</label>';
	}
}
