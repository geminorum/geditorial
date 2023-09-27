<style lang="scss">
.search-results-dropdown {
  font-size: .75rem !important;
  position: absolute;
  top: calc(100% - 0.125rem);
  width: calc(100% - 34px);
  left: 1px;
  background-color: #fff;
  border: 1px solid #8c8f94;
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
      padding: .25rem .25rem .25rem .5rem;

      +li {
        border-top: 1px solid #8c8f94;
      }

      >span {
        display: inline-block;
        cursor: pointer;
      }

      button {
        float: right;
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
        <span @click="addItem(item, true)" v-html="item.text"></span>
        <button @click="addItem(item)">
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
