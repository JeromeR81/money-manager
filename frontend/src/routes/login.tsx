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
    if (!(login.error instanceof HttpError)) {
      errorMessage = 'Erreur de connexion, réessayez'
    } else if (login.error.status === 429) {
      errorMessage = 'Trop de tentatives, réessayez dans un moment'
    } else {
      errorMessage = 'Identifiants invalides'
    }
  }

  return (
    <LoginPage
      onSubmit={login.mutate}
      isLoading={login.isPending}
      error={errorMessage}
    />
  )
}
