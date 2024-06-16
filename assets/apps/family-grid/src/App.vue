<style lang="scss" scoped>
@import './Styles/App.scss';
</style>

<template>
  <div class="main-app-wrap grid-wrap">
    <table class="table table-striped table-bordered app-table">
      <thead>
        <tr>
          <th v-for="(field, key) in fields" :class="[key, { required: config.required.includes(key) }]">{{ field }}</th>
          <th class="actions">{{ $translate('actions') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in items" :key="item._id" :class="{ 'is-editing': editing == item._id }">
          <td v-for="(field, key) in fields" :title="field" :class="[key, cellClass(item[key], key)]">{{item[key]}}</td>
          <td class="actions">
            <GridButton @click="removeItem(item._id)" dashicon="remove" :title="i18n.remove" />
            <GridPopper>
              <GridButton @click="infoItem(item._id)" dashicon="info" :title="i18n.info" />
              <template #content>
                <div>{{ infoContent(item._id) }}</div>
              </template>
            </GridPopper>
            <GridButton @click="editItem(item._id)" :dashicon="editing == item._id ? 'dismiss' : 'edit'" :title="i18n.edit" :class="{ 'is-editing': editing == item._id }" />
          </td>
        </tr>
        <tr>
          <td v-for="(field, key) in fields" :class="['form', { 'is-not-valid': isNotValidInput(key) }]">
            <GridInput
              :field="key"
              :label="field"
              :value="form[key]"
              :class="[key, cellClass(form[key], key)]"
              @change="event => updateInput(key, event.target.value)"
            />
          </td>
          <td class="actions">
            <GridButton @click="insertItem()" dashicon="insert" :title="i18n.insert" />
            <GridButton @click="infoItem(0)" dashicon="info" :title="i18n.info" />
            <GridSpinner :class="{ 'is-active': spinner }" :title="i18n.loading"></GridSpinner>
          </td>
        </tr>
      </tbody>
    </table>
    <GridMessage :class="state" :message="message"></GridMessage>
  </div>
</template>

<script>
import { debounce } from 'lodash-es';
import { EnterToTabMixin } from '../../vue-plugins/vue-enter-to-tab.v2'; // https://github.com/ajomuch92/vue-enter-to-tab
import { verifyDateString, verifyYearString } from '../../js-utils/datetime.v1';
import { checkMobileByLocale } from '../../js-utils/mobile.v1';

// https://github.com/WordPress/gutenberg/tree/trunk/packages/api-fetch
import apiFetch from '@wordpress/api-fetch'; // https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/
// import { addQueryArgs } from '@wordpress/url'; // https://github.com/WordPress/gutenberg/tree/trunk/packages/url
import { verifyIranianNationalId } from "@persian-tools/persian-tools"; // https://github.com/persian-tools/persian-tools

export default {
  mixins: [EnterToTabMixin],
  inject: ['endpoint', 'config', 'fields', 'i18n', 'locale'],
  data() {
    return {
      spinner: true,
      message: this.i18n.message,
      state: 'initial',
      editing: 0,
      items: [],
      hidden: {},
      form: {}
    }
  },
  // computed: {},
  methods: {

    formReset() {
      // https://stackoverflow.com/a/71344289
      this.form = Object.fromEntries(Object.keys(this.fields).map(key => [key, '']));
      this.editing = 0;
    },

    messageReset() {
      this.message = this.i18n.message;
      this.state = 'initial';
    },

    isDateField(field) {
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

    // NOTE: better to be in `methods`: “You can pass a parameter to a computed
    // property in Vue, but if you need parameters there are most likely no
    // benefits of using a computed property function over a method.”
    // @REF: https://beginnersoftwaredeveloper.com/can-i-pass-a-parameter-to-a-computed-property-vue/
    cellClass(cell, offset) {
      if(!cell) {

        return '-empty';

      } else if (this.isDateField(offset)) {

        return 'fa-IR'===this.locale && ( verifyDateString(cell) || verifyYearString(cell) ) ? '-is-valid' : '-is-not-valid';

      } else if (offset=='identity'||offset=='identifier') {

        return 'fa-IR'===this.locale && verifyIranianNationalId(cell) ? '-is-valid' : '-is-not-valid';

      } else if (offset=='mobile'||offset=='phone') {

        return checkMobileByLocale(cell, this.locale) ? '-is-valid' : '-is-not-valid';
      }

      return '-unknown';
    },

    updateInput(field, value) {
      this.form[field] = value;
    },

    isNotValidInput(field) {
      if(!field) return false;
      // console.log(field);
      if(!this.form[field].trim()) return false;

      // if (field === 'iban') {
      //   return ! isShebaValid(this.form[field]);
      // } else if (field === 'card') {
      //   return ! verifyCardNumber(this.form[field]);
      // }

      return false;
    },

    hasValidInput() {
      for (const required of this.config.required) {
        if (!this.form[required].trim()) {
          return false;
        }
      }
      return true;
    },

    doQuery() {
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

    insertItem () {
      if (this.hasValidInput()) {
        this.spinner = true;

        const post = this.form;
        post._id = this.editing;

        apiFetch({
          path: this.endpoint + '/query/' + this.config.linked,
          method: 'POST',
          data: post
        }).then((data) => {
          this.items = data;
          this.spinner = false;
          this.state = 'saved';
          this.message = this.editing ? this.i18n.edited : this.i18n.saved;
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
      console.log(id);
    },

    infoContent (id) {
      return '+'+id;
    },

    editItem (id) {
      this.spinner = true;
      if (this.editing === id) {
        this.formReset();
      } else {
        this.editing = id;
        for (const item of this.items) {
          if ( item._id === id ) {
            this.form = item;
            break;
          }
        }
      }
      this.spinner = false;
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
          this.messageReset();
        }).catch((error) => {
          this.spinner = false;
          this.state = 'wrong';
          this.message = error.message;
        });
    },
  },
  created() {
    // https://vuejs.org/guide/essentials/reactivity-fundamentals.html#stateful-methods
    // this.debouncedDoQuery = window.lodash.debounce(this.doQuery, 500);
    // this.debouncedDoQuery = _.debounce(this.doQuery, 500);
    this.debouncedDoQuery = debounce(this.doQuery, 500);
  },
  unmounted() {
    this.debouncedDoQuery.cancel();
  },
  mounted() {
    this.formReset();
    this.$nextTick(() => {
      // this.fetchLinked();
      // this.fetchStored();
      this.doQuery();
    });
  }
}
</script>
