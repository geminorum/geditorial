(function ($) {
	"use strict";
	$(function () {

		var $like = $('.geditorial-wrap.like'),
			$list = $like.data('avatars');

		if ($like.length > 0) {

			var button  = $like.find('a.like'),
				counter = $like.find('span.like'),
				avatars = $list ? $like.find('ul.like') : null;

			button.removeAttr('href');

			$.post( gEditorial.api, {
					action : 'geditorial_like',
					what : 'check',
					id : button.data('id')
				}, function (r) {
					if (r.success) {
						button.prop('title', r.data.title)
							.data('action', r.data.action)
							.data('nonce', r.data.nonce)
							.removeClass(r.data.remove)
							.addClass(r.data.add);

						counter.html(r.data.count);

						if ( $list ) {
							avatars.html(r.data.avatars);
						}

						$like.show();

					} else {
						console.log(r.data);
					}
				}
			);

			button.click(function (e) {
				e.preventDefault();
				$.post( gEditorial.api, {
						action: 'geditorial_like',
						what : button.data('action'),
						id : button.data('id'),
						nonce : button.data('nonce'),
					}, function (r) {
						if (r.success) {
							button.prop('title', r.data.title)
								.data('action', r.data.action)
								.removeClass(r.data.remove)
								.addClass(r.data.add);

							counter.html(r.data.count);
							if ( $list ) {
								avatars.html(r.data.avatars);
							}
						} else {
							console.log(r.data);
						}

						/**
						// If the response was successful (that is, 1 was returned), hide the notification;
						// Otherwise, we'll change the class name of the notification
						if ('1' === response) {
							$('#ajax-notification').fadeOut('slow');
						} else {

							$('#ajax-notification')
								.removeClass('updated')
								.addClass('error');

						} // end if
						**/
					}
				);
			});
		}
	});
}(jQuery));
