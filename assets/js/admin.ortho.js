/* global QTags, Virastar */

(function ($, p, module) {
  const settings = $.extend({}, {
    virastar_on_paste: false
  }, p[module].settings);

  const types = {
    text: '#titlewrap input, input#attachment_alt, input#tag-name, #edittag input#name, [data-' + module + '=\'text\']',
    markdown: '[data-' + module + '=\'markdown\']',
    html: 'textarea#excerpt:not(.wp-editor-area), textarea#attachment_caption, textarea#tag-description, #edittag textarea#description, [data-' + module + '=\'html\']'
  };

  const inputs = {
    number: '[data-' + module + '=\'number\']'
    // code: '[data-' + module + '=\'code\']',
    // color: '[data-' + module + '=\'color\']',
    // currency: '[data-' + module + '=\'currency\']'
  };

  const strings = $.extend({}, {
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

  const doButton = '<a href="#" class="do-' + module + '" title="' + strings.button_virastar_title + '" tabindex="-1">' + strings.button_virastar + '</a>';
  // const doWrap = '<span class="' + module + '-input-wrap"></span>';

  const options = p[module].virastar || {};

  const virastar = {

    text: new Virastar($.extend({}, options, {
      preserve_HTML: false,
      preserve_URIs: false,
      preserve_frontmatter: false
    })),

    markdown: new Virastar($.extend({}, options, {
      // fix_dashes: false,
      // cleanup_spacing: false,
      // cleanup_begin_and_end: false,
      preserve_frontmatter: false,
      skip_markdown_ordered_lists_numbers_conversion: true
    })),

    html: new Virastar($.extend({}, options, {
      // cleanup_spacing: false,
      preserve_frontmatter: false,
      preserve_brackets: true,
      preserve_braces: true
    }))
  };

  function doFootnotes (content) {
    const footnotes = {};
    content = content.replace(/<a[^h]*(?=href="[^"]*#_(?:ftn|edn|etc)ref([0-9]+)")[^>]*>\[([0-9]+)\]<\/a>(.*)/g, function (m, p1, p2, p3) {
      footnotes[p1] = p3.replace(/^\s*./, '').trim();
      return '';
    });

    return content.replace(/<a[^h]*(?=href="[^"]*#_(?:ftn|edn|etc)([0-9]+)")[^>]*>(?:<\w+>)*\[([0-9]+)\](?:<\/\w+>)*<\/a>/g, function (m, p1, p2, p3, p4) {
      return '[ref]' + footnotes[p1].replace(/\r\n|\r|\n/g, ' ').trim() + '[/ref]';
    });
  }

  function swapQuotes (content) {
    return content
      .replace(/(»)(.+?)(«)/g, '«$2»')
      .replace(/(”)(.+?)(“)/g, '“$2”');
  }

  // function toPersian (n) {
  //   var p = '۰'.charCodeAt(0);
  //   return n.toString().replace(/\d+/g, function (m) {
  //     return m.split('').map(function (n) {
  //       return String.fromCharCode(p + parseInt(n));
  //     }).join('');
  //   });
  // }

  function toEnglish (n) {
    return n.toString().replace(/[۱۲۳۴۵۶۷۸۹۰]+/g, function (m) {
      return m.split('').map(function (n) {
        return n.charCodeAt(0) % 1776;
      }).join('');
    });
  }

  // @REF: http://codepen.io/geminorum/pen/Ndzdqw
  function downloadText (filename, text) {
    const element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
  }

  const inputCallbacks = {

    number: function () {
      const $el = $(this);
      try {
        $el.prop('type', 'text');
      } catch (e) {}
      $el.change(function () {
        $el.val(toEnglish($el.val()).replace(/[^\d.-]/g, '').trim());
      });
    }

    // code: function () {},
    // color: function () {},
    // currency: function () {} // @SEE: https://github.com/habibpour/rial.js
  };

  // map quicktag buttons to targeted editor id
  const quickButtons = {
    nbsp: '',
    virastar: '',
    swapquotes: 'content',
    mswordnotes: 'content',
    download: 'content'
  };

  const quickCallbacks = {

    nbsp: function (e, c, ed) {
      QTags.insertContent('\n\n' + '&nbsp;' + '\n\n');
    },

    virastar: function (e, c, ed) {
      const s = c.value.substring(c.selectionStart, c.selectionEnd);
      if (s !== '') {
        QTags.insertContent(virastar.html.cleanup(s));
      } else {
        $(c).val(virastar.html.cleanup($(c).val()));
      }
    },

    swapquotes: function (e, c, ed) {
      $(c).val(swapQuotes($(c).val()));
    },

    mswordnotes: function (e, c, ed) {
      $(c).val(doFootnotes($(c).val()));
    },

    download: function (e, c, ed) {
      let filename = 'Untitled';
      let metadata = '';

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
        const text = $(this).val();
        if (text) metadata = metadata + $(this).data('meta-title') + ': ' + text + '\n';
      });

      // Frontmatter
      if (metadata) metadata = '---\n' + metadata + '---\n\n';

      downloadText(filename + '.md', metadata + '## ' + filename + '\n' + $(c).val());
    }
  };

  $(function () {
    for (var type in types) { // eslint-disable-line no-var
      $(types[type]).each(function () {
        $(this).data(module, type)
          .addClass('target-' + module)
          .add($(doButton))
          .wrapAll('<span class="' + module + '-input-wrap ' + module + '-input-' + type + '-wrap"></span>');
      });

      $('a.do-' + module).on('click', function (event) {
        event.preventDefault();
        const target = $(this).closest('.' + module + '-input-wrap').find('.target-' + module);
        target.val(virastar[target.data(module)].cleanup(target.val()));
      });

      if (settings.virastar_on_paste) {
        $('.target-' + module).on('paste', function () {
          const el = this;
          setTimeout(function () {
            const target = $(el);
            target.val(virastar[target.data(module)].cleanup(target.val()));
          }, 100);
        });
      }
    }

    for (var input in inputs) { // eslint-disable-line no-var
      $(inputs[input]).each(function () {
        inputCallbacks[input].call(this);
      });
    }

    if (typeof QTags !== 'undefined') {
      for (var button in quickButtons) { // eslint-disable-line no-var
        QTags.addButton(
          button,
          strings['qtag_' + button],
          quickCallbacks[button],
          '',
          '',
          strings['qtag_' + button + '_title'],
          0,
          quickButtons[button]
        );
      }
    }

    // giving back focus to title input
    try {
      document.post.title.focus();
    } catch (e) {}

    $(document).trigger('gEditorialReady', [module, null]);
  });
}(jQuery, gEditorial, 'ortho'));
