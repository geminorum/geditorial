/* global QTags, persianTools */

(function ($, p, module, s) {
  if (typeof p === 'undefined') return;

  const o = {};

  o.s = $.extend({}, {
    qtag_orthography: 'Orthography!',
    qtag_orthography_title: 'Apply Orthography!',
    qtag_tostandard: 'Standard!',
    qtag_tostandard_title: 'To Standard!'
  // }, p[module].strings || {} );
  }, {});

  o.u = {
    pF: function (c) {
      const fn = {};
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
      const p = '۰'.charCodeAt(0);
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
      const element = document.createElement('a');
      element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
      element.setAttribute('download', filename);
      element.style.display = 'none';
      document.body.appendChild(element);
      element.click();
      document.body.removeChild(element);
    }
  };

  o.q = {
    orthography: function (e, c, ed) {
      const s = c.value.substring(c.selectionStart, c.selectionEnd);
      if (s !== '') {
        QTags.insertContent(persianTools.applyOrthography(s));
      } else {
        $(c).val(persianTools.applyOrthography($(c).val()));
      }
    },
    tostandard: function (e, c, ed) {
      const s = c.value.substring(c.selectionStart, c.selectionEnd);
      if (s !== '') {
        QTags.insertContent(persianTools.toStandardPersianCharacters(s));
      } else {
        $(c).val(persianTools.toStandardPersianCharacters($(c).val()));
      }
    }
  };

  $(function () {
    if (typeof QTags !== 'undefined') {
      for (var b in o.q) { // eslint-disable-line no-var
        QTags.addButton(b, o.s['qtag_' + b], o.q[b], '', '', o.s['qtag_' + b + '_title']);
      }
    }

    $(document).trigger('gEditorialReady', [module, o, s]);
  });
}(jQuery, gEditorial, 'ortho', 'persiantools'));
