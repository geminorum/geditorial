<?php namespace geminorum\gEditorial\Modules\Importer;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\File;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Database;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Strings;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\User;

class Importer extends gEditorial\Module
{

	protected $disable_no_posttypes    = TRUE;
	protected $default_audit_attribute = 'imported';

	public static function module()
	{
		return [
			'name'     => 'importer',
			'title'    => _x( 'Importer', 'Modules: Importer', 'geditorial' ),
			'desc'     => _x( 'Data Import Tools', 'Modules: Importer', 'geditorial' ),
			'icon'     => 'upload',
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
				[
					'field'       => 'add_audit_attribute',
					'title'       => _x( 'Add Audit Attribute', 'Setting Title', 'geditorial-importer' ),
					/* translators: %s: default term placeholder */
					'description' => sprintf( _x( 'Appends %s audit attribute to each imported item.', 'Setting Description', 'geditorial-importer' ), '<code>'.$this->default_audit_attribute.'</code>' ),
					'disabled'    => ! gEditorial()->enabled( 'audit' ),
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
			'metakey_source_map'  => '_importer_source_map',
			'metakey_source_data' => '_importer_source_data',
			'metakey_attach_id'   => '_importer_attachment_id',
			'metakey_source_key'  => '_importer_source_id_key',
			'metakey_source_id'   => 'import_source_id',
		];
	}

	protected function get_global_strings()
	{
		return [
			'js' => [
				'modal_title'  => _x( 'Choose a Datasheet', 'Javascript String', 'geditorial-importer' ),
				'modal_button' => _x( 'Select as Source', 'Javascript String', 'geditorial-importer' ),
			],
		];
	}

	protected function tool_box_content()
	{
		HTML::desc( _x( 'Helps with Importing contents from CSV files into any post-type, with meta support.', 'Tool Box', 'geditorial-importer' ) );
	}

	private function _guessed_fields_map( $headers, $key = 'source_map' )
	{
		if ( ! $stored = get_option( $this->hook( $key ), [] ) )
			return [];

		$samekey = Arraay::sameKey( $headers );

		foreach ( array_reverse( $stored ) as $map )
			if ( Arraay::equalKeys( $samekey, $map ) )
				return array_values( $map );

		return [];
	}

	private function _store_fields_map( $file, $headers, $map, $key = 'source_map' )
	{
		$option = $this->hook( $key );
		$stored = get_option( $option, [] );

		// override the old data, if any
		// key's better to be file-name than file-path
		$stored[File::basename( $file )] = array_combine( $headers, $map );

		return update_option( $option, $stored );
	}

	private function _render_posttype_taxonomies( $posttype )
	{
		$taxonomies = Taxonomy::get( 4, [], $posttype );

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
			echo HTML::escape( $object->labels->menu_name );
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
		$iterator = new \SplFileObject( File::normalize( $file ) );
		$parser   = new \KzykHys\CsvParser\CsvParser( $iterator, [ 'encoding' => 'UTF-8' ] );
		$items    = $parser->parse();
		$headers  = $items[0];

		unset( $items[0] );

		$taxonomies = Taxonomy::get( 4, [], $posttype );
		$fields     = $this->get_importer_fields( $posttype, $taxonomies );
		$source_key = $this->fetch_postmeta( $id, 'none', $this->constant( 'metakey_source_key' ) );
		$map        = $this->fetch_postmeta( $id, [], $this->constant( 'metakey_source_map' ) );

		if ( empty( $map ) )
			$map = $this->_guessed_fields_map( $headers );

		echo '<table class="base-table-raw"><tbody>';

		echo '<tr><td><strong>'._x( 'Source ID', 'Dropdown Label', 'geditorial-importer' );
		echo '</strong></td><td class="-sep">';

			Settings::fieldSeparate( 'from' );

		echo '</td><td>';

			echo HTML::dropdown( $headers, [
				'selected'   => $source_key,
				'name'       => 'source_key',
				'class'      => '-dropdown-source-key',
				'none_title' => Settings::showOptionNone(),
				'none_value' => 'none', // `0` is offset!
			] );

		echo '</td><td>&nbsp;</td><td>&nbsp;</td><td>';

			HTML::desc( _x( 'Used as Identifider of each item.', 'Description', 'geditorial-importer' ) );

		echo '</td></tr>';

		foreach ( $headers as $key => $title ) {

			echo '<tr><td class="-val"><code>'
				.HTML::escape( $title )
			.'</code></td><td class="-sep">';

				Settings::fieldSeparate( 'into' );

			echo '</td><td>'
				.HTML::dropdown( $fields, [
					'selected'   => array_key_exists( $key, $map ) ? $map[$key] : 'none',
					'name'       => 'field_map['.$key.']',
					'none_title' => Settings::showOptionNone(),
					'none_value' => 'none',
				] )
			.'</td><td><td class="-sep">';

				Settings::fieldSeparate( 'ex' );

			echo '</td><td class="-val"><code>'
				.HTML::sanitizeDisplay( $items[1][$key] )
			.'</code></td><td class="-sep">';

				Settings::fieldSeparate( 'count' );

			echo '</td><td class="-count"><code>'
				.Helper::htmlCount( Strings::filterEmpty( Arraay::column( $items, $key ) ) )
			.'</code></td></tr>';
		}

		echo '</tbody></table>';
	}

	private function _form_posts_attached( $id = 0, $posttype = 'post', $user_id = NULL )
	{
		echo '<input id="upload_csv_button" class="button" value="'._x( 'Upload', 'Button', 'geditorial-importer' ).'" type="button" />';
		echo '<input id="upload_attach_id" type="hidden" name="upload_id" value="" />';

		Settings::fieldSeparate( 'or' );

		Media::selectAttachment( $id, [ 'application/vnd.ms-excel', 'text/csv' ], 'attach_id', gEditorial\Plugin::na() );

		Settings::fieldSeparate( 'into' );

		echo HTML::dropdown( $this->list_posttypes( NULL, NULL, 'edit_posts' ), [
			'selected' => $posttype,
			'name'     => 'posttype',
		] );

		Settings::fieldSeparate( 'as' );

		echo HTML::dropdown( User::get(), [
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

		return HTML::tableList( [
			'_cb'   => 'ID',
			'import_image' => [
				'title'    => _x( 'Image', 'Table Column', 'geditorial-importer' ),
				'args'     => $args,
				'class'    => 'image-column',
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {

					if ( ! $id = get_post_meta( $row->ID, $column['args']['metakey'], TRUE ) )
						return Helper::htmlEmpty();

					$src = sprintf( $column['args']['template'], $id );

					return HTML::tag( 'a', [
						'href'  => $src,
						'title' => $id, // get_the_title( $row ),
						'class' => 'thickbox',
					], HTML::img( $src ) );
				},
			],
			'ID'    => Tablelist::columnPostID(),
			'title' => Tablelist::columnPostTitle(),
			'type'  => Tablelist::columnPostType(),
			'thumb_image' => [
				'title'    => _x( 'Thumbnail', 'Table Column', 'geditorial-importer' ),
				'class'    => 'image-column',
				'callback' => static function( $value, $row, $column, $index, $key, $args ) {
					$html = PostType::htmlFeaturedImage( $row->ID, [ 45, 72 ] );
					return $html ?: Helper::htmlEmpty();
				},
			],
		], $posts, [
			/* translators: %s: count placeholder */
			'title' => HTML::tag( 'h3', Strings::getCounted( count( $posts ), _x( '%s Records Found', 'Header', 'geditorial-importer' ) ) ),
			'empty' => Helper::getPostTypeLabel( $args['posttype'], 'not_found' ),
		] );
	}

	private function _form_posts_table( $id, $map = [], $posttype = 'post', $terms_all = [], $source_key = 'none' )
	{
		if ( ! $file = get_attached_file( $id ) )
			return FALSE;

		$this->_raise_resources();

		// https://github.com/kzykhys/PHPCsvParser
		$iterator = new \SplFileObject( File::normalize( $file ) );
		$parser   = new \KzykHys\CsvParser\CsvParser( $iterator, [ 'encoding' => 'UTF-8' ] );

		$items   = $parser->parse();
		$headers = $items[0];

		unset( $iterator, $parser, $items[0] );

		$this->store_postmeta( $id, $map, $this->constant( 'metakey_source_map' ) );
		$this->store_postmeta( $id, ( 'none' === $source_key ? FALSE : $source_key ), $this->constant( 'metakey_source_key' ) );
		$this->_store_fields_map( $file, $headers, $map, $source_key );

		$this->_render_data_table( $items, $headers, $map, $posttype, $source_key );
	}

	private function _render_data_table( $data, $headers, $map = [], $posttype = 'post', $source_key = 'none' )
	{
		$taxonomies = Taxonomy::get( 4, [], $posttype );
		$fields     = $this->get_importer_fields( $posttype, $taxonomies );

		$columns = [
			'_cb' => '_index',
			'_check_column' => [
				'title'    => _x( '[Checks]', 'Table Column', 'geditorial-importer' ),
				'callback' => function( $value, $row, $column, $index, $key, $args ) {

					$title_key = array_search( 'importer_post_title', $args['extra']['map'] );
					$source_id = NULL;

					if ( FALSE === $title_key )
						return Helper::htmlEmpty();

					if ( 'none' !== $args['extra']['source_key']
						&& \array_key_exists( $args['extra']['source_key'], $row ) )
							$source_id = $row[$args['extra']['source_key']];

					$title = $this->filters( 'prepare',
						$row[$title_key],
						$args['extra']['post_type'],
						'importer_post_title',
						$row,
						$this->filters( 'source_id',
							$source_id,
							$args['extra']['post_type'],
							$row,
							$args['extra']['taxonomies'],
							$args['extra']['headers']
						),
						$args['extra']['taxonomies'],
						$args['extra']['headers'][$title_key]
					);

					if ( ! $title = trim( $title ) )
						return Helper::htmlEmpty();

					$posts = PostType::getIDsByTitle( $title, [ 'post_type' => $args['extra']['post_type'] ] );

					if ( empty( $posts ) )
						return Helper::htmlEmpty();

					$html = '<div class="-danger">'._x( 'Similar to Title:', 'Table Column', 'geditorial-importer' );

					foreach ( $posts as $post_id )
						$html.= '<br />'.Helper::getPostTitleRow( $post_id ).' <code>'.$post_id.'</code>';

					return $html.'</div>';
				},
			],
		];

		foreach ( $map as $key => $field ) {

			if ( 'none' == $field )
				continue;

			if ( 'importer_custom_meta' == $field )
				/* translators: %s: custom metakey */
				$columns[$key] = sprintf( _x( 'Custom: %s', 'Post Field Column', 'geditorial-importer' ), '<code>'.$headers[$key].'</code>' );

			else
				$columns[$key] = $fields[$field];
		}

		HTML::tableList( $columns, $data, [
			/* translators: %s: count placeholder */
			'title'     => HTML::tag( 'h3', Strings::getCounted( count( $data ), _x( '%s Records Found', 'Header', 'geditorial-importer' ) ) ),
			'callback'  => [ $this, 'form_posts_table_callback' ],
			'row_prep' => [ $this, 'form_posts_table_row_prep' ],
			'extra'     => [
				'na'         => gEditorial()->na(),
				'map'        => $map,
				'headers'    => $headers,
				'post_type'  => $posttype,
				'taxonomies' => $taxonomies,
				'source_key' => $source_key,
			],
		] );
	}

	public function form_posts_table_row_prep( $row, $index, $args )
	{
		// empty rows have one empty cells
		return count( $row ) > 1 ? $row : FALSE;
	}

	public function form_posts_table_callback( $value, $row, $column, $index, $key, $args )
	{
		$source_id = NULL;

		if ( 'none' !== $args['extra']['source_key']
			&& \array_key_exists( $args['extra']['source_key'], $row ) )
				$source_id = $row[$args['extra']['source_key']];

		$filtered = $this->filters( 'prepare',
			$value,
			$args['extra']['post_type'],
			$args['extra']['map'][$key],
			$row,
			$this->filters( 'source_id',
				$source_id,
				$args['extra']['post_type'],
				$row,
				$args['extra']['taxonomies'],
				$args['extra']['headers']
			),
			$args['extra']['taxonomies'],
			$args['extra']['headers'][$key]
		);

		if ( FALSE === $filtered )
			$filtered = $args['extra']['na'];

		else if ( Strings::isEmpty( $filtered ) )
			$filtered = '';

		return HTML::sanitizeDisplay( $filtered );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			$this->filter( 'import_memory_limit' );

			add_filter( $this->hook( 'prepare' ), [ $this, 'importer_prepare' ], 9, 7 );
			add_action( $this->hook( 'saved' ), [ $this, 'importer_saved' ], 9, 8 );

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

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

						$attachment = Media::sideloadImageURL( sprintf( $args['template'], $id ), $post_id, $extra );

						if ( is_wp_error( $attachment ) ) {
							$this->log( 'NOTICE', $attachment->get_error_message() );
							continue;
						}

						if ( isset( $_POST['images_import_as_thumbnail'] ) )
							set_post_thumbnail( $post_id, $attachment );

						$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'imported',
						'count'   => $count,
					] );

				} else if ( Tablelist::isAction( 'posts_import', TRUE ) ) {

					$count      = 0;
					$field_map  = self::req( 'field_map', [] );
					$terms_all  = self::req( 'terms_all', [] );
					$posttype   = self::req( 'posttype', $this->get_setting( 'post_type', 'post' ) );
					$attach_id  = self::req( 'attach_id', FALSE );
					$user_id    = self::req( 'user_id', gEditorial()->user( TRUE ) );
					$source_key = self::req( 'source_key', 'none' );

					if ( ! $file = get_attached_file( $attach_id ) )
						WordPress::redirectReferer( 'wrong' );

					$post_status    = $this->get_setting( 'post_status', 'pending' );
					$comment_status = $this->get_setting( 'comment_status', 'closed' );
					$taxonomies     = Taxonomy::get( 4, [], $posttype );

					$this->_raise_resources();

					$iterator = new \SplFileObject( File::normalize( $file ) );
					$options  = [ 'encoding' => 'UTF-8', 'limit' => 1 ];
					$parser   = new \KzykHys\CsvParser\CsvParser( $iterator, $options );
					$items    = $parser->parse();
					$headers  = array_pop( $items ); // used on maping cutom meta

					unset( $parser, $items );

					foreach ( $_POST['_cb'] as $offset ) {

						$options['offset'] = $offset;

						$parser = new \KzykHys\CsvParser\CsvParser( $iterator, $options );
						$items  = $parser->parse();
						$raw    = array_pop( $items );

						$data      = [ 'tax_input' => [] ];
						$prepared  = [];
						$source_id = $this->filters( 'source_id',
							( 'none' !== $source_key && \array_key_exists( $source_key, $raw )
								? $raw[$source_key]
								: NULL
							),
							$posttype,
							$raw,
							$taxonomies,
							$headers
						);

						if ( $source_id && $this->get_setting( 'match_source_id' ) ) {

							$matched = PostType::getIDbyMeta( $this->constant( 'metakey_source_id' ), $source_id );

							if ( $matched && $posttype === get_post_type( intval( $matched ) ) )
								$data['ID'] = intval( $matched );
						}

						unset( $parser, $items );

						foreach ( $field_map as $key => $field ) {

							if ( 'none' == $field )
								continue;

							$value = $this->filters( 'prepare',
								$raw[$key],
								$posttype,
								$field,
								$raw,
								$source_id,
								$taxonomies,
								$headers[$key]
							);

							// filter bail-out!
							if ( FALSE === $value )
								continue;

							if ( Strings::isEmpty( $value ) && '' !== trim( $value ) )
								continue;

							if ( $value && $field == 'importer_post_title' && $this->get_setting( 'skip_same_title' ) ) {

								$posts = PostType::getIDsByTitle( $value, [ 'post_type' => $posttype ] );

								if ( ! empty( $posts ) )
									continue 2;
							}

							switch ( $field ) {

								case 'importer_custom_meta':

									if ( $custom_metakey = $this->filters( 'custom_metakey', $headers[$key], $posttype, $field, $raw, $taxonomies ) )
										$data['meta_input'][$custom_metakey] = $prepared[sprintf( '%s__%s', $field, $custom_metakey )] = $value;

									continue 2;

								case 'importer_menu_order': $data['menu_order'] = $prepared[$field] = $value; continue 2;
								case 'importer_post_title': $data['post_title'] = $prepared[$field] = $value; continue 2;
								case 'importer_post_content': $data['post_content'] = $prepared[$field] = $value; continue 2;
								case 'importer_post_excerpt': $data['post_excerpt'] = $prepared[$field] = $value; continue 2;
							}

							foreach ( $taxonomies as $taxonomy => $taxonomy_object ) {

								// skip empty values on terms
								if ( ! $value )
									break;

								if ( $field != 'importer_tax_'.$taxonomy )
									continue;

								// allows to import multiple columns for one taxonomy
								$already = array_key_exists( $taxonomy, $data['tax_input'] ) ? $data['tax_input'][$taxonomy] : [];

								if ( $taxonomy_object->hierarchical ) {

									if ( $terms = Taxonomy::insertDefaultTerms( $taxonomy, Arraay::sameKey( $value ), FALSE ) )
										$data['tax_input'][$taxonomy] = Arraay::prepNumeral( $already, wp_list_pluck( $terms, 'term_taxonomy_id' ) );

								} else {

									$data['tax_input'][$taxonomy] = Arraay::prepString( $already, $value );
								}

								$prepared[sprintf( 'taxonomy__%s', $taxonomy )] = $data['tax_input'][$taxonomy];

								continue 2;
							}

							// otherwise store prepared value
							$prepared[$field] = $value;
						}

						if ( FALSE === ( $insert = $this->filters( 'insert', $data, $prepared, $posttype, $source_id, $attach_id, $raw ) ) ) {

							$this->log( 'NOTICE', ( $source_id
								? sprintf( 'ID: %s :: %s', $source_id, 'SKIPPED BY `insert` FILTER' )
								: 'SKIPPED BY `insert` FILTER'
							) );

							continue;
						}

						// only if it's new!
						if ( empty( $insert['ID'] ) ) {

							$insert = array_merge( [
								// 'post_name'      => '', // The name (slug) for your post
								// 'ping_status'    => 'closed', //[ 'closed' | 'open' ] // Pingbacks or trackbacks allowed. Default is the option 'default_ping_status'.
								// 'post_date'      => current_time( 'mysql' ), //[ Y-m-d H:i:s ] // The time post was made.
								// 'post_parent'    => 0, // Sets the parent of the new post, if any. Default 0.
								// 'tax_input'      => [], //[ [ <taxonomy> => <array | string> ] ] // For custom taxonomies. Default empty.

								'post_type'      => $posttype,
								'post_status'    => $post_status,
								'comment_status' => $comment_status,
								'post_author'    => $user_id,
							], $insert );

							if ( $source_id )
								$insert['meta_input'][$this->constant( 'metakey_source_id' )] = $source_id;
						}

						$post_id = wp_insert_post( $insert, TRUE );

						if ( is_wp_error( $post_id ) ) {

							$this->log( 'NOTICE', ( $source_id
								? sprintf( 'ID: %s :: %s', $source_id, $post_id->get_error_message() )
								: $post_id->get_error_message()
							) );

							continue;
						}

						foreach ( $terms_all as $taxonomy => $term_id ) {

							if ( ! $taxonomy || ! $term_id )
								continue;

							wp_set_object_terms( $post_id, Arraay::prepNumeral( $term_id ), $taxonomy, TRUE );
						}

						$this->actions( 'saved', PostType::getPost( $post_id ), $insert, $prepared, $field_map, $source_id, $attach_id, $terms_all, $raw );

						$count++;
					}

					unset( $iterator );

					WordPress::redirectReferer( [
						'message' => 'imported',
						'count'   => $count,
					] );
				}
			}

