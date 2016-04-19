<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

if ( ! class_exists( 'WP_List_Table' ) )
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class gEditorialListTableCore extends \WP_List_Table
{
	// [How To Create WordPress Admin Tables Using WP_List_Table](https://paulund.co.uk/wordpress-tables-using-wp_list_table)
}
