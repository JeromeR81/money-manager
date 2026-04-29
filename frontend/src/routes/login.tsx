import { createFileRoute } from '@tanstack/react-router'
import { LoginPage } from '@/features/auth/LoginPage'
import { useLogin } from '@/features/auth/hooks'
import { HttpError } from '@/lib/api'

export const Route = createFileRoute('/login')({
  component: LoginRoute,
})

export function LoginRoute() {
  const login = useLogin()

  let errorMessage: string | null = null
  if (login.isError) {
    errorMessage =
      login.error instanceof HttpError && login.error.status === 429
        ? 'Trop de tentatives, réessayez dans un moment'
        : 'Identifiants invalides'
  }

  return (
    <LoginPage
      onSubmit={login.mutate}
      isLoading={login.isPending}
      error={errorMessage}
    />
  )
}
