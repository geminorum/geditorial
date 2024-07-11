<template>
  <input class="grid-input" :class="class" type="text" :title="label" :value="value" />
</template>

<style lang="scss">
.grid-input {
  box-sizing: border-box;
  width: calc( 100% - 2px );
  border: none !important;
  border-radius: 0 !important;
  padding: 0 calc( .5em - 2px ) !important;
  font-family: inherit !important;
  font-size: inherit !important;

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
