<!--
  TODO: SearchResults: separate logic
  TODO: Button: remove selected from imported list
  TODO: handle esc key: @see: assignment-dock app
-->

<style lang="scss" scoped>
@import './Styles/App.scss';
</style>

<template>
  <div class="main-app-wrap">
    <div class="main-app-side to-side">
      <div class="side-to-head">
        <HeadTitle :count="items.length"><div v-html="title"></div></HeadTitle>
        <div class="side-to-search">
          <input
            type="search"
            ref="searchinput"
            v-model="search"
            :placeholder="i18n.searchholder"
            @keyup.enter="searchDiscovery"
            @keyup.page-up="searchPageUp"
            @keyup.page-down="searchPageDown"
            @keyup.esc="searchDiscovered=[]"
            @search="searchDiscovered=[]"
            v-prevent-enter-tab />
          <GridButton @click="searchDiscovery()" dashicon="search" :title="i18n.search" />
          <SearchResults @add-searched-item="addSearchedItem" :items="searchDiscovered" />
        </div>
      </div>
      <table class="app-table"><thead>
        <tr>
          <th class="table-actions" style="width:40px">
            <ImportSpinner :class="{ 'is-active': searchSpinner }" :title="i18n.loading"></ImportSpinner>
          </th>
          <th v-for="(field, key) in fields" :class="[key]">
            {{ field }}
          </th>
        </tr>
      </thead><tbody>
        <tr v-for="item in filteredItems" :key="item.id">
          <td class="table-actions">
            <GridButton @click="openItem(item)" dashicon="external state-info" :title="i18n.openitem" />
            <GridButton @click="removeItem(item)" dashicon="dismiss state-warning" :title="i18n.remove" />
          </td>
          <td v-for="(field, key) in fields" v-html="item[key]"></td>
        </tr>
      </tbody></table>
    </div>
    <div class="main-app-side from-side">
      <div class="side-from-head">
        <HeadMessage :state="state"><div v-html="message"></div></HeadMessage>
        <div class="side-from-buttons">
          <ImportButton v-show="selectedLines.length" @click="clickDiscovery()" dashicon="database-export" :title="i18n.discovery">{{ $translate('discovery') }}</ImportButton>
          <ImportButton v-show="discovered.length" @click="clickClearAdded()" dashicon="database-view" :title="i18n.clearadded">{{ $translate('clearadded') }}</ImportButton>
          <ImportButton v-show="selectedLines.length" @click="clickAddSelected()" dashicon="database-add" :title="i18n.addtitle">{{ $translate('add') }}</ImportButton>
          <ImportButton v-show="selectedLines.length" @click="clickDeleteSelected()" dashicon="database-remove" :title="i18n.deletetitle">{{ $translate('delete') }}</ImportButton>
          <ImportButton @click="clickUpload()" dashicon="database-import" :title="i18n.uploadtitle">{{ $translate('upload') }}</ImportButton>
          <ImportButton v-show="supportClipboard" @click="clickPasteSupported()" dashicon="editor-paste-text" :title="i18n.pastetitle">{{ $translate('paste') }}</ImportButton>
          <ImportButton @click="clickClearAll()" dashicon="trash" :title="i18n.cleartitle">{{ $translate('clear') }}</ImportButton>
          <label><input type="checkbox" v-model="addNewOnDiscovery" /> <span>{{ $translate('insertnew') }}</span></label>
          <input type="file" ref="fileinput" @change="readFile()" hidden accept=".csv, .txt, .xlsx" />
          <div class="form-extra-info">
            <span class="-count-selected" v-show="selectedLines.length">{{ countSelected }}</span>
            <span class="-count-lines" v-show="lines.length">{{ countLines }}</span>
            <span class="-file-name" v-show="fileName">{{ fileName }}</span>
          </div>
        </div>
      </div>
      <table class="app-table" v-show="lines.length">
        <thead>
        <tr>
          <th class="-checkbox"><input type="checkbox" @change="event => selectAll(event.target.checked)"/></th>
          <th v-for="rawColumn in rawColumns" :key="rawColumn" class="table-column-select">
            <!-- <HeadSelect :options="columns" :default="rawColumn" /> -->
            <select v-model="columnTypes[rawColumn]" @change="event => columnTypeChanges(rawColumn, event.target.value)">
              <option value="_undefined" class="-undefined">[{{ $translate('undefined') }}]</option>
              <option v-for="(label, code) in types" :key="code" :value="code">{{ label }}</option>
              <option value="_deleteme" class="-deleteme">[{{ $translate('deleteme') }}]</option>
              <option value="_flipdate" class="-flipdate">[{{ $translate('flipdate') }}]</option>
            </select>
          </th>
          <th class="table-actions" style="width:20px">
            <ImportSpinner :class="{ 'is-active': spinner }" :title="i18n.loading"></ImportSpinner>
          </th>
          <th class="-founded">{{ $translate('founded') }}</th>
        </tr>
      </thead><tbody>
        <tr v-for="(line, index) in lines" :key="index">
          <td class="-checkbox"><input type="checkbox" :value="index" v-model="selectedLines" /></td>
          <td v-for="rawColumn in rawColumns" :class="['table-column-input', cellClass(line[rawColumn], rawColumn)]">
            <input type="text" v-model="lines[index][rawColumn]" />
          </td>
          <td class="table-actions">
            <GridButton @click="checkLineClick(index)" :dashicon="checkLineIcon(index)" />
          </td>
          <!-- <td class="-actions">
            <GridPopper>
              <button @click="checkLineInfo(index, discovered[index])">
                <span class="dashicons dashicons-info"></span>
              </button>
              <template #content>
                <div v-html="infoContent(index, discovered[index])"></div>
              </template>
            </GridPopper>
          </td> -->
          <td class="-founded" v-html="renderFounded(index)"></td>
        </tr>
      </tbody></table>
    </div>
  </div>
