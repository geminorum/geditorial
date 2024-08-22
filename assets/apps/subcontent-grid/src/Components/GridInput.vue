<template>
  <select v-if="options"
      ref="gridInput"
      class="grid-select"
      :class="class"
      :title="label"
      :modelValue="data"
    ><option value="">{{ i18n.select }}</option>
    <option v-for="(option, key) in options" :value="key" :selected="data==key">{{ option }}</option>
  </select>
  <input v-else
    ref="gridInput"
    type="text"
    class="grid-input"
    :class="class"
    :title="label"
    :value="data"
  />
</template>

<style lang="scss">
.grid-select {
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
  inject: ['i18n'],
  props: ['field', 'label', 'data', 'options', 'class'],
  // emits: ['update-input'],
  // computed: {
  //   placeholderObject() {
  //     return this.label; // ''; // this.i18n['grid_' + this.$attrs.field];
  //   }
  // }
}
</script>
