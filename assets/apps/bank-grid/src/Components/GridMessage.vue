<template>
  <p class="description -description grid-message">{{ message }} <span class="-count-items" v-show="count">({{ countItems }})</span></p>
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

span.-count-items {
  font-size: smaller;
}
</style>

<script>
import { sprintf } from '@wordpress/i18n'; // https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
import { formatNumber } from '../../../js-utils/number.v1';

export default {
  inject: ['i18n', 'locale'],
  props: ['message', 'count'],
  computed: {
    countItems() {
      return sprintf(this.i18n.countitems, formatNumber(this.count, this.locale));
    },
  }
}
</script>
