import { InitApp } from '../helpers/main'

import AssignBusinessEmployeeOffcanvas from './components/business/AssignBusinessEmployeeOffcanvas.vue'
import BusinessFormOffcanvas from './components/business/BusinessFormOffcanvas.vue'
import BusinessGalleryOffcanvas from './components/business/BusinessGalleryOffcanvas.vue'
import ModuleOffcanvas from './components/module/ModuleOffcanvas.vue'
import ManageRoleForm from './components/role_permission/ManageRoleForm.vue'

import VueTelInput from 'vue3-tel-input'
import 'vue3-tel-input/dist/vue3-tel-input.css'

const app = InitApp()

app.use(VueTelInput)
app.component('assign-business-employee-offcanvas', AssignBusinessEmployeeOffcanvas)
app.component('business-form-offcanvas', BusinessFormOffcanvas)
app.component('business-gallery-offcanvas', BusinessGalleryOffcanvas)
app.component('module-form-offcanvas', ModuleOffcanvas)
app.component('manage-role-form', ManageRoleForm)

app.mount('[data-render="app"]');
