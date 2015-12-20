<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialEventTemplates extends gEditorialTemplateCore
{

	// FIXME: DRAFT
	// https://wordpress.org/plugins/events-manager-rich-snippets/
	// Extends Events Manager WordPress plugin with rich snippets preview (Google structured data).
	function emrs_content($content){

		$EM_Events = EM_Events::get(array('limit'=>'25', 'orderby'=>'event_start_date', 'scope'=>'future'));

		foreach ( $EM_Events as $EM_Event ) {
			if (!$EM_Event->output('#@_{Y-m-d\T}')) {
				$endDate = $EM_Event->output('#_{Y-m-d\T}');
			} else {
				$endDate = $EM_Event->output('#@_{Y-m-d\T}');
			}
			$item .= '
			<script type="application/ld+json">
			{
			  "@context": "http://schema.org",
			  "@type": "Event",
			  "name": "'.$EM_Event->output('#_EVENTNAME').'",
			  "startDate" : "'.$EM_Event->output('#_{Y-m-d\T}').$EM_Event->output('#_24HSTARTTIME').'+02:00",
			  "endDate" : "'.$endDate.$EM_Event->output('#_24HENDTIME').'+02:00",
			  "url" : "'.$EM_Event->output('#_EVENTURL').'",
			  "image" : "'.$EM_Event->output('#_EVENTIMAGEURL').'",
			  "location" : {
				"@type" : "Place",
				"sameAs" : "'.$EM_Event->output('#_LOCATIONURL').'",
				"name" : "'.$EM_Event->output('#_LOCATIONNAME').'",
				"address" : {
				"@type" : "PostalAddress",
					"streetAddress" : "'.$EM_Event->output('#_LOCATIONADDRESS').'",
					"addressLocality" : "'.$EM_Event->output('#_LOCATIONTOWN').'",
					"addressRegion" : "'.$EM_Event->output('#_LOCATIONREGION').'",
					"postalCode" : "'.$EM_Event->output('#_LOCATIONPOSTCODE').'",
					"addressCountry" : "'.$EM_Event->output('#_LOCATIONCOUNTRY').'"
				  }
			  }
			}
			</script>';
		}
		return $item . $content;
	}
	// add_filter('em_content','emrs_content');
}
