<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialHTML extends gEditorialBaseCore
{

	public static function link( $html, $link = '#', $target_blank = FALSE )
	{
		return self::tag( 'a', array( 'href' => $link, 'target' => ( $target_blank ? '_blank' : FALSE ) ), $html );
	}

	public static function inputHidden( $name, $value = '' )
	{
		echo '<input type="hidden" name="'.self::escapeAttr( $name ).'" value="'.self::escapeAttr( $value ).'" />';
	}

	public static function joined( $items, $before = '', $after = '', $sep = '|' )
	{
		return count( $items ) ? ( $before.join( $sep, $items ).$after ) : '';
	}

	public static function tag( $tag, $atts = array(), $content = FALSE, $sep = '' )
	{
		$tag = self::sanitizeTag( $tag );

		if ( is_array( $atts ) )
			$html = self::_tag_open( $tag, $atts, $content );
		else
			return '<'.$tag.'>'.$atts.'</'.$tag.'>'.$sep;

		if ( FALSE === $content )
			return $html.$sep;

		if ( is_null( $content ) )
			return $html.'</'.$tag.'>'.$sep;

		return $html.$content.'</'.$tag.'>'.$sep;
	}

	public static function attrClass()
	{
		$classes = array();

		foreach ( func_get_args() as $arg )

			if ( is_array( $arg ) )
				$classes = array_merge( $classes, $arg );

			else if ( $arg )
				$classes = array_merge( $classes, explode( ' ', $arg ) );

		return array_unique( array_filter( $classes, 'trim' ) );
	}

	private static function _tag_open( $tag, $atts, $content = TRUE )
	{
		$html = '<'.$tag;

		foreach ( $atts as $key => $att ) {

			$sanitized = FALSE;

			if ( is_array( $att ) ) {

				if ( ! count( $att ) )
					continue;

				if ( 'data' == $key ) {

					foreach ( $att as $data_key => $data_val ) {

						if ( is_array( $data_val ) )
							$html .= ' data-'.$data_key.'=\''.wp_json_encode( $data_val ).'\'';

						else if ( FALSE === $data_val )
							continue;

						else
							$html .= ' data-'.$data_key.'="'.self::escapeAttr( $data_val ).'"';
					}

					continue;

				} else if ( 'class' == $key ) {
					$att = implode( ' ', array_unique( array_filter( $att, array( __CLASS__, 'sanitizeClass' ) ) ) );

				} else {
					$att = implode( ' ', array_unique( array_filter( $att, 'trim' ) ) );
				}

				$sanitized = TRUE;
			}

			if ( in_array( $key, array( 'selected', 'checked', 'readonly', 'disabled' ) ) )
				$att = $att ? $key : FALSE;

			if ( FALSE === $att )
				continue;

			if ( 'class' == $key && ! $sanitized )
				$att = implode( ' ', array_unique( array_filter( explode( ' ', $att ), array( __CLASS__, 'sanitizeClass' ) ) ) );

			else if ( 'class' == $key )
				$att = $att;

			else if ( 'href' == $key && '#' != $att )
				$att = self::escapeURL( $att );

			else if ( 'src' == $key && FALSE === strpos( $att, 'data:image' ) )
				$att = self::escapeURL( $att );

			else
				$att = self::escapeAttr( $att );

			$html .= ' '.$key.'="'.trim( $att ).'"';
		}

		if ( FALSE === $content )
			return $html.' />';

		return $html.'>';
	}

	// like WP core but without filter
	// @SOURCE: `esc_attr()`
	public static function escapeAttr( $text )
	{
		$safe_text = wp_check_invalid_utf8( $text );
		$safe_text = _wp_specialchars( $safe_text, ENT_QUOTES );

		return $safe_text;
	}

	public static function escapeURL( $url )
	{
		return esc_url( $url );
	}

	// like WP core but without filter and fallback
	// ANCESTOR: sanitize_html_class()
	public static function sanitizeClass( $class )
	{
		// strip out any % encoded octets
		$sanitized = preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', $class );

		// limit to A-Z,a-z,0-9,_,-
		$sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', $sanitized );

		return $sanitized;
	}

	// like WP core but without filter
	// ANCESTOR: tag_escape()
	public static function sanitizeTag( $tag )
	{
		return strtolower( preg_replace('/[^a-zA-Z0-9_:]/', '', $tag ) );
	}

	// @SOURCE: http://www.billerickson.net/code/phone-number-url/
	public static function sanitizePhoneNumber( $number )
	{
		return self::escapeURL( 'tel:'.str_replace( array( '(', ')', '-', '.', '|', ' ' ), '', $number ) );
	}

	public static function getAtts( $string, $expecting = array() )
	{
		foreach ( $expecting as $attr => $default ) {

			preg_match( "#".$attr."=\"(.*?)\"#s", $string, $matches );

			if ( isset( $matches[1] ) )
				$expecting[$attr] = trim( $matches[1] );
		}

		return $expecting;
	}

	public static function linkStyleSheet( $url, $version = NULL, $media = 'all' )
	{
		if ( is_array( $version ) )
			$url = add_query_arg( $version, $url );

		else if ( $version )
			$url = add_query_arg( 'ver', $version, $url );

		echo "\t".self::tag( 'link', array(
			'rel'   => 'stylesheet',
			'href'  => $url,
			'type'  => 'text/css',
			'media' => $media,
		) )."\n";
	}

	public static function headerNav( $uri = '', $active = '', $subs = array(), $prefix = 'nav-tab-', $tag = 'h3' )
	{
		if ( ! count( $subs ) )
			return;

		$html = '';

		foreach ( $subs as $slug => $page )
			$html .= self::tag( 'a', array(
				'class' => 'nav-tab '.$prefix.$slug.( $slug == $active ? ' nav-tab-active' : '' ),
				'href'  => add_query_arg( 'sub', $slug, $uri ),
			), $page );

		echo self::tag( $tag, array(
			'class' => 'nav-tab-wrapper',
		), $html );
	}

	// @REF: https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices
	// CLASSES: notice-error, notice-warning, notice-success, notice-info, is-dismissible
	public static function notice( $notice, $class = 'notice-success fade', $echo = TRUE )
	{
		$html = sprintf( '<div class="notice %s is-dismissible"><p>%s</p></div>', $class, $notice );

		if ( ! $echo )
			return $html;

		echo $html;
	}

	public static function error( $message, $echo = FALSE )
	{
		return self::notice( $message, 'notice-error fade', $echo );
	}

	public static function success( $message, $echo = FALSE )
	{
		return self::notice( $message, 'notice-success fade', $echo );
	}

	public static function warning( $message, $echo = FALSE )
	{
		return self::notice( $message, 'notice-warning fade', $echo );
	}

	public static function info( $message, $echo = FALSE )
	{
		return self::notice( $message, 'notice-info fade', $echo );
	}

	public static function tableList( $columns, $data = array(), $args = array() )
	{
		if ( ! count( $columns ) )
			return FALSE;

		if ( ! $data || ! count( $data ) ) {
			if ( isset( $args['empty'] ) && $args['empty'] )
				echo '<div class="base-table-empty description">'.$args['empty'].'</div>';
			return FALSE;
		}

		echo '<div class="base-table-wrap">';

		if ( isset( $args['title'] ) && $args['title'] )
			echo '<div class="base-table-title">'.$args['title'].'</div>';

		$pagination = isset( $args['pagination'] ) ? $args['pagination'] : array();

		if ( isset( $args['before'] )
			|| ( isset( $args['navigation'] ) && 'before' == $args['navigation'] )
			|| ( isset( $args['search'] ) && 'before' == $args['search'] ) )
				echo '<div class="base-table-actions base-table-list-before">';
		else
			echo '<div>';

		if ( isset( $args['navigation'] ) && 'before' == $args['navigation'] )
			self::tableNavigation( $pagination );

		if ( isset( $args['before'] ) && is_callable( $args['before'] ) )
			call_user_func_array( $args['before'], array( $columns, $data, $args ) );

		echo '</div><table class="widefat fixed base-table-list"><thead><tr>';
			foreach ( $columns as $key => $column ) {

				$tag   = 'th';
				$class = '';

				if ( is_array( $column ) ) {
					$title = isset( $column['title'] ) ? $column['title'] : $key;

					if ( isset( $column['class'] ) )
						$class = self::escapeAttr( $column['class'] );

				} else if ( '_cb' == $key ) {
					$title = '<input type="checkbox" id="cb-select-all-1" class="-cb-all" />';
					$class = ' check-column';
					$tag   = 'td';
				} else {
					$title = $column;
				}

				echo '<'.$tag.' class="-column -column-'.self::escapeAttr( $key ).$class.'">'.$title.'</'.$tag.'>';
			}
		echo '</tr></thead><tbody>';

		$alt = TRUE;
		foreach ( $data as $index => $row ) {

			echo '<tr class="-row -row-'.$index.( $alt ? ' alternate' : '' ).'">';

			foreach ( $columns as $key => $column ) {

				$class = $callback = $actions = '';
				$cell = 'td';

				if ( '_cb' == $key ) {
					if ( '_index' == $column )
						$value = $index;
					else if ( is_array( $column ) && isset( $column['value'] ) )
						$value = call_user_func_array( $column['value'], array( NULL, $row, $column, $index ) );
					else if ( is_array( $row ) && isset( $row[$column] ) )
						$value = $row[$column];
					else if ( is_object( $row ) && isset( $row->{$column} ) )
						$value = $row->{$column};
					else
						$value = '';
					$value = '<input type="checkbox" name="_cb[]" value="'.self::escapeAttr( $value ).'" class="-cb" />';
					$class .= ' check-column';
					$cell = 'th';

				} else if ( is_array( $row ) && isset( $row[$key] ) ) {
					$value = $row[$key];

				} else if ( is_object( $row ) && isset( $row->{$key} ) ) {
					$value = $row->{$key};

				} else {
					$value = NULL;
				}

				if ( is_array( $column ) ) {
					if ( isset( $column['class'] ) )
						$class .= ' '.self::escapeAttr( $column['class'] );

					if ( isset( $column['callback'] ) )
						$callback = $column['callback'];

					if ( isset( $column['actions'] ) ) {
						$actions = $column['actions'];
						$class .= ' has-row-actions';
					}
				}

				echo '<'.$cell.' class="-cell -cell-'.$key.$class.'">';

				if ( $callback ){
					echo call_user_func_array( $callback,
						array( $value, $row, $column, $index ) );

				} else if ( $value ) {
					echo $value;

				} else {
					echo '&nbsp;';
				}

				if ( $actions )
					self::tableActions( call_user_func_array( $actions,
						array( $value, $row, $column, $index ) ) );

				echo '</'.$cell.'>';
			}

			$alt = ! $alt;

			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '<div class="clear"></div>';

		if ( isset( $args['after'] )
			|| ( isset( $args['navigation'] ) && 'after' == $args['navigation'] )
			|| ( isset( $args['search'] ) && 'after' == $args['search'] ) )
				echo '<div class="base-table-actions base-table-list-after">';
		else
			echo '<div>';

		if ( isset( $args['navigation'] ) && 'after' == $args['navigation'] )
			self::tableNavigation( $pagination );

		// FIXME: add search box

		if ( isset( $args['after'] ) && is_callable( $args['after'] ) )
			call_user_func_array( $args['after'], array( $columns, $data, $args ) );

		echo '</div></div>';

		return TRUE;
	}

	public static function tableActions( $actions )
	{
		$count = count( $actions );

		if ( ! $actions )
			return;

		$i = 0;

		echo '<div class="base-table-actions row-actions">';

			foreach ( $actions as $action => $html ) {
				++$i;
				$sep = $i == $count ? '' : ' | ';
				echo '<span class="-action-'.$action.'">'.$html.$sep.'</span>';
			}

		echo '</div>';
	}

	public static function tableNavigation( $pagination = array() )
	{
		$args = self::atts( array(
			'total'    => 0,
			'pages'    => 0,
			'limit'    => self::limit(),
			'paged'    => self::paged(),
			'all'      => FALSE,
			'next'     => FALSE,
			'previous' => FALSE,
		), $pagination );

		$icons = array(
			'next'     => self::getDashicon( 'controls-forward' ), // &rsaquo;
			'previous' => self::getDashicon( 'controls-back' ), // &lsaquo;
			'refresh'  => self::getDashicon( 'controls-repeat' ),
		);

		echo '<div class="base-table-navigation">';

			echo '<input type="number" class="small-text -paged" name="paged" value="'.$args['paged'].'" />';
			echo '<input type="number" class="small-text -limit" name="limit" value="'.$args['limit'].'" />';

			if ( FALSE === $args['previous'] ) {
				$previous = '<span class="-previous -span button" disabled="disabled">'.$icons['previous'].'</span>';
			} else {
				$previous = self::tag( 'a', array(
					'href'  => add_query_arg( 'paged', $args['previous'] ),
					'class' => '-previous -link button',
				), $icons['previous'] );
			}

			if ( FALSE === $args['next'] ) {
				$next = '<span class="-next -span button" disabled="disabled">'.$icons['next'].'</span>';
			} else {
				$next = self::tag( 'a', array(
					'href'  => add_query_arg( 'paged', $args['next'] ),
					'class' => '-next -link button',
				), $icons['next'] );
			}

			$refresh = self::tag( 'a', array(
				'href'  => gEditorialHTTP::currentURL(),
				'class' => '-refresh -link button',
			), $icons['refresh'] );

			$template = is_rtl() ? '<span class="-next-previous">%3$s %1$s %2$s</span>' : '<span class="-next-previous">%2$s %1$s %3$s</span>';

			vprintf( $template, array( $refresh, $previous, $next ) );

			vprintf( '<span class="-total-pages">%s / %s</span>', array(
				gEditorialNumber::format( $args['total'] ),
				gEditorialNumber::format( $args['pages'] ),
			) );

		echo '</div>';
	}

	public static function tableSide( $array, $type = TRUE )
	{
		echo '<table class="base-table-side">';

		if ( count( $array ) ) {

			foreach ( $array as $key => $val ) {

				echo '<tr class="-row">';

				if ( is_string( $key ) ) {
					echo '<td class="-key" style=""><strong>'.$key.'</strong>';
						if ( $type ) echo '<br /><small>'.gettype( $val ).'</small>';
					echo '</td>';
				}

				if ( is_array( $val ) || is_object( $val ) ) {
					echo '<td class="-val -table">';
					self::tableSide( $val, $type );
				} else if ( is_bool( $val ) ){
					echo '<td class="-val -not-table"><code>'.( $val ? 'TRUE' : 'FALSE' ).'</code>';
				} else if ( ! empty( $val ) ){
					echo '<td class="-val -not-table"><code>'.$val.'</code>';
				} else {
					echo '<td class="-val -not-table"><small class="-empty">empty</small>';
				}

				echo '</td></tr>';
			}

		} else {
			echo '<tr class="-row"><td class="-val -not-table"><small class="-empty">empty</small></td></tr>';
		}

		echo '</table>';
	}

	public static function tableCode( $array, $reverse = FALSE, $caption = FALSE )
	{
		if ( ! $array )
			return;

		if ( $reverse )
			$row = '<tr><td class="-val"><code>%1$s</code></td><td class="-var">%2$s</td></tr>';
		else
			$row = '<tr><td class="-var">%1$s</td><td class="-val"><code>%2$s</code></td></tr>';

		echo '<table class="base-table-code'.( $reverse ? ' -reverse' : '' ).'">';

		if ( $caption )
			echo '<caption>'.$caption.'</caption>';

		echo '<tbody>';

		foreach ( (array) $array as $key => $val )
			@printf( $row, $key, ( is_bool( $val ) ? ( $val ? 'TRUE' : 'FALSE' ) : $val ) );

		echo '</tbody></table>';
	}

	public static function menu( $menu, $callback = FALSE, $list = 'ul', $children = 'children' )
	{
		echo '<'.$list.'>';

		foreach ( $menu as $item ) {

			echo '<li>';

			if ( is_callable( $callback ) )
				echo call_user_func_array( $callback, array( $item ) );
			else
				echo self::link( $item['title'], '#'.$item['slug'] );

			if ( ! empty( $item[$children] ) )
				self::menu( $item[$children], $callback, $list, $children );

			echo '</li>';
		}

		echo '</'.$list.'>';
	}

	public static function wrapScript( $script )
	{
		if ( ! $script )
			return;

		echo '<script type="text/javascript">'."\n".'/* <![CDATA[ */'."\n";
			echo $script;
		echo "\n".'/* ]]> */'."\n".'</script>';
	}

	public static function wrapjQueryReady( $script )
	{
		if ( ! $script )
			return;

		echo '<script type="text/javascript">'."\n".'/* <![CDATA[ */'."\n";
			echo 'jQuery(document).ready(function($) {'."\n".$script.'});'."\n";
		echo '/* ]]> */'."\n".'</script>'."\n";
	}

	// @REF: https://developer.wordpress.org/resource/dashicons/
	public static function getDashicon( $icon = 'wordpress-alt', $tag = 'span' )
	{
		return self::tag( $tag, array(
			'class' => array(
				'dashicons',
				'dashicons-'.$icon,
			),
		), NULL );
	}
}
