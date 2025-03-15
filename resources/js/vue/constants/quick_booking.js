export const SERVICE_LIST = ({ business_id, employee_id }) => { return { path: `api/quick-booking/services-list?business_id=${business_id}&employee_id=${employee_id}`, method: 'GET' } }
export const BRANCH_LIST = ({ employee_id, service_id, start_date_time }) => { return { path: `api/quick-booking/business-list?employee_id=${employee_id}&service_id=${service_id}&start_date_time=${start_date_time}`, method: 'GET' } }
export const EMPLOYEE_LIST = ({ business_id, service_id, start_date_time }) => { return { path: `api/quick-booking/employee-list?business_id=${business_id}&service_id=${service_id}&start_date_time=${start_date_time}`, method: 'GET' } }
export const SLOT_TIME_LIST = ({ business_id, date, service_id, employee_id, }) => { return { path: `api/quick-booking/slot-time-list?business_id=${business_id}&date=${date}&employee_id=${employee_id}&service_id=${service_id}`, method: 'GET' } }
export const STORE_URL = () => { return { path: `api/quick-booking/store`, method: 'POST' } }
export const HOLIDAY_SLOT_LIST = ({ business_id, date, service_id, employee_id }) => { return { path: `api/quick-booking/slot-date-list?business_id=${business_id}&date=${date}&employee_id=${employee_id}&service_id=${service_id}`, method: 'GET' } }

