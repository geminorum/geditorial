<template>
  <span class="spinner -loading grid-spinner"></span>
</template>

<style lang="scss">
.grid-spinner {
  padding: 0 !important;
  margin: 0 !important;
  float: none !important;

  button + & {
    margin-left: .25rem !important;
  }
}
</style>

<script>
export default {
}
</script>
