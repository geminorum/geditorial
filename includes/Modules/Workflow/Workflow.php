<?php namespace geminorum\gEditorial\Modules\Workflow;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\Color;
use geminorum\gEditorial\Core\L10n;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\User;

class Workflow extends gEditorial\Module
{

	private $statuses = [];

	public static function module()
	{
		return [
			'name'  => 'workflow',
			'title' => _x( 'Workflow', 'Modules: Workflow', 'geditorial' ),
			'desc'  => _x( 'Customized Workflow of Contents', 'Modules: Workflow', 'geditorial' ),
			'icon'  => 'clipboard',
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles( [ 'administrator', 'subscriber' ] );

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
					'values'       => wp_list_pluck( $this->get_statuses(), 'label', 'name' ),
				],
			],
		];

		return $settings;
	}

	protected function get_global_constants()
	{
		return [
			'status_tax' => 'custom_status',
		];
	}

	protected function get_global_strings()
	{
		return [
			'noops' => [
				'status_tax' => _n_noop( 'Custom Status', 'Custom Statuses', 'geditorial-workflow' ),
			],
			'labels' => [
				'status_tax' => [
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
			$this->constant( 'status_tax' ),
			[],
			[
				'labels'       => $this->get_taxonomy_labels( 'status_tax' ),
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

	public function init_ajax()
	{
		if ( $taxonomy = $this->is_inline_save_taxonomy( 'status_tax' ) )
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

				if ( ! $post = PostType::getPost( $args[0] ) )
					return $caps;

				if ( ! $this->posttype_supported( $post->post_type ) )
					return $caps;

				if ( ! $this->in_setting( $post->post_status, 'locking_statuses' ) )
					return $caps;

				$list = $this->get_statuses( $user_id );

				$disabled = isset( $list[$post->post_status]->disabled )
					? $list[$post->post_status]->disabled
					: FALSE;

				return $disabled ? [ 'do_not_allow' ] : $caps;
		}

		return $caps;
	}

	public function admin_menu()
	{
		if ( $this->get_setting( 'status_menus' ) ) {

			$statuses = $this->get_statuses();

			foreach ( $this->posttypes() as $posttype ) {

				$object = PostType::object( $posttype );

				if ( ! current_user_can( $object->cap->edit_others_posts ) )
				// if ( ! current_user_can( $object->cap->edit_posts ) )
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
						HTML::escape( $status->label ),
						Number::format( $counts->{$status->name} ),
						$counts->{$status->name},
					] );

					$GLOBALS['submenu'][$menu][] = [ $title, 'read', $link ];
				}
			}
		}

		if ( $tax = get_taxonomy( $this->constant( 'status_tax' ) ) )
			add_options_page(
				HTML::escape( $tax->labels->menu_name ),
				HTML::escape( $tax->labels->menu_name ),
				$tax->cap->manage_terms,
				'edit-tags.php?taxonomy='.$tax->name
			);
	}

	public function get_adminmenu( $page = TRUE, $extra = [] )
	{
		return FALSE;
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'status_tax' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'options-general.php' );

			if ( 'edit-tags' == $screen->base )
				$this->_edit_tags_screen( $screen->taxonomy );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				$edit = WordPress::getEditTaxLink( $this->constant( 'status_tax' ), FALSE, [ 'post_type' => $screen->post_type ] );
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

				// FIXME: temporarily hiding inline/bulk edit action
				// @REF: https://core.trac.wordpress.org/ticket/19343
				if ( is_post_type_hierarchical( $screen->post_type ) )
					$this->filter( 'page_row_actions', 2 );
				else
					$this->filter( 'post_row_actions', 2 );

				add_filter( 'bulk_actions-'.$screen->id, [ $this, 'bulk_actions' ] );

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

		if ( ! empty( $this->statuses[$user_id] ) )
			return $this->statuses[$user_id];

		$args = [
			'taxonomy'   => $this->constant( 'status_tax' ),
			'hide_empty' => FALSE,
			'orderby'    => 'none',
			// NOTE: needs meta cache updated
		];

		$query     = new \WP_Term_Query();
		$admin     = User::isSuperAdmin();
		$supported = $this->posttypes();
		$statuses  = [];

		foreach ( $query->query( $args ) as $term ) {

			$metas = Taxonomy::getTermMeta( $term, [
				'order'     => '',
				'color'     => '',
				'posttype'  => '',
				'posttypes' => $supported,
				'roles'     => FALSE,
			] );

			$status = [
				'term_id'   => $term->term_id,
				'name'      => $term->slug,
				'label'     => $term->name,
				'color'     => Color::validHex( $metas['color'] ) ?: '',
				'order'     => $metas['order'],
				'post_type' => $metas['posttypes'],
				'disabled'  => FALSE,
				'viewable'  => FALSE, // FIXME
				'plural'    => FALSE, // FIXME
				'icon'      => FALSE, // FIXME
			];

			if ( $metas['posttype'] && in_array( $metas['posttype'], $supported, TRUE ) )
				$status['post_type'] = (array) $metas['posttype'];

			if ( ! $admin && $metas['roles'] ) {

				if ( ! User::hasRole( array_merge( [ 'administrator' ], (array) $metas['roles'] ), $user_id ) )
					$status['disabled'] = TRUE;
			}

			$statuses[$term->slug] = (object) $status;
		}

		return $this->statuses[$user_id] = Arraay::sortObjectByPriority( $statuses, 'order' );
	}

	// TODO: filter `is_post_status_viewable` @since WP 5.9.0
	// TODO: filter `update_post_term_count_statuses` @since WP 5.7.0
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
				'label_count' => L10n::getNooped( $status->label.' <span class="count">(%s)</span>', $status->label.' <span class="count">(%s)</span>' ),

				'show_in_admin_all_list'    => TRUE,
				'show_in_admin_status_list' => ! $status->disabled,
			];

			register_post_status( $status->name, $args );
		}
	}

	public function page_row_actions( $actions, $post )
	{
		unset( $actions['inline hide-if-no-js'] );
		return $actions;
	}

	public function post_row_actions( $actions, $post )
	{
		unset( $actions['inline hide-if-no-js'] );
		return $actions;
	}

	public function bulk_actions( $actions )
	{
		unset( $actions['edit'] );
		return $actions;
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
			// 	echo HTML::tag( 'input', [
			// 		'type'  => 'submit',
			// 		'id'    => 'save-post',
			// 		'name'  => 'save',
			// 		'class' => [ 'button', 'button-small' ],
			// 		'value' => __( 'Save Draft' ),
			// 	] );

			// else if ( 'pending' === $current )
			// 	echo HTML::tag( 'input', [
			// 		'type'  => 'submit',
			// 		'id'    => 'save-post',
			// 		'name'  => 'save',
			// 		'class' => [ 'button', 'button-small' ],
			// 		'value' => __( 'Save as Pending' ),
			// 	] );

			if ( ! empty( $GLOBALS['publish_callback_args']['revisions_count'] ) )
				echo HTML::tag( 'a', [
					'href'  => get_edit_post_link( $GLOBALS['publish_callback_args']['revision_id'] ),
					'id'    => $this->classs( 'browse-revisions' ),
					'class' => [ 'button', 'button-small', 'hide-if-no-js', '-browse-revisions' ],
				], __( 'Browse revisions' ) );

			echo Ajax::spinner();

		echo '</div>';

		if ( PostType::viewable( $post->post_type ) ) {

			echo '<div id="preview-action">';
				echo HTML::tag( 'a', [
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
			$html.= HTML::tag( 'option', [
				'value'    => 'draft',
				'disabled' => ! $draft,
				'selected' => $current == 'draft',
			], __( 'Draft' ) );

		foreach ( $list as $status ) {

			if ( $hide && $status->disabled && $current != $status->name )
				continue;

			if ( ! in_array( $post->post_type, $status->post_type, TRUE ) )
				continue;

			$html.= HTML::tag( 'option', [
				'value'    => $status->name,
				'disabled' => $status->disabled,
				'selected' => $current == $status->name,
				'style'    => $status->color ? sprintf( 'color:%s;background-color:%s', $status->color, Color::lightOrDark( $status->color ) ) : FALSE,
			], HTML::escape( $status->label ) );

			if ( $status->disabled )
				continue;

			if ( ! $desc = get_term_field( 'description', $status->term_id, $this->constant( 'status_tax' ) ) )
				continue;

			$class = 'field-wrap -desc misc-pub-section status-'.$status->name;

			if ( $current != $status->name )
				$class.= ' hidden';

			// TODO: changes via js and `status-` class
			$info.= HTML::wrap( Helper::prepDescription( $desc, FALSE ), $class );
		}

		if ( empty ( $html ) )
			return HTML::desc( _x( 'There are no statuses available!', 'Message', 'geditorial-workflow' ), TRUE, 'field-wrap misc-pub-section -empty-status' );

		$html = HTML::tag( 'select', [
			'id'       => $this->classs( 'post-status' ),
			'name'     => 'post_status',
			'disabled' => isset( $list[$current]->disabled ) ? $list[$current]->disabled : FALSE,
		], $html );

		$label = HTML::tag( 'select', [
			'for'   => $this->classs( 'post-status' ),
			'class' => 'screen-reader-text',
		], __( 'Set status' ) );

		if ( ! empty( $list[$current]->disabled ) )
			echo '<input type="hidden" name="post_status" value="'.$current.'" />';

		echo HTML::wrap( $label.$html, 'field-wrap -select misc-pub-section' ).$info;
	}

	public function do_time_publishing( $post, $current = '' )
	{
		$html = '<span id="timestamp">';
		$html.= date_i18n( __( 'M j, Y @ H:i' ), strtotime( $post->post_date ) );
		$html.= '</span>';

		echo HTML::wrap( $html, 'field-wrap -select misc-pub-section curtime misc-pub-curtime' );

		if ( $post->post_modified == $post->post_date )
			return;

		$html = '<small id="last-edit">';
		$html.= sprintf( __( 'Last edited on %1$s at %2$s' ),
			mysql2date( __( 'F j, Y' ), $post->post_modified ),
			mysql2date( __( 'g:i a' ), $post->post_modified ) );
		$html.= '</small>';

		echo HTML::wrap( $html, 'field-wrap -select misc-pub-section curtime misc-pub-curtime' );
	}

	public function do_major_publishing( $post, $current = '' )
	{
		if ( empty( $current ) )
			return;

		do_action( 'post_submitbox_start', $post );

		echo '<div id="delete-action">';

			if ( current_user_can( 'delete_post', $post->ID ) )
				echo HTML::tag( 'a', [
					'href'  => get_delete_post_link( $post->ID ),
					'class' => [ 'submitdelete', 'deletion' ],
				], ! EMPTY_TRASH_DAYS ? __( 'Delete Permanently' ) : __( 'Move to Trash' ) );

		echo '</div><div id="publishing-action">';

			echo Ajax::spinner();

			submit_button( __( 'Submit' ), 'primary large', 'save', FALSE, [ 'id' => 'publish' ] );

		echo '</div>';
	}
}
