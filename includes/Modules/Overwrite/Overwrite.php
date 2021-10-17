<?php namespace geminorum\gEditorial\Modules\Overwrite;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Core\HTML;

class Overwrite extends gEditorial\Module
{

	protected $textdomain_frontend = FALSE;

	public static function module()
	{
		return [
			'name'  => 'overwrite',
			'title' => _x( 'Overwrite', 'Modules: Overwrite', 'geditorial' ),
			'desc'  => _x( 'Customized Translation Strings', 'Modules: Overwrite', 'geditorial' ),
			'icon'  => 'editor-strikethrough',
		];
	}


	protected function get_global_settings()
	{
		$object = [
			[
				'field'       => 'target',
				'type'        => 'text',
				'title'       => _x( 'Target', 'Setting Title', 'geditorial-overwrite' ),
				'description' => _x( 'Replaces with the translation string on the filter. Skips if empty.', 'Setting Description', 'geditorial-overwrite' ),
			],
			[
				'field'       => 'translation',
				'type'        => 'text',
				'title'       => _x( 'Translation', 'Setting Title', 'geditorial-overwrite' ),
				'description' => _x( 'Checks against the translation string on the filter. Leave empty to disable checks.', 'Setting Description', 'geditorial-overwrite' ),
			],
			[
				'field'       => 'text',
				'type'        => 'text',
				'title'       => _x( 'Source', 'Setting Title', 'geditorial-overwrite' ),
				'description' => _x( 'Checks against the original string on the filter. Leave empty to disable checks', 'Setting Description', 'geditorial-overwrite' ),
				'dir'         => 'ltr',
			],
			[
				'field'       => 'context',
				'type'        => 'text',
				'title'       => _x( 'Context', 'Setting Title', 'geditorial-overwrite' ),
				'description' => _x( 'Checks against the context of the string on the filter. Leave empty to disable checks', 'Setting Description', 'geditorial-overwrite' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'dir'         => 'ltr',
			],
			[
				'field'       => 'domain',
				'type'        => 'text',
				'title'       => _x( 'Domain', 'Setting Title', 'geditorial-overwrite' ),
				'description' => _x( 'Checks against the domain of the string on the filter. Leave empty to disable checks', 'Setting Description', 'geditorial-overwrite' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'default'     => 'default',
				'dir'         => 'ltr',
			],
		];

		return [
			'_backend' => [
				[
					'field'  => 'backend_strings',
					'type'   => 'object',
					'title'  => _x( 'Back-end Strings', 'Setting Title', 'geditorial-overwrite' ),
					'values' => $object,
				],
			],
			'_frontend' => [
				[
					'field'  => 'frontend_strings',
					'type'   => 'object',
					'title'  => _x( 'Front-end Strings', 'Setting Title', 'geditorial-overwrite' ),
					'values' => $object,
				],
			],
		];
	}

	protected function setup_disabled()
	{
		return is_admin()
			? empty( $this->get_setting( 'backend_strings' ) )
			: empty( $this->get_setting( 'frontend_strings' ) );
	}

	public function init()
	{
		parent::init();

		$this->filter( 'gettext', 3, 12, is_admin() ? 'backend' : 'frontend' );
		$this->filter( 'gettext_with_context', 4, 12, is_admin() ? 'backend' : 'frontend' );
	}

	public function gettext_backend( $translation, $text, $domain )
	{
		return $this->_overwrite_no_context( $this->get_setting( 'backend_strings' ), $translation, $text, $domain );
	}

	public function gettext_frontend( $translation, $text, $domain )
	{
		return $this->_overwrite_no_context( $this->get_setting( 'frontend_strings' ), $translation, $text, $domain );
	}

	public function gettext_with_context_backend( $translation, $text, $context, $domain )
	{
		return $this->_overwrite_with_context( $this->get_setting( 'backend_strings' ), $translation, $text, $context, $domain );
	}

	public function gettext_with_context_frontend( $translation, $text, $context, $domain )
	{
		return $this->_overwrite_with_context( $this->get_setting( 'frontend_strings' ), $translation, $text, $context, $domain );
	}

	private function _overwrite_no_context( $strings, $translation, $text, $domain )
	{
		foreach ( $strings as $string ) {

			if ( empty( $string['target'] ) )
				continue;

			if ( ! empty( $string['domain'] ) && $domain != $string['domain'] )
				continue;

			if ( ! empty( $string['context'] ) )
				continue;

			if ( ! empty( $string['translation'] ) && $translation == $string['translation'] )
				return $string['target'];

			if ( ! empty( $string['text'] ) && $text == $string['text'] )
				return $string['target'];
		}

		return $translation;
	}

	private function _overwrite_with_context( $strings, $translation, $text, $context, $domain )
	{
		foreach ( $strings as $string ) {

			if ( empty( $string['target'] ) )
				continue;

			if ( ! empty( $string['domain'] ) && $domain != $string['domain'] )
				continue;

			if ( ! empty( $string['context'] ) && $context != $string['context'] )
				continue;

			if ( ! empty( $string['translation'] ) && $translation == $string['translation'] )
				return $string['target'];

			if ( ! empty( $string['text'] ) && $text == $string['text'] )
				return $string['target'];
		}

		return $translation;
	}
}
