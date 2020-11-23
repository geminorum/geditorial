/* global _ */

(function ($, counter) {
  function toPersian (n) {
    const p = '۰'.charCodeAt(0);
    return n.toString().replace(/\d+/g, function (m) {
      return m.split('').map(function (n) {
        return String.fromCharCode(p + parseInt(n));
      }).join('');
    });
  }

  $('textarea.editor-status-counts').each(function () {
    const $content = $(this);
    const $chars = $(this).closest('.-wordcount-wrap').find('.-editor-status-info .char-count');
    const $words = $(this).closest('.-wordcount-wrap').find('.-editor-status-info .word-count');
    let prevChars = 0;
    let prevWords = 0;
    let contentEditor;

    function update () {
      let text, lang;

      if (!contentEditor || contentEditor.isHidden()) {
        text = $content.val();
        lang = $('html').attr('lang');
      } else {
        text = contentEditor.getContent({ format: 'raw' });
        lang = contentEditor.settings.wp_lang_attr;
      }

      const chars = counter.count(text, 'characters_including_spaces');
      const words = counter.count(text, 'words');

      if (chars !== prevChars) {
        $chars.text((lang === 'fa-IR' ? toPersian(chars) : chars));
      }

      if (words !== prevWords) {
        $words.text((lang === 'fa-IR' ? toPersian(words) : words));
      }

      prevChars = chars;
      prevWords = words;
    }

    $(document).on('tinymce-editor-init', function (event, editor) {
      if (editor.id !== $content.attr('id')) return;
      contentEditor = editor;
      editor.on('nodechange keyup', _.debounce(update, 1000));
    });

    $content.on('input keyup', _.debounce(update, 1000));

    update();
  });
})(jQuery, new wp.utils.WordCounter());
