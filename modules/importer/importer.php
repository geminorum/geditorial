<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\File;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\User;

class Importer extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

	protected $default_audit_attribute = 'imported';

	public $meta_key = '_geditorial_importer';

	public static function module()
	{
		return [
			'name'     => 'importer',
			'title'    => _x( 'Importer', 'Modules: Importer', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Import and Export Tools', 'Modules: Importer', GEDITORIAL_TEXTDOMAIN ),
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
					'field'       => 'store_source_data',
					'title'       => _x( 'Store Source Data', 'Modules: Importer: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Stores raw source data and attchment reference as meta for each imported item.', 'Modules: Importer: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'add_audit_attribute',
					'title'       => _x( 'Add Audit Attribute', 'Modules: Importer: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => sprintf( _x( 'Appends %s audit attribute to each imported item.', 'Modules: Importer: Setting Description', GEDITORIAL_TEXTDOMAIN ), '<code>'.$this->default_audit_attribute.'</code>' ),
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
			'metakey_source_data' => '_importer_source_data',
			'metakey_attach_id'   => '_importer_attachment_id',
		];
	}

	protected function get_global_strings()
	{
		return [
			'js' => [
				'modal_title' => _x( 'Choose a Datasheet', 'Modules: Importer: Javascript String', GEDITORIAL_TEXTDOMAIN ),
				'modal_button' => _x( 'Select as Source', 'Modules: Importer: Javascript String', GEDITORIAL_TEXTDOMAIN ),
			],
		];
	}

	protected function form_map( $id, $posttype = 'post' )
	{
		if ( ! $file = get_attached_file( $id ) )
			return FALSE;

		// https://github.com/kzykhys/PHPCsvParser
		$iterator = new \SplFileObject( File::normalize( $file ) );
		$parser   = new \KzykHys\CsvParser\CsvParser( $iterator, [ 'encoding' => 'UTF-8', 'limit' => 1 ] );
		$items    = $parser->parse();

		$map    = $this->get_postmeta( $id, FALSE, [], $this->meta_key.'_map' );
		$fields = $this->get_importer_fields( $posttype );

		echo '<table class="base-table-raw"><tbody>';

		foreach ( $items[0] as $key => $title )
			echo '<tr><td class="-val"><code>'
				.HTML::escape( $title )
			.'</td><td></code>'
				.HTML::dropdown( $fields, [
					'selected' => array_key_exists( $key, $map ) ? $map[$key] : 'none',
					'name'     => 'field_map['.$key.']',
				] )
			.'</td></tr>';

		echo '</tbody></table>';
	}

	protected function from_attached( $id = 0, $posttype = 'post', $user_id = NULL )
	{
		echo '<input id="upload_csv_button" class="button" value="'._x( 'Upload', 'Modules: Importer: Button', GEDITORIAL_TEXTDOMAIN ).'" type="button" />';
		echo '<input id="upload_attach_id" type="hidden" name="upload_id" value="" />';

		Settings::fieldSeparate( 'or' );

		Media::selectAttachment( $id, [ 'application/vnd.ms-excel', 'text/csv' ], 'attach_id', gEditorial\Plugin::na() );

		Settings::fieldSeparate( 'into' );

		echo HTML::dropdown( $this->list_posttypes(), [
			'selected' => $posttype,
			'name'     => 'posttype',
		] );

		Settings::fieldSeparate( 'as' );

		echo HTML::dropdown( User::get(), [
			'selected' => is_null( $user_id ) ? gEditorial()->user( TRUE ) : $user_id,
			'name'     => 'user_id',
			'prop'     => 'display_name',
		] );
	}

	protected function form_table( $id, $map = [], $posttype = 'post' )
	{
		if ( ! $file = get_attached_file( $id ) )
			return FALSE;

		// https://github.com/kzykhys/PHPCsvParser
		$iterator = new \SplFileObject( File::normalize( $file ) );
		$parser   = new \KzykHys\CsvParser\CsvParser( $iterator, [ 'encoding' => 'UTF-8' ] );
		$items    = $parser->parse();

		unset( $iterator, $parser, $items[0] );

		$this->data_table( $items, $map, $posttype );

		$this->set_meta( $id, $map, '_map' );
	}

	private function data_table( $data, $map = [], $posttype = 'post' )
	{
		$fields   = $this->get_importer_fields( $posttype );
		$selected = array_flip( Arraay::stripByValue( $map, 'none' ) );
		$columns  = array_intersect_key( $fields, $selected );

		$pre = [
			'_cb' => '_index',
			'_check_column' => [
				'title'    => _x( '[Checks]', 'Modules: Importer: Table Column', GEDITORIAL_TEXTDOMAIN ),
				'args'     => [ 'map' => $selected ],
				'callback' => function( $value, $row, $column, $index ){
					if ( ! array_key_exists( 'importer_post_title', $column['args']['map'] ) )
						return '&mdash;';

					if ( ! $title = trim( $row[$column['args']['map']['importer_post_title']] ) )
						return '&mdash;';

					$posts = PostType::getIDsByTitle( $title );

					if ( empty( $posts ) )
						return '&mdash;';

					$html = '<div class="-danger">'._x( 'Similar to Title:', 'Modules: Importer: Table Column', GEDITORIAL_TEXTDOMAIN );

					foreach ( $posts as $post_id )
						$html.= '<br />'.Helper::getPostTitleRow( $post_id ).' <code>'.$post_id.'</code>';

					return $html.'</div>';
				},
			],
		];

		HTML::tableList( $pre + $columns, $data, [
			'map'      => $selected,
			'check'    => [ $this, 'form_table_check' ],
			'callback' => [ $this, 'form_table_callback' ],
			'extra'    => [ 'post_type' => $posttype ],
			'title'    => HTML::tag( 'h3', Helper::getCounted( count( $data ), _x( '%s Records Found', 'Modules: Importer', GEDITORIAL_TEXTDOMAIN ) ) ),
		] );
	}

	public function form_table_check( $row, $index, $args )
	{
		return count( $row ) > 1; // empty rows have one empty cells
	}

	public function form_table_callback( $value, $row, $column, $index, $key, $args )
	{
		return HTML::sanitizeDisplay( $this->filters( 'prepare', $value, $args['extra']['post_type'], array_search( $key, $args['map'] ), $row ) );
	}

	// no need / not used
	protected function form_check( $selected, $id, $map = [], $posttype = 'post' )
	{
		if ( ! $file = get_attached_file( $id ) )
			return FALSE;

		$data = [];

		// https://github.com/kzykhys/PHPCsvParser
		$iterator = new \SplFileObject( File::normalize( $file ) );

		foreach ( $selected as $offset ) {

			$options = [
				'encoding' => 'UTF-8',
				'offset'   => $offset,
				'limit'    => 1,
			];

			$parser = new \KzykHys\CsvParser\CsvParser( $iterator, $options );
			$items  = $parser->parse();

			$data[$offset] = array_pop( $items );

			unset( $parser, $items );
		}

		unset( $iterator );

		$this->data_table( $data, $map, $posttype );
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			add_filter( $this->hook( 'prepare' ), [ $this, 'importer_prepare' ], 9, 4 );
			add_action( $this->hook( 'saved' ), [ $this, 'importer_saved' ], 9, 5 );

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( isset( $_POST['csv_import'] ) ) {

					$count     = 0;
					$selected  = self::req( '_cb', [] );
					$field_map = self::req( 'field_map', [] );
					$posttype = self::req( 'posttype', $this->get_setting( 'post_type', 'post' ) );
					$attach_id = self::req( 'attach_id', FALSE );
					$user_id   = self::req( 'user_id', gEditorial()->user( TRUE ) );

					if ( ! $file = get_attached_file( $attach_id ) )
						WordPress::redirectReferer( 'wrong' );

					$post_status    = $this->get_setting( 'post_status', 'pending' );
					$comment_status = $this->get_setting( 'comment_status', 'closed' );

					$iterator = new \SplFileObject( File::normalize( $file ) );

					foreach ( $selected as $offset ) {

						$options = [
							'encoding' => 'UTF-8',
							'offset'   => $offset,
							'limit'    => 1,
						];

						$parser = new \KzykHys\CsvParser\CsvParser( $iterator, $options );
						$items  = $parser->parse();
						$raw    = array_pop( $items );

						unset( $parser, $items );

						$data = [
							// 'post_name'      => '', // The name (slug) for your post
							// 'ping_status'    => 'closed', //[ 'closed' | 'open' ] // Pingbacks or trackbacks allowed. Default is the option 'default_ping_status'.
							// 'post_date'      => current_time( 'mysql' ), //[ Y-m-d H:i:s ] // The time post was made.
							// 'post_parent'    => 0, // Sets the parent of the new post, if any. Default 0.
							// 'tax_input'      => [], //[ [ <taxonomy> => <array | string> ] ] // For custom taxonomies. Default empty.

							'post_type'      => $posttype,
							'post_status'    => $post_status,
							'comment_status' => $comment_status,
							'post_author'    => $user_id,
						];

						foreach ( $field_map as $key => $field )	{

							$value = $this->filters( 'prepare', $raw[$key], $posttype, $field, $raw );

							switch ( $field ) {

								case 'importer_menu_order': $data['menu_order'] = $value; break;
								case 'importer_post_title': $data['post_title'] = $value; break;
								case 'importer_post_content': $data['post_content'] = $value; break;
								case 'importer_post_excerpt': $data['post_excerpt'] = $value; break;

								case 'importer_post_cats': $data['tax_input']['category'] = (array) $value; break;
								case 'importer_post_tags': $data['tax_input']['post_tag'] = (array) $value; break;
							}
						}

						$post_id = wp_insert_post( $data, TRUE );

						if ( is_wp_error( $post_id ) )
							continue;

						$this->actions( 'saved', get_post( $post_id ), $data, $raw, $field_map, $attach_id );

						$count++;
					}

					unset( $iterator );

					WordPress::redirectReferer( [
						'message' => 'imported',
						'count'   => $count,
					] );
				}
			}

			wp_enqueue_media();

			$this->enqueue_asset_js( [
				'strings' => $this->strings['js'],
			], $this->key.'.media', [ 'jquery', 'media-upload' ] );
		}
	}

	public function tools_sub( $uri, $sub )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'tools', FALSE );

		$selected  = self::req( '_cb', [] );
		$field_map = self::req( 'field_map', [] );
		$posttype = self::req( 'posttype', $this->get_setting( 'post_type', 'post' ) );
		$upload_id = self::req( 'upload_id', FALSE );
		$attach_id = self::req( 'attach_id', FALSE );
		$user_id   = self::req( 'user_id', gEditorial()->user( TRUE ) );

		if ( $upload_id )
			$attach_id = $upload_id;

		// no need /  not used
		if ( isset( $_POST['csv_step_four'] ) && $attach_id ) {

			HTML::inputHiddenArray( $selected, '_cb' );
			HTML::inputHiddenArray( $field_map, 'field_map' );
			HTML::inputHidden( 'posttype', $posttype );
			HTML::inputHidden( 'attach_id', $attach_id );
			HTML::inputHidden( 'user_id', $user_id );

			$this->form_check( $selected, $attach_id, $field_map, $posttype );

			echo $this->wrap_open_buttons();

			Settings::submitButton( 'csv_import',
				_x( 'Step 4: Import', 'Modules: Importer: Setting Button', GEDITORIAL_TEXTDOMAIN ), TRUE );

			HTML::desc( _x( 'Check processed records and import, finally.', 'Modules: Importer', GEDITORIAL_TEXTDOMAIN ), FALSE );

		} else if ( isset( $_POST['csv_step_three'] ) && $attach_id ) {

			HTML::inputHiddenArray( $field_map, 'field_map' );
			HTML::inputHidden( 'posttype', $posttype );
			HTML::inputHidden( 'attach_id', $attach_id );
			HTML::inputHidden( 'user_id', $user_id );

			$this->form_table( $attach_id, $field_map, $posttype );

			echo $this->wrap_open_buttons();

			// Settings::submitButton( 'csv_step_four',
			// 	_x( 'Step 3: Select', 'Modules: Importer: Setting Button', GEDITORIAL_TEXTDOMAIN ), TRUE );

			// HTML::desc( _x( 'Select records to check the process.', 'Modules: Importer', GEDITORIAL_TEXTDOMAIN ), FALSE );

			Settings::submitButton( 'csv_import', _x( 'Import', 'Modules: Importer: Setting Button', GEDITORIAL_TEXTDOMAIN ), TRUE );

			HTML::desc( _x( 'Select records to finally import.', 'Modules: Importer', GEDITORIAL_TEXTDOMAIN ), FALSE );

		} else if ( isset( $_POST['csv_step_two'] ) && $attach_id ) {

			HTML::h3( _x( 'Map the Importer', 'Modules: Importer', GEDITORIAL_TEXTDOMAIN ) );

			HTML::inputHidden( 'posttype', $posttype );
			HTML::inputHidden( 'attach_id', $attach_id );
			HTML::inputHidden( 'user_id', $user_id );

			$this->form_map( $attach_id, $posttype );

			echo $this->wrap_open_buttons();

			Settings::submitButton( 'csv_step_three',
				_x( 'Step 2: Map', 'Modules: Importer: Setting Button', GEDITORIAL_TEXTDOMAIN ), TRUE );

			HTML::desc( _x( 'Map the file fields to the post-type fields.', 'Modules: Importer', GEDITORIAL_TEXTDOMAIN ), FALSE );

		} else if ( count( $this->posttypes() ) ) {

			HTML::h3( _x( 'Importer Tools', 'Modules: Importer', GEDITORIAL_TEXTDOMAIN ) );

			$this->from_attached( 0, $posttype );

			echo $this->wrap_open_buttons();

			Settings::submitButton( 'csv_step_two',
				_x( 'Step 1: Attachment', 'Modules: Importer: Setting Button', GEDITORIAL_TEXTDOMAIN ), TRUE );

			HTML::desc( _x( 'Upload or select a CSV file, post-type and user to map the import.', 'Modules: Importer', GEDITORIAL_TEXTDOMAIN ), FALSE );

		} else {
			HTML::desc( _x( 'Imports are not supported for any of the post-types!', 'Modules: Importer', GEDITORIAL_TEXTDOMAIN ), FALSE );
		}

		echo '</p>';

		$this->render_form_end( $uri, $sub );
	}

	public function get_importer_fields( $posttype = NULL )
	{
		return $this->filters( 'fields', [
			'none'                  => Settings::showOptionNone(),
			'importer_menu_order'   => _x( 'Menu Order', 'Modules: Importer: Post Field', GEDITORIAL_TEXTDOMAIN ),
			'importer_post_title'   => _x( 'Post Title', 'Modules: Importer: Post Field', GEDITORIAL_TEXTDOMAIN ),
			'importer_post_content' => _x( 'Post Content', 'Modules: Importer: Post Field', GEDITORIAL_TEXTDOMAIN ),
			'importer_post_excerpt' => _x( 'Post Excerpt', 'Modules: Importer: Post Field', GEDITORIAL_TEXTDOMAIN ),
			'importer_post_cats'    => _x( 'Post Category', 'Modules: Importer: Post Field', GEDITORIAL_TEXTDOMAIN ),
			'importer_post_tags'    => _x( 'Post Tags', 'Modules: Importer: Post Field', GEDITORIAL_TEXTDOMAIN ),
			// 'importer_post_author'  => _x( 'Post Author', 'Modules: Importer: Post Field', GEDITORIAL_TEXTDOMAIN ),
			// 'importer_post_status'  => _x( 'Post Status', 'Modules: Importer: Post Field', GEDITORIAL_TEXTDOMAIN ),
		], $posttype );
	}

	public function importer_prepare( $value, $posttype, $field, $raw )
	{
		switch ( $field ) {

			case 'importer_menu_order': return Number::intval( $value );
			case 'importer_post_title': return Helper::kses( $value, 'none' );
			case 'importer_post_content': return Helper::kses( $value, 'html' );
			case 'importer_post_excerpt': return Helper::kses( $value, 'text' );

			case 'importer_post_cats': return array_filter( Helper::ksesArray( Helper::getSeperated( $value ) ) );
			case 'importer_post_tags': return array_filter( Helper::ksesArray( Helper::getSeperated( $value ) ) );
		}

		return $value;
	}

	public function importer_saved( $post, $data, $raw, $field_map, $attach_id )
	{
		if ( $this->get_setting( 'store_source_data' ) ) {
			update_post_meta( $post->ID, $this->constant( 'metakey_source_data' ), $raw );
			update_post_meta( $post->ID, $this->constant( 'metakey_attach_id' ), $attach_id );
		}

		if ( $this->get_setting( 'add_audit_attribute' )
			&& gEditorial()->enabled( 'audit' ) ) {

			gEditorial()->audit->set_terms( $post, $this->default_audit_attribute );
		}

		if ( WordPress::isDev() )
			self::__log( $post->ID, $data, $raw, $field_map, $attach_id );
	}
}
