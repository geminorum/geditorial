// (function ($) {
jQuery(document).ready(function($) {
	"use strict";

	$('#wp-admin-bar-editorial-drafts a.ab-item').click(function(e) {
		e.preventDefault();

		// slide up
		var wrap = $('#editorial-drafts');

		if ( wrap.size() && wrap.is( ':visible' ) ) {
			wrap.slideUp(function() {
				$(this).remove();
			});
			return;
		}

		// slide down
		$('body').append('<div id="editorial-drafts" class="geditorial-wrap drafts"><div class="-content"></div></div>');

		// show spinner
		var wrap = $('#editorial-drafts');
		wrap.css({'height': '100%'}).slideDown().addClass('-loading');

		// load drafts
		$.post( gEditorial.api, {
				action: 'geditorial_drafts',
				what: 'list',
				nonce: gEditorial.nonce,
			},
			function(response) {
				// console.log(response.data);
				var content = wrap.find('.-content');
				console.log(content);
				content.html(response.data.html);
				wrap.removeClass('-loading');
				content.hide().css({ 'visibility': 'visible' }).fadeIn();
			},
			'json'
		);
	});
//}(jQuery));
});
