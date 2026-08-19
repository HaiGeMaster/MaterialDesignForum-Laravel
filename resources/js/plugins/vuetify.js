import "vuetify/styles";
import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
import "@mdi/font/css/materialdesignicons.css";

export default createVuetify({
  components,
  directives,
  icons: {
    defaultSet: "mdi",
  },
  theme: {
    defaultTheme: "system",
    themes: {
      //   light: {
      //     dark: false,
      //     colors: {
      //       // 黑白灰商务配色
      //       primary: '#1C1C1C', // 近黑，主按钮/强调
      //       secondary: '#424242', // 深灰
      //       accent: '#9E9E9E', // 中灰
      //       background: '#F5F5F5', // 页面浅灰底
      //       surface: '#FFFFFF', // 卡片纯白
      //       'surface-variant': '#EEEEEE', // 浅灰辅助面
      //       'on-surface-variant': '#424242',
      //       'on-primary': '#FFFFFF',
      //       'on-secondary': '#FFFFFF',
      //       'on-background': '#1C1C1C',
      //       'on-surface': '#1C1C1C',
      //       error: '#B71C1C', // 深红，仅用于错误
      //       info: '#37474F', // 蓝灰
      //       success: '#2E7D32', // 墨绿
      //       warning: '#F57F17', // 暗橙
      //     },
      //   },
      //   dark: {
      //     dark: true,
      //     colors: {
      //       // 黑白灰商务配色（深色）
      //       primary: '#F5F5F5', // 近白，主按钮/强调（深色下反转为浅色以保证对比度）
      //       secondary: '#BDBDBD', // 浅灰
      //       accent: '#757575', // 中灰
      //       background: '#121212', // 页面深灰底
      //       surface: '#1E1E1E', // 卡片深灰
      //       'surface-variant': '#2C2C2C', // 浅灰辅助面
      //       'on-surface-variant': '#9E9E9E',
      //       'on-primary': '#1C1C1C',
      //       'on-secondary': '#1C1C1C',
      //       'on-background': '#F5F5F5',
      //       'on-surface': '#F5F5F5',
      //       error: '#EF5350', // 浅红，深底上更易辨认
      //       info: '#90A4AE', // 浅蓝灰
      //       success: '#66BB6A', // 浅墨绿
      //       warning: '#FFB74D', // 浅暗橙
      //     },
      //   },
      light: {
        dark: false,
        colors: {
          background: "#FFFFFF",
          surface: "#FFFFFF",
          "surface-bright": "#FFFFFF",
          "surface-light": "#EEEEEE",
          "surface-variant": "#424242",
          "on-surface-variant": "#EEEEEE",
          primary: "#1867C0",
          "primary-darken-1": "#1F5592",
          secondary: "#48A9A6",
          "secondary-darken-1": "#018786",
          error: "#B00020",
          info: "#2196F3",
          success: "#4CAF50",
          warning: "#FB8C00",
        },
        variables: {
          "border-color": "#000000",
          "border-opacity": 0.12,
          "shadow-color": "#000000",
          "high-emphasis-opacity": 0.87,
          "medium-emphasis-opacity": 0.6,
          "disabled-opacity": 0.38,
          "idle-opacity": 0.04,
          "hover-opacity": 0.04,
          "focus-opacity": 0.12,
          "selected-opacity": 0.08,
          "activated-opacity": 0.12,
          "pressed-opacity": 0.12,
          "dragged-opacity": 0.08,
          "theme-kbd": "#EEEEEE",
          "theme-on-kbd": "#000000",
          "theme-code": "#F5F5F5",
          "theme-on-code": "#000000",
          "theme-on-dark": "#FFF",
          "theme-on-light": "#000",
          "elevation-overlay-color": "black",
          "elevation-overlay-opacity-step": "2%",
        },
      },
      dark: {
        dark: true,
        colors: {
          background: "#121212",
          surface: "#212121",
          "surface-bright": "#ccbfd6",
          "surface-light": "#424242",
          "surface-variant": "#c8c8c8",
          "on-surface-variant": "#000000",
          primary: "#2196F3",
          "primary-darken-1": "#277CC1",
          secondary: "#54B6B2",
          "secondary-darken-1": "#48A9A6",
          error: "#CF6679",
          info: "#2196F3",
          success: "#4CAF50",
          warning: "#FB8C00",
        },
        variables: {
          "border-color": "#FFFFFF",
          "border-opacity": 0.12,
          "shadow-color": "#000000",
          "high-emphasis-opacity": 1,
          "medium-emphasis-opacity": 0.7,
          "disabled-opacity": 0.5,
          "idle-opacity": 0.1,
          "hover-opacity": 0.04,
          "focus-opacity": 0.12,
          "selected-opacity": 0.08,
          "activated-opacity": 0.12,
          "pressed-opacity": 0.16,
          "dragged-opacity": 0.08,
          "theme-kbd": "#424242",
          "theme-on-kbd": "#FFFFFF",
          "theme-code": "#343434",
          "theme-on-code": "#CCCCCC",
          "theme-on-dark": "#FFF",
          "theme-on-light": "#000",
          "elevation-overlay-color": "white",
          "elevation-overlay-opacity-step": "2%",
        },
      },
    },
  },
});