			Scripts::enqueueThickBox();
			wp_enqueue_media();

			$this->enqueue_asset_js( [
				'strings' => $this->strings['js'],
			], $this->key.'.media', [ 'jquery', 'media-upload' ] );
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		$for    = self::req( 'tools_for' );
		$images = 'images' == $for || self::req( 'images_step_two' );
		$posts  = 'posts' == $for || self::req( 'posts_step_two' );
		$first  = ! $for && ! $images && ! $posts;

		if ( $first )
			HTML::h3( _x( 'Importer Tools', 'Header', 'geditorial-importer' ) );

		if ( ! count( $this->posttypes() ) )
			return HTML::desc( _x( 'Imports are not supported for any of the post-types!', 'Message', 'geditorial-importer' ) );

		if ( $first )
			echo HTML::tag( 'h4', _x( 'Import Data from CSV into Posts', 'Header', 'geditorial-importer' ) );

		if ( $first || $posts )
			$this->_render_tools_for_posts();

		if ( $first )
			echo '<br /><hr />'.HTML::tag( 'h4', _x( 'Import Remote Files as Attachments', 'Header', 'geditorial-importer' ) );

		if ( $first || $images )
			$this->_render_tools_for_images();

		// TODO: `_render_tools_for_files()`
		// --- import data from directory of files into fields: like excerpt
		// - attach file from directory of files into posts with field data to rename

