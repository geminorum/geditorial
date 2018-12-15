<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\WordPress;

class Alphabet extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'alphabet',
			'title' => _x( 'Alphabet', 'Modules: Alphabet', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'A to Z Lists for Post Types, Taxonomies and Users', 'Modules: Alphabet', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'editor-textcolor',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
			'_supports' => [
				'shortcode_support',
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

	protected function taxonomies_excluded()
	{
		return Settings::taxonomiesExcluded( [
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
		] );
	}

	public function init()
	{
		parent::init();

		$this->register_shortcode( 'shortcode_posts' );
		$this->register_shortcode( 'shortcode_terms' );
	}

	public function shortcode_posts( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'locale'    => get_locale(),
			'post_type' => $this->posttypes(),
			'comments'  => FALSE,
			'excerpt'   => FALSE,
			'item_cb'   => FALSE,
			'context'   => NULL,
			'wrap'      => TRUE,
			'before'    => '',
			'after'     => '',
			'class'     => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$key = $this->hash( 'posts', $args );

		if ( WordPress::isFlush() )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			$query_args = [
				'orderby'          => 'title',
				'order'            => 'ASC',
				'post_status'      => 'publish',
				'post_type'        => $args['post_type'],
				'posts_per_page'   => -1,
				'suppress_filters' => TRUE,
			];

			$query = new \WP_Query;
			$posts = $query->query( $query_args );

			$current  = $html = $list = '';
			$actives  = [];
			$alphabet = self::getAlphabet( $args['locale'] );
			$keys     = array_flip( Arraay::column( $alphabet, 'letter', 'key' ) );

			if ( $args['item_cb'] && ! is_callable( $args['item_cb'] ) )
				$args['item_cb'] = FALSE;

			foreach ( $posts as $post ) {

				$letter = self::firstLetter( $post->post_title, $alphabet );

				if ( $current != $letter ) {

					$html.= ( count( $actives ) ? '</dl><div class="clearfix"></div></li>' : '' );

					$html.= '<li id="'.( isset( $keys[$letter] ) ? $keys[$letter] : $letter ).'">';
					$html.= '<h4 class="-heading">'.$letter.'</h4><dl'.( $args['excerpt'] ? ' class="dl-horizontal"' : '' ).'>';

					$actives[] = $current = $letter;
				}

				if ( $args['item_cb'] ) {

					$html.= call_user_func_array( $args['item_cb'], [ $post, $args ] );

				} else {

					$title = Helper::getPostTitle( $post );
					$link  = WordPress::getPostShortLink( $post->ID );

					$html.= '<dt><span class="-title">'.HTML::link( $title, $link ).'</span>';

					if ( $args['comments'] && $post->comment_count )
						$html.= '<span class="-comments-count">'.Helper::getCounted( $post->comment_count, '&nbsp;(%s)' ).'</span>';

					$html.= '</dt>';

					if ( $args['excerpt'] && $post->post_excerpt )
						$html.= '<dd class="-excerpt">'.wpautop( Helper::prepDescription( $post->post_excerpt, TRUE, FALSE ), FALSE ).'</dd>';
					else
						$html.= '<dd class="-empty"></dd>';
				}
			}

			$html.= '</dl><div class="clearfix"></div></li>';

			foreach ( $alphabet as $key => $info )
				$list.= '<li>'.(
					in_array( $info['letter'], $actives )
					? HTML::scroll( $info['letter'], $info['key'], $info['name'] )
					: '<span>'.$info['letter'].'</span>'
				).'</li>';

			$fields = '<input class="-search" type="search" style="display:none;" />';
			$html   = '<ul class="list-inline -letters">'.$list.'</ul>'.$fields.'<ul class="list-unstyled -definitions">'.$html.'</ul>';

			$html = ShortCode::wrap( $html, $this->constant( 'shortcode_posts' ), $args );
			$html = Text::minifyHTML( $html );

			set_transient( $key, $html, 12 * HOUR_IN_SECONDS );
		}

		return $html;
	}

	public function shortcode_terms( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'locale'      => get_locale(),
			'taxonomy'    => $this->taxonomies(),
			'description' => FALSE,
			'count'       => FALSE,
			'item_cb'     => FALSE,
			'context'     => NULL,
			'wrap'        => TRUE,
			'before'      => '',
			'after'       => '',
			'class'       => '',
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$key = $this->hash( 'terms', $args );

		if ( WordPress::isFlush() )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			$query_args = [
				'taxonomy' => $args['taxonomy'],
				'orderby'  => 'name',
				'order'    => 'ASC',
			];

			$query = new \WP_Term_Query();
			$terms = $query->query( $args );

			$current  = $html = $list = '';
			$actives  = [];
			$alphabet = self::getAlphabet( $args['locale'] );
			$keys     = array_flip( Arraay::column( $alphabet, 'letter', 'key' ) );

			if ( $args['item_cb'] && ! is_callable( $args['item_cb'] ) )
				$args['item_cb'] = FALSE;

			foreach ( $terms as $term ) {

				$letter = self::firstLetter( $term->name, $alphabet );

				if ( $current != $letter ) {

					$html.= ( count( $actives ) ? '</dl><div class="clearfix"></div></li>' : '' );

					$html.= '<li id="'.( isset( $keys[$letter] ) ? $keys[$letter] : $letter ).'">';
					$html.= '<h4 class="-heading">'.$letter.'</h4><dl'.( $args['description'] ? ' class="dl-horizontal"' : '' ).'>';

					$actives[] = $current = $letter;
				}

				if ( $args['item_cb'] ) {

					$html.= call_user_func_array( $args['item_cb'], [ $term, $args ] );

				} else {

					$title = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );
					$title = Text::reFormatName( $title ); // no need
					$link  = get_term_link( $term->term_id, $term->taxonomy );

					$html.= '<dt><span class="-title">'.HTML::link( $title, $link ).'</span>';

					if ( $args['count'] && $term->count )
						$html.= '<span class="-term-count">'.Helper::getCounted( $term->count, '&nbsp;(%s)' ).'</span>';

					$html.= '</dt>';

					if ( $args['description'] && $term->description )
						$html.= '<dd class="-description">'.wpautop( Helper::prepDescription( $term->description, TRUE, FALSE ), FALSE ).'</dd>';
					else
						$html.= '<dd class="-empty"></dd>';
				}
			}

			$html.= '</dl><div class="clearfix"></div></li>';

			foreach ( $alphabet as $key => $info )
				$list.= '<li>'.(
					in_array( $info['letter'], $actives )
					? HTML::scroll( $info['letter'], $info['key'], $info['name'] )
					: '<span>'.$info['letter'].'</span>'
				).'</li>';

			$fields = '<input class="-search" type="search" style="display:none;" />';

			$html = '<ul class="list-inline -letters">'.$list.'</ul>'.$fields.'<ul class="list-unstyled -definitions">'.$html.'</ul>';

			$html = ShortCode::wrap( $html, $this->constant( 'shortcode_terms' ), $args );
			$html = Text::minifyHTML( $html );

			set_transient( $key, $html, 12 * HOUR_IN_SECONDS );
		}

		return $html;
	}

	// sort array by value based on locale
	// @REF: https://stackoverflow.com/a/7096937
	// @REF: `WP_List_Util::sort_callback()`
	public static function sort( $array, $orderby = NULL, $order = 'ASC', $preserve_keys = FALSE, $locale = NULL )
	{
		if ( is_null( $orderby ) )
			return self::sortSimple( $array, $order, $preserve_keys, $locale );

		$alphabet = self::getAlphabet( $locale );
		$letters  = Arraay::column( $alphabet, 'letter' );
		$sort     = $preserve_keys ? 'uasort' : 'usort';

		if ( is_string( $orderby ) )
			$orderby = [ $orderby => $order ];

		foreach ( $orderby as $field => $direction )
			$orderby[$field] = 'DESC' === strtoupper( $direction ) ? 'DESC' : 'ASC';

		$sort( $array, function( $a, $b ) use( $orderby, $alphabet, $letters ){

			$a = (array) $a;
			$b = (array) $b;

			foreach ( $orderby as $field => $direction ) {

				if ( ! isset( $a[$field] ) || ! isset( $b[$field] ) )
					continue;

				if ( $a[$field] == $b[$field] )
					continue;

				$results = 'DESC' === $direction ? [ 1, -1 ] : [ -1, 1 ];

				if ( is_numeric( $a[$field] ) && is_numeric( $b[$field] ) )
					return ( $a[$field] < $b[$field] ) ? $results[0] : $results[1];

				$a_order = array_search( self::firstLetter( $a[$field], $alphabet ), $letters );
				$b_order = array_search( self::firstLetter( $b[$field], $alphabet ), $letters );

				// not in this locale
				if ( FALSE === $a_order || FALSE === $b_order )
					return 0 > strcmp( $a[$field], $b[$field] ) ? $results[0] : $results[1];

				if ( $a_order < $b_order )
					return $results[0];

				if ( $a_order > $b_order )
					return $results[1];
			}

			return 0;
		} );

		return $array;
	}

	public static function sortSimple( $array, $order = 'ASC', $preserve_keys = FALSE, $locale = NULL )
	{
		$alphabet = self::getAlphabet( $locale );
		$letters  = Arraay::column( $alphabet, 'letter' );
		$sort     = $preserve_keys ? 'uasort' : 'usort';

		$sort( $array, function( $a, $b ) use( $order, $alphabet, $letters ){

			$results = 'DESC' === strtoupper( $order ) ? [ 1, -1 ] : [ -1, 1 ];

			$a_order = array_search( self::firstLetter( $a, $alphabet ), $letters );
			$b_order = array_search( self::firstLetter( $b, $alphabet ), $letters );

			// not in this locale
			if ( FALSE === $a_order || FALSE === $b_order )
				return 0 > strcmp( $a, $b ) ? $results[0] : $results[1];

			if ( $a_order < $b_order )
				return $results[0];

			if ( $a_order > $b_order )
				return $results[1];

			return 0;
		} );

		return $array;
	}

	public static function firstLetter( $string, $alphabet )
	{
		$first = Text::subStr( $string, 0, 1 );

		foreach ( Arraay::column( $alphabet, 'search', 'letter' ) as $letter => $searchs )
			if ( FALSE !== array_search( $first, $searchs ) )
				return $letter;

		return $first;
	}

	public static function getAlphabet( $locale = NULL )
	{
		if ( is_null( $locale ) )
			$locale = get_locale();

		switch ( $locale ) {

			// @REF: [Persian alphabet](https://en.wikipedia.org/wiki/Persian_alphabet)
			// @REF: [Help:IPA for Persian](https://en.wikipedia.org/wiki/Help:IPA_for_Persian)
			case 'fa_IR': return [
				// [ 'letter' => 'ء', 'key' => 'hamza', 'ipa' => '[ʔ]', 'name' => 'ء (همزه)' ],
				[ 'letter' => 'آ', 'key' => 'alef', 'ipa' => '[ɒ]', 'name' => 'الف', 'search' => [ 'آ', 'ا', 'ء', 'أ', 'إ' ] ],
				[ 'letter' => 'ب', 'key' => 'be', 'ipa' => '[b]', 'name' => 'بِ' ],
				[ 'letter' => 'پ', 'key' => 'pe', 'ipa' => '[p]', 'name' => 'پِ' ],
				[ 'letter' => 'ت', 'key' => 'te', 'ipa' => '[t]', 'name' => 'تِ' ],
				[ 'letter' => 'ث', 'key' => 'se', 'ipa' => '[s]', 'name' => 'ثِ' ],
				[ 'letter' => 'ج', 'key' => 'jim', 'ipa' => '[d͡ʒ]', 'name' => 'جیم' ],
				[ 'letter' => 'چ', 'key' => 'che', 'ipa' => '[t͡ʃ]', 'name' => 'چِ' ],
				[ 'letter' => 'ح', 'key' => 'he_jimi', 'ipa' => '[h]', 'name' => 'حِ' ],
				[ 'letter' => 'خ', 'key' => 'khe', 'ipa' => '[x]', 'name' => 'خِ' ],
				[ 'letter' => 'د', 'key' => 'dal', 'ipa' => '[d]', 'name' => 'دال' ],
				[ 'letter' => 'ذ', 'key' => 'zal', 'ipa' => '[z]', 'name' => 'ذال' ],
				[ 'letter' => 'ر', 'key' => 're', 'ipa' => '[ɾ]', 'name' => 'ر' ],
				[ 'letter' => 'ز', 'key' => 'ze', 'ipa' => '[z]', 'name' => 'زِ' ],
				[ 'letter' => 'ژ', 'key' => 'je', 'ipa' => '[ʒ]', 'name' => 'ژِ' ],
				[ 'letter' => 'س', 'key' => 'sin', 'ipa' => '[s]', 'name' => 'سین' ],
				[ 'letter' => 'ش', 'key' => 'shin', 'ipa' => '[ʃ]', 'name' => 'شین' ],
				[ 'letter' => 'ص', 'key' => 'sad', 'ipa' => '[s]', 'name' => 'صاد' ],
				[ 'letter' => 'ض', 'key' => 'zad', 'ipa' => '[z]', 'name' => 'ضاد' ],
				[ 'letter' => 'ط', 'key' => 'ta', 'ipa' => '[t]', 'name' => 'طا' ],
				[ 'letter' => 'ظ', 'key' => 'za', 'ipa' => '[z]', 'name' => 'ظا' ],
				[ 'letter' => 'ع', 'key' => 'eyn', 'ipa' => '[ʔ]', 'name' => 'عین' ],
				[ 'letter' => 'غ', 'key' => 'qeyn', 'ipa' => '[ɣ] / [ɢ]', 'name' => 'غین' ],
				[ 'letter' => 'ف', 'key' => 'fe', 'ipa' => '[f]', 'name' => 'فِ' ],
				[ 'letter' => 'ق', 'key' => 'qaf', 'ipa' => '[ɢ] / [ɣ] / [q] (in some dialects)', 'name' => 'قاف' ],
				[ 'letter' => 'ک', 'key' => 'kaf', 'ipa' => '[k]', 'name' => 'کاف', 'search' => [ 'ك', 'ک' ] ],
				[ 'letter' => 'گ', 'key' => 'gaf', 'ipa' => '[ɡ]', 'name' => 'گاف' ],
				[ 'letter' => 'ل', 'key' => 'lam', 'ipa' => '[l]', 'name' => 'لام' ],
				[ 'letter' => 'م', 'key' => 'mim', 'ipa' => '[m]', 'name' => 'میم' ],
				[ 'letter' => 'ن', 'key' => 'nun', 'ipa' => '[n]', 'name' => 'نون' ],
				[ 'letter' => 'و', 'key' => 'vav', 'ipa' => '[v] / [uː] / [o] / [ow] / ([w] / [aw] / [oː] in Dari)', 'name' => 'واو' ],
				[ 'letter' => 'ه', 'key' => 'he_docesm', 'ipa' => '[h]', 'name' => 'هِ' ],
				[ 'letter' => 'ی', 'key' => 'ye', 'ipa' => '[j] / [i] / [ɒː] / ([aj] / [eː] in Dari)', 'name' => 'یِ', 'search' => [ 'ي', 'ی' ] ],
			];

			// @REF: [English alphabet - Wikipedia](https://en.wikipedia.org/wiki/English_alphabet)
			// @REF: [Help:IPA for English - Wikipedia](https://en.wikipedia.org/wiki/Help:IPA_for_English)
			default: return [
				[ 'letter' => 'A', 'key' => 'a', 'ipa' => '[ˈeɪ] / [æ]', 'name' => 'ā' ],
				[ 'letter' => 'B', 'key' => 'bee', 'ipa' => '[ˈbiː]', 'name' => 'bē' ],
				[ 'letter' => 'C', 'key' => 'cee', 'ipa' => '[ˈsiː]', 'name' => 'cē' ],
				[ 'letter' => 'D', 'key' => 'dee', 'ipa' => '[ˈdiː]', 'name' => 'dē' ],
				[ 'letter' => 'E', 'key' => 'e', 'ipa' => '[ˈiː]', 'name' => 'ē' ],
				[ 'letter' => 'F', 'key' => 'ef', 'ipa' => '[ˈɛf]', 'name' => 'ef' ],
				[ 'letter' => 'G', 'key' => 'gee', 'ipa' => '[ˈdʒiː]', 'name' => 'gē' ],
				[ 'letter' => 'H', 'key' => 'aitch', 'ipa' => '[ˈeɪtʃ] / [ˈheɪtʃ]', 'name' => 'hā' ],
				[ 'letter' => 'I', 'key' => 'i', 'ipa' => '[ˈaɪ]', 'name' => 'ī' ],
				[ 'letter' => 'J', 'key' => 'jay', 'ipa' => '[ˈdʒeɪ] / [ˈdʒaɪ]', 'name' => '' ],
				[ 'letter' => 'K', 'key' => 'kay', 'ipa' => '[ˈkeɪ]', 'name' => 'kā' ],
				[ 'letter' => 'L', 'key' => 'el', 'ipa' => '[ˈɛl]', 'name' => 'el' ],
				[ 'letter' => 'M', 'key' => 'em', 'ipa' => '[ˈɛm]', 'name' => 'em' ],
				[ 'letter' => 'N', 'key' => 'en', 'ipa' => '[ˈɛn]', 'name' => 'en' ],
				[ 'letter' => 'O', 'key' => 'o', 'ipa' => '[ˈoʊ]', 'name' => 'ō' ],
				[ 'letter' => 'P', 'key' => 'pee', 'ipa' => '[ˈpiː]', 'name' => 'pē' ],
				[ 'letter' => 'Q', 'key' => 'cue', 'ipa' => '[ˈkjuː]', 'name' => 'qū' ],
				[ 'letter' => 'R', 'key' => 'ar', 'ipa' => '[ˈɑːr] / [ˈɔːr]', 'name' => 'er' ],
				[ 'letter' => 'S', 'key' => 'ess', 'ipa' => '[ˈɛs]', 'name' => 'es' ],
				[ 'letter' => 'T', 'key' => 'tee', 'ipa' => '[ˈtiː]', 'name' => 'tē' ],
				[ 'letter' => 'U', 'key' => 'u', 'ipa' => '[ˈjuː]', 'name' => 'ū' ],
				[ 'letter' => 'V', 'key' => 'vee', 'ipa' => '[ˈviː]', 'name' => '' ],
				[ 'letter' => 'W', 'key' => 'double-u', 'ipa' => '[ˈdʌbəl.juː]', 'name' => '' ],
				[ 'letter' => 'X', 'key' => 'ex', 'ipa' => '[ˈɛks]', 'name' => 'ex' ],
				[ 'letter' => 'Y', 'key' => 'wy', 'ipa' => '[ˈwaɪ]', 'name' => 'hȳ' ],
				[ 'letter' => 'Z', 'key' => 'zed', 'ipa' => '[ˈzɛd]', 'name' => 'zēta' ],
			];
		}
	}
}
