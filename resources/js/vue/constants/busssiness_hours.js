export const MODULE = 'bussinesshours'
export const LISTING_URL = ({ business_id }) => { return { path: `bussinesshours/index_list?business_id=${business_id}`, method: 'GET' } }
export const STORE_URL = () => { return { path: `${MODULE}`, method: 'POST' } }