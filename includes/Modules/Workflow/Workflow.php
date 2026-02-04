<?php namespace geminorum\gEditorial\Modules\Workflow;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Workflow extends gEditorial\Module
{
	use Internals\CoreMenuPage;

	public static function module()
	{
		return [
			'name'   => 'workflow',
			'title'  => _x( 'Workflow', 'Modules: Workflow', 'geditorial-admin' ),
			'desc'   => _x( 'Customized Workflow of Contents', 'Modules: Workflow', 'geditorial-admin' ),
			'icon'   => 'clipboard',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		$settings = [
			'posttypes_option' => 'posttypes_option',
			'_editpost' => [
				[
					'field'       => 'hide_disabled',
					'title'       => _x( 'Hide Disabled', 'Setting Title', 'geditorial-workflow' ),
					'description' => _x( 'Hides statuses disabled for each role on the dropdown.', 'Setting Description', 'geditorial-workflow' ),
				],
				[
					'field'       => 'action_time',
					'title'       => _x( 'Time Action', 'Setting Title', 'geditorial-workflow' ),
					'description' => _x( 'Displays time action on the workflow meta-box.', 'Setting Description', 'geditorial-workflow' ),
				],
				[
					'field'       => 'draft_roles',
					'type'        => 'checkbox-panel',
					'title'       => _x( 'Draft Roles', 'Setting Title', 'geditorial-workflow' ),
					'description' => _x( 'Roles that can rollback to Draft status.', 'Setting Description', 'geditorial-workflow' ),
					'values'      => $roles,
				],
			],
			'_editlist' => [
				[
					'field'       => 'status_menus',
					'title'       => _x( 'Status Menu', 'Setting Title', 'geditorial-workflow' ),
					'description' => _x( 'Adds status links to the admin submenus for each supported posttype.', 'Setting Description', 'geditorial-workflow' ),
				],
				[
					'field'       => 'display_states',
					'title'       => _x( 'Display States', 'Setting Title', 'geditorial-workflow' ),
					'description' => _x( 'Appends current status to the end of the title.', 'Setting Description', 'geditorial-workflow' ),
				],
				[
					'field'        => 'locking_statuses',
					'type'         => 'checkbox-panel',
					'title'        => _x( 'Locking Statuses', 'Setting Title', 'geditorial-workflow' ),
					'description'  => _x( 'Selected statuses will lock editing the post to their assigned roles.', 'Setting Description', 'geditorial-workflow' ),
					'string_empty' => _x( 'There are no statuses available!', 'Message', 'geditorial-workflow' ),
					'values'       => Core\Arraay::pluck( $this->get_statuses(), 'label', 'name' ),
				],
			],
		];

		return $settings;
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'custom_status',
		];
	}

	protected function get_global_strings()
	{
		return [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Custom Status', 'Custom Statuses', 'geditorial-workflow' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name' => _x( 'Statuses', 'Label: Menu Name', 'geditorial-workflow' ),
				],
			],
			'metabox' => [
				'metabox_title' => _x( 'Workflow', 'MetaBox Title', 'geditorial-workflow' ),
			],
		];
	}

	public function init()
	{
		parent::init();

		register_taxonomy(
			$this->constant( 'main_taxonomy' ),
			[],
			[
				'labels'       => $this->get_taxonomy_labels( 'main_taxonomy' ),
				'show_ui'      => $this->cuc( 'settings' ),
				'public'       => FALSE,
				'rewrite'      => FALSE,
				'meta_box_cb'  => FALSE,
				'capabilities' => [
					'manage_terms' => $this->caps['settings'],
					'edit_terms'   => $this->caps['settings'],
					'delete_terms' => $this->caps['settings'],
					'assign_terms' => $this->caps['settings'],
				],
			]
		);

		$this->register_post_statuses();

		if ( count( $this->get_setting( 'locking_statuses', [] ) ) )
			$this->filter( 'map_meta_cap', 4, 12 );
	}

	public function setup_ajax()
	{
		if ( $taxonomy = $this->is_inline_save_taxonomy( 'main_taxonomy' ) )
			$this->_edit_tags_screen( $taxonomy );
	}

	public function map_meta_cap( $caps, $cap, $user_id, $args )
	{
		switch ( $cap ) {

			case 'read_post':
			case 'read_page':
			case 'edit_post':
			case 'edit_page':
			case 'delete_post':
			case 'delete_page':
			// case 'publish_post':

				if ( ! $post = WordPress\Post::get( $args[0] ) )
					return $caps;

				if ( ! $this->posttype_supported( $post->post_type ) )
					return $caps;

				if ( ! $this->in_setting( $post->post_status, 'locking_statuses' ) )
					return $caps;

				$list = $this->get_statuses( $user_id );

				$disabled = $list[$post->post_status]->disabled ?? FALSE;

				return $disabled ? [ 'do_not_allow' ] : $caps;
		}

		return $caps;
	}

	public function admin_menu()
	{
		if ( $this->get_setting( 'status_menus' ) ) {

			$statuses = $this->get_statuses();

			foreach ( $this->posttypes() as $posttype ) {

				if ( ! WordPress\PostType::can( $posttype, 'edit_others_posts' ) )
					continue;

				$counts = wp_count_posts( $posttype );

				$menu = 'post' === $posttype
					? 'edit.php'
					: 'edit.php?post_type='.$posttype;

				foreach ( $statuses as $status ) {

					if ( $status->disabled )
						continue;

					if ( ! in_array( $posttype, $status->post_type ) )
						continue;

					if ( $counts->{$status->name} < 1 )
						continue;

					$link = sprintf( 'edit.php?post_status=%1$s&post_type=%2$s', $status->name, $posttype );

					$title = vsprintf( '%1$s <span class="awaiting-mod" data-count="%3$s"><span class="pending-count">%2$s</span></span>', [
						Core\HTML::escape( $status->label ),
						Core\Number::format( $counts->{$status->name} ),
						$counts->{$status->name},
					] );

					$GLOBALS['submenu'][$menu][] = [ $title, 'read', $link ];
				}
			}
		}

		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->_hook_parentfile_for_optionsgeneralphp();

			if ( 'edit-tags' == $screen->base )
				$this->_edit_tags_screen( $screen->taxonomy );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				$edit = WordPress\Taxonomy::edit( $this->constant( 'main_taxonomy' ), [ 'post_type' => $screen->post_type ] );
				remove_meta_box( 'submitdiv', $screen, 'side' );

				$this->class_metabox( $screen, 'mainbox' );
				add_meta_box( $this->classs( 'mainbox' ),
					$this->get_meta_box_title( $screen->post_type, $edit ),
					[ $this, 'render_mainbox_metabox' ],
					$screen,
					'side',
					'high'
				);

			} else if ( 'edit' == $screen->base ) {

				// FIXME: Temporarily hiding inline/bulk edit action
				Services\AdminScreen::disableQuickEdit( $screen );
				$this->filter_false( 'quick_edit_enabled_for_post_type' );

				if ( $this->get_setting( 'display_states' ) )
					$this->filter( 'display_post_states', 2 );
			}
		}
	}

	private function _edit_tags_screen( $taxonomy )
	{
		add_filter( 'manage_edit-'.$taxonomy.'_columns', [ $this, 'manage_columns' ] );
		// add_filter( 'manage_'.$taxonomy.'_custom_column', [ $this, 'custom_column' ], 10, 3 );
	}

	public function manage_columns( $columns )
	{
		unset( $columns['posts'] );
		return $columns;
	}

	private function get_statuses( $user_id = NULL )
	{
		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		if ( empty( $this->cache['statuses'] ) )
			$this->cache['statuses'] = [];

		if ( isset( $this->cache['statuses'][$user_id] ) )
			return $this->cache['statuses'][$user_id];

		$args = [
			'taxonomy'   => $this->constant( 'main_taxonomy' ),
			'hide_empty' => FALSE,
			'orderby'    => 'none',
			// NOTE: needs meta cache updated
		];

		$query     = new \WP_Term_Query();
		$admin     = WordPress\User::isSuperAdmin();
		$supported = $this->posttypes();
		$statuses  = [];

		foreach ( $query->query( $args ) as $term ) {

			$metas = WordPress\Term::getMeta( $term, [
				'order'     => '',
				'color'     => '',
				'posttype'  => '',
				'posttypes' => $supported,
				'viewable'  => '0',          // NOTE: `0`: Undefined, `1`: Non-Viewable, `2`: Viewable
				'roles'     => FALSE,
				'plural'    => FALSE,
				'icon'      => FALSE,
			] );

			$status = [
				'term_id'   => $term->term_id,
				'name'      => $term->slug,
				'label'     => $term->name,
				'color'     => Core\Color::validHex( $metas['color'] ) ?: '',
				'order'     => $metas['order'],
				'post_type' => $metas['posttypes'],
				'viewable'  => $metas['viewable'],
				'disabled'  => FALSE,
				'plural'    => $metas['plural'],
				'icon'      => $metas['icon'],
			];

			if ( $metas['posttype'] && in_array( $metas['posttype'], $supported, TRUE ) )
				$status['post_type'] = (array) $metas['posttype'];

			if ( ! $admin && $metas['roles'] ) {

				if ( ! WordPress\Role::has( Core\Arraay::prepString( 'administrator', $metas['roles'] ), $user_id ) )
					$status['disabled'] = TRUE;
			}

			$statuses[$term->slug] = (object) $status;
		}

		return $this->cache['statuses'][$user_id] = Core\Arraay::sortObjectByPriority( $statuses, 'order' );
	}

	private function register_post_statuses()
	{
		$builtins = get_post_stati();

		foreach ( $this->get_statuses() as $status ) {

			// bail if already registered by core
			if ( in_array( $status->name, $builtins ) )
				continue;

			$args = [
				'public'      => TRUE,
				'post_type'   => $status->post_type,
				'label'       => $status->label,
				'label_count' => Core\L10n::getNooped( $status->label.' <span class="count">(%s)</span>', $status->label.' <span class="count">(%s)</span>' ),

				'show_in_admin_all_list'    => TRUE,
				'show_in_admin_status_list' => ! $status->disabled,
			];

			register_post_status( $status->name, $args );
		}

		$this->filter( 'is_post_status_viewable', 2, 12 );
		$this->filter( 'update_post_term_count_statuses', 2, 12 );
	}

	public function is_post_status_viewable( $is_viewable, $post_status )
	{
		$statuses = $this->get_statuses();

		if ( ! array_key_exists( $post_status->name, $statuses ) )
			return $is_viewable;

		if ( ! $statuses[$post_status->name]->viewable )
			return $is_viewable;

		// NOTE: `0`: Undefined, `1`: None-Viewable, `2`: Viewable
		return '2' == $statuses[$post_status->name]->viewable;
	}

	public function update_post_term_count_statuses( $post_statuses, $taxonomy )
	{
		return array_merge( $post_statuses, wp_filter_object_list( $this->get_statuses(), [ 'viewable' => '2' ], 'and', 'name' ) );
	}

	public function display_post_states( $states, $post )
	{
		// bail if the view is set
		if ( self::req( 'post_status' ) )
			return $states;

		$statuses = $this->get_statuses();

		if ( array_key_exists( $post->post_status, $statuses ) )
			return [ $post->post_status => $statuses[$post->post_status]->label ] + $states;

		return $states;
	}

	public function render_mainbox_metabox( $post, $box )
	{
		if ( empty( $post->post_type ) )
			return; // FIXME: add notice

		$status = $post->post_status;

		if ( 'auto-draft' === $status )
			$status = 'draft';

		else if ( ! empty( $post->post_password ) )
			$status = 'password';

		echo $this->wrap_open( '-admin-metabox submitbox' );
			$this->actions( 'render_metabox', $post, $box, NULL, 'mainbox' );

			echo '<div id="minor-publishing">';

				// Hidden submit button early on so that the browser chooses
				// the right button when form is submitted with Return key
				echo '<div style="display:none;">';

					submit_button( __( 'Save' ), '', 'save' );

				echo '</div><div id="minor-publishing-actions">';

					$this->do_minor_publishing( $post, $status );

				echo '</div><div class="clear"></div>';
				echo '<div id="misc-publishing-actions">';

					$this->do_status_publishing( $post, $status );
					// $this->do_status_extra_attributes( $post, $status );

					if ( $this->get_setting( 'action_time' ) )
						$this->do_time_publishing( $post, $status );

				echo '</div><div class="clear"></div>';

			echo '</div>';
			echo '<div id="major-publishing-actions">';

				$this->do_major_publishing( $post, $status );

			echo '<div class="clear"></div></div>';

			$this->actions( 'render_metabox_after', $post, $box, 'mainbox' );
		echo '</div>';
	}

	public function do_minor_publishing( $post, $current = '' )
	{
		echo '<div id="save-action">';

			// if ( 'draft' === $current )
			// 	echo Core\HTML::tag( 'input', [
			// 		'type'  => 'submit',
			// 		'id'    => 'save-post',
			// 		'name'  => 'save',
			// 		'class' => Core\HTML::buttonClass(),
			// 		'value' => __( 'Save Draft' ),
			// 	] );

			// else if ( 'pending' === $current )
			// 	echo Core\HTML::tag( 'input', [
			// 		'type'  => 'submit',
			// 		'id'    => 'save-post',
			// 		'name'  => 'save',
			// 		'class' => Core\HTML::buttonClass(),
			// 		'value' => __( 'Save as Pending' ),
			// 	] );

			if ( ! empty( $GLOBALS['publish_callback_args']['revisions_count'] ) )
				echo Core\HTML::tag( 'a', [
					'href'  => get_edit_post_link( $GLOBALS['publish_callback_args']['revision_id'] ),
					'id'    => $this->classs( 'browse-revisions' ),
					'class' => Core\HTML::buttonClass( TRUE, [ 'hide-if-no-js', '-browse-revisions' ] ),
				], __( 'Browse revisions' ) );

			echo gEditorial\Ajax::spinner();

		echo '</div>';

		if ( WordPress\PostType::viewable( $post->post_type ) ) {

			echo '<div id="preview-action">';
				echo Core\HTML::tag( 'a', [
					'href'   => get_preview_post_link( $post ),
					'id'     => 'post-preview',
					'target' => 'wp-preview-'.$post->ID,
					'class'  => [ 'button', 'button-small', 'preview' ],
				], 'publish' === $current ? __( 'Preview Changes' ) : __( 'Preview' ) );

				echo '<input type="hidden" name="wp-preview" id="wp-preview" value="" />';
			echo '</div>';
		}

		do_action( 'post_submitbox_minor_actions', $post );
	}

	public function do_status_publishing( $post, $current = '' )
	{
		if ( empty( $current ) )
			return;

		$html  = $info = '';
		$list  = $this->get_statuses();
		$hide  = $this->get_setting( 'hide_disabled' );
		$draft = $this->role_can( 'draft' );

		if ( ! $hide || $draft )
			$html.= Core\HTML::tag( 'option', [
				'value'    => 'draft',
				'disabled' => ! $draft,
				'selected' => $current == 'draft',
			], __( 'Draft' ) );

		foreach ( $list as $status ) {

			if ( $hide && $status->disabled && $current != $status->name )
				continue;

			if ( ! in_array( $post->post_type, $status->post_type, TRUE ) )
				continue;

			$html.= Core\HTML::tag( 'option', [
				'value'    => $status->name,
				'disabled' => $status->disabled,
				'selected' => $current == $status->name,
				'style'    => $status->color ? sprintf( 'color:%s;background-color:%s', $status->color, Core\Color::lightOrDark( $status->color ) ) : FALSE,
			], Core\HTML::escape( $status->label ) );

			if ( $status->disabled )
				continue;

			if ( ! $desc = get_term_field( 'description', $status->term_id, $this->constant( 'main_taxonomy' ) ) )
				continue;

			$class = 'field-wrap -desc misc-pub-section status-'.$status->name;

			if ( $current != $status->name )
				$class.= ' hidden';

			// TODO: changes via js and `status-` class
			$info.= Core\HTML::wrap( WordPress\Strings::prepDescription( $desc, FALSE ), $class );
		}

		if ( empty( $html ) )
			return Core\HTML::desc( _x( 'There are no statuses available!', 'Message', 'geditorial-workflow' ), TRUE, 'field-wrap misc-pub-section -empty-status' );

		$html = Core\HTML::tag( 'select', [
			'id'       => $this->classs( 'post-status' ),
			'name'     => 'post_status',
			'disabled' => isset( $list[$current]->disabled ) ? $list[$current]->disabled : FALSE,
		], $html );

		$label = Core\HTML::tag( 'select', [
			'for'   => $this->classs( 'post-status' ),
			'class' => 'screen-reader-text',
		], __( 'Set status' ) );

		if ( ! empty( $list[$current]->disabled ) )
			echo '<input type="hidden" name="post_status" value="'.$current.'" />';

		echo Core\HTML::wrap( $label.$html, 'field-wrap -select misc-pub-section' ).$info;
	}

	public function do_time_publishing( $post, $current = '' )
	{
		$html = '<span id="timestamp">';
		$html.= Core\Date::get( __( 'M j, Y @ H:i' ), strtotime( $post->post_date ) );
		$html.= '</span>';

		echo Core\HTML::wrap( $html, 'field-wrap -select misc-pub-section curtime misc-pub-curtime' );

		if ( $post->post_modified == $post->post_date )
			return;

		$html = '<small id="last-edit">';
		$html.= sprintf( __( 'Last edited on %1$s at %2$s' ),
			mysql2date( __( 'F j, Y' ), $post->post_modified ),
			mysql2date( __( 'g:i a' ), $post->post_modified ) );
		$html.= '</small>';

		echo Core\HTML::wrap( $html, 'field-wrap -select misc-pub-section curtime misc-pub-curtime' );
	}

	public function do_major_publishing( $post, $current = '' )
	{
		if ( empty( $current ) )
			return;

		do_action( 'post_submitbox_start', $post );

		echo '<div id="delete-action">';

			if ( current_user_can( 'delete_post', $post->ID ) )
				echo Core\HTML::tag( 'a', [
					'href'  => get_delete_post_link( $post->ID ),
					'class' => [ 'submitdelete', 'deletion' ],
				], ! EMPTY_TRASH_DAYS ? __( 'Delete Permanently' ) : __( 'Move to Trash' ) );

		echo '</div><div id="publishing-action">';

			echo gEditorial\Ajax::spinner();

			submit_button( __( 'Submit' ), 'primary large', 'save', FALSE, [ 'id' => 'publish' ] );

		echo '</div>';
	}
}
