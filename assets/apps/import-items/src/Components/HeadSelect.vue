<!--
https://github.com/sagalbot/vue-select
https://github.com/yuche/vue-strap/blob/master/src/Select.vue
[How to Build a Custom Select Component in Vue.js](https://morioh.com/p/5c9411f34356)
https://madewithvuejs.com/select
https://hackernoon.com/how-to-make-a-custom-select-component-in-vuejs-8kt32pj
https://blog.boot.dev/javascript/custom-select-component-in-vue/
-->

<style lang="scss">
.head-select {
  width: 100%;
  padding: 3px 6px !important;
  font-size: smaller !important;
  min-height: auto !important;

  option {
    padding: .125rem .25rem;
  }
}
</style>

<template>
  <select class="head-select" v-model="selected">
    <option v-for="(label, code) in options" :key="code">{{ label }}</option>
  </select>
</template>

<script>
export default {
  props: ['options', 'default'],
  data() {
    return {
      selected: '',
    }
  },
  // computed: {
  //   classObject() {
  //     // return 'dashicons-' + this.$attrs.dashicon;
  //     return 'dashicons-' + this.dashicon;
  //   }
  // }
}
</script>
