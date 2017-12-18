<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Database;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\Templates\Terms as ModuleTemplate;

class Terms extends gEditorial\Module
{

	protected $partials = [ 'templates' ];

	public static function module()
	{
		return [
			'name'  => 'terms',
			'title' => _x( 'Terms', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Taxonomy & Term Tools', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'image-filter',
		];
	}

	protected function get_global_settings()
	{
		$this->taxonomies_excluded = [
			'system_tags',
			'nav_menu',
			'post_format',
			'link_category',
			'bp_member_type',
			'bp_group_type',
			'bp-email-type',
			'ef_editorial_meta',
			'following_users',
			'ef_usergroup',
			'post_status',
			'rel_people',
			'rel_post',
		];

		return [
			'_general' => [
				[
					'field'       => 'term_order',
					'type'        => 'taxonomies',
					'title'       => _x( 'Term Order', 'Modules: Terms: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Supports term order for selected taxonomies.', 'Modules: Terms: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'values'      => $this->get_taxonomies_support( 'order' ),
				],
				[
					'field'       => 'term_image',
					'type'        => 'taxonomies',
					'title'       => _x( 'Term Image', 'Modules: Terms: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Supports term image for selected taxonomies.', 'Modules: Terms: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'values'      => $this->get_taxonomies_support( 'image' ),
				],
				[
					'field'       => 'term_author',
					'type'        => 'taxonomies',
					'title'       => _x( 'Term Author', 'Modules: Terms: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Supports term author for selected taxonomies.', 'Modules: Terms: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'values'      => $this->get_taxonomies_support( 'author' ),
				],
				[
					'field'       => 'term_color',
					'type'        => 'taxonomies',
					'title'       => _x( 'Term Color', 'Modules: Terms: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Supports term color for selected taxonomies.', 'Modules: Terms: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'values'      => $this->get_taxonomies_support( 'color' ),
				],
			],
			'_frontend' => [
				'adminbar_summary',
			],
			'taxonomies_option' => 'taxonomies_option',
		];
	}

	protected function get_global_strings()
	{
		return [
			'titles' => [
				'order'  => _x( 'Order', 'Modules: Terms: Titles', GEDITORIAL_TEXTDOMAIN ),
				'image'  => _x( 'Image', 'Modules: Terms: Titles', GEDITORIAL_TEXTDOMAIN ),
				'author' => _x( 'Author', 'Modules: Terms: Titles', GEDITORIAL_TEXTDOMAIN ),
				'color'  => _x( 'Color', 'Modules: Terms: Titles', GEDITORIAL_TEXTDOMAIN ),
			],
			'descriptions' => [
				'order'  => _x( 'Terms are usually ordered alphabetically, but you can choose your own order by entering a number (1 for first, etc.) in this field.', 'Modules: Terms: Descriptions', GEDITORIAL_TEXTDOMAIN ),
				'image'  => _x( 'Assign terms a custom image to visually separate them from each-other.', 'Modules: Terms: Descriptions', GEDITORIAL_TEXTDOMAIN ),
				'author' => _x( 'Set term author to help identify who created or owns each term.', 'Modules: Terms: Descriptions', GEDITORIAL_TEXTDOMAIN ),
				'color'  => _x( 'Terms can have unique colors to help separate them from each other.', 'Modules: Terms: Descriptions', GEDITORIAL_TEXTDOMAIN ),
			],
			'misc' => [
				'order_column_title'  => _x( 'O', 'Modules: Terms: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'image_column_title'  => _x( 'Image', 'Modules: Terms: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'author_column_title' => _x( 'Author', 'Modules: Terms: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'color_column_title'  => _x( 'C', 'Modules: Terms: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'posts_column_title'  => _x( 'P', 'Modules: Terms: Column Title', GEDITORIAL_TEXTDOMAIN ),
			],
			'js' => [
				'modal_title' => _x( 'Choose an Image', 'Modules: Terms: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'modal_button' => _x( 'Set as image', 'Modules: Terms: Javascript String', GEDITORIAL_TEXTDOMAIN ),
			],
		];
	}

	protected function get_taxonomies_support( $field )
	{
		$supported = Taxonomy::get();
		$excludes  = $this->taxonomies_excluded;

		// FIXME: factoring the field?

		return array_diff_key( $supported, array_flip( $excludes ) );
	}

	public function init()
	{
		parent::init();

		if ( is_admin() ) {
			add_action( 'create_term', [ $this, 'edit_term' ], 10, 3 );
			add_action( 'edit_term', [ $this, 'edit_term' ], 10, 3 );
		}
	}

	public function init_ajax()
	{
		if ( $taxonomy = self::req( 'taxonomy' ) )
			$this->_edit_tags_screen( $taxonomy );
	}

	public function current_screen( $screen )
	{
		$fields  = [ 'order', 'image', 'author', 'color' ];
		$enqueue = FALSE;

		if ( 'edit-tags' == $screen->base ) {

			foreach ( $fields as $field ) {

				if ( ! in_array( $screen->taxonomy, $this->get_setting( 'term_'.$field, [] ) ) )
					continue;

				add_action( $screen->taxonomy.'_add_form_fields', function( $taxonomy ) use( $field ){
					$this->add_form_field( $field, $taxonomy );
				}, 8, 1 );

				add_action( 'quick_edit_custom_box', function( $column, $screen, $taxonomy ) use( $field ){
					if ( $this->classs( $field ) == $column )
						$this->quick_form_field( $field, $taxonomy );
				}, 10, 3 );

				if ( 'image' == $field ) {

					add_thickbox();

				} else if ( 'color' == $field ) {
					wp_enqueue_script( 'wp-color-picker' );
					wp_enqueue_style( 'wp-color-picker' );
				}

				$enqueue = TRUE;
			}

			if ( $enqueue ) {

				$this->_admin_enabled();

				$this->_edit_tags_screen( $screen->taxonomy );
				add_action( 'manage_edit-'.$screen->taxonomy.'_sortable_columns', [ $this, 'sortable_columns' ], 10, 3 );

				wp_enqueue_media();

				$this->enqueue_asset_js( [
					'strings' => $this->strings['js'],
				], NULL, [ 'jquery', 'media-upload' ] );
			}

		} else if ( 'term' == $screen->base ) {

			foreach ( $fields as $field ) {

				if ( ! in_array( $screen->taxonomy, $this->get_setting( 'term_'.$field, [] ) ) )
					continue;

				add_action( $screen->taxonomy.'_edit_form_fields', function( $term, $taxonomy ) use( $field ){
					$this->edit_form_field( $field, $taxonomy, $term );
				}, 8, 2 );

				if ( 'image' == $field ) {

					add_thickbox();

				} else if ( 'color' == $field ) {

					wp_enqueue_script( 'wp-color-picker' );
					wp_enqueue_style( 'wp-color-picker' );
				}

				$enqueue = TRUE;
			}

			if ( $enqueue ) {

				$this->_admin_enabled();

				wp_enqueue_media();

				$this->enqueue_asset_js( [
					'strings' => $this->strings['js'],
				], NULL, [ 'jquery', 'media-upload' ] );
			}
		}
	}

	private function _edit_tags_screen( $taxonomy )
	{
		add_filter( 'manage_edit-'.$taxonomy.'_columns', [ $this, 'manage_columns' ] );
		add_filter( 'manage_'.$taxonomy.'_custom_column', [ $this, 'custom_column' ], 10, 3 );
	}

	public function manage_columns( $columns )
	{
		if ( ! $taxonomy = self::req( 'taxonomy' ) )
			return $columns;

		$fields = [
			'order'  => [ 'cb', 'after' ],
			'image'  => [ 'name', 'before' ],
			'author' => [ 'name', 'after' ],
			'color'  => [ 'name', 'before' ],
		];

		foreach ( $fields as $field => $place )
			if ( in_array( $taxonomy, $this->get_setting( 'term_'.$field, [] ) ) )
				$columns = Arraay::insert( $columns, [
					$this->classs( $field ) => $this->get_column_title( $field, $taxonomy ),
				], $place[0], $place[1] );

		// smaller name for posts column
		if ( array_key_exists( 'posts', $columns ) )
			$columns['posts'] = $this->get_column_title( 'posts', $taxonomy );

		return $columns;
	}

	public function sortable_columns( $columns )
	{
		$fields = [ 'order', 'image', 'author', 'color' ];

		foreach ( $fields as $field )
			$columns[$this->classs( $field )] = 'meta_'.$field;

		return $columns;
	}

	public function custom_column( $display, $column, $term_id )
	{
		if ( $this->classs( 'order' ) == $column ) {

			$this->column_order( get_term_meta( $term_id, 'order', TRUE ) );

		} else if ( $this->classs( 'image' ) == $column ) {

			// FIXME
			// $sizes = Media::getPosttypeImageSizes( $post->post_type );
			// $size  = isset( $sizes[$post->post_type.'-thumbnail'] ) ? $post->post_type.'-thumbnail' : 'thumbnail';
			$size = [ 45, 72 ];

			$this->column_image( $term_id, $size );

		} else if ( $this->classs( 'author' ) == $column ) {

			if ( $meta = get_term_meta( $term_id, 'author', TRUE ) ) {

				$user = get_user_by( 'id', $meta );

				if ( ! empty( $user->first_name ) && ! empty( $user->last_name ) )
					echo "{$user->first_name} {$user->last_name}";

				else
					echo $user->display_name;

				echo '<span class="author" data-author="'.$meta.'"></span>';
			} else {
				echo '<span class="column-author-empty -empty">&mdash;</span>';
				echo '<span class="author" data-author="0"></span>';
			}

		} else if ( $this->classs( 'color' ) == $column ) {

			if ( $meta = get_term_meta( $term_id, 'color', TRUE ) )
				echo '<i class="-color" data-color="'.HTML::escapeAttr( $meta ).'" style="background-color:'.HTML::escapeAttr( $meta ).'"></i>';
		}
	}

	public function edit_term( $term_id, $tt_id, $taxonomy )
	{
		$fields = [ 'order', 'image', 'author', 'color' ];

		foreach ( $fields as $field ) {

			if ( in_array( $taxonomy, $this->get_setting( 'term_'.$field, [] ) ) ) {

				if ( ! array_key_exists( 'term-'.$field, $_REQUEST ) )
					continue;

				$meta = empty( $_REQUEST['term-'.$field] ) ? FALSE : trim( $_REQUEST['term-'.$field] );

				if ( $meta ) {

					if ( 'image' == $field ) {
						update_post_meta( intval( $meta ), '_wp_attachment_is_term_image', $taxonomy );
						do_action( 'clean_term_attachment_cache', intval( $meta ), $taxonomy, $term_id );
					}

					update_term_meta( $term_id, $field, $meta );

				} else {

					if ( 'image' == $field && $meta = get_term_meta( $term_id, $field, TRUE ) ) {
						delete_post_meta( intval( $meta ), '_wp_attachment_is_term_image' );
						do_action( 'clean_term_attachment_cache', intval( $meta ), $taxonomy, $term_id );
					}

					delete_term_meta( $term_id, $field );
				}

				// FIXME: experiment: since the action may trigger twice
				unset( $_REQUEST['term-'.$field] );
			}
		}
	}

	private function quick_form_field( $field, $taxonomy )
	{
		echo '<fieldset><div class="inline-edit-col"><label><span class="title">';
			echo esc_html( $this->get_string( $field, $taxonomy, 'titles', $field ) );
		echo '</span><span class="input-text-wrap">';
			$this->quickedit_field( $field, $taxonomy );
		echo '</span></label></div></fieldset>';
	}

	private function add_form_field( $field, $taxonomy, $term = FALSE )
	{
		echo '<div class="form-field term-'.$field.'-wrap">';
		echo '<label for="term-'.$field.'">';
			echo esc_html( $this->get_string( $field, $taxonomy, 'titles', $field ) );
		echo '</label>';
			$this->form_field( $field, $taxonomy, $term );
			HTML::desc( $this->get_string( $field, $taxonomy, 'descriptions', '' ) );
		echo '</div>';
	}

	private function edit_form_field( $field, $taxonomy, $term = FALSE )
	{
		echo '<tr class="form-field term-'.$field.'-wrap"><th scope="row" valign="top">';
		echo '<label for="term-'.$field.'">';
			echo esc_html( $this->get_string( $field, $taxonomy, 'titles', $field ) );
		echo '</label></th><td>';
			$this->form_field( $field, $taxonomy, $term );
			HTML::desc( $this->get_string( $field, $taxonomy, 'descriptions', '' ) );
		echo '</td></tr>';
	}

	private function form_field( $field, $taxonomy, $term = FALSE )
	{
		$term_id = empty( $term->term_id ) ? 0 : $term->term_id;
		$meta    = get_term_meta( $term_id, $field, TRUE );

		switch ( $field ) {

			case 'image':

				echo '<div>'.HTML::tag( 'img', [
					'id'    => $this->classs( $field, 'img' ),
					'src'   => empty( $meta ) ? '' : wp_get_attachment_image_url( $meta, 'thumbnail' ),
					'style' => empty( $meta ) ? 'display:none' : FALSE,
				] ).'</div>';

				echo HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => $meta,
					'style' => 'display:none',
				] );

				echo HTML::tag( 'a', [
					'class' => [ 'button', 'button-small', 'button-secondary', '-modal' ],
				], _x( 'Choose', 'Modules: Terms: Button', GEDITORIAL_TEXTDOMAIN ) );

				echo '&nbsp;'.HTML::tag( 'a', [
					'class' => [ 'button', 'button-small', 'button-link-delete', '-remove' ],
					'style' => empty( $meta ) ? 'display:none' : FALSE,
				], _x( 'Remove', 'Modules: Terms: Button', GEDITORIAL_TEXTDOMAIN ) );

			break;
			case 'order':

				echo HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'number',
					'value' => empty( $meta ) ? '' : $meta,
					'class' => 'small-text',
					'data'  => [ 'ortho' => 'number' ],
				] );

			break;
			case 'author':

				// on add term
				if ( empty( $meta ) && FALSE === $term )
					$meta = get_current_user_id();

				wp_dropdown_users( [
					'name'              => 'term-'.$field,
					'who'               => 'authors',
					'show'              => 'display_name_with_login',
					'selected'          => empty( $meta ) ? '0' : $meta,
					'show_option_all'   => Settings::showOptionNone(),
					'option_none_value' => 0,
				] );

			break;
			default:

				echo HTML::tag( 'input', [
					'id'    => $this->classs( $field, 'id' ),
					'name'  => 'term-'.$field,
					'type'  => 'text',
					'value' => empty( $meta ) ? '' : $meta,
					'data'  => [ 'ortho' => 'color' ],
				] );
		}
	}

	private function quickedit_field( $field, $taxonomy )
	{
		switch ( $field ) {

			case 'image':

				echo '<input type="hidden" name="term-'.$field.'" value="" />';

				echo HTML::tag( 'button', [
					'class' => [ 'button', 'button-small', 'button-secondary', '-modal', '-quick' ],
				], _x( 'Choose', 'Modules: Terms: Button', GEDITORIAL_TEXTDOMAIN ) );

				echo '&nbsp;'.HTML::tag( 'a', [
					'href'  => '',
					'class' => [ 'button', 'button-small', 'button-link-delete', '-remove', '-quick' ],
					'style' => 'display:none',
				], _x( 'Remove', 'Modules: Terms: Button', GEDITORIAL_TEXTDOMAIN ) ).'&nbsp;';

				echo HTML::tag( 'img', [
					// 'src'   => '',
					'class' => '-img',
					'style' => 'display:none',
				] );

			break;
			case 'order':

				echo HTML::tag( 'input', [
					'name'  => 'term-'.$field,
					'type'  => 'number',
					'value' => '',
					'class' => [ 'ptitle', 'small-text' ],
					// 'data'  => [ 'ortho' => 'number' ],
				] );

			break;
			case 'author':

				wp_dropdown_users( [
					'name'              => 'term-'.$field,
					'who'               => 'authors',
					'show'              => 'display_name_with_login',
					'show_option_all'   => Settings::showOptionNone(),
					'option_none_value' => 0,
				] );

			break;
			case 'color':

				echo HTML::tag( 'input', [
					'name'  => 'term-'.$field,
					'type'  => 'color',
					'value' => '',
					'class' => [ 'small-text' ],
					'data'  => [ 'ortho' => 'color' ],
				] );

			break;
			default:
				echo '<input type="text" class="ptitle" name="term-'.$field.'" value="" />';
		}
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() || ! is_singular() )
			return;

		$post_id = get_queried_object_id();

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		$nodes[] = [
			'id'     => $this->classs(),
			'title'  => _x( 'Term Summary', 'Modules: Terms: Adminbar', GEDITORIAL_TEXTDOMAIN ),
			'parent' => $parent,
			'href'   => $this->get_module_url( 'reports', 'uncategorized' ),
		];

		foreach ( $this->taxonomies() as $taxonomy ) {

			$terms = get_the_terms( $post_id, $taxonomy );

			if ( ! $terms || is_wp_error( $terms ) )
				continue;

			$object = get_taxonomy( $taxonomy );

			$nodes[] = [
				'id'     => $this->classs( 'tax', $taxonomy ),
				'title'  => $object->labels->name.':',
				'parent' => $this->classs(),
				'href'   => WordPress::getEditTaxLink( $taxonomy ),
			];

			foreach ( $terms as $term )
				$nodes[] = [
					'id'     => $this->classs( 'term', $term->term_id ),
					'title'  => '&ndash; '.sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ),
					'parent' => $this->classs(),
					'href'   => get_term_link( $term ),
				];
		}
	}

	public function append_sub( $subs, $page = 'settings' )
	{
		if ( ! $this->cuc( $page ) )
			return $subs;

		if ( $page == 'reports' )
			return array_merge( $subs, [
				'uncategorized' => _x( 'Uncategorized', 'Modules: Terms: Reports: Sub Title', GEDITORIAL_TEXTDOMAIN ),
			] );
		else
			return array_merge( $subs, [ $this->module->name => $this->module->title ] );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( isset( $_POST['orphaned_terms'] ) ) {

					$post = $this->settings_form_req( [
						'dead_tax' => FALSE,
						'live_tax' => FALSE,
					], 'tools' );

					if ( $post['dead_tax'] && $post['live_tax'] ) {

						global $wpdb;

						$result = $wpdb->query( $wpdb->prepare( "
							UPDATE {$wpdb->term_taxonomy} SET taxonomy = %s WHERE taxonomy = %s
						", trim( $post['live_tax'] ), trim( $post['dead_tax'] ) ) );

						if ( count( $result ) )
							WordPress::redirectReferer( [
								'message' => 'changed',
								'count'   => count( $result ),
							] );
					}
				}
			}
		}
	}

	public function tools_sub( $uri, $sub )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', 'tools', FALSE, FALSE );

			HTML::h3( _x( 'Term Tools', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ) );

			echo '<table class="form-table">';

			$db_taxes   = Database::getTaxonomies( TRUE );
			$live_taxes = Taxonomy::get( 6 );
			$dead_taxes = array_diff_key( $db_taxes, $live_taxes );

			if ( count( $dead_taxes ) ) {

				echo '<tr><th scope="row">'._x( 'Orphaned Terms', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

					$this->do_settings_field( [
						'type'         => 'select',
						'field'        => 'dead_tax',
						'values'       => $dead_taxes,
						'default'      => ( isset( $post['dead_tax'] ) ? $post['dead_tax'] : 'post_tag' ),
						'option_group' => 'tools',
					] );

					$this->do_settings_field( [
						'type'         => 'select',
						'field'        => 'live_tax',
						'values'       => $live_taxes,
						'default'      => ( isset( $post['live_tax'] ) ? $post['live_tax'] : 'post_tag' ),
						'option_group' => 'tools',
					] );

					echo '&nbsp;&nbsp;';

					Settings::submitButton( 'orphaned_terms',
						_x( 'Convert', 'Modules: Terms: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

					HTML::desc( _x( 'Converts orphaned terms into currently registered taxonomies.', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ) );

				echo '</td></tr>';

			} else {

				HTML::desc( _x( 'Currently no tool available.', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ) );
			}

			echo '</table>';

		$this->settings_form_after( $uri, $sub );
	}

	public function reports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'reports', 'uncategorized' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'reports', $sub );

				if ( 'cleanup_terms' == self::req( 'table_action' )
					&& count( self::req( '_cb' ) ) ) {

					$all   = Taxonomy::get();
					$count = 0;

					foreach ( $_POST['_cb'] as $post_id ) {

						$taxes = get_object_taxonomies( get_post( $post_id ) );
						$diff  = array_diff_key( $all, array_flip( $taxes ) );

						foreach ( $diff as $tax => $title )
							wp_set_object_terms( $post_id, NULL, $tax );

						$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'cleaned',
						'count'   => $count,
					] );
				}
			}

			$this->screen_option( $sub );
		}
	}

	public function reports_sub( $uri, $sub )
	{
		if ( 'uncategorized' == $sub )
			return $this->reports_sub_uncategorized( $uri, $sub );
	}

	public function reports_sub_uncategorized( $uri, $sub )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', 'reports', FALSE, FALSE );

			$this->tableUncategorized();

		$this->settings_form_after( $uri, $sub );
	}

	private function tableUncategorized()
	{
		$query = [
			'tax_query'        => [ [
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => [ intval( get_option( 'default_category' ) ) ],
			] ],
		];

		list( $posts, $pagination ) = $this->getTablePosts( $query, [], 'any' );

		$pagination['actions']['cleanup_terms'] = _x( 'Cleanup Terms', 'Modules: Terms: Table Action', GEDITORIAL_TEXTDOMAIN );
		$pagination['before'][] = Helper::tableFilterPostTypes();

		return HTML::tableList( [
			'_cb'   => 'ID',
			'ID'    => Helper::tableColumnPostID(),
			'date'  => Helper::tableColumnPostDate(),
			'type'  => Helper::tableColumnPostType(),
			'title' => Helper::tableColumnPostTitle(),
			'terms' => Helper::tableColumnPostTerms(),
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Uncategorized Posts', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ) ),
			'empty'      => Helper::tableArgEmptyPosts(),
			'pagination' => $pagination,
		] );
	}
}
