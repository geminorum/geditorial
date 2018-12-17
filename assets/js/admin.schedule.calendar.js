(function ($, plugin, module) {
  var s = {
    action: plugin._base + '_' + module,
    classs: plugin._base + '-' + module,
    spinner: 'span.-loading',
    theday: 'span.-the-day-number',
    msg: '.' + plugin._base + '-' + module + '-calendar > .-wrap.-messages',
    cal: '#' + plugin._base + '-' + module + '-calendar',
    box: '#' + plugin._base + '-' + module + '-add-new'
  };

  var app = {
    rtl: false,

    append: function (el) {
      var $box = $(s.box);
      var $day = $(el).parents('td.-day')[0];

      $box.appendTo($day).show();
      $('input[data-field="type"]', $box).val($(el).data('type'));
      $('input[data-field="day"]', $box).val($($day).data('day'));
      $('input[data-field="month"]', $box).val($(s.cal).data('month'));
      $('input[data-field="year"]', $box).val($(s.cal).data('year'));
      $('input[data-field="title"]', $box).attr('placeholder', $(el).data('title')).focus();
    },

    close: function (clear) {
      $(s.box).hide();
      if (clear) $('input[data-field="title"]', s.box).val('');
    },

    save: function () {
      var $box = $(s.box);
      var data = $(':input', $box).serialize();
      var day = $('input[data-field="day"]', $box).val();
      var nonce = $('input[data-field="nonce"]', $box).val();
      var $day = $('td[data-day="' + day + '"]');
      var $title = $('input[data-field="title"]', $box);
      var $messages = $(s.msg);
      var $spinner = $day.find(s.spinner);

      $.ajax({
        url: plugin._url,
        method: 'POST',
        data: {
          action: s.action,
          what: 'addnew',
          data: data,
          nonce: nonce
        },
        beforeSend: function (xhr) {
          $title.prop('disabled', true);
          $spinner.addClass('is-active');
          $messages.html('');
        },
        success: function (response, textStatus, xhr) {
          $title.prop('disabled', false);
          $spinner.removeClass('is-active');
          if (response.success) {
            $(response.data).appendTo($('ol', $day));
            app.close(true);
          } else {
            $messages.html(response.data);
            app.close();
            setTimeout(function () {
              $messages.html('');
            }, 4500);
          }
        }
      });
    },

    reschedule: function ($sortable, post, nonce, day, month, year) {
      var $cal = $(s.cal);
      var $day = $('td[data-day="' + day + '"]');
      var $theday = $day.find(s.theday);
      var $spinner = $day.find(s.spinner);
      var $messages = $(s.msg);

      $.ajax({
        url: plugin._url,
        method: 'POST',
        data: {
          action: s.action,
          what: 'reschedule',
          day: day,
          month: month || $cal.data('month'),
          year: year || $cal.data('year'),
          cal: $cal.data('calendar'),
          post_id: post,
          nonce: nonce
        },
        beforeSend: function (xhr) {
          $theday.hide();
          $spinner.addClass('is-active');
          $sortable.sortable('disable');
          $messages.html('');
        },
        success: function (response, textStatus, xhr) {
          $theday.show();
          $spinner.removeClass('is-active');
          $sortable.sortable('enable');
          // if (response.success) {
          $messages.html(response.data);
          setTimeout(function () {
            $messages.html('');
          }, 4500);
          // }
        }
      });
    }
  };

  $(function () {
    app.rtl = $('html').attr('dir') === 'rtl';

    $('a.-the-day-newpost', s.cal).click(function (e) {
      e.preventDefault();
      app.append(this);
    });

    $('a[data-action="close"]', s.box).click(function (e) {
      e.preventDefault();
      app.close(true);
    });

    $('a[data-action="save"]', s.box).click(function (e) {
      e.preventDefault();
      app.save();
    });

    $('input[data-field="title"]', s.box).bind('enterKey', function (e) {
      app.save();
    });

    $('input[data-field="title"]', s.box).keyup(function (e) {
      if (e.keyCode === 13) {
        app.save();
      } else if (e.keyCode === 27) {
        app.close();
      }
    });

    var sortable = $('ol.-sortable, td.-next-prev').sortable({
      group: s.classs,
      handle: 'span.-handle',
      containerSelector: 'ol, td',
      nested: false,
      delay: 200,
      tolerance: 6,
      distance: 10,
      onDrop: function ($item, container, _super) {
        // var data = sortable.sortable("serialize").get();
        // var jsonString = JSON.stringify(data, null, ' ');

        var day = $($item).data('day');
        var theday = $(container.el).data('day');

        if (theday) {
          if (day !== theday) {
            app.reschedule(sortable,
              $($item).data('post'),
              $($item).data('nonce'),
              theday
            );

            $($item).data('day', theday);
          }
        } else {
          app.reschedule(sortable,
            $($item).data('post'),
            $($item).data('nonce'),
            1, // TODO: tell to find the first/last of the month
            $(container.el).data('month'),
            $(container.el).data('year')
          );

          $($item).remove();
        }

        _super($item, container);
      },
      onDrag: function ($item, position, _super, event) {
        if (app.rtl) {
          position = {
            top: position.top,
            right: (position.left - $item.outerWidth()) * -1
          };
        }
        _super($item, position, _super, event);
      },
      afterMove: function ($placeholder, container, $closestItemOrContainer) {
        $('td', s.cal).removeClass('-dragged-here');
        $(container.el).parent('td').addClass('-dragged-here');
        $(container.el).addClass('-dragged-here'); // for nex/prev month
      }
    // }).disableSelection();
    });

    $(document).trigger('gEditorialReady', [ module, app ]);
  });
}(jQuery, gEditorial, 'schedule'));
