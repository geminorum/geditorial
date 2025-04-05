<style lang="scss">
@import './Styles/App.scss';
</style>

<template>
  <div class="main-app-wrap wrap-sidebyside"
    @keyup.esc="doStore(closeApp)"
    tabindex="-1">

    <div class="-side -start">
      <div class="-head">
        <div class="-form">
          <div class="-input">

            <input v-model.lazy="queried" @keyup.enter="doQuery(true)" :placeholder="i18n.placeholder" type="search" ref="searchBox" />
            <AppSpinner :class="{ 'is-active': searchSpinner }" :title="i18n.loading" />

          </div>
          <div class="-buttons">

            <IconButton :dashicon="(rtl?'controls-forward':'controls-back')" @click="previousPage()" :disabled="searchPage===1" />
            <IconButton :dashicon="(rtl?'controls-back':'controls-forward')" @click="nextPage()" :disabled="!searchPageMore" />
            <IconButton :dashicon="(newitem?'no':'plus-alt')" @click="showNewItem()" />

          </div>
        </div>
        <div class="-message">

          <AppMessage :message="(!onLine ? i18n.offline : showBackOnline ? i18n.online : searchMessage)" :state="searchState" />

        </div>
        <div class="-hints" v-show="hints">
          <AppHint v-for="(hint, offset) in hints" :hint="hint" :offset="offset" />
        </div>
      </div>
      <Transition name="bounce">
      <div class="-newitem" v-show="newitem">
          <IconButton dashicon="insert" @click="newname ? doStore(doInsert) : false" />

          <div class="-group">
            <input class="-newname" v-model.lazy.trim="newname" :placeholder="i18n.newname" type="text" ref="newItemBox" @keyup.esc="showNewItem()" @keyup.enter="doInsert()" />
            <input class="-newslug" v-model.lazy.trim="newslug" :placeholder="i18n.newslug" type="text" ref="slugItemBox" @keyup.esc="showNewItem()" @keyup.enter="doInsert()" dir="ltr" />
          </div>

          <select v-model="newtarget" v-show="Object.keys(config.labels).length>1">
            <option v-for="(label, target) in config.labels" :value="target">{{ label }}</option>
          </select>
      </div>
      </Transition>
      <div class="-list"><ul>
        <li v-for="(result, index) in results" class="-result">
          <div class="-buttons">
              <IconButton dashicon="insert" @click="doStore(() => doAdd(result))" />
              <IconButton dashicon="edit" @click="openLink(result.extra.edit)" v-show="result.extra.edit" />
            </div>
          <div class="-box">
            <h5 class="-title" @click="openLink(result.extra.link)">{{ result.text }}</h5>
            <h6 class="-subtitle" v-show="result.extra.subtitle">{{ result.extra.subtitle }}</h6>
            <div class="-content" v-html="result.extra.description"></div>
          </div>
          <div class="-image" v-show="result.image"><img :src="result.image" /></div>
        </li>
      </ul></div>
    </div>
    <div class="-side -end">
      <div class="-head">
        <div class="-form">
          <div class="-input">
            <input :value="linked.text" type="text" readonly />
            <AppSpinner :class="{ 'is-active': summarySpinner }" :title="i18n.loading" />
          </div>
          <div class="-buttons">
            <IconButton dashicon="external" @click="openLink(linked.extra.link)" />
            <IconButton dashicon="yes-alt" @click="doStore()" />
          </div>
        </div>
        <div class="-message">
          <AppMessage :message="summaryMessage" :state="summaryState" />
        </div>
      </div>
      <div class="-list"><ul>
        <li v-for="(item, index) in items" class="-item">
          <div class="-buttons">
              <IconButton dashicon="remove" @click="doStore(() => doAdd(item,true))" />
              <!-- <IconButton dashicon="external" @click="openLink(item.extra.link)" v-show="item.extra.link" /> -->
              <IconButton dashicon="edit" @click="openLink(item.extra.edit)" v-show="item.extra.edit" />
              <IconButton dashicon="arrow-up" @click="moveItem(index, true)" />
              <IconButton dashicon="arrow-down" @click="moveItem(index, false)" />
            </div>
          <div class="-box">
            <h5 class="-title" @click="openLink(item.extra.link)">{{ item.text }}</h5>
            <!-- <h6 class="-subtitle" v-show="item.extra.subtitle">{{ item.extra.subtitle }}</h6> -->
            <!-- <div class="-content" v-html="item.extra.description"></div> -->
            <div class="-fields">
              <!-- fixme: toggle buttom for custom fields -->
              <CustomField v-for="(field, metakey) in fields" :field="field" :metakey="metakey" v-model="item[metakey]" />
              <!-- <input v-for="(field, metakey) in fields" type="text" :placeholder="field.title" v-model="item[metakey]" /> -->
            </div>
          </div>
          <div class="-image" v-show="item.image"><img :src="item.image" /></div>
        </li>
      </ul></div>
    </div>
  </div>
