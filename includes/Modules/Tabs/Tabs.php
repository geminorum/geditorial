<?php namespace geminorum\gEditorial\Modules\Tabs;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\WordPress;

class Tabs extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'tabs',
			'title'    => _x( 'Tabs', 'Modules: Tabs', 'geditorial-admin' ),
			'desc'     => _x( 'Extra Contents in Tabs', 'Modules: Tabs', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'segmented-nav' ],
			'access'   => 'beta',
			'keywords' => [
				'frontend',
				'bootstrap',
			],
		];
	}

	protected function get_global_settings()
	{
		$settings  = [];
		$mimetypes = $this->get_strings( 'mimetypes', 'fields' );

		$settings['posttypes_option'] = 'posttypes_option';

		foreach ( $this->list_posttypes() as $posttype_name => $posttype_label ) {

			$settings['_posttypes'][] = [
				'field' => 'posttype_'.$posttype_name.'_builtins',
				'type'  => 'checkbox-panel',
				'title' => sprintf(
					/* translators: %s: supported object label */
					_x( 'Built-in Tabs for %s', 'Setting Title', 'geditorial-tabs' ),
					'<i>'.$posttype_label.'</i>'
				),
				'description' => _x( 'Select to add pre-configured tabs for posts.', 'Setting Description', 'geditorial-tabs' ),
				'values'      => Core\Arraay::pluck( $this->_get_builtins_tabs( $posttype_name ), 'title', 'name' ),
			];
		}

		if ( ! $mimetypes )
			return $settings;

		$settings['_general'] = [
			'insert_content_enabled',
			[
				'field'       => 'attachment_summary_mimetypes',
				'type'        => 'checkboxes-values',
				'title'       => _x( 'Attachment Mime-types', 'Setting Title', 'geditorial-tabs' ),
				'description' => _x( 'Defines the mime-types on attachment summary tab on front-end.', 'Setting Description', 'geditorial-tabs' ),
				'values'      => $mimetypes ?: FALSE,
			],
		];

		return $settings;
	}

	protected function get_global_strings()
	{
		$strings = [
			'fields' => [
				'mimetypes' => [
					'application/pdf'      => _x( 'PDF', 'Mime-Type', 'geditorial-tabs' ),
					'text/csv'             => _x( 'CSV', 'Mime-Type', 'geditorial-tabs' ),
					'application/msword'   => _x( 'Microsoft Word', 'Mime-Type', 'geditorial-tabs' ),
					'application/epub+zip' => _x( 'ePub', 'Mime-Type', 'geditorial-tabs' ),
				],
			],
		];

		return $strings;
	}

	// TODO: tab: post content
	// TODO: tab: post excerpt
	// TODO: tab: post terms
	// FIXME: override registered tab titles
	private function _get_builtins_tabs( $posttype = NULL )
	{
		$tabs = [];

		if ( $posttype && post_type_supports( $posttype, 'meta_fields' ) )
			$tabs[] = [
				'name'        => 'meta_summary',
				'title'       => _x( 'Meta', 'Tab Title', 'geditorial-tabs' ),
				'description' => _x( 'Meta Summary of the post.', 'Tab Description', 'geditorial-tabs' ),
				'callback'    => [ $this, 'callback_post_meta_summary' ],
				'viewable'    => [ $this, 'viewable_post_meta_summary' ],
				'priority'    => 10,
			];

		if ( gEditorial()->enabled( 'attachments' ) )
			$tabs[] = [
				'name'        => 'attachment_summary',
				'title'       => _x( 'Attachments', 'Tab Title', 'geditorial-tabs' ),
				'description' => _x( 'Attachment Summary of the post.', 'Tab Description', 'geditorial-tabs' ),
				'callback'    => [ $this, 'callback_post_attachment_summary' ],
				'viewable'    => [ $this, 'viewable_post_attachment_summary' ],
				'priority'    => 20,
			];

		return $this->filters( 'builtins_tabs', $tabs, $posttype );
	}

	protected function posttypes_excluded( $extra = [] )
	{
		return $this->filters( 'posttypes_excluded',
			Settings::posttypesExcluded(
				get_post_types( [
					'public' => FALSE,
				], 'names', 'or' ) + $extra
			)
		);
	}

	protected function taxonomies_excluded( $extra = [] )
	{
		return $this->filters( 'taxonomies_excluded',
			Settings::taxonomiesExcluded(
				get_taxonomies( [
					'public'                              => FALSE,
					Services\Paired::PAIRED_POSTTYPE_PROP => TRUE,    // NOTE: gEditorial prop
				], 'names', 'or' ) + $extra
			)
		);
	}

	public function template_redirect()
	{
		if ( ! $this->get_setting( 'insert_content' ) )
			return;

		if ( is_embed() )
			return;

		if ( ! is_singular( $this->posttypes() ) )
			return;

		$this->current_queried = get_queried_object_id();

		add_action( $this->hook_base( 'content', 'after' ), [ $this, 'content_after' ], 50 );

		// BS5 only for now
		// $this->enqueue_asset_js();
		// $this->enqueue_styles();
	}

	public function content_after( $content )
	{
		if ( ! $this->is_content_insert( FALSE, FALSE ) )
			return;

		$this->render_post_tabs( $this->current_queried );
	}

	public function render_post_tabs( $post = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $items = $this->get_post_tabs( $post ) )
			return FALSE;

		echo $this->wrap_open( 'post-tabs' );
			ModuleTemplate::bootstrap5Tabs( $items, $post );
		echo '</div>';
	}

	public function get_post_tabs( $post = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$tabs = [];

		foreach ( $this->_get_builtins_tabs( $post->post_type ) as $tab ) {

			if ( empty( $tab['name'] ) )
				continue;

			if ( ! in_array( $tab['name'], $this->get_setting( 'posttype_'.$post->post_type.'_builtins', [] ), TRUE ) )
				continue;

			if ( array_key_exists( 'viewable', $tab ) && TRUE !== $tab['viewable'] ) {

				if ( ! $tab['viewable'] || ! is_callable( $tab['viewable'] ) )
					continue;

				if ( ! call_user_func_array( $tab['viewable'], [ $post, $tab['name'], $tab ] ) )
					continue;
			}

			$tabs[$tab['name']] = $tab;
		}

		return Core\Arraay::sortByPriority( $this->filters( 'post_tabs', $tabs, $post ), 'priority' );
	}

	public function viewable_post_meta_summary( $post = NULL, $item_name = '', $item_args = [] )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		// if ( ! post_type_supports( $post->post_type, 'meta_fields' ) )
		// 	return FALSE;

		$fields = Services\PostTypeFields::getEnabled( $post->post_type, 'meta' );

		if ( ! count( $fields ) )
			return FALSE;

		return TRUE;
	}

	public function callback_post_meta_summary( $post = NULL, $item_name = '', $item_args = [] )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		echo $this->wrap( Template::metaSummary( [
			'echo'   => FALSE,
			'id'     => $post->ID,
			'type'   => $post->post_type,
			'fields' => $this->filters( 'post_meta_summary_fields', NULL, $post ),
		] ), '-meta-summary' );
	}

	public function viewable_post_attachment_summary( $post = NULL, $item_name = '', $item_args = [] )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$attachemts = WordPress\Media::getAttachments( $post->ID,
			$this->mimetype_post_attachment_summary( $post ) );

		return (bool) count( $attachemts );
	}

	public function callback_post_attachment_summary( $post = NULL, $item_name = '', $item_args = [] )
	{
		echo $this->wrap( gEditorial()->attachments->attachments_shortcode( [
			'id'        => $post,
			'mime_type' => $this->mimetype_post_attachment_summary( $post ),
			'title'     => FALSE,
			'wrap'      => FALSE,
		] ), '-attachment-summary' );
	}

	// @SEE: `wp_post_mime_type_where()`
	public function mimetype_post_attachment_summary( $post = NULL )
	{
		$defaults = [
			'application/pdf',
		];

		return $this->filters( 'attachment_summary_mimetypes',
			$this->get_setting( 'attachment_summary_mimetypes', $defaults ),
			$post,
			$defaults
		);
	}
}
