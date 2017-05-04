<?php namespace geminorum\gEditorial\Helpers;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\Number;

class Entry extends gEditorial\Helper
{

	const MODULE = 'entry';

	///////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////
	/////MUST REWRITE//////////////////////////////////////////
	///////////////////////////////////////////////////////////

	function sections( $sections = array(), $active_section = NULL )
	{
		$taxonomy = 'section';
		$link_class = '';
		if ( empty( $sections ) ) {
			$link_class = 'root';
			$sections = get_terms( $taxonomy, array( 'parent' => 0, 'hide_empty' => 0 ) );
			$active_section = self::active_section();
			echo '<ul id="kb-sections" class="unstyled">';
		}
		if ( empty( $active_section ) ) {
			$active_section = '';
		}
		foreach ( $sections as $section ) {
			$toggle = '';
			$section_children = get_terms( $taxonomy, array( 'parent' => $section->term_id, 'hide_empty' => 0 ) );
			if ( !empty( $section_children ) && $link_class != 'root' ) {
				$toggle = '<i class="toggle"></i>';
			}
			echo '<li class="'.( $section->term_id == $active_section ? 'active' : '' ).'">';
			echo '<a  href="'.get_term_link( $section, $taxonomy ).'" class="'.$link_class.'" rel="'.$section->slug.'">'.$toggle.$section->name.'</a>';

			if ( !empty( $section_children ) ) {
				echo '<ul id="'.$section->slug.'" class="children">';
				self::sections( $section_children, $active_section );
			}
			echo "</li>";
		}
		echo "</ul>";
	}

	function active_section()
	{
		$taxonomy = 'section';
		$current_section = '';
		if ( is_single() ) {
			$sections = explode( '/', get_query_var( $taxonomy ) );
			$section_slug = end( $sections );
			if ( $section_slug != '' ) {
				$term = get_term_by( 'slug', $section_slug, $taxonomy );
			} else {
				$terms = wp_get_post_terms( get_the_ID(), $taxonomy );
				$term = $terms[0];
			}
			if ( $term )
				$current_section = $term->term_id;
		} else {
			$term = get_term_by( 'slug', get_query_var( $taxonomy ), get_query_var( 'taxonomy' ) );
			if ( $term )
				$current_section = $term->term_id;
		}
		return $current_section;
	}

	function article_permalink( $article_id, $section_id )
	{
		$taxonomy = 'section';
		$article = get_post( $article_id );
		$section = get_term( $section_id, $taxonomy );
		$section_ancestors = get_ancestors( $section->term_id, $taxonomy );
		krsort( $section_ancestors );
		$permalink = '<a href="/entry/';
		foreach ( $section_ancestors as $ancestor ):
			$section_ancestor = get_term( $ancestor, $taxonomy );
			$permalink.= $section_ancestor->slug.'/';
		endforeach;
		$permalink.= $section->slug.'/'.$article->post_name.'/" >'.$article->post_title.'</a>';
		return $permalink;
	}

	// JUST COPY : https://wordpress.org/plugins/word-highlighter/
	// add_filter( 'the_content', 'apply_word_highligher' );
	function apply_word_highligher( $content )
	{

		global $post;

		$post_type=get_post_type($post->ID);

		$options = get_option('highlightedtext_options');

		if ($options['highlightedtext_type']!=$post_type and $options['highlightedtext_type']!='both')
		return $content;
		//echo "<pre>";print_r($options);
		if ($options['highlightedtext_active']) {

		//echo "here=".$text."<br />";
		$text_name=explode(',',trim($options['highlightedtext_name']));
		//echo "<pre>";print_r($text_name);
		if (!empty($text_name)){
		for ($i=0;$i<count($text_name);$i++){
		if (trim($text_name[$i])!=''){

			if (preg_match('~\b' . preg_quote($text_name[$i], '~') . '\b(?![^<]*?>)~', $content, $result))
			{
				$rep_html='<label class="wh_highlighted">'.$text_name[$i].'</label>';
				if ($options['highlightedtext_case'])
				{

					$content = preg_replace('~\b' . preg_quote($text_name[$i], '~') . '\b(?![^<]*?>)~', $rep_html, $content);

				}
				else
				{
						$content = preg_replace('~\b' . preg_quote($text_name[$i], '~') . '\b(?![^<]*?>)~i', $rep_html, $content);

				}
			}

		}
		}
		}
		}

		return $content;
	}
}
