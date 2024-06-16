<template>
  <p class="description -description grid-message">{{ message }}</p>
</template>

<style lang="scss">
$brand-success: #5cb85c !default;
$brand-info:    #5bc0de !default;
$brand-warning: #f0ad4e !default;
$brand-danger:  #d9534f !default;

.grid-message {
  font-size: .75rem !important;
  margin-top: 1rem;
  margin-left: .25rem;

  &.initial {
    color: gray;
  }
  &.saved {
    color: $brand-success;
  }

  &.wrong {
    color: $brand-danger;
  }
}
</style>

<script>
export default {
  props: ['message']
}
</script>
