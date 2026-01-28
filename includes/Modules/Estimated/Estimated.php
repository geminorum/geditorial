<?php namespace geminorum\gEditorial\Modules\Estimated;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Estimated extends gEditorial\Module
{
	use Internals\CoreAdmin;
	use Internals\PostMeta;

	protected $disable_no_posttypes   = TRUE;
	protected $priority_adminbar_init = 90;

	public static function module()
	{
		return [
			'name'     => 'estimated',
			'title'    => _x( 'Estimated', 'Modules: Estimated', 'geditorial-admin' ),
			'desc'     => _x( 'Average Required Reading Time', 'Modules: Estimated', 'geditorial-admin' ),
			'icon'     => 'clock',
			'access'   => 'beta',
			'keywords' => [
				'has-adminbar',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'average',
					'type'        => 'number',
					'title'       => _x( 'Reading Time', 'Setting Title', 'geditorial-estimated' ),
					'description' => _x( 'Average words per minute', 'Setting Description', 'geditorial-estimated' ),
					'default'     => 250,
				],
				[
					'field'       => 'prefix',
					'type'        => 'text',
					'title'       => _x( 'Content Prefix', 'Setting Title', 'geditorial-estimated' ),
					'description' => _x( 'Custom string before the estimated time on the content.', 'Setting Description', 'geditorial-estimated' ),
					'default'     => _x( 'Estimated read time:', 'Setting Default', 'geditorial-estimated' ),
				],
				[
					'field'       => 'teaser',
					'type'        => 'select',
					'title'       => _x( 'Content Teaser', 'Setting Title', 'geditorial-estimated' ),
					'description' => _x( 'Calculate teaser text along with rest of the content', 'Setting Description', 'geditorial-estimated' ),
					'default'     => 'include',
					'values'      => [
						'ignore'  => _x( 'Ignore', 'Content Teaser Option', 'geditorial-estimated' ),
						'include' => _x( 'Include', 'Content Teaser Option', 'geditorial-estimated' ),
					],
				],
				'insert_content',
				'insert_priority',
				[
					'field'       => 'min_words',
					'type'        => 'number',
					'title'       => _x( 'Minimum Words', 'Setting Title', 'geditorial-estimated' ),
					'description' => _x( 'And above this number of words will show the notice', 'Setting Description', 'geditorial-estimated' ),
					'default'     => 1000,
				],
				'adminbar_summary',
			],
			'posttypes_option' => 'posttypes_option',
		];
	}

	protected function get_global_constants()
	{
		return [
			'metakey_post_wordcount' => '_ge_estimated',
		];
	}

	public function template_redirect()
	{
		if ( ! is_singular( $this->posttypes() ) )
			return;

		if ( $this->hook_insert_content( 22 ) )
			$this->enqueue_styles(); // widget must add this itself!
	}

	public function setup_ajax()
	{
		if ( ! $posttype = $this->is_inline_save_posttype( $this->posttypes() ) )
			return;

		$this->coreadmin__hook_tweaks_column_attr( $posttype, 40 );
	}

	public function current_screen( $screen )
	{
		if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				$this->_hook_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->coreadmin__hook_tweaks_column_attr( $screen->post_type, 40 );
			}
		}
	}

	public function tweaks_column_attr( $post, $before, $after )
	{
		if ( $wordcount = $this->_fetch_postmeta( $post->ID ) ) {

			printf( $before, '-estimated-wordcount' );

				echo $this->get_column_icon( FALSE, NULL, _x( 'Estimated Time', 'Row Icon Title', 'geditorial-estimated' ) );

				echo '<span class="-wordcount" title="'
					.esc_attr_x( 'Word Count', 'Row Title', 'geditorial-estimated' ).'">'
					.$this->nooped_count( 'word', $wordcount )
					.'</span>';

				echo ' <span class="-estimated-time">('
					.$this->get_time_estimated( $wordcount )
					.')</span>';

			echo $after;
		}
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() || ! is_singular( $this->posttypes() ) || WordPress\IsIt::mobile() )
			return;

		$post_id = get_queried_object_id();
		$node_id = $this->classs();

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		if ( $wordcount = $this->fetch_postmeta( $post_id ) ) {

			$html = '<span class="-wordcount">'
				.$this->nooped_count( 'word', $wordcount )
				.'</span>';

			$html.= ' <span class="-estimated-time">('
				.$this->get_time_estimated( $wordcount, FALSE )
				.')</span>';

			$avgtime = $this->get_setting( 'average', 250 );
			$title   = sprintf(
				/* translators: `%s`: average word count */
				_x( 'If you try to read %s words per minute.', 'Title Attr', 'geditorial-estimated' ),
				Core\Number::format( $avgtime )
			);

			$nodes[] = [
				'parent' => $parent,
				'id'     => $node_id,
				'title'  => $html,
				'href'   => FALSE, // $this->get_module_url(),
				'meta'   => [
					'title' => $title,
					'class' => $this->class_for_adminbar_node(),
				],
			];
		}
	}

	public function store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( $this->is_save_post( $post, $this->posttypes() ) )
			$this->_get_post_wordcount( $post_id, TRUE );
	}

	public function insert_content( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		if ( ! $post = WordPress\Post::get() )
			return;

		if ( $html = $this->get_estimated( $post->ID ) )
			echo $this->wrap( $html, '-before' );
	}

	private function _get_post_wordcount( $post_id, $update = FALSE )
	{
		$content = get_post_field( 'post_content', $post_id, 'raw' );

		if ( 'ignore' == $this->get_setting( 'teaser', 'include' )
			|| Core\Text::has( $content, '<!--noteaser-->' ) ) {

			if ( preg_match( '/<!--more(.*?)?-->/', $content, $matches ) ) {
				$content = explode( $matches[0], $content, 2 );
				$content = $content[1];
			}
		}

		$wordcount = Core\Text::wordCountUTF8( $content );

		if ( $update )
			update_post_meta( $post_id, $this->constant( 'metakey_post_wordcount' ), $wordcount );

		return $wordcount;
	}

	private function _fetch_postmeta( $post_id, $fallback = FALSE )
	{
		return $this->fetch_postmeta( $post_id, $fallback, $this->constant( 'metakey_post_wordcount' ) );
	}

	public function get_estimated( $post_id, $prefix = NULL )
	{
		if ( ! $wordcount = $this->_fetch_postmeta( $post_id ) )
			$wordcount = $this->_get_post_wordcount( $post_id, TRUE );

		if ( $this->get_setting( 'min_words', 250 ) > $wordcount )
			return FALSE;

		if ( is_null( $prefix ) )
			$prefix = $this->get_setting( 'prefix', _x( 'Estimated read time:', 'Setting Default', 'geditorial-estimated' ) );

		$html = ( $prefix ? $prefix.' ' : '' ).$this->get_time_estimated( $wordcount, TRUE );

		return $html;
	}

	public function get_time_estimated( $wordcount = 0, $info = TRUE )
	{
		$avgtime = $this->get_setting( 'average', 250 );
		$minutes = floor( (int) $wordcount / (int) $avgtime );

		if ( $minutes < 1 )
			$estimated = __( 'less than 1 minute', 'geditorial-estimated' );
		else
			$estimated = sprintf(
				/* translators: `%s`: number of minutes */
				_n( '%s minute', '%s minutes', $minutes, 'geditorial-estimated' ),
				Core\Number::localize( $minutes )
			);

		if ( $info )
			return '<span data-toggle="tooltip" title="'.Core\HTML::escape( sprintf(
				/* translators: `%s`: average word count */
				_x( 'If you try to read %s words per minute.', 'Title Attr', 'geditorial-estimated' ),
				Core\Number::format( $avgtime )
			) ).'">'.$estimated.'</span>';

		return $estimated;
	}
}
