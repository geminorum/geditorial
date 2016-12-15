<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialAudit extends gEditorialModuleCore
{

	protected $caps = array(
		'default' => 'edit_others_posts',
		'tools'   => 'edit_others_posts',
	);

	public static function module()
	{
		return array(
			'name'  => 'audit',
			'title' => _x( 'Audit', 'Audit Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Content Inventory Tools', 'Audit Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'visibility',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'dashboard_widgets',
				'summary_scope',
				'admin_restrict',
			),
			'posttypes_option' => 'posttypes_option',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'audit_tax' => 'audit_attribute',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'tweaks_column_title' => _x( 'Audit Attributes', 'Audit Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
			),
			'settings' => array(
				'install_def_audit_tax' => _x( 'Install Default Attributes', 'Audit Module: Setting Button', GEDITORIAL_TEXTDOMAIN ),
			),
			'noops' => array(
				'audit_tax' => _nx_noop( 'Audit Attribute', 'Audit Attributes', 'Audit Module: Noop', GEDITORIAL_TEXTDOMAIN ),
			),
			'terms' => array(
				'audit_tax' => array(
					'audited'      => _x( 'Audited', 'Audit Module: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'outdated'     => _x( 'Outdated', 'Audit Module: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'redundant'    => _x( 'Redundant', 'Audit Module: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'review-seo'   => _x( 'Review SEO', 'Audit Module: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'review-style' => _x( 'Review Style', 'Audit Module: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'trivial'      => _x( 'Trivial', 'Audit Module: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				),
			),
		);
	}

	public function init()
	{
		do_action( 'geditorial_audit_init', $this->module );

		$this->do_globals();

		// FIXME: add setting option to choose editing role
		if ( $this->cuc( 'default' ) )
			$this->register_taxonomy( 'audit_tax', array(
				'hierarchical'       => TRUE,
				'show_in_quick_edit' => TRUE,
			) );
	}

	public function current_screen( $screen )
	{
		if ( 'dashboard' == $screen->base ) {

			if ( $this->get_setting( 'dashboard_widgets', FALSE ) )
				add_action( 'activity_box_end', array( $this, 'activity_box_end' ), 9 );

		} else if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_restrict', FALSE ) ) {
					add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ), 20 ,2 );
					add_filter( 'parse_query', array( $this, 'parse_query' ) );
				}
			}
		}
	}

	public function activity_box_end()
	{
		$terms = gEditorialWPTaxonomy::getTerms( $this->constant( 'audit_tax' ), FALSE, TRUE, 'slug', array( 'hide_empty' => TRUE ) );

		if ( ! count( $terms ) )
			return;

		$user_id = 'all' == $this->get_setting( 'summary_scope', 'all' ) ? 0 : get_current_user_id();
		$counts  = gEditorialWordPress::countPostsByTaxonomy( $terms, $this->post_types(), $user_id );

		if ( $html = $this->get_summary( $counts, $terms, $user_id ) ) {
			echo '<div class="geditorial-admin-wrap -audit"><h3>';

			if ( $user_id )
				_ex( 'Your Audit Summary', 'Audit Module', GEDITORIAL_TEXTDOMAIN );
			else
				_ex( 'Editorial Audit Summary', 'Audit Module', GEDITORIAL_TEXTDOMAIN );

			echo '</h3>'.$html.'</div>';
		}
	}

	private function get_summary( $counts, $terms, $user_id = 0, $wrap = 'ul', $list = 'li' )
	{
		$html = '';
		$tax  = $this->constant( 'audit_tax' );
		$all  = gEditorialWordPress::getPostTypes( 3 );

		$objects = array();

		foreach ( $counts as $term => $posts ) {

			$name = sanitize_term_field( 'name', $terms[$term]->name, $terms[$term]->term_id, $terms[$term]->taxonomy, 'display' );

			foreach ( $posts as $type => $count ) {

				if ( ! $count )
					continue;

				if ( empty( $objects[$type] ) )
					$objects[$type] = get_post_type_object( $type );

				$query = array( 'post_type' => $type, $tax => $term );

				if ( $user_id )
					$query['author'] = $user_id;

				$text = vsprintf( '<span>%4$s</span> %1$s <span title="%3$s">(%2$s)</span>', array(
					gEditorialHelper::noopedCount( $count, $all[$type] ),
					gEditorialCoreText::trimChars( $name, 35 ),
					esc_attr( $name ),
					number_format_i18n( $count ),
				) );

				if ( $objects[$type] && current_user_can( $objects[$type]->cap->edit_posts ) )
					$text = gEditorialHTML::tag( 'a', array(
						'href' => add_query_arg( $query, admin_url( 'edit.php' ) ),
					), $text );

				$html .= gEditorialHTML::tag( $list, array(
					'class' => $term.'-'.$type.'-count',
				), $text );
			}
		}

		if ( $html )
			return gEditorialHTML::tag( $wrap, $html );

		return FALSE;
	}

	public function register_settings( $page = NULL )
	{
		if ( ! $this->is_register_settings( $page ) )
			return;

		if ( isset( $_POST['install_def_audit_tax'] ) )
			$this->insert_default_terms( 'audit_tax' );

		parent::register_settings( $page );
		$this->register_settings_button( 'install_def_audit_tax' );
	}

	public function tweaks_strings( $strings )
	{
		$this->tweaks = TRUE;

		$new = array(
			'taxonomies' => array(
				$this->constant( 'audit_tax' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'audit_tax' ),
					'icon'   => $this->module->icon,
					'title'  => $this->get_column_title( 'tweaks', 'audit_tax' ),
				),
			),
		);

		return self::recursiveParseArgs( $new, $strings );
	}

	public function restrict_manage_posts( $post_type, $which )
	{
		$tax = get_taxonomy( $audit = $this->constant( 'audit_tax' ) );

		wp_dropdown_categories( array(
			'taxonomy'        => $audit,
			'show_option_all' => $tax->labels->all_items,
			'name'            => $tax->name,
			'order'           => 'DESC',
			'selected'        => isset( $_GET[$audit] ) ? $_GET[$audit] : 0,
			'hierarchical'    => $tax->hierarchical,
			'show_count'      => FALSE,
			'hide_empty'      => TRUE,
			'hide_if_empty'   => TRUE,
		) );
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query->query_vars, array( 'audit_tax' ) );
	}

	public function meta_box_cb_audit_tax( $post, $box )
	{
		$this->meta_box_choose_tax( $post, $box );
	}
}
