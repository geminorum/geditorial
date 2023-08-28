(function ($) {
  $('div.wp-tab-panel.-with-select-all').each(function (index, currentObject) {
    const row = $(
      '<li><label><input type="checkbox" class="checkbox-all" /> ' +
        ($(currentObject).data('select-all-label') || 'Select All') +
        '</label></li>'
    );
    $('ul', currentObject).prepend(row);
  });

  $('input.checkbox-all').on('click', function () {
    $('input[type=checkbox]', $(this).parents('ul')).prop(
      'checked',
      $(this).prop('checked')
    );
  });
})(jQuery);
