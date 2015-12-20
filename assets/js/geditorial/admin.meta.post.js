jQuery(document).ready(function($) {
	if ( gEditorial.meta.hasOwnProperty('ot') ) {
		$('#geditorial-meta-ot').insertBefore('#titlewrap');
	};
	if ( gEditorial.meta.hasOwnProperty('st') ) {
		$('#geditorial-meta-st').insertAfter('#title');
	};
	if ( gEditorial.meta.hasOwnProperty('le') ) {
		$("#geditorial-meta-le-wrap").appendTo("#titlediv");
	};
});
