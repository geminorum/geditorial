<?php namespace geminorum\gEditorial\Modules\Addendum;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Addendum extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\LateChores;
	use Internals\MainDownload;
	use Internals\PairedAdmin;
	use Internals\PairedCore;
	use Internals\PairedFront;
	use Internals\PairedMetaBox;
	use Internals\PairedRowActions;
	use Internals\PairedTools;
	use Internals\PostMeta;
	use Internals\PostTypeOverview;
	use Internals\TemplatePostType;

	protected $deafults  = [ 'multiple_instances' => TRUE ];
	protected $positions = [ 'primary_posttype' => 18 ];

	public static function module()
	{
		return [
			'name'     => 'addendum',
			'title'    => _x( 'Addendum', 'Modules: Addendum', 'geditorial-admin' ),
			'desc'     => _x( 'Content Appendages', 'Modules: Addendum', 'geditorial-admin' ),
			'icon'     => 'carrot',
			'access'   => 'beta',
			'keywords' => [
				'pairedmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'paired_force_parents',
				'paired_manage_restricted',
				[
					'field'       => 'subterms_support',
					'title'       => _x( 'Appendage Necessities', 'Settings', 'geditorial-addendum' ),
					'description' => _x( 'Substitute taxonomy for the appendages and supported post-types.', 'Settings', 'geditorial-addendum' ),
				],
				'paired_exclude_terms' => [
					NULL,
					$this->constant( 'primary_taxonomy' ),
					$this->get_taxonomy_label( 'primary_taxonomy', 'no_terms' ),
				],
			],
			'_editlist' => [
				'admin_ordering',
				'admin_bulkactions',
			],
			'_editpost' => [
				'assign_default_term',
			],
			'_frontend' => [
				[
					'field'       => 'redirect_archives',
					'type'        => 'url',
					'title'       => _x( 'Redirect Archives', 'Settings', 'geditorial-addendum' ),
					'description' => _x( 'Redirects appendage archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-addendum' ),
					'placeholder' => Core\URL::home( 'archives' ),
				],
			],
			'_content' => [
				'archive_override',
				'archive_title' => [ NULL, $this->get_posttype_label( 'primary_posttype', 'all_items' ) ],
				'archive_content',
				'archive_template',
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'comment_status',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'primary_posttype', [
					'title',
					'excerpt',
					'thumbnail',
					'author',
					'comments',
					'date-picker',
					'editorial-roles'
				] ),
			],
			'_reports' => [
				'overview_taxonomies' => [ NULL, $this->get_posttype_taxonomies_list( 'primary_posttype' ) ],
				'overview_fields'     => [ NULL, $this->get_posttype_fields_list( 'primary_posttype', 'meta' ) ],
				'overview_units'      => [ NULL, $this->get_posttype_fields_list( 'primary_posttype', 'units' ) ],
			],
			'_constants' => [
				'main_shortcode_constant' => [ NULL, 'appendages' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype' => 'appendage',
			'primary_paired'   => 'appendages',
			'primary_taxonomy' => 'appendage_category',
			'primary_subterm'  => 'appendage_necessity',
			'type_taxonomy'    => 'appendage_type',
			'status_taxonomy'  => 'appendage_status',
			'main_shortcode'   => 'appendages',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype' => _n_noop( 'Appendage', 'Appendages', 'geditorial-addendum' ),
				'primary_paired'   => _n_noop( 'Appendage', 'Appendages', 'geditorial-addendum' ),
				'primary_taxonomy' => _n_noop( 'Appendage Category', 'Appendage Categories', 'geditorial-addendum' ),
				'primary_subterm'  => _n_noop( 'Appendage Necessity', 'Appendage Necessities', 'geditorial-addendum' ),
				'type_taxonomy'    => _n_noop( 'Appendage Type', 'Appendage Types', 'geditorial-addendum' ),
				'status_taxonomy'  => _n_noop( 'Appendage Status', 'Appendage Statuses', 'geditorial-addendum' ),
			],
			'labels' => [
				'primary_posttype' => [
					'featured_image' => _x( 'Appendage Cover', 'Label: Featured Image', 'geditorial-addendum' ),
					'metabox_title'  => _x( 'The Appendage', 'Label: MetaBox Title', 'geditorial-addendum' ),
				],
				'primary_paired' => [
					'metabox_title' => _x( 'To This Appendage', 'Label: MetaBox Title', 'geditorial-addendum' ),
				],
				'primary_taxonomy' => [
					'menu_name' => _x( 'Categories', 'Label: Menu Name', 'geditorial-addendum' ),
				],
				'primary_subterm' => [
					'menu_name' => _x( 'Necessities', 'Label: Menu Name', 'geditorial-addendum' ),
				],
				'type_taxonomy' => [
					'menu_name' => _x( 'Types', 'Label: Menu Name', 'geditorial-addendum' ),
				],
				'status_taxonomy' => [
					'menu_name' => _x( 'Statuses', 'Label: Menu Name', 'geditorial-addendum' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'column_icon_title' => _x( 'Appendage', 'Misc: `column_icon_title`', 'geditorial-addendum' ),
		];

		$strings['metabox'] = [
			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'listbox_title' => _x( 'Posts with &ldquo;%1$s&rdquo; %2$s', 'MetaBox: `listbox_title`', 'geditorial-addendum' ),
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'primary_subterm' => [
				'required'  => _x( 'Required', 'Necessity Taxonomy: Default Term', 'geditorial-addendum' ),
				'mandatory' => _x( 'Mandatory', 'Necessity Taxonomy: Default Term', 'geditorial-addendum' ),
			],
		];
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
				$this->constant( 'primary_posttype' ) => [
					'over_title' => [ 'type' => 'title_before' ],
					'sub_title'  => [ 'type' => 'title_after' ],
					'lead'       => [ 'type' => 'postbox_html' ],

					'content_embed_url' => [ 'type' => 'embed' ],
					'text_source_url'   => [ 'type' => 'text_source' ],
					'audio_source_url'  => [ 'type' => 'audio_source' ],
					'video_source_url'  => [ 'type' => 'video_source' ],
					'image_source_url'  => [ 'type' => 'image_source' ],
					'main_download_url' => [ 'type' => 'downloadable' ],
					'main_download_id'  => [ 'type' => 'attachment' ],
				],
				// '_supported' => [],
			],
		];
	}

	protected function paired_get_paired_constants()
	{
		return [
			'primary_posttype',
			'primary_paired',
			'primary_subterm',
			'primary_taxonomy'
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'primary_posttype' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_for( 'meta', 'primary_posttype' );
		// $this->add_posttype_fields_supported();
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'primary_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
		], 'primary_posttype', [

		] );

		$this->register_taxonomy( 'type_taxonomy', [
			'hierarchical'       => TRUE,
			// 'meta_box_cb'        => '__singleselect_terms_callback',
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'primary_posttype', [
			'custom_icon'     => 'screenoptions',
			// 'single_selected' => TRUE,
		] );

		$this->register_taxonomy( 'status_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__singleselect_terms_callback',
		], 'primary_posttype', [
			'admin_managed'   => TRUE,
			'single_selected' => TRUE,
		] );

		$this->paired_register( [], [
			'primary_taxonomy' => TRUE,
			'status_taxonomy'  => TRUE,
		] );

		$this->register_shortcode( 'main_shortcode' );

		$this->latechores__init_post_aftercare( $this->constant( 'primary_posttype' ) );

		if ( is_admin() )
			return;

		$this->_hook_paired_exclude_from_subterm();
		$this->_hook_paired_override_term_link();
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'primary_posttype' ) ) {
			$this->pairedadmin__hook_tweaks_column_connected( $posttype );
		}
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'primary_subterm' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'primary_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->comments__handle_default_status( $screen->post_type );
				$this->posttypes__media_register_headerbutton( 'primary_posttype' );
				$this->_hook_post_updated_messages( 'primary_posttype' );
				$this->_hook_paired_mainbox( $screen );
				$this->_hook_paired_listbox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->modulelinks__register_headerbuttons();
				$this->latechores__hook_admin_bulkactions( $screen );
				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );
				$this->coreadmin__hook_admin_ordering( $screen->post_type );
				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );
				$this->pairedadmin__hook_tweaks_column_connected( $screen->post_type );
				$this->pairedcore__hook_sync_paired();
				$this->corerestrictposts__hook_screen_taxonomies( [
					'primary_subterm',
					'primary_taxonomy',
					'type_taxonomy',
					'status_taxonomy',
				] );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( $subterms && $subterms === $screen->taxonomy )
				$this->filter_string( 'parent_file', sprintf( 'edit.php?post_type=%s', $this->constant( 'primary_posttype' ) ) );

			if ( 'edit-tags' == $screen->base ) {

				$this->_hook_paired_taxonomy_bulk_actions( $screen->post_type, $screen->taxonomy );

			} else if ( 'post' == $screen->base ) {

				$this->_metabox_remove_subterm( $screen, $subterms );
				$this->_hook_paired_pairedbox( $screen );
				$this->_hook_paired_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_paired_store_metabox( $screen->post_type );
				// $this->paired__hook_tweaks_column( $screen->post_type, 8 );
				// $this->paired__hook_screen_restrictposts();
				// $this->postmeta__hook_meta_column_row( $screen->post_type, [] );
			}
		}

		// only for supported post-types
		$this->remove_taxonomy_submenu( $subterms );

		$this->modulelinks__hook_calendar_linked_post( $screen );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'primary_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'primary_posttype' ) ) ) {

			if ( $post_id = $this->paired_get_to_post_id( get_queried_object(), 'primary_posttype', 'primary_paired' ) )
				WordPress\Redirect::doWP( get_permalink( $post_id ), 301 );

		} else if ( is_post_type_archive( $this->constant( 'primary_posttype' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress\Redirect::doWP( $redirect, 301 );
		}
	}

	public function template_include( $template )
	{
		return $this->templateposttype__include( $template, $this->constant( 'primary_posttype' ), FALSE );
	}

	protected function _render_mainbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		gEditorial\MetaBox::fieldPostMenuOrder( $object );
		gEditorial\MetaBox::fieldPostParent( $object );

		gEditorial\MetaBox::singleselectTerms( $object->ID, [
			'taxonomy'   => $this->constant( 'type_taxonomy' ),
			'posttype'   => $object->post_type,
			'empty_link' => FALSE,
		] );
	}

	protected function maindownload__posttype_supported( $posttype )
	{
		return $this->constant( 'primary_posttype' ) === $posttype;
	}

	protected function latechores_post_aftercare( $post )
	{
		return $this->maindownload__get_file_data_for_latechores( $post );
	}

	// TODO: must sort appendages by menu_order
	// TODO: optional append child appendages
	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'       => get_queried_object_id(),
			'render'   => NULL,
			'template' => NULL,
			'context'  => NULL,
			'wrap'     => TRUE,
			'class'    => '',
			'before'   => '',
			'after'    => '',
		], $atts, $tag ?: $this->constant( 'main_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $content;

		if ( $this->constant( 'primary_posttype' ) === $post->post_type ) {

			$appendages = [];

			// self as an appendage only if has download
			if ( $this->maindownload__get_link( $post ) )
				$appendages[] = $post;

			$extra = [
				'post_parent' => $post->ID,
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
			];

			if ( $children = WordPress\PostType::getIDs( $post->post_type, $extra, 'all' ) )
				$appendages = [ ...$appendages,  ...$children ];

			if ( empty( $appendages ) )
				return $content;

		} else {

			if ( ! $this->posttype_supported( $post->post_type ) )
				return $content;

			// NOTE: already sorted
			if ( ! $appendages = $this->get_linked_to_posts( $post ) )
				return $content;
		}

		if ( is_null( $args['template'] ) )
			$args['template'] = 'downloadbox';

		if ( is_null( $args['render'] ) )
			$args['render'] = [ '\geminorum\gEditorial\WordPress\Theme', 'render_post_template' ];

		if ( ! $template = $this->locate_template_part( $args['template'], $args['context'] ) ) {
			$this->log( 'CRITICAL', sprintf( 'TEMPLATE NOT FOUND: %s', $args['template'] ) );
			return $content;
		}

		$html = '';

		$this->maindownload__override_loop_before();

		foreach ( $appendages as $appendage )
			$html.= self::buffer( $args['render'], [ $template, $appendage ] );

		$this->maindownload__override_loop_after();

		if ( empty( $html ) )
			return $content;

		return gEditorial\ShortCode::wrap( $html, $this->constant( 'main_shortcode' ), $args );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );
				$this->paired_tools_handle_tablelist( $sub );
			}

			gEditorial\Scripts::enqueueThickBox();
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo gEditorial\Settings::toolboxColumnOpen(
			_x( 'Appendage Tools', 'Header', 'geditorial-addendum' ) );

			$this->paired_tools_render_card( $uri, $sub );

		echo '</div>';
	}

	protected function render_tools_html_before( $uri, $sub )
	{
		return $this->paired_tools_render_before( $uri, $sub );
	}

	public function imports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'imports', 'per_page' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'imports', $sub );
				$this->paired_imports_handle_tablelist( $sub );
			}

			gEditorial\Scripts::enqueueThickBox();
		}
	}

	protected function render_imports_html( $uri, $sub )
	{
		if ( ! $this->paired_imports_render_tablelist( $uri, $sub ) )
			return gEditorial\Info::renderNoImportsAvailable();
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', 'per_page' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->posttype_overview_render_table( 'primary_posttype', $uri, $sub ) )
			return gEditorial\Info::renderNoReportsAvailable();
	}
}
