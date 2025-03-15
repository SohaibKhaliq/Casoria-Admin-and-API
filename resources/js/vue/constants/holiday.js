export const MODULE = 'holidays'
export const LISTING_URL = ({ business_id }) => { return { path: `holidays/index_list?business_id=${business_id}`, method: 'GET' } }
export const STORE_URL = () => { return { path: `${MODULE}`, method: 'POST' } }
