<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
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
	// NOTE: merge numeric keys will rearrange them!
	// NOTE: `moveup` is FALSE by default
	public function get_strings( $subgroup, $group = 'titles', $fallback = [], $moveup = FALSE )
	{
		if ( $subgroup && isset( $this->strings[$group][$subgroup] ) )
			return is_array( $fallback ) && count( $fallback )
				? array_merge( $fallback, $this->strings[$group][$subgroup] )
				: $this->strings[$group][$subgroup];

		if ( $moveup && isset( $this->strings[$group] ) )
			return is_array( $fallback ) && count( $fallback )
				? array_merge( $fallback, $this->strings[$group] )
				: $this->strings[$group];

		return $fallback;
	}

	// NOTE: for post-type/taxonomy use:
	// - `Services\CustomPostType::getLabel( $posttype, 'noop' );`
	// - `Services\CustomTaxonomy::getLabel( $taxonomy, 'noop' );`
	public function get_noop( $constant )
	{
		if ( ! empty( $this->strings['noops'][$constant] ) )
			return $this->strings['noops'][$constant];

		if ( NULL !== ( $pre = gEditorial\Info::getNoop( $constant ) ) )
			return $pre;

		$noop = [
			// 'context' => ucwords( $module->name ).' Internal: Strings: Noop',   // no need
			'domain'  => 'geditorial',
		];

		if ( ! empty( $this->strings['labels'][$constant]['name'] ) )
			$noop['plural'] = $this->strings['labels'][$constant]['name'];
		else
			$noop['plural'] = Core\L10n::pluralize( $constant );

		if ( ! empty( $this->strings['labels'][$constant]['singular_name'] ) )
			$noop['singular'] = $this->strings['labels'][$constant]['singular_name'];
		else
			$noop['singular'] = $constant;

		return $noop;
	}

	public function nooped_count( $constant, $count )
	{
		return sprintf(
			gEditorial\Helper::noopedCount( $count, $this->get_noop( $constant ) ),
			Core\Number::format( $count )
		);
	}

	protected function strings_metabox_noitems_via_posttype( $posttype, $context = 'default', $default = NULL, $post = NULL, $prop = 'empty', $group = 'metabox' )
	{
		if ( is_null( $default ) ) {

			switch ( $context ) {

				case 'listbox':
				case 'default':
				default:
					/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
					$default = _x( 'No items connected to &ldquo;%1$s&rdquo; %2$s!', 'Module: MetaBox Empty: `listbox_empty`', 'geditorial-admin' );
			}
		}

		$template = $this->get_string( sprintf( '%s_%s', $context, $prop ), $posttype, 'metabox', $default );
		$singular = Services\CustomPostType::getLabel( $posttype, 'singular_name' );
		$title    = WordPress\Post::title( $post, $singular );

		return sprintf( $template, $title, $singular );
	}

	protected function strings_metabox_title_via_posttype( $posttype, $context = 'default', $default = NULL, $post = NULL, $prop = 'title', $group = 'metabox' )
	{
		if ( is_null( $default ) ) {

			switch ( $context ) {

				case 'supportedbox':

					/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
					$default = _x( 'For this &ldquo;%2$s&rdquo;', 'Internal: Strings: MetaBox via Posttype: `supportedbox_title`', 'geditorial-admin' );
					break;

				case 'printingbox':

					/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
					$default = _x( 'Prints for this &ldquo;%2$s&rdquo;', 'Internal: Strings: MetaBox via Posttype: `printingbox_title`', 'geditorial-admin' );
					break;

				case 'mainbox':

					/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
					$default = _x( 'The %2$s', 'Internal: Strings: MetaBox via Posttype: `mainbox_title`', 'geditorial-admin' );
					break;

				case 'overviewbox':
				case 'pairedbox':

					/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
					$default = _x( 'Connected &ldquo;%2$s&rdquo;', 'Internal: Strings: MetaBox via Posttype: `pairedbox_title`', 'geditorial-admin' );
					break;

				case 'megabox':
				case 'listbox':

					/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
					$default = _x( 'In &ldquo;%1$s&rdquo; %2$s', 'Internal: Strings: MetaBox via Posttype: `listbox_title`', 'geditorial-admin' );
					break;

				case 'quickedit':

					/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
					$default = _x( 'The %2$s', 'Internal: Strings: MetaBox via Posttype: `quickedit_title`', 'geditorial-admin' );
					break;

				case 'default':
				default:

					/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
					$default = _x( 'About &ldquo;%2$s&rdquo;', 'Internal: Strings: MetaBox via Posttype: `default_title`', 'geditorial-admin' );
			}
		}

		$template = $this->get_string( sprintf( '%s_%s', $context, $prop ), $posttype, $group, $default );
		$singular = Services\CustomPostType::getLabel( $posttype, 'singular_name' );
		$title    = WordPress\Post::title( $post );

		return sprintf( $template, $title ?: gEditorial\Plugin::untitled( FALSE ), $singular );
	}

	protected function strings_metabox_title_via_taxonomy( $taxonomy, $context = 'default', $default = NULL, $term = NULL, $prop = 'title', $group = 'metabox' )
	{
		if ( is_null( $default ) ) {

			switch ( $context ) {

				case 'supportedbox':

					/* translators: `%1$s`: current term title, `%2$s`: taxonomy singular name */
					$default = _x( 'For this &ldquo;%2$s&rdquo;', 'Internal: Strings: MetaBox via Taxonomy: `supportedbox_title`', 'geditorial-admin' );
					break;

				case 'default':
				default:

					/* translators: `%1$s`: current term title, `%2$s`: taxonomy singular name */
					$default = _x( 'About &ldquo;%2$s&rdquo;', 'Internal: Strings: MetaBox via Taxonomy: `default_title`', 'geditorial-admin' );
			}
		}

		$template = $this->get_string( sprintf( '%s_%s', $context, $prop ), $taxonomy, $group, $default );
		$singular = Services\CustomTaxonomy::getLabel( $taxonomy, 'singular_name' );
		$title    = WordPress\Term::title( $term, $singular );

		return sprintf( $template, $title, $singular );
	}

	// OLD: `subcontent_get_empty_notice()`
	protected function get_notice_for_empty( $context = 'display', $string_key = NULL, $check_thrift = TRUE )
	{
		if ( $check_thrift && $this->is_thrift_mode() )
			return '<div class="-placeholder-empty"></div>';

		return Core\HTML::tag( 'p', [
			'class' => [ 'description', '-description', '-empty' ],
		], $this->get_string( $string_key ?? 'empty', $context, 'notices', gEditorial\Plugin::noinfo( FALSE ) ) );
	}

	// OLD: `subcontent_get_noaccess_notice()`
	protected function get_notice_for_noaccess( $context = 'display', $string_key = NULL, $check_thrift = TRUE )
	{
		$default = _x( 'You do not have the necessary permission to manage the information.', 'Internal: Strings: No-Access Notice', 'geditorial' );

		return Core\HTML::tag( 'p', [
			'class' => [ 'description', '-description', '-noaccess' ],
		], $this->get_string( $string_key ?? 'noaccess', $context, 'notices', $default ) );
	}

	protected function _hook_post_updated_messages( $constant )
	{
		add_filter( 'post_updated_messages',
			function ( $messages )
				use ( $constant ) {

				$posttype  = $this->constant( $constant, $constant );
				$generated = Services\CustomPostType::generateMessages(
					Services\CustomPostType::getLabel( $posttype, 'noop' ),
					$posttype
				);

				return array_merge( $messages, [
					$posttype => $generated,
				] );
			} );
	}

	protected function _hook_bulk_post_updated_messages( $constant )
	{
		add_filter( 'bulk_post_updated_messages',
			function ( $messages, $counts )
				use ( $constant ) {

				$posttype  = $this->constant( $constant, $constant );
				$generated = Services\CustomPostType::generateBulkMessages(
					Services\CustomPostType::getLabel( $posttype, 'noop' ),
					$counts,
					$posttype
				);

				return array_merge( $messages, [
					$posttype => $generated,
				] );
			}, 10, 2 );
	}
}
