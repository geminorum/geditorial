(function ($, plugin, module) {
  let modal;

  const s = {
    action: plugin._base + '_' + module,
    classs: plugin._base + '-' + module
  };

  const app = {

    strings: $.extend({}, {
      modal_title: 'Choose an Image',
      modal_button: 'Set as image'
    }, plugin[module].strings || {}),

    modalImage: function (element, event) {
      event.preventDefault();

      if (!modal) {
        modal = wp.media({
          title: this.strings.modal_title,
          button: { text: this.strings.modal_button },
          library: { type: 'image' },
          multiple: false
        });

        modal.on('select', function () {
          const image = modal.state().get('selection').first().toJSON();

          if (image !== '') {
            if (!$(element).hasClass('-quick')) {
              $('#' + s.classs + '-image-id').val(image.id);
              $('#' + s.classs + '-image-img').attr('src', image.url).show();
              $('a.-remove').show();
            } else {
              $('a.-remove', '.inline-edit-row').show();
              $(':input[name="term-image"]', '.inline-edit-row').val(image.id);
              $('img.-img', '.inline-edit-row').attr('src', image.url).show();
            }
          }
        });
      }

      modal.open();
    },

    resetImage: function (element, event) {
      event.preventDefault();

      if (!$(element).hasClass('-quick')) {
        $('#' + s.classs + '-image-id').val(0);
        $('#' + s.classs + '-image-img').attr('src', '').hide();
        $('a.-remove').hide();
      } else {
        $('a.-remove', '.inline-edit-row').hide();
        $(':input[name="term-image"]', '.inline-edit-row').val('');
        $('img.-img', '.inline-edit-row').attr('src', '').hide();
      }
    },

    inlineImage: function (tag, event) {
      const image = $('td.geditorial-terms-image img', '#' + tag);
      const src = image.attr('src');
      const id = image.data('attachment');

      if (typeof id !== 'undefined') {
        $('a.-remove', '.inline-edit-row').show();
        $(':input[name="term-image"]', '.inline-edit-row').val(id);
        $('img.-img', '.inline-edit-row').attr('src', src).show();
      } else {
        $('a.-remove', '.inline-edit-row').hide();
        $(':input[name="term-image"]', '.inline-edit-row').val('');
        $('img.-img', '.inline-edit-row').attr('src', '').hide();
      }
    },

    inlineColor: function (tag, event) {
      const value = $('td.' + s.classs + '-color i', '#' + tag).attr('data-color');
      $(':input[name="term-color"]', '.inline-edit-row').val(value);
    },

    inlineOrder: function (tag, event) {
      const value = $('td.' + s.classs + '-order span.order', '#' + tag).attr('data-order');
      $(':input[name="term-order"]', '.inline-edit-row').val(value);
    },

    inlineText: function (field, tag, event) {
      const value = $('td.' + s.classs + '-' + field + ' span.field-' + field, '#' + tag).attr('data-' + field);
      $(':input[name="term-' + field + '"]', '.inline-edit-row').val(value);
    },

    inlineCode: function (field, tag, event) {
      const value = $('td.' + s.classs + '-' + field + ' code.field-' + field, '#' + tag).attr('data-' + field);
      $(':input[name="term-' + field + '"]', '.inline-edit-row').val(value);
    },

    inlineSelect: function (field, tag, event) {
      const value = $('td.' + s.classs + '-' + field + ' span.field-' + field, '#' + tag).attr('data-' + field);
      const $el = $(':input[name="term-' + field + '"]', '.inline-edit-row');

      $el.find('option:selected').attr('selected', false);
      $el.find('option[value="' + value + '"]').attr('selected', true);
    }
  };

  $(function () {
    $('#addtag, #edittag, #the-list')
      .on('click', '.-modal', function (event) {
        app.modalImage(this, event);
      })
      .on('click', '.-remove', function (event) {
        app.resetImage(this, event);
      });

    $('#the-list').on('click', '.editinline', function (event) {
      const tag = $(this).parents('tr').attr('id');
      app.inlineImage(tag, event);
      app.inlineColor(tag, event);
      app.inlineOrder(tag, event);
      app.inlineText('tagline', tag, event);
      app.inlineText('contact', tag, event);
      app.inlineSelect('author', tag, event);
      app.inlineSelect('role', tag, event);
      app.inlineSelect('posttype', tag, event);
      app.inlineSelect('arrow', tag, event);
      app.inlineText('label', tag, event);
      app.inlineCode('code', tag, event);
      app.inlineText('barcode', tag, event);
    });

    // reset the form on submit
    // since the form is never *actually* submitted (but instead serialized
    // on #submit being clicked), we'll have to do the same
    // @SEE: https://core.trac.wordpress.org/ticket/36956
    $(document).on('term-added', function (event) {
      app.resetImage($('#addtag #submit'), event);
    });

    if (typeof $.wp === 'object' && typeof $.wp.wpColorPicker === 'function') {
      $('#' + s.classs + '-color-id').wpColorPicker();
    }

    $(document).trigger('gEditorialReady', [module, app]);
  });
}(jQuery, gEditorial, 'terms'));
