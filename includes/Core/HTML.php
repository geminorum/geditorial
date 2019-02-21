<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class HTML extends Base
{

	public static function rtl()
	{
		return function_exists( 'is_rtl' ) ? is_rtl() : FALSE;
	}

	public static function link( $html, $link = '#', $target_blank = FALSE )
	{
		return self::tag( 'a', array( 'href' => $link, 'class' => '-link', 'target' => ( $target_blank ? '_blank' : FALSE ) ), $html );
	}

	public static function mailto( $email, $title = NULL, $wrap = FALSE )
	{
		$title = $title ? $title : trim( $email );
		$link  = '<a class="-mailto" href="mailto:'.trim( $email ).'">'.$title.'</a>';
		return $wrap ? self::tag( $wrap, $link ) : $link;
	}

	public static function tel( $number, $title = FALSE, $content = NULL )
	{
		if ( is_null( $content ) )
			$content = Number::format( $number );

		return '<a class="-tel" href="'.self::sanitizePhoneNumber( $number )
				.'"'.( $title ? ' data-toggle="tooltip" title="'.self::escape( $title ).'"' : '' )
				.' data-tel-number="'.self::escape( $number ).'">'
				.self::wrapLTR( $content ).'</a>';
	}

	public static function scroll( $html, $to, $title = '' )
	{
		return '<a class="scroll" title="'.$title.'" href="#'.$to.'">'.$html.'</a>';
	}

	public static function img( $src, $class = '', $alt = '' )
	{
		return '<img src="'.$src.'" class="'.$class.'" alt="'.$alt.'" />';
	}

	public static function h2( $html, $class = FALSE, $link = FALSE )
	{
		echo self::tag( 'h2', array( 'class' => $class ), ( $link ? self::link( $html, $link ) : $html ) );
	}

	public static function h3( $html, $class = FALSE, $link = FALSE )
	{
		echo self::tag( 'h3', array( 'class' => $class ), ( $link ? self::link( $html, $link ) : $html ) );
	}

	public static function desc( $html, $block = TRUE, $class = '', $nl2br = TRUE )
	{
		if ( ! $html ) return;
		if ( $nl2br ) $html = nl2br( trim( $html ) );
		echo $block ? '<p class="description -description '.$class.'">'.$html.'</p>' : '<span class="description -description '.$class.'">'.$html.'</span>';
	}

	public static function label( $input, $for = FALSE, $wrap = 'p' )
	{
		$html = self::tag( 'label', [ 'for' => $for ], $input );
		echo $wrap ? self::tag( $wrap, $html ) : $html;
	}

	public static function button( $html, $link = '#', $title = FALSE, $icon = FALSE, $data = array() )
	{
		$classes = array(
			'btn',
			'btn-default',
			'btn-xs',
			'button',
			'-button',
		);

		if ( $icon )
			$classes[] = '-button-icon';

		return self::tag( ( $link ? 'a' : 'span' ), array(
			'href'   => $link ? $link : FALSE,
			'title'  => $title,
			'class'  => $classes,
			'data'   => $data,
			'target' => '_blank',
		), $html );
	}

	public static function wrap( $html, $class = '', $block = TRUE )
	{
		return $block ? '<div class="-wrap '.$class.'">'.$html.'</div>' : '<span class="-wrap '.$class.'">'.$html.'</span>';
	}

	public static function wrapLTR( $content )
	{
		return '&#8206;'.$content.'&#8207;';
	}

	public static function preCode( $content, $rows = 1 )
	{
		echo '<textarea dir="ltr" class="textarea-autosize" rows="'.$rows.'" style="width:100%;text-align:left;direction:ltr;" readonly>';
			echo self::escapeTextarea( $content );
		echo '</textarea>';
	}

	public static function inputHidden( $name, $value = '' )
	{
		echo '<input type="hidden" name="'.self::escape( $name ).'" value="'.self::escape( $value ).'" />';
	}

	// @REF: https://gist.github.com/eric1234/5802030
	// useful when you want to pass on a complex data structure via a form
	public static function inputHiddenArray( $array, $prefix = '' )
	{
		if ( (bool) count( array_filter( array_keys( $array ), 'is_string' ) ) ) {

			foreach ( $array as $key => $value ) {
				$name = empty( $prefix ) ? $key : $prefix.'['.$key.']';

				if ( is_array( $value ) )
					self::inputHiddenArray( $value, $name );
				else
					self::inputHidden( $name, $value );
			}

		} else {

			foreach ( $array as $item ) {
				if ( is_array( $item ) )
					self::inputHiddenArray( $item, $prefix.'[]' );
				else
					self::inputHidden( $prefix.'[]', $item );
			}
		}
	}

	public static function joined( $items, $before = '', $after = '', $sep = '|', $empty = '' )
	{
		return count( $items ) ? ( $before.join( $sep, $items ).$after ) : $empty;
	}

	public static function tag( $tag, $atts = array(), $content = FALSE, $sep = '' )
	{
		if ( FALSE === $tag && ! is_array( $atts ) )
			return $atts;

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
				$classes = array_merge( $classes, preg_split( '#\s+#', $arg ) );

		return array_unique( array_filter( $classes, 'trim' ) );
	}

	public static function prepClass( $classes )
	{
		if ( TRUE === $classes )
			return '';

		return implode( ' ', array_unique( array_filter( self::attrClass( $classes ), array( __CLASS__, 'sanitizeClass' ) ) ) );
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
							$html.= ' data-'.$data_key.'=\''.wp_json_encode( $data_val ).'\'';

						else if ( FALSE === $data_val )
							continue;

						else
							$html.= ' data-'.$data_key.'="'.self::escape( $data_val ).'"';
					}

					continue;

				} else if ( 'class' == $key ) {
					$att = self::prepClass( $att );

				} else {
					$att = implode( ' ', array_unique( array_filter( $att, 'trim' ) ) );
				}

				$sanitized = TRUE;
			}

			if ( in_array( $key, array( 'selected', 'checked', 'readonly', 'disabled', 'default', 'required' ) ) )
				$att = $att ? $key : FALSE;

			if ( FALSE === $att )
				continue;

			if ( 'class' == $key && ! $sanitized )
				$att = self::prepClass( $att );

			else if ( 'class' == $key || 'title' == $key )
				$att = $att;

			else if ( 'href' == $key && '#' != $att )
				$att = self::escapeURL( $att );

			else if ( 'src' == $key && FALSE === strpos( $att, 'data:image' ) )
				$att = self::escapeURL( $att );

			else
				$att = self::escape( $att );

			$html.= ' '.$key.'="'.trim( $att ).'"';
		}

		if ( FALSE === $content )
			return $html.' />';

		return $html.'>';
	}

	// @ref: `esc_html()`, `esc_attr()`
	public static function escape( $text )
	{
		return Text::utf8Compliant( $text )
			? Text::utf8SpecialChars( $text, ENT_QUOTES )
			: '';
	}

	// FIXME: DEPRECATED
	public static function escapeAttr( $text )
	{
		return self::escape( $text );
	}

	public static function escapeURL( $url )
	{
		return esc_url( $url );
	}

	public static function escapeTextarea( $html )
	{
		return Text::utf8SpecialChars( $html, ENT_QUOTES );
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

	// FIXME: DEPRECATED
	public static function getAtts( $string, $expecting = array() )
	{
		self::_dev_dep( 'HTML::parseAtts()' );

		return self::parseAtts( $string, $expecting );
	}

	public static function parseAtts( $string, $expecting = array() )
	{
		foreach ( $expecting as $attr => $default ) {

			preg_match( "#".$attr."=\"(.*?)\"#s", $string, $matches );

			if ( isset( $matches[1] ) )
				$expecting[$attr] = trim( $matches[1] );
		}

		return $expecting;
	}

	public static function linkStyleSheet( $url, $version = NULL, $media = 'all', $echo = TRUE )
	{
		if ( is_array( $version ) )
			$url = add_query_arg( $version, $url );

		else if ( $version )
			$url = add_query_arg( 'ver', $version, $url );

		$html = self::tag( 'link', array(
			'rel'   => 'stylesheet',
			'href'  => $url,
			'type'  => 'text/css',
			'media' => $media,
		) )."\n";

		if ( ! $echo )
			return $html;

		echo $html;
	}

	public static function headerNav( $uri = '', $active = '', $subs = array(), $prefix = 'nav-tab', $wrap = 'h3', $item = FALSE )
	{
		if ( empty( $subs ) )
			return '';

		$html = '';

		foreach ( $subs as $slug => $page ) {

			if ( is_array( $page ) ) {
				$title = empty( $page['title'] ) ? $slug : $page['title'];
				$args  = empty( $page['args'] ) ? array( 'sub' => $slug ) : $page['args'];
			} else {
				$title = $page;
				$args  = array( 'sub' => $slug );
			}

			$url   = add_query_arg( $args, $uri );
			$class = $prefix.' '.$prefix.'-'.$slug.( $slug == $active ? ' '.$prefix.'-active -active' : '' );

			if ( $item )
				$html.= self::tag( $item, array( 'class' => $class ), self::link( $title, $url ) );
			else
				$html.= self::tag( 'a', array( 'class' => $class, 'href' => $url ), $title );
		}

		if ( $wrap )
			echo self::tag( $wrap, array(
				'class' => $prefix.'-wrapper',
			), $html );

		else
			echo $html;
	}

	public static function tabNav( $active = '', $subs = array(), $prefix = 'nav-tab-', $tag = 'div' )
	{
		if ( empty( $subs ) )
			return;

		$html = '';

		foreach ( $subs as $slug => $page )
			$html.= self::tag( 'a', array(
				'class' => 'nav-tab '.$prefix.$slug.( $slug == $active ? ' nav-tab-active' : '' ),
				'href'  => '#'.$slug,
			), $page );

		echo self::tag( $tag, array(
			'class' => 'nav-tab-wrapper',
		), $html );
	}

	// @REF: https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices
	// CLASSES: notice-error, notice-warning, notice-success, notice-info, is-dismissible
	public static function notice( $notice, $class = 'notice-success fade inline', $dismissible = TRUE )
	{
		return sprintf( '<div class="notice %s%s -notice">%s</div>', $class, ( $dismissible ? ' is-dismissible' : '' ), Text::autoP( $notice ) );
	}

	public static function error( $notice, $dismissible = TRUE )
	{
		return self::notice( $notice, 'notice-error fade inline', $dismissible );
	}

	public static function success( $notice, $dismissible = TRUE )
	{
		return self::notice( $notice, 'notice-success fade inline', $dismissible );
	}

	public static function warning( $notice, $dismissible = TRUE )
	{
		return self::notice( $notice, 'notice-warning fade inline', $dismissible );
	}

	public static function info( $notice, $dismissible = TRUE )
	{
		return self::notice( $notice, 'notice-info fade inline', $dismissible );
	}

	public static function tableList( $columns, $data = array(), $atts = array() )
	{
		if ( empty( $columns ) )
			return FALSE;

		$args = self::atts( array(
			'empty'      => NULL,
			'title'      => NULL,
			'before'     => FALSE,
			'after'      => FALSE,
			'check'      => FALSE, // call back to check each row
			'callback'   => FALSE, // for all cells
			'sanitize'   => TRUE, // using sanitizeDisplay()
			'search'     => FALSE, // 'before', // 'after', // FIXME: add search box
			'navigation' => FALSE, // 'before', // 'after',
			'pagination' => array(),
			'map'        => array(),
			'extra'      => array(), // just passing around!
		), $atts );

		if ( empty( $data ) ) {
			self::desc( $args['empty'], TRUE, 'base-table-empty' );
			return FALSE;
		}

		echo '<div class="base-table-wrap">';

		if ( $args['title'] )
			echo '<div class="base-table-title">'.$args['title'].'</div>';

		if ( $args['before'] || 'before' == $args['navigation'] || 'before' == $args['search'] )
			echo '<div class="base-table-actions base-table-list-before">';
		else
			echo '<div>';

		if ( 'before' == $args['navigation'] )
			self::tableNavigation( $args['pagination'] );

		if ( $args['before'] && is_callable( $args['before'] ) )
			call_user_func_array( $args['before'], array( $columns, $data, $args ) );

		echo '</div><table class="widefat fixed base-table-list"><thead><tr>';
			foreach ( $columns as $key => $column ) {

				$tag   = 'th';
				$class = '';

				if ( is_array( $column ) ) {
					$title = isset( $column['title'] ) ? $column['title'] : $key;

					if ( isset( $column['class'] ) )
						$class = ' '.self::prepClass( $column['class'] );

				} else if ( '_cb' === $key ) {
					$title = '<input type="checkbox" id="cb-select-all-1" class="-cb-all" />';
					$class = ' check-column';
					$tag   = 'td';
				} else {
					$title = $column;
				}

				echo '<'.$tag.' class="-column -column-'.self::sanitizeClass( $key ).$class.'">'.$title.'</'.$tag.'>';
			}
		echo '</tr></thead><tbody class="-list">';

		$alt = TRUE;
		foreach ( $data as $index => $row ) {

			if ( $args['check'] && ! (bool) call_user_func_array( $args['check'], array( $row, $index, $args ) ) )
				continue;

			echo '<tr class="-row -row-'.$index.( $alt ? ' alternate' : '' ).'">';

			foreach ( $columns as $offset => $column ) {

				$cell  = 'td';
				$class = $callback = $actions = '';
				$key   = $offset;
				$value = NULL;

				// override key using map
				if ( array_key_exists( $offset, $args['map'] ) )
					$key = $args['map'][$offset];

				if ( is_array( $column ) ) {

					if ( isset( $column['class'] ) )
						$class.= ' '.self::prepClass( $column['class'] );

					if ( isset( $column['callback'] ) )
						$callback = $column['callback'];

					if ( isset( $column['actions'] ) ) {
						$actions = $column['actions'];
						$class.= ' has-row-actions';
					}

					// again override key using map
					if ( isset( $column['map'] ) )
						$key = $column['map'];
				}

				if ( '_cb' === $key ) {

					if ( '_index' == $column )
						$value = $index;

					else if ( is_array( $column ) && isset( $column['value'] ) )
						$value = call_user_func_array( $column['value'], array( NULL, $row, $column, $index, $key, $args ) );

					else if ( is_array( $row ) && array_key_exists( $column, $row ) )
						$value = $row[$column];

					else if ( is_object( $row ) && property_exists( $row, $column ) )
						$value = $row->{$column};

					else
						$value = '';

					$cell = 'th';
					$class.= ' check-column';
					$value = '<input type="checkbox" name="_cb[]" value="'.self::escape( $value ).'" class="-cb" />';

				} else if ( is_array( $row ) ) {

					if ( array_key_exists( $key, $row ) )
						$value = $row[$key];

				} else if ( is_object( $row ) ) {

					if ( property_exists( $row, $key ) )
						$value = $row->{$key};
				}

				echo '<'.$cell.' class="-cell -cell-'.self::sanitizeClass( $key ).$class.'">';

				if ( $callback )
					echo call_user_func_array( $callback,
						array( $value, $row, $column, $index, $key, $args ) );

				else if ( $args['callback'] && '_cb' !== $key )
					echo call_user_func_array( $args['callback'],
						array( $value, $row, $column, $index, $key, $args ) );

				else if ( $args['sanitize'] && '_cb' !== $key )
					echo self::sanitizeDisplay( $value );

				else if ( $value )
					echo $value;

				else
					echo '&nbsp;';

				if ( $actions )
					self::tableActions( call_user_func_array( $actions,
						array( $value, $row, $column, $index, $key, $args ) ) );

				echo '</'.$cell.'>';
			}

			$alt = ! $alt;

			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '<div class="clear"></div>';

		if ( $args['after'] || 'after' == $args['navigation'] || 'after' == $args['search'] )
			echo '<div class="base-table-actions base-table-list-after">';
		else
			echo '<div>';

		if ( 'after' == $args['navigation'] )
			self::tableNavigation( $args['pagination'] );

		if ( $args['after'] && is_callable( $args['after'] ) )
			call_user_func_array( $args['after'], array( $columns, $data, $args ) );

		echo '</div></div>';

		return TRUE;
	}

	public static function tableActions( $actions, $echo = TRUE )
	{
		if ( ! $actions || ! is_array( $actions ) )
			return;

		$count = count( $actions );

		$i = 0;

		$html = '<div class="base-table-actions row-actions">';

			foreach ( $actions as $name => $action ) {
				++$i;
				$sep = $i == $count ? '' : ' | ';
				$html.= '<span class="-action-'.$name.' '.$name.'">'.$action.$sep.'</span>';
			}

		$html.= '</div>';

		if ( ! $echo )
			return $html;

		echo $html;
	}

	public static function tableNavigation( $pagination = array() )
	{
		$args = self::atts( array(
			'actions'  => array(),
			'before'   => array(),
			'after'    => array(),
			'total'    => 0,
			'pages'    => 0,
			'limit'    => self::limit(),
			'paged'    => self::paged(),
			'order'    => self::order( 'ASC' ),
			'extra'    => array(),
			'all'      => FALSE,
			'next'     => FALSE,
			'previous' => FALSE,
			'rtl'      => self::rtl(),
		), $pagination );

		$icons = array(
			'action'   => self::getDashicon( 'update' ),
			'filter'   => self::getDashicon( 'filter' ),
			'last'     => self::getDashicon( $args['rtl'] ? 'controls-skipback' : 'controls-skipforward' ),
			'first'    => self::getDashicon( $args['rtl'] ? 'controls-skipforward' : 'controls-skipback' ),
			'next'     => self::getDashicon( $args['rtl'] ? 'controls-back' : 'controls-forward' ), // &rsaquo;
			'previous' => self::getDashicon( $args['rtl'] ? 'controls-forward' : 'controls-back' ), // &lsaquo;
			'refresh'  => self::getDashicon( 'controls-repeat' ),
			'order'    => self::getDashicon( 'sort' ),
		);

		echo '<div class="base-table-navigation">';

			if ( count( $args['actions'] ) ) {
				echo '<span class="-before">';
				echo self::dropdown( $args['actions'], array(
					'name'       => 'table_action',
					'selected'   => self::req( 'table_action', 'none' ),
					'none_value' => 'none',
					'none_title' => '&mdash;',
				) );
				echo '</span>&nbsp;';
				echo '<button type="submit" class="button -action" />'.$icons['action'].'</button>&nbsp;&nbsp;';
			}

			foreach ( (array) $args['before'] as $before )
				echo '<span class="-before">'.$before.'</span>&nbsp;';

			echo '<input type="number" class="small-text -paged" name="paged" value="'.$args['paged'].'" />&nbsp;';
			echo '<input type="number" class="small-text -limit" name="limit" value="'.$args['limit'].'" />&nbsp;';
			echo '<button type="submit" name="filter_action" class="button -filter" />'.$icons['filter'].'</button>&nbsp;';

			echo self::tag( 'a', array(
				'href' => add_query_arg( array_merge( $args['extra'], array(
					'order' => ( 'ASC' === $args['order'] ) ? 'desc' : 'asc',
					'limit' => $args['limit'],
				) ) ),
				'class' => '-order -link button',
			), $icons['order'] );

			foreach ( (array) $args['after'] as $after )
				echo '&nbsp;<span class="-after">'.$after.'</span>';

			echo '<div class="-controls">';

			echo '&nbsp;';
			echo '&nbsp;';

			vprintf( '<span class="-total-pages">%s / %s</span>', array(
				Number::format( $args['total'] ),
				Number::format( $args['pages'] ),
			) );

			echo '&nbsp;';
			echo '&nbsp;';

			if ( FALSE === $args['previous'] ) {
				echo '<span class="-first -span button" disabled="disabled">'.$icons['first'].'</span>';
				echo '&nbsp;';
				echo '<span class="-previous -span button" disabled="disabled">'.$icons['previous'].'</span>';
			} else {
				echo self::tag( 'a', array(
					'href'  => add_query_arg( array(
						'paged' => FALSE,
						'limit' => $args['limit'],
					) ),
					'class' => '-first -link button',
				), $icons['first'] );
				echo '&nbsp;';
				echo self::tag( 'a', array(
					'href'  => add_query_arg( array_merge( $args['extra'], array(
						'paged' => $args['previous'],
						'limit' => $args['limit'],
					) ) ),
					'class' => '-previous -link button',
				), $icons['previous'] );
			}

			echo '&nbsp;';
			echo self::tag( 'a', array(
				'href'  => add_query_arg( array_merge( $args['extra'], array(
					'paged' => $args['paged'],
					'limit' => $args['limit'],
				) ) ),
				'class' => '-refresh -link button',
			), $icons['refresh'] );
			echo '&nbsp;';

			if ( FALSE === $args['next'] ) {
				echo '<span class="-last -span button" disabled="disabled">'.$icons['last'].'</span>';
				echo '&nbsp;';
				echo '<span class="-next -span button" disabled="disabled">'.$icons['next'].'</span>';
			} else {
				echo self::tag( 'a', array(
					'href'  => add_query_arg( array_merge( $args['extra'], array(
						'paged' => $args['next'],
						'limit' => $args['limit'],
					) ) ),
					'class' => '-next -link button',
				), $icons['next'] );
				echo '&nbsp;';
				echo self::tag( 'a', array(
					'href'  => add_query_arg( array_merge( $args['extra'], array(
						'paged' => $args['pages'],
						'limit' => $args['limit'],
					) ) ),
					'class' => '-last -link button',
				), $icons['last'] );
			}

			echo '</div>';
		echo '</div>';
	}

	public static function tablePagination( $found, $max, $limit, $paged, $extra = array(), $all = FALSE )
	{
		$pagination = array(
			'total'    => intval( $found ),
			'pages'    => intval( $max ),
			'limit'    => intval( $limit ),
			'paged'    => intval( $paged ),
			'extra'    => $extra, // extra args to add to the links
			'all'      => $all, // WTF?! (probably display all!)
			'next'     => FALSE,
			'previous' => FALSE,
		);

		if ( $pagination['pages'] > 1 ) {
			if ( $pagination['paged'] != 1 )
				$pagination['previous'] = $pagination['paged'] - 1;

			if ( $pagination['paged'] != $pagination['pages'] )
				$pagination['next'] = $pagination['paged'] + 1;
		}

		return $pagination;
	}

	public static function tableSide( $array, $type = TRUE )
	{
		echo '<table class="base-table-side">';

		if ( ! empty( $array ) ) {

			foreach ( $array as $key => $val ) {

				$val = maybe_unserialize( $val );

				echo '<tr class="-row">';

				if ( is_string( $key ) ) {
					echo '<td class="-key">';
						echo '<strong>'.$key.'</strong>';
						if ( $type ) echo '<br /><small>'.gettype( $val ).'</small>';
					echo '</td>';
				}

				if ( is_array( $val ) || is_object( $val ) ) {

					echo '<td class="-val -table">';
					self::tableSide( $val, $type );

				} else if ( is_null( $val ) ) {

					echo '<td class="-val -not-table"><code>NULL</code>';

				} else if ( is_bool( $val ) ) {

					echo '<td class="-val -not-table"><code>'.( $val ? 'TRUE' : 'FALSE' ).'</code>';

				} else if ( ! empty( $val ) ) {

					echo '<td class="-val -not-table"><code>'.$val.'</code>';

				} else {

					echo '<td class="-val -not-table"><small class="-empty">EMPTY</small>';
				}

				echo '</td></tr>';
			}

		} else {
			echo '<tr class="-row"><td class="-val -not-table"><small class="-empty">EMPTY</small></td></tr>';
		}

		echo '</table>';
	}

	public static function tableCode( $array, $reverse = FALSE, $caption = FALSE )
	{
		if ( ! $array )
			return '';

		if ( $reverse )
			$row = '<tr><td class="-val"><code>%1$s</code></td><td class="-var" valign="top">%2$s</td></tr>';
		else
			$row = '<tr><td class="-var" valign="top">%1$s</td><td class="-val"><code>%2$s</code></td></tr>';

		$html = '<table class="base-table-code'.( $reverse ? ' -reverse' : '' ).'">';

		if ( $caption )
			$html.= '<caption>'.$caption.'</caption>';

		$html.= '<tbody>';

		foreach ( (array) $array as $key => $value )
			$html.= sprintf( $row, $key, self::sanitizeDisplay( $value ) );

		return $html.'</tbody></table>';
	}

	public static function sanitizeDisplay( $value )
	{
		if ( is_null( $value ) )
			$value = 'NULL';

		else if ( is_bool( $value ) )
			$value = $value ? 'TRUE' : 'FALSE';

		else if ( is_array( $value ) )
			$value = self::joined( $value, '[', ']', ',', 'EMPTY ARRAY' );

		else if ( is_object( $value ) )
			$value = json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		else if ( is_int( $value ) )
			$value = $value;

		else if ( empty( $value ) )
			$value = 'EMPTY';

		else
			$value = nl2br( trim( $value ) );

		return $value;
	}

	public static function menu( $menu, $callback = FALSE, $list = 'ul', $children = 'children' )
	{
		if ( ! $menu )
			return;

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

	public static function wrapScript( $code, $echo = TRUE )
	{
		if ( ! $code )
			return '';

		$script = '<script type="text/javascript">'."\n".'/* <![CDATA[ */';
		$script.= "\n".$code."\n";
		$script.= '/* ]]> */'."\n".'</script>'."\n";

		if ( ! $echo )
			return $script;

		echo $script;
	}

	// @REF: https://jquery.com/upgrade-guide/3.0/#deprecated-document-ready-handlers-other-than-jquery-function
	public static function wrapjQueryReady( $code, $echo = TRUE )
	{
		if ( ! $code )
			return '';

		$script = '<script type="text/javascript">'."\n".'/* <![CDATA[ */'."\n";
		$script.= 'jQuery(function($){'."\n".$code.'});'."\n";
		$script.= '/* ]]> */'."\n".'</script>'."\n";

		if ( ! $echo )
			return $script;

		echo $script;
	}

	// @REF: https://developer.wordpress.org/resource/dashicons/
	public static function getDashicon( $icon = 'wordpress-alt', $title = FALSE, $class = '' )
	{
		return self::tag( 'span', array(
			'data-icon' => 'dashicons',
			'title'     => $title,
			'class'     => self::attrClass( [ 'dashicons', 'dashicons-'.$icon ], $class ),
		), NULL );
	}

	public static function dropdown( $list, $atts = array() )
	{
		$args = self::atts( array(
			'id'         => '',
			'name'       => '',
			'none_title' => NULL,
			'none_value' => 0,
			'class'      => FALSE,
			'selected'   => 0,
			'disabled'   => FALSE,
			'dir'        => FALSE,
			'prop'       => FALSE,
			'value'      => FALSE,
			'exclude'    => array(),
		), $atts );

		$html = '';

		if ( FALSE === $list ) // alow hiding
			return $html;

		if ( ! is_null( $args['none_title'] ) )
			$html.= self::tag( 'option', array(
				'value'    => $args['none_value'],
				'selected' => $args['selected'] == $args['none_value'],
			), $args['none_title'] );

		foreach ( $list as $offset => $value ) {

			if ( $args['value'] )
				$key = is_object( $value ) ? $value->{$args['value']} : $value[$args['value']];

			else
				$key = $offset;

			if ( in_array( $key, (array) $args['exclude'] ) )
				continue;

			if ( $args['prop'] )
				$title = is_object( $value ) ? $value->{$args['prop']} : $value[$args['prop']];

			else
				$title = $value;

			$html.= self::tag( 'option', array(
				'value'    => $key,
				'selected' => $args['selected'] == $key,
			), $title );
		}

		return self::tag( 'select', array(
			'name'     => $args['name'],
			'id'       => $args['id'],
			'class'    => $args['class'],
			'disabled' => $args['disabled'],
			'dir'      => $args['dir'],
		), $html );
	}

	public static function renderList( $items, $keys = FALSE, $list = 'ul' )
	{
		return self::tag( $list, '<li>'.implode( '</li><li>', $keys ? array_keys( $items ) : $items ).'</li>' );
	}
}
