(function ($, plugin, mainkey, context) {
  const s = {
    before: 'do-' + mainkey + '',
    after: 'hooked-' + mainkey + '',
    trigger: 'select.do-' + mainkey + ', input[type="radio"].do-' + mainkey + '',
    link: 'a[data-' + mainkey + '-target]',
    spinner: 'span[data-' + mainkey + '-loading]',
    modifier: '' + mainkey + '-modifier',
    target: '' + mainkey + '-target'
  };

  const app = {
    init: function () {
      $(s.trigger).each(function () {
        const $instance = $(this);
        const from = $instance.data(s.modifier);
        const name = $instance.prop('name');

        $instance.removeClass(s.before);
        if (!from || !name) return;

        const $spinner = $instance.parents().find(s.spinner);

        $instance.on('change', function (event) {
          let value = $instance.val();
          if (!value || value === '0') value = false;
          $spinner.addClass('is-active');
          $(s.link).each(function () {
            const $link = $(this);
            const to = $link.data(s.target);
            if (to !== from) return;
            const url = $link.prop('href');
            const args = Object.fromEntries([[name, value]]);
            $link.prop('href', wp.url.addQueryArgs(url, args));
          });

          $spinner.removeClass('is-active'); // WTF: must wait for the loop to finish!
        });

        $instance.addClass(s.after);
      });
    }
  };

  $(window).load(function () {
    $(document).on('gEditorial:DynamicSubmit:Hook', function () { app.init(); });
    $(document).trigger('gEditorial:Module:Loaded', [
      mainkey,
      context,
      app,
      app.init()
    ]);
  });
}(jQuery, gEditorial, 'dynamicsubmit', 'all'));
