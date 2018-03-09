<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\User;

class MetaBox extends Core\Base
{

	const BASE   = 'geditorial';
	const MODULE = FALSE;

	protected static function constant( $key, $default = FALSE )
	{
		return gEditorial()->constant( static::MODULE, $key, $default );
	}

	protected static function getString( $string, $posttype = 'post', $group = 'titles', $fallback = FALSE )
	{
		return gEditorial()->{static::MODULE}->get_string( $string, $posttype, $group, $fallback );
	}

	protected static function getPostMeta( $post_id, $field = FALSE, $default = '', $key = NULL )
	{
		return gEditorial()->{static::MODULE}->get_postmeta( $post_id, $field, $default, $key );
	}

	public static function checkHidden( $metabox_id, $after = '' )
	{
		if ( ! in_array( $metabox_id, get_hidden_meta_boxes( get_current_screen() ) ) )
			return FALSE;

		echo HTML::tag( 'a', [
			'href'  => add_query_arg( 'flush', '' ),
			'class' => [ '-description', '-refresh' ],
		], _x( 'Please refresh the page to generate the data.', 'MetaBox: Refresh Link', GEDITORIAL_TEXTDOMAIN ) );

		echo $after;

		return TRUE;
	}

	// TODO: radio list box using custom walker
	// CAUTION: tax must be cat (hierarchical)
	// hierarchical taxonomies save by IDs,
	// whereas non-hierarchical save by slugs
	// WTF: because the core's not passing args into the waker!
	// @REF: `post_categories_meta_box()`, `wp_terms_checklist()`
	public static function checklistTerms( $post_id = 0, $atts = [] )
	{
		$atts = apply_filters( 'wp_terms_checklist_args', $atts, $post_id );

		$args = self::args( $atts, [
			'taxonomy'             => NULL,
			'metabox'              => NULL, // metabox id to check for disabled
			'list_only'            => NULL,
			'selected_only'        => NULL,
			'selected_preserve'    => NULL, // keep hidden selected / NULL to check for assign cap
			'descendants_and_self' => 0,
			'selected_cats'        => FALSE,
			'popular_cats'         => FALSE,
			'walker'               => NULL,
			'checked_ontop'        => TRUE,
			'edit'                 => NULL, // manage page if has no terms, FALSE to disable
			'role'                 => FALSE, // `disabled` / `hidden`
			'name'                 => 'tax_input', // override if not saving by core
			'walker'               => FALSE,
			'echo'                 => TRUE,
		] );

		if ( ! $args['taxonomy'] )
			return FALSE;

		if ( $args['metabox'] && self::checkHidden( $args['metabox'] ) )
			return;

		if ( $args['descendants_and_self'] ) {

			$childs = intval( $args['descendants_and_self'] );

			$terms = (array) get_terms( $args['taxonomy'], [
				'child_of'     => $childs,
				'hierarchical' => 0,
				'hide_empty'   => 0,
			] );

			$self = get_term( $childs, $args['taxonomy'] );

			array_unshift( $terms, $self );

		} else {

			$terms = Taxonomy::getTerms( $args['taxonomy'], FALSE, TRUE );
		}

		if ( ! count( $terms ) )
			return self::fieldEmptyTaxonomy( $args['taxonomy'], $args['edit'] );

		$html = $hidden = '';
		$tax  = get_taxonomy( $args['taxonomy'] );
		$atts = [ 'taxonomy' => $args['taxonomy'], 'atts' => $args ];

		if ( empty( $args['walker'] ) || ! ( $args['walker'] instanceof \Walker ) ) {

			gEditorial()->files( 'misc/walker-category-checklist' );
			$walker = new Misc\Walker_Category_Checklist;

		} else {
			$walker = $args['walker'];
		}

		if ( is_array( $args['selected_cats'] ) )
			$atts['selected_cats'] = $args['selected_cats'];

		else if ( $post_id )
			$atts['selected_cats'] = wp_get_object_terms( $post_id, $args['taxonomy'], [ 'fields' => 'ids' ] );

		else
			$atts['selected_cats'] = [];

		if ( FALSE === $args['popular_cats'] )
			$atts['popular_cats'] = [];

		else if ( is_array( $args['popular_cats'] ) )
			$atts['popular_cats'] = $args['popular_cats'];

		else
			$atts['popular_cats'] = get_terms( $args['taxonomy'], [
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
			$html = HTML::wrap( '<ul>'.$html.'</ul>', 'field-wrap field-wrap-list' );

		$html.= $hidden;

		if ( ! $args['echo'] )
			return $html;

		echo $html;
	}

	public static function checklistUserTerms( $post_id = 0, $atts = [], $users = NULL, $threshold = 5 )
	{
		$args = self::args( $atts, [
			'taxonomy'          => NULL,
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

		if ( $args['metabox'] && self::checkHidden( $args['metabox'] ) )
			return FALSE;

		$selected = $post_id ? Taxonomy::getTerms( $args['taxonomy'], $post_id, FALSE, 'slug' ) : [];

		if ( is_null( $users ) )
			$users = User::get();

		if ( empty( $args['walker'] ) || ! ( $args['walker'] instanceof \Walker ) ) {

			gEditorial()->files( 'misc/walker-user-checklist' );
			$walker = new Misc\Walker_User_Checklist;

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
				'placeholder' => _x( 'Search …', 'MetaBox: Checklist', GEDITORIAL_TEXTDOMAIN ),
			] );

			$form.= HTML::tag( 'button', [
				'type'  => 'button',
				'class' => [ '-button', 'button', 'button-small', '-sort', 'hide-if-no-js' ],
				'data'  => [ 'sort' => '-name' ],
				'title' => _x( 'Sort by name', 'MetaBox: Checklist', GEDITORIAL_TEXTDOMAIN ),
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

		$html.= HTML::wrap( '<ul class="-list">'.$list.'</ul>', 'field-wrap field-wrap-list' );

		// allows for an empty term set to be sent. 0 is an invalid Term ID
		// and will be ignored by empty() checks
		if ( ! $args['list_only'] && ! $atts['disabled'] )
			$hidden.= '<input type="hidden" name="'.$args['name'].'['.$args['taxonomy'].'][]" value="0" />';

		// ListJS needs the wrap!
		echo '<div id="'.$id.'" class="field-wrap-listjs">'.$html.'</div>';
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

	public static function getTermPosts( $taxonomy, $term_or_id, $exclude = [], $title = TRUE )
	{
		if ( ! $term_or_id || is_wp_error( $term_or_id ) )
			return '';

		if ( ! $term = Taxonomy::getTerm( $term_or_id, $taxonomy ) )
			return '';

		$args = [
			'posts_per_page' => -1,
			'orderby'        => [ 'menu_order', 'date' ],
			'order'          => 'ASC',
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
		$statuses = PostType::getStatuses();

		if ( $title )
			$html.= '<h4>'.Helper::getTermTitleRow( $term ).'</h4>';

		$html.= '<ol>';

		foreach ( $posts as $post )
			$html.= '<li>'.Helper::getPostTitleRow( $post, 'edit', $statuses ).'</li>';

		return HTML::wrap( $html.'</ol>', 'field-wrap field-wrap-list' );
	}

	public static function fieldEmptyTaxonomy( $taxonomy, $edit = NULL )
	{
		if ( FALSE === $edit )
			return FALSE;

		if ( ! is_object( $taxonomy ) )
			$taxonomy = get_taxonomy( $taxonomy );

		if ( is_null( $edit ) )
			$edit = WordPress::getEditTaxLink( $taxonomy->name );

		if ( $edit )
			$html = HTML::tag( 'a', [
				'href'   => $edit,
				'title'  => $taxonomy->labels->add_new_item,
				'target' => '_blank',
			], $taxonomy->labels->not_found );

		else
			$html = '<span>'.$taxonomy->labels->not_found.'</span>';

		echo HTML::wrap( $html, 'field-wrap field-wrap-empty' );
	}

	public static function fieldEmptyPostType( $posttype )
	{
		$object = PostType::object( $posttype );

		$html = HTML::tag( 'a', [
			'href'   => WordPress::getPostNewLink( $posttype ),
			'title'  => $object->labels->add_new_item,
			'target' => '_blank',
		], $object->labels->not_found );

		echo HTML::wrap( $html, 'field-wrap field-wrap-empty' );
	}

	public static function titleActionRefresh()
	{
		$html = ' <span class="postbox-title-action"><a href="'.esc_url( add_query_arg( 'flush', '' ) ).'"';
		$html.= ' title="'._x( 'Click to refresh the content', 'MetaBox: Title Action', GEDITORIAL_TEXTDOMAIN ).'">';
		$html.= _x( 'Refresh', 'MetaBox: Title Action', GEDITORIAL_TEXTDOMAIN ).'</a></span>';

		return $html;
	}

	public static function dropdownAssocPosts( $posttype, $selected = '', $prefix = '', $exclude = '' )
	{
		gEditorial()->files( 'misc/walker-page-dropdown' );

		$html = wp_dropdown_pages( [
			'post_type'        => $posttype,
			'selected'         => $selected,
			'name'             => ( $prefix ? $prefix.'-' : '' ).$posttype.'[]',
			'id'               => ( $prefix ? $prefix.'-' : '' ).$posttype.'-'.( $selected ? $selected : '0' ),
			'class'            => static::BASE.'-admin-dropbown',
			'show_option_none' => Settings::showOptionNone(),
			'sort_column'      => 'menu_order',
			'sort_order'       => 'desc',
			'post_status'      => [ 'publish', 'future', 'draft', 'pending' ],
			'value_field'      => 'post_name',
			'exclude'          => $exclude,
			'echo'             => 0,
			'walker'           => new Misc\Walker_PageDropdown(),
			'title_with_meta'  => 'issue_number_line', // extra arg for the walker
		] );

		return $html ? HTML::wrap( $html, 'field-wrap field-wrap-select' ) : FALSE;
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
			'title'       => _x( 'Order', 'MetaBox: Title Attr', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Order', 'MetaBox: Placeholder', GEDITORIAL_TEXTDOMAIN ),
			'class'       => 'small-text',
			'data'        => [ 'ortho' => 'number' ],
		] );

		echo HTML::wrap( $html, 'field-wrap field-wrap-inputnumber' );
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
			'title'       => _x( 'Slug', 'MetaBox: Title Attr', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Slug', 'MetaBox: Placeholder', GEDITORIAL_TEXTDOMAIN ),
			'class'       => 'code-text',
		] );

		echo HTML::wrap( $html, 'field-wrap field-wrap-inputcode' );
	}

	// @REF: `post_author_meta_box()`
	public static function fieldPostAuthor( $post )
	{
		global $user_ID;

		$args = [
			'who'              => 'authors',
			'name'             => 'post_author_override',
			'selected'         => empty( $post->ID ) ? $user_ID : $post->post_author,
			'include_selected' => TRUE,
			'show'             => 'display_name_with_login',
			'class'            => static::BASE.'-admin-dropbown',
			'echo'             => 0,
		];

		if ( ! $html = wp_dropdown_users( $args ) )
			return;

		$html = '<label class="screen-reader-text" for="post_author_override">'.__( 'Author' ).'</label>'.$html;

		echo HTML::wrap( $html, 'field-wrap field-wrap-select' );
	}

	public static function fieldPostParent( $post, $check = TRUE, $posttype = NULL, $statuses = [ 'publish', 'future', 'draft' ] )
	{
		// allows for a parent of diffrent type
		if ( is_null( $posttype ) )
			$posttype = $post->post_type;

		if ( $check && ! PostType::object( $posttype )->hierarchical )
			return;

		$args = [
			'post_type'        => $posttype,
			'selected'         => $post->post_parent,
			'name'             => 'parent_id',
			'class'            => static::BASE.'-admin-dropbown',
			'show_option_none' => _x( '&mdash; no parent &mdash;', 'MetaBox: Parent Dropdown: Select Option None', GEDITORIAL_TEXTDOMAIN ),
			'sort_column'      => 'menu_order, post_title',
			'sort_order'       => 'desc',
			'post_status'      => $statuses,
			'exclude_tree'     => $post->ID,
			'echo'             => 0,
		];

		$html = wp_dropdown_pages( apply_filters( 'page_attributes_dropdown_pages_args', $args, $post ) );

		if ( $html )
			echo HTML::wrap( $html, 'field-wrap field-wrap-select' );
	}

	public static function fieldPostExcerpt( $post, $title = '', $locked = FALSE )
	{
		if ( is_null( $title ) )
			$title = __( 'Excerpt' );

		$html = '<div id="postexcerpt" class="postbox '.static::BASE.'-wrap -admin-postbox">';
		$html.= '<button type="button" class="handlediv button-link" aria-expanded="true">';
		$html.= '<span class="screen-reader-text">'.esc_attr_x( 'Click to toggle', 'MetaBox', GEDITORIAL_TEXTDOMAIN ).'</span>';
		$html.= '<span class="toggle-indicator" aria-hidden="true"></span></button>';
		$html.= '<h2 class="hndle"><span>'.$title.'</span></h2><div class="inside">';
		$html.= '<div class="'.static::BASE.'-admin-wrap-textbox '.static::BASE.'-wordcount-wrap">';
		$html.= '<div class="-wrap field-wrap field-wrap-textarea">';
		$html.= '<label class="screen-reader-text" for="excerpt">'.$title.'</label>';

		if ( ! $locked )
			$html.= '<textarea rows="1" cols="40" name="excerpt" id="excerpt">';

		if ( $locked )
			$html.= HTML::wrap( Text::autoP( $post->post_excerpt ), '-excerpt' );
		else
			$html.= $post->post_excerpt; // textarea_escaped

		if ( ! $locked )
			$html.= '</textarea>';

		if ( ! $locked )
			$html.= Helper::htmlWordCount( 'excerpt', $post->post_type );

		$html.= '</div></div></div></div>';

		echo $html;
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
			'show_option_none'  => Settings::showOptionNone( $obj->labels->menu_name ),
			'option_none_value' => '0',
			'class'             => static::BASE.'-admin-dropbown',
			'name'              => 'tax_input['.$taxonomy.'][]',
			'id'                => static::BASE.'-'.$taxonomy,
			// 'name'              => static::BASE.'-'.$this->module->name.'-'.$taxonomy.( FALSE === $key ? '' : '['.$key.']' ),
			// 'id'                => static::BASE.'-'.$this->module->name.'-'.$taxonomy.( FALSE === $key ? '' : '-'.$key ),
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
				'class' => '-wrap field-wrap field-wrap-select',
				'title' => $obj->labels->menu_name,
			], $terms );
		else
			self::fieldEmptyTaxonomy( $obj, NULL );
	}

	public static function glancePosttype( $posttype, $noop, $extra_class = '' )
	{
		$posts = wp_count_posts( $posttype );

		if ( ! $posts->publish )
			return FALSE;

		$object = PostType::object( $posttype );

		$class  = 'geditorial-glance-item -posttype -posttype-'.$posttype.' '.$extra_class;
		$format = current_user_can( $object->cap->edit_posts )
			? '<a class="'.$class.'" href="edit.php?post_type=%3$s">%1$s %2$s</a>'
			: '<div class="'.$class.'">%1$s %2$s</div>';

		return vsprintf( $format, [
			Number::format( $posts->publish ),
			Helper::noopedCount( $posts->publish, $noop ),
			$posttype,
		] );
	}

	public static function glanceTaxonomy( $taxonomy, $noop, $extra_class = '' )
	{
		if ( ! $terms = wp_count_terms( $taxonomy ) )
			return FALSE;

		$object = get_taxonomy( $taxonomy );

		$class  = 'geditorial-glance-item -tax -taxonomy-'.$taxonomy.' '.$extra_class;
		$format = current_user_can( $object->cap->manage_terms )
			? '<a class="'.$class.'" href="edit-tags.php?taxonomy=%3$s">%1$s %2$s</a>'
			: '<div class="'.$class.'">%1$s %2$s</div>';

		return vsprintf( $format, [
			Number::format( $terms ),
			Helper::noopedCount( $terms, $noop ),
			$taxonomy,
		] );
	}
}
