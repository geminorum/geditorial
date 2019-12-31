/* global QTags, Virastar */

(function ($, p, module) {
  var o = {};

  var app = {

    settings: $.extend({}, {
      virastar_on_paste: false
    }, p[module].settings),

    types: {
      text: '#titlewrap input, input#attachment_alt, input#tag-name, #edittag input#name, [data-' + module + '=\'text\']',
      markdown: '[data-' + module + '=\'markdown\']',
      html: 'textarea#excerpt:not(.wp-editor-area), textarea#attachment_caption, textarea#tag-description, #edittag textarea#description, [data-' + module + '=\'html\']'
    },

    inputs: {
      number: '[data-' + module + '=\'number\']',
      code: '[data-' + module + '=\'code\']',
      color: '[data-' + module + '=\'color\']'
      // currency: '[data-' + module + '=\'currency\']',
    }
  };

  o.s = $.extend({}, {
    button_virastar: '<span class="dashicons dashicons-text"></span>',
    button_virastar_title: 'Apply Virastar!',
    qtag_virastar: 'Virastar!',
    qtag_virastar_title: 'Apply Virastar!',
    qtag_swapquotes: 'Swap Quotes',
    qtag_swapquotes_title: 'Swap Not-Correct Quotes',
    qtag_mswordnotes: 'Word Footnotes',
    qtag_mswordnotes_title: 'MS Word Footnotes to WordPress [ref]',
    qtag_download: 'Download',
    qtag_download_title: 'Download text as markdown',
    qtag_nbsp: 'nbsp',
    qtag_nbsp_title: 'Non-Breaking SPace'
  }, p[module].strings);

  o.b = '<a href="#" class="do-' + module + '" title="' + o.s.button_virastar_title + '" tabindex="-1">' + o.s.button_virastar + '</a>';
  o.w = '<span class="' + module + '-input-wrap"></span>';

  o.o = p[module].virastar || {};

  o.v = {
    text: new Virastar($.extend({}, o.o, {
      preserve_HTML: false,
      preserve_URIs: false,
      preserve_brackets: false,
      preserve_braces: false
    })),
    markdown: new Virastar($.extend({}, o.o, {
      fix_dashes: false,
      cleanup_spacing: false,
      cleanup_begin_and_end: false,
      skip_markdown_ordered_lists_numbers_conversion: false
    })),
    html: new Virastar($.extend({}, o.o, {
      cleanup_spacing: false
    }))
  };

  o.n = {
    number: function () {
      var $i = $(this);
      try {
        $i.prop('type', 'text');
      } catch (e) {}
      $i.change(function () {
        $i.val(o.u.tE($i.val()).replace(/[^\d.-]/g, '').trim());
      });
    },
    code: function () {},
    color: function () {}
    // currency: function(){}, // @SEE: https://github.com/habibpour/rial.js
  };

  o.u = {
    pF: function (c) {
      var fn = {};
      c = c.replace(/<a[^h]*(?=href="[^"]*#_(?:ftn|edn|etc)ref([0-9]+)")[^>]*>\[([0-9]+)\]<\/a>(.*)/g, function (m, p1, p2, p3) {
        fn[p1] = p3.replace(/^\s*./, '').trim();
        return '';
      });

      return c.replace(/<a[^h]*(?=href="[^"]*#_(?:ftn|edn|etc)([0-9]+)")[^>]*>(?:<\w+>)*\[([0-9]+)\](?:<\/\w+>)*<\/a>/g, function (m, p1, p2, p3, p4) {
        return '[ref]' + fn[p1].replace(/\r\n|\r|\n/g, ' ').trim() + '[/ref]';
      });
    },
    sQ: function (c) {
      return c.replace(/(»)(.+?)(«)/g, '«$2»').replace(/(”)(.+?)(“)/g, '“$2”');
    },
    tP: function (n) {
      var p = '۰'.charCodeAt(0);
      return n.toString().replace(/\d+/g, function (m) {
        return m.split('').map(function (n) {
          return String.fromCharCode(p + parseInt(n));
        }).join('');
      });
    },
    tE: function (n) {
      return n.toString().replace(/[۱۲۳۴۵۶۷۸۹۰]+/g, function (m) {
        return m.split('').map(function (n) {
          return n.charCodeAt(0) % 1776;
        }).join('');
      });
    },
    // @REF: http://codepen.io/geminorum/pen/Ndzdqw
    downloadText: function (filename, text) {
      var element = document.createElement('a');
      element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
      element.setAttribute('download', filename);
      element.style.display = 'none';
      document.body.appendChild(element);
      element.click();
      document.body.removeChild(element);
    }
  };

  // map quicktag buttons to targeted editor id
  o.q = {
    nbsp: '',
    virastar: '',
    swapquotes: 'content',
    mswordnotes: 'content',
    download: 'content'
  };

  o.qu = {
    nbsp: function (e, c, ed) {
      QTags.insertContent('\n\n' + '&nbsp;' + '\n\n');
    },
    virastar: function (e, c, ed) {
      var s = c.value.substring(c.selectionStart, c.selectionEnd);
      if (s !== '') {
        QTags.insertContent(o.v.html.cleanup(s));
      } else {
        $(c).val(o.v.html.cleanup($(c).val()));
      }
    },
    swapquotes: function (e, c, ed) {
      $(c).val(o.u.sQ($(c).val()));
    },
    mswordnotes: function (e, c, ed) {
      $(c).val(o.u.pF($(c).val()));
    },
    download: function (e, c, ed) {
      var filename = 'Untitled';
      var metadata = '';

      if ($('#title').length && $('#title').val()) {
        filename = $('#title').val().trim();
      } else if ($('#tag-name').length && $('#tag-name').val()) {
        filename = $('#tag-name').val().trim();
      } else if ($('#name').length && $('#name').val()) {
        filename = $('#name').val().trim();
      } else if ($('#post_ID').length) {
        filename = 'Untitled-' + $('#post_ID').val();
      }

      $('input[data-meta-title]').each(function (i) {
        var text = $(this).val();
        if (text) metadata = metadata + $(this).data('meta-title') + ': ' + text + '\n';
      });

      // Front Matter
      if (metadata) metadata = '---\n' + metadata + '---\n\n';

      o.u.downloadText(filename + '.md', metadata + '## ' + filename + '\n' + $(c).val());
    }
  };

  $(function () {
    for (var t in app.types) {
      $(app.types[t]).each(function () {
        $(this).data(module, t)
          .addClass('target-' + module)
          .add($(o.b))
          .wrapAll('<span class="' + module + '-input-wrap ' + module + '-input-' + t + '-wrap"></span>');
      });

      $('a.do-' + module).on('click', function (e) {
        e.preventDefault();
        var t = $(this).closest('.' + module + '-input-wrap').find('.target-' + module);
        t.val(o.v[t.data(module)].cleanup(t.val()));
      });

      if (app.settings.virastar_on_paste) {
        $('.target-' + module).on('paste', function () {
          var e = this;
          setTimeout(function () {
            var t = $(e);
            t.val(o.v[t.data(module)].cleanup(t.val()));
          }, 100);
        });
      }
    }

    for (var i in app.inputs) {
      $(app.inputs[i]).each(function () {
        o.n[i].call(this);
      });
    }

    if (typeof QTags !== 'undefined') {
      for (var b in o.q) {
        QTags.addButton(b, o.s['qtag_' + b], o.qu[b], '', '', o.s['qtag_' + b + '_title'], 0, o.q[b]);
      }
    }

    // giving back focus to title input
    try {
      document.post.title.focus();
    } catch (e) {}

    $(document).trigger('gEditorialReady', [module, o]);
  });
}(jQuery, gEditorial, 'ortho'));