		// TODO: `_render_tools_for_metas()`
		// - import data by metakey + support types: string/int/comma seperated
	}

	private function _render_tools_for_posts()
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
				return HTML::desc( _x( 'Import source is not defined!', 'Message', 'geditorial-importer' ) );

			if ( ! PostType::can( $posttype, 'edit_posts' ) )
				return HTML::desc( _x( 'You are not allowed to edit this post-type!', 'Message', 'geditorial-importer' ) );

			HTML::inputHiddenArray( $field_map, 'field_map' );
			HTML::inputHiddenArray( $terms_all, 'terms_all' );
			HTML::inputHidden( 'posttype', $posttype );
			HTML::inputHidden( 'attach_id', $attach_id );
			HTML::inputHidden( 'user_id', $user_id );
			HTML::inputHidden( 'source_key', $source_key );
			HTML::inputHidden( 'tools_for', 'posts' );

			$this->_form_posts_table( $attach_id, $field_map, $posttype, $terms_all, $source_key );

			echo $this->wrap_open_buttons();
			Settings::submitButton( 'posts_import', _x( 'Import', 'Button', 'geditorial-importer' ), TRUE );
			HTML::desc( _x( 'Select records to finally import.', 'Message', 'geditorial-importer' ), FALSE );

		} else if ( isset( $_POST['posts_step_three'] ) ) {

			if ( ! $attach_id )
				return HTML::desc( _x( 'Import source is not defined!', 'Message', 'geditorial-importer' ) );

			if ( ! PostType::can( $posttype, 'edit_posts' ) )
				return HTML::desc( _x( 'You are not allowed to edit this post-type!', 'Message', 'geditorial-importer' ) );

			HTML::h3( _x( 'Terms to Append All', 'Header', 'geditorial-importer' ) );

			HTML::inputHiddenArray( $field_map, 'field_map' );
			HTML::inputHidden( 'posttype', $posttype );
			HTML::inputHidden( 'attach_id', $attach_id );
			HTML::inputHidden( 'user_id', $user_id );
			HTML::inputHidden( 'source_key', $source_key );
			HTML::inputHidden( 'tools_for', 'posts' );

			if ( ! $this->_render_posttype_taxonomies( $posttype ) )
				HTML::desc( _x( 'No taxonomy availabe for this post-type!', 'Message', 'geditorial-importer' ) );

			echo $this->wrap_open_buttons();
			Settings::submitButton( 'posts_step_four', _x( 'Step 3: Terms', 'Button', 'geditorial-importer' ), TRUE );
			HTML::desc( _x( 'Select a term from each post-type supported taxonomy to append all imported posts.', 'Message', 'geditorial-importer' ), FALSE );

		} else if ( isset( $_POST['posts_step_two'] ) ) {

			if ( ! $attach_id )
				return HTML::desc( _x( 'Import source is not defined!', 'Message', 'geditorial-importer' ) );

			if ( ! PostType::can( $posttype, 'edit_posts' ) )
				return HTML::desc( _x( 'You are not allowed to edit this post-type!', 'Message', 'geditorial-importer' ) );

			HTML::h3( _x( 'Map the Importer', 'Header', 'geditorial-importer' ) );

			HTML::inputHidden( 'posttype', $posttype );
			HTML::inputHidden( 'attach_id', $attach_id );
			HTML::inputHidden( 'user_id', $user_id );
			HTML::inputHidden( 'tools_for', 'posts' );

			$this->_form_posts_map( $attach_id, $posttype );

			echo $this->wrap_open_buttons();
			Settings::submitButton( 'posts_step_three', _x( 'Step 2: Map', 'Button', 'geditorial-importer' ), TRUE );
			HTML::desc( _x( 'Map the file fields to the post-type fields.', 'Message', 'geditorial-importer' ), FALSE );

		} else {

			$this->_form_posts_attached( 0, $posttype );

			echo $this->wrap_open_buttons();
			Settings::submitButton( 'posts_step_two', _x( 'Step 1: Attachment', 'Button', 'geditorial-importer' ), TRUE );
			HTML::desc( _x( 'Upload or select a CSV file, post-type and user to map the import.', 'Message', 'geditorial-importer' ), FALSE );
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

	private function _render_tools_for_images()
	{
		if ( ! current_user_can( 'upload_files' ) )
			return HTML::desc( _x( 'You are not allowed to upload files!', 'Message', 'geditorial-importer' ) );

		$args = $this->_get_current_form_images();

		if ( isset( $_POST['images_step_two'] )  ) {

			if ( empty( $args['metakey'] ) )
				return HTML::desc( _x( 'Refrence meta-key is not defined!', 'Message', 'geditorial-importer' ) );

			if ( ! PostType::can( $args['posttype'], 'edit_posts' ) )
				return HTML::desc( _x( 'You are not allowed to edit this post-type!', 'Message', 'geditorial-importer' ) );

			HTML::inputHidden( 'tools_for', 'images' );

			$this->fields_current_form( $args, 'forimages' );
			$this->_form_images_table( $args );

			echo $this->wrap_open_buttons();
			Settings::submitButton( 'images_import_as_thumbnail', _x( 'Import & Set Thumbnail', 'Button', 'geditorial-importer' ), TRUE );
			Settings::submitButton( 'images_import', _x( 'Import Only', 'Button', 'geditorial-importer' ) );
			HTML::desc( _x( 'Select records to finally import.', 'Message', 'geditorial-importer' ), FALSE );

		} else {

			$this->do_settings_field( [
				'type'         => 'select',
				'field'        => 'metakey',
				'values'       => Database::getPostMetaKeys( TRUE ),
				'none_title'   => Settings::showOptionNone(),
				'default'      => $args['metakey'],
				'option_group' => 'forimages',
			] );

			Settings::fieldSeparate( 'from' );

			$this->do_settings_field( [
				'type'         => 'text',
				'field'        => 'template',
				'default'      => $args['template'],
				'placeholder'  => URL::home( 'repo/%s.jpg' ),
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
			HTML::desc( _x( 'Select a meta-key for refrence on importing the attachments.', 'Message', 'geditorial-importer' ), FALSE );
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
			'importer_custom_meta'  => _x( 'Extra: Custom Meta', 'Post Field', 'geditorial-importer' ),
			'importer_menu_order'   => _x( 'Menu Order', 'Post Field', 'geditorial-importer' ),
			'importer_post_title'   => _x( 'Post Title', 'Post Field', 'geditorial-importer' ),
			'importer_post_content' => _x( 'Post Content', 'Post Field', 'geditorial-importer' ),
			'importer_post_excerpt' => _x( 'Post Excerpt', 'Post Field', 'geditorial-importer' ),
		];

		foreach ( (array) $taxonomies as $taxonomy => $taxonomy_object )
			/* translators: %s: taxonomy name placeholder */
			$fields['importer_tax_'.$taxonomy] = sprintf( _x( 'Taxonomy: %s', 'Post Field', 'geditorial-importer' ), $taxonomy_object->labels->singular_name );

		return $this->filters( 'fields', $fields, $posttype );
	}

	public function importer_prepare( $value, $posttype, $field, $raw, $source_id, $taxonomies, $key )
	{
		switch ( $field ) {

			case 'importer_menu_order'  : return Number::intval( $value );
			case 'importer_post_title'  : return Helper::kses( $value, 'none' );
			case 'importer_post_content': return Helper::kses( $value, 'html' );
			case 'importer_post_excerpt': return Helper::kses( $value, 'text' );
			case 'importer_custom_meta' : return Helper::kses( $value, 'text' );
		}

		foreach ( (array) $taxonomies as $taxonomy => $taxonomy_object )
			if ( $field == 'importer_tax_'.$taxonomy )
				return array_filter( Helper::ksesArray( Strings::getSeparated( $value ) ) );

		return $value;
	}

	public function importer_saved( $post, $data, $prepared, $field_map, $source_id, $attach_id, $terms_all, $raw )
	{
		if ( $this->get_setting( 'store_source_data' ) ) {
			update_post_meta( $post->ID, $this->constant( 'metakey_source_data' ), $raw );
			update_post_meta( $post->ID, $this->constant( 'metakey_attach_id' ), $attach_id );
		}

		if ( $this->get_setting( 'add_audit_attribute' ) )
			Helper::setTaxonomyAudit( $post, $this->default_audit_attribute );
	}

	private function _raise_resources( $count = 0 )
	{
		gEditorial()->disable_process( 'audit', 'import' );

		Media::disableThumbnailGeneration();
		Taxonomy::disableTermCounting();
		wp_defer_comment_counting( TRUE );

		if ( ! WordPress::isDev() )
			do_action( 'qm/cease' ); // QueryMonitor: Cease data collections

		$this->raise_resources( $count, 60, 'import' );
	}
}
