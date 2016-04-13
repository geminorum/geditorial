(function($) {
	'use strict';

	$('#the-list').on('click', '.editinline', function() {

		inlineEditPost.revert(); // revert Quick Edit menu so that it refreshes properly

		var tag_id = $(this).parents('tr').attr('id'),
			postTitleLabel = $(':input[name="post_title"]', '.inline-edit-row').parents('label'),
			postNameLabel = $(':input[name="post_name"]', '.inline-edit-row').parents('label');

		if (gEditorial.meta.hasOwnProperty('ot')) {
			var ot = $('#' + tag_id)
				.find('div.geditorial-meta-ot-value')
				.text();

			$('.inline-edit-row')
				.find('input.geditorial-meta-ot')
				.val(ot)
				.parents('label')
				.insertBefore(postTitleLabel);
		}

		if (gEditorial.meta.hasOwnProperty('st')) {
			var st = $('#' + tag_id)
				.find('div.geditorial-meta-st-value')
				.text();

			$('.inline-edit-row')
				.find('input.geditorial-meta-st')
				.val(st)
				.parents('label')
				.insertAfter(postTitleLabel);
		}

		if (gEditorial.meta.hasOwnProperty('as')) {
			var as = $('#' + tag_id)
				.find('div.geditorial-meta-as-value')
				.text();

			$('.inline-edit-row')
				.find('input.geditorial-meta-as')
				.val(as)
				.parents('label')
				.insertAfter(postNameLabel);
		}
	});
}(jQuery));
