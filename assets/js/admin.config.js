/* global List */

jQuery(function ($) {
  const moduleList = new List('geditorial-settings', {
    listClass: '-list',
    searchClass: '-search',
    valueNames: [
      '-title',
      '-description',
      '-th',
      '-module-key',
      '-module-title',
      '-module-access',
      '-module-keywords',
      'status'
    ]
  });

  // https://github.com/javve/list.js/issues/366#issuecomment-274942284
  moduleList.on('updated', function (list) {
    // also .clear container
    if ((list.items.length - 1) === list.matchingItems.length) return;

    // every updated item take animation with their id
    list.matchingItems.forEach(function (element) {
      const id = element.elm.id;
      $('#' + id).addClass('animated fadeIn');
    });
  });

  $('input[data-filter]').on('change', function () {
    const val = this.value;
    if (val === 'all') {
      moduleList.filter();
    } else {
      moduleList.filter(function (item) {
        return (item.values().status === val);
      });
    }
  });

  $('.fields-check-all').on('click', function (e) {
    $(this).closest('table').find('.fields-check').prop('checked', this.checked);
  });

  $('.fields-check').on('click', function (e) {
    $(this).closest('table').find('.fields-check-all').prop('checked', false);
  });

  $('input[data-do]').on('click', function (e) {
    e.preventDefault();

    const module = $(this).data('module');
    const action = $(this).data('do');
    const $box = $("div[data-module='" + module + "']");
    const $spinner = $box.find('.spinner');
    const $icon = $box.find('[data-icon]');

    $.ajax({
      url: gEditorial._url,
      method: 'POST',
      data: {
        action: gEditorial._base + '_config',
        what: 'state',
        name: module,
        doing: action,
        nonce: gEditorial.config._nonce
      },
      beforeSend: function (xhr) {
        $box.addClass('-spinning');
        $icon.hide();
        $spinner.addClass('is-active');
      },
      success: function (response, textStatus, xhr) {
        $box.removeClass('-spinning');
        $icon.show();
        $spinner.removeClass('is-active');

        if (response.success) {
          $box.find('[data-do]').hide();

          // FIXME: display response somewhere!

          if (action === 'disable') {
            $box.addClass('-disabled').removeClass('-enabled');
            $box.find('[data-do="enable"]').show();
            $box.find('[data-do="configure"]').hide();
            $box.find('[data-do="enabled"]').html('false');
          } else if (action === 'enable') {
            $box.addClass('-enabled').removeClass('-disabled');
            $box.find('[data-do="disable"]').show();
            $box.find('[data-do="configure"]').show();
            $box.find('[data-do="enabled"]').html('true');
          }
        }
      }
    });
  });
});
