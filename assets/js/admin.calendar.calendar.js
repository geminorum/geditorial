(function ($, p, c, m) {
  "use strict";

  var modal,
    s = {
      action: p._base + '_' + m,
      classs: p._base + '-' + m,
      spinner: 'span.-loading',
      theday: 'span.-the-day-number',
      msg: '.' + p._base + '-' + m + '-calendar > .-wrap.-messages',
      cal: '#' + p._base + '-' + m + '-calendar',
      box: '#' + p._base + '-' + m + '-add-new',
    },
    o = {
      rtl: 'rtl' === $('html').attr('dir'),
      // strings: $.extend({}, {
      //   modal_title: 'Choose an Image',
      //   modal_button: 'Set as image',
      // }, p[m].strings || {} ),

      append: function(el){
        var $box = $(s.box),
          $day = $(el).parents('td.-day')[0];

        $box.appendTo($day).show();
        $('input[data-field="type"]', $box).val($(el).data('type'));
        $('input[data-field="day"]', $box).val($($day).data('day'));
        $('input[data-field="month"]', $box).val($(s.cal).data('month'));
        $('input[data-field="year"]', $box).val($(s.cal).data('year'));
        $('input[data-field="title"]', $box).attr('placeholder', $(el).data('title')).focus();
      },

      close: function(clear) {
        $(s.box).hide();
        if ( clear ) {
          $('input[data-field="title"]', s.box).val('');
        }
      },

      save: function(){

        var $box = $(s.box),
          data = $(':input', $box).serialize(),
          day = $('input[data-field="day"]', $box).val(),
          nonce =  $('input[data-field="nonce"]', $box).val(),
          $day = $('td[data-day="'+day+'"]'),
          $title = $('input[data-field="title"]', $box),
          $messages = $(s.msg),
          $spinner = $day.find(s.spinner);

        $.ajax({
          url: p._url,
          method: 'POST',
          data: {
            action: s.action,
            what: 'addnew',
            data: data,
            nonce: nonce
          },
          beforeSend: function(xhr) {
            $title.prop('disabled', true);
            $spinner.addClass('is-active');
            $messages.html('');
          },
          success: function(response, textStatus, xhr) {
            $title.prop('disabled', false);
            $spinner.removeClass('is-active');
            if (response.success) {
              $(response.data).appendTo($('ol', $day));
              o.close(true);
            } else {
              $messages.html(response.data);
              o.close();
              setTimeout(function(){
                $messages.html('');
              }, 4500);
            }
          }
        });
      },

      reorder: function($sortable, post, nonce, day){

        var $cal = $(s.cal),
          $day = $('td[data-day="'+day+'"]'),
          cal = $cal.data('calendar'),
          year = $cal.data('year'),
          month = $cal.data('month'),
          $theday = $day.find(s.theday),
          $messages = $(s.msg),
          $spinner = $day.find(s.spinner);

        $.ajax({
          url: p._url,
          method: 'POST',
          data: {
            action: s.action,
            what: 'reorder',
            day: day,
            month: month,
            year: year,
            cal: cal,
            post_id: post,
            nonce: nonce
          },
          beforeSend: function(xhr) {
            $theday.hide();
            $spinner.addClass('is-active');
            $sortable.sortable('disable');
            $messages.html('');
          },
          success: function(response, textStatus, xhr) {
            $theday.show();
            $spinner.removeClass('is-active');
            $sortable.sortable('enable');
            // if (response.success) {
              $messages.html(response.data);
              setTimeout(function(){
                $messages.html('');
              }, 4500);
            // }
          }
        });
      }
    };

  $(function() {

    $('a.-the-day-newpost', s.cal).click(function(e) {
      e.preventDefault();
      o.append(this);
    });

    $('a[data-action="close"]', s.box).click(function(e) {
      e.preventDefault();
      o.close(true);
    });

    $('a[data-action="save"]', s.box).click(function(e) {
      e.preventDefault();
      o.save();
    });

    $('input[data-field="title"]', s.box).bind('enterKey',function(e){
       o.save();
    });

    $('input[data-field="title"]', s.box).keyup(function(e){
      if(e.keyCode == 13){
        o.save();
      } else if ( e.keyCode == 27) {
        o.close();
      }
    });

    var sortable = $('ol.-sortable').sortable({
      group: s.classs,
      handle: 'span.-handle',
      nested: false,
      delay: 200,
      tolerance: 6,
      distance: 10,
      onDrop: function ($item, container, _super) {
        // var data = sortable.sortable("serialize").get();
        // var jsonString = JSON.stringify(data, null, ' ');

        var day = $($item).data('day'),
          theday = $(container.el).data('day');

        if( day !== theday ) {
          o.reorder(sortable,
            $($item).data('post'),
            $($item).data('nonce'),
            theday
          );
          $($item).data('day', theday);
        }

        _super($item, container);
      },
      onDrag: function ($item, position, _super, event) {
        if ( o.rtl )
          position = {
            top: position.top,
            right: ( position.left - $item.outerWidth() )*-1,
          };
        _super($item, position, _super, event);
      },
      afterMove: function ($placeholder, container, $closestItemOrContainer) {
        $('td', s.cal).removeClass('-dragged-here');
        $(container.el).parent('td').addClass('-dragged-here');
      }
    // }).disableSelection();
    });
  });

  // c[m] = o;
  //
  // if (p._dev) {
  //   console.log(o);
  // }

}(jQuery, gEditorial, gEditorialModules, 'calendar'));
