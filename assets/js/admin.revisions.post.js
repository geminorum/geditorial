/* global jQuery, gEditorial */

(function ($, plugin, module) {
  var s = {
    action: plugin._base + '_' + module
  };

  var app = {
    purge: function (el) {
      var $el = $(el);
      var $row = $el.parent('.misc-pub-section');
      var post = $el.data('parent');
      var $spinner = $row.find('.spinner');

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
      var $el = $(el);
      var $row = $el.parents('li');
      var post = $el.data('parent');
      var revision = $el.data('id');
      var $spinner = $row.find('.spinner');

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

    $(document).trigger('gEditorialReady', [ module, app ]);
  });
}(jQuery, gEditorial, 'revisions'));
