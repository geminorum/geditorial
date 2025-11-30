(function ($, plugin, mainkey, section) {
  const s = {
    inputs: {
      name: 'input#tag-name',
      slug: 'input#tag-slug',
      desc: 'input#tag-description'
    },
    selects: {
      parent: 'select#parent'
    }
  };

  const u = {
    query: (name, url) => {
      if (!url) url = window.location.href;
      name = name.replace(/[[\]]/g, '\\$&');
      const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
      const results = regex.exec(url);
      if (!results) return null;
      if (!results[2]) return '';
      return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }
  };

  const app = {
    fillByQuery: () => {
      const inputs = $.extend({}, s.inputs, plugin[mainkey].settings.inputs || {});

      $.each(inputs, (name, selector) => {
        $(selector).each(function () {
          const query = u.query(name);
          if (!query) return;
          $(this).val(query);
        });
      });

      const selects = $.extend({}, s.selects, plugin[mainkey].settings.selects || {});

      $.each(selects, (name, selector) => {
        $(selector).each(function () {
          const query = u.query(name);
          if (!query) return;
          $(this).val(query);
          $(this).find('option:selected').attr('selected', false);
          $(this).find('option[value="' + query + '"]').attr('selected', true);
        });
      });
    }
  };

  $(window).load(function () {
    app.fillByQuery();

    $(document).on('gEditorial:ColorBox:Hook', function () { app.hook(); });
    $(document).trigger('gEditorial:Module:Loaded', [mainkey, app]);
  });
}(jQuery, gEditorial, 'adminscreen', 'edit-tags'));
