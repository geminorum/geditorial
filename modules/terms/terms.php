<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Database;
use geminorum\gEditorial\WordPress\Taxonomy;

class Terms extends gEditorial\Module
{

	protected $taxonomies_excluded = [
		'nav_menu',
	];

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
		return [
			'_general' => [
				'adminbar_summary',
			],
			'taxonomies_option' => 'taxonomies_option',
		];
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() || ! is_singular() )
			return;

		if ( ! $this->cuc( 'adminbar' ) )
			return;

		$nodes[] = [
			'id'     => $this->classs(),
			'title'  => _x( 'Term Summary', 'Modules: Terms: Adminbar', GEDITORIAL_TEXTDOMAIN ),
			'parent' => $parent,
			'href'   => Settings::subURL( 'uncategorized', 'reports' ),
		];

		foreach ( $this->taxonomies() as $taxonomy ) {

			$terms = get_the_terms( NULL, $taxonomy );

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

				$this->settings_check_referer( $sub, 'tools' );

				if ( isset( $_POST['orphaned_terms'] ) ) {

					if ( ! empty( $post['dead_tax'] )
						&& ! empty( $post['live_tax'] ) ) {

						$result = $wpdb->query( $wpdb->prepare( "
							UPDATE {$wpdb->term_taxonomy} SET taxonomy = %s WHERE taxonomy = '%s'
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

					HTML::desc( _x( 'Converts orphaned terms into currently registered taxonomies', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ) );

				echo '</td></tr>';
			}

			echo '</table>';

		$this->settings_form_after( $uri, $sub );
	}

	public function reports_settings( $sub )
	{
		if ( ! $this->cuc( 'reports' ) )
			return;

		if ( 'uncategorized' == $sub ) {

			if ( ! empty( $_POST ) ) {

				$this->settings_check_referer( $sub, 'reports' );

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

			add_action( 'geditorial_reports_sub_'.$sub, [ $this, 'reports_sub' ], 10, 2 );
			$this->screen_option( $sub );
		}

		add_filter( 'geditorial_reports_subs', [ $this, 'append_sub' ], 10, 2 );
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
		list( $posts, $pagination ) = $this->getPostArray();

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

	protected function getPostArray()
	{
		$limit  = $this->limit_sub();
		$paged  = self::paged();
		$offset = ( $paged - 1 ) * $limit;

		$args = [
			'posts_per_page'   => $limit,
			'offset'           => $offset,
			'orderby'          => self::orderby( 'ID' ),
			'order'            => self::order( 'asc' ),
			'post_type'        => 'any', // $this->post_types()
			'post_status'      => [ 'publish', 'future', 'draft', 'pending' ],
			'suppress_filters' => TRUE,
			'tax_query'        => [ [
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => [ intval( get_option( 'default_category' ) ) ],
			] ],
		];

		if ( ! empty( $_REQUEST['id'] ) )
			$args['post__in'] = explode( ',', maybe_unserialize( $_REQUEST['id'] ) );

		if ( ! empty( $_REQUEST['type'] ) )
			$args['post_type'] = $_REQUEST['type'];

		if ( 'attachment' == $args['post_type'] )
			$args['post_status'][] = 'inherit';

		$query = new \WP_Query;
		$posts = $query->query( $args );

		$pagination = HTML::tablePagination( $query->found_posts, $query->max_num_pages, $limit, $paged );

		return [ $posts, $pagination ];
	}
}
