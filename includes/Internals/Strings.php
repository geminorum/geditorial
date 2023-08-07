<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\WordPress;

trait Strings
{

	public function get_string( $string, $subgroup = 'post', $group = 'titles', $fallback = FALSE, $moveup = TRUE )
	{
		if ( $subgroup && isset( $this->strings[$group][$subgroup][$string] ) )
			return $this->strings[$group][$subgroup][$string];

		if ( isset( $this->strings[$group]['post'][$string] ) )
			return $this->strings[$group]['post'][$string];

		if ( $moveup && isset( $this->strings[$group][$string] ) )
			return $this->strings[$group][$string];

		if ( FALSE === $fallback )
			return $string;

		return $fallback;
	}

	// NOTE: fallback will merge if is an array
	// NOTE: moveup is FALSE by default
	public function get_strings( $subgroup, $group = 'titles', $fallback = [], $moveup = FALSE )
	{
		if ( $subgroup && isset( $this->strings[$group][$subgroup] ) )
			return is_array( $fallback )
				? array_merge( $fallback, $this->strings[$group][$subgroup] )
				: $this->strings[$group][$subgroup];

		if ( $moveup && isset( $this->strings[$group] ) )
			return is_array( $fallback )
				? array_merge( $fallback, $this->strings[$group] )
				: $this->strings[$group];

		return $fallback;
	}

	public function get_noop( $constant )
	{
		if ( ! empty( $this->strings['noops'][$constant] ) )
			return $this->strings['noops'][$constant];

		if ( in_array( $constant, [ 'item', 'paired_item' ], TRUE ) )
			/* translators: %s: items count */
			return _nx_noop( '%s Item', '%s Items', 'Internal: Strings: Noop', 'geditorial' );

		if ( in_array( $constant, [ 'member', 'family_member' ], TRUE ) )
			/* translators: %s: items count */
			return _nx_noop( '%s Member', '%s Members', 'Internal: Strings: Noop', 'geditorial' );

		/**
		 * Persons vs. People vs. Peoples
		 * Most of the time, `people` is the correct word to choose as a plural
		 * for `person`. `Persons` is archaic, and it is safe to avoid using it,
		 * except in legal writing, which has its own traditional language.
		 * `Peoples` is only necessary when you refer to distinct ethnic groups.
		 * @source https://www.grammarly.com/blog/persons-people-peoples/
		 */
		if ( in_array( $constant, [ 'people', 'person' ], TRUE ) )
			/* translators: %s: people count */
			return _nx_noop( '%s Person', '%s People', 'Internal: Strings: Noop', 'geditorial' );

		if ( 'post' == $constant )
			/* translators: %s: posts count */
			return _nx_noop( '%s Post', '%s Posts', 'Internal: Strings: Noop', 'geditorial' );

		if ( 'connected' == $constant )
			/* translators: %s: items count */
			return _nx_noop( '%s Item Connected', '%s Items Connected', 'Internal: Strings: Noop', 'geditorial' );

		if ( 'word' == $constant )
			/* translators: %s: words count */
			return _nx_noop( '%s Word', '%s Words', 'Internal: Strings: Noop', 'geditorial' );

		$noop = [
			'plural'   => Core\L10n::pluralize( $constant ),
			'singular' => $constant,
			// 'context'  => ucwords( $module->name ).' Internal: Strings: Noop', // no need
			'domain'   => 'geditorial',
		];

		if ( ! empty( $this->strings['labels'][$constant]['name'] ) )
			$noop['plural'] = $this->strings['labels'][$constant]['name'];

		if ( ! empty( $this->strings['labels'][$constant]['singular_name'] ) )
			$noop['singular'] = $this->strings['labels'][$constant]['singular_name'];

		return $noop;
	}

	public function nooped_count( $constant, $count )
	{
		return sprintf( Helper::noopedCount( $count, $this->get_noop( $constant ) ), Core\Number::format( $count ) );
	}

