<style lang="scss" scoped>
@import './Styles/App.scss';
</style>

<template>
  <div class="main-app-wrap grid-wrap">
    <table class="table table-striped table-bordered app-table">
      <thead>
        <tr>
          <th v-for="(field, key) in fields" :class="['field-'+key, { required: config.required.includes(key) }]">{{ field }}</th>
          <th class="actions">{{ $translate('actions') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in items" :key="item._id" :class="{ 'is-editing': editing == item._id }">
          <td v-for="(field, key) in fields" :title="field" :class="['field-'+key, cellClass(item[key], key)]">{{item[key]}}</td>
          <td class="actions">
            <GridButton @click="removeItem(item._id)" dashicon="remove" :title="i18n.remove" />
            <GridButton @click="infoItem(item._id)" dashicon="info" :title="i18n.info" />
            <GridButton @click="editItem(item._id)" :dashicon="editing == item._id ? 'dismiss' : 'edit'" :title="i18n.edit" :class="{ 'is-editing': editing == item._id }" />
          </td>
        </tr>
        <tr>
          <td v-for="(field, key) in fields" :class="['form', { 'is-not-valid': isNotValidInput(key) }]">
            <GridInput
              :field="key"
              :label="field"
              :value="form[key]"
              :class="['field-'+key, cellClass(form[key], key)]"
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
// import { verifyDateString, verifyYearString } from '../../js-utils/datetime.v1';
// import { checkMobileByLocale } from '../../js-utils/mobile.v1';

// https://github.com/WordPress/gutenberg/tree/trunk/packages/api-fetch
import apiFetch from '@wordpress/api-fetch'; // https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/
// import { EnterToTabMixin } from 'vue-enter-to-tab'; // https://github.com/ajomuch92/vue-enter-to-tab
import { verifyCardNumber, getBankNameFromCardNumber, isShebaValid, getShebaInfo } from "@persian-tools/persian-tools"; // https://github.com/persian-tools/persian-tools

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
      this.hidden = Object.fromEntries(Object.values(this.config.hidden).map(key => [key, '']));
      this.editing = 0;
    },

    messageReset() {
      this.message = this.i18n.message;
      this.state = 'initial';
    },

    // NOTE: better to be in `methods`: “You can pass a parameter to a computed
    // property in Vue, but if you need parameters there are most likely no
    // benefits of using a computed property function over a method.”
    // @REF: https://beginnersoftwaredeveloper.com/can-i-pass-a-parameter-to-a-computed-property-vue/
    cellClass(cell, offset) {
      if(!cell) {
        return '-empty';
      } else if (offset=='iban') {
        return 'fa-IR'===this.locale && isShebaValid(cell) ? '-is-valid' : '-is-not-valid';
      } else if (offset === 'card') {
        return 'fa-IR'===this.locale && verifyCardNumber(cell) ? '-is-valid' : '-is-not-valid';
      }

      return '-unknown';
    },

    updateInput(field, value) {
      this.form[field] = value;
    },

    isNotValidInput(field) {
      if(!field) return false;
      if(!this.form[field]||!this.form[field].trim()) return false;

      if (field === 'iban') {
        return ! isShebaValid(this.form[field]);
      } else if (field === 'card') {
        return ! verifyCardNumber(this.form[field]);
      }

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

      // TODO: check for duplicate rows

      if (this.hasValidInput()) {
        this.spinner = true;

        // const post = this.form;
        // post._id = this.editing;

        apiFetch({
          path: this.endpoint + '/query/' + this.config.linked,
          method: 'POST',
          data: Object.assign({}, this.form, this.hidden, { _id: this.editing } )
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
    this.debouncedDoQuery = debounce(this.doQuery, 500);

    if ('card' in this.fields) {
      this.$watch('form.card', (newValue) => {
        if(verifyCardNumber(newValue)) {
          // console.log(getBankNameFromCardNumber(newValue));
          if ('bankname' in this.fields && ! this.form.bankname) {
            this.form.bankname = getBankNameFromCardNumber(newValue);
          }
        }
      });
    }

    if ('iban' in this.fields) {
      this.$watch('form.iban', (newValue) => {
        if(isShebaValid(newValue)) {
          const info = getShebaInfo(newValue);
          // console.log(info);
          this.form.bank = info.nickname;

          if ('bankname' in this.fields && ! this.form.bankname) {
            this.form.bankname = info.persianName;
          }

          // WORKING: but incorrect process of the account number
          // if ('account' in this.fields && info.accountNumberAvailable) {
          //   this.form.account = info.formattedAccountNumber;
          // }
        }
      });
    }
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
