<?php namespace geminorum\gEditorial\Modules\Alphabet;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Misc;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Alphabet extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'alphabet',
			'title'    => _x( 'Alphabet', 'Modules: Alphabet', 'geditorial-admin' ),
			'desc'     => _x( 'A to Z Lists for Post-types, Taxonomies and Users', 'Modules: Alphabet', 'geditorial-admin' ),
			'icon'     => 'editor-textcolor',
			'i18n'     => 'adminonly',
			'access'   => 'stable',
			'keywords' => [
				'shortcodemodule',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
			'_constants'        => [
				$this->settings_shortcode_constant(
					'shortcode_posts',
					_x( 'Posts', 'Setting: Short-code Title', 'geditorial-alphabet' )
				),

				$this->settings_shortcode_constant(
					'shortcode_terms',
					_x( 'Terms', 'Setting: Short-code Title', 'geditorial-alphabet' )
				),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'shortcode_posts' => 'alphabet-posts',
			'shortcode_terms' => 'alphabet-terms',
		];
	}

	public function init()
	{
		parent::init();

		$this->register_shortcode( 'shortcode_posts', TRUE );
		$this->register_shortcode( 'shortcode_terms', TRUE );
	}

	public function shortcode_posts( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'locale'            => Core\L10n::locale( TRUE ),
			'alternative'       => 'en_US',                     // FALSE to disable
			'posttype'          => $this->posttypes(),
			'exclude_posttypes' => '',
			'term'              => FALSE,
			'excerpt'           => FALSE,
			'comments'          => FALSE,
			'comments_template' => '&nbsp;(%s)',
			'meta_title'        => NULL,
			'list_mode'         => 'dl',                        // `dl`/`ul`/`ol`
			'list_tag'          => NULL,
			'term_tag'          => NULL,
			'desc_tag'          => NULL,
			'head_tag'          => NULL,
			'heading_cb'        => FALSE,
			'item_cb'           => FALSE,
			'context'           => NULL,
			'wrap'              => TRUE,
			'before'            => '',
			'after'             => '',
			'class'             => '',
		], $atts, $tag ?: $this->constant( 'shortcode_posts' ) );

		if ( FALSE === $args['context'] || empty( $args['posttype'] ) )
			return NULL;

		$key = $this->hash( 'posts', $args );

		if ( WordPress\IsIt::flush() )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			if ( $args['locale'] == $args['alternative'] )
				$args['alternative'] = FALSE;

			if ( 'any' === $args['posttypes'] )
				$posttypes = WordPress\PostType::get( -1 );

			else
				$posttypes = WordPress\Strings::getSeparated( $args['posttypes'] );

			if ( $args['exclude_posttypes'] )
				$posttypes = array_diff( $posttypes,
					WordPress\Strings::getSeparated( $args['exclude_posttypes'] )
				);

			$query_args = [
				'orderby'          => 'title',
				'order'            => 'ASC',
				'post_status'      => 'publish',
				'post_type'        => $posttypes,
				'posts_per_page'   => -1,
				'suppress_filters' => TRUE,
			];

			if ( $term = WordPress\Term::get( $args['term'] ) )
				$query_args['tax_query'] = [ [
					'taxonomy' => $term->taxonomy,
					'terms'    => [ $term->term_id ],
				] ];

			$query = new \WP_Query();
			$posts = $query->query( $query_args );

			if ( empty( $posts ) )
				return $content;

			$current = $html = $list = '';
			$actives = [];

			$alphabet = Misc\Alphabet::get( $args['locale'] );
			$keys     = Core\Arraay::pluck( $alphabet, 'key', 'letter' );

			$alt      = $args['alternative'] ? Misc\Alphabet::get( $args['alternative'] ) : FALSE;
			$alt_keys = $alt ? Core\Arraay::pluck( $alt, 'key', 'letter' ) : [];

			if ( $args['heading_cb'] && ! is_callable( $args['heading_cb'] ) )
				$args['heading_cb'] = FALSE;

			if ( $args['item_cb'] && ! is_callable( $args['item_cb'] ) )
				$args['item_cb'] = FALSE;

			if ( is_null( $args['meta_title'] ) )
				$args['meta_title'] = $this->filters( 'post_title_metakeys', [], $posttypes );

			else if ( $args['meta_title'] && ! is_array( $args['meta_title'] ) )
				$args['meta_title'] = array_fill_keys( $posttypes, $args['meta_title'] );

			else if ( ! $args['meta_title'] )
				$args['meta_title'] = [];

			$mode = $this->_get_alphabet_list_mode( $args['list_mode'], $args );

			foreach ( $posts as $post ) {

				$letter = Misc\Alphabet::firstLetter( Core\Text::trim( $post->post_title ), $alphabet, $alt );

				if ( $current != $letter ) {

					if ( $alt && array_key_exists( $letter, $alt_keys ) )
						$id = $alt_keys[$letter];

					else if ( array_key_exists( $letter, $keys ) )
						$id = $keys[$letter];

					else
						$id = strtolower( $letter );

					if ( $args['heading_cb'] ) {

						$html.= call_user_func_array( $args['heading_cb'], [ $letter, $id, $args ] );

					} else {

						$html.= ( count( $actives ) ? '</'.$mode['tag'].'><div class="clearfix"></div></li>' : '' );

						$html.= '<li id="'.$id.'"><'.$mode['head'].' class="-heading">'.$letter.'</'.$mode['head'].'>';
						$html.= '<'.$mode['tag'].' class="-terms'.( $args['excerpt'] ? ' -with-desc' : ' -without-desc' ).'">';
					}

					$actives[] = $current = $letter;
				}

				if ( $args['item_cb'] ) {

					$html.= call_user_func_array( $args['item_cb'], [ $post, $args, $mode ] );

				} else {

					$name  = WordPress\Post::title( $post );
					$link  = WordPress\Post::shortlink( $post->ID );
					$title = empty( $args['meta_title'][$post->post_type] )
						? FALSE
						: get_post_meta( $post->ID, $args['meta_title'][$post->post_type], TRUE );

					$html.= '<'.$mode['term'].'><span class="-title">'.Core\HTML::tag( 'a', [ 'href' => $link, 'title' => $title ], $name ).'</span>';

					if ( $args['comments'] && $post->comment_count )
						$html.= '<span class="-comments-count">'.WordPress\Strings::getCounted( $post->comment_count, $args['comments_template'] ).'</span>';

					$html.= '<span class="-dummy"></span></'.$mode['term'].'>';

					if ( $args['excerpt'] && $post->post_excerpt )
						$html.= '<'.$mode['desc'].' class="-excerpt"><div class="-wrap">'
							.wpautop( WordPress\Strings::prepDescription( $post->post_excerpt, TRUE, FALSE ), FALSE )
							.'</div></'.$mode['desc'].'>';

					else if ( 'dd' === $mode['desc'] && $args['excerpt'] )
						$html.= '<'.$mode['desc'].' class="-empty"></'.$mode['desc'].'>';
				}
			}

			$html.= '</'.$mode['tag'].'><div class="clearfix"></div></li>';

			$list.= $this->get_alphabet_list_html( [ [ 'letter' => '#', 'key' => '#', 'name' => '#' ] ], $actives );
			$list.= $this->get_alphabet_list_html( $alt, $actives );
			$list.= $this->get_alphabet_list_html( $alphabet, $actives );

			$fields = '<input class="-search" type="search" style="display:none;" />';

			$html = '<ul class="'.$this->key.'-letters -letters list-inline">'.$list.'</ul>'
				.$fields.'<ul class="'.$this->key.'-definitions -definitions list-unstyled">'.$html.'</ul>';

			$html = gEditorial\ShortCode::wrap( $html, $this->constant( 'shortcode_posts' ), $args );
			$html = Core\Text::minifyHTML( $html );

			set_transient( $key, $html, 12 * HOUR_IN_SECONDS );
		}

		return $html;
	}

	public function shortcode_terms( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'locale'             => Core\L10n::locale( TRUE ),
			'alternative'        => 'en_US',                     // FALSE to disable
			'taxonomy'           => $this->taxonomies(),
			'exclude_taxonomies' => '',
			'description'        => FALSE,
			'hide_empty'         => TRUE,
			'count'              => FALSE,
			'count_template'     => '&nbsp;(%s)',
			'meta_title'         => NULL,
			'list_mode'          => 'dl',                        // `dl`/`ul`/`ol`
			'list_tag'           => NULL,
			'term_tag'           => NULL,
			'desc_tag'           => NULL,
			'head_tag'           => NULL,
			'heading_cb'         => FALSE,
			'item_cb'            => FALSE,
			'context'            => NULL,
			'wrap'               => TRUE,
			'before'             => '',
			'after'              => '',
			'class'              => '',
		], $atts, $tag ?: $this->constant( 'shortcode_terms' ) );

		if ( FALSE === $args['context'] || empty( $args['taxonomy'] ) )
			return NULL;

		$key = $this->hash( 'terms', $args );

		if ( WordPress\IsIt::flush() )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			if ( $args['locale'] == $args['alternative'] )
				$args['alternative'] = FALSE;

			$taxonomies = WordPress\Strings::getSeparated( $args['taxonomy'] );

			if ( $args['exclude_taxonomies'] )
				$taxonomies = array_diff( $taxonomies,
					WordPress\Strings::getSeparated( $args['exclude_taxonomies'] )
				);

			$query_args = [
				'hide_empty' => $args['hide_empty'],
				'taxonomy'   => $taxonomies,
				'orderby'    => 'name',
				'order'      => 'ASC',
			];

			$query = new \WP_Term_Query();
			$terms = $query->query( $query_args );

			if ( empty( $terms ) )
				return $content;

			$current = $html = $list = '';
			$actives = [];

			$alphabet = Misc\Alphabet::get( $args['locale'] );
			$keys     = Core\Arraay::pluck( $alphabet, 'key', 'letter' );

			$alt      = $args['alternative'] ? Misc\Alphabet::get( $args['alternative'] ) : FALSE;
			$alt_keys = $alt ? Core\Arraay::pluck( $alt, 'key', 'letter' ) : [];

			if ( $args['heading_cb'] && ! is_callable( $args['heading_cb'] ) )
				$args['heading_cb'] = FALSE;

			if ( $args['item_cb'] && ! is_callable( $args['item_cb'] ) )
				$args['item_cb'] = FALSE;

			if ( is_null( $args['meta_title'] ) )
				$args['meta_title'] = $this->filters( 'term_title_metakeys', [], $taxonomies );

			else if ( $args['meta_title'] && ! is_array( $args['meta_title'] ) )
				$args['meta_title'] = array_fill_keys( $taxonomies, $args['meta_title'] );

			else if ( ! $args['meta_title'] )
				$args['meta_title'] = [];

			$mode = $this->_get_alphabet_list_mode( $args['list_mode'], $args );

			foreach ( $terms as $term ) {

				$letter = Misc\Alphabet::firstLetter( Core\Text::trim( $term->name ), $alphabet, $alt );

				if ( $current != $letter ) {

					if ( $alt && array_key_exists( $letter, $alt_keys ) )
						$id = $alt_keys[$letter];

					else if ( array_key_exists( $letter, $keys ) )
						$id = $keys[$letter];

					else
						$id = strtolower( $letter );

					if ( $args['heading_cb'] ) {

						$html.= call_user_func_array( $args['heading_cb'], [ $letter, $id, $args ] );

					} else {

						$html.= ( count( $actives ) ? '</'.$mode['tag'].'><div class="clearfix"></div></li>' : '' );

						$html.= '<li id="'.$id.'"><'.$mode['head'].' class="-heading">'.$letter.'</'.$mode['head'].'>';
						$html.= '<'.$mode['tag'].' class="-terms'.( $args['description'] ? ' -with-desc' : ' -without-desc' ).'">';
					}

					$actives[] = $current = $letter;
				}

				if ( $args['item_cb'] ) {

					$html.= call_user_func_array( $args['item_cb'], [ $term, $args, $mode ] );

				} else {

					$name  = WordPress\Term::title( $term );
					$link  = WordPress\Term::link( $term );
					$title = empty( $args['meta_title'][$term->taxonomy] )
						? FALSE
						: get_term_meta( $term->term_id, $args['meta_title'][$term->taxonomy], TRUE );

					$html.= '<'.$mode['term'].'><span class="-title">'.Core\HTML::tag( 'a', [ 'href' => $link, 'title' => $title ], $name ).'</span>';

					if ( $args['count'] && $term->count )
						$html.= '<span class="-term-count">'.WordPress\Strings::getCounted( $term->count, $args['count_template'] ).'</span>';

					$html.= '<span class="-dummy"></span></'.$mode['term'].'>';

					if ( $args['description'] && $term->description )
						$html.= '<'.$mode['desc'].' class="-description"><div class="-wrap">'
							.wpautop( WordPress\Strings::prepDescription( $term->description, TRUE, FALSE ), FALSE )
							.'</div></'.$mode['desc'].'>';

					else if ( 'dd' === $mode['desc'] && $args['description'] )
						$html.= '<'.$mode['desc'].' class="-empty"></'.$mode['desc'].'>';
				}
			}

			$html.= '</'.$mode['tag'].'><div class="clearfix"></div></li>';

			$list.= $this->get_alphabet_list_html( [ [ 'letter' => '#', 'key' => '#', 'name' => '#' ] ], $actives );
			$list.= $this->get_alphabet_list_html( $alt, $actives );
			$list.= $this->get_alphabet_list_html( $alphabet, $actives );

			$fields = '<input class="-search" type="search" style="display:none;" />';

			$html = '<ul class="'.$this->key.'-letters -letters list-inline">'.$list.'</ul>'
				.$fields.'<ul class="'.$this->key.'-definitions -definitions list-unstyled">'.$html.'</ul>';

			$html = gEditorial\ShortCode::wrap( $html, $this->constant( 'shortcode_terms' ), $args );
			$html = Core\Text::minifyHTML( $html );

			set_transient( $key, $html, 12 * HOUR_IN_SECONDS );
		}

		return $html;
	}

	private function get_alphabet_list_html( $alphabet, $actives = [], $tag = 'li' )
	{
		if ( empty( $alphabet ) )
			return '';

		// no actives on this alphabet
		if ( ! Core\Arraay::exists( Core\Arraay::column( $alphabet, 'letter' ), $actives ) )
			return '';

		$list = [];

		foreach ( $alphabet as $key => $info )
			$list[] = in_array( $info['letter'], $actives, TRUE )
				? Core\HTML::scroll( $info['letter'], $info['key'], $info['name'] )
				: Core\HTML::tag( 'span', $info['letter'] );

		return '<li>'.implode( '</li><li>', $list ).'</li>';
	}

	// TODO: Move to `Core\HTML`
	private function _get_alphabet_list_mode( $mode, $defaults )
	{
		$args = self::atts( [
			'list_tag' => NULL,
			'term_tag' => NULL,
			'desc_tag' => NULL,
			'head_tag' => NULL,
		], $defaults );

		switch ( $mode ) {

			case 'ul':

				$mode = [
					'tag'  => $args['list_tag'] ?? 'ul',
					'term' => $args['term_tag'] ?? 'li',
					'desc' => $args['desc_tag'] ?? 'li',
					'head' => $args['head_tag'] ?? 'h4',
				];

				break;

			case 'ol':

				$mode = [
					'tag'  => $args['list_tag'] ?? 'ol',
					'term' => $args['term_tag'] ?? 'li',
					'desc' => $args['desc_tag'] ?? 'li',
					'head' => $args['head_tag'] ?? 'h4',
				];

				break;

			default:
			case 'dl':

				$mode = [
					'tag'  => $args['list_tag'] ?? 'dl',
					'term' => $args['term_tag'] ?? 'dt',
					'desc' => $args['desc_tag'] ?? 'dd',
					'head' => $args['head_tag'] ?? 'h4',
				];
		}

		return $mode;
	}
}
