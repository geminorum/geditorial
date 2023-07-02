<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\WordPress;

trait Deprecated
{

	// DEPRECATED: use `$this->get_adminpage_url( FALSE )`
	// OVERRIDE: if has no admin menu but using the hook
	public function get_adminmenu( $page = TRUE, $extra = [] )
	{
		self::_dep( '$this->get_adminpage_url( FALSE )' );

		if ( $page )
			return $this->classs();

		$url = get_admin_url( NULL, 'index.php' );

		return add_query_arg( array_merge( [ 'page' => $this->classs() ], $extra ), $url );
	}

	public function get_image_size_key( $constant, $size = 'thumbnail' )
	{
		self::_dep();

		$posttype = $this->constant( $constant );

		if ( isset( $this->image_sizes[$posttype][$posttype.'-'.$size] ) )
			return $posttype.'-'.$size;

		if ( isset( $this->image_sizes[$posttype]['post-'.$size] ) )
			return 'post-'.$size;

		return $size;
	}

	// CAUTION: tax must be hierarchical
	public function add_meta_box_checklist_terms( $constant, $posttype, $role = NULL, $type = FALSE )
	{
		self::_dep();

		$taxonomy = $this->constant( $constant );
		$metabox  = $this->classs( $taxonomy );
		$edit     = WordPress::getEditTaxLink( $taxonomy );

		if ( $type )
			$this->remove_meta_box( $constant, $posttype, $type );

		add_meta_box( $metabox,
			$this->get_meta_box_title( $constant, $edit, TRUE ),
			[ $this, 'add_meta_box_checklist_terms_cb' ],
			NULL,
			'side',
			'default',
			[
				'taxonomy' => $taxonomy,
				'posttype' => $posttype,
				'metabox'  => $metabox,
				'edit'     => $edit,
				'role'     => $role,
			]
		);
	}

	public function add_meta_box_checklist_terms_cb( $post, $box )
	{
		self::_dep();

		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function remove_meta_box( $constant, $posttype, $type = 'tag' )
	{
		self::_dep();

		if ( 'tag' == $type )
			remove_meta_box( 'tagsdiv-'.$this->constant( $constant ), $posttype, 'side' );

		else if ( 'cat' == $type )
			remove_meta_box( $this->constant( $constant ).'div', $posttype, 'side' );

		else if ( 'parent' == $type )
			remove_meta_box( 'pageparentdiv', $posttype, 'side' );

		else if ( 'image' == $type )
			remove_meta_box( 'postimagediv', $this->constant( $constant ), 'side' );

		else if ( 'author' == $type )
			remove_meta_box( 'authordiv', $this->constant( $constant ), 'normal' );

		else if ( 'excerpt' == $type )
			remove_meta_box( 'postexcerpt', $posttype, 'normal' );

		else if ( 'submit' == $type )
			remove_meta_box( 'submitdiv', $posttype, 'side' );
	}

	// get stored post meta by the field
	public function get_postmeta( $post_id, $field = FALSE, $default = '', $metakey = NULL )
	{
		self::_dep( '$this->get_postmeta_legacy() || $this->get_postmeta_field()' );

		global $gEditorialPostMeta;

		if ( is_null( $metakey ) )
			$metakey = $this->meta_key;

		if ( ! isset( $gEditorialPostMeta[$post_id][$metakey] ) )
			$gEditorialPostMeta[$post_id][$metakey] = get_metadata( 'post', $post_id, $metakey, TRUE );

		if ( empty( $gEditorialPostMeta[$post_id][$metakey] ) )
			return $default;

		if ( FALSE === $field )
			return $gEditorialPostMeta[$post_id][$metakey];

		foreach ( $this->sanitize_postmeta_field_key( $field ) as $field_key )
			if ( isset( $gEditorialPostMeta[$post_id][$metakey][$field_key] ) )
				return $gEditorialPostMeta[$post_id][$metakey][$field_key];

		return $default;
	}

	public function set_meta( $post_id, $postmeta, $key_suffix = '' )
	{
		self::_dep( '$this->store_postmeta()' );

		global $gEditorialPostMeta;

		if ( ! empty( $postmeta ) )
			update_post_meta( $post_id, $this->meta_key.$key_suffix, $postmeta );
		else
			delete_post_meta( $post_id, $this->meta_key.$key_suffix );

		unset( $gEditorialPostMeta[$post_id][$this->meta_key.$key_suffix] );
	}

	// DEFAULT METHOD
	public function dISABLED_render_metabox( $post, $box, $fields = NULL, $context = NULL )
	{
		self::_dep();

		if ( is_null( $fields ) )
			$fields = $this->get_posttype_fields( $post->post_type );

		foreach ( $fields as $field => $args ) {

			if ( $context != $args['context'] )
				continue;

			echo '<div class="-wrap field-wrap -setting-field" title="'.HTML::escape( $args['description'] ).'">';

			$atts = [
				'field'       => $this->constant( 'metakey_'.$field, $field ),
				'type'        => $args['type'],
				'title'       => $args['title'],
				'placeholder' => $args['title'],
				'values'      => $args['values'],
			];

			if ( 'checkbox' == $atts['type'] )
				$atts['description'] = $atts['title'];

			$this->do_posttype_field( $atts, $post );

			echo '</div>';
		}
	}

	// PAIRED API
	public function get_linked_post_id( $term_or_id, $posttype_constant_key, $tax_constant_key, $check_slug = TRUE )
	{
		self::_dep( '$this->paired_get_to_post_id()' );

		return $this->paired_get_to_post_id( $term_or_id, $posttype_constant_key, $tax_constant_key, $check_slug );
	}

	// PAIRED API
	public function get_linked_posts( $post_id, $posttype_constant_key, $tax_constant_key, $count = FALSE, $term_id = NULL )
	{
		self::_dep( '$this->paired_get_from_posts()' );

		return $this->paired_get_from_posts( $post_id, $posttype_constant_key, $tax_constant_key, $count, $term_id );
	}

	protected function do_restrict_manage_posts_taxes( $taxes, $posttype_constant_key = TRUE )
	{
		self::_dev_dep( 'restrict_manage_posts_restrict_taxonomy()' );

		if ( TRUE === $posttype_constant_key ||
			$this->is_current_posttype( $posttype_constant_key ) ) {

			foreach ( (array) $taxes as $constant )
				Listtable::restrictByTaxonomy( $this->constant( $constant ) );
		}
	}

	protected function do_parse_query_taxes( &$query, $taxes, $posttype_constant_key = TRUE )
	{
		self::_dev_dep( 'parse_query_restrict_taxonomy()' );

		if ( TRUE === $posttype_constant_key ||
			$this->is_current_posttype( $posttype_constant_key ) ) {

			foreach ( (array) $taxes as $constant )
				Listtable::parseQueryTaxonomy( $query, $this->constant( $constant ) );
		}
	}

	protected function do_restrict_manage_posts_posts( $tax_constant_key, $posttype_constant_key )
	{
		self::_dev_dep( 'restrict_manage_posts_restrict_paired()' );

		Listtable::restrictByPosttype(
			$this->constant( $tax_constant_key ),
			$this->constant( $posttype_constant_key )
		);
	}

	// DEFAULT METHOD
	// INTENDED HOOK: `save_post`, `save_post_[post_type]`
	public function dISABLED_store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		$fields = $this->get_posttype_fields( $post->post_type );

		foreach ( $fields as $field => $args ) {

			if ( $context != $args['context'] )
				continue;

			$key = $this->constant( 'metakey_'.$field, $field );

			// FIXME: DO THE SAVINGS!
		}
	}
}
