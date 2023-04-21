<?php namespace geminorum\gEditorial\Modules\Wysiwyg;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

class Wysiwyg extends gEditorial\Module
{

	protected $disable_no_taxonomies = FALSE;

	protected $deafults = [ 'description_editor' => TRUE ];

	public static function module()
	{
		return [
			'name'     => 'wysiwyg',
			'title'    => _x( 'Wysiwyg', 'Modules: Wysiwyg', 'geditorial' ),
			'desc'     => _x( 'What You See Is What You Get', 'Modules: Wysiwyg', 'geditorial' ),
			'icon'     => 'embed-generic',
			'i18n'     => 'adminonly',
			'access'   => 'beta',
			'frontend' => FALSE,
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'taxonomies_option' => 'taxonomies_option',
			'_roles'            => [
				[
					'field'       => 'description_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Description Roles', 'Setting Title', 'geditorial-wysiwyg' ),
					'description' => _x( 'Editor will be available on descriptions for selected Roles.', 'Setting Description', 'geditorial-wysiwyg' ),
					'values'      => $roles,
				],
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'titles' => [ // label of description field
				// '{$taxonomy_name}' => _x( 'Description', 'Titles', 'geditorial-wysiwyg' ),
			],
			'descriptions' => [ // description of description field
				// '{$taxonomy_name}' => _x( 'The description is not prominent by default; however, some themes may show it.', 'Titles', 'geditorial-wysiwyg' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		// $strings['misc'] = [];

		return $strings;
	}

	protected function taxonomies_excluded( $extra = [] )
	{
		return $this->filters( 'taxonomies_excluded', Settings::taxonomiesExcluded( [
			'redundant', // gEditorial Redundancy
		] + $extra ) );
	}

	public function current_screen( $screen )
	{
		if ( ( 'edit-tags' == $screen->base || 'term' == $screen->base )
			&& $this->taxonomy_supported( $screen->taxonomy ) ) {

			$this->_admin_enabled();

			if ( $this->get_setting( 'description_editor' )
				&& $this->role_can( 'description' ) )
					$this->_enqueue_editor( $screen );
		}
	}

	private function _enqueue_editor( $screen )
	{
		// remove the filters which disallow HTML in term descriptions
		remove_filter( 'pre_term_description', 'wp_filter_kses' );
		remove_filter( 'term_description', 'wp_kses_data' );

		// add filters to disallow unsafe HTML tags
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			add_filter( 'pre_term_description', 'wp_kses_post' );
			add_filter( 'term_description', 'wp_kses_post' );
		}

		if ( 'edit-tags' === $screen->base )
			add_action( $screen->taxonomy.'_add_form_fields', [ $this, 'add_form_fields_editor' ], 1, 1 );

		else if ( 'term' === $screen->base )
			add_action( $screen->taxonomy.'_edit_form_fields', [ $this, 'edit_form_fields_editor' ], 1, 2 );

		Scripts::enqueueWordCount();
	}

	private function _get_taxonomy_desc_title( $taxonomy )
	{
		$fallback = empty( $taxonomy->labels->desc_field_title )
			? _x( 'Description', 'Taxonomy Description: Label', 'geditorial-wysiwyg' )
			: $taxonomy->labels->desc_field_title;

		return $this->get_string( $taxonomy->name, FALSE, 'titles', $fallback );
	}

	private function _get_taxonomy_desc_desc( $taxonomy )
	{
		$fallback = empty( $taxonomy->labels->desc_field_description )
			? _x( 'The description is not prominent by default; however, some themes may show it.', 'Taxonomy Description: Description', 'geditorial-wysiwyg' )
			: $taxonomy->labels->desc_field_description;

		return $this->get_string( $taxonomy->name, FALSE, 'descriptions', $fallback );
	}

	// @SEE: for caption type editor: `edit_form_image_editor()`
	private function _get_taxonomy_desc_editor_settings( $taxonomy, $name_attr )
	{
		return [
			'textarea_name'  => $name_attr,
			'textarea_rows'  => 7, // 10
			'teeny'          => TRUE,
			'media_buttons'  => FALSE,
			'default_editor' => 'html',
			'editor_class'   => 'editor-status-counts i18n-multilingual', // qtranslate-x
			'quicktags'      => [ 'buttons' => 'link,em,strong,li,ul,ol,code' ],
			'tinymce'        => [
				'toolbar1' => 'bold,italic,alignleft,aligncenter,alignright,link,undo,redo',
				'toolbar2' => '',
				'toolbar3' => '',
				'toolbar4' => '',
			],
		];
	}

	public function add_form_fields_editor( $taxonomy )
	{
		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return;

		$id = $this->classs( 'description' );

		echo '<div class="form-field term-description-wrap -wordcount-wrap">';

			echo '<label for="'.$id.'">';
			echo Core\HTML::escape( $this->_get_taxonomy_desc_title( $object ) );
			echo '</label>';

			wp_editor( '', $id, $this->_get_taxonomy_desc_editor_settings( $object, 'description' ) );

			Helper::renderEditorStatusInfo( $id );

			Core\HTML::desc( $this->_get_taxonomy_desc_desc( $object ) );

			Core\HTML::wrapScript( 'jQuery("textarea#tag-description").closest(".form-field").remove();' );

			$ready = <<<'JS'
$("#addtag").on("mousedown","#submit",function(){
	tinyMCE.triggerSave();
	$(document).on("ajaxSuccess.geditorial_wysiwyg_add_term", function(){
		if (tinyMCE.activeEditor) {
			tinyMCE.activeEditor.setContent("");
		}
		$(document).off("ajaxSuccess.geditorial_wysiwyg_add_term",false);
	});
});
JS;
			Core\HTML::wrapjQueryReady( $ready );

		echo '</div>';
	}

	public function edit_form_fields_editor( $term, $taxonomy )
	{
		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return;

		$id = $this->classs( 'description' );

		echo '<tr class="form-field term-description-wrap -wordcount-wrap">';
			echo '<th scope="row" valign="top"><label for="'.$id.'">';
			echo Core\HTML::escape( $this->_get_taxonomy_desc_title( $object ) );
			echo '</label></th><td>';

			wp_editor( htmlspecialchars_decode( $term->description ), $id,
				$this->_get_taxonomy_desc_editor_settings( $object, 'description' ) );

			Helper::renderEditorStatusInfo( $id );

			Core\HTML::desc( $this->_get_taxonomy_desc_desc( $object ) );
			Core\HTML::wrapScript( 'jQuery("textarea#description").closest(".form-field").remove();' );

		echo '</tr>';
	}
}
