<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

class CustomPostType extends gEditorial\Service
{
	/**
	 * Generates labels for given post-type.
	 *
	 * @REF: `get_post_type_labels()`
	 * @REF: `_get_custom_object_labels()`
	 * @REF: `_nx_noop()`
	 * @REF: `translate_nooped_plural()`
	 * @REF: `WP_Post_Type::get_default_labels()`
	 * TODO: add raw name strings on the object
	 * NOTE: OLD: `Helper::generatePostTypeLabels()`
	 *
	 * %1$s => Camel Case / Plural  : `Posts`
	 * %2$s => Camel Case / Singular: `Post`
	 * %3$s => Lower Case / Plural  : `posts`
	 * %4$s => Lower Case / Singular: `post`
	 * %5$s => %s
	 *
	 * @param  mixed  $name
	 * @param  mixed  $featured
	 * @param  array  $pre
	 * @param  string $posttype
	 * @return array $labels
	 */
	public static function generateLabels( $name, $featured = FALSE, $pre = [], $posttype = NULL )
	{
		$strings = WordPress\Strings::getNameForms( $name );

		$name_templates = apply_filters( static::BASE.'_posttype_labels_name_templates', [
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'name'                     => _x( '%1$s', 'CustomPostType: Label for `name`', 'geditorial' ),
			// 'menu_name'                => _x( '%1$s', 'CustomPostType: Label for `menu_name`', 'geditorial' ),
			// 'description'              => _x( '%1$s', 'CustomPostType: Label for `description`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'singular_name'            => _x( '%2$s', 'CustomPostType: Label for `singular_name`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'add_new'                  => _x( 'Add New', 'CustomPostType: Label for `add_new`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'add_new_item'             => _x( 'Add New %2$s', 'CustomPostType: Label for `add_new_item`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'edit_item'                => _x( 'Edit %2$s', 'CustomPostType: Label for `edit_item`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'new_item'                 => _x( 'New %2$s', 'CustomPostType: Label for `new_item`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'view_item'                => _x( 'View %2$s', 'CustomPostType: Label for `view_item`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'view_items'               => _x( 'View %1$s', 'CustomPostType: Label for `view_items`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'search_items'             => _x( 'Search %1$s', 'CustomPostType: Label for `search_items`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'not_found'                => _x( 'No %3$s found.', 'CustomPostType: Label for `not_found`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'not_found_in_trash'       => _x( 'No %3$s found in Trash.', 'CustomPostType: Label for `not_found_in_trash`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'parent_item_colon'        => _x( 'Parent %2$s:', 'CustomPostType: Label for `parent_item_colon`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'all_items'                => _x( 'All %1$s', 'CustomPostType: Label for `all_items`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'archives'                 => _x( '%2$s Archives', 'CustomPostType: Label for `archives`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'attributes'               => _x( '%2$s Attributes', 'CustomPostType: Label for `attributes`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'insert_into_item'         => _x( 'Insert into %4$s', 'CustomPostType: Label for `insert_into_item`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'uploaded_to_this_item'    => _x( 'Uploaded to this %4$s', 'CustomPostType: Label for `uploaded_to_this_item`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'filter_items_list'        => _x( 'Filter %3$s list', 'CustomPostType: Label for `filter_items_list`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'filter_by_date'           => _x( 'Filter by date', 'CustomPostType: Label for `filter_by_date`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'items_list_navigation'    => _x( '%1$s list navigation', 'CustomPostType: Label for `items_list_navigation`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'items_list'               => _x( '%1$s list', 'CustomPostType: Label for `items_list`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'item_published'           => _x( '%2$s published.', 'CustomPostType: Label for `item_published`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'item_published_privately' => _x( '%2$s published privately.', 'CustomPostType: Label for `item_published_privately`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'item_reverted_to_draft'   => _x( '%2$s reverted to draft.', 'CustomPostType: Label for `item_reverted_to_draft`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'item_trashed'             => _x( '%2$s trashed.', 'CustomPostType: Label for `item_trashed`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'item_scheduled'           => _x( '%2$s scheduled.', 'CustomPostType: Label for `item_scheduled`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'item_updated'             => _x( '%2$s updated.', 'CustomPostType: Label for `item_updated`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'item_link'                => _x( '%2$s Link', 'CustomPostType: Label for `item_link`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'item_link_description'    => _x( 'A link to a %4$s.', 'CustomPostType: Label for `item_link_description`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'template_name'            => _x( 'Single item: %2$s', 'CustomPostType: Label for `template_name`', 'geditorial' ),
		], $posttype, $strings, $name );

		$featured_templates = apply_filters( static::BASE.'_posttype_labels_featured_templates', [
			/* translators: `%1$s`: featured camel case, `%2$s`: featured lower case */
			'featured_image'        => _x( '%1$s', 'CustomPostType: Label for `featured_image`', 'geditorial' ),
			/* translators: `%1$s`: featured camel case, `%2$s`: featured lower case */
			'set_featured_image'    => _x( 'Set %2$s', 'CustomPostType: Label for `set_featured_image`', 'geditorial' ),
			/* translators: `%1$s`: featured camel case, `%2$s`: featured lower case */
			'remove_featured_image' => _x( 'Remove %2$s', 'CustomPostType: Label for `remove_featured_image`', 'geditorial' ),
			/* translators: `%1$s`: featured camel case, `%2$s`: featured lower case */
			'use_featured_image'    => _x( 'Use as %2$s', 'CustomPostType: Label for `use_featured_image`', 'geditorial' ),
		], $posttype, $featured, $name );

		foreach ( $name_templates as $key => $template )
			if ( ! array_key_exists( $key, $pre ) )
				$pre[$key] = vsprintf( $template, $strings );

		// NOTE: this is plugin custom
		if ( ! array_key_exists( 'extended_label', $pre ) )
			$pre['extended_label'] = $pre['name'];

		if ( ! array_key_exists( 'menu_name', $pre ) )
			$pre['menu_name'] = $strings[0];

		if ( ! array_key_exists( 'name_admin_bar', $pre ) )
			$pre['name_admin_bar'] = $strings[1];

		if ( ! array_key_exists( 'column_title', $pre ) )
			$pre['column_title'] = $strings[0];

		if ( ! array_key_exists( 'metabox_title', $pre ) )
			$pre['metabox_title'] = $strings[0];

		// EXTRA: must be in the core!
		if ( ! array_key_exists( 'author_label', $pre ) )
			$pre['author_label'] = __( 'Author' );

		// EXTRA: must be in the core!
		if ( ! array_key_exists( 'excerpt_label', $pre ) )
			$pre['excerpt_label'] = __( 'Excerpt' );

		if ( ! $featured && array_key_exists( 'featured_image', $pre ) )
			$featured = $pre['featured_image'];

		if ( $featured )
			foreach ( $featured_templates as $key => $template )
				if ( ! array_key_exists( $key, $pre ) )
					$pre[$key] = vsprintf( $template, [ $featured, Core\Text::strToLower( $featured ) ] );

		return $pre;
	}

