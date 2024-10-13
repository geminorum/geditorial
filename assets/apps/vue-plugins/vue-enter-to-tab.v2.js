// @SOURCE: https://github.com/ajomuch92/vue-enter-to-tab
// EXAMPLE: https://codesandbox.io/s/hardcore-ritchie-3g2my
// NOTE: changed to support Vue3

// @SEE: https://github.com/ajomuch92/vue-enter-to-tab/issues/4
// @SEE: https://github.com/l3d00m/vue3-enter-to-tab
// https://www.npmjs.com/package/vue3-enter-to-tab

const ENTER_CODE = 13;

export const EnterToTabMixin = {
  mounted () {
    this.$el.addEventListener('keydown', this.$keyDownEventHandler);
  },
  beforeDestroy () {
    this.$el.removeEventListener('keydown', this.$keyDownEventHandler);
  },
  methods: {
    $keyDownEventHandler (e) {
      const { target, ctrlKey, keyCode } = e;
      if (keyCode === ENTER_CODE &&
        !ctrlKey &&
        target &&
        target.tagName.toLowerCase() !== 'textarea' &&
        this.$isEnterToTabEnabled &&
        !target.preventEnterTab) {
        e.preventDefault();
        const allElementsQuery = this.$el.querySelectorAll('input, button, a, textarea, select, audio, video, [contenteditable]');
        const allElements = [...allElementsQuery].filter(r => !r.disabled && !r.hidden && r.offsetParent && !r.readOnly);
        const currentIndex = [...allElements].indexOf(target);
        const targetIndex = (currentIndex + 1) % allElements.length;
        allElements[targetIndex].focus();
      }
    }
  }
};

export default {
  install: (app, options) => {
    app.config.globalProperties.$isEnterToTabEnabled = options || true;

    app.config.globalProperties.$disableEnterToTab = () => {
      app.config.globalProperties.$isEnterToTabEnabled = false;
    };

    app.config.globalProperties.$enabledEnterToTab = () => {
      app.config.globalProperties.$isEnterToTabEnabled = true;
    };

    app.config.globalProperties.$disableEnterToTab = () => {
      app.config.globalProperties.$isEnterToTabEnabled = false;
    };

    app.config.globalProperties.$setEnterToTabStatus = (value) => {
      app.config.globalProperties.$isEnterToTabEnabled = value;
    };

    app.config.globalProperties.$toggleEnterToTab = () => {
      app.config.globalProperties.$isEnterToTabEnabled = !app.config.globalProperties.$isEnterToTabEnabled;
    };

    // https://vuejs.org/guide/reusability/custom-directives.html
    app.directive('prevent-enter-tab', {
      mounted: (el) => {
        el.preventEnterTab = true;
      },
      unmounted: (el) => {
        delete el.preventEnterTab;
      }
    });
  }
};
