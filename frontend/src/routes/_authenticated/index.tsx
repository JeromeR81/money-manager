import { createFileRoute } from '@tanstack/react-router'
import { Button } from '@/components/ui/button'
import { useLogout } from '@/features/auth/hooks'

export const Route = createFileRoute('/_authenticated/')({
  component: HomePage,
})

export function HomePage() {
  const logout = useLogout()

  return (
    <main className="flex min-h-screen flex-col items-center justify-center gap-4">
      <h1 className="text-2xl font-semibold tracking-tight">Money Manager</h1>
      <Button variant="outline" onClick={() => logout.mutate()} disabled={logout.isPending}>
        Se déconnecter
      </Button>
    </main>
  )
}
