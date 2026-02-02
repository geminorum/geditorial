(function ($, plugin, module) {
  if (typeof plugin === 'undefined') return;

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

    customs: plugin[module].customs || [],

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
      if (typeof value !== 'undefined') $(':input[name="term-color"]', '.inline-edit-row').val(value);
    },

    inlineOrder: function (tag, event) {
      const value = $('td.' + s.classs + '-order span.order', '#' + tag).attr('data-order');
      $(':input[name="term-order"]', '.inline-edit-row').val(value);
    },

    inlineText: function (field, tag, event) {
      const value = $('td.' + s.classs + '-' + field + ' span.field-' + field, '#' + tag).attr('data-' + field);
      $(':input[name="term-' + field + '"]', '.inline-edit-row').val(value);
    },

    inlineNumber: function (field, tag, event) {
      const value = $('td.' + s.classs + '-' + field + ' span.field-' + field, '#' + tag).attr('data-' + field);
      $(':input[name="term-' + field + '"]', '.inline-edit-row').val(value);
    },

    inlineDate: function (field, tag, event) {
      const value = $('td.' + s.classs + '-' + field + ' span.field-' + field, '#' + tag).attr('data-' + field);
      $(':input[name="term-' + field + '"]', '.inline-edit-row').val(value);
    },

    // NOTE: handles `code` tags
    inlineCode: function (field, tag, event) {
      const value = $('td.' + s.classs + '-' + field + ' code.field-' + field, '#' + tag).attr('data-' + field);
      $(':input[name="term-' + field + '"]', '.inline-edit-row').val(value);
    },

    inlineURL: function (field, tag, event) {
      const value = $('td.' + s.classs + '-' + field + ' span.field-' + field, '#' + tag).attr('data-' + field);
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

    $('#the-list').on('click', '.editinline', function (e) {
      const tag = $(this).parents('tr').attr('id');
      const event = e;

      app.inlineNumber('parent', tag, event);
      app.inlineOrder(tag, event);
      app.inlineText('plural', tag, event);
      // app.inlineText('singular', tag, event); // TODO
      app.inlineText('overwrite', tag, event);
      app.inlineText('fullname', tag, event);
      app.inlineText('tagline', tag, event);
      app.inlineText('subtitle', tag, event);
      app.inlineText('contact', tag, event); // TODO convert to code
      app.inlineText('venue', tag, event);
      app.inlineImage(tag, event);
      app.inlineSelect('user', tag, event);
      app.inlineSelect('author', tag, event);
      app.inlineColor(tag, event);
      app.inlineSelect('role', tag, event);
      // TODO: multi-select: `roles`
      app.inlineSelect('posttype', tag, event);
      // TODO: multi-select: `posttypes`
      app.inlineSelect('arrow', tag, event);
      app.inlineText('label', tag, event);
      app.inlineCode('code', tag, event);
      app.inlineText('barcode', tag, event);
      app.inlineCode('latlng', tag, event);
      app.inlineDate('date', tag, event);
      app.inlineDate('datetime', tag, event);
      app.inlineDate('datestart', tag, event);
      app.inlineDate('dateend', tag, event);
      app.inlineDate('born', tag, event);
      app.inlineDate('dead', tag, event);
      app.inlineDate('establish', tag, event);
      app.inlineDate('abolish', tag, event);
      // 'distance', // TODO
      // 'duration', // TODO
      // 'area',     // TODO
      app.inlineNumber('days', tag, event);
      app.inlineNumber('hours', tag, event);
      app.inlineText('period', tag, event);
      app.inlineNumber('amount', tag, event);
      app.inlineNumber('unit', tag, event);
      app.inlineNumber('min', tag, event);
      app.inlineNumber('max', tag, event);
      app.inlineSelect('viewable', tag, event);
      app.inlineURL('source', tag, event);
      app.inlineURL('embed', tag, event);
      app.inlineURL('url', tag, event);
      // 'identity',  // TODO
      // 'plate',     // TODO

      // FIXME: WTF: data attr cannot contain underscores!
      // @SEE: https://www.sitepoint.com/how-why-use-html5-custom-data-attributes/
      app.customs.forEach(function (custom) {
        app.inlineText(custom, tag, event); // only text supported for customs
      });
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
