import { createRouter, createWebHashHistory } from 'vue-router'
import ProfileLayout from '@/Profile/ProfileLayout.vue'
import InformationPage from '@/Profile/SectionPages/InformationPage.vue'
import ChangePasswordPage from '@/Profile/SectionPages/ChangePasswordPage.vue'
import BusinessSettingPage from '@/Profile/SectionPages/BusinessSettingPage.vue'

const routes = [
  {
    path: '/',
    component: ProfileLayout,
    children: [
      {
        path: '',
        name: 'profile.info',
        component: InformationPage
      },
      {
        path: 'change-password',
        name: 'profile.change.password',
        component: ChangePasswordPage
      },
      {
        path: 'business-setting',
        name: 'profile.businessSetting',
        component: BusinessSettingPage
      }
    ]
  }
]

export const router = createRouter({
  linkActiveClass: '',
  linkExactActiveClass: 'active',
  history: createWebHashHistory(),
  routes
})

