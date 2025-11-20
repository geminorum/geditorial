<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

class FieldTitlePost extends FieldTitle
{
	public function get_data( $item )
	{
		$data = [ 'title-attr' => $item->get_permalink() ];
		$post = $item->get_object();

		if ( 'publish' != $post->post_status ) {

			$status_obj = get_post_status_object( $post->post_status );

			if ( $status_obj )
				$data['status']['text'] = $status_obj->label;
		}

		return $data;
	}
}
