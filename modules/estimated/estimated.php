<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;

class Estimated extends gEditorial\Module
{

	public $meta_key = '_ge_estimated';

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'  => 'estimated',
			'title' => _x( 'Estimated', 'Modules: Estimated', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Average Required Reading Time', 'Modules: Estimated', GEDITORIAL_TEXTDOMAIN ),
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
					'title'       => _x( 'Reading Time', 'Modules: Estimated: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Average words per minute', 'Modules: Estimated: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => 250,
				],
				[
					'field'       => 'prefix',
					'type'        => 'text',
					'title'       => _x( 'Content Prefix', 'Modules: Estimated: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'String before the estimated time on the content', 'Modules: Estimated: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => _x( 'Estimated read time:', 'Modules: Estimated: Setting Default', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'teaser',
					'type'        => 'select',
					'title'       => _x( 'Content Teaser', 'Modules: Estimated: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Calculate teaser text along with rest of the content', 'Modules: Estimated: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => 'include',
					'values'      => [
						'ignore'  => _x( 'Ignore', 'Modules: Estimated: Content Teaser Option', GEDITORIAL_TEXTDOMAIN ),
						'include' => _x( 'Include', 'Modules: Estimated: Content Teaser Option', GEDITORIAL_TEXTDOMAIN ),
					],
				],
				'insert_content',
				'insert_priority',
				[
					'field'       => 'min_words',
					'type'        => 'number',
					'title'       => _x( 'Minimum Words', 'Modules: Estimated: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'And above this number of words will show the notice', 'Modules: Estimated: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => 1000,
				],
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
		if ( in_array( $screen->post_type, $this->posttypes() ) ) {

			if ( 'post' == $screen->base ) {

				add_action( 'save_post_'.$screen->post_type, [ $this, 'store_metabox' ], 20, 3 );

			} else if ( 'edit' == $screen->base ) {

				$this->action_module( 'tweaks', 'column_attr', 1, 12 );
			}
		}
	}

	public function tweaks_column_attr( $post )
	{
		if ( $wordcount = get_post_meta( $post->ID, $this->meta_key, TRUE ) ) {

			echo '<li class="-row -estimated -wordcount">';

				echo $this->get_column_icon( FALSE, NULL, _x( 'Estimated Time', 'Modules: Estimated: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );

				echo '<span class="-wordcount" title="'
					.esc_attr_x( 'Word Count', 'Modules: Estimated: Row Title', GEDITORIAL_TEXTDOMAIN ).'">'
					.$this->nooped_count( 'word', $wordcount )
					.'</span>';

				echo ' <span class="-estimated-time">('
					.$this->get_time_estimated( $wordcount )
					.')</span>';

			echo '</li>';
		}
	}

	public function store_metabox( $post_id, $post, $update, $context = 'box' )
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
		if ( ! $wordcount = get_post_meta( $post_id, $this->meta_key, TRUE ) )
			$wordcount = $this->get_post_wordcount( $post_id, TRUE );

		if ( $this->get_setting( 'min_words', 250 ) > $wordcount )
			return FALSE;

		if ( is_null( $prefix ) )
			$prefix = $this->get_setting( 'prefix', _x( 'Estimated read time:', 'Modules: Estimated', GEDITORIAL_TEXTDOMAIN ) );

		$html = ( $prefix ? $prefix.' ' : '' ).$this->get_time_estimated( $wordcount, TRUE );

		return $html;
	}

	public function get_time_estimated( $wordcount = 0, $info = TRUE )
	{
		$avgtime = $this->get_setting( 'average', 250 );
		$minutes = floor( (int) $wordcount / (int) $avgtime );

		if ( $minutes < 1 )
			$estimated = _x( 'less than 1 minute', 'Modules: Estimated', GEDITORIAL_TEXTDOMAIN );
		else
			$estimated = sprintf( _nx( '%s minute', '%s minutes', $minutes, 'Modules: Estimated', GEDITORIAL_TEXTDOMAIN ), Number::format( $minutes ) );

		if ( $info )
			return '<span data-toggle="tooltip" title="'.HTML::escape(
				sprintf( _x( 'If you try to read %s words per minute', 'Modules: Estimated', GEDITORIAL_TEXTDOMAIN ),
				Number::format( $avgtime ) ) )
				.'">'.$estimated.'</span>';

		return $estimated;
	}
}
