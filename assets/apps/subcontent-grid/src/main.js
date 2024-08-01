import { createApp } from 'vue';

import editorialPlugin from '../../vue-plugins/editorial.v1';
import VueEnterToTab from '../../vue-plugins/vue-enter-to-tab.v2';
import Popper from 'vue3-popper'; // https://valgeirb.github.io/vue3-popper/

import App from './App.vue';
import GridMessage from './Components/GridMessage.vue';
import GridSpinner from './Components/GridSpinner.vue';
import GridInput from './Components/GridInput.vue';
import GridHidden from './Components/GridHidden.vue';
import GridButton from './Components/GridButton.vue';
import GridPopper from './Components/GridPopper.vue';
import GridInfo from './Components/GridInfo.vue';
import SearchResults from './Components/SearchResults.vue';

const app = createApp(App);

app.use(editorialPlugin, gEditorial._subcontent || {});
app.use(VueEnterToTab, true);

app.component('Popper', Popper);
app.component('GridMessage', GridMessage);
app.component('GridSpinner', GridSpinner);
app.component('GridInput', GridInput);
app.component('GridHidden', GridHidden);
app.component('GridButton', GridButton);
app.component('GridPopper', GridPopper);
app.component('GridInfo', GridInfo);
app.component('SearchResults', SearchResults);

app.mount('#geditorial-app-subcontent-grid');
