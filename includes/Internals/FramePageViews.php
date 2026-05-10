<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait FramePageViews
{
	use Internals\ViewEngines;

	/**
	 * Renders context view for admin page.
	 *
	 * @param string $context
	 * @param string $target
	 * @param string $linked
	 * @return string
	 */
	protected function framepageviews__render_context_content( $context, $target = NULL, $linked = NULL )
	{
		$target = $target ?? 'post';    // the default target
		$linked = $linked ?? 'linked';  // the request key

		if ( 'post' === self::req( 'target', $target ) ) {

			if ( ! $post = WordPress\Post::get( self::req( $linked, FALSE ) ) )
				return gEditorial\Info::renderNoPostsAvailable();

			$this->framepageviews__render_view_for_post( $post, $context );

		} else if ( 'term' === self::req( 'target', $target ) ) {

			if ( ! $term = WordPress\Term::get( self::req( $linked, FALSE ) ) )
				return gEditorial\Info::renderNoTermsAvailable();

			$this->framepageviews__render_view_for_term( $term, $context );

		} else {

			gEditorial\Info::renderNoDataAvailable();
		}

		return $context;
	}

	/**
	 * Renders post view for given context.
	 *
	 * @param object $post
	 * @param string $context
	 * @return array
	 */
	private function framepageviews__render_view_for_post( $post, $context )
	{
		if ( ! $view = $this->viewengine__view_by_post( $post, $context ) )
			return gEditorial\Info::renderSomethingIsWrong();

		$data = $this->framepageviews__get_view_data_for_post( $post, $context );

		if ( ! empty( $data['___flags'] ) && method_exists( $this, 'framepageviews__handle_flags_for_post' ) )
			$this->framepageviews__handle_flags_for_post( (array) $data['___flags'], $post, $context, $data );

		echo $this->wrap_open( '-view -'.$context );
			$this->actions( 'render_view_post_before', $post, $context, $data, $view );
			$this->viewengine__render( $view, $data );
			$this->actions( 'render_view_post_after', $post, $context, $data, $view );
		echo '</div>';

		$data = $this->framepageviews__cleanup_view_data_for_post( $data, $post, $context );

		$this->framepageviews__print_script_for_post( $post, $context, $data );

		echo $this->wrap_open( '-debug -debug-data', TRUE, $this->classs( 'raw' ), TRUE );
			Core\HTML::tableSide( $data );
		echo '</div>';

		return $data;
	}

	private function framepageviews__get_view_data_for_post( $post, $context )
	{
		$data = [];

		if ( $response = Services\RestAPI::getPostResponse( $post, 'view' ) )
			$data = $response;

		// Fallback if `title` is not supported by the post-type
		if ( empty( $data['title'] ) )
			$data['title'] = [ 'rendered' => WordPress\Post::title( $post ) ];

		// strip the generated excerpt
		if ( empty( $data['excerpt']['raw'] ) )
			$data['excerpt']['rendered'] = '';

		$data = WordPress\Strings::stripByProp( $data, 'meta_rendered',  Core\Arraay::prepString( $this->filters( 'post_meta_exclude_rendered',  [], $post, $context ) ), 'name' );
		// $data = WordPress\Strings::stripByProp( $data, 'units_rendered', Core\Arraay::prepString( $this->filters( 'post_units_exclude_rendered', [], $post, $context ) ), 'name' );
		$data = WordPress\Strings::stripByProp( $data, 'terms_rendered', Core\Arraay::prepString( $this->filters( 'post_terms_exclude_rendered', [], $post, $context ) ), 'name' );

		$data = WordPress\Strings::stripEmptyValues( $data, 'meta_rendered',  'rendered' );
		// $data = WordPress\Strings::stripEmptyValues( $data, 'units_rendered', 'rendered' );
		$data = WordPress\Strings::stripEmptyValues( $data, 'terms_rendered', 'rendered' );

		if ( method_exists( $this, 'framepageviews__prep_data_for_post' ) )
			$data = $this->framepageviews__prep_data_for_post( $data, $post, $context );

		$data['__direction']  = Core\HTML::dir();
		$data['__can_edit']   = WordPress\Post::edit( $post );
		$data['__can_debug']  = WordPress\IsIt::dev() || WordPress\User::isSuperAdmin();
		$data['__can_print']  = $this->role_can( 'prints' );
		$data['__can_export'] = $this->role_can( 'reports' );
		$data['__today']      = gEditorial\Datetime::dateFormat( 'now', 'printdate' );
		$data['__summaries']  = $this->filters( 'post_summaries', [], $data, $post, $context );
		$data['___flags']     = $this->filters( 'post_flags', [], $data, $post, $context );

		return $this->filters( 'view_data_for_post', $data, $post, $context );
	}

	private function framepageviews__cleanup_view_data_for_post( $data, $post, $context )
	{
		unset( $data['guid'] );
		unset( $data['class_list'] );
		unset( $data['thumbnail_data'] );
		unset( $data['meta_rendered'] );
		unset( $data['units_rendered'] );
		unset( $data['terms_rendered'] );

		unset( $data['_links'] );

		unset( $data['___flags'] );
		unset( $data['___sides'] );
		unset( $data['___hooks'] );
		unset( $data['__summaries'] );
		unset( $data['__direction'] );
		unset( $data['__can_edit'] );
		unset( $data['__can_debug'] );
		unset( $data['__can_print'] );
		unset( $data['__can_export'] );
		unset( $data['__today'] );

		if ( method_exists( $this, 'framepageviews__cleanup_data_for_post' ) )
			$data = $this->framepageviews__cleanup_data_for_post( $data, $post, $context );

		return $this->filters( 'cleanup_view_data_for_post', $data, $post, $context );
	}

	private function framepageviews__print_script_for_post( $post, $context, $data )
	{
		Core\HTML::wrapScript( sprintf( 'window.%s = %s;',
			$this->hook( 'data' ),
			wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES )
		) );

		$this->enqueue_asset_js( [
			'config' => [
				'printtitle'  => WordPress\Post::title( $post ),
				'printstyles' => gEditorial\Scripts::getPrintStylesURL(),
			],
		], $this->dotted( $context ), [
			'jquery',
			gEditorial\Scripts::pkgPrintThis(),
		] );
	}

	/**
	 * Renders term view for given context.
	 *
	 * @param object $term
	 * @param string $context
	 * @return array
	 */
	private function framepageviews__render_view_for_term( $term, $context )
	{
		if ( ! $view = $this->viewengine__view_by_term( $term, $context ) )
			return gEditorial\Info::renderSomethingIsWrong();

		$data = $this->framepageviews__get_view_data_for_term( $term, $context );

		if ( ! empty( $data['___flags'] ) && method_exists( $this, 'framepageviews__handle_flags_for_term' ) )
			$this->framepageviews__handle_flags_for_term( (array) $data['___flags'], $term, $context, $data );

		echo $this->wrap_open( '-view -'.$context );
			$this->actions( 'render_view_term_before', $term, $context, $data, $view );
			$this->viewengine__render( $view, $data );
			$this->actions( 'render_view_term_after', $term, $context, $data, $view );
		echo '</div>';

		$data = $this->framepageviews__cleanup_view_data_for_term( $data, $term, $context );

		$this->framepageviews__print_script_for_term( $term, $context, $data );

		echo $this->wrap_open( '-debug -debug-data', TRUE, $this->classs( 'raw' ), TRUE );
			Core\HTML::tableSide( $data );
		echo '</div>';

		return $data;
	}

	private function framepageviews__get_view_data_for_term( $term, $context )
	{
		$data = [];

		if ( $response = Services\RestAPI::getTermResponse( $term, 'view' ) )
			$data = $response;

		$data = WordPress\Strings::stripByProp( $data, 'meta_rendered',  Core\Arraay::prepString( $this->filters( 'term_meta_exclude_rendered',  [], $term, $context ) ), 'name' );
		// $data = WordPress\Strings::stripByProp( $data, 'units_rendered', Core\Arraay::prepString( $this->filters( 'term_units_exclude_rendered', [], $term, $context ) ), 'name' );
		$data = WordPress\Strings::stripByProp( $data, 'terms_rendered', Core\Arraay::prepString( $this->filters( 'term_terms_exclude_rendered', [], $term, $context ) ), 'name' );

		$data = WordPress\Strings::stripEmptyValues( $data, 'meta_rendered',  'rendered' );
		// $data = WordPress\Strings::stripEmptyValues( $data, 'units_rendered', 'rendered' );
		$data = WordPress\Strings::stripEmptyValues( $data, 'terms_rendered', 'rendered' );

		if ( method_exists( $this, 'framepageviews__prep_data_for_term' ) )
			$data = $this->framepageviews__prep_data_for_term( $data, $term, $context );

		$data['__direction']  = Core\HTML::dir();
		$data['__can_edit']   = WordPress\Term::edit( $term );
		$data['__can_debug']  = WordPress\IsIt::dev() || WordPress\User::isSuperAdmin();
		$data['__can_print']  = $this->role_can( 'prints' );
		$data['__can_export'] = $this->role_can( 'reports' );
		$data['__today']      = gEditorial\Datetime::dateFormat( 'now', 'printdate' );
		$data['__summaries']  = $this->filters( 'term_summaries', [], $data, $term, $context );
		$data['___flags']     = $this->filters( 'term_flags', [], $data, $term, $context );

		return $this->filters( 'view_data_for_term', $data, $term, $context );
	}

	private function framepageviews__cleanup_view_data_for_term( $data, $term, $context )
	{
		unset( $data['meta_rendered'] );
		unset( $data['units_rendered'] );
		unset( $data['terms_rendered'] );

		unset( $data['_links'] );

		unset( $data['___flags'] );
		unset( $data['___sides'] );
		unset( $data['___hooks'] );
		unset( $data['__summaries'] );
		unset( $data['__direction'] );
		unset( $data['__can_edit'] );
		unset( $data['__can_debug'] );
		unset( $data['__can_print'] );
		unset( $data['__can_export'] );
		unset( $data['__today'] );

		if ( method_exists( $this, 'framepageviews__cleanup_data_for_term' ) )
			$data = $this->framepageviews__cleanup_data_for_term( $data, $term, $context );

		return $this->filters( 'cleanup_view_data_for_term', $data, $term, $context );
	}

	private function framepageviews__print_script_for_term( $term, $context, $data )
	{
		Core\HTML::wrapScript( sprintf( 'window.%s = %s;',
			$this->hook( 'data' ),
			wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES )
		) );

		$this->enqueue_asset_js( [
			'config' => [
				'printtitle'  => WordPress\Term::title( $term ),
				'printstyles' => gEditorial\Scripts::getPrintStylesURL(),
			],
		], $this->dotted( $context ), [
			'jquery',
			gEditorial\Scripts::pkgPrintThis(),
		] );
	}
}
