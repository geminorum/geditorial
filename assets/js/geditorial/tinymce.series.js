(function() {
    tinymce.create('tinymce.plugins.gE_Series', {
        init: function(editor, url) {
			editor.addButton('ge_series', {
				title:   editor.getLang('geditorial.ge_series-title'),
				icon:    'icon geditorial-tinymce-icon icon-ge_series',
				onclick: function() {
					editor.selection.setContent('[series]');
                }
            });
        },
        createControl: function(n, cm) {
            return null;
        },
        getInfo: function() {
            return {
				longname:  "gEditorial Series",
				author:    'geminorum',
				authorurl: 'http://geminorum.ir/',
				infourl:   'http://github.com/geminorum/geditorial/',
				version:   "0.2"
            };
        }
    });
    tinymce.PluginManager.add('ge_series', tinymce.plugins.gE_Series);
})();
