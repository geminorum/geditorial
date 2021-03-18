(function ($) {
  $('div[data-setting="type-object"]').each(function () {
    const instance = $(this);
    // const field = instance.data('field');

    $('a[data-setting="object-addnew"]', instance).on('click', function (event) {
      event.preventDefault();
      const cloned = $('div[data-setting="object-empty"]', instance).clone(true);
      cloned.removeAttr('data-setting').css('display', 'block').appendTo($('div.-body', instance));
    });

    $('a[data-setting="object-remove"]', instance).on('click', function (event) {
      event.preventDefault();
      $(this).parents('div.-object-group').slideUp('normal', function () {
        $(this).remove();
      });
    });
  });
})(jQuery);
