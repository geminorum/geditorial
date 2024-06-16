<template>
  <input class="grid-hidden" type="hidden" :value="value" />
</template>

<style lang="scss">
  .grid-hidden {}
</style>

<script>
export default {
  props: ['field', 'value'],
}
</script>
