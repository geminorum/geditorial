<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class MetaBox extends WordPress\Main
{

	const BASE = 'geditorial';

	const POSTTYPE_MAINBOX_PROP = 'editorial_mainbox';

	public static function factory()
	{
		return gEditorial();
	}

	public static function checkHidden(
		mixed $metabox_id,
		string $after = '',
		string $posttype = '',
	): bool {

		static $hidden = NULL;

		if ( ! $metabox_id )
			return FALSE;

		if ( ! $screen = get_current_screen() )
			return FALSE;

		if ( $posttype && ! empty( $screen->is_block_editor ) )
			return FALSE;

		if ( is_null( $hidden ) )
			$hidden = (array) get_hidden_meta_boxes( $screen );

		if ( ! in_array( (string) $metabox_id, $hidden, TRUE ) )
			return FALSE;

		$html = Core\HTML::tag( 'a', [
			'href'  => add_query_arg( 'flush', '' ),
			'class' => [ '-description', '-refresh' ],
		], _x( 'Please refresh the page to generate the data.', 'MetaBox: Refresh Link', 'geditorial' ) );

		echo Core\HTML::wrap( $html, 'field-wrap -needs-refresh' ).$after;

		return TRUE;
	}

	// TODO: move to `WordPress\MetaBox`
	// FIXME: adopt `wp_dropdown_categories()`
	// FIXME: add taxonomy `title::description` as drop-down title attribute
	public static function singleselectTerms( int $object_id = 0, array $atts = [], ?array $terms = NULL ): bool|string
	{
		$args = self::args( $atts, [
			'context'     => NULL,
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
			'show_option_none'  => $args['none'] ?? Services\CustomTaxonomy::getLabel( $taxonomy, 'show_option_select' ),
			'option_none_value' => '0',
			'show_count'        => FALSE,
			'hide_empty'        => FALSE,
			'hide_if_empty'     => TRUE,
			'title_with_meta'   => $args['with_meta'] ?? FALSE,
			'title_with_parent' => $args['with_parent'] ?? $taxonomy->hierarchical,
			'restricted'        => $args['restricted'],
			'walker'            => new Misc\WalkerCategoryDropdown(),
			'class'             => self::dsh( static::BASE, 'admin', 'dropbown' ).' -dropdown-with-reset',
			'echo'              => FALSE,
			'disabled'          => ! current_user_can( $taxonomy->cap->assign_terms ),
		];

		if ( ! $html = wp_dropdown_categories( $dropdown ) )
			return self::fieldEmptyTaxonomy( $taxonomy->name, $args['empty_link'], $args['posttype'], $args['echo'] );

		$html = Core\HTML::wrap( $html, 'field-wrap -select' );

		if ( ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	// TODO: move to `WordPress\MetaBox`
	// TODO: radio list box using custom walker
	// CAUTION: tax must be hierarchical.
	// Hierarchical taxonomies save by IDs,
	// whereas non-hierarchical save by slugs.
	// WTF: because the core's not passing arguments into the walker!
	// @REF: `post_categories_meta_box()`, `wp_terms_checklist()`
	public static function checklistTerms( int $object_id = 0, array $atts = [], ?array $terms = NULL ): bool|string
	{
		$atts = apply_filters( 'wp_terms_checklist_args', $atts, $object_id );

		$args = self::args( $atts, [
			'taxonomy'             => NULL,
			'posttype'             => FALSE,
			'metabox'              => NULL,          // `metabox` id to check for hidden
			'header'               => FALSE,         // `TRUE`, `NULL`, or template
			'list_only'            => NULL,
			'selected_only'        => NULL,
			'selected_preserve'    => NULL,          // Keeps hidden selected / `NULL` to check for assign cap
			'descendants_and_self' => 0,
			'selected_cats'        => FALSE,
			'popular_cats'         => FALSE,
			'checked_ontop'        => TRUE,
			'show_count'           => FALSE,         // `TRUE`, `NULL`, or template
			'minus_count'          => FALSE,         // or number to subtract from count
			'edit'                 => NULL,          // Links to manage page if has no terms, FALSE to disable
			'restricted'           => FALSE,         // `disabled` / `hidden`
			'name'                 => NULL,          // Override this if not saving by core
			'field_class'          => '',
			'walker'               => NULL,
			'echo'                 => TRUE,
		] );

		if ( ! $args['taxonomy'] )
			return FALSE;

		if ( $args['metabox'] && self::checkHidden( $args['metabox'], '', $args['posttype'] ) )
			return FALSE;

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
		$tax    = get_taxonomy( $args['taxonomy'] );
		$name   = $args['name'] ?? sprintf( 'tax_input[%s]', $tax->name );
		$atts   = [
			'taxonomy' => $args['taxonomy'],
			'atts'     => $args,
		];

		if ( TRUE === $args['header'] )
			$args['header'] = '<h4 class="-title">%s</h4>';

		else if ( is_null( $args['header'] ) )
			$args['header'] = '%s';

		if ( $args['header'] )
			$header = sprintf( $args['header'], Services\CustomTaxonomy::getLabel( $tax, 'metabox_title', 'name' ) );

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

		// Preserves terms that hidden on the current list.
		if ( $args['selected_preserve'] ) {

			$diff = array_diff( $atts['selected_cats'], Core\Arraay::pluck( $terms, 'term_id' ) );

			foreach ( $diff as $term )
				$hidden.= '<input type="hidden" name="'.$name.'[]" value="'.$term.'" />';
		}

		if ( $args['checked_ontop'] || $atts['selected_only'] ) {

			// Postprocess `$terms` rather than adding an exclude to
			// the `get_terms()` query to keep the query the same across
			// all posts (for any query cache).
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

		// Allows for an empty term set to be sent. `0` is an invalid Term ID
		// and will be ignored by `empty()` checks
		if ( ! $args['list_only'] && ! $atts['disabled'] )
			$hidden.= '<input type="hidden" name="'.$name.'[]" value="0" />';

		if ( $html )
			$html = Core\HTML::wrap( $header.'<ul>'.$html.'</ul>'.$hidden, [ 'field-wrap', '-list', $args['field_class'] ] );

		else
			$html = $hidden;

		if ( ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	// TODO: move to `WordPress\MetaBox`
	public static function checklistUserTerms( int $post_id = 0, array $atts = [], ?array $users = NULL, int $threshold = 5 ): bool|string
	{
		$args = self::args( $atts, [
			'taxonomy'          => NULL,
			'posttype'          => FALSE,
			'metabox'           => NULL,
			'edit'              => FALSE,
			'restricted'        => NULL,
			'list_only'         => NULL,
			'selected_only'     => NULL,
			'selected_preserve' => NULL,          // Keeps hidden selected / NULL to check for assign cap
			'walker'            => NULL,
			'name'              => 'tax_input',   // Override this if not saving by core
		] );

		if ( ! $args['taxonomy'] )
			return FALSE;

		if ( $args['metabox'] && self::checkHidden( $args['metabox'], '', $args['posttype'] ) )
			return FALSE;

		$users    = $users ?? WordPress\User::get();
		$selected = $post_id ? WordPress\Taxonomy::getPostTerms( $args['taxonomy'], $post_id, FALSE, 'slug' ) : [];

		if ( empty( $args['walker'] ) || ! ( $args['walker'] instanceof \Walker ) ) {

			$walker = new Misc\WalkerUserChecklist();

		} else {

			$walker = $args['walker'];
		}

		$html = $form = $list = $hidden = '';
		$id   = self::dsh( static::BASE, $args['taxonomy'], 'list' );
		$tax  = get_taxonomy( $args['taxonomy'] );
		$atts = [
			'taxonomy' => $args['taxonomy'],
			'selected' => $selected,
			'atts'     => $args,
		];

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

		// Preserves users that hidden on the current list.
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

		// Allows for an empty term set to be sent. `0` is an invalid Term ID
		// and will be ignored by `empty()` checks
		if ( ! $args['list_only'] && ! $atts['disabled'] )
			$hidden.= '<input type="hidden" name="'.$args['name'].'['.$args['taxonomy'].'][]" value="0" />';

		// `List.JS` needs the wrap!
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

	// TODO: move to `WordPress\MetaBox`
	public static function getChildrenPosts(
		mixed $post,
		string|array|null $posttypes = NULL,
		string|bool $title = FALSE,
		int $current = 0,
		array $exclude = []
	): bool|string {

		if ( ! $post = WordPress\Post::get( $post ) )
			return '';

		$posttype = is_null( $posttypes ) ? 'any' : (array) $posttypes;

		$args = [
			'posts_per_page' => -1,
			'orderby'        => [ 'menu_order', 'date' ],
			'order'          => 'ASC',
			'post_type'      => $posttype,
			'post_status'    => WordPress\Status::acceptable( $posttype ),
			'post__not_in'   => $exclude,
			'post_parent'    => $post->ID,
		];

		$posts = get_posts( $args );

		if ( empty( $posts ) )
			return FALSE;

		$html     = '';
		$statuses = WordPress\Status::get();

		if ( TRUE === $title )
			$html.= Core\HTML::tag( 'h4', Helper::getPostTitleRow( $post ) );

		else if ( $title )
			$html.= Core\HTML::tag( 'h4', $title );

		$html.= '<ol>';

		foreach ( $posts as $item )
			$html.= '<li>'.Helper::getPostTitleRow( $item, ( $item->ID === $current ? FALSE : 'edit' ), $statuses ).'</li>';

		return Core\HTML::wrap( $html.'</ol>', 'field-wrap -list' );
	}

	// TODO: move to `WordPress\MetaBox`
	public static function getTermPosts(
		string $taxonomy,
		int|object $term,
		string|array|null $posttypes = NULL,
		string|bool $title = FALSE,
		int $current = 0,
		array $exclude = []
	): bool|string {

		if ( ! $term = WordPress\Term::get( $term, $taxonomy ) )
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
				'field'    => 'term_id',
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

		foreach ( $posts as $item )
			$html.= '<li>'.Helper::getPostTitleRow( $item, ( $item->ID === $current ? FALSE : 'edit' ), $statuses ).'</li>';

		return Core\HTML::wrap( $html.'</ol>', 'field-wrap -list' );
	}

	public static function fieldEmptyTaxonomy(
		string|object $taxonomy,
		string|false|null $edit = NULL,
		string|false $posttype = FALSE,
		bool $echo = TRUE
	): bool|string {

		if ( FALSE === $edit )
			return FALSE;

		$edit = $edit ?? WordPress\Taxonomy::edit( $taxonomy, $posttype ? [ 'post_type' => $posttype ] : [] );

		if ( $edit )
			$html = Core\HTML::tag( 'a', [
				'href'   => $edit,
				'title'  => Services\CustomTaxonomy::getLabel( $taxonomy, 'add_new_item' ),
				'target' => '_blank',
			], Services\CustomTaxonomy::getLabel( $taxonomy, 'no_items_available', 'not_found' ) );

		else
			$html = '<span>'.Services\CustomTaxonomy::getLabel( $taxonomy, 'no_items_available', 'not_found' ).'</span>';

		$html = Core\HTML::wrap( $html, 'field-wrap -empty' );

		if ( ! $echo )
			return $html;

		echo $html;
		return TRUE;
	}

	public static function fieldEmptyPostType( string|object $posttype, bool $echo = TRUE ): true|string
	{
		$object = WordPress\PostType::object( $posttype );

		if ( WordPress\PostType::can( $posttype, 'create_posts' ) )
			$html = Core\HTML::tag( 'a', [
				'href'   => WordPress\PostType::newLink( $posttype ),
				'title'  => $object->labels->add_new_item,
				'target' => '_blank',
			], $object->labels->not_found );
		else
			$html = Plugin::noinfo();

		$html = Core\HTML::wrap( $html, 'field-wrap -empty' );

		if ( ! $echo )
			return $html;

		echo $html;
		return TRUE;
	}

	#[\Deprecated('USE `WordPress\MetaBox::markupTitleAction()`')]
	public static function getTitleAction( string $action ): string
	{
		self::_dev_dep( 'WordPress\MetaBox::markupTitleAction()' );

		return WordPress\MetaBox::markupTitleAction( $action );
	}

	public static function titleActionRefresh(): string
	{
		return WordPress\MetaBox::markupTitleAction( [
			'url'   => add_query_arg( 'flush', '' ),
			'title' => _x( 'Click to refresh the content', 'MetaBox: Title Action', 'geditorial' ),
			'link'  => _x( 'Refresh', 'MetaBox: Title Action', 'geditorial' ),
		] );
	}

	// NOTE: DEPRECATED
	public static function titleActionInfo( string $info ): string
	{
		self::_dev_dep( 'WordPress\MetaBox::markupTitleInfo()' );

		return WordPress\MetaBox::markupTitleInfo( $info );
	}

	// PAIRED API
	// @OLD: `dropdownAssocPostsSubTerms()`
	public static function paired_dropdownSubTerms(
		string $taxonomy,
		int $paired = 0,
		string $prefix = '',
		int $selected = 0,
		?string $none = NULL
	): string {

		$name = sprintf( '%s[%s]', $prefix, $paired );

		if ( ! $terms = WordPress\Taxonomy::getPostTerms( $taxonomy, $paired ) )
			return Core\HTML::tag( 'input', [ 'type' => 'hidden', 'value' => '0', 'name' => $name ] );

		$html = Core\HTML::dropdown( $terms, [
			'class'      => self::dsh( static::BASE, 'paired-subterms' ),
			'name'       => $name,
			'prop'       => 'name',
			'value'      => 'term_id',
			'selected'   => $selected,
			'none_title' => $none ?? Settings::showOptionNone(),
			'none_value' => '0',
			'data'       => [ 'paired' => $paired ],
		] );

		return Core\HTML::wrap( $html, 'field-wrap -select' );
	}

	// PAIRED API
	// OLD: `dropdownAssocPostsRedux()`
	public static function paired_dropdownToPosts(
		string $posttype,
		string $taxonomy = '',
		string|int $paired = '0', // WTF?!
		string $prefix = '',
		array $exclude = [],
		?string $none = NULL,
		bool $display_empty = TRUE,
	): string {

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

		$none = $none ?? Services\CustomPostType::getLabel( $posttype, 'show_option_select' );
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

	// NOTE: DEPRECATED
	public static function dropdownAssocPosts(
		string $posttype,
		string $selected = '',
		string $prefix = '',
		string $exclude = ''
	): string {

		$html = wp_dropdown_pages( [
			'post_type'        => $posttype,
			'selected'         => $selected,
			'name'             => ( $prefix ? $prefix.'-' : '' ).$posttype.'[]',
			'id'               => ( $prefix ? $prefix.'-' : '' ).$posttype.'-'.( $selected ? $selected : '0' ),
			'class'            => self::dsh( static::BASE, 'admin', 'dropbown' ),
			'show_option_none' => Services\CustomPostType::getLabel( $posttype, 'show_option_select' ),
			'sort_column'      => 'menu_order',
			'sort_order'       => 'desc',
			'post_status'      => WordPress\Status::acceptable( $posttype, 'dropdown' ),
			'value_field'      => 'post_name',
			'exclude'          => $exclude,
			'echo'             => 0,
			'walker'           => new Misc\WalkerPageDropdown(),
			'title_with_meta'  => 'number_line', // extra argument for the walker
		] );

		return $html ? Core\HTML::wrap( $html, 'field-wrap -select' ) : '';
	}

	public static function fieldPostMenuOrder( object $post ): void
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
			'data'        => [ 'validator' => 'number' ],
		] );

		echo Core\HTML::wrap( $html, 'field-wrap -inputnumber' );
	}

	// TODO: move to `WordPress\MetaBox`
	// @REF: `post_slug_meta_box()`
	public static function fieldPostSlug( object $post ): void
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

	// TODO: move to `WordPress\MetaBox`
	// @REF: `post_author_meta_box()`
	public static function fieldPostAuthor( object $post ): void
	{
		$selected = empty( $post->ID ) ? $GLOBALS['user_ID'] : $post->post_author;

		$html = Listtable::restrictByAuthor( $selected, 'post_author_override', [
			'echo'            => FALSE,
			'class'           => self::dsh( static::BASE, 'admin', 'dropbown' ),
			'show_option_all' => '',
		] );

		if ( empty( $html ) )
			return;

		$label = '<label class="screen-reader-text" for="post_author_override">'.__( 'Author' ).'</label>';

		echo Core\HTML::wrap( $label.$html, 'field-wrap -select' );
	}

	public static function fieldPostParent(
		object $post,
		bool $check = TRUE,
		?string $name = NULL,
		?string $posttype = NULL,
		string|array|null $statuses = NULL,
	): void {

		// NOTE: allows for a parent of different type
		$posttype = $posttype ?? $post->post_type;

		$object = WordPress\PostType::object( $posttype );

		if ( $check && ! $object->hierarchical )
			return;

		$args = [
			'post_type'         => $posttype,
			'selected'          => $post->post_parent,
			'name'              => $name ?? 'parent_id',
			'class'             => self::dsh( static::BASE, 'admin', 'dropbown' ),
			'show_option_none'  => Services\CustomPostType::getLabel( $object, 'show_option_parent' ),
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

	public static function classEditorBox( object $screen, string $id = 'postexcerpt' ): void
	{
		add_filter( self::und( 'postbox_classes', $screen->id, $id ),
			static function ( $classes ) {
				return Core\Arraay::prepString( $classes, [
					static::BASE.'-wrap',
					'-admin-postbox',
					'-admin-postbox-editorbox',
				] );
			} );
	}

	public static function fieldEditorBox(
		string $content = '',
		string $id = 'excerpt',
		?string $title = NULL,
		array $atts = [],
	): void {

		$args = self::args( $atts, [
			'media_buttons' => FALSE,
			'textarea_rows' => 5,
			'editor_class'  => 'editor-status-counts textarea-autosize i18n-multilingual', // `qtranslate-x`
			'teeny'         => TRUE,
			'tinymce'       => FALSE,
			'quicktags'     => [
				'buttons' => implode( ',', [
					'link',
					'em',
					'strong',
					'li',
					'ul',
					'ol',
					'code',
				] ),
			],
		] );

		echo '<div class="-wordcount-wrap">';

			echo '<label class="screen-reader-text" for="'.$id.'">'.( $title ?? __( 'Excerpt' ) ).'</label>';

			wp_editor( html_entity_decode( $content ), $id, $args );

			Services\ClassicEditor::renderEditorStatusInfo( $id );

		echo '</div>';

		Scripts::enqueueWordCount();
	}

	// FIXME: finalize name/id
	public static function dropdownPostTaxonomy(
		string|object $taxonomy,
		object $post,
		string|false $key = FALSE,
		bool $show_count = TRUE,
		string $excludes = '',
		string|int $default = '0',
	): void {

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
			'show_option_none'  => Services\CustomTaxonomy::getLabel( $taxonomy, 'show_option_select' ),
			'option_none_value' => '0',
			'class'             => self::dsh( static::BASE, 'admin', 'dropbown' ),
			'id'                => self::dsh( static::BASE, $taxonomy ),
			'name'              => 'tax_input['.$taxonomy.'][]',
			// 'name'              => static::BASE.'-'.$taxonomy.( FALSE === $key ? '' : '['.$key.']' ),
			// 'id'                => static::BASE.'-'.$taxonomy.( FALSE === $key ? '' : '-'.$key ),
			'hierarchical'      => $obj->hierarchical,
			'orderby'           => 'name',
			'show_count'        => $show_count,
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

	public static function glancePosttype(
		string $posttype,
		array $noop,
		string|array $extra_class = '',
		string $status = 'publish',
	): false|string {

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

	public static function glanceTaxonomy(
		string $taxonomy,
		array $noop,
		string|array $extra_class = ''
	): false|string {

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

	public static function tableRowObjectTaxonomy(
		string|object $taxonomy,
		int $object_id = 0,
		?string $name = NULL,
		false|string|null $edit = NULL,
		string $before = '',
		string $after = '',
	): bool {

		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return FALSE;

		if ( ! current_user_can( $object->cap->assign_terms ) )
			return FALSE;

		echo $before.'<tr class="form-field"><th scope="row">'.Core\HTML::escape( $object->label ).'</th><td>';

		self::checklistTerms( $object_id, [
			'field_class' => 'wp-tab-panel',
			'taxonomy'    => $object->name,
			'edit'        => $edit,
			'name'        => $name ?: self::dsh( static::BASE, 'object_tax' )
		] );

		echo '</td></tr>'.$after;
		return TRUE;
	}

	public static function storeObjectTaxonomy(
		string|object $taxonomy,
		int $object_id,
		?array $data = NULL,
		?string $name = NULL,
		bool $check = TRUE,
	): false|array|object {

		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return FALSE;

		if ( $check && ! current_user_can( $object->cap->assign_terms ) )
			return FALSE;

		$name = $name ?? self::dsh( static::BASE, 'object_tax' );
		$data = $data ?? self::req( $name, [] );

		// For clearing must send `0` as `term_id`
		if ( empty( $data ) || ! is_array( $data ) )
			return FALSE;

		$result = wp_set_object_terms( $object_id, Core\Arraay::prepNumeral( $data ), $object->name, FALSE );

		clean_object_term_cache( $object_id, $object->name );

		return $result;
	}

	public static function getFieldDefaults( string $field, ?string $module = NULL ): array
	{
		// if ( ! $module = $module ?? static::MODULE )
		// 	return FALSE;

		return [
			'name'         => $field,
			'rest'         => $field,      // `FALSE` to disable
			'title'        => NULL,        // `self::getString( $field, $posttype, 'titles', $field, $module ),`
			'description'  => NULL,        // `self::getString( $field, $posttype, 'descriptions', FALSE, $module ),`
			'access_view'  => NULL,        // @SEE: `$this->access_posttype_field()`
			'access_edit'  => NULL,        // @SEE: `$this->access_posttype_field()`
			'sanitize'     => NULL,        // callback
			'prep'         => NULL,        // callback
			'pattern'      => NULL,        // HTML5 input pattern
			'default'      => NULL,        // currently only on rest
			'datatype'     => NULL,        // `DataType` Class
			'data_unit'    => NULL,        // The unit which in the data is stored.
			'data_length'  => NULL,        // typical length of the data
			'icon'         => 'smiley',
			'type'         => 'text',
			'context'      => NULL,        // default is `mainbox`
			'quickedit'    => FALSE,
			'autocomplete' => 'off',       // `NULL` to drop the attribute,
			'values'       => [],
			'none_title'   => NULL,
			'none_value'   => '',
			'repeat'       => FALSE,
			'ltr'          => FALSE,
			'taxonomy'     => FALSE,
			'posttype'     => NULL,        // `NULL` means same as the post / `FALSE` disable
			'exclude'      => FALSE,       // `NULL` means parent post
			'role'         => FALSE,
			'group'        => 'general',
			'order'        => 1000,

			/// render settings
			'name_for_rest'    => ! is_admin(),           // Determines the `name` attribute format of the HTML tag.
			'custom_name_attr' => NULL,                   // Overrides the `name` attribute for the HTML tag.
			'calendar_type'    => Core\L10n::calendar(),
		];
	}

	/**
	 * Renders a general text-area tag for forms in a meta-box.
	 *
	 * NOTE: HTML5 `<textarea>` element does not support the pattern attribute.
	 *
	 * @param array $field
	 * @param mixed $post
	 * @param string $module
	 * @return bool
	 */
	public static function renderFieldTextarea( array $field, mixed $post = NULL, ?string $module = NULL ): bool
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $module = $module ?? static::MODULE )
			return FALSE;

		$args  = self::parsed( self::getFieldDefaults( $field['name'], $module ), $field );
		$value = self::_getMetaFieldRaw( $args, $post, $module );
		$wrap  = [ 'field-wrap', '-textarea' ];

		$args['title']       = $args['title']       ?? self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );
		$args['description'] = $args['description'] ?? self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );

		$atts = [
			// 'rows'        => '1',
			// 'cols'        => '40',
			'name'        => self::_getNameAttr( $args, $module ),
			'title'       => WordPress\Strings::makeTitleAttribute( $args['title'] ?: $args['name'], $args['description'] ?: '' ),
			'placeholder' => $args['title'],
			'class'       => [
				'form-control',
				self::dsh( static::BASE, 'textarea' ),
				self::dsh( static::BASE, $module, 'field', $args['name'] ),
				self::dsh( static::BASE, $module, 'type', $args['type'] ),
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
				$atts['data']['validator'] = 'address';

				// NO BREAK!

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
	 * Renders a general input tag for forms in a meta-box.
	 *
	 * @param array $field
	 * @param mixed $post
	 * @param string $module
	 * @return bool
	 */
	public static function renderFieldInput( array $field, mixed $post = NULL, ?string $module = NULL ): bool
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $module = $module ?? static::MODULE )
			return FALSE;

		$args  = self::parsed( self::getFieldDefaults( $field['name'], $module ), $field );
		$value = self::_getMetaFieldRaw( $args, $post, $module );
		$wrap  = [ 'field-wrap', '-inputgeneral' ];

		$args['title']       = $args['title']       ?? self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );
		$args['description'] = $args['description'] ?? self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );

		$atts = [
			'type'         => 'text',
			'value'        => $value ?: '',
			'name'         => self::_getNameAttr( $args, $module ),
			'title'        => WordPress\Strings::makeTitleAttribute( $args['title'] ?: $args['name'], $args['description'] ?: '' ),
			'pattern'      => $args['pattern'],
			'autocomplete' => $args['autocomplete'] ?? FALSE,
			'placeholder'  => $args['title'],
			'class'        => [
				'form-control',
				self::dsh( static::BASE, 'inputgeneral' ),
				self::dsh( static::BASE, $module, 'field', $args['name'] ),
				self::dsh( static::BASE, $module, 'type', $args['type'] ),
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
			case 'title_link':
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

				$atts['data']['validator'] = 'year';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputyear';

				break;

			case 'date':

				$atts['dir'] = 'ltr';

				$atts['data']['validator'] = 'date';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputdate';

				if ( $value )
					$atts['value'] = Datetime::prepForInput(
						$value,
						Datetime::dateFormats( 'default' ),
						$field['calendar_type']
					);

				break;

			case 'datetime':

				$atts['dir'] = 'ltr';

				$atts['data']['validator'] = 'datetime';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputdate';

				if ( $value )
					$atts['value'] = Datetime::prepForInput(
						$value,
						Datetime::dateFormats( 'datetime' ),
						$field['calendar_type']
					);

				break;

			case 'distance':

				$atts['dir'] = 'ltr';

				$atts['data']['validator'] = 'distance';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputdistance';

				if ( $value )
					$atts['value'] = Core\Distance::prep( $value, [], 'input' );

				break;

			case 'duration':

				$atts['dir'] = 'ltr';

				$atts['data']['validator'] = 'duration';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputduration';

				if ( $value )
					$atts['value'] = Core\Duration::prep( $value, [], 'input' );

				break;

			case 'area':

				$atts['dir'] = 'ltr';

				$atts['data']['validator'] = 'area';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputarea';

				if ( $value )
					$atts['value'] = Core\Area::prep( $value, [], 'input' );

				break;

			case 'postcode':

				$atts['dir']     = 'ltr';
				$atts['pattern'] = $atts['pattern'] ?? Core\PostCode::getHTMLPattern();

				$atts['data']['validator'] = 'postcode';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputpostcode';

				break;

			case 'contact':

				$atts['dir'] = 'ltr';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputcontact';
				break;

			case 'latlng':

				$atts['dir'] = 'ltr';

				$atts['data']['validator'] = 'latlng';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputlatlng';

				break;

			case 'code':

				$atts['dir'] = 'ltr';

				$wrap[] = '-inputcode';

				break;

			case 'color':

				$atts['dir'] = 'ltr';

				$wrap[] = '-inputcolor'; // 'color-text'

				// NOTE: CAUTION: module must enqueue `wp-color-picker` styles/scripts
				// @SEE: `Scripts::enqueueColorPicker()`
				// $scripts[] = '$("#'.$id.'").wpColorPicker();';

				break;

			case 'email':

				$atts['dir']     = 'ltr';
				$atts['type']    = 'email';
				$atts['pattern'] = $atts['pattern'] ?? Core\Email::getHTMLPattern();

				$atts['data']['validator'] = 'email';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputemail';

				break;

			case 'mobile':
			case 'phone':

				$atts['dir']  = 'ltr';
				$atts['type'] = 'tel';

				$atts['data']['validator'] = 'phone';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputphone';

				// FIXME: the pattern is not cover all numbers
				// if ( is_null( $atts['pattern'] ) )
				// 	$atts['pattern'] = 'mobile' === $args['type']
				// 	 	? Core\Mobile::getHTMLPattern()
				// 		: Core\Phone::getHTMLPattern();

				break;

			case 'isbn':

				$atts['dir']     = 'ltr';
				$atts['pattern'] = $atts['pattern'] ?? Core\ISBN::getHTMLPattern();

				$atts['data']['validator'] = 'isbn';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputisbn';

				break;

			case 'vin':

				$atts['dir']     = 'ltr';
				$atts['pattern'] = $atts['pattern'] ?? Core\Validation::getVINHTMLPattern();

				$atts['data']['validator'] = 'vin';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputvin';

				break;

			case 'plate':

				$atts['dir']     = 'ltr';
				$atts['pattern'] = $atts['pattern'] ?? Core\Validation::getPlateHTMLPattern();

				$atts['data']['validator'] = 'plate';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputplate';

				break;

			case 'iban':

				$atts['dir']     = 'ltr';
				$atts['pattern'] = $atts['pattern'] ?? Core\Validation::getIBANHTMLPattern();

				$atts['data']['validator'] = 'iban';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputiban';

				break;

			case 'bankcard':

				$atts['dir']     = 'ltr';
				$atts['pattern'] = $atts['pattern'] ?? Core\Validation::getCardNumberHTMLPattern();

				$atts['data']['validator'] = 'bankcard';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputbankcard';

				break;

			case 'identity':

				// @REF: https://community.bitwarden.com/t/never-autofill-social-security-number/17900
				$atts['autocomplete'] = 'off';
				$atts['dir']          = 'ltr';
				$atts['pattern']      = $atts['pattern'] ?? Core\Validation::getIdentityNumberHTMLPattern();

				$atts['data']['validator'] = 'identity';

				$wrap[] = '-inputcode';
				$wrap[] = '-inputidentity';

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
	 * Renders a number input tag for forms in a meta-box.
	 *
	 * @param array $field
	 * @param mixed $post
	 * @param string $module
	 * @return bool
	 */
	public static function renderFieldNumber( array $field, mixed $post = NULL, ?string $module = NULL ): bool
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $module = $module ?? static::MODULE )
			return FALSE;

		$args  = self::parsed( self::getFieldDefaults( $field['name'], $module ), $field );
		$value = self::_getMetaFieldRaw( $args, $post, $module );
		$wrap  = [ 'field-wrap', '-inputnumber' ];
		$label = FALSE;

		$args['title']       = $args['title']       ?? self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );
		$args['description'] = $args['description'] ?? self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );

		$atts = [
			'type'         => 'number',
			'dir'          => 'ltr',
			'value'        => $value ?: '',
			'name'         => self::_getNameAttr( $args, $module ),
			'title'        => WordPress\Strings::makeTitleAttribute( $args['title'] ?: $args['name'], $args['description'] ?: '' ),
			'pattern'      => $args['pattern'] ?? FALSE,
			'size'         => $args['data_length'] ?? FALSE,
			'autocomplete' => $args['autocomplete'] ?? FALSE,
			// 'placeholder' => $args['title'],
			'class'       => [
				'form-control',
				self::dsh( static::BASE, 'inputnumber' ),
				self::dsh( static::BASE, $module, 'field', $args['name'] ),
				self::dsh( static::BASE, $module, 'type', $args['type'] ),
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

			case 'metre'    :
			case 'kilogram' :
			case 'kilometre':
			case 'hectare'  :
			case 'float'    :

				if ( empty( $args['pattern'] ) )
					// $args['pattern'] = 'fa_IR' === self::const( 'GNETWORK_WPLANG' )
					// 	? '[0-9۰-۹]*[.,]?[0-9۰-۹]*'
					// 	: '[0-9]*[.,]?[0-9]*';
					$args['pattern'] = '[0-9۰-۹]*[.,]?[0-9۰-۹]*';

				/**
				 * `step="any"` will allow any decimal.
				 * `step="1"` will allow no decimal.
				 * `step="0.5"` will allow 0.5; 1; 1.5; …
				 * `step="0.1"` will allow 0.1; 0.2; 0.3; 0.4; …
				 *
				 * @source https://stackoverflow.com/a/24921307
				 * @REF: https://web.archive.org/web/20150305121355/http://blog.isotoma.com/2012/03/html5-input-typenumber-and-decimalsfloats-in-chrome/
				 */
				$atts['step'] = 'any';

				// NOTE: no break!

			case 'number':
			default:

				$label  = sprintf( '<span class="%s" title="%s">%s</span>', '-label', $args['description'], $args['title'] );
				$wrap[] = sprintf( '-input%s', $args['type'] ?: 'unknowntype' );

				$atts['data']['validator'] = 'number';
		}

		if ( ! $atts['pattern'] )
			unset( $atts['pattern'] );

		$html = Core\HTML::tag( 'input', $atts );

		if ( $label )
			$html = '<label>'.$html.' '.$label.'</label>';

		echo Core\HTML::wrap( $html, $wrap );

		return TRUE;
	}

	public static function renderFieldSelect( array $field, mixed $post = NULL, ?string $module = NULL ): bool
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $module = $module ?? static::MODULE )
			return FALSE;

		$html     = '';
		$args     = self::parsed( self::getFieldDefaults( $field['name'], $module ), $field );
		$selected = self::_getMetaFieldRaw( $args, $post, $module );
		$wrap     = [ 'field-wrap', '-select' ];
		$label    = FALSE;

		if ( empty( $args['values'] ) && in_array( $args['type'], [ 'term' ], TRUE ) && ! empty( $args['taxonomy'] ) )
			$args['values'] = WordPress\Taxonomy::listTerms( $args['taxonomy'] );

		$args['title']       = $args['title']       ?? self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );
		$args['description'] = $args['description'] ?? self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );
		$args['none_title']  = $args['none_title']  ?? self::getString( $args['name'], $post->post_type, 'none', Settings::showOptionNone( $args['title'] ), $module );

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
			'name'  => self::_getNameAttr( $args, $module ),
			'title' => WordPress\Strings::makeTitleAttribute( $args['title'], $args['description'] ),
			'class' => [
				'form-control',
				self::dsh( static::BASE, 'select' ),
				self::dsh( static::BASE, $module, 'field', $args['name'] ),
				self::dsh( static::BASE, $module, 'type', $args['type'] ),
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
		return TRUE;
	}

	// Like any other meta-field but stores in `$post->post_parent`
	public static function renderFieldPostParent( array $field, mixed $post = NULL, ?string $module = NULL ): bool|string
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $module = $module ?? static::MODULE )
			return FALSE;

		$html = '';
		$args = self::parsed( self::getFieldDefaults( $field['name'], $module ), $field );

		if ( is_null( $args['posttype'] ) )
			$args['posttype'] = $post->post_type;

		else if ( ! $args['posttype'] )
			return FALSE;

		$args['title']       = $args['title']       ?? self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );
		$args['description'] = $args['description'] ?? self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );

		if ( $post->post_parent && ( $parent = WordPress\Post::get( $post->post_parent ) ) )
			$html.= Core\HTML::tag( 'option', [
				'selected' => TRUE,
				'value'    => $parent->ID,
			], WordPress\Post::title( $parent ) );

		// NOTE: no need for `Select2` but passing in case JavaScript-disabled.
		$none = Core\HTML::tag( 'option', [
			'selected' => empty( $parent ),
			'value'    => '0',
		], Settings::showOptionNone() );

		$atts = [
			'name'  => 'parent_id', // self::dsh( static::BASE, $module, $args['name'] ),
			'title' => WordPress\Strings::makeTitleAttribute( $args['title'], $args['description'] ),
			'class' => [
				self::dsh( static::BASE, 'searchselect', 'select2' ),
				self::dsh( static::BASE, $module, 'field', $args['name'] ),
				self::dsh( static::BASE, $module, 'type', $args['type'] ),
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

		echo Core\HTML::wrap( Core\HTML::tag( 'select', $atts, $none.$html ), 'field-wrap -select hide-if-no-js' );

		return Services\SearchSelect::enqueueSelect2();
	}

	public static function renderFieldPost( array $field, mixed $post = NULL, ?string $module = NULL ): bool|string
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $module = $module ?? static::MODULE )
			return FALSE;

		$html = '';
		$args = self::parsed( self::getFieldDefaults( $field['name'], $module ), $field );

		$args['title']       = $args['title']       ?? self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );
		$args['description'] = $args['description'] ?? self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );

		if ( $value = self::getPostMeta( $post->ID, $args['name'], '', NULL, $module ) )
			$html.= Core\HTML::tag( 'option', [
				'selected' => TRUE,
				'value'    => $value,
			], WordPress\Post::title( $value ) );

		// NOTE: no need for `Select2` but passing in case JavaScript-disabled.
		$none = Core\HTML::tag( 'option', [
			'selected' => empty( $value ),
			'value'    => '0',
		], Settings::showOptionNone() );

		$atts = [
			'name'  => self::_getNameAttr( $args, $module ),
			'title' => WordPress\Strings::makeTitleAttribute( $args['title'], $args['description'] ),
			'class' => [
				self::dsh( static::BASE, 'searchselect', 'select2' ),
				self::dsh( static::BASE, $module, 'field', $args['name'] ),
				self::dsh( static::BASE, $module, 'type', $args['type'] ),
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

		echo Core\HTML::wrap( Core\HTML::tag( 'select', $atts, $none.$html ), 'field-wrap -select hide-if-no-js' );

		return Services\SearchSelect::enqueueSelect2();
	}

	// FIXME: BLOCKED MODULE: `ShowCase`/`Spread`
	public static function renderFieldAttachment( array $field, mixed $post = NULL, ?string $module = NULL ): bool|string
	{

		return FALSE;
	}

	public static function renderFieldTerm( array $field, mixed $post = NULL, ?string $module = NULL ): bool|string
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $module = $module ?? static::MODULE )
			return FALSE;

		$html = '';
		$args = self::parsed( self::getFieldDefaults( $field['name'], $module ), $field );

		$args['title']       = $args['title']       ?? self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );
		$args['description'] = $args['description'] ?? self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );

		if ( $value = self::getPostMeta( $post->ID, $args['name'], '', NULL, $module ) )
			$html.= Core\HTML::tag( 'option', [
				'selected' => TRUE,
				'value'    => $value,
			], WordPress\Term::title( $value ) );

		$atts = [
			'name'  => self::_getNameAttr( $args, $module ),
			'title' => WordPress\Strings::makeTitleAttribute( $args['title'], $args['description'] ),
			'class' => [
				self::dsh( static::BASE, 'searchselect', 'select2' ),
				self::dsh( static::BASE, $module, 'field', $args['name'] ),
				self::dsh( static::BASE, $module, 'type', $args['type'] ),
			],
			'data' => [
				'meta-field' => $args['name'],
				'meta-type'  => $args['type'],
				'meta-title' => $args['title'],
				'meta-desc'  => $args['description'],

				'query-target'   => 'term',
				'query-exclude'  => FALSE, // NOTE: `exclude` in post-type-fields are only for posts
				'query-posttype' => $args['posttype'] ? implode( ',', (array) $args['posttype'] ) : FALSE,
				'query-taxonomy' => $args['taxonomy'] ? implode( ',', (array) $args['taxonomy'] ) : FALSE,
				'query-role'     => $args['role']     ? implode( ',', (array) $args['role'] )     : FALSE,

				'searchselect-placeholder' => $args['title'],
			],
		];

		echo Core\HTML::wrap( Core\HTML::tag( 'select', $atts, $html ), 'field-wrap -select hide-if-no-js' );

		return Services\SearchSelect::enqueueSelect2();
	}

	public static function renderFieldUser( array $field, mixed $post = NULL, ?string $module = NULL ): bool|string
	{
		if ( empty( $field['name'] ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $module = $module ?? static::MODULE )
			return FALSE;

		$html = '';
		$args = self::parsed( self::getFieldDefaults( $field['name'], $module ), $field );

		$args['title']       = $args['title']       ?? self::getString( $args['name'], $post->post_type, 'titles', $args['name'], $module );
		$args['description'] = $args['description'] ?? self::getString( $args['name'], $post->post_type, 'descriptions', FALSE, $module );

		if ( $value = self::getPostMeta( $post->ID, $args['name'], '', NULL, $module ) )
			$html.= Core\HTML::tag( 'option', [
				'selected' => TRUE,
				'value'    => $value,
			], WordPress\User::getTitleRow( (int) $value,
				sprintf(
					/* translators: `%s`: user id number */
					_x( 'Unknown User #%s', 'MetaBox: Option', 'geditorial' ),
					$value
				) )
			);

		$atts = [
			'name'  => self::_getNameAttr( $args, $module ),
			'title' => WordPress\Strings::makeTitleAttribute( $args['title'], $args['description'] ),
			'class' => [
				self::dsh( static::BASE, 'searchselect', 'select2' ),
				self::dsh( static::BASE, $module, 'field', $args['name'] ),
				self::dsh( static::BASE, $module, 'type', $args['type'] ),
			],
			'data' => [
				'meta-field' => $args['name'],
				'meta-type'  => $args['type'],
				'meta-title' => $args['title'],
				'meta-desc'  => $args['description'],

				'query-target'   => 'user',
				'query-exclude'  => FALSE, // NOTE: `exclude` in post-type-fields are only for posts
				'query-posttype' => $args['posttype'] ? implode( ',', (array) $args['posttype'] ) : FALSE,
				'query-taxonomy' => $args['taxonomy'] ? implode( ',', (array) $args['taxonomy'] ) : FALSE,
				'query-role'     => $args['role']     ? implode( ',', (array) $args['role'] )     : FALSE,

				'searchselect-placeholder' => $args['title'],
			],
		];

		echo Core\HTML::wrap( Core\HTML::tag( 'select', $atts, $html ), 'field-wrap -select hide-if-no-js' );

		return Services\SearchSelect::enqueueSelect2();
	}

	private static function _getMetaFieldRaw( array $field, object $post, string $module ): mixed
	{
		if ( ! empty( $field['taxonomy'] ) && in_array( $field['type'], [ 'term' ], TRUE ) ) {

			$terms = WordPress\Taxonomy::getPostTerms( $field['taxonomy'], $post, FALSE );
			$meta  = empty( $terms ) ? '' : reset( $terms );

		} else {

			$meta = Template::getMetaFieldRaw( $field['name'], $post->ID, $module, FALSE, '' );
		}

		if ( '' === $meta
			&& in_array( $post->post_status, [ 'draft', 'auto-draft' ], TRUE )
			&& ( $metakey = Services\PostTypeFields::getPostMetaKey( $field['name'], $module ) ) ) {

			// Fills the meta by query data, only on new posts.
			$meta = WordPress\Strings::kses( self::req( $metakey, '' ), 'none' );
			$meta = Services\PostTypeFields::replaceTokens( $meta, $field, $post, 'raw' );
			$meta = apply_filters( self::und( static::BASE, $module, 'initial', $field['name'] ), $meta, $field, $post, $module );
		}

		return $meta;
	}

	private static function _getNameAttr( array $field, string $module ): string
	{
		if ( ! empty( $field['custom_name_attr'] ) )
			return $field['custom_name_attr'];

		return vsprintf( $field['name_for_rest'] ? 'meta[_%2$s_%3$s]' : '%1$s-%2$s-%3$s', [
			static::BASE,
			$module,
			$field['name'],
		] );
	}

	// OLD: `check_draft_metabox()`
	public static function checkDraftMetaBox( ?array $box, object $post, ?string $message = NULL ): bool
	{
		if ( ! in_array( $post->post_status, [ 'trash', 'private', 'auto-draft' ], TRUE ) )
			return FALSE;

		Core\HTML::desc(
			$message ?? _x( 'You can see the contents once you\'ve saved this post for the first time.', 'MetaBox: Draft MetaBox', 'geditorial-admin' ),
			TRUE,
			'field-wrap -empty'
		);

		return TRUE;
	}
}
