/* global jQuery, gEditorial */

(function ($, plugin, mainkey, context) {
  let modal;

  const s = {
    // action: [plugin._base, mainkey].join('_'),
    // classs: [plugin._base, mainkey].join('-'),

    pot: '#' + [plugin._base, mainkey, 'content', 'banners'].join('-'),
    empty: '#' + [plugin._base, mainkey, 'empty', 'row'].join('-') + '>li',
    item: 'li.-banner-item',
    upload: 'li.-banner-item .button.-upload',
    trash: 'li.-banner-item .button.-trash',
    move: '.button.-move',
    after: 'li.-banner-item .button.-insert-after',
    before: 'li.-banner-item .button.-insert-before',
    attachment: 'input.-attachment',
    title: 'input.-title',
    caption: 'input.-caption',
    // url: 'input.-url',
    image: 'div.-image-placeholder'
  };

  const app = {
    request: null,
    rtl: $('html').attr('dir') === 'rtl',
    strings: $.extend({}, {
      modal_title: 'Choose an Image',
      modal_button: 'Set as image'
    }, plugin[mainkey].strings || {}),

    init: function () {
      $(s.pot).sortable({
        handle: s.move
      });

      $(s.upload).on('click', function (e) {
        e.preventDefault();
        app.openModal($(this).parents(s.item));
      });

      $(s.after).on('click', function (e) {
        e.preventDefault();
        const row = $(s.empty).clone(true);
        row.insertAfter($(this).parents(s.item));
      });

      $(s.before).on('click', function (e) {
        e.preventDefault();
        const row = $(s.empty).clone(true);
        row.insertBefore($(this).parents(s.item));
      });

      $(s.trash).on('click', function (e) {
        e.preventDefault();
        $(this).parents(s.item).remove();
        if ($(s.pot + '>li').length) return;
        const row = $(s.empty).clone(true);
        row.appendTo(s.pot);
      });
    },

    // @REF: https://codex.wordpress.org/Javascript_Reference/wp.media
    openModal: function (item) {
      // if (!modal) { // NOTE: we must initiate modal every time to target the hidden input for each item!
      modal = wp.media({
        title: app.strings.modal_title,
        button: { text: app.strings.modal_button },
        // library: { type: app.config.mimetypes },
        library: { type: 'image' },
        multiple: false
      });

      modal.on('open', () => {
        const selection = modal.state().get('selection');
        const selected = $(s.attachment, item).val();
        selection.reset(selected ? [wp.media.attachment(selected)] : []);
      });

      modal.on('select', function () {
        const attachment = modal.state().get('selection').first().toJSON();
        $(s.attachment, item).val(attachment.id);
        $(s.image, item).css('background-image', 'url(' + attachment.url + ')');
        if (!$(s.title, item).val()) $(s.title, item).val(attachment.title);
        if (!$(s.caption, item).val()) $(s.caption, item).val(attachment.caption);
        if (plugin._debug || plugin._dev) console.log(attachment);
      });
      // }

      modal.open();
    }
  };

  $(function () {
    window[plugin._base] = window[plugin._base] || {};
    window[plugin._base][mainkey] = app;

    $(document).trigger('gEditorial:Module:Loaded', [
      mainkey,
      context,
      app,
      app.init()
    ]);
  });
}(jQuery, gEditorial, 'placard', 'post'));
