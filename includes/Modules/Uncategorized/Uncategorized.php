<?php namespace geminorum\gEditorial\Modules\Uncategorized;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class Uncategorized extends gEditorial\Module
{
	use Internals\CoreRowActions;

	public static function module()
	{
		return [
			'name'     => 'uncategorized',
			'title'    => _x( 'Uncategorized', 'Modules: Uncategorized', 'geditorial-admin' ),
			'desc'     => _x( 'Term Leftover Management', 'Modules: Uncategorized', 'geditorial-admin' ),
			'icon'     => 'hammer',
			'i18n'     => 'adminonly',
			'access'   => 'stable',
			'keywords' => [
				'termtools',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
			'_general' => [
				'admin_bulkactions',
			],
			'_roles' => [
				'reports_roles' => [ _x( 'Roles that can see Uncategorized Reports.', 'Setting Description', 'geditorial-uncategorized' ), $roles ],
			],
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

	public function current_screen( $screen )
	{
		if ( 'edit' == $screen->base
			// TODO: add separate list of posttypes on settings for this
			&& $this->posttype_supported( $screen->post_type ) ) {

			add_filter( "views_{$screen->id}", function ( $views ) use ( $screen ) {
				return array_merge( $views, $this->_get_posttype_view( $screen->post_type ) );
			}, 9 );

			$this->rowactions__hook_admin_bulkactions( $screen );

		} else if ( 'dashboard' == $screen->base
			// NOTE: only for `post` posttype
			&& current_user_can( 'edit_others_posts' ) ) {

			$this->filter( 'dashboard_pointers', 1, 10, FALSE, 'gnetwork' );
		}
	}

	// override
	public function cuc( $context = 'settings', $fallback = '' )
	{
		return 'reports' == $context
			? $this->role_can( 'reports', NULL, $fallback )
			: parent::cuc( $context, $fallback );
	}

	public function rowactions_bulk_actions( $actions )
	{
		$prefix = $this->classs();

		return array_merge( $actions, [
			$prefix.'_clean_uncategorized' => _x( 'Clean Uncategorized', 'Action', 'geditorial-uncategorized' ),
			$prefix.'_clean_unregistered'  => _x( 'Clean Unregistered', 'Action', 'geditorial-uncategorized' ),
			$prefix.'_clean_unattached'    => _x( 'Clean Unattached', 'Action', 'geditorial-uncategorized' ),
		] );
	}

	public function rowactions_handle_bulk_actions( $redirect_to, $doaction, $post_ids )
	{
		$count  = 0;
		$prefix = $this->classs();

		switch ( $doaction ) {

			case $prefix.'_clean_uncategorized':

				$taxonomies = $this->taxonomies();
				$callback   = [ $this, '_do_clean_uncategorized' ];
				break;

			case $prefix.'_clean_unregistered':

				$taxonomies = NULL;
				$callback   = [ $this, '_do_clean_unregistered' ];
				break;

			case $prefix.'_clean_unattached':

				$taxonomies = WordPress\Taxonomy::get( -1 );
				$callback   = [ $this, '_do_clean_unattached' ];
				break;

			default:
				return $redirect_to;
		}

		foreach ( $post_ids as $post_id ) {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				continue;

			if ( call_user_func_array( $callback, [ $post_id, $taxonomies ] ) )
				$count++;
		}

		return add_query_arg( $this->hook( 'cleaned' ), $count, $redirect_to );
	}

	public function rowactions_admin_notices()
	{
		$hook = $this->hook( 'cleaned' );

		if ( ! $count = self::req( $hook ) )
			return;

		$_SERVER['REQUEST_URI'] = remove_query_arg( $hook, $_SERVER['REQUEST_URI'] );

		/* translators: %s: count */
		echo Core\HTML::success( sprintf( _x( '%s items(s) cleaned!', 'Message', 'geditorial-uncategorized' ), Core\Number::format( $count ) ) );
	}

	// NOTE: already cap checked!
	// TODO: posinter for all supported posttypes
	public function dashboard_pointers( $items )
	{
		if ( ! $count = $this->_get_post_count() )
			return $items;

		/* translators: %s: posts count */
		$noopd = _nx_noop( '%s Uncategorized Post', '%s Uncategorized Posts', 'Noop', 'geditorial-uncategorized' );
		$can   = $this->role_can( 'reports' );

		$items[] = Core\HTML::tag( $can ? 'a' : 'span', [
			'href'  => $can ? $this->get_module_url( 'reports' ) : FALSE,
			'title' => _x( 'You need to assign categories to some posts!', 'Title Attr', 'geditorial-uncategorized' ),
			'class' => '-uncategorized-count',
		], sprintf( Helper::noopedCount( $count, $noopd ), Core\Number::format( $count ) ) );

		return $items;
	}

	public function reports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'reports' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'reports', $sub );

				$count = 0;

				if ( Tablelist::isAction( 'clean_uncategorized', TRUE ) ) {

					$taxonomies = $this->taxonomies();

					foreach ( $_POST['_cb'] as $post_id ) {

						if ( $results = $this->_do_clean_uncategorized( $post_id, $taxonomies ) )
							$count+= $results;
					}

					if ( $count ) {

						// delete pointer's cache
						delete_transient( $this->_get_count_cache_key() );

						Core\WordPress::redirectReferer( [
							'message' => 'cleaned',
							'count'   => $count,
						] );
					}

				} else if ( Tablelist::isAction( 'clean_unregistered', TRUE ) ) {

					foreach ( $_POST['_cb'] as $post_id ) {

						if ( $this->_do_clean_unregistered( $post_id ) )
							$count++;
					}

					if ( $count ) {

						// delete pointer's cache
						delete_transient( $this->_get_count_cache_key() );

						Core\WordPress::redirectReferer( [
							'message' => 'cleaned',
							'count'   => $count,
						] );
					}

				} else if ( Tablelist::isAction( 'clean_unattached', TRUE ) ) {

					$taxonomies = WordPress\Taxonomy::get( -1 );

					foreach ( $_POST['_cb'] as $post_id ) {

						if ( $this->_do_clean_unattached( $post_id, $taxonomies ) )
							$count++;
					}

					if ( $count ) {

						// delete pointer's cache
						delete_transient( $this->_get_count_cache_key() );

						Core\WordPress::redirectReferer( [
							'message' => 'cleaned',
							'count'   => $count,
						] );
					}
				}

				Core\WordPress::redirectReferer( 'nochange' );
			}
		}
	}

	protected function render_reports_html( $uri, $sub )
	{
		// FIXME: add screen option for this!
		// $query = [ 'tax_query' => $this->_get_uncategorized_tax_query() ];
		$query = $extra = [];
		$list  = $this->list_posttypes();

		list( $posts, $pagination ) = Tablelist::getPosts( $query, $extra, array_keys( $list ), $this->get_sub_limit_option( $sub ) );

		// TODO: add screen help tabs explainig the actions
		$pagination['actions']['clean_uncategorized'] = _x( 'Clean Uncategorized', 'Action', 'geditorial-uncategorized' );
		$pagination['actions']['clean_unregistered']  = _x( 'Clean Unregistered', 'Action', 'geditorial-uncategorized' );
		$pagination['actions']['clean_unattached']    = _x( 'Clean Unattached', 'Action', 'geditorial-uncategorized' );

		$pagination['before'][] = Tablelist::filterPostTypes();
		$pagination['before'][] = Tablelist::filterAuthors();
		$pagination['before'][] = Tablelist::filterSearch();

		Core\HTML::tableList( [
			'_cb'   => 'ID',
			'ID'    => Tablelist::columnPostID(),
			'date'  => Tablelist::columnPostDate(),
			'type'  => Tablelist::columnPostType(),
			'title' => Tablelist::columnPostTitle(),
			'terms' => Tablelist::columnPostTerms(),
			'raw'   => [
				'title'    => _x( 'Raw', 'Table Column', 'geditorial-uncategorized' ),
				'class'    => '-has-list',
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

					$query = new \WP_Term_Query( [
						'object_ids' => $row->ID,
						'get'        => 'all',
					] );

					if ( empty( $query->terms ) )
						return Helper::htmlEmpty();

					$list = [];

					foreach ( $query->terms as $term )
						$list[$term->taxonomy][] = $term->name;

					foreach ( $list as $taxonomy => $terms )
						$list[$taxonomy] = sprintf( '<code>%s</code>: %s', $taxonomy, WordPress\Strings::getJoined( $terms ) );

					return Core\HTML::renderList( $list );
				},
			],

		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', _x( 'Overview of Posts in Uncategorized Terms', 'Header', 'geditorial-uncategorized' ) ),
			'empty'      => Helper::getPostTypeLabel( 'post', 'not_found' ),
			'pagination' => $pagination,
		] );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( Tablelist::isAction( 'orphaned_terms' ) ) {

					$post = $this->get_current_form( [
						'dead_tax' => FALSE,
						'live_tax' => FALSE,
					], 'tools' );

					if ( $post['dead_tax'] && $post['live_tax'] ) {

						global $wpdb;

						$result = $wpdb->query( $wpdb->prepare( "
							UPDATE {$wpdb->term_taxonomy} SET taxonomy = %s WHERE taxonomy = %s
						", trim( $post['live_tax'] ), trim( $post['dead_tax'] ) ) );

						if ( FALSE !== $result )
							Core\WordPress::redirectReferer( [
								'message' => 'changed',
								'count'   => $result,
							] );
					}
				}

				Core\WordPress::redirectReferer( 'nochange' );
			}
		}
	}

	// TODO: option to delete orphaned terms
	// TODO: convert to `.card` UI: @SEE Audit module
	protected function render_tools_html( $uri, $sub )
	{
		$available  = FALSE;
		$db_taxes   = WordPress\Database::getTaxonomies( TRUE );
		$live_taxes = WordPress\Taxonomy::get( 6 );
		$dead_taxes = array_diff_key( $db_taxes, $live_taxes );

		Core\HTML::h3( _x( 'Uncategorized Tools', 'Header', 'geditorial-uncategorized' ) );

		echo '<table class="form-table">';

		if ( count( $dead_taxes ) ) {

			echo '<tr><th scope="row">'._x( 'Orphaned Terms', 'Tools', 'geditorial-uncategorized' ).'</th><td>';

				$this->do_settings_field( [
					'type'         => 'select',
					'field'        => 'dead_tax',
					'values'       => $dead_taxes,
					'default'      => ( isset( $post['dead_tax'] ) ? $post['dead_tax'] : 'post_tag' ),
					'option_group' => 'tools',
				] );

				$this->do_settings_field( [
					'type'         => 'select',
					'field'        => 'live_tax',
					'values'       => $live_taxes,
					'default'      => ( isset( $post['live_tax'] ) ? $post['live_tax'] : 'post_tag' ),
					'option_group' => 'tools',
				] );

				echo '&nbsp;&nbsp;';

				Settings::submitButton( 'orphaned_terms', _x( 'Convert', 'Button', 'geditorial-uncategorized' ) );

				Core\HTML::desc( _x( 'Converts orphaned terms into currently registered taxonomies.', 'Message', 'geditorial-uncategorized' ) );

			echo '</td></tr>';

			$available = TRUE;
		}

		if ( ! $available )
			Info::renderNoToolsAvailable();

		echo '</table>';
	}

	private function _do_clean_unattached( $post, $taxonomies = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( is_null( $taxonomies ) )
			$taxonomies = WordPress\Taxonomy::get( -1 ); // better to be all!

		$diff = array_diff( $taxonomies, get_object_taxonomies( $post ) );

		if ( empty( $diff ) )
			return FALSE;

		foreach ( $diff as $taxonomy )
			wp_set_object_terms( $post->ID, [], $taxonomy );

		return TRUE;
	}

	private function _do_clean_unregistered( $post )
	{
		global $wpdb;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$taxonomies   = get_object_taxonomies( $post );
		$currents     = wp_get_object_terms( $post->ID, $taxonomies, [ 'fields' => 'ids' ] );
		$unregistered = get_terms( [ 'object_ids' => $post->ID, 'orderby' => 'none', 'exclude' => $currents ] );

		if ( empty( $unregistered ) )
			return FALSE;

		$tt_ids   = Core\Arraay::pluck( $unregistered, 'term_taxonomy_id' );
		$prepared = "'" . implode( "', '", $tt_ids ) . "'";

		$query = $wpdb->prepare( "
			DELETE FROM {$wpdb->term_relationships}
			WHERE object_id = %d
			AND term_taxonomy_id IN ({$prepared})
		", $post->ID );

		if ( ! $wpdb->query( $query ) )
			return FALSE;

		WordPress\Taxonomy::updateTermCount( Core\Arraay::pluck( $unregistered, 'term_id' ) );

		return TRUE;
	}

	private function _do_clean_uncategorized( $post, $taxonomies = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( is_null( $taxonomies ) )
			$taxonomies = $this->taxonomies();

		$count    = 0;
		$currents = get_object_taxonomies( $post );

		foreach ( $taxonomies as $taxonomy ) {

			if ( ! in_array( $taxonomy, $currents ) )
				continue;

			if ( ! $default = WordPress\Taxonomy::getDefaultTermID( $taxonomy ) )
				continue;

			$terms = wp_get_object_terms( $post->ID, $taxonomy, [ 'fields' => 'ids' ] );
			$diff  = Core\Arraay::prepNumeral( array_diff( $terms, [ $default ] ) );

			// keep default if empty
			if ( empty( $diff ) )
				continue;

			$results = wp_set_object_terms( $post->ID, $diff, $taxonomy );

			if ( ! self::isError( $results ) )
				$count++;
		}

		return $count;
	}

	private function _get_uncategorized_tax_query( $taxonomies = NULL )
	{
		if ( is_null( $taxonomies ) )
			$taxonomies = $this->taxonomies();

		$tax_query = [ 'relation' => 'OR' ];

		foreach ( (array) $taxonomies as $taxonomy )
			if ( $default = WordPress\Taxonomy::getDefaultTermID( $taxonomy ) )
				$tax_query[] = [
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => [ (int) $default ],
				];

		return $tax_query;
	}

	private function _get_count_cache_key( $taxonomies = NULL )
	{
		if ( is_null( $taxonomies ) )
			$taxonomies = $this->taxonomies();

		return $this->hash( 'uncategorizedcount', $taxonomies );
	}

	// NOTE: only for `post` posttype
	private function _get_post_count()
	{
		$taxonomies = $this->taxonomies();
		$cache_key  = $this->_get_count_cache_key( $taxonomies );

		if ( Core\WordPress::isFlush( 'edit_others_posts' ) )
			delete_transient( $cache_key );

		if ( FALSE === ( $count = get_transient( $cache_key ) ) ) {

			$args = [
				'tax_query'              => $this->_get_uncategorized_tax_query( $taxonomies ),
				'fields'                 => 'ids',
				'post_type'              => 'post', // 'any',
				'orderby'                => 'none',
				'posts_per_page'         => -1,
				'nopaging'               => TRUE,
				'ignore_sticky_posts'    => TRUE,
				'suppress_filters'       => TRUE,
				'no_found_rows'          => TRUE,
				'update_post_meta_cache' => FALSE,
				'update_post_term_cache' => FALSE,
				'lazy_load_term_meta'    => FALSE,
			];

			$query = new \WP_Query();
			$count = count( $query->query( $args ) );

			set_transient( $cache_key, $count, 12 * HOUR_IN_SECONDS );
		}

		return $count;
	}

	private function _get_posttype_view( $posttype )
	{
		if ( ! $taxonomy = WordPress\PostType::getPrimaryTaxonomy( $posttype ) )
			return [];

		if ( ! $default = WordPress\Taxonomy::getDefaultTermID( $taxonomy ) )
			return [];

		$object = WordPress\Taxonomy::object( $taxonomy );
		$term   = WordPress\Term::get( $default, $taxonomy );

		return [ $this->key => vsprintf( '<a href="%1$s"%2$s>%3$s <span class="count">(%4$s)</span></a>', [

			Core\WordPress::getPostTypeEditLink( $posttype, 0, [
				$object->query_var => $term->slug,
				'post_status'      => 'all',
			] ),

			$term->slug === self::req( $object->query_var, FALSE )
				? ' class="current" aria-current="page"' : '',

			empty( $object->labels->uncategorized )
				? _x( 'Uncategorized', 'Default Label', 'geditorial-uncategorized' )
				: Core\HTML::escape( $object->labels->uncategorized ),

			Core\Number::format( $term->count ),
		] ) ];
	}
}
