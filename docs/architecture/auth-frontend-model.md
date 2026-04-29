# Modèle de données et structure frontend — Authentification (US 18)

## Types TypeScript partagés

```typescript
// src/features/auth/types.ts

export interface LoginCredentials {
  email: string
  password: string
}

export interface AuthUser {
  email: string
}

export interface ApiError {
  message: string
}
```

Contraintes de validation (miroir des contraintes backend) :
- `email` : non vide, format email, max 180 caractères
- `password` : non vide, max 4096 caractères

---

## Structure de fichiers frontend

```
frontend/src/
  features/
    auth/
      api.ts          — appels fetch vers /api/auth/* (login, logout, me)
      hooks.ts        — mutations/queries TanStack Query (useLogin, useLogout, useCurrentUser)
      types.ts        — LoginCredentials, AuthUser, ApiError
      LoginPage.tsx   — page de connexion (route /login)
      LoginForm.tsx   — formulaire email/password avec états loading/error

  lib/
    api.ts            — API_URL + fetchApi() wrapper avec intercepteur 401→refresh

  routes/
    __root.tsx        — root route (inchangé sauf ajout queryClient dans context)
    login.tsx         — route publique /login
    _authenticated.tsx          — layout route protégé (beforeLoad → GET /me)
    _authenticated/
      index.tsx       — page d'accueil (ancienne routes/index.tsx déplacée ici)
```

---

## Architecture du client fetch (`lib/api.ts`)

Le wrapper `fetchApi` centralise la gestion du 401 et du refresh silencieux.

```typescript
// Contrat de l'interface — implémentation libre au Frontend Developer

async function fetchApi<T>(
  path: string,
  options?: RequestInit
): Promise<T>
```

**Comportement attendu :**

1. Appeler `fetch(API_URL + path, { credentials: 'include', ...options })`
2. Si réponse `401` :
   a. Tenter `POST /api/auth/refresh`
   b. Si refresh `200` → rejouer la requête originale une seule fois
   c. Si refresh `401` → invalider le cache TanStack Query sur `['auth', 'me']` et lancer un redirect vers `/login`
3. Si autre erreur → propager l'erreur normalement

**`credentials: 'include'`** obligatoire sur toutes les requêtes pour que les cookies HttpOnly soient envoyés.

**Guard anti-boucle :** le refresh ne se rejoue jamais lui-même (pas d'intercepteur sur `/api/auth/refresh`).

---

## Contexte routeur TanStack Router

Le `queryClient` est injecté dans le contexte du routeur pour que `beforeLoad` puisse l'utiliser sans couplage direct à l'instance globale.

```typescript
// src/main.tsx — modification du createRouter existant

const router = createRouter({
  routeTree,
  context: { queryClient },   // ← ajout
})

// Déclaration de module à mettre à jour
declare module '@tanstack/react-router' {
  interface Register {
    router: typeof router
  }
  interface RouterContext {
    queryClient: QueryClient
  }
}
```

---

## Stratégie de détection d'authentification

**Source de vérité :** TanStack Query avec la clé `['auth', 'me']`.

```typescript
// Contrat de la query — implémentation libre

const authMeQueryOptions = queryOptions({
  queryKey: ['auth', 'me'],
  queryFn: () => fetchApi<AuthUser>('/auth/me'),
  retry: false,           // ne pas retenter sur 401
  staleTime: Infinity,    // valide tant que la session est active
})
```

**`beforeLoad` sur la route `_authenticated` :**

```typescript
beforeLoad: async ({ context: { queryClient } }) => {
  try {
    await queryClient.ensureQueryData(authMeQueryOptions)
  } catch {
    throw redirect({ to: '/login' })
  }
}
```

**Sur logout :**
```typescript
queryClient.removeQueries({ queryKey: ['auth', 'me'] })
// + redirect vers /login
```

**Justification `staleTime: Infinity` :** la session est invalidée uniquement par un logout explicite ou une réponse 401 non rattrapable par le refresh. Un refetch systématique au focus ne fait pas sens ici.

---

## Flux d'authentification — vue d'ensemble

```
Accès route protégée
        │
        ▼
  beforeLoad
  ensureQueryData(['auth', 'me'])
        │
   ┌────┴────┐
 200 OK    401
   │         │
   ▼         ▼
 Accès    fetchApi intercepteur
 accordé      │
         ┌────┴────┐
       refresh   refresh
       200 OK    401
           │       │
           ▼       ▼
       Retry    invalidate cache
       /me      redirect /login
           │
           ▼
       Accès accordé
```

---

## Page de login — comportement attendu

| État | Affichage |
|------|-----------|
| Idle | Formulaire email + password + bouton "Se connecter" |
| Pending (mutation) | Bouton désactivé + indicateur de chargement |
| Erreur 401 | Message "Identifiants invalides" sous le formulaire |
| Erreur 429 | Message "Trop de tentatives, réessayez dans un moment" |
| Succès (200) | Redirect vers `/` (route protégée d'accueil) |

**Pas de stockage local :** aucun `localStorage`, `sessionStorage`, ni variable globale JS ne contient un état d'authentification.

---

## Endpoint backend manquant — rappel pour le Backend Developer

`GET /api/auth/me` doit être implémenté avant que le Frontend Developer commence.

Voir détail dans `docs/architecture/auth-frontend-api.md`.

---

## Dépendances entre agents

| Ordre | Agent | Tâche | Dépend de |
|-------|-------|-------|-----------|
| 1 | Backend Developer | Ajouter `GET /api/auth/me` | — |
| 2 ‖ 2 | UI/UX Designer | Maquette LoginPage + état erreur | — |
| 3 | Frontend Developer | Implémenter lib/api.ts + features/auth + routes | Backend Developer (endpoint /me) + UI/UX Designer (maquette) |
