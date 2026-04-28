# CLAUDE.md

Ce fichier fournit des instructions à Claude Code (claude.ai/code) pour travailler sur ce dépôt.

## Présentation du projet

Application personnelle de gestion financière. Non destinée à un déploiement multi-utilisateurs.

**Stack :**
- Backend : PHP 8.5, Symfony 7.4 LTS, API Platform, Doctrine ORM, PostgreSQL
- Frontend : React + Vite, TypeScript, TanStack Router, TanStack Query, Tailwind CSS
- Auth : JWT via LexikJWTAuthenticationBundle, stocké en cookies HttpOnly (jamais en localStorage)

## Structure du dépôt

```
backend/                    # Application Symfony
frontend/                   # Application React + Vite
devops/
├── docker/
│   ├── dev/                # Dockerfiles dev (Xdebug, hot reload)
│   ├── prod/               # Dockerfiles prod (optimisés, sans outils de dev)
│   └── config/             # Configurations Filebeat, RabbitMQ
└── generate-jwt-keys.sh    # Génération des clés JWT RS256
docker-compose.yml          # Développement
docker-compose.prod.yml     # Production
```

## Commandes

> Un `Makefile` est disponible à la racine — `make help` liste toutes les cibles disponibles.

### Setup initial

```bash
make setup   # Copie .env.example → .env (si absent), génère les clés JWT, démarre Docker
```

Si `.env` n'existe pas, `make setup` le crée et s'arrête — renseigne les variables, puis relance.

### Docker (dev)

```bash
make up       # Démarrer tous les services
make down     # Arrêter tous les services
make logs     # Suivre les logs (Ctrl+C pour quitter)
make restart  # Redémarrer tous les services
make ps       # État des conteneurs
make build    # Rebuild les images sans cache
```

### Services dev

| Service | URL |
|---|---|
| API Backend | http://localhost:8080 |
| Frontend Vite | http://localhost:5173 |
| RabbitMQ Management | http://localhost:15672 |
| Mailpit | http://localhost:8025 |
| Kibana | http://localhost:5601 |

> En production, Kibana n'est pas exposé publiquement. Accès : `ssh -L 5601:localhost:5601 <serveur>`

### Backend (Symfony)

```bash
make install                          # Installer les dépendances Composer
make cc                               # Vider le cache Symfony
make migrate                          # Exécuter les migrations Doctrine
make diff                             # Générer une migration depuis les entités
make sf c="about"                     # Commande console libre
make require p="vendor/package"       # Ajouter une dépendance Composer
make require-dev p="vendor/package"   # Ajouter une dépendance dev
```

### Frontend

```bash
make npm-install                  # Installer les dépendances npm (conteneur)
make lint                         # Lancer ESLint
make npm-add p="package"          # Ajouter une dépendance npm
make npm-add-dev p="package"      # Ajouter une dépendance npm dev
```

### Tests

```bash
make test           # Tous les tests (backend + frontend)
make test-back      # PHPUnit (backend)
make test-front     # Vitest (frontend, exécution unique)
make test-e2e       # Playwright (E2E)
```

Tests ciblés :

```bash
docker compose exec php bin/phpunit tests/Unit/MyTest.php              # PHPUnit fichier unique
docker compose exec node npm run test:e2e -- --grep "nom du test"      # Playwright test unique
```

## Architecture

### Backend

Symfony suit la structure standard des bundles. API Platform génère automatiquement les endpoints REST depuis les entités Doctrine annotées avec `#[ApiResource]`. La logique métier réside dans les services, jamais dans les contrôleurs. Les migrations sont gérées par Doctrine Migrations.

Les access tokens JWT ont une courte durée de vie (15 min) ; les refresh tokens gèrent le renouvellement. Les tokens sont définis en cookies `HttpOnly`, `Secure`, `SameSite=Strict` par le backend — jamais exposés à JavaScript.

### Frontend

TanStack Router gère le routage côté client avec une sécurité de types TypeScript complète. TanStack Query gère tout le state serveur (fetch, cache, invalidation) depuis le backend API Platform. Les composants sont organisés par feature, pas par type.

Tailwind est utilisé pour tout le style — pas de fichiers CSS séparés sauf nécessité absolue.

### Docker

Les images de développement incluent Xdebug et montent les répertoires sources en volumes. Les images de production sont des builds optimisés sans dépendances de dev. Les données PostgreSQL sont persistées via un volume Docker nommé. Le port PostgreSQL n'est jamais exposé publiquement.

## Conventions

### GitHub Issues

#### User Stories (US) — rédigées par le PO
- Titre : `#X-US : <titre>` où X est l'ID de l'issue GitHub
- Langue : français
- Format du corps : Gherkin (`Feature`, `Scenario`, `Given`, `When`, `Then`)

