<style lang="scss">
.import-button {
  margin: 0 !important;
  padding: 1px 6px 0 !important;

  color: inherit !important;
  min-height: auto !important;

  .dashicons {
    color: inherit;
    color: #333;
    font-size: 16px;
    width: 16px;
    height: 16px;
    vertical-align: text-bottom;
  }
}
</style>

<template>
  <button class="button small-button import-button" v-prevent-enter-tab>
    <span class="dashicons" :class="classObject"></span>
    <slot></slot>
  </button>
</template>

<script>
export default {
  props: ['dashicon'],
  computed: {
    classObject() {
      // return 'dashicons-' + this.$attrs.dashicon;
      return 'dashicons-' + this.dashicon;
    }
  }
}
</script>
