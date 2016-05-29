jQuery(document).ready(function($) {
	'use strict';

	$("[data-meta-type='title_before']").each(function() {
		$(this).insertBefore('#titlewrap').show();
	});

	$("[data-meta-type='title_after']").each(function() {
		$(this).insertAfter('#titlewrap').show();
	});

	$("[data-meta-type='box']").each(function() {
		$(this).parents('div.postbox').appendTo("#titlediv");
	});
});
