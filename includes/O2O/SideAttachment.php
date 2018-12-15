<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

// `P2P_Side_Attachment`
class SideAttachment extends SidePost
{

	protected $item_type = 'ItemAttachment';

	public function __construct( $query_vars )
	{
		$this->query_vars = $query_vars;
		$this->query_vars['post_type'] = [ 'attachment' ];
	}

	public function can_create_item()
	{
		return FALSE;
	}

	public function get_base_qv( $q )
	{
		return array_merge( parent::get_base_qv( $q ), [ 'post_status' => 'inherit' ] );
	}
}