</template>

<script>
import { debounce } from 'lodash-es';
import { omitDeep } from '../../js-utils/object.v1';
import apiFetch from '@wordpress/api-fetch'; // https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/
import { addQueryArgs } from '@wordpress/url'; // https://github.com/WordPress/gutenberg/tree/trunk/packages/url

export default {
  inject: ['plugin', 'endpoint', 'linked', 'config', 'fields', 'i18n', 'locale', 'rtl'],
  data () {
    return {
      onLine: navigator.onLine,
      showBackOnline: false,

      queried: '',
      items: {},
      summarySpinner: false,
      summaryMessage: '',
      summaryState: 'initial',

      hints: [],

      results: {},
      searchSpinner: false,
      searchMessage: this.i18n.initial,
      searchState: 'initial',
      searchPageMore: false,
      searchPage: 1,

      newitem: false,
      newname: '',
      newslug: '',
      newtarget: Object.keys(this.config.labels)[0],
    }
  },
  // computed: {},
  methods: {
    updateOnlineStatus(e) {
      const { type } = e
      this.onLine = type === 'online'
      this.searchState = this.onLine ? 'initial' : 'wrong';
    },

    focusSearch () {
      this.$refs.searchBox.focus();
    },

    focusNewItem () {
      this.$refs.newItemBox.focus();
    },

    openLink (link) {
      if (link) window.open(link); // else return false;
    },

    closeApp() {
      try {
        // @REF: https://stackoverflow.com/a/15992002
        window.parent.jQuery.colorbox.close();
        // window.parent.$.colorbox.close();
      } catch (error) {
        console.log(error);
      }
    },

    showNewItem () {
      if ( this.newitem ) {
        this.newitem = false;
        // this.$refs.searchBox.focus();
        this.focusSearch();
      } else {
        this.newitem = true;
        this.$nextTick(() => {
          // this.$refs.newItemBox.focus();
          this.focusNewItem();
        });
      }
    },

    nextPage () {
      this.searchPage++;
      this.doQuery();
    },
    previousPage () {
      this.searchPage--;
      this.doQuery();
    },

    moveItem (index, direction) {
      const to = direction ? (index - 1) : (index + 1);
      if ( to < 0 || to >= this.items.length) {
        return;
      }

      const temp = this.items[index];
      this.items[index] = this.items[to];
      this.items[to] = temp;

      this.doSort();
    },

    doInsert() {
      if(!this.newname) return;
      this.summarySpinner = true;

      apiFetch({
        method: 'POST',
        path: this.config.routes[this.newtarget],
        data: {
          name: this.newname,
          slug: this.newslug
        }
      }).then((data) => {
        // console.log(data);
        this.newitem = false;
        this.newname = '';
        this.newslug = '';

        this.doAdd({
          id: data.id,
          text: data.name,
          // taxonomy: data.taxonomy,
          extra: {
            link: data.link,
            edit: '',
          }
        });

        // NOTE: spinner/state will set by `doAdd()`

        // this.$refs.searchBox.focus();
        this.focusSearch();
      }).catch((error) => {
        this.summarySpinner = false;
        this.state = 'wrong';
        this.message = error.message;
        // this.$refs.newItemBox.focus();
        // this.focusNewItem();
      });
    },

    doAdd (item, del = false) {
      this.summarySpinner = true;

      const data = del ? {
        id: item.id,
        __delete: true
      } : {
        id: item.id,
        _order: this.items.length
      };

      apiFetch({
        method: 'POST',
        path: this.linked.rest,
        data: {
          // [(item.extra._base || item.extra._type)]: [ item.id ]
          [(this.linked.related)]: [ data ]
        }
      }).then((data) => {
        // this.items = data.results;

        // if(del) {
        //   this.items = remove(this.items, (i) => i.id!=item.id);
        // } else {
        //   this.items.push(item);
        // }

        this.renderSummary();
        // NOTE: spinner/state will set by `renderSummary()`
        // this.$refs.searchBox.focus();
        this.focusSearch();

      }).catch((error) => {
        this.summarySpinner = false;
        this.state = 'wrong';
        this.message = error.message;
        // this.$refs.searchBox.focus();
        this.focusSearch();
      });
    },
    renderSummary (lite) {
      this.summarySpinner = true;

      apiFetch({
        path: addQueryArgs(this.linked.rest, {
          context: 'edit'
        })
        }).then((data) => {
          // console.log(data);
          this.summarySpinner = false;
          this.summaryState = 'initial';
          // this.summaryMessage = data[this.config.attribute].rendered;
          // this.items = data[this.config.attribute].terms;
          this.summaryMessage = data[this.config.attribute];
          if (!lite) this.items = data[this.linked.related];
        }).catch((error) => {
          this.summarySpinner = false;
          this.summaryState = 'wrong';
          this.summaryMessage = error.message;
        });
    },

    doStore (callback) {
      this.summarySpinner = true;

      apiFetch({
        method: 'POST',
        path: this.linked.rest,
        data: {
          [(this.linked.related)]: omitDeep(this.items, [
            '_order', // orders are saved via `doSort()`
            'extra', // no need to pass extra info
          ]),
        }
      }).then((data) => {
        this.renderSummary(true);
        this.state = 'success';
        this.summarySpinner = false;
        if(callback) callback();
      }).catch((error) => {
        this.summarySpinner = false;
        this.state = 'wrong';
        this.message = error.message;
      });
    },

    fetchHints() {
      // no need for spin!

      apiFetch({
        path: addQueryArgs( this.config.hints + '/tips', {
          id: this.linked.id,
          target: 'post',
          extend: this.config.attribute,
          context: 'edit', // this.config.context, // this.plugin.appname,
        } )
      }).then((data) => {
          console.log(data);
          this.hints = data;
      }).catch((error) => {
          console.log(error);
          this.state = 'wrong';
          this.message = error.message;
      });
    },

    doQuery (changed) {
      this.searchSpinner = true;

      if (changed) {
        this.searchPage = 1;
      }

      apiFetch({
        path: addQueryArgs( this.config.searchselect + '/query', {
            search: this.queried,
            target: 'term',
            // context: ,
            // context: this.plugin.appname ? this.plugin.appname : 'assignment-dock',
            context: this.plugin.appname,
            taxonomy: this.config.targets.join(','),
            page: this.searchPage,
            per: this.config.perpage || 5,
          } )
        }).then((data) => {
          this.results = data.results;
          this.searchPageMore = data.pagination.more;
          this.searchState = this.queried ? 'success' : 'initial';
          this.searchSpinner = false;
          // this.$refs.searchBox.focus();
          this.focusSearch();
        }).catch((error) => {
          this.searchMessage = error.message;
          this.searchPageMore = false;
          this.searchState = 'wrong';
          this.searchSpinner = false;
          // this.$refs.searchBox.focus();
          this.focusSearch();
        });
    },

    doSort () {
      this.summarySpinner = true;

      apiFetch({
          method: 'POST',
          path: this.linked.rest,
          data: {
            [(this.linked.related)]: this.items.map( (item, index) => { return { id: item.id, _order: index }} )
          }
        }).then((data) => {
          // this.items = data;

          this.renderSummary(true);
          this.summarySpinner = false;
          this.state = 'saved';
          // this.message = this.i18n.sorted;
          // this.formReset();

        }).catch((error) => {
          this.summarySpinner = false;
          this.state = 'wrong';
          // this.message = error.message;
        });
    }
  },
  watch: {
    onLine(v) {
      if (v) {
        this.showBackOnline = true
        setTimeout(() => {
          this.showBackOnline = false
        }, 1000)
      }
    }
  },
  created () {
    // NOTE: replace intended method with debounced one
    // @REF: https://stackoverflow.com/a/75374781
    this.doQuery = debounce(this.doQuery, 500);
    this.doSort = debounce(this.doSort, 3000);
    this.renderSummary = debounce(this.renderSummary, 1000);
  },
  mounted () {
    // this.focusSearch();
    // this.formReset();
    // this.searchMessage = this.i18n.initial;
    this.$nextTick(() => {
      this.renderSummary();
      this.doQuery();
      this.fetchHints();
    });

    window.addEventListener('online', this.updateOnlineStatus);
    window.addEventListener('offline', this.updateOnlineStatus);
  },
  unmounted () {
    this.doQuery.cancel();
    this.doSort.cancel();
    this.renderSummary.cancel();
  },
  beforeDestroy() {
    window.removeEventListener('online', this.updateOnlineStatus);
    window.removeEventListener('offline', this.updateOnlineStatus);
  }
};
</script>
