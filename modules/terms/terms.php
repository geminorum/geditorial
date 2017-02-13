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

			if ( self::tableUncategorized() )
				$this->settings_buttons();

		$this->settings_form_after( $uri, $sub );
	}

	private static function tableUncategorized()
	{
		list( $posts, $pagination ) = self::getPostArray();

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

	protected static function getPostArray()
	{
		$limit  = self::limit();
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
