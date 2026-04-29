import { useEffect } from 'react'
import { createFileRoute, redirect, Outlet, useRouter } from '@tanstack/react-router'
import { useQueryClient } from '@tanstack/react-query'
import { authMeQueryOptions } from '@/features/auth/api'

export const Route = createFileRoute('/_authenticated')({
  beforeLoad: async ({ context: { queryClient } }) => {
    try {
      await queryClient.ensureQueryData(authMeQueryOptions)
    } catch {
      // eslint-disable-next-line @typescript-eslint/only-throw-error
      throw redirect({ to: '/login' })
    }
  },
  component: AuthenticatedLayout,
})

export function AuthenticatedLayout() {
  const queryClient = useQueryClient()
  const router = useRouter()

  useEffect(() => {
    const channel = new BroadcastChannel('auth')
    channel.onmessage = (event: MessageEvent<string>) => {
      if (event.data === 'logout') {
        queryClient.removeQueries({ queryKey: ['auth', 'me'] })
        void router.navigate({ to: '/login' })
      }
    }
    return () => channel.close()
  }, [queryClient, router])

  return <Outlet />
}
