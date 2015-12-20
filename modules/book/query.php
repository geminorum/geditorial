<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

// http://bradt.ca/blog/extending-wp_query/
class gEditorialBookQuery extends WP_Query
{

	private $cpt = 'publication';
	private $tax = 'publication_subject';

	public function __construct( $args = array() )
	{
		$this->cpt = gEditorial()->get_constant( 'book', 'publication_cpt', 'publication' );
		$this->tax = gEditorial()->get_constant( 'book', 'subject_tax', 'publication_subject' );

		// Force these args
		$args = array_merge( $args, array(
			'post_type'              => $this->cpt,
			'posts_per_page'         => -1,  // turn off paging
			'no_found_rows'          => TRUE, // optimize query for no paging
			'update_post_term_cache' => FALSE,
			'update_post_meta_cache' => FALSE
		) );

		$filters = array(
			'posts_fields',
			'posts_join',
			'posts_where',
			'posts_orderby',
		);

		foreach ( $filters as $filter )
			add_filter( $filter, array( $this, $filter ) );

		parent::__construct( $args );

		// make sure these filters don't affect any other queries
		foreach ( $filters as $filter )
			remove_filter( $filter, array( $this, $filter ) );
	}

	public function posts_fields( $sql )
	{
		global $wpdb;
		return $sql . ", $wpdb->terms.name AS '{$this->tax}'";
	}

	public function posts_join( $sql )
	{
		global $wpdb;
		return $sql . "
			INNER JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)
			INNER JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
			INNER JOIN $wpdb->terms ON ($wpdb->terms.term_id = $wpdb->term_taxonomy.term_id)
		";
	}

	public function posts_where( $sql )
	{
		global $wpdb;
		return $sql . " AND $wpdb->term_taxonomy.taxonomy = '{$this->tax}'";
	}

	public function posts_orderby( $sql )
	{
		global $wpdb;
		return "$wpdb->terms.name ASC, $wpdb->posts.post_title ASC";
	}
}
