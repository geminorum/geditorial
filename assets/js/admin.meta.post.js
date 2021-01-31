/* global tinymce */

jQuery(function ($) {
  $("[data-meta-type='title_before']").each(function () {
    $(this).insertBefore('#titlewrap').show();
  });

  $("[data-meta-type='title_after']").each(function () {
    $(this).insertAfter('#titlewrap').show();
  });

  $("[data-meta-type='postbox_legacy']").each(function () {
    $(this).parents('div.postbox').appendTo('#titlediv');
  });

  // wait till post.js binds
  $(window).on('load', function () {
    if ($("[data-meta-type='title_after']").length) {
      $('#title').unbind('keydown.editor-focus');

      // copy from post.js
      // This code is meant to allow tabbing from Title to Post content.
      $("[data-meta-type='title_after']").on('keydown.editor-focus', function (event) {
        let editor;
        const $textarea = $('#content');

        if (event.keyCode === 9 && !event.ctrlKey && !event.altKey && !event.shiftKey) {
          editor = typeof tinymce !== 'undefined' && tinymce.get('content');

          if (editor && !editor.isHidden()) {
            editor.trigger('focus');
          } else if ($textarea.length) {
            $textarea.trigger('focus');
          } else {
            return;
          }

          event.preventDefault();
        }
      });
    }
  });
});
