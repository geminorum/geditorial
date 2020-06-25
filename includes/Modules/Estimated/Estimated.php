<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;

class Estimated extends gEditorial\Module
{

	public $meta_key = '_ge_estimated';

	protected $disable_no_posttypes   = TRUE;
	protected $priority_adminbar_init = 90;

	public static function module()
	{
		return [
			'name'  => 'estimated',
			'title' => _x( 'Estimated', 'Modules: Estimated', 'geditorial' ),
			'desc'  => _x( 'Average Required Reading Time', 'Modules: Estimated', 'geditorial' ),
			'icon'  => 'clock',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'average',
					'type'        => 'number',
					'title'       => _x( 'Reading Time', 'Modules: Estimated: Setting Title', 'geditorial' ),
					'description' => _x( 'Average words per minute', 'Modules: Estimated: Setting Description', 'geditorial' ),
					'default'     => 250,
				],
				[
					'field'       => 'prefix',
					'type'        => 'text',
					'title'       => _x( 'Content Prefix', 'Modules: Estimated: Setting Title', 'geditorial' ),
					'description' => _x( 'Custom string before the estimated time on the content.', 'Modules: Estimated: Setting Description', 'geditorial' ),
					'default'     => _x( 'Estimated read time:', 'Modules: Estimated: Setting Default', 'geditorial' ),
				],
				[
					'field'       => 'teaser',
					'type'        => 'select',
					'title'       => _x( 'Content Teaser', 'Modules: Estimated: Setting Title', 'geditorial' ),
					'description' => _x( 'Calculate teaser text along with rest of the content', 'Modules: Estimated: Setting Description', 'geditorial' ),
					'default'     => 'include',
					'values'      => [
						'ignore'  => _x( 'Ignore', 'Modules: Estimated: Content Teaser Option', 'geditorial' ),
						'include' => _x( 'Include', 'Modules: Estimated: Content Teaser Option', 'geditorial' ),
					],
				],
				'insert_content',
				'insert_priority',
				[
					'field'       => 'min_words',
					'type'        => 'number',
					'title'       => _x( 'Minimum Words', 'Modules: Estimated: Setting Title', 'geditorial' ),
					'description' => _x( 'And above this number of words will show the notice', 'Modules: Estimated: Setting Description', 'geditorial' ),
					'default'     => 1000,
				],
				'adminbar_summary',
			],
			'posttypes_option' => 'posttypes_option',
		];
	}

	public function template_redirect()
	{
		if ( ! is_singular( $this->posttypes() ) )
			return;

		if ( $this->hook_insert_content( 22 ) )
			$this->enqueue_styles(); // widget must add this itself!
	}

	public function current_screen( $screen )
	{
		if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				add_action( 'save_post_'.$screen->post_type, [ $this, 'store_metabox' ], 20, 3 );

			} else if ( 'edit' == $screen->base ) {

				$this->action_module( 'tweaks', 'column_attr', 1, 12 );
			}
		}
	}

	public function tweaks_column_attr( $post )
	{
		if ( $wordcount = $this->get_postmeta( $post->ID ) ) {

			echo '<li class="-row -estimated -wordcount">';

				echo $this->get_column_icon( FALSE, NULL, _x( 'Estimated Time', 'Modules: Estimated: Row Icon Title', 'geditorial' ) );

				echo '<span class="-wordcount" title="'
					.esc_attr_x( 'Word Count', 'Modules: Estimated: Row Title', 'geditorial' ).'">'
					.$this->nooped_count( 'word', $wordcount )
					.'</span>';

				echo ' <span class="-estimated-time">('
					.$this->get_time_estimated( $wordcount )
					.')</span>';

			echo '</li>';
		}
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() || ! is_singular( $this->posttypes() ) )
			return;

		$post_id = get_queried_object_id();

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		if ( $wordcount = $this->get_postmeta( $post_id ) ) {

			$html = '<span class="-wordcount">'
				.$this->nooped_count( 'word', $wordcount )
				.'</span>';

			$html.= ' <span class="-estimated-time">('
				.$this->get_time_estimated( $wordcount, FALSE )
				.')</span>';

			$avgtime = $this->get_setting( 'average', 250 );
			$title   = sprintf( _x( 'If you try to read %s words per minute', 'Modules: Estimated', 'geditorial' ), Number::format( $avgtime ) );

			$nodes[] = [
				'id'     => $this->classs(),
				'title'  => $html,
				'parent' => $parent,
				'href'   => FALSE, // $this->get_module_url(),
				'meta'   => [ 'title' => $title ],
			];
		}
	}

	public function store_metabox( $post_id, $post, $update, $context = 'main' )
	{
		if ( $this->is_save_post( $post, $this->posttypes() ) )
			$this->get_post_wordcount( $post_id, TRUE );
	}

	public function insert_content( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		if ( ! $post = get_post() )
			return;

		if ( $html = $this->get_estimated( $post->ID ) )
			echo $this->wrap( $html, '-before' );
	}

	protected function get_post_wordcount( $post_id, $update = FALSE )
	{
		$content = get_post_field( 'post_content', $post_id, 'raw' );

		if ( 'ignore' == $this->get_setting( 'teaser', 'include' )
			|| Text::has( $content, '<!--noteaser-->' ) ) {

			if ( preg_match( '/<!--more(.*?)?-->/', $content, $matches ) ) {
				$content = explode( $matches[0], $content, 2 );
				$content = $content[1];
			}
		}

		$wordcount = Text::wordCountUTF8( $content );

		if ( $update )
			update_post_meta( $post_id, $this->meta_key, $wordcount );

		return $wordcount;
	}

	public function get_estimated( $post_id, $prefix = NULL )
	{
		if ( ! $wordcount = $this->get_postmeta( $post_id ) )
			$wordcount = $this->get_post_wordcount( $post_id, TRUE );

		if ( $this->get_setting( 'min_words', 250 ) > $wordcount )
			return FALSE;

		if ( is_null( $prefix ) )
			$prefix = $this->get_setting( 'prefix', _x( 'Estimated read time:', 'Modules: Estimated', 'geditorial' ) );

		$html = ( $prefix ? $prefix.' ' : '' ).$this->get_time_estimated( $wordcount, TRUE );

		return $html;
	}

	public function get_time_estimated( $wordcount = 0, $info = TRUE )
	{
		$avgtime = $this->get_setting( 'average', 250 );
		$minutes = floor( (int) $wordcount / (int) $avgtime );

		if ( $minutes < 1 )
			$estimated = _x( 'less than 1 minute', 'Modules: Estimated', 'geditorial' );
		else
			/* translators: %s: number of minutes */
			$estimated = sprintf( _nx( '%s minute', '%s minutes', $minutes, 'Modules: Estimated', 'geditorial' ), Number::localize( $minutes ) );

		if ( $info )
			return '<span data-toggle="tooltip" title="'.HTML::escape(
				/* translators: %s: words count */
				sprintf( _x( 'If you try to read %s words per minute', 'Modules: Estimated', 'geditorial' ),
				Number::format( $avgtime ) ) )
				.'">'.$estimated.'</span>';

		return $estimated;
	}
}
