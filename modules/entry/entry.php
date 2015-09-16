<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialEntry extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'entry';
	var $meta_key    = '_ge_entry';

	public function __construct()
	{
		global $gEditorial;

		$args = array(
			'title'                => __( 'Entry', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Posts Entries, Wiki-like', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( '', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'             => 'media-document',
			'slug'                 => 'entry',
			'load_frontend'        => TRUE,

			'constants' => array(
				'entry_cpt'         => 'entry',
				'entry_cpt_archive' => 'entries',
				'rewrite_prefix'    => 'entry', // wiki
				'section_tax'       => 'section',
				'section_shortcode' => 'section',
			),

			'supports' => array(
				'entry_cpt' => array(
					'title',
					'editor',
					'excerpt',
					'author',
					'thumbnail',
					'trackbacks',
					'custom-fields',
					'comments',
					'revisions',
					'page-attributes',
				),
			),

			'default_options' => array(
				'enabled' => FALSE,
				'post_types' => array(
					'post' => TRUE,
					'page' => FALSE,
				),
				'post_fields' => array(
					'in_entry_title' => TRUE,
					'in_entry_order' => TRUE,
					'in_entry_desc'  => FALSE,
				),
				'settings' => array(),
			),

			'settings' => array(
				'_general' => array(
					array(
						'field'       => 'multiple',
						'title'       => __( 'Multiple Entry', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Using multiple entry for each post.', GEDITORIAL_TEXTDOMAIN ),
						'default'     => 0,
					),
					array(
						'field'       => 'editor_button',
						'title'       => _x( 'Editor Button', 'Entry Editor Button', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Adding an Editor Button to insert shortcodes', GEDITORIAL_TEXTDOMAIN ),
						'default'     => 1,
					),
				),
				'post_types_option' => 'post_types_option',
				'post_types_fields' => 'post_types_fields',
			),
			'strings' => array(
				'titles' => array(
					'post' => array(
						'in_entry_title' => __( 'Title', GEDITORIAL_TEXTDOMAIN ),
						'in_entry_order' => __( 'Order', GEDITORIAL_TEXTDOMAIN ),
						'in_entry_desc'  => __( 'Description', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'descriptions' => array(
					'post' => array(
						'in_entry_title' => __( 'In Entry Title', GEDITORIAL_TEXTDOMAIN ),
						'in_entry_order' => __( 'In Entry Order', GEDITORIAL_TEXTDOMAIN ),
						'in_entry_desc'  => __( 'In Entry Description', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'misc' => array(
					'entry_cpt' => array(
						'section_column_title' => _x( 'Section', '[Entry Module] Column Title', GEDITORIAL_TEXTDOMAIN ),
						'order_column_title'   => _x( 'O', '[Entry Module] Column Title', GEDITORIAL_TEXTDOMAIN ),
					),

					'meta_box_title'     => __( 'Entry', GEDITORIAL_TEXTDOMAIN ),

					'post' => array(
						'box_title'    => __( 'Entry', GEDITORIAL_TEXTDOMAIN ),
						'column_title' => __( 'Entry', GEDITORIAL_TEXTDOMAIN ),
						'select_entry' => __( '&mdash; Choose a Entry &mdash;', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'labels' => array(
					'entry_cpt' => array(
						'name'               => __( 'Entries', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'          => __( 'Entries', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'      => __( 'Entry', GEDITORIAL_TEXTDOMAIN ),
						'add_new'            => __( 'Add New', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'       => __( 'Add New Entry', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'          => __( 'Edit Entry', GEDITORIAL_TEXTDOMAIN ),
						'new_item'           => __( 'New Entry', GEDITORIAL_TEXTDOMAIN ),
						'view_item'          => __( 'View Entry', GEDITORIAL_TEXTDOMAIN ),
						'search_items'       => __( 'Search Entries', GEDITORIAL_TEXTDOMAIN ),
						'not_found'          => __( 'No entries found', GEDITORIAL_TEXTDOMAIN ),
						'not_found_in_trash' => __( 'No entries found in Trash', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'  => __( 'Parent Entry:', GEDITORIAL_TEXTDOMAIN ),
					),

					'section_tax' => array(
						'name'                       => __( 'Sections', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => __( 'Sections', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => __( 'Section', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => __( 'Search Sections', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => __( 'All Sections', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => __( 'Parent Section', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => __( 'Parent Section:', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => __( 'Edit Section', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => __( 'Update Section', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => __( 'Add New Section', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => __( 'New Section Name', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => __( 'Separate sections with commas', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => __( 'Add or remove section', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => __( 'Choose from the most used sections', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
					),
				),
			),
			'configure_page_cb' => 'print_configure_view',
		);

		$gEditorial->register_module( $this->module_name, $args );
	}

	public function setup()
	{
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'generate_rewrite_rules', array( &$this, 'generate_rewrite_rules' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );

			add_action( 'restrict_manage_posts', array( &$this, 'restrict_manage_posts' ) );
			add_filter( 'pre_get_posts', array( &$this, 'pre_get_posts' ) );
			add_filter( 'parse_query', array( &$this, 'parse_query' ) );
		}
	}

	public function init()
	{
		do_action( 'geditorial_entry_init', $this->module );

		$this->do_filters();

		$this->register_post_type( 'entry_cpt', array(), array( 'post_tag' ) );
		$this->register_taxonomy( 'section_tax', array(
			'hierarchical' => TRUE,
		), $this->module->constants['entry_cpt'] );

		$this->register_shortcode( 'section_shortcode' );
	}

	public function admin_init()
	{
		add_filter( "manage_{$this->module->constants['entry_cpt']}_posts_columns", array( &$this, 'manage_posts_columns' ) );
		add_filter( "manage_edit-{$this->module->constants['entry_cpt']}_sortable_columns", array( &$this, 'sortable_columns' ) );
		add_action( "manage_{$this->module->constants['entry_cpt']}_posts_custom_column", array( &$this, 'posts_custom_column'), 10, 2 );
	}

	public function restrict_manage_posts()
	{
		global $wp_query;

		$post_type = gEditorialHelper::getCurrentPostType();

		if ( $post_type == $this->module->constants['entry_cpt'] ) {

			$tax = $this->module->constants['section_tax'];
			if ( $obj = get_taxonomy( $tax ) ) {

				wp_dropdown_categories( array(
					'show_option_all' => $obj->labels->all_items,
					'taxonomy'        => $tax,
					'name'            => $obj->name,
					'orderby'         => 'name',
					'selected'        => ( isset( $wp_query->query[$tax] ) ? $wp_query->query[$tax] : '' ),
					'hierarchical'    => $obj->hierarchical,
					'show_count'      => FALSE,
					'hide_empty'      => TRUE
				) );
			}
		}
	}

	public function pre_get_posts( $wp_query )
	{
		if ( is_admin() && isset( $wp_query->query['post_type'] ) ) {
			if ( $this->module->constants['entry_cpt'] == $wp_query->query['post_type'] ) {
				if ( ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', 'menu_order' );
				if ( ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', 'DESC' );
			}
		}
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query->query_vars, array(
			'section_tax',
		), 'entry_cpt' );
	}

	public function manage_posts_columns( $posts_columns )
	{
		$new_columns = array();

		foreach ( $posts_columns as $key => $value ) {

			if ( $key == 'title' ) {
				$new_columns['taxonomy-section'] = $this->get_column_title( 'section', 'entry_cpt' );
				$new_columns['order']            = $this->get_column_title( 'order', 'entry_cpt' );
				$new_columns[$key]               = $value;

			} else if ( in_array( $key, array( 'author', 'taxonomy-section' ) ) ) {
				continue; // he he!

			} else {
				$new_columns[$key] = $value;
			}
		}
		return $new_columns;
	}

	public function sortable_columns( $columns )
	{
		$columns['order'] = 'menu_order';
		return $columns;
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( 'order' == $column_name )
			$this->column_count( get_post( $post_id )->menu_order );
	}

	public function generate_rewrite_rules( $wp_rewrite )
	{
		$new_rules = array(
			$this->module->constants['rewrite_prefix'].'/(.*)/(.*)'
				=> 'index.php?post_type='.$this->module->constants['entry_cpt']
				  .'&'.$this->module->constants['section_tax'].'='.$wp_rewrite->preg_index( 1 )
				  .'&'.$this->module->constants['entry_cpt'].'='.$wp_rewrite->preg_index( 2 ),
		);

		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
	}

	public function section_shortcode( $atts, $content = NULL, $tag = '' )
	{
		global $post;
		$error = $the_term = FALSE;

		$args = shortcode_atts( array(
			//'section' => '',
			'slug'          => '',
			'id'            => '',
			'title'         => 'def',
			'title_wrap'    => 'h3',
			'list'          => 'ul',
			'limit'         => -1,
			'order'         => 'ASC',
			'orderby'       => 'menu_order',
			// 'future'        => TRUE,
			'li_before'     => '',
			'order_before'  => FALSE,
			'order_sep'     => ' - ',
			'order_zeroise' => FALSE,
			'context'       => NULL,
		), $atts, $this->module->constants['section_shortcode'] );


		if ( $args['id'] ) {
			$the_term = get_term_by( 'id', $args['id'], $this->module->constants['section_tax'] );
			$tax_query = array( array(
				'taxonomy' => $this->module->constants['section_tax'],
				'field' => 'id',
				'terms' => array( $args['id'] ),
			) );
		} else if ( $args['slug'] ) {
			$the_term = get_term_by( 'slug', $args['slug'], $this->module->constants['section_tax'] );
			$tax_query = array( array(
				'taxonomy' => $this->module->constants['section_tax'],
				'field' => 'slug',
				'terms' => array( $args['slug'] ),
			) );
		} else if ( $post->post_type == $this->module->constants['entry_cpt'] ) {
			$terms = get_the_terms( $post->ID, $this->module->constants['section_tax'] );
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term )
					$term_list[] = $term->slug;
				$tax_query = array( array(
					'taxonomy' => $this->module->constants['section_tax'],
					'field' => 'slug',
					'terms' => $term_list,
				) );
			} else {
				$error = TRUE;
			}
		} else {
			$error = TRUE;
		}

		if ( $error )
			return $content;

		$html = '<div>';
		if ( $args['title'] && 'def' == $args['title'] ) {
			if ( $the_term )
				$args['title'] = $the_term->name;
			else
				$args['title'] = FALSE;
		}
		if ( $args['title'] )
			$html .= '<'.$args['title_wrap'].'>'.esc_html( $args['title'] ).'</'.$args['title_wrap'].'>';
		$html .= '<ul>';

		$entry_query_args = array(
			'tax_query' => $tax_query,
			'posts_per_page' => $args['limit'],
			'orderby' => $args['orderby'],
			'order' => $args['order'],
			//'post_status' => $post_status
		);
		$entry_query = new WP_Query( $entry_query_args );

		if ( $entry_query->have_posts() ) {
			while ( $entry_query->have_posts() ) {
				$entry_query->the_post();
				$order_before = ( $args['order_before'] ? number_format_i18n( $args['order_zeroise'] ? zeroise( $post->menu_order, $args['order_zeroise'] ) : $post->menu_order ).$args['order_sep'] : '' );
				$html .= '<li>'.$args['li_before'].'<a href="'.get_permalink().'">'.$order_before.get_the_title().'</a></li>';
			}
			$html .= '</ul></div>';
			wp_reset_postdata();
			return $html;
		}
		return $content;
	}

	///////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////
	/////MUST REWRITE//////////////////////////////////////////
	///////////////////////////////////////////////////////////

	function sections( $sections = array(), $active_section = NULL )
	{
		$taxonomy = 'section';
		$link_class = '';
		if ( empty( $sections ) ) {
			$link_class = 'root';
			$sections = get_terms( $taxonomy, array( 'parent' => 0, 'hide_empty' => 0 ) );
			$active_section = self::active_section();
			echo '<ul id="kb-sections" class="unstyled">';
		}
		if ( empty( $active_section ) ) {
			$active_section = '';
		}
		foreach ( $sections as $section ) {
			$toggle = '';
			$section_children = get_terms( $taxonomy, array( 'parent' => $section->term_id, 'hide_empty' => 0 ) );
			if ( !empty( $section_children ) && $link_class != 'root' ) {
				$toggle = '<i class="toggle"></i>';
			}
			echo '<li class="'.( $section->term_id == $active_section ? 'active' : '' ).'">';
			echo '<a  href="'.get_term_link( $section, $taxonomy ).'" class="'.$link_class.'" rel="'.$section->slug.'">'.$toggle.$section->name.'</a>';

			if ( !empty( $section_children ) ) {
				echo '<ul id="'.$section->slug.'" class="children">';
				self::sections( $section_children, $active_section );
			}
			echo "</li>";
		}
		echo "</ul>";
	}

	function active_section()
	{
		$taxonomy = 'section';
		$current_section = '';
		if ( is_single() ) {
			$sections = explode( '/', get_query_var( $taxonomy ) );
			$section_slug = end( $sections );
			if ( $section_slug != '' ) {
				$term = get_term_by( 'slug', $section_slug, $taxonomy );
			} else {
				$terms = wp_get_post_terms( get_the_ID(), $taxonomy );
				$term = $terms[0];
			}
			if ( $term )
				$current_section = $term->term_id;
		} else {
			$term = get_term_by( 'slug', get_query_var( $taxonomy ), get_query_var( 'taxonomy' ) );
			if ( $term )
				$current_section = $term->term_id;
		}
		return $current_section;
	}

	function article_permalink( $article_id, $section_id )
	{
		$taxonomy = 'section';
		$article = get_post( $article_id );
		$section = get_term( $section_id, $taxonomy );
		$section_ancestors = get_ancestors( $section->term_id, $taxonomy );
		krsort( $section_ancestors );
		$permalink = '<a href="/entry/';
		foreach ( $section_ancestors as $ancestor ):
			$section_ancestor = get_term( $ancestor, $taxonomy );
			$permalink.= $section_ancestor->slug.'/';
		endforeach;
		$permalink.= $section->slug.'/'.$article->post_name.'/" >'.$article->post_title.'</a>';
		return $permalink;
	}

	// JUST COPY : https://wordpress.org/plugins/word-highlighter/
	// add_filter( 'the_content', 'apply_word_highligher' );
	function apply_word_highligher( $content )
	{

		 global $post;

		$post_type=get_post_type($post->ID);

		$options = get_option('highlightedtext_options');

		if ($options['highlightedtext_type']!=$post_type and $options['highlightedtext_type']!='both')
		return $content;
		//echo "<pre>";print_r($options);
		if ($options['highlightedtext_active']) {

		//echo "here=".$text."<br />";
		$text_name=explode(',',trim($options['highlightedtext_name']));
		//echo "<pre>";print_r($text_name);
		if (!empty($text_name)){
		for($i=0;$i<count($text_name);$i++){
		if (trim($text_name[$i])!=''){

			if (preg_match('~\b' . preg_quote($text_name[$i], '~') . '\b(?![^<]*?>)~',$content,$result))
			{
				$rep_html='<label class="wh_highlighted">'.$text_name[$i].'</label>';
				if ($options['highlightedtext_case'])
				{

					$content = preg_replace('~\b' . preg_quote($text_name[$i], '~') . '\b(?![^<]*?>)~',$rep_html,$content);

				}
				else
				{
						$content = preg_replace('~\b' . preg_quote($text_name[$i], '~') . '\b(?![^<]*?>)~i',$rep_html,$content);

				}
			}

		}
		}
		}
		}


		return $content;
	}


}

// http://www.456bereastreet.com/archive/201010/creating_a_hierarchical_submenu_in_wordpress/
// http://wordpress.mfields.org/2010/selective-page-hierarchy-for-wp_list_pages/
