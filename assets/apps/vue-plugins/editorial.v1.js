// https://vuejs.org/guide/reusability/plugins.html#writing-a-plugin
export default {
  install: (app, options) => {
    // inject a globally available $translate() method
    // EXAMPLE: `<h2>{{ $translate('greetings.hello') }}</h2>`
    app.config.globalProperties.$translate = (key) => {
      // retrieve a nested property in `options`
      // using `key` as the path
      return key.split('.').reduce((o, i) => {
        // if (o) return o[i];
        return o ? o[i] : '';
      }, options.strings || {});
    };

    app.provide('locale', document.documentElement.getAttribute('lang') || 'en');
    app.provide('rtl', document.documentElement.getAttribute('dir') === 'rtl');
    app.provide('i18n', options.strings || {});
    app.provide('settings', options.settings || {});
    app.provide('fields', options.fields || {});
    app.provide('linked', options.linked || {});
    app.provide('config', options.config || {});
    app.provide('endpoint', options._rest ? options._rest : '/wp/v2/posts'); // `this.endpoint`
  }
};
