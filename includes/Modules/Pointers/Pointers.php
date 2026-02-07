<?php namespace geminorum\gEditorial\Modules\Pointers;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Pointers extends gEditorial\Module
{
	use Internals\MetaBoxSupported;

	public static function module()
	{
		return [
			'name'   => 'pointers',
			'title'  => _x( 'Pointers', 'Modules: Pointers', 'geditorial-admin' ),
			'desc'   => _x( 'Editorial Content Hints', 'Modules: Pointers', 'geditorial-admin' ),
			'i18n'   => FALSE, // NOTE: strings in this module are loaded via plugin
			'icon'   => 'sticky',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
		];
	}

	public function init()
	{
		parent::init();

		if ( ! is_admin() )
			return;

		$this->filter_module( 'tabloid', 'post_summaries', 4, 50 );
		$this->filter_module( 'static_covers', 'post_summaries', 4, 50 );
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->base, [ 'post', 'edit' ], TRUE ) ) {

			if ( $this->posttype_supported( $screen->post_type ) ) {

				if ( 'post' == $screen->base ) {
					$this->_hook_general_supportedbox( $screen, NULL, 'side', 'high' );
				}
			}

		} else if ( in_array( $screen->base, [ 'edit-tags', 'term' ], TRUE ) ) {

			if ( $this->taxonomy_supported( $screen->taxonomy ) ) {

				if ( 'term' == $screen->base ) {
					$this->_hook_term_supportedbox( $screen, NULL, 'side', 'high' );
				}
			}
		}
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'supportedbox';

		if ( is_null( $screen ) )
			$screen = get_current_screen();

		if ( 'post' === $screen->base )
			$this->render_post_pointers( $object, $context, $screen );

		else if ( 'term' === $screen->base )
			$this->render_term_pointers( $object, $context, $screen );
	}

	public function render_post_pointers( $post, $context = 'box', $screen = NULL )
	{
		$action_context = sprintf( '%s_%s', $context, $post->post_type );

		echo $this->wrap_open( [ '-wrap-rows', sprintf( '-post-%s', $this->key ) ] );
		echo '<ul class="-rows">';

		$fired = $this->actions(
			'post',
			$post,
			$this->wrap_open_row( 'pointer', [
				'-post-pointer',
				$post->post_type,
				$context,
				$action_context,
				'%s',
			] ),
			'</li>',
			in_array( $post->post_status, [ 'auto-draft' ], TRUE ), // Is it a new post?!
			$action_context,
			$screen
		);

		if ( ! $fired )
			echo Core\HTML::row(
				_x( 'There are no pointers available!', 'Message', 'geditorial-admin' ),
				'-empty -no-pointers'
			);

		echo '</ul></div>';
	}

	public function render_term_pointers( $term, $context = 'box', $screen = NULL )
	{
		$action_context = sprintf( '%s_%s', $context, $term->taxonomy );

		echo $this->wrap_open( [ '-wrap-rows', sprintf( '-term-%s', $this->key ) ] );
		echo '<ul class="-rows">';

		$fired = $this->actions(
			'term',
			$term,
			$this->wrap_open_row( 'pointer', [
				'-term-pointer',
				$term->taxonomy,
				$context,
				$action_context,
				'%s',
			] ),
			'</li>',
			FALSE, // is it a new term?!
			$action_context,
			$screen
		);

		if ( ! $fired )
			echo Core\HTML::row(
				_x( 'There are no pointers available!', 'Message', 'geditorial-admin' ),
				'-empty -no-pointers'
			);

		echo '</ul></div>';
	}

	public function tabloid_post_summaries( $list, $data, $post, $context )
	{
		if ( $this->posttype_supported( $post->post_type ) )
			$list[] = [
				'key'     => $this->key,
				'title'   => $this->strings_metabox_title_via_posttype( $post->post_type, $context, NULL, $post ),
				'content' => self::buffer( [ $this, 'render_post_pointers' ], [ $post, $context	] ),
			];

		return $list;
	}

	public function static_covers_post_summaries( $list, $data, $post, $context )
	{
		return $this->tabloid_post_summaries( $list, $data, $post, $context );
	}
}
