jQuery(document).ready(function($) {

	$('ul.geditorial-specs-new select.item-dropdown-new').change(function(){
		if ($(this).parents('ol.geditorial-specs-list').length) { return false; }

		var selectedVal = $(this).find(":selected").val();
		if ( selectedVal == '-1' ){ return false; }

		$("ol.geditorial-specs-list .item-body").slideUp();
		var row = $('ul.geditorial-specs-new li').clone(true);
		var selectedTitle = $(this).find(":selected").text();
		$(this).find(":selected").remove();

		row.find('select.item-dropdown-new').removeClass('item-dropdown-new');
		row.find('span.item-excerpt').html(selectedTitle);
		row.find('select.item-dropdown option[value="-1"]').remove();
		row.find('select.item-dropdown option[value="'+selectedVal+'"]').selected = true;
		row.appendTo('ol.geditorial-specs-list');
		row.find('.item-body').slideDown();
		row.find('textarea').focus();
		gEditorialSpecsReOrder();
	});

	$('body').on('click', 'ol.geditorial-specs-list .item-delete', function(e){
		e.preventDefault();
		$(this).closest('li').slideUp('normal', function() {
			$(this).remove();
		});
	});

	$('body').on('click', 'ol.geditorial-specs-list .item-excerpt', function(e){
		e.preventDefault();
		$("ol.geditorial-specs-list .item-body").slideUp();
		var clicked = $(this).parent().parent().find('.item-body');
		if( ! clicked.is(":visible") ) {
			clicked.slideDown();
		};
	});

	$('ol.geditorial-specs-list').sortable({
		// disable: true,
		group: 'geditorial-specs',
		handle: '.item-handle',
		stop: function () {
			gEditorialSpecsReOrder();
		}
	// }).disableSelection();
	});

	// http://stackoverflow.com/a/14736775
	var gEditorialSpecsReOrder = function(){
		var inputs = $('input.item-order');
		var nbElems = inputs.length;
		inputs.each(function(idx) {
			$(this).val(nbElems - idx);
		});
	};
});
