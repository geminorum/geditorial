/* global jQuery, gEditorial, gEditorialModules */

(function ($, p, c, m) {
  var o = {};

  o.action = p._base + '_' + m;
  o.buttons = '#wp-admin-bar-geditorial-markdown-default li.-action a';
  o.spinner = '.geditorial-spinner';

  o.doaction = function (el) {
    var action = $(el).attr('rel');
    var spinner = $(el).find(o.spinner);

    $.ajax({
      url: p._url,
      method: 'POST',
      data: {
        action: o.action,
        what: action,
        post_id: p[m].post_id,
        nonce: p[m]._nonce
      },
      beforeSend: function (xhr) {
        spinner.addClass('is-active');
      },
      success: function (response, textStatus, xhr) {
        spinner.removeClass('is-active');

        // if (response.success) {
        $(el).parent().html(response.data);
        // }
      }
    });
  };

  $(function () {
    $(o.buttons).click(function (e) {
      e.preventDefault();
      o.doaction(this);
    });
  });

  // c[m] = o;
  //
  // if (p._dev)
  //   console.log(o);
}(jQuery, gEditorial, gEditorialModules, 'markdown'));
