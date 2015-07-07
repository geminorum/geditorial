<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialAlphabets extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'alphabets';
	var $meta_key    = '_ge_alphabets';
	var $cookie      = 'geditorial-alphabets';

	function __construct()
	{
		global $gEditorial;

		$args = array(
			'title'                => __( 'Alphabets', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'A to Z Glossaries for Post Types, Taxonomies and Users', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'             => 'format-alphabets',
			'slug'                 => 'alphabets',
			'load_frontend'        => TRUE,

			'constants' => array(
				'alphabets_tax'            => 'alphabets_tax',
				'tax_alphabets_shortcode'  => 'tax-alphabets',
				'post_alphabets_shortcode' => 'post-alphabets',
			),

			'default_options' => array(
				'enabled' => FALSE,
				'post_types' => array(
					'post' => FALSE,
					'page' => FALSE,
				),
				'settings' => array(
				),
			),
			'settings' => array(
				'post_types_option' => 'post_types_option',
				'taxonomies_option' => 'taxonomies_option',
				// 'alphabet_defaults' => 'alphabet_defaults', // FIXME: add option to display filtered default alphabets
			),
			'strings' => array(
				'labels' => array(
					'alphabets_tax' => array(
						'menu_name'     => __( 'Glossary', GEDITORIAL_TEXTDOMAIN ),
						'name'          => __( 'Glossary', GEDITORIAL_TEXTDOMAIN ),
						'popular_items' => NULL,
					),
				),
				'terms' => array(
					'alphabets_tax' => array(
						'alphabet_a' => __( 'A', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_b' => __( 'B', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_c' => __( 'C', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_d' => __( 'D', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_e' => __( 'E', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_f' => __( 'F', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_g' => __( 'G', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_h' => __( 'H', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_i' => __( 'I', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_j' => __( 'J', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_k' => __( 'K', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_l' => __( 'L', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_m' => __( 'M', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_n' => __( 'N', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_o' => __( 'O', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_p' => __( 'P', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_q' => __( 'Q', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_r' => __( 'R', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_s' => __( 'S', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_t' => __( 'T', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_u' => __( 'U', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_x' => __( 'X', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_y' => __( 'Y', GEDITORIAL_TEXTDOMAIN ),
						'alphabet_z' => __( 'Z', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
			'configure_page_cb' => 'print_configure_view',
			'settings_help_tabs' => array(
				array(
				'id'       => 'geditorial-alphabets-overview',
				'title'    => __( 'help-tab-title', GEDITORIAL_TEXTDOMAIN ),
				'content'  => __( '<p>help-tab-content</p>', GEDITORIAL_TEXTDOMAIN ),
				'callback' => FALSE,
			),
		),
		'settings_help_sidebar' => sprintf(
			__( '<p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p>', GEDITORIAL_TEXTDOMAIN ),
			'http://geminorum.ir/wordpress/geditorial/modules/alphabets',
			__( 'Editorial Alphabets Documentations', GEDITORIAL_TEXTDOMAIN ),
			'https://github.com/geminorum/gEditorial' ),
		);

		$gEditorial->register_module( $this->module_name, $args );
	}

	public function setup()
	{
		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );
			add_filter( 'admin_menu', array( &$this, 'admin_menu' ) );
			add_filter( 'parent_file', array( &$this, 'parent_file' ) );
		}

		$this->_taxonomies_excluded = array( $this->module->constants['alphabets_tax'] );
	}

	public function init()
	{
		do_action( 'geditorial_alphabets_init', $this->module );

		$this->do_filters();
		$this->register_taxonomies();

		add_shortcode( $this->module->constants['tax_alphabets_shortcode'], array( $this, 'shortcode_tax_alphabets' ) );
		add_shortcode( $this->module->constants['post_alphabets_shortcode'], array( $this, 'shortcode_post_alphabets' ) );
	}

	public function admin_init()
	{
		// SUPPORTED tax bulk actions with gNetworkTaxonomy
		add_filter( 'gnetwork_taxonomy_bulk_actions', array( &$this, 'taxonomy_bulk_actions' ), 12, 2 );
		add_filter( 'gnetwork_taxonomy_bulk_callback', array( &$this, 'taxonomy_bulk_callback' ), 12, 3 );
		add_filter( 'gnetwork_taxonomy_bulk_input', array( &$this, 'taxonomy_bulk_input' ), 12, 3 );
	}

	public function admin_menu()
	{
		$alphabets_tax = get_taxonomy( $this->module->constants['alphabets_tax'] );
		add_options_page(
			esc_attr( $alphabets_tax->labels->menu_name ),
			esc_attr( $alphabets_tax->labels->menu_name ),
			$alphabets_tax->cap->manage_terms,
			'edit-tags.php?taxonomy='.$alphabets_tax->name
		);
	}

	public function parent_file( $parent_file = '' )
	{
		global $pagenow;

		if ( ! empty( $_GET['taxonomy'] )
			&& $_GET['taxonomy'] == $this->module->constants['alphabets_tax']
			&& $pagenow == 'edit-tags.php' )
				$parent_file = 'options-general.php';

		return $parent_file;
	}

	public function register_taxonomies()
	{
		$editor = current_user_can( 'edit_others_posts' );

		register_taxonomy( $this->module->constants['alphabets_tax'],
			array_merge( $this->post_types(), $this->taxonomies() ),
			array(
				'labels'                => $this->module->strings['labels']['alphabets_tax'],
				'public'                => FALSE,
				'show_in_nav_menus'     => FALSE,
				'show_ui'               => $editor,
				'show_admin_column'     => $editor,
				'show_tagcloud'         => FALSE,
				'hierarchical'          => FALSE,
				'query_var'             => TRUE,
				'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
				'rewrite'               => array(
					'slug' => $this->module->constants['alphabets_tax'],
					'hierarchical' => FALSE,
					'with_front' => TRUE
				),
				'capabilities' => array(
					'manage_terms' => 'edit_others_posts',
					'edit_terms'   => 'edit_others_posts',
					'delete_terms' => 'edit_others_posts',
					'assign_terms' => 'edit_published_posts'
				)
			) );
	}

	public function register_settings( $page = NULL )
	{
		if ( isset( $_POST['install_def_alphabets'] ) )
			$this->insert_default_terms();

		parent::register_settings( $page );
		$this->register_settings_button( 'install_def_alphabets', __( 'Install Default Alphabets', GEDITORIAL_TEXTDOMAIN ) );
	}

	private function insert_default_terms()
	{
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->module->options_group_name.'-options' ) )
			return;

		$added = gEditorialHelper::insertDefaultTerms(
			$this->module->constants['alphabets_tax'],
			$this->module->strings['terms']['alphabets_tax']
		);

		wp_redirect( add_query_arg( 'message', $added ? 'insert_default_terms' : 'error_default_terms' ) );
		exit;
	}

	public function taxonomy_bulk_actions( $actions, $taxonomy )
	{
		if ( in_array( $taxonomy, $this->taxonomies() ) ) {
			$actions['add_to_glossary'] = __( 'Add to Glossary', GEDITORIAL_TEXTDOMAIN );
		}
		return $actions;
	}

	public function taxonomy_bulk_callback( $callback, $action, $taxonomy )
	{
		if ( 'add_to_glossary' == $action && in_array( $taxonomy, $this->taxonomies() ) )
			return array( &$this, 'bulk_add_to_glossary' );

		return $callback;
	}

	public function taxonomy_bulk_input( $callback, $key, $taxonomy )
	{
		if ( 'add_to_glossary' == $key && in_array( $taxonomy, $this->taxonomies() ) )
			return array( &$this, 'bulk_input_glossary' );

		return $callback;
	}

	public function bulk_add_to_glossary( $term_ids, $taxonomy )
	{
		$glossary_id = $_REQUEST['new_glossary'];

		foreach ( $term_ids as $term_id ) {

			$ret = wp_set_object_terms( $term_id, array( intval( $glossary_id ) ), $this->module->constants['alphabets_tax'], FALSE );

			if ( is_wp_error( $ret ) )
				return FALSE;

			clean_object_term_cache( $term_id, $this->module->constants['alphabets_tax'] );
		}

		return TRUE;
	}

	public function bulk_input_glossary( $taxonomy )
	{
		$terms = gEditorialHelper::getTerms( $this->module->constants['alphabets_tax'], FALSE, TRUE );

		echo '<select class="postform" name="new_glossary">';
		echo '<option value="0">'. __( '&mdash; Select &mdash;', GEDITORIAL_TEXTDOMAIN ).'</option>'."\n";
		foreach ( $terms as $term_id => $term )
			echo '<option value="'.$term_id.'">'.$term->name.'</option>'."\n";
		echo '</select>';
	}

	// FIXME: DRAFT!
	public function shortcode_tax_alphabets( $atts, $content = NULL, $tag = '' )
	{
		global $post;

		$args = shortcode_atts( array(
			'taxonomy'  => NULL,

			'title'     => '',
			'title_tag' => 'h3',
			'class'     => '',
			'order'     => 'ASC',
			'orderby'   => 'term_order, name',
			'exclude'   => TRUE, // or array
			'before'    => '',
			'after'     => '',
			'context'   => NULL,
			'args'      => array(),
		), $atts, $this->module->constants['multiple_series_shortcode'] );

		if ( FALSE === $args['context'] )
			return NULL;



			// get_objects_in_term( $term_ids, $taxonomies, $args );

			// http://www.smashingmagazine.com/2014/08/27/customizing-wordpress-archives-categories-terms-taxonomies/

			// http://stackoverflow.com/questions/2003666/order-a-mysql-query-alphabetically
			// http://stackoverflow.com/questions/10446787/how-can-i-control-utf-8-ordering-in-mysql


		return $content;
	}

	// FIXME: UNFINISHED!
	public function shortcode_post_alphabets( $atts, $content = NULL, $tag = '' )
	{
		return $content;
	}

	// get supported alphabets from filter for each supported cpt/tax/user
	// store like : cpt_post_a, tax_people_a, user_aleph, cpt_post_jim, tax_post_tag_kaaf
	// short code to render lists based on alphabets
	// tools to build, rebuild list
	// check filters for each supported cpt/tax/user name first letter so that unicode works!
	// check new post/tax/user and add them to each glossory

}
