<template>
  <button class="grid-button" :class="class" v-prevent-enter-tab><span class="dashicons" :class="classObject"></span></button>
</template>

<style lang="scss">

$brand-success: #5cb85c !default;
$brand-info:    #5bc0de !default;
$brand-warning: #f0ad4e !default;
$brand-danger:  #d9534f !default;

.grid-button {
  border: none;
  background-color: transparent;
  cursor: pointer;
  margin: 0;
  padding: 0;

  &:focus {
    /* outline: none; */
  }

  .dashicons {
    /* font-size: 14px; */
  }

  .dashicons-info {
    color: $brand-info;
  }

  .dashicons-edit {
    color: $brand-warning;
  }

  .dashicons-insert {
    color: $brand-success;
  }

  .dashicons-remove {
    color: $brand-danger;
  }

  &.is-editing {
    .dashicons {
      color: $brand-danger !important;
    }
  }
}
</style>

<script>
export default {
  props: ['dashicon', 'class'],
  computed: {
    classObject() {
      // return 'dashicons-' + this.$attrs.dashicon;
      return 'dashicons-' + this.dashicon;
    }
  }
}
</script>
