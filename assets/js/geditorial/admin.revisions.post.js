(function($, p, c, m) {
  'use strict';

  var o = {};

  o.action = p._domain + '_' + m;

  o.p = function(el) {

    var $el = $(el),
      $row = $el.parent('.misc-pub-section'),
      post = $el.data('parent'),
      $spinner = $row.find('.spinner');

    if ($row.hasClass('-done'))
      return false;

    $.ajax({
      url: p._api,
      method: 'POST',
      data: {
        action: o.action,
        what: 'purge',
        post_id: post,
        nonce: p._nonce
      },
      beforeSend: function(xhr) {
        $spinner.addClass('is-active');
      },
      success: function(response, textStatus, xhr) {
        $spinner.removeClass('is-active');
        $el.attr('disabled', 'disabled');
        $row.addClass('-done');
        if (response.success) {
          $row.append('<span class="dashicons dashicons-yes -success"></span>');
          setTimeout(function() {
            $row.fadeOut('slow', function() {
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

  o.d = function(el) {

    var $el = $(el),
      $row = $el.parents('li'),
      post = $el.data('parent'),
      revision = $el.data('id'),
      $spinner = $row.find('.spinner');

    if ($row.hasClass('-done'))
      return false;

    $.ajax({
      url: p._api,
      method: 'POST',
      data: {
        action: o.action,
        what: 'delete',
        revision_id: revision,
        post_id: post,
        nonce: p._nonce
      },
      beforeSend: function(xhr) {
        $spinner.addClass('is-active');
      },
      success: function(response, textStatus, xhr) {
        $spinner.removeClass('is-active');
        $el.attr('disabled', 'disabled');
        $row.addClass('-done');
        if (response.success) {
          $row.append('<span class="dashicons dashicons-yes -success"></span>');
          setTimeout(function() {
            $row.fadeOut('slow', function() {
              $(this).remove();
            });
          }, 3500);
        } else {
          $row.append('<span class="dashicons dashicons-warning -error"></span>');
        }
      }
    });
  };

  $(document).ready(function() {

    $('#revisionsdiv a.-delete').on('click', function(e) {
      e.preventDefault();
      o.d(this);
    });

    $('.misc-pub-section.geditorial-admin-wrap.-revisions a.-purge').on('click', function(e) {
      e.preventDefault();
      o.p(this);
    });
  });

  c[m] = o;

  if (p._dev)
    console.log(c);

}(jQuery, gEditorial, gEditorialModules, 'revisions'));
