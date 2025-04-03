// https://vuejs.org/guide/reusability/plugins.html#writing-a-plugin
// import { getCurrentInstance } from '../vue';

export default {
  install: (app, options) => {
    // Injects a globally available $translate() method
    // EXAMPLE: `<h2>{{ $translate('greetings.hello') }}</h2>`
    app.config.globalProperties.$translate = (key) => {
      // Retrieves a nested property in `options` using `key` as the path
      return key.split('.').reduce((o, i) => {
        return o ? o[i] : '';
      }, options.strings || {});
    };

    const editorial = {
      rtl: document.documentElement.getAttribute('dir') === 'rtl',
      locale: document.documentElement.getAttribute('lang') || 'en',
      endpoint: options._rest ? options._rest : '/wp/v2/posts',

      // https://stackoverflow.com/questions/75446100/vue-3-get-current-application-instance
      appname: '' // app.appContext.app
    };

    // console.log(this.$);
    console.log(app);

    app.provide('locale', document.documentElement.getAttribute('lang') || 'en'); // DEPRECATED
    app.provide('rtl', document.documentElement.getAttribute('dir') === 'rtl'); // DEPRECATED
    app.provide('endpoint', options._rest ? options._rest : '/wp/v2/posts'); // DEPRECATED

    app.provide('i18n', options.strings || {});
    app.provide('settings', options.settings || {});
    app.provide('fields', options.fields || {});
    app.provide('linked', options.linked || {});
    app.provide('config', options.config || {});

    app.provide('plugin', editorial);
  }
};
