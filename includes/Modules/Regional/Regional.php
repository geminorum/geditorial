<?php namespace geminorum\gEditorial\Modules\Regional;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\MetaBox;

class Regional extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'regional',
			'title' => _x( 'Regional', 'Modules: Regional', 'geditorial' ),
			'desc'  => _x( 'Regional MetaData', 'Modules: Regional', 'geditorial' ),
			'icon'  => 'translation',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
		];
	}


	protected function get_global_constants()
	{
		return [
			'lang_tax' => 'language',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'lang_tax' => NULL,
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'lang_tax' => _n_noop( 'Language', 'Languages', 'geditorial-regional' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'show_option_all'  => _x( 'Language', 'Show Option All', 'geditorial-regional' ),
			'show_option_none' => _x( '(Uknonwn Language)', 'Show Option None', 'geditorial-regional' ),
		];

		$strings['terms'] = [
			'lang_tax' => [
				// @SEE: https://en.wikipedia.org/wiki/ISO_639
				'ar' => _x( 'Arabic', 'Default Term: Language', 'geditorial-regional' ),
				'fa' => _x( 'Farsi', 'Default Term: Language', 'geditorial-regional' ),
				'en' => _x( 'English', 'Default Term: Language', 'geditorial-regional' ),
				'fr' => _x( 'French', 'Default Term: Language', 'geditorial-regional' ),
			],
		];

		return $strings;
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'lang_tax', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => TRUE,
			'show_in_nav_menus'  => TRUE,
		] );

		if ( ! is_admin() )
			return;

		$this->register_default_terms( 'lang_tax' );
	}

	public function meta_box_cb_lang_tax( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, [ 'taxonomy' => $box['args']['taxonomy'], 'posttype' => $post->post_type ] );
		echo '</div>';
	}
}
