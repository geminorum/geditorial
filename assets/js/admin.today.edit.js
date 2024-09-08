/* global inlineEditPost */

(function ($) {
  $('#the-list').on('click', '.editinline', function () {
    inlineEditPost.revert(); // revert Quick Edit menu so that it refreshes properly

    const tagID = $(this).parents('tr').attr('id');
    const editColCenter = $('fieldset.inline-edit-categories', '.inline-edit-row');
    const daySelect = $('.geditorial-admin-wrap-quickedit.-today', '.inline-edit-row');

    const day = $('#' + tagID).find('div.-date-badge span.-day').data('day');
    const month = $('#' + tagID).find('div.-date-badge span.-month').data('month');
    const year = $('#' + tagID).find('div.-date-badge span.-year').data('year');
    const cal = $('#' + tagID).find('div.-date-badge span.-cal').data('cal');

    $(':input[name=geditorial-today-date-day]', daySelect).val(day);
    $(':input[name=geditorial-today-date-month]', daySelect).val(month);
    $(':input[name=geditorial-today-date-year]', daySelect).val(year);

    $(':input[name=geditorial-today-date-cal]', daySelect).find('option').removeAttr('selected');

    if (cal) {
      $(':input[name=geditorial-today-date-cal]', daySelect).val(cal);
      $(':input[name=geditorial-today-date-cal]', daySelect).find('option:selected').attr('selected', false);
      $(':input[name=geditorial-today-date-cal]', daySelect).find('option[value="' + cal + '"]').attr('selected', true);
    }

    daySelect.appendTo(editColCenter[0]);
  });
}(jQuery));
