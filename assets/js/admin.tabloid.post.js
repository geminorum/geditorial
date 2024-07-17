/* global jQuery, gEditorial */

(function ($, plugin, module, section) {
  const s = {
    // action: plugin._base + '_' + module,
    // classs: plugin._base + '-' + module,
    // headerEnd: '#wpbody-content div.wrap hr.wp-header-end'
    headerStart: '#wpbody-content div.wrap h1.wp-heading-inline'
    // titleAction: 'page-title-action'
  };

  const app = {
    // rtl: $('html').attr('dir') === 'rtl',
    // strings: $.extend({}, {
    //   button_title: 'Import Items',
    //   button_text: 'Import'
    // }, plugin[module].strings || {}),

    appendButton: function (to) {
      // if (!plugin[module].link) return false;
      if (!plugin[module].button) return false;

      // const button = ' <a class="' +
      //   s.titleAction + '" href="' +
      //   plugin[module].link + '" title="' +
      //   this.strings.button_title + '">' +
      //   this.strings.button_text + '</a> ';

      // $(button).insertBefore(to);
      // $(plugin[module].button).insertBefore(to);
      $(plugin[module].button).insertAfter(to);
    }
  };

  $(function () {
    app.appendButton($(s.headerStart));

    $(document).trigger('gEditorialReady', [module, app]);
  });
}(jQuery, gEditorial, 'tabloid', 'post'));
