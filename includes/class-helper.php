<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialHelper extends gEditorialBaseCore
{

	public static function moduleClass( $module, $check = TRUE, $prefix = 'gEditorial' )
	{
		$class = '';

		foreach ( explode( '-', $module ) as $word )
			$class .= ucfirst( $word ).'';

		if ( $check && ! class_exists( $prefix.$class ) )
			return FALSE;

		return $prefix.$class;
	}

	// FIXME: MUST DEPRECATE
	public static function moduleEnabled( $options )
	{
		$enabled = isset( $options->enabled ) ? $options->enabled : FALSE;

		if ( 'off' === $enabled )
			return FALSE;

		if ( 'on' === $enabled )
			return TRUE;

		return $enabled;
	}

	// override to use plugin version
	public static function linkStyleSheet( $url, $version = GEDITORIAL_VERSION, $media = FALSE )
	{
		parent::linkStyleSheet( $url, $version, $media );
	}

	public static function linkStyleSheetAdmin( $page )
	{
		parent::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.'.$page.'.css', GEDITORIAL_VERSION );
	}

	public static function meta_admin_field( $field, $fields, $post, $ltr = FALSE, $title = NULL, $key = FALSE )
	{
		global $gEditorial;

		if ( in_array( $field, $fields )
			&& $gEditorial->meta->user_can( 'view', $field )  ) {

				if ( is_null( $title ) )
					$title = $gEditorial->meta->get_string( $field, $post->post_type );

				$atts = array(
					'type'         => 'text',
					'autocomplete' => 'off',
					'class'        => 'geditorial-meta-field-'.$field,
					'name'         => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
					'id'           => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
					'value'        => $gEditorial->meta->get_postmeta( $post->ID, $field ),
					'title'        => $title,
					'placeholder'  => $title,
					'readonly'     => ! $gEditorial->meta->user_can( 'edit', $field ),
				);

				if ( $ltr )
					$atts['dir'] = 'ltr';

				$html = self::html( 'input', $atts );

				echo self::html( 'div', array(
					'class' => 'field-wrap field-wrap-inputtext',
				), $html );
		}
	}

	public static function meta_admin_number_field( $field, $fields, $post, $ltr = TRUE, $title = NULL, $key = FALSE )
	{
		global $gEditorial;

		if ( in_array( $field, $fields )
			&& $gEditorial->meta->user_can( 'view', $field )  ) {

				if ( is_null( $title ) )
					$title = $gEditorial->meta->get_string( $field, $post->post_type );

				$atts = array(
					'type'         => 'number',
					'autocomplete' => 'off',
					'class'        => 'geditorial-meta-field-'.$field,
					'name'         => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
					'id'           => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
					'value'        => $gEditorial->meta->get_postmeta( $post->ID, $field ),
					'title'        => $title,
					'placeholder'  => $title,
					'readonly'     => ! $gEditorial->meta->user_can( 'edit', $field ),
				);

				if ( $ltr )
					$atts['dir'] = 'ltr';

				$html = self::html( 'input', $atts );

				echo self::html( 'div', array(
					'class' => 'field-wrap field-wrap-inputnumber',
				), $html );
		}
	}

	public static function meta_admin_tax_field( $field, $fields, $post, $tax, $ltr = FALSE, $title = NULL, $key = FALSE )
	{
		global $gEditorial;

		if ( in_array( $field, $fields )
			// && $gEditorial->meta->user_can( 'view', $field )  ) {
			&& $gEditorial->meta->user_can( 'edit', $field )  ) {

				if ( is_null( $title ) )
					$title = $gEditorial->meta->get_string( $field, $post->post_type );

				echo '<div class="field-wrap" title="'.esc_attr( $title ).'">';

				wp_dropdown_categories( array(
					'taxonomy'          => $tax,
					'selected'          => self::theTerm( $tax, $post->ID ),
					'show_option_none'  => sprintf( _x( '&mdash; Select %s &mdash;', 'Meta Module: Dropdown Select Option None', GEDITORIAL_TEXTDOMAIN ), $title ),
					'option_none_value' => '0',
					'class'             => 'geditorial-admin-dropbown geditorial-meta-field-'.$field.( $ltr ? ' dropbown-ltr' : '' ),
					'name'              => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
					'id'                => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
					'show_count'        => TRUE,
					'hide_empty'        => FALSE,
					'hide_if_empty'     => TRUE,
					'echo'              => TRUE,
					// 'exclude'           => $excludes,
				) );

				echo '</div>';
		}
	}

	public static function meta_admin_textarea_field( $field, $fields, $post, $ltr = FALSE, $title = NULL, $key = FALSE )
	{
		global $gEditorial;

		if ( in_array( $field, $fields )
			&& $gEditorial->meta->user_can( 'view', $field )  ) {

				if ( is_null( $title ) )
					$title = $gEditorial->meta->get_string( $field, $post->post_type );

				$atts = array(
					// 'rows'         => '5',
					// 'cols'         => '40',
					'class'        => 'geditorial-meta-field-'.$field,
					'name'         => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
					'id'           => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
					'title'        => $title,
					'placeholder'  => $title,
					'readonly'     => ! $gEditorial->meta->user_can( 'edit', $field ),
					'tabindex'     => '0',
				);

				if ( $ltr )
					$atts['dir'] = 'ltr';

				$html = self::html( 'textarea', $atts, esc_textarea( $gEditorial->meta->get_postmeta( $post->ID, $field ) ) );

				echo self::html( 'div', array(
					'class' => 'field-wrap field-wrap-textarea',
				), $html );
		}
	}

	// for meta fields before and after post title
	public static function meta_admin_title_field( $field, $fields, $post, $ltr = FALSE, $title = NULL, $key = FALSE )
	{
		global $gEditorial;

		if ( in_array( $field, $fields )
			&& $gEditorial->meta->user_can( 'view', $field )  ) {

				if ( is_null( $title ) )
					$title = $gEditorial->meta->get_string( $field, $post->post_type );

				$atts = array(
					'type'         => 'text',
					'autocomplete' => 'off',
					'class'        => 'geditorial-admin-posttitle geditorial-meta-field-'.$field,
					'name'         => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
					'id'           => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
					'value'        => $gEditorial->meta->get_postmeta( $post->ID, $field ),
					'title'        => $title,
					'placeholder'  => $title,
					'readonly'     => ! $gEditorial->meta->user_can( 'edit', $field ),
					'tabindex'     => '0',
				);

				if ( $ltr )
					$atts['dir'] = 'ltr';

				echo self::html( 'input', $atts );
		}
	}

	public static function meta_admin_text_field( $field, $fields, $post, $ltr = FALSE, $title = NULL, $key = FALSE )
	{
		global $gEditorial;

		if ( in_array( $field, $fields )
			&& $gEditorial->meta->user_can( 'view', $field )  ) {

				if ( is_null( $title ) )
					$title = $gEditorial->meta->get_string( $field, $post->post_type );

				$html  = '<div id="geditorial-meta-'.$field.'-wrap" class="postbox geditorial-admin-postbox geditorial-meta-field-'.$field.'">';
				$html .= '<div class="handlediv" title="'.esc_attr( _x( 'Click to toggle', 'Module Helper', GEDITORIAL_TEXTDOMAIN ) ).'"><br /></div><h2 class="hndle"><span>'.$title.'</span></h2>';
				$html .= '<div class="inside"><label class="screen-reader-text" for="geditorial-meta-'.$field.'">'.$title.'</label>';
				$html .= self::html( 'textarea', array(
					'rows'     => '1',
					'cols'     => '40',
					'name'     => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '['.$key.']' ),
					'id'       => 'geditorial-meta-'.$field.( FALSE === $key ? '' : '-'.$key ),
					'class'    => 'textarea-autosize geditorial-meta-field-'.$field,
					'readonly' => ! $gEditorial->meta->user_can( 'edit', $field ),
					'tabindex' => '0',
				), esc_textarea( $gEditorial->meta->get_postmeta( $post->ID, $field ) ) );
				$html .= '</div></div>';

				echo $html;
		}
	}

	public static function set_postmeta_field_string( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = self::kses( $_POST[$prefix.$field] );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public static function set_postmeta_field_number( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = self::intval( $_POST[$prefix.$field] );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public static function set_postmeta_field_url( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = esc_url( $_POST[$prefix.$field] );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public static function set_postmeta_field_term( $post_id, $field, $taxonomy, $prefix = 'geditorial-meta-' )
	{
		if ( isset( $_POST[$prefix.$field] ) && '0' != $_POST[$prefix.$field] )
			wp_set_object_terms( $post_id, intval( $_POST[$prefix.$field] ), $taxonomy, FALSE );

		else if ( isset( $_POST[$prefix.$field] ) && '0' == $_POST[$prefix.$field] )
			wp_set_object_terms( $post_id, NULL, $taxonomy, FALSE );
	}

	// converts back number chars into english
	public static function intval( $text, $intval = TRUE )
	{
		$number = apply_filters( 'number_format_i18n_back', $text );
		return $intval ? intval( $number ) : $number;
	}

	public static function kses( $text, $allowed = array(), $context = 'store' )
	{
		return apply_filters( 'geditorial_kses', wp_kses( $text, $allowed ), $allowed, $context );
	}

	public static function getTermsEditRow( $post_id, $post_type, $taxonomy, $before = '', $after = '' )
	{
		$taxonomy_object = get_taxonomy( $taxonomy );

		if ( $terms = get_the_terms( $post_id, $taxonomy ) ) {

			$out = array();

			foreach ( $terms as $t ) {

				$query = array();

				if ( 'post' != $post_type )
					$query['post_type'] = $post_type;

				if ( $taxonomy_object->query_var ) {
					$query[$taxonomy_object->query_var] = $t->slug;

				} else {
					$query['taxonomy'] = $taxonomy;
					$query['term']     = $t->slug;
				}

				$out[] = sprintf( '<a href="%s">%s</a>',
					esc_url( add_query_arg( $query, 'edit.php' ) ),
					esc_html( sanitize_term_field( 'name', $t->name, $t->term_id, $taxonomy, 'display' ) )
				);
			}

			echo $before.join( _x( ', ', 'Module Helper: Term Seperator', GEDITORIAL_TEXTDOMAIN ), $out ).$after;
		}
	}

	public static function registerColorBox()
	{
		wp_register_style( 'jquery-colorbox', GEDITORIAL_URL.'assets/css/admin.colorbox.css', array(), '1.6.3', 'screen' );
		wp_register_script( 'jquery-colorbox', GEDITORIAL_URL.'assets/packages/jquery-colorbox/jquery.colorbox-min.js', array( 'jquery'), '1.6.3', TRUE );
	}

	public static function enqueueColorBox()
	{
		wp_enqueue_style( 'jquery-colorbox' );
		wp_enqueue_script( 'jquery-colorbox' );
	}

	public static function getTerms( $taxonomy = 'category', $post_id = FALSE, $object = FALSE, $key = 'term_id' )
	{
		$the_terms = array();

		if ( FALSE === $post_id ) {
			$terms = get_terms( $taxonomy, array(
				'hide_empty' => FALSE,
				'orderby'    => 'name',
				'order'      => 'ASC'
			) );
		} else {
			$terms = get_the_terms( $post_id, $taxonomy );
		}

		if ( is_wp_error( $terms ) || FALSE === $terms )
			return $the_terms;

		$the_list = wp_list_pluck( $terms, $key );
		$terms = array_combine( $the_list, $terms );

		if ( $object )
			return $terms;

		foreach ( $terms as $term )
			$the_terms[] = $term->term_id;

		return $the_terms;
	}

	public static function getTermPosts( $taxonomy, $term_or_id, $exclude = array() )
	{
		if ( is_object( $term_or_id ) )
			$term = $term_or_id;
		else if ( is_numeric( $term_or_id ) )
			$term = get_term_by( 'id', $term_or_id, $taxonomy );
		else
			$term = get_term_by( 'slug', $term_or_id, $taxonomy );

		if ( ! $term )
			return '';

		$query_args = array(
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order', 'date' ),
			'order'          => 'ASC',
			'post_status'    => array( 'publish', 'pending', 'draft' ),
			'post__not_in'   => $exclude,
			'tax_query'      => array( array(
				'taxonomy' => $taxonomy,
				'field'    => 'id',
				'terms'    => $term->term_id,
			) ),
		);

		$the_posts = get_posts( $query_args );
		if ( ! count( $the_posts ) )
			return FALSE;

		$output = '<div class="field-wrap field-wrap-list"><h4>';
		$output .= sprintf( _x( 'Other Posts on <a href="%1$s" target="_blank">%2$s</a>', 'Module Helper', GEDITORIAL_TEXTDOMAIN ),
			get_term_link( $term, $term->taxonomy ),
			sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' )
		).'</h4><ol>';

		foreach ( $the_posts as $post ) {
			setup_postdata( $post );

			$url = add_query_arg( array(
				'action' => 'edit',
				'post'   => $post->ID,
			), get_admin_url( NULL, 'post.php' ) );

			$output .= '<li><a href="'.get_permalink( $post->ID ).'">'
					.get_the_title( $post->ID ).'</a>'
					.'&nbsp;<span class="edit">'
					.sprintf( _x( '&ndash; <a href="%1$s" target="_blank" title="Edit this Post">%2$s</a>', 'Module Helper', GEDITORIAL_TEXTDOMAIN ),
						esc_url( $url ),
						'<span class="dashicons dashicons-welcome-write-blog"></span>'
					).'</span></li>';
		}
		wp_reset_query();
		$output .= '</ol></div>';

		return $output;
	}

	const SETTINGS_SLUG = 'geditorial-settings';
	const TOOLS_SLUG    = 'geditorial-tools';

	public static function settingsURL( $full = TRUE )
	{
		//$relative = current_user_can( 'manage_options' ) ? 'admin.php?page='.self::SETTINGS_SLUG : 'index.php?page='.self::SETTINGS_SLUG;
		$relative = 'admin.php?page='.self::SETTINGS_SLUG;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	public static function toolsURL( $full = TRUE )
	{
		$relative = 'admin.php?page='.self::TOOLS_SLUG;

		if ( $full )
			return get_admin_url( NULL, $relative );

		return $relative;
	}

	public static function isSettings( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		if ( isset( $screen->base )
			&& FALSE !== strripos( $screen->base, self::SETTINGS_SLUG ) )
				return TRUE;

		return FALSE;
	}

	public static function isTools( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		if ( isset( $screen->base ) && FALSE !== strripos( $screen->base, self::TOOLS_SLUG ) )
			return TRUE;

		return FALSE;
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( 'geditorial_tinymce_strings', array() );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.geditorial", '.wp_json_encode( $strings ).');'."\n" : '';
	}

	public static function printJSConfig( $args, $object = 'gEditorial' )
	{
		$args['api']   = defined( 'GNETWORK_AJAX_ENDPOINT' ) && GNETWORK_AJAX_ENDPOINT ? GNETWORK_AJAX_ENDPOINT : admin_url( 'admin-ajax.php' );
		$args['nonce'] = wp_create_nonce( 'geditorial' );

	?> <script type="text/javascript">
/* <![CDATA[ */
	var <?php echo $object; ?> = <?php echo wp_json_encode( $args ); ?>;
	<?php if ( self::isDev() ) echo 'console.log('.$object.');'; ?>
/* ]]> */
</script><?php
	}

	// WP default sizes from options
	public static function getWPImageSizes()
	{
		global $gEditorial_WPImageSizes;

		if ( ! empty( $gEditorial_WPImageSizes ) )
			return $gEditorial_WPImageSizes;

		$gEditorial_WPImageSizes = array(
			'thumbnail' => array(
				'n' => _x( 'Thumbnail', 'Module Helper', GEDITORIAL_TEXTDOMAIN ),
				'w' => get_option( 'thumbnail_size_w' ),
				'h' => get_option( 'thumbnail_size_h' ),
				'c' => get_option( 'thumbnail_crop' ),
			),
			'medium' => array(
				'n' => _x( 'Medium', 'Module Helper', GEDITORIAL_TEXTDOMAIN ),
				'w' => get_option( 'medium_size_w' ),
				'h' => get_option( 'medium_size_h' ),
				'c' => 0,
			),
			'large' => array(
				'n' => _x( 'Large', 'Module Helper', GEDITORIAL_TEXTDOMAIN ),
				'w' => get_option( 'large_size_w' ),
				'h' => get_option( 'large_size_h' ),
				'c' => 0,
			),
		);

		return $gEditorial_WPImageSizes;
	}

	public static function settingsHelpLinks( $wiki_page = 'Modules', $wiki_title = NULL )
	{
		if ( is_null( $wiki_title ) )
			$wiki_title = _x( 'gEditorial Documentation', 'Module Helper', GEDITORIAL_TEXTDOMAIN );

		return sprintf(
			_x( '<p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p>', 'Module Helper', GEDITORIAL_TEXTDOMAIN ),
			'https://github.com/geminorum/geditorial/wiki/'.$wiki_page, $wiki_title, 'https://github.com/geminorum/geditorial' );
	}

	// WORKING DRAFT
	// USE: https://raw.githubusercontent.com/wiki/geminorum/geditorial/Modules.md
	// SEE: gNetworkCode
	public static function settingsHelpContent( $module )
	{
		return array(
			array(
				'id'      => 'geditorial-'.$module->name.'-overview',
				'title'   => sprintf( _x( '%s Overview', 'Module Helper: Help Screen Title', GEDITORIAL_TEXTDOMAIN ), $module->title ),
				'content' => '', // TODO: get this from wikipage
			),
		);
	}

	// https://codex.wordpress.org/Javascript_Reference/ThickBox
	public static function thickBoxTest()
	{
		add_thickbox();

		?><div id="my-content-id" style="display:none;">
		 <p> This is my hidden content! It will appear in ThickBox when the link is clicked.</p>
	</div>
	<br />
	<a href="#TB_inline?width=600&height=150&inlineId=my-content-id" class="thickbox button">View my inline content!</a>

	<a href="http://localhost/wpn/wp-admin/network/plugin-install.php?tab=plugin-information&amp;plugin=buddypress&amp;TB_iframe=true&amp;width=772&amp;height=261" class="thickbox button" aria-label="More information about BuddyPress 2.3.4" data-title="BuddyPress 2.3.4">More Details</a>
	<?php
	}
}

class gEditorial_Walker_PageDropdown extends Walker_PageDropdown
{

	public function start_el( &$output, $page, $depth = 0, $args = array(), $id = 0 ) {
		$pad = str_repeat('&nbsp;', $depth * 3);

		if ( ! isset( $args['value_field'] ) || ! isset( $page->{$args['value_field']} ) ) {
			$args['value_field'] = 'ID';
		}

		$output .= "\t<option class=\"level-$depth\" value=\"" . esc_attr( $page->{$args['value_field']} ) . "\"";
		if ( $page->{$args['value_field']} == $args['selected'] ) // <---- CHANGED
			$output .= ' selected="selected"';
		$output .= '>';

		$title = $page->post_title;
		if ( '' === $title ) {
			$title = sprintf( __( '#%d (no title)' ), $page->ID );
		}

		$title = apply_filters( 'list_pages', $title, $page );
		$output .= $pad . esc_html( $title );
		$output .= "</option>\n";
	}
}
