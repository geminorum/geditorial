import { createApp } from 'vue';

import editorialPlugin from '../../vue-plugins/editorial.v2';
// import VueEnterToTab from '../../vue-plugins/vue-enter-to-tab.v2';

import App from './App.vue';
import AppSpinner from './Components/AppSpinner.vue';
import AppMessage from './Components/AppMessage.vue';
import AppHint from './Components/AppHint.vue';
import IconButton from './Components/IconButton.vue';
import TextButton from './Components/TextButton.vue';
import CustomField from './Components/CustomField.vue';
// import SearchInput from './Components/SearchInput.vue';

const app = createApp(App);

app.config.globalProperties.appname = 'assignment-dock';
app.use(editorialPlugin, gEditorial._assignment || {});
// app.use(VueEnterToTab, true);

// app.component('SearchInput', SearchInput);
app.component('AppSpinner', AppSpinner);
app.component('AppMessage', AppMessage);
app.component('AppHint', AppHint);
app.component('IconButton', IconButton);
app.component('TextButton', TextButton);
app.component('CustomField', CustomField);

app.mount('#geditorial-app-assignment-dock');
