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

## Deployment

<!-- Document the production deployment process here (server setup, Docker, migrations, etc.). -->

## Agents

Each agent has a defined scope, toolset, and model. Agents do not overlap in write responsibilities.

| Agent | Model | Tools | Notes |
|---|---|---|---|
| **PO** | Sonnet | Read, Write, WebSearch, WebFetch | Specs and user stories only, no code access |
| **Architecte** | Opus | Read, Write, Edit, Glob, Grep, Bash, WebSearch, WebFetch | API contracts, data models, front/back consistency |
| **Backend Developer** | Sonnet | Read, Write, Edit, Glob, Grep, Bash | Symfony, API Platform, Doctrine |
| **Frontend Developer** | Sonnet | Read, Write, Edit, Glob, Grep, Bash | React, TypeScript, TanStack, Tailwind |
| **UI/UX Designer** | Sonnet | Read, Write, Glob, WebSearch, WebFetch | Design specs only, no Bash |
| **QA** | Sonnet | Read, Write, Edit, Glob, Grep, Bash | Test writing and execution |
| **DevOps** | Sonnet | Read, Write, Edit, Glob, Grep, Bash | Docker, CI/CD, infrastructure |
| **Security & Code Reviewer** | Opus | Read, Glob, Grep, WebSearch, WebFetch | Reviews only — no direct code modifications |
| **Documentaliste** | Haiku | Read, Write, Edit, Glob, Grep, WebSearch, WebFetch | API docs, technical and user documentation |
