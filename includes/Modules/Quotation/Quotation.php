<?php namespace geminorum\gEditorial\Modules\Quotation;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Quotation extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;
	use Internals\MetaBoxList;

	public static function module()
	{
		return [
			'name'     => 'quotation',
			'title'    => _x( 'Quotation', 'Modules: Quotation', 'geditorial-admin' ),
			'desc'     => _x( 'Snippets from Content', 'Modules: Quotation', 'geditorial-admin' ),
			'icon'     => 'format-quote',
			'access'   => 'beta',
			'keywords' => [
				'quote',
				'cptmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'comment_status',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'main_posttype', [
					'title',
					'editor',
					'excerpt',
					'author',
					'comments',
					'custom-fields',
					'editorial-roles',
				] ),
			],
			'_constants' => [
				'main_posttype_constant'     => [ NULL, 'quote' ],
				'category_taxonomy_constant' => [ NULL, 'quote_topic' ],
				'main_shortcode_constant'    => [ NULL, 'quote-topic' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_posttype'     => 'quote',
			'category_taxonomy' => 'quote_topic',
			'main_shortcode'    => 'quote-topic',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_posttype'     => _n_noop( 'Quote', 'Quotes', 'geditorial-quotation' ),
				'category_taxonomy' => _n_noop( 'Topic', 'Topics', 'geditorial-quotation' ),

				/* translators: `%s`: count number */
				'main_posttype_count' => _n_noop( '%s Quotation', '%s Quotations', 'geditorial-quotation' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'listbox_title' => _x( 'Quotations from &ldquo;%1$s&rdquo; %2$s', 'MetaBox: `listbox_title`', 'geditorial-quotation' ),
		];

		$strings['misc'] = [
			'main_posttype' => [
				'menu_name'      => _x( 'Quotation', 'Posttype Menu', 'geditorial-quotation' ),
				'meta_box_title' => _x( 'Quotation', 'MetaBox Title', 'geditorial-quotation' ),
			],
			'topic_column_title'  => _x( 'Topic', 'Column Title', 'geditorial-quotation' ),
			'column_icon_title'   => _x( 'Quotation Sources', 'Misc: `column_icon_title`', 'geditorial-quotation' ),
			'parent_post_empty'   => _x( 'Unknown Source', 'Misc: `parent_post_empty`', 'geditorial-quotation' ),
			'post_children_empty' => _x( 'Not Quoted', 'Misc: `post_children_empty`', 'geditorial-quotation' ),
		];

		return $strings;
	}

	public function get_global_fields()
	{
		$rtl = Core\L10n::rtl();

		return [
			'meta' => [
				$this->constant( 'main_posttype' ) => [
					'parent_post_id' => [
						'title'       => _x( 'Parent', 'Field Title', 'geditorial-quotation' ),
						'description' => _x( 'Parent post of the Quote', 'Field Description', 'geditorial-quotation' ),
						'type'        => 'parent_post',
						'posttype'    => $this->posttypes(),
					],
					// NOTE: DEPRECATED
					'quotation_pages' => [
						'title'       => _x( 'Pages', 'Field Title', 'geditorial-quotation' ),
						'description' => _x( 'Source Pages of the Quote', 'Field Description', 'geditorial-quotation' ),
						'type'        => 'text',
						'icon'        => 'admin-page',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
					],
					'quotation_pagestart' => [
						'title'       => _x( 'Page Start', 'Field Title', 'geditorial-quotation' ),
						'description' => _x( 'Source Start Page of the Quote', 'Field Description', 'geditorial-quotation' ),
						'type'        => 'number',
						'icon'        => $rtl ? 'controls-skipforward' : 'controls-skipback',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
					],
					'quotation_pageend' => [
						'title'       => _x( 'Page End', 'Field Title', 'geditorial-quotation' ),
						'description' => _x( 'Source End Page of the Quote', 'Field Description', 'geditorial-quotation' ),
						'type'        => 'number',
						'icon'        => $rtl ? 'controls-skipback' : 'controls-skipforward',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
					],
					'quotation_section' => [
						'title'       => _x( 'Section', 'Field Title', 'geditorial-quotation' ),
						'description' => _x( 'Source Section of the Quote', 'Field Description', 'geditorial-quotation' ),
						'type'        => 'number',
						'quickedit'   => TRUE,
					],
					'quotation_volume' => [
						'title'       => _x( 'Volume', 'Field Title', 'geditorial-quotation' ),
						'description' => _x( 'Source Volume of the Quote', 'Field Description', 'geditorial-quotation' ),
						'type'        => 'number',
						'icon'        => 'book-alt',
						'quickedit'   => TRUE,
					],
					'quotation_reference' => [
						'title'       => _x( 'Reference', 'Field Title', 'geditorial-quotation' ),
						'description' => _x( 'Reference to the Quote Source', 'Field Description', 'geditorial-quotation' ),
						'type'        => 'note',
					],
					'quotation_desc' => [
						'title'       => _x( 'Description', 'Field Title', 'geditorial-quotation' ),
						'description' => _x( 'Description of the Quote', 'Field Description', 'geditorial-quotation' ),
						'type'        => 'note',
					],
				],
			],
		];
	}

	protected function posttypes_excluded( $extra = [] )
	{
		return $this->filters( 'posttypes_excluded',
			gEditorial\Settings::posttypesExcluded( $extra + [
				$this->constant( 'main_posttype' ),
			], $this->keep_posttypes )
		);
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'main_posttype' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'category_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
			'show_in_nav_menus'  => TRUE,
			'meta_box_cb'        => NULL,
		], 'main_posttype', [

		] );

		$this->register_posttype( 'main_posttype', [], [

		] );

		$this->register_shortcode( 'main_shortcode' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_for( 'meta', 'main_posttype' );
		$this->filter( 'prep_meta_row', 2, 12, 'module', $this->base );
		$this->filter( 'meta_field', 7, 9, FALSE, $this->base );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'main_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->action_module( 'meta', 'render_metabox', 4, 1 );
				$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
				$this->filter_false_module( 'tweaks', 'metabox_parent' );
				remove_meta_box( 'pageparentdiv', $screen, 'side' );

				$this->comments__handle_default_status( $screen->post_type );
				$this->posttypes__media_register_headerbutton( 'main_posttype' );
				$this->_hook_post_updated_messages( 'main_posttype' );

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'the_title', 2, 9 );

				// TODO: restrict quotations by parents

				if ( Services\PostTypeFields::isAvailable( 'parent_post_id', $this->constant( 'main_posttype' ) ) ) {
					$this->corerestrictposts__hook_columnrow_for_parent_post( $screen->post_type, 'book-alt', 'meta', NULL, -10 );
					$this->corerestrictposts__hook_parsequery_for_post_parent( 'main_posttype' );
				}

				$this->corerestrictposts__hook_screen_taxonomies( 'category_taxonomy' );
				$this->_hook_bulk_post_updated_messages( 'main_posttype' );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				if ( Services\PostTypeFields::isAvailable( 'parent_post_id', $this->constant( 'main_posttype' ) ) ) {
					$this->filter( 'the_title', 2, 9 );
					$this->_hook_children_listbox( $screen, $this->constant( 'main_posttype' ) );
				}

			} else if ( 'edit' == $screen->base ) {

				if ( Services\PostTypeFields::isAvailable( 'parent_post_id', $this->constant( 'main_posttype' ) ) )
					$this->corerestrictposts__hook_columnrow_for_post_children( $screen->post_type, 'main_posttype', NULL, NULL, NULL, -10 );
			}
		}
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'main_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function meta_render_metabox( $post, $box, $fields = NULL, $context = 'mainbox' )
	{
		gEditorial\MetaBox::fieldPostMenuOrder( $post );
	}

	// @REF: `MetaBox::fieldPostParent()`
	// @REF: `page_attributes_misc_attributes`
	public function meta_render_metabox_OLD( $post, $box, $fields = NULL, $context = 'mainbox' )
	{
		$html = Core\HTML::tag( 'input', [
			'name'        => 'menu_order',
			'type'        => 'number',
			// 'dir'         => 'ltr',
			'value'       => $post->menu_order ?: '',
			'placeholder' => _x( 'Menu Order', 'Placeholder', 'geditorial-quotation' ),
		] );

		$html.= ' <span>'._x( 'Menu Order', 'Placeholder', 'geditorial-quotation' ).'</span>';

		echo Core\HTML::wrap( $html, 'field-wrap -inputnumber' );

		echo '<hr />';
	}

	public function the_title( $title, $post_id )
	{
		if ( ! empty( $title ) )
			return $title;

		if ( ! $post = WordPress\Post::get( $post_id ) )
			return $title;

		if ( $post->post_parent && $this->is_posttype( 'main_posttype', $post ) )
			return vsprintf(
				/* translators: `%1$s`: post parent, `%2$s`: menu order */
				_x( '[Quote from &ldquo;%1$s&rdquo; &mdash; %2$s]', 'Title Template', 'geditorial-quotation' ),
				[
					WordPress\Post::title( $post->post_parent, NULL, FALSE ),
					Core\Number::format( $post->menu_order ),
				]
			);

		return $title;
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'assigned',
			$this->constant( 'main_posttype' ),
			$this->constant( 'category_taxonomy' ),
			array_merge( [
				'post_id' => NULL,
			], (array) $atts ),
			$content,
			$this->constant( 'main_shortcode', $tag ),
			$this->key
		);
	}

	public function prep_meta_row_module( $value, $field_key = NULL, $field = [], $raw = NULL )
	{
		switch ( $field_key ) {

			case 'quotation_pagestart':
				return sprintf(
					/* translators: `%s`: page start placeholder */
					_x( 'Starts on Page %s', 'Display', 'geditorial-quotation' ),
					Core\Number::localize( $raw ?: $value )
				);

			case 'quotation_pageend':
				return sprintf(
					/* translators: `%s`: page end placeholder */
					_x( 'Ends in Page %s', 'Display', 'geditorial-quotation' ),
					Core\Number::localize( $raw ?: $value )
				);

			case 'quotation_section':
				return sprintf(
					/* translators: `%s`: section placeholder */
					_x( 'Section %s', 'Display', 'geditorial-quotation' ),
					Core\Number::localize( $raw ?: $value )
				);

			case 'quotation_volume':
				return sprintf(
					/* translators: `%s`: volume placeholder */
					_x( 'Volume %s', 'Display', 'geditorial-quotation' ),
					Core\Number::localize( $raw ?: $value )
				);
		}

		return $value;
	}

	// @REF: `Template::getMetaField()`
	public function meta_field( $meta, $field, $post, $args, $raw, $field_args, $context )
	{
		return $this->prep_meta_row_module( $meta, $field, $field_args, $raw );
	}
}
