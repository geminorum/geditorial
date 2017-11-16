/* global jQuery, gEditorial, gEditorialModules */

(function ($, p, c, m) {
  var o = {};

  o.action = p._base + '_' + m;

  o.p = function (el) {
    var $el = $(el);
    var $row = $el.parent('.misc-pub-section');
    var post = $el.data('parent');
    var $spinner = $row.find('.spinner');

    if ($row.hasClass('-done')) return false;

    $.ajax({
      url: p._url,
      method: 'POST',
      data: {
        action: o.action,
        what: 'purge',
        post_id: post,
        nonce: p[m]._nonce
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
  };

  o.d = function (el) {
    var $el = $(el);
    var $row = $el.parents('li');
    var post = $el.data('parent');
    var revision = $el.data('id');
    var $spinner = $row.find('.spinner');

    if ($row.hasClass('-done')) return false;

    $.ajax({
      url: p._url,
      method: 'POST',
      data: {
        action: o.action,
        what: 'delete',
        revision_id: revision,
        post_id: post,
        nonce: p[m]._nonce
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
  };

  $(function () {
    $('#revisionsdiv a.-delete').on('click', function (e) {
      e.preventDefault();
      o.d(this);
    });

    $('.misc-pub-section.geditorial-admin-wrap.-revisions a.-purge').on('click', function (e) {
      e.preventDefault();
      o.p(this);
    });
  });

  // c[m] = o;
  //
  // if (p._dev)
  //   console.log(o);
}(jQuery, gEditorial, gEditorialModules, 'revisions'));
