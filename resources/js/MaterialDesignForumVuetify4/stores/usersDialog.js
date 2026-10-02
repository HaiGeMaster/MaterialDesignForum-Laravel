import { defineStore } from 'pinia'

/**
 * 用户浏览对话框 store
 *
 * 全局单例，供「用户资料卡 / 用户迷你卡 / 内容关注数」等入口打开同一个对话框，
 * 查看某对象的粉丝（followers）或某用户关注的人（followees）。
 */
export const useUsersDialogStore = defineStore('usersDialog', {
  state: () => ({
    model: false,
    // followers：关注了该对象的人；followees：该用户关注的人
    modes: 'followers',
    // 关注目标类型：user / topic / question / article
    type: 'user',
    id: null,
  }),

  actions: {
    open({ modes = 'followers', type = 'user', id }) {
      this.modes = modes
      this.type = type
      this.id = id
      this.model = true
    },
    close() {
      this.model = false
    },
  },
})
