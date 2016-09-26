<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialSettingsCore extends gEditorialBaseCore
{

	const REPORTS  = 'geditorial-reports';
	const SETTINGS = 'geditorial-settings';
	const TOOLS    = 'geditorial-tools';

	public static function reportsURL( $full = TRUE, $dashboard = FALSE )
	{
		$relative = 'index.php?page='.self::REPORTS;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	public static function settingsURL( $full = TRUE )
	{
		$relative = 'admin.php?page='.self::SETTINGS;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	public static function toolsURL( $full = TRUE, $dashboard = FALSE )
	{
		$relative = $dashboard ? 'index.php?page='.self::TOOLS : 'admin.php?page='.self::TOOLS;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	public static function isReports( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		if ( isset( $screen->base )
			&& FALSE !== strripos( $screen->base, self::REPORTS ) )
				return TRUE;

		return FALSE;
	}

	public static function isSettings( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		if ( isset( $screen->base )
			&& FALSE !== strripos( $screen->base, self::SETTINGS ) )
				return TRUE;

		return FALSE;
	}

	public static function isTools( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		if ( isset( $screen->base )
			&& FALSE !== strripos( $screen->base, self::TOOLS ) )
				return TRUE;

		return FALSE;
	}

	public static function isDashboard( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		if ( isset( $screen->base )
			&& FALSE !== strripos( $screen->base, 'dashboard' ) )
				return TRUE;

		return FALSE;
	}

	public static function showOptionNone( $string = NULL )
	{
		if ( $string )
			return sprintf( _x( '&mdash; Select %s &mdash;', 'Settings: Dropdown Select Option None', GEDITORIAL_TEXTDOMAIN ), $string );

		return _x( '&mdash; Select &mdash;', 'Settings: Dropdown Select Option None', GEDITORIAL_TEXTDOMAIN );
	}

	public static function infoP2P()
	{
		return _x( 'Connected via <code>P2P</code>', 'Settings: Setting Info', GEDITORIAL_TEXTDOMAIN );
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

	public static function getSetting_autolink_terms( $section )
	{
		return array(
			'field'       => 'autolink_terms',
			'title'       => _x( 'Autolink Terms', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Trying to link the terms titles in the content.', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '0',
			'section'     => $section,
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
			'description' => _x( 'Puts automatically in the content', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => 'none',
			'section'     => $section,
			'values'      => array(
				'none'   => _x( 'No', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
				'before' => _x( 'Before', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
				'after'  => _x( 'After', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	public static function getSetting_insert_content_before( $section )
	{
		$args = self::getSetting_insert_content( $section );

		$args['field'] = 'insert_content_before';
		unset( $args['values'], $args['type'], $args['default'] );

		return $args;
	}

	public static function getSetting_insert_content_after( $section )
	{
		$args = self::getSetting_insert_content( $section );

		$args['field'] = 'insert_content_after';
		unset( $args['values'], $args['type'], $args['default'] );

		return $args;
	}

	public static function getSetting_before_content( $section )
	{
		return array(
			'field'       => 'before_content',
			'type'        => 'textarea',
			'title'       => _x( 'Before Content', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Adds <code>HTML</code> to the start of all the supported posttypes', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'section'     => $section,
		);
	}

	public static function getSetting_after_content( $section )
	{
		return array(
			'field'       => 'after_content',
			'type'        => 'textarea',
			'title'       => _x( 'After Content', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Adds <code>HTML</code> to the end of all the supported posttypes', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
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

	public static function getSetting_summary_scope( $section )
	{
		return array(
			'field'       => 'summary_scope',
			'type'        => 'select',
			'title'       => _x( 'Summary Scope', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Dashboard Widget Summary User Scope', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => 'all',
			'section'     => $section,
			'values'      => array(
				'all'     => _x( 'All Users', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
				'current' => _x( 'Current User', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			),
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

	public static function sub( $default = 'general' )
	{
		return isset( $_REQUEST['sub'] ) ? trim( $_REQUEST['sub'] ) : $default;
	}

	public static function headerTitle( $title = NULL, $back = NULL, $to = NULL, $icon = '' )
	{
		if ( is_null( $title ) )
			$title = _x( 'Editorial', 'Settings', GEDITORIAL_TEXTDOMAIN );

		if ( is_null( $back )
			&& current_user_can( 'manage_options' ) ) // FIXME: get cap from settings module
				$back = self::settingsURL();

		if ( is_null( $to ) )
			$to = _x( 'Back to Editorial', 'Settings', GEDITORIAL_TEXTDOMAIN );

		if ( $icon )
			$icon = ' dashicons-before dashicons-'.$icon;

		if ( $back )
			printf( '<h1 class="settings-title'.$icon.'">%s <a href="%s" class="-action page-title-action">%s</a></h1>', $title, $back, $to );
		else
			printf( '<h1 class="settings-title'.$icon.'">%s</h1>', $title );
	}

	public static function message( $messages = NULL )
	{
		if ( is_null( $messages ) )
			$messages = self::messages();

		if ( isset( $_GET['message'] ) ) {

			if ( isset( $messages[$_GET['message']] ) )
				echo $messages[$_GET['message']];
			else
				gEditorialHTML::warning( $_GET['message'], TRUE );

			$_SERVER['REQUEST_URI'] = remove_query_arg( 'message', $_SERVER['REQUEST_URI'] );
		}
	}

	public static function messages()
	{
		return array(
			'resetting' => gEditorialHTML::success( _x( 'Settings reset.', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'optimized' => gEditorialHTML::success( _x( 'Tables optimized.', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'updated'   => gEditorialHTML::success( _x( 'Settings updated.', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'purged'    => gEditorialHTML::success( _x( 'Data purged.', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'error'     => gEditorialHTML::error( _x( 'Error occurred!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'wrong'     => gEditorialHTML::error( _x( 'Something\'s wrong!', 'Settings', GEDITORIAL_TEXTDOMAIN ) ),
			'nochange'  => gEditorialHTML::error( _x( 'No item changed!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'created'   => self::counted( _x( '%s items(s) created!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'deleted'   => self::counted( _x( '%s items(s) deleted!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'cleaned'   => self::counted( _x( '%s items(s) cleaned!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'changed'   => self::counted( _x( '%s items(s) changed!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'emptied'   => self::counted( _x( '%s items(s) emptied!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'ordered'   => self::counted( _x( '%s items(s) re-ordered!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'huh'       => gEditorialHTML::error( self::huh( empty( $_REQUEST['huh'] ) ? NULL : $_REQUEST['huh'] ) ),
		);
	}

	public static function counted( $message = NULL, $count = NULL, $class = 'notice-success' )
	{
		if ( is_null( $message ) )
			$message = _x( '%s Counted!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN );

		if ( is_null( $count ) )
			$count = isset( $_REQUEST['count'] ) ? $_REQUEST['count'] : 0;

		return gEditorialHTML::notice( sprintf( $message, number_format_i18n( $count ) ), $class.' fade', FALSE );
	}

	public static function cheatin( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Cheatin&#8217; uh?', 'Settings: Message', GEDITORIAL_TEXTDOMAIN );

		gEditorialHTML::error( $message, TRUE );
	}

	public static function huh( $message = NULL )
	{
		if ( $message )
			return sprintf ( _x( 'huh? %s', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ), $message );

		return _x( 'huh?', 'Settings: Message', GEDITORIAL_TEXTDOMAIN );
	}

	public static function headerNav( $uri = '', $active = '', $subs = array(), $prefix = 'nav-tab-', $tag = 'h3' )
	{
		gEditorialHTML::headerNav( $uri, $active, $subs, $prefix, $tag );
	}

	public static function moduleButtons( $module, $enabled = FALSE )
	{
		echo gEditorialHTML::tag( 'input', array(
			'type'  => 'submit',
			'value' => _x( 'Enable', 'Settings: Button', GEDITORIAL_TEXTDOMAIN ),
			'style' => $enabled ? 'display:none' : FALSE,
			'class' => array( 'hide-if-no-js', 'button', 'button-primary', 'button-toggle' ),
			'data'  => array(
				'module' => $module->name,
				'do'     => 'enable',
			),
		) );

		echo gEditorialHTML::tag( 'input', array(
			'type'  => 'submit',
			'value' => _x( 'Disable', 'Settings: Button', GEDITORIAL_TEXTDOMAIN ),
			'style' => $enabled ? FALSE : 'display:none',
			'class' => array( 'hide-if-no-js', 'button', 'button-secondary', 'button-toggle', 'button-remove' ),
			'data'  => array(
				'module' => $module->name,
				'do'     => 'disable',
			),
		) );

		// echo gEditorialHTML::tag( 'span', array(
		// 	'class' => array( 'button', 'hide-if-js' ),
		// ), _x( 'You have to enable Javascript!', 'Settings: Notice', GEDITORIAL_TEXTDOMAIN ) );
	}

	public static function moduleConfigure( $module, $enabled = FALSE )
	{
		if ( $module->configure )
			echo gEditorialHTML::tag( 'a', array(
				'href'  => add_query_arg( 'page', $module->settings, get_admin_url( NULL, 'admin.php' ) ),
				'style' => $enabled ? FALSE : 'display:none',
				'class' => array( 'button', 'button-primary', 'button-configure' ),
				'data'  => array(
					'module' => $module->name,
					'do'     => 'configure',
				),
			), _x( 'Configure', 'Settings: Button', GEDITORIAL_TEXTDOMAIN ) );
	}

	public static function moduleInfo( $module, $tag = 'h3' )
	{
		$links = self::getModuleWiki( $module );

		$link = gEditorialHTML::tag( 'a', array(
			'href'   => $links[0],
			'title'  => $links[1],
			'target' => '_blank',
		), $module->title );

		echo gEditorialHTML::tag( $tag, $link );
		echo gEditorialHTML::tag( 'p', $module->desc );
	}

	public static function getModuleWiki( $module = FALSE )
	{
		if ( $module ) {

			return array(
				'https://github.com/geminorum/geditorial/wiki/Modules-'.gEditorialHelper::moduleSlug( $module->name ),
				sprintf( 'Editorial %s Documentation', gEditorialHelper::moduleSlug( $module->name, FALSE ) ),
				'https://github.com/geminorum/geditorial',
			);

		} else {

			return array(
				'https://github.com/geminorum/geditorial/wiki',
				'Editorial Documentation',
				'https://github.com/geminorum/geditorial',
			);
		}
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

		return vsprintf( $template, self::getModuleWiki( $module ) );
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
