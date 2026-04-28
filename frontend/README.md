# Frontend — React + Vite + TypeScript

Application frontend pour Money Manager — gestion financière personnelle.

## Stack

| Outil | Version | Rôle |
|---|---|---|
| **React** | 19 | Framework composants |
| **Vite** | 8 | Bundler / dev server (HMR) |
| **TypeScript** | 6 | Typage strict (mode strict activé) |
| **TanStack Router** | 1 | Routage type-safe, file-based (`src/routes/`) |
| **TanStack Query** | 5 | Gestion du state serveur (fetch, cache, invalidation) |
| **Tailwind CSS** | 4 | Utilitaires de style (via `@tailwindcss/vite`) |
| **ShadCN/UI** | Latest | Composants UI accessibles (Button, etc.) |
| **Vitest** | 4 | Tests unitaires (jsdom + Testing Library) |
| **Playwright** | 1.59 | Tests E2E (infrastructure, binaires non disponibles — issue #24) |
| **ESLint** | 10 | Linting TypeScript/React (flat config) |

## Prérequis

- Docker + Docker Compose (le projet s'exécute dans le conteneur `node`)
- Voir `docker-compose.yml` — service `node` expose le port 5173

## Commandes

Toutes les commandes npm s'exécutent **dans le conteneur Docker**. Utilise `make` à la racine du projet :

```bash
# Installation
make npm-install              # Installer les dépendances npm (volume `node_modules`)

# Développement
make dev                       # Démarrer le dev server Vite (port 5173, HMR)
make lint                      # Lancer ESLint
make npm-add p="package"      # Ajouter une dépendance npm
make npm-add-dev p="package"  # Ajouter une dépendance de dev

# Build & tests
make build                     # Build de production (`tsc -b && vite build`)
make test-front               # Vitest (run unique)
make test-e2e                 # Playwright (E2E) — bloqué par issue #24
```

Ou directement via Docker :

```bash
docker compose run --rm node npm run dev
docker compose run --rm node npm run build
docker compose run --rm node npm run test -- --run
docker compose run --rm node npm run lint
```

## Développement

### Dev Server

```bash
make dev
```

Accessible sur `http://localhost:5173`. Hot Module Replacement activé.

### Variables d'environnement

Créer un fichier `.env` (ou copier `.env.example`) :

```
VITE_API_URL=http://localhost:8080/api
```

L'URL par défaut est déjà configurée comme fallback dans `src/lib/api.ts`. Optionnel en dev.

### Structure

```
frontend/
├── src/
│   ├── routes/              # TanStack Router — file-based routing
│   │   ├── __root.tsx       # Root route avec Outlet, TanStackRouterDevtools
│   │   └── index.tsx        # Page d'accueil (/)
│   ├── components/
│   │   └── ui/              # Composants ShadCN (Button, etc.)
│   ├── lib/
│   │   ├── api.ts           # API_URL helper
│   │   └── utils.ts         # cn() utility (clsx + tailwind-merge)
│   ├── test/
│   │   ├── setup.ts         # Configuration Vitest
│   │   └── smoke.test.ts    # Tests utilitaires
│   ├── main.tsx             # Entry point (React + Router + QueryClient)
│   ├── routeTree.gen.ts     # Généré par TanStack Router plugin — ne pas modifier
│   └── index.css            # Tailwind + variables CSS ShadCN (@theme inline)
├── tests/
│   └── e2e/
│       └── home.spec.ts     # Tests E2E Playwright
├── vite.config.ts           # Vite + TanStack Router plugin + Tailwind
├── playwright.config.ts     # Playwright (baseURL: http://localhost:5173)
├── eslint.config.js         # Flat config (ESLint 10, React + TypeScript)
├── tsconfig.json            # Config TypeScript (references app + node)
├── tsconfig.app.json        # Config app (strict, paths, jsdom)
├── tsconfig.node.json       # Config build tools
├── package.json             # Dépendances
└── .env.example             # Variables d'environnement exemple
```

### Routage (TanStack Router)

Routes déclarées en fichiers sous `src/routes/` :

```tsx
// src/routes/index.tsx
export const Route = createFileRoute('/')({
  component: HomePage,
})

function HomePage() { /* ... */ }
```

`routeTree.gen.ts` est généré automatiquement par le plugin Vite — **ne pas modifier**.

### State serveur (TanStack Query)

```tsx
import { useQuery } from '@tanstack/react-query'

function MyComponent() {
  const { data, isLoading } = useQuery({
    queryKey: ['users'],
    queryFn: async () => {
      const res = await fetch(`${API_URL}/users`)
      return res.json()
    },
  })
  // ...
}
```

QueryClient configuré dans `main.tsx` : `staleTime: 60_000` (60s).

### Styling

Tailwind CSS v4 avec variables CSS ShadCN intégrées :

```tsx
<div className="bg-background text-foreground">
  <Button variant="default">Cliquer</Button>
</div>
```

Les couleurs (`--primary`, `--background`, etc.) sont définies en oklch dans `src/index.css`.

### Tests

**Unitaires (Vitest)** :

```bash
make test-front    # ou docker compose run --rm node npm run test -- --run
```

Fichiers : `src/**/*.test.ts(x)`

**E2E (Playwright)** :

Infrastructure en place (voir `tests/e2e/home.spec.ts`), mais non exécutable actuellement — issue #24 (Dockerfile node Alpine sans binaires Playwright). À corriger via TS-Infra.

## Intégration backend

L'API est sur `http://localhost:8080/api` (Symfony + API Platform). Les requêtes réseau passent par TanStack Query :

```tsx
const { data: posts } = useQuery({
  queryKey: ['posts'],
  queryFn: async () => {
    const res = await fetch(`${API_URL}/posts`)
    if (!res.ok) throw new Error(res.statusText)
    return res.json()
  },
})
```

Les tokens JWT sont gérés côté backend (cookies HttpOnly) — jamais exposés en JavaScript.

## TypeScript strict

TypeScript 6 en mode strict (`noUnusedLocals`, `noUnusedParameters`, `strict: true`). Les composants et types doivent être correctement typés :

```tsx
interface ComponentProps {
  title: string
  count?: number
}

function MyComponent({ title, count = 0 }: ComponentProps) {
  return <div>{title} — {count}</div>
}
```

## Build production

```bash
make build    # ou docker compose run --rm node npm run build
```

Génère `frontend/dist/` :
- `index.html` (0.46 kB)
- CSS bundlé (13.26 kB / 3.27 kB gzip)
- JS bundlé (329 kB / 103 kB gzip, includes devtools)

Pour réduire la taille, les devtools TanStack Query/Router ne sont rendus qu'en mode DEV via `{import.meta.env.DEV && <Devtools />}`.

## Issues & limitations

- **Playwright E2E bloqué** (issue #24) — le Dockerfile `node:lts-alpine` n'inclut pas les binaires Playwright. À corriger via TS-Infra (DevOps).
- **1 warning ESLint** — `react-refresh/only-export-components` sur `src/routes/index.tsx` malgré `allowExportNames: ['Route']`. Config à vérifier.

## Ressources

- [React Docs](https://react.dev)
- [Vite Docs](https://vitejs.dev)
- [TanStack Router](https://tanstack.com/router/)
- [TanStack Query](https://tanstack.com/query/)
- [Tailwind CSS v4](https://tailwindcss.com/blog/tailwindcss-v4)
- [ShadCN/UI](https://ui.shadcn.com)
- [TypeScript](https://www.typescriptlang.org)
