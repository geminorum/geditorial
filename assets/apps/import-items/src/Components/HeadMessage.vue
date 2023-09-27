<template>
  <div :class="['head-message', state]">
    <slot></slot>
  </div>
</template>

<style lang="scss">
$spacer: 1rem !default;
$brand-success: #5cb85c !default;
$brand-info: #5bc0de !default;
$brand-warning: #f0ad4e !default;
$brand-danger: #d9534f !default;

.head-message {
  border: 1px dashed #e2e2e2;
  padding: $spacer * .125 $spacer * .25;
  margin: 1px;
  margin-bottom: $spacer * .5;

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
  props: ['state']
}
</script>
