jQuery(function ($) {
  const settings = $.extend({}, {
    checklist_tree: false,
    category_search: false
  }, gEditorial.tweaks.settings);

  if (settings.checklist_tree) {
    $('[id$="-all"] > ul.categorychecklist').each(function () {
      const $list = $(this);
      const $firstChecked = $list.find(':checkbox:checked').first();

      if (!$firstChecked.length) return;

      const posFirst = $list.find(':checkbox').position().top;
      const posChecked = $firstChecked.position().top;

      $list.closest('.tabs-panel').scrollTop(posChecked - posFirst + 5);
    });
  }

  if (settings.category_search) {
    const $input = $('<input class="category-search" type="search" value="" placeholder="' + gEditorial.tweaks.strings.search_placeholder + '" title="' + gEditorial.tweaks.strings.search_title + '" />');

    $('.inside > div.categorydiv').each(function () {
      const $row = $input.clone(true);

      $row.prependTo(this)
        .on('keyup', function (e) {
          const val = $(this).val();
          const li = $(this).parent().find('ul.categorychecklist li');
          const list = $(this).parent().find('ul.categorychecklist');

          li.hide();
          const containingLabels = list.find("label:contains('" + val + "')");
          containingLabels.closest('li').find('li').addBack().show();
          containingLabels.parents('ul.categorychecklist li').show();
        })
        .show();
    });
  }
});
