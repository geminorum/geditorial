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

	public static function priorityOptions( $format = TRUE )
	{
		return
			array_reverse( gEditorialArraay::range( -100, -1000, 100, $format ), TRUE ) +
			array_reverse( gEditorialArraay::range( -10, -100, 10, $format ), TRUE ) +
			gEditorialArraay::range( 0, 100, 10, $format ) +
			gEditorialArraay::range( 100, 1000, 100, $format );
	}

	public static function minutesOptions()
	{
		return array(
			'5'    => _x( '5 Minutes', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'10'   => _x( '10 Minutes', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'15'   => _x( '15 Minutes', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'30'   => _x( '30 Minutes', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'60'   => _x( '60 Minutes', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'120'  => _x( '2 Hours', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'180'  => _x( '3 Hours', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'240'  => _x( '4 Hours', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'480'  => _x( '8 Hours', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
			'1440' => _x( '24 Hours', 'Settings: Option: Time in Minutes', GEDITORIAL_TEXTDOMAIN ),
		);
	}

	public static function showOptionNone( $string = NULL )
	{
		if ( $string )
			return sprintf( _x( '&mdash; Select %s &mdash;', 'Settings: Dropdown Select Option None', GEDITORIAL_TEXTDOMAIN ), $string );

		return _x( '&mdash; Select &mdash;', 'Settings: Dropdown Select Option None', GEDITORIAL_TEXTDOMAIN );
	}

	public static function fieldSection( $title, $description = FALSE, $tag = 'h3' )
	{
		echo gEditorialHTML::tag( $tag, $title );

		gEditorialHTML::desc( $description );
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

	public static function getSetting_insert_priority( $section )
	{
		return array(
			'field'       => 'insert_priority',
			'type'        => 'priority',
			'title'       => _x( 'Insert Priority', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Priority of inserting buttons on the content.', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '10',
			'section'     => $section,
		);
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
			'section'     => $section,
		);
	}

	public static function getSetting_dashboard_widgets( $section )
	{
		return array(
			'field'       => 'dashboard_widgets',
			'title'       => _x( 'Dashboard Widgets', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Enhance admin dashboard with customized widgets', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'section'     => $section,
		);
	}

	public static function getSetting_summary_scope( $section )
	{
		return array(
			'field'       => 'summary_scope',
			'type'        => 'select',
			'title'       => _x( 'Summary Scope', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'User scope for the content summary', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => 'all',
			'section'     => $section,
			'values'      => array(
				'all'     => _x( 'All Users', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
				'current' => _x( 'Current User', 'Settings: Setting Option', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	public static function getSetting_count_not( $section )
	{
		return array(
			'field'       => 'count_not',
			'title'       => _x( 'Count Not', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Count not affacted items in content summary', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'section'     => $section,
		);
	}

	public static function getSetting_posttype_feeds( $section )
	{
		return array(
			'field'       => 'posttype_feeds',
			'title'       => _x( 'Feeds', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Supporting feeds on the posttype', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'section'     => $section,
		);
	}

	public static function getSetting_posttype_pages( $section )
	{
		return array(
			'field'       => 'posttype_pages',
			'title'       => _x( 'Pages', 'Settings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Supporting pagination on the posttype', 'Settings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
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
		return trim( self::req( 'sub', $default ) );
	}

	public static function wrapOpen( $sub = 'general', $base = 'geditorial', $page = 'settings' )
	{
		echo '<div class="wrap '.$base.'-admin-wrap '.$base.'-'.$page.' '.$base.'-'.$page.'-'.$sub.' sub-'.$sub.'">';
	}

	public static function wrapClose()
	{
		echo '<div class="clear"></div></div>';
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
			'noaccess'  => gEditorialHTML::error( _x( 'You do not have the access!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'created'   => self::counted( _x( '%s items(s) created!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'deleted'   => self::counted( _x( '%s items(s) deleted!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'cleaned'   => self::counted( _x( '%s items(s) cleaned!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'changed'   => self::counted( _x( '%s items(s) changed!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'emptied'   => self::counted( _x( '%s items(s) emptied!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'ordered'   => self::counted( _x( '%s items(s) re-ordered!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN ) ),
			'huh'       => gEditorialHTML::error( self::huh( self::req( 'huh', NULL ) ) ),
		);
	}

	public static function getButtonConfirm( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Are you sure? This operation can not be undone.', 'Settings: Confirm', GEDITORIAL_TEXTDOMAIN );

		return array(
			'onclick' => sprintf( 'return confirm(\'%s\')', esc_attr( $message ) ),
		);
	}

	public static function counted( $message = NULL, $count = NULL, $class = 'notice-success' )
	{
		if ( is_null( $message ) )
			$message = _x( '%s Counted!', 'Settings: Message', GEDITORIAL_TEXTDOMAIN );

		if ( is_null( $count ) )
			$count = self::req( 'count', 0 );

		return gEditorialHTML::notice( sprintf( $message, gEditorialNumber::format( $count ) ), $class.' fade', FALSE );
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

	// @SOURCE: `add_settings_section()`
	public static function addModuleSection( $page, $atts = array() )
	{
		global $wp_settings_sections;

		$args = self::atts( array(
			'id'            => FALSE,
			'title'         => FALSE,
			'callback'      => '__return_false',
			'section_class' => '',
		), $atts );

		if ( ! $args['id'] )
			return FALSE;

		return $wp_settings_sections[$page][$args['id']] = $args;
	}

	// @SOURCE: `do_settings_sections()`
	public static function moduleSections( $page )
	{
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_sections[$page] ) )
			return;

		foreach ( (array) $wp_settings_sections[$page] as $section ) {

			echo '<div class="-section-wrap '.$section['section_class'].'">';

				if ( $section['title'] )
					gEditorialHTML::h2( $section['title'], '-section-title' );

				if ( $section['callback'] )
					call_user_func( $section['callback'], $section );

				if ( ! isset( $wp_settings_fields )
					|| ! isset( $wp_settings_fields[$page] )
					|| ! isset( $wp_settings_fields[$page][$section['id']] ) ) {

					echo '</div>';
					continue;
				}

				echo '<table class="form-table -section-table">';
					do_settings_fields( $page, $section['id'] );
				echo '</table>';

			echo '</div>';
		}
	}

	public static function moduleSectionEmpty( $description )
	{
		echo gEditorialHTML::tag( 'p', array(
			'class' => 'description -section-description -section-empty',
		), $description );
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
					'title'   => sprintf( _x( '%s Overview', 'Settings: Help Content Title', GEDITORIAL_TEXTDOMAIN ), $module->title ),
					'content' => gnetwork_github( array(
						'repo'    => 'geminorum/geditorial',
						'type'    => 'wiki',
						'page'    => 'Modules-'.gEditorialHelper::moduleSlug( $module->name ),
						'context' => 'help_tab',
					) ),
				);

			else
				$tabs[] = array(
					'id'      => 'geditorial-overview',
					'title'   => _x( 'Editorial Overview', 'Settings: Help Content Title', GEDITORIAL_TEXTDOMAIN ),
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

	public static function fieldType( $atts = array(), &$scripts )
	{
		$args = self::atts( array(
			'title'        => '',
			'label_for'    => '',
			'type'         => 'enabled',
			'field'        => FALSE,
			'values'       => array(),
			'exclude'      => '',
			'none_title'   => NULL, // select option none title
			'none_value'   => NULL, // select option none value
			'filter'       => FALSE, // will use via sanitize
			'callback'     => FALSE, // callable for `callback` type
			'dir'          => FALSE,
			'disabled'     => FALSE,
			'readonly'     => FALSE,
			'default'      => '',
			'defaults'     => array(), // default value to ignore && override the saved
			'description'  => isset( $atts['desc'] ) ? $atts['desc'] : '',
			'before'       => '', // html to print before field
			'after'        => '', // html to print after field
			'field_class'  => '', // formally just class!
			'class'        => '', // now used on wrapper
			'option_group' => 'settings',
			'option_base'  => 'geditorial',
			'options'      => array(), // saved options
			'id_name_cb'   => FALSE, // id/name generator callback
			'id_attr'      => FALSE, // override
			'name_attr'    => FALSE, // override
			'step_attr'    => '1', // for number type
			'min_attr'     => '0', // for number type
			'rows_attr'    => '5', // for textarea type
			'cols_attr'    => '45', // for textarea type
			'placeholder'  => FALSE,
			'constant'     => FALSE, // override value if constant defined & disabling
			'data'         => array(), // data attr
			'extra'        => array(), // extra args to pass to deeper generator
			'wrap'         => FALSE,
			'cap'          => NULL,

			'string_disabled' => _x( 'Disabled', 'Settings', GEDITORIAL_TEXTDOMAIN ),
			'string_enabled'  => _x( 'Enabled', 'Settings', GEDITORIAL_TEXTDOMAIN ),
			'string_select'   => self::showOptionNone(),
			'string_noaccess' => _x( 'You do not have access to change this option.', 'Settings', GEDITORIAL_TEXTDOMAIN ),
		), $atts );

		if ( $args['wrap'] ) {
			if ( ! empty( $args['label_for'] ) )
				echo '<tr class="'.$args['class'].'"><th scope="row"><label for="'.esc_attr( $args['label_for'] ).'">'.$args['title'].'</label></th><td>';
			else
				echo '<tr class="'.$args['class'].'"><th scope="row">'.$args['title'].'</th><td>';
		}

		if ( ! $args['field'] )
			return;

		$html    = '';
		$value   = $args['default'];
		$exclude = $args['exclude'] && ! is_array( $args['exclude'] ) ? array_filter( explode( ',', $args['exclude'] ) ) : array();

		if ( $args['id_name_cb'] ) {
			list( $id, $name ) = call_user_func( $args['id_name_cb'], $args );
		} else {
			$id   = $args['id_attr'] ? $args['id_attr'] : ( $args['option_base'] ? $args['option_base'].'-' : '' ).$args['option_group'].'-'.esc_attr( $args['field'] );
			$name = $args['name_attr'] ? $args['name_attr'] : ( $args['option_base'] ? $args['option_base'].'_' : '' ).$args['option_group'].'['.esc_attr( $args['field'] ).']';
		}

		if ( isset( $args['options'][$args['field']] ) ) {
			$value = $args['options'][$args['field']];

			// override: using settings default instead of module's option
			if ( isset( $args['defaults'][$args['field']] )
				&& $value === $args['defaults'][$args['field']] )
					$value = $args['default'];
		}

		if ( $args['constant'] && defined( $args['constant'] ) ) {
			$value = constant( $args['constant'] );

			$args['disabled'] = TRUE;
			$args['after'] = '<code>'.$args['constant'].'</code>';
		}

		if ( is_null( $args['cap'] ) ) {

			if ( in_array( $args['type'], array( 'role', 'cap', 'user' ) ) )
				$args['cap'] = 'promote_users';
			else
				$args['cap'] = 'manage_options';
		}

		if ( ! current_user_can( $args['cap'] ) )
			$args['type'] = 'noaccess';

		if ( $args['before'] )
			echo $args['before'].'&nbsp;';

		switch ( $args['type'] ) {

			case 'hidden' :

				echo gEditorialHTML::tag( 'input', array(
					'type'  => 'hidden',
					'id'    => $id,
					'name'  => $name,
					'value' => $value,
					'data'  => $args['data'],
				) );

				$args['description'] = FALSE;

			break;
			case 'enabled' :

				$html = gEditorialHTML::tag( 'option', array(
					'value'    => '0',
					'selected' => '0' == $value,
				), esc_html( empty( $args['values'][0] ) ? $args['string_disabled'] : $args['values'][0] ) );

				$html .= gEditorialHTML::tag( 'option', array(
					'value'    => '1',
					'selected' => '1' == $value,
				), esc_html( empty( $args['values'][1] ) ? $args['string_enabled'] : $args['values'][1] ) );

				echo gEditorialHTML::tag( 'select', array(
					'id'       => $id,
					'name'     => $name,
					'class'    => gEditorialHTML::attrClass( $args['field_class'], '-type-enabled' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				), $html );

			break;
			case 'text' :

				if ( ! $args['field_class'] )
					$args['field_class'] = 'regular-text';

				if ( ! count( $args['dir'] ) )
					$args['data'] = array( 'accept' => 'text' );

				echo gEditorialHTML::tag( 'input', array(
					'type'        => 'text',
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'class'       => $args['field_class'],
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				) );

			break;
			case 'number' :

				if ( ! $args['field_class'] )
					$args['field_class'] = 'small-text';

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				if ( ! count( $args['dir'] ) )
					$args['data'] = array( 'accept' => 'number' );

				echo gEditorialHTML::tag( 'input', array(
					'type'        => 'number',
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'step'        => $args['step_attr'],
					'min'         => $args['min_attr'],
					'class'       => gEditorialHTML::attrClass( $args['field_class'], '-type-number' ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				) );

			break;
			case 'url' :

				if ( ! $args['field_class'] )
					$args['field_class'] = array( 'large-text', 'url-text' );

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				if ( ! count( $args['dir'] ) )
					$args['data'] = array( 'accept' => 'url' );

				echo gEditorialHTML::tag( 'input', array(
					'type'        => 'url',
					'id'          => $id,
					'name'        => $name,
					'value'       => $value,
					'class'       => $args['field_class'],
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				) );

			break;
			case 'checkbox' :

				if ( count( $args['values'] ) ) {

					if ( ! is_null( $args['none_title'] ) ) {

						$html = gEditorialHTML::tag( 'input', array(
							'type'     => 'checkbox',
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'name'     => $name.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'value'    => is_null( $args['none_value'] ) ? '1' : $args['none_value'],
							'checked'  => in_array( $args['none_value'], ( array ) $value ),
							'class'    => gEditorialHTML::attrClass( $args['field_class'], '-type-checkbox', '-option-none' ),
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						) );

						echo '<p>'.gEditorialHTML::tag( 'label', array(
							'for' => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
						), $html.'&nbsp;'.esc_html( $args['none_title'] ) ).'</p>';
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = gEditorialHTML::tag( 'input', array(
							'type'     => 'checkbox',
							'id'       => $id.'-'.$value_name,
							'name'     => $name.'['.$value_name.']',
							'value'    => '1',
							'checked'  => in_array( $value_name, ( array ) $value ),
							'class'    => $args['field_class'],
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						) );

						echo '<p>'.gEditorialHTML::tag( 'label', array(
							'for' => $id.'-'.$value_name,
						), $html.'&nbsp;'.$value_title ).'</p>';
					}

				} else {

					$html = gEditorialHTML::tag( 'input', array(
						'type'     => 'checkbox',
						'id'       => $id,
						'name'     => $name,
						'value'    => '1',
						'checked'  => $value,
						'class'    => $args['field_class'],
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					) );

					echo '<p>'.gEditorialHTML::tag( 'label', array(
						'for' => $id,
					), $html.'&nbsp;'.$args['description'] ).'</p>';

					$args['description'] = FALSE;
				}

			break;
			case 'radio' :

				if ( count( $args['values'] ) ) {

					if ( ! is_null( $args['none_title'] ) ) {

						$html = gEditorialHTML::tag( 'input', array(
							'type'     => 'radio',
							'id'       => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
							'name'     => $name,
							'value'    => is_null( $args['none_value'] ) ? FALSE : $args['none_value'],
							'checked'  => in_array( $args['none_value'], ( array ) $value ),
							'class'    => gEditorialHTML::attrClass( $args['field_class'], '-type-radio', '-option-none' ),
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						) );

						echo '<p>'.gEditorialHTML::tag( 'label', array(
							'for' => $id.( is_null( $args['none_value'] ) ? '' : '-'.$args['none_value'] ),
						), $html.'&nbsp;'.esc_html( $args['none_title'] ) ).'</p>';
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html = gEditorialHTML::tag( 'input', array(
							'type'     => 'radio',
							'id'       => $id.'-'.$value_name,
							'name'     => $name,
							'value'    => $value_name,
							'checked'  => in_array( $value_name, ( array ) $value ),
							'class'    => gEditorialHTML::attrClass( $args['field_class'], '-type-radio' ),
							'disabled' => $args['disabled'],
							'readonly' => $args['readonly'],
							'dir'      => $args['dir'],
						) );

						echo '<p>'.gEditorialHTML::tag( 'label', array(
							'for' => $id.'-'.$value_name,
						), $html.'&nbsp;'.$value_title ).'</p>';
					}
				}

			break;
			case 'select' :

				if ( FALSE !== $args['values'] ) {

					if ( ! is_null( $args['none_title'] ) ) {

						if ( is_null( $args['none_value'] ) )
							$args['none_value'] = '0';

						$html .= gEditorialHTML::tag( 'option', array(
							'value'    => $args['none_value'],
							'selected' => $value == $args['none_value'],
						), esc_html( $args['none_title'] ) );
					}

					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html .= gEditorialHTML::tag( 'option', array(
							'value'    => $value_name,
							'selected' => $value == $value_name,
						), esc_html( $value_title ) );
					}

					echo gEditorialHTML::tag( 'select', array(
						'id'       => $id,
						'name'     => $name,
						'class'    => gEditorialHTML::attrClass( $args['field_class'], '-type-select' ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					), $html );
				}

			break;
			case 'textarea' :
			case 'textarea-quicktags' :

				if ( ! $args['field_class'] )
					$args['field_class'] = 'large-text';

				if ( 'textarea-quicktags' == $args['type'] ) {

					$args['field_class'] = gEditorialHTML::attrClass( $args['field_class'], 'textarea-quicktags', 'code' );

					if ( ! $args['values'] )
						$args['values'] = array(
							'link',
							'em',
							'strong',
						);

					$scripts[] = 'quicktags({id:"'.$id.'",buttons:"'.implode( ',', $args['values'] ).'"});';

					wp_enqueue_script( 'quicktags' );
				}

				echo gEditorialHTML::tag( 'textarea', array(
					'id'          => $id,
					'name'        => $name,
					'rows'        => $args['rows_attr'],
					'cols'        => $args['cols_attr'],
					'class'       => gEditorialHTML::attrClass( $args['field_class'], '-type'.$args['type'] ),
					'placeholder' => $args['placeholder'],
					'disabled'    => $args['disabled'],
					'readonly'    => $args['readonly'],
					'dir'         => $args['dir'],
					'data'        => $args['data'],
				), $value );

			break;
			case 'page' :

				if ( ! $args['values'] )
					$args['values'] = 'page';

				if ( is_null( $args['none_title'] ) )
					$args['none_title'] = $args['string_select'];

				if ( is_null( $args['none_value'] ) )
					$args['none_value'] = '0';

				$query = array_merge( array(
					'post_type'   => $args['values'],
					'selected'    => $value,
					'exclude'     => implode( ',', $exclude ),
					'sort_column' => 'menu_order',
					'sort_order'  => 'asc',
					'post_status' => 'publish,private,draft',
				), $args['extra'] );

				$pages = get_pages( $query );

				if ( ! empty( $pages ) ) {

					$html .= gEditorialHTML::tag( 'option', array(
						'value' => $args['none_value'],
					), esc_html( $args['none_title'] ) );

					$html .= walk_page_dropdown_tree( $pages, ( isset( $query['depth'] ) ? $query['depth'] : 0 ), $query );

					echo gEditorialHTML::tag( 'select', array(
						'id'       => $id,
						'name'     => $name,
						'class'    => gEditorialHTML::attrClass( $args['field_class'], '-type-page', '-posttype-'.$args['values'] ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
						'data'     => $args['data'],
					), $html );

				} else {
					$args['description'] = FALSE;
				}

			break;
			case 'role' :

				if ( ! $args['values'] )
					$args['values'] = array_reverse( get_editable_roles() );

				if ( is_null( $args['none_title'] ) )
					$args['none_title'] = $args['string_select'];

				if ( is_null( $args['none_value'] ) )
					$args['none_value'] = '0';

				$html .= gEditorialHTML::tag( 'option', array(
					'value' => $args['none_value'],
				), esc_html( $args['none_title'] ) );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html .= gEditorialHTML::tag( 'option', array(
						'value'    => $value_name,
						'selected' => $value == $value_name,
					), esc_html( translate_user_role( $value_title['name'] ) ) );
				}

				echo gEditorialHTML::tag( 'select', array(
					'id'       => $id,
					'name'     => $name,
					'class'    => gEditorialHTML::attrClass( $args['field_class'], '-type-role' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				), $html );

			break;
			case 'user' :

				if ( ! $args['values'] )
					$args['values'] = gEditorialWordPress::getUsers( FALSE, FALSE, $args['extra'] );

				if ( ! is_null( $args['none_title'] ) ) {

					$html .= gEditorialHTML::tag( 'option', array(
						'value'    => is_null( $args['none_value'] ) ? FALSE : $args['none_value'],
						'selected' => $value == $args['none_value'],
					), esc_html( $args['none_title'] ) );
				}

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html .= gEditorialHTML::tag( 'option', array(
						'value'    => $value_name,
						'selected' => $value == $value_name,
					), esc_html( sprintf( '%1$s (%2$s)', $value_title->display_name, $value_title->user_login ) ) );
				}

				echo gEditorialHTML::tag( 'select', array(
					'id'       => $id,
					'name'     => $name,
					'class'    => gEditorialHTML::attrClass( $args['field_class'], '-type-user' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				), $html );

			break;
			case 'priority' :

				if ( ! $args['values'] )
					$args['values'] = self::priorityOptions( FALSE );

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html .= gEditorialHTML::tag( 'option', array(
						'value'    => $value_name,
						'selected' => $value == $value_name,
					), esc_html( $value_title ) );
				}

				echo gEditorialHTML::tag( 'select', array(
					'id'       => $id,
					'name'     => $name,
					'class'    => gEditorialHTML::attrClass( $args['field_class'], '-type-priority' ),
					'disabled' => $args['disabled'],
					'readonly' => $args['readonly'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				), $html );

			break;
			case 'button' :

				echo get_submit_button(
					$value,
					( empty( $args['field_class'] ) ? 'secondary' : $args['field_class'] ),
					$args['field'], // $id,
					FALSE,
					$args['values']
				);

			break;
			case 'file' :

				echo gEditorialHTML::tag( 'input', array(
					'type'     => 'file',
					'id'       => $id,
					'name'     => $id,
					'class'    => $args['field_class'],
					'disabled' => $args['disabled'],
					'dir'      => $args['dir'],
					'data'     => $args['data'],
				) );

			break;
			case 'posttypes' :

				if ( ! $args['values'] )
					$args['values'] = gEditorialWPPostType::get( 0,
						array_merge( array( 'public' => TRUE ), $args['extra'] ) );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html = gEditorialHTML::tag( 'input', array(
						'type'     => 'checkbox',
						'id'       => $id.'-'.$value_name,
						'name'     => $name.'['.$value_name.']',
						'value'    => '1',
						'checked'  => in_array( $value_name, ( array ) $value ),
						'class'    => gEditorialHTML::attrClass( $args['field_class'], '-type-posttypes' ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
					) );

					echo '<p>'.gEditorialHTML::tag( 'label', array(
						'for' => $id.'-'.$value_name,
					), $html.'&nbsp;'.esc_html( $value_title ) ).'</p>';
				}

			break;
			case 'taxonomies' :

				if ( ! $args['values'] )
					$args['values'] = gEditorialWPTaxonomy::get( 0, $args['extra'] );

				foreach ( $args['values'] as $value_name => $value_title ) {

					if ( in_array( $value_name, $exclude ) )
						continue;

					$html = gEditorialHTML::tag( 'input', array(
						'type'     => 'checkbox',
						'id'       => $id.'-'.$value_name,
						'name'     => $name.'['.$value_name.']',
						'value'    => '1',
						'checked'  => in_array( $value_name, ( array ) $value ),
						'class'    => gEditorialHTML::attrClass( $args['field_class'], '-type-taxonomies' ),
						'disabled' => $args['disabled'],
						'readonly' => $args['readonly'],
						'dir'      => $args['dir'],
					) );

					echo '<p>'.gEditorialHTML::tag( 'label', array(
						'for' => $id.'-'.$value_name,
					), $html.'&nbsp;'.esc_html( $value_title ) ).'</p>';
				}

			break;
			case 'callback' :

				if ( is_callable( $args['callback'] ) ) {

					call_user_func_array( $args['callback'], array( &$args,
						compact( 'html', 'value', 'name', 'id', 'exclude' ) ) );

				} else if ( gEditorialWordPress::isDev() ) {

					echo 'Error: Setting Is Not Callable!';
				}

			break;
			case 'noaccess' :

				echo gEditorialHTML::tag( 'span', array(
					'class' => '-type-noaccess',
				), $args['string_noaccess'] );

			break;
			case 'custom' :

				if ( ! is_array( $args['values'] ) )
					echo $args['values'];
				else
					echo $value;

			break;
			case 'debug' :

				self::dump( $args['options'] );

			break;
			default :

				echo 'Error: setting type not defind!';
		}

		if ( $args['after'] )
			echo '&nbsp;'.$args['after'];

		if ( $args['description'] && FALSE !== $args['values'] )
			echo gEditorialHTML::tag( 'p', array(
				'class' => 'description',
			), $args['description'] );

		if ( $args['wrap'] )
			echo '</td></tr>';
	}
}
