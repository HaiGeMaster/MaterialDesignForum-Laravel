import Clarity from '@microsoft/clarity'

const projectId = import.meta.env.VITE_CLARITY_PROJECT_ID

export function initClarity() {
    if (!projectId || typeof window === 'undefined') {
        return
    }

    try {
        Clarity.init(projectId)
        Clarity.consent(true)

        const envName = import.meta.env.MODE || 'production'
        Clarity.setTag('app_env', envName)
        Clarity.setTag('app_name', import.meta.env.VITE_APP_NAME || 'MDForum')
    } catch (error) {
        console.warn('Clarity initialization failed:', error)
    }
}

export function identifyClarityUser(user) {
    if (!projectId || !user || typeof window === 'undefined') {
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
        Clarity.setTag('is_admin', Boolean(user.user_group?.ability_admin_login))
    } catch (error) {
        console.warn('Clarity user identification failed:', error)
    }
}
