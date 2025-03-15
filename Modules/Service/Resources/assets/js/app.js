import { InitApp } from '@/helpers/main'

import ServiceFormOffcanvas from './components/ServiceFormOffcanvas.vue'
import GalleryFormOffcanvas from './components/GalleryFormOffcanvas.vue'
import AssignEmployeeFormOffCanvas from './components/assign/AssignEmployeeFormOffCanvas.vue'
import AssignBusinessFormOffCanvas from './components/assign/AssignBusinessFormOffCanvas.vue'


const app = InitApp()

app.component('service-form-offcanvas', ServiceFormOffcanvas)

// Assign Staff & Business Offcanvas
app.component('assign-employee-form-offcanvas', AssignEmployeeFormOffCanvas)
app.component('assign-business-form-offcanvas', AssignBusinessFormOffCanvas)

// Gallery Offcanvas
app.component('gallery-form-offcanvas', GalleryFormOffcanvas)



app.mount('[data-render="app"]');
