<?php namespace geminorum\gEditorial\Modules\PagedContent;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class PagedContent extends gEditorial\Module
{
	use Internals\Rewrites;

	public static function module()
	{
		return [
			'name'     => 'paged_content',
			'title'    => _x( 'Paged Content', 'Modules: Paged Content', 'geditorial-admin' ),
			'desc'     => _x( 'Pagination Enhancements', 'Modules: Paged Content', 'geditorial-admin' ),
			'icon'     => 'editor-insertmore',
			'i18n'     => 'adminonly',
			'access'   => 'beta',
			'keywords' => [
				'paginate',
				'nextpage',
				'content-paged',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_viewall'          => [
				[
					'field'       => 'endpoint_support',
					'title'       => _x( 'View-All Link', 'Setting Title', 'geditorial-paged-content' ),
					'description' => _x( 'Enables the endpoint and filters for a view-all option on paginated contents.', 'Setting Description', 'geditorial-paged-content' ),
				],
				[
					'field'       => 'viewall_label',
					'type'        => 'text',
					'title'       => _x( 'View-All Label', 'Setting Title', 'geditorial-paged-content' ),
					'description' => _x( 'Custom string used on the view-all link on the front.', 'Setting Description', 'geditorial-paged-content' ),
					'default'     => _x( 'View All', 'Setting Default', 'geditorial-paged-content' ), // NOTE: not using fallback setting to avoid i18n loading on front just for one single string!
					'placeholder' => _x( 'View All', 'Setting Default', 'geditorial-paged-content' ),
					'field_class' => [ 'medium-text' ],
				],
			],
			'_autopaging' => [
				[
					'field'   => 'split_type',
					'type'    => 'radio',
					'title'   => _x( 'Split Type', 'Setting Title', 'geditorial-paged-content' ),
					'default' => 'none',
					'values'  => [
						'none'  => _x( 'Single page with no Auto-split', 'Setting Option', 'geditorial-paged-content' ),
						'words' => _x( 'Approximate Words per Page', 'Setting Option', 'geditorial-paged-content' ),
						'pages' => _x( 'Keep Total Number of Pages', 'Setting Option', 'geditorial-paged-content' ),
					],
				],
				[
					'field'       => 'num_words',
					'type'        => 'number',
					'title'       => _x( 'Approximate Words', 'Setting Title', 'geditorial-paged-content' ),
					'description' => _x( 'Each page will contain approximately this many words, depending on paragraph lengths.', 'Setting Description', 'geditorial-paged-content' ),
				],
				[
					'field'       => 'num_pages',
					'type'        => 'number',
					'title'       => _x( 'Total Pages', 'Setting Title', 'geditorial-paged-content' ),
					'description' => _x( 'If chosen, each page will contain approximately this many words, depending on paragraph lengths.', 'Setting Description', 'geditorial-paged-content' ),
				],
				[
					'field'        => $this->get_setting_key_posttypes_for_target( 'autopaging' ),
					'type'         => 'checkboxes-values',
					'title'        => _x( 'Supported Post-types', 'Settings: Setting Title', 'geditorial-paged-content' ),
					'description'  => _x( 'Auto-paging will be available for the selected post-type.', 'Settings: Setting Description', 'geditorial-paged-content' ),
					'string_empty' => _x( 'There are no supported post-types available!', 'Settings: Setting Empty String', 'geditorial-admin' ),
					'values'       => $this->get_settings_posttypes_for_target( 'autopaging' ),
				],
				[
					'field'       => 'control_termid',
					'type'        => 'number',
					'title'       => _x( 'Control Term', 'Setting Title', 'geditorial-paged-content' ),
					'description' => _x( 'Enables the auto-paging if the term exists on the post. Leave blank to disable.', 'Setting Description', 'geditorial-paged-content' ),
					'after'       => gEditorial\Settings::fieldAfterText( WordPress\Term::title( $this->get_setting( 'control_termid', 0 ) ), 'code' ),
				],
			],
		];
	}

	protected function settings_section_titles( $suffix )
	{
		switch ( $suffix ) {

			case '_viewall': return [
				_x( 'View All Pages', 'Setting Section Title', 'geditorial-paged-content' ),
				[
					sprintf(
						/* translators: `%s`: `nextpage` placeholder */
						_x( 'Provides a &#8220;view all&#8221; (single page) option for supported multipage post-types, using WordPress %s Quicktag.', 'Setting Section Description', 'geditorial-paged-content' ),
						Core\HTML::code( '&lt;!--nextpage--&gt;' )
					),
					gEditorial\Settings::fieldSectionAdopted(
						'View All Posts Pages',
						'Erick Hitter',
						'https://github.com/ethitter/View-All-Posts-Pages'
					),
				],
				// 'https://wordpress.org/plugins/view-all-posts-pages/',
			];

			case '_autopaging': return [
				_x( 'Auto-paging Posts', 'Setting Section Title', 'geditorial-paged-content' ),
				[
					sprintf(
						/* translators: `%s`: `nextpage` placeholder */
						_x( 'Automatically page posts by injecting %s Quick-tag.', 'Setting Section Description', 'geditorial-paged-content' ),
						Core\HTML::code( '&lt;!--nextpage--&gt;' )
					),
					gEditorial\Settings::fieldSectionAdopted(
						'Automatically Paginate Posts',
						'Erick Hitter',
						'https://github.com/ethitter/Automatically-Paginate-Posts'
					),
				],
			];
		}

		return FALSE;
	}

	protected function get_global_constants()
	{
		return [
			'viewall_queryvar'  => 'viewall',
			'viewall_disabled'  => 'viewall_disabled',
			'viewall_linklabel' => 'viewallpageslink',
		];
	}

	public function init()
	{
		parent::init();

		$this->_init_viewall();
		$this->_init_autopaging();
	}

	private function _init_viewall()
	{
		if ( ! $this->get_setting( 'endpoint_support' ) )
			return;

		$this->rewrites__add_endpoint( 'viewall' );
		$this->action( 'redirect_canonical', 2, 10, 'viewall' );

		if ( is_admin() )
			return;

		$this->action( 'the_post', 1, 5, 'viewall' );
		$this->filter( 'link_pages_args', 1, 99, 'viewall', 'wp' );
	}

	private function _init_autopaging()
	{
		if ( 'none' === $this->get_setting( 'split_type', 'none' ) )
			return;

		if ( is_admin() )
			return;

		if ( empty( $this->get_setting_posttypes( 'autopaging' ) ) )
			return;

		$this->filter( 'the_posts', 2, 9, 'autopaging' );
	}

	// NOTE: Only applied if the post doesn't already contain the Quick-tag.
	public function the_posts_autopaging( $posts, $wp_query )
	{
		if ( ! $wp_query->is_main_query() || ! $wp_query->is_singular() )
			return $posts;

		if ( $this->is_viewall() )
			return $posts;

		$term  = WordPress\Term::get( absint( $this->get_setting( 'control_termid', 0 ) ) );
		$type  = $this->get_setting( 'split_type', 'words' );
		$pages = absint( $this->get_setting( 'num_pages' ) );
		$words = absint( $this->get_setting( 'num_words' ) );

		if ( $pages < 2 && empty( $words ) )
			return $posts;

		foreach ( $posts as &$post ) {

			if ( ! $this->in_setting_posttypes( $post->post_type, 'autopaging' ) )
				continue;

			if ( $term && ! has_term( $term, $term->taxonomy, $post ) )
				continue;

			// already paginated!
			if ( preg_match( '#<!--nextpage-->#i', $post->post_content ) )
				continue;

			// Starts with post content, but alias to protect the raw content.
			$content = $post->post_content;

			// Normalizes post content to simplify paragraph counting and automatic paging.
			// Accounts for content that hasn't been cleaned up by Tiny-MCE.
			// $content = preg_replace( '#<p>(.+?)</p>#i', "$1\r\n\r\n", $content );
			$xx_content = preg_replace( '#<br(\s*/)?>#i', "\r\n", $content );

			$content = preg_replace( '/(<!--)(.+?)(-->)/', '', $content );
			$content = preg_replace( '#<p>(.+?)</p>#i', "$1\n\n", $content );
			$content = preg_replace( '#<br(\s*/)?>#i', "\n", $content );
			$content = preg_replace( '/\n+/', "\n\n", $content );
			$content = trim( $content );


			// Counts the paragraphs
			// $paragraphs = preg_match_all( '#\r\n\r\n#', $content, $matches );
			$paragraphs = preg_match_all( '#\n\n#', $content, $matches );

			// Keep going, if we have something to count.
			if ( is_int( $paragraphs ) && 0 < $paragraphs ) {

				// Explodes content at double (or more) line breaks
				// $content = explode( "\r\n\r\n", $content );
				$content = explode( "\n\n", $content );

				switch ( $type ) {

					case 'words':

						$counter = 0;

						// Count words per paragraph and break after
						// the paragraph that exceeds the set threshold
						foreach ( $content as $index => $paragraph ) {

							$counter+= count( preg_split( '/\s+/', Core\Text::stripTags( $paragraph ) ) );

							if ( $counter >= $words ) {

								$content[$index].= '<!--nextpage-->';
								$counter = 0;

							} else {

								continue;
							}
						}

						unset( $index, $paragraph );

						break;

					default:
					case 'pages':

						// count number of paragraphs content was exploded to
						$count = count( $content );

						// determine when to insert Quicktag
						$every = round( $count / $pages );

						// if number of pages is greater than number of paragraphs,
						// put each paragraph on its own page
						if ( $pages > $count )
							$every = 1;

						// set initial counter position.
						$i = $count - 1 == $pages ? 2 : 1;

						// loop through content pieces and append Quicktag as is appropriate
						foreach ( $content as $key => $value ) {

							if ( $key + 1 == $count )
								break;

							if ( ( $key + 1 ) == ( $i * $every ) ) {
								$content[$key] = $content[$key].'<!--nextpage-->';
								++$i;
							}
						}

						unset( $key, $value );
				}

				// reunite content
				// $content = implode( "\r\n\r\n", $content );
				$content = implode( "\n\n", $content );

				// and, overwrite the original content
				$post->post_content = wpautop( $content, FALSE );
			}
		}

		return $posts;
	}

	/**
	 * Determine if full post view is being requested.
	 *
	 * @return bool
	 */
	public function is_viewall()
	{
		global $wp_query;

		if ( ! is_array( $wp_query->query ) )
			return FALSE;

		if ( is_404() )
			return FALSE;

		if ( ! array_key_exists( $this->rewrites__get_queryvar( 'viewall' ), $wp_query->query ) )
			return FALSE;

		return TRUE;
	}

	public function get_viewall_url( $post_id = FALSE )
	{
		global $wp_rewrite, $post;

		$link = FALSE;

		// Get link base specific to page type being viewed.
		if ( is_singular() || in_the_loop() ) {

			$post_id = intval( $post_id );

			if ( ! $post_id )
				$post_id = $post->ID;

			if ( ! $post_id )
				return FALSE;

			$link = get_permalink( $post_id );

		} elseif ( is_home() || is_front_page() ) {

			$link = home_url( '/' );

		} elseif ( is_category() ) {

			$link = get_category_link( get_query_var( 'cat' ) );

		} elseif ( is_tag() ) {

			$link = get_tag_link( get_query_var( 'tag_id' ) );

		} elseif ( is_tax() ) {

			$queried_object = get_queried_object();

			if ( is_object( $queried_object ) && property_exists( $queried_object, 'taxonomy' ) && property_exists( $queried_object, 'term_id' ) )
				$link = get_term_link( (int) $queried_object->term_id, $queried_object->taxonomy );
		}

		// If link base is set, build link.
		if ( FALSE !== $link ) {

			$query = $this->rewrites__get_queryvar( 'viewall' );

			if ( $wp_rewrite->using_permalinks() ) {

				$link = path_join( $link, $query );

				if ( $wp_rewrite->use_trailing_slashes )
					$link = trailingslashit( $link );

			} else {

				$link = add_query_arg( $query, 1, $link );
			}
		}

		return $link;
	}

	/**
	 * Prevent canonical redirect if full-post page is requested.
	 * @hook: `redirect_canonical`
	 *
	 * @param string $url
	 * @return string|false
	 */
	public function redirect_canonical_viewall( $redirect_url, $requested_url )
	{
		return $this->is_viewall() ? FALSE : $redirect_url;
	}

	// @hook: `the_post`
	public function the_post_viewall( $post )
	{
		global $pages, $more, $multipage;

		if ( ! $this->is_viewall() )
			return;

		$post->post_content = str_replace( "\n<!--nextpage-->\n", "\n\n", $post->post_content );
		$post->post_content = str_replace( "\n<!--nextpage-->", "\n", $post->post_content );
		$post->post_content = str_replace( "<!--nextpage-->\n", "\n", $post->post_content );
		$post->post_content = str_replace( '<!--nextpage-->', ' ', $post->post_content );

		$pages = [ $post->post_content ];

		$more      = 1;
		$multipage = 0;
	}

	// @hook: `wp_link_pages_args`
	public function link_pages_args_viewall( $args )
	{
		// Set global `$more` to `FALSE` so that `wp_link_pages()`
		// outputs links for all pages when viewing full post page.
		if ( $viewall = $this->is_viewall() )
			$GLOBALS['more'] = FALSE;

		if ( ! empty( $args[$this->constant( 'viewall_disabled' )] ) )
			return $args;

		if ( ! empty( $args[$this->constant( 'viewall_linklabel' )] ) )
			$label = $args[$this->constant( 'viewall_linklabel' )];

		else
			$label = $this->get_setting_fallback( 'viewall_label',
				_x( 'View All', 'Setting Default', 'geditorial-paged-content' ) );

		// Process link text, respecting `pagelink` parameter.
		$text = str_replace( '%', $label, $args['pagelink'] );

		$link = $args['link_before'].Core\HTML::tag( $viewall ? 'span' : 'a', [
			'href'  => $viewall ? FALSE : $this->get_viewall_url(),
			'class' => $this->classs( 'viewall' ),
		], $text ).$args['link_after'];

		$args['after'] = $link.$args['after'];

		return $args;
	}
}
