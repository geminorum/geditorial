<?php namespace geminorum\gEditorial\Modules\Importer;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class Importer extends gEditorial\Module
{
	use Internals\CoreToolBox;
	use Internals\RawImports;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'importer',
			'title'    => _x( 'Importer', 'Modules: Importer', 'geditorial' ),
			'desc'     => _x( 'Data Import Tools', 'Modules: Importer', 'geditorial' ),
			'icon'     => 'upload',
			'access'   => 'stable',
			'frontend' => FALSE,
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
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
				],
				[
					'field'       => 'store_source_data',
					'title'       => _x( 'Store Source Data', 'Setting Title', 'geditorial-importer' ),
					'description' => _x( 'Stores raw source data and attchment reference as meta for each imported item.', 'Setting Description', 'geditorial-importer' ),
				],
				'add_audit_attribute' => [
					/* translators: %s: audit attribute placeholder */
					sprintf( _x( 'Appends %s audit attribute to each imported item.', 'Setting Description', 'geditorial-importer' ),
						Core\HTML::code( $this->constant( 'term_newpost_imported' ) ) ),
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
			'metakey_source_key'    => '_importer_source_id_key',
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
				'edit' => [
					'button_title' => _x( 'Import Items', 'Javascript String', 'geditorial-importer' ),
					'button_text'  => _x( 'Import', 'Javascript String', 'geditorial-importer' ),
				],
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

	public function current_screen( $screen )
	{
		if ( 'edit' == $screen->base
			&& $this->posttype_supported( $screen->post_type ) ) {

			if ( $this->cuc( 'imports' ) )
				$this->enqueue_asset_js( [
					'strings' => $this->get_strings( $screen->base, 'js' ),
					'link'    => $this->get_imports_page_url( NULL, [ 'posttype' => $screen->post_type ] ),
				], $screen );
		}
	}

	private function _guessed_fields_map( $headers, $key = 'source_map' )
	{
		if ( ! $stored = get_option( $this->hook( $key ), [] ) )
			return [];

		$samekey = Core\Arraay::sameKey( $headers );

		foreach ( array_reverse( $stored ) as $map )
			if ( Core\Arraay::equalKeys( $samekey, $map ) )
				return array_values( $map );

		return [];
	}

	private function _store_fields_map( $file, $headers, $map, $key = 'source_map' )
	{
		$option = $this->hook( $key );
		$stored = get_option( $option, [] );

		// override the old data, if any
		// key's better to be file-name than file-path
		$stored[Core\File::basename( $file )] = array_combine( $headers, $map );

		return update_option( $option, $stored );
	}

	private function _render_posttype_taxonomies( $posttype )
	{
		$taxonomies = WordPress\Taxonomy::get( 4, [], $posttype );

		if ( empty( $taxonomies ) )
			return FALSE;

		echo '<table class="base-table-raw"><tbody>';

		foreach ( $taxonomies as $taxonomy => $object ) {

			$dropdown = wp_dropdown_categories( [
				'taxonomy'          => $taxonomy,
				'name'              => 'terms_all['.$taxonomy.']',
				'hierarchical'      => $object->hierarchical,
				'show_option_none'  => Settings::showOptionNone(),
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

		echo '</tbody></table>';

		return TRUE;
	}

	private function _form_posts_map( $id, $posttype = 'post' )
	{
		if ( ! $file = get_attached_file( $id ) )
			return FALSE;

		$this->_raise_resources();

		// https://github.com/kzykhys/PHPCsvParser
		$iterator = new \SplFileObject( Core\File::normalize( $file ) );
		$parser   = new \KzykHys\CsvParser\CsvParser( $iterator, [ 'encoding' => 'UTF-8' ] );
		$items    = $parser->parse();
		$headers  = $items[0];

		unset( $items[0] );

		$taxonomies = WordPress\Taxonomy::get( 4, [], $posttype );
		$fields     = $this->get_importer_fields( $posttype, $taxonomies );
		$source_key = $this->fetch_postmeta( $id, 'none', $this->constant( 'metakey_source_key' ) );
		$map        = $this->fetch_postmeta( $id, [], $this->constant( 'metakey_source_map' ) );

		if ( empty( $map ) )
			$map = $this->_guessed_fields_map( $headers );

		if ( $dups = Core\Arraay::duplicates( $headers ) )
			/* translators: %s: joined duplicate keys */
			echo Core\HTML::warning( sprintf( _x( 'Found duplicate column headers: %s', 'Message', 'geditorial-importer' ), WordPress\Strings::getJoined( $dups ) ), FALSE, 'inline' );

		echo '<table class="base-table-raw"><tbody>';

		echo '<tr><td><strong>'._x( 'Source ID', 'Dropdown Label', 'geditorial-importer' );
		echo '</strong></td><td class="-sep">';

			Settings::fieldSeparate( 'from' );

		echo '</td><td>';

			echo Core\HTML::dropdown( $headers, [
				'selected'   => $source_key,
				'name'       => 'source_key',
				'class'      => '-dropdown-source-key',
				'none_title' => Settings::showOptionNone(),
				'none_value' => 'none', // `0` is offset!
			] );

		echo '</td><td>&nbsp;</td><td>&nbsp;</td><td>';

			Core\HTML::desc( _x( 'Used as Identifider of each item.', 'Description', 'geditorial-importer' ) );

		echo '</td></tr>';

		foreach ( $headers as $key => $title ) {

			echo '<tr><td class="-val"><code>'
				.Core\HTML::escape( $title )
			.'</code></td><td class="-sep">';

				Settings::fieldSeparate( 'into' );

			echo '</td><td>'
				.Core\HTML::dropdown( $fields, [
					'selected'   => array_key_exists( $key, $map ) ? $map[$key] : 'none',
					'name'       => 'field_map['.$key.']',
					'none_title' => Settings::showOptionNone(),
					'none_value' => 'none',
				] )
			.'</td><td><td class="-sep">';

				Settings::fieldSeparate( 'ex' );

			echo '</td><td class="-val"><code>'
				.Core\HTML::sanitizeDisplay( $items[1][$key] )
			.'</code></td><td class="-sep">';

				Settings::fieldSeparate( 'count' );

			echo '</td><td class="-count"><code>'
				.Helper::htmlCount( WordPress\Strings::filterEmpty( Core\Arraay::column( $items, $key ) ) )
			.'</code></td></tr>';
		}

		echo '</tbody></table>';
	}

	private function _form_posts_attached( $id = 0, $posttype = 'post', $user_id = NULL )
	{
		echo '<input id="upload_csv_button" class="button" value="'._x( 'Upload', 'Button', 'geditorial-importer' ).'" type="button" />';
		echo '<input id="upload_attach_id" type="hidden" name="upload_id" value="" />';

		Settings::fieldSeparate( 'or' );

		WordPress\Media::selectAttachment( $id, [ 'application/vnd.ms-excel', 'text/csv' ], 'attach_id', gEditorial\Plugin::na() );

		Settings::fieldSeparate( 'into' );

		echo Core\HTML::dropdown( $this->list_posttypes( NULL, NULL, 'edit_posts' ), [
			'selected' => $posttype,
			'name'     => 'posttype',
		] );

		Settings::fieldSeparate( 'as' );

		echo Core\HTML::dropdown( WordPress\User::get(), [
			'selected' => is_null( $user_id ) ? gEditorial()->user( TRUE ) : $user_id,
			'name'     => 'user_id',
			'prop'     => 'display_name',
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

		list( $posts, ) = Tablelist::getPosts( $query, [], $args['posttype'], $this->get_sub_limit_option( $this->key ) );

		return Core\HTML::tableList( [
			'_cb'   => 'ID',
			'import_image' => [
				'title'    => _x( 'Image', 'Table Column', 'geditorial-importer' ),
				'args'     => $args,
				'class'    => 'image-column',
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {

					if ( ! $id = get_post_meta( $row->ID, $column['args']['metakey'], TRUE ) )
						return Helper::htmlEmpty();

					$src = sprintf( $column['args']['template'], $id );

					return Core\HTML::tag( 'a', [
						'href'  => $src,
						'title' => $id, // get_the_title( $row ),
						'class' => 'thickbox',
					], Core\HTML::img( $src ) );
				},
			],
			'ID'    => Tablelist::columnPostID(),
			'title' => Tablelist::columnPostTitle(),
			'type'  => Tablelist::columnPostType(),
			'thumb_image' => [
				'title'    => _x( 'Thumbnail', 'Table Column', 'geditorial-importer' ),
				'class'    => 'image-column',
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {
					$html = WordPress\PostType::htmlFeaturedImage( $row->ID, [ 45, 72 ] );
					return $html ?: Helper::htmlEmpty();
				},
			],
		], $posts, [
			/* translators: %s: count placeholder */
			'title' => Core\HTML::tag( 'h3', WordPress\Strings::getCounted( count( $posts ), _x( '%s Records Found', 'Header', 'geditorial-importer' ) ) ),
			'empty' => Helper::getPostTypeLabel( $args['posttype'], 'not_found' ),
		] );
	}

	private function _form_posts_table( $id, $map = [], $posttype = 'post', $terms_all = [], $source_key = 'none' )
	{
		if ( ! $file = get_attached_file( $id ) )
			return FALSE;

		$this->_raise_resources();

		// https://github.com/kzykhys/PHPCsvParser
		$iterator = new \SplFileObject( Core\File::normalize( $file ) );
		$parser   = new \KzykHys\CsvParser\CsvParser( $iterator, [ 'encoding' => 'UTF-8' ] );

		$items   = $parser->parse();
		$headers = $items[0];

		unset( $iterator, $parser, $items[0] );

		$this->store_postmeta( $id, $map, $this->constant( 'metakey_source_map' ) );
		$this->store_postmeta( $id, ( 'none' === $source_key ? FALSE : $source_key ), $this->constant( 'metakey_source_key' ) );
		$this->_store_fields_map( $file, $headers, $map, $source_key );

		$this->_render_data_table( $id, $items, $headers, $map, $posttype, $source_key );
	}

	private function _render_data_table( $id, $data, $headers, $map = [], $posttype = 'post', $source_key = 'none' )
	{
		$taxonomies = WordPress\Taxonomy::get( 4, [], $posttype );
		$fields     = $this->get_importer_fields( $posttype, $taxonomies );

		$columns = [
			'_cb'           => '_index',
			'_check_column' => [
				'title'    => _x( '[Checks]', 'Table Column', 'geditorial-importer' ),
				'callback' => function( $value, $row, $column, $index, $key, $args ) {

					$checks = [];

					if ( $row['___source_id'] )
						/* translators: %s: source id */
						$checks[] = sprintf( _x( 'SourceID: %s', 'Checks', 'geditorial-importer' ), Core\HTML::code( $row['___source_id'] ) );

					if ( $row['___matched'] )
						/* translators: %s: post title */
						$checks[] = sprintf( _x( 'Matched: %s', 'Checks', 'geditorial-importer' ),
							Helper::getPostTitleRow( $row['___matched'], 'edit', FALSE, $row['___matched'] ) );

					if ( FALSE !== ( $title_key = array_search( 'importer_post_title', $args['extra']['mapped'] ) ) ) {
						$title = $this->filters( 'prepare',
							$row[$title_key],
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
								$html.= Helper::getPostTitleRow( $post_id, 'edit', FALSE, $post_id ).', ';

							$checks[] = trim( $html, ', ' ).'</div>';
						}
					}

					return WordPress\Strings::getJoined( $checks, '', '', Helper::htmlEmpty(), '<br />' );
				},
			],
		];

		foreach ( $map as $key => $field ) {

			if ( 'none' == $field )
				continue;

			if ( 'importer_custom_meta' == $field )
				/* translators: %s: custom metakey */
				$columns[$headers[$key]] = sprintf( _x( 'Custom: %s', 'Post Field Column', 'geditorial-importer' ), Core\HTML::code( $headers[$key] ) );

			else
				$columns[$headers[$key]] = $fields[$field];
		}

		Core\HTML::tableList( $columns, $data, [
			'title' => Core\HTML::tag( 'h3', sprintf(
				/* translators: %1$s: count placeholder, %2$s: attachment title */
				_x( '%1$s Records Found for &ldquo;%2$s&rdquo;', 'Header', 'geditorial-importer' ),
				Core\Number::format( count( $data ) ),
				get_the_title( $id )
			) ),
			'callback' => [ $this, 'form_posts_table_callback' ],
			'row_prep' => [ $this, 'form_posts_table_row_prep' ],
			'extra'    => [
				'na'         => gEditorial()->na(),
				'mapped'     => array_combine( $headers, $map ),
				'headers'    => $headers,
				'post_type'  => $posttype,
				'taxonomies' => $taxonomies,
				'source_key' => $source_key,
			],
		] );
	}

	// NOTE: combines raw data with header keys and adds source_id and matched
	public function form_posts_table_row_prep( $row, $index, $args )
	{
		// empty rows have one empty cells
		if ( count( $row ) < 2 )
			return FALSE;

		$raw       = Core\Arraay::combine( $args['extra']['headers'], $row );
		$source_id = NULL;

		if ( 'none' !== $args['extra']['source_key']
			&& \array_key_exists( $args['extra']['source_key'], $row ) )
				$source_id = $row[$args['extra']['source_key']];

		$raw['___source_id'] = $this->filters( 'source_id',
			$source_id,
			$args['extra']['post_type'],
			$raw
		);

		if ( $matched = $this->_get_source_id_matched( $raw['___source_id'], $args['extra']['post_type'], $raw ) )
			$raw['___matched'] = intval( $matched );
		else
			$raw['___matched'] = 0;

		return $raw;
	}

	// NOTE: only applies on columns with no `callback`
	public function form_posts_table_callback( $value, $row, $column, $index, $key, $args )
	{
		$filtered = $this->filters( 'prepare',
			$value,
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

				if ( Tablelist::isAction( 'images_import', TRUE )
					|| Tablelist::isAction( 'images_import_as_thumbnail', TRUE ) ) {

					$count = 0;
					$args  = $this->_get_current_form_images();

					$this->_raise_resources();

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

						$count++;
					}

					Core\WordPress::redirectReferer( [
						'message' => 'imported',
						'count'   => $count,
					] );

				} else if ( Tablelist::isAction( 'posts_import_newonly', TRUE )
					|| Tablelist::isAction( 'posts_import_override', TRUE ) ) {

					$count      = 0;
					$field_map  = self::req( 'field_map', [] );
					$terms_all  = self::req( 'terms_all', [] );
					$posttype   = self::req( 'posttype', $this->get_setting( 'post_type', 'post' ) );
					$attach_id  = self::req( 'attach_id', FALSE );
					$user_id    = self::req( 'user_id', gEditorial()->user( TRUE ) );
					$source_key = self::req( 'source_key', 'none' );
					$override   = isset( $_POST['posts_import_override'] );

					if ( ! $file = get_attached_file( $attach_id ) )
						Core\WordPress::redirectReferer( 'wrong' );

					$post_status    = $this->get_setting( 'post_status', 'pending' );
					$comment_status = $this->get_setting( 'comment_status', 'closed' );
					$all_taxonomies = WordPress\Taxonomy::get( 4, [], $posttype );

					$this->_raise_resources();

					$iterator = new \SplFileObject( Core\File::normalize( $file ) );
					$options  = [ 'encoding' => 'UTF-8', 'limit' => 1 ];
					$parser   = new \KzykHys\CsvParser\CsvParser( $iterator, $options );
					$items    = $parser->parse();
					$headers  = array_pop( $items ); // used on maping cutom meta

					unset( $parser, $items );

					// NOTE: to avoid `Content, title, and excerpt are empty.` Error on `wp_insert_post()`
					add_filter( 'wp_insert_post_empty_content', '__return_false', 12 );

					foreach ( $_POST['_cb'] as $offset ) {

						$options['offset'] = $offset;

						$parser = new \KzykHys\CsvParser\CsvParser( $iterator, $options );
						$items  = $parser->parse();
						$row    = array_pop( $items );

						$raw        = Core\Arraay::combine( $headers, $row );
						$data       = []; // [ 'tax_input' => [] ];
						$prepared   = [];
						$comments   = [];
						$taxonomies = [];
						$oldpost    = $post_id = FALSE;

						// @EXAMPLE: `$this->filter_module( 'importer', 'source_id', 3 );`
						$source_id = $this->filters( 'source_id',
							( 'none' !== $source_key && array_key_exists( $source_key, $row )
								? $row[$source_key]
								: NULL
							),
							$posttype,
							$raw
						);

						if ( $matched = $this->_get_source_id_matched( $source_id, $posttype, $raw ) )
							if ( $oldpost = WordPress\Post::get( intval( $matched ) ) )
								$data['ID'] = $oldpost->ID;

						unset( $parser, $items );

						foreach ( $field_map as $offsetkey => $field ) {

							if ( 'none' == $field )
								continue;

							$value = $this->filters( 'prepare',
								$row[$offsetkey],
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

							if ( WordPress\Strings::isEmpty( $value ) && '' !== trim( $value ) )
								continue;

							if ( $value && $field == 'importer_post_title' && $this->get_setting( 'skip_same_title' ) ) {

								$posts = WordPress\Post::getByTitle( $value, $posttype );

								if ( ! empty( $posts ) )
									continue 2;
							}

							switch ( $field ) {

								case 'importer_custom_meta':

									if ( $custom_metakey = $this->filters( 'custom_metakey', $headers[$offsetkey], $posttype, $field, $raw, $all_taxonomies ) )
										$data['meta_input'][$custom_metakey] = $prepared[sprintf( '%s__%s', $field, $custom_metakey )] = $value;

									continue 2;

								case 'importer_menu_order':

									if ( $override || ! $oldpost || ( $oldpost && '' == $oldpost->menu_order ) )
										$data['menu_order'] = $prepared[$field] = $value;

									continue 2;

								case 'importer_post_title':

									if ( $override || ! $oldpost || ( $oldpost && '' == $oldpost->post_title ) )
										$data['post_title'] = $prepared[$field] = $value;

									continue 2;

								case 'importer_post_content':

									if ( $override || ! $oldpost || ( $oldpost && '' == $oldpost->post_content ) )
										$data['post_content'] = $prepared[$field] = $value;

									continue 2;

								case 'importer_post_excerpt':

									if ( $override || ! $oldpost || ( $oldpost && '' == $oldpost->post_excerpt ) )
										$data['post_excerpt'] = $prepared[$field] = $value;

									continue 2;

								case 'importer_comment_content':

									// NOTE: comments have no overrides!
									if ( ! WordPress\Strings::isEmpty( $value ) )
										$comments[] = [ 'comment_content' => $value ];

									$prepared[$field] = $value;
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

							$post_id = wp_insert_post( $insert, TRUE );

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

							$post_id = wp_insert_post( $insert, TRUE );
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
						$this->_store_taxonomies_for_post( $post_id, $taxonomies, $source_id, $override );

						foreach ( $terms_all as $taxonomy => $term_id ) {

							if ( ! $taxonomy || ! $term_id )
								continue;

							wp_set_object_terms( $post_id, Core\Arraay::prepNumeral( $term_id ), $taxonomy, TRUE );
						}

						if ( FALSE !== ( $comments = $this->filters( 'comments', $comments, $data, $prepared, $posttype, $source_id, $attach_id, $raw ) ) ) {

							foreach ( $comments as $comment ) {

								if ( empty( $comment ) )
									continue;

								if ( empty( $comment['comment_post_ID'] ) )
									$comment['comment_post_ID'] = $post_id;

								if ( empty( $comment['user_id'] ) )
									$comment['user_id'] = $user_id;

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
							'raw'        => $raw,
							'map'        => array_combine( $headers, $field_map ),
							'source_id'  => $source_id,
							'attach_id'  => $attach_id,
							'terms_all'  => $terms_all,
							'taxonomies' => $taxonomies,
							'override'   => $override,
							'oldpost'    => $oldpost,
						] );

						$count++;
					}

					remove_filter( 'wp_insert_post_empty_content', '__return_false', 12 );
					unset( $iterator );

					Core\WordPress::redirectReferer( [
						'message' => 'imported',
						'count'   => $count,
					] );
				}
			}

			Scripts::enqueueThickBox();
			wp_enqueue_media();

			$this->enqueue_asset_js( [
				'strings' => $this->get_strings( 'media', 'js' ),
			], $this->dotted( 'media' ), [ 'jquery', 'media-upload' ] );
		}
	}

	protected function render_imports_html( $uri, $sub )
	{
		$for    = self::req( 'imports_for' );
		$images = 'images' == $for || self::req( 'images_step_two' );
		$posts  = 'posts' == $for || self::req( 'posts_step_two' );
		$first  = ! $for && ! $images && ! $posts;

		if ( $first )
			Core\HTML::h3( _x( 'Importer Tools', 'Header', 'geditorial-importer' ) );

		if ( ! count( $this->posttypes() ) )
			return Core\HTML::desc( _x( 'Imports are not supported for any of the post-types!', 'Message', 'geditorial-importer' ) );

		if ( $first )
			echo Core\HTML::tag( 'h4', _x( 'Import Data from CSV into Posts', 'Header', 'geditorial-importer' ) );

		if ( $first || $posts )
			$this->_render_imports_for_posts();

		if ( $first )
			echo '<br /><hr />'.Core\HTML::tag( 'h4', _x( 'Import Remote Files as Attachments', 'Header', 'geditorial-importer' ) );

		if ( $first || $images )
			$this->_render_imports_for_images();

		// TODO: `_render_imports_for_files()`
		// --- import data from directory of files into fields: like excerpt
		// - attach file from directory of files into posts with field data to rename

		// TODO: `_render_imports_for_metas()`
		// - import data by metakey + support types: string/int/comma separated
	}

	private function _render_imports_for_posts()
	{
		$field_map  = self::req( 'field_map', [] );
		$terms_all  = self::req( 'terms_all', [] );
		$posttype   = self::req( 'posttype', $this->get_setting( 'post_type', 'post' ) );
		$upload_id  = self::req( 'upload_id', FALSE );
		$attach_id  = self::req( 'attach_id', FALSE );
		$user_id    = self::req( 'user_id', gEditorial()->user( TRUE ) );
		$source_key = self::req( 'source_key', 'none' );

		if ( $upload_id )
			$attach_id = $upload_id;

		if ( isset( $_POST['posts_step_four'] ) ) {

			if ( ! $attach_id )
				return Core\HTML::desc( _x( 'Import source is not defined!', 'Message', 'geditorial-importer' ) );

			if ( ! WordPress\PostType::can( $posttype, 'edit_posts' ) )
				return Core\HTML::desc( _x( 'You are not allowed to edit this post-type!', 'Message', 'geditorial-importer' ) );

			Core\HTML::inputHiddenArray( $field_map, 'field_map' );
			Core\HTML::inputHiddenArray( $terms_all, 'terms_all' );
			Core\HTML::inputHidden( 'posttype', $posttype );
			Core\HTML::inputHidden( 'attach_id', $attach_id );
			Core\HTML::inputHidden( 'user_id', $user_id );
			Core\HTML::inputHidden( 'source_key', $source_key );
			Core\HTML::inputHidden( 'imports_for', 'posts' );

			$this->_form_posts_table( $attach_id, $field_map, $posttype, $terms_all, $source_key );

			echo $this->wrap_open_buttons();
			Settings::submitButton( 'posts_import_newonly', _x( 'Import New Data', 'Button', 'geditorial-importer' ), TRUE );
			Settings::submitButton( 'posts_import_override', _x( 'Import and Override', 'Button', 'geditorial-importer' ) );
			Core\HTML::desc( _x( 'Select records to finally import.', 'Message', 'geditorial-importer' ), FALSE );

		} else if ( isset( $_POST['posts_step_three'] ) ) {

			if ( ! $attach_id )
				return Core\HTML::desc( _x( 'Import source is not defined!', 'Message', 'geditorial-importer' ) );

			if ( ! WordPress\PostType::can( $posttype, 'edit_posts' ) )
				return Core\HTML::desc( _x( 'You are not allowed to edit this post-type!', 'Message', 'geditorial-importer' ) );

			Core\HTML::h3( sprintf(
				/* translators: %s: attachment title */
				_x( 'Terms to Append All for &ldquo;%s&rdquo;', 'Header', 'geditorial-importer' ),
				get_the_title( $attach_id )
			) );

			Core\HTML::inputHiddenArray( $field_map, 'field_map' );
			Core\HTML::inputHidden( 'posttype', $posttype );
			Core\HTML::inputHidden( 'attach_id', $attach_id );
			Core\HTML::inputHidden( 'user_id', $user_id );
			Core\HTML::inputHidden( 'source_key', $source_key );
			Core\HTML::inputHidden( 'imports_for', 'posts' );

			if ( ! $this->_render_posttype_taxonomies( $posttype ) )
				Core\HTML::desc( _x( 'No taxonomy availabe for this post-type!', 'Message', 'geditorial-importer' ) );

			echo $this->wrap_open_buttons();
			Settings::submitButton( 'posts_step_four', _x( 'Step 3: Terms', 'Button', 'geditorial-importer' ), TRUE );
			Core\HTML::desc( _x( 'Select a term from each post-type supported taxonomy to append all imported posts.', 'Message', 'geditorial-importer' ), FALSE );

		} else if ( isset( $_POST['posts_step_two'] ) ) {

			if ( ! $attach_id )
				return Core\HTML::desc( _x( 'Import source is not defined!', 'Message', 'geditorial-importer' ) );

			if ( ! WordPress\PostType::can( $posttype, 'edit_posts' ) )
				return Core\HTML::desc( _x( 'You are not allowed to edit this post-type!', 'Message', 'geditorial-importer' ) );

			Core\HTML::h3( sprintf(
				/* translators: %s: attachment title */
				_x( 'Map the Importer for &ldquo;%s&rdquo;', 'Header', 'geditorial-importer' ),
				get_the_title( $attach_id )
			) );

			Core\HTML::inputHidden( 'posttype', $posttype );
			Core\HTML::inputHidden( 'attach_id', $attach_id );
			Core\HTML::inputHidden( 'imports_for', 'posts' );
			Core\HTML::inputHidden( 'user_id', $user_id );

			$this->_form_posts_map( $attach_id, $posttype );

			echo $this->wrap_open_buttons();
			Settings::submitButton( 'posts_step_three', _x( 'Step 2: Map', 'Button', 'geditorial-importer' ), TRUE );
			Core\HTML::desc( _x( 'Map the file fields to the post-type fields.', 'Message', 'geditorial-importer' ), FALSE );

		} else {

			$this->_form_posts_attached( 0, $posttype );

			echo $this->wrap_open_buttons();
			Settings::submitButton( 'posts_step_two', _x( 'Step 1: Attachment', 'Button', 'geditorial-importer' ), TRUE );
			Core\HTML::desc( _x( 'Upload or select a CSV file, post-type and user to map the import.', 'Message', 'geditorial-importer' ), FALSE );
		}

		echo '</p>';
	}

	private function _get_current_form_images()
	{
		return $this->get_current_form( [
			'user_id'  => gEditorial()->user( TRUE ),
			'posttype' => $this->get_setting( 'post_type', 'post' ),
			'metakey'  => $this->constant( 'metakey_source_id' ),
			'template' => $this->filters( 'images_default_template', '' ), // FIXME: get default from settings
		], 'forimages' );
	}

	private function _render_imports_for_images()
	{
		if ( ! current_user_can( 'upload_files' ) )
			return Core\HTML::desc( _x( 'You are not allowed to upload files!', 'Message', 'geditorial-importer' ) );

		$args = $this->_get_current_form_images();

		if ( isset( $_POST['images_step_two'] )  ) {

			if ( empty( $args['metakey'] ) )
				return Core\HTML::desc( _x( 'Refrence meta-key is not defined!', 'Message', 'geditorial-importer' ) );

			if ( ! WordPress\PostType::can( $args['posttype'], 'edit_posts' ) )
				return Core\HTML::desc( _x( 'You are not allowed to edit this post-type!', 'Message', 'geditorial-importer' ) );

			Core\HTML::inputHidden( 'imports_for', 'images' );

			$this->fields_current_form( $args, 'forimages' );
			$this->_form_images_table( $args );

			echo $this->wrap_open_buttons();
			Settings::submitButton( 'images_import_as_thumbnail', _x( 'Import & Set Thumbnail', 'Button', 'geditorial-importer' ), TRUE );
			Settings::submitButton( 'images_import', _x( 'Import Only', 'Button', 'geditorial-importer' ) );
			Core\HTML::desc( _x( 'Select records to finally import.', 'Message', 'geditorial-importer' ), FALSE );

		} else {

			$this->do_settings_field( [
				'type'         => 'select',
				'field'        => 'metakey',
				'values'       => WordPress\Database::getPostMetaKeys( TRUE ),
				'none_title'   => Settings::showOptionNone(),
				'default'      => $args['metakey'],
				'option_group' => 'forimages',
			] );

			Settings::fieldSeparate( 'from' );

			$this->do_settings_field( [
				'type'         => 'text',
				'field'        => 'template',
				'default'      => $args['template'],
				'placeholder'  => Core\URL::home( 'repo/%s.jpg' ),
				'dir'          => 'ltr',
				'option_group' => 'forimages',
			] );

			Settings::fieldSeparate( 'in' );

			$this->do_settings_field( [
				'type'         => 'select',
				'field'        => 'posttype',
				'values'       => $this->list_posttypes( NULL, NULL, 'edit_posts' ),
				'default'      => $args['posttype'],
				'option_group' => 'forimages',
			] );

			Settings::fieldSeparate( 'as' );

			$this->do_settings_field( [
				'type'         => 'user',
				'field'        => 'user_id',
				'default'      => $args['user_id'],
				'option_group' => 'forimages',
			] );

			echo $this->wrap_open_buttons();
			Settings::submitButton( 'images_step_two', _x( 'Step 1: Meta-key', 'Button', 'geditorial-importer' ), TRUE );
			Core\HTML::desc( _x( 'Select a meta-key for refrence on importing the attachments.', 'Message', 'geditorial-importer' ), FALSE );
		}

		echo '</p>';
	}

	public function import_memory_limit( $filtered_limit )
	{
		return -1;
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
			/* translators: %s: taxonomy name placeholder */
			$fields['importer_tax_'.$taxonomy] = sprintf( _x( 'Taxonomy: %s', 'Post Field', 'geditorial-importer' ), $taxonomy_object->labels->singular_name );

		return $this->filters( 'fields', $fields, $posttype );
	}

	public function importer_prepare( $value, $posttype, $field, $header, $raw, $source_id, $all_taxonomies )
	{
		switch ( $field ) {

			case 'importer_menu_order'     : return Core\Number::intval( $value );
			case 'importer_post_title'     : return Helper::kses( $value, 'none' );
			case 'importer_post_content'   : return Helper::kses( $value, 'html' );
			case 'importer_post_excerpt'   : return Helper::kses( $value, 'text' );
			case 'importer_custom_meta'    : return Helper::kses( $value, 'text' );
			case 'importer_comment_content': return Helper::kses( $value, 'html' );
		}

		foreach ( array_keys( $all_taxonomies ) as $taxonomy )
			if ( $field == 'importer_tax_'.$taxonomy )
				return array_filter( Helper::ksesArray( Helper::getSeparated( $value ) ) );

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
			Helper::setTaxonomyAudit( $post, $this->constant( 'term_newpost_imported' ) );
	}

	private function _get_source_id_matched( $source_id, $posttype, $raw = [] )
	{
		if ( ! $source_id || ! $this->get_setting( 'match_source_id' ) )
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

	/**
	 * Checks if given data is sutable to use on `wp_insert_post()`
	 *
	 * @param  array $data
	 * @param  bool|int $post_id
	 * @return bool $empty
	 */
	private function _check_insert_is_empty( $data, $post_id = FALSE )
	{
		unset( $data['ID'] );

		return empty( array_filter( $data ) );
	}

	private function _store_taxonomies_for_post( $post_id, $taxonomies, $source_id = NULL, $override = FALSE )
	{
		foreach ( $taxonomies as $taxonomy => $terms ) {

			if ( empty( $terms ) )
				continue;

			$result = wp_set_object_terms( $post_id, $terms, $taxonomy, ! $override );

			if ( is_wp_error( $result ) )
				$this->log( 'NOTICE', ( $source_id
					? sprintf( 'ID: %s :: %s: %s', $source_id, 'ERROR SETTING TERMS FOR TAXONOMY', $taxonomy )
					: sprintf( '%s: %s', 'ERROR SETTING TERMS FOR TAXONOMY', $taxonomy )
				) );
		}
	}

	private function _raise_resources( $count = 0 )
	{
		gEditorial()->disable_process( 'audit', 'import' );

		WordPress\Media::disableThumbnailGeneration();
		WordPress\Taxonomy::disableTermCounting();
		wp_defer_comment_counting( TRUE );

		if ( ! Core\WordPress::isDev() )
			do_action( 'qm/cease' ); // QueryMonitor: Cease data collections

		$this->raise_resources( $count, 60, 'import' );
	}
}
