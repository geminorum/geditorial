<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

// `P2P_Item_Attachment`
class ItemAttachment extends ItemPost
{

	public function get_title()
	{
		if ( wp_attachment_is_image( $this->item->ID ) )
			return wp_get_attachment_image( $this->item->ID, 'thumbnail', FALSE );

		return get_the_title( $this->item );
	}
}
