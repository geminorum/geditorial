<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core;

class Media extends Core\Base
{

	// PDF: 'application/pdf'
	// MP3: 'audio/mpeg'
	// CSV: 'application/vnd.ms-excel'
	public static function selectAttachment( $selected = 0, $mime = NULL, $name = 'attach_id', $empty = '' )
	{
		$attachments = get_posts( array(
			'post_type'      => 'attachment',
			'numberposts'    => -1,
			'post_status'    => NULL,
			'post_mime_type' => $mime,
			'post_parent'    => NULL,
		) );

		if ( ! count( $attachments ) ) {
			echo $empty;
			return FALSE;
		}

		echo Core\HTML::dropdown(
			Core\Arraay::reKey( $attachments, 'ID' ),
			array(
				'name'       => $name,
				'none_title' => Settings::showOptionNone(),
				'class'      => '-attachment',
				'selected'   => $selected,
				'prop'       => 'post_title',
			)
		);
	}
}