	// NOTE: OLD: `Helper::getPostTypeLabel()`
	public static function getLabel( $post_or_posttype, $label, $fallback_key = NULL, $fallback = '' )
	{
		if ( ! $object = WordPress\PostType::object( $post_or_posttype ) )
			return $fallback ?? gEditorial\Plugin::na();

		if ( ! empty( $object->labels->{$label} ) )
			return $object->labels->{$label};

		$name = [
			'plural'   => $object->labels->name,
			'singular' => $object->labels->singular_name,
		];

		switch ( $label ) {

			case 'noop':
				return $name;

			case 'extended_label':
				return $object->labels->name;

			// case 'extended_no_items': // TODO

			case 'desc':
			case 'description':

				if ( ! empty( $object->description ) )
					return $object->description;

				break; // go to fall-backs

			case 'paired_no_items':
				/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
				return vsprintf( _x( 'There are no items available!', 'CustomPostType: Label for `paired_no_items`', 'geditorial' ), WordPress\Strings::getNameForms( $name ) );

			case 'paired_has_items':
				/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
				return vsprintf( _x( 'The %2$s has %5$s.', 'CustomPostType: Label for `paired_has_items`', 'geditorial' ), WordPress\Strings::getNameForms( $name ) );

			case 'paired_connected_to':
				/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
				return vsprintf( _x( 'Connected to %5$s', 'CustomPostType: Label for `paired_connected_to`', 'geditorial' ), WordPress\Strings::getNameForms( $name ) );

			case 'paired_mean_age':
				/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
				return vsprintf( _x( 'The %2$s Mean-age is %5$s.', 'CustomPostType: Label for `paired_mean_age`', 'geditorial' ), WordPress\Strings::getNameForms( $name ) );

			case 'show_option_no_items':
				/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
				return vsprintf( _x( '(No %3$s)', 'CustomPostType: Label for `show_option_no_items`', 'geditorial' ), WordPress\Strings::getNameForms( $name ) );

			case 'show_option_select':
				/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
				return vsprintf( _x( '&ndash; Select %2$s &ndash;', 'CustomPostType: Label for `show_option_select`', 'geditorial' ), WordPress\Strings::getNameForms( $name ) );

			case 'show_option_all':
				return $object->labels->all_items;

			case 'show_option_none':
				return sprintf( '&ndash; %s &ndash;', Settings::showRadioNone() );

			case 'show_option_parent':
				return sprintf( '&ndash; %s &ndash;', trim( $object->labels->parent_item_colon, ':' ) );

			case 'import_items':
				/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
				return vsprintf( _x( 'Import %1$s', 'CustomPostType: Label for `import_items`', 'geditorial' ), WordPress\Strings::getNameForms( $name ) );
		}

		if ( $fallback_key && isset( $object->labels->{$fallback_key} ) )
			return $object->labels->{$fallback_key};

		return $fallback;
	}

