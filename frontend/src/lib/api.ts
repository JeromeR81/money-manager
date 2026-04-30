export const API_URL: string =
  (import.meta.env.VITE_API_URL as string | undefined) ?? 'http://localhost:8080/api'

export class HttpError extends Error {
  readonly status: number

  constructor(message: string, status: number) {
    super(message)
    this.name = 'HttpError'
    this.status = status
  }
}

// Shared refresh promise so concurrent 401s don't trigger multiple refresh calls.
let refreshPromise: Promise<boolean> | null = null

function tryRefresh(): Promise<boolean> {
  if (!refreshPromise) {
    refreshPromise = fetch(`${API_URL}/auth/refresh`, {
      method: 'POST',
      credentials: 'include',
    })
      .then((r) => r.ok)
      .finally(() => {
        refreshPromise = null
      })
  }
  return refreshPromise
}

// Paths that must never trigger a refresh attempt.
// /auth/me is included because it's the session-check endpoint: a 401 there means
// "not authenticated", not "token expired" — attempting a refresh would cause an
// infinite redirect loop when /login's beforeLoad calls ensureQueryData.
const SKIP_REFRESH_PATHS = ['/auth/login', '/auth/logout', '/auth/refresh', '/auth/me']

async function parseErrorBody(response: Response): Promise<HttpError> {
  const body = await response.json().catch(() => null) as { message?: string } | null
  return new HttpError(body?.message ?? `HTTP ${response.status}`, response.status)
}

export async function fetchApi<T>(path: string, options?: RequestInit): Promise<T> {
  const response = await fetch(`${API_URL}${path}`, {
    credentials: 'include',
    ...options,
  })

  if (response.status === 401 && !SKIP_REFRESH_PATHS.includes(path)) {
    const refreshed = await tryRefresh()

    if (refreshed) {
      const retry = await fetch(`${API_URL}${path}`, {
        credentials: 'include',
        ...options,
      })
      if (!retry.ok) throw await parseErrorBody(retry)
      return retry.json() as Promise<T>
    }

    window.location.href = '/login'
    throw new HttpError('Session expirée', 401)
  }

  if (!response.ok) throw await parseErrorBody(response)

  return response.json() as Promise<T>
}
