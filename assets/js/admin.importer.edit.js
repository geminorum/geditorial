/* global jQuery, gEditorial */

(function ($, plugin, module, section) {
  const s = {
    // action: plugin._base + '_' + module,
    // classs: plugin._base + '-' + module,
    headerEnd: '#wpbody-content div.wrap hr.wp-header-end',
    titleAction: 'page-title-action'
  };

  const app = {
    rtl: $('html').attr('dir') === 'rtl',
    strings: $.extend({}, {
      button_title: 'Import Items',
      button_text: 'Import'
    }, plugin[module].strings || {}),

    appendButton: function (to) {
      if (!plugin[module].link) return false;

      const button = ' <a class="' +
        s.titleAction + '" href="' +
        plugin[module].link + '" title="' +
        this.strings.button_title + '">' +
        this.strings.button_text + '</a> ';

      $(button).insertBefore(to);
    }
  };

  $(function () {
    app.appendButton($(s.headerEnd));

    $(document).trigger('gEditorialReady', [module, app]);
  });
}(jQuery, gEditorial, 'importer', 'edit'));
