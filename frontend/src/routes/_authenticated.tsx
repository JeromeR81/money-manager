import { createFileRoute, redirect, Outlet } from '@tanstack/react-router'
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
  component: () => <Outlet />,
})
