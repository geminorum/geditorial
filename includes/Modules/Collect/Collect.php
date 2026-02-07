<?php namespace geminorum\gEditorial\Modules\Collect;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Collect extends gEditorial\Module
{
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\MetaBoxMain;
	use Internals\PairedAdmin;
	use Internals\PairedCore;
	use Internals\PairedFront;
	use Internals\PairedMetaBox;
	use Internals\PairedRowActions;
	use Internals\PairedTools;
	use Internals\PostMeta;
	use Internals\PostTypeOverview;

	public static function module()
	{
		return [
			'name'     => 'collect',
			'title'    => _x( 'Collect', 'Modules: Collect', 'geditorial-admin' ),
			'desc'     => _x( 'Create and use Collections of Posts', 'Modules: Collect', 'geditorial-admin' ),
			'icon'     => 'star-filled',
			'access'   => 'beta',
			'keywords' => [
				'has-widgets',
				'pairedmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'multiple_instances',
				'paired_force_parents',
				'paired_manage_restricted',
				[
					'field'       => 'subterms_support',
					'title'       => _x( 'Collection Parts', 'Settings', 'geditorial-collect' ),
					'description' => _x( 'Partition taxonomy for the collections and supported post-types.', 'Settings', 'geditorial-collect' ),
				],
				'comment_status',
			],
			'_editlist' => [
				'admin_ordering',
			],
			'_frontend' => [
				'insert_cover',
				'insert_priority',
				'posttype_feeds',
				'posttype_pages',
				[
					'field'       => 'redirect_archives',
					'type'        => 'url',
					'title'       => _x( 'Redirect Archives', 'Settings', 'geditorial-collect' ),
					'description' => _x( 'Redirects collection archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-collect' ),
					'placeholder' => Core\URL::home( 'archives' ),
				],
				[
					'field'       => 'redirect_groups',
					'type'        => 'url',
					'title'       => _x( 'Redirect Groups', 'Settings', 'geditorial-collect' ),
					'description' => _x( 'Redirects all group archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-collect' ),
					'placeholder' => Core\URL::home( 'archives' ),
				],
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'widget_support',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'collection_posttype', TRUE ),
			],
			'_reports' => [
				'overview_taxonomies' => [ NULL, $this->get_posttype_taxonomies_list( 'collection_posttype' ) ],
				'overview_fields'     => [ NULL, $this->get_posttype_fields_list( 'collection_posttype', 'meta' ) ],
				'overview_units'      => [ NULL, $this->get_posttype_fields_list( 'collection_posttype', 'units' ) ],
			],
			'_constants' => [
				'main_shortcode_constant' => [ NULL, 'collection' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'collection_posttype' => 'collection',
			'collection_paired'   => 'collections',
			'group_taxonomy'      => 'collection_group',
			'part_taxonomy'       => 'collection_part',

			'main_shortcode'   => 'collection',
			'group_shortcode'  => 'collection-group',
			'poster_shortcode' => 'collection-poster',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'collection_posttype' => _n_noop( 'Collection', 'Collections', 'geditorial-collect' ),
				'collection_paired'   => _n_noop( 'Collection', 'Collections', 'geditorial-collect' ),
				'group_taxonomy'      => _n_noop( 'Group', 'Groups', 'geditorial-collect' ),
				'part_taxonomy'       => _n_noop( 'Part', 'Parts', 'geditorial-collect' ),
			],
			'labels' => [
				'collection_posttype' => [
					'featured_image' => _x( 'Poster Image', 'Label: Featured Image', 'geditorial-collect' ),
				],
				'collection_paired' => [
					'show_option_all' => _x( 'Collection', 'Label: Show Option All', 'geditorial-collect' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'column_icon_title' => _x( 'Collection', 'Misc: `column_icon_title`', 'geditorial-collect' ),
		];

		$strings['metabox'] = [
			'collection_posttype' => [
				'metabox_title' => _x( 'The Collection', 'Label: MetaBox Title', 'geditorial-collect' ),
				'listbox_title' => _x( 'In This Collection', 'Label: MetaBox Title', 'geditorial-collect' ),
			],
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
				$this->constant( 'collection_posttype' ) => [
					'over_title' => [ 'type' => 'title_before' ],
					'sub_title'  => [ 'type' => 'title_after' ],

					'number_line' => [
						'title'       => _x( 'Number Line', 'Field Title', 'geditorial-collect' ),
						'description' => _x( 'The collection number line', 'Field Description', 'geditorial-collect' ),
						'icon'        => 'menu',
					],
					'total_items' => [
						'title'       => _x( 'Total Items', 'Field Title', 'geditorial-collect' ),
						'description' => _x( 'The collection total items', 'Field Description', 'geditorial-collect' ),
						'icon'        => 'admin-page',
					],
				],
				'_supported' => [
					'in_collection_order' => [
						'title'       => _x( 'Order', 'Field Title', 'geditorial-collect' ),
						'description' => _x( 'Post order in the collection', 'Field Description', 'geditorial-collect' ),
						'type'        => 'number',
						'context'     => 'pairedbox_collection',
						'icon'        => 'sort',
					],
					'in_collection_title' => [
						'title'       => _x( 'Title', 'Field Title', 'geditorial-collect' ),
						'description' => _x( 'Override post title in the collection', 'Field Description', 'geditorial-collect' ),
						'context'     => 'pairedbox_collection',
					],
					'in_collection_subtitle' => [
						'title'       => _x( 'Subtitle', 'Field Title', 'geditorial-collect' ),
						'description' => _x( 'Post subtitle in the collection', 'Field Description', 'geditorial-collect' ),
						'context'     => 'pairedbox_collection',
					],
					'in_collection_collaborator' => [
						'title'       => _x( 'Collaborator', 'Field Title', 'geditorial-collect' ),
						'description' => _x( 'Post collaborator in the collection', 'Field Description', 'geditorial-collect' ),
						'context'     => 'pairedbox_collection',
					],
				],
			],
		];
	}

	protected function paired_get_paired_constants()
	{
		return [
			'collection_posttype',
			'collection_paired',
			'part_taxonomy',
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'collection_posttype' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'group_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'collection_posttype', [
			'custom_icon' => 'clipboard',
		] );

		$this->paired_register( [], [
			'custom_icon' => $this->module->icon,
		], [
			'custom_icon' => 'exerpt-view',
		] );

		$this->register_shortcode( 'main_shortcode' );
		$this->register_shortcode( 'group_shortcode' );
		$this->register_shortcode( 'poster_shortcode' );

		if ( is_admin() )
			return;

		$this->_hook_paired_exclude_from_subterm();
		$this->_hook_paired_override_term_link();
	}

	public function template_redirect()
	{
		if ( is_robots() || is_favicon() || is_feed() )
			return;

		if ( is_tax( $this->constant( 'collection_paired' ) ) ) {

			if ( $post_id = $this->paired_get_to_post_id( get_queried_object(), 'collection_posttype', 'collection_paired' ) )
				WordPress\Redirect::doWP( get_permalink( $post_id ), 301 );

		} else if ( is_tax( $this->constant( 'group_taxonomy' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_groups', FALSE ) )
				WordPress\Redirect::doWP( $redirect, 301 );

		} else if ( is_post_type_archive( $this->constant( 'collection_posttype' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress\Redirect::doWP( $redirect, 301 );

		} else if ( is_singular( $this->constant( 'collection_posttype' ) ) ) {

			if ( $this->get_setting( 'insert_cover' ) )
				add_action( $this->hook_base( 'content', 'before' ),
					[ $this, 'insert_cover' ],
					$this->get_setting( 'insert_priority', -50 )
				);
		}
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'collection_posttype' ) ) {
			$this->pairedadmin__hook_tweaks_column_connected( $posttype );
		}
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'part_taxonomy' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'collection_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->comments__handle_default_status( $screen->post_type );
				$this->posttypes__increase_menu_order( $screen->post_type );
				$this->posttypes__media_register_headerbutton( 'collection_posttype' );
				$this->_hook_post_updated_messages( 'collection_posttype' );
				$this->_hook_paired_mainbox( $screen );
				$this->_hook_paired_listbox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );
				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );

				$this->coreadmin__hook_admin_ordering( $screen->post_type );
				$this->_hook_bulk_post_updated_messages( 'collection_posttype' );
				$this->pairedadmin__hook_tweaks_column_connected( $screen->post_type );
				$this->pairedcore__hook_sync_paired();
				$this->corerestrictposts__hook_screen_taxonomies( 'group_taxonomy' );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( $subterms && $subterms === $screen->taxonomy )
				$this->filter_string( 'parent_file', sprintf( 'edit.php?post_type=%s', $this->constant( 'collection_posttype' ) ) );

			if ( 'edit-tags' == $screen->base ) {

				$this->_hook_paired_taxonomy_bulk_actions( $screen->post_type, $screen->taxonomy );

			} else if ( 'post' == $screen->base ) {

				$this->_metabox_remove_subterm( $screen, $subterms );
				$this->_hook_paired_pairedbox( $screen );
				$this->_hook_paired_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_paired_store_metabox( $screen->post_type );
				$this->paired__hook_tweaks_column( $screen->post_type, 12 );
				$this->paired__hook_screen_restrictposts();
				$this->postmeta__hook_meta_column_row( $screen->post_type, [
					// 'in_collection_order',
					'in_collection_title',
					'in_collection_subtitle',
					'in_collection_collaborator',
				] );
			}
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );

		$this->modulelinks__hook_calendar_linked_post( $screen );
	}

	public function widgets_init()
	{
		register_widget( __NAMESPACE__.'\\Widgets\\CollectionPoster' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_for( 'meta', 'collection_posttype' );
		$this->add_posttype_fields_supported();

		$this->filter( 'prep_meta_row', 2, 12, 'module', $this->base );
		$this->filter( 'meta_field', 7, 9, FALSE, $this->base );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'collection_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function insert_cover( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		ModuleTemplate::postImage( [
			'size' => WordPress\Media::getAttachmentImageDefaultSize( $this->constant( 'collection_posttype' ), NULL, 'medium' ),
			'link' => 'attachment',
		] );
	}

	public function prep_meta_row_module( $value, $field_key = NULL, $field = [], $raw = NULL )
	{
		switch ( $field_key ) {
			/* translators: `%s`: count placeholder */
			case 'in_collection_order': return WordPress\Strings::getCounted( $raw ?: $value, _x( 'Order in Collection: %s', 'Display', 'geditorial-collect' ) );
		}

		return $value;
	}

	// @REF: `Template::getMetaField()`
	public function meta_field( $meta, $field, $post, $args, $raw, $field_args, $context )
	{
		switch ( $field ) {

			case 'total_items':
				return sprintf( Helper::noopedCount( trim( $raw ), gEditorial\Info::getNoop( 'item' ) ),
					Core\Number::format( trim( $raw ) ) );
		}

		return $meta;
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
			_x( 'Collection Tools', 'Header', 'geditorial-collect' ) );

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
		if ( ! $this->posttype_overview_render_table( 'collection_posttype', $uri, $sub ) )
			return gEditorial\Info::renderNoReportsAvailable();
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'paired',
			$this->constant( 'collection_posttype' ),
			$this->constant( 'collection_paired' ),
			array_merge( [
				'post_id'     => NULL,
				'posttypes'   => $this->posttypes(),
				'order_cb'    => NULL,                    // NULL for default ordering by meta
				'orderby'     => 'order',                 // order by meta
				'order_order' => 'in_collection_order',   // meta field for ordering
			], (array) $atts ),
			$content,
			$this->constant( 'main_shortcode', $tag ),
			$this->key
		);
	}

	public function group_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'assigned',
			$this->constant( 'collection_posttype' ),
			$this->constant( 'group_taxonomy' ),
			array_merge( [
				'post_id' => NULL,
			], (array) $atts ),
			$content,
			$this->constant( 'group_shortcode' )
		);
	}

	public function poster_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$type = $this->constant( 'collection_posttype' );
		$args = [
			'size' => WordPress\Media::getAttachmentImageDefaultSize( $type, NULL, 'medium' ),
			'type' => $type,
			'echo' => FALSE,
		];

		if ( is_singular( $args['type'] ) )
			$args['id'] = NULL;

		else if ( is_singular() )
			$args['id'] = 'paired';

		if ( ! $html = ModuleTemplate::postImage( array_merge( $args, (array) $atts ) ) )
			return $content;

		return gEditorial\ShortCode::wrap( $html,
			$this->constant( 'poster_shortcode' ),
			array_merge( [ 'wrap' => TRUE ], (array) $atts )
		);
	}
}
