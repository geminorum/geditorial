(function ($, plugin, module) {
  if (typeof plugin === 'undefined') return;

  const s = {
    action: plugin._base + '_' + module
  };

  const app = {
    action: function (el) {
      const $el = $(el);
      const $row = $el.parents('.geditorial-admin-wrap');
      const $input = $row.find('input.-link');
      const action = $el.data('action');
      const $spinner = $row.find('.spinner');

      $.ajax({
        url: plugin._url,
        method: 'POST',
        data: {
          action: s.action,
          what: action,
          post_id: $el.data('id'),
          nonce: $el.data('nonce')
        },
        beforeSend: function (xhr) {
          $spinner.addClass('is-active');
        },
        success: function (response, textStatus, xhr) {
          $spinner.removeClass('is-active');

          if (response.success) {
            if (action === 'public') {
              $input.val(response.data).show();
            } else {
              $input.hide();
            }

            $el.hide();
            $row.find('a.-after-' + action).show();
          } else {
            console.log(response);
          }
        }
      });
    }
  };

  $(function () {
    $('.geditorial-admin-wrap.-drafts a.-action').on('click', function (e) {
      e.preventDefault();
      app.action(this);
    });

    $(document).trigger('gEditorialReady', [module, app]);
  });
}(jQuery, gEditorial, 'drafts'));
