(function($, p, c, m) {
  'use strict';

  var o = {};

  o.t = {
    text: '#titlewrap input, input#attachment_alt, input#tag-name, #edittag input#name, [data-' + m + '=\'text\']',
    markdown: '[data-' + m + '=\'markdown\']',
    html: 'textarea#excerpt, textarea#attachment_caption, textarea#tag-description, #edittag textarea#description, [data-' + m + '=\'html\']'
  };

  o.i = {
    number: '[data-' + m + '=\'number\']',
    // currency: '[data-' + m + '=\'currency\']',
  };

  o.s = $.extend({}, {
    button_virastar: '<span class="dashicons dashicons-filter">',
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
    qtag_nbsp_title: 'Non-Breaking SPace',
  }, p[m].strings);

  o.b = '<a href="#" class="do-' + m + '" title="' + o.s['button_virastar_title'] + '" tabindex="-1">' + o.s['button_virastar'] + '</span></a>';
  o.w = '<span class="' + m + '-input-wrap"></span>';

  o.o = p[m].virastar || {};

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
    number: function() {
      var $i=$(this);
      try{$i.prop('type','text');}catch(e){}
      $i.change(function() {
        $i.val(o.u.tE($i.val()).replace(/[^\d.-]/g,'').trim());
      });
    },
    // currency: function(){}, // @SEE: https://github.com/habibpour/rial.js
  };

  o.u = {
    pF: function(c) {
      var fn = {};
      c = c.replace(/\<a[^h]*(?=href\=\"[^\"]*\#_(?:ftn|edn|etc)ref([0-9]+)\")[^>]*\>\[([0-9]+)\]\<\/a\>(.*)/g, function(m, p1, p2, p3) {
        fn[p1] = p3.replace(/^\s*./, "").trim();
        return '';
      });

      return c.replace(/\<a[^h]*(?=href\=\"[^\"]*\#_(?:ftn|edn|etc)([0-9]+)\")[^>]*\>(?:\<\w+\>)*\[([0-9]+)\](?:\<\/\w+\>)*\<\/a\>/g, function(m, p1, p2, p3, p4) {
        return '[ref]' + fn[p1].replace(/\r\n|\r|\n/g, " ").trim() + '[/ref]';
      });
    },
    sQ: function(c) {
      return c.replace(/(»)(.+?)(«)/g, '«$2»').replace(/(”)(.+?)(“)/g, '“$2”');
    },
    tP: function(n) {
      var p = '۰'.charCodeAt(0);
      return n.toString().replace(/\d+/g,function (m) {
          return m.split('').map(function (n) {
              return String.fromCharCode(p+parseInt(n))
          }).join('');
      });
    },
    tE: function(n) {
        return n.toString().replace(/[۱۲۳۴۵۶۷۸۹۰]+/g,function (m) {
          return m.split('').map(function (n) {
            return n.charCodeAt(0)%1776;
          }).join('');
      });
    },
    // @REF: http://codepen.io/geminorum/pen/Ndzdqw
    downloadText: function(filename, text) {
      var element = document.createElement('a');
      element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
      element.setAttribute('download', filename);
      element.style.display = 'none';
      document.body.appendChild(element);
      element.click();
      document.body.removeChild(element);
    }
  };

  o.q = {
    nbsp: function(e, c, ed) {
      QTags.insertContent("\n\n"+'&nbsp;'+"\n\n");
    },
    virastar: function(e, c, ed) {
      var s = c.value.substring(c.selectionStart, c.selectionEnd);
      if (s !== '') {
        QTags.insertContent(o.v['html'].cleanup(s));
      } else {
        $(c).val(o.v['html'].cleanup($(c).val()));
      }
    },
    swapquotes: function(e, c, ed) {
      $(c).val(o.u.sQ($(c).val()));
    },
    mswordnotes: function(e, c, ed) {
      $(c).val(o.u.pF($(c).val()));
    },
    download: function(e, c, ed) {
      var filename = 'Untitled';
      if ( $('#title').length && $('#title').val() )
        filename = $('#title').val().trim();
      else if ( $('#tag-name').length && $('#tag-name').val() )
        filename = $('#tag-name').val().trim();
      else if ( $('#name').length && $('#name').val() )
        filename = $('#name').val().trim();
      else if ( $('#post_ID').length )
        filename = 'Untitled-'+$('#post_ID').val();
      o.u.downloadText(filename+'.md', '## '+filename+"\n\n"+$(c).val());
    },
  };

  $(document).ready(function() {

    for (var t in o.t) {

      $(o.t[t]).each(function() {
        $(this).data(m, t)
          .addClass('target-' + m)
          .add($(o.b))
        .wrapAll('<span class="' + m + '-input-wrap ' + m + '-input-' + t + '-wrap"></span>');
      });

      $('a.do-' + m).on('click', function(e) {
        e.preventDefault();
        var t = $(this).closest('.' + m + '-input-wrap').find('.target-' + m);
        t.val(o.v[t.data(m)].cleanup(t.val()));
      });

      $('.target-' + m).on('paste', function() {
        var e = this;
        setTimeout(function() {
          var t = $(e);
          t.val(o.v[t.data(m)].cleanup(t.val()));
        }, 100);
      });
    }

    for (var i in o.i) {
      $(o.i[i]).each(function() {
        o.n[i].call(this);
      });
    }

    if (typeof(QTags) !== 'undefined') {
      for (var b in o.q) {
        QTags.addButton(b, o.s['qtag_' + b], o.q[b], '', '', o.s['qtag_' + b + '_title']);
      }
    }

    // giving back focus to title input
    try{document.post.title.focus();}catch(e){}
  });

  c[m] = o;

  if (p._dev)
    console.log(o);

}(jQuery, gEditorial, gEditorialModules, 'ortho'));
