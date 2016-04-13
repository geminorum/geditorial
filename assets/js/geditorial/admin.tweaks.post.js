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

		var $input = $('<input class="category-search" type="text" value="" placeholder="' + gEditorial.tweaks.strings.search_placeholder + '" title="' + gEditorial.tweaks.strings.search_title + '" />');

		$('.inside > div.categorydiv').each(function() {
			var $tax = $(this),
				$row = $input.clone(true);

			$row.prependTo(this)
				.bind('keyup', function(e) {
					var $val = $(this).val(),
						$list = $(this).parent().find('ul.categorychecklist'),
						$labels = $list.find("label:contains('" + $val + "')");

					$list.find('li').hide();
					$labels.closest('li').find('li').addBack().show();
					$list.show();
				})
				.show();
		});
	}
});