</template>

<script>
import { EnterToTabMixin } from '../../vue-plugins/vue-enter-to-tab.v2'; // https://github.com/ajomuch92/vue-enter-to-tab
import { toSingleSpace, toMultipleLines } from '../../js-utils/text.v1';
import { verifyDateString, flipDateString } from '../../js-utils/datetime.v1';
import { checkMobileByLocale } from '../../js-utils/mobile.v1';
import { getName } from '../../js-utils/file.v1';
import { formatNumber } from '../../js-utils/number.v1';

import XLSX from 'xlsx'; // https://docs.sheetjs.com/docs/demos/frontend/vue
import apiFetch from '@wordpress/api-fetch'; // https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/
import { sprintf } from '@wordpress/i18n'; // https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
import { addQueryArgs } from '@wordpress/url'; // https://github.com/WordPress/gutenberg/tree/trunk/packages/url
import { parse } from 'csv-parse/browser/esm/sync'; // https://www.npmjs.com/package/csv-parse

import {
  debounce,
  find,
  has,
  omit,
  each,
  uniq,
  filter,
  clone,
  remove,
  map,
  mapKeys,
  mapValues,
  unset,
  unionBy
} from 'lodash-es';

import {
  verifyIranianNationalId,
  isShebaValid,
  digitsArToEn, // TODO: migrate to number util
  digitsFaToEn // TODO: migrate to number util
} from "@persian-tools/persian-tools"; // https://github.com/persian-tools/persian-tools

