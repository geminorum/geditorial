<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait DashboardSummary
{

	// USAGE: `$this->add_dashboard_widget( 'dashboard-summary', NULL, 'refresh' );`
	public function render_widget_dashboard_summary( $object, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		echo $this->wrap_open( [ '-admin-widget', '-core-styles' ], TRUE, 'dashboard_right_now' );

		$scope = $this->get_setting( 'summary_scope', 'all' );
		$key   = $this->hash( 'widgetsummary', $scope, get_current_user_id() );

		if ( Core\WordPress::isFlush( 'read' ) )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			if ( ! method_exists( $this, 'get_dashboard_summary_content' ) )
				return $this->log( 'CRITICAL', sprintf( 'MISSING CALLBACK: %s', 'get_dashboard_summary_content()' ) );

			if ( $summary = $this->get_dashboard_summary_content( $scope, NULL, NULL, 'li' ) ) {

				$html = Core\Text::minifyHTML( $summary );
				set_transient( $key, $html, 12 * HOUR_IN_SECONDS );

			} else {

				Info::renderNoReportsAvailable();
			}
		}

		if ( $html )
			echo '<div class="main"><ul>'.$html.'</ul></div>';

		echo '</div>';
	}

	protected function add_dashboard_term_summary( $constant, $posttypes = NULL, $role_check = TRUE, $title = NULL, $context = 'reports' )
	{
		if ( $role_check && ! $this->corecaps_taxonomy_role_can( $constant, $context ) )
			return FALSE;

		if ( is_null( $title ) )
			$title = sprintf(
				/* translators: `%s`: taxonomy extended label */
				_x( '%s Summary', 'Internal: Dashboard Summary: Widget Title', 'geditorial-admin' ),
				$this->get_taxonomy_label( $constant, 'extended_label' ),
			);

		$this->add_dashboard_widget( 'term-summary', $title, 'refresh', [],
			function ( $object, $box ) use ( $constant, $posttypes ) {
				$this->do_dashboard_term_summary( $constant, $box, $posttypes );
			} );
	}

	protected function do_dashboard_term_summary( $constant, $box, $posttypes = NULL, $edit = NULL )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		// using core styles
		echo $this->wrap_open( [ '-admin-widget', '-core-styles' ], TRUE, 'dashboard_right_now' );

		$taxonomy = WordPress\Taxonomy::object( $this->constant( $constant ) );

		if ( ! WordPress\Taxonomy::hasTerms( $taxonomy->name ) ) {

			if ( is_null( $edit ) )
				$edit = WordPress\Taxonomy::edit( $taxonomy );

			if ( $edit )
				$empty = Core\HTML::tag( 'a', [
					'href'   => $edit,
					'title'  => $taxonomy->labels->add_new_item,
					'target' => '_blank',
				], $taxonomy->labels->no_terms );

			else
				$empty = gEditorial()->na();

			Core\HTML::desc( $empty, FALSE, '-empty' );
			echo '</div>';
			return;
		}

		$scope = $this->get_setting( 'summary_scope', 'all' );
		$key   = $this->hash( 'widgetsummary', $taxonomy, $scope, get_current_user_id() );

		if ( Core\WordPress::isFlush( 'read' ) )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			if ( $summary = $this->get_dashboard_term_summary( $constant, $posttypes, NULL, $scope ) ) {

				$html = Core\Text::minifyHTML( $summary );
				set_transient( $key, $html, 12 * HOUR_IN_SECONDS );

			} else {

				Info::renderNoReportsAvailable();
			}
		}

		if ( $html )
			echo '<div class="main"><ul>'.$html.'</ul></div>';

		echo '</div>';
	}

	// TODO: support nooped term title via term meta from Terms module
	protected function get_dashboard_term_summary( $constant, $posttypes = NULL, $terms = NULL, $scope = 'all', $user_id = NULL, $paired = NULL, $list = 'li' )
	{
		$html     = '';
		$check    = FALSE;
		$nooped   = WordPress\PostType::get( 3, [ 'show_ui' => TRUE ] );
		$exclude  = WordPress\Database::getExcludeStatuses();
		$taxonomy = $this->constant( $constant );

		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return FALSE;

		$query_var = WordPress\Taxonomy::queryVar( $object );

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		if ( is_null( $terms ) )
			$terms = WordPress\Taxonomy::getTerms( $taxonomy, FALSE, TRUE, 'slug', [
				'hide_empty' => TRUE,
				'exclude'    => $this->get_setting( 'summary_excludes', '' ),
				'parent'     => $this->get_setting( 'summary_parents', TRUE ) ? 0 : '',
			] );

		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		if ( 'roles' == $scope && $this->role_can( sprintf( 'taxonomy_%s_locking_terms', $taxonomy ), $user_id, FALSE, FALSE ) )
			$check = TRUE; // 'hidden' == $this->get_setting( sprintf( 'taxonomy_%s_restricted_visibility', $taxonomy ), 'disabled' );

		if ( $this->get_setting( 'summary_drafts', FALSE ) )
			$exclude = array_diff( $exclude, [ 'draft' ] );

		if ( count( $terms ) ) {

			$objects = [];
			$counts  = $paired
				? WordPress\Taxonomy::countPostsDoubleTerms( $paired, $taxonomy, $posttypes, $exclude )
				: WordPress\Database::countPostsByTaxonomy( $terms, $posttypes, ( 'current' == $scope ? $user_id : 0 ), $exclude );

			foreach ( $counts as $term_slug => $posts ) {

				// empty count
				if ( empty( $posts ) )
					continue;

				// term not available for display (usually child terms)
				if ( ! array_key_exists( $term_slug, $terms ) )
					continue;

				if ( $check && ( $roles = get_term_meta( $terms[$term_slug]->term_id, 'roles', TRUE ) ) ) {

					if ( ! WordPress\User::hasRole( Core\Arraay::prepString( 'administrator', $roles ), $user_id ) )
						continue;
				}

				// TODO: support term meta icon
				$style = '';

				// NOTE: we use custom color as background
				if ( $color = get_term_meta( $terms[$term_slug]->term_id, 'color', TRUE ) )
					$style.= sprintf(
						// @REF: https://css-tricks.com/css-attr-function-got-nothin-custom-properties/
						'--custom-link-color:%s;--custom-link-background:%s;',
						Core\Color::lightOrDark( $color ),
						$color
					);

				$query = [ $query_var => $term_slug ];
				$name  = WordPress\Term::title( $terms[$term_slug] );

				if ( 'current' === $scope )
					$query['author'] = $user_id;

				if ( $paired )
					$query[WordPress\Taxonomy::queryVar( $paired->taxonomy )] = $paired->slug;

				foreach ( $posts as $type => $count ) {

					if ( ! $count )
						continue;

					if ( count( $posttypes ) > 1 )
						$text = vsprintf( '<b>%3$s</b> %1$s: <b title="%4$s">%2$s</b>', [
							Helper::noopedCount( $count, $nooped[$type] ),
							WordPress\Strings::trimChars( $name, 35 ),
							Core\Number::format( $count ),
							$name,
						] );

					else
						$text = vsprintf( '<b>%2$s</b> %1$s', [
							$name,
							Core\Number::format( $count ),
						] );

					if ( empty( $objects[$type] ) )
						$objects[$type] = WordPress\PostType::object( $type );

					$classes = [
						'geditorial-glance-item',
						'-'.$this->key,
						'-term',
						'-taxonomy-'.$taxonomy,
						'-term-'.$term_slug.'-'.$type.'-count',
					];

					if ( $objects[$type] && current_user_can( $objects[$type]->cap->edit_posts ) )
						$text = Core\HTML::tag( 'a', [
							'href'  => WordPress\PostType::edit( $type, $query ),
							'class' => $classes,
							'style' => $style ?: FALSE,
						], $text );

					else
						$text = Core\HTML::wrap( $text, $classes, FALSE );

					$html.= Core\HTML::tag( $list, $text );
				}
			}
		}

		if ( $this->get_setting( 'count_not', FALSE ) ) {

			$none  = Services\CustomTaxonomy::getLabel( $object, 'show_option_no_items' );
			$query = [ $query_var => -1 ];

			if ( 'current' === $scope )
				$query['author'] = $user_id;

			if ( $paired ) {

				foreach ( $posttypes as $posttype )
					$not[$posttype] = WordPress\Taxonomy::countPostsWithoutTerms( $taxonomy, $posttype, $paired, $exclude );

				$query[WordPress\Taxonomy::queryVar( $paired->taxonomy )] = $paired->slug;

			} else {

				$not = WordPress\Database::countPostsByNotTaxonomy( $taxonomy, $posttypes, ( 'current' == $scope ? $user_id : 0 ), $exclude );
			}

			foreach ( $not as $type => $count ) {

				if ( ! $count )
					continue;

				if ( count( $posttypes ) > 1 )
					$text = vsprintf( '<b>%3$s</b> %1$s %2$s', [
						Helper::noopedCount( $count, $nooped[$type] ),
						$none,
						Core\Number::format( $count ),
					] );

				else
					$text = vsprintf( '<b>%2$s</b> %1$s', [
						$none,
						Core\Number::format( $count ),
					] );

				if ( empty( $objects[$type] ) )
					$objects[$type] = WordPress\PostType::object( $type );

				$classes = [
					'geditorial-glance-item',
					'-'.$this->key,
					'-not-in',
					'-taxonomy-'.$taxonomy,
					'-not-in-'.$type.'-count',
				];

				if ( $objects[$type] && current_user_can( $objects[$type]->cap->edit_posts ) )
					$text = Core\HTML::tag( 'a', [
						'href'  => WordPress\PostType::edit( $type, $query ),
						'class' => $classes,
					], $text );

				else
					$text = Core\HTML::wrap( $text, $classes, FALSE );

				$html.= Core\HTML::tag( $list, [ 'class' => 'warning' ], $text );
			}
		}

		return $html;
	}

	protected function hook_dashboardsummary_paired_post_summaries( $constant, $supported = NULL, $setting = NULL, $priority = NULL )
	{
		if ( $setting !== TRUE && ! $this->get_setting( $setting ?? 'dashboard_widgets', FALSE ) )
			return FALSE;

		if ( $supported && is_string( $supported ) )
			$supported = (array) $this->constant( $supported );

		else if ( is_null( $supported ) )
			$supported = $this->posttypes();

		if ( empty( $supported ) || ! $constant )
			return FALSE;

		add_filter( $this->hook_base( 'paired', 'post_summaries' ),
			function ( $summaries, $paired, $posttype, $taxonomy, $posttypes, $post, $context ) use ( $constant, $supported ) {

				if ( ! array_intersect( $posttypes, $supported ) )
					return $summaries;

				$html = $this->get_dashboard_term_summary(
					$constant,
					$posttypes,
					NULL,
					'paired',
					NULL,
					$paired,
					'li'
				);

				if ( ! $html )
					return $summaries;

				$target = $this->constant( $constant );

				$summaries[] = [
					'key'     => $this->classs( 'summary', $target ),
					'class'   => '-paired-summary',
					'title'   => $this->get_string( 'widget_title', 'paired', 'dashboard', Services\CustomTaxonomy::getLabel( $target, 'extended_label' ) ),
					'content' => Core\HTML::wrap( Core\HTML::tag( 'ul', $html ), 'list-columns -term-columns' ),
				];

				return $summaries;

			}, 7, $priority ?? 90 );
	}
}
