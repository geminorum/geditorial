/* global jQuery, gEditorial, gEditorialModules */

(function ($, p, c, m) {
  var o = {};

  o.action = p._base + '_' + m;

  o.a = function (el) {
    var $el = $(el);
    var $row = $el.parents('.geditorial-admin-wrap');
    var $input = $row.find('input.-link');
    var action = $el.data('action');
    var $spinner = $row.find('.spinner');

    $.ajax({
      url: p._url,
      method: 'POST',
      data: {
        action: o.action,
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
  };

  $(function () {
    $('.geditorial-admin-wrap.-drafts a.-action').on('click', function (e) {
      e.preventDefault();
      o.a(this);
    });
  });

  // c[m] = o;
  // if (p._dev) console.log(o);
}(jQuery, gEditorial, gEditorialModules, 'drafts'));
