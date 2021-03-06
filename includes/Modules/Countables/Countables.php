<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\WordPress\Taxonomy;

class Countables extends gEditorial\Module
{

 	// https://codepen.io/geminorum/pen/NWxqZKO

	public static function module()
	{
		return [
			'name'  => 'countables',
			'title' => _x( 'Countables', 'Modules: Countables', 'geditorial' ),
			'desc'  => _x( 'Editorial Countable Items', 'Modules: Countables', 'geditorial' ),
			'icon'  => 'performance',
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

		$this->register_shortcode( 'posttype_shortcode', NULL, TRUE );
		$this->register_shortcode( 'taxonomy_shortcode', NULL, TRUE );
	}

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

		$html = '';

		if ( is_null( $args['template'] ) )
			$args['template'] = $this->countbox_default_template( 'posttype' );

		foreach ( Helper::getSeparated( $args['post_type'] ) as $posttype ) {

			if ( ! $posttype )
				continue;

			$counts = wp_count_posts( $posttype );

			if ( ! isset( $counts->{$args['status']} ) )
				continue;

			$count  = $counts->{$args['status']};
			$tokens = $this->filters( 'posttype_countbox_tokens', [
				'countable' => '-posttype-'.$posttype,
				'count'     => $count,
				'formatted' => $args['counter'] ? '' : Number::format( $count ),
				'link'      => $args['link'] ?: get_post_type_archive_link( $posttype ),
				'title'     => $args['title'] ?: $this->get_posttype_label( $posttype, 'name' ),
				'text'      => $content ?: $this->get_posttype_label( $posttype, 'description' ),
			], $posttype, $count, $args );

			$box  = Text::replaceTokens( $args['template'], $tokens );
			$html.= $args['wrap_class'] ? HTML::wrap( $box, $args['wrap_class'] ) : $box;
		}

		return ShortCode::wrap( $html, $this->constant( 'posttype_shortcode' ), $args );
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

		$html = '';

		if ( is_null( $args['template'] ) )
			$args['template'] = $this->countbox_default_template( 'taxonomy' );

		foreach ( Helper::getSeparated( $args['taxonomy'] ) as $taxonomy ) {

			if ( ! $taxonomy )
				continue;

			$count = wp_count_terms( $taxonomy );

			if ( self::isError( $count ) )
				continue;

			$tokens = $this->filters( 'taxonomy_countbox_tokens', [
				'countable' => '-taxonomy-'.$taxonomy,
				'count'     => $count,
				'formatted' => $args['counter'] ? '' : Number::format( $count ),
				'link'      => $args['link'] ?: '#',
				'title'     => $args['title'] ?: Taxonomy::object( $taxonomy )->labels->name,
				'text'      => $content ?: '',
			], $taxonomy, $count, $args );

			$box  = Text::replaceTokens( $args['template'], $tokens );
			$html.= $args['wrap_class'] ? HTML::wrap( $box, $args['wrap_class'] ) : $box;
		}

		return ShortCode::wrap( $html, $this->constant( 'taxonomy_shortcode' ), $args );
	}
}
