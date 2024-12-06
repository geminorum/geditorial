<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class MetaBox extends WordPress\Main
{

	const BASE = 'geditorial';

	const POSTTYPE_MAINBOX_PROP = 'editorial_mainbox';

	public static function checkHidden( $metabox_id, $posttype = FALSE, $after = '' )
	{
		if ( ! $metabox_id )
			return FALSE;

		if ( $posttype && WordPress\PostType::supportBlocks( $posttype ) )
			return FALSE;

		if ( ! in_array( $metabox_id, get_hidden_meta_boxes( get_current_screen() ) ) )
			return FALSE;

		$html = Core\HTML::tag( 'a', [
			'href'  => add_query_arg( 'flush', '' ),
			'class' => [ '-description', '-refresh' ],
		], _x( 'Please refresh the page to generate the data.', 'MetaBox: Refresh Link', 'geditorial' ) );

		echo Core\HTML::wrap( $html, 'field-wrap -needs-refresh' ).$after;

		return TRUE;
	}

	// FIXME: adopt `wp_dropdown_categories()`
	// FIXME: add taxonomy `title::description` as dropdown title attr
	public static function singleselectTerms( $object_id = 0, $atts = [], $terms = NULL )
	{
		$args = self::args( $atts, [
			'taxonomy'    => NULL,
			'posttype'    => FALSE,
			'restricted'  => FALSE,   // `disabled` / `hidden` / FALSE
			'with_meta'   => NULL,    // override name meta field
			'with_parent' => NULL,    // `NULL` for hierarchical
			'empty_link'  => NULL,    // `NULL` for cap check or string for edit link, `FALSE` for disable
			'echo'        => TRUE,
			'name'        => NULL,
			'none'        => NULL,    // `NULL` for label check, `FALSE` for disable
			'empty'       => FALSE,   // `NULL` for empty box, `FALSE` for disable
		] );

		if ( ! $args['taxonomy'] || ( ! $taxonomy = WordPress\Taxonomy::object( $args['taxonomy'] ) ) )
			return FALSE;

		$selected = $object_id ? wp_get_object_terms( $object_id, $taxonomy->name, [
			'fields' => $taxonomy->hierarchical ? 'ids' : 'slugs'
		] ) : [];

		$reversed = empty( $taxonomy->{Services\TermHierarchy::REVERSE_ORDERED_TERMS} )
			? FALSE
			: $taxonomy->{Services\TermHierarchy::REVERSE_ORDERED_TERMS};

		$dropdown = [
			'taxonomy'          => $taxonomy->name,
			'selected'          => count( $selected ) ? $selected[0] : '0',
			'hierarchical'      => $taxonomy->hierarchical,
			'value'             => $taxonomy->hierarchical ? 'term_id' : 'slug',
			'name'              => $args['name'] ?? 'tax_input['.$taxonomy->name.'][]',
			'include'           => $terms ?? [],
			'order'             => $reversed ? 'DESC' : 'ASC',
			'orderby'           => $reversed ?: 'id',
			'show_option_none'  => $args['none'] ?? Helper::getTaxonomyLabel( $taxonomy, 'show_option_all' ),
			'option_none_value' => '0',
			'show_count'        => FALSE,
			'hide_empty'        => FALSE,
			'hide_if_empty'     => TRUE,
			'title_with_meta'   => $args['with_meta'] ?? FALSE,
			'title_with_parent' => $args['with_parent'] ?? $taxonomy->hierarchical,
			'restricted'        => $args['restricted'],
			'walker'            => new Misc\WalkerCategoryDropdown(),
			'class'             => static::BASE.'-admin-dropbown -dropdown-with-reset',
			'echo'              => FALSE,
			'disabled'          => ! current_user_can( $taxonomy->cap->assign_terms ),
		];

		if ( ! $html = wp_dropdown_categories( $dropdown ) )
			return self::fieldEmptyTaxonomy( $taxonomy->name, $args['empty_link'], $args['posttype'], $args['echo'] );

		if ( ! $args['echo'] )
			return;

		echo Core\HTML::wrap( $html, 'field-wrap -select' );
	}

	// FIXME: DROP THIS
	public static function singleselectTerms_OLD( $object_id = 0, $atts = [], $terms = NULL )
	{
		$args = self::args( $atts, [
			'taxonomy' => NULL,
			'posttype' => FALSE,
			'echo'     => TRUE,
			'none'     => NULL,    // `NULL` for label check, `FALSE` for disable
			'empty'    => FALSE,   // `NULL` for empty box, `FALSE` for disable
		] );

		if ( ! $args['taxonomy'] || ( ! $taxonomy = WordPress\Taxonomy::object( $args['taxonomy'] ) ) )
			return FALSE;

		if ( ! is_null( $terms ) ) {

			// FIXME: make sure it's a list of objects

		} else {

			// TODO: if `hierarchical` the title must have parent prefixes
			// @SEE: `PostType::getParentTitles()`
			// - e.g. tree list select

			// $terms = WordPress\Taxonomy::getTerms( $taxonomy->name, FALSE, TRUE );
			$terms = WordPress\Taxonomy::listTerms( $taxonomy->name, 'all' );
		}

		if ( empty( $terms ) ) {

			if ( is_null( $args['empty'] ) )
				return self::fieldEmptyTaxonomy( $taxonomy->name, NULL, $args['posttype'], $args['echo'] );

			if ( $args['empty'] && ! $args['echo'] )
				return $args['empty'];

			if ( $args['empty'] )
				echo $args['empty'];

			return FALSE;
		}

		if ( is_null( $args['none'] ) )
			$args['none'] = Helper::getTaxonomyLabel( $taxonomy, 'show_option_select' );

		$selected = wp_get_object_terms( $object_id, $taxonomy->name, [ 'fields' => $taxonomy->hierarchical ? 'ids' : 'slugs' ] );

		$dropdown = [
			'selected'   => count( $selected ) ? $selected[0] : '0',
			'prop'       => 'name',
			'value'      => $taxonomy->hierarchical ? 'term_id' : 'slug',
			'name'       => 'tax_input['.$taxonomy->name.'][]',
			'none_title' => $args['none'],
			'none_value' => 0,
			'class'      => static::BASE.'-admin-dropbown',
		];

		$html = Core\HTML::dropdown( $terms, $dropdown );

		if ( $html )
			$html = Core\HTML::wrap( $html, 'field-wrap -select' );

		if ( ! $args['echo'] )
			return $html;

		echo Core\HTML::wrap( $html, 'field-wrap -select' );
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
			'metabox'              => NULL,          // metabox id to check for hidden
			'header'               => FALSE,         // `TRUE`, `NULL`, or template
			'list_only'            => NULL,
			'selected_only'        => NULL,
			'selected_preserve'    => NULL,          // keep hidden selected / NULL to check for assign cap
			'descendants_and_self' => 0,
			'selected_cats'        => FALSE,
			'popular_cats'         => FALSE,
			'checked_ontop'        => TRUE,
			'show_count'           => FALSE,         // `TRUE`, `NULL`, or template
			'minus_count'          => FALSE,         // or number to subtract from count
			'edit'                 => NULL,          // manage page if has no terms, FALSE to disable
			'restricted'           => FALSE,         // `disabled` / `hidden`
			'name'                 => NULL,          // override if not saving by core
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

			// $terms = WordPress\Taxonomy::getTerms( $args['taxonomy'], FALSE, TRUE );
			$terms = WordPress\Taxonomy::listTerms( $args['taxonomy'], 'all' );
		}

		if ( ! count( $terms ) )
			return self::fieldEmptyTaxonomy( $args['taxonomy'], $args['edit'], $args['posttype'] );

		$header = $html = $hidden = '';
		$tax  = get_taxonomy( $args['taxonomy'] );
		$atts = [ 'taxonomy' => $args['taxonomy'], 'atts' => $args ];
		$name = $args['name'] ?? 'tax_input['.$tax->name.']';

		if ( TRUE === $args['header'] )
			$args['header'] = '<h4 class="-title">%s</h4>';

		if ( is_null( $args['header'] ) )
			$args['header'] = '%s';

		if ( $args['header'] )
			$header = sprintf( $args['header'], Helper::getTaxonomyLabel( $tax, 'metabox_title', 'name' ) );

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

			$diff = array_diff( $atts['selected_cats'], Core\Arraay::pluck( $terms, 'term_id' ) );

			foreach ( $diff as $term )
				$hidden.= '<input type="hidden" name="'.$name.'[]" value="'.$term.'" />';
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
			$html.= call_user_func_array( [ $walker, 'walk' ], [ $checked, 0, $atts ] );
		}

		if ( ! $atts['selected_only'] )
			$html.= call_user_func_array( [ $walker, 'walk' ], [ $terms, 0, $atts ] );

		// allows for an empty term set to be sent. 0 is an invalid Term ID
		// and will be ignored by empty() checks
		if ( ! $args['list_only'] && ! $atts['disabled'] )
			$hidden.= '<input type="hidden" name="'.$name.'[]" value="0" />';

		if ( $html )
			$html = Core\HTML::wrap( $header.'<ul>'.$html.'</ul>'.$hidden, [ 'field-wrap', '-list', $args['field_class'] ] );

		else
			$html = $hidden;

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
			'restricted'        => NULL,
			'list_only'         => NULL,
			'selected_only'     => NULL,
			'selected_preserve' => NULL,          // keep hidden selected / NULL to check for assign cap
			'walker'            => NULL,
			'name'              => 'tax_input',   // override if not saving by core
		] );

		if ( ! $args['taxonomy'] )
			return FALSE;

		if ( $args['metabox'] && self::checkHidden( $args['metabox'], $args['posttype'] ) )
			return FALSE;

		$selected = $post_id ? WordPress\Taxonomy::getPostTerms( $args['taxonomy'], $post_id, FALSE, 'slug' ) : [];

		if ( is_null( $users ) )
			$users = WordPress\User::get();

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

			$form.= Core\HTML::tag( 'input', [
				'type'        => 'search',
				'class'       => [ '-search', 'hide-if-no-js' ],
				'placeholder' => _x( 'Search …', 'MetaBox: Checklist', 'geditorial' ),
			] );

			$form.= Core\HTML::tag( 'button', [
				'type'  => 'button',
				'class' => [ '-button', 'button', 'button-small', '-sort', 'hide-if-no-js' ],
				'data'  => [ 'sort' => '-name' ],
				'title' => _x( 'Sort by name', 'MetaBox: Checklist', 'geditorial' ),
			], Core\HTML::getDashicon( 'sort' ) );

			$html.= Core\HTML::wrap( $form, 'field-wrap field-wrap-filter' );
		}

		if ( is_null( $args['selected_preserve'] ) )
			$args['selected_preserve'] = ! $atts['disabled'];

		// preserves users that hidden on the current list
		if ( $args['selected_preserve'] ) {

			$diff = array_diff( $selected, Core\Arraay::pluck( $users, 'user_login' ) );

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

		$html.= Core\HTML::wrap( '<ul>'.$list.'</ul>', 'field-wrap -list' );

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

		if ( ! $term = WordPress\Term::get( $term_or_id, $taxonomy ) )
			return '';

		$posttype = is_null( $posttypes ) ? 'any' : (array) $posttypes;

		$args = [
			'posts_per_page' => -1,
			'orderby'        => [ 'menu_order', 'date' ],
			'order'          => 'ASC',
			'post_type'      => $posttype,
			'post_status'    => WordPress\Status::acceptable( $posttype ),
			'post__not_in'   => $exclude,
			'tax_query'      => [ [
				'taxonomy' => $taxonomy,
				'field'    => 'id',
				'terms'    => [ $term->term_id ],

				'include_children' => FALSE, // @REF: https://docs.wpvip.com/code-quality/term-queries-should-consider-include_children-false/
			] ],
		];

		$posts = get_posts( $args );

		if ( empty( $posts ) )
			return FALSE;

		$html     = '';
		$statuses = WordPress\Status::get();

		if ( TRUE === $title )
			$html.= Core\HTML::tag( 'h4', Tablelist::getTermTitleRow( $term ) );

		else if ( $title )
			$html.= Core\HTML::tag( 'h4', $title );

		$html.= '<ol>';

		foreach ( $posts as $post )
			$html.= '<li>'.Helper::getPostTitleRow( $post, ( $post->ID == $current ? FALSE : 'edit' ), $statuses ).'</li>';

		return Core\HTML::wrap( $html.'</ol>', 'field-wrap -list' );
	}

	public static function fieldEmptyTaxonomy( $taxonomy, $edit = NULL, $posttype = FALSE, $echo = TRUE )
	{
		if ( FALSE === $edit )
			return FALSE;

		if ( is_null( $edit ) )
			$edit = Core\WordPress::getEditTaxLink( $taxonomy, FALSE, $posttype ? [ 'post_type' => $posttype ] : [] );

		if ( $edit )
			$html = Core\HTML::tag( 'a', [
				'href'   => $edit,
				'title'  => Helper::getTaxonomyLabel( $taxonomy, 'add_new_item' ),
				'target' => '_blank',
			], Helper::getTaxonomyLabel( $taxonomy, 'no_items_available', 'not_found' ) );

		else
			$html = '<span>'.Helper::getTaxonomyLabel( $taxonomy, 'no_items_available', 'not_found' ).'</span>';

		$html = Core\HTML::wrap( $html, 'field-wrap -empty' );

		if ( ! $echo )
			return $html;

		echo $html;
	}

	public static function fieldEmptyPostType( $posttype, $echo = TRUE )
	{
		$object = WordPress\PostType::object( $posttype );

		if ( WordPress\PostType::can( $posttype, 'create_posts' ) )
			$html = Core\HTML::tag( 'a', [
				'href'   => Core\WordPress::getPostNewLink( $posttype ),
				'title'  => $object->labels->add_new_item,
				'target' => '_blank',
			], $object->labels->not_found );
		else
			$html = gEditorial\Plugin::noinfo();

		$html = Core\HTML::wrap( $html, 'field-wrap -empty' );

		if ( ! $echo )
			return $html;

		echo $html;
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

		$html = ' <span class="postbox-title-action" data-tooltip="'.Core\Text::wordWrap( $info ).'"';
		$html.= ' data-tooltip-pos="'.( Core\HTML::rtl() ? 'down-left' : 'down-right' ).'"';
		$html.= ' data-tooltip-length="xlarge">'.Core\HTML::getDashicon( 'info' ).'</span>';

		return $html;
	}

	// PAIRED API
	// @OLD: `dropdownAssocPostsSubTerms()`
	public static function paired_dropdownSubTerms( $taxonomy, $paired = 0, $prefix = '', $selected = 0, $none = NULL )
	{
		$name = sprintf( '%s[%s]', $prefix, $paired );

		if ( ! $terms = WordPress\Taxonomy::getPostTerms( $taxonomy, $paired ) )
			return Core\HTML::tag( 'input', [ 'type' => 'hidden', 'value' => '0', 'name' => $name ] );

		if ( is_null( $none ) )
			$none = Settings::showOptionNone();

		$html = Core\HTML::dropdown( $terms, [
			'class'      => static::BASE.'-paired-subterms',
			'name'       => $name,
			'prop'       => 'name',
			'value'      => 'term_id',
			'selected'   => $selected,
			'none_title' => $none,
			'none_value' => '0',
			'data'       => [ 'paired' => $paired ],
		] );

		return Core\HTML::wrap( $html, 'field-wrap -select' );
	}

	// PAIRED API
	// OLD: `dropdownAssocPostsRedux()`
	public static function paired_dropdownToPosts( $posttype, $taxonomy = FALSE, $paired = '0', $prefix = '', $exclude = [], $none = NULL, $display_empty = TRUE )
	{
		$args = [
			'post_type'    => $posttype,
			'post__not_in' => $exclude,
			'post_status'  => WordPress\Status::acceptable( $posttype ),
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

		$html = $none ? Core\HTML::tag( 'option', [ 'value' => '0' ], $none ) : '';
		$html.= walk_page_dropdown_tree( $posts, 0, [
			'selected'          => $paired,
			'walker'            => new Misc\WalkerPageDropdown(),
			'title_with_parent' => TRUE,
		] );

		$html = Core\HTML::tag( 'select', [
			'name'  => ( $prefix ? $prefix.'-' : '' ).$posttype.'[]',
			'class' => [ static::BASE.'-paired-to-post-dropdown', empty( $posts ) ? 'hidden' : '' ],
			'data'  => [
				'type'   => $posttype,
				'paired' => $paired,
			],
		], $html );

		return $html ? Core\HTML::wrap( $html, 'field-wrap -select' ) : '';
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
			'post_status'      => WordPress\Status::acceptable( $posttype, 'dropdown' ),
			'value_field'      => 'post_name',
			'exclude'          => $exclude,
			'echo'             => 0,
			'walker'           => new Misc\WalkerPageDropdown(),
			'title_with_meta'  => 'number_line', // extra arg for the walker
		] );

		return $html ? Core\HTML::wrap( $html, 'field-wrap -select' ) : '';
	}

	public static function fieldPostMenuOrder( $post )
	{
		$html = Core\HTML::tag( 'input', [
			'type'        => 'number',
			'dir'         => 'ltr',
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

		echo Core\HTML::wrap( $html, 'field-wrap -inputnumber' );
	}

	// @REF: `post_slug_meta_box()`
	public static function fieldPostSlug( $post )
	{
		$html = '<label class="screen-reader-text" for="post_name">'.__( 'Slug' ).'</label>';

		$html.= Core\HTML::tag( 'input', [
			'type'        => 'text',
			'name'        => 'post_name',
			'id'          => 'post_name',
			'value'       => apply_filters( 'editable_slug', $post->post_name, $post ),
			'title'       => _x( 'Slug', 'MetaBox: Title Attr', 'geditorial' ),
			'placeholder' => _x( 'Slug', 'MetaBox: Placeholder', 'geditorial' ),
			'class'       => 'code-text',
		] );

		echo Core\HTML::wrap( $html, 'field-wrap -inputcode' );
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

		echo Core\HTML::wrap( $label.$html, 'field-wrap -select' );
	}

	public static function fieldPostParent( $post, $check = TRUE, $name = NULL, $posttype = NULL, $statuses = NULL )
	{
		// allows for a parent of diffrent type
		if ( is_null( $posttype ) )
			$posttype = $post->post_type;

		$object = WordPress\PostType::object( $posttype );

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
			'post_status'       => $statuses ?? WordPress\Status::acceptable( $posttype, 'dropdown', [ 'pending' ] ),
			'exclude_tree'      => $post->ID,
			'echo'              => 0,
			'walker'            => new Misc\WalkerPageDropdown(),
			'title_with_parent' => TRUE,
		];

		$html = wp_dropdown_pages( apply_filters( 'page_attributes_dropdown_pages_args', $args, $post ) );

		if ( $html )
			echo Core\HTML::wrap( $html, 'field-wrap -select' );
	}

	public static function classEditorBox( $screen, $id = 'postexcerpt' )
	{
		add_filter( 'postbox_classes_'.$screen->id.'_'.$id, static function ( $classes ) {
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

		if ( ! $selected = WordPress\Taxonomy::theTerm( $taxonomy, $post->ID ) )
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
			echo Core\HTML::tag( 'div', [
				'class' => '-wrap field-wrap -select',
				'title' => $obj->labels->menu_name,
			], $terms );
		else
			self::fieldEmptyTaxonomy( $obj, NULL, $post->post_type );
	}

	public static function glancePosttype( $posttype, $noop, $extra_class = '', $status = 'publish' )
	{
		$posts = WordPress\Database::countPostsByPosttype( $posttype );

		if ( empty( $posts[$status] ) )
			return FALSE;

		$class  = Core\HTML::prepClass( 'geditorial-glance-item', '-posttype', '-posttype-'.$posttype, $extra_class );
		$format = WordPress\PostType::can( $posttype, 'edit_posts' )
			? '<a class="'.$class.'" href="edit.php?post_type=%3$s">%1$s %2$s</a>'
			: '<span class="'.$class.'">%1$s %2$s</span>';

		return vsprintf( $format, [
			Core\Number::format( $posts[$status] ),
			Helper::noopedCount( $posts[$status], $noop ),
			$posttype,
		] );
	}

	public static function glanceTaxonomy( $taxonomy, $noop, $extra_class = '' )
	{
		if ( ! $terms = WordPress\Taxonomy::hasTerms( $taxonomy ) )
			return FALSE;

		$class  = Core\HTML::prepClass( 'geditorial-glance-item', '-tax', '-taxonomy-'.$taxonomy, $extra_class );
		$format = WordPress\Taxonomy::can( $taxonomy, 'manage_terms' )
			? '<a class="'.$class.'" href="edit-tags.php?taxonomy=%3$s">%1$s %2$s</a>'
			: '<span class="'.$class.'">%1$s %2$s</span>';

		return vsprintf( $format, [
			Core\Number::format( $terms ),
			Helper::noopedCount( $terms, $noop ),
			$taxonomy,
		] );
	}

	public static function tableRowObjectTaxonomy( $taxonomy, $object_id = 0, $name = NULL, $edit = NULL, $before = '', $after = '' )
	{
		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return FALSE;

		if ( ! current_user_can( $object->cap->assign_terms ) )
			return FALSE;

		echo $before.'<tr class="form-field"><th scope="row">'.Core\HTML::escape( $object->label ).'</th><td>';

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
		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
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

		$result = wp_set_object_terms( $object_id, Core\Arraay::prepNumeral( $data ), $object->name, FALSE );

		clean_object_term_cache( $object_id, $object->name );

		return $result;
	}

	public static function getFieldDefaults( $field, $module = NULL )
	{
		if ( is_null( $module ) )
			$module = static::MODULE;

		return [
			'name'        => $field,
			'rest'        => $field,      // FALSE to disable
			'title'       => NULL,        // self::getString( $field, $posttype, 'titles', $field, $module ),
			'description' => NULL,        // self::getString( $field, $posttype, 'descriptions', FALSE, $module ),
			'access_view' => NULL,        // @SEE: `$this->access_posttype_field()`
			'access_edit' => NULL,        // @SEE: `$this->access_posttype_field()`
			'sanitize'    => NULL,
			'sanitize'    => NULL,        // callback
			'prep'        => NULL,        // callback
			'pattern'     => NULL,        // HTML5 input pattern
			'default'     => NULL,        // currently only on rest
			'datatype'    => NULL,        // DataType Class
			'data_unit'   => NULL,        // the unit which in the data is stored
			'data_length' => NULL,        // typical length of the data
			'icon'        => 'smiley',
			'type'        => 'text',
			'context'     => NULL,        // default is `mainbox`
			'quickedit'   => FALSE,
			'values'      => [],
			'none_title'  => NULL,
			'none_value'  => '',
			'repeat'      => FALSE,
			'ltr'         => FALSE,
			'taxonomy'    => FALSE,
			'posttype'    => FALSE,
			'exclude'     => FALSE,       // `NULL` means parent post
			'role'        => FALSE,
			'group'       => 'general',
			'order'       => 1000,
		];
	}

	/**
	 * renders an general textarea tag for forms in a metabox.
	 *
	 * NOTE: HTML5 <textarea> element does not support the pattern attribute.
	 *
	 * @param  array $field
	 * @param  null|int|object $post
	 * @param  string $module
	 * @return bool $success
	 */
	public static function renderFieldTextarea( $field, $post = NULL, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$args  = self::atts( self::getFieldDefaults( $field['name'], $module ), $field );
		$value = self::_getMetaFieldRaw( $args, $post, $module );
		$wrap  = [ 'field-wrap', '-textarea' ];

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );

		$atts = [
			// 'rows'        => '1',
			// 'cols'        => '40',
			'name'        => sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title'       => sprintf( '%s :: %s', $args['title'] ?: $args['name'], $args['description'] ?: '' ),
			'placeholder' => $args['title'],
			'class'       => [
				sprintf( '%s-textarea', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
				'textarea-autosize',
			],
			'data' => [
				'meta-field'  => $args['name'],
				'meta-type'   => $args['type'],
				'meta-title'  => $args['title'] ?: $args['name'],
				'meta-desc'   => $args['description'] ?: '',
				'meta-unit'   => $args['data_unit'] ?: '',
				'meta-length' => $args['data_length'] ?: '',
			],
		];

		switch ( $args['type'] ) {

			case 'address':
			case 'note':
				$atts['rows'] = '1';
				$atts['data']['ortho'] = 'html';
				break;

			default:
				$atts['data']['ortho'] = 'html';
				$wrap[] = sprintf( '-textarea%s', $args['type'] ?: 'unknowntype' );
		}

		echo Core\HTML::wrap( Core\HTML::tag( 'textarea', $atts, esc_textarea( $value ) ), $wrap );

		return TRUE;
	}

	/**
	 * renders an general input tag for forms in a metabox
	 *
	 * @param  array $field
	 * @param  null|int|object $post
	 * @param  string $module
	 * @return bool $success
	 */
	public static function renderFieldInput( $field, $post = NULL, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$args  = self::atts( self::getFieldDefaults( $field['name'], $module ), $field );
		$value = self::_getMetaFieldRaw( $args, $post, $module );
		$wrap  = [ 'field-wrap', '-inputgeneral' ];

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );

		$atts = [
			'type'        => 'text',
			'value'       => $value ?: '',
			'name'        => sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title'       => sprintf( '%s :: %s', $args['title'] ?: $args['name'], $args['description'] ?: '' ),
			'pattern'     => $args['pattern'],
			'placeholder' => $args['title'],
			'class'       => [
				sprintf( '%s-inputgeneral', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
			],
			'data' => [
				'meta-field'  => $args['name'],
				'meta-type'   => $args['type'],
				'meta-title'  => $args['title'] ?: $args['name'],
				'meta-desc'   => $args['description'] ?: '',
				'meta-unit'   => $args['data_unit'] ?: '',
				'meta-length' => $args['data_length'] ?: '',
			],
		];

		switch ( $args['type'] ) {

			case 'text':
			case 'datestring':

				$atts['data']['ortho'] = 'text';

				$wrap[] = '-inputtext';

				break;

			case 'embed':
			case 'text_source':
			case 'audio_source':
			case 'video_source':
			case 'image_source':
			case 'downloadable':
			case 'link':

				$atts['dir'] = 'ltr';

				$wrap[] = '-inputlink';

				break;

			case 'people':

				$atts['data']['ortho'] = 'text';

				$wrap[] = '-inputpeople';

				break;

			case 'venue':

				$atts['data']['ortho'] = 'text';

				$wrap[] = '-inputvenue';

				break;

			case 'year':

				$atts['dir'] = 'ltr';
				$atts['data']['ortho'] = 'year';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputyear';

				break;

			case 'date':

				$atts['dir'] = 'ltr';
				$atts['data']['ortho'] = 'date';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputdate';

				if ( $value )
					$atts['value'] = Datetime::prepForInput( $value, 'Y/m/d', 'gregorian' );

				break;

			case 'datetime':

				$atts['dir'] = 'ltr';
				$atts['data']['ortho'] = 'datetime';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputdate';

				if ( $value )
					$atts['value'] = Datetime::prepForInput( $value, 'Y/m/d H:i', 'gregorian' );

				break;

			case 'distance':

				$atts['dir'] = 'ltr';
				$atts['data']['ortho'] = 'distance';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputdistance';

				if ( $value )
					$atts['value'] = Core\Distance::prep( $value, [], 'input' );

				break;

			case 'duration':

				$atts['dir'] = 'ltr';
				$atts['data']['ortho'] = 'duration';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputduration';

				if ( $value )
					$atts['value'] = Core\Duration::prep( $value, [], 'input' );

				break;

			case 'area':

				$atts['dir'] = 'ltr';
				$atts['data']['ortho'] = 'area';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputarea';

				if ( $value )
					$atts['value'] = Core\Area::prep( $value, [], 'input' );

				break;

			case 'postcode':
				$atts['dir'] = 'ltr';
				$atts['data']['ortho'] = 'postcode';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputpostcode';
				break;

			case 'contact':
				$atts['dir'] = 'ltr';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputcontact';
				break;

			case 'code':

				$atts['dir'] = 'ltr';

				$wrap[] = '-inputcode';

				break;

			case 'email':

				$atts['dir'] = 'ltr';
				$atts['type'] = 'email';
				$atts['data']['ortho'] = 'email';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputemail';

				if ( is_null( $atts['pattern'] ) )
					$atts['pattern'] = Core\Email::getHTMLPattern();

				break;

			case 'mobile':
			case 'phone':

				$atts['dir'] = 'ltr';
				$atts['type'] = 'tel';
				$atts['data']['ortho'] = 'phone';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputphone';

				// FIXME: the pattern is not cover all numbers
				// if ( is_null( $atts['pattern'] ) )
				// 	$atts['pattern'] = 'mobile' === $args['type']
				// 	 	? Core\Mobile::getHTMLPattern()
				// 		: Core\Phone::getHTMLPattern();

				break;

			case 'isbn':

				$atts['dir'] = 'ltr';
				$atts['data']['ortho'] = 'isbn';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputisbn';

				if ( is_null( $atts['pattern'] ) )
					$atts['pattern'] = Core\ISBN::getHTMLPattern();

				break;

			case 'vin':

				$atts['dir'] = 'ltr';
				$atts['data']['ortho'] = 'vin';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputvin';

				if ( is_null( $atts['pattern'] ) )
					$atts['pattern'] = Core\Validation::getVINHTMLPattern();

				break;

			case 'iban':

				$atts['dir'] = 'ltr';
				$atts['data']['ortho'] = 'iban';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputiban';

				if ( is_null( $atts['pattern'] ) )
					$atts['pattern'] = Core\Validation::getIBANHTMLPattern();

				break;

			case 'bankcard':

				$atts['dir'] = 'ltr';
				$atts['data']['ortho'] = 'bankcard';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputbankcard';

				if ( is_null( $atts['pattern'] ) )
					$atts['pattern'] = Core\Validation::getCardNumberHTMLPattern();

				break;

			case 'identity':

				$atts['dir'] = 'ltr';
				$atts['data']['ortho'] = 'identity';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputidentity';

				if ( is_null( $atts['pattern'] ) )
					$atts['pattern'] = Core\Validation::getIdentityNumberHTMLPattern();

				break;

			default:
				$wrap[] = sprintf( '-input%s', $args['type'] ?: 'unknowntype' );
		}

		if ( ! $atts['pattern'] )
			unset( $atts['pattern'] );

		echo Core\HTML::wrap( Core\HTML::tag( 'input', $atts ), $wrap );

		return TRUE;
	}

	/**
	 * renders an number input tag for forms in a metabox.
	 *
	 * @param  array $field
	 * @param  null|int|object $post
	 * @param  string $module
	 * @return bool $success
	 */
	public static function renderFieldNumber( $field, $post = NULL, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$args  = self::atts( self::getFieldDefaults( $field['name'], $module ), $field );
		$value = self::_getMetaFieldRaw( $args, $post, $module );
		$wrap  = [ 'field-wrap', '-inputnumber' ];
		$label = FALSE;

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );

		$atts = [
			'type'        => 'number',
			'dir'         => 'ltr',
			'value'       => $value ?: '',
			'name'        => sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title'       => sprintf( '%s :: %s', $args['title'] ?: $args['name'], $args['description'] ?: '' ),
			'pattern'     => $args['pattern'],
			// 'placeholder' => $args['title'],
			'class'       => [
				sprintf( '%s-inputnumber', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
			],
			'data' => [
				'meta-field'  => $args['name'],
				'meta-type'   => $args['type'],
				'meta-title'  => $args['title'] ?: $args['name'],
				'meta-desc'   => $args['description'] ?: '',
				'meta-unit'   => $args['data_unit'] ?: '',
				'meta-length' => $args['data_length'] ?: '',
			],
		];

		switch ( $args['type'] ) {

			case 'float':
			case 'number':
			default:
				$label = sprintf( '<span class="%s" title="%s">%s</span>', '-label', $args['description'], $args['title'] );
				$wrap[] = sprintf( '-input%s', $args['type'] ?: 'unknowntype' );
				$atts['data']['ortho'] = 'number';
		}

		if ( ! $atts['pattern'] )
			unset( $atts['pattern'] );

		$html = Core\HTML::tag( 'input', $atts );

		if ( $label )
			$html = '<label>'.$html.' '.$label.'</label>';

		echo Core\HTML::wrap( $html, $wrap );

		return TRUE;
	}

	public static function renderFieldSelect( $field, $post = NULL, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$html     = '';
		$args     = self::atts( self::getFieldDefaults( $field['name'], $module ), $field );
		$selected = self::_getMetaFieldRaw( $args, $post, $module );
		$wrap     = [ 'field-wrap', '-select' ];
		$label    = FALSE;

		if ( empty( $args['values'] ) && in_array( $args['type'], [ 'term' ], TRUE ) && ! empty( $args['taxonomy'] ) )
			$args['values'] = WordPress\Taxonomy::listTerms( $args['taxonomy'] );

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );

		if ( is_null( $args['none_title'] ) )
			$args['none_title'] = self::getString( $args['name'], $post->post_type, 'none', Settings::showOptionNone( $args['title'] ), $module );

		if ( $args['none_title'] )
			$html.= Core\HTML::tag( 'option', [
				'selected' => $selected == $args['none_value'],
				'value'    => $args['none_value'],
			], $args['none_title'] );

		foreach ( $args['values'] as $value_key => $value_label )
			$html.= Core\HTML::tag( 'option', [
				'selected' => $selected == $value_key,
				'value'    => $value_key,
			], $value_label );

		$atts = [
			'name'  => sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title' => sprintf( '%s :: %s', $args['title'], $args['description'] ),
			'class' => [
				sprintf( '%s-select', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
			],
			'data' => [
				'meta-field'  => $args['name'],
				'meta-type'   => $args['type'],
				'meta-title'  => $args['title'],
				'meta-desc'   => $args['description'] ?: '',
				'meta-unit'   => $args['data_unit'] ?: '',
				'meta-length' => $args['data_length'] ?: '',
			],
		];

		switch ( $args['type'] ) {

			case 'european_shoe':
			case 'international_shirt':
			case 'international_pants':
			case 'bookcover':
			case 'papersize':

				$label = sprintf( '<span class="%s" title="%s">%s</span>', '-label', $args['description'], $args['title'] );
				$wrap[] = '-inputtext-half';

				break;

			default:
				$wrap[] = sprintf( '-select%s', $args['type'] ?: 'unknowntype' );
		}

		$html = Core\HTML::tag( 'select', $atts, $html );

		if ( $label )
			$html = '<label>'.$html.' '.$label.'</label>';

		echo Core\HTML::wrap( $html, $wrap );
	}

	// just like any other meta-field but stores in `$post->post_parent`
	public static function renderFieldPostParent( $field, $post = NULL, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$html = '';
		$args = self::atts( self::getFieldDefaults( $field['name'], $module ), $field );

		if ( ! $args['posttype'] )
			$args['posttype'] = $post->post_type;

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );

		if ( $post->post_parent && ( $parent = WordPress\Post::get( $post->post_parent ) ) )
			$html.= Core\HTML::tag( 'option', [
				'selected' => TRUE,
				'value'    => $parent->ID,
			], WordPress\Post::title( $parent ) );

		$atts = [
			'name'  => 'parent_id', // sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title' => sprintf( '%s :: %s', $args['title'], $args['description'] ),
			'class' => [
				sprintf( '%s-searchselect-select2', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
			],
			'data' => [
				'meta-field' => $args['name'],
				'meta-type'  => $args['type'],
				'meta-title' => $args['title'],
				'meta-desc'  => $args['description'],

				'query-target'   => 'post',
				'query-exclude'  => is_null( $args['exclude'] ) ? $post->ID : ( $args['exclude'] ? implode( ',', (array) $args['exclude'] ) : FALSE ),
				'query-posttype' => $args['posttype'] ? implode( ',', (array) $args['posttype'] ) : FALSE,
				'query-taxonomy' => $args['taxonomy'] ? implode( ',', (array) $args['taxonomy'] ) : FALSE,
				'query-role'     => $args['role']     ? implode( ',', (array) $args['role'] )     : FALSE,

				'searchselect-placeholder' => $args['title'],
			],
		];

		echo Core\HTML::wrap( Core\HTML::tag( 'select', $atts, $html ), 'field-wrap -select hide-if-no-js' );

		return Services\SearchSelect::enqueueSelect2();
	}

	public static function renderFieldPost( $field, $post = NULL, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$html = '';
		$args = self::atts( self::getFieldDefaults( $field['name'], $module ), $field );

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );

		if ( $value = self::getPostMeta( $post->ID, $args['name'], '', NULL, $module ) )
			$html.= Core\HTML::tag( 'option', [
				'selected' => TRUE,
				'value'    => $value,
			], WordPress\Post::title( $value ) );

		$atts = [
			'name'  => sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title' => sprintf( '%s :: %s', $args['title'], $args['description'] ),
			'class' => [
				sprintf( '%s-searchselect-select2', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
			],
			'data' => [
				'meta-field' => $args['name'],
				'meta-type'  => $args['type'],
				'meta-title' => $args['title'],
				'meta-desc'  => $args['description'],

				'query-target'   => 'post',
				'query-exclude'  => is_null( $args['exclude'] ) ? $post->ID : ( $args['exclude'] ? implode( ',', (array) $args['exclude'] ) : FALSE ),
				'query-posttype' => $args['posttype'] ? implode( ',', (array) $args['posttype'] ) : FALSE,
				'query-taxonomy' => $args['taxonomy'] ? implode( ',', (array) $args['taxonomy'] ) : FALSE,
				'query-role'     => $args['role']     ? implode( ',', (array) $args['role'] )     : FALSE,

				'searchselect-placeholder' => $args['title'],
			],
		];

		echo Core\HTML::wrap( Core\HTML::tag( 'select', $atts, $html ), 'field-wrap -select hide-if-no-js' );

		return Services\SearchSelect::enqueueSelect2();
	}

	public static function renderFieldTerm( $field, $post = NULL, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$html = '';
		$args = self::atts( self::getFieldDefaults( $field['name'], $module ), $field );

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );

		if ( $value = self::getPostMeta( $post->ID, $args['name'], '', NULL, $module ) )
			$html.= Core\HTML::tag( 'option', [
				'selected' => TRUE,
				'value'    => $value,
			], WordPress\Term::title( $value ) );

		$atts = [
			'name'  => sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title' => sprintf( '%s :: %s', $args['title'], $args['description'] ),
			'class' => [
				sprintf( '%s-searchselect-select2', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
			],
			'data' => [
				'meta-field' => $args['name'],
				'meta-type'  => $args['type'],
				'meta-title' => $args['title'],
				'meta-desc'  => $args['description'],

				'query-target'   => 'term',
				'query-exclude'  => FALSE, // NOTE: `exclude` in porttype-fields are only for posts
				'query-posttype' => $args['posttype'] ? implode( ',', (array) $args['posttype'] ) : FALSE,
				'query-taxonomy' => $args['taxonomy'] ? implode( ',', (array) $args['taxonomy'] ) : FALSE,
				'query-role'     => $args['role']     ? implode( ',', (array) $args['role'] )     : FALSE,

				'searchselect-placeholder' => $args['title'],
			],
		];

		echo Core\HTML::wrap( Core\HTML::tag( 'select', $atts, $html ), 'field-wrap -select hide-if-no-js' );

		return Services\SearchSelect::enqueueSelect2();
	}

	public static function renderFieldUser( $field, $post = NULL, $module = NULL )
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = static::MODULE;

		$html = '';
		$args = self::atts( self::getFieldDefaults( $field['name'], $module ), $field );

		if ( is_null( $args['title'] ) )
			$args['title'] = self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );

		if ( is_null( $field['description'] ) )
			$args['description'] = self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );

		if ( $value = self::getPostMeta( $post->ID, $args['name'], '', NULL, $module ) )
			$html.= Core\HTML::tag( 'option', [
				'selected' => TRUE,
				'value'    => $value,
			], WordPress\User::getTitleRow( (int) $value,
				/* translators: %s: user id number */
				sprintf( _x( 'Unknown User #%s', 'MetaBox: Title Attr', 'geditorial' ), $value ) ) );

		$atts = [
			'name'  => sprintf( '%s-%s-%s', static::BASE, $module, $args['name'] ),
			'title' => sprintf( '%s :: %s', $args['title'], $args['description'] ),
			'class' => [
				sprintf( '%s-searchselect-select2', static::BASE ),
				sprintf( '%s-%s-field-%s', static::BASE, $module, $args['name'] ),
				sprintf( '%s-%s-type-%s', static::BASE, $module, $args['type'] ),
			],
			'data' => [
				'meta-field' => $args['name'],
				'meta-type'  => $args['type'],
				'meta-title' => $args['title'],
				'meta-desc'  => $args['description'],

				'query-target'   => 'user',
				'query-exclude'  => FALSE, // NOTE: `exclude` in porttype-fields are only for posts
				'query-posttype' => $args['posttype'] ? implode( ',', (array) $args['posttype'] ) : FALSE,
				'query-taxonomy' => $args['taxonomy'] ? implode( ',', (array) $args['taxonomy'] ) : FALSE,
				'query-role'     => $args['role']     ? implode( ',', (array) $args['role'] )     : FALSE,

				'searchselect-placeholder' => $args['title'],
			],
		];

		echo Core\HTML::wrap( Core\HTML::tag( 'select', $atts, $html ), 'field-wrap -select hide-if-no-js' );

		return Services\SearchSelect::enqueueSelect2();
	}

	private static function _getMetaFieldRaw( $field, $post, $module )
	{
		$meta = Template::getMetaFieldRaw( $field['name'], $post->ID, $module, FALSE, '' );

		if ( '' === $meta
			&& 'auto-draft' === $post->post_status
			&& ( $metakey = Services\PostTypeFields::getPostMetaKey( $field['name'], $module ) ) ) {

			// fills the meta by query data only on new posts
			$meta = WordPress\Strings::kses( self::req( $metakey, '' ), 'none' );
		}

		return $meta;
	}
}
