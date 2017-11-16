/* global jQuery, inlineEditPost */

(function ($) {
  $('#the-list').on('click', '.editinline', function () {
    inlineEditPost.revert(); // revert Quick Edit menu so that it refreshes properly

    var tagID = $(this).parents('tr').attr('id');
    var editColCenter = $('fieldset.inline-edit-categories', '.inline-edit-row');
    var daySelect = $('.geditorial-admin-wrap-quickedit.-today', '.inline-edit-row');

    var day = $('#' + tagID).find('div.-date-icon span.-day').data('day');
    var month = $('#' + tagID).find('div.-date-icon span.-month').data('month');
    var year = $('#' + tagID).find('div.-date-icon span.-year').data('year');
    var cal = $('#' + tagID).find('div.-date-icon span.-cal').data('cal');

    $(':input[name=geditorial-today-date-day]', daySelect).val(day);
    $(':input[name=geditorial-today-date-month]', daySelect).val(month);
    $(':input[name=geditorial-today-date-year]', daySelect).val(year);

    $(':input[name=geditorial-today-date-cal]', daySelect).find('option').removeAttr('selected');

    if (cal) {
      $(':input[name=geditorial-today-date-cal]', daySelect).val(cal);
      $(':input[name=geditorial-today-date-cal]', daySelect).find(':selected').attr('selected', false);
      $(':input[name=geditorial-today-date-cal]', daySelect).find('option[value="' + cal + '"]').attr('selected', true);
    }

    daySelect.appendTo(editColCenter[0]);
  });
}(jQuery));
