jQuery(document).ready(function($) {
	'use strict';

	if (gEditorial.tweaks.settings.checklist_tree) {
		$('[id$="-all"] > ul.categorychecklist').each(function() {
			var $list = $(this);
			var $firstChecked = $list.find(':checkbox:checked').first();

			if (!$firstChecked.length)
				return;

			var pos_first = $list.find(':checkbox').position().top;
			var pos_checked = $firstChecked.position().top;

			$list.closest('.tabs-panel').scrollTop(pos_checked - pos_first + 5);
		});
	}

	if (gEditorial.tweaks.settings.category_search) {

		var $input = $('<input class="category-search" type="search" value="" placeholder="' + gEditorial.tweaks.strings.search_placeholder + '" title="' + gEditorial.tweaks.strings.search_title + '" />');

		$('.inside > div.categorydiv').each(function() {
			var $tax = $(this),
				$row = $input.clone(true);

			$row.prependTo(this)
				.bind('keyup', function(e) {

					var val = $(this).val(),
			            li = $(this).parent().find('ul.categorychecklist li'),
			            list = $(this).parent().find('ul.categorychecklist');

			        li.hide();
			        var containingLabels = list.find("label:contains('" + val + "')");
			        containingLabels.closest('li').find('li').addBack().show();
			        containingLabels.parents('ul.categorychecklist li').show();
				})
				.show();
		});
	}
});
