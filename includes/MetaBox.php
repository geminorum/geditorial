<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Misc;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Database;
use geminorum\gEditorial\WordPress\Main;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\Status;
use geminorum\gEditorial\WordPress\User;

class MetaBox extends Main
{

	const BASE = 'geditorial';

	public static function checkHidden( $metabox_id, $posttype = FALSE, $after = '' )
	{
		if ( $posttype && PostType::supportBlocks( $posttype ) )
			return FALSE;

		if ( ! in_array( $metabox_id, get_hidden_meta_boxes( get_current_screen() ) ) )
			return FALSE;

		$html = HTML::tag( 'a', [
			'href'  => add_query_arg( 'flush', '' ),
			'class' => [ '-description', '-refresh' ],
		], _x( 'Please refresh the page to generate the data.', 'MetaBox: Refresh Link', 'geditorial' ) );

		echo HTML::wrap( $html, 'field-wrap -needs-refresh' ).$after;

		return TRUE;
	}

	public static function singleselectTerms( $object_id = 0, $atts = [], $terms = NULL )
	{
		$args = self::args( $atts, [
			'taxonomy' => NULL,
			'posttype' => FALSE,
			'echo'     => TRUE,
			'none'     => Settings::showOptionNone(),
		] );

		if ( ! $args['taxonomy'] )
			return FALSE;

		if ( ! is_null( $terms ) ) {

			// FIXME: make sure it's a list of objects
		} else {

			// $terms = Taxonomy::getTerms( $args['taxonomy'], FALSE, TRUE );
			$terms = Taxonomy::listTerms( $args['taxonomy'], 'all' );
		}

		$selected = wp_get_object_terms( $object_id, $args['taxonomy'], [ 'fields' => 'ids' ] );

		$dropdown = [
			'selected'   => count( $selected ) ? $selected[0] : '0',
			'prop'       => 'name',
			'value'      => 'term_id',
			'name'       => 'tax_input['.$args['taxonomy'].'][]',
			'none_title' => $args['none'],
			'none_value' => 0,
			'class'      => static::BASE.'-admin-dropbown',
		];

		$html = HTML::dropdown( $terms, $dropdown );

		if ( $html )
			$html = HTML::wrap( $html, 'field-wrap -select' );

		if ( ! $args['echo'] )
			return $html;

		echo $html;
	}

