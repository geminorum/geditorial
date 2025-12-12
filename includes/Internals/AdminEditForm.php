<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait AdminEditForm
{
	protected function _hook_editform_meta_summary( $fields = NULL, $priority = NULL )
	{
		add_action( 'edit_form_after_title',
			function ( $post ) use ( $fields ) {
				echo $this->wrap( gEditorial\Template::metaSummary( [
					'echo'   => FALSE,
					'id'     => $post->ID,
					'type'   => $post->post_type,
					'fields' => $this->filters( 'editform_meta_summary', $fields, $post ),
				] ), '-meta-summary -double-column-table' );
			},
			1,
			$priority ?? 9
		);
	}

	protected function _hook_editform_globalsummary( $option_key = NULL, $priority = NULL )
	{
		if ( $option_key !== TRUE && ! $this->get_setting( $option_key ?? 'display_globalsummary' ) )
			return FALSE;

		add_action( 'edit_form_after_title',
			function ( $post ) {

				if ( ! $markup = Services\Paired::getGlobalSummaryForPostMarkup( $post, 'editpost', 'table' ) )
					return;

				Core\HTML::h4( $markup['title'], '-table-header' );
				echo $this->wrap( $markup['html'], '-global-summary -multiple-column-table' );

				$html = '';

				// TODO: import buttons

				if ( $this->role_can( 'reports' ) && method_exists( $this, 'exports_get_export_buttons' ) )
					$html.= $this->exports_get_export_buttons( $post->ID, 'editpost', 'globalsummary' );

				if ( ! $html )
					return;

				echo $this->wrap_open_buttons().$html.'</p>';
			},
			1,
			$priority ?? 80
		);
	}
}
