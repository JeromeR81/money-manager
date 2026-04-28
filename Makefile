.DEFAULT_GOAL := help

# ── Docker ────────────────────────────────────────────────────────────────────

up: ## Démarrer tous les services Docker
	docker compose up -d

down: ## Arrêter tous les services Docker
	docker compose down

restart: ## Redémarrer tous les services Docker
	docker compose restart

logs: ## Suivre les logs Docker (Ctrl+C pour quitter)
	docker compose logs -f

ps: ## État des conteneurs Docker
	docker compose ps

build: ## Rebuild les images Docker sans cache
	docker compose build --no-cache

# ── Symfony / Console ─────────────────────────────────────────────────────────

cc: ## Vider le cache Symfony
	docker compose exec php bin/console cache:clear

migrate: ## Exécuter les migrations Doctrine
	docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

diff: ## Générer une migration Doctrine depuis les entités
	docker compose exec php bin/console doctrine:migrations:diff

about: ## Afficher les infos Symfony / PHP
	docker compose exec php bin/console about

sf: ## Commande console libre  —  usage : make sf c="cache:clear"
	docker compose exec php bin/console $(c)

# ── Composer ──────────────────────────────────────────────────────────────────

install: ## Installer les dépendances Composer
	docker compose exec php composer install

update: ## Mettre à jour les dépendances Composer
	docker compose exec php composer update

require: ## Ajouter une dépendance  —  usage : make require p="vendor/package"
	docker compose exec php composer require $(p)

require-dev: ## Ajouter une dépendance dev  —  usage : make require-dev p="vendor/package"
	docker compose exec php composer require --dev $(p)

# ── Linting ───────────────────────────────────────────────────────────────────

lint: lint-back lint-front ## Lancer tous les linters (backend + frontend)

lint-back: lint-back-stan lint-back-cs ## Lancer les linters backend (phpstan + php-cs-fixer)

lint-back-stan: ## Analyser le code PHP avec phpstan (niveau 6)
	docker compose exec php bin/console cache:warmup --env=dev --quiet
	docker compose exec php vendor/bin/phpstan analyse --memory-limit=256M

lint-back-cs: ## Vérifier le style PHP avec php-cs-fixer (dry-run)
	docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff --config .php-cs-fixer.dist.php

fix-back: ## Corriger automatiquement le style PHP avec php-cs-fixer
	docker compose exec php vendor/bin/php-cs-fixer fix --config .php-cs-fixer.dist.php

lint-front: ## Lancer ESLint sur le frontend
	docker compose exec node npm run lint

# ── Frontend / npm (conteneur node) ──────────────────────────────────────────

npm-install: ## Installer les dépendances npm
	docker compose exec node npm install

npm-add: ## Ajouter une dépendance npm  —  usage : make npm-add p="package"
	docker compose exec node npm install $(p)

npm-add-dev: ## Ajouter une dépendance npm dev  —  usage : make npm-add-dev p="package"
	docker compose exec node npm install --save-dev $(p)

# ── Tests ─────────────────────────────────────────────────────────────────────

test: test-back test-front ## Lancer tous les tests (backend puis frontend)

test-back: ## Lancer les tests PHPUnit
	docker compose exec php bin/phpunit

test-front: ## Lancer les tests Vitest (exécution unique)
	docker compose exec node npm run test -- --run

test-e2e: ## Lancer les tests Playwright E2E
	docker compose exec node npm run test:e2e

# ── Setup ─────────────────────────────────────────────────────────────────────

setup: ## Initialiser l'environnement (première installation)
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo ""; \
		echo "  .env créé depuis .env.example."; \
		echo "  Renseigne les valeurs puis relance : make setup"; \
		echo ""; \
		exit 1; \
	fi
	bash devops/generate-jwt-keys.sh
	docker compose up -d

jwt: ## Générer les clés JWT RS256
	bash devops/generate-jwt-keys.sh

# ── Help ──────────────────────────────────────────────────────────────────────

help: ## Afficher cette aide
	@echo ""
	@echo "  money-manager — commandes disponibles"
	@echo ""
	@grep -E '^[a-zA-Z0-9_-]+:.*##' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*##"}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'
	@echo ""

.PHONY: up down restart logs ps build \
        cc migrate diff about sf \
        install update require require-dev \
        lint lint-back lint-back-stan lint-back-cs fix-back lint-front \
        npm-install npm-add npm-add-dev \
        test test-back test-front test-e2e \
        setup jwt help
