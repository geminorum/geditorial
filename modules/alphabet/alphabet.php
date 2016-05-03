<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialAlphabet extends gEditorialModuleCore
{

	public static function module()
	{
		if ( ! self::isDev() )
			return FALSE;

		return array(
			'name'     => 'alphabet',
			'title'    => _x( 'Alphabet', 'Alphabet Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'A to Z Glossaries for Post Types, Taxonomies and Users', 'Alphabet Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon' => 'editor-textcolor',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'shortcode_support',
				'editor_button',
			),
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'alphabet_tax'            => 'alphabet_tax',
			'tax_alphabet_shortcode'  => 'tax-alphabet',
			'post_alphabet_shortcode' => 'post-alphabet',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'alphabet_tax' => array(
					'tax_form_label'   => _x( 'Alphabet', 'Alphabet Module', GEDITORIAL_TEXTDOMAIN ),
					'tax_form_desc'    => _x( 'This term is in the selected alphabet', 'Alphabet Module', GEDITORIAL_TEXTDOMAIN ),
					'tax_column_title' => _x( 'A', 'Alphabet Module: Tax Column Title', GEDITORIAL_TEXTDOMAIN ),
					'tax_column_empty' => _x( 'No Alphabet Assigned', 'Alphabet Module: Tax Column Empty', GEDITORIAL_TEXTDOMAIN ),
					'show_option_none' => _x( '&mdash; Choose an Alphabet &mdash;', 'Alphabet Module', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'labels' => array(
				'alphabet_tax' => gEditorialHelper::generateTaxonomyLabels(
					_nx_noop( 'Alphabet', 'Alphabets', 'Alphabet Module: Alphabet Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
					array(
						'menu_name' => _x( 'Glossary', 'Alphabet Module: Alphabet Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
					)
				),
			),
			'terms' => array(
				'alphabet_tax' => array(
					'alphabet_a' => 'A',
					'alphabet_b' => 'B',
					'alphabet_c' => 'C',
					'alphabet_d' => 'D',
					'alphabet_e' => 'E',
					'alphabet_f' => 'F',
					'alphabet_g' => 'G',
					'alphabet_h' => 'H',
					'alphabet_i' => 'I',
					'alphabet_j' => 'J',
					'alphabet_k' => 'K',
					'alphabet_l' => 'L',
					'alphabet_m' => 'M',
					'alphabet_n' => 'N',
					'alphabet_o' => 'O',
					'alphabet_p' => 'P',
					'alphabet_q' => 'Q',
					'alphabet_r' => 'R',
					'alphabet_s' => 'S',
					'alphabet_t' => 'T',
					'alphabet_u' => 'U',
					'alphabet_x' => 'X',
					'alphabet_y' => 'Y',
					'alphabet_z' => 'Z',
				),
			),
		);
	}

	public function setup( $partials = array() )
	{
		parent::setup();

		if ( is_admin() ) {
			add_filter( 'admin_menu', array( $this, 'admin_menu' ) );
			add_filter( 'parent_file', array( $this, 'parent_file' ) );
		}
	}

	public function init()
	{
		do_action( 'geditorial_alphabet_init', $this->module );

		$this->do_globals();

		$this->taxonomies_excluded = array( $this->constant( 'alphabet_tax' ) );

		$editor = current_user_can( 'edit_others_posts' );
		$this->register_taxonomy( 'alphabet_tax', array(
			'show_ui'           => $editor,
			'show_admin_column' => $editor,
		), array_merge( $this->post_types(), $this->taxonomies() ) );

		$this->register_shortcode( 'tax_alphabet_shortcode' );
		$this->register_shortcode( 'post_alphabet_shortcode' );
	}

	public function current_screen( $screen )
	{
		if ( ( 'edit-tags' == $screen->base
			|| 'term' == $screen->base )
				&& in_array( $screen->taxonomy, $this->taxonomies() ) ) {

			// add_action( $screen->taxonomy.'_pre_add_form', array( $this, 'tax_pre_add_form' ) );
			add_action( $screen->taxonomy.'_add_form_fields', array( $this, 'tax_add_form_fields' ), 8, 1 );
			add_action( $screen->taxonomy.'_edit_form_fields', array( $this, 'tax_edit_form_fields' ), 8, 2 );

			add_action( 'created_'.$screen->taxonomy, array( $this, 'edited_tax' ), 10, 2 );
			add_action( 'edited_'.$screen->taxonomy, array( $this, 'edited_tax' ),10, 2 );

			add_filter( 'manage_edit-'.$screen->taxonomy.'_columns', array( $this, 'tax_manage_edit_columns' ) );
			add_action( 'manage_'.$screen->taxonomy.'_custom_column', array( $this, 'tax_manage_custom_column' ), 10, 3 );
			// add_filter( $screen->taxonomy.'_row_actions', array( $this, 'tax_row_actions' ), 12, 2 );
			// add_action( 'after-'.$screen->taxonomy.'-table', array( $this, 'tax_after_table' ) );

			// SUPPORTED tax bulk actions with gNetworkTaxonomy
			add_filter( 'gnetwork_taxonomy_bulk_actions', array( $this, 'taxonomy_bulk_actions' ), 12, 2 );
			add_filter( 'gnetwork_taxonomy_bulk_callback', array( $this, 'taxonomy_bulk_callback' ), 12, 3 );
			add_filter( 'gnetwork_taxonomy_bulk_input', array( $this, 'taxonomy_bulk_input' ), 12, 3 );

		} else if ( 'edit' == $screen->base
			&& in_array( $screen->post_type, $this->post_types() ) ) {


		} else if ( gEditorialHelper::isTools( $screen ) ) {

			if ( current_user_can( 'edit_others_posts' ) )
				add_action( 'geditorial_tools_settings', array( $this, 'tools_settings' ) );
		}
	}

	public function admin_menu()
	{
		$alphabet_tax = get_taxonomy( $this->constant( 'alphabet_tax' ) );

		add_options_page(
			esc_attr( $alphabet_tax->labels->menu_name ),
			esc_attr( $alphabet_tax->labels->menu_name ),
			$alphabet_tax->cap->manage_terms,
			'edit-tags.php?taxonomy='.$alphabet_tax->name
		);
	}

	public function parent_file( $parent_file = '' )
	{
		global $pagenow;

		if ( ! empty( $_GET['taxonomy'] )
			&& $_GET['taxonomy'] == $this->constant( 'alphabet_tax' )
			&& ( $pagenow == 'edit-tags.php'
				|| $pagenow == 'term.php' ) )
					$parent_file = 'options-general.php';

		return $parent_file;
	}

	public function register_settings( $page = NULL )
	{
		if ( ! $this->is_register_settings( $page ) )
			return;

		if ( isset( $_POST['install_def_alphabet_tax'] ) )
			$this->insert_default_terms( 'alphabet_tax' );

		parent::register_settings( $page );
		$this->register_settings_button( 'install_def_alphabet_tax', _x( 'Install Default Alphabet', 'Alphabet Module', GEDITORIAL_TEXTDOMAIN ) );
	}

	// FIXME: styling
	public function tax_add_form_fields( $taxonomy )
	{
		echo '<div class="form-field">';
		echo '<label for="geditorial-alphabet_term_id">';
			echo $this->get_string( 'tax_form_label', 'alphabet_tax', 'misc' );
		echo '</label>';
		echo '<div class="field-wrap field-wrap-select">';

		// TODO: custom waker to use description plus title for each option

		wp_dropdown_categories( array(
			'taxonomy'         => $this->constant( 'alphabet_tax' ),
			'show_option_none' => $this->get_string( 'show_option_none', 'alphabet_tax', 'misc' ),
			'name'             => 'geditorial-alphabet_term_id',
			'id'             => 'geditorial-alphabet_term_id',
			'class'            => 'geditorial-admin-dropbown',
			'show_count'       => 0,
			'hide_empty'       => 0,
			'echo'             => 1,
		) );

		echo '</div>';
		echo '<p class="description">';
			echo $this->get_string( 'tax_form_desc', 'alphabet_tax', 'misc' );
		echo '</p>';
		echo '</div>';
	}

	// FIXME: styling
	public function tax_edit_form_fields( $term, $taxonomy )
	{
		$alphabet_id   = 0;
		$alphabet_tax = $this->constant( 'alphabet_tax' );
		$alphabet_term = wp_get_object_terms( $term->term_id, $alphabet_tax );

		if ( ! is_wp_error( $alphabet_term ) && count( $alphabet_term ) )
			$alphabet_id = $alphabet_term[0]->term_id;

		echo '<tr class="form-field"><th scope="row" valign="top"><label>';
			echo $this->get_string( 'tax_form_label', 'alphabet_tax', 'misc' );
		echo '</label></th><td>';
		echo '<div class="field-wrap field-wrap-select">';

		wp_dropdown_categories( array(
			'taxonomy'         => $alphabet_tax,
			'selected'         => $alphabet_id,
			'show_option_none' => $this->get_string( 'show_option_none', 'alphabet_tax', 'misc' ),
			'name'             => 'geditorial-alphabet_term_id',
			'class'            => 'geditorial-admin-dropbown',
			'show_count'       => 0,
			'hide_empty'       => 0,
			'echo'             => 1,
		) );

		echo '</div>';
		echo '<p class="description">';
			echo $this->get_string( 'tax_form_desc', 'alphabet_tax', 'misc' );
		echo '</p>';

			// gnetwork_dump($alphabet_term);

		echo '</td></tr>';
	}

	// on edit-tags.php / edit-tags.php?action=edit
	// save new term / save edited term
	public function edited_tax( $term_id, $tt_id )
	{
		if ( isset( $_POST['geditorial-alphabet_term_id'] ) ) {

			$alphabet_tax = $this->constant( 'alphabet_tax' );

			if ( 0 == $_POST['geditorial-alphabet_term_id'] )
				wp_set_object_terms( $term_id, NULL, $alphabet_tax, FALSE );

			else
				wp_set_object_terms( $term_id, array( intval( $_POST['geditorial-alphabet_term_id'] ) ), $alphabet_tax, FALSE );

			clean_object_term_cache( $term_id, $alphabet_tax );
		}
	}

	public function tax_manage_edit_columns( $columns )
	{
		$new_columns = array();

		foreach ( $columns as $key => $value ) {

			if ( 'name' == $key )
				$new_columns['alphabet'] = $this->get_column_title( 'tax', 'alphabet_tax' );

			$new_columns[$key] = $value;
		}

		return $new_columns;
	}

	public function tax_manage_custom_column( $display, $column, $term_id )
	{
		if ( 'alphabet' === $column )
			$this->column_term( $term_id, 'alphabet_tax', $this->get_string( 'tax_column_empty', 'alphabet_tax', 'misc' ) );
	}

	public function tax_row_actions( $actions, $term )
	{
		$actions['profile'] = $profile;
		return $actions;
	}

	public function tax_after_table( $taxonomy )
	{
	}

	public function taxonomy_bulk_actions( $actions, $taxonomy )
	{
		if ( in_array( $taxonomy, $this->taxonomies() ) )
			$actions['add_to_glossary'] = _x( 'Add to Glossary', 'Alphabet Module', GEDITORIAL_TEXTDOMAIN );

		return $actions;
	}

	public function taxonomy_bulk_callback( $callback, $action, $taxonomy )
	{
		if ( 'add_to_glossary' == $action
			&& in_array( $taxonomy, $this->taxonomies() ) )
				return array( $this, 'bulk_add_to_glossary' );

		return $callback;
	}

	public function taxonomy_bulk_input( $callback, $key, $taxonomy )
	{
		if ( 'add_to_glossary' == $key
			&& in_array( $taxonomy, $this->taxonomies() ) )
				return array( $this, 'bulk_input_glossary' );

		return $callback;
	}

	public function bulk_add_to_glossary( $term_ids, $taxonomy )
	{
		$glossary_id = $_REQUEST['new_glossary'];

		foreach ( $term_ids as $term_id ) {

			$ret = wp_set_object_terms( $term_id, array( intval( $glossary_id ) ), $this->constant( 'alphabet_tax' ), FALSE );

			if ( is_wp_error( $ret ) )
				return FALSE;

			clean_object_term_cache( $term_id, $this->constant( 'alphabet_tax' ) );
		}

		return TRUE;
	}

	public function bulk_input_glossary( $taxonomy )
	{
		$terms = gEditorialHelper::getTerms( $this->constant( 'alphabet_tax' ), FALSE, TRUE );

		echo '<select class="postform" name="new_glossary">';
		echo '<option value="0">'. _x( '&mdash; Select &mdash;', 'Alphabet Module', GEDITORIAL_TEXTDOMAIN ).'</option>'."\n";
		foreach ( $terms as $term_id => $term )
			echo '<option value="'.$term_id.'">'.$term->name.'</option>'."\n";
		echo '</select>';
	}

	// FIXME: DRAFT!
	public function tax_alphabet_shortcode( $atts, $content = NULL, $tag = '' )
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
		), $atts, $this->constant( 'multiple_series_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;



			// get_objects_in_term( $term_ids, $taxonomies, $args );

			// http://www.smashingmagazine.com/2014/08/27/customizing-wordpress-archives-categories-terms-taxonomies/

			// http://stackoverflow.com/questions/2003666/order-a-mysql-query-alphabetically
			// http://stackoverflow.com/questions/10446787/how-can-i-control-utf-8-ordering-in-mysql


		return $content;
	}

	// FIXME: UNFINISHED!
	public function post_alphabet_shortcode( $atts, $content = NULL, $tag = '' )
	{
		return $content;
	}

	// get supported alphabet from filter for each supported cpt/tax/user
	// store like : cpt_post_a, tax_people_a, user_aleph, cpt_post_jim, tax_post_tag_kaaf
	// short code to render lists based on alphabet
	// tools to build, rebuild list
	// check filters for each supported cpt/tax/user name first letter so that unicode works!
	// check new post/tax/user and add them to each glossory

	public function tools_settings( $sub )
	{
		if ( $this->module->name == $sub ) {

			// if ( ! empty( $_POST ) ) {
			// 	$this->tools_check_referer( $sub );
			// 	if ( isset( $_POST['issue_post_create'] ) ) {
			// 	}
			// }

			add_action( 'geditorial_tools_sub_'.$this->module->name, array( $this, 'tools_sub' ), 10, 2 );
		}

		add_filter( 'geditorial_tools_subs', array( $this, 'tools_subs' ) );
	}

	public function tools_sub( $uri, $sub )
	{
		echo '<form method="post" action="">';

			$this->tools_field_referer( $sub );

			echo '<h3>'._x( 'Alphabet Tools', 'Alphabet Module', GEDITORIAL_TEXTDOMAIN ).'</h3>';
			echo '<table class="form-table">';

				echo '<tr><th scope="row">'._x( 'Current Filtered Alphabet', 'Alphabet Module', GEDITORIAL_TEXTDOMAIN ).'</th><td>';
					self::tableList( array(
						'_cb'         => 'term_id',
						'name' => array(
							'title'    => _x( 'Name', 'Alphabet Module', GNETWORK_TEXTDOMAIN ),
							'callback' => function( $value, $row, $column, $index ){
								return $row;
							},
						),
						'slug' => array(
							'title'    => _x( 'Slug', 'Alphabet Module', GNETWORK_TEXTDOMAIN ),
							'callback' => function( $value, $row, $column, $index ){
								return $index;
							},
						),
					), $this->strings['terms']['alphabet_tax'] );
				echo '</td></tr>';

				echo '<tr><th scope="row">'._x( 'Current Installed Alphabet', 'Alphabet Module', GEDITORIAL_TEXTDOMAIN ).'</th><td>';
					self::tableList( array(
						'_cb'         => 'term_id',
						'term_id'     => _x( 'ID', 'Alphabet Module', GEDITORIAL_TEXTDOMAIN ),
						'name'        => _x( 'Name', 'Alphabet Module', GEDITORIAL_TEXTDOMAIN ),
						'slug'        => _x( 'Slug', 'Alphabet Module', GEDITORIAL_TEXTDOMAIN ),
						'description' => _x( 'Description', 'Alphabet Module', GEDITORIAL_TEXTDOMAIN ),
					), gEditorialHelper::getTerms( $this->constant( 'alphabet_tax' ), FALSE, TRUE ) );
				echo '</td></tr>';

			echo '</table>';
		echo '</form>';
	}
}
