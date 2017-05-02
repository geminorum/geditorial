<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialTerms extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'      => 'terms',
			'title'     => _x( 'Terms', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ),
			'desc'      => _x( 'Taxonomy & Term Tools', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ),
			'icon'      => 'image-filter',
			'configure' => FALSE,
			'frontend'  => FALSE,
		);
	}

	public function append_sub( $subs, $page = 'settings' )
	{
		if ( ! $this->cuc( $page ) )
			return $subs;

		if ( $page == 'reports' )
			return array_merge( $subs, array(
				'uncategorized' => _x( 'Uncategorized', 'Modules: Terms: Reports: Sub Title', GEDITORIAL_TEXTDOMAIN ),
			) );
		else
			return array_merge( $subs, array( $this->module->name => $this->module->title ) );
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
							gEditorialWordPress::redirectReferer( array(
								'message' => 'changed',
								'count'   => count( $result ),
							) );
					}
				}
			}
		}
	}

	public function tools_sub( $uri, $sub )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', 'tools', FALSE, FALSE );

			gEditorialHTML::h3( _x( 'Term Tools', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ) );

			echo '<table class="form-table">';

			$db_taxes   = gEditorialWPDatabase::getTaxonomies( TRUE );
			$live_taxes = gEditorialWPTaxonomy::get( 6 );
			$dead_taxes = array_diff_key( $db_taxes, $live_taxes );

			if ( count( $dead_taxes ) ) {

				echo '<tr><th scope="row">'._x( 'Orphaned Terms', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

					$this->do_settings_field( array(
						'type'         => 'select',
						'field'        => 'dead_tax',
						'values'       => $dead_taxes,
						'default'      => ( isset( $post['dead_tax'] ) ? $post['dead_tax'] : 'post_tag' ),
						'option_group' => 'tools',
					) );

					$this->do_settings_field( array(
						'type'         => 'select',
						'field'        => 'live_tax',
						'values'       => $live_taxes,
						'default'      => ( isset( $post['live_tax'] ) ? $post['live_tax'] : 'post_tag' ),
						'option_group' => 'tools',
					) );

					echo '&nbsp;&nbsp;';

					gEditorialSettingsCore::submitButton( 'orphaned_terms',
						_x( 'Convert', 'Modules: Terms: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

					gEditorialHTML::desc( _x( 'Converts orphaned terms into currently registered taxonomies', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ) );

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

				if ( isset( $_POST['cleanup_terms'] )
					&& isset( $_POST['_cb'] )
					&& count( $_POST['_cb'] ) ) {

					$all   = gEditorialWPTaxonomy::get();
					$count = 0;

					foreach ( $_POST['_cb'] as $post_id ) {

						$taxes = get_object_taxonomies( get_post( $post_id ) );
						$diff  = array_diff_key( $all, array_flip( $taxes ) );

						foreach ( $diff as $tax => $title )
							wp_set_object_terms( $post_id, NULL, $tax );

						$count++;
					}

					gEditorialWordPress::redirectReferer( array(
						'message' => 'cleaned',
						'count'   => $count,
					) );
				}
			}

			add_action( 'geditorial_reports_sub_'.$sub, array( $this, 'reports_sub' ), 10, 2 );

			$this->screen_option( $sub );
			$this->register_button( 'cleanup_terms', _x( 'Cleanup Terms', 'Modules: Terms: Setting Button', GEDITORIAL_TEXTDOMAIN ) );
		}

		add_filter( 'geditorial_reports_subs', array( $this, 'append_sub' ), 10, 2 );
	}

	public function reports_sub( $uri, $sub )
	{
		if ( 'uncategorized' == $sub )
			return $this->reports_sub_uncategorized( $uri, $sub );
	}

	public function reports_sub_uncategorized( $uri, $sub )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', 'reports', FALSE, FALSE );

			if ( $this->tableUncategorized() )
				$this->settings_buttons();

		$this->settings_form_after( $uri, $sub );
	}

	private function tableUncategorized()
	{
		list( $posts, $pagination ) = $this->getPostArray();

		$pagination['before'][] = gEditorialHelper::tableFilterPostTypes();

		return gEditorialHTML::tableList( array(
			'_cb'   => 'ID',
			'ID'    => gEditorialHelper::tableColumnPostID(),
			'date'  => gEditorialHelper::tableColumnPostDate(),
			'type'  => gEditorialHelper::tableColumnPostType(),
			'title' => gEditorialHelper::tableColumnPostTitle(),
			'terms' => gEditorialHelper::tableColumnPostTerms(),
		), $posts, array(
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => gEditorialHTML::tag( 'h3', _x( 'Overview of Uncategorized Posts', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ) ),
			'empty'      => gEditorialHTML::warning( _x( 'No Posts!', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ) ),
			'pagination' => $pagination,
		) );
	}

	protected function getPostArray()
	{
		$limit  = $this->limit_sub();
		$paged  = self::paged();
		$offset = ( $paged - 1 ) * $limit;

		$args = array(
			'posts_per_page'   => $limit,
			'offset'           => $offset,
			'orderby'          => self::orderby( 'ID' ),
			'order'            => self::order( 'asc' ),
			'post_type'        => 'any', // $this->post_types()
			'post_status'      => array( 'publish', 'future', 'draft', 'pending' ),
			'suppress_filters' => TRUE,
			'tax_query'        => array( array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => array( get_option( 'default_category' ) ),
			) ),
		);

		if ( ! empty( $_REQUEST['id'] ) )
			$args['post__in'] = explode( ',', maybe_unserialize( $_REQUEST['id'] ) );

		if ( ! empty( $_REQUEST['type'] ) )
			$args['post_type'] = $_REQUEST['type'];

		if ( 'attachment' == $args['post_type'] )
			$args['post_status'][] = 'inherit';

		$query = new \WP_Query;
		$posts = $query->query( $args );

		$pagination = gEditorialHTML::tablePagination( $query->found_posts, $query->max_num_pages, $limit, $paged );

		return array( $posts, $pagination );
	}
}
