<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait PairedImports
{
	/**
	 * Appends import button on edit posts page,
	 * while restricted by the paired via java-script.
	 *
	 * NOTE: uses paired slug!
	 * TODO: migrate to `Services\HeaderButtons::register()`
	 *
	 * @param string $posttype
	 * @return bool $hooked
	 */
	protected function pairedimports__hook_append_import_button( $posttype )
	{
		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		if ( ! $this->role_can( 'import', NULL, TRUE ) )
			return FALSE;

		if ( ! $selected = self::req( WordPress\Taxonomy::queryVar( $this->constant( $constants[1] ) ) ) )
			return FALSE;

		if ( ! $post_id = WordPress\Post::getIDbySlug( $selected, $this->constant( $constants[0] ) ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post_id ) )
			return FALSE;

		add_action( 'admin_footer',
			function () use ( $post ) {
				$label  = sprintf(
					/* translators: `%s`: post title */
					_x( 'Upload into %s', 'Internal: PairedImports: Button Label', 'geditorial' ),
					WordPress\Post::title( $post )
				);

				$button = $this->pairedimports_get_import_buttons( $post, 'importitems', 'NULL', $label, 'page-title-action' );
				$id     = $this->classs( 'hidden-importbutton' );

				echo $this->wrap( $button, 'hidden', TRUE, $id, TRUE );
				Core\HTML::wrapjQueryReady( '$($("#'.$id.'").html()).insertBefore($("#wpbody-content div.wrap hr.wp-header-end"));' );
			} );

		gEditorial\Scripts::enqueueColorBox();

		// TODO: add row action to remove from paired via Ajax
		// TODO: add bulk action to remove from paired
		$this->current_queried = $post->ID;

		return TRUE;
	}

	// NOTE: default context is `importitems`
	protected function pairedimports_get_import_buttons( $post, $context, $target = NULL, $label = NULL, $button_class = NULL )
	{
		$link = $this->get_adminpage_url( TRUE, [
			'target'   => $target ?? 'paired',
			'linked'   => $post->ID,
			'noheader' => 1
		], 'importitems' );

		$title = _x( 'Import Items via File or Paste', 'Internal: PairedImports: Button Title', 'geditorial' );
		$label = $label ?? sprintf(
			/* translators: `%s`: icon markup */
			_x( '%s Upload', 'Internal: PairedImports: Button Label', 'geditorial' ),
			Services\Icons::get( 'upload' )
		);

		return Core\HTML::tag( 'a', [
			'href'  => $link,
			'class' => Core\HTML::attrClass(
				$button_class ?? Core\HTML::buttonClass( TRUE, '-button-icon' ),
				[
					'-import-button',
					'do-colorbox-iframe',
				]
			),
			'title' => $title,
			'data'  => [
				'target'    => $target ?? 'paired',
				'module'    => $this->key,
				'linked'    => $post->ID,
				'max-width' => '95%',
			],
		], $label );
	}

	// NOTE: on strings API: `$strings['import_types']['pairedimports']`
	protected function pairedimports_define_import_types( $linked = FALSE, $posttypes = NULL )
	{
		return apply_filters( $this->hook_base( 'pairedimports', 'import_types' ),
			$this->get_strings( 'pairedimports', 'import_types', [
				'post_title' => _x( 'Title', 'Internal: PairedImports: Import Type', 'geditorial' ),
			] ),
			$linked,
			$posttypes,
			$this->key
		);
	}

	protected function pairedimports_define_fields()
	{
		return $this->get_strings( 'pairedimports', 'fields', [
			'identifier' => _x( 'Identifier', 'Internal: PairedImports: Field', 'geditorial' ),
			'title'      => _x( 'Title', 'Internal: PairedImports: Field', 'geditorial' ),
		] );
	}

	protected function pairedimports_get_fields( $context = 'display', $settings_key = 'pairedimports_fields' )
	{
		$all      = $this->pairedimports_define_fields();
		$required = [ 'title' ];
		$enabled  = $this->get_setting( $settings_key, array_keys( $all ) );
		$fields   = [];

		foreach ( $all as $field => $label )
			if ( in_array( $field, $required, TRUE ) || in_array( $field, $enabled, TRUE ) )
				$fields[$field] = $label;

		return $this->filters( 'pairedimports_fields', $fields, $enabled, $required, $context );
	}

	public function load_importitems_adminpage( $context = 'importitems' )
	{
		$this->_load_submenu_adminpage( $context );

		if ( ! $this->role_can( 'import', NULL, TRUE ) )
			return;

		if ( ! $linked = self::req( 'linked', FALSE ) )
			return;

		if ( ! $post = WordPress\Post::get( (int) $linked ) )
			return;

		$posttypes = $this->posttypes();

		$this->enqueue_asset_js( [
			'strings' => $this->get_strings( 'pairedimports', 'js_strings', [
				'message'      => _x( 'Import Raw Data into this Post', 'Internal: PairedImports: String', 'geditorial' ),
				'emptydata'    => _x( 'Empty Data!', 'Internal: PairedImports: String', 'geditorial' ),
				'unsupported'  => _x( 'Unsupported Data!', 'Internal: PairedImports: String', 'geditorial' ),
				'novalid'      => _x( 'No Valid Data Available!', 'Internal: PairedImports: String', 'geditorial' ),
				'discovery'    => _x( 'Discovery', 'Internal: PairedImports: String', 'geditorial' ),
				'clearadded'   => _x( 'Clear Added', 'Internal: PairedImports: String', 'geditorial' ),
				'insertnew'    => _x( 'Add New on Discovery', 'Internal: PairedImports: String', 'geditorial' ),
				'add'          => _x( 'Add', 'Internal: PairedImports: String', 'geditorial' ),
				'addtitle'     => _x( 'Add selected rows', 'Internal: PairedImports: String', 'geditorial' ),
				'delete'       => _x( 'Delete', 'Internal: PairedImports: String', 'geditorial' ),
				'deletetitle'  => _x( 'Delete selected rows', 'Internal: PairedImports: String', 'geditorial' ),
				'clear'        => _x( 'Clear', 'Internal: PairedImports: String', 'geditorial' ),
				'cleartitle'   => _x( 'Clear all data on table', 'Internal: PairedImports: String', 'geditorial' ),
				'paste'        => _x( 'Paste', 'Internal: PairedImports: String', 'geditorial' ),
				'pastetitle'   => _x( 'Paste from supported formats', 'Internal: PairedImports: String', 'geditorial' ),
				'upload'       => _x( 'Upload', 'Internal: PairedImports: String', 'geditorial' ),
				'uploadtitle'  => _x( 'Upload from supported files', 'Internal: PairedImports: String', 'geditorial' ),
				'undefined'    => _x( 'Undefined', 'Internal: PairedImports: String', 'geditorial' ),
				'deleteme'     => _x( 'Delete Me', 'Internal: PairedImports: String', 'geditorial' ),
				'flipdate'     => _x( 'Flip Date', 'Internal: PairedImports: String', 'geditorial' ),
				'founded'      => _x( 'Founded', 'Internal: PairedImports: String', 'geditorial' ),
				'remove'       => _x( 'Delete', 'Internal: PairedImports: String', 'geditorial' ),
				'search'       => _x( 'Search', 'Internal: PairedImports: String', 'geditorial' ),
				'openitem'     => _x( 'Open Item', 'Internal: PairedImports: String', 'geditorial' ),
				'searchholder' => _x( 'Search …', 'Internal: PairedImports: String', 'geditorial' ),

				/* translators: `%s`: count number */
				'countlines'    => _x( '%s lines', 'Internal: PairedImports: String', 'geditorial' ),
				/* translators: `%s`: count number */
				'countselected' => _x( '%s selected', 'Internal: PairedImports: String', 'geditorial' ),
			] ),
			'fields'  => $this->pairedimports_get_fields( 'edit' ),
			'config'  => [
				'linked'       => $post->ID,
				'route'        => WordPress\Post::getRestRoute( $post ),
				'title'        => WordPress\Post::fullTitle( $post, TRUE ),
				'itemsfrom'    => Services\Paired::PAIRED_REST_FROM,
				'discovery'    => Services\LineDiscovery::namespace(),
				'searchselect' => Services\SearchSelect::namespace(),
				'infolink'     => add_query_arg( [ 'action' => 'edit' ], admin_url( 'post.php' ) ),
				'posttypes'    => $posttypes,
				'types'        => $this->pairedimports_define_import_types( $post, $posttypes ),
			],
		], FALSE, [], '_importitems' );

		gEditorial\Scripts::enqueueApp( 'import-items', [ gEditorial\Scripts::pkgSheetJS() ] );
	}

	public function render_importitems_adminpage()
	{
		if ( ! $post = self::req( 'linked' ) )
			return gEditorial\Info::renderNoPostsAvailable();

		if ( ! $post = WordPress\Post::get( $post ) )
			return gEditorial\Info::renderNoPostsAvailable();

		$context = 'importitems';

		if ( current_user_can( 'edit_post', $post->ID ) ) {

			$title = sprintf(
				/* translators: `%s`: post title */
				_x( 'Import Items for %s', 'Internal: PairedImports: Page Title', 'geditorial' ),
				WordPress\Post::title( $post )
			);

			gEditorial\Settings::wrapOpen( $this->key, $context, $title );

				gEditorial\Scripts::renderAppMounter( 'import-items', $this->key );
				gEditorial\Scripts::noScriptMessage();

			gEditorial\Settings::wrapClose();

		} else {

			gEditorial\Settings::wrapOpen( $this->key, $context, gEditorial\Plugin::denied( FALSE ) );
				Core\HTML::dieMessage( $this->get_notice_for_noaccess() );
			gEditorial\Settings::wrapClose( FALSE );
		}
	}
}
