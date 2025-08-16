<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait PairedMetaBox
{
	/**
	 * Returns post ids with selected terms from settings.
	 * The results will be excluded form drop-down on supported post-types.
	 *
	 * @return array
	 */
	protected function paired_get_dropdown_excludes()
	{
		if ( ! $terms = $this->get_setting( 'paired_exclude_terms' ) )
			return [];

		if ( ! $constants = $this->paired_get_constants() )
			return [];

		$args = [
			'post_type' => $this->constant( $constants[0] ),
			'tax_query' => [ [
				'taxonomy' => $this->constant( $constants[3] ),
				'terms'    => $terms,
			] ],
			'fields'         => 'ids',
			'posts_per_page' => -1,

			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		];

		$query = new \WP_Query();
		return $query->query( $args );
	}

	// NOTE: sub-terms must be hierarchical
	// OLD: `do_render_metabox_assoc()`
	// FIXME: rewrite to accept `$constants`
	protected function paired_do_render_metabox( $post, $posttype_constant, $paired_constant, $subterm_constant = FALSE, $display_empty = FALSE )
	{
		$subterm   = FALSE;
		$dropdowns = $displayed = $parents = $subterms = [];
		$excludes  = $this->paired_get_dropdown_excludes();
		$multiple  = $this->get_setting( 'multiple_instances', FALSE );
		$forced    = $this->get_setting( 'paired_force_parents', FALSE );
		$posttype  = $this->constant( $posttype_constant );
		$paired    = $this->constant( $paired_constant );
		$terms     = WordPress\Taxonomy::getPostTerms( $paired, $post );
		$none_main = Services\CustomPostType::getLabel( $posttype, 'show_option_select' );
		$prefix    = $this->classs();
		// $access    = $this->role_can( 'paired' );

		if ( $subterm_constant && $this->get_setting( 'subterms_support' ) ) {

			$subterm  = $this->constant( $subterm_constant );
			$none_sub = Services\CustomTaxonomy::getLabel( $subterm, 'show_option_select' );
			$subterms = WordPress\Taxonomy::getPostTerms( $subterm, $post, FALSE );
		}

		foreach ( $terms as $term ) {

			if ( $term->parent )
				$parents[] = $term->parent;

			// avoid if has child in the list
			if ( $forced && in_array( $term->term_id, $parents, TRUE ) )
				continue;

			if ( ! $to_post_id = $this->paired_get_to_post_id( $term, $posttype_constant, $paired_constant ) )
				continue;

			if ( in_array( $to_post_id, $excludes, TRUE ) )
				continue;

			$dropdown = MetaBox::paired_dropdownToPosts( $posttype, $paired, $to_post_id, $prefix, $excludes, $none_main, $display_empty );

			if ( $subterm ) {

				if ( $multiple ) {

					$sub_meta = get_post_meta( $post->ID, sprintf( '_%s_subterm_%s', $posttype, $to_post_id ), TRUE );
					$selected = ( $sub_meta && $subterms && in_array( $sub_meta, $subterms ) ) ? $sub_meta : 0;

				} else {

					$selected = $subterms ? array_pop( $subterms ) : 0;
				}

				$dropdown.= MetaBox::paired_dropdownSubTerms( $subterm, $to_post_id, $this->classs( $subterm ), $selected, $none_sub );

				if ( $multiple )
					$dropdown.= '<hr />';
			}

			$dropdowns[$term->term_id] = $dropdown;
			$displayed[] = $to_post_id;
		}

		// final check if had children in the list
		if ( $forced && count( $parents ) )
			$dropdowns = Core\Arraay::stripByKeys( $dropdowns, Core\Arraay::prepNumeral( $parents ) );

		$excludes = Core\Arraay::prepNumeral( $excludes, $displayed );

		if ( empty( $dropdowns ) )
			$dropdowns[0] = MetaBox::paired_dropdownToPosts( $posttype, $paired, '0', $prefix, $excludes, $none_main, $display_empty );

		else if ( $multiple )
			$dropdowns[0] = MetaBox::paired_dropdownToPosts( $posttype, $paired, '0', $prefix, $excludes, $none_main, $display_empty );

		foreach ( $dropdowns as $dropdown )
			if ( $dropdown )
				echo $dropdown;

		// TODO: support for clear all button via js, like `subterms`

		if ( $subterm )
			$this->enqueue_asset_js( 'subterms', 'module' );
	}

	protected function _hook_paired_mainbox( $screen, $remove_parent_order = TRUE, $context = NULL, $metabox_context = 'side', $extra = [] )
	{
		if ( ! $this->_paired || empty( $screen->post_type ) )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		if ( is_null( $context ) )
			$context = 'mainbox';

		if ( method_exists( $this, 'store_'.$context.'_metabox_'.$screen->post_type ) )
			add_action( sprintf( 'save_post_%s', $screen->post_type ), [ $this, 'store_'.$context.'_metabox_'.$screen->post_type ], 20, 3 );

		else if ( method_exists( $this, 'store_'.$context.'_metabox' ) )
			add_action( 'save_post', [ $this, 'store_'.$context.'_metabox' ], 20, 3 );

		$this->filter_false_module( 'meta', 'mainbox_callback', 12 );

		if ( $remove_parent_order ) {
			$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
			$this->filter_false_module( 'tweaks', 'metabox_parent' );
			remove_meta_box( 'pageparentdiv', $screen, 'side' );
		}

		$metabox  = $this->classs( $context );
		$callback = function ( $post, $box ) use ( $constants, $context, $screen ) {

			if ( $this->check_hidden_metabox( $box, $post->post_type ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

				$this->actions(
					sprintf( 'render_%s_metabox', $context ),
					$post,
					$box,
					NULL,
					sprintf( '%s_%s', $context, $this->constant( $constants[0] ) )
				);

				do_action( 'geditorial_meta_render_metabox', $post, $box, NULL );

				$this->_render_mainbox_content( $post, $box, $context, $screen );

				do_action(
					// // @HOOK: `geditorial_mainbox_{paired_posttype}_{current_posttype}`
					// $this->hook_base( 'metabox', $context, $this->constant( $constants[0] ), $post->post_type ),
					// @HOOK: `geditorial_metabox_mainbox_{current_posttype}`
					$this->hook_base( 'metabox', $context, $post->post_type ),
					$post,
					$box,
					$context,
					$screen
				);

			echo '</div>';

			$this->nonce_field( $context );
		};

		add_meta_box(
			$metabox,
			$this->strings_metabox_title_via_posttype( $screen->post_type, $context ),
			$callback,
			$screen,
			$metabox_context,
			'high'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function ( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );
	}

	protected function _hook_paired_listbox( $screen, $context = NULL, $metabox_context = 'advanced', $extra = [] )
	{
		if ( ! $this->_paired || empty( $screen->post_type ) )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		if ( is_null( $context ) )
			$context = 'listbox';

		$metabox  = $this->classs( $context );
		$callback = function ( $object, $box ) use ( $constants, $context, $screen ) {

			if ( $this->check_hidden_metabox( $box, $object->post_type ) )
				return;

			if ( MetaBox::checkDraftMetaBox( $box, $object ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

			$this->actions(
				sprintf( 'render_%s_metabox', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $this->constant( $constants[0] ) )
			);

			$term = $this->paired_get_to_term( $object->ID, $constants[0], $constants[1] );

			if ( $list = MetaBox::getTermPosts( $this->constant( $constants[1] ), $term, $this->posttypes() ) )
				echo $list;

			else
				echo Core\HTML::wrap( $this->strings_metabox_noitems_via_posttype( $screen->post_type, $context ), 'field-wrap -empty' );

			$this->_render_listbox_extra( $object, $box, $context, $screen );

			echo '</div>';

			$this->nonce_field( $context );
		};

		add_meta_box(
			$metabox,
			$this->strings_metabox_title_via_posttype( $screen->post_type, $context ),
			$callback,
			$screen,
			$metabox_context,
			'low'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function ( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );

		if ( $this->role_can( 'import', NULL, TRUE ) )
			Scripts::enqueueColorBox();
	}

	// DEFAULT METHOD
	// TODO: support for `post_actions` on Actions module
	// TODO: support for empty paired items with js button confirmation
	protected function _render_listbox_extra( $post, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'listbox';

		$html = '';

		if ( $this->_paired && $this->role_can( 'import', NULL, TRUE ) && method_exists( $this, 'pairedimports_get_import_buttons' ) )
			$html.= $this->pairedimports_get_import_buttons( $post, $context );

		if ( $this->role_can( 'reports' ) && method_exists( $this, 'exports_get_export_buttons' ) )
			$html.= $this->exports_get_export_buttons( $post->ID, $context, 'paired' );

		echo Core\HTML::wrap( $html, 'field-wrap -buttons' );
	}

	protected function _hook_paired_pairedbox( $screen, $menuorder = FALSE, $context = NULL, $extra = [] )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		if ( is_null( $context ) )
			$context = 'pairedbox';

		$metabox  = $this->classs( $context );
		$action   = sprintf( 'render_%s_metabox', $context );
		$callback = function ( $post, $box ) use ( $constants, $context, $action, $menuorder ) {

			if ( $this->check_hidden_metabox( $box, $post->post_type ) )
				return;

			$action_context = sprintf( '%s_%s', $context, $this->constant( $constants[0] ) );

			echo $this->wrap_open( '-admin-metabox' );

			if ( $this->get_setting( 'quick_newpost' ) ) {

				$this->actions(
					$action,
					$post,
					$box,
					NULL,
					$action_context
				);

			} else {

				// TODO: check for `assign` roles
				if ( ! $this->paired_assign_is_available() )
					MetaBox::fieldEmptyPostType( $this->constant( $constants[0] ) );

				else
					$this->actions(
						$action,
						$post,
						$box,
						NULL,
						$action_context
					);
			}

			do_action(
				$this->hook_base( 'meta', 'render_metabox' ),
				$post,
				$box,
				NULL,
				$action_context
			);

			if ( $menuorder )
				MetaBox::fieldPostMenuOrder( $post );

			echo '</div>';

			$this->nonce_field( $context );
		};

		add_meta_box(
			$metabox,
			$this->strings_metabox_title_via_posttype( $this->constant( $constants[0] ), $context ),
			$callback,
			$screen,
			'side'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function ( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );

		add_action( $this->hook( $action ), function ( $post, $box, $fields = NULL, $action_context = NULL ) use ( $constants, $context ) {

			if ( ( $newpost = $this->get_setting( 'quick_newpost' ) ) && method_exists( $this, 'do_render_thickbox_newpostbutton' ) )
				$this->do_render_thickbox_newpostbutton( $post, $constants[0], 'newpost', [ 'target' => 'paired' ] );

			$this->paired_do_render_metabox( $post, $constants[0], $constants[1], $constants[2], $newpost );

		}, 10, 4 );

		if ( $this->get_setting( 'quick_newpost' ) )
			Scripts::enqueueThickBox();
	}

	// NOTE: logic separated for the use on edit screen
	protected function _hook_paired_store_metabox( $posttype )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		add_action( sprintf( 'save_post_%s', $posttype ), function ( $post_id, $post, $update ) use ( $constants ) {

			if ( ! $this->is_save_post( $post, $this->posttypes() ) )
				return;

			$this->paired_do_store_metabox( $post, $constants[0], $constants[1], $constants[2] );

		}, 20, 3 );

		return TRUE;
	}

	// OLD: `do_store_metabox_assoc()`
	protected function paired_do_store_metabox( $post, $posttype_constant, $paired_constant, $subterm_constant = FALSE )
	{
		$posttype = $this->constant( $posttype_constant );
		$paired   = self::req( $this->classs().'-'.$posttype, FALSE ); // NOTE: post-type may contain underline

		if ( FALSE === $paired )
			return;

		$terms = $this->paired_do_connection( 'store', $post->ID, $paired, $posttype_constant, $paired_constant );

		if ( FALSE === $terms )
			return FALSE;

		if ( ! $subterm_constant || ! $this->get_setting( 'subterms_support' ) )
			return;

		$subterm = $this->constant( $subterm_constant );

		// no post, no sub-term
		if ( ! count( $terms ) )
			return wp_set_object_terms( $post->ID, [], $subterm, FALSE );

		$request = self::req( $this->classs( $subterm ), FALSE );

		if ( FALSE === $request || ! is_array( $request ) )
			return;

		$subterms = [];
		$multiple = $this->get_setting( 'multiple_instances', FALSE );

		foreach ( (array) $paired as $paired_id ) {

			if ( ! $paired_id )
				continue;

			if ( ! array_key_exists( $paired_id, $request ) )
				continue;

			$sub_paired = $request[$paired_id];

			if ( $multiple ) {

				$sub_metakey = sprintf( '_%s_subterm_%s', $posttype, $paired_id );

				if ( $sub_paired )
					update_post_meta( $post->ID, $sub_metakey, (int) $sub_paired );
				else
					delete_post_meta( $post->ID, $sub_metakey );
			}

			if ( $sub_paired )
				$subterms[] = (int) $sub_paired;
		}

		wp_set_object_terms( $post->ID, Core\Arraay::prepNumeral( $subterms ), $subterm, FALSE );
	}

	// NOTE: alternative to `pairedbox`
	protected function _hook_paired_overviewbox( $screen, $menuorder = FALSE, $context = NULL, $extra = [] )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		if ( is_null( $context ) )
			$context = 'overviewbox';

		$metabox  = $this->classs( $context );
		$action   = sprintf( 'render_%s_metabox', $context );
		$callback = function ( $post, $box ) use ( $constants, $context, $action, $menuorder ) {

			if ( $this->check_hidden_metabox( $box, $post->post_type ) )
				return;

			$action_context = sprintf( '%s_%s', $context, $this->constant( $constants[0] ) );

			echo $this->wrap_open( '-admin-metabox' );

			$this->actions(
				$action,
				$post,
				$box,
				NULL,
				$action_context
			);

			// action for `Meta` Module
			do_action(
				$this->hook_base( 'meta', 'render_metabox' ),
				$post,
				$box,
				NULL,
				$action_context
			);

			if ( $menuorder )
				MetaBox::fieldPostMenuOrder( $post );

			echo '</div>';

			$this->nonce_field( $context );
		};

		add_meta_box(
			$metabox,
			$this->strings_metabox_title_via_posttype( $this->constant( $constants[0] ), $context ),
			$callback,
			$screen,
			'advanced'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function ( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );

		add_action( $this->hook( $action ), function ( $post, $box, $fields = NULL, $action_context = NULL ) use ( $constants, $context ) {

			if ( ! $items = $this->paired_all_connected_from( $post, $context ) )
				return Core\HTML::desc( $this->get_string( 'empty', $context, 'notices', gEditorial\Plugin::noinfo( FALSE ) ), TRUE, 'field-wrap -empty' );

			echo $this->wrap_open( 'field-wrap -list' ).'<ol>';

			$before = $this->wrap_open_row( $this->constant( $constants[1] ), '-paired-row' );
			// $before.= $this->get_column_icon( FALSE, NULL, NULL, $constants[1] );
			$after  = '</li>';

			foreach ( $items as $item )
				echo $before.WordPress\Post::fullTitle( $item, 'overview' ).$after;

			echo '</ol></div>';

		}, 10, 4 );
	}

	// NOTE: alternative to `listbox`
	protected function pairedmetabox__hook_megabox( $screen, $context = NULL, $metabox_context = 'advanced', $extra = [] )
	{
		if ( ! $this->_paired || empty( $screen->post_type ) )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		if ( is_null( $context ) )
			$context = 'megabox';

		$post_title    = WordPress\Post::title(); // NOTE: gets post from query-args in admin
		$singular_name = Services\CustomPostType::getLabel( $screen->post_type, 'singular_name' );

		/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
		$default = _x( 'No items connected to &ldquo;%1$s&rdquo; %2$s!', 'Internal: PairedMetaBox: MetaBox Empty: `megabox_empty`', 'geditorial' );
		$empty   = $this->get_string( sprintf( '%s_empty', $context ), $constants[0], 'metabox', $default );
		$noitems = sprintf( $empty, $post_title, $singular_name );

		$callback = function ( $object, $box ) use ( $constants, $context, $screen, $noitems ) {

			if ( $this->check_hidden_metabox( $box, $object->post_type ) )
				return;

			if ( MetaBox::checkDraftMetaBox( $box, $object ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

			$this->actions(
				sprintf( 'render_%s_metabox', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $this->constant( $constants[0] ) )
			);

			$term = $this->paired_get_to_term( $object->ID, $constants[0], $constants[1] );

			// FIXME: new list with custom callback for each item
			if ( $list = MetaBox::getTermPosts( $this->constant( $constants[1] ), $term, $this->posttypes() ) )
				echo $list;

			else
				echo Core\HTML::wrap( $noitems, 'field-wrap -empty' );

			$this->_render_megabox_extra( $object, $box, $context, $screen );

			echo '</div>';

			$this->nonce_field( $context );
		};

		/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
		$default = _x( 'In &ldquo;%1$s&rdquo; %2$s', 'Internal: PairedMetaBox: MetaBox Title: `megabox_title`', 'geditorial' );
		$title   = $this->get_string( sprintf( '%s_title', $context ), $constants[0], 'metabox', $default );
		$metabox = $this->classs( $context );

		add_meta_box(
			$metabox,
			sprintf( $title, $post_title ?: gEditorial\Plugin::untitled( FALSE ), $singular_name ),
			$callback,
			$screen,
			$metabox_context,
			'low'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function ( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );

		if ( $this->role_can( 'import', NULL, TRUE ) )
			Scripts::enqueueColorBox();
	}

	// DEFAULT METHOD
	// https://codepen.io/geminorum/pen/yLQqYVX
	// NOTE: alternative to `listbox`
	protected function _render_megabox_extra( $post, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'megabox';

		$this->_render_listbox_extra( $post, $box, $context, $screen );
	}
}
