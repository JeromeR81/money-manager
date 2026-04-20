# Changelog

## [Unreleased]

### Added
- Infrastructure Docker complète (issue #3, PR #4)
  - Environnement dev : PHP 8.5+Xdebug, Nginx, Node/Vite, PostgreSQL, RabbitMQ, Mailpit, Elasticsearch, Kibana, Filebeat
  - Environnement prod : builds optimisés multi-stage, Elasticsearch sécurisé (`xpack.security.enabled=true`), Kibana sans port public
  - Filebeat avec `cap_drop: ALL` et `no-new-privileges` pour réduire la surface d'attaque
  - Script `devops/generate-jwt-keys.sh` pour la génération des clés RS256
  - Fichier `.env.example` avec toutes les variables requises
