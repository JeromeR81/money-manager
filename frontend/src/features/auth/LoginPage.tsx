import { LoginForm, type LoginCredentials } from './LoginForm'

interface LoginPageProps {
  onSubmit: (credentials: LoginCredentials) => void
  isLoading?: boolean
  error?: string | null
}

export function LoginPage({ onSubmit, isLoading = false, error = null }: LoginPageProps) {
  return (
    <main className="flex min-h-screen items-center justify-center bg-background px-4">
      <div className="w-full max-w-sm space-y-6">
        <div className="space-y-1 text-center">
          <h1 className="text-2xl font-semibold tracking-tight">Money Manager</h1>
          <p className="text-sm text-muted-foreground">Connectez-vous pour accéder à vos finances</p>
        </div>

        <div className="rounded-lg border bg-card p-6 shadow-sm">
          <LoginForm onSubmit={onSubmit} isLoading={isLoading} error={error} />
        </div>
      </div>
    </main>
  )
}
