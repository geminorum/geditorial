(function ($, plugin, module, section) {
  const s = {
    // action: plugin._base + '_' + module,
    // classs: plugin._base + '-' + module,
    select: 'select.' + plugin._base + '-' + module + '-' + section
  };

  function toPersian (n) {
    const p = '۰'.charCodeAt(0);
    return n.toString().replace(/\d+/g, function (m) {
      return m.split('').map(function (n) {
        return String.fromCharCode(p + parseInt(n));
      }).join('');
    });
  }

  // @REF: https://stackoverflow.com/a/31007976
  function sprintf (theString, argumentArray) {
    const regex = /%s/;
    const _r = function (p, c) { return p.replace(regex, c); };
    return argumentArray.reduce(_r, theString);
  }

  const app = {
    // rtl: false,
    // lang: 'en',

    strings: $.extend({}, {
      placeholder: 'Select an item &hellip;',
      loadingmore: 'Loading more results &hellip;',
      searching: 'Searching &hellip;',
      noresults: 'No results found',
      removeallitems: 'Remove all items',
      removeitem: 'Remove item',
      search: 'Search',
      errorloading: 'The results could not be loaded.',
      inputtooshort: 'Please enter %s or more characters',
      inputtoolong: 'Please delete %s character(s)',
      maximumselected: 'You can only select %s item(s)'
    }, plugin[module].strings || {}),

    num: function (number, lang) {
      return lang === 'fa-IR' ? toPersian(number) : number;
    },
    str: function (el, key) {
      return el.data(module + '-' + key) || app.strings[key];
    },

    // @REF: https://select2.org/configuration/options-api
    init: function (el) {
      el.select2({
        dir: $('html').attr('dir'),
        width: '100%', // 'element'
        theme: el.data('theme') || plugin._base,
        allowClear: true,
        minimumInputLength: el.data('query-minimum') || 5,
        placeholder: { id: '0', text: app.str(el, 'placeholder') },
        language: {
          errorLoading: function () { return app.str(el, 'errorloading'); },
          inputTooLong: function (e) { return sprintf(app.str(el, 'inputtoolong'), [app.num((e.input.length - e.maximum), app.lang)]); },
          inputTooShort: function (e) { return sprintf(app.str(el, 'inputtooshort'), [app.num((e.minimum - e.input.length), app.lang)]); },
          loadingMore: function () { return app.str(el, 'loadingmore'); },
          maximumSelected: function (e) { return sprintf(app.str(el, 'maximumselected'), [app.num(e.maximum, app.lang)]); },
          noResults: function () { return app.str(el, 'noresults'); },
          searching: function () { return app.str(el, 'searching'); },
          removeAllItems: function () { return app.str(el, 'removeallitems'); },
          removeItem: function () { return app.str(el, 'removeitem'); },
          search: function () { return app.str(el, 'search'); }
        },
        ajax: {
          // @REF: https://select2.org/data-sources/ajax
          url: plugin._restBase + plugin[module]._rest + '/query',
          dataType: 'json',
          headers: {
            'X-WP-Nonce': plugin._restNonce
          },
          delay: 500,
          cache: true,
          data: function (params) {
            return {
              context: el.data('query-context') || section,
              search: params.term,
              target: el.data('query-target'),
              posttype: el.data('query-posttype'),
              taxonomy: el.data('query-taxonomy'),
              exclude: el.data('query-exclude'),
              role: el.data('query-role'),
              page: params.page || 1
            };
          }
        }
      });
    }
  };

  $(function () {
    // app.rtl = $('html').attr('dir') === 'rtl';
    app.lang = $('html').attr('lang');

    $(s.select).each(function () {
      app.init($(this));
    });

    // $(document.body).on('focus', '.ptitle,select',
    //   function (event) {
    //     if (event.target.nodeName === 'SELECT') {
    //       // fire for this element only
    //       $(this).select2({ width: 'element' });
    //     } else {
    //       // fire again, but only for selects that haven't yet been select2'd
    //       $('select:visible').not('.select2-offscreen').select2({ width: 'element' });
    //     }
    //   }
    // );

    $(document).trigger('gEditorialReady', [module, app]);
  });
}(jQuery, gEditorial, 'searchselect', 'select2'));
