<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait PostDate
{
	public static $postdate__action_override_dates = 'postdate_do_override_dates';

	protected function postdate__render_before_override_dates( $supported, $metakeys, $uri = '', $sub = NULL, $context = 'tools' )
	{
		if ( self::do( self::$postdate__action_override_dates )
			&& $this->get_setting( 'override_dates', TRUE ) ) {

			if ( $this->postdate__do_override_dates( $supported, $metakeys, $this->get_sub_limit_option( $sub, $context ) ) )
				return FALSE;
		}
	}

	public function postdate__do_override_dates( $supported, $metakeys, $limit = 25 )
	{
		if ( ! $posttype = self::req( 'type' ) )
			return ! gEditorial\Info::renderEmptyPosttype(
				gEditorial\Settings::processingErrorOpen(), '</div></div>' );

		if ( ! in_array( $posttype, (array) $supported, TRUE ) )
			return ! gEditorial\Info::renderNotSupportedPosttype(
				gEditorial\Settings::processingErrorOpen(), '</div></div>' );

		$this->raise_resources( $limit );

		$query = [
			'orderby'     => 'date',
			'order'       => 'DESC',
			'post_status' => 'any',
			'meta_query'  => [
				'relation' => 'OR',
			],
		];

		foreach ( (array) $metakeys as $metakey )
			$query['meta_query'][] = [
				'key'     => $metakey,
				'compare' => 'EXISTS'
			];

		list( $posts, $pagination ) = gEditorial\Tablelist::getPosts( $query, [], $posttype, $limit );

		if ( empty( $posts ) )
			return gEditorial\Settings::processingAllDone();

		echo gEditorial\Settings::processingListOpen();

		foreach ( $posts as $post )
			$this->postdate__post_override_date( $post, $metakeys, TRUE );

		echo '</ul></div>';

		return WordPress\Redirect::doJS( add_query_arg( [
			'action' => self::$postdate__action_override_dates,
			'type'   => $posttype,
			'paged'  => self::paged() + 1,
		] ) );
	}

	public function postdate__post_override_date( $post, $metakeys, $verbose = FALSE )
	{
		if ( TRUE === ( $data = $this->postdate__get_post_data_for_latechores( $post, $metakeys, $verbose ) ) )
			return TRUE;

		if ( empty( $data ) || ! is_array( $data ) )
			return gEditorial\Settings::processingListItem( $verbose, gEditorial\Plugin::wrong( FALSE ) );

		$updated = wp_update_post( array_merge( $data, [ 'ID' => $post->ID ] ) );

		if ( ! $updated || self::isError( $updated ) )
			return gEditorial\Settings::processingListItem( $verbose,
				/* translators: `%1$s`: post date, `%2$s`: post title */
				_x( 'There is problem updating post date (%1$s) for &ldquo;%2$s&rdquo;!', 'Notice', 'geditorial-admin' ), [
					Core\HTML::code( $data['post_date'] ),
					WordPress\Post::title( $post ),
				] );

		$this->actions( 'postdate_after_post_override_date', $updated, $data['post_date'], $metakeys, $verbose );

		return gEditorial\Settings::processingListItem( $verbose,
			/* translators: `%1$s`: post date, `%2$s`: post title */
			_x( '&ldquo;%1$s&rdquo; date is set on &ldquo;%2$s&rdquo;!', 'Notice', 'geditorial-admin' ), [
				Core\HTML::code( gEditorial\Datetime::prepForDisplay( $data['post_date'], NULL, $this->default_calendar() ) ),
				WordPress\Post::title( $post ),
			], TRUE );
	}

	public function postdate__render_card_override_dates( $uri = '', $sub = NULL, $supported_list = NULL, $card_title = NULL )
	{
		echo gEditorial\Settings::toolboxCardOpen( $card_title ?? _x( 'Post-date by Meta-fields', 'Internal: PostDate: Card Title', 'geditorial-admin' ) );

			if ( is_null( $supported_list ) )
				gEditorial\Settings::submitButton( self::$postdate__action_override_dates,
					_x( 'Sync Dates', 'Internal: PostDate: Button', 'geditorial-admin' ), 'small' );

			else if ( is_array( $supported_list ) && Core\Arraay::isAssoc( $supported_list ) )
				foreach ( $supported_list as $posttype => $label )
					gEditorial\Settings::submitButton( add_query_arg( [
						'sub'    => $sub,
						'action' => self::$postdate__action_override_dates,
						'type'   => $posttype,
					] ), sprintf(
						/* translators: `%s`: post-type label */
						_x( 'On %s', 'Button', 'geditorial-admin' ),
						$label
					), 'link-small' );

			else if ( is_array( $supported_list ) )
				foreach ( $supported_list as $posttype )
					gEditorial\Settings::submitButton( add_query_arg( [
						'sub'    => $sub,
						'action' => self::$postdate__action_override_dates,
						'type'   => $posttype,
					] ), sprintf(
						/* translators: `%s`: post-type label */
						_x( 'On %s', 'Button', 'geditorial-admin' ),
						Services\CustomPostType::getLabel( $posttype, 'name' )
					), 'link-small' );

			else if ( is_string( $supported_list ) )
				gEditorial\Settings::submitButton( add_query_arg( [
					'sub'    => $sub,
					'action' => self::$postdate__action_override_dates,
					'type'   => $supported_list,
				], $uri ), _x( 'Set Post-Date', 'Button', 'geditorial-admin' ), 'link-small' );

			Core\HTML::desc( _x( 'Tries to set the post-date form meta field data for all the main posts.', 'Internal: PostDate: Button Description', 'geditorial-admin' ), FALSE );
		echo '</div></div>';

		return TRUE;
	}

	// CAUTION: used in multiple callbacks
	public function postdate__get_post_data_for_latechores( $post, $metakeys, $verbose = FALSE )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return gEditorial\Settings::processingListItem( $verbose, gEditorial\Plugin::wrong( FALSE ) );

		$date = FALSE;

		foreach ( (array) $metakeys as $metakey )
			if ( $metakey && ( $date = get_post_meta( $post->ID, $metakey, TRUE ) ) )
				break;

		if ( ! $date )
			return gEditorial\Settings::processingListItem( $verbose, gEditorial\Plugin::na() );

		$datetime = gEditorial\Datetime::prepForMySQL( $date, NULL, $this->default_calendar() );

		if ( ! $datetime && $verbose )
			return gEditorial\Settings::processingListItem( $verbose,
				/* translators: `%1$s`: date-time, `%2$s`: post title */
				_x( 'Date data (%1$s) is not valid for &ldquo;%2$s&rdquo;.', 'Notice', 'geditorial-admin' ), [
					Core\HTML::code( $date ),
					WordPress\Post::title( $post ),
				] );

		else if ( ! $datetime )
			return $this->log( 'FAILED', sprintf( 'after-care process of #%s: date is not valid: %s', $post->ID, $date ), [ $post->ID, $date ] );

		if ( $datetime === $post->post_date )
			return gEditorial\Settings::processingListItem( $verbose,
				/* translators: `%1$s`: date-time, `%2$s`: post title */
				_x( 'Date data (%1$s) already is set for &ldquo;%2$s&rdquo;!', 'Notice', 'geditorial-admin' ), [
					Core\HTML::code( $datetime ),
					WordPress\Post::title( $post ),
				] );

		return [
			'post_date'     => $datetime,
			'post_date_gmt' => get_gmt_from_date( $datetime ),
		];
	}
}
