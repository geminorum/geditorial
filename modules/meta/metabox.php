<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMetaMetaBox extends gEditorialMetaBox
{

	public static function setPostMetaField_String( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = gEditorialHelper::kses( $_POST[$prefix.$field] );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public static function setPostMetaField_Number( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = gEditorialHelper::intval( $_POST[$prefix.$field] );

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

		if ( in_array( $field, $fields )
			&& $gEditorial->meta->user_can( 'view', $field )  ) {

			if ( is_null( $title ) )
				$title = $gEditorial->meta->get_string( $field, $post->post_type );

			$edit = $gEditorial->meta->user_can( 'edit', $field );

			$atts = array(
				'type'         => 'text',
				'autocomplete' => 'off',
				'name'         => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
				'id'           => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
				'value'        => $gEditorial->meta->get_postmeta( $post->ID, $field ),
				'title'        => $gEditorial->meta->get_string( $field, $post->post_type, 'descriptions', $title ), // FIXME: get from fields args
				'placeholder'  => $title,
				'readonly'     => ! $edit,
				'class'        => array(
					'geditorial-meta-field-'.$field,
					'geditorial-meta-type-'.$type,
				),
				'data' => array(
					'meta-field' => $field,
					'meta-type'  => $type,
				),
			);

			if ( $ltr )
				$atts['dir'] = 'ltr';

			else if ( $edit && 'text' == $type )
				$atts['data']['ortho'] = 'text';

			$html = self::html( 'input', $atts );

			echo self::html( 'div', array(
				'class' => 'field-wrap field-wrap-inputtext',
			), $html );
		}
	}

	public static function fieldNumber( $field, $fields, $post, $ltr = TRUE, $title = NULL, $key = FALSE, $type = 'number' )
	{
		global $gEditorial;

		if ( in_array( $field, $fields )
			&& $gEditorial->meta->user_can( 'view', $field )  ) {

			if ( is_null( $title ) )
				$title = $gEditorial->meta->get_string( $field, $post->post_type );

			$edit = $gEditorial->meta->user_can( 'edit', $field );

			$atts = array(
				'type'         => 'number',
				'autocomplete' => 'off',
				'name'         => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
				'id'           => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
				'value'        => $gEditorial->meta->get_postmeta( $post->ID, $field ),
				'title'        => $gEditorial->meta->get_string( $field, $post->post_type, 'descriptions', $title ), // FIXME: get from fields args
				'placeholder'  => $title,
				'readonly'     => ! $edit,
				'class'        => array(
					'geditorial-meta-field-'.$field,
					'geditorial-meta-type-'.$type,
				),
				'data' => array(
					'meta-field' => $field,
					'meta-type'  => $type,
				),
			);

			if ( $ltr )
				$atts['dir'] = 'ltr';

			if ( $edit )
				$atts['data']['ortho'] = 'number';

			$html = self::html( 'input', $atts );

			echo self::html( 'div', array(
				'class' => 'field-wrap field-wrap-inputnumber',
			), $html );
		}
	}

	public static function fieldTerm( $field, $fields, $post, $tax, $ltr = FALSE, $title = NULL, $key = FALSE, $type = 'term' )
	{
		global $gEditorial;

		if ( in_array( $field, $fields )
			&& $gEditorial->meta->user_can( 'edit', $field )  ) {

			if ( is_null( $title ) )
				$title = $gEditorial->meta->get_string( $field, $post->post_type );

			$desc = $gEditorial->meta->get_string( $field, $post->post_type, 'descriptions', $title ); // FIXME: get from fields args

			echo '<div class="field-wrap" title="'.esc_attr( $desc ).'">';

			// FIXME: core dropdown does not support: data attr
			wp_dropdown_categories( array(
				'taxonomy'          => $tax,
				'selected'          => self::theTerm( $tax, $post->ID ),
				'show_option_none'  => gEditorialSettingsCore::showOptionNone( $title ),
				'option_none_value' => '0',
				'class'             => 'geditorial-admin-dropbown geditorial-meta-field-'.$field.( $ltr ? ' dropbown-ltr' : '' ),
				'name'              => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
				'id'                => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
				'show_count'        => TRUE,
				'hide_empty'        => FALSE,
				'hide_if_empty'     => TRUE,
				'echo'              => TRUE,
			) );

			echo '</div>';
		}
	}

	public static function fieldTextarea( $field, $fields, $post, $ltr = FALSE, $title = NULL, $key = FALSE, $type = 'textarea' )
	{
		global $gEditorial;

		if ( in_array( $field, $fields )
			&& $gEditorial->meta->user_can( 'view', $field )  ) {

			if ( is_null( $title ) )
				$title = $gEditorial->meta->get_string( $field, $post->post_type );

			$edit = $gEditorial->meta->user_can( 'edit', $field );

			$atts = array(
				'rows'        => '1',
				// 'cols'        => '40',
				'name'        => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
				'id'          => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
				'title'       => $gEditorial->meta->get_string( $field, $post->post_type, 'descriptions', $title ), // FIXME: get from fields args
				'placeholder' => $title,
				'readonly'    => ! $edit,
				'tabindex'    => '0',
				'class'       => array(
					'geditorial-meta-field-'.$field,
					'geditorial-meta-type-'.$type,
				),
				'data' => array(
					'meta-field' => $field,
					'meta-type'  => $type,
				),
			);

			if ( $ltr )
				$atts['dir'] = 'ltr';

			else if ( $edit )
				$atts['data']['ortho'] = 'html';

			$html = self::html( 'textarea', $atts, esc_textarea( $gEditorial->meta->get_postmeta( $post->ID, $field ) ) );

			echo self::html( 'div', array(
				'class' => 'field-wrap field-wrap-textarea',
			), $html );
		}
	}

	// for meta fields before and after post title
	public static function fieldTitle( $field, $fields, $post, $ltr = FALSE, $title = NULL, $key = FALSE, $type = 'title_after' )
	{
		global $gEditorial;

		if ( in_array( $field, $fields )
			&& $gEditorial->meta->user_can( 'view', $field )  ) {

			if ( is_null( $title ) )
				$title = $gEditorial->meta->get_string( $field, $post->post_type );

			$edit = $gEditorial->meta->user_can( 'edit', $field );

			$atts = array(
				'type'         => 'text',
				'autocomplete' => 'off',
				'name'         => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
				'id'           => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
				'value'        => $gEditorial->meta->get_postmeta( $post->ID, $field ),
				'title'        => $gEditorial->meta->get_string( $field, $post->post_type, 'descriptions', $title ), // FIXME: get from fields args
				'placeholder'  => $title,
				'readonly'     => ! $edit,
				'tabindex'     => '0',
				'style'        => 'display:none;',
				'class'        => array(
					'geditorial-admin-posttitle',
					'geditorial-meta-field-'.$field,
					'geditorial-meta-type-'.$type,
					'hide-if-no-js',
				),
				'data' => array(
					'meta-field' => $field,
					'meta-type'  => $type,
				),
			);

			if ( $ltr )
				$atts['dir'] = 'ltr';

			else if ( $edit )
				$atts['data']['ortho'] = 'text';

			echo self::html( 'input', $atts );
		}
	}

	public static function fieldBox( $field, $fields, $post, $ltr = FALSE, $title = NULL, $key = FALSE, $type = 'box' )
	{
		global $gEditorial;

		if ( in_array( $field, $fields )
			&& $gEditorial->meta->user_can( 'view', $field )  ) {

			if ( is_null( $title ) )
				$title = $gEditorial->meta->get_string( $field, $post->post_type );

			$edit = $gEditorial->meta->user_can( 'edit', $field );

			$html  = '<div id="geditorial-meta-'.$field.'-wrap" class="postbox geditorial-admin-postbox geditorial-meta-field-'.$field.'">';
			$html .= '<button type="button" class="handlediv button-link" aria-expanded="true">';
			$html .= '<span class="screen-reader-text">'.esc_attr_x( 'Click to toggle', 'Module Helper', GEDITORIAL_TEXTDOMAIN ).'</span>';
			$html .= '<span class="toggle-indicator" aria-hidden="true"></span></button>';
			$html .= '<h2 class="hndle ui-sortable-handle"><span>'.$title.'</span></h2>';
			$html .= '<div class="inside">';

			$html .= '<div class="geditorial-admin-wrap-textbox geditorial-wordcount-wrap">';
			$html .= '<label class="screen-reader-text" for="geditorial-meta-'.$field.'">'.$title.'</label>';
			$html .= '<div class="field-wrap field-wrap-textarea">';

			$atts = array(
				'rows'     => '1',
				// 'cols'     => '40',
				'name'     => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
				'id'       => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
				'title'    => $gEditorial->meta->get_string( $field, $post->post_type, 'descriptions', $title ), // FIXME: get from fields args
				'readonly' => ! $edit,
				'tabindex' => '0',
				'class'    => array(
					'geditorial-meta-field-'.$field,
					'geditorial-meta-type-'.$type,
					'textarea-autosize',
				),
				'data' => array(
					'meta-field' => $field,
					'meta-type'  => $type,
				),

			);

			if ( $ltr )
				$atts['dir'] = 'ltr';

			else if ( $edit )
				$atts['data']['ortho'] = 'html';

			$html .= self::html( 'textarea', $atts, esc_textarea( $gEditorial->meta->get_postmeta( $post->ID, $field ) ) );
			$html .= gEditorialHelper::htmlWordCount( ( 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ) ), $post->post_type );

			$html .= '</div></div></div>';

			echo $html;
		}
	}
}
