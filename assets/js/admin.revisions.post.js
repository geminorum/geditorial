(function ($, plugin, module) {
  if (typeof plugin === 'undefined') return;

  const s = {
    action: plugin._base + '_' + module
  };

  const app = {
    purge: function (el) {
      const $el = $(el);
      const $row = $el.parent('.misc-pub-section');
      const post = $el.data('parent');
      const $spinner = $row.find('.spinner');

      if ($row.hasClass('-done')) return false;

      $.ajax({
        url: plugin._url,
        method: 'POST',
        data: {
          action: s.action,
          what: 'purge',
          post_id: post,
          nonce: plugin[module]._nonce
        },
        beforeSend: function (xhr) {
          $spinner.addClass('is-active');
        },
        success: function (response, textStatus, xhr) {
          $spinner.removeClass('is-active');
          $el.attr('disabled', 'disabled');
          $row.addClass('-done');
          if (response.success) {
            $row.append('<span class="dashicons dashicons-yes -success"></span>');
            setTimeout(function () {
              $row.fadeOut('slow', function () {
                $(this).remove();
              });
            }, 3500);
            $('#revisionsdiv').slideUp();
          } else {
            $row.append('<span class="dashicons dashicons-warning -error"></span>');
          }
        }
      });
    },

    del: function (el) {
      const $el = $(el);
      const $row = $el.parents('li');
      const post = $el.data('parent');
      const revision = $el.data('id');
      const $spinner = $row.find('.spinner');

      if ($row.hasClass('-done')) return false;

      $.ajax({
        url: plugin._url,
        method: 'POST',
        data: {
          action: s.action,
          what: 'delete',
          revision_id: revision,
          post_id: post,
          nonce: plugin[module]._nonce
        },
        beforeSend: function (xhr) {
          $spinner.addClass('is-active');
        },
        success: function (response, textStatus, xhr) {
          $spinner.removeClass('is-active');
          $el.attr('disabled', 'disabled');
          $row.addClass('-done');
          if (response.success) {
            $row.append('<span class="dashicons dashicons-yes -success"></span>');
            setTimeout(function () {
              $row.fadeOut('slow', function () {
                $(this).remove();
              });
            }, 3500);
          } else {
            $row.append('<span class="dashicons dashicons-warning -error"></span>');
          }
        }
      });
    }
  };

  $(function () {
    $('#revisionsdiv a.-delete').on('click', function (e) {
      e.preventDefault();
      app.del(this);
    });

    $('.misc-pub-section.geditorial-admin-wrap.-revisions a.-purge').on('click', function (e) {
      e.preventDefault();
      app.purge(this);
    });

    $(document).trigger('gEditorialReady', [module, app]);
  });
}(jQuery, gEditorial, 'revisions'));
