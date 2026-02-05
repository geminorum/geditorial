<?php namespace geminorum\gEditorial\Modules\Countables;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Countables extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'   => 'countables',
			'title'  => _x( 'Countables', 'Modules: Countables', 'geditorial-admin' ),
			'desc'   => _x( 'Editorial Countable Items', 'Modules: Countables', 'geditorial-admin' ),
			'icon'   => 'performance',
			'i18n'   => FALSE,
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
		];
	}

	protected function get_global_constants()
	{
		return [
			'posttype_shortcode' => 'posttype-countbox',
			'taxonomy_shortcode' => 'taxonomy-countbox',
		];
	}

	public function init()
	{
		parent::init();

		$this->register_shortcode( 'posttype_shortcode', TRUE );
		$this->register_shortcode( 'taxonomy_shortcode', TRUE );
	}

	// TODO: add setting
	private function countbox_default_template( $type )
	{
		$template = '<div class="-countable {{countable}}" data-count="{{count}}">'
			.'<a href="{{link}}">'
				.'<span class="-title">{{title}}</span>'
				.'<span class="-count" data-to="{{count}}">{{formatted}}</span>'
				.'<span class="-text">{{text}}</span>'
			.'</a></div>';

		return $this->filters( 'countbox_default_template', $template, $type );
	}

	public function posttype_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'counter'    => FALSE, // FIXME
			'title'      => NULL,
			'link'       => NULL,
			'post_type'  => $this->posttypes(),
			'status'     => 'publish',
			'wrap_class' => FALSE,
			'template'   => NULL,
			'context'    => NULL,
			'wrap'       => TRUE,
			'before'     => '',
			'after'      => '',
		], $atts, $this->constant( 'posttype_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = '';

		if ( is_null( $args['template'] ) )
			$args['template'] = $this->countbox_default_template( 'posttype' );

		foreach ( Services\Markup::getSeparated( $args['post_type'] ) as $posttype ) {

			if ( ! $posttype )
				continue;

			$counts = wp_count_posts( $posttype );

			if ( ! isset( $counts->{$args['status']} ) )
				continue;

			$count  = $counts->{$args['status']};
			$tokens = $this->filters( 'posttype_countbox_tokens', [
				'countable' => '-posttype-'.$posttype,
				'count'     => $count,
				'formatted' => $args['counter'] ? '' : Core\Number::format( $count ),
				'link'      => $args['link'] ?: WordPress\PostType::link( $posttype ),
				'title'     => is_null( $args['title'] ) ? Services\CustomPostType::getLabel( $posttype, 'name' ) : trim( $args['title'] ),
				'text'      => $content ? trim( $content ) : Services\CustomPostType::getLabel( $posttype, 'description' ),
			], $posttype, $count, $args );

			$box  = Core\Text::replaceTokens( $args['template'], $tokens );
			$html.= $args['wrap_class'] ? Core\HTML::wrap( $box, $args['wrap_class'] ) : $box;
		}

		return gEditorial\ShortCode::wrap(
			$html,
			$this->constant( 'posttype_shortcode' ),
			$args
		);
	}

	public function taxonomy_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'counter'    => FALSE, // FIXME
			'title'      => NULL,
			'link'       => NULL,
			'taxonomy'   => $this->taxonomies(),
			'wrap_class' => FALSE,
			'template'   => NULL,
			'context'    => NULL,
			'wrap'       => TRUE,
			'before'     => '',
			'after'      => '',
		], $atts, $this->constant( 'taxonomy_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = '';

		if ( is_null( $args['template'] ) )
			$args['template'] = $this->countbox_default_template( 'taxonomy' );

		foreach ( Services\Markup::getSeparated( $args['taxonomy'] ) as $taxonomy ) {

			if ( ! WordPress\Taxonomy::exists( $taxonomy ) )
				continue;

			if ( ! $count = WordPress\Taxonomy::hasTerms( $taxonomy ) )
				continue;

			$tokens = $this->filters( 'taxonomy_countbox_tokens', [
				'countable' => '-taxonomy-'.$taxonomy,
				'count'     => $count,
				'formatted' => $args['counter'] ? '' : Core\Number::format( $count ),
				'link'      => $args['link'] ?: '#',
				'title'     => is_null( $args['title'] ) ? WordPress\Taxonomy::object( $taxonomy )->labels->name : trim( $args['title'] ),
				'text'      => $content ? trim( $content ) : '',
			], $taxonomy, $count, $args );

			$box  = Core\Text::replaceTokens( $args['template'], $tokens );
			$html.= $args['wrap_class'] ? Core\HTML::wrap( $box, $args['wrap_class'] ) : $box;
		}

		return gEditorial\ShortCode::wrap(
			$html,
			$this->constant( 'taxonomy_shortcode' ),
			$args
		);
	}
}