	// TODO: radio list box using custom walker
	// CAUTION: tax must be cat (hierarchical)
	// hierarchical taxonomies save by IDs,
	// whereas non-hierarchical save by slugs
	// WTF: because the core's not passing args into the walker!
	// @REF: `post_categories_meta_box()`, `wp_terms_checklist()`
	public static function checklistTerms( $object_id = 0, $atts = [], $terms = NULL )
	{
		$atts = apply_filters( 'wp_terms_checklist_args', $atts, $object_id );

		$args = self::args( $atts, [
			'taxonomy'             => NULL,
			'posttype'             => FALSE,
			'metabox'              => NULL, // metabox id to check for hidden
			'list_only'            => NULL,
			'selected_only'        => NULL,
			'selected_preserve'    => NULL, // keep hidden selected / NULL to check for assign cap
			'descendants_and_self' => 0,
			'selected_cats'        => FALSE,
			'popular_cats'         => FALSE,
			'checked_ontop'        => TRUE,
			'edit'                 => NULL, // manage page if has no terms, FALSE to disable
			'role'                 => FALSE, // `disabled` / `hidden`
			'name'                 => 'tax_input', // override if not saving by core
			'field_class'          => '',
			'walker'               => NULL,
			'echo'                 => TRUE,
		] );

		if ( ! $args['taxonomy'] )
			return FALSE;

		if ( $args['metabox'] && self::checkHidden( $args['metabox'], $args['posttype'] ) )
			return;

		if ( ! is_null( $terms ) ) {

			// FIXME: make sure it's a list of objects

		} else if ( $args['descendants_and_self'] ) {

			$childs = (int) $args['descendants_and_self'];

			$terms = (array) get_terms( [
				'taxonomy'     => $args['taxonomy'],
				'child_of'     => $childs,
				'hierarchical' => 0,
				'hide_empty'   => 0,
			] );

			$self = get_term( $childs, $args['taxonomy'] );

			array_unshift( $terms, $self );

		} else {

			// $terms = Taxonomy::getTerms( $args['taxonomy'], FALSE, TRUE );
			$terms = Taxonomy::listTerms( $args['taxonomy'], 'all' );
		}

		if ( ! count( $terms ) )
			return self::fieldEmptyTaxonomy( $args['taxonomy'], $args['edit'], $args['posttype'] );

		$html = $hidden = '';
		$tax  = get_taxonomy( $args['taxonomy'] );
		$atts = [ 'taxonomy' => $args['taxonomy'], 'atts' => $args ];

		if ( empty( $args['walker'] ) || ! ( $args['walker'] instanceof \Walker ) ) {

			$walker = new Misc\WalkerCategoryChecklist();

		} else {

			$walker = $args['walker'];
		}

		if ( is_array( $args['selected_cats'] ) )
			$atts['selected_cats'] = $args['selected_cats'];

		else if ( $object_id )
			$atts['selected_cats'] = wp_get_object_terms( $object_id, $args['taxonomy'], [ 'fields' => 'ids' ] );

		else
			$atts['selected_cats'] = [];

		if ( FALSE === $args['popular_cats'] )
			$atts['popular_cats'] = [];

		else if ( is_array( $args['popular_cats'] ) )
			$atts['popular_cats'] = $args['popular_cats'];

		else
			$atts['popular_cats'] = get_terms( [
				'taxonomy'     => $args['taxonomy'],
				'fields'       => 'ids',
				'orderby'      => 'count',
				'order'        => 'DESC',
				'number'       => 10,
				'hierarchical' => FALSE,
			] );

		$atts['disabled']      = ! current_user_can( $tax->cap->assign_terms );
		$atts['list_only']     = ! empty( $args['list_only'] );
		$atts['selected_only'] = ! empty( $args['selected_only'] );

		if ( is_null( $args['selected_preserve'] ) )
			$args['selected_preserve'] = ! $atts['disabled'];

		// preserves terms that hidden on the current list
		if ( $args['selected_preserve'] ) {

			$diff = array_diff( $atts['selected_cats'], wp_list_pluck( $terms, 'term_id' ) );

			foreach ( $diff as $term )
				$hidden.= '<input type="hidden" name="'.$args['name'].'['.$tax->name.'][]" value="'.$term.'" />';
		}

		if ( $args['checked_ontop'] || $atts['selected_only'] ) {

			// post process $terms rather than adding an exclude to
			// the get_terms() query to keep the query the same across
			// all posts (for any query cache)
			$checked = [];

			foreach ( array_keys( $terms ) as $key ) {
				if ( in_array( $terms[$key]->term_id, $atts['selected_cats'] ) ) {
					$checked[] = $terms[$key];
					unset( $terms[$key] );
				}
			}

			// put checked terms on top
			$html.= call_user_func_array( [ $walker, 'walk' ] , [ $checked, 0, $atts ] );
		}

		if ( ! $atts['selected_only'] )
			$html.= call_user_func_array( [ $walker, 'walk' ], [ $terms, 0, $atts ] );

		// allows for an empty term set to be sent. 0 is an invalid Term ID
		// and will be ignored by empty() checks
		if ( ! $args['list_only'] && ! $atts['disabled'] )
			$hidden.= '<input type="hidden" name="'.$args['name'].'['.$tax->name.'][]" value="0" />';

		if ( $html )
			$html = HTML::wrap( '<ul>'.$html.'</ul>', [ 'field-wrap', '-list', $args['field_class'] ] );

		$html.= $hidden;

		if ( ! $args['echo'] )
			return $html;

		echo $html;
	}

