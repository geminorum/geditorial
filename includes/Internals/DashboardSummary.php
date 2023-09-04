<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\WordPress;

trait DashboardSummary
{

	// USAGE: `$this->add_dashboard_widget( 'dashboard-summary', NULL, 'refresh' );`
	public function render_widget_dashboard_summary( $object, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		echo $this->wrap_open( [ '-admin-widget', '-core-styles' ], TRUE, 'dashboard_right_now' );

		$scope  = $this->get_setting( 'summary_scope', 'all' );
		$suffix = 'all' == $scope ? 'all' : get_current_user_id();
		$key    = $this->hash( 'widgetsummary', $scope, $suffix );

		if ( Core\WordPress::isFlush( 'read' ) )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			if ( $this->check_hidden_metabox( $box, FALSE, '</div>' ) )
				return;

			if ( ! method_exists( $this, 'get_dashboard_summary_content' ) )
				return $this->log( 'NOTICE', sprintf( 'MISSING CALLBACK: %s', 'get_dashboard_summary_content()' ) );

			if ( $summary = $this->get_dashboard_summary_content( $scope, NULL, 'li' ) ) {

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

	protected function do_dashboard_term_summary( $constant, $box, $posttypes = NULL, $edit = NULL )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		// using core styles
		echo $this->wrap_open( [ '-admin-widget', '-core-styles' ], TRUE, 'dashboard_right_now' );

		$taxonomy = WordPress\Taxonomy::object( $this->constant( $constant ) );

		if ( ! WordPress\Taxonomy::hasTerms( $taxonomy->name ) ) {

			if ( is_null( $edit ) )
				$edit = Core\WordPress::getEditTaxLink( $taxonomy->name );

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

		$scope  = $this->get_setting( 'summary_scope', 'all' );
		$suffix = 'all' == $scope ? 'all' : get_current_user_id();
		$key    = $this->hash( 'widgetsummary', $scope, $suffix );

		if ( Core\WordPress::isFlush( 'read' ) )
			delete_transient( $key );

		if ( FALSE === ( $html = get_transient( $key ) ) ) {

			if ( $this->check_hidden_metabox( $box, FALSE, '</div>' ) )
				return;

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
	protected function get_dashboard_term_summary( $constant, $posttypes = NULL, $terms = NULL, $scope = 'all', $user_id = NULL, $list = 'li' )
	{
		$html     = '';
		$check    = FALSE;
		$nooped   = WordPress\PostType::get( 3 );
		$exclude  = WordPress\Database::getExcludeStatuses();
		$taxonomy = $this->constant( $constant );

		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return FALSE;

		$query_var = empty( $object->query_var ) ? $object->name : $object->query_var;

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		if ( is_null( $terms ) )
			$terms = WordPress\Taxonomy::getTerms( $taxonomy, FALSE, TRUE, 'slug', [
				'hide_empty' => TRUE,
				'exclude'    => $this->get_setting( 'summary_excludes', '' ),
			] );

		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		if ( 'roles' == $scope && $this->role_can( sprintf( 'taxonomy_%s_locking_terms', $taxonomy ), $user_id, FALSE, FALSE ) )
			$check = TRUE; // 'hidden' == $this->get_setting( sprintf( 'taxonomy_%s_restricted_visibility', $taxonomy ), 'disabled' );

		if ( $this->get_setting( 'summary_drafts', FALSE ) )
			$exclude = array_diff( $exclude, [ 'draft' ] );

		if ( count( $terms ) ) {

			$counts  = WordPress\Database::countPostsByTaxonomy( $terms, $posttypes, ( 'current' == $scope ? $user_id : 0 ), $exclude );
			$objects = [];

			foreach ( $counts as $term => $posts ) {

				if ( $check && ( $roles = get_term_meta( $terms[$term]->term_id, 'roles', TRUE ) ) ) {

					if ( ! WordPress\User::hasRole( Core\Arraay::prepString( 'administrator', $roles ), $user_id ) )
						continue;
				}

				$name = sanitize_term_field( 'name', $terms[$term]->name, $terms[$term]->term_id, $terms[$term]->taxonomy, 'display' );

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
						'-term-'.$term.'-'.$type.'-count',
					];

					if ( $objects[$type] && current_user_can( $objects[$type]->cap->edit_posts ) )
						$text = Core\HTML::tag( 'a', [
							'href'  => Core\WordPress::getPostTypeEditLink( $type, ( 'current' == $scope ? $user_id : 0 ), [ $query_var => $term ] ),
							'class' => $classes,
						], $text );

					else
						$text = Core\HTML::wrap( $text, $classes, FALSE );

					$html.= Core\HTML::tag( $list, $text );
				}
			}
		}

		if ( $this->get_setting( 'count_not', FALSE ) ) {

			$none = Helper::getTaxonomyLabel( $object, 'show_option_no_items' );
			$not  = WordPress\Database::countPostsByNotTaxonomy( $taxonomy, $posttypes, ( 'current' == $scope ? $user_id : 0 ), $exclude );

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
						'href'  => Core\WordPress::getPostTypeEditLink( $type, ( 'current' == $scope ? $user_id : 0 ), [ $query_var => '-1' ] ),
						'class' => $classes,
					], $text );

				else
					$text = Core\HTML::wrap( $text, $classes, FALSE );

				$html.= Core\HTML::tag( $list, [ 'class' => 'warning' ], $text );
			}
		}

		return $html;
	}
}
