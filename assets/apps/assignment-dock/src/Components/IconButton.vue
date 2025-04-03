<style lang="scss">
$spacer: 1rem !default;
$brand-success: #5cb85c !default;
$brand-info:    #5bc0de !default;
$brand-warning: #f0ad4e !default;
$brand-danger:  #d9534f !default;

.icon-button {
  border: none;
  background-color: transparent;
  cursor: pointer;
  margin: 0;
  padding: 0;

/**
  + button {
    margin-left: $spacer * .5;
  }
 */
  &:focus {
    outline: none;
  }

  .dashicons {}

  .dashicons-info {
    color: $brand-info;
  }

  .dashicons-database-view,
  .dashicons-edit {
    color: $brand-warning;
  }

  .dashicons-database-add,
  .dashicons-insert {
    color: $brand-success;
  }

  .dashicons-database-remove,
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

<template>
  <button class="icon-button" :class="class" :disabled="disabled" v-prevent-enter-tab><span class="dashicons" :class="classObject"></span></button>
</template>

<script>
export default {
  props: ['dashicon', 'class', 'disabled'],
  computed: {
    classObject() {
      return 'dashicons-' + this.dashicon;
    }
  }
}
</script>
