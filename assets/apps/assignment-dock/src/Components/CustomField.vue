<style lang="scss">
$spacer: 1rem !default;
$brand-success: #5cb85c !default;
$brand-info:    #5bc0de !default;
$brand-warning: #f0ad4e !default;
$brand-danger:  #d9534f !default;

.-custom-field {

  /**
  margin-bottom: $spacer * .25;
  &:last-of-type {
    margin-bottom: 0;
  }
     */

  select,
  input[type="text"] {
    width: 100%;
    max-width: unset !important;
  }

  label {
    display: block;
    margin-top: $spacer * .25;
    line-height: 1;
  }

  input[type="checkbox"] {
    /*margin-top: $spacer * .125;*/
    vertical-align: bottom;
  }
}
</style>

<template>
  <div class="-custom-field">
     <label v-if="field.type === 'boolean'"
       :title="field.desc"
       ><input
         type="checkbox"
         v-model="value"> {{ field.title }}</label>

     <select v-else-if="field.options"
       ref="gridInput"
       class="grid-select"
       :title="field.desc"
       v-model="value"
     ><option value="">{{ i18n.select }}</option>
       <option v-for="(option, slug) in field.options" :value="slug">{{ option }}</option>
     </select>

     <input v-else
       v-bind="$attrs"
       type="text"
       :placeholder="field.title"
       :title="field.desc"
       v-model="value"
       />
   </div>
</template>

<script>
export default {
  inject: ['i18n'],
  props: [ 'modelValue', 'field', 'metakey' ],
  emits: [ 'update:modelValue' ],
  computed: {
    value: {
      get() {
        // console.log(this.modelValue);
        return this.modelValue
      },
      set(value) {
        // console.log(value);
        this.$emit('update:modelValue', value)
      }
    }
  }
}
</script>
