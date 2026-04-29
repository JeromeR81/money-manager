import { queryOptions } from '@tanstack/react-query'
import { fetchApi } from '@/lib/api'
import type { AuthUser, LoginCredentials } from './types'

interface ApiMessage {
  message: string
}

export function login(credentials: LoginCredentials): Promise<ApiMessage> {
  return fetchApi<ApiMessage>('/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(credentials),
  })
}

export function logout(): Promise<ApiMessage> {
  return fetchApi<ApiMessage>('/auth/logout', { method: 'POST' })
}

export const authMeQueryOptions = queryOptions({
  queryKey: ['auth', 'me'] as const,
  queryFn: () => fetchApi<AuthUser>('/auth/me'),
  retry: false,
  staleTime: Infinity,
})
