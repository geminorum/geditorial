<style lang="scss">
.search-results-dropdown {
  /**font-size: .75rem !important;**/
  position: absolute;
  /**top: calc(100% - 0.125rem);**/
  top: calc(100%);
  /**width: calc(100% - 34px);**/
  width: calc(100%);
  right: 0;
  left: 0;
  background-color: #fff;
  border: 1px solid #ccc;
  border-top: none;

  .close-me {
    position: absolute;
    height: 1rem;
    top: -1.5rem;
    left: calc(100% - 1.5rem);
    background-color: #fff;
  }

  ul {
    margin: 0;
    padding: 0;

    li {
      margin: 0;
      padding: .35rem .25rem .35rem .5rem;
      border-top: 1px solid #ccc;

      button {
        float: right;

        > span {
          display: inline-block;
          cursor: pointer;
          color: #8c8f94;
          vertical-align: text-top;
          }
      }
    }
  }

  button {
    border: none;
    background-color: transparent;
    cursor: pointer;
    margin: 0;
    padding: 0;
    height: 100%;
    display: inline-block;
  }
}
</style>

<template>
  <div class="search-results-dropdown" v-show="opened">
    <button @click="closeMe()" class="close-me">
      <span class="dashicons dashicons-dismiss"></span>
    </button>
    <ul>
      <li v-for="(item, key) in items">
        <!-- <span @click="addItem(item, true)" v-html="item.text"></span> -->
        <span v-html="item.text"></span>
        <button @click="addItem(item, true)">
          <span class="dashicons dashicons-insert"></span>
        </button>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  props: [
    'items',
  ],
  data() {
    return {
      closed: null
    }
  },
  computed: {
    opened() {
      return this.closed === null ? this.items.length : !this.closed;
    }
  },
  watch: {
    items(newItems, oldItems) {
      this.closed = null;
    }
  },
  methods: {
    closeMe() {
      this.closed = !this.closed;;
    },
    addItem(item, close) {
      this.$emit('addSearchedItem', item, close);
      if (close) this.closed = true;
    },
  },
}
</script>
