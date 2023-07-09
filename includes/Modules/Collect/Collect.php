<?php namespace geminorum\gEditorial\Modules\Collect;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\WordPress;

class Collect extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\PairedAdmin;

	public static function module()
	{
		return [
			'name'   => 'collect',
			'title'  => _x( 'Collect', 'Modules: Collect', 'geditorial' ),
			'desc'   => _x( 'Create and use Collections of Posts', 'Modules: Collect', 'geditorial' ),
			'icon'   => 'star-filled',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'multiple_instances',
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
				$this->settings_supports_option( 'collection_cpt', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'collection_cpt' => 'collection',
			'collection_tax' => 'collections',
			'group_tax'      => 'collection_group',
			'part_tax'       => 'collection_part',

			'collection_shortcode' => 'collection',
			'group_shortcode'      => 'collection-group',
			'poster_shortcode'     => 'collection-poster',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'collection_tax' => 'star-filled',
				'group_tax'      => 'clipboard',
				'part_tax'       => 'exerpt-view',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'collection_cpt' => _n_noop( 'Collection', 'Collections', 'geditorial-collect' ),
				'collection_tax' => _n_noop( 'Collection', 'Collections', 'geditorial-collect' ),
				'group_tax'      => _n_noop( 'Group', 'Groups', 'geditorial-collect' ),
				'part_tax'       => _n_noop( 'Part', 'Parts', 'geditorial-collect' ),
			],
			'labels' => [
				'collection_cpt' => [
					'featured_image' => _x( 'Poster Image', 'Label: Featured Image', 'geditorial-collect' ),
				],
				'collection_tax' => [
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
			'collection_cpt' => [
				'metabox_title' => _x( 'The Collection', 'Label: MetaBox Title', 'geditorial-collect' ),
				'listbox_title' => _x( 'In This Collection', 'Label: MetaBox Title', 'geditorial-collect' ),
			],
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			$this->constant( 'collection_cpt' ) => [
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
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'collection_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'group_tax', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'collection_cpt' );

		$this->paired_register_objects( 'collection_cpt', 'collection_tax', 'part_tax' );

		$this->register_shortcode( 'collection_shortcode' );
		$this->register_shortcode( 'group_shortcode' );
		$this->register_shortcode( 'poster_shortcode' );

		if ( is_admin() )
			return;

		$this->_hook_paired_exclude_from_subterm();
		$this->_hook_paired_override_term_link();
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'collection_tax' ) ) ) {

			if ( $post_id = $this->paired_get_to_post_id( get_queried_object(), 'collection_cpt', 'collection_tax' ) )
				Core\WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_tax( $this->constant( 'group_tax' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_groups', FALSE ) )
				Core\WordPress::redirect( $redirect, 301 );

		} else if ( is_post_type_archive( $this->constant( 'collection_cpt' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				Core\WordPress::redirect( $redirect, 301 );

		} else if ( is_singular( $this->constant( 'collection_cpt' ) ) ) {

			if ( $this->get_setting( 'insert_cover' ) )
				add_action( $this->base.'_content_before',
					[ $this, 'insert_cover' ],
					$this->get_setting( 'insert_priority', -50 )
				);
		}
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save_posttype( 'collection_cpt' ) )
			$this->_hook_paired_sync_primary_posttype();
	}

	public function setup_restapi()
	{
		$this->_hook_paired_sync_primary_posttype();
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'part_tax' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'collection_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'wp_insert_post_data', 2, 9, 'menu_order' );
				$this->filter( 'get_default_comment_status', 3 );

				$this->_hook_post_updated_messages( 'collection_cpt' );
				$this->_hook_paired_mainbox( $screen );
				$this->_hook_paired_listbox( $screen );
				$this->_hook_paired_sync_primary_posttype();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->_hook_screen_restrict_taxonomies();

				$this->action_module( 'meta', 'column_row', 3 );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );

				$this->_hook_admin_ordering( $screen->post_type );
				$this->_hook_bulk_post_updated_messages( 'collection_cpt' );
				$this->_hook_paired_sync_primary_posttype();
				$this->_hook_paired_tweaks_column_attr();
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( $subterms && $subterms === $screen->taxonomy )
				$this->filter_string( 'parent_file', sprintf( 'edit.php?post_type=%s', $this->constant( 'collection_cpt' ) ) );

			if ( 'edit-tags' == $screen->base ) {

				$this->_hook_paired_taxonomy_bulk_actions( $screen->post_type, $screen->taxonomy );

			} else if ( 'post' == $screen->base ) {

				$this->_metabox_remove_subterm( $screen, $subterms );
				$this->_hook_paired_pairedbox( $screen );
				$this->_hook_paired_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_screen_restrict_paired();
				$this->_hook_paired_store_metabox( $screen->post_type );
				$this->paired__hook_tweaks_column( $screen->post_type, 12 );

				$this->action_module( 'meta', 'column_row', 3 );

				if ( $subterms )
					$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );

		if ( Settings::isDashboard( $screen ) )
			$this->filter_module( 'calendar', 'post_row_title', 4, 12 );
	}

	protected function paired_get_paired_constants()
	{
		return [ 'collection_cpt', 'collection_tax', 'part_tax' ];
	}

	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [ 'group_tax' ];
	}

	public function widgets_init()
	{
		register_widget( __NAMESPACE__.'\\Widgets\\CollectionPoster' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'collection_cpt' ) );
		$this->add_posttype_fields_supported();

		$this->filter( 'prep_meta_row', 2, 12, 'module', $this->base );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'collection_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function insert_cover( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		ModuleTemplate::postImage( [
			'size' => WordPress\Media::getAttachmentImageDefaultSize( $this->constant( 'collection_cpt' ), NULL, 'medium' ),
			'link' => 'attachment',
		] );
	}

	public function prep_meta_row_module( $value, $field_key = NULL, $field = [], $raw = NULL )
	{
		switch ( $field_key ) {
			/* translators: %s: count placeholder */
			case 'in_collection_order': return WordPress\Strings::getCounted( $raw ?: $value, _x( 'Order in Collection: %s', 'Display', 'geditorial-collect' ) );
		}

		return $value;
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );
				$this->paired_tools_handle_tablelist( 'collection_cpt', 'collection_tax' );
			}

			Scripts::enqueueThickBox();
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		return $this->paired_tools_render_tablelist( 'collection_cpt', 'collection_tax', NULL, _x( 'Collect Tools', 'Header', 'geditorial-collect' ) );
	}

	protected function render_tools_html_after( $uri, $sub )
	{
		$this->paired_tools_render_card( 'collection_cpt', 'collection_tax' );
	}

	public function collection_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts( 'paired',
			$this->constant( 'collection_cpt' ),
			$this->constant( 'collection_tax' ),
			array_merge( [
				'posttypes'   => $this->posttypes(),
				'order_cb'    => NULL, // NULL for default ordering by meta
				'orderby'     => 'order', // order by meta
				'order_order' => 'in_collection_order', // meta field for ordering
			], (array) $atts ),
			$content,
			$this->constant( 'collection_shortcode', $tag ),
			$this->key
		);
	}

	public function group_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return Shortcode::listPosts( 'assigned',
			$this->constant( 'collection_cpt' ),
			$this->constant( 'group_tax' ),
			$atts,
			$content,
			$this->constant( 'group_shortcode' )
		);
	}

	public function poster_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$type = $this->constant( 'collection_cpt' );
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

		return ShortCode::wrap( $html,
			$this->constant( 'poster_shortcode' ),
			array_merge( [ 'wrap' => TRUE ], (array) $atts )
		);
	}
}
