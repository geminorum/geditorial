<?php namespace geminorum\gEditorial\Modules\Alphabet;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
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
				[
					'field'       => 'shortcode_posts_constant',
					'type'        => 'text',
					'title'       => _x( 'Posts Shortcode Tag', 'Setting: Setting Title', 'geditorial-alphabet' ),
					'description' => _x( 'Customizes the alphabet list of posts short-code tag. Leave blank for default.', 'Setting: Setting Description', 'geditorial-alphabet' ),
					'after'       => Settings::fieldAfterShortCodeConstant(),
					'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
					'field_class' => [ 'medium-text', 'code-text' ],
					'placeholder' => 'alphabet-posts',
				],
				[
					'field'       => 'shortcode_terms_constant',
					'type'        => 'text',
					'title'       => _x( 'Terms Shortcode Tag', 'Setting: Setting Title', 'geditorial-alphabet' ),
					'description' => _x( 'Customizes the alphabet list of terms short-code tag. Leave blank for default.', 'Setting: Setting Description', 'geditorial-alphabet' ),
					'after'       => Settings::fieldAfterShortCodeConstant(),
					'pattern'     => WordPress\ShortCode::NAME_INPUT_PATTERN,
					'field_class' => [ 'medium-text', 'code-text' ],
					'placeholder' => 'alphabet-terms',
				],
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
			'list_mode'         => 'dl',                        // `dl`/`ul`/`ol`
			'list_tag'          => NULL,
			'term_tag'          => NULL,
			'desc_tag'          => NULL,
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
			$keys     = Core\Arraay::pluck( $alphabet, 'key', 'letter' );

			$alt      = $args['alternative'] ? Core\L10n::getAlphabet( $args['alternative'] ) : FALSE;
			$alt_keys = $alt ? Core\Arraay::pluck( $alt, 'key', 'letter' ) : [];

			if ( $args['heading_cb'] && ! is_callable( $args['heading_cb'] ) )
				$args['heading_cb'] = FALSE;

			if ( $args['item_cb'] && ! is_callable( $args['item_cb'] ) )
				$args['item_cb'] = FALSE;

			$mode = $this->_get_alphabet_list_mode( $args['list_mode'], $args );

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

						$html.= ( count( $actives ) ? '</'.$mode['tag'].'><div class="clearfix"></div></li>' : '' );

						$html.= '<li id="'.$id.'"><h4 class="-heading">'.$letter.'</h4>';
						$html.= '<'.$mode['tag'].( $args['excerpt'] ? ' class="dl-horizontal"' : '' ).'>';
					}

					$actives[] = $current = $letter;
				}

				if ( $args['item_cb'] ) {

					$html.= call_user_func_array( $args['item_cb'], [ $post, $args ] );

				} else {

					$title = WordPress\Post::title( $post );
					$link  = Core\WordPress::getPostShortLink( $post->ID );

					$html.= '<'.$mode['term'].'><span class="-title">'.Core\HTML::link( $title, $link ).'</span>';

					if ( $args['comments'] && $post->comment_count )
						$html.= '<span class="-comments-count">'.WordPress\Strings::getCounted( $post->comment_count, $args['comments_template'] ).'</span>';

					$html.= '<span class="-dummy"></span></'.$mode['term'].'>';

					if ( $args['excerpt'] && $post->post_excerpt )
						$html.= '<'.$mode['desc'].' class="-excerpt">'
							.wpautop( WordPress\Strings::prepDescription( $post->post_excerpt, TRUE, FALSE ), FALSE )
							.'</'.$mode['desc'].'>';

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
			'list_mode'      => 'dl',                        // `dl`/`ul`/`ol`
			'list_tag'       => NULL,
			'term_tag'       => NULL,
			'desc_tag'       => NULL,
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
			$terms = $query->query( $query_args );

			$current = $html = $list = '';
			$actives = [];

			$alphabet = Core\L10n::getAlphabet( $args['locale'] );
			$keys     = Core\Arraay::pluck( $alphabet, 'key', 'letter' );

			$alt      = $args['alternative'] ? Core\L10n::getAlphabet( $args['alternative'] ) : FALSE;
			$alt_keys = $alt ? Core\Arraay::pluck( $alt, 'key', 'letter' ) : [];

			if ( $args['heading_cb'] && ! is_callable( $args['heading_cb'] ) )
				$args['heading_cb'] = FALSE;

			if ( $args['item_cb'] && ! is_callable( $args['item_cb'] ) )
				$args['item_cb'] = FALSE;

			$mode = $this->_get_alphabet_list_mode( $args['list_mode'], $args );

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

						$html.= ( count( $actives ) ? '</'.$mode['tag'].'><div class="clearfix"></div></li>' : '' );

						$html.= '<li id="'.$id.'"><h4 class="-heading">'.$letter.'</h4>';
						$html.= '<'.$mode['tag'].' class="-terms'.( $args['description'] ? ' -has-desc' : '' ).'">';
					}

					$actives[] = $current = $letter;
				}

				if ( $args['item_cb'] ) {

					$html.= call_user_func_array( $args['item_cb'], [ $term, $args ] );

				} else {

					$title = WordPress\Term::title( $term );
					// $title = Core\Text::nameFamilyLast( $title ); // no need on front
					$link  = WordPress\Term::link( $term );

					$html.= '<'.$mode['term'].'><span class="-title">'.Core\HTML::link( $title, $link ).'</span>';

					if ( $args['count'] && $term->count )
						$html.= '<span class="-term-count">'.WordPress\Strings::getCounted( $term->count, $args['count_template'] ).'</span>';

					$html.= '<span class="-dummy"></span></'.$mode['term'].'>';

					if ( $args['description'] && $term->description )
						$html.= '<'.$mode['desc'].' class="-description">'
							.wpautop( WordPress\Strings::prepDescription( $term->description, TRUE, FALSE ), FALSE )
							.'</'.$mode['desc'].'>';

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

	private function _get_alphabet_list_mode( $mode, $defaults )
	{
		$args = self::atts( [
			'list_tag' => NULL,
			'term_tag' => NULL,
			'desc_tag' => NULL,
		], $defaults );

		switch ( $mode ) {

			case 'ul':

				$mode = [
					'tag'  => $args['list_tag'] ?? 'ul',
					'term' => $args['term_tag'] ?? 'li',
					'desc' => $args['desc_tag'] ?? 'li',
				];

				break;

			case 'ol':

				$mode = [
					'tag'  => $args['list_tag'] ?? 'ol',
					'term' => $args['term_tag'] ?? 'li',
					'desc' => $args['desc_tag'] ?? 'li',
				];

				break;

			default:
			case 'dl':

				$mode = [
					'tag'  => $args['list_tag'] ?? 'dl',
					'term' => $args['term_tag'] ?? 'dt',
					'desc' => $args['desc_tag'] ?? 'dd',
				];
		}

		return $mode;
	}
}
