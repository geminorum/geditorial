(function($, p, c, m) {
  "use strict";

  var o = {};

  o.e = true; // empty
  o.action = p._base + '_' + m;
  o.box = '#wp-admin-bar-geditorial-audit-box';
  o.button = '#wp-admin-bar-geditorial-audit-attributes a.ab-item';
  o.spinner = '.geditorial-spinner';

  o.watch = function() {
    // do stuff when user has been idle for 1 second
    // @REF: https://stackoverflow.com/a/9424784/4864081
    var wto;
    $(':input', o.box).change(function() {
      clearTimeout(wto);
      wto = setTimeout(function() {
        o.store();
      }, 1000);
    });
  };

  o.store = function() {
    var data = $(':input', o.box).serialize(),
      spinner = $(o.button).find(o.spinner);

    $.ajax({
      url: p._url,
      method: 'POST',
      data: {
        action: o.action,
        what: 'store',
        post_id: p[m].post_id,
        data: data,
        nonce: p[m]._nonce
      },
      beforeSend: function(xhr) {
        spinner.addClass('is-active');
      },
      success: function(response, textStatus, xhr) {
        spinner.removeClass('is-active');

        if (response.success) {
          $(o.box).html(response.data);
          o.watch();
        }
      }
    });
  };

  o.populate = function() {

    if (!this.e) {
      return;
    }

    var spinner = $(this.button).find(this.spinner);

    $.ajax({
      url: p._url,
      method: 'POST',
      data: {
        action: o.action,
        what: 'list',
        post_id: p[m].post_id,
        nonce: p[m]._nonce
      },
      beforeSend: function(xhr) {
        spinner.addClass('is-active');
      },
      success: function(response, textStatus, xhr) {
        spinner.removeClass('is-active');

        if (response.success) {
          $(o.box).html(response.data);
          o.e = false;

          o.watch();
        }
      }
    });
  };

  $(function() {
    $(o.button).click(function(e) {
      e.preventDefault();
      o.populate();
    });
  });

  // c[m] = o;
  //
  // if (p._dev)
  //   console.log(o);

}(jQuery, gEditorial, gEditorialModules, 'audit'));
