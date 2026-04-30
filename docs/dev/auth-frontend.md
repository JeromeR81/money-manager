# Guide développeur — Authentification frontend (US 18)

## Vue d'ensemble

L'authentification repose sur des cookies HttpOnly gérés côté serveur. JavaScript n'a jamais accès aux tokens JWT. La session est vérifiée via `GET /api/auth/me`.

## Fichiers clés

| Fichier | Rôle |
|---|---|
| `src/lib/api.ts` | Wrapper `fetchApi` avec intercepteur 401→refresh |
| `src/features/auth/api.ts` | Appels API auth + `authMeQueryOptions` |
| `src/features/auth/hooks.ts` | Hooks TanStack Query : `useLogin`, `useLogout`, `useCurrentUser` |
| `src/features/auth/types.ts` | Types partagés : `LoginCredentials`, `AuthUser` |
| `src/features/auth/LoginPage.tsx` | Page de connexion (mise en page) |
| `src/features/auth/LoginForm.tsx` | Formulaire email/password avec états loading/error |
| `src/routes/login.tsx` | Route publique `/login` |
| `src/routes/_authenticated.tsx` | Layout route protégé (toutes les routes authentifiées) |

---

## Hooks disponibles

### `useLogin()`

Mutation TanStack Query. En cas de succès : vide le cache `['auth', 'me']` et navigue vers `/`.

### `useLogout()`

Mutation TanStack Query. Au `onSettled` (succès ou échec) :
1. Émet `'logout'` sur `BroadcastChannel('auth')` — propagation aux autres onglets
2. Vide le cache `['auth', 'me']`
3. Navigue vers `/login`

### `useCurrentUser()`

Query `GET /api/auth/me`. Retourne `{ email: string }`. Destinée à l'affichage de l'identité dans l'interface. Ne pas utiliser pour protéger des routes — utiliser `authMeQueryOptions` dans `beforeLoad`.

---

## Protection des routes

La route `_authenticated` protège toutes ses sous-routes via `beforeLoad` :

```typescript
beforeLoad: async ({ context: { queryClient } }) => {
  try {
    await queryClient.ensureQueryData(authMeQueryOptions)
  } catch {
    throw redirect({ to: '/login' })
  }
}
```

`authMeQueryOptions` est configurée avec `staleTime: Infinity` — le résultat est mis en cache pour toute la session. Un refetch ne se produit que si le cache est explicitement invalidé (logout) ou si une requête retourne 401 et que le refresh échoue.

Pour ajouter une nouvelle page protégée, placer le fichier dans `src/routes/_authenticated/`.

---

## Refresh silencieux (`fetchApi`)

Le wrapper `fetchApi` gère automatiquement les tokens expirés :

1. Si une réponse est `401` et que le chemin n'est **pas** dans `SKIP_REFRESH_PATHS`
2. → `POST /api/auth/refresh` est appelé (shared `refreshPromise` — un seul appel même si plusieurs requêtes sont en vol simultanément)
3. → Si refresh `200` : la requête originale est rejouée une fois
4. → Si refresh échoue : navigation dure vers `/login` via `window.location.href`

### SKIP_REFRESH_PATHS

```typescript
const SKIP_REFRESH_PATHS = ['/auth/login', '/auth/logout', '/auth/refresh', '/auth/me']
```

`/auth/me` est exclu car un 401 sur cet endpoint signifie "non authentifié" (pas "token expiré"). Tenter un refresh sur ce chemin provoquerait une boucle infinie : `beforeLoad /login` → `ensureQueryData('/auth/me')` → 401 → `tryRefresh()` → échec → `window.location.href = '/login'` → boucle.

### Navigation dure sur refresh échoué

`fetchApi` utilise `window.location.href = '/login'` plutôt que `router.navigate()`. Ce choix garantit que tous les états et requêtes en vol sont annulés par le rechargement complet — comportement le plus sûr face à une session expirée sans récupération possible.

---

## Logout multi-onglets (BroadcastChannel)

La déconnexion est propagée à tous les onglets ouverts via l'API `BroadcastChannel` :

- **Émetteur** (`hooks.ts → useLogout`) : poste `'logout'` sur le canal `'auth'`, puis ferme le canal immédiatement
- **Récepteur** (`_authenticated.tsx → AuthenticatedLayout`) : écoute le canal et redirige vers `/login` si `'logout'` est reçu ; ferme le canal au démontage du composant

---

## Gestion des erreurs de connexion

La route `/login` différencie les erreurs du backend :

| Situation | Message affiché |
|---|---|
| Erreur réseau ou non-`HttpError` | "Erreur de connexion, réessayez" |
| HTTP 429 (trop de tentatives) | "Trop de tentatives, réessayez dans un moment" |
| HTTP 401 (identifiants invalides) | "Identifiants invalides" |

Le champ mot de passe est vidé à chaque soumission du formulaire (succès ou erreur), avant l'appel à `onSubmit`.

---

## Tests

| Suite | Fichier | Couverture |
|---|---|---|
| Vitest | `src/features/auth/LoginForm.test.tsx` | Rendu, soumission, reset mot de passe, états loading/error (6 tests) |
| Playwright | `tests/e2e/auth.spec.ts` | Login réussi/échoué, protection de route, logout, redirection si authentifié (5 tests) |
| Playwright | `tests/e2e/home.spec.ts` | Accès page d'accueil avec session active (1 test) |

> Les tests Playwright ne sont pas exécutables dans l'environnement Docker actuel (image Alpine — voir issue #31).

---

## Contraintes de sécurité

- Les cookies `BEARER` et `REFRESH_TOKEN` sont `HttpOnly` — jamais accessibles en JavaScript
- Aucune information d'authentification dans `localStorage`, `sessionStorage`, ni état global React
- Le champ mot de passe est vidé à chaque soumission (avant l'appel réseau)
- `credentials: 'include'` est obligatoire sur toutes les requêtes `fetchApi` pour que les cookies soient transmis
