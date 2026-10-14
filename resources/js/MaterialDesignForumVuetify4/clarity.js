import Clarity from '@microsoft/clarity'
import { checkConsent } from './stores/cookiePreference'

const projectId = import.meta.env.VITE_CLARITY_PROJECT_ID

let clarityLoaded = false

export function initClarity() {
    if (!projectId || typeof window === 'undefined') {
        return
    }

    // // 仅在用户同意 analytics 和 marketing 两个类别后才初始化
    // if (!checkConsent('analytics') || !checkConsent('marketing')) {
    //     return
    // }

    // 仅在用户同意 analytics 类别后才初始化
    if (!checkConsent('analytics')) {
        return
    }

    if (clarityLoaded) {
        // 已加载过，仅恢复同意状态（用户重新同意时）
        try {
            Clarity.consent(true)
        } catch (error) {
            console.warn('Clarity consent failed:', error)
        }
        return
    }

    try {
        Clarity.init(projectId)
        Clarity.consent(true)
        clarityLoaded = true

        const envName = import.meta.env.MODE || 'production'
        Clarity.setTag('app_env', envName)
        Clarity.setTag('app_name', import.meta.env.VITE_APP_NAME || 'MaterialDesignForum')
    } catch (error) {
        console.warn('Clarity initialization failed:', error)
    }
}

export function stopClarity() {
    if (!clarityLoaded) {
        return
    }
    try {
        Clarity.consent(false)
    } catch (error) {
        console.warn('Clarity stop failed:', error)
    }
}

export function identifyClarityUser(user) {
    if (!projectId || !user || typeof window === 'undefined' || !clarityLoaded) {
        return
    }

    try {
        const userId = user.user_id ?? user.id ?? user.username
        if (!userId) {
            return
        }

        const friendlyName = user.username || user.email || user.display_name || 'user'
        Clarity.identify(String(userId), undefined, undefined, friendlyName)
        Clarity.setTag('user_group', user.user_group?.user_group_name || 'guest')
        Clarity.setTag('is_admin', Boolean(user.user_group?.is_admin))
    } catch (error) {
        console.warn('Clarity user identification failed:', error)
    }
}