	protected function strings_metabox_noitems_via_posttype( $posttype, $context = 'default', $default = NULL, $post = NULL, $prop = 'empty', $group = 'metabox' )
	{
		if ( is_null( $default ) ) {
			switch ( $context ) {

				case 'listbox':
				case 'default':
				default:
					/* translators: %1$s: current post title, %2$s: posttype singular name */
					$default = _x( 'No items connected to &ldquo;%1$s&rdquo; %2$s!', 'Module: Metabox Empty: `listbox_empty`', 'geditorial' );
			}
		}

		$template = $this->get_string( sprintf( '%s_%s', $context, $prop ), $posttype, 'metabox', $default );
		$singular = Helper::getPostTypeLabel( $posttype, 'singular_name' );
		$title    = WordPress\Post::title( $post, $singular );

		return sprintf( $template, $title, $singular );
	}

	protected function strings_metabox_title_via_posttype( $posttype, $context = 'default', $default = NULL, $post = NULL, $prop = 'title', $group = 'metabox' )
	{
		if ( is_null( $default ) ) {
			switch ( $context ) {

				case 'supportedbox':
					/* translators: %1$s: current post title, %2$s: posttype singular name */
					$default = _x( 'For this &ldquo;%2$s&rdquo;', 'Internal: Strings: Metabox via Posttype: `supportedbox_title`', 'geditorial' );
					break;

				case 'mainbox':
					/* translators: %1$s: current post title, %2$s: posttype singular name */
					$default = _x( 'The %2$s', 'Internal: Strings: Metabox via Posttype: `mainbox_title`', 'geditorial' );
					break;

				case 'pairedbox':
					/* translators: %1$s: current post title, %2$s: posttype singular name */
					$default = _x( 'Connected &ldquo;%2$s&rdquo;', 'Internal: Strings: Metabox via Posttype: `pairedbox_title`', 'geditorial' );
					break;

				case 'listbox':
					/* translators: %1$s: current post title, %2$s: posttype singular name */
					$default = _x( 'In &ldquo;%1$s&rdquo; %2$s', 'Internal: Strings: Metabox via Posttype: `listbox_title`', 'geditorial' );
					break;

				case 'default':
				default:
					/* translators: %1$s: current post title, %2$s: posttype singular name */
					$default = _x( 'About &ldquo;%2$s&rdquo;', 'Internal: Strings: Metabox via Posttype: `default_title`', 'geditorial' );
			}
		}

		$template = $this->get_string( sprintf( '%s_%s', $context, $prop ), $posttype, $group, $default );
		$singular = Helper::getPostTypeLabel( $posttype, 'singular_name' );
		$title    = WordPress\Post::title( $post, $singular );

		return sprintf( $template, $title, $singular );
	}

	protected function strings_metabox_title_via_taxonomy( $taxonomy, $context = 'default', $default = NULL, $term = NULL, $prop = 'title', $group = 'metabox' )
	{
		if ( is_null( $default ) ) {
			switch ( $context ) {

				case 'supportedbox':
					/* translators: %1$s: current term title, %2$s: taxonomy singular name */
					$default = _x( 'For this &ldquo;%2$s&rdquo;', 'Internal: Strings: Metabox via Taxonomy: `supportedbox_title`', 'geditorial' );
					break;

				case 'default':
				default:
					/* translators: %1$s: current term title, %2$s: taxonomy singular name */
					$default = _x( 'About &ldquo;%2$s&rdquo;', 'Internal: Strings: Metabox via Taxonomy: `default_title`', 'geditorial' );
			}
		}

		$template = $this->get_string( sprintf( '%s_%s', $context, $prop ), $taxonomy, $group, $default );
		$singular = Helper::getTaxonomyLabel( $taxonomy, 'singular_name' );
		$title    = WordPress\Term::title( $term, $singular );

		return sprintf( $template, $title, $singular );
	}
}
