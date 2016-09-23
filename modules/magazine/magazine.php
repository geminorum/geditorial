<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMagazine extends gEditorialModuleCore
{

	public $meta_key     = '_ge_magazine';
	protected $root_key  = 'GEDITORIAL_MAGAZINE_ROOT_BLOG';

	protected $caps = array(
		'tools' => 'edit_others_posts',
	);

	public static function module()
	{
		return array(
			'name'  => 'magazine',
			'title' => _x( 'Magazine', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Issue Management for Magazines', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'book',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'multiple_instances',
				'admin_ordering',
				'admin_restrict',
				array(
					'field'       => 'issue_sections',
					'title'       => _x( 'Issue Sections', 'Magazine Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Section taxonomy for issue and supported post types', 'Magazine Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '0',
				),
				'posttype_feeds',
				'posttype_pages',
				'redirect_archives',
				array(
					'field'       => 'redirect_spans',
					'type'        => 'text',
					'title'       => _x( 'Redirect Spans', 'Magazine Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Redirect all Span Archives to a URL', 'Magazine Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '',
					'dir'         => 'ltr',
				),
			),
			'posttypes_option' => 'posttypes_option',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'issue_cpt'           => 'issue',
			'issue_cpt_archive'   => 'issues',
			'issue_cpt_permalink' => '/%postname%',
			'issue_cpt_p2p'       => 'related_issues',
			'issue_tax'           => 'issues',
			'span_tax'            => 'issue_span',
			'span_tax_slug'       => 'issue-span',
			'section_tax'         => 'issue_section',
			'section_tax_slug'    => 'issue-section',
			'issue_shortcode'     => 'issue',
			'span_shortcode'      => 'issue-span',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'issue_cpt' => array(
					'featured'              => _x( 'Cover Image', 'Magazine Module: Issue CPT: Featured', GEDITORIAL_TEXTDOMAIN ),
					'cover_column_title'    => _x( 'Cover', 'Magazine Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'order_column_title'    => _x( 'O', 'Magazine Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'children_column_title' => _x( 'Posts', 'Magazine Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'issue_tax' => array(
					'meta_box_title' => _x( 'In This Issue', 'Magazine Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'span_tax' => array(
					'meta_box_title'      => _x( 'Spans', 'Magazine Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Issue Spans', 'Magazine Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'section_tax' => array(
					'meta_box_title'      => _x( 'Sections', 'Magazine Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Issue Sections', 'Magazine Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'meta_box_title'      => _x( 'The Issue', 'Magazine Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
				'tweaks_column_title' => _x( 'Issues', 'Magazine Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
			),
			'noops' => array(
				'issue_cpt'   => _nx_noop( 'Issue',   'Issues',   'Magazine Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'issue_tax'   => _nx_noop( 'Issue',   'Issues',   'Magazine Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'span_tax'    => _nx_noop( 'Span',    'Spans',    'Magazine Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'section_tax' => _nx_noop( 'Section', 'Sections', 'Magazine Module: Noop', GEDITORIAL_TEXTDOMAIN ),
			),
			'p2p' => array(
				'issue_cpt' => array(
					'title' => array(
						'from' => _x( 'Connected Issues', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'to'   => _x( 'Connected Posts', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN )
					),
					'from_labels' => array(
						'singular_name' => _x( 'Post', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'search_items'  => _x( 'Search posts', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'not_found'     => _x( 'No posts found.', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'create'        => _x( 'Connect to a post', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
					),
					'to_labels' => array(
						'singular_name' => _x( 'Issue', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'search_items'  => _x( 'Search issues', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'not_found'     => _x( 'No issues found.', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
						'create'        => _x( 'Connect to an issue', 'Magazine Module: P2P', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
		);
	}

	protected function get_global_supports()
	{
		return array(
			'issue_cpt' => array(
				'title',
				'editor',
				'excerpt',
				'author',
				'thumbnail',
				// 'trackbacks',
				// 'custom-fields',
				'comments',
				'revisions',
				'page-attributes',
			),
		);
	}

	protected function get_global_fields()
	{
		return array(
			$this->constant( 'issue_cpt' ) => array (
				'ot' => array( 'type' => 'title_before' ),
				'st' => array( 'type' => 'title_after' ),

				'issue_number_line' => array(
					'title'       => _x( 'Number Line', 'Magazine Module: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'The issue number line', 'Magazine Module: Field Description', GEDITORIAL_TEXTDOMAIN ),
				),
				'issue_total_pages' => array(
					'title'       => _x( 'Total Pages', 'Magazine Module: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'The issue total pages', 'Magazine Module: Field Description', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'post' => array(
				'in_issue_order' => array(
					'title'       => _x( 'Order', 'Magazine Module: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Post order in issue list', 'Magazine Module: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'number',
					'context'     => 'issue',
				),
				'in_issue_page_start' => array(
					'title'       => _x( 'Page Start', 'Magazine Module: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Post start page on issue (printed)', 'Magazine Module: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'type'        => 'number',
					'context'     => 'issue',
				),
				'in_issue_pages' => array(
					'title'       => _x( 'Total Pages', 'Magazine Module: Field Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Post total pages on issue (printed)', 'Magazine Module: Field Description', GEDITORIAL_TEXTDOMAIN ),
					'context'     => 'issue',
				),
			),
		);
	}

	public function setup( $partials = array() )
	{
		parent::setup( 'templates' );
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'issue_cpt' );
	}

	public function p2p_init()
	{
		$this->register_p2p( 'issue_cpt' );
	}

	public function init()
	{
		do_action( 'geditorial_magazine_init', $this->module );

		$this->do_globals();

		$this->post_types_excluded = array( $this->constant( 'issue_cpt' ) );

		$this->register_post_type( 'issue_cpt', array(
			'hierarchical' => TRUE,
			'rewrite'      => array(
				'feeds' => (bool) $this->get_setting( 'posttype_feeds', FALSE ),
				'pages' => (bool) $this->get_setting( 'posttype_pages', FALSE ),
			),
		), array( 'post_tag' ) );

		$this->register_taxonomy( 'issue_tax', array(
			'show_ui'      => FALSE,
			'hierarchical' => TRUE,
		) );

		$this->register_taxonomy( 'span_tax', array(
			'show_admin_column' => TRUE,
		), 'issue_cpt' );

		if ( $this->get_setting( 'issue_sections', FALSE ) )
			$this->register_taxonomy( 'section_tax', array(
				'hierarchical'       => TRUE,
				'show_in_quick_edit' => TRUE,
				'show_in_nav_menus'  => TRUE,
			), $this->post_types( 'issue_cpt' ) );

		$this->register_shortcode( 'issue_shortcode', array( 'gEditorialMagazineTemplates', 'issue_shortcode' ) );
		$this->register_shortcode( 'span_shortcode', array( 'gEditorialMagazineTemplates', 'span_shortcode' ) );

		if ( ! is_admin() ) {

			add_filter( 'term_link', array( $this, 'term_link' ), 10, 3 );
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		}
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'issue_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 9, 2 );
				add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );

				add_filter( 'geditorial_meta_box_callback', '__return_false', 12 );

				$this->remove_meta_box( $screen->post_type, $screen->post_type, 'parent' );
				add_meta_box( 'geditorial-magazine-main',
					$this->get_meta_box_title( 'issue_cpt', FALSE ),
					array( $this, 'do_meta_box_main' ),
					$screen->post_type,
					'side',
					'high'
				);

				add_meta_box( 'geditorial-magazine-list',
					$this->get_meta_box_title( 'issue_tax', $this->get_url_post_edit( 'post_cpt' ), TRUE ),
					array( $this, 'do_meta_box_list' ),
					$screen->post_type,
					'advanced',
					'low'
				);

			} else if ( 'edit' == $screen->base ) {

				add_filter( 'disable_months_dropdown', '__return_true', 12 );

				if ( $this->get_setting( 'admin_restrict', FALSE ) ) {
					add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts_main_cpt' ) );
					add_filter( 'parse_query', array( $this, 'parse_query' ) );
				}

				if ( $this->get_setting( 'admin_ordering', TRUE ) )
					add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

				add_filter( 'manage_'.$screen->post_type.'_posts_columns', array( $this, 'manage_posts_columns' ) );
				add_filter( 'manage_'.$screen->post_type.'_posts_custom_column', array( $this, 'posts_custom_column'), 10, 2 );
				add_filter( 'manage_edit-'.$screen->post_type.'_sortable_columns', array( $this, 'sortable_columns' ) );
			}

			add_action( 'save_post', array( $this, 'save_post_main_cpt' ), 20, 3 );
			add_action( 'post_updated', array( $this, 'post_updated' ), 20, 3 );

			add_action( 'wp_trash_post', array( $this, 'wp_trash_post' ) );
			add_action( 'untrash_post', array( $this, 'untrash_post' ) );
			add_action( 'before_delete_post', array( $this, 'before_delete_post' ) );

		} else if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'post' == $screen->base ) {

				add_meta_box( 'geditorial-magazine-supported',
					$this->get_meta_box_title( $screen->post_type, $this->get_url_post_edit( 'issue_cpt' ) ),
					array( $this, 'do_meta_box_supported' ),
					$screen->post_type,
					'side'
				);

				// internal actions:
				add_action( 'geditorial_magazine_supported_meta_box', array( $this, 'supported_meta_box' ), 5, 3 );

				// TODO: add a thick-box to list the posts with this issue taxonomy

			} else if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_restrict', FALSE ) )
					add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts_supported_cpt' ) );
			}

			add_action( 'save_post', array( $this, 'save_post_supported_cpt' ), 20, 3 );
		}

		// $size = apply_filters( 'admin_post_thumbnail_size', $size, $thumbnail_id, $post );
	}

	public function widgets_init()
	{
		$this->require_code( 'widgets' );

		register_widget( 'gEditorialMagazineWidget_IssueCover' );
	}

	public function meta_init()
	{
		$this->add_post_type_fields( $this->constant( 'issue_cpt' ) );
		$this->add_post_type_fields( $this->constant( 'post_cpt' ) );
	}

	public function tweaks_strings( $strings )
	{
		$this->tweaks = TRUE;

		$new = array(
			'taxonomies' => array(
				$this->constant( 'issue_tax' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'issue_tax' ),
					'icon'   => 'book',
					'title'  => $this->get_column_title( 'tweaks', 'issue_tax' ),
				),
				$this->constant( 'span_tax' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'span_tax' ),
					'icon'   => 'backup',
					'title'  => $this->get_column_title( 'tweaks', 'span_tax' ),
				),
				$this->constant( 'section_tax' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'section_tax' ),
					'icon'   => 'category',
					'title'  => $this->get_column_title( 'tweaks', 'section_tax' ),
				),
			),
		);

		return self::recursiveParseArgs( $new, $strings );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'issue_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->constant( 'issue_tax' ) == $taxonomy ) {
			$post_id = '';

			// FIXME: working but disabled
			// if ( function_exists( 'get_term_meta' ) )
			// 	$post_id = get_term_meta( $term->term_id, $this->constant( 'issue_cpt' ).'_linked', TRUE );

			if ( FALSE == $post_id || empty( $post_id ) )
				$post_id = self::getPostIDbySlug( $term->slug, $this->constant( 'issue_cpt' ) );

			if ( ! empty( $post_id ) )
				return get_permalink( $post_id );
		}

		return $link;
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'issue_tax' ) ) ) {

			$term = get_queried_object();
			if ( $post_id = self::getPostIDbySlug( $term->slug, $this->constant( 'issue_cpt' ) ) )
				self::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_tax( $this->constant( 'span_tax' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_spans', FALSE ) )
				self::redirect( $redirect, 301 );

		} else if ( is_post_type_archive( $this->constant( 'issue_cpt' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				self::redirect( $redirect, 301 );
		}
	}

	public function post_updated( $post_ID, $post_after, $post_before )
	{
		if ( ! $this->is_save_post( $post_after, 'issue_cpt' ) )
			return $post_ID;

		if ( 'trash' == $post_after->post_status )
			return $post_ID;

		if ( empty( $post_before->post_name ) )
			$post_before->post_name = sanitize_title( $post_before->post_title );

		if ( empty( $post_after->post_name ) )
			$post_after->post_name = sanitize_title( $post_after->post_title );

		$args = array(
			'name'        => $post_after->post_title,
			'slug'        => $post_after->post_name,
			'description' => $post_after->post_excerpt,
			// 'parent'      => ( isset( $parent_term_id ) ? $parent_term_id : 0 ),
		);

		$the_term = get_term_by( 'slug', $post_before->post_name, $this->constant( 'issue_tax' ) );

		if ( FALSE === $the_term ){
			$the_term = get_term_by( 'slug', $post_after->post_name, $this->constant( 'issue_tax' ) );
			if ( FALSE === $the_term )
				$term = wp_insert_term( $post_after->post_title, $this->constant( 'issue_tax' ), $args );
			else
				$term = wp_update_term( $the_term->term_id, $this->constant( 'issue_tax' ), $args );
		} else {
			$term = wp_update_term( $the_term->term_id, $this->constant( 'issue_tax' ), $args );
		}

		if ( ! is_wp_error( $term ) ) {
			update_post_meta( $post_ID, '_'.$this->constant( 'issue_cpt' ).'_term_id', $term['term_id'] );

			if ( function_exists( 'update_term_meta' ) )
				update_term_meta( $term['term_id'], $this->constant( 'issue_cpt' ).'_linked', $post_ID );
		}

		return $post_ID;
	}

	public function save_post_main_cpt( $post_ID, $post, $update )
	{
		// we handle updates on another action, see : post_updated()
		if ( $update )
			return $post_ID;

		if ( ! $this->is_save_post( $post ) )
			return $post_ID;

		if ( empty( $post->post_name ) )
			$post->post_name = sanitize_title( $post->post_title );

		$args = array(
			'name'        => $post->post_title,
			'slug'        => $post->post_name,
			'description' => $post->post_excerpt,
			// 'parent'      => ( isset( $parent_term_id ) ? $parent_term_id : 0 ),
		);

		$term = wp_insert_term( $post->post_title, $this->constant( 'issue_tax' ), $args );

		if ( ! is_wp_error( $term ) ) {
			update_post_meta( $post_ID, '_'.$this->constant( 'issue_cpt' ).'_term_id', $term['term_id'] );

			if ( function_exists( 'update_term_meta' ) )
				update_term_meta( $term['term_id'], $this->constant( 'issue_cpt' ).'_linked', $post_ID );
		}

		return $post_ID;
	}

	public function wp_trash_post( $post_id )
	{
		if ( $term = $this->get_linked_term( $post_id, 'issue_cpt', 'issue_tax' ) ) {
			wp_update_term( $term->term_id, $this->constant( 'issue_tax' ), array(
				'name' => $term->name.' - '._x( '(Trashed)', 'Suffix for term name linked to trashed post', GEDITORIAL_TEXTDOMAIN ),
				'slug' => $term->slug.'-trashed',
			) );
		}
	}

	public function untrash_post( $post_id )
	{
		if ( $term = $this->get_linked_term( $post_id, 'issue_cpt', 'issue_tax' ) ) {
			wp_update_term( $term->term_id, $this->constant( 'issue_tax' ), array(
				'name' => str_ireplace( ' - '._x( '(Trashed)', 'Suffix for term name linked to trashed post', GEDITORIAL_TEXTDOMAIN ), '', $term->name ),
				'slug' => str_ireplace( '-trashed', '', $term->slug ),
			) );
		}
	}

	public function before_delete_post( $post_id )
	{
		if ( $term = $this->get_linked_term( $post_id, 'issue_cpt', 'issue_tax' ) ) {
			wp_delete_term( $term->term_id, $this->constant( 'issue_tax' ) );
			delete_metadata( 'term', $term->term_id, $this->constant( 'issue_cpt' ).'_linked' );
		}
	}

	public function wp_insert_post_data( $data, $postarr )
	{
		if ( $this->constant( 'issue_cpt' ) == $postarr['post_type'] && ! $data['menu_order'] )
			$data['menu_order'] = self::getLastPostOrder( $this->constant( 'issue_cpt' ),
				( isset( $postarr['ID'] ) ? $postarr['ID'] : '' ) ) + 1;

		return $data;
	}

	public function save_post_supported_cpt( $post_ID, $post, $update )
	{
		if ( ! $this->is_save_post( $post, $this->post_types() ) )
			return $post_ID;

		if ( isset( $_POST['geditorial-magazine-issue'] ) ) {
			$terms = array();

			foreach ( $_POST['geditorial-magazine-issue'] as $issue ) {
				if ( trim( $issue ) ) {
					$term = get_term_by( 'slug', $issue, $this->constant( 'issue_tax' ) );
					if ( ! empty( $term ) && ! is_wp_error( $term ) )
						$terms[] = intval( $term->term_id );
				}
			}

			wp_set_object_terms( $post_ID, ( count( $terms ) ? $terms : NULL ), $this->constant( 'issue_tax' ), FALSE );
		}

		return $post_ID;
	}

	public function gpeople_support( $post_types )
	{
		return array_merge( $post_types, array( $this->constant( 'issue_cpt' ) ) );
	}

	public function pre_get_posts( $wp_query )
	{
		if ( $wp_query->is_admin
			&& isset( $wp_query->query['post_type'] ) ) {

			if ( $this->constant( 'issue_cpt' ) == $wp_query->query['post_type'] ) {
				if ( ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', 'menu_order' );
				if ( ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', 'DESC' );
			}
		}
	}

	public function restrict_manage_posts_main_cpt()
	{
		$tax = get_taxonomy( $span = $this->constant( 'span_tax' ) );

		wp_dropdown_categories( array(
			'taxonomy'        => $span,
			'show_option_all' => $tax->labels->all_items,
			'name'            => $tax->name,
			'order'           => 'DESC',
			'selected'        => isset( $_GET[$span] ) ? $_GET[$span] : 0,
			'hierarchical'    => $tax->hierarchical,
			'show_count'      => FALSE,
			'hide_empty'      => TRUE,
			'hide_if_empty'   => TRUE,
		) );
	}

	public function restrict_manage_posts_supported_cpt()
	{
		$issue_tax = $this->constant( 'issue_tax' );
		$tax_obj   = get_taxonomy( $issue_tax );

		wp_dropdown_pages( array(
			'post_type'        => $this->constant( 'issue_cpt' ),
			'selected'         => isset( $_GET[$issue_tax] ) ? $_GET[$issue_tax] : '',
			'name'             => $issue_tax,
			'class'            => 'geditorial-admin-dropbown',
			'show_option_none' => $tax_obj->labels->all_items,
			'sort_column'      => 'menu_order',
			'sort_order'       => 'desc',
			'post_status'      => 'publish,private,draft',
			'value_field'      => 'post_name',
			'walker'           => new gEditorial_Walker_PageDropdown(),
		));
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query->query_vars, array( 'span_tax' ) );
	}

	public function do_meta_box_supported( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox magazine">';

		$terms = gEditorialHelper::getTerms( $this->constant( 'issue_tax' ), $post->ID, TRUE );

		do_action( 'geditorial_magazine_supported_meta_box', $post, $box, $terms );

		do_action( 'geditorial_meta_do_meta_box', $post, $box, NULL, 'issue' );

		echo '</div>';
	}

	public function supported_meta_box( $post, $box, $terms )
	{
		$dropdowns = $excludes = array();

		foreach ( $terms as $term ) {

			$dropdowns[$term->slug] = wp_dropdown_pages( array(
				'post_type'        => $this->constant( 'issue_cpt' ),
				'selected'         => $term->slug,
				'name'             => 'geditorial-magazine-issue[]',
				'id'               => 'geditorial-magazine-issue-'.$term->slug,
				'class'            => 'geditorial-admin-dropbown',
				'show_option_none' => gEditorialSettingsCore::showOptionNone(),
				'sort_column'      => 'menu_order',
				'sort_order'       => 'desc',
				'post_status'      => 'publish,private,draft',
				'value_field'      => 'post_name',
				'echo'             => 0,
				'walker'           => new gEditorial_Walker_PageDropdown(),
			));

			$excludes[] = $term->slug;
		}

		if ( ! count( $terms ) || $this->get_setting( 'multiple_instances', FALSE ) ) {
			$dropdowns[0] = wp_dropdown_pages( array(
				'post_type'        => $this->constant( 'issue_cpt' ),
				'selected'         => '',
				'name'             => 'geditorial-magazine-issue[]',
				'id'               => 'geditorial-magazine-issue-0',
				'class'            => 'geditorial-admin-dropbown',
				'show_option_none' => gEditorialSettingsCore::showOptionNone(),
				'sort_column'      => 'menu_order',
				'sort_order'       => 'desc',
				'post_status'      => 'publish,private,draft',
				'value_field'      => 'post_name',
				'exclude'          => $excludes,
				'echo'             => 0,
				'walker'           => new gEditorial_Walker_PageDropdown(),
			));
		}

		$empty = TRUE;

		foreach ( $dropdowns as $term_slug => $dropdown ) {
			if ( $dropdown ) {
				echo '<div class="field-wrap">';
					echo $dropdown;
				echo '</div>';

				$empty = FALSE;
			}
		}

		if ( $empty )
			return gEditorialMetaBox::fieldEmptyPostType( $this->constant( 'issue_cpt' ) );
	}

	public function do_meta_box_main( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox magazine">';

		do_action( 'geditorial_magazine_main_meta_box', $post, $box );

		do_action( 'geditorial_meta_do_meta_box', $post, $box, NULL );

		$this->field_post_order( 'issue_cpt', $post );

		if ( get_post_type_object( $this->constant( 'issue_cpt' ) )->hierarchical )
			$this->field_post_parent( 'issue_cpt', $post );

		echo '</div>';
	}

	public function do_meta_box_list( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox magazine">';

		do_action( 'geditorial_magazine_list_meta_box', $post, $box );

		// TODO: add collapsible button
		if ( $term = $this->get_linked_term( $post->ID, 'issue_cpt', 'issue_tax' ) )
			echo gEditorialHelper::getTermPosts( $this->constant( 'issue_tax' ), $term );

		echo '</div>';
	}

	public function get_issue_post( $post_id = NULL, $single = FALSE )
	{
		if ( is_null( $post_id ) )
			$post_id = get_the_ID();

		$terms = gEditorialHelper::getTerms( $this->constant( 'issue_tax' ), $post_id, TRUE );
		if ( ! count( $terms ) )
			return FALSE;

		$id  = FALSE;
		$ids = array();
		foreach ( $terms as $term ) {

			// FIXME: working but disabled
			// if ( function_exists( 'get_term_meta' ) )
			// 	$id = get_term_meta( $term->term_id, $this->constant( 'issue_cpt'].'_linked', TRUE );

			if ( FALSE == $id || empty( $id ) )
				$id = self::getPostIDbySlug( $term->slug, $this->constant( 'issue_cpt' ) );

			if ( FALSE != $id && ! empty( $id ) ) {

				if ( $single )
					return $id;

				$status = get_post_status( $id );

				if ( 'publish' == $status )
					$ids[$id] = get_permalink( $id );
				else
					$ids[$id] = FALSE;
			}
		}

		if ( ! count( $ids ) )
			return FALSE;
		return $ids;
	}

	public function manage_posts_columns( $posts_columns )
	{
		$new_columns = array();
		foreach ( $posts_columns as $key => $value ) {

			if ( 'title' == $key ) {
				$new_columns['order'] = $this->get_column_title( 'order', 'issue_cpt' );
				$new_columns['cover'] = $this->get_column_title( 'cover', 'issue_cpt' );

				$new_columns[$key] = $value;

			} else if ( 'date' == $key ){
				$new_columns['children'] = $this->get_column_title( 'children', 'issue_cpt' );

			} else if ( in_array( $key, array( 'author', 'comments' ) ) ) {
				continue; // he he!

			} else {
				$new_columns[$key] = $value;
			}
		}
		return $new_columns;
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( 'children' == $column_name )
			$this->column_count( $this->get_linked_posts( $post_id, 'issue_cpt', 'issue_tax', TRUE ) );

		else if ( 'order' == $column_name )
			$this->column_count( get_post( $post_id )->menu_order );

		else if ( 'cover' == $column_name )
			$this->column_thumb( $post_id, $this->get_image_size_key( 'issue_cpt' ) );
	}

	public function sortable_columns( $columns )
	{
		$columns['order'] = 'menu_order';
		return $columns;
	}

	public function post_updated_messages( $messages )
	{
		$messages[$this->constant( 'issue_cpt' )] = $this->get_post_updated_messages( 'issue_cpt' );
		return $messages;
	}

	public function tools_messages( $messages, $sub )
	{
		if ( $this->module->name == $sub )
			$messages['created'] = self::counted( _x( '%s Issue Post(s) Created.', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ) );
		return $messages;
	}

	public function tools_sub( $uri, $sub )
	{
		echo '<form class="settings-form" method="post" action="">';

			$this->settings_field_referer( $sub, 'tools' );

			echo '<h3>'._x( 'Magazine Tools', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ).'</h3>';
			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'From Terms', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			if ( ! empty( $_POST ) && isset( $_POST['issue_tax_check'] ) ) {

				self::tableList( array(
					'_cb'     => 'term_id',
					'term_id' => _x( 'ID', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
					'name'    => _x( 'Name', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
					'issue'   => array(
						'title' => _x( 'Issue', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column ){
							if ( $post_id = self::getPostIDbySlug( $row->slug, $this->constant( 'issue_cpt' ) ) )
								return $post_id.' &mdash; '.get_post($post_id)->post_title;
							return _x( '&mdash;&mdash;&mdash;&mdash; No Issue', 'Magazine Module', GEDITORIAL_TEXTDOMAIN );
						},
					),
					'count' => array(
						'title'    => _x( 'Count', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column ){
							if ( $post_id = self::getPostIDbySlug( $row->slug, $this->constant( 'issue_cpt' ) ) )
								return number_format_i18n( $this->get_linked_posts( $post_id, 'issue_cpt', 'issue_tax', TRUE ) );
							return number_format_i18n( $row->count );
						},
					),
					'description' => array(
						'title'    => _x( 'Description', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ),
						'callback' => 'wpautop',
						'class'    => 'description',
					),
				), gEditorialHelper::getTerms( $this->constant( 'issue_tax' ), FALSE, TRUE ) );

				echo '<br />';
			}

			submit_button( _x( 'Check Terms', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'issue_tax_check', FALSE, array( 'default' => 'default' ) ); echo '&nbsp;&nbsp;';
			submit_button( _x( 'Create Issue', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'issue_post_create', FALSE  ); echo '&nbsp;&nbsp;';
			submit_button( _x( 'Store Orders', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'issue_store_order', FALSE  ); //echo '&nbsp;&nbsp;';

			echo self::html( 'p', array(
				'class' => 'description',
			), _x( 'Check for issue terms and create corresponding issue posts.', 'Magazine Module', GEDITORIAL_TEXTDOMAIN ) );

			echo '</td></tr>';
			echo '</table>';
		echo '</form>';
	}

	public function tools_settings( $sub )
	{
		if ( ! $this->cuc( 'tools' ) )
			return;

		if ( $this->module->name == $sub ) {
			if ( ! empty( $_POST ) ) {

				$this->settings_check_referer( $sub, 'tools' );

				if ( isset( $_POST['issue_post_create'] ) ) {

					// FIXME: get term_id list from table checkbox

					$terms = gEditorialHelper::getTerms( $this->constant( 'issue_tax' ), FALSE, TRUE );
					$posts = array();

					foreach ( $terms as $term_id => $term ) {
						$issue_post_id = self::getPostIDbySlug( $term->slug, $this->constant( 'issue_cpt' ) ) ;
						if ( FALSE === $issue_post_id )
							$posts[] = self::newPostFromTerm( $term, $this->constant( 'issue_tax' ), $this->constant( 'issue_cpt' ) );
					}

					self::redirect( add_query_arg( array(
						'message' => 'created',
						'count'   => count( $posts ),
					), wp_get_referer() ) );

				} else if ( isset( $_POST['_cb'] )
					&& ( isset( $_POST['issue_store_order'] )
						|| isset( $_POST['issue_store_start'] ) ) ) {

					$meta_key = isset( $_POST['issue_store_order'] ) ? 'in_issue_order' : 'in_issue_page_start';
					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {
						foreach ( $this->get_linked_posts( NULL, 'issue_cpt', 'issue_tax', FALSE, $term_id ) as $post ) {

							if ( $post->menu_order )
								continue;

							if ( $order = gEditorial()->meta->get_postmeta( $post->ID, $meta_key, FALSE ) ) {
								wp_update_post( array(
									'ID'         => $post->ID,
									'menu_order' => $order,
								) );
								$count++;
							}
						}
					}

					self::redirect( add_query_arg( array(
						'message' => 'ordered',
						'count'   => $count,
					), wp_get_referer() ) );
				}
			}

			add_filter( 'geditorial_tools_messages', array( $this, 'tools_messages' ), 10, 2 );
			add_action( 'geditorial_tools_sub_'.$this->module->name, array( $this, 'tools_sub' ), 10, 2 );
		}

		add_filter( 'geditorial_tools_subs', array( $this, 'append_sub' ), 10, 2 );
	}
}
