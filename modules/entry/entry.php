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
			'constants'            => array(
				'entry_cpt'       => 'entry',
				'entry_archives'  => 'entries',
				'rewrite_prefix'  => 'entry', // wiki
				'section_tax'     => 'section',
				'entry_shortcode' => 'entry',
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
					'post' => array(
						'box_title'    => __( 'Entry', GEDITORIAL_TEXTDOMAIN ),
						'column_title' => __( 'Entry', GEDITORIAL_TEXTDOMAIN ),
						'select_entry' => __( '&mdash; Choose a Entry &mdash;', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'labels' => array(
					'entry_cpt' => array(
						'name'               => __( 'Entries', GEDITORIAL_TEXTDOMAIN ),
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
						'menu_name'          => __( 'Entries', GEDITORIAL_TEXTDOMAIN ),
					),

					'section_tax' => array(
						'name'                       => __( 'Sections', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => __( 'Section', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => __( 'Search Sections', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
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
						'menu_name'                  => __( 'Sections', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
			'configure_page_cb' => 'print_configure_view',
			'settings_help_tab' => array(
				'id'      => 'geditorial-entry-overview',
				'title'   => __( 'help-tab-title', GEDITORIAL_TEXTDOMAIN ),
				'content' => __( '<p>help-tab-content</p>', GEDITORIAL_TEXTDOMAIN ),
				),
			'settings_help_sidebar' => sprintf(
				__( '<p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p>', GEDITORIAL_TEXTDOMAIN ),
				'http://geminorum.ir/wordpress/geditorial/modules/entry',
				__( 'Editorial Entry Documentations', GEDITORIAL_TEXTDOMAIN ),
				'https://github.com/geminorum/gEditorial' ),

		);

		$gEditorial->register_module( $this->module_name, $args );
	}

	public function setup()
	{
		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );

			add_action( 'restrict_manage_posts', array( &$this, 'restrict_manage_posts' ) );
			add_filter( 'pre_get_posts', array( &$this, 'pre_get_posts' ) );
			add_filter( 'parse_query', array( &$this, 'parse_query' ) );
		} else {

		}
	}

	public function init()
	{
		do_action( 'geditorial_entry_init', $this->module );

		$this->do_filters();
		$this->register_post_types();
		$this->register_taxonomies();

		add_action( 'generate_rewrite_rules', array( &$this, 'generate_rewrite_rules' ) );

		add_shortcode( $this->module->constants['entry_shortcode'], array( &$this, 'shortcode_entry' ) );
	}

	public function admin_init()
	{
		add_filter( "manage_{$this->module->constants['entry_cpt']}_posts_columns", array( &$this, 'posts_columns' ) );
		add_filter( "manage_edit-{$this->module->constants['entry_cpt']}_sortable_columns", array( &$this, 'sortable_columns' ) );
		add_action( "manage_{$this->module->constants['entry_cpt']}_posts_custom_column", array( &$this, 'custom_column'), 10, 2 );
	}

	public function restrict_manage_posts()
	{
		global $typenow;

		if ( $typenow != $this->module->constants['entry_cpt'] )
			return;

		$filters = get_object_taxonomies( $typenow );
		$tax_obj = get_taxonomy( $this->module->constants['section_tax'] );

		// TODO : check if there's no section

		wp_dropdown_categories( array(
			//'show_option_all' => sprintf( _x( 'Show All %s', GEDITORIAL_TEXTDOMAIN ), $tax_obj->labels->all_items ),
			'show_option_all' => $tax_obj->labels->all_items,
			'taxonomy'        => $this->module->constants['section_tax'],
			'name'            => $tax_obj->name,
			'orderby'         => 'name',
			'selected'        => @ $_GET[$this->module->constants['section_tax']],
			'hierarchical'    => $tax_obj->hierarchical,
			'show_count'      => false,
			'hide_empty'      => true
		) );
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
		global $pagenow, $typenow;

		if ( 'edit.php' != $pagenow || $typenow != $this->module->constants['entry_cpt'] )
			return;

		if ( isset( $query->query_vars[$this->module->constants['section_tax']] ) ) {
			$section = get_term_by( 'id', $query->query_vars[$this->module->constants['section_tax']], $this->module->constants['section_tax'] );
			if ( ! empty( $section ) && ! is_wp_error( $section ) )
				$query->query_vars[$this->module->constants['section_tax']] = $section->slug;
		}
	}

	public function posts_columns( $posts_columns )
	{
		$new_columns = array();
		foreach ( $posts_columns as $key => $value ) {
			if ( $key == 'title' ) {
				$new_columns['taxonomy-section'] = __( 'Sections', GEDITORIAL_TEXTDOMAIN );
				$new_columns['entry_order'] = _x( 'O', 'manage_posts_columns', GEDITORIAL_TEXTDOMAIN );
				$new_columns[$key] = $value;
				//$new_columns['entry_section'] = __( 'Sections', GEDITORIAL_TEXTDOMAIN );
			//} else if ( in_array( $key, array( 'author', 'date', 'comments' ) ) ) {
			} else if ( in_array( $key, array( 'author', 'taxonomy-section' ) ) ) {
				continue; // hehe!
			} else {
				$new_columns[$key] = $value;
			}
		}
		return $new_columns;
	}

	public function sortable_columns( $columns )
	{
		$columns['entry_order'] = 'menu_order';
		return $columns;
	}


	public function custom_column( $column_name, $post_id )
	{
		/*
		if ( 'entry_section' == $column_name ) {
		//    $post_type = get_post_type( $post_id );
			$terms = get_the_terms( $post_id, $this->_constants['section_tax'] );
			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$post_terms[] = "<a href='edit.php?post_type={$this->_constants['entry_cpt']}&{$this->_constants['section_tax']}={$term->slug}'> " . esc_html(sanitize_term_field('name', $term->name, $term->term_id, $this->_constants['section_tax'], 'edit')) . '</a>';
				}
				echo join( ', ', $post_terms );
			}
			else echo '<i>No terms.</i>';
		} else
		*/

		if ( 'entry_order' == $column_name ) {
			$post = get_post( $post_id );
			if ( ! empty( $post->menu_order ) )
				echo number_format_i18n( $post->menu_order );
			else
				_e( '<span title="No Order">&mdash;</span>', GEDITORIAL_TEXTDOMAIN );

		}
	}

	public function register_post_types()
	{
		register_post_type( $this->module->constants['entry_cpt'], array(
			'labels' => $this->module->strings['labels']['entry_cpt'],
			'hierarchical' => false,
			'supports' => array(
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
			'taxonomies'          => array( $this->module->constants['section_tax'] ),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 4,
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => $this->module->constants['entry_archives'],
			'query_var'           => $this->module->constants['entry_cpt'],
			'can_export'          => true,
			'rewrite'             => array(
				'slug' => $this->module->constants['entry_cpt'],
				'with_front' => false
			),
			'map_meta_cap' => true,
		) );
	}

	public function register_taxonomies()
	{
		register_taxonomy( $this->module->constants['section_tax'], array( $this->module->constants['entry_cpt'] ), array(
			'labels'                => $this->module->strings['labels']['section_tax'],
			'public'                => true,
			'show_in_nav_menus'     => true,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'show_tagcloud'         => false,
			'hierarchical'          => true,
			'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
			'rewrite'               => array(
				'slug'         => $this->module->constants['section_tax'],
				'hierarchical' => true,
				'with_front'   => true
			),
			'query_var' => true,
			'capabilities' => array(
				'manage_terms' => 'edit_others_posts',
				'edit_terms'   => 'edit_others_posts',
				'delete_terms' => 'edit_others_posts',
				'assign_terms' => 'edit_published_posts'
			)
		) );
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

	public function shortcode_series( $atts, $content = null, $tag = '' )
	{
		global $post;
		$error = $the_term = false;

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
			'order_before'  => false,
			'order_sep'     => ' - ',
			'order_zeroise' => false,
			'context'       => null,
		), $atts, $this->module->constants['entry_shortcode'] );


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
				$error = true;
			}
		} else {
			$error = true;
		}

		if ( $error )
			return $content;

		$html = '<div>';
		if ( $args['title'] && 'def' == $args['title'] ) {
			if ( $the_term )
				$args['title'] = $the_term->name;
			else
				$args['title'] = false;
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

	function sections( $sections = array(), $active_section = null )
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
