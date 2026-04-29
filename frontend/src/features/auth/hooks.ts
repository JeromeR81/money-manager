import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useRouter } from '@tanstack/react-router'
import { login, logout, authMeQueryOptions } from './api'
import type { LoginCredentials } from './types'

export function useLogin() {
  const queryClient = useQueryClient()
  const router = useRouter()

  return useMutation({
    mutationFn: (credentials: LoginCredentials) => login(credentials),
    onSuccess: () => {
      queryClient.removeQueries({ queryKey: ['auth', 'me'] })
      void router.navigate({ to: '/' })
    },
  })
}

export function useLogout() {
  const queryClient = useQueryClient()
  const router = useRouter()

  return useMutation({
    mutationFn: logout,
    onSettled: () => {
      queryClient.removeQueries({ queryKey: ['auth', 'me'] })
      void router.navigate({ to: '/login' })
    },
  })
}

export function useCurrentUser() {
  return useQuery(authMeQueryOptions)
}
