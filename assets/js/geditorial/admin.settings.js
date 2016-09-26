jQuery(document).ready(function($) {

	$('.fields-check-all').click(function(e) {
		$(this).closest('table').find('.fields-check').prop('checked', this.checked);
	});

	$('.fields-check').click(function(e) {
		$(this).closest('table').find('.fields-check-all').prop('checked', false);
	});

	$('.button-toggle').click(function(e) {
		e.preventDefault();

		var module = $(this).data('module'),
			action = $(this).data('do'),
			$box = $("div[data-module='" + module + "']"),
			$spinner = $box.find('.spinner'),
			$icon = $box.find('.dashicons');

		$.ajax({
			url: gEditorial.api,
			method: 'POST',
			data: {
				action: 'geditorial_settings',
				what: 'state',
				name: module,
				doing: action,
				nonce: gEditorial.nonce
			},
			beforeSend: function(xhr) {
				$box.addClass('module-spinning');
				$icon.hide();
				$spinner.addClass('is-active');
			},
			success: function(response, textStatus, xhr) {
				$box.removeClass('module-spinning');
				$icon.show();
				$spinner.removeClass('is-active');

				if (response.success) {
					$box.find('.button-toggle').hide();

					if ('disable' == action) {
						$box.addClass('module-disabled').removeClass('module-enabled');
						$box.find('.button-toggle.button-primary').show();
						$box.find('.button-configure').hide();
					} else if ('enable' == action) {
						$box.addClass('module-enabled').removeClass('module-disabled');
						$box.find('.button-toggle.button-remove').show();
						$box.find('.button-configure').show();
					}
				}
			}
		});
	});
});
