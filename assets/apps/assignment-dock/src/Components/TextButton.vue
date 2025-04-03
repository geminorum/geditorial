<style lang="scss">
$spacer: 1rem !default;

.text-button {
  margin: 0 !important;
  padding: 1px 6px 0 !important;

  color: inherit !important;
  min-height: auto !important;

  .dashicons {
    color: inherit;
    font-size: 16px;
    width: 16px;
    height: 16px;
    vertical-align: text-bottom;
  }

  .-text {
    &:empty {
      display: none;
    }

    display: inline-block;
    margin-left: $spacer * .25;
    margin-right: $spacer * .5;
  }
}
</style>

<template>
  <button class="button small-button text-button" v-prevent-enter-tab>
    <span class="dashicons" :class="classObject"></span>
    <span class="-text"><slot></slot></span>
  </button>
</template>

<script>
export default {
  props: ['dashicon'],
  computed: {
    classObject() {
      return 'dashicons-' + this.dashicon;
    }
  }
}
</script>
