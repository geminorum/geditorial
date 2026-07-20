<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

class CommonSettings extends gEditorial\Service
{

	public static function thrift_mode( $description = NULL ): array
	{
		return [
			'field'       => 'thrift_mode',
			'type'        => 'disabled',
			'title'       => _x( 'Thrift Mode', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Tries to be more careful with system resources!', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function debug_mode( $description = NULL ): array
	{
		return [
			'field'       => 'debug_mode',
			'type'        => 'disabled',
			'title'       => _x( 'Debug Mode', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Tries to figure out what happens behind the curtains!', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function editor_button( $description = NULL ): array
	{
		return [
			'field'       => 'editor_button',
			'title'       => _x( 'Editor Button', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => '1',
		];
	}

	public static function quick_newpost( $description = NULL ): array
	{
		return [
			'field'       => 'quick_newpost',
			'title'       => _x( 'Quick New Post', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function admin_displaystates( $description = NULL, $default = 0 ): array
	{
		return [
			'field'       => 'admin_displaystates',
			'title'       => _x( 'Display States', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Appends assigned data as post state on post edit screen.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	public static function widget_support( $description = NULL ): array
	{
		return [
			'field'       => 'widget_support',
			'title'       => _x( 'Default Widgets', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function shortcode_support( $description = NULL ): array
	{
		return [
			'field'       => 'shortcode_support',
			'title'       => _x( 'Default Shortcodes', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function tabs_support( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'tabs_support',
			'title'       => _x( 'Tabs Support', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => $default ?? '1',
		];
	}

	public static function tab_title( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'tab_title',
			'type'        => 'text',
			'title'       => _x( 'Tab Title', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Template for the custom tab title. Leave empty to use defaults.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?: '',
			'placeholder' => $default ?: '',
		];
	}

	public static function tab_priority( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'tab_priority',
			'type'        => 'priority',
			'title'       => _x( 'Tab Priority', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? sprintf(
				/* translators: `%s`: zero placeholder */
				_x( 'Priority of the custom tab. Leave at %s to use defaults.', 'Setting Description', 'geditorial-admin' ),
				Core\HTML::code( '0' )
			),
			'default' => $default ?: 0,
		];
	}

	public static function woocommerce_support( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'woocommerce_support',
			'title'       => _x( 'WooCommerce Support', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? '',
			'default'     => $default ?? '0',
		];
	}

	public static function buddybress_support( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'buddybress_support',
			'title'       => _x( 'BuddyPress Support', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? '',
			'default'     => $default ?? '0',
		];
	}

	public static function avatar_support( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'avatar_support',
			'title'       => _x( 'Avatar Support', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? '',
			'default'     => $default ?? '0',
		];
	}

	public static function thumbnail_support( $description = NULL ): array
	{
		return [
			'field'       => 'thumbnail_support',
			'title'       => _x( 'Default Image Sizes', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function thumbnail_fallback( $description = NULL ): array
	{
		return [
			'field'       => 'thumbnail_fallback',
			'title'       => _x( 'Thumbnail Fallback', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Sets the parent post thumbnail image as fallback for the child post.', 'Setting Description', 'geditorial-admin' ),
			'default'     => '0',
		];
	}

	public static function legacy_migration( $description = NULL ): array
	{
		return [
			'field'       => 'legacy_migration',
			'title'       => _x( 'Legacy Migration', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Imports metadata from legacy plugin system.', 'Setting Description', 'geditorial-admin' ),
			'default'     => '0',
		];
	}

	public static function assignment_dock( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'assignment_dock',
			'title'       => _x( 'Assignment Dock', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Select to use advanced assignment UI on edit post screen.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function metabox_advanced( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'metabox_advanced',
			'title'       => _x( 'Advanced Meta-Box', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Select to use advanced meta-box UI on edit post screen.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function show_in_quickedit( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'show_in_quickedit',
			'title'       => _x( 'Show in Quick-Edit', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Whether to show the taxonomy in the quick/bulk edit panel.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function show_in_navmenus( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'show_in_navmenus',
			'title'       => _x( 'Show in Navigation', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Makes available for selection in navigation menus.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function autolink_terms( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'autolink_terms',
			'title'       => _x( 'Auto-link Terms', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Tries to linkify the terms in the content.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function selectmultiple_term( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'selectmultiple_term',
			'title'       => _x( 'Multiple Terms', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Whether to assign multiple terms in edit panel.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function assign_default_term( $description = NULL ): array
	{
		return [
			'field'       => 'assign_default_term',
			'title'       => _x( 'Assign Default Term', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Applies the fallback default term from primary taxonomy.', 'Setting Description', 'geditorial-admin' ),
			'default'     => '0',
		];
	}

	public static function multiple_instances( $description = NULL ): array
	{
		return [
			'field'       => 'multiple_instances',
			'title'       => _x( 'Multiple Instances', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => '0',
		];
	}

	public static function comment_status( $description = NULL ): array
	{
		return [
			'field'       => 'comment_status',
			'type'        => 'select',
			'title'       => _x( 'Comment Status', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Determines the default status of the new post comments.', 'Setting Description', 'geditorial-admin' ),
			'default'     => 'closed',
			'values'      => [
				'open'   => _x( 'Open', 'Setting Option', 'geditorial-admin' ),
				'closed' => _x( 'Closed', 'Setting Option', 'geditorial-admin' ),
			],
		];
	}

	public static function post_status( $description = NULL ): array
	{
		return [
			'field'       => 'post_status',
			'type'        => 'select',
			'title'       => _x( 'Post Status', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => 'pending',
			'values'      => Core\Arraay::stripByKeys( WordPress\Status::get(), [
				'future',
				'auto-draft',
				'inherit',
				'trash',
			] ),
		];
	}

	public static function post_type( $description = NULL ): array
	{
		return [
			'field'       => 'post_type',
			'type'        => 'select',
			'title'       => _x( 'Post Type', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => 'post',
			'values'      => WordPress\PostType::get( 2, [ 'show_ui' => TRUE ] ),
			'exclude'     => [ 'attachment', 'wp_theme' ],
		];
	}

	public static function insert_content( $description = NULL ): array
	{
		return [
			'field'       => 'insert_content',
			'type'        => 'select',
			'title'       => _x( 'Insert in Content', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Outputs automatically in the content.', 'Setting Description', 'geditorial-admin' ),
			'default'     => 'none',
			'values'      => [
				'none'   => _x( 'No', 'Setting Option', 'geditorial-admin' ),
				'before' => _x( 'Before', 'Setting Option', 'geditorial-admin' ),
				'after'  => _x( 'After', 'Setting Option', 'geditorial-admin' ),
			],
		];
	}

	public static function insert_content_enabled( $description = NULL ): array
	{
		return array_merge( self::insert_content( $description ), [
			'type'    => 'enabled',
			'values'  => [],
			'default' => '',
		] );
	}

	public static function insert_cover( $description = NULL ): array
	{
		return [
			'field'       => 'insert_cover',
			'title'       => _x( 'Insert Cover', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
		];
	}

	// NOTE: DEPRECATED: USE: `settings_insert_priority_option()`
	public static function insert_priority( $description = NULL ): array
	{
		return [
			'field'       => 'insert_priority',
			'type'        => 'priority',
			'title'       => _x( 'Insert Priority', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => '10',
		];
	}

	public static function before_content( $description = NULL ): array
	{
		return [
			'field'       => 'before_content',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'Before Content', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? sprintf(
				/* translators: `%s`: code placeholder */
				_x( 'Adds %s before start of all the supported post-types.', 'Setting Description', 'geditorial-admin' ),
				Core\HTML::code( 'HTML' )
			),
		];
	}

	public static function after_content( $description = NULL ): array
	{
		return [
			'field'       => 'after_content',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'After Content', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? sprintf(
				/* translators: `%s`: code placeholder */
				_x( 'Adds %s after end of all the supported post-types.', 'Setting Description', 'geditorial-admin' ),
				Core\HTML::code( 'HTML' )
			),
		];
	}

	public static function admin_ordering( $description = NULL ): array
	{
		return [
			'field'       => 'admin_ordering',
			'title'       => _x( 'Ordering', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Enhances item ordering on admin edit pages.', 'Setting Description', 'geditorial-admin' ),
			'default'     => '1',
		];
	}

	public static function admin_restrict( $description = NULL ): array
	{
		return [
			'field'       => 'admin_restrict',
			'title'       => _x( 'List Restrictions', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Enhances restrictions on admin edit pages.', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function admin_columns( $description = NULL ): array
	{
		return [
			'field'       => 'admin_columns',
			'title'       => _x( 'List Columns', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Enhances columns on admin edit pages.', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function admin_bulkactions( $description = NULL ): array
	{
		return [
			'field'       => 'admin_bulkactions',
			'title'       => _x( 'Bulk Actions', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Enhances bulk actions on admin edit pages.', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function admin_rowactions( $description = NULL ): array
	{
		return [
			'field'       => 'admin_rowactions',
			'title'       => _x( 'Row Actions', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Enhances row actions on admin edit pages.', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function adminbar_summary( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'adminbar_summary',
			'title'       => _x( 'Adminbar Summary', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Summary for the current item as a node in admin-bar.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '1',
		];
	}

	public static function adminbar_tools( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'adminbar_tools',
			'title'       => _x( 'Adminbar Tools', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Enabeles enhancement tools on the admin-bar.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function dashboard_widgets( $description = NULL ): array
	{
		return [
			'field'       => 'dashboard_widgets',
			'title'       => _x( 'Dashboard Widgets', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Enhances admin dashboard with customized widgets.', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function dashboard_authors( $description = NULL ): array
	{
		return [
			'field'       => 'dashboard_authors',
			'title'       => _x( 'Dashboard Authors', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Displays author column on the dashboard widget.', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function dashboard_statuses( $description = NULL ): array
	{
		return [
			'field'       => 'dashboard_statuses',
			'title'       => _x( 'Dashboard Statuses', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Displays status column on the dashboard widget.', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function dashboard_count( $description = NULL ): array
	{
		return [
			'field'       => 'dashboard_count',
			'type'        => 'number',
			'title'       => _x( 'Dashboard Count', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Limits displaying rows of items on the dashboard widget.', 'Setting Description', 'geditorial-admin' ),
			'default'     => 10,
		];
	}

	public static function summary_scope( $description = NULL ): array
	{
		return [
			'field'       => 'summary_scope',
			'type'        => 'select',
			'title'       => _x( 'Summary Scope', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'User scope for the content summary.', 'Setting Description', 'geditorial-admin' ),
			'default'     => 'all',
			'values'      => [
				'all'     => _x( 'All Users', 'Setting Option', 'geditorial-admin' ),
				'current' => _x( 'Current User', 'Setting Option', 'geditorial-admin' ),
				'roles'   => _x( 'Within the Roles', 'Setting Option', 'geditorial-admin' ),
			],
		];
	}

	public static function summary_drafts( $description = NULL ): array
	{
		return [
			'field'       => 'summary_drafts',
			'title'       => _x( 'Include Drafts', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Include drafted items in the content summary.', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function summary_excludes( $description = NULL, $values = [], $empty = NULL ): array
	{
		return [
			'field'        => 'summary_excludes',
			'type'         => 'checkboxes-values',
			'title'        => _x( 'Summary Excludes', 'Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Selected terms will be excluded on the content summary.', 'Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no items available!', 'Empty String', 'geditorial-admin' ),
			'values'       => $values,
		];
	}

	public static function summary_parents( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'summary_parents',
			'title'       => _x( 'Summary Parents', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Displays only parent terms on the content summary.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '1',
		];
	}

	public static function public_statuses( $description = NULL, $values = [], $empty = NULL ): array
	{
		return [
			'field'        => 'public_statuses',
			'type'         => 'checkboxes-values',
			'title'        => _x( 'Public Statuses', 'Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Selected terms will be acceptable on the public content queries.', 'Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no items available!', 'Empty String', 'geditorial-admin' ),
			'values'       => $values,
		];
	}

	public static function paired_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'paired_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Paired Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can assign paired defenitions.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function paired_exclude_terms( $description = NULL, $taxonomy = 'post_tag', $empty = NULL ): array
	{
		return [
			'field'        => 'paired_exclude_terms',
			'type'         => 'checkbox-panel',
			'title'        => _x( 'Exclude Terms', 'Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Items with selected terms will be excluded form dropdown on supported post-types.', 'Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no items available!', 'Empty String', 'geditorial-admin' ),
			'values'       => WordPress\Taxonomy::listTerms( $taxonomy ),
		];
	}

	public static function paired_force_parents( $description = NULL ): array
	{
		return [
			'field'       => 'paired_force_parents',
			'title'       => _x( 'Force Parents', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Includes parents on the supported post-types.', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function paired_globalsummary( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'paired_globalsummary',
			'title'       => _x( 'Global Summary', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Includes connected main posts on the global summary for each supported item.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function display_globalsummary( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'display_globalsummary',
			'title'       => _x( 'Global Summary', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Displays connected posts on the global summary on the main post edit screen.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function paired_manage_restricted( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'paired_manage_restricted',
			'title'       => _x( 'Management Restricted', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Limits creation and deletion of the main posts to administrators.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function parents_as_views( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'parents_as_views',
			'title'       => _x( 'Parents as Views', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Prepends the parent terms to views on supported post-types.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function views_exclude_terms( $description = NULL, $taxonomy = 'post_tag', $empty = NULL ): array
	{
		return [
			'field'        => 'views_exclude_terms',
			'type'         => 'checkbox-panel',
			'title'        => _x( 'Exclude Terms', 'Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Selected terms will be excluded form views on supported post-types.', 'Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: Services\CustomTaxonomy::getLabel( $taxonomy, 'extended_no_items', 'no_terms' ),
			'values'       => WordPress\Taxonomy::listTerms( $taxonomy ),
		];
	}

	public static function force_parents( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'force_parents',
			'title'       => _x( 'Force Parents', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Includes parents when selecting the main contents.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function count_not( $description = NULL ): array
	{
		return [
			'field'       => 'count_not',
			'title'       => _x( 'Count Not', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Counts not affected items in the content summary.', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function posttype_feeds( $description = NULL ): array
	{
		return [
			'field'       => 'posttype_feeds',
			'title'       => _x( 'Feeds', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Supports feeds for the supported post-types.', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function posttype_pages( $description = NULL ): array
	{
		return [
			'field'       => 'posttype_pages',
			'title'       => _x( 'Pages', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Supports pagination on the supported post-types.', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function units_posttypes( $description = NULL, $values = NULL, $empty = NULL ): array
	{
		return [
			'field'        => 'units_posttypes',
			'type'         => 'checkboxes-values',
			'title'        => _x( 'Units Post-types', 'Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Unit Fields will be available for selected post-type.', 'Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no unit post-types available!', 'Empty String', 'geditorial-admin' ),
			'values'       => $values ?? WordPress\PostType::get( 0, [ 'show_ui' => TRUE ] ),
		];
	}

	public static function main_posttype_constant( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'main_posttype_constant',
			'type'        => 'text',
			'title'       => _x( 'Post-Type Key', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the main post-type key. Leave blank for default.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterPostTypeConstant(),
			'pattern'     => WordPress\PostType::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function main_taxonomy_constant( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'main_taxonomy_constant',
			'type'        => 'text',
			'title'       => _x( 'Taxonomy Key', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the main taxonomy key. Leave blank for default.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterTaxonomyConstant(),
			'pattern'     => WordPress\Taxonomy::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function category_taxonomy_constant( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'category_taxonomy_constant',
			'type'        => 'text',
			'title'       => _x( 'Taxonomy Key', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the main taxonomy key. Leave blank for default.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterTaxonomyConstant(),
			'pattern'     => WordPress\Taxonomy::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function status_taxonomy_constant( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'status_taxonomy_constant',
			'type'        => 'text',
			'title'       => _x( 'Status Key', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the status taxonomy key. Leave blank for default.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterTaxonomyConstant(),
			'pattern'     => WordPress\Taxonomy::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function main_shortcode_constant( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'main_shortcode_constant',
			'type'        => 'text',
			'title'       => _x( 'Shortcode Tag', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the main short-code tag. Leave blank for default.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterShortCodeConstant(),
			'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function searchform_shortcode_constant( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'searchform_shortcode_constant',
			'type'        => 'text',
			'title'       => _x( 'Search Form Shortcode Tag', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the search form short-code tag. Leave blank for default.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterShortCodeConstant(),
			'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function span_shortcode_constant( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'span_shortcode_constant',
			'type'        => 'text',
			'title'       => _x( 'Span Shortcode Tag', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the span short-code tag. Leave blank for default.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterShortCodeConstant(),
			'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function cover_shortcode_constant( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'cover_shortcode_constant',
			'type'        => 'text',
			'title'       => _x( 'Cover Shortcode Tag', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the cover short-code tag. Leave blank for default.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterShortCodeConstant(),
			'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function subterm_shortcode_constant( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'subterm_shortcode_constant',
			'type'        => 'text',
			'title'       => _x( 'Sub-Term Shortcode Tag', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the sub-term short-code tag. Leave blank for default.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterShortCodeConstant(),
			'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function connected_shortcode_constant( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'connected_shortcode_constant',
			'type'        => 'text',
			'title'       => _x( 'Connected Shortcode Tag', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the connected short-code tag. Leave blank for default.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterShortCodeConstant(),
			'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function children_shortcode_constant( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'children_shortcode_constant',
			'type'        => 'text',
			'title'       => _x( 'Children Shortcode Tag', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the children short-code tag. Leave blank for default.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterShortCodeConstant(),
			'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function primary_posttype_constant( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'primary_posttype_constant',
			'type'        => 'text',
			'title'       => _x( 'Primary Post-Type Key', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the primary post-type key. Leave blank for default.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterPostTypeConstant(),
			'pattern'     => WordPress\PostType::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function primary_taxonomy_constant( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'primary_taxonomy_constant',
			'type'        => 'text',
			'title'       => _x( 'Primary Taxonomy Key', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the primary taxonomy key. Leave blank for default.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterTaxonomyConstant(),
			'pattern'     => WordPress\Taxonomy::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function secondary_posttype_constant( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'secondary_posttype_constant',
			'type'        => 'text',
			'title'       => _x( 'Secondary Post-Type Key', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the secondary post-type key. Leave blank for default.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterPostTypeConstant(),
			'pattern'     => WordPress\PostType::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function secondary_taxonomy_constant( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'secondary_taxonomy_constant',
			'type'        => 'text',
			'title'       => _x( 'Secondary Taxonomy Key', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the secondary taxonomy key. Leave blank for default.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterTaxonomyConstant(),
			'pattern'     => WordPress\Taxonomy::NAME_INPUT_PATTERN,
			'field_class' => [ 'medium-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function subcontent_posttypes( $description = NULL, $values = NULL, $empty = NULL ): array
	{
		return [
			'field'        => 'subcontent_posttypes',
			'type'         => 'checkboxes-panel-expanded',
			'title'        => _x( 'Supported Post-types', 'Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Will be available for selected post-type.', 'Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no supported post-types available!', 'Empty String', 'geditorial-admin' ),
			'values'       => $values ?? WordPress\PostType::get( 0, [ 'show_ui' => TRUE ] ),
		];
	}

	public static function subcontent_fields( $description = NULL, $values = [], $empty = NULL, $default = TRUE ): array
	{
		return [
			'field'        => 'subcontent_fields',
			'type'         => 'checkboxes-values',
			'title'        => _x( 'Supported Fields', 'Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Determines the optional fields for each supported post-type.', 'Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no supported fields available!', 'Empty String', 'geditorial-admin' ),
			'values'       => $values,
			'default'      => $default,
		];
	}

	public static function subcontent_types( $description = NULL, $values = [], $empty = NULL, $default = TRUE ): array
	{
		return [
			'field'        => 'subcontent_types',
			'type'         => 'checkboxes-panel-expanded',
			'title'        => _x( 'Supported Types', 'Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Determines the optional types for each supported post-type.', 'Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no supported types available!', 'Empty String', 'geditorial-admin' ),
			'values'       => $values,
			'default'      => $default,
		];
	}

	public static function parent_posttypes( $description = NULL, $values = NULL, $empty = NULL ): array
	{
		return [
			'field'        => 'parent_posttypes',
			'type'         => 'checkboxes-values',
			'title'        => _x( 'Parent Post-types', 'Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Selected parents will be used on the selection box.', 'Setting Description', 'geditorial-admin' ),
			'string_empty' => $empty ?: _x( 'There are no parents available!', 'Empty String', 'geditorial-admin' ),
			'values'       => $values ?? WordPress\PostType::get( 0, [ 'show_ui' => TRUE ] ),
		];
	}

	public static function custom_archives( $description = NULL, $default = '' ): array
	{
		return [
			'field'       => 'custom_archives',
			'type'        => 'text',
			'title'       => _x( 'Custom Archives', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: _x( 'Customizes the main archives page for the content.', 'Setting Description', 'geditorial-admin' ),
			'field_class' => [ 'regular-text', 'code-text' ],
			'placeholder' => $default,
		];
	}

	public static function empty_content( $description = NULL ): array
	{
		return [
			'field'       => 'empty_content',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'Empty Content', 'Setting Title', 'geditorial-admin' ),
			'default'     => _x( 'There are no content by this title. Search again or create one.', 'Setting: Setting Default', 'geditorial-admin' ),
			'placeholder' => _x( 'There are no content by this title. Search again or create one.', 'Setting: Setting Default', 'geditorial-admin' ),
			'description' => $description ?? sprintf(
				/* translators: `%s`: zero placeholder */
				_x( 'Displays as empty content placeholder. Leave blank for default or %s to disable.', 'Setting Description', 'geditorial-admin' ),
				Core\HTML::code( '0' )
			),
		];
	}

	public static function archive_empty_items( $description = NULL ): array
	{
		return [
			'field'       => 'archive_empty_items',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'Empty Items', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Displays as empty items placeholder.', 'Setting Description', 'geditorial-admin' ),
			'default'     => _x( 'There are no contents available.', 'Setting: Setting Default', 'geditorial-admin' ),
		];
	}

	public static function archive_override( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'archive_override',
			'title'       => _x( 'Archive Override', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Overrides default template hierarchy for archive.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '1',
		];
	}

	public static function archive_title( $description = NULL, $placeholder = FALSE ): array
	{
		return [
			'field'       => 'archive_title',
			'type'        => 'text',
			'title'       => _x( 'Archive Title', 'Setting Title', 'geditorial-admin' ),
			'placeholder' => $placeholder,
			'description' => $description ?? sprintf(
				/* translators: `%s`: zero placeholder */
				_x( 'Displays as archive title. Leave blank for default or %s to disable.', 'Setting Description', 'geditorial-admin' ),
				Core\HTML::code( '0' )
			),
		];
	}

	public static function newpost_title( $description = NULL, $placeholder = FALSE ): array
	{
		return [
			'field'       => 'newpost_title',
			'type'        => 'text',
			'title'       => _x( 'New-post Title', 'Setting Title', 'geditorial-admin' ),
			'placeholder' => $placeholder,
			'description' => $description ?? sprintf(
				/* translators: `%s`: zero placeholder */
				_x( 'Displays as new-post title. Leave blank for default or %s to disable.', 'Setting Description', 'geditorial-admin' ),
				Core\HTML::code( '0' )
			),
		];
	}

	public static function archive_content( $description = NULL ): array
	{
		return [
			'field'       => 'archive_content',
			'type'        => 'textarea-quicktags',
			'title'       => _x( 'Archive Content', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? sprintf(
				/* translators: `%s`: zero placeholder */
				_x( 'Displays as archive content. Leave blank for default or %s to disable.', 'Setting Description', 'geditorial-admin' ),
				Core\HTML::code( '0' )
			),
		];
	}

	public static function archive_template( $description = NULL ): array
	{
		return [
			'field'       => 'archive_template',
			'type'        => 'select',
			'title'       => _x( 'Archive Template', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Used as page template on the archive page.', 'Setting Description', 'geditorial-admin' ),
			'none_title'  => Settings::showOptionNone(),
			'values'      => wp_get_theme()->get_page_templates(),
		];
	}

	public static function newpost_template( $description = NULL ): array
	{
		return [
			'field'       => 'newpost_template',
			'type'        => 'select',
			'title'       => _x( 'New-post Template', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Used as page template on the new-post page.', 'Setting Description', 'geditorial-admin' ),
			'none_title'  => Settings::showOptionNone(),
			'values'      => wp_get_theme()->get_page_templates(),
		];
	}

	public static function display_searchform( $description = NULL ): array
	{
		return [
			'field'       => 'display_searchform',
			'title'       => _x( 'Display Search Form', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Appends a search form to the content generated on front-end.', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function display_threshold( $description = NULL ): array
	{
		return [
			'field'       => 'display_threshold',
			'type'        => 'number',
			'title'       => _x( 'Display Threshold', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Maximum number of items to consider as a long list.', 'Setting Description', 'geditorial-admin' ),
			'default'     => '5',
		];
	}

	public static function display_perpage( $description = NULL ): array
	{
		return [
			'field'       => 'display_perpage',
			'type'        => 'number',
			'title'       => _x( 'Display Per-Page', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Total rows of items per each page of the list.', 'Setting Description', 'geditorial-admin' ),
			'default'     => 15,
		];
	}

	public static function frontend_search( $description = NULL, $default = 0 ): array
	{
		return [
			'field'       => 'frontend_search',
			'title'       => _x( 'Front-end Search', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Adds results by the information on front-end search.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	#[\Deprecated()]
	public static function posttype_viewable( $description = NULL, $default = 1 ): array
	{
		return [
			'field'       => 'posttype_viewable',
			'title'       => _x( 'Viewable Post-Type', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Determines the visibility of the main post-type.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	public static function contents_viewable( $description = NULL, $default = 1 ): array
	{
		return [
			'field'       => 'contents_viewable',
			'title'       => _x( 'Viewable Contents', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Determines whether the contents are publicly viewable.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	public static function custom_captype( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'custom_captype',
			'title'       => _x( 'Custom Capabilities', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Registers custom capability-type for the contents.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '0',
		];
	}

	public static function auto_term_parents( $description = NULL, $default = NULL ): array
	{
		return [
			'field'       => 'auto_term_parents',
			'title'       => _x( 'Auto Term Parents', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Auto-assigns parent terms on supported posts.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default ?? '1',
		];
	}

	public static function override_dates( $description = NULL, $default = 1 ): array
	{
		return [
			'field'        => 'override_dates',
			'title'        => _x( 'Override Dates', 'Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Tries to override post-date with provided date data on supported post-types.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	public static function calendar_type( $description = NULL ): array
	{
		return [
			'field'       => 'calendar_type',
			'title'       => _x( 'Default Calendar', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'type'        => 'select',
			'default'     => Core\L10n::calendar(),
			'values'      => Services\Calendars::getDefualts( TRUE ),
		];
	}

	public static function calendar_list( $description = NULL ): array
	{
		return [
			'field'       => 'calendar_list',
			'title'       => _x( 'Calendar List', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'type'        => 'checkboxes',
			'default'     => [ Core\L10n::calendar() ],
			'values'      => Services\Calendars::getDefualts( TRUE ),
		];
	}

	public static function add_audit_attribute( $description = NULL, $module = 'audit' ): array
	{
		return [
			'field'       => 'add_audit_attribute',
			'title'       => _x( 'Add Audit Attribute', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Appends an audit attribute to each item.', 'Setting Description', 'geditorial-admin' ),
			'disabled'    => ! gEditorial()->enabled( $module ),
		];
	}

	public static function supported_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'supported_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Supported Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function excluded_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'excluded_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Excluded Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function adminmenu_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'adminmenu_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Admin Menu Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function metabox_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'metabox_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Meta Box Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function adminbar_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'adminbar_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Adminbar Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?: '',
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function reports_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'reports_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Reports Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can access data reports.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function tools_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'tools_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Tools Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can access data tools.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function imports_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'imports_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Imports Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can access data imports.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	// NOTE: DEPRECATED
	public static function exports_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'exports_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Exports Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can export data entries.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function prints_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'prints_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Prints Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can print data entries.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function uploads_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'uploads_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Uploads Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can upload data into the site.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function public_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'public_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Public Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can access the links to public endpoints in the site.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function overview_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'overview_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Overview Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can view data overviews.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function manage_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'manage_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Manage Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can manage, edit and delete entry defenitions.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function assign_roles( $description = NULL, $roles = NULL, $excludes = NULL ): array
	{
		return [
			'field'       => 'assign_roles',
			'type'        => 'checkboxes',
			'title'       => _x( 'Assign Roles', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Roles that can assign entry defenitions.', 'Setting Description', 'geditorial-admin' ),
			'default'     => [],
			'exclude'     => $excludes ?? ( is_null( $roles ) ? Settings::rolesExcluded() : '' ),
			'values'      => $roles ?? WordPress\Role::get(),
		];
	}

	public static function reports_post_edit( $description = NULL, $default = 1 ): array
	{
		return [
			'field'       => 'reports_post_edit',
			'title'       => _x( 'Edit Post Reports', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Also checks for <strong>edit-post</strong> capability for <em>reports</em> roles.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	public static function assign_post_edit( $description = NULL, $default = 1 ): array
	{
		return [
			'field'       => 'assign_post_edit',
			'title'       => _x( 'Edit Post Assign', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Also checks for <strong>edit-post</strong> capability for <em>assign</em> roles.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	public static function overview_fields( $description = NULL, $fields = NULL, $empty = NULL ): array
	{
		return [
			'field'        => 'overview_fields',
			'type'         => 'checkbox-panel',
			'title'        => _x( 'Overview Fields', 'Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Whether to appear as columns on the overview.', 'Setting Description', 'geditorial-admin' ),
			'default'      => [],
			'values'       => $fields ?? [],
			'string_empty' => $empty ?? _x( 'There are no fields available!', 'Empty String', 'geditorial-admin' ),
		];
	}

	public static function overview_units( $description = NULL, $fields = NULL, $empty = NULL ): array
	{
		return [
			'field'        => 'overview_units',
			'type'         => 'checkbox-panel',
			'title'        => _x( 'Overview Units', 'Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Whether to appear as columns on the overview.', 'Setting Description', 'geditorial-admin' ),
			'default'      => [],
			'values'       => $fields ?? [],
			'string_empty' => $empty ?? _x( 'There are no units available!', 'Empty String', 'geditorial-admin' ),
		];
	}

	public static function overview_taxonomies( $description = NULL, $taxes = NULL, $empty = NULL ): array
	{
		return [
			'field'        => 'overview_taxonomies',
			'type'         => 'checkbox-panel',
			'title'        => _x( 'Overview Taxonomies', 'Setting Title', 'geditorial-admin' ),
			'description'  => $description ?? _x( 'Whether to appear as columns on the overview.', 'Setting Description', 'geditorial-admin' ),
			'default'      => [],
			'values'       => $taxes ?? [],
			'string_empty' => $empty ?? _x( 'There are no taxonomies available!', 'Empty String', 'geditorial-admin' ),
		];
	}

	public static function append_identifier_code( $description = NULL ): array
	{
		return [
			'field'       => 'append_identifier_code',
			'title'       => _x( 'Append Identifier', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Appends the identifier code field data to each item supported title.', 'Setting Description', 'geditorial-admin' ),
		];
	}

	public static function printpage_enqueue_librefonts( $description = NULL ): array
	{
		return [
			'field'       => 'printpage_enqueue_librefonts',
			'title'       => _x( 'Enqueue Libre Fonts', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Loads Libre Barcode fonts on print page html head.', 'Setting Description', 'geditorial-admin' ),
			'after'       => Settings::fieldAfterIcon( 'https://graphicore.github.io/librebarcode/' ),
		];
	}

	public static function force_sanitize( $description = NULL, $default = 0 ): array
	{
		return [
			'field'       => 'force_sanitize',
			'title'       => _x( 'Force Sanitize', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Tries to force the sanitization upon storing data.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}

	public static function restapi_restricted( $description = NULL, $default = 1 ): array
	{
		return [
			'field'       => 'restapi_restricted',
			'title'       => _x( 'Restricted API', 'Setting Title', 'geditorial-admin' ),
			'description' => $description ?? _x( 'Access Rest-API requires logged-in users.', 'Setting Description', 'geditorial-admin' ),
			'default'     => $default,
		];
	}
}
