jQuery(function ($) {
  var settings = $.extend({}, {
    checklist_tree: false,
    category_search: false
  }, gEditorial.tweaks.settings);

  if (settings.checklist_tree) {
    $('[id$="-all"] > ul.categorychecklist').each(function () {
      var $list = $(this);
      var $firstChecked = $list.find(':checkbox:checked').first();

      if (!$firstChecked.length) return;

      var posFirst = $list.find(':checkbox').position().top;
      var posChecked = $firstChecked.position().top;

      $list.closest('.tabs-panel').scrollTop(posChecked - posFirst + 5);
    });
  }

  if (settings.category_search) {
    var $input = $('<input class="category-search" type="search" value="" placeholder="' + gEditorial.tweaks.strings.search_placeholder + '" title="' + gEditorial.tweaks.strings.search_title + '" />');

    $('.inside > div.categorydiv').each(function () {
      var $row = $input.clone(true);

      $row.prependTo(this)
        .bind('keyup', function (e) {
          var val = $(this).val();
          var li = $(this).parent().find('ul.categorychecklist li');
          var list = $(this).parent().find('ul.categorychecklist');

          li.hide();
          var containingLabels = list.find("label:contains('" + val + "')");
          containingLabels.closest('li').find('li').addBack().show();
          containingLabels.parents('ul.categorychecklist li').show();
        })
        .show();
    });
  }
});
