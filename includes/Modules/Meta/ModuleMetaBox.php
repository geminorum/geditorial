<?php namespace geminorum\gEditorial\Modules\Meta;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleMetaBox extends gEditorial\MetaBox
{

	const MODULE = 'meta';

	public static function setPostMetaField_String( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = WordPress\Strings::kses( $_POST[$prefix.$field] );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public static function setPostMetaField_Text( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = WordPress\Strings::kses( $_POST[$prefix.$field], 'text' );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public static function setPostMetaField_HTML( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = WordPress\Strings::balanceTags( WordPress\Strings::kses( $_POST[$prefix.$field], 'html' ) );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public static function setPostMetaField_Number( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = Core\Number::intval( trim( $_POST[$prefix.$field] ) );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public static function setPostMetaField_URL( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = esc_url( $_POST[$prefix.$field] );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public static function setPostMetaField_Code( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = trim( $_POST[$prefix.$field] );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public static function setPostMetaField_Term( $post_id, $field, $taxonomy, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && '0' != $_POST[$prefix.$field] )
			wp_set_object_terms( (int) $post_id, Core\Arraay::prepNumeral( $_POST[$prefix.$field] ), $taxonomy, FALSE );

		else if ( isset( $_POST[$prefix.$field] ) && '0' == $_POST[$prefix.$field] )
			wp_set_object_terms( (int) $post_id, [], $taxonomy, FALSE );
	}

	public static function legacy_fieldString( $field, $fields, $post, $ltr = FALSE, $title = NULL, $key = FALSE, $type = 'text' )
	{
		if ( ! in_array( $field, $fields ) )
			return;

		if ( is_null( $title ) )
			$title = self::getString( $field, $post->post_type );

		$atts = [
			'type'         => 'text',
			'autocomplete' => 'off',
			'name'         => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
			'id'           => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
			'value'        => self::getPostMeta( $post->ID, $field, '' ),
			'title'        => self::getString( $field, $post->post_type, 'descriptions', $title ), // FIXME: get from fields args
			'placeholder'  => $title,
			'class'        => [
				'geditorial-meta-field-'.$field,
				'geditorial-meta-type-'.$type,
			],
			'data' => [
				'meta-field' => $field,
				'meta-type'  => $type,
				'meta-title' => $title,
			],
		];

		if ( $ltr )
			$atts['dir'] = 'ltr';

		else if ( 'text' == $type )
			$atts['data']['ortho'] = 'text';

		echo Core\HTML::wrap( Core\HTML::tag( 'input', $atts ), 'field-wrap -inputtext' );
	}

	public static function legacy_fieldNumber( $field, $fields, $post, $ltr = TRUE, $title = NULL, $key = FALSE, $type = 'number' )
	{
		if ( ! in_array( $field, $fields ) )
			return;

		if ( is_null( $title ) )
			$title = self::getString( $field, $post->post_type );

		$atts = [
			'type'         => 'number',
			'autocomplete' => 'off',
			'name'         => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
			'id'           => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
			'value'        => self::getPostMeta( $post->ID, $field, '' ),
			'title'        => self::getString( $field, $post->post_type, 'descriptions', $title ), // FIXME: get from fields args
			'placeholder'  => $title,
			'class'        => [
				'geditorial-meta-field-'.$field,
				'geditorial-meta-type-'.$type,
			],
			'data' => [
				'meta-field' => $field,
				'meta-type'  => $type,
				'meta-title' => $title,
			],
		];

		if ( $ltr )
			$atts['dir'] = 'ltr';

		$atts['data']['ortho'] = 'number';

		echo Core\HTML::wrap( Core\HTML::tag( 'input', $atts ), 'field-wrap -inputnumber' );
	}

	public static function legacy_fieldTerm( $field, $fields, $post, $tax, $ltr = FALSE, $title = NULL, $key = FALSE, $type = 'term' )
	{
		if ( ! in_array( $field, $fields ) )
			return;

		if ( is_null( $title ) )
			$title = self::getString( $field, $post->post_type );

		$desc = self::getString( $field, $post->post_type, 'descriptions', $title ); // FIXME: get from fields args

		echo '<div class="-wrap field-wrap -select" title="'.Core\HTML::escape( $desc ).'">';

		// FIXME: core dropdown does not support: data attr
		wp_dropdown_categories( [
			'taxonomy'          => $tax,
			'selected'          => WordPress\Taxonomy::theTerm( $tax, $post->ID ),
			'show_option_none'  => gEditorial\Settings::showOptionNone( $title ),
			'option_none_value' => '0',
			'class'             => 'geditorial-admin-dropbown geditorial-meta-field-'.$field.( $ltr ? ' dropbown-ltr' : '' ),
			'name'              => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
			'id'                => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
			'orderby'           => 'name',
			'show_count'        => FALSE, // FIXME: make this optional
			'hide_empty'        => FALSE,
			'hide_if_empty'     => TRUE,
			'echo'              => TRUE,
		] );

		echo '</div>';
	}

	public static function legacy_fieldTextarea( $field, $fields, $post, $ltr = FALSE, $title = NULL, $key = FALSE, $type = 'textarea' )
	{
		if ( ! in_array( $field, $fields ) )
			return;

		if ( is_null( $title ) )
			$title = self::getString( $field, $post->post_type );

		$atts = [
			'rows'        => '1',
			// 'cols'        => '40',
			'name'        => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
			'id'          => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
			'title'       => self::getString( $field, $post->post_type, 'descriptions', $title ), // FIXME: get from fields args
			'placeholder' => $title,
			'tabindex'    => '0',
			'class'       => [
				'geditorial-meta-field-'.$field,
				'geditorial-meta-type-'.$type,
				'textarea-autosize',
			],
			'data' => [
				'meta-field' => $field,
				'meta-type'  => $type,
				'meta-title' => $title,
				'ortho'      => 'html',
			],
		];

		if ( $ltr )
			$atts['dir'] = 'ltr';

		else
			$atts['data']['ortho'] = 'html';

		$html = Core\HTML::tag( 'textarea', $atts, esc_textarea( self::getPostMeta( $post->ID, $field, '' ) ) );

		echo Core\HTML::wrap( $html, 'field-wrap -textarea' );
	}

	// for meta fields before and after post title
	public static function legacy_fieldTitle( $field, $fields, $post, $ltr = FALSE, $title = NULL, $key = FALSE, $type = 'title_after' )
	{
		if ( ! in_array( $field, $fields ) )
			return;

		if ( is_null( $title ) )
			$title = self::getString( $field, $post->post_type );

		$atts = [
			'type'         => 'text',
			'autocomplete' => 'off',
			'name'         => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
			'id'           => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
			'value'        => self::getPostMeta( $post->ID, $field, '' ),
			'title'        => self::getString( $field, $post->post_type, 'descriptions', $title ), // FIXME: get from fields args
			'placeholder'  => $title,
			'tabindex'     => '0',
			'style'        => 'display:none;',
			'class'        => [
				'geditorial-admin-posttitle',
				'geditorial-meta-field-'.$field,
				'geditorial-meta-type-'.$type,
				'hide-if-no-js',
			],
			'data' => [
				'meta-field' => $field,
				'meta-type'  => $type,
				'meta-title' => $title,
			],
		];

		if ( $ltr )
			$atts['dir'] = 'ltr';

		else
			$atts['data']['ortho'] = 'text';

		echo Core\HTML::tag( 'input', $atts );
	}

	public static function legacy_fieldBox( $field, $fields, $post, $ltr = FALSE, $title = NULL, $key = FALSE, $type = 'postbox_legacy' )
	{
		if ( ! in_array( $field, $fields ) )
			return;

		if ( is_null( $title ) )
			$title = self::getString( $field, $post->post_type );

		$html = '<div id="geditorial-meta-'.$field.'-wrap" class="postbox geditorial-wrap -admin-postbox -admin-postbox-manual geditorial-meta-field-'.$field.'">';
		$html.= '<button type="button" class="handlediv button-link" aria-expanded="true">';
		$html.= '<span class="screen-reader-text">'.esc_attr_x( 'Click to toggle', 'MetaBox', 'geditorial-admin' ).'</span>';
		$html.= '<span class="toggle-indicator" aria-hidden="true"></span></button>';
		$html.= '<h2 class="hndle"><span>'.$title.'</span></h2><div class="inside">';
		$html.= '<div class="geditorial-admin-wrap-textbox">';
		$html.= '<div class="-wrap field-wrap -textarea">';

		$atts = [
			'rows'     => '1',
			// 'cols'     => '40',
			'name'     => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
			'id'       => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
			'title'    => self::getString( $field, $post->post_type, 'descriptions', $title ), // FIXME: get from fields args
			'tabindex' => '0',
			'class'    => [
				'geditorial-meta-field-'.$field,
				'geditorial-meta-type-'.$type,
				'textarea-autosize',
			],
			'data' => [
				'meta-field' => $field,
				'meta-type'  => $type,
				'meta-title' => $title,
			],
		];

		if ( $ltr )
			$atts['dir'] = 'ltr';

		else
			$atts['data']['ortho'] = 'html';

		$html.= Core\HTML::tag( 'textarea', $atts, esc_textarea( self::getPostMeta( $post->ID, $field, '' ) ) );
		$html.= '</div></div></div></div>';

		echo $html;
	}

	public static function legacy_fieldEditorBox( $field, $post, $ltr = FALSE, $title = NULL, $key = FALSE, $type = 'postbox_html' )
	{
		$id   = 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key );
		$atts = [
			'textarea_name' => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
			'editor_class'  => 'editor-status-counts textarea-autosize geditorial-meta-field-'.$field.' geditorial-meta-type-'.$type,
			'editor_css'    => apply_filters( 'geditorial_meta_'.$type.'_css', '', $type, $field, $post ),
			'tinymce'       => 'postbox_tiny' == $type,
		];

		if ( is_null( $title ) )
			$title = self::getString( $field, $post->post_type );

		parent::fieldEditorBox( self::getPostMeta( $post->ID, $field, '' ), $id, $title, $atts );
	}
}