	/**
	 * %1$s => Camel Case / Plural
	 * %2$s => Camel Case / Singular
	 * %3$s => Lower Case / Plural
	 * %4$s => Lower Case / Singular
	 * %5$s => %s
	 */
	// NOTE: OLD: `Helper::generatePostTypeMessages()`
	public static function generateMessages( $name, $posttype = NULL )
	{
		global $post_type_object, $post, $post_ID;

		$strings = WordPress\Strings::getNameForms( $name );

		$templates = apply_filters( static::BASE.'_posttype_message_templates', [
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'view_post'                      => _x( 'View %4$s', 'CustomPostType: Message for `view_post`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'preview_post'                   => _x( 'Preview %4$s', 'CustomPostType: Message for `preview_post`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'post_updated'                   => _x( '%2$s updated.', 'CustomPostType: Message for `post_updated`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'custom_field_updated'           => _x( 'Custom field updated.', 'CustomPostType: Message for `custom_field_updated`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'custom_field_deleted'           => _x( 'Custom field deleted.', 'CustomPostType: Message for `custom_field_deleted`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'post_restored_to_revision_from' => _x( '%2$s restored to revision from %5$s.', 'CustomPostType: Message for `post_restored_to_revision_from`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'post_published'                 => _x( '%2$s published.', 'CustomPostType: Message for `post_published`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'post_saved'                     => _x( '%2$s saved.' , 'CustomPostType: Message for `post_saved`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'post_submitted'                 => _x( '%2$s submitted.', 'CustomPostType: Message for `post_submitted`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'post_scheduled_for'             => _x( '%2$s scheduled for: %5$s.', 'CustomPostType: Message for `post_scheduled_for`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'post_draft_updated'             => _x( '%2$s draft updated.', 'CustomPostType: Message for `post_draft_updated`', 'geditorial' ),
		], $posttype, $strings, $name );

		$messages = [];

		foreach ( $templates as $key => $template )
			$messages[$key] = vsprintf( $template, $strings );

		if ( ! $permalink = get_permalink( $post_ID ) )
			$permalink = '';

		$preview = $scheduled = $view = '';
		$scheduled_date = Datetime::dateFormat( $post->post_date, 'datetime' );

		if ( WordPress\PostType::viewable( $post_type_object ) ) {
			$view      = ' '.Core\HTML::link( $messages['view_post'], $permalink );
			$preview   = ' '.Core\HTML::link( $messages['preview_post'], get_preview_post_link( $post ), TRUE );
			$scheduled = ' '.Core\HTML::link( $messages['preview_post'], $permalink, TRUE );
		}

		return [
			0  => '', // Unused. Messages start at index 1.
			1  => $messages['post_updated'].$view,
			2  => $messages['custom_field_updated'],
			3  => $messages['custom_field_deleted'],
			4  => $messages['post_updated'],
			5  => isset( $_GET['revision'] ) ? sprintf( $messages['post_restored_to_revision_from'], wp_post_revision_title( (int) $_GET['revision'], FALSE ) ) : FALSE,
			6  => $messages['post_published'].$view,
			7  => $messages['post_saved'],
			8  => $messages['post_submitted'].$preview,
			9  => sprintf( $messages['post_scheduled_for'], '<strong>'.$scheduled_date.'</strong>' ).$scheduled,
			10 => $messages['post_draft_updated'].$preview,
		];
	}

	/**
	 * %1$s => Camel Case / Plural
	 * %2$s => Camel Case / Singular
	 * %3$s => Lower Case / Plural
	 * %4$s => Lower Case / Singular
	 * %5$s => %s
	 */
	// NOTE: OLD: `Helper::generateBulkPostTypeMessages()`
	public static function generateBulkMessages( $name, $counts, $posttype = NULL )
	{
		$strings = WordPress\Strings::getNameForms( $name );

		$templates = apply_filters( static::BASE.'_posttype_bulk_message_templates', [
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'updated'   => _nx_noop( '%5$s %4$s updated.', '%5$s %3$s updated.', 'CustomPostType: Bulk Message for `updated`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'locked'    => _nx_noop( '%5$s %4$s not updated, somebody is editing it.', '%5$s %3$s not updated, somebody is editing them.', 'CustomPostType: Bulk Message for `locked`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'deleted'   => _nx_noop( '%5$s %4$s permanently deleted.', '%5$s %3$s permanently deleted.', 'CustomPostType: Bulk Message for `deleted`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'trashed'   => _nx_noop( '%5$s %4$s moved to the Trash.', '%5$s %3$s moved to the Trash.', 'CustomPostType: Bulk Message for `trashed`', 'geditorial' ),
			/* translators: `%1$s`: camel case / plural posttype, `%2$s`: camel case / singular posttype, `%3$s`: lower case / plural posttype, `%4$s`: lower case / singular posttype, `%5$s`: `%s` placeholder */
			'untrashed' => _nx_noop( '%5$s %4$s restored from the Trash.', '%5$s %3$s restored from the Trash.', 'CustomPostType: Bulk Message for `untrashed`', 'geditorial' ),
		], $posttype, $strings, $name );

		$messages = [];

		foreach ( $templates as $key => $template )
			// needs to apply the role so we use noopedCount()
			$messages[$key] = vsprintf( Helper::noopedCount( $counts[$key], $template ), $strings );

		return $messages;
	}

	/**
	 * Switches post-type with PAIRED API support
	 *
	 * @param  int|object $post
	 * @param  string|object $posttype
	 * @return int|false $changed
	 */
	public static function switchType( $post, $posttype )
	{
		if ( ! $posttype = WordPress\PostType::object( $posttype ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( $posttype->name === $post->post_type )
			return TRUE;

		$paired_from = Services\Paired::isPostType( $post->post_type );
		$paired_to   = Services\Paired::isPostType( $posttype->name );

		// neither is paired
		if ( ! $paired_from && ! $paired_to )
			return WordPress\Post::setPostType( $post, $posttype );

		// bail if paired term not defined
		if ( ! $term = Services\Paired::getToTerm( $post->ID, $post->post_type, $paired_from ) )
			return WordPress\Post::setPostType( $post, $posttype );

		// NOTE: the `term_id` remains intact
		if ( ! WordPress\Term::setTaxonomy( $term, $paired_to ) )
			return FALSE;

		if ( ! WordPress\Post::setPostType( $post, $posttype ) )
			return FALSE;

		delete_post_meta( $post->ID, '_'.$post->post_type.'_term_id' );
		delete_term_meta( $term->term_id, $post->post_type.'_linked' );

		update_post_meta( $post->ID, '_'.$posttype->name.'_term_id', $term->term_id );
		update_term_meta( $term->term_id, $posttype->name.'_linked', $post->ID );

		return TRUE;
	}
}
