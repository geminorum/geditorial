<!--

  FIXME: support object fileds with declerations
  -- example: precision data: 10x numbers with calc anonymous callback
  -- angle + distance from center = score

-->

<style lang="scss" scoped>
@import './Styles/App.scss';
</style>

<template>
  <div class="main-app-wrap grid-wrap">
    <table class="table table-striped table-bordered app-table">
      <thead>
        <tr>
          <th class="index">{{ i18n.index }}</th>
          <th v-for="(field, key) in fields" :class="['field-'+key, {
            required: config.required.includes(key),
            readonly: config.readonly.includes(key)
          }]">{{ field }}</th>
          <th :class="['actions', {
            'is-frozen': frozen
          }]">
            {{ $translate('actions') }}
            <GridSpinner :class="{ 'is-active': spinner }" :title="i18n.loading"></GridSpinner>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in items" :key="item._id" :class="{ 'is-editing': editing == item._id }">
          <td class="index">{{ cellIndex(index) }}</td>
          <td v-for="(field, key) in fields" :title="field" :class="[
            'field-'+key,
            cellClass(item[key], key), {
            'field-is-readonly': config.readonly.includes(key)
          }]">{{ cellValue(item[key], key) }}</td>
          <td class="actions"><div>
            <GridButton v-show="!frozen" @click="removeItem(item._id)" dashicon="remove" :title="i18n.remove" />
            <GridPopper>
              <GridButton @click="infoItem(item._id)" dashicon="info" :title="i18n.info" />
              <template #content>
                <GridInfo :info="info" :item="item" />
              </template>
            </GridPopper>
            <GridButton @click="editItem(item._id)" :dashicon="editing == item._id ? 'dismiss' : 'edit'" :title="i18n.edit" :class="{ 'is-editing': editing == item._id }" />
            <GridButton v-show="!frozen" @click="moveItem(index, true)" dashicon="arrow-up" :title="i18n.moveup" />
            <GridButton v-show="!frozen" @click="moveItem(index, false)" dashicon="arrow-down" :title="i18n.movedown" />
          </div></td>
        </tr>
        <template v-if="showForm()"><tr>
          <td class="plus">{{ i18n.plus }}</td>
          <td v-for="(field, key) in fields" :class="['form', {
            'is-not-valid': isNotValidInput(key),
            'is-required': config.required.includes(key),
            'is-read-only': config.readonly.includes(key),
            'is-searchable': isSearchable(key),
            'is-selectable': isSelectable(key)
            }]">
            <div
              v-if="config.readonly.includes(key)"
              :title="i18n.readonly"
              :class="[
                'field-'+key,
                cellClass(form[key], key),
                '-field-is-readonly'
              ]" v-html="form[key]"></div>
            <GridInput
              v-else
              ref="inputs"
              :field="key"
              :label="field"
              :data="form[key]"
              :class="['field-'+key, cellClass(form[key], key)]"
              :options="isSelectable(key) ? selectable[key] : false"
              @change="event => updateInput(key, event.target.value)"
              @keyup.esc="cancelEdit()"
            />
            <template v-if="isSearchable(key)">
              <GridButton @click="searchDiscovery(key, config.searchable[key])" dashicon="search" :title="i18n.search" class="-search-button" />
              <SearchResults @add-searched-item="addSearchedItem" :items="searchDiscovered" />
            </template>
          </td>
          <td class="actions"><div v-if="frozen">
              <GridButton @click="insertItem()" dashicon="saved" :title="i18n.edit" />
            </div><div v-else>
              <GridButton @click="insertItem()" dashicon="insert" :title="i18n.insert" />
            </div></td>
        </tr></template>
      </tbody>
    </table>
    <GridMessage :class="state" :message="message" :count="items.length"></GridMessage>
  </div>
</template>

<script>
import { debounce, has, each } from 'lodash-es';
import { EnterToTabMixin } from '../../vue-plugins/vue-enter-to-tab.v2'; // https://github.com/ajomuch92/vue-enter-to-tab
import { formatNumber } from '../../js-utils/number.v1';
import { isValidField } from '../../js-utils/fields.v1';
import apiFetch from '@wordpress/api-fetch'; // https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/
import { addQueryArgs } from '@wordpress/url'; // https://github.com/WordPress/gutenberg/tree/trunk/packages/url

