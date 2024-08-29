(function ($, plugin, module, section) {
  let modal;

  const s = {
    // action: plugin._base + '_' + module,
    // classs: plugin._base + '-' + module
    button: 'input.' + plugin._base + '-' + module + '-uploadbutton'
  };

  const app = {
    strings: $.extend({}, {
      modal_title: 'Choose a Datasheet',
      modal_button: 'Select as Source'
    }, plugin[module].strings || {}),

    config: $.extend({}, {
      mimetypes: ['application/vnd.ms-excel', 'text/csv']
    }, plugin[module].config || {}),

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
          // console.log(attachment);
        });
      }

      modal.open();
    }
  };

  $(function () {
    $(s.button).on('click', function (e) {
      e.preventDefault();
      app.openModal($(this).data('target'));
    });

    $(document).trigger('gEditorial:Module:Loaded', [module, app]);
  });
}(jQuery, gEditorial, 'importer', 'media'));
