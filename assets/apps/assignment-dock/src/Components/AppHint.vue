<style lang="scss">
$spacer: 1rem !default;
$brand-success: #5cb85c !default;
$brand-info:    #5bc0de !default;
$brand-warning: #f0ad4e !default;
$brand-danger:  #d9534f !default;

.app-hint {
  font-size: smaller;
  padding: $spacer * .25;

  &.-hint-html {}
  &.-hint-text {}

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
  <div v-if="hint.html" :class="['app-hint', '-hint-html', hint.class, state]" v-html="hint.html" :title="hint.title"></div>
  <div v-else :class="['app-hint', '-hint-text', hint.class, state]">
    <a :href="hint.link" :title="hint.title">{{ hint.text }}</a>
  </div>
</template>

<script>
export default {
  props: ['state', 'hint', 'offset']
}
</script>
