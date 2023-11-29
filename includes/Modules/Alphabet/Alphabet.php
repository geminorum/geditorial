<?php namespace geminorum\gEditorial\Modules\Alphabet;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\WordPress;

class Alphabet extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'   => 'alphabet',
			'title'  => _x( 'Alphabet', 'Modules: Alphabet', 'geditorial-admin' ),
			'desc'   => _x( 'A to Z Lists for Post Types, Taxonomies and Users', 'Modules: Alphabet', 'geditorial-admin' ),
			'icon'   => 'editor-textcolor',
			'i18n'   => FALSE,
			'access' => 'stable',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
		];
	}

	protected function get_global_constants()
	{
		return [
			'shortcode_posts' => 'alphabet-posts',
			'shortcode_terms' => 'alphabet-terms',
		];
	}

	protected function taxonomies_excluded( $extra = [] )
	{
		return $this->filters( 'taxonomies_excluded', Settings::taxonomiesExcluded( [
			'system_tags',
			'nav_menu',
			'post_format',
			'link_category',
			'bp_member_type',
			'bp_group_type',
			'bp-email-type',
			'ef_editorial_meta',
			'following_users',
			'ef_usergroup',
			'post_status',
			'rel_people',
			'rel_post',
			'affiliation',
			'specs',
		] + $extra ) );
	}

	public function init()
	{
		parent::init();

		$this->register_shortcode( 'shortcode_posts', NULL, TRUE );
		$this->register_shortcode( 'shortcode_terms', NULL, TRUE );
	}

	public function shortcode_posts( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'locale'            => Core\L10n::locale( TRUE ),
			'alternative'       => 'en_US', // FALSE to disable
			'post_type'         => $this->posttypes(),
			'comments'          => FALSE,
			'comments_template' => '&nbsp;(%s)',
			'excerpt'           => FALSE,
			'list_tag'          => 'dl',
			'term_tag'          => 'dt',
			'desc_tag'          => 'dd',
			'heading_cb'        => FALSE,
			'item_cb'           => FALSE,
			'context'           => NULL,
			'wrap'              => TRUE,
			'before'            => '',
			'after'             => '',
			'class'             => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$key = $this->hash( 'posts', $args );

		if ( Core\WordPress::isFlush() )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			if ( $args['locale'] == $args['alternative'] )
				$args['alternative'] = FALSE;

			$query_args = [
				'orderby'          => 'title',
				'order'            => 'ASC',
				'post_status'      => 'publish',
				'post_type'        => $args['post_type'],
				'posts_per_page'   => -1,
				'suppress_filters' => TRUE,
			];

			$query = new \WP_Query();
			$posts = $query->query( $query_args );

			// FIXME: check for empty

			$current = $html = $list = '';
			$actives = [];

			$alphabet = Core\L10n::getAlphabet( $args['locale'] );
			$keys     = array_flip( Core\Arraay::column( $alphabet, 'letter', 'key' ) );

			$alt      = $args['alternative'] ? Core\L10n::getAlphabet( $args['alternative'] ) : FALSE;
			$alt_keys = $alt ? array_flip( Core\Arraay::column( $alt, 'letter', 'key' ) ) : [];

			if ( $args['heading_cb'] && ! is_callable( $args['heading_cb'] ) )
				$args['heading_cb'] = FALSE;

			if ( $args['item_cb'] && ! is_callable( $args['item_cb'] ) )
				$args['item_cb'] = FALSE;

			foreach ( $posts as $post ) {

				$letter = Core\L10n::firstLetter( $post->post_title, $alphabet, $alt );

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

						$html.= ( count( $actives ) ? '</'.$args['list_tag'].'><div class="clearfix"></div></li>' : '' );

						$html.= '<li id="'.$id.'"><h4 class="-heading">'.$letter.'</h4>';
						$html.= '<'.$args['list_tag'].( $args['excerpt'] ? ' class="dl-horizontal"' : '' ).'>';
					}

					$actives[] = $current = $letter;
				}

				if ( $args['item_cb'] ) {

					$html.= call_user_func_array( $args['item_cb'], [ $post, $args ] );

				} else {

					$title = WordPress\Post::title( $post );
					$link  = Core\WordPress::getPostShortLink( $post->ID );

					$html.= '<'.$args['term_tag'].'><span class="-title">'.Core\HTML::link( $title, $link ).'</span>';

					if ( $args['comments'] && $post->comment_count )
						$html.= '<span class="-comments-count">'.WordPress\Strings::getCounted( $post->comment_count, $args['comments_template'] ).'</span>';

					$html.= '<span class="-dummy"></span></'.$args['term_tag'].'>';

					if ( $args['excerpt'] && $post->post_excerpt )
						$html.= '<'.$args['desc_tag'].' class="-excerpt">'
							.wpautop( Helper::prepDescription( $post->post_excerpt, TRUE, FALSE ), FALSE )
							.'</'.$args['desc_tag'].'>';

					else if ( 'dd' == $args['desc_tag'] && $args['excerpt'] )
						$html.= '<'.$args['desc_tag'].' class="-empty"></'.$args['desc_tag'].'>';
				}
			}

			$html.= '</'.$args['list_tag'].'><div class="clearfix"></div></li>';

			$list.= $this->get_alphabet_list_html( [ [ 'letter' => '#', 'key' => '#', 'name' => '#' ] ], $actives );
			$list.= $this->get_alphabet_list_html( $alt, $actives );
			$list.= $this->get_alphabet_list_html( $alphabet, $actives );

			$fields = '<input class="-search" type="search" style="display:none;" />';

			$html = '<ul class="list-inline -letters">'.$list.'</ul>'
				.$fields.'<ul class="list-unstyled -definitions">'.$html.'</ul>';

			$html = ShortCode::wrap( $html, $this->constant( 'shortcode_posts' ), $args );
			$html = Core\Text::minifyHTML( $html );

			set_transient( $key, $html, 12 * HOUR_IN_SECONDS );
		}

		return $html;
	}

	public function shortcode_terms( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'locale'         => Core\L10n::locale( TRUE ),
			'alternative'    => 'en_US', // FALSE to disable
			'taxonomy'       => $this->taxonomies(),
			'description'    => FALSE,
			'count'          => FALSE,
			'count_template' => '&nbsp;(%s)',
			'list_tag'       => 'dl',
			'term_tag'       => 'dt',
			'desc_tag'       => 'dd',
			'heading_cb'     => FALSE,
			'item_cb'        => FALSE,
			'context'        => NULL,
			'wrap'           => TRUE,
			'before'         => '',
			'after'          => '',
			'class'          => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$key = $this->hash( 'terms', $args );

		if ( Core\WordPress::isFlush() )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			if ( $args['locale'] == $args['alternative'] )
				$args['alternative'] = FALSE;

			$query_args = [
				'taxonomy' => $args['taxonomy'],
				'orderby'  => 'name',
				'order'    => 'ASC',
			];

			$query = new \WP_Term_Query();
			$terms = $query->query( $args );

			$current = $html = $list = '';
			$actives = [];

			$alphabet = Core\L10n::getAlphabet( $args['locale'] );
			$keys     = array_flip( Core\Arraay::column( $alphabet, 'letter', 'key' ) );

			$alt      = $args['alternative'] ? Core\L10n::getAlphabet( $args['alternative'] ) : FALSE;
			$alt_keys = $alt ? array_flip( Core\Arraay::column( $alt, 'letter', 'key' ) ) : [];

			if ( $args['heading_cb'] && ! is_callable( $args['heading_cb'] ) )
				$args['heading_cb'] = FALSE;

			if ( $args['item_cb'] && ! is_callable( $args['item_cb'] ) )
				$args['item_cb'] = FALSE;

			foreach ( $terms as $term ) {

				$letter = Core\L10n::firstLetter( $term->name, $alphabet, $alt );

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

						$html.= ( count( $actives ) ? '</'.$args['list_tag'].'><div class="clearfix"></div></li>' : '' );

						$html.= '<li id="'.$id.'"><h4 class="-heading">'.$letter.'</h4>';
						$html.= '<'.$args['list_tag'].( $args['description'] ? ' class="dl-horizontal"' : '' ).'>';
					}

					$actives[] = $current = $letter;
				}

				if ( $args['item_cb'] ) {

					$html.= call_user_func_array( $args['item_cb'], [ $term, $args ] );

				} else {

					$title = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );
					// $title = Core\Text::nameFamilyLast( $title ); // no need on front
					$link  = get_term_link( $term->term_id, $term->taxonomy );

					$html.= '<'.$args['term_tag'].'><span class="-title">'.Core\HTML::link( $title, $link ).'</span>';

					if ( $args['count'] && $term->count )
						$html.= '<span class="-term-count">'.WordPress\Strings::getCounted( $term->count, $args['count_template'] ).'</span>';

					$html.= '<span class="-dummy"></span></'.$args['term_tag'].'>';

					if ( $args['description'] && $term->description )
						$html.= '<'.$args['desc_tag'].' class="-description">'
							.wpautop( Helper::prepDescription( $term->description, TRUE, FALSE ), FALSE )
							.'</'.$args['desc_tag'].'>';

					else if ( 'dd' == $args['desc_tag'] && $args['description'] )
						$html.= '<'.$args['desc_tag'].' class="-empty"></'.$args['desc_tag'].'>';
				}
			}

			$html.= '</'.$args['list_tag'].'><div class="clearfix"></div></li>';

			$list.= $this->get_alphabet_list_html( [ [ 'letter' => '#', 'key' => '#', 'name' => '#' ] ], $actives );
			$list.= $this->get_alphabet_list_html( $alt, $actives );
			$list.= $this->get_alphabet_list_html( $alphabet, $actives );

			$fields = '<input class="-search" type="search" style="display:none;" />';

			$html = '<ul class="list-inline -letters">'.$list.'</ul>'
				.$fields.'<ul class="list-unstyled -definitions">'.$html.'</ul>';

			$html = ShortCode::wrap( $html, $this->constant( 'shortcode_terms' ), $args );
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
		if ( empty( array_intersect( Core\Arraay::column( $alphabet, 'letter' ), $actives ) ) )
			return '';

		$list = [];

		foreach ( $alphabet as $key => $info )
			$list[] = in_array( $info['letter'], $actives, TRUE )
				? Core\HTML::scroll( $info['letter'], $info['key'], $info['name'] )
				: Core\HTML::tag( 'span', $info['letter'] );

		return '<li>'.implode( '</li><li>', $list ).'</li>';
	}
}
