<?php namespace geminorum\gEditorial\MetaBoxes;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\WordPress\Taxonomy;

class Meta extends gEditorial\MetaBox
{

	public static function setPostMetaField_String( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = trim( Helper::kses( $_POST[$prefix.$field] ) );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public static function setPostMetaField_Text( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = trim( Helper::kses( $_POST[$prefix.$field], 'text' ) );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public static function setPostMetaField_HTML( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = trim( Helper::kses( $_POST[$prefix.$field], 'html' ) );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public static function setPostMetaField_Number( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = Number::intval( trim( $_POST[$prefix.$field] ) );

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
			wp_set_object_terms( $post_id, intval( $_POST[$prefix.$field] ), $taxonomy, FALSE );

		else if ( isset( $_POST[$prefix.$field] ) && '0' == $_POST[$prefix.$field] )
			wp_set_object_terms( $post_id, NULL, $taxonomy, FALSE );
	}

	public static function fieldString( $field, $fields, $post, $ltr = FALSE, $title = NULL, $key = FALSE, $type = 'text' )
	{
		global $gEditorial;

		if ( ! in_array( $field, $fields ) )
			return;

		if ( is_null( $title ) )
			$title = $gEditorial->meta->get_string( $field, $post->post_type );

		$atts = [
			'type'         => 'text',
			'autocomplete' => 'off',
			'name'         => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
			'id'           => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
			'value'        => $gEditorial->meta->get_postmeta( $post->ID, $field ),
			'title'        => $gEditorial->meta->get_string( $field, $post->post_type, 'descriptions', $title ), // FIXME: get from fields args
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

		$html = HTML::tag( 'input', $atts );

		echo HTML::tag( 'div', [
			'class' => 'field-wrap field-wrap-inputtext',
		], $html );
	}

	public static function fieldNumber( $field, $fields, $post, $ltr = TRUE, $title = NULL, $key = FALSE, $type = 'number' )
	{
		global $gEditorial;

		if ( ! in_array( $field, $fields ) )
			return;


		if ( is_null( $title ) )
			$title = $gEditorial->meta->get_string( $field, $post->post_type );

		$atts = [
			'type'         => 'number',
			'autocomplete' => 'off',
			'name'         => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
			'id'           => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
			'value'        => $gEditorial->meta->get_postmeta( $post->ID, $field ),
			'title'        => $gEditorial->meta->get_string( $field, $post->post_type, 'descriptions', $title ), // FIXME: get from fields args
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

		$html = HTML::tag( 'input', $atts );

		echo HTML::tag( 'div', [
			'class' => 'field-wrap field-wrap-inputnumber',
		], $html );
	}

	public static function fieldTerm( $field, $fields, $post, $tax, $ltr = FALSE, $title = NULL, $key = FALSE, $type = 'term' )
	{
		global $gEditorial;

		if ( ! in_array( $field, $fields ) )
			return;

		if ( is_null( $title ) )
			$title = $gEditorial->meta->get_string( $field, $post->post_type );

		$desc = $gEditorial->meta->get_string( $field, $post->post_type, 'descriptions', $title ); // FIXME: get from fields args

		echo '<div class="field-wrap" title="'.esc_attr( $desc ).'">';

		// FIXME: core dropdown does not support: data attr
		wp_dropdown_categories( [
			'taxonomy'          => $tax,
			'selected'          => Taxonomy::theTerm( $tax, $post->ID ),
			'show_option_none'  => Settings::showOptionNone( $title ),
			'option_none_value' => '0',
			'class'             => 'geditorial-admin-dropbown geditorial-meta-field-'.$field.( $ltr ? ' dropbown-ltr' : '' ),
			'name'              => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
			'id'                => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
			'orderby'           => 'name',
			'show_count'        => TRUE,
			'hide_empty'        => FALSE,
			'hide_if_empty'     => TRUE,
			'echo'              => TRUE,
		] );

		echo '</div>';
	}

	public static function fieldTextarea( $field, $fields, $post, $ltr = FALSE, $title = NULL, $key = FALSE, $type = 'textarea' )
	{
		global $gEditorial;

		if ( ! in_array( $field, $fields ) )
			return;

		if ( is_null( $title ) )
			$title = $gEditorial->meta->get_string( $field, $post->post_type );

		$atts = [
			'rows'        => '1',
			// 'cols'        => '40',
			'name'        => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
			'id'          => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
			'title'       => $gEditorial->meta->get_string( $field, $post->post_type, 'descriptions', $title ), // FIXME: get from fields args
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

		$html = HTML::tag( 'textarea', $atts, esc_textarea( $gEditorial->meta->get_postmeta( $post->ID, $field ) ) );

		echo HTML::tag( 'div', [
			'class' => 'field-wrap field-wrap-textarea',
		], $html );
	}

	// for meta fields before and after post title
	public static function fieldTitle( $field, $fields, $post, $ltr = FALSE, $title = NULL, $key = FALSE, $type = 'title_after' )
	{
		global $gEditorial;

		if ( ! in_array( $field, $fields ) )
			return;

		if ( is_null( $title ) )
			$title = $gEditorial->meta->get_string( $field, $post->post_type );

		$atts = [
			'type'         => 'text',
			'autocomplete' => 'off',
			'name'         => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
			'id'           => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
			'value'        => $gEditorial->meta->get_postmeta( $post->ID, $field ),
			'title'        => $gEditorial->meta->get_string( $field, $post->post_type, 'descriptions', $title ), // FIXME: get from fields args
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

		echo HTML::tag( 'input', $atts );
	}

	public static function fieldBox( $field, $fields, $post, $ltr = FALSE, $title = NULL, $key = FALSE, $type = 'box' )
	{
		global $gEditorial;

		if ( ! in_array( $field, $fields ) )
			return;

		if ( is_null( $title ) )
			$title = $gEditorial->meta->get_string( $field, $post->post_type );

		$html  = '<div id="geditorial-meta-'.$field.'-wrap" class="postbox geditorial-admin-postbox geditorial-meta-field-'.$field.'">';
		$html .= '<button type="button" class="handlediv button-link" aria-expanded="true">';
		$html .= '<span class="screen-reader-text">'.esc_attr_x( 'Click to toggle', 'MetaBox', GEDITORIAL_TEXTDOMAIN ).'</span>';
		$html .= '<span class="toggle-indicator" aria-hidden="true"></span></button>';
		$html .= '<h2 class="hndle"><span>'.$title.'</span></h2><div class="inside">';
		$html .= '<div class="geditorial-admin-wrap-textbox geditorial-wordcount-wrap">';
		$html .= '<div class="field-wrap field-wrap-textarea">';

		$atts = [
			'rows'     => '1',
			// 'cols'     => '40',
			'name'     => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
			'id'       => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
			'title'    => $gEditorial->meta->get_string( $field, $post->post_type, 'descriptions', $title ), // FIXME: get from fields args
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

		$html .= HTML::tag( 'textarea', $atts, esc_textarea( $gEditorial->meta->get_postmeta( $post->ID, $field ) ) );
		$html .= Helper::htmlWordCount( ( 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ) ), $post->post_type );

		$html .= '</div></div></div></div>';

		echo $html;
	}
}
