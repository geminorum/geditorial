<template>
  <input class="grid-input" type="text" :title="label" :value="value" />
</template>

<style lang="scss">

$brand-success: #5cb85c !default;
$brand-info:    #5bc0de !default;
$brand-warning: #f0ad4e !default;
$brand-danger:  #d9534f !default;

  .grid-input {
    border: none !important;
    border-radius: 0 !important;
    width: calc( 100% - 2px );
    /* margin: 0 1px; */
    box-sizing: border-box;
    /* background-color: transparent !important; */
    /* cursor: pointer; */
    /* margin: 0; */
    /* padding: 0; */

    &:focus {
      /* box-shadow: none !important; */
    }
  }
</style>

<script>

// https://vuejs.org/guide/components/v-model.html

export default {
  // inject: ['i18n'],
  props: ['field', 'label', 'value', 'class'],
  // emits: ['update-input'],
  // computed: {
  //   placeholderObject() {
  //     return this.label; // ''; // this.i18n['grid_' + this.$attrs.field];
  //   }
  // }
}
</script>
