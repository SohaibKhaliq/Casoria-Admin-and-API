export const MODULE = 'business'
export const BRANCH_LIST = () => {
  return { path: `${MODULE}/index_list`, method: 'GET' }
}
export const EDIT_URL = (id) => {
  return { path: `${MODULE}/${id}/edit`, method: 'GET' }
}
export const STORE_URL = () => {
  return { path: `${MODULE}`, method: 'POST' }
}
export const UPDATE_URL = (id) => {
  return { path: `${MODULE}/${id}`, method: 'POST' }
}
export const GET_ASSIGN_URL = (id) => {
  return { path: `${MODULE}/assign/${id}`, method: 'GET' }
}
export const POST_ASSIGN_URL = (id) => {
  return { path: `${MODULE}/assign/${id}`, method: 'POST' }
}
export const EMPLOYEE_LIST = ({ role = '' }) => {
  return { path: `employees/employee_list?role=${role}`, method: 'GET' }
}

// Gallery Images
export const GET_GALLERY_URL = (id) => {
  return { path: `${MODULE}/gallery-images/${id}`, method: 'GET' }
}
export const POST_GALLERY_URL = (id) => {
  return { path: `${MODULE}/gallery-images/${id}`, method: 'POST' }
}

// Services
export const SERVICE_LIST = () => {
  return { path: `services/index_list`, method: 'GET' }
}

export const GET_URL = () => {
  return { path: `business-info`, method: 'GET' }
}

export const UPDATE_BUSINESS_SETTING = () => {
  return { path: `business/business-setting`, method: 'POST' }
}

export const COUNTRY_URL = () => {
  return { path: `country/index_list`, method: 'GET' }
}
export const STATE_URL = (id) => {
  return { path: `state/index_list?country_id=${id}`, method: 'GET' }
}
export const CITY_URL = (id) => {
  return { path: `city/index_list?state_id=${id}`, method: 'GET' }
}
export const IS_HOLIDAY = ({ business_id }) => {
  return {
    path: `holidays/is-holiday?business_id=${business_id}`,
    method: 'GET'
  }
}
