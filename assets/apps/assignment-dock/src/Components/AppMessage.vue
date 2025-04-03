<style lang="scss">
$spacer: 1rem !default;
$brand-success: #5cb85c !default;
$brand-info:    #5bc0de !default;
$brand-warning: #f0ad4e !default;
$brand-danger:  #d9534f !default;

.app-message {
  font-size: smaller;
  padding-left: $spacer * .25;

  p + p {
    margin-top: $spacer * .5;
  }

  a {

  }

  a:not(:hover) {
    color: inherit;
    text-decoration: none;
  }

  &.initial {}
  &.wrong {
    color: $brand-danger;
  }

  &.success {
    color: $brand-success;
  }
}
</style>

<template>
  <div :class="['app-message', state]" v-html="message"></div>
</template>

<script>
export default {
  props: ['state', 'message']
}
</script>
