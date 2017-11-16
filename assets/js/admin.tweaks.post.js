/* global jQuery, wp, gEditorial */

jQuery(function ($) {
  var settings = $.extend({}, {
    checklist_tree: false,
    category_search: false,
    excerpt_count: false
  }, gEditorial.tweaks.settings);

  if (settings.checklist_tree) {
    $('[id$="-all"] > ul.categorychecklist').each(function () {
      var $list = $(this);
      var $firstChecked = $list.find(':checkbox:checked').first();

      if (!$firstChecked.length) return;

      var posFirst = $list.find(':checkbox').position().top;
      var posChecked = $firstChecked.position().top;

      $list.closest('.tabs-panel').scrollTop(posChecked - posFirst + 5);
    });
  }

  if (settings.category_search) {
    var $input = $('<input class="category-search" type="search" value="" placeholder="' + gEditorial.tweaks.strings.search_placeholder + '" title="' + gEditorial.tweaks.strings.search_title + '" />');

    $('.inside > div.categorydiv').each(function () {
      // var $tax = $(this);
      var $row = $input.clone(true);

      $row.prependTo(this)
        .bind('keyup', function (e) {
          var val = $(this).val();
          var li = $(this).parent().find('ul.categorychecklist li');
          var list = $(this).parent().find('ul.categorychecklist');

          li.hide();
          var containingLabels = list.find("label:contains('" + val + "')");
          containingLabels.closest('li').find('li').addBack().show();
          containingLabels.parents('ul.categorychecklist li').show();
        })
        .show();
    });
  }

  // FIXME: https://github.com/sheabunge/visual-term-description-editor/blob/master/js/wordcount.js
  // FIXME: move this to Writing module
  if (settings.excerpt_count) {
    if (wp.utils) {
      // var counter = new wp.utils.WordCounter();

      $('div.geditorial-wordcount-wrap').each(function () {
        var $content = $(this).find('textarea');
        var $count = $(this).find('.geditorial-wordcount .-chars');
        var prevCount = 0;
        // var contentEditor;

        $content.bind('keyup', function (e) {
          var text = $(this).val();
          // var count = counter.count(text);
          // var count = text.split(" ").join("").length;
          var count = text.trim().length;

          if (count !== prevCount) {
            $count.text(count);
          }

          prevCount = count;
        });

        $(this).find('.geditorial-wordcount').show();

        // FIXME: check for max/min

        // FIXME: use debounce

        // function update() {
        //   var text = $content.val(),
        //     count = counter.count( text );
        //
        //   if ( count !== prevCount ) {
        //     $count.text( count );
        //   }
        //
        //   prevCount = count;
        // }
        //
        // $( document ).on( 'tinymce-editor-init', function( event, editor ) {
        //   if ( editor.id !== 'content' ) {
        //     return;
        //   }
        //
        //   contentEditor = editor;
        //
        //   editor.on( 'nodechange keyup', _.debounce( update, 1000 ) );
        // } );
        //
        // $content.on( 'input keyup', _.debounce( update, 1000 ) );
        //
        // update();
      });
    }
  }
});
