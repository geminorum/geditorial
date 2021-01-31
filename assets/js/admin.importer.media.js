(function ($, plugin, module, section) {
  let modal;

  const app = {
    strings: $.extend({}, {
      modal_title: 'Choose a Datasheet',
      modal_button: 'Select as Source'
    }, plugin[module].strings || {}),

    init: function () {
      if (!modal) {
        // @REF: https://codex.wordpress.org/Javascript_Reference/wp.media
        modal = wp.media({
          title: app.strings.modal_title,
          button: { text: app.strings.modal_button },
          library: { type: ['application/vnd.ms-excel', 'text/csv'] },
          multiple: false
        });

        modal.on('select', function () {
          const attachment = modal.state().get('selection').first().toJSON();
          // $('.wpaparat_thumbnail').attr('src', attachment.url);
          $('#upload_attach_id').val(attachment.id);
          // console.log(attachment);
        });
      }
    }
  };

  $(function () {
    $('#upload_csv_button').on('click', function (e) {
      e.preventDefault();
      app.init();
      modal.open();
    });

    $(document).trigger('gEditorialReady', [module, app]);
  });
}(jQuery, gEditorial, 'importer', 'media'));