#### Technical Stories (TS) — rédigées par l'Architecte ou le DevOps
- Titre : `#X-TS : <titre>` où X est l'ID de l'issue GitHub
- Corps : liste de tâches (`- [ ] action`)
- Choix du rédacteur pour les TS transverses : décidé par le product owner

## Feature Workflow

Flux détaillé (gates, diagrammes, points clés) : [`docs/workflow.md`](docs/workflow.md)

### Résumé

Chaque ⏸ gate est un point d'arrêt — aucun agent ne démarre sans feu vert explicite de l'utilisateur.

**Flux US :** PO → ⏸1 → Architecte → ⏸2 → Backend Dev ‖ UI/UX → ⏸3 → Frontend Dev → Security Reviewer → ⏸4 → QA → ⏸5 → Documentaliste ‖ DevOps

**Flux TS-Technique :** Architecte → ⏸TS1 → Backend/Frontend Dev → Security Reviewer → ⏸TS2 → QA → ⏸TS3 → DevOps

**Flux TS-Infra :** DevOps → ⏸TSI1 → DevOps → Security Reviewer → ⏸TSI2 → Documentaliste *(si impact sur commandes ou config utilisateur)* → merge

**Flux TS-Transverse :** initiateur choisi par le PO, puis flux TS-Technique.

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

### Commentaires de PR vs. issues GitHub

Les problèmes identifiés pendant la revue sont signalés via **des commentaires de PR** lorsque la correction est attendue dans la branche courante — évitant des issues créées et fermées dans le même cycle (bruit inutile).

Une **issue GitHub** est ouverte uniquement pour les problèmes qui outrepassent la PR courante : vulnérabilité nécessitant une refonte, dette technique à traiter ultérieurement, comportement à vérifier en production.

## Règles d'honnêteté des agents

Ces règles s'appliquent à tous les agents sans exception.

- **Ne jamais déclarer une tâche terminée sans avoir exécuté la vérification correspondante** : tests lancés, audit réalisé, build vérifié — selon le rôle.
- **Signaler explicitement tout blocage** : si un agent ne peut pas accomplir une étape (outil manquant, dépendance non livrée, contrainte impossible à respecter), il le dit clairement plutôt que de sauter l'étape silencieusement.
- **Toute déviation du contrat de l'Architecte est signalée** : jamais adaptée silencieusement. L'Architecte valide avant que l'implémentation continue.
- **Un feu vert est une déclaration formelle** : le Security & Code Reviewer et le QA ne donnent pas leur accord par défaut ou par acquiescement. Leur validation est une phrase explicite.
- **Les hypothèses sont toujours déclarées** : si un agent fait une supposition faute d'information, il l'écrit noir sur blanc pour que les autres agents puissent la corriger.

## Agents

Each agent has a defined scope, toolset, and model. Agents do not overlap in write responsibilities.

Skills are installed by skills.sh in `.agents/skills/` (symlinked into `.claude/skills/`) and invoked via `/po`, `/architecte`, etc.

| Agent | Model | Tools | Skills |
|---|---|---|---|
| **PO** | Sonnet | Read, Write, Bash, WebSearch, WebFetch | `/brainstorming`, `/writing-plans`, `/writing-skills` |
| **Architecte** | Opus | Read, Write, Edit, Glob, Grep, Bash, WebSearch, WebFetch | `/systematic-debugging`, `/writing-plans`, `/typescript-advanced-types`, `/subagent-driven-development`, `/grill-me` |
| **Backend Developer** | Sonnet | Read, Write, Edit, Glob, Grep, Bash | `/systematic-debugging`, `/test-driven-development`, `/optimize`, `/verification-before-completion` |
| **Frontend Developer** | Sonnet | Read, Write, Edit, Glob, Grep, Bash | `/vercel-react-best-practices`, `/frontend-design`, `/vercel-composition-patterns`, `/shadcn`, `/typescript-advanced-types`, `/tailwind-design-system`, `/test-driven-development` |
| **UI/UX Designer** | Sonnet | Read, Write, Glob, WebSearch, WebFetch | `/frontend-design`, `/vercel-composition-patterns`, `/ui-ux-pro-max`, `/canvas-design`, `/react-components`, `/tailwind-design-system` |
| **QA** | Sonnet | Read, Write, Edit, Glob, Grep, Bash | `/systematic-debugging`, `/test-driven-development`, `/webapp-testing`, `/verification-before-completion`, `/playwright-best-practices` |
| **DevOps** | Sonnet | Read, Write, Edit, Glob, Grep, Bash | `/github-actions-templates`, `/using-git-worktrees`, `/finishing-a-development-branch` |
| **Security & Code Reviewer** | Opus | Read, Glob, Grep, Bash, WebSearch, WebFetch | `/audit`, `/systematic-debugging`, `/code-review-excellence` |
| **Documentaliste** | Haiku | Read, Write, Edit, Glob, Grep, WebSearch, WebFetch | `/clarify`, `/distill`, `/writing-skills`, `/doc-coauthoring`, `/verification-before-completion` |
