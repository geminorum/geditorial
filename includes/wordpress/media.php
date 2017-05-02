<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialWPMedia extends gEditorialBaseCore
{

	// PDF: 'application/pdf'
	// MP3: 'audio/mpeg'
	// CSV: 'application/vnd.ms-excel'
	public static function selectAttachment( $selected = 0, $mime = NULL, $name = 'attach_id' )
	{
		$attachments = get_posts( array(
			'post_type'      => 'attachment',
			'numberposts'    => -1,
			'post_status'    => NULL,
			'post_mime_type' => $mime,
			'post_parent'    => NULL,
		) );

		if ( ! $attachments )
			return;

		echo gEditorialHTML::dropdown(
			gEditorialArraay::reKey( $attachments, 'ID' ),
			array(
				'name'       => $name,
				'none_title' => gEditorialSettingsCore::showOptionNone(),
				'class'      => '-attachment',
				'selected'   => $selected,
				'prop'       => 'post_title',
			)
		);
	}
}
