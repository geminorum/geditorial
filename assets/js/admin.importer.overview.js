(function ($, plugin, mainkey, context) {
  const s = {
    action: plugin._base + '_' + mainkey,
    delete: '#' + plugin._base + '-' + mainkey + '-wrap a.button.has-action',
    toggle: '#' + plugin._base + '-' + mainkey + '-wrap a.button.do-toggle',
    wrap: '#' + plugin._base + '-' + mainkey + '-wrap div.-data-block-wrap',
    pot: 'div.-block-message',
    block: 'div.-data-block',
    content: 'div.-block-content',
    cleanup: 'a.do-cleanup-post',
    spinner: '.spinner'
  };

  const utils = {
    $io: (el, content) => {
      el.fadeOut('fast', function () {
        $(this).html(content || '').fadeIn();
      });
    }
  };

  const app = {
    config: $.extend({}, {
      canimport: false,
      noaccess: 'You don\'t have permission to do this.'
    }, plugin[mainkey].config || {}),

    init: function () {
      if (app.config.canimport) {
        $(s.delete).removeClass('hidden');

        $(s.delete).on('click', function (event) {
          event.preventDefault();
          app.action(this);
        });

        $(s.cleanup).on('click', function (event) {
          event.preventDefault();
          app.cleanup(this);
        });
      }

      $(s.toggle).on('click', function (event) {
        event.preventDefault();
        const $content = $(this).parents(s.block).find(s.content);

        if ($(this).hasClass('visible')) {
          $content.slideUp();
        } else {
          $content.slideDown();
        }

        $(this).toggleClass('visible');
      });
    },

    cleanup: (el) => {
      const $spinner = $(el).parent().find(s.spinner);
      const $wrap = $(s.wrap);
      const what = $(el).data('action');

      $.ajax({
        url: plugin._url,
        method: 'POST',
        data: {
          what,
          action: s.action,
          post_id: plugin[mainkey].post_id,
          nonce: plugin[mainkey]._nonce
        },
        beforeSend: function (xhr) {
          $spinner.addClass('is-active');
        },
        success: function (response, textStatus, xhr) {
          $spinner.removeClass('is-active');
          if (response.success) {
            utils.$io($wrap, response.data);
            $(el).addClass('hidden');
          } else {
            utils.$io($wrap, response.data);
            console.log(response);
          }
        }
      });
    },

    action: function (el) {
      const $spinner = $(el).parent().find(s.spinner);
      const $content = $(el).parents(s.block).find(s.content);
      const $pot = $(el).parent().find(s.pot);
      const what = $(el).data('action');
      const metakey = $(el).data('key');

      $.ajax({
        url: plugin._url,
        method: 'POST',
        data: {
          what,
          metakey,
          action: s.action,
          post_id: plugin[mainkey].post_id,
          nonce: plugin[mainkey]._nonce
        },
        beforeSend: function (xhr) {
          $spinner.addClass('is-active');
        },
        success: function (response, textStatus, xhr) {
          $spinner.removeClass('is-active');
          if (response.success) {
            utils.$io($pot, response.data);
            if (what === 'discard_meta') $content.slideUp();
          } else {
            utils.$io($pot, response.data);
            console.log(response);
          }
        }
      });
    }
  };

  $(function () {
    $(document).trigger('gEditorial:Module:Loaded', [
      mainkey,
      context,
      app,
      app.init()
    ]);
  });
}(jQuery, gEditorial, 'importer', 'overview'));
