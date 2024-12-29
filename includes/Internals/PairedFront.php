<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\WordPress;

trait PairedFront
{
	// excludes paired posttype from subterm archives
	protected function _hook_paired_exclude_from_subterm()
	{
		if ( ! $this->get_setting( 'subterms_support' ) )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		add_action( 'pre_get_posts', function ( &$wp_query ) use ( $constants ) {

			$subterms = $this->constant( $constants[2] );

			if ( ! $wp_query->is_main_query() || ! $wp_query->is_tax( $subterms ) )
				return;

			$primaries = WordPress\PostType::getIDs( $this->constant( $constants[0] ), [
				'tax_query' => [ [
					'taxonomy' => $subterms,
					'terms'    => [ get_queried_object_id() ],
				] ],
			] );

			if ( count( $primaries ) ) {

				if ( $not = $wp_query->get( 'post__not_in' ) )
					$primaries = Core\Arraay::prepNumeral( $not, $primaries );

				$wp_query->set( 'post__not_in', $primaries );
			}
		}, 8 );
	}

	protected function _hook_paired_override_term_link()
	{
		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		add_filter( 'term_link', function ( $link, $term, $taxonomy ) use ( $constants ) {

			if ( $taxonomy !== $this->constant( $constants[1] ) )
				return $link;

			if ( $post_id = $this->paired_get_to_post_id( $term, $constants[0], $constants[1] ) )
				return get_permalink( $post_id );

			return $link;

		}, 9, 3 );
	}

	protected function pairedfront_hook__post_tabs( $priority = NULL )
	{
		if ( ! $this->get_setting( 'tabs_support', TRUE ) )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		if ( ! gEditorial()->enabled( 'tabs' ) )
			return FALSE;

		add_filter( $this->hook_base( 'tabs', 'builtins_tabs' ),
			function ( $tabs, $posttype ) use ( $constants, $priority ) {

				if ( in_array( $posttype, $this->posttypes(), TRUE ) )
					$tabs[] = [
						'name' => $this->hook( 'paired', $posttype ),

						'title' => sprintf( is_admin() ? '%1$s: %2$s' : '%2$s',
							$this->module->title,
							Helper::getPostTypeLabel( $this->constant( $constants[0] ), 'extended_label' )
						),

						'description' => sprintf(
							/* translators: %1$s: supported object label, %2$s: main post singular label */
							_x( '%1$s items connected to this %2$s.', 'Internal: PairedFront: Tab Description', 'geditorial' ),
							Helper::getPostTypeLabel( $this->constant( $constants[0] ), 'name' ),
							Helper::getPostTypeLabel( $posttype, 'singular_name' ),
						),

						'viewable' => function ( $post ) use ( $posttype, $constants ) {
							return (bool) $this->paired_all_connected_from( $post, 'tabs' );
						},

						'callback' => function ( $post ) use ( $posttype, $constants ) {

							echo ShortCode::listPosts( 'paired',
								$this->constant( $constants[0] ),
								$this->constant( $constants[1] ),
								[
									'post_id'   => $post,
									'posttypes' => (array) $posttype,
									'context'   => 'tabs',
									'wrap'      => FALSE,
									'title'     => FALSE,
								],
								NULL,
								'',
								$this->module->name
							);
						},

						'priority' => $priority ?? 40,
					];

				if ( $posttype !== $this->constant( $constants[0] ) )
					return $tabs;

				foreach ( $this->posttypes() as $supported )
					$tabs[] = [
						'name' => $this->hook( 'supported', $supported ),

						'title' => sprintf( is_admin() ? '%1$s: %2$s' : '%2$s',
							$this->module->title,
							Helper::getPostTypeLabel( $supported, 'extended_label' )
						),

						'description' => sprintf(
							/* translators: %1$s: supported object label, %2$s: main post singular label */
							_x( '%1$s items connected to this %2$s.', 'Internal: PairedFront: Tab Description', 'geditorial' ),
							Helper::getPostTypeLabel( $supported, 'name' ),
							Helper::getPostTypeLabel( $posttype, 'singular_name' ),
						),

						'viewable' => function ( $post ) use ( $supported, $constants ) {
							return (bool) $this->paired_count_connected_to( $post, 'tabs', [], (array) $supported );
						},

						'callback' => function ( $post ) use ( $supported, $constants ) {

							echo ShortCode::listPosts( 'paired',
								$this->constant( $constants[0] ),
								$this->constant( $constants[1] ),
								[
									'post_id'   => $post,
									'posttypes' => (array) $supported,
									'context'   => 'tabs',
									'wrap'      => FALSE,
									'title'     => FALSE,
								],
								NULL,
								'',
								$this->module->name
							);
						},

						'priority' => $priority ?? 40,
					];

				return $tabs;
			}, 10, 2 );
	}
}
