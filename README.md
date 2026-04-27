# money-manager

Application personnelle de gestion financière.

**Stack :** PHP 8.5 / Symfony 7.4 · API Platform · PostgreSQL · React + Vite · TypeScript · TanStack · Tailwind CSS · JWT (cookies HttpOnly)

---

## Démarrage rapide

### Prérequis

- Docker Desktop ≥ 4.24 (Docker Compose v2.20+)
- `make` (inclus sur macOS et Linux)
- `openssl` disponible en ligne de commande

### Installation

```bash
make setup
```

`make setup` copie `.env.example` vers `.env` (si absent), génère les clés JWT RS256, puis démarre tous les services Docker. Si `.env` vient d'être créé, renseigne les mots de passe (remplace les `change_me`), puis relance `make setup`.

### Services accessibles en dev

| Service | URL | Credentials |
|---|---|---|
| API Backend | http://localhost:8080 | — |
| Frontend (Vite) | http://localhost:5173 | — |
| RabbitMQ Management | http://localhost:15672 | `RABBITMQ_USER` / `RABBITMQ_PASSWORD` |
| Mailpit (emails) | http://localhost:8025 | — |
| Kibana (logs) | http://localhost:5601 | — |

> **Kibana en production** : le port 5601 n'est pas exposé. Accès via tunnel SSH :
> `ssh -L 5601:localhost:5601 <serveur>`

---

## Structure du dépôt

```
backend/                    # Application Symfony
frontend/                   # Application React + Vite
devops/
├── docker/
│   ├── dev/                # Dockerfiles dev (Xdebug, hot reload)
│   ├── prod/               # Dockerfiles prod (optimisés)
│   └── config/             # Configurations Nginx, Filebeat, RabbitMQ
└── generate-jwt-keys.sh    # Génération des clés JWT RS256
docker-compose.yml          # Dev
docker-compose.prod.yml     # Prod
docs/                       # Documentation technique
```

---

## Documentation

- [Workflow des features](docs/workflow.md)