export default {
  mixins: [EnterToTabMixin],
  inject: ['endpoint', 'config', 'fields', 'i18n', 'locale'],
  data () {
    return {
      spinner: true,
      // expanding: this.config.expanding || true, // whether the items in this collection can be expanded, i.e. no-new-item
      frozen: this.config.frozen || false, // whether the items in this collection can be expanded/reordered/deleted
      message: this.i18n.message,
      state: 'initial',
      editing: 0,

      searchDiscovered: [],
      searchPageMore: false,
      searchPage: 1,

      items: [],
      temp: [], // temporary data in case of edit canceling
      hidden: [], // // list of hidden fields
      unique: [], // list of unique fields
      readonly: [], // list of readonly fields
      required: [], // list of required fields
      searchable: {}, // list of searchable fields
      selectable: {}, // list of selectable fields
      info: {}, // info data fetched
      current: 0, // current comment id for info
      form: {}
    }
  },
  // computed: {},
  methods: {

    formReset () {
      // https://stackoverflow.com/a/71344289
      this.form = Object.fromEntries(Object.keys(this.fields).map(key => [key, '']));
      this.temp = [];
      this.hidden = this.config.hidden || [];
      this.unique = this.config.unique || [];
      this.searchable = this.config.searchable || {};
      this.selectable = this.config.selectable || {};
      this.required = this.config.required || [];
      this.readonly = this.config.readonly || [];
      this.editing = 0;
    },

    messageReset () {
      this.message = this.i18n.message;
      this.state = 'initial';
    },

    // @REF: https://codingbeautydev.com/blog/vue-focus-input/
    // https://michaelnthiessen.com/set-focus-on-input-vue
    // https://stackoverflow.com/questions/73753350/how-to-get-the-ref-which-is-in-a-child-component-in-vue
    focusInput () {
      if (this.frozen) return;
      // this.$refs.email.$el.focus();
      this.$refs.inputs[0].$refs.gridInput.focus();
    },

    cellIndex (index) {
      return formatNumber(index+1, this.locale);
    },

    // NOTE: better to be in `methods`: “You can pass a parameter to a computed
    // property in Vue, but if you need parameters there are most likely no
    // benefits of using a computed property function over a method.”
    // @REF: https://beginnersoftwaredeveloper.com/can-i-pass-a-parameter-to-a-computed-property-vue/
    cellClass (cell, offset) {
      if (!cell) return '-empty';
      const valid = isValidField(cell, offset, this.locale);
      if (valid===null) return '-unknown';
      return valid ? '-is-valid' : '-is-not-valid';
    },

    cellValue (value, key) {
      if (!this.isSelectable(key)) return value;
      if (!has(this.config.selectable[key], value)) return value;
      return this.config.selectable[key][value];
    },

    isSearchable (field) {
      return has(this.config.searchable, field);
    },

    isSelectable (field) {
      return has(this.config.selectable, field);
    },

    searchDiscovery (field, targets) {
      if (!this.form[field]) return; // no criteria
      this.doSearchDiscovery(this.form[field], targets, this.searchPage);
    },

    addSearchedItem (item, close) {
      const temp = JSON.parse(JSON.stringify(item));

      if (has(temp, 'id')&&this.config.hidden.includes('postid')){
        this.form.postid = temp.id;
      }

      each( this.fields, ( name, field ) => {
        if (this.form[field].trim()&&!this.isSearchable(field)) return;
        if (has(temp, 'extra.'+field)){
          this.form[field] = temp.extra[field];
        }
      });

      console.log(temp);

      // if (close) {
      //   // @REF: https://codingbeautydev.com/blog/vue-focus-input/
      //   this.$refs.searchinput.focus();
      // }
    },

    updateInput (field, value) {
      this.form[field] = value;
    },

    isNotValidInput (field) {
      if (!field) return false;
      if (!this.form[field]||!this.form[field].trim()) return false;
      const valid = isValidField(this.form[field], field, this.locale);
      return valid===null ? false : ! valid;
    },

    hasValidInput () {

      for (const required of this.required) {
        if (has(this.fields, required)&&!this.form[required].trim()) {
          return false;
        }
      }

      if (this.editing) return true;

      for (const unique of this.unique) {
        const current = this.form[unique].trim();
        if (current) {
          for (const item of this.items) {
            if (current===item[unique]) {
              return false;
            }
          }
        }
      }

      return true;
    },

    doQuery () {
      this.spinner = true;

      // https://github.com/WordPress/gutenberg/tree/trunk/packages/url#addqueryargs
      // const queryParams = { include: [1, 2, 3] }; // Return posts with ID = 1,2,3.
      // path = addQueryArgs(path, queryParams)

      apiFetch({
        path: this.endpoint + '/query/' + this.config.linked
      }).then((data) => {
        this.items = data;
        this.spinner = false;
      }).catch((error) => {
        this.spinner = false;
        this.state = 'wrong';
        this.message = error.message;
      });
    },

    doSearchDiscovery (criteria, targets, page) {
      this.spinner = true;

      apiFetch({
          path: addQueryArgs( this.config.searchselect + '/query', {
            search: criteria,
            target: 'post',
            context: 'subcontent',
            // exclude: this.config.linked,
            // posttype: this.config.targeting,
            posttype: targets,
            page: page || 1,
          } )
        }).then((data) => {
          this.searchDiscovered = data.results;
          this.searchPageMore = data.pagination.more;
          this.searchPage = page || 1;
          this.spinner = false;
        }).catch((error) => {
          this.spinner = false;
          this.state = 'wrong';
          this.message = error.message;
        });
    },

    doSort () {
      this.spinner = true;

      apiFetch({
          path: this.endpoint + '/sort/' + this.config.linked,
          method: 'POST',
          data: this.items.map( item => item._id )
        }).then((data) => {
          this.items = data;
          this.spinner = false;
          this.state = 'saved';
          this.message = this.i18n.sorted;
          this.formReset();
        }).catch((error) => {
          this.spinner = false;
          this.state = 'wrong';
          this.message = error.message;
        });
    },

    doInfo (id) {
      if (id === this.current) return;

      this.spinner = true;

      apiFetch({
        path: this.endpoint + '/summary/' + id
      }).then((data) => {
        this.info = data;
        this.current = id;
        this.spinner = false;
      }).catch((error) => {
        this.spinner = false;
        this.state = 'wrong';
        this.message = error.message;
      });
    },

    insertItem () {

      if (this.hasValidInput()) {
        this.spinner = true;

        apiFetch({
          path: this.endpoint + '/query/' + this.config.linked,
          method: 'POST',
          data: this.form,
        }).then((data) => {
          this.items = data;
          this.spinner = false;
          this.state = 'saved';
          this.message = this.editing ? this.i18n.edited : this.i18n.saved;
          this.focusInput();
          this.formReset();
        }).catch((error) => {
          this.spinner = false;
          this.state = 'wrong';
          this.message = error.message;
        });
      } else {
        this.state = 'wrong';
        this.message = this.i18n.invalid;
      }
    },

    infoItem (id) {
      if (id !== this.current) this.info = {}; // clear the old data
      this.doInfo(id);
    },

    showForm () {
      if (!this.frozen) return true;
      return this.editing ? true : false;
    },

    cancelEdit (id) {
      this.editItem(this.editing);
    },

    editItem (id) {
      if (!id) return;

      this.spinner = true;

      if (this.editing === id) {

        this.items = this.temp; // https://stackoverflow.com/a/71642679
        this.formReset();

      } else {

        this.editing = id;
        this.temp = JSON.parse(JSON.stringify(this.items));

        for (const item of this.items) {
          if ( item._id === id ) {
            this.form = item;
            break;
          }
        }
      }

      this.spinner = false;
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

    removeItem (id) {
      this.spinner = true;
      apiFetch({
          path: this.endpoint + '/query/' + this.config.linked,
          method: 'delete',
          data: { _id: id },
        }).then((data) => {
          this.formReset();
          this.items = data;
          this.spinner = false;
          this.focusInput();
		      this.messageReset();
        }).catch((error) => {
          this.spinner = false;
          this.state = 'wrong';
          this.message = error.message;
        });
    },
  },
  created () {
    // NOTE: replace intended method with debounced one
    // @REF: https://stackoverflow.com/a/75374781
    this.doSort = debounce(this.doSort, 1000);
  },
  unmounted () {
    this.doSort.cancel();
  },
  mounted () {
    this.focusInput();
    this.formReset();
    this.$nextTick(() => {
      this.doQuery();
    });
  }
}
</script>
