<style lang="scss">
@import '../Styles/Popper.scss';

.grid-popper {
  margin: 0 !important;
  border: none !important;
}
</style>

<template>
  <Popper v-bind="$attrs" class="grid-popper">
    <slot />
    <template #content="props">
      <slot name="content" v-bind="props" />
    </template>
  </Popper>
</template>

<script>
export default {}
</script>