	public static function checklistUserTerms( $post_id = 0, $atts = [], $users = NULL, $threshold = 5 )
	{
		$args = self::args( $atts, [
			'taxonomy'          => NULL,
			'posttype'          => FALSE,
			'metabox'           => NULL,
			'edit'              => FALSE,
			'role'              => NULL,
			'list_only'         => NULL,
			'selected_only'     => NULL,
			'selected_preserve' => NULL, // keep hidden selected / NULL to check for assign cap
			'walker'            => NULL,
			'name'              => 'tax_input', // override if not saving by core
		] );

		if ( ! $args['taxonomy'] )
			return FALSE;

		if ( $args['metabox'] && self::checkHidden( $args['metabox'], $args['posttype'] ) )
			return FALSE;

		$selected = $post_id ? Taxonomy::getPostTerms( $args['taxonomy'], $post_id, FALSE, 'slug' ) : [];

		if ( is_null( $users ) )
			$users = User::get();

		if ( empty( $args['walker'] ) || ! ( $args['walker'] instanceof \Walker ) ) {

			$walker = new Misc\WalkerUserChecklist();

		} else {
			$walker = $args['walker'];
		}

		$html = $form = $list = $hidden = '';
		$id   = static::BASE.'-'.$args['taxonomy'].'-list';
		$tax  = get_taxonomy( $args['taxonomy'] );
		$atts = [ 'taxonomy' => $args['taxonomy'], 'atts' => $args, 'selected' => $selected ];

		$atts['disabled']      = ! current_user_can( $tax->cap->assign_terms );
		$atts['list_only']     = ! empty( $args['list_only'] );
		$atts['selected_only'] = ! empty( $args['selected_only'] );

		if ( ! $atts['list_only'] && count( $users ) > $threshold ) {

			$form.= HTML::tag( 'input', [
				'type'        => 'search',
				'class'       => [ '-search', 'hide-if-no-js' ],
				'placeholder' => _x( 'Search …', 'MetaBox: Checklist', 'geditorial' ),
			] );

			$form.= HTML::tag( 'button', [
				'type'  => 'button',
				'class' => [ '-button', 'button', 'button-small', '-sort', 'hide-if-no-js' ],
				'data'  => [ 'sort' => '-name' ],
				'title' => _x( 'Sort by name', 'MetaBox: Checklist', 'geditorial' ),
			], HTML::getDashicon( 'sort' ) );

			$html.= HTML::wrap( $form, 'field-wrap field-wrap-filter' );
		}

		if ( is_null( $args['selected_preserve'] ) )
			$args['selected_preserve'] = ! $atts['disabled'];

		// preserves users that hidden on the current list
		if ( $args['selected_preserve'] ) {

			$diff = array_diff( $selected, wp_list_pluck( $users, 'user_login' ) );

			foreach ( $diff as $term )
				$hidden.= '<input type="hidden" name="'.$args['name'].'['.$args['taxonomy'].'][]" value="'.$term.'" />';
		}

		if ( count( $selected ) ) {
			$checked = [];

			foreach ( array_keys( $users ) as $key ) {
				if ( in_array( $users[$key]->user_login, $selected ) ) {
					$checked[] = $users[$key];
					unset( $users[$key] );
				}
			}

			$list.= call_user_func_array( [ $walker, 'walk' ], [ $checked, 0, $atts ] );
		}

		if ( ! $atts['selected_only'] )
			$list.= call_user_func_array( [ $walker, 'walk' ], [ $users, 0, $atts ] );

		if ( ! $list ) {
			echo $hidden;
			return FALSE;
		}

		$html.= HTML::wrap( '<ul>'.$list.'</ul>', 'field-wrap -list' );

		// allows for an empty term set to be sent. 0 is an invalid Term ID
		// and will be ignored by empty() checks
		if ( ! $args['list_only'] && ! $atts['disabled'] )
			$hidden.= '<input type="hidden" name="'.$args['name'].'['.$args['taxonomy'].'][]" value="0" />';

		// ListJS needs the wrap!
		echo '<div id="'.$id.'" class="field-wrap -listjs">'.$html.'</div>';
		echo $hidden;

		if ( $form ) {

			$script = "(function($){List('".$id."', {
				listClass: '-list',
				searchClass: '-search',
				sortClass: '-sort',
				valueNames: [ '-name', '-email', '-login' ]
			});}(jQuery));";

			wp_add_inline_script( Scripts::pkgListJS( TRUE ), $script );
		}

		return TRUE;
	}

	public static function getTermPosts( $taxonomy, $term_or_id, $posttypes = NULL, $title = FALSE, $current = FALSE, $exclude = [] )
	{
		if ( ! $term_or_id || is_wp_error( $term_or_id ) )
			return '';

		if ( ! $term = Taxonomy::getTerm( $term_or_id, $taxonomy ) )
			return '';

		$args = [
			'posts_per_page' => -1,
			'orderby'        => [ 'menu_order', 'date' ],
			'order'          => 'ASC',
			'post_type'      => is_null( $posttypes ) ? 'any' : (array) $posttypes,
			'post_status'    => [ 'publish', 'future', 'pending', 'draft' ],
			'post__not_in'   => $exclude,
			'tax_query'      => [ [
				'taxonomy' => $taxonomy,
				'field'    => 'id',
				'terms'    => [ $term->term_id ],
			] ],
		];

		$posts = get_posts( $args );

		if ( empty( $posts ) )
			return FALSE;

		$html     = '';
		$statuses = Status::get();

		if ( TRUE === $title )
			$html.= HTML::tag( 'h4', Tablelist::getTermTitleRow( $term ) );

		else if ( $title )
			$html.= HTML::tag( 'h4', $title );

		$html.= '<ol>';

		foreach ( $posts as $post )
			$html.= '<li>'.Helper::getPostTitleRow( $post, ( $post->ID == $current ? FALSE : 'edit' ), $statuses ).'</li>';

		return HTML::wrap( $html.'</ol>', 'field-wrap -list' );
	}

	public static function fieldEmptyTaxonomy( $taxonomy, $edit = NULL, $posttype = FALSE )
	{
		if ( FALSE === $edit )
			return FALSE;

		$taxonomy = Taxonomy::object( $taxonomy );
		$extra    = $posttype ? [ 'post_type' => $posttype ] : [];

		if ( is_null( $edit ) )
			$edit = WordPress::getEditTaxLink( $taxonomy->name, FALSE, $extra );

		if ( $edit )
			$html = HTML::tag( 'a', [
				'href'   => $edit,
				'title'  => $taxonomy->labels->add_new_item,
				'target' => '_blank',
			], $taxonomy->labels->not_found );

		else
			$html = '<span>'.$taxonomy->labels->not_found.'</span>';

		echo HTML::wrap( $html, 'field-wrap -empty' );
	}

	public static function fieldEmptyPostType( $posttype )
	{
		$object = PostType::object( $posttype );

		$html = HTML::tag( 'a', [
			'href'   => WordPress::getPostNewLink( $posttype ),
			'title'  => $object->labels->add_new_item,
			'target' => '_blank',
		], $object->labels->not_found );

		echo HTML::wrap( $html, 'field-wrap -empty' );
	}

	public static function getTitleAction( $action )
	{
		return empty( $action['link'] ) ? '' : ' <span class="postbox-title-action"><a href="'.esc_url( $action['url'] ).'" title="'.$action['title'].'">'.$action['link'].'</a></span>';
	}

	public static function titleActionRefresh()
	{
		return self::getTitleAction( [
			'url'   => add_query_arg( 'flush', '' ),
			'title' => _x( 'Click to refresh the content', 'MetaBox: Title Action', 'geditorial' ),
			'link'  => _x( 'Refresh', 'MetaBox: Title Action', 'geditorial' ),
		] );
	}

	public static function titleActionInfo( $info )
	{
		if ( ! $info )
			return '';

		$html = ' <span class="postbox-title-action" data-tooltip="'.Text::wordWrap( $info ).'"';
		$html.= ' data-tooltip-pos="'.( HTML::rtl() ? 'down-left' : 'down-right' ).'"';
		$html.= ' data-tooltip-length="xlarge">'.HTML::getDashicon( 'info' ).'</span>';

		return $html;
	}

	// PAIRED API
	// @OLD: `dropdownAssocPostsSubTerms()`
	public static function paired_dropdownSubTerms( $taxonomy, $paired = 0, $prefix = '', $selected = 0, $none = NULL )
	{
		$name = sprintf( '%s[%s]', $prefix, $paired );

		if ( ! $terms = Taxonomy::getPostTerms( $taxonomy, $paired ) )
			return HTML::tag( 'input', [ 'type' => 'hidden', 'value' => '0', 'name' => $name ] );

		if ( is_null( $none ) )
			$none = Settings::showOptionNone();

		$html = HTML::dropdown( $terms, [
			'class'      => static::BASE.'-paired-subterms',
			'name'       => $name,
			'prop'       => 'name',
			'value'      => 'term_id',
			'selected'   => $selected,
			'none_title' => $none,
			'none_value' => '0',
			'data'       => [ 'paired' => $paired ],
		] );

		return HTML::wrap( $html, 'field-wrap -select' );
	}

	// PAIRED API
	// OLD: `dropdownAssocPostsRedux()`
	public static function paired_dropdownToPosts( $posttype, $taxonomy = FALSE, $paired = '0', $prefix = '', $exclude = [], $none = NULL, $display_empty = TRUE )
	{
		$args = [
			'post_type'    => $posttype,
			'post__not_in' => $exclude,
			'post_status'  => [ 'publish', 'future', 'draft', 'pending' ],
			'orderby'      => 'menu_order',
			'order'        => 'desc',

			'posts_per_page'         => -1,
			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		];

		// FIXME: WORKING BUT: creates problem with old setups
		// that main post is not connected to the paired term.
		// if ( $taxonomy )
		// 	$args['tax_query'] = [ [
		// 		'taxonomy' => $taxonomy,
		// 		'operator' => 'EXISTS',
		// 	] ];

		$query = new \WP_Query();
		$posts = $query->query( $args );

		if ( empty( $posts ) && ! $display_empty )
			return '';

		if ( is_null( $none ) )
			$none = Helper::getPostTypeLabel( $posttype, 'show_option_select' );

		$html = $none ? HTML::tag( 'option', [ 'value' => '0' ], $none ) : '';
		$html.= walk_page_dropdown_tree( $posts, 0, [
			'selected'          => $paired,
			'title_with_parent' => TRUE,
		] );

		$html = HTML::tag( 'select', [
			'name'  => ( $prefix ? $prefix.'-' : '' ).$posttype.'[]',
			'class' => [ static::BASE.'-paired-to-post-dropdown', empty( $posts ) ? 'hidden' : '' ],
			'data'  => [
				'type'   => $posttype,
				'paired' => $paired,
			],
		], $html );

		return $html ? HTML::wrap( $html, 'field-wrap -select' ) : '';
	}

	// FIXME: DEPRECATED
	public static function dropdownAssocPosts( $posttype, $selected = '', $prefix = '', $exclude = '' )
	{
		$html = wp_dropdown_pages( [
			'post_type'        => $posttype,
			'selected'         => $selected,
			'name'             => ( $prefix ? $prefix.'-' : '' ).$posttype.'[]',
			'id'               => ( $prefix ? $prefix.'-' : '' ).$posttype.'-'.( $selected ? $selected : '0' ),
			'class'            => static::BASE.'-admin-dropbown',
			'show_option_none' => Helper::getPostTypeLabel( $posttype, 'show_option_select' ),
			'sort_column'      => 'menu_order',
			'sort_order'       => 'desc',
			'post_status'      => [ 'publish', 'future', 'draft', 'pending' ],
			'value_field'      => 'post_name',
			'exclude'          => $exclude,
			'echo'             => 0,
			'walker'           => new Misc\WalkerPageDropdown(),
			'title_with_meta'  => 'number_line', // extra arg for the walker
		] );

		return $html ? HTML::wrap( $html, 'field-wrap -select' ) : '';
	}

	public static function fieldPostMenuOrder( $post )
	{
		$html = HTML::tag( 'input', [
			'type'        => 'number',
			'step'        => '1',
			'size'        => '4',
			'name'        => 'menu_order',
			'id'          => 'menu_order',
			'value'       => $post->menu_order,
			'title'       => _x( 'Order', 'MetaBox: Title Attr', 'geditorial' ),
			'placeholder' => _x( 'Order', 'MetaBox: Placeholder', 'geditorial' ),
			'class'       => 'small-text',
			'data'        => [ 'ortho' => 'number' ],
		] );

		echo HTML::wrap( $html, 'field-wrap -inputnumber' );
	}

	// @REF: `post_slug_meta_box()`
	public static function fieldPostSlug( $post )
	{
		$html = '<label class="screen-reader-text" for="post_name">'.__( 'Slug' ).'</label>';

		$html.= HTML::tag( 'input', [
			'type'        => 'text',
			'name'        => 'post_name',
			'id'          => 'post_name',
			'value'       => apply_filters( 'editable_slug', $post->post_name, $post ),
			'title'       => _x( 'Slug', 'MetaBox: Title Attr', 'geditorial' ),
			'placeholder' => _x( 'Slug', 'MetaBox: Placeholder', 'geditorial' ),
			'class'       => 'code-text',
		] );

		echo HTML::wrap( $html, 'field-wrap -inputcode' );
	}

	// @REF: `post_author_meta_box()`
	public static function fieldPostAuthor( $post )
	{
		$selected = empty( $post->ID ) ? $GLOBALS['user_ID'] : $post->post_author;

		$html = Listtable::restrictByAuthor( $selected, 'post_author_override', [
			'echo'            => FALSE,
			'class'           => static::BASE.'-admin-dropbown',
			'show_option_all' => '',
		] );

		if ( empty( $html ) )
			return;

		$label = '<label class="screen-reader-text" for="post_author_override">'.__( 'Author' ).'</label>';

		echo HTML::wrap( $label.$html, 'field-wrap -select' );
	}

	public static function fieldPostParent( $post, $check = TRUE, $name = NULL, $posttype = NULL, $statuses = [ 'publish', 'future', 'draft' ] )
	{
		// allows for a parent of diffrent type
		if ( is_null( $posttype ) )
			$posttype = $post->post_type;

		$object = PostType::object( $posttype );

		if ( $check && ! $object->hierarchical )
			return;

		$args = [
			'post_type'         => $posttype,
			'selected'          => $post->post_parent,
			'name'              => is_null( $name ) ? 'parent_id' : $name,
			'class'             => static::BASE.'-admin-dropbown',
			'show_option_none'  => Helper::getPostTypeLabel( $object, 'show_option_parent' ),
			'sort_column'       => 'menu_order, post_title',
			'sort_order'        => 'desc',
			'post_status'       => $statuses,
			'exclude_tree'      => $post->ID,
			'echo'              => 0,
			'walker'            => new Misc\WalkerPageDropdown(),
			'title_with_parent' => TRUE,
		];

		$html = wp_dropdown_pages( apply_filters( 'page_attributes_dropdown_pages_args', $args, $post ) );

		if ( $html )
			echo HTML::wrap( $html, 'field-wrap -select' );
	}

	public static function classEditorBox( $screen, $id = 'postexcerpt' )
	{
		add_filter( 'postbox_classes_'.$screen->id.'_'.$id, static function( $classes ) {
			return array_merge( $classes, [ static::BASE.'-wrap', '-admin-postbox', '-admin-postbox-editorbox' ] );
		} );
	}

	public static function fieldEditorBox( $content = '', $id = 'excerpt', $title = NULL, $atts = [] )
	{
		$args = self::args( $atts, [
			'media_buttons' => FALSE,
			'textarea_rows' => 5,
			'editor_class'  => 'editor-status-counts textarea-autosize i18n-multilingual', // qtranslate-x
			'teeny'         => TRUE,
			'tinymce'       => FALSE,
			'quicktags'     => [ 'buttons' => 'link,em,strong,li,ul,ol,code' ],
		] );

		if ( is_null( $title ) )
			$title = __( 'Excerpt' );

		echo '<div class="-wordcount-wrap">';

			echo '<label class="screen-reader-text" for="'.$id.'">'.$title.'</label>';

			wp_editor( html_entity_decode( $content ), $id, $args );

			Helper::renderEditorStatusInfo( $id );

		echo '</div>';

		Scripts::enqueueWordCount();
	}

	// FIXME: finalize name/id
	public static function dropdownPostTaxonomy( $taxonomy, $post, $key = FALSE, $count = TRUE, $excludes = '', $default = '0' )
	{
		if ( ! $obj = get_taxonomy( $taxonomy ) )
			return;

		if ( $default && ! is_numeric( $default ) ) {
			if ( $term = get_term_by( 'slug', $default, $taxonomy ) )
				$default = $term->term_id;
			else
				$default = '0';
		}

		if ( ! $selected = Taxonomy::theTerm( $taxonomy, $post->ID ) )
			$selected = $default;

		$terms = wp_dropdown_categories( [
			'taxonomy'          => $taxonomy,
			'selected'          => $selected,
			'show_option_none'  => Helper::getTaxonomyLabel( $taxonomy, 'show_option_select' ),
			'option_none_value' => '0',
			'class'             => static::BASE.'-admin-dropbown',
			'name'              => 'tax_input['.$taxonomy.'][]',
			'id'                => static::BASE.'-'.$taxonomy,
			// 'name'              => static::BASE.'-'.$taxonomy.( FALSE === $key ? '' : '['.$key.']' ),
			// 'id'                => static::BASE.'-'.$taxonomy.( FALSE === $key ? '' : '-'.$key ),
			'hierarchical'      => $obj->hierarchical,
			'orderby'           => 'name',
			'show_count'        => $count,
			'hide_empty'        => FALSE,
			'hide_if_empty'     => TRUE,
			'echo'              => FALSE,
			'exclude'           => $excludes,
		] );

		if ( $terms )
			echo HTML::tag( 'div', [
				'class' => '-wrap field-wrap -select',
				'title' => $obj->labels->menu_name,
			], $terms );
		else
			self::fieldEmptyTaxonomy( $obj, NULL, $post->post_type );
	}

	public static function glancePosttype( $posttype, $noop, $extra_class = '' )
	{
		$posts = Database::countPostsByPosttype( $posttype );

		if ( ! $posts['publish'] )
			return FALSE;

		$object = PostType::object( $posttype );

		$class  = HTML::prepClass( 'geditorial-glance-item', '-posttype', '-posttype-'.$posttype, $extra_class );
		$format = current_user_can( $object->cap->edit_posts )
			? '<a class="'.$class.'" href="edit.php?post_type=%3$s">%1$s %2$s</a>'
			: '<div class="'.$class.'">%1$s %2$s</div>';

		return vsprintf( $format, [
			Number::format( $posts['publish'] ),
			Helper::noopedCount( $posts['publish'], $noop ),
			$posttype,
		] );
	}

	public static function glanceTaxonomy( $taxonomy, $noop, $extra_class = '' )
	{
		if ( ! $terms = Taxonomy::hasTerms( $taxonomy ) )
			return FALSE;

		$object = get_taxonomy( $taxonomy );

		$class  = HTML::prepClass( 'geditorial-glance-item', '-tax', '-taxonomy-'.$taxonomy, $extra_class );
		$format = current_user_can( $object->cap->manage_terms )
			? '<a class="'.$class.'" href="edit-tags.php?taxonomy=%3$s">%1$s %2$s</a>'
			: '<div class="'.$class.'">%1$s %2$s</div>';

		return vsprintf( $format, [
			Number::format( $terms ),
			Helper::noopedCount( $terms, $noop ),
			$taxonomy,
		] );
	}

	public static function tableRowObjectTaxonomy( $taxonomy, $object_id = 0, $name = NULL, $edit = NULL, $before = '', $after = '' )
	{
		if ( ! $object = Taxonomy::object( $taxonomy ) )
			return FALSE;

		if ( ! current_user_can( $object->cap->assign_terms ) )
			return FALSE;

		echo $before.'<tr class="form-field"><th scope="row">'.HTML::escape( $object->label ).'</th><td>';

		self::checklistTerms( $object_id, [
			'field_class' => 'wp-tab-panel',
			'taxonomy'    => $object->name,
			'edit'        => $edit,
			'name'        => $name ?: sprintf( '%s-object_tax', static::BASE )
		] );

		echo '</td></tr>'.$after;
	}

	public static function storeObjectTaxonomy( $taxonomy, $object_id, $data = NULL, $name = NULL, $check = TRUE )
	{
		if ( ! $object = Taxonomy::object( $taxonomy ) )
			return FALSE;

		if ( $check && ! current_user_can( $object->cap->assign_terms ) )
			return FALSE;

		if ( is_null( $name ) )
			$name = sprintf( '%s-object_tax', static::BASE );

		if ( is_null( $data ) )
			$data = self::req( $name, [] );

		// for clearing must send `0` as term_id
		if ( empty( $data ) || ! is_array( $data ) )
			return FALSE;

		if ( ! array_key_exists( $object->name, $data ) )
			return FALSE;

		$result = wp_set_object_terms( $object_id, Arraay::prepNumeral( $data[$object->name] ), $object->name, FALSE );

		clean_object_term_cache( $object_id, $object->name );

		return $result;
	}

	public static function getFieldDefaults( $field )
	{
		return [
			'name'        => $field,
			'rest'        => $field, // FALSE to disable
			'title'       => NULL, // self::getString( $field, $posttype, 'titles', $field ),
			'description' => NULL, // self::getString( $field, $posttype, 'descriptions' ),
			'sanitize'    => NULL,
			'pattern'     => NULL, // HTML5 input pattern
			'default'     => '', // currently only on rest
			'icon'        => 'smiley',
			'type'        => 'text',
			'context'     => NULL, // default is `mainbox`
			'quickedit'   => FALSE,
			'values'      => [],
			'repeat'      => FALSE,
			'ltr'         => FALSE,
			'taxonomy'    => FALSE,
			'posttype'    => FALSE,
			'role'        => FALSE,
			'group'       => 'general',
			'order'       => 1000,
		];
	}

	public static function renderFieldSelect( $field, $post = NULL, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = PostType::getPost( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$html     = '';
		$args     = self::atts( self::getFieldDefaults( $field['name'] ), $field );
		$selected = Template::getMetaFieldRaw( $args['name'], $post->ID, $module, FALSE );

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'] );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions' );

		foreach ( $args['values'] as $value => $label )
			$html.= HTML::tag( 'option', [
				'selected' => $selected == $value,
				'value'    => $value,
			], $label );

		$atts = [
			'name'  => sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title' => $args['title'],
			'class' => [
				sprintf( '%s-select', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
			],
			'data' => [
				'meta-field' => $args['name'],
				'meta-type'  => $args['type'],
				'meta-title' => $args['title'],
			],
		];

		echo HTML::wrap( HTML::tag( 'select', $atts, $html ), 'field-wrap -select' );
	}

	public static function renderFieldDate( $field, $post = NULL, $module = NULL, $calendar = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = PostType::getPost( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$args = self::atts( self::getFieldDefaults( $field['name'] ), $field );

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'] );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions' );

		$value = Template::getMetaFieldRaw( $args['name'], $post->ID, $module, FALSE );

		$atts = [
			'type'        => 'text',
			'value'       => $value ? Datetime::prepForInput( $value, 'Y/m/d', 'gregorian' ) : '',
			'name'        => sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title'       => $args['description'],
			'placeholder' => $args['title'],
			'class'       => [
				sprintf( '%s-inputdate', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
			],
			'data' => [
				'meta-field' => $args['name'],
				'meta-type'  => $args['type'],
				'meta-title' => $args['title'],
				'ortho'      => 'date',
			],
		];

		echo HTML::wrap( HTML::tag( 'input', $atts ), 'field-wrap -inputdate' );
	}

	// TODO: utilize the pattern!
	public static function renderFieldIdentity( $field, $post = NULL, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = PostType::getPost( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$args = self::atts( self::getFieldDefaults( $field['name'] ), $field );

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'] );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions' );

		$value = Template::getMetaFieldRaw( $args['name'], $post->ID, $module, FALSE );

		$atts = [
			'type'        => 'text',
			'value'       => $value ?: '',
			'name'        => sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title'       => $args['description'],
			'placeholder' => $args['title'],
			'class'       => [
				sprintf( '%s-inputidentity', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
			],
			'data' => [
				'meta-field' => $args['name'],
				'meta-type'  => $args['type'],
				'meta-title' => $args['title'],
				'ortho'      => 'identity',
			],
		];

		echo HTML::wrap( HTML::tag( 'input', $atts ), 'field-wrap -inputidentity' );
	}

	// TODO: utilize the pattern!
	public static function renderFieldIBAN( $field, $post = NULL, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = PostType::getPost( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$args = self::atts( self::getFieldDefaults( $field['name'] ), $field );

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'] );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions' );

		$value = Template::getMetaFieldRaw( $args['name'], $post->ID, $module, FALSE );

		$atts = [
			'type'        => 'text',
			'value'       => $value ?: '',
			'name'        => sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title'       => $args['description'],
			'placeholder' => $args['title'],
			'class'       => [
				sprintf( '%s-inputiban', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
			],
			'data' => [
				'meta-field' => $args['name'],
				'meta-type'  => $args['type'],
				'meta-title' => $args['title'],
				'ortho'      => 'iban',
			],
		];

		echo HTML::wrap( HTML::tag( 'input', $atts ), 'field-wrap -inputiban' );
	}

	// just like any other meta-field but stores in `$post->post_parent`
	public static function renderFieldPostParent( $field, $post = NULL, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = PostType::getPost( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$html = '';
		$args = self::atts( self::getFieldDefaults( $field['name'] ), $field );

		if ( ! $args['posttype'] )
			$args['posttype'] = $post->post_type;

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'] );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions' );

		if ( $post->post_parent && ( $parent = PostType::getPost( $post->post_parent ) ) )
			$html.= HTML::tag( 'option', [
				'selected' => TRUE,
				'value'    => $parent->ID,
			], PostType::getPostTitle( $parent ) );

		$atts = [
			'name'  => 'parent_id', // sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title' => $args['title'],
			'class' => [
				sprintf( '%s-selectsingle', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
			],
			'data' => [
				'meta-field' => $args['name'],
				'meta-type'  => $args['type'],
				'meta-title' => $args['title'],

				'query-target'   => 'post',
				'query-posttype' => $args['posttype'] ? implode( ',', (array) $args['posttype'] ) : FALSE,
				'query-taxonomy' => $args['taxonomy'] ? implode( ',', (array) $args['taxonomy'] ) : FALSE,
				'query-role'     => $args['role']     ? implode( ',', (array) $args['role'] )     : FALSE,

				'selectsingle-placeholder' => $args['title'],
			],
		];

		echo HTML::wrap( HTML::tag( 'select', $atts, $html ), 'field-wrap -select hide-no-js' );

		return Services\SelectSingle::enqueue();
	}

	public static function renderFieldPost( $field, $post = NULL, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = PostType::getPost( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$html = '';
		$args = self::atts( self::getFieldDefaults( $field['name'] ), $field );

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'] );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions' );

		if ( $value = self::getPostMeta( $post->ID, $args['name'], '' ) )
			$html.= HTML::tag( 'option', [
				'selected' => TRUE,
				'value'    => $value,
			], PostType::getPostTitle( $value ) );

		$atts = [
			'name'  => sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title' => $args['title'],
			'class' => [
				sprintf( '%s-selectsingle', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
			],
			'data' => [
				'meta-field' => $args['name'],
				'meta-type'  => $args['type'],
				'meta-title' => $args['title'],

				'query-target'   => 'post',
				'query-posttype' => $args['posttype'] ? implode( ',', (array) $args['posttype'] ) : FALSE,
				'query-taxonomy' => $args['taxonomy'] ? implode( ',', (array) $args['taxonomy'] ) : FALSE,
				'query-role'     => $args['role']     ? implode( ',', (array) $args['role'] )     : FALSE,

				'selectsingle-placeholder' => $args['title'],
			],
		];

		echo HTML::wrap( HTML::tag( 'select', $atts, $html ), 'field-wrap -select hide-no-js' );

		return Services\SelectSingle::enqueue();
	}

	public static function renderFieldTerm( $field, $post = NULL, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = PostType::getPost( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$html = '';
		$args = self::atts( self::getFieldDefaults( $field['name'] ), $field );

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'] );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions' );

		if ( $value = self::getPostMeta( $post->ID, $args['name'], '' ) )
			$html.= HTML::tag( 'option', [
				'selected' => TRUE,
				'value'    => $value,
			], Taxonomy::getTermTitle( $value ) );

		$atts = [
			'name'  => sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title' => $args['title'],
			'class' => [
				sprintf( '%s-selectsingle', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
			],
			'data' => [
				'meta-field' => $args['name'],
				'meta-type'  => $args['type'],
				'meta-title' => $args['title'],

				'query-target'   => 'term',
				'query-posttype' => $args['posttype'] ? implode( ',', (array) $args['posttype'] ) : FALSE,
				'query-taxonomy' => $args['taxonomy'] ? implode( ',', (array) $args['taxonomy'] ) : FALSE,
				'query-role'     => $args['role']     ? implode( ',', (array) $args['role'] )     : FALSE,

				'selectsingle-placeholder' => $args['title'],
			],
		];

		echo HTML::wrap( HTML::tag( 'select', $atts, $html ), 'field-wrap -select hide-no-js' );

		return Services\SelectSingle::enqueue();
	}


	public static function renderFieldUser( $field, $post = NULL, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = PostType::getPost( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$html = '';
		$args = self::atts( self::getFieldDefaults( $field['name'] ), $field );

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'] );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions' );

		if ( $value = self::getPostMeta( $post->ID, $args['name'], '' ) )
			$html.= HTML::tag( 'option', [
				'selected' => TRUE,
				'value'    => $value,
			], User::getTitleRow( (int) $value,
				/* translators: %s: user id number */
				sprintf( _x( 'Unknown User #%s', 'MetaBox: Title Attr', 'geditorial' ), $value ) ) );

		$atts = [
			'name'  => sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title' => $args['title'],
			'class' => [
				sprintf( '%s-selectsingle', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
			],
			'data' => [
				'meta-field' => $args['name'],
				'meta-type'  => $args['type'],
				'meta-title' => $args['title'],

				'query-target'   => 'user',
				'query-posttype' => $args['posttype'] ? implode( ',', (array) $args['posttype'] ) : FALSE,
				'query-taxonomy' => $args['taxonomy'] ? implode( ',', (array) $args['taxonomy'] ) : FALSE,
				'query-role'     => $args['role']     ? implode( ',', (array) $args['role'] )     : FALSE,

				'selectsingle-placeholder' => $args['title'],
			],
		];

		echo HTML::wrap( HTML::tag( 'select', $atts, $html ), 'field-wrap -select hide-no-js' );

		return Services\SelectSingle::enqueue();
	}
}
