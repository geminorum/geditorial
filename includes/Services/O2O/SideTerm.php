<?php namespace geminorum\gEditorial\Services\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class SideTerm extends Side
{

	protected $item_type = 'ItemTerm';

	public function __construct( $query_vars )
	{
		$this->query_vars = $query_vars;
	}

	public function get_object_type()
	{
		return 'term';
	}

	public function get_title()
	{
		return $this->get_labels()->name;
	}

	public function get_desc()
	{
		return implode( ', ', array_map( [ $this, 'taxonomy_label' ], $this->query_vars['taxonomy'] ) );
	}

	public function get_labels()
	{
		try {

			$labels = $this->get_tax()->labels;

		} catch ( Exception $e ) {

			trigger_error( $e->getMessage(), E_USER_WARNING );
			$labels = new \stdClass();
		}

		return $labels;
	}

	public function can_edit_connections()
	{
		try {

			return current_user_can( $this->get_tax()->cap->assign_terms );

		} catch ( Exception $e ) {

			trigger_error( $e->getMessage(), E_USER_WARNING );
			return FALSE;
		}
	}

	public function can_create_item()
	{
		if ( count( $this->query_vars['taxonomy'] ) > 1 )
			return FALSE;

		if ( count( $this->query_vars ) > 1 )
			return FALSE;

		return TRUE;
	}

	public function get_base_qv( $q )
	{
		if ( isset( $q['taxonomy'] ) && 'any' != $q['taxonomy'] ) {
			$common = array_intersect( $this->query_vars['taxonomy'], (array) $q['taxonomy'] );

			if ( ! $common )
				unset( $q['taxonomy'] );
		}

		return array_merge( $this->query_vars, $q, [
			'hide_empty'             => FALSE,
			// 'update_term_meta_cache' => FALSE,
		] );
	}

	public function translate_qv( $qv )
	{
		if ( isset( $qv['o2o:include'] ) )
			$qv['include'] = Utils::pluck( $qv, 'o2o:include' );

		if ( isset( $qv['o2o:exclude'] ) )
			$qv['exclude'] = Utils::pluck( $qv, 'o2o:exclude' );

		if ( isset( $qv['o2o:search'] ) && $qv['o2o:search'] )
			$qv['search'] = '*'.Utils::pluck( $qv, 'o2o:search' ).'*';

		if ( isset( $qv['o2o:page'] ) && $qv['o2o:page'] > 0 ) {
			if ( isset( $qv['o2o:per_page'] ) && $qv['o2o:per_page'] > 0 ) {
				$qv['number'] = $qv['o2o:per_page'];
				$qv['offset'] = $qv['o2o:per_page'] * ( $qv['o2o:page'] - 1 );
			}
		}

		return $qv;
	}

	public function do_query( $args )
	{
		return new \WP_Term_Query( $args );
	}

	// `post_type_label()`
	private function taxonomy_label( $taxonomy )
	{
		$tax = get_taxonomy( $taxonomy );
		return $tax ? $tax->labels->name : $taxonomy;
	}

	// `first_post_type()`
	public function first_taxonomy()
	{
		return $this->query_vars['taxonomy'][0];
	}

	// `get_ptype()`
	private function get_tax()
	{
		$tax = $this->first_taxonomy();

		if ( ! $tax_object = get_taxonomy( $tax ) )
			throw new Exception( "Can't find {$tax}." );

		return $tax_object;
	}


}
