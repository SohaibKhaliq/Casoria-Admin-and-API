<template>
  <form @submit.prevent="formSubmit">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="service-business-assign-form" aria-labelledby="form-offcanvasLabel">
      <div class="offcanvas-header border-bottom" v-if="service">
        <h6 class="m-0 h5">
          {{ $t('service.singular_title') }} : <span>{{ service.name }}</span>
        </h6>
      </div>

      <div class="offcanvas-body">
        <div class="form-group">
          <div class="d-grid">
            <div class="d-flex flex-column">
              <div class="form-group">
                <Multiselect v-model="assign_ids" placeholder="Select Business" :canClear="false" :value="assign_ids" v-bind="businesses" @select="selectBusiness" @deselect="removeBusiness" id="businesses_ids">
                  <template v-slot:multiplelabel="{ values }">
                    <div class="multiselect-multiple-label">{{ $t('business.select_business') }}</div>
                  </template>
                </Multiselect>
              </div>
            </div>
            <div class="list-group list-group-flush">
              <div v-for="(item, index) in selectedBusiness" :key="item" class="list-group-item">
                <div class="d-flex justify-between align-items-center flex-grow-1 gap-2 mt-2">
                  <span>{{ ++index }} - </span>
                  <div class="flex-grow-1">{{ item.name }}</div>
                  <button type="button" @click="removeBusiness(item.business_id)" class="btn btn-sm text-danger"><i class="fa-regular fa-trash-can"></i></button>
                </div>
                <div class="row mb-2">
                  <div class="d-flex justify-content-end align-items-center gap-2 col-6"><i class="fa-regular fa-clock"></i><input type="number" v-model="item.duration_min" class="form-control" /></div>
                  <div class="d-flex justify-content-end align-items-center gap-2 col-6">{{ CURRENCY_SYMBOL }}<input type="text" v-model="item.service_price" class="form-control" :validation="validationSchema.service_price" /></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="offcanvas-footer">
        <p class="text-center mb-0">
          <small> {{ $t('business.assign_business_to_service') }}</small>
        </p>
        <div class="d-grid gap-3 p-3">
          <button class="btn btn-primary d-block">
            <i class="fa-solid fa-floppy-disk"></i>
            {{ $t('messages.update') }}
          </button>
          <button class="btn btn-outline-primary d-block" type="button" data-bs-dismiss="offcanvas">
            <i class="fa-solid fa-angles-left"></i>
            {{ $t('messages.close') }}
          </button>
        </div>
      </div>
    </div>
  </form>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { POST_BRANCH_ASSIGN_URL, GET_BRANCH_ASSIGN_URL, EDIT_URL, BRANCH_LIST } from '../../constant/service'

import { useModuleId, useRequest } from '@/helpers/hooks/useCrudOpration'
import { buildMultiSelectObject } from '@/helpers/utilities'
import * as yup from 'yup'

// Request
const { listingRequest, getRequest, updateRequest } = useRequest()

// Vue Form Select START
// Select Option
const businesses = ref({
  mode: 'multiple',
  searchable: true,
  options: []
})
const CURRENCY_SYMBOL = ref(window.defaultCurrencySymbol)
const selectedBusiness = ref([])
// Vue Form Select END

// Form Values
const assign_ids = ref([])
const service = ref(null)
const serviceId = useModuleId(() => {
  getRequest({ url: GET_BRANCH_ASSIGN_URL, id: serviceId.value }).then((res) => {
    if (res.status && res.data) {
      selectedBusiness.value = res.data
      assign_ids.value = res.data.map((item) => item.business_id)
    }
  })
  getRequest({ url: EDIT_URL, id: serviceId.value }).then((res) => res.status && (service.value = res.data))
}, 'business_assign')

const businessList = ref([])
onMounted(() => {
  listingRequest({ url: BRANCH_LIST }).then((res) => {
    businessList.value = res
    businesses.value.options = buildMultiSelectObject(res, { value: 'id', label: 'name' })
  })
})

// Reload Datatable, SnackBar Message, Alert, Offcanvas Close
const errorMessages = ref([])
const reset_close_offcanvas = (res) => {
  if (res.status) {
    window.successSnackbar(res.message)
    bootstrap.Offcanvas.getInstance('#service-business-assign-form').hide()
    renderedDataTable.ajax.reload(null, false)
  } else {
    window.errorSnackbar(res.message)
    errorMessages.value = res.all_message
  }
}

const formSubmit = () => {
  const data = { businesses: [] }
  for (let index = 0; index < selectedBusiness.value.length; index++) {
    const element = selectedBusiness.value[index]
    data.businesses.push({
      business_id: element.business_id,
      service_id: element.service_id,
      service_price: element.service_price,
      duration_min: element.duration_min
    })
  }
  updateRequest({ url: POST_BRANCH_ASSIGN_URL, id: serviceId.value, body: data }).then((res) => reset_close_offcanvas(res))
}

const selectBusiness = (value) => {
  const business = businessList.value.find((business) => business.id === value)
  const newBusiness = {
    name: business.name,
    business_id: business.id,
    service_id: service.value.id,
    service_price: service.value.default_price,
    duration_min: service.value.duration_min
  }
  selectedBusiness.value = [...selectedBusiness.value, newBusiness]
}

const removeBusiness = (value) => {
  selectedBusiness.value = [...selectedBusiness.value.filter((business) => business.business_id !== value)]
  assign_ids.value = [...assign_ids.value.filter((id) => id !== value)]
}

const validationSchema = yup.object().shape({
  service_price: yup
    .number()
    .transform((value, originalValue) => (originalValue === '' ? null : parseFloat(originalValue)))
    .typeError('Please enter a valid number')
    .positive('Value must be positive')
    .max(999999.99, 'Exceeds the maximum allowed value')
    .required('Currency value is required')
})
</script>
