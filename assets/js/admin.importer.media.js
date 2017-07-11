(function($, p, c, m, s) {
  'use strict';

  var o = {},
    modal;

  o.s = $.extend({}, {
    modal_title: 'Choose a Datasheet',
    modal_button: 'Select as Source',
  }, p[m].strings || {} );

  $(document).ready(function() {

    $('#upload_csv_button').click(function(e) {

      e.preventDefault();

      if ( ! modal ) {

        // https://codex.wordpress.org/Javascript_Reference/wp.media
        modal = wp.media({
          title: o.s['modal_title'],
          button: { text: o.s['modal_button'] },
          library: { type: [ 'application/vnd.ms-excel', 'text/csv' ] },
          multiple: false,
        });

        modal.on('select', function() {
          var attachment = modal.state().get('selection').first().toJSON();
          // $('.wpaparat_thumbnail').attr('src', attachment.url);
          $('#upload_attach_id').val(attachment.id);
          // console.log(attachment);
        });
      }

      modal.open();
    });
  });

  // c[m] = o;

  if (p._dev)
    console.log(o);

}(jQuery, gEditorial, gEditorialModules, 'importer', 'media'));
