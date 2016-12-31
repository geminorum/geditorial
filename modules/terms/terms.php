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
		);
	}

	public function init()
	{
		do_action( 'geditorial_terms_init', $this->module );
		$this->do_globals();
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

		if ( 'uncategorized' == $sub )
			add_action( 'geditorial_reports_sub_uncategorized', array( $this, 'reports_sub' ), 10, 2 );

		add_filter( 'geditorial_reports_subs', array( $this, 'append_sub' ), 10, 2 );
	}

	public function reports_sub( $uri, $sub )
	{
		if ( 'uncategorized' == $sub )
			return $this->reports_sub_uncategorized( $uri, $sub );
	}

	public function reports_sub_uncategorized( $uri, $sub )
	{
		echo '<form class="settings-form" method="post" action="">';

			$this->settings_fields( $sub, 'bulk', 'reports' );

			self::tableUncategorized();

		echo '</form>';
	}

	private static function tableUncategorized()
	{
		list( $posts, $pagination ) = self::getPostArray();

		return gEditorialHTML::tableList( array(
			'_cb' => 'ID',
			'ID'  => _x( 'ID', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ),

			'date' => array(
				'title'    => _x( 'Date', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ),
				'callback' => function( $value, $row, $column, $index ){
					return gEditorialHelper::humanTimeDiffRound( strtotime( $row->post_date ) );
				},
			),

			'type' => array(
				'title'    => _x( 'Type', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ),
				'args'     => array( 'post_types' => gEditorialWPPostType::get( 2 ) ),
				'callback' => function( $value, $row, $column, $index ){
					return isset( $column['args']['post_types'][$row->post_type] ) ? $column['args']['post_types'][$row->post_type] : $row->post_type;
				},
			),

			'title' => array(
				'title'    => _x( 'Title', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ),
				'callback' => function( $value, $row, $column, $index ){
					return apply_filters( 'the_title', $row->post_title, $row->ID );
				},
				'actions' => function( $value, $row, $column, $index ){
					return gEditorialHelper::getPostRowActions( $row->ID );
				},
			),

			'terms' => array(
				'title'    => _x( 'Terms', 'Modules: Terms', GEDITORIAL_TEXTDOMAIN ),
				'callback' => function( $value, $row, $column, $index ){
					$html = '';
					foreach( get_object_taxonomies( $row, 'objects' ) as $taxonomy => $object )
						$html .= gEditorialHelper::getTermsEditRow( $row, $object, '<div>'.$object->label.': ', '</div>' );
					return $html;
				},
			),

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
			'no_found_rows'    => TRUE,
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

		$pagination = array(
			'total'    => $query->found_posts,
			'pages'    => $query->max_num_pages,
			'limit'    => $limit,
			'paged'    => $paged,
			'all'      => FALSE,
			'next'     => FALSE,
			'previous' => FALSE,
		);

		if ( $pagination['pages'] > 1 ) {
			if ( $paged != 1 )
				$pagination['previous'] = $paged - 1;

			if ( $paged != $pagination['pages'] )
				$pagination['next'] = $paged + 1;
		}

		return array( $posts, $pagination );
	}
}
