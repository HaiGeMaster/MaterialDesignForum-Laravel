import "vuetify/styles";
import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
import "@mdi/font/css/materialdesignicons.css";

import {
  // pl,
  zhHans,
  en,
  zhHant,
  ru,
  fr,
  de,
  ja,
  ko,
} from 'vuetify/locale'

const vuetify = createVuetify({
  components,
  directives,
  icons: {
    defaultSet: "mdi",
  },
  locale: {
    locale: 'zhHans',
    fallback: 'en',
    messages: {
      zhHans,
      en,
      zhHant,
      ru,
      fr,
      de,
      ja,
      ko,
      // pl,
      // sv
    },
  },
  theme: {
    defaultTheme: "system",
    themes: {
        // testlight: {
        //   dark: false,
        //   colors: {
        //     // 黑白灰商务配色
        //     primary: '#1C1C1C', // 近黑，主按钮/强调
        //     secondary: '#424242', // 深灰
        //     accent: '#9E9E9E', // 中灰
        //     background: '#F5F5F5', // 页面浅灰底
        //     surface: '#FFFFFF', // 卡片纯白
        //     'surface-variant': '#EEEEEE', // 浅灰辅助面
        //     'on-surface-variant': '#424242',
        //     'on-primary': '#FFFFFF',
        //     'on-secondary': '#FFFFFF',
        //     'on-background': '#1C1C1C',
        //     'on-surface': '#1C1C1C',
        //     error: '#B71C1C', // 深红，仅用于错误
        //     info: '#37474F', // 蓝灰
        //     success: '#2E7D32', // 墨绿
        //     warning: '#F57F17', // 暗橙
        //   },
        // },
        // testdark: {
        //   dark: true,
        //   colors: {
        //     // 黑白灰商务配色（深色）
        //     primary: '#F5F5F5', // 近白，主按钮/强调（深色下反转为浅色以保证对比度）
        //     secondary: '#BDBDBD', // 浅灰
        //     accent: '#757575', // 中灰
        //     background: '#121212', // 页面深灰底
        //     surface: '#1E1E1E', // 卡片深灰
        //     'surface-variant': '#2C2C2C', // 浅灰辅助面
        //     'on-surface-variant': '#9E9E9E',
        //     'on-primary': '#1C1C1C',
        //     'on-secondary': '#1C1C1C',
        //     'on-background': '#F5F5F5',
        //     'on-surface': '#F5F5F5',
        //     error: '#EF5350', // 浅红，深底上更易辨认
        //     info: '#90A4AE', // 浅蓝灰
        //     success: '#66BB6A', // 浅墨绿
        //     warning: '#FFB74D', // 浅暗橙
        //   },
        // },
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
          warning: "#FB8C00"
        },
        variables: {
          "border-color": "#000000",
          "border-opacity": .12,
          "shadow-color": "#000000",
          "high-emphasis-opacity": .87,
          "medium-emphasis-opacity": .6,
          "disabled-opacity": .38,
          "idle-opacity": .04,
          "hover-opacity": .04,
          "focus-opacity": .12,
          "selected-opacity": .08,
          "activated-opacity": .12,
          "pressed-opacity": .12,
          "dragged-opacity": .08,
          "theme-kbd": "#EEEEEE",
          "theme-on-kbd": "#000000",
          "theme-code": "#F5F5F5",
          "theme-on-code": "#000000",
          "theme-on-dark": "#FFF",
          "theme-on-light": "#000",
          "elevation-overlay-color": "black",
          "elevation-overlay-opacity-step": "2%"
        }
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
          warning: "#FB8C00"
        },
        variables: {
          "border-color": "#FFFFFF",
          "border-opacity": .12,
          "shadow-color": "#000000",
          "high-emphasis-opacity": 1,
          "medium-emphasis-opacity": .7,
          "disabled-opacity": .5,
          "idle-opacity": .1,
          "hover-opacity": .04,
          "focus-opacity": .12,
          "selected-opacity": .08,
          "activated-opacity": .12,
          "pressed-opacity": .16,
          "dragged-opacity": .08,
          "theme-kbd": "#424242",
          "theme-on-kbd": "#FFFFFF",
          "theme-code": "#343434",
          "theme-on-code": "#CCCCCC",
          "theme-on-dark": "#FFF",
          "theme-on-light": "#000",
          "elevation-overlay-color": "white",
          "elevation-overlay-opacity-step": "2%"
        }
      },
      asphalt9: {
        dark: true,
        colors: {
          background: "#0a0e17",        // 深蓝黑背景（模拟夜赛）
          primary: "#ff3e00",           // 火焰橙（赛车经典色）
          surface: "#1a2238",           // 金属蓝灰（车身质感）
          info: "#00e5ff",              // 电子蓝（科技感）
          accent: "#ff00aa",             // 霓虹粉（速度线效果）
          success: "#39ff14",            // 荧光绿（氮气加速）
          "surface-light": "#2d3a5c",    // 浅金属蓝
          "surface-bright": "#4a5a8a",   // 高光蓝
          "surface-variant": "#ff3e00",  // 火焰橙变体
          "on-surface-variant": "#000",  // 深色文字
          "primary-darken-1": "#e03800", // 深火焰橙
          secondary: "#00e5ff",          // 电子蓝
          "secondary-darken-1": "#00b8d4",
          error: "#ff1744",              // 警报红
          warning: "#ffea00",            // 警示黄
          // 新增赛道元素色
          track: "#2a3a5a",              // 赛道灰
          nitro: "#39ff14",              // 氮气绿
          speedline: "#ff00aa"           // 速度线粉
        },
        variables: {
          "border-color": "#ff3e00",     // 火焰橙边框
          "border-opacity": 0.3,
          "high-emphasis-opacity": 0.9,
          "medium-emphasis-opacity": 0.7,
          "disabled-opacity": 0.3,
          "idle-opacity": 0.05,
          "hover-opacity": 0.1,
          "focus-opacity": 0.2,
          "selected-opacity": 0.25,
          "activated-opacity": 0.3,
          "pressed-opacity": 0.35,
          "dragged-opacity": 0.15,
          "theme-kbd": "#ff3e00",         // 键盘火焰橙
          "theme-on-kbd": "#000",
          "theme-code": "#1a2238",        // 代码金属蓝
          "theme-on-code": "#00e5ff",     // 代码电子蓝
          "theme-on-dark": "#FFF",
          // 新增动态效果变量
          "speed-blur": "0 0 10px #ff00aa",
          "nitro-glow": "0 0 15px #39ff14"
        }
      },
      blackguard: {
        dark: true,
        colors: {
          background: "#0f0c24",
          primary: "#e7810d",
          surface: "#1e184a",
          info: "#9c27b0",
          accent: "#FF4081",
          success: "#84b38a",
          "surface-light": "#362b89",
          "surface-bright": "#564f8a",
          "surface-variant": "#6458b4",
          "on-surface-variant": "#FFFFFF",
          "primary-darken-1": "#c56e0b",
          secondary: "#8A2BE2",
          "secondary-darken-1": "#7919cb",
          error: "#FF5555",
          warning: "#ffa726"
        },
        variables: {
          "border-color": "#FFFFFF",
          "border-opacity": .12,
          "high-emphasis-opacity": .87,
          "medium-emphasis-opacity": .6,
          "disabled-opacity": .38,
          "idle-opacity": .1,
          "hover-opacity": .08,
          "focus-opacity": .12,
          "selected-opacity": .12,
          "activated-opacity": .12,
          "pressed-opacity": .16,
          "dragged-opacity": .08,
          "theme-kbd": "#212529",
          "theme-on-kbd": "#FFFFFF",
          "theme-code": "#343434",
          "theme-on-code": "#CCCCCC",
          "theme-on-dark": "#FFF",
        }
      },
      polaris: {
        dark: true,
        colors: {
          background: "#1f0a0d",
          primary: "#972800",
          surface: "#29110e",
          info: "#e7810d",
          accent: "#b35000",
          success: "#486a2b",
          "surface-light": "#773421",
          "surface-bright": "#ffa752",
          "surface-variant": "#924d2c",
          "on-surface-variant": "#FFFFFF",
          "primary-darken-1": "#c33100",
          secondary: "#ac1900",
          "secondary-darken-1": "#8a1600",
          error: "#ff5555",
          warning: "#ffa726"
        },
        variables: {
          "border-color": "#ffffff",
          "border-opacity": .12,
          "high-emphasis-opacity": .87,
          "medium-emphasis-opacity": .6,
          "disabled-opacity": .38,
          "idle-opacity": .1,
          "hover-opacity": .08,
          "focus-opacity": .12,
          "selected-opacity": .12,
          "activated-opacity": .12,
          "pressed-opacity": .16,
          "dragged-opacity": .08,
          "theme-kbd": "#212529",
          "theme-on-kbd": "#ffffff",
          "theme-code": "#343434",
          "theme-on-code": "#cccccc",
          "theme-on-dark": "#FFF",
        }
      },
      nebula: {
        dark: true,
        colors: {
          background: "#0f172a",
          primary: "#3462e3",
          surface: "#1e293b",
          info: "#64748b",
          accent: "#0ea5e9",
          success: "#14b8a6",
          "surface-light": "#2e3d59",
          "surface-bright": "#87a7ff",
          "surface-variant": "#334155",
          "on-surface-variant": "#FFFFFF",
          "primary-darken-1": "#1c2d60",
          secondary: "#0284c7",
          "secondary-darken-1": "#0369a1",
          error: "#ef4444",
          warning: "#f97316"
        },
        variables: {
          "border-color": "#ffffff",
          "border-opacity": .2,
          "high-emphasis-opacity": .87,
          "medium-emphasis-opacity": .6,
          "disabled-opacity": .38,
          "idle-opacity": .1,
          "hover-opacity": .08,
          "focus-opacity": .12,
          "selected-opacity": .12,
          "activated-opacity": .12,
          "pressed-opacity": .16,
          "dragged-opacity": .08,
          "theme-kbd": "#1e40af",
          "theme-on-kbd": "#dbeafe",
          "theme-code": "#0f172a",
          "theme-on-code": "#93c5fd",
          "theme-on-dark": "#FFF",
        }
      },
      minecraft: {
        dark: true,
        colors: {
          background: "#1f1f1f",
          primary: "#6FAD3A",
          surface: "#2b2720",
          info: "#55A8D8",
          accent: "#55DDE0",
          success: "#79B84A",
          "surface-light": "#344331",
          "surface-bright": "#4A5B42",
          "surface-variant": "#465441",
          "on-surface-variant": "#E2E8DC",
          "primary-darken-1": "#54852D",
          secondary: "#D6B83F",
          "secondary-darken-1": "#AF912A",
          error: "#E05252",
          warning: "#E7A93B"
        },
        variables: {
          "border-color": "#E2E8DC",
          "border-opacity": .12,
          "high-emphasis-opacity": .87,
          "medium-emphasis-opacity": .6,
          "disabled-opacity": .38,
          "idle-opacity": .1,
          "hover-opacity": .08,
          "focus-opacity": .12,
          "selected-opacity": .12,
          "activated-opacity": .12,
          "pressed-opacity": .16,
          "dragged-opacity": .08,
          "theme-kbd": "#344331",
          "theme-on-kbd": "#E2E8DC",
          "theme-code": "#171D16",
          "theme-on-code": "#79B84A",
          "theme-on-dark": "#FFF"
        }
      },
      odyssey: {
        dark: false,
        colors: {
          background: "#fffbe6",
          primary: "#356859",
          surface: "#fffbe6",
          info: "#37966f",
          accent: "#b9e4c9",
          success: "#5bb49a",
          "surface-light": "#95b3a0",
          "surface-bright": "#ffffff",
          "surface-variant": "#37966f",
          "primary-darken-1": "#00CC00",
          secondary: "#FD5523",
          "secondary-darken-1": "#CC3700",
          error: "#FF5555",
          warning: "#ecb85b"
        },
        variables: {
          "border-color": "#000000",
          "border-opacity": .24,
          "high-emphasis-opacity": 1,
          "medium-emphasis-opacity": .87,
          "disabled-opacity": .5,
          "idle-opacity": .1,
          "hover-opacity": .2,
          "focus-opacity": .3,
          "selected-opacity": .3,
          "activated-opacity": .3,
          "pressed-opacity": .4,
          "dragged-opacity": .2,
          "theme-kbd": "#000000",
          "theme-on-kbd": "#ffffff",
          "theme-code": "#000000",
          "theme-on-code": "#ffffff",
          "theme-on-dark": "#FFF",
        }
      },
      highContrast: {
        dark: true,
        colors: {
          background: "#000000",
          surface: "#121212",
          primary: "#ffD700",
          "primary-darken-1": "#e6c300",
          secondary: "#00ccdd",
          "secondary-darken-1": "#00e600",
          accent: "#ff00ff",
          info: "#31aaff",
          warning: "#ffaa00",
          error: "#ff0000",
          success: "#00ff00",
          "on-surface-variant": "#ffffff",
          "surface-light": "#222222",
          "surface-bright": "#000000",
          "surface-variant": "#333333"
        },
        variables: {
          "border-color": "#ffffff",
          "border-opacity": .87,
          "high-emphasis-opacity": 1,
          "medium-emphasis-opacity": .87,
          "disabled-opacity": .5,
          "idle-opacity": .1,
          "hover-opacity": .2,
          "focus-opacity": .3,
          "selected-opacity": .3,
          "activated-opacity": .3,
          "pressed-opacity": .4,
          "dragged-opacity": .2,
          "theme-kbd": "#000000",
          "theme-on-kbd": "#ffffff",
          "theme-code": "#000000",
          "theme-on-code": "#ffffff",
          "theme-on-dark": "#FFF",
        }
      }
    },
  },
});

export default vuetify;

/**
 * 内置主题元数据列表。
 * 用于前台主题切换对话框 & 后台主题管理。
 * name 与 vuetify theme key 一一对应。
 */
export const BUILTIN_THEMES = [
  { name: 'light', dark: false, icon: 'mdi-white-balance-sunny', label: 'Light' },
  { name: 'dark', dark: true, icon: 'mdi-weather-night', label: 'Dark' },
  { name: 'asphalt9', dark: true, icon: 'mdi-flag-checkered', label: 'Asphalt 9' },
  { name: 'blackguard', dark: true, icon: 'mdi-shield-sword', label: 'Blackguard' },
  { name: 'polaris', dark: true, icon: 'mdi-star-four-points', label: 'Polaris' },
  { name: 'nebula', dark: true, icon: 'mdi-rocket-launch', label: 'Nebula' },
  { name: 'minecraft', dark: true, icon: 'mdi-cube-outline', label: 'Minecraft' },
  { name: 'odyssey', dark: false, icon: 'mdi-compass', label: 'Odyssey' },
  { name: 'highContrast', dark: true, icon: 'mdi-eye', label: 'High Contrast' },
];
