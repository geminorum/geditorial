(function ($, plugin, module) {
  if (typeof plugin === 'undefined') return;

  const s = {
    action: plugin._base + '_' + module,
    classs: plugin._base + '-' + module,
    spinner: 'span.-loading',
    theday: 'span.-the-day-number',
    msg: '.' + plugin._base + '-' + module + '-calendar > .-wrap.-messages',
    cal: '#' + plugin._base + '-' + module + '-calendar',
    box: '#' + plugin._base + '-' + module + '-add-new'
  };

  const app = {
    rtl: false,

    append: function (el) {
      const $box = $(s.box);
      const $day = $(el).parents('td.-day')[0];

      $box.appendTo($day).show();
      $('input[data-field="type"]', $box).val($(el).data('type'));
      $('input[data-field="day"]', $box).val($($day).data('day'));
      $('input[data-field="month"]', $box).val($(s.cal).data('month'));
      $('input[data-field="year"]', $box).val($(s.cal).data('year'));
      $('input[data-field="title"]', $box).attr('placeholder', $(el).data('title')).trigger('focus');
    },

    close: function (clear) {
      $(s.box).hide();
      if (clear) $('input[data-field="title"]', s.box).val('');
    },

    save: function () {
      const $box = $(s.box);
      const data = $(':input', $box).serialize();
      const day = $('input[data-field="day"]', $box).val();
      const nonce = $('input[data-field="nonce"]', $box).val();
      const $day = $('td[data-day="' + day + '"]');
      const $title = $('input[data-field="title"]', $box);
      const $messages = $(s.msg);
      const $spinner = $day.find(s.spinner);

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
      const $cal = $(s.cal);
      const $day = $('td[data-day="' + day + '"]');
      const $theday = $day.find(s.theday);
      const $spinner = $day.find(s.spinner);
      const $messages = $(s.msg);

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

    $('a.-the-day-newpost', s.cal).on('click', function (e) {
      e.preventDefault();
      app.append(this);
    });

    $('a[data-action="close"]', s.box).on('click', function (e) {
      e.preventDefault();
      app.close(true);
    });

    $('a[data-action="save"]', s.box).on('click', function (e) {
      e.preventDefault();
      app.save();
    });

    $('input[data-field="title"]', s.box).on('enterKey', function (e) {
      app.save();
    });

    $('input[data-field="title"]', s.box).on('keyup', function (e) {
      if (e.keyCode === 13) {
        app.save();
      } else if (e.keyCode === 27) {
        app.close();
      }
    });

    const sortable = $('ol.-sortable, td.-next-prev').sortable({
      group: s.classs,
      handle: 'span.-handle',
      containerSelector: 'ol, td',
      nested: false,
      delay: 200,
      tolerance: 6,
      distance: 10,
      onDrop: function ($item, container, _super) {
        // const data = sortable.sortable("serialize").get();
        // const jsonString = JSON.stringify(data, null, ' ');

        const day = $($item).data('day');
        const theday = $(container.el).data('day');

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

    $(document).trigger('gEditorialReady', [module, app]);
  });
}(jQuery, gEditorial, 'schedule'));
