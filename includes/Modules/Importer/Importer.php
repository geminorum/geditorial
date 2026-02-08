<?php namespace geminorum\gEditorial\Modules\Importer;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Importer extends gEditorial\Module
{
	use Internals\CoreToolBox;
	use Internals\PostMeta;
	use Internals\RawImports;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'importer',
			'title'    => _x( 'Importer', 'Modules: Importer', 'geditorial-admin' ),
			'desc'     => _x( 'Data Import Tools', 'Modules: Importer', 'geditorial-admin' ),
			'icon'     => 'cloud-upload',
			'access'   => 'stable',
			'frontend' => FALSE,
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_general'         => [
				[
					'field'       => 'skip_no_source_id',
					'title'       => _x( 'Skip No Source ID', 'Setting Title', 'geditorial-importer' ),
					'description' => _x( 'Tries to avoid creating posts with no source IDs.', 'Setting Description', 'geditorial-importer' ),
					'default'     => '1',
				],
				[
					'field'       => 'skip_same_title',
					'title'       => _x( 'Skip Same Title', 'Setting Title', 'geditorial-importer' ),
					'description' => _x( 'Tries to avoid creating posts with the same titles.', 'Setting Description', 'geditorial-importer' ),
				],
				[
					'field'       => 'skip_has_thumbnail',
					'title'       => _x( 'Skip Has Thumbnail', 'Setting Title', 'geditorial-importer' ),
					'description' => _x( 'Tries to avoid importing attachments for posts with thumbnail images.', 'Setting Description', 'geditorial-importer' ),
				],
				[
					'field'       => 'match_source_id',
					'title'       => _x( 'Match Source ID', 'Setting Title', 'geditorial-importer' ),
					'description' => _x( 'Tries to find the previously imported by provided source id.', 'Setting Description', 'geditorial-importer' ),
					'default'     => '1',
				],
				[
					'field'       => 'store_source_data',
					'title'       => _x( 'Store Source Data', 'Setting Title', 'geditorial-importer' ),
					'description' => _x( 'Stores raw source data and attchment reference as meta for each imported item.', 'Setting Description', 'geditorial-importer' ),
				],
				'add_audit_attribute' => [
					sprintf(
						/* translators: `%s`: audit attribute placeholder */
						_x( 'Appends %s audit attribute to each imported item.', 'Setting Description', 'geditorial-importer' ),
						Core\HTML::code( $this->constant( 'term_newpost_imported' ) )
					),
				],
			],
			'_defaults' => [
				'post_type',
				'post_status',
				'comment_status',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'metakey_source_map'    => '_importer_source_map',
			'metakey_source_offset' => '_importer_source_id_key',
			'metakey_source_data'   => '_import_source_data',
			'metakey_prepared_data' => '_import_prepared_data',
			'metakey_attach_id'     => '_import_attachment_id',
			'metakey_source_id'     => 'import_source_id',
			'term_newpost_imported' => 'imported',
		];
	}

	protected function get_global_strings()
	{
		return [
			'js' => [
				'media' => [
					'modal_title'  => _x( 'Choose a Datasheet', 'Javascript String', 'geditorial-importer' ),
					'modal_button' => _x( 'Select as Source', 'Javascript String', 'geditorial-importer' ),
				],
			],
		];
	}

	protected function tool_box_content()
	{
		Core\HTML::desc( _x( 'Helps with Importing contents from CSV files into any post-type, with meta support.', 'Tool Box', 'geditorial-importer' ) );
	}

	// public function init()
	// {
	// 	parent::init();

	// 	$this->action( 'imports_general_summary', 1, 10, FALSE, $this->base );
	// }

	public function current_screen( $screen )
	{
		if ( 'edit' == $screen->base
			&& $this->posttype_supported( $screen->post_type ) ) {

			Services\HeaderButtons::register( $this->key, [
				'text'      => Services\CustomPostType::getLabel( $screen->post_type, 'import_items', FALSE, _x( 'Import', 'Button', 'geditorial-importer' ) ),
				'link'      => $this->get_imports_page_url( NULL, [ 'posttype' => $screen->post_type ] ), // also `'attachment' => 12`
				'icon'      => $this->module->icon,
				'cap_check' => WordPress\PostType::cap( $screen->post_type, 'import_posts' ),
			] );
		}
	}

	// @REF: https://www.kristinfalkner.com/adding-url-link-custom-post-type-wordpress-admin-submenu/
	public function admin_menu()
	{
		global $submenu;

		if ( $this->is_thrift_mode() )
			return;

		foreach ( $this->posttypes() as $posttype )
			if ( WordPress\PostType::can( $posttype, 'import_posts' ) )
				$submenu[( 'post' == $posttype ? 'edit.php' : sprintf( 'edit.php?post_type=%s',$posttype ) )][] = [
					Services\CustomPostType::getLabel( $posttype, 'import_items', FALSE, _x( 'Import', 'Menu', 'geditorial-importer' ) ),
					'exist', // already checked
					$this->get_imports_page_url( NULL, [ 'posttype' => $posttype ] ),
				];
	}

	private function _guess_fields_map( $headers, $attachment_id = NULL )
	{
		return $this->filters( 'guessed_fields_map',
			Core\Arraay::keepByKeys(
				(array) get_option( $this->hook( 'fields_history' ), [] ),
				$headers
			),
			$headers,
			$attachment_id
		);
	}

	private function _record_fields_map( $map )
	{
		$option = $this->hook( 'fields_history' );

		$filtered = array_filter( array_map( function ( $value ) {
			return $value && 'none' !== $value ? $value : FALSE;
		}, $map ) );

		return update_option( $option, array_merge( (array) get_option( $option, [] ), $filtered ) );
	}

	private function _render_posttype_taxonomies( $posttype )
	{
		$template   = 'terms_all[%s]';
		$taxonomies = $this->get_importer_taxonomies( $posttype );

		echo '<table class="base-table-raw"><tbody>';

			$this->actions( 'posttype_taxonomies_before',
				$posttype,
				$taxonomies,
				$template,
				'<tr><td>',
				'</td></tr>',
				'</td><td>'
			);

		foreach ( $taxonomies as $taxonomy => $object ) {

			$dropdown = wp_dropdown_categories( [
				'taxonomy'          => $taxonomy,
				'name'              => sprintf( $template, $taxonomy ),
				'hierarchical'      => $object->hierarchical,
				'show_option_none'  => gEditorial\Settings::showOptionNone(),
				'option_none_value' => '0',
				'hide_if_empty'     => TRUE,
				'hide_empty'        => FALSE,
				'echo'              => FALSE,
			] );

			if ( empty( $dropdown ) )
				continue;

			echo '<tr><td>';
			echo Core\HTML::escape( $object->labels->menu_name );
			echo '</td><td>';
				echo $dropdown;
			echo '</td></tr>';
		}

		$this->actions( 'posttype_taxonomies_after',
			$posttype,
			$taxonomies,
			$template,
			'<tr><td>',
			'</td></tr>',
			'</td><td>'
		);

		echo '</tbody></table>';

		return TRUE;
	}

	private function _form_posts_map( $id, $posttype = 'post' )
	{
		if ( ! $file = get_attached_file( $id ) )
			return FALSE;

		$this->raise_resources();

		// https://github.com/kzykhys/PHPCsvParser
		$iterator = new \SplFileObject( Core\File::normalize( $file ) );
		$parser   = @new \KzykHys\CsvParser\CsvParser( $iterator, [ 'encoding' => 'UTF-8' ] );
		$items    = $parser->parse();
		$headers  = $items[0];

		unset( $items[0] );

		$taxonomies    = $this->get_importer_taxonomies( $posttype );
		$fields        = $this->get_importer_fields( $posttype, $taxonomies );
		$source_offset = $this->fetch_postmeta( $id, 'none', $this->constant( 'metakey_source_offset' ) );
		$map           = $this->fetch_postmeta( $id, [], $this->constant( 'metakey_source_map' ) );

		if ( empty( $map ) )
			$map = $this->_guess_fields_map( $headers, $id );

		if ( $dups = Core\Arraay::duplicates( $headers ) )
			echo Core\HTML::warning( sprintf(
				/* translators: `%s`: joined duplicate keys */
				_x( 'Found duplicate column headers: %s', 'Message', 'geditorial-importer' ),
				WordPress\Strings::getJoined( $dups )
			), FALSE, 'inline' );

		echo '<table class="base-table-raw"><tbody>';

		echo '<tr><td><strong>'._x( 'Source ID', 'Dropdown Label', 'geditorial-importer' );
		echo '</strong></td><td class="-sep">';

			gEditorial\Settings::fieldSeparate( 'from' );

		echo '</td><td>';

			echo Core\HTML::dropdown( $headers, [
				'selected'   => $source_offset,
				'name'       => 'source_offset',
				'class'      => '-dropdown-source-key',
				'none_title' => gEditorial\Settings::showOptionNone(),
				'none_value' => 'none', // `0` is offset!
			] );

		echo '</td><td>&nbsp;</td><td>&nbsp;</td><td>';

			Core\HTML::desc( _x( 'Used as Identifider of each item.', 'Description', 'geditorial-importer' ) );

		echo '</td></tr>';

		foreach ( $headers as $key => $title ) {

			echo '<tr><td class="-val"><code>'
				.Core\HTML::escape( $title )
			.'</code></td><td class="-sep">';

				gEditorial\Settings::fieldSeparate( 'into' );

			echo '</td><td>'
				.Core\HTML::dropdown( $fields, [
					'selected'   => array_key_exists( $title, $map ) ? $map[$title] : 'none',
					'name'       => 'field_map['.$key.']',
					'none_title' => gEditorial\Settings::showOptionNone(),
					'none_value' => 'none',
				] )
			.'</td><td><td class="-sep">';

				gEditorial\Settings::fieldSeparate( 'ex' );

			echo '</td><td class="-val"><code>'
				.Core\HTML::sanitizeDisplay( empty( $items[1][$key] ) ? '' : $items[1][$key] )
			.'</code></td><td class="-sep">';

				gEditorial\Settings::fieldSeparate( 'count' );

			echo '</td><td class="-count"><code>'
				.gEditorial\Helper::htmlCount( WordPress\Strings::filterEmpty( Core\Arraay::column( $items, $key ) ) )
			.'</code></td></tr>';
		}

		echo '</tbody></table>';
	}

	private function _form_posts_attached( $id = 0, $posttype = 'post', $user_id = NULL )
	{
		echo '<hr class="-silent" />';
		gEditorial\Settings::fieldSeparate( 'from' );

		Core\HTML::inputHidden( 'upload_id' );

		echo Core\HTML::tag( 'input', [
			'type'  => 'button',
			'value' => _x( 'Upload', 'Button', 'geditorial-importer' ),
			'data'  => [ 'target' => 'upload_id' ],
			'class' => [
				'button',
				'-button',
				$this->classs( 'uploadbutton' ),
			],
		] );

		gEditorial\Settings::fieldSeparate( 'or' );

		WordPress\Media::selectAttachment( $id, $this->_get_source_mimetypes(), 'attach_id', gEditorial\Plugin::na() );

		echo '<hr class="-silent" />';

		gEditorial\Settings::fieldSeparate( 'into' );

		echo Core\HTML::dropdown( $this->list_posttypes( NULL, NULL, 'edit_posts' ), [
			'selected' => $posttype,
			'name'     => 'posttype',
		] );

		gEditorial\Settings::fieldSeparate( 'as' );

		echo Core\HTML::dropdown( WordPress\User::get(), [
			'selected' => is_null( $user_id ) ? $this->_get_user_id() : $user_id,
			'name'     => 'user_id',
			'prop'     => 'display_name',
			'disabled' => ! $this->_can_change_user(),
		] );

		// TODO: checkbox to only import if import_id found, e.g: not creating new posts!
	}

	private function _form_images_table( $args )
	{
		$query = [
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'     => $args['metakey'],
					'compare' => 'EXISTS',
				]
			],
		];

		if ( $this->get_setting( 'skip_has_thumbnail' ) )
			$query['meta_query'][] = [
				'key'     => '_thumbnail_id',
				'compare' => 'NOT EXISTS',
			];

		list( $posts, ) = gEditorial\Tablelist::getPosts( $query, [], $args['posttype'], $this->get_sub_limit_option( NULL, 'imports' ) );

		return Core\HTML::tableList( [
			'_cb'   => 'ID',
			'import_image' => [
				'title'    => _x( 'Image', 'Table Column', 'geditorial-importer' ),
				'args'     => $args,
				'class'    => 'image-column',
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

					if ( ! $id = get_post_meta( $row->ID, $column['args']['metakey'], TRUE ) )
						return gEditorial\Helper::htmlEmpty();

					$src = sprintf( $column['args']['template'], $id );

					return Core\HTML::tag( 'a', [
						'href'  => $src,
						'title' => $id, // get_the_title( $row ),
						'class' => 'thickbox',
					], Core\HTML::img( $src ) );
				},
			],
			'ID'    => gEditorial\Tablelist::columnPostID(),
			'title' => gEditorial\Tablelist::columnPostTitle(),
			'type'  => gEditorial\Tablelist::columnPostType(),
			'thumb_image' => [
				'title'    => _x( 'Thumbnail', 'Table Column', 'geditorial-importer' ),
				'class'    => 'image-column',
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
					$html = WordPress\PostType::htmlFeaturedImage( $row->ID, [ 45, 72 ] );
					return $html ?: gEditorial\Helper::htmlEmpty();
				},
			],
		], $posts, [
			/* translators: `%s`: count placeholder */
			'title' => Core\HTML::tag( 'h3', WordPress\Strings::getCounted( count( $posts ), _x( '%s Records Found', 'Header', 'geditorial-importer' ) ) ),
			'empty' => Services\CustomPostType::getLabel( $args['posttype'], 'not_found' ),
		] );
	}

	private function _form_posts_table( $id, $map = [], $posttype = 'post', $terms_all = [], $source_offset = 'none' )
	{
		if ( ! $file = get_attached_file( $id ) )
			return FALSE;

		$this->raise_resources();

		// https://github.com/kzykhys/PHPCsvParser
		$iterator = new \SplFileObject( Core\File::normalize( $file ) );
		$parser   = @new \KzykHys\CsvParser\CsvParser( $iterator, [ 'encoding' => 'UTF-8' ] );

		$items   = $parser->parse();
		$headers = $items[0];

		unset( $iterator, $parser, $items[0] );

		$this->store_postmeta( $id, array_combine( $headers, $map ), $this->constant( 'metakey_source_map' ) );
		$this->store_postmeta( $id, ( 'none' === $source_offset ? FALSE : $source_offset ), $this->constant( 'metakey_source_offset' ) );
		$this->_record_fields_map( array_combine( $headers, $map ) );

		$this->_render_data_table_for_posts( $id, $items, $headers, $map, $posttype, $terms_all, $source_offset );
	}

	private function _render_data_table_for_posts( $id, $data, $headers, $map = [], $posttype = 'post', $term_all = [], $source_offset = 'none' )
	{
		$taxonomies = $this->get_importer_taxonomies( $posttype );
		$fields     = $this->get_importer_fields( $posttype, $taxonomies );

		$columns = [
			'_cb'           => '_index',
			'_check_column' => [
				'title'    => _x( '[Checks]', 'Table Column', 'geditorial-importer' ),
				'callback' => [ $this, 'form_posts_table_checks' ],
			],
		];

		foreach ( $map as $key => $field ) {

			if ( 'none' == $field )
				continue;

			if ( 'importer_custom_meta' == $field )
				$columns[$headers[$key]] = sprintf(
					/* translators: `%s`: custom meta-key */
					_x( 'Custom: %s', 'Post Field Column', 'geditorial-importer' ),
					Core\HTML::code( $headers[$key] )
				);

			else
				$columns[$headers[$key]] = $fields[$field];
		}

		Core\HTML::tableList( $columns, $data, [
			'title' => Core\HTML::tag( 'h3', sprintf(
				/* translators: `%1$s`: count placeholder, `%2$s`: attachment title */
				_x( '%1$s Records Found for &ldquo;%2$s&rdquo;', 'Header', 'geditorial-importer' ),
				Core\Number::format( count( $data ) ),
				get_the_title( $id )
			) ),
			'callback' => [ $this, 'form_posts_table_callback' ],
			'row_prep' => [ $this, 'form_posts_table_row_prep' ],
			'extra'    => [
				'na'            => gEditorial()->na(),
				'mapped'        => array_combine( $headers, $map ),
				'headers'       => $headers,
				'post_type'     => $posttype,
				'taxonomies'    => $taxonomies,
				'source_offset' => $source_offset,
				'source_key'    => $source_offset, // is the same for this CSV-Parser
			],
		] );
	}

	// CAUTION: used more than once!
	public function form_posts_table_checks( $value, $row, $column, $index, $key, $args )
	{
		$checks = [];

		if ( $row['___source_id'] )
			$checks[] = sprintf(
				/* translators: `%s`: source id */
				_x( 'SourceID: %s', 'Checks', 'geditorial-importer' ),
				Core\HTML::code( $row['___source_id'] )
			);

		else if ( FALSE === $row['___source_id'] )
			$checks[] = _x( 'Skipped: Filtered', 'Checks', 'geditorial-importer' );

		else if ( $this->get_setting( 'skip_no_source_id', TRUE ) && 'none' !== $args['extra']['source_offset'] )
			$checks[] = _x( 'Skipped: No SourceID', 'Checks', 'geditorial-importer' );

		if ( $row['___matched'] )
			$checks[] = sprintf(
				/* translators: `%s`: post title */
				_x( 'Matched: %s', 'Checks', 'geditorial-importer' ),
				gEditorial\Helper::getPostTitleRow( $row['___matched'], 'edit', FALSE, $row['___matched'] )
			);

		if ( ! empty( $args['extra']['mapped'] ) ) {

			if ( FALSE !== ( $title_key = array_search( 'importer_post_title', $args['extra']['mapped'] ) ) ) {
				$title = $this->filters( 'prepare',
					Core\Text::trim( $row[$title_key] ),
					$args['extra']['post_type'],
					'importer_post_title',
					$title_key,
					$row,
					$row['___source_id'],
					$args['extra']['taxonomies']
				);

				if ( ( ! $title = trim( $title ) ) && ( $posts = WordPress\Post::getByTitle( $title, $args['extra']['post_type'] ) ) ) {

					$html = '<div class="-danger">'._x( 'Similar:', 'Checks', 'geditorial-importer' ).' ';

					foreach ( $posts as $post_id )
						$html.= gEditorial\Helper::getPostTitleRow( $post_id, 'edit', FALSE, $post_id ).', ';

					$checks[] = trim( $html, ', ' ).'</div>';
				}
			}
		}

		return WordPress\Strings::getJoined( $checks, '', '', gEditorial\Helper::htmlEmpty(), '<br />' );
	}

	// NOTE: combines raw data with header keys and adds source_id and matched
	// CAUTION: used more than once!
	public function form_posts_table_row_prep( $row, $index, $args )
	{
		// empty rows have only one empty cell
		if ( count( $row ) === 1 && empty( $row[0] ) )
			return FALSE;

		$raw       = Core\Arraay::combine( $args['extra']['headers'], $row );
		$source_id = NULL;

		if ( 'none' !== $args['extra']['source_offset']
			&& \array_key_exists( $args['extra']['source_key'], $row ) )
				$source_id = $row[$args['extra']['source_key']];

		$raw['___source_id'] = $this->filters( 'source_id',
			$source_id,
			$args['extra']['post_type'],
			$raw
		);

		// skipped by filter
		if ( FALSE === $raw['___source_id'] )
			$raw['___matched'] = 0;

		// no source id provided in the row
		else if ( ! $raw['___source_id'] && $this->get_setting( 'skip_no_source_id', TRUE ) && 'none' !== $args['extra']['source_offset'] )
			$raw['___matched'] = 0;

		else if ( $matched = $this->_get_source_id_matched( $raw['___source_id'], $args['extra']['post_type'], $raw ) )
			$raw['___matched'] = intval( $matched );
		else
			$raw['___matched'] = 0;

		return $raw;
	}

	// NOTE: only applies on columns with no `callback`
	public function form_posts_table_callback( $value, $row, $column, $index, $key, $args )
	{
		$filtered = $this->filters( 'prepare',
			Core\Text::trim( $value ),
			$args['extra']['post_type'],
			$args['extra']['mapped'][$key],
			$key,
			$row,
			$row['___source_id'],
			$args['extra']['taxonomies']
		);

		// TODO: optional check for previously stored data

		if ( FALSE === $filtered )
			$filtered = $args['extra']['na'];

		else if ( WordPress\Strings::isEmpty( $filtered ) )
			$filtered = '';

		return Core\HTML::sanitizeDisplay( $filtered );
	}

	public function imports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'imports' ) ) {

			$this->filter( 'import_memory_limit' );

			add_filter( $this->hook( 'prepare' ), [ $this, 'importer_prepare' ], 9, 7 );
			add_action( $this->hook( 'saved' ), [ $this, 'importer_saved' ], 9, 2 );

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'imports', $sub );

				if ( gEditorial\Tablelist::isAction( [
					'images_import',
					'images_import_as_thumbnail',
				], TRUE ) ) {

					$count = 0;
					$args  = $this->_get_current_form_images();

					$this->raise_resources();

					foreach ( $_POST['_cb'] as $post_id ) {

						if ( ! $id = get_post_meta( $post_id, $args['metakey'], TRUE ) )
							continue;

						if ( ! $post = get_post( $post_id ) )
							continue;

						$extra = [ 'post_author' => $args['user_id'] ];

						// TODO: make this optional
						// FIXME: filter the title for here
						if ( ! empty( $post->post_title ) ) {
							$extra['post_title'] = $post->post_title;
							$extra['meta_input']['_wp_attachment_image_alt'] = $post->post_title;
						}

						$attachment = WordPress\Media::sideloadImageURL( sprintf( $args['template'], $id ), $post_id, $extra );

						if ( is_wp_error( $attachment ) ) {
							$this->log( 'NOTICE', $attachment->get_error_message() );
							continue;
						}

						if ( isset( $_POST['images_import_as_thumbnail'] ) )
							set_post_thumbnail( $post_id, $attachment );

						++$count;
					}

					WordPress\Redirect::doReferer( [
						'message' => 'imported',
						'count'   => $count,
					] );

				} else if ( gEditorial\Tablelist::isAction( [
					'terms_import_newonly',
					'terms_import_append',
					'terms_import_override',
				], TRUE ) ) {

					if ( FALSE === ( $count = $this->_handle_terms_import() ) )
						WordPress\Redirect::doReferer( 'wrong' );

					else
						WordPress\Redirect::doReferer( [
							'message' => 'imported',
							'count'   => $count,
						] );

				} else if ( gEditorial\Tablelist::isAction( [
					'posts_import_newonly',
					'posts_import_override',
				], TRUE ) ) {

					$count         = 0;
					$field_map     = self::req( 'field_map', [] );
					$terms_all     = self::req( 'terms_all', [] );
					$posttype      = self::req( 'posttype', $this->get_setting( 'post_type', 'post' ) );
					$attach_id     = self::req( 'attach_id', FALSE );
					$user_id       = self::req( 'user_id', $this->_get_user_id() );
					$source_offset = self::req( 'source_offset', 'none' );
					$override      = isset( $_POST['posts_import_override'] );

					if ( ! $file = get_attached_file( $attach_id ) )
						WordPress\Redirect::doReferer( 'wrong' );

					$post_status    = $this->get_setting( 'post_status', 'pending' );
					$comment_status = $this->get_setting( 'comment_status', 'closed' );
					$all_taxonomies = WordPress\Taxonomy::get( 4, [], $posttype ); // NOTE: all and must not be filtered
					$terms_all      = array_map( [ 'geminorum\gEditorial\Core\Arraay', 'prepNumeral' ], $terms_all );

					$this->raise_resources();

					$iterator = new \SplFileObject( Core\File::normalize( $file ) );
					$options  = [ 'encoding' => 'UTF-8', 'limit' => 1 ];
					$parser   =@new \KzykHys\CsvParser\CsvParser( $iterator, $options );
					$items    = $parser->parse();
					$headers  = array_pop( $items ); // used on maping cutom meta

					unset( $parser, $items );

					// NOTE: to avoid `Content, title, and excerpt are empty.` Error on `wp_insert_post()`
					add_filter( 'wp_insert_post_empty_content', '__return_false', 12 );

					$this->actions( 'posts_before', $posttype );

					foreach ( $_POST['_cb'] as $offset ) {

						$options['offset'] = $offset;

						$parser = @new \KzykHys\CsvParser\CsvParser( $iterator, $options );
						$items  = $parser->parse();
						$row    = array_pop( $items );

						unset( $parser, $items );

						$raw        = Core\Arraay::combine( $headers, $row );
						$data       = []; // [ 'tax_input' => [] ];
						$prepared   = [];
						$comments   = [];
						$taxonomies = [];
						$oldpost    = $post_id = FALSE;

						// @EXAMPLE: `$this->filter_module( 'importer', 'source_id', 3 );`
						$source_id = $this->filters( 'source_id',
							( 'none' !== $source_offset && array_key_exists( $source_offset, $row )
								? $row[$source_offset]
								: NULL
							),
							$posttype,
							$raw
						);

						// skipped by filter
						if ( FALSE === $source_id )
							continue;

						if ( ! $source_id && $this->get_setting( 'skip_no_source_id', TRUE ) && 'none' !== $source_offset )
							continue;

						if ( $matched = $this->_get_source_id_matched( $source_id, $posttype, $raw ) )
							if ( $oldpost = WordPress\Post::get( intval( $matched ) ) )
								$data['ID'] = $oldpost->ID;

						foreach ( $field_map as $offsetkey => $field ) {

							if ( 'none' == $field )
								continue;

							$value = $this->filters( 'prepare',
								Core\Text::trim( $row[$offsetkey] ),
								$posttype,
								$field,
								$headers[$offsetkey],
								$raw,
								$source_id,
								$all_taxonomies
							);

							// filter bail-out!
							if ( FALSE === $value )
								continue;

							if ( WordPress\Strings::isEmpty( $value ) ) {

								if ( ! $override )
									continue;

								if ( ! is_array( $value ) )
									$value = '';
							}

							if ( $value && $field == 'importer_post_title' && $this->get_setting( 'skip_same_title' ) ) {

								$posts = WordPress\Post::getByTitle( $value, $posttype );

								if ( ! empty( $posts ) )
									continue 2;
							}

							switch ( $field ) {

								case 'importer_custom_meta':

									if ( $custom_metakey = $this->filters( 'custom_metakey', $headers[$offsetkey], $posttype, $field, $raw, $all_taxonomies ) )
										$data['meta_input'][$custom_metakey] = $prepared[sprintf( '%s__%s', $field, $custom_metakey )] = Core\Text::normalizeWhitespace( $value );

									continue 2;

								case 'importer_menu_order':

									if ( $override || ! $oldpost || ( $oldpost && '' == $oldpost->menu_order ) )
										$data['menu_order'] = $prepared[$field] = Core\Number::translate( trim( $value ) );

									continue 2;

								case 'importer_post_title':

									if ( $override || ! $oldpost || ( $oldpost && '' == $oldpost->post_title ) )
										$data['post_title'] = $prepared[$field] = Core\Text::normalizeWhitespace( $value );

									continue 2;

								case 'importer_post_content':

									if ( $override || ! $oldpost || ( $oldpost && '' == $oldpost->post_content ) )
										$data['post_content'] = $prepared[$field] = Core\Text::normalizeWhitespace( $value, TRUE );

									continue 2;

								case 'importer_post_excerpt':

									if ( $override || ! $oldpost || ( $oldpost && '' == $oldpost->post_excerpt ) )
										$data['post_excerpt'] = $prepared[$field] = Core\Text::normalizeWhitespace( $value, TRUE );

									continue 2;

								case 'importer_comment_content':

									// NOTE: comments have no overrides!
									// TODO: support multiple comment fields

									// skip empty values on comments
									if ( ! $value ) {

										$prepared[$field] = '';

									} else {

										$prepared[$field] = Core\Text::normalizeWhitespace( $value, TRUE );

										$comments[] = [
											// Prefixes the comment content with column name
											'comment_content' => sprintf( '[%s]: %s', $headers[$offsetkey], $prepared[$field] ),
										];
									}

									continue 2;
							}

							foreach ( $all_taxonomies as $taxonomy => $taxonomy_object ) {

								// skip empty values on terms
								if ( ! $value )
									break;

								if ( $field != 'importer_tax_'.$taxonomy )
									continue;

								// allows to import multiple columns for one taxonomy
								$already = array_key_exists( $taxonomy, $taxonomies ) ? $taxonomies[$taxonomy] : [];

								if ( $taxonomy_object->hierarchical ) {

									if ( $terms = WordPress\Taxonomy::insertDefaultTerms( $taxonomy, Core\Arraay::sameKey( $value ), FALSE ) )
										$taxonomies[$taxonomy] = Core\Arraay::prepNumeral( $already, Core\Arraay::pluck( $terms, 'term_taxonomy_id' ) );

								} else {

									$taxonomies[$taxonomy] = Core\Arraay::prepString( $already, $value );
								}

								$prepared[sprintf( 'taxonomy__%s', $taxonomy )] = $taxonomies[$taxonomy];

								continue 2;
							}

							// otherwise store prepared value
							$prepared[$field] = $value;
						}

						if ( FALSE === ( $insert = $this->filters( 'insert', $data, $prepared, $taxonomies, $posttype, $source_id, $attach_id, $raw, $override ) ) ) {

							$this->log( 'NOTICE', ( $source_id
								? sprintf( 'ID: %s :: %s', $source_id, 'SKIPPED BY `insert` FILTER' )
								: 'SKIPPED BY `insert` FILTER'
							) );

							continue;
						}

						if ( empty( $insert['ID'] ) ) {

							// only if it's new!
							$insert = array_merge( [
								// 'post_name'      => '', // The name (slug) for your post
								// 'ping_status'    => 'closed', //[ 'closed' | 'open' ] // Pingbacks or trackbacks allowed. Default is the option 'default_ping_status'.
								// 'post_date'      => current_time( 'mysql' ), //[ Y-m-d H:i:s ] // The time post was made.
								// 'post_parent'    => 0, // Sets the parent of the new post, if any. Default 0.

								'post_type'      => $posttype,
								'post_status'    => $post_status,
								'comment_status' => $comment_status,
								'post_author'    => $user_id,
							], $insert );

							if ( $source_id )
								$insert['meta_input'][$this->constant( 'metakey_source_id' )] = $source_id;

							$post_id = wp_insert_post( $insert, TRUE, FALSE );

						} else if ( $this->_check_insert_is_empty( $insert, $insert['ID'] ) ) {

							// TODO: maybe manually store: `meta_input` to avoid `wp_insert_post`

							if ( $post = WordPress\Post::get( $insert['ID'] ) ) {

								$post_id = $post->ID;

							} else {

								$this->log( 'NOTICE', ( $source_id
									? sprintf( 'ID: %s :: %s', $source_id, 'PROVIDED POST-ID NOT FOUND' )
									: 'PROVIDED POST-ID NOT FOUND'
								) );

								continue;
							}

						} else {

							$post_id = wp_insert_post( $insert, TRUE, FALSE );
						}

						if ( ! $post_id ) {

							$this->log( 'NOTICE', ( $source_id
								? sprintf( 'ID: %s :: %s', $source_id, 'SOMETHING IS WRONG!' )
								: 'SOMETHING IS WRONG!'
							) );

							continue;

						} else if ( is_wp_error( $post_id ) ) {

							$this->log( 'NOTICE', ( $source_id
								? sprintf( 'ID: %s :: %s', $source_id, $post_id->get_error_message() )
								: $post_id->get_error_message()
							) );

							continue;
						}

						// NOTE: `wp_insert_post()` overrides existing terms
						$this->_set_terms_for_post( $post_id, $taxonomies, $source_id, $oldpost, $override, FALSE );
						$this->_set_terms_for_post( $post_id, $terms_all, $source_id, $oldpost );

						if ( FALSE !== ( $comments = $this->filters( 'comments', $comments, $data, $prepared, $posttype, $source_id, $attach_id, $raw ) ) ) {

							foreach ( $comments as $comment ) {

								if ( empty( $comment ) )
									continue;

								if ( empty( $comment['comment_post_ID'] ) )
									$comment['comment_post_ID'] = $post_id;

								if ( empty( $comment['user_id'] ) )
									$comment['user_id'] = $user_id;

								// TODO: maybe add the custom bot title on `comment_author` from settings

								if ( ! wp_insert_comment( $comment ) )
									$this->log( 'NOTICE', ( $source_id
										? sprintf( 'ID: %s :: %s', $source_id, 'FAILED STORING COMMENT' )
										: 'FAILED STORING COMMENT'
									) );
							}
						}

						$this->actions( 'saved', WordPress\Post::get( $post_id ), [
							'updated'    => ( ! empty( $insert['ID'] ) ),
							'data'       => $insert,
							'prepared'   => $prepared,
							'headers'    => $headers, // NOTE: used for getting the column title
							'raw'        => $raw,
							'map'        => array_combine( $headers, $field_map ),
							'source_id'  => $source_id,
							'attach_id'  => $attach_id,
							'terms_all'  => $terms_all,
							'taxonomies' => $taxonomies,
							'override'   => $override,
							'oldpost'    => $oldpost,
						] );

						// @REF: https://make.wordpress.org/core/2020/11/20/new-action-wp_after_insert_post-in-wordpress-5-6/
						wp_after_insert_post( $post_id, $oldpost ? TRUE : FALSE, $oldpost ?: NULL );

						++$count;
					}

					$this->actions( 'posts_after', $posttype );

					remove_filter( 'wp_insert_post_empty_content', '__return_false', 12 );
					unset( $iterator );

					WordPress\Redirect::doReferer( [
						'message' => 'imported',
						'count'   => $count,
					] );
				}
			}

			gEditorial\Scripts::enqueueThickBox();
			wp_enqueue_media();

			$this->enqueue_asset_js( [
				'strings' => $this->get_strings( 'media', 'js' ),
				'config'  => [
					'mimetypes' => $this->_get_source_mimetypes(),
				],
			], $this->dotted( 'media' ), [ 'jquery', 'media-upload' ] );
		}
	}

	protected function render_imports_html( $uri, $sub )
	{
		switch ( self::step() ) {

			case 'posts_step_two':
			case 'posts_step_three':
			case 'posts_step_four':

				$this->_render_imports_for_posts( $uri, $sub );

				break;

			case 'terms_step_two':
			case 'terms_step_three':
			case 'terms_step_four':

				$this->_render_imports_for_terms( $uri, $sub );

				break;

			case 'images_step_two':

				$this->_render_imports_for_images( $uri, $sub );

				break;

			// TODO: `_render_imports_for_files()`
			// --- import data from directory of files into fields: like excerpt
			// - attach file from directory of files into posts with field data to rename

			// TODO: `_render_imports_for_metas()`
			// - import data by metakey + support types: string/int/comma separated

			// TODO: imports for sub-contents!

			default:

				$this->_render_imports_firstpage( $uri, $sub );
		}
	}

	private function _render_imports_firstpage( $uri, $sub )
	{
		echo gEditorial\Settings::toolboxColumnOpen( _x( 'Content Imports', 'Header', 'geditorial-importer' ) );

		if ( ! count( $this->posttypes() ) )
			return gEditorial\Info::renderNoImportsAvailable();

		echo gEditorial\Settings::toolboxCardOpen( _x( 'Import Data from CSV into Posts', 'Header', 'geditorial-importer' ), FALSE );
			$this->_render_imports_for_posts( $uri, $sub );
		echo '</div>';

		echo gEditorial\Settings::toolboxCardOpen( _x( 'Import Terms and Assign them to Posts', 'Header', 'geditorial-importer' ), FALSE );
			$this->_render_imports_for_terms( $uri, $sub );
		echo '</div>';

		echo gEditorial\Settings::toolboxCardOpen( _x( 'Import Remote Files as Attachments', 'Header', 'geditorial-importer' ), FALSE );
			$this->_render_imports_for_images( $uri, $sub );
		echo '</div>';

		echo '</div>';
	}

	private function _render_imports_for_posts( $uri, $sub )
	{
		$field_map     = self::req( 'field_map', [] );
		$terms_all     = self::req( 'terms_all', [] );
		$posttype      = self::req( 'posttype', $this->get_setting( 'post_type', 'post' ) );
		$upload_id     = self::req( 'upload_id', FALSE );
		$attach_id     = self::req( 'attach_id', FALSE );
		$user_id       = self::req( 'user_id', $this->_get_user_id() );
		$source_offset = self::req( 'source_offset', 'none' );

		if ( $upload_id )
			$attach_id = $upload_id;

		if ( self::step( 'posts_step_four' ) ) {

			if ( ! $attach_id )
				return Core\HTML::desc( _x( 'Import source is not defined!', 'Message', 'geditorial-importer' ) );

			if ( ! WordPress\PostType::can( $posttype, 'edit_posts' ) )
				return Core\HTML::desc( _x( 'You are not allowed to edit this post-type!', 'Message', 'geditorial-importer' ) );

			echo $this->wrap_open( '-step-hints' );
				Services\Markup::renderCircleProgress( 3, 4, _x( 'Import', 'Step', 'geditorial-importer' ) );
			echo '<br /><br /></div>';

			Core\HTML::inputHiddenArray( $field_map, 'field_map' );
			Core\HTML::inputHiddenArray( $terms_all, 'terms_all' );
			Core\HTML::inputHidden( 'posttype', $posttype );
			Core\HTML::inputHidden( 'attach_id', $attach_id );
			Core\HTML::inputHidden( 'user_id', $user_id );
			Core\HTML::inputHidden( 'source_offset', $source_offset );
			// Core\HTML::inputHidden( 'imports_for', 'posts' );

			$this->_form_posts_table( $attach_id, $field_map, $posttype, $terms_all, $source_offset );

			echo $this->wrap_open_buttons();
			gEditorial\Settings::submitButton( 'posts_import_newonly', _x( 'Import New Data', 'Button', 'geditorial-importer' ), TRUE );
			gEditorial\Settings::submitButton( 'posts_import_override', _x( 'Import and Override', 'Button', 'geditorial-importer' ) );
			Core\HTML::desc( _x( 'Select records to finally import.', 'Message', 'geditorial-importer' ), FALSE );

		} else if ( self::step( 'posts_step_three' ) ) {

			if ( ! $attach_id )
				return Core\HTML::desc( _x( 'Import source is not defined!', 'Message', 'geditorial-importer' ) );

			if ( ! WordPress\PostType::can( $posttype, 'edit_posts' ) )
				return Core\HTML::desc( _x( 'You are not allowed to edit this post-type!', 'Message', 'geditorial-importer' ) );

			echo $this->wrap_open( '-step-hints' );
				Services\Markup::renderCircleProgress( 2, 4, _x( 'Terms', 'Step', 'geditorial-importer' ) );
			echo '</div>';

			Core\HTML::h3( sprintf(
				/* translators: `%s`: attachment title */
				_x( 'Terms to Append All for &ldquo;%s&rdquo;', 'Header', 'geditorial-importer' ),
				get_the_title( $attach_id )
			) );

			Core\HTML::inputHiddenArray( $field_map, 'field_map' );
			Core\HTML::inputHidden( 'posttype', $posttype );
			Core\HTML::inputHidden( 'attach_id', $attach_id );
			Core\HTML::inputHidden( 'user_id', $user_id );
			Core\HTML::inputHidden( 'source_offset', $source_offset );
			// Core\HTML::inputHidden( 'imports_for', 'posts' );

			if ( ! $this->_render_posttype_taxonomies( $posttype ) )
				Core\HTML::desc( _x( 'No taxonomy availabe for this post-type!', 'Message', 'geditorial-importer' ) );

			echo $this->wrap_open_buttons();
			gEditorial\Settings::actionButton( 'posts_step_four', _x( 'Step 4: Import', 'Button', 'geditorial-importer' ), TRUE );
			Core\HTML::desc( _x( 'Select a term from each post-type supported taxonomy to append all imported posts.', 'Message', 'geditorial-importer' ), FALSE );

		} else if ( self::step( 'posts_step_two' ) ) {

			if ( ! $attach_id )
				return Core\HTML::desc( _x( 'Import source is not defined!', 'Message', 'geditorial-importer' ) );

			if ( ! WordPress\PostType::can( $posttype, 'edit_posts' ) )
				return Core\HTML::desc( _x( 'You are not allowed to edit this post-type!', 'Message', 'geditorial-importer' ) );

			echo $this->wrap_open( '-step-hints' );
				Services\Markup::renderCircleProgress( 1, 4, _x( 'Map', 'Step', 'geditorial-importer' ) );
			echo '</div>';

			Core\HTML::h3( sprintf(
				/* translators: `%s`: attachment title */
				_x( 'Map the Importer for &ldquo;%s&rdquo;', 'Header', 'geditorial-importer' ),
				get_the_title( $attach_id )
			) );

			Core\HTML::inputHidden( 'posttype', $posttype );
			Core\HTML::inputHidden( 'attach_id', $attach_id );
			// Core\HTML::inputHidden( 'imports_for', 'posts' );
			Core\HTML::inputHidden( 'user_id', $user_id );

			$this->_form_posts_map( $attach_id, $posttype );

			echo $this->wrap_open_buttons();
			gEditorial\Settings::actionButton( 'posts_step_three', _x( 'Step 3: Terms', 'Button', 'geditorial-importer' ), TRUE );
			Core\HTML::desc( _x( 'Map the file fields to the post-type fields.', 'Message', 'geditorial-importer' ), FALSE );

		} else {

			echo $this->wrap_open( '-step-hints' );
				Services\Markup::renderCircleProgress( 0, 4, _x( 'Attachment', 'Step', 'geditorial-importer' ) );
			echo '</div>';

			$this->_form_posts_attached( self::req( 'attachment', 0 ), $posttype, $user_id );

			echo $this->wrap_open_buttons();

			gEditorial\Settings::actionButton( 'posts_step_two', _x( 'Step 2: Map', 'Button', 'geditorial-importer' ), TRUE );
			Core\HTML::desc( _x( 'Upload or select a CSV file, post-type and user to map the import.', 'Message', 'geditorial-importer' ), FALSE );
		}

		echo '</p>';
	}

	private function _render_imports_for_terms( $uri, $sub )
	{
		// $field_map  = self::req( 'field_map', [] );
		$terms_all     = self::req( 'terms_all', [] );
		$posttype      = self::req( 'posttype', $this->get_setting( 'post_type', 'post' ) );
		$upload_id     = self::req( $this->classs( 'terms', 'attachment', 'uploaded' ) );
		$attach_id     = self::req( $this->classs( 'terms', 'attachment', 'selected' ) );
		$user_id       = self::req( 'user_id', $this->_get_user_id() );
		$source_offset = self::req( 'source_offset', 'none' );

		if ( $upload_id )
			$attach_id = $upload_id;

		if ( self::step( 'terms_step_four' ) ) {

			if ( ! $attach_id )
				return Core\HTML::desc( _x( 'Import source is not defined!', 'Message', 'geditorial-importer' ) );

			if ( ! WordPress\PostType::can( $posttype, 'edit_posts' ) )
				return Core\HTML::desc( _x( 'You are not allowed to edit this post-type!', 'Message', 'geditorial-importer' ) );

			// Core\HTML::inputHiddenArray( $field_map, 'field_map' );
			Core\HTML::inputHiddenArray( array_filter( $terms_all ), 'terms_all' );
			Core\HTML::inputHidden( 'posttype', $posttype );
			Core\HTML::inputHidden( $this->classs( 'terms', 'attachment', 'selected' ), $attach_id );
			Core\HTML::inputHidden( 'user_id', $user_id );
			Core\HTML::inputHidden( 'source_offset', $source_offset );

			$this->_form_terms_table( $attach_id, $field_map ?? [], $posttype, $terms_all, $source_offset );

			echo $this->wrap_open_buttons();
			gEditorial\Settings::submitButton( 'terms_import_newonly', _x( 'Import New Data', 'Button', 'geditorial-importer' ), TRUE );
			gEditorial\Settings::submitButton( 'terms_import_append', _x( 'Import and Append', 'Button', 'geditorial-importer' ) );
			gEditorial\Settings::submitButton( 'terms_import_override', _x( 'Import and Override', 'Button', 'geditorial-importer' ) );
			Core\HTML::desc( _x( 'Select records to finally import.', 'Message', 'geditorial-importer' ), FALSE );

		} else if ( self::step( 'terms_step_three' ) ) {

			if ( ! $attach_id )
				return Core\HTML::desc( _x( 'Import source is not defined!', 'Message', 'geditorial-importer' ) );

			if ( 'none' === $source_offset )
				return Core\HTML::desc( _x( 'Import source column is not defined!', 'Message', 'geditorial-importer' ) );

			if ( ! WordPress\PostType::can( $posttype, 'edit_posts' ) )
				return Core\HTML::desc( _x( 'You are not allowed to edit this post-type!', 'Message', 'geditorial-importer' ) );

			Core\HTML::h3( sprintf(
				/* translators: `%s`: attachment title */
				_x( 'Terms to Append All for &ldquo;%s&rdquo;', 'Header', 'geditorial-importer' ),
				get_the_title( $attach_id )
			) );

			if ( ! $this->_render_posttype_taxonomies( $posttype ) )
				return Core\HTML::desc( _x( 'No taxonomy availabe for this post-type!', 'Message', 'geditorial-importer' ) );

			// Core\HTML::inputHiddenArray( $field_map, 'field_map' );
			Core\HTML::inputHidden( 'posttype', $posttype );
			Core\HTML::inputHidden( $this->classs( 'terms', 'attachment', 'selected' ), $attach_id );
			Core\HTML::inputHidden( 'user_id', $user_id );
			Core\HTML::inputHidden( 'source_offset', $source_offset );

			echo $this->wrap_open_buttons();
			gEditorial\Settings::actionButton( 'terms_step_four', _x( 'Step 3: Terms', 'Button', 'geditorial-importer' ), TRUE );
			Core\HTML::desc( _x( 'Select a term from each post-type supported taxonomy to append all imported posts.', 'Message', 'geditorial-importer' ), FALSE );

		} else if ( self::step( 'terms_step_two' ) ) {

			if ( ! $attach_id )
				return Core\HTML::desc( _x( 'Import source is not defined!', 'Message', 'geditorial-importer' ) );

			if ( ! WordPress\PostType::can( $posttype, 'edit_posts' ) )
				return Core\HTML::desc( _x( 'You are not allowed to edit this post-type!', 'Message', 'geditorial-importer' ) );

			Core\HTML::h3( sprintf(
				/* translators: `%s`: attachment title */
				_x( 'Map the Importer for &ldquo;%s&rdquo;', 'Header', 'geditorial-importer' ),
				get_the_title( $attach_id )
			) );

			$this->_form_terms_map( $attach_id, $posttype );

			Core\HTML::inputHidden( 'posttype', $posttype );
			Core\HTML::inputHidden( $this->classs( 'terms', 'attachment', 'selected' ), $attach_id );
			Core\HTML::inputHidden( 'user_id', $user_id );

			echo $this->wrap_open_buttons();
			gEditorial\Settings::actionButton( 'terms_step_three', _x( 'Step 2: Map', 'Button', 'geditorial-importer' ), TRUE );
			Core\HTML::desc( _x( 'Map the file fields to the post-type fields.', 'Message', 'geditorial-importer' ), FALSE );

		} else {

			$this->_form_terms_attached( self::req( 'attachment', 0 ), $posttype, $user_id );

			echo $this->wrap_open_buttons();
			gEditorial\Settings::actionButton( 'terms_step_two', _x( 'Step 2: Map', 'Button', 'geditorial-importer' ), TRUE );
			Core\HTML::desc( _x( 'Upload or select a CSV file with post-type to map the import.', 'Message', 'geditorial-importer' ), FALSE );
		}
	}

	private function _get_current_form_images()
	{
		return $this->get_current_form( [
			'user_id'  => $this->_get_user_id(),
			'posttype' => $this->get_setting( 'post_type', 'post' ),
			'metakey'  => $this->constant( 'metakey_source_id' ),
			'template' => $this->_get_default_template_for_image(),
		], 'forimages' );
	}

	private function _render_imports_for_images( $uri, $sub )
	{
		if ( ! current_user_can( 'upload_files' ) )
			return Core\HTML::desc( _x( 'You are not allowed to upload files!', 'Message', 'geditorial-importer' ) );

		$args = $this->_get_current_form_images();

		if ( self::step( 'images_step_two' ) ) {

			if ( empty( $args['metakey'] ) )
				return Core\HTML::desc( _x( 'Refrence meta-key is not defined!', 'Message', 'geditorial-importer' ) );

			if ( ! WordPress\PostType::can( $args['posttype'], 'edit_posts' ) )
				return Core\HTML::desc( _x( 'You are not allowed to edit this post-type!', 'Message', 'geditorial-importer' ) );

			// Core\HTML::inputHidden( 'imports_for', 'images' );

			$this->fields_current_form( $args, 'forimages' );
			$this->_form_images_table( $args );

			echo $this->wrap_open_buttons();
			gEditorial\Settings::submitButton( 'images_import_as_thumbnail', _x( 'Import & Set Thumbnail', 'Button', 'geditorial-importer' ), TRUE );
			gEditorial\Settings::submitButton( 'images_import', _x( 'Import Only', 'Button', 'geditorial-importer' ) );
			Core\HTML::desc( _x( 'Select records to finally import.', 'Message', 'geditorial-importer' ), FALSE );

		} else {

			echo '<hr class="-silent" />';
			gEditorial\Settings::fieldSeparate( 'from' );

			$this->do_settings_field( [
				'type'         => 'select',
				'field'        => 'metakey',
				'values'       => $this->_get_metakeys_for_image( $args['posttype'] ),
				'none_title'   => gEditorial\Settings::showOptionNone(),
				'default'      => $args['metakey'],
				'option_group' => 'forimages',
				'cap'          => TRUE, // already checked
			] );

			echo '<hr class="-silent" />';

			gEditorial\Settings::fieldSeparate( 'in' );

			$this->do_settings_field( [
				'type'         => 'text',
				'field'        => 'template',
				'default'      => $args['template'],
				'placeholder'  => $this->_get_default_template_for_image( $args['posttype'] ),
				'dir'          => 'ltr',
				'option_group' => 'forimages',
				'cap'          => TRUE, // already checked
			] );

			echo '<hr class="-silent" />';

			gEditorial\Settings::fieldSeparate( 'into' );

			$this->do_settings_field( [
				'type'         => 'select',
				'field'        => 'posttype',
				'values'       => $this->list_posttypes( NULL, NULL, 'edit_posts' ),
				'default'      => $args['posttype'],
				'option_group' => 'forimages',
				'cap'          => TRUE, // already checked
			] );

			gEditorial\Settings::fieldSeparate( 'as' );

			$this->do_settings_field( [
				'type'         => 'user',
				'field'        => 'user_id',
				'default'      => $args['user_id'],
				'disabled'     => ! $this->_can_change_user(),
				'option_group' => 'forimages',
				'cap'          => TRUE, // already checked
			] );

			echo $this->wrap_open_buttons();
			gEditorial\Settings::actionButton( 'images_step_two', _x( 'Step 1: Meta-key', 'Button', 'geditorial-importer' ), TRUE );
			Core\HTML::desc( _x( 'Select a meta-key for refrence on importing the attachments.', 'Message', 'geditorial-importer' ), FALSE );
		}

		echo '</p>';
	}

	public function import_memory_limit( $filtered_limit )
	{
		return -1;
	}

	public function get_importer_taxonomies( $posttype = NULL )
	{
		$list = [];

		if ( $posttype ) {

			foreach ( get_object_taxonomies( $posttype, 'objects' ) as $taxonomy ) {

				if ( ! empty( $taxonomy->{Services\Paired::PAIRED_POSTTYPE_PROP} ) )
					continue;

				if ( ! empty( $taxonomy->{Services\TermHierarchy::AUTO_ASSIGNED_TERMS} ) )
					continue;

				$list[$taxonomy->name] = $taxonomy;
			}
		}

		return $this->filters( 'taxonomies', $list, $posttype );
	}

	public function get_importer_fields( $posttype = NULL, $taxonomies = [] )
	{
		$fields = [
			'importer_custom_meta'     => _x( 'Extra: Custom Meta', 'Post Field', 'geditorial-importer' ),
			'importer_menu_order'      => _x( 'Menu Order', 'Post Field', 'geditorial-importer' ),
			'importer_post_title'      => _x( 'Post Title', 'Post Field', 'geditorial-importer' ),
			'importer_post_content'    => _x( 'Post Content', 'Post Field', 'geditorial-importer' ),
			'importer_post_excerpt'    => _x( 'Post Excerpt', 'Post Field', 'geditorial-importer' ),
			'importer_comment_content' => _x( 'Comment Content', 'Post Field', 'geditorial-importer' ),
		];

		foreach ( (array) $taxonomies as $taxonomy => $taxonomy_object )
			$fields['importer_tax_'.$taxonomy] = sprintf(
				/* translators: `%s`: taxonomy name placeholder */
				_x( 'Taxonomy: %s', 'Post Field', 'geditorial-importer' ),
				$taxonomy_object->labels->singular_name
			);

		return $this->filters( 'fields', $fields, $posttype );
	}

	public function importer_prepare( $value, $posttype, $field, $header, $raw, $source_id, $all_taxonomies )
	{
		switch ( $field ) {

			case 'importer_menu_order'     : return Core\Number::intval( $value );
			case 'importer_post_title'     : return WordPress\Strings::kses( $value, 'none' );
			case 'importer_post_content'   : return WordPress\Strings::balanceTags( WordPress\Strings::kses( $value, 'html' ) );
			case 'importer_post_excerpt'   : return WordPress\Strings::kses( $value, 'text' );
			case 'importer_custom_meta'    : return WordPress\Strings::kses( $value, 'text' );
			case 'importer_comment_content': return WordPress\Strings::balanceTags( WordPress\Strings::kses( $value, 'html' ) );
		}

		foreach ( array_keys( $all_taxonomies ) as $taxonomy )
			if ( $field == 'importer_tax_'.$taxonomy )
				return array_filter( WordPress\Strings::ksesArray( Services\Markup::getSeparated( $value ) ) );

		return $value;
	}

	// public function importer_saved( $post, $data, $prepared, $field_map, $source_id, $attach_id, $terms_all, $raw )
	public function importer_saved( $post, $atts = [] )
	{
		if ( ! $post )
			return;

		if ( $this->get_setting( 'store_source_data' ) ) {
			$suffix = '_'.current_time( 'Ymd-His', TRUE );
			add_post_meta( $post->ID, $this->constant( 'metakey_source_data' ).$suffix , $atts['raw'] );
			add_post_meta( $post->ID, $this->constant( 'metakey_prepared_data' ).$suffix, $atts['prepared'] );
			add_post_meta( $post->ID, $this->constant( 'metakey_attach_id' ).$suffix, $atts['attach_id'] );
		}

		if ( $this->get_setting( 'add_audit_attribute' ) )
			Services\Modulation::setTaxonomyAudit( $post, $this->constant( 'term_newpost_imported' ) );
	}

	private function _get_source_id_matched( $source_id, $posttype, $raw = [] )
	{
		if ( ! $source_id || ! $this->get_setting( 'match_source_id', TRUE ) )
			return FALSE;

		$matched = FALSE;

		if ( $matches = WordPress\PostType::getIDbyMeta( $this->constant( 'metakey_source_id' ), $source_id, FALSE ) ) {

			foreach ( $matches as $match ) {

				if ( $posttype !== get_post_type( intval( $match ) ) )
					continue;

				$matched = intval( $match );
				break;
			}
		}

		return $this->filters( 'matched', $matched, $source_id, $posttype, $raw );
	}

	private function _get_metakeys_for_image( $posttype = NULL )
	{
		return $this->filters( 'metakeys_for_image',
			WordPress\Database::getPostMetaKeys( TRUE ),
			$posttype ?? $this->get_setting( 'post_type', 'post' ),
		);
	}

	private function _get_default_template_for_image( $posttype = NULL )
	{
		return $this->filters( 'template_for_image',
			Core\URL::home( 'repo/%s.jpg' ), // FIXME: get default from settings
			$posttype ?? $this->get_setting( 'post_type', 'post' ),
		);
	}

	/**
	 * Checks if given data is suitable to use on `wp_insert_post()`
	 *
	 * @param array $data
	 * @param bool|int $post_id
	 * @return bool
	 */
	private function _check_insert_is_empty( $data, $post_id = FALSE )
	{
		unset( $data['ID'] );

		return empty( array_filter( $data ) );
	}

	private function _set_terms_for_post( $post_id, $taxonomies, $source_id = NULL, $oldpost = FALSE, $override = TRUE, $append = TRUE )
	{
		foreach ( $taxonomies as $taxonomy => $terms ) {

			if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
				continue;

			$currents = $oldpost
				? WordPress\Taxonomy::getPostTerms( $taxonomy, $oldpost, FALSE )
				: [];

			if ( ! $override && count( $currents ) )
				continue;

			$filtered = $this->filters( 'set_terms_'.$taxonomy,
				$terms,
				$currents,
				$source_id,
				$post_id,
				$oldpost,
				$override,
				$append
			);

			if ( FALSE === $filtered )
				continue;

			if ( Services\TermHierarchy::isSingleTerm( $object ) ) {

				if ( $override && ( $single = Services\TermHierarchy::getSingleSelectTerm( $object, $filtered, $post_id ) ) )
					$filtered = $single->term_id;
			}

			if ( is_null( $filtered ) )
				$result = wp_set_object_terms( $post_id, NULL, $taxonomy );

			else if ( ! empty( $filtered ) )
				$result = wp_set_object_terms( $post_id, $filtered, $taxonomy, $append );

			else
				continue;

			if ( is_wp_error( $result ) )
				$this->log( 'NOTICE', ( $source_id
					? sprintf( 'ID: %s :: %s: %s', $source_id, 'ERROR SETTING TERMS FOR TAXONOMY', $taxonomy )
					: sprintf( '%s: %s', 'ERROR SETTING TERMS FOR TAXONOMY', $taxonomy )
				) );
		}
	}

	public function tools_settings( $sub )
	{
		$this->check_settings( $sub, 'tools', 'per_page' );
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo gEditorial\Settings::toolboxColumnOpen( _x( 'Importer Tools', 'Header', 'geditorial-importer' ) );

		$available = FALSE;
		$posttypes = $this->list_posttypes();

		if ( count( $posttypes ) ) {

			ModuleSettings::renderCard_cleanup_raw_data( $posttypes );

			$available = TRUE;
		}

		if ( ! $available )
			gEditorial\Info::renderNoToolsAvailable();

		echo '</div>';
	}

	protected function render_tools_html_before( $uri, $sub )
	{
		if ( $this->_do_tool_cleanup_raw_data( $sub ) )
			return FALSE; // avoid further UI
	}

	private function _do_tool_cleanup_raw_data( $sub )
	{
		if ( ! self::do( ModuleSettings::ACTION_CLEANUP_RAW_DATA ) )
			return FALSE;

		if ( ! $posttype = self::req( 'type' ) )
			return ! gEditorial\Info::renderEmptyPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $this->posttype_supported( $posttype ) )
			return ! gEditorial\Info::renderNotSupportedPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		$this->raise_resources();

		return ModuleSettings::handleTool_cleanup_raw_data(
			$posttype,
			$this->_get_metakeys(),
			$this->get_sub_limit_option( $sub, 'tools' )
		);
	}

	private function _get_metakeys()
	{
		return Core\Arraay::prepString( $this->constants( [
			'metakey_source_data',
			'metakey_prepared_data',
			'metakey_attach_id',
		] ), [
			// OLD KEYS: DEPRECATED
			'_importer_source_data',
			'_importer_attachment_id',
		] );
	}

	// TODO: support for excel source!
	private function _get_source_mimetypes()
	{
		return [
			'text/csv',
			// 'application/vnd.ms-excel',
		];
	}

	private function _can_change_user()
	{
		// return current_user_can( 'manage_option' );
		return WordPress\User::isSuperAdmin();
	}

	private function _get_user_id()
	{
		return $this->_can_change_user()
			? gEditorial()->user( TRUE )
			: get_current_user_id();
	}

	public function imports_general_summary( $uri )
	{
		// TODO: report on available imports
	}

	private function _form_terms_attached( $id = 0, $posttype = 'post', $user_id = NULL )
	{
		$target = $this->classs( 'terms', 'attachment', 'uploaded' );
		$select = $this->classs( 'terms', 'attachment', 'selected' );

		echo '<hr class="-silent" />';
		gEditorial\Settings::fieldSeparate( 'from' );

		Core\HTML::inputHidden( $target );

		echo Core\HTML::tag( 'input', [
			'type'  => 'button',
			'value' => _x( 'Upload', 'Button', 'geditorial-importer' ),
			'data'  => [ 'target' => $target ],
			'class' => [
				'button',
				'-button',
				$this->classs( 'uploadbutton' ),
			],
		] );

		gEditorial\Settings::fieldSeparate( 'or' );

		WordPress\Media::selectAttachment( $id, $this->_get_source_mimetypes(), $select, gEditorial\Plugin::na() );

		echo '<hr class="-silent" />';

		gEditorial\Settings::fieldSeparate( 'into' );

		echo Core\HTML::dropdown( $this->list_posttypes( NULL, NULL, 'edit_posts' ), [
			'selected' => $posttype,
			'name'     => 'posttype',
		] );

		gEditorial\Settings::fieldSeparate( 'as' );

		echo Core\HTML::dropdown( WordPress\User::get(), [
			'selected' => is_null( $user_id ) ? $this->_get_user_id() : $user_id,
			'name'     => 'user_id',
			'prop'     => 'display_name',
			'disabled' => ! $this->_can_change_user(),
		] );
	}

	private function _form_terms_map( $id, $posttype = 'post' )
	{
		if ( ! $file = get_attached_file( $id ) )
			return FALSE;

		$this->raise_resources();

		$parsed        = gEditorial\Parser::fromCSV( $file, [ 'headers' => TRUE ] );
		$source_offset = $this->fetch_postmeta( $id, 'none', $this->constant( 'metakey_source_offset' ) );

		if ( $dups = Core\Arraay::duplicates( $parsed['headers'] ) )
			echo Core\HTML::warning( sprintf(
				/* translators: `%s`: joined duplicate keys */
				_x( 'Found duplicate column headers: %s', 'Message', 'geditorial-importer' ),
				WordPress\Strings::getJoined( $dups )
			), FALSE, 'inline' );

		echo '<table class="base-table-raw"><tbody>';

		echo '<tr><td><strong>'._x( 'Source ID', 'Dropdown Label', 'geditorial-importer' );
		echo '</strong></td><td class="-sep">';

			gEditorial\Settings::fieldSeparate( 'from' );

		echo '</td><td>';

			echo Core\HTML::dropdown( $parsed['headers'], [
				'selected'   => $source_offset,
				'name'       => 'source_offset',
				'class'      => '-dropdown-source-key',
				'none_title' => gEditorial\Settings::showOptionNone(),
				'none_value' => 'none', // `0` is offset!
			] );

		echo '</td><td>&nbsp;</td><td>&nbsp;</td><td>';

			Core\HTML::desc( _x( 'Used as Identifider of each item.', 'Description', 'geditorial-importer' ) );

		echo '</td></tr>';
		echo '</table>';
	}

	private function _form_terms_table( $id, $map = [], $posttype = 'post', $terms_all = [], $source_offset = 'none' )
	{
		if ( ! $file = get_attached_file( $id ) )
			return FALSE;

		$this->raise_resources();

		$parsed = gEditorial\Parser::fromCSV( $file );

		$this->store_postmeta( $id, ( 'none' === $source_offset ? FALSE : $source_offset ), $this->constant( 'metakey_source_offset' ) );

		$this->_render_data_table_for_terms( $id, $parsed['items'], $parsed['headers'], $map, $posttype, $terms_all, $source_offset );
	}

	private function _render_data_table_for_terms( $id, $data, $headers, $map = [], $posttype = 'post', $terms_all = [], $source_offset = 'none' )
	{
		$taxonomies = WordPress\Taxonomy::get( 4, [], $posttype );  // NOTE: all and must not be filtered
		$columns    = [
			'_cb'           => '_index',
			'_check_column' => [
				'title'    => _x( '[Checks]', 'Table Column', 'geditorial-importer' ),
				'callback' => [ $this, 'form_posts_table_checks' ],
			],
		];

		foreach ( $terms_all as $taxonomy => $term_id ) {

			if ( empty( $term_id ) )
				continue;

			if ( ! array_key_exists( $taxonomy, $taxonomies ) )
				continue;

			if ( ! $term = WordPress\Term::get( $term_id, $taxonomy ) )
				continue;

			$columns[sprintf( '%s_%s', $taxonomy, $term_id )] = [
				'title' => sprintf( '%s: %s', $taxonomies[$taxonomy]->label, Core\HTML::code( $term->name ) ),
				'args'  => [
					'taxonomy' => $taxonomy,
					'term_id'  => $term_id,
					'term'     => $term,
				],
			];
		}

		Core\HTML::tableList( $columns, $data, [
			'title' => Core\HTML::tag( 'h3', sprintf(
				/* translators: `%1$s`: count placeholder, `%2$s`: attachment title */
				_x( '%1$s Records Found for &ldquo;%2$s&rdquo;', 'Header', 'geditorial-importer' ),
				Core\Number::format( count( $data ) ),
				get_the_title( $id )
			) ),
			'callback' => [ $this, 'form_terms_table_callback' ],
			'row_prep' => [ $this, 'form_posts_table_row_prep' ],
			'extra'    => [
				'na'            => gEditorial()->na(),
				// 'mapped'        => array_combine( $headers, $map ),
				'headers'       => $headers,
				'post_type'     => $posttype,
				'taxonomies'    => $taxonomies,
				'source_offset' => $source_offset,
				'source_key'    => $headers[$source_offset],
			],
		] );
	}

	// NOTE: only applies on columns with no `callback`
	public function form_terms_table_callback( $value, $row, $column, $index, $key, $args )
	{
		if ( empty( $row['___matched'] ) )
			return gEditorial\Helper::htmlEmpty();

		if ( ! $terms = WordPress\Taxonomy::getPostTerms( $column['args']['taxonomy'], $row['___matched'] ) )
			return gEditorial\Helper::htmlEmpty();

		echo '<ul class="-rows">';

		foreach ( $terms as $term )
			echo Core\HTML::wrap( $term->name, '-row' );

		echo '</ul>';
	}

	private function _handle_terms_import()
	{
		$count         = 0;
		$terms_all     = self::req( 'terms_all', [] );
		$posttype      = self::req( 'posttype', $this->get_setting( 'post_type', 'post' ) );
		$attach_id     = self::req( $this->classs( 'terms', 'attachment', 'selected' ), FALSE );
		$user_id       = self::req( 'user_id', $this->_get_user_id() );
		$source_offset = self::req( 'source_offset', 'none' );
		$append        = isset( $_POST['terms_import_append'] );
		$override      = isset( $_POST['terms_import_override'] );

		if ( 'none' === $source_offset )
			return FALSE;

		if ( ! $file = get_attached_file( $attach_id ) )
			return FALSE;

		$this->raise_resources();

		$parsed     = gEditorial\Parser::fromCSV( $file );
		$taxonomies = array_map( [ 'geminorum\gEditorial\Core\Arraay', 'prepNumeral' ], $terms_all );
		$source_key = $parsed['headers'][$source_offset];

		$this->actions( 'terms_before', $posttype );

		foreach ( $_POST['_cb'] as $offset ) {

			$row = $raw = $parsed['items'][$offset]; // this parser combines header data

			$this->actions( 'terms_before_each', $posttype );

			$source_id = $this->filters( 'source_id',
				( 'none' !== $source_offset && array_key_exists( $source_key, $row )
					? $row[$source_key]
					: NULL
				),
				$posttype,
				$raw
			);

			if ( ! $source_id )
				continue;

			if ( ! $matched = $this->_get_source_id_matched( $source_id, $posttype, $raw ) )
				continue;

			if ( ! $post = WordPress\Post::get( intval( $matched ) ) )
				continue;

			if ( $append )
				$this->_set_terms_for_post( $post->ID, $taxonomies, $source_id, $post, TRUE, TRUE );

			else if ( $override )
				$this->_set_terms_for_post( $post->ID, $taxonomies, $source_id, $post, TRUE, FALSE );

			else
				$this->_set_terms_for_post( $post->ID, $taxonomies, $source_id, $post, FALSE, FALSE );

			// TODO: log the changes with user id

			$this->actions( 'terms_after_each', $posttype );

			++$count;
		}

		$this->actions( 'terms_after', $posttype );

		return $count;
	}
}
