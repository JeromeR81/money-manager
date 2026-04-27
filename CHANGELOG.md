# Changelog

## [Unreleased]

### Changed
- Nginx dev : port hôte changé de 80 à 8080 (issue #11) — compatibilité Docker rootless (`ip_unprivileged_port_start=1024`)
- Commandes CLAUDE.md restructurées autour des cibles `make` (issue #13)
- Flux TS-Infra : ajout d'une étape Documentaliste conditionnelle après Gate TSI2 (si impact sur commandes ou config utilisateur)

### Added
- Makefile à la racine avec raccourcis pour Docker, Symfony, Composer, npm et tests (issue #13) — `make help` liste toutes les cibles

### Added
- Stack backend initialisée (issue #6)
  - Symfony 7.4.8 LTS (`symfony/skeleton`)
  - API Platform 4.3.3 (`api-platform/symfony`, `api-platform/doctrine-orm`)
  - Doctrine ORM 3.6.3 + doctrine/doctrine-bundle 3.2.2 + doctrine/doctrine-migrations-bundle 4.0.0 (PostgreSQL 16)
  - LexikJWTAuthenticationBundle 3.2.0 (RS256, cookies HttpOnly à implémenter en US auth)
  - symfony/messenger (transport RabbitMQ via `MESSENGER_TRANSPORT_DSN`)
  - symfony/mailer (Mailpit en dev via `MAILER_DSN`)
  - nelmio/cors-bundle 2.6.1
  - API disponible sur `http://localhost:8080/api`, Swagger UI sur `http://localhost:8080/api/docs`
  - Correction Dockerfile PHP : `opcache` retiré de `docker-php-ext-install` (compilé statiquement en PHP 8.5), `xdebug.ini` renommé pour ne pas écraser le fichier généré par `docker-php-ext-enable`

- Infrastructure Docker complète (issue #3, PR #4)
  - Environnement dev : PHP 8.5+Xdebug, Nginx, Node/Vite, PostgreSQL, RabbitMQ, Mailpit, Elasticsearch, Kibana, Filebeat
  - Environnement prod : builds optimisés multi-stage, Elasticsearch sécurisé (`xpack.security.enabled=true`), Kibana sans port public
  - Filebeat avec `cap_drop: ALL` et `no-new-privileges` pour réduire la surface d'attaque
  - Script `devops/generate-jwt-keys.sh` pour la génération des clés RS256
  - Fichier `.env.example` avec toutes les variables requises
