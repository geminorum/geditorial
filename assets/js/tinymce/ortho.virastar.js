(function (tinymce, plugin, mainkey, context) {
  if (!window[plugin._base] || !window[plugin._base][mainkey]) return;

  const s = {
    action: [plugin._base, mainkey, context].join('_'),
    domain: plugin._base + '.' + mainkey + '_' + context + '_',
    shortcut: 'ctrl+shift+1', // TODO: maybe get from settings
    // icon: 'icon ' + plugin._base + '-tinymce-icon -icon-' + mainkey + '-' + context
    icon: 'icon ' + plugin._base + '-custom-icon -icon-' + mainkey + '-' + context
  };

  const virastar = window[plugin._base][mainkey].virastar.html;

  function handle (editor) {
    const selected = editor.selection.getContent();

    // NOTE: better not to `.trim()` the results!
    if (selected) {
      editor.selection.setContent(virastar.cleanup(selected));
    } else {
      editor.setContent(virastar.cleanup(editor.getContent()));
    }
  }

  tinymce.PluginManager.add(s.action, function (editor, url) {
    const localized = editor.getLang(s.domain + 'title');
    editor.addShortcut(s.shortcut, localized, s.action);

    editor.addCommand(s.action, function () {
      handle(editor);
    });

    editor.addButton(s.action, {
      title: localized,
      icon: s.icon,
      onclick: function () {
        handle(editor);
      }
    });
  });
})(window.tinymce, gEditorial, 'ortho', 'virastar');
