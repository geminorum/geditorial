import { createApp } from 'vue';

import editorialPlugin from '../../vue-plugins/editorial.v1';
import VueEnterToTab from '../../vue-plugins/vue-enter-to-tab.v2';
import Popper from 'vue3-popper'; // https://valgeirb.github.io/vue3-popper/

import App from './App.vue';
import ImportMessage from './Components/ImportMessage.vue';
import ImportSpinner from './Components/ImportSpinner.vue';
import GridPopper from './Components/GridPopper.vue';
import GridButton from './Components/GridButton.vue';
import ImportButton from './Components/ImportButton.vue';
import HeadTitle from './Components/HeadTitle.vue';
import HeadSelect from './Components/HeadSelect.vue';
import HeadMessage from './Components/HeadMessage.vue';
import SearchResults from './Components/SearchResults.vue';

const app = createApp(App);

app.use(editorialPlugin, gEditorial._importitems || {});
app.use(VueEnterToTab, true);

app.component('Popper', Popper);

app.component('ImportMessage', ImportMessage);
app.component('ImportSpinner', ImportSpinner);
app.component('HeadTitle', HeadTitle);
app.component('HeadSelect', HeadSelect);
app.component('HeadMessage', HeadMessage);
app.component('ImportButton', ImportButton);
app.component('GridPopper', GridPopper);
app.component('GridButton', GridButton);
app.component('SearchResults', SearchResults);

app.mount('#geditorial-app-import-items');