export default {
  mixins: [EnterToTabMixin],
  inject: ['endpoint', 'linked', 'config', 'fields', 'i18n', 'locale'],
  data () {
    return {
      spinner: true,
      title: this.config.title,
      state: 'initial',
      message: this.i18n.message,

      search: '',
      searchDiscovered: [],
      searchPageMore: false,
      searchPage: 1,
      searchSpinner: true,

      items: [],
      already: [],

      content: '',
      contentType: null,
      fileName: '',

      lines: [],
      selectedLines: [],
      rawColumns: [],
      types: this.config.types,
      columnTypes: {},
      discovered: [],

      addNewOnDiscovery: false,
      supportClipboard: false,
    }
  },
  computed: {
    filteredItems () {
      return this.items.filter(item => {
        // return true if the item should be visible

        const title = item.title || '';
        const identifier = item.identifier || '';

        // in this example we just check if the search string
        // is a substring of the item title (case insensitive)
        return (title.toLowerCase().indexOf(this.search.toLowerCase()) != -1)
        || (identifier.toLowerCase().indexOf(this.search.toLowerCase()) != -1);
      });
    },
    countLines () {
      return sprintf(this.i18n.countlines, formatNumber(this.lines.length, this.locale));
    },
    countSelected () {
      return sprintf(this.i18n.countselected, formatNumber(this.selectedLines.length, this.locale));
    }
  },
  methods: {
    clickClearAll () {
      this.spinner = true;
      this.state = 'initial';
      this.message = this.i18n.message;

      this.content = '';
      this.contentType = null;
      this.fileName = '';

      this.lines = [];
      this.selectedLines = [];
      this.rawColumns = [];

      this.columnTypes = {};
      this.discovered = [];

      this.message = this.i18n.message;
      this.spinner = false;
    },

    messageReset () {
      this.message = this.i18n.message;
      this.state = 'initial';
      this.spinner = false;
    },

    // @REF: https://codingbeautydev.com/blog/vue-focus-input/
    // https://michaelnthiessen.com/set-focus-on-input-vue
    // https://stackoverflow.com/questions/73753350/how-to-get-the-ref-which-is-in-a-child-component-in-vue
    focusInput () {
      // if(this.frozen) return;
      // this.$refs.email.$el.focus();
      // this.$refs.inputs[0].$refs.gridInput.focus();
      this.$refs.searchinput.focus();
    },

    isDateField (field) {
      return [
        'date',
        'datetime',
        'datestart',
        'dateend',
        'date_of_birth',
        'date_of_death',
        'dob'
      ].includes(field);
    },

    // better to be in `methods`: “You can pass a parameter to a computed
    // property in Vue, but if you need parameters there are most likely no
    // benefits of using a computed property function over a method.”
    // @REF: https://beginnersoftwaredeveloper.com/can-i-pass-a-parameter-to-a-computed-property-vue/
    cellClass (cell, offset) {
      if(!cell) {
        return '-empty';
      } else if (this.isDateField(this.columnTypes[offset])) {
        return 'fa-IR'===this.locale && verifyDateString(cell) ? '-is-valid' : '-is-not-valid';
      } else if (offset=='identity'||offset=='identifier'||this.columnTypes[offset]=='identity_number') {
        return 'fa-IR'===this.locale && verifyIranianNationalId(cell) ? '-is-valid' : '-is-not-valid';
      } else if (offset=='iban'||offset=='sheba'||this.columnTypes[offset]=='iban') {
        return isShebaValid(cell) ? '-is-valid' : '-is-not-valid';
      } else if (offset=='mobile'||offset=='phone'||this.columnTypes[offset]=='mobile_number') {
        return checkMobileByLocale(cell, this.locale) ? '-is-valid' : '-is-not-valid';
      }

      return '-unknown';
    },

    // infoContent(offset, discovered) {
    //   return discovered;
    // },
    // checkLineInfo(offset, discovered) {
    //   console.log(offset, discovered);
    // },

    checkLineClick (index) {
      const found = find(this.discovered, { _ref: index });
      if (!found) {
        this.doSingleDiscovery(this.lines[index], index, false);
      } else if(found.postid) {
        window.open(addQueryArgs(this.config.infolink, { post: found.postid }));
      } else if (found.status==='creatable') {
        this.doSingleDiscovery(this.lines[index], index, true);
      } else if (found.status==='unavailable') {
        this.doSingleDiscovery(this.lines[index], index);
      }
    },

    checkLineIcon (index) {
      const found = find(this.discovered, { _ref: index });
      if (!found) {
        return 'marker';
      } else if(found.postid&&this.already.includes(found.postid)) {
        return 'admin-post state-success';
      } else if(found.postid) {
        return 'yes-alt state-success';
      } else if (found.status==='creatable') {
        return 'insert';
      } else if (found.status==='unavailable') {
        return 'warning state-warning';
      } else {
        return 'editor-help state-danger';
      }
    },

    renderFounded (index) {
      const found = find(this.discovered, { _ref: index });
      return found ? found.message : '';
    },

    addSearchedItem (item, close) {
      if (item&&item.id) {
        this.search = '';
        this.doAddConnection([{
          id: item.id,
        }]);
      }
      if(close) this.focusInput();
    },

    searchPageUp (event) {
      // event.preventDefault();
      if(!this.search||this.searchPage<2) return;
      this.doSearchDiscovery(this.search, this.searchPage-1);
      // return false;
    },

    searchPageDown (event) {
      // event.preventDefault();
      if(!this.search||!this.searchPageMore) return;
      this.doSearchDiscovery(this.search, this.searchPage+1);
      // return false;
    },

    searchDiscovery () {
      this.doSearchDiscovery(this.search, this.searchPage);
    },

    removeItem (item) {
      if (item&&item.id) {
        this.doRemoveConnection([{
          id: item.id,
        }]);
      }
    },

    openItem (item) {
      window.open(addQueryArgs(this.config.infolink, { post: item.id }));
    },

    columnTypeChanges (index, value) {
      if (value=='_deleteme') {
        this.rawColumns = remove(this.rawColumns, (rawColumn) => rawColumn!=index );
        this.lines = map(this.lines, (line) => omit(line, [ index ]) );
        unset(this.columnTypes, index);
      } else if (value === '_flipdate'){
        mapValues(this.lines, (line) => {
          if (has(line, index)) {
            line[index] = flipDateString(line[index]);
          }
          return line;
        });
        this.columnTypes[index] = '';
      }
    },

    selectAll (isSelected) {
      if (isSelected) {
        this.lines.map((item, index) => {
          this.selectedLines.push(index);
        } );
      } else {
        this.selectedLines = [];
      }
    },

    getDelimiter () {
      if('csv'===this.contentType)
        return ',';
      if('xls'===this.contentType)
        return '\t';
      return ',';
    },

    clickDiscovery () {
      this.doDiscovery();
    },

    clickClearAdded () {
      const data = [];

      each(this.discovered, (found) => {
        if (found.status!=='available') return;
        if (!this.already.includes(found.postid)) return;
        data.push(found._ref);
      });

      if (!data.length) return;

      // https://www.geeksforgeeks.org/how-to-remove-multiple-elements-from-array-in-javascript/
      for (let i = data.length - 1; i >= 0; i--) {
        this.lines.splice(data[i], 1);
      }

      this.selectedLines = []; // refkey messed up!
      this.discovered = []; // refkey messed up!
    },

    clickDeleteSelected () {
      for (let i = this.selectedLines.length - 1; i >= 0; i--) {
        this.lines.splice(this.selectedLines[i], 1);
      }

      this.selectedLines = []; // refkey messed up!
      this.discovered = []; // refkey messed up!
    },

    clickAddSelected () {
      const data = [];

      each(this.selectedLines, (selected) => {
        const found = find(this.discovered, { _ref: selected });
        if (!found) return;
        if (this.already.includes(found.postid)) return;

        data.push({
          id: found.postid,
          meta: mapKeys(clone(this.lines[selected]), (value, key, object) => {
            return has(this.columnTypes,key) && this.columnTypes[key]!=='_undefined' ? this.columnTypes[key] : key;
          } ),
        });
      });

      if(data.length) {
        this.doAddConnection(data);
      } else {
        this.message = this.i18n.novalid;
        this.state = 'wrong';
      }
    },

    clickAddSelected_OLD () {
      const data = [];

      each(this.selectedLines, (selected) => {

        if (!has(this.discovered, selected)) return;
        if (!this.discovered[selected].postid) return;
        if (this.already.includes(this.discovered[selected].postid)) return;

        data.push({
          id: this.discovered[selected].postid,
          meta: mapKeys(clone(this.lines[selected]), (value, key, object) => {
            return has(this.columnTypes,key) && this.columnTypes[key]!=='_undefined' ? this.columnTypes[key] : key;
          } ),
        });
      });

      if(data.length) {
        this.doAddConnection(data);
      } else {
        this.message = this.i18n.novalid;
        this.state = 'wrong';
      }
    },

    doCheckType () {
      if (this.content) {
        const firstLine = this.content.split(/\n/, 1)[0];
        if (firstLine.split(",").length > 1) {
          this.contentType = 'csv';
        } else if (firstLine.split(/\t/).length > 1) {
          this.contentType = 'xls';
        } else {
          this.contentType = 'raw';
        }
      }
    },

    doCheckData () {
      if (!this.content) {

        this.message = this.i18n.emptydata;
        this.state = 'wrong';
        this.spinner = false;

      } else if ('raw'===this.contentType) {

        this.lines = map(filter(toMultipleLines(this.content)), (line) => {
          return {
            _undefined: toSingleSpace(digitsArToEn(digitsFaToEn(line)))
          };
        });

        this.rawColumns = [ '_undefined' ];
        this.messageReset();

      } else if ('json'===this.contentType) {

        this.extractColumns();
        this.messageReset();

      } else {

        this.parseCSV();
      }
    },

    parseCSV () {
      try {
        this.lines = parse(this.content, {
          columns: true,
          bom: true,
          trim: true,
          skip_empty_lines: true,
          delimiter: this.getDelimiter(),
          cast: (value, context) => {
            if(!value) return value;
            return toSingleSpace(digitsArToEn(digitsFaToEn(value)));
          },
        });

      } catch (error) {
        console.log(error);
        this.message = error.message;
        this.state = 'wrong';
        return;
      }

      this.extractColumns();
      this.messageReset();
    },

    extractColumns () {
      const keys = [];

      each( this.lines, ( line, offset ) => {
        each( line, ( cell, key ) => {
          keys.push(key);
        });
      });

      this.rawColumns = uniq(keys);
    },

    clickPasteSupported () {
      this.clickClearAll();
      navigator.clipboard.readText()
        .then((clipText) => {
          this.content = clipText;
          this.doCheckType();
          this.doCheckData();
        });
    },

    clickUpload () {
      this.$refs.fileinput.click();
    },

    doSingleDiscovery (line, offset, insert) {
      const lines = [];
      let selected = clone(line);
      selected._ref = offset; // will passed back!
      lines.push(selected);

      this.fetchDiscovery(lines, insert ?? this.addNewOnDiscovery);
    },

    doDiscovery () {
      this.spinner = true;

      const lines = [];
      each(this.selectedLines, (selected) => {
        const line = mapKeys(clone(this.lines[selected]), (value, key, object) => {
          return has(this.columnTypes,key) && this.columnTypes[key]!=='_undefined' ? this.columnTypes[key] : key;
        } );
        line._ref = selected;
        lines.push(line);
      });

      if (!lines.length) {
        this.message = this.i18n.emptydata;
        this.state = 'wrong';
        this.spinner = false;

        return;
      }

      this.fetchDiscovery(lines, this.addNewOnDiscovery);
    },

    fetchDiscovery (lines, insert) {
      this.spinner = true;

      apiFetch({
          path: this.config.discovery + '/bulk',
          method: 'POST',
          data: {
            raw: lines,
            target: 'post',
            refkey: '_ref',
            // linked: this.config.linked,
            // columns: pickBy(this.columnTypes, (type) => type !== '_undefined' ), // WORKING: no need
            posttype: this.config.posttypes,
            insert: insert ?? false,
          }
        }).then((data) => {
          console.log(data);
          this.discovered = unionBy(this.discovered, data, '_ref');
          this.spinner = false;
          this.state = 'saved';
        }).catch((error) => {
          this.spinner = false;
          this.state = 'wrong';
          this.message = error.message;
        });
    },

    doSearchDiscovery (criteria, page) {
      this.searchSpinner = true;

      apiFetch({
          path: addQueryArgs( this.config.searchselect + '/query', {
            context: 'pairedimports',
            search: criteria,
            target: 'post',
            exclude: this.config.linked,
            posttype: this.config.posttypes,
            page: page || 1,
          } )
        }).then((data) => {
          this.searchDiscovered = data.results;
          this.searchPageMore = data.pagination.more;
          this.searchPage = page || 1;
          this.searchSpinner = false;
        }).catch((error) => {
          this.searchSpinner = false;
          this.state = 'wrong';
          this.message = error.message;
        });
    },

    doAddConnection (data) {
      this.searchSpinner = true;

      apiFetch({
          path: this.config.route + '/' + this.config.itemsfrom,
          method: 'POST',
          data: data,
        }).then((data) => {
          this.items = data;
          this.searchSpinner = false;
          this.state = 'initial';
          this.message = '';
          this.already = map(data, 'id');
          // this.discovered = []; // refkey messed up!
        }).catch((error) => {
          this.searchSpinner = false;
          this.state = 'wrong';
          this.message = error.message;
        });
    },

    doRemoveConnection (data) {
      this.searchSpinner = true;

      apiFetch({
          path: this.config.route + '/' + this.config.itemsfrom,
          method: 'DELETE',
          data: data,
        }).then((data) => {
          this.items = data;
          this.searchSpinner = false;
          this.already = map(data, 'id');
        }).catch((error) => {
          this.searchSpinner = false;
          this.state = 'wrong';
          this.message = error.message;
        });
    },

    doQuery () {
      this.searchSpinner = true;

      apiFetch({
        path: this.config.route + '/' + this.config.itemsfrom,
      }).then((data) => {
        this.items = data;
        this.searchSpinner = false;
        this.already = map(data, 'id');
        this.focusInput();
      }).catch((error) => {
        this.searchSpinner = false;
        this.state = 'wrong';
        this.message = error.message;
      });
    },

    readFile () {
      this.clickClearAll();
      this.spinner = true;

      const file = this.$refs.fileinput.files[0];

      if (file.name.includes('.txt')) {

        const reader = new FileReader();

        reader.onload = (res) => {
          this.content = res.target.result;
          this.contentType = 'raw';
          this.fileName = getName(file.name);
          this.doCheckData();
        };

        reader.onerror = (error) => {
          console.log(error);
          this.message = error.message;
          this.state = 'wrong';
          this.spinner = false;
        };

        reader.readAsText(file);

      } else if (file.name.includes('.xlsx')) {

        const reader = new FileReader();

        reader.onload = (res) => {

          // @REF: https://gist.github.com/qkreltms/564332f2460a5899a7573b8d7510b0d2
          const data = new Uint8Array(res.target.result);
          const book = XLSX.read(data, {type: 'array'});

          this.lines = XLSX.utils.sheet_to_json(book.Sheets[book.SheetNames[0]], { header: 0 });
          this.content = 'dummy'; // needed for `doCheckData()`
          this.contentType = 'json';
          this.fileName = getName(file.name) + ' (' + book.SheetNames[0] + ')';
          this.doCheckData();
        };

        reader.onerror = (error) => {
          console.log(error);
          this.message = error.message;
          this.state = 'wrong';
          this.spinner = false;
        };

        reader.readAsArrayBuffer(file);

      } else if (file.name.includes('.csv')) {

        const reader = new FileReader();

        reader.onload = (res) => {
          this.content = res.target.result;
          this.contentType = 'csv';
          this.fileName = getName(file.name);
          this.doCheckData();
        };

        reader.onerror = (error) => {
          console.log(error);
          this.message = error.message;
          this.state = 'wrong';
          this.spinner = false;
        };

        reader.readAsText(file);

      } else {

        const reader = new FileReader();

        this.message = this.i18n.unsupported;
        this.state = 'wrong';

        reader.onload = (res) => {
          console.log(res.target.result);
        };

        reader.onerror = (error) => {
          console.log(error);
          this.message = error.message;
          this.state = 'wrong';
          this.spinner = false;
        };

        reader.readAsText(file);
      }
    }
  },

  created () {
    this.debouncedDoQuery = debounce(this.doQuery, 500);
  },
  unmounted () {
    this.debouncedDoQuery.cancel();
  },
  mounted () {
    this.$nextTick(() => {
      this.doQuery();
    });

    navigator.permissions
      .query({name: 'clipboard-read'})
      .then((result) => {
        if (result.state === 'granted'||result.state === 'prompt') {
          this.supportClipboard = true;
        }
      });
  }
};
</script>
