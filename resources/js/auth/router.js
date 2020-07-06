import C from './components.js';
import Vue from 'vue';
import VueRouter from 'vue-router'
Vue.use(VueRouter);

const router = new VueRouter
({
    routes: [
        {path: '/sign-in', component: C.signin, name: 'signin'},
        {path: '/sign-up', component: C.signup, name: 'signup'},
        {path: '/tel/verification/:user', component: C.telver, name: 'telver'},
    ]
})




export default router;
