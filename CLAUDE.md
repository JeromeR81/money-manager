# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Personal money manager application. Not intended for multi-user deployment.

**Stack:**
- Backend: PHP 8.5, Symfony 7.4 LTS, API Platform, Doctrine ORM, PostgreSQL
- Frontend: React + Vite, TypeScript, TanStack Router, TanStack Query, Tailwind CSS
- Auth: JWT via LexikJWTAuthenticationBundle, stored in HttpOnly cookies (never localStorage)

## Repository Structure

```
backend/          # Symfony application
frontend/         # React + Vite application
devops/
└── docker/
    ├── dev/      # Dockerfiles for development (includes Xdebug, hot reload)
    └── prod/     # Dockerfiles for production (optimized, no debug tools)
docker-compose.yml       # Development
docker-compose.prod.yml  # Production
```

## Commands

### Docker (dev)

```bash
docker compose up -d          # Start all services
docker compose down           # Stop all services
docker compose logs -f        # Follow logs
```

### Backend (Symfony)

```bash
docker compose exec php composer install
docker compose exec php bin/console cache:clear
docker compose exec php bin/console doctrine:migrations:migrate
docker compose exec php bin/console doctrine:migrations:diff   # Generate migration from entity changes
```

### Frontend

```bash
cd frontend
npm install
npm run dev        # Start Vite dev server
npm run build      # Production build
npm run lint       # ESLint
```

### Tests

```bash
# Backend
docker compose exec php bin/phpunit
docker compose exec php bin/phpunit tests/Unit/MyTest.php   # Single test file

# Frontend — unit/component
cd frontend
npm run test              # Vitest (watch mode)
npm run test -- --run     # Vitest (single run)

# Frontend — E2E
npm run test:e2e          # Playwright
npm run test:e2e -- --grep "test name"   # Single E2E test
```

## Architecture

### Backend

Symfony follows standard bundle structure. API Platform auto-generates REST endpoints from Doctrine entities annotated with `#[ApiResource]`. Business logic lives in services, not controllers. Migrations are managed by Doctrine Migrations.

JWT access tokens have a short TTL (15 min); refresh tokens handle renewal. Tokens are set as `HttpOnly`, `Secure`, `SameSite=Strict` cookies by the backend — never exposed to JavaScript.

### Frontend

TanStack Router handles client-side routing with full TypeScript type safety. TanStack Query manages all server state (fetching, caching, invalidation) from the API Platform backend. Components are organized by feature, not by type.

Tailwind is used for all styling — no separate CSS files unless strictly necessary.

### Docker

Development images include Xdebug and mount source directories as volumes. Production images are optimized builds with no dev dependencies. PostgreSQL data is persisted via a named Docker volume. PostgreSQL port is not exposed publicly.

## Environment Variables

<!-- Document required .env variables here (backend and frontend) once defined. -->

## Conventions

### Naming

<!-- Document entity, service, component, and file naming conventions here. -->

### Feature Structure

<!-- Document how a new feature is organized across backend (entity, service, API resource) and frontend (route, query, components). -->

### GitHub Issues

#### User Stories (US) — rédigées par le PO
- Titre : `#X-US- <titre>` où X est l'ID de l'issue GitHub
- Langue : français
- Format du corps : Gherkin (`Feature`, `Scenario`, `Given`, `When`, `Then`)

#### Technical Stories (TS) — rédigées par l'Architecte ou le DevOps
- Titre : `#X-TS- <titre>` où X est l'ID de l'issue GitHub
- Corps : liste de tâches (`- [ ] action`)
- Choix du rédacteur pour les TS transverses : décidé par le product owner

## Feature Workflow

```
PO
└── Rédige la user story + critères d'acceptance
    │
    ▼
Architecte
└── Définit le contrat API (endpoints, types partagés)
    └── Valide le modèle de données
    │
    ▼
┌──────────────────────┬──────────────────────┐
Backend Developer      UI/UX Designer
└── Entité + migration └── Maquettes
└── Service + tests    └── Specs design
└── API Resource       │
    │                  ▼
    │         Frontend Developer
    │         └── Routes + composants
    │         └── Queries (mockées puis réelles)
    │         └── Tests
    │                  │
    └──────────────────┘
                │
                ▼
    Security & Code Reviewer
        └── Revue sécurité + qualité
                │
                ▼
               QA
        └── Tests d'intégration + E2E
                │
                ▼
        Documentaliste
        └── Docs API + guides
                │
                ▼
            DevOps
        └── Déploiement
```

**Points clés :**
- Backend et UI/UX travaillent en parallèle — ils sont indépendants
- Frontend démarre dès que l'UI/UX a terminé, avec des données mockées
- Frontend branche sur la vraie API quand le Backend est prêt
- Security & Code Reviewer intervient avant QA — on corrige avant de valider
- QA valide sur du code déjà revu et sécurisé

## Git Workflow

Le projet utilise **GitHub Flow** — simple, adapté à un déploiement continu sans cycles de release formels.

### Branches

| Branche | Rôle |
|---|---|
| `main` | Toujours stable et déployable en production |
| `feature/X-US-<slug>` | Développement d'une User Story (X = ID issue) |
| `feature/X-TS-<slug>` | Développement d'une Technical Story (X = ID issue) |
| `hotfix/X-<slug>` | Correctif urgent depuis `main` (X = ID issue) |

