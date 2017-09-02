(function($, p, c, m) {
  "use strict";

  var o = {};

  o.e = true; // empty
  o.action = p._base + '_' + m;
  o.box = '#editorial-' + m;
  o.button = '#wp-admin-bar-editorial-drafts a.ab-item';
  o.spinner = '.geditorial-spinner-adminbar';
  o.wrapper = '<div id="editorial-' + m + '" class="geditorial-wrap -drafts" style="display:none;"><div class="-content"></div></div>';

  o.toggle = function() {
    if ($(this.box).is(':visible')) {
      $(this.box).slideUp(function() {
        $(this).hide();
      });
    } else {
      $(this.box).css({height: 'auto'}).slideDown();
    };
  };

  o.populate = function() {

    if (!this.e) {
      this.toggle();
      return;
    }

    $('body').append(this.wrapper);

    var spinner = $(this.button).find(this.spinner);

    $.ajax({
      url: p._url,
      method: 'POST',
      data: {
        action: o.action,
        what: 'list',
        nonce: p[m]._nonce
      },
      beforeSend: function(xhr) {
        spinner.addClass('is-active');
      },
      success: function(response, textStatus, xhr) {
        spinner.removeClass('is-active');

        if (response.success) {
          $(o.box).find('.-content').html(response.data);
          o.e = false;
          o.toggle();
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

}(jQuery, gEditorial, gEditorialModules, 'drafts'));
