# Backend — Symfony + API Platform

Application backend pour Money Manager — gestion financière personnelle.

## Stack

| Outil | Version | Rôle |
|---|---|---|
| **PHP** | 8.5 | Langage |
| **Symfony** | 7.4 LTS | Framework |
| **API Platform** | 4.x | API REST + Swagger |
| **Doctrine ORM** | 3.x | ORM PostgreSQL |
| **LexikJWTAuthenticationBundle** | 3.x | Auth JWT RS256 |

## Commandes (depuis la racine du projet)

```bash
make install        # Installer les dépendances Composer
make migrate        # Exécuter les migrations Doctrine
make diff           # Générer une migration depuis les entités
make cc             # Vider le cache Symfony
make test-back      # Lancer les tests PHPUnit
make lint-back      # phpstan niveau 6 + php-cs-fixer dry-run
make fix-back       # Corriger automatiquement le style PHP
```

## Utilisateurs de développement

Charger les fixtures : `make sf c="doctrine:fixtures:load --no-interaction"`

| Email | Mot de passe | Rôle |
|---|---|---|
| `user@money-manager.local` | `password` | `ROLE_USER` |
| `admin@money-manager.local` | `password` | `ROLE_ADMIN` |

> Ces credentials sont exclusivement destinés à l'environnement de développement local.

## Authentification

Les endpoints d'auth sont visibles dans Swagger UI : `http://localhost:8080/api/docs`

- `POST /api/auth/login` — retourne un cookie `BEARER` (HttpOnly, Secure, SameSite=Strict, TTL 15 min)
- `POST /api/auth/logout` — efface le cookie `BEARER`

Le token JWT n'est jamais exposé en JavaScript — il est lu automatiquement depuis le cookie sur les endpoints protégés.
