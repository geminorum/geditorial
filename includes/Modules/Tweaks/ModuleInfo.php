<?php namespace geminorum\gEditorial\Modules\Tweaks;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;

class ModuleInfo extends gEditorial\Info
{

	const MODULE = 'tweaks';

	public static function getHelpTabs( $context = NULL )
	{
		return [
			[
				'title'   => _x( 'Category Search', 'Help Tab Title', 'geditorial-tweaks' ),
				'id'      => self::classs( 'category-search' ),
				'content' => self::buffer( [ __CLASS__, 'renderrenderHelpTab_category_search' ] ),
			],
			[
				'title'   => _x( 'Checklist Tree', 'Help Tab Title', 'geditorial-tweaks' ),
				'id'      => self::classs( 'checklist-tree' ),
				'content' => self::buffer( [ __CLASS__, 'renderrenderHelpTab_checklist_tree' ] ),
			],
		];
	}

	public static function renderrenderHelpTab_category_search()
	{
		echo '<div class="-info"><p>Makes it quick and easy for writers to select categories related to what they are writing. As they type in the search box, categories will be shown and hidden in real time, allowing them to easily select what is relevant to their content without having to scroll through possibly hundreds of categories.</p>
<p class="-from">Adopted from: <a href="https://wordpress.org/plugins/searchable-categories/" target="_blank">Searchable Categories</a> by <a href="http://ididntbreak.it" target="_blank">Jason Corradino</a></p></div>';
	}

	public static function renderrenderHelpTab_checklist_tree()
	{
		echo '<div class="-info"><p>If you\'ve ever used categories extensively, you will have noticed that after you save a post, the checked categories appear on top of all the other ones. This can be useful if you have a lot of categories, since you don’t have to scroll. Unfortunately, this behaviour has a serious side-effect: it breaks the hierarchy. If you have deeply nested categories that don’t make sense out of context, this will completely screw you over.</p>
<p class="-from">Adopted from: <a href="https://wordpress.org/plugins/category-checklist-tree/" target="_blank">Category Checklist Tree</a> by <a href="http://scribu.net/wordpress/category-checklist-tree" target="_blank">scribu</a></p></div>';
	}
}
