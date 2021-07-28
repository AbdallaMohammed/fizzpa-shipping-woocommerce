import Vue from 'vue'
import lodash from 'lodash'
import VueStash from 'vue-stash'
import VueAxios from 'vue-axios'
import VueToast from 'vue-toast-notification'
import VueLodash from 'vue-lodash'
import VModal from 'vue-js-modal'
import App from './components/App.vue'

import 'vue-toast-notification/dist/theme-sugar.css'
import '../scss/app.scss'

window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

Vue.use(VueToast)
Vue.use(VModal)
Vue.use(VueAxios, window.axios)
Vue.use(VueLodash, {
    name: 'custom',
    lodash: lodash
})
Vue.use(VueStash)

const app = new Vue({
    el: '#fizzpa-container',
    render: h => h(App)
})