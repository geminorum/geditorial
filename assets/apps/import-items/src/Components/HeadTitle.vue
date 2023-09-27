<template>
  <div class="head-title">
    <slot></slot>
    <span v-if="count" class="-count">({{ countItems }})</span>
  </div>
</template>

<style lang="scss">
$spacer: 1rem !default;

.head-title {
  border: 1px dashed #e2e2e2;
  padding: $spacer * .125 $spacer * .25;
  margin: 1px;
  margin-bottom: $spacer * .5;

  >div {
    display: inline-block;
  }

  .-count {
    display: inline-block;
    margin-left: $spacer * .25;
    font-size: smaller;
  }

  a {
    color: inherit;
  }
}
</style>

<script>
import { toPersian } from '../../../js-utils/number.v1';

export default {
  props: ['count'],
  inject: ['locale'],
  computed: {
    countItems() {
      return 'fa-IR' === this.locale
        ? toPersian(this.count)
        : this.count;
    },
  },
}
</script>
