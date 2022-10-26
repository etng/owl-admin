import {request} from '@umijs/max'

export async function base(options?: { [key: string]: any }) {
    return request('/admin/base', {
        method: 'GET',
        ...(options || {}),
    })
}

const get = (url: string, options?: any) => request(url, {method: 'GET', ...(options || {})})
// @ts-ignore
const post = (url: string, options?: any) => request(url, {method: 'POST', ...(options || {})})

export const adminService = {
    login: async (data: any) => post('/admin/login', {data}),
    queryCurrentUser: async () => get('/admin/current-user'),
    captcha: async (options?: any) => get('/admin/login/reload-captcha', options),
}
