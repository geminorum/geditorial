var gEditorialDrafts = {};

(function($, p, m) {
	'use strict';

	m.empty   = true;
	m.added   = false;
	m.action  = 'geditorial_drafts';
	m.box     = '#editorial-drafts';
	m.button  = '#wp-admin-bar-editorial-drafts a.ab-item';
	m.spinner = '.geditorial-spinner-adminbar';
	m.wrapper = '<div id="editorial-drafts" class="geditorial-wrap -drafts" style="display:none;"><div class="-content"></div></div>';

	m.toggle = function(){
		if($(this.box).is(':visible')) {
			$(this.box).slideUp(function() {
				$(this).hide();
			});
		} else {
			$(this.box).css({
				height:'auto',
			}).slideDown();
		};
	};

	m.populate = function(){

		if ( ! this.empty ){
			this.toggle();
			return;
		}

		$('body').append(this.wrapper);

		var spinner = $(this.button).find(this.spinner);

		$.ajax({
			url: p.api,
			method: 'POST',
			data: {
				action: m.action,
				what: 'list',
				nonce: p.nonce
			},
			beforeSend: function(xhr) {
				spinner.addClass('is-active');
			},
			success: function(response, textStatus, xhr) {
				spinner.removeClass('is-active');

				if (response.success) {
					$(m.box).find('.-content').html(response.data.html);
					m.empty = false;
					m.toggle();
				}
			}
		});
	};

	$( document ).ready( function () {
		$(m.button).click(function(e) {
			e.preventDefault();
			m.populate();
		});
	});

}(jQuery, gEditorial, gEditorialDrafts));
