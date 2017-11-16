/* global jQuery, wp, gEditorial, gEditorialModules */

(function ($, p, c, m) {
  var modal;

  var o = {
    action: p._base + '_' + m,
    classs: p._base + '-' + m,

    strings: $.extend({}, {
      modal_title: 'Choose an Image',
      modal_button: 'Set as image'
    }, p[m].strings || {}),

    modalImage: function (element, event) {
      event.preventDefault();

      if (!modal) {
        modal = wp.media({
          title: this.strings['modal_title'],
          button: { text: this.strings['modal_button'] },
          library: { type: 'image' },
          multiple: false
        });

        modal.on('select', function () {
          var image = modal.state().get('selection').first().toJSON();

          if (image !== '') {
            if (!$(element).hasClass('-quick')) {
              $('#' + o.classs + '-image-id').val(image.id);
              $('#' + o.classs + '-image-img').attr('src', image.url).show();
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
        $('#' + o.classs + '-image-id').val(0);
        $('#' + o.classs + '-image-img').attr('src', '').hide();
        $('a.-remove').hide();
      } else {
        $('a.-remove', '.inline-edit-row').hide();
        $(':input[name="term-image"]', '.inline-edit-row').val('');
        $('img.-img', '.inline-edit-row').attr('src', '').hide();
      }
    },

    inlineImage: function (tag, event) {
      var image = $('td.geditorial-terms-image img', '#' + tag);
      var src = image.attr('src');
      var id = image.data('attachment');

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
      var color = $('td.geditorial-terms-color i', '#' + tag).attr('data-color');
      $(':input[name="term-color"]', '.inline-edit-row').val(color);
    },

    inlineOrder: function (tag, event) {
      var order = $('td.geditorial-terms-order span.order', '#' + tag).attr('data-order');
      $(':input[name="term-order"]', '.inline-edit-row').val(order);
    },

    inlineAuthor: function (tag, event) {
      var author = $('td.geditorial-terms-author span.author', '#' + tag).attr('data-author');
      var $select = $(':input[name="term-author"]', '.inline-edit-row');

      $select.find('option:selected').attr('selected', false);
      $select.find('option[value="' + author + '"]').attr('selected', true);
    }
  };

  $(function () {
    $('#addtag, #edittag, #the-list')
      .on('click', '.-modal', function (event) {
        o.modalImage(this, event);
      })
      .on('click', '.-remove', function (event) {
        o.resetImage(this, event);
      });

    $('#the-list').on('click', 'a.editinline', function (event) {
      var tag = $(this).parents('tr').attr('id');
      o.inlineImage(tag, event);
      o.inlineColor(tag, event);
      o.inlineOrder(tag, event);
      o.inlineAuthor(tag, event);
    });

    // reset the form on submit
    // since the form is never *actually* submitted (but instead serialized
    // on #submit being clicked), we'll have to do the same
    // @SEE: https://core.trac.wordpress.org/ticket/36956
    $(document).on('term-added', function (event) {
      o.resetImage($('#addtag #submit'), event);
    });

    if (typeof $.wp === 'object' && typeof $.wp.wpColorPicker === 'function') {
      $('#' + o.classs + '-color-id').wpColorPicker();
    }
  });

  // c[m] = o;
  //
  // if (p._dev)
  //   console.log(o);
}(jQuery, gEditorial, gEditorialModules, 'terms'));
