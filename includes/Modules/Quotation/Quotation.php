<?php namespace geminorum\gEditorial\Modules\Quotation;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\WordPress\PostType;

class Quotation extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'   => 'quotation',
			'title'  => _x( 'Quotation', 'Modules: Quotation', 'geditorial' ),
			'desc'   => _x( 'Snippets from Content', 'Modules: Quotation', 'geditorial' ),
			'icon'   => 'format-quote',
			'access' => 'beta',
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
				$this->settings_supports_option( 'quote_cpt', [
					'title',
					'editor',
					'excerpt',
					'author',
					'comments',
					'custom-fields',
					'editorial-roles',
				]  ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'quote_cpt'       => 'quote',
			'topic_tax'       => 'quote_topic',
			'topic_shortcode' => 'quote-topic',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'quote_cpt' => _n_noop( 'Quote', 'Quotes', 'geditorial-quotation' ),
				'topic_tax' => _n_noop( 'Topic', 'Topics', 'geditorial-quotation' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'quote_cpt' => [
				'menu_name'      => _x( 'Quotation', 'Posttype Menu', 'geditorial-quotation' ),
				'meta_box_title' => _x( 'Quotation', 'MetaBox Title', 'geditorial-quotation' ),
			],
			'topic_column_title' => _x( 'Topic', 'Column Title', 'geditorial-quotation' ),
		];

		return $strings;
	}

	public function get_global_fields()
	{
		$rtl = HTML::rtl();

		return [
			$this->constant( 'quote_cpt' ) => [
				'parent_post_id' => [
					'title'       => _x( 'Parent', 'Field Title', 'geditorial-quotation' ),
					'description' => _x( 'Parent post of the Quote', 'Field Description', 'geditorial-quotation' ),
					'type'        => 'parent_post',
					'posttype'    => $this->posttypes(),
				],
				// FIXME: DEPRECATED
				'quotation_pages' => [
					'title'       => _x( 'Pages', 'Field Title', 'geditorial-quotation' ),
					'description' => _x( 'Source Pages of the Quote', 'Field Description', 'geditorial-quotation' ),
					'type'        => 'text',
					'icon'        => 'admin-page',
					'quickedit'   => TRUE,
				],
				'quotation_pagestart' => [
					'title'       => _x( 'Page Start', 'Field Title', 'geditorial-quotation' ),
					'description' => _x( 'Source Start Page of the Quote', 'Field Description', 'geditorial-quotation' ),
					'type'        => 'number',
					'icon'        => $rtl ? 'controls-skipforward' : 'controls-skipback',
					'quickedit'   => TRUE,
				],
				'quotation_pageend' => [
					'title'       => _x( 'Page End', 'Field Title', 'geditorial-quotation' ),
					'description' => _x( 'Source End Page of the Quote', 'Field Description', 'geditorial-quotation' ),
					'type'        => 'number',
					'icon'        => $rtl ? 'controls-skipback' : 'controls-skipforward',
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
		];
	}

	protected function posttypes_excluded( $extra = [] )
	{
		return $this->filters( 'posttypes_excluded', Settings::posttypesExcluded( $extra + [ $this->constant( 'quote_cpt' ) ] ) );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'quote_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'topic_tax', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
			'show_in_nav_menus'  => TRUE,
			'meta_box_cb'        => NULL,
		], 'quote_cpt' );

		$this->register_posttype( 'quote_cpt' );

		$this->register_shortcode( 'topic_shortcode' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'quote_cpt' ) );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'quote_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'post_updated_messages' );
				$this->filter( 'get_default_comment_status', 3 );

				$this->action_module( 'meta', 'render_metabox', 4, 1 );
				$this->remove_meta_box( NULL, $screen->post_type, 'parent' );

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );

				$this->_hook_screen_restrict_taxonomies();

				$this->filter( 'the_title', 2, 9 );
				$this->action_module( 'meta', 'column_row', 1, -25, 'source' );
			}
		}
	}

	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [ 'topic_tax' ];
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'quote_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'quote_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'quote_cpt', $counts ) );
	}

	// @REF: `MetaBox::fieldPostParent()`
	// @REF: `page_attributes_misc_attributes`
	public function meta_render_metabox( $post, $box, $fields = NULL, $context = 'mainbox' )
	{
		$html = HTML::tag( 'input', [
			'name'        => 'menu_order',
			'type'        => 'number',
			'dir'         => 'ltr',
			'value'       => $post->menu_order ?: '',
			'placeholder' => _x( 'Menu Order', 'Placeholder', 'geditorial-quotation' ),
		] );

		$html.= ' <span>'._x( 'Menu Order', 'Placeholder', 'geditorial-quotation' ).'</span>';

		echo HTML::wrap( $html, 'field-wrap -inputnumber' );

		echo '<hr />';
	}

	public function the_title( $title, $post_id )
	{
		if ( ! empty( $title ) )
			return $title;

		if ( ! $post = PostType::getPost( $post_id ) )
			return $title;

		if ( $post->post_parent && $this->constant( 'quote_cpt' ) == $post->post_type )
			/* translators: %1$s: post parent, %2$s: menu order */
			return vsprintf( _x( '[Quote from &ldquo;%1$s&rdquo; &mdash; %2$s]', 'Title Template', 'geditorial-quotation' ), [
				PostType::getPostTitle( $post->post_parent, NULL, FALSE ),
				Number::format( $post->menu_order ),
			] );

		return $title;
	}

	public function meta_column_row_source( $post )
	{
		echo '<li class="-row -cartable-user">';

			echo $this->get_column_icon( FALSE, 'book-alt', _x( 'Quotation Source', 'Row Icon Title', 'geditorial-quotation' ) );

			if ( $post->post_parent )
				echo Helper::getPostTitleRow( $post->post_parent, 'edit', FALSE ); // , _x( 'Quotation Source', 'Row Title', 'geditorial-quotation' ) );

			else
				echo gEditorial()->na();

		echo '</li>';
	}
}
