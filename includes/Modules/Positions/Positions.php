<?php namespace geminorum\gEditorial\Modules\Positions;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Positions extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\CoreAdmin;
	use Internals\CoreMenuPage;
	use Internals\CoreRowActions;
	use Internals\FramePage;
	use Internals\MetaBoxMain;
	use Internals\MetaBoxSupported;
	use Internals\PairedCore;
	use Internals\PairedMetaBox;
	use Internals\PostMeta;
	use Internals\RestAPI;
	use Internals\SubContents;

	protected $deafults = [
		'multiple_instances' => TRUE,
		'subterms_support'   => TRUE,
	];

	public static function module()
	{
		return [
			'name'     => 'positions',
			'title'    => _x( 'Positions', 'Modules: Positions', 'geditorial-admin' ),
			'desc'     => _x( 'Content Position Management', 'Modules: Positions', 'geditorial-admin' ),
			'icon'     => 'database-view',
			'access'   => 'beta',
			'keywords' => [
				'paired',
				'subcontent',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'_subcontent' => [
				'subcontent_fields' => [ NULL, $this->subcontent_get_fields_for_settings() ],
			],
			'_roles' => [
				'manage_roles'  => [ _x( 'Roles that can manage position information.', 'Setting Description', 'geditorial-positions' ), $roles ],
				'reports_roles' => [ _x( 'Roles that can view position information.', 'Setting Description', 'geditorial-positions' ), $roles ],
				'assign_roles'  => [ _x( 'Roles that can assign position information.', 'Setting Description', 'geditorial-positions' ), $roles ],
			],
			'_editpost' => [
				'admin_rowactions',
			],
			'_supports' => [
				'shortcode_support',
				$this->settings_supports_option( 'primary_posttype', [
					'title',
					// 'editor',
					'custom-fields',
					'excerpt',
				] ),
			],
			'posttypes_option' => 'posttypes_option',
			'_general'         => [
				'paired_exclude_terms' => [
					NULL,
					$this->constant( 'primary_taxonomy' ),
					$this->get_taxonomy_label( 'primary_taxonomy', 'no_terms' ),
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype' => 'position_profile',
			'primary_paired'   => 'position_profiles',
			'primary_subterm'  => 'position_profiles_type',
			'primary_taxonomy' => 'position_profiles_category',
			'flag_taxonomy'    => 'position_profiles_flag',

			'restapi_namespace' => 'content-positions',
			'subcontent_type'   => 'content_positions',
			'subcontent_status' => 'private',
			'main_shortcode'    => 'content-positions',

			'term_empty_subcontent_data' => 'position-data-empty',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype' => _n_noop( 'Position Profile', 'Position Profiles', 'geditorial-positions' ),
				'primary_paired'   => _n_noop( 'Position Profile', 'Position Profiles', 'geditorial-positions' ),
				'primary_subterm'  => _n_noop( 'Position Profile Type', 'Position Profile Types', 'geditorial-positions' ),
				'primary_taxonomy' => _n_noop( 'Position Profile Category', 'Position Profile Categories', 'geditorial-positions' ),
				'flag_taxonomy'    => _n_noop( 'Position Profile Flag', 'Position Profile Flags', 'geditorial-positions' ),
			],
			'labels' => [
				'flag_taxonomy' => [
					'menu_title' => _x( 'Flags', 'Menu Title', 'geditorial-papered' ),
				],
			],
			'fields' => [
				'subcontent' => [
					'label'      => _x( 'Position Title', 'Field Label: `label`', 'geditorial-positions' ),
					'fullname'   => _x( 'Incumbent', 'Field Label: `fullname`', 'geditorial-positions' ),
					'identity'   => _x( 'Identity', 'Field Label: `identity`', 'geditorial-positions' ),
					'phone'      => _x( 'Contact', 'Field Label: `phone`', 'geditorial-positions' ),
					'datestring' => _x( 'Date', 'Field Label: `datestring`', 'geditorial-positions' ),
					'desc'       => _x( 'Description', 'Field Label: `desc`', 'geditorial-positions' ),
				],
			],
		];

		$strings['notices'] = [
			'empty'    => _x( 'There is no position information available!', 'Notice', 'geditorial-positions' ),
			'noaccess' => _x( 'You do not have the necessary permission to manage the position data.', 'Notice', 'geditorial-positions' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Positions', 'MetaBox Title', 'geditorial-positions' ),
			// 'metabox_action' => _x( 'Directory', 'MetaBox Action', 'geditorial-positions' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'mainbutton_title' => _x( 'Positions of %1$s', 'Button Title', 'geditorial-positions' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Positions of %2$s', 'Button Text', 'geditorial-positions' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'rowaction_title' => _x( 'Positions of %1$s', 'Action Title', 'geditorial-positions' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'rowaction_text'  => _x( 'Positions', 'Action Text', 'geditorial-positions' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'columnrow_title' => _x( 'Positions of %1$s', 'Row Title', 'geditorial-positions' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'columnrow_text'  => _x( 'Positions', 'Row Text', 'geditorial-positions' ),
		];

		return $strings;
	}

	protected function subcontent_get_data_mapping()
	{
		return array_merge( $this->subcontent_base_data_mapping(), [
			'comment_content' => 'desc',    // `text`
			'comment_agent'   => 'label',   // `varchar(255)`
			'comment_karma'   => 'order',   // `int(11)`

			'comment_author'       => 'fullname',     // `tinytext`
			'comment_author_url'   => 'phone',        // `varchar(200)`
			'comment_author_email' => 'identity',     // `varchar(100)`
			'comment_author_IP'    => 'datestring',   // `varchar(100)`
		] );
	}

	protected function subcontent_define_searchable_fields()
	{
		if ( $human = gEditorial()->constant( 'personage', 'primary_posttype' ) )
			return [ 'fullname' => [ $human ] ];

		return [];
	}

	protected function subcontent_define_unique_fields()
	{
		return [
			'identity',
		];
	}

	protected function subcontent_define_required_fields()
	{
		return [
			'label',
			'fullname',
		];
	}

	protected function paired_get_paired_constants()
	{
		return [
			'primary_posttype',
			'primary_paired',
			'primary_subterm',
			'primary_taxonomy',
			FALSE,  // hierarchical!
			TRUE,   // private
		];
	}

	public function after_setup_theme()
	{
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		// FIXME: handle caps
		$this->register_taxonomy( 'primary_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
			'show_in_menu'       => FALSE,
		], 'primary_posttype' );

		// FIXME: handle caps
		$this->register_taxonomy( 'flag_taxonomy', [
			'hierarchical' => TRUE,
			'public'       => FALSE,
			'rewrite'      => FALSE,
			'show_in_menu' => FALSE,
			'meta_box_cb'  => '__checklist_terms_callback',
		], 'primary_posttype', [
			'custom_captype' => TRUE,
		] );

		$this->paired_register( [
			'public'       => FALSE,
			'rewrite'      => FALSE,
			'show_in_menu' => FALSE, // NOTE: better to be `FALSE`, adding parent-slug will override the `mainpage`
		] );

		$this->filter_module( 'audit', 'auto_audit_save_post', 5, 12, 'subcontent' );
		$this->register_shortcode( 'main_shortcode' );

		if ( ! is_admin() )
			return;

		$this->_do_check_requests();
		$this->filter_module( 'tabloid', 'post_summaries', 4, 40, 'subcontent' );
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'primary_subterm' )
			: FALSE;

		if ( $this->constant( 'primary_taxonomy' ) == $screen->taxonomy ) {

			$this->_hook_parentfile_for_optionsgeneralphp();

		} else if ( $this->constant( 'flag_taxonomy' ) == $screen->taxonomy ) {

			$this->_hook_parentfile_for_optionsgeneralphp();

		} else if ( $screen->post_type == $this->constant( 'primary_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->_hook_parentfile_for_optionsgeneralphp();

				$this->comments__handle_default_status( $screen->post_type );
				$this->_hook_post_updated_messages( 'primary_posttype' );
				$this->_hook_paired_mainbox( $screen );
				$this->_hook_paired_listbox( $screen );
				$this->pairedcore__hook_sync_paired();

				if ( $this->role_can( [ 'reports', 'assign' ] ) )
					$this->_hook_general_supportedbox( $screen, NULL, 'advanced', 'low', '-subcontent-grid-metabox' );

				$this->subcontent_do_enqueue_asset_js( $screen );

			} else if ( 'edit' == $screen->base ) {

				$this->pairedcore__hook_sync_paired();

				if ( $this->role_can( [ 'reports', 'assign' ] ) ) {

					if ( ! $this->rowactions__hook_mainlink_for_post( $screen->post_type, 18, 'subcontent' ) )
						$this->coreadmin__hook_tweaks_column_row( $screen->post_type, 18, 'subcontent' );

					gEditorial\Scripts::enqueueColorBox();
				}
			}

		} else if ( 'post' == $screen->base ) {

			if ( $this->posttype_supported( $screen->post_type ) ) {

				$this->_metabox_remove_subterm( $screen, $subterms );

				if ( $this->role_can( 'manage' ) ) {
					$this->_hook_paired_pairedbox( $screen );
					$this->_hook_paired_store_metabox( $screen->post_type );
				}

				if ( $this->role_can( [ 'reports', 'assign' ] ) )
					$this->_hook_general_supportedbox( $screen, NULL, 'advanced', 'low', '-subcontent-grid-metabox' );

				$this->subcontent_do_enqueue_asset_js( $screen );
			}

		} else if ( 'edit' == $screen->base ) {

			if ( $this->posttype_supported( $screen->post_type ) ) {

				if ( $this->role_can( 'manage' ) ) {
					$this->_hook_paired_store_metabox( $screen->post_type );
				}

				// $this->pairedimports__hook_append_import_button( $screen->post_type );
				// $this->pairedrowactions__hook_for_supported_posttypes( $screen );
				// $this->paired__hook_tweaks_column( $screen->post_type, 8 );
				// $this->paired__hook_screen_restrictposts( FALSE, 9 );

				if ( $this->role_can( [ 'reports', 'assign' ] ) ) {

					if ( ! $this->rowactions__hook_mainlink_for_post( $screen->post_type, 18, 'checkprofile' ) )
						$this->coreadmin__hook_tweaks_column_row( $screen->post_type, 18, 'checkprofile' );

					gEditorial\Scripts::enqueueColorBox();
				}
			}
		}
	}

	protected function rowaction_get_mainlink_for_post_checkprofile( $post )
	{
		if ( ! $this->get_linked_to_posts( $post, TRUE ) )
			return FALSE;

		return $this->rowaction_get_mainlink_for_post_subcontent( $post );
	}

	protected function tweaks_column_row_checkprofile( $post, $before, $after, $module )
	{
		if ( ! $this->get_linked_to_posts( $post, TRUE ) )
			return FALSE;

		return $this->tweaks_column_row_subcontent( $post, $before, $after, $module );
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'supportedbox';

		$this->subcontent_render_metabox_data_grid( $object, $context );

		if ( $object->post_type == $this->constant( 'primary_posttype' ) ) {

			// NOTE: user already have the cap to edit.

			echo Core\HTML::wrap( $this->framepage_get_mainlink_for_post( $object, [
				'context' => 'mainbutton',
				'target'  => 'grid',
			] ), 'field-wrap -buttons' );

		} else if ( $this->posttype_supported( $object->post_type ) ) {

			$buttons = [];
			$manage  = $this->role_can( 'manage' );
			$assign  = $this->role_can( 'assign' );
			$count   = $this->subcontent_get_data_count( $object );
			$profile = $this->get_linked_to_posts( $object, TRUE ); // single profile only

			if ( $count && ( $manage || $assign ) )
				$buttons[] = $this->framepage_get_mainlink_for_post( $object, [
					'context' => 'mainbutton',
					'target'  => 'grid',
				] );

			if ( $profile && ! $count && $manage ) {

				$label = sprintf(
					/* translators: `%1$s`: icon markup, `%2$s`: profile post title */
					_x( '%1$s Mount: %2$s', 'Button Label', 'geditorial-positions' ),
					Services\Icons::get( 'database-add' ),
					WordPress\Post::title( $profile, '' )
				);

				$buttons[] = Core\HTML::tag( 'a', [
					'href'  => $this->_get_mount_link( $object, $profile, $context ),
					'class' => Core\HTML::buttonClass( FALSE, '-button-icon' ),
					'title' => _x( 'Click to Mount the Position Profile', 'Button Title', 'geditorial-positions' ),
				], $label );
			}

			if ( $manage && $count ) {

				$label = sprintf(
					/* translators: `%1$s`: icon markup, `%2$s`: profile post title */
					_x( '%1$s Clear: %2$s', 'Button Label', 'geditorial-positions' ),
					Services\Icons::get( 'database-remove' ),
					WordPress\Post::title( $profile, '' )
				);

				$buttons[] = Core\HTML::tag( 'a', array_merge( [
					'href'  => $this->_get_clear_link( $object, $profile, $context ),
					'class' => Core\HTML::buttonClass( FALSE, [ '-button-icon', '-button-danger' ] ),
					'title' => _x( 'Click to Clear the Position Profile', 'Button Title', 'geditorial-positions' ),
				], gEditorial\Settings::getButtonConfirm() ), $label );
			}

			if ( count( $buttons ) )
				echo Core\HTML::wrap( join( ' ', $buttons ), 'field-wrap -buttons' );

			else if ( ! $profile )
				Core\HTML::desc( _x( 'No Position Profile assigned to this content.', 'Notice', 'geditorial-positions' ), TRUE, '-empty' );

			else
				echo $this->get_notice_for_noaccess();
		}
	}

	private function _get_mount_link( $post, $profile, $context = FALSE, $extra = [] )
	{
		return add_query_arg( array_merge( [
			'action'  => $this->classs( 'mount', 'profile' ),
			'target'  => $post->ID,
			'ref'     => $profile->ID,
			'context' => $context,
		], $extra ), get_admin_url() );
	}

	private function _get_clear_link( $post, $profile, $context = FALSE, $extra = [] )
	{
		return add_query_arg( array_merge( [
			'action'  => $this->classs( 'clear', 'profile' ),
			'target'  => $post->ID,
			'ref'     => $profile->ID,
			'context' => $context,
		], $extra ), get_admin_url() );
	}

	// NOTE: only fires on admin
	private function _do_check_requests()
	{
		if ( ! $action = self::req( 'action' ) )
			return;

		switch ( $action ) {

			case $this->classs( 'mount', 'profile' ):

				$reference = self::req( 'ref', NULL );
				$target    = self::req( 'target', NULL );
				// $context   = self::req( 'context', 'default' );

				if ( $reference && $target ) {

					if ( FALSE !== ( $count = $this->subcontent_clone_data_all( $reference, $target, TRUE ) ) )
						WordPress\Redirect::doURL( WordPress\Post::edit( $target ), [
							'message' => 'created',
							'count'   => $count,
						] );
				}

				WordPress\Redirect::doReferer( 'wrong' );

			case $this->classs( 'clear', 'profile' ):

				// $reference = self::req( 'ref', NULL );
				$target    = self::req( 'target', NULL );
				// $context   = self::req( 'context', 'default' );

				if ( $target ) {

					if ( FALSE !== ( $count = $this->subcontent_delete_data_all( $target, TRUE ) ) )
						WordPress\Redirect::doURL( WordPress\Post::edit( $target ), [
							'message' => 'deleted',
							'count'   => $count,
						] );
				}

				WordPress\Redirect::doReferer( 'wrong' );
		}
	}

	public function admin_menu()
	{
		$this->_hook_menu_posttype( 'primary_posttype', 'options-general.php' );
		$this->_hook_menu_taxonomy( 'primary_taxonomy', 'options-general.php' );
		$this->_hook_menu_taxonomy( 'flag_taxonomy', 'options-general.php' );

		if ( $this->role_can( [ 'assign', 'reports' ] ) )
			$this->_hook_submenu_adminpage( 'overview', 'exist' );
	}

	public function load_submenu_adminpage()
	{
		$this->_load_submenu_adminpage( 'overview' );

		if ( $post = WordPress\Post::get( self::req( 'linked', FALSE ) ) ) {

			if ( $post->post_type == $this->constant( 'primary_posttype' ) ) {

				$enabled = $this->subcontent_get_fields( 'manage' );

				$args = [
					'frozen'     => FALSE,
					'searchable' => [], // reset!
					'required'   => [ 'label' ],
					'readonly'   => array_values( Core\Arraay::stripByValue( array_keys( $enabled ), 'label' ) ),   // all but `Position Title`
					'strings'    => [
						'message' => _x( 'Here you can define the position profile by titles with custom ordering.', 'Javascript String', 'geditorial-positions' ),
					],
				];

			} else {

				$args = [
					'frozen' => TRUE,  // Expanding only for primary post-types.
				];
			}
		}

		$this->subcontent_do_enqueue_app( $args );
	}

	public function render_submenu_adminpage()
	{
		$this->subcontent_do_render_iframe_content(
			'overview',
			/* translators: `%s`: post title */
			_x( 'Position Grid for %s', 'Page Title', 'geditorial-positions' ),
			/* translators: `%s`: post title */
			_x( 'Positions Overview for %s', 'Page Title', 'geditorial-positions' )
		);
	}

	public function setup_restapi()
	{
		$this->subcontent_restapi_register_routes();
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return $this->subcontent_do_main_shortcode( $atts, $content, $tag );
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Services\Modulation::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_subcontent_data' ) => _x( 'Empty Position Data', 'Default Term: Audit', 'geditorial-positions' ),
		] ) : $terms;
	}

	// NOTE: refines count based on sub-content rows that contain `fullname` aka `comment_author`
	protected function subcontent_get_data_count( $parent = NULL, $context = NULL, $extra = [] )
	{
		global $wpdb;

		if ( ! $post = WordPress\Post::get( $parent ) )
			return FALSE;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return $this->subcontent_query_data_count( $post, $extra );

		$query = $wpdb->prepare(
			"SELECT COUNT(*)
			FROM {$wpdb->comments}
			WHERE comment_post_ID = %d
			AND comment_type IN ('%s')
			AND comment_author != ''",
			$post->ID,
			$this->subcontent_get_comment_type()
		);

		return $wpdb->get_var( $query );
	}
}
