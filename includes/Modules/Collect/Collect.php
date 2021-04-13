<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\Templates\Collect as ModuleTemplate;

class Collect extends gEditorial\Module
{

	protected $partials = [ 'Templates' ];

	public static function module()
	{
		return [
			'name'  => 'collect',
			'title' => _x( 'Collect', 'Modules: Collect', 'geditorial' ),
			'desc'  => _x( 'Create and use Collections of Posts', 'Modules: Collect', 'geditorial' ),
			'icon'  => 'star-filled',
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
				'admin_restrict',
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
					'placeholder' => URL::home( 'archives' ),
				],
				[
					'field'       => 'redirect_groups',
					'type'        => 'url',
					'title'       => _x( 'Redirect Groups', 'Settings', 'geditorial-collect' ),
					'description' => _x( 'Redirects all group archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-collect' ),
					'placeholder' => URL::home( 'archives' ),
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
			'collection_cpt'         => 'collection',
			'collection_cpt_archive' => 'collections',
			'collection_tax'         => 'collections',
			'group_tax'              => 'collection_group',
			'part_tax'               => 'collection_part',
			'collection_shortcode'   => 'collection',
			'group_shortcode'        => 'collection-group',
			'poster_shortcode'       => 'collection-poster',
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
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'collection_cpt' => [
				'featured'         => _x( 'Poster Image', 'Posttype Featured', 'geditorial-collect' ),
				'show_option_none' => _x( '&ndash; Select Collection &ndash;', 'Select Option None', 'geditorial-collect' ),
			],
			'collection_tax' => [
				'meta_box_title' => _x( 'In This Collection', 'MetaBox Title', 'geditorial-collect' ),
			],
			'group_tax' => [
				'meta_box_title'      => _x( 'Groups', 'MetaBox Title', 'geditorial-collect' ),
				'tweaks_column_title' => _x( 'Collection Groups', 'Column Title', 'geditorial-collect' ),
			],
			'part_tax' => [
				'meta_box_title'      => _x( 'Parts', 'MetaBox Title', 'geditorial-collect' ),
				'tweaks_column_title' => _x( 'Collection Parts', 'Column Title', 'geditorial-collect' ),
				'show_option_none'    => _x( '&ndash; Select Part &ndash;', 'Select Option None', 'geditorial-collect' ),
			],
			'meta_box_title'         => _x( 'The Collection', 'MetaBox Title', 'geditorial-collect' ),
			'tweaks_column_title'    => _x( 'Collections', 'Column Title', 'geditorial-collect' ),
			'connected_column_title' => _x( 'Connected Items', 'Column Title', 'geditorial-collect' ),
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

	protected function posttypes_excluded()
	{
		return Settings::posttypesExcluded( $this->constant( 'collection_cpt' ) );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'collection_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'group_tax', [
			'hierarchical'       => TRUE, // required by `MetaBox::checklistTerms()`
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'collection_cpt' );

		$this->paired_register_objects( 'collection_cpt', 'collection_tax', 'part_tax' );

		$this->register_shortcode( 'collection_shortcode' );
		$this->register_shortcode( 'group_shortcode' );
		$this->register_shortcode( 'poster_shortcode' );

		if ( is_admin() )
			return;

		$this->filter( 'term_link', 3 );
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'collection_tax' ) ) ) {

			$term = get_queried_object();

			if ( $post_id = $this->paired_get_to_post_id( $term, 'collection_cpt', 'collection_tax' ) )
				WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_tax( $this->constant( 'group_tax' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_groups', FALSE ) )
				WordPress::redirect( $redirect, 301 );

		} else if ( is_post_type_archive( $this->constant( 'collection_cpt' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress::redirect( $redirect, 301 );

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
		if ( $this->is_inline_save( $_REQUEST, 'collection_cpt' ) )
			$this->_hook_paired_to( $_REQUEST['post_type'] );
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'part_tax' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'collection_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'wp_insert_post_data', 2, 9, 'menu_order' );
				$this->filter( 'post_updated_messages' );
				$this->filter( 'get_default_comment_status', 3 );

				$this->filter_false_module( 'meta', 'mainbox_callback', 12 );
				$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
				$this->filter_false_module( 'tweaks', 'metabox_parent' );
				remove_meta_box( 'pageparentdiv', $screen, 'side' );

				$this->class_metabox( $screen, 'mainbox' );
				add_meta_box( $this->classs( 'mainbox' ),
					$this->get_meta_box_title( 'collection_cpt', FALSE ),
					[ $this, 'render_mainbox_metabox' ],
					$screen,
					'side',
					'high'
				);

				$this->class_metabox( $screen, 'listbox' );
				add_meta_box( $this->classs( 'listbox' ),
					$this->get_meta_box_title( 'collection_tax' ),
					[ $this, 'render_listbox_metabox' ],
					$screen,
					'advanced',
					'low'
				);

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );
				$this->filter( 'bulk_post_updated_messages', 2 );

				if ( $this->get_setting( 'admin_restrict', FALSE ) ) {
					$this->action( 'restrict_manage_posts', 2, 12 );
					$this->filter( 'parse_query' );
				}

				if ( $this->get_setting( 'admin_ordering', TRUE ) )
					$this->action( 'pre_get_posts' );

				$this->action_module( 'meta', 'column_row', 3 );
				$this->action_module( 'tweaks', 'column_attr' );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}

			$this->_hook_paired_to( $screen->post_type );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				if ( $subterms )
					remove_meta_box( $subterms.'div', $screen->post_type, 'side' );

				$this->class_metabox( $screen, 'pairedbox' );
				add_meta_box( $this->classs( 'pairedbox' ),
					$this->get_meta_box_title_posttype( 'collection_cpt' ),
					[ $this, 'render_pairedbox_metabox' ],
					$screen,
					'side'
				);

				add_action( $this->hook( 'render_pairedbox_metabox' ), [ $this, 'render_metabox' ], 10, 4 );

			} else if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_restrict', FALSE ) )
					$this->action( 'restrict_manage_posts', 2, 12, 'supported' );

				$this->action_module( 'meta', 'column_row', 3 );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}

			$this->_hook_store_metabox( $screen->post_type );
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );

		if ( Settings::isDashboard( $screen ) )
			$this->filter_module( 'calendar', 'post_row_title', 4, 12 );
	}

	public function widgets_init()
	{
		$this->require_code( 'Widgets/Collection-Poster' );

		register_widget( '\\geminorum\\gEditorial\\Collect\\Widgets\\CollectionPoster' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'collection_cpt' ) );
		$this->add_posttype_fields_supported();
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'collection_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->constant( 'collection_tax' ) != $taxonomy )
			return $link;

		if ( $post_id = $this->paired_get_to_post_id( $term, 'collection_cpt', 'collection_tax' ) )
			return get_permalink( $post_id );

		return $link;
	}

	public function insert_cover( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		ModuleTemplate::postImage( [
			'size' => $this->get_image_size_key( 'collection_cpt', 'medium' ),
			'link' => 'attachment',
		] );
	}

	public function post_updated( $post_id, $post_after, $post_before )
	{
		$this->paired_do_save_to_post_update( $post_after, $post_before, 'collection_cpt', 'collection_tax' );
	}

	public function save_post( $post_id, $post, $update )
	{
		// we handle updates on another action, see : post_updated()
		if ( ! $update )
			$this->paired_do_save_to_post_new( $post, 'collection_cpt', 'collection_tax' );
	}

	public function wp_trash_post( $post_id )
	{
		$this->paired_do_trash_to_post( $post_id, 'collection_cpt', 'collection_tax' );
	}

	public function untrash_post( $post_id )
	{
		$this->paired_do_untrash_to_post( $post_id, 'collection_cpt', 'collection_tax' );
	}

	public function before_delete_post( $post_id )
	{
		$this->paired_do_before_delete_to_post( $post_id, 'collection_cpt', 'collection_tax' );
	}

	public function pre_get_posts( &$wp_query )
	{
		if ( $this->constant( 'collection_cpt' ) == $wp_query->get( 'post_type' ) ) {

			if ( $wp_query->is_admin ) {

				if ( ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', 'menu_order' );

				if ( ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', 'DESC' );
			}
		}
	}

	public function restrict_manage_posts( $posttype, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'group_tax' );
	}

	public function restrict_manage_posts_supported( $posttype, $which )
	{
		$this->do_restrict_manage_posts_posts( 'collection_tax', 'collection_cpt' );
	}

	public function parse_query( &$query )
	{
		$this->do_parse_query_taxes( $query, 'group_tax' );
	}

	public function meta_box_cb_group_tax( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, [ 'taxonomy' => $box['args']['taxonomy'], 'posttype' => $post->post_type ] );
		echo '</div>';
	}

	public function render_pairedbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

		if ( ! Taxonomy::hasTerms( $this->constant( 'collection_tax' ) ) ) {

			MetaBox::fieldEmptyPostType( $this->constant( 'collection_cpt' ) );

		} else {

			$this->actions( 'render_pairedbox_metabox', $post, $box, NULL, 'pairedbox_collection' );

			do_action( 'geditorial_meta_render_metabox', $post, $box, NULL, 'pairedbox_collection' );
		}

		echo '</div>';
	}

	public function render_metabox( $post, $box, $fields = NULL, $context = NULL )
	{
		$this->paired_do_render_metabox( $post, 'collection_cpt', 'collection_tax', 'part_tax' );
	}

	public function store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		$this->paired_do_store_metabox( $post, 'collection_cpt', 'collection_tax', 'part_tax' );
	}

	public function render_mainbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'render_metabox', $post, $box, NULL, 'mainbox' );

			do_action( 'geditorial_meta_render_metabox', $post, $box, NULL );

			MetaBox::fieldPostMenuOrder( $post );
			MetaBox::fieldPostParent( $post );

		echo '</div>';
	}

	public function render_listbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'render_listbox_metabox', $post, $box, NULL, 'listbox_collection' );

			$term = $this->paired_get_to_term( $post->ID, 'collection_cpt', 'collection_tax' );

			if ( $list = MetaBox::getTermPosts( $this->constant( 'collection_tax' ), $term, $this->posttypes() ) )
				echo $list;

			else
				HTML::desc( _x( 'No items connected!', 'Message', 'geditorial-collect' ), FALSE, '-empty' );

		echo '</div>';
	}

	public function get_linked_to_posts( $post = NULL, $single = FALSE, $published = TRUE )
	{
		return $this->paired_do_get_to_posts( 'collection_cpt', 'collection_tax', $post, $single, $published );
	}

	public function tweaks_column_attr( $post )
	{
		$posts = $this->paired_get_from_posts( $post->ID, 'collection_cpt', 'collection_tax' );
		$count = count( $posts );

		if ( ! $count )
			return;

		echo '<li class="-row -collect -connected">';

			echo $this->get_column_icon( FALSE, NULL, $this->get_column_title( 'connected', 'collection_cpt' ) );

			$posttypes = array_unique( array_map( function( $r ){
				return $r->post_type;
			}, $posts ) );

			$args = [ $this->constant( 'collection_tax' ) => $post->post_name ];

			if ( empty( $this->cache_posttypes ) )
				$this->cache_posttypes = PostType::get( 2 );

			echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

			$list = [];

			foreach ( $posttypes as $posttype )
				$list[] = HTML::tag( 'a', [
					'href'   => WordPress::getPostTypeEditLink( $posttype, 0, $args ),
					'title'  => _x( 'View the connected list', 'Title Attr', 'geditorial-collect' ),
					'target' => '_blank',
				], $this->cache_posttypes[$posttype] );

			echo Helper::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

		echo '</li>';
	}

	public function display_meta_row( $value, $key = NULL, $field = [] )
	{
		switch ( $key ) {
			/* translators: %s: count placeholder */
			case 'in_collection_order': return Helper::getCounted( $value, _x( 'Order in Collection: %s', 'Display', 'geditorial-collect' ) );
		}

		return parent::display_meta_row( $value, $key, $field );
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'collection_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'collection_cpt', $counts ) );
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
			$this->constant( 'collection_shortcode' )
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
		$args = [
			'size' => $this->get_image_size_key( 'collection_cpt', 'medium' ),
			'type' => $this->constant( 'collection_cpt' ),
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

	// FIXME: update from magazine module
	protected function render_tools_html( $uri, $sub )
	{
		HTML::h3( _x( 'Collect Tools', 'Header', 'geditorial-collect' ) );

		echo '<table class="form-table">';
		echo '<tr><th scope="row">'._x( 'From Terms', 'Tools', 'geditorial-collect' ).'</th><td>';
		echo $this->wrap_open_buttons( '-tools' );

		Settings::submitButton( 'collection_tax_check',
			_x( 'Check Terms', 'Button', 'geditorial-collect' ), TRUE );

		Settings::submitButton( 'collection_post_create',
			_x( 'Create Collection Posts', 'Button', 'geditorial-collect' ) );

		Settings::submitButton( 'collection_post_connect',
			_x( 'Re-Connect Posts', 'Button', 'geditorial-collect' ) );

		Settings::submitButton( 'collection_store_order',
			_x( 'Store Orders', 'Button', 'geditorial-collect' ) );

		Settings::submitButton( 'collection_tax_delete',
			_x( 'Delete Terms', 'Button', 'geditorial-collect' ), 'danger', TRUE );


		echo '</p>';

		if ( ! empty( $_POST ) && isset( $_POST['collection_tax_check'] ) ) {
			echo '<br />';

			HTML::tableList( [
				'_cb'     => 'term_id',
				'term_id' => Tablelist::columnTermID(),
				'name'    => Tablelist::columnTermName(),
				'paired'   => [
					'title'    => _x( 'Paired Collection Post', 'Table Column', 'geditorial-collect' ),
					'callback' => function( $value, $row, $column, $index ){

						if ( $post_id = $this->paired_get_to_post_id( $row, 'collection_cpt', 'collection_tax', FALSE ) )
							return Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';

						return Helper::htmlEmpty();
					},
				],
				'slugged'   => [
					'title' => _x( 'Same Slug Collection Post', 'Table Column', 'geditorial-collect' ),
					'callback' => function( $value, $row, $column, $index ){

						if ( $post_id = PostType::getIDbySlug( $row->slug, $this->constant( 'collection_cpt' ) ) )
							return Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';

						return Helper::htmlEmpty();
					},
				],
				'count' => [
					'title'    => _x( 'Count', 'Table Column', 'geditorial-collect' ),
					'callback' => function( $value, $row, $column, $index ){
						if ( $post_id = PostType::getIDbySlug( $row->slug, $this->constant( 'collection_cpt' ) ) )
							return Number::format( $this->paired_get_from_posts( $post_id, 'collection_cpt', 'collection_tax', TRUE ) );
						return Number::format( $row->count );
					},
				],
				'description' => Tablelist::columnTermDesc(),
			], Taxonomy::getTerms( $this->constant( 'collection_tax' ), FALSE, TRUE ), [
				'empty' => HTML::warning( _x( 'No Terms Found!', 'Table Empty', 'geditorial-collect' ), FALSE ),
			] );
		}

		HTML::desc( _x( 'Check for collection terms and create corresponding collection posts.', 'Message', 'geditorial-collect' ) );

		echo '</td></tr>';
		echo '</table>';
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( Tablelist::isAction( 'collection_post_create', TRUE ) ) {

					$terms = Taxonomy::getTerms( $this->constant( 'collection_tax' ), FALSE, TRUE );
					$posts = [];

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! isset( $terms[$term_id] ) )
							continue;

						$post_id = PostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( 'collection_cpt' ) );

						if ( FALSE !== $post_id )
							continue;

						$posts[] = PostType::newPostFromTerm(
							$terms[$term_id],
							$this->constant( 'collection_tax' ),
							$this->constant( 'collection_cpt' ),
							gEditorial()->user( TRUE )
						);
					}

					WordPress::redirectReferer( [
						'message' => 'created',
						'count'   => count( $posts ),
					] );

				} else if ( Tablelist::isAction( 'collection_store_order', TRUE )
					|| Tablelist::isAction( 'collection_store_start', TRUE ) ) {

					if ( ! gEditorial()->enabled( 'meta' ) )
						WordPress::redirectReferer( 'wrong' );

					$count = 0;

					$field_key = isset( $_POST['collection_store_order'] )
						? 'in_collection_order'
						: 'in_collection_page_start';

					foreach ( $_POST['_cb'] as $term_id ) {
						foreach ( $this->paired_get_from_posts( NULL, 'collection_cpt', 'collection_tax', FALSE, $term_id ) as $post ) {

							if ( $post->menu_order )
								continue;

							if ( $order = gEditorial()->meta->get_postmeta_field( $post->ID, $field_key ) ) {
								wp_update_post( [
									'ID'         => $post->ID,
									'menu_order' => $order,
								] );
								$count++;
							}
						}
					}

					WordPress::redirectReferer( [
						'message' => 'ordered',
						'count'   => $count,
					] );

				} else if ( Tablelist::isAction( 'collection_post_connect', TRUE ) ) {

					$terms = Taxonomy::getTerms( $this->constant( 'collection_tax' ), FALSE, TRUE );
					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! isset( $terms[$term_id] ) )
							continue;

						$post_id = PostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( 'collection_cpt' ) );

						if ( FALSE === $post_id )
							continue;

						if ( $this->paired_set_to_term( $post_id, $terms[$term_id], 'collection_cpt', 'collection_tax' ) )
							$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'updated',
						'count'   => $count,
					] );

				} else if ( Tablelist::isAction( 'collection_tax_delete', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( $this->paired_remove_to_term( NULL, $term_id, 'collection_cpt', 'collection_tax' ) ) {

							$deleted = wp_delete_term( $term_id, $this->constant( 'collection_tax' ) );

							if ( $deleted && ! is_wp_error( $deleted ) )
								$count++;
						}
					}

					WordPress::redirectReferer( [
						'message' => 'deleted',
						'count'   => $count,
					] );
				}
			}
		}
	}
}
