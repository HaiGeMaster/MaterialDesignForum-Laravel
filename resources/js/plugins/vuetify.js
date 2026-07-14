import { createVuetify } from 'vuetify'
import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { aliases, mdi } from 'vuetify/iconsets/mdi'

const vuetify = createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#2196F3',
        //   primary: '#4c5e8b',
        //   primary: '#009688',
          secondary: '#E91E63',
          surface: '#FFFFFF',
          background: '#F5F5F5',
        },
      },
      dark: {
        colors: {
          primary: '#2196F3',
        //   primary: '#4c5e8b',
        //   primary: '#009688',
          secondary: '#E91E63',
          surface: '#1E1E2E',
          background: '#121212',
        },
      },
    },
  },
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: { mdi },
  },
})

export default vuetify
