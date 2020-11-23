(function ($) {
  $(function () {
    const $like = $('.geditorial-wrap.-like');
    const $list = $like.data('avatars');

    if ($like.length > 0) {
      const button = $like.find('a.like');
      const counter = $like.find('span.like');
      const avatars = $list ? $like.find('ul.like') : null;

      button.removeAttr('href');

      $.post(gEditorial._url, {
        action: gEditorial._base + '_like',
        what: 'check',
        id: button.data('id')
      }, function (r) {
        if (r.success) {
          button
            .prop('title', r.data.title)
            .data('action', r.data.action)
            .data('nonce', r.data.nonce)
            .removeClass(r.data.remove)
            .addClass(r.data.add);

          counter.html(r.data.count);

          if ($list) {
            avatars.html(r.data.avatars);
          }

          $like.show();
        } else {
          console.log(r.data);
        }
      });

      button.click(function (e) {
        e.preventDefault();
        $.post(gEditorial._url, {
          action: gEditorial._base + '_like',
          what: button.data('action'),
          id: button.data('id'),
          nonce: button.data('nonce')
        }, function (r) {
          if (r.success) {
            button.prop('title', r.data.title).data('action', r.data.action).removeClass(r.data.remove).addClass(r.data.add);

            counter.html(r.data.count);
            if ($list) {
              avatars.html(r.data.avatars);
            }
          } else {
            console.log(r.data);
          }
        });
      });
    }
  });
}(jQuery));
