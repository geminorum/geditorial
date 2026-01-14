<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait PostTypeFieldsReports
{
	protected function posttypefields_reports_handle_tablelist( $sub = NULL )
	{
		if ( gEditorial\Tablelist::isAction( 'check_empty_fields', TRUE ) ) {

			$count = 0;

			foreach ( $_POST['_cb'] as $post_id )
				if ( $this->posttypefields__do_check_empty_fields( $post_id ) )
					++$count;

			WordPress\Redirect::doReferer( [
				'message' => 'purged',
				'count'   => $count,
			] );

		} else if ( gEditorial\Tablelist::isAction( 'convert_legacy_fields', TRUE ) ) {

			$count = 0;

			foreach ( $_POST['_cb'] as $post_id )
				if ( $this->posttypefields__do_convert_legacy_fields( $post_id ) )
					++$count;

			WordPress\Redirect::doReferer( [
				'message' => 'synced',
				'count'   => $count,
			] );

		} else if ( gEditorial\Tablelist::isAction( 'sanitize_fields', TRUE ) ) {

			$count = 0;

			foreach ( $_POST['_cb'] as $post_id )
				if ( $this->posttypefields__do_sanitize_fields( $post_id ) )
					++$count;

			WordPress\Redirect::doReferer( [
				'message' => 'cleaned',
				'count'   => $count,
			] );

		} else if ( gEditorial\Tablelist::isAction( 'empty_all_metadata', TRUE ) ) {

			$count = 0;

			foreach ( $_POST['_cb'] as $post_id )
				if ( $this->posttypefields__do_empty_all_metadata( $post_id ) )
					++$count;

			WordPress\Redirect::doReferer( [
				'message' => 'cleaned',
				'count'   => $count,
			] );
		}

		return TRUE;
	}

	protected function posttypefields_reports_render_tablelist( $uri = '', $sub = NULL, $actions = NULL, $title = NULL )
	{
		$context = 'reports';
		$query   = $extra = $fields = [];
		$list    = $this->list_posttypes();

		list( $posts, $pagination ) = gEditorial\Tablelist::getPosts(
			$query,
			$extra,
			array_keys( $list ),
			$this->get_sub_limit_option( $sub, 'reports' )
		);

		$pagination['before'][] = gEditorial\Tablelist::filterPostTypes( $list );
		$pagination['before'][] = gEditorial\Tablelist::filterAuthors( $list );
		$pagination['before'][] = gEditorial\Tablelist::filterSearch( $list );

		$pagination['actions'] = [
			'check_empty_fields'    => _x( 'Check Empty Fields', 'Internal: PostTypeFieldsReports: Table Action', 'geditorial-admin' ),
			'convert_legacy_fields' => _x( 'Convert Legacy Fields', 'Internal: PostTypeFieldsReports: Table Action', 'geditorial-admin' ),
			'sanitize_fields'       => _x( 'Sanitize Fields', 'Internal: PostTypeFieldsReports: Table Action', 'geditorial-admin' ),
			'empty_all_metadata'    => _x( 'Empty All Meta-data', 'Internal: PostTypeFieldsReports: Table Action', 'geditorial-admin' ),
		];

		foreach ( $list as $posttype => $label )
			$fields[$posttype] = $this->get_posttype_fields( $posttype );

		return Core\HTML::tableList( [
			'_cb'    => 'ID',
			'type'   => gEditorial\Tablelist::columnPostType(),
			'title'  => gEditorial\Tablelist::columnPostTitle(),
			'meta' => [
				'title'    => _x( 'Meta', 'Internal: PostTypeFieldsReports: Table Column', 'geditorial-admin' ),
				'class'    => '-meta-metafields -has-table',
				'callback' => function ( $value, $row, $column, $index, $key, $args ) {

					$data = [];

					foreach ( $args['extra']['fields'][$row->post_type] as $field )
						if ( FALSE !== ( $meta = $this->get_postmeta_field( $row->ID, $field['name'] ) ) )
							$data[$field['title']] = Core\HTML::sanitizeDisplay( $meta );

					return $data
						? Core\HTML::tableCode( $data )
						: gEditorial\Helper::htmlEmpty();
				},
			],

			'legacy' => [
				'title'    => _x( 'Legacy', 'Internal: PostTypeFieldsReports: Table Column', 'geditorial-admin' ),
				'class'    => '-meta-legacies -has-count',
				'callback' => function ( $value, $row, $column, $index, $key, $args ) {

					if ( ! $legacies = $this->get_postmeta_legacy( $row->ID ) )
						return gEditorial\Helper::htmlEmpty();

					// empty: `array( 0 => '' )`
					if ( ! array_filter( $legacies ) )
						return Core\HTML::code( Core\HTML::sanitizeDisplay( $legacies ) );

					return gEditorial\Helper::htmlCount( count( $legacies ) );
				},
			],

		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', _x( 'Overview of Meta Fields', 'Internal: PostTypeFieldsReports: Header', 'geditorial-admin' ) ),
			'empty'      => $this->get_string( 'empty', $context, 'notices', gEditorial\Plugin::noinfo( FALSE ) ),
			'pagination' => $pagination,
			'extra'      => [
				'na'      => gEditorial()->na(),
				'fields'  => $fields,
				'context' => $context,
			],
		] );
	}
}