### Règles

- `main` ne reçoit jamais de commit direct — uniquement via PR.
- Chaque branche correspond à une issue GitHub.
- Le nom de branche reprend le préfixe de l'issue (`US` ou `TS`) pour la traçabilité.
- Toute PR doit être validée par les agents concernés (Security & Code Reviewer, QA) avant merge.
- Le merge dans `main` déclenche le déploiement.

### Flux standard

```
main  ──●────────────────────────────●──▶ production
         \                          /
          feature/42-US-ajout-compte
                   ●──●──● (PR → review → merge)
```

### Flux hotfix

```
main  ──●──────────────────●──▶ production
         \                /
          hotfix/43-crash-login
```

### Tags — CalVer

Les déploiements sont taggés sur `main` au format `YYYY.MM.DD`, avec un suffixe numérique si plusieurs déploiements ont lieu le même jour.

```
2026.04.20       # premier déploiement du jour
2026.04.20-2     # second déploiement le même jour
```

```bash
git tag -a 2026.04.20 -m "Description du déploiement"
git push origin 2026.04.20
```

## Deployment

<!-- Document the production deployment process here (server setup, Docker, migrations, etc.). -->

## Rapports de validation sur les PR

Les agents ayant un rôle de validateur (**Security & Code Reviewer**, **QA**) doivent publier un rapport structuré en commentaire de PR.

### Structure minimale du rapport

```
## Rapport de validation — [Nom de l'agent] — [Date]

### 🔴 Bloquants
- ...

### 🟠 Majeurs
- ...

### 🟡 Mineurs
- ...

### ✅ Conclusion
[Refus / Approbation conditionnelle / Approbation]
```

### Règles

- **Bloquants** : empêchent le merge — doivent être résolus avant toute autre action.
- **Majeurs** : défauts significatifs (sécurité, logique, performance) — doivent être résolus dans la même PR.
- **Mineurs** : améliorations souhaitables — peuvent être traités dans une issue de suivi.
- Un feu vert (`✅ Approbation`) n'est possible qu'une fois tous les bloquants et majeurs résolus.
- La conclusion est une déclaration formelle, pas un acquiescement implicite.

## Règles d'honnêteté des agents

Ces règles s'appliquent à tous les agents sans exception.

- **Ne jamais déclarer une tâche terminée sans avoir exécuté la vérification correspondante** : tests lancés, audit réalisé, build vérifié — selon le rôle.
- **Signaler explicitement tout blocage** : si un agent ne peut pas accomplir une étape (outil manquant, dépendance non livrée, contrainte impossible à respecter), il le dit clairement plutôt que de sauter l'étape silencieusement.
- **Toute déviation du contrat de l'Architecte est signalée** : jamais adaptée silencieusement. L'Architecte valide avant que l'implémentation continue.
- **Un feu vert est une déclaration formelle** : le Security & Code Reviewer et le QA ne donnent pas leur accord par défaut ou par acquiescement. Leur validation est une phrase explicite.
- **Les hypothèses sont toujours déclarées** : si un agent fait une supposition faute d'information, il l'écrit noir sur blanc pour que les autres agents puissent la corriger.

## Agents

Each agent has a defined scope, toolset, and model. Agents do not overlap in write responsibilities.

Skills are installed in `.agents/skills/` and invoked via `/skill-name`.

| Agent | Model | Tools | Skills |
|---|---|---|---|
| **PO** | Sonnet | Read, Write, Bash, WebSearch, WebFetch | `/brainstorming`, `/writing-plans`, `/writing-skills` |
| **Architecte** | Opus | Read, Write, Edit, Glob, Grep, Bash, WebSearch, WebFetch | `/systematic-debugging`, `/writing-plans`, `/typescript-advanced-types`, `/subagent-driven-development`, `/grill-me` |
| **Backend Developer** | Sonnet | Read, Write, Edit, Glob, Grep, Bash | `/systematic-debugging`, `/test-driven-development`, `/optimize`, `/verification-before-completion` |
| **Frontend Developer** | Sonnet | Read, Write, Edit, Glob, Grep, Bash | `/vercel-react-best-practices`, `/frontend-design`, `/vercel-composition-patterns`, `/shadcn`, `/typescript-advanced-types`, `/tailwind-design-system`, `/test-driven-development` |
| **UI/UX Designer** | Sonnet | Read, Write, Glob, WebSearch, WebFetch | `/frontend-design`, `/vercel-composition-patterns`, `/ui-ux-pro-max`, `/canvas-design`, `/react-components`, `/tailwind-design-system` |
| **QA** | Sonnet | Read, Write, Edit, Glob, Grep, Bash | `/systematic-debugging`, `/test-driven-development`, `/webapp-testing`, `/verification-before-completion`, `/playwright-best-practices` |
| **DevOps** | Sonnet | Read, Write, Edit, Glob, Grep, Bash | `/github-actions-templates`, `/using-git-worktrees`, `/finishing-a-development-branch` |
| **Security & Code Reviewer** | Opus | Read, Glob, Grep, WebSearch, WebFetch | `/audit`, `/requesting-code-review`, `/code-review-excellence` |
| **Documentaliste** | Haiku | Read, Write, Edit, Glob, Grep, WebSearch, WebFetch | `/clarify`, `/distill`, `/writing-skills`, `/doc-coauthoring` |
