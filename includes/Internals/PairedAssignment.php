<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait PairedAssignment
{
	use MetaBoxSupported;

	protected function paired_assignment__init()
	{
		$this->filter( 'restapi_terms_rendered', 5, 12, 'paired_assignment', $this->base );
		$this->filter( 'searchselect_result_image_for_term', 3, 12, 'paired_assignment', $this->base );
		$this->filter( 'searchselect_result_extra_for_term', 3, 12, 'paired_assignment', $this->base );
		$this->filter( 'termrelations_supported', 4, 9, 'paired_assignment', $this->base );
	}

	protected function paired_assignment__load_submenu_adminpage( $context )
	{
		$target = self::req( 'target', 'mainapp' );

		if ( 'mainapp' === $target )
			$this->paired_assignment__do_enqueue_app();

		// else if ( 'summaryreport' === $target && $this->role_can( 'reports' ) )
		// 	$this->action( 'admin_print_styles', 0, 99, 'summaryreport' );
	}

	public function restapi_terms_rendered_paired_assignment( $data, $taxonomy, $params, $object_type, $post )
	{
		if ( ! $paired = $this->paired_get_constants() )
			return $data;

		if ( $taxonomy->name !== $this->constant( $paired[1] ) )
			return $data;

		$html = '';
		$context = 'terms_rendered';

		if ( $items = $this->paired_all_connected_from( $post, $context ) ) {

			$html.= '<ol>';

			$before = $this->wrap_open_row( $taxonomy->name, '-paired-row' );
			// $before.= $this->get_column_icon( FALSE, NULL, NULL, $paired[1] );
			$after  = '</li>';

			foreach ( $items as $item )
				$html.= $before.WordPress\Post::fullTitle( $item, 'overview' ).$after;

			$html.= '</ol>';

		} else {

			// FIXME: should report empty
			$html = $this->get_string( 'empty', $context, 'notices', gEditorial\Plugin::noinfo( FALSE ) );
		}

		$data['ordered'] = $html;

		return $data;
	}

	protected function paired_assignment__do_enqueue_app( $atts = [] )
	{
		if ( ! $paired = $this->paired_get_constants() )
			return FALSE;

		$args = self::atts( [
			'app'     => defined( 'self::APP_NAME' ) ? constant( 'self::APP_NAME' ) : 'assignment-dock',
			'asset'   => defined( 'self::APP_ASSET' ) ? constant( 'self::APP_ASSET' ) : '_assignment',
			'can'     => 'paired',
			'linked'  => NULL,
			'targets' => [ $this->constant( $paired[1] ) => $this->get_taxonomy_label( $paired[1] ) ],
			'summary' => FALSE,                                                                             // $this->constant( 'restapi_attribute' ),
			'context' => 'edit',
			'strings' => [],
		], $atts );

		if ( ! $this->role_can( $args['can'] ) || empty( $args['targets'] ) )
			return FALSE;

		if ( ! $linked = $args['linked'] ?? self::req( 'linked', FALSE ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $linked ) )
			return FALSE;

		$targets = $routes = [];

		foreach ( $args['targets'] as $target => $label ) {

			if ( WordPress\Taxonomy::can( $target, 'manage_terms' ) )
				$routes[$target] = WordPress\Taxonomy::getRestRoute( $target );

			if ( WordPress\Taxonomy::can( $target, 'assign_terms' ) )
				$targets[$target] = $label;
		}

		$asset = [
			'strings' => $this->get_strings( $args['asset'], 'js', $args['strings'] ),
			'fields'  => $this->paired_assignment_get_supported_fields( $args['context'], $post->post_type ),
			'linked'  => [
				'id'      => $post->ID,
				'text'    => WordPress\Post::title( $post ),
				'rest'    => WordPress\Post::getRestRoute( $post ),
				'base'    => WordPress\PostType::getRestRoute( $post ),
				'extra'   => Services\SearchSelect::getExtraForPost( $post, [ 'context' => $args['asset'] ], WordPress\Post::summary( $post, 'edit' ) ),
				'image'   => Services\SearchSelect::getImageForPost( $post, [ 'context' => $args['asset'] ] ),
				'related' => Services\TermRelations::POSTTYPE_ATTR,
			],
			'config' => [
				'linked'       => $linked,
				'context'      => $args['context'],
				'targets'      => array_keys( $targets ),
				'labels'       => $targets,
				'routes'       => $routes,
				'searchselect' => Services\SearchSelect::namespace(),
				// 'discovery'    => Services\LineDiscovery::namespace(), // NOT USED YET
				'hints'        => Services\ObjectHints::namespace(),
				'summary'      => $args['summary'],
				'perpage'      => 5,
			],
		];

		return $this->enqueue_asset_js( $asset, FALSE, [
			gEditorial\Scripts::enqueueApp( $args['app'] )
		], $args['asset'] );
	}

	protected function paired_assignment__do_render_iframe_content( $context = NULL, $assign_template = NULL, $reports_template = NULL, $custom_app = NULL )
	{
		if ( ! $post = self::req( 'linked' ) )
			return gEditorial\Info::renderNoPostsAvailable();

		if ( ! $post = WordPress\Post::get( $post ) )
			return gEditorial\Info::renderNoPostsAvailable();

		$target = self::req( 'target', 'mainapp' );

		if ( $this->role_can_post( $post, 'paired' ) && 'mainapp' === $target ) {

			/* translators: `%s`: post title */
			$assign_template = _x( 'Assignment Dock for %s', 'Internal: PairedAssignment: Page Title', 'geditorial-admin' );

			gEditorial\Settings::wrapOpen( $this->key, $context, sprintf( $assign_template ?? '%s', WordPress\Post::title( $post ) ) );

				gEditorial\Scripts::renderAppMounter( $custom_app ?? ( defined( 'self::APP_NAME' ) ? constant( 'self::APP_NAME' ) : 'assignment-dock' ), $this->key );
				gEditorial\Scripts::noScriptMessage();

			gEditorial\Settings::wrapClose( FALSE );

		} else if ( $this->role_can_post( $post, 'reports' ) && 'summaryreport' === $target ) {

			/* translators: `%s`: post title */
			$reports_template = _x( 'Assignment Overview for %s', 'Internal: PairedAssignment: Page Title', 'geditorial-admin' );

			gEditorial\Settings::wrapOpen( $this->key, $context, sprintf( $reports_template ?? '%s', WordPress\Post::title( $post ) ) );

				// TODO

			gEditorial\Settings::wrapClose( FALSE );

		} else {

			gEditorial\Settings::wrapOpen( $this->key, $context, gEditorial\Plugin::denied( FALSE ) );
				Core\HTML::dieMessage( $this->get_notice_for_noaccess() );
			gEditorial\Settings::wrapClose( FALSE );
		}
	}

	// `$this->filter( 'searchselect_result_image_for_term', 3, 12, 'paired_assignment', $this->base );`
	public function searchselect_result_image_for_term_paired_assignment( $data, $term, $queried )
	{
		if ( empty( $queried['context'] )
			|| in_array( $queried['context'], [ 'select2', 'pairedimports' ], TRUE ) )
			return $data;

		if ( ! $paired = $this->paired_get_constants() )
			return $data;

		if ( ! $this->is_taxonomy( $paired[1], $term ) )
			return $data;

		$metakey    = $this->constant( 'metakey_term_image', 'image' );
		$attachment = WordPress\Taxonomy::getThumbnailID( $term->term_id, $metakey );

		if ( $src = WordPress\Media::getAttachmentSrc( $attachment, [ 45, 72 ], FALSE ) )
			return $src;

		return $data;
	}

	// `$this->filter( 'searchselect_result_extra_for_term', 3, 12, 'paired_assignment', $this->base );`
	public function searchselect_result_extra_for_term_paired_assignment( $data, $term, $queried )
	{
		if ( empty( $queried['context'] )
			|| in_array( $queried['context'], [ 'select2', 'pairedimports' ], TRUE ) )
			return $data;

		if ( ! $paired = $this->paired_get_constants() )
			return $data;

		if ( $this->is_taxonomy( $paired[1], $term ) )
			return WordPress\Term::summary( $term );

		return $data;
	}

	// `$this->filter( 'termrelations_supported', 4, 9, 'paired_assignment', $this->base );`
	public function termrelations_supported_paired_assignment( $fields, $taxonomy, $context, $posttype )
	{
		if ( ! $paired = $this->paired_get_constants() )
			return $fields;

		if ( $this->constant( $paired[1] ) === $taxonomy )
			return array_merge( $fields, $this->paired_assignment_get_supported_fields( $context, $posttype ) );

		return $fields;
	}

	protected function paired_assignment_get_supported_fields( $context, $posttype )
	{
		return $this->filters( 'paired_supported_fields', [

			'overwrite' => [
				'title'   => _x( 'Overwrite', 'Paired Assignment: Field Title', 'geditorial-admin' ),
				'desc'    => _x( 'String to override the title.', 'Paired Assignment: Field Description', 'geditorial-admin' ),
				'type'    => 'string',
				'default' => '',
			],

			'notes' => [
				'title'   => _x( 'Notes', 'Paired Assignment: Field Title', 'geditorial-admin' ),
				'desc'    => _x( 'About relation to the post.', 'Paired Assignment: Field Description', 'geditorial-admin' ),
				'type'    => 'string',
				'default' => '',
			],

			'featured' => [
				'title'   => _x( 'Featured', 'Paired Assignment: Field Title', 'geditorial-admin' ),
				'desc'    => _x( 'Features the connection on the extended list.', 'Paired Assignment: Field Description', 'geditorial-admin' ),
				'type'    => 'boolean',
				'default' => FALSE,
			],
		], $context, $posttype );
	}

	protected function pairedmetabox__render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		if ( ! $paired = $this->paired_get_constants() )
			return FALSE;

		if ( $items = $this->paired_all_connected_from( $object, $context ) ) {

			echo $this->wrap_open( 'field-wrap -list', TRUE, $this->classs( 'rendered' ) ).'<ol>';

			$before = $this->wrap_open_row( $this->constant( $paired[1] ), '-paired-row' );
			// $before.= $this->get_column_icon( FALSE, NULL, NULL, $paired[1] );
			$after  = '</li>';

			foreach ( $items as $item )
				echo $before.WordPress\Post::fullTitle( $item, 'overview' ).$after;

			echo '</ol></div>';

		} else {

			Core\HTML::desc( $this->get_string( 'empty', $context, 'notices', gEditorial\Plugin::noinfo( FALSE ) ), TRUE, 'field-wrap -empty' );
		}

		echo Core\HTML::wrap( $this->framepage_get_mainlink_for_post( $object, [
			'context'  => 'mainbutton',
			'target'   => 'mainapp',                                                                                           // OR: `summaryreport`
			'maxwidth' => '800px',
			'refresh'  => sprintf( 'terms_rendered.%s.ordered', get_taxonomy( $this->constant( $paired[1] ) )->rest_base ),
		] ), 'field-wrap -buttons' );
	}
}
