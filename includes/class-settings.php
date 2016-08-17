<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialSettingsCore extends gEditorialBaseCore
{

	public static function showOptionNone( $string = NULL )
	{
		if ( $string )
			return sprintf( _x( '&mdash; Select %s &mdash;', 'Settings: Dropdown Select Option None', GEDITORIAL_TEXTDOMAIN ), $string );

		return _x( '&mdash; Select &mdash;', 'Settings: Dropdown Select Option None', GEDITORIAL_TEXTDOMAIN );
	}

	public static function getSetting_editor_button( $section )
	{
		return array(
			'field'   => 'editor_button',
			'title'   => _x( 'Editor Button', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => '1',
			'section' => $section,
		);
	}

	public static function getSetting_shortcode_support( $section )
	{
		return array(
			'field'   => 'shortcode_support',
			'title'   => _x( 'Default Shortcodes', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => '1',
			'section' => $section,
		);
	}

	public static function getSetting_markdown_support( $section )
	{
		return array(
			'field'   => 'markdown_support',
			'title'   => _x( 'Markdown Support', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => '0',
			'section' => $section,
		);
	}

	public static function getSetting_multiple_instances( $section )
	{
		return array(
			'field'   => 'multiple_instances',
			'title'   => _x( 'Multiple Instances', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => '0',
			'section' => $section,
		);
	}

	public static function getSetting_rewrite_prefix( $section )
	{
		return array(
			'field'       => 'rewrite_prefix',
			'type'        => 'text',
			'title'       => _x( 'URL Base Prefix', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'String before the permalink structure', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '',
			'dir'         => 'ltr',
			'placeholder' => 'wiki',
			'section'     => $section,
		);
	}

	public static function getSetting_redirect_archives( $section )
	{
		return array(
			'field'       => 'redirect_archives',
			'type'        => 'text',
			'title'       => _x( 'Redirect Archives', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Redirect Post Type Archives to a URL', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '',
			'dir'         => 'ltr',
			'placeholder' => 'http://example.com/archives/',
			'section'     => $section,
		);
	}

	public static function getSetting_comment_status( $section )
	{
		return array(
			'field'       => 'comment_status',
			'type'        => 'select',
			'title'       => _x( 'Comment Status', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Default Comment Status of the Posttype', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => 'closed',
			'section'     => $section,
			'values'      => array(
				'open'   => _x( 'Open', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
				'closed' => _x( 'Closed', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	public static function getSetting_insert_content( $section )
	{
		return array(
			'field'       => 'insert_content',
			'type'        => 'select',
			'title'       => _x( 'Insert in Content', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Put html automatically on the content', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => 'none',
			'section'     => $section,
			'values'      => array(
				'none'   => _x( 'No', 'Module Core: Insert in Content Option', GEDITORIAL_TEXTDOMAIN ),
				'before' => _x( 'Before', 'Module Core: Insert in Content Option', GEDITORIAL_TEXTDOMAIN ),
				'after'  => _x( 'After', 'Module Core: Insert in Content Option', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	public static function getSetting_before_content( $section )
	{
		return array(
			'field'       => 'before_content',
			'type'        => 'textarea',
			'title'       => _x( 'Before Content', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Adds <code>HTML</code> content to the start of all the supported posttypes', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'section'     => $section,
		);
	}

	public static function getSetting_after_content( $section )
	{
		return array(
			'field'       => 'after_content',
			'type'        => 'textarea',
			'title'       => _x( 'After Content', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Adds <code>HTML</code> content to the end of all the supported posttypes', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'section'     => $section,
		);
	}

	public static function getSetting_admin_ordering( $section )
	{
		return array(
			'field'       => 'admin_ordering',
			'title'       => _x( 'Ordering', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Enhance admin edit page ordering', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '1',
			'section'     => $section,
		);
	}

	public static function getSetting_admin_restrict( $section )
	{
		return array(
			'field'       => 'admin_restrict',
			'title'       => _x( 'Restrictions', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Enhance admin edit page restrictions', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '0',
			'section'     => $section,
		);
	}

	public static function getSetting_dashboard_widgets( $section )
	{
		return array(
			'field'       => 'dashboard_widgets',
			'title'       => _x( 'Dashboard Widgets', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Enhance admin dashboard with customized widgets', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '0',
			'section'     => $section,
		);
	}

	public static function getSetting_posttype_feeds( $section )
	{
		return array(
			'field'       => 'posttype_feeds',
			'title'       => _x( 'Feeds', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Supporting feeds on the posttype', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '0',
			'section'     => $section,
		);
	}

	public static function getSetting_posttype_pages( $section )
	{
		return array(
			'field'       => 'posttype_pages',
			'title'       => _x( 'Pages', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Supporting pagination on the posttype', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '0',
			'section'     => $section,
		);
	}

	public static function getSetting_calendar_type( $section )
	{
		return array(
			'field'   => 'calendar_type',
			'title'   => _x( 'Default Calendar', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'type'    => 'select',
			'default' => 'gregorian',
			'values'  => gEditorialHelper::getDefualtCalendars( TRUE ),
			'section' => $section,
		);
	}

	public static function settingsTitle( $title = NULL, $back = NULL, $to = NULL )
	{
		if ( is_null( $title ) )
			$title = _x( 'Editorial', 'Settings', GEDITORIAL_TEXTDOMAIN );

		if ( is_null( $back )
			&& current_user_can( 'manage_options' ) )
				$back = gEditorialHelper::settingsURL();

		if ( is_null( $to ) )
			$to = _x( 'Back to Editorial', 'Settings', GEDITORIAL_TEXTDOMAIN );

		if ( $back )
			printf( '<h1 class="settings-title">%s <a href="%s" class="-action page-title-action">%s</a></h1>', $title, $back, $to );
		else
			printf( '<h1 class="settings-title">%s</h1>', $title );
	}

	public static function settingsCredits()
	{
		echo '<div class="credits"><p>';
			echo 'You\'re using gEditorial v'.GEDITORIAL_VERSION.'<br />';
			echo 'This is a fork in structure of <a href="http://editflow.org/">EditFlow</a><br />';
			echo '<a href="https://github.com/geminorum/geditorial/issues">Feedback, Ideas and Bug Reports</a> are welcomed';
		echo '</p></div>';
	}

	public static function settingsSignature()
	{
		echo '<div class="signature"><p>';
			printf( _x( '<a href="%1$s" title="Editorial">gEditorial</a> is a <a href="%2$s">geminorum</a> project.', 'Settings', GEDITORIAL_TEXTDOMAIN ),
				'http://github.com/geminorum/geditorial',
				'http://geminorum.ir/' );
		echo '</p></div>';
	}

	public static function settingsHelpLinks( $module = FALSE, $template = NULL )
	{
		if ( is_null( $template ) )
			$template = '<div class="-links"><p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p></div>';

		if ( $module ) {

			return vsprintf( $template, array(
				'https://github.com/geminorum/geditorial/wiki/Modules-'.ucwords( $module->name ),
				sprintf( 'Editorial %s Documentation', ucwords( $module->name ) ),
				'https://github.com/geminorum/geditorial',
			) );

		} else {

			return vsprintf( $template, array(
				'https://github.com/geminorum/geditorial/wiki',
				'Editorial Documentation',
				'https://github.com/geminorum/geditorial',
			) );
		}
	}

	public static function settingsHelpContent( $module = FALSE )
	{
		$tabs = array();

		if ( function_exists( 'gnetwork_github' ) ) {

			if ( $module )
				$tabs[] = array(
					'id'      => 'geditorial-'.$module->name.'-overview',
					'title'   => sprintf( '%s Overview', ucwords( $module->name ) ),
					'content' => gnetwork_github( array(
						'repo'    => 'geminorum/geditorial',
						'type'    => 'wiki',
						'page'    => 'Modules-'.ucwords( $module->name ),
						'context' => 'help_tab',
					) ),
				);

			else
				$tabs[] = array(
					'id'      => 'geditorial-overview',
					'title'   => 'Editorial Overview',
					'content' => gnetwork_github( array(
						'repo'    => 'geminorum/geditorial',
						'type'    => 'wiki',
						'page'    => 'Modules',
						'context' => 'help_tab',
					) ),
				);
		}

		return $tabs;
	}
}
