<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialShortCodeCore extends gEditorialTemplateCore
{
	// love the idea of the shortcode as an object
	// must self register
	// move helper functions

    public function __construct()
    {
        add_shortcode('test_shortcode', array($this, 'shortcode_content'));
    }

    public function shortcode_content( $attr, $content )
    {
        return $content;
    }
}
