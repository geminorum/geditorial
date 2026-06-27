(function ($, plugin, mainkey, context) {
  let modal;

  const s = {
    button: 'input.' + plugin._base + '-' + mainkey + '-uploadbutton'
  };

  const app = {
    strings: $.extend({}, {
      modal_title: 'Choose a Datasheet',
      modal_button: 'Select as Source'
    }, plugin[mainkey].strings || {}),

    config: $.extend({}, {
      mimetypes: ['application/vnd.ms-excel', 'text/csv']
    }, plugin[mainkey].config || {}),

    init: function () {
      $(s.button).on('click', function (e) {
        e.preventDefault();
        app.openModal($(this).data('target'));
      });
    },

    openModal: function (target) {
      if (!modal) {
        // @REF: https://codex.wordpress.org/Javascript_Reference/wp.media
        modal = wp.media({
          title: app.strings.modal_title,
          button: { text: app.strings.modal_button },
          library: { type: app.config.mimetypes },
          multiple: false
        });

        modal.on('select', function () {
          const attachment = modal.state().get('selection').first().toJSON();
          $('input[name=' + target + ']').val(attachment.id);
          if (plugin._debug) console.log(attachment);
        });
      }

      modal.open();
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
}(jQuery, gEditorial, 'importer', 'media'));
