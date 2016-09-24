jQuery(document).ready(function($) {
	'use strict';

	var parseFootnotes = function(content) {
		var footnotes = {};
		content = content.replace(/\<a[^h]*(?=href\=\"[^\"]*\#_ftnref([0-9]+)\")[^>]*\>\[([0-9]+)\]\<\/a\>(.*)/g,
			function(match, p1, p2, p3) {
				footnotes[p1] = p3.trim().replace(/^./, "").trim();
				return '';
			});

		// remove all the original footnotes when done
		// return content.replace(/\<a[^h]*(?=href\=\"[^\"]*\#_ftn([0-9]+)\")[^>]*\>(?:\<(?:strong|sup)\>)+\[([0-9]+)\](?:\<\/(?:strong|sup)\>)+\<\/a\>/g,
		return content.replace(/\<a[^h]*(?=href\=\"[^\"]*\#_ftn([0-9]+)\")[^>]*\>(?:\<\w+\>)*\[([0-9]+)\](?:\<\/\w+\>)*\<\/a\>/g,
			function(match, p1, p2, p3, p4) {
				return '[ref]' + footnotes[p1].replace(/\r\n|\r|\n/g, "") + '[/ref]';
			});
	}

	// http://stackoverflow.com/a/4215753
	// var toParams = function(arr) {
	// 	var rv = {};
	// 	for (var i = 0; i < arr.length; ++i)
	// 		rv[arr[i]] = true; // rv[i] = arr[i];
	// 	return rv;
	// }

	// var settings = $.extend({}, {
	// 	virastar_options: {},
	// }, gEditorial.ortho.settings);

	var strings = $.extend({}, {
		button_virastar_title: 'Apply Virastar!',
		qtag_virastar: 'V!',
		qtag_virastar_title: 'Apply Virastar!',
		qtag_swap_quotes: 'Swap Quotes',
		qtag_swap_quotes_title: 'Swap Not-Correct Quotes',
		qtag_word_footnotes: 'Word Footnotes',
		qtag_word_footnotes_title: 'MS Word Footnotes to WordPress [ref]',
	}, gEditorial.ortho.strings);

	var virastar_options = gEditorial.ortho.virastar || {};

	var virastarText = new Virastar(
		$.extend({}, virastar_options, {
			preserve_HTML: false,
			preserve_URIs: false,
		})
	);

	var virastarMarkdown = new Virastar(
		$.extend({}, virastar_options, {
			fix_dashes: false,
			cleanup_spacing: false,
			skip_markdown_ordered_lists_numbers_conversion: false,
			preserve_HTML: false,
		})
	);

	var virastarHTML = new Virastar(
		$.extend({}, virastar_options, {
			cleanup_spacing: false,
		})
	);

	if (typeof(QTags) !== 'undefined') {

		QTags.addButton('virastar', strings.qtag_virastar, function(e, c, ed) {

			var selected = c.value.substring(c.selectionStart, c.selectionEnd);
			if (selected !== '') {
				QTags.insertContent(virastarHTML.cleanup(selected));
			} else {
				$(c).val(virastarHTML.cleanup($(c).val()));
			}
		}, '', '', strings.qtag_virastar_title);

		QTags.addButton('swapquotes', strings.qtag_swap_quotes, function(e, c, ed) {
			var content = $(c).val();
			content = content.replace(/(»)(.+?)(«)/g, '«$2»');
			content = content.replace(/(”)(.+?)(“)/g, '“$2”');
			$(c).val(content);
		}, '', '', strings.qtag_swap_quotes_title);

		QTags.addButton('parsemswordfootnotes', strings.qtag_word_footnotes, function(e, c, ed) {
			var content = $(c).val();
			$(c).val(parseFootnotes(content));
		}, '', '', strings.qtag_word_footnotes_title);
	}

	// http://www.jankoatwarpspeed.com/make-image-buttons-a-part-of-input-fields/
	$("#titlewrap input, [data-ortho='text']").each(function() {
		$(this)
			.addClass('target-ortho-text')
			.add($('<a href="#" class="do-ortho do-ortho-text" title="' + strings.button_virastar_title + '" tabindex="-1"><span class="dashicons dashicons-admin-site"></span></a>'))
			.wrapAll('<span class="ortho-input-wrap"></span>');
	});

	$('a.do-ortho-text').click(function(e) {
		e.preventDefault();
		var target = $(this).closest('.ortho-input-wrap').find('.target-ortho-text');
		target.val(virastarText.cleanup(target.val()));
	});

	// http://stackoverflow.com/a/1503425/4864081
	$('.target-ortho-text').on('paste', function() {
		var element = this;
		setTimeout(function() {
			$(element).val(virastarText.cleanup($(element).val()));
		}, 100);
	});

	$("[data-ortho='markdown']").each(function() {
		$(this)
			.addClass('target-ortho-markdown')
			.add($('<a href="#" class="do-ortho do-ortho-markdown" title="' + strings.button_virastar_title + '" tabindex="-1"><span class="dashicons dashicons-admin-site"></span></a>'))
			.wrapAll('<span class="ortho-input-wrap"></span>');
	});

	$('a.do-ortho-markdown').click(function(e) {
		e.preventDefault();
		var target = $(this).closest('.ortho-input-wrap').find('.target-ortho-markdown');
		target.val(virastarMarkdown.cleanup(target.val()));
	});

	$('.target-ortho-markdown').on('paste', function() {
		var element = this;
		setTimeout(function() {
			$(element).val(virastarMarkdown.cleanup($(element).val()));
		}, 100);
	});

	$("#excerpt, [data-ortho='html']").each(function() {
		$(this)
			.addClass('target-ortho-html')
			.add($('<a href="#" class="do-ortho do-ortho-html" title="' + strings.button_virastar_title + '" tabindex="-1"><span class="dashicons dashicons-admin-site"></span></a>'))
			.wrapAll('<span class="ortho-input-wrap"></span>');
	});

	$('a.do-ortho-html').click(function(e) {
		e.preventDefault();
		var target = $(this).closest('.ortho-input-wrap').find('.target-ortho-html');
		target.val(virastarHTML.cleanup(target.val()));
	});

	// http://stackoverflow.com/a/1503425/4864081
	$('.target-ortho-html').on('paste', function() {
		var element = this;
		setTimeout(function() {
			$(element).val(virastarHTML.cleanup($(element).val()));
		}, 100);
	});
});
