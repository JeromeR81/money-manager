# money-manager

Application personnelle de gestion financière.

**Stack :** PHP 8.5 / Symfony 7.4 · API Platform · PostgreSQL · React + Vite · TypeScript · TanStack · Tailwind CSS · JWT (cookies HttpOnly)

---

## Démarrage rapide

### Prérequis

- Docker Desktop ≥ 4.24 (Docker Compose v2.20+)
- `openssl` disponible en ligne de commande

### 1. Configuration

```bash
cp .env.example .env
# Renseigner les mots de passe dans .env (remplacer tous les "change_me")
```

### 2. Clés JWT

```bash
bash devops/generate-jwt-keys.sh
# Génère backend/config/jwt/private.pem et public.pem
# Écrit JWT_PASSPHRASE dans .env automatiquement
```

### 3. Démarrage

```bash
docker compose up -d
```

### Services accessibles en dev

| Service | URL | Credentials |
|---|---|---|
| API Backend | http://localhost | — |
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
