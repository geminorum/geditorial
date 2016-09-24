jQuery(document).ready(function($) {
	'use strict';

	var settings = $.extend({}, {
		checklist_tree: '0',
		category_search: '0',
		excerpt_count: '0',
	}, gEditorial.tweaks.settings);

	if ('0' != settings.checklist_tree) {

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

	if ('0' != settings.category_search) {

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

	if ('0' != settings.excerpt_count) {

		if (wp.utils) {

			var counter = new wp.utils.WordCounter();

			$('div.geditorial-wordcount-wrap').each(function() {

				var $content = $(this).find('textarea'),
					$count = $(this).find('.geditorial-wordcount .-words'),
					prevCount = 0,
					contentEditor;

				$content.bind('keyup', function(e) {
					var text = $(this).val(),
						count = counter.count(text);

					if (count !== prevCount) {
						$count.text(count);
					}

					prevCount = count;
				});

				$(this).find('.geditorial-wordcount').show();

				// FIXME: check for max/min

				// FIXME: use debounce

				// function update() {
				// 	var text = $content.val(),
				// 		count = counter.count( text );
				//
				// 	if ( count !== prevCount ) {
				// 		$count.text( count );
				// 	}
				//
				// 	prevCount = count;
				// }
				//
				// $( document ).on( 'tinymce-editor-init', function( event, editor ) {
				// 	if ( editor.id !== 'content' ) {
				// 		return;
				// 	}
				//
				// 	contentEditor = editor;
				//
				// 	editor.on( 'nodechange keyup', _.debounce( update, 1000 ) );
				// } );
				//
				// $content.on( 'input keyup', _.debounce( update, 1000 ) );
				//
				// update();
			});
		}
	}
});
