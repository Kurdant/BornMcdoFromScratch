# Guide CI/CD Complet — Du code au déploiement

> Document de référence réutilisable sur tout projet PHP + Docker + GitHub Actions.  
> Couvre : tests → quality gates → pipeline CI/CD → déploiement multi-environnement.

---

## Table des matières

1. [Philosophie CI/CD](#1-philosophie-cicd)
2. [Structure des branches](#2-structure-des-branches)
3. [Écriture des tests (PHPUnit)](#3-écriture-des-tests-phpunit)
4. [Quality Gates](#4-quality-gates)
5. [Structure GitHub Actions](#5-structure-github-actions)
6. [Workflow : Tests sur PR](#6-workflow--tests-sur-pr)
7. [Workflow : CI/CD Dev](#7-workflow--cicd-dev)
8. [Workflow : Déploiement Prod](#8-workflow--déploiement-prod)
9. [Docker](#9-docker)
10. [Secrets GitHub à configurer](#10-secrets-github-à-configurer)
11. [Flux de travail quotidien](#11-flux-de-travail-quotidien)
12. [Checklist nouveau projet](#12-checklist-nouveau-projet)

---

## 1. Philosophie CI/CD

**CI = Continuous Integration** → chaque push déclenche automatiquement des vérifications (tests, linting).  
**CD = Continuous Deployment** → si la CI passe, le code est déployé automatiquement sur l'environnement cible.

### Règles fondamentales

- **Fail Fast** : détecter les erreurs le plus tôt possible (avant le déploiement)
- **Ne jamais déployer du code cassé** : les tests bloquent le déploiement
- **Rendre ça reproductible** : tout doit fonctionner de la même façon à chaque run
- **Pas de secrets dans le code** : utiliser les secrets GitHub

### Ce qu'un bon pipeline garantit

```
Code local → Tests OK → Qualité OK → Build Docker → Push Registry → Deploy → Health Check
     ↑                                                                              |
     └──────────────────── Rollback si échec ──────────────────────────────────────┘
```

---

## 2. Structure des branches

```
main   ← branche de référence stable (jamais poussé manuellement, merge auto depuis prod)
prod   ← déploiement production (merge de dev validé)
dev    ← développement actif (feature branches mergées ici)
```

### Règle de promotion

```
feature/* → dev → prod → main (automatique après health check)
```

- On ne push **jamais** directement sur `main`
- On ne push **jamais** directement sur `prod` sans que `dev` soit stable
- Chaque push sur `dev` → déploiement automatique sur serveur de dev
- Chaque push sur `prod` → déploiement automatique sur serveur de prod

---

## 3. Écriture des tests (PHPUnit)

### 3.1 Installation

```bash
composer require --dev phpunit/phpunit
```

### 3.2 Configuration `phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src/</directory>
        </include>
    </source>
</phpunit>
```

### 3.3 Structure des dossiers

```
tests/
├── Entities/          ← tests des modèles/entités
│   ├── ProduitTest.php
│   └── ClientTest.php
├── Business/          ← tests des règles métier
│   ├── PanierTest.php
│   └── CommandeTest.php
└── Integration/       ← tests avec base de données (optionnel)
```

### 3.4 Exemple de test

```php
<?php

declare(strict_types=1);

namespace Tests\Entities;

use PHPUnit\Framework\TestCase;
use App\Entities\Produit;

class ProduitTest extends TestCase
{
    public function testProduitAvecStockZeroEstIndisponible(): void
    {
        $produit = new Produit('Burger', 8.50, stock: 0);

        $this->assertFalse($produit->estDisponible());
    }

    public function testPrixDoitEtrePositif(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Produit('Burger', -1.00, stock: 10);
    }
}
```

### 3.5 Ce qu'il faut tester en priorité

| Priorité | Ce qu'on teste |
|---|---|
| 🔴 Critique | Règles métier (calculs, validations, contraintes) |
| 🟠 Important | Comportements des entités (constructeurs, getters) |
| 🟡 Utile | Cas limites (valeurs nulles, vides, extremes) |
| 🟢 Bonus | Intégration avec base de données |

### 3.6 Lancer les tests localement

```bash
./vendor/bin/phpunit tests/
./vendor/bin/phpunit tests/ --coverage-text   # avec rapport de couverture
./vendor/bin/phpunit tests/Entities/          # un dossier spécifique
```

---

## 4. Quality Gates

Les quality gates sont des vérifications automatiques qui s'assurent que le code est **propre** avant de le déployer.

### 4.1 Linting PHP (syntaxe)

Vérifie qu'il n'y a pas d'erreurs de syntaxe PHP.

```bash
# Installation : aucune, intégré à PHP
find src/ tests/ -name "*.php" -exec php -l {} \;
```

**Ce que ça détecte :** accolades manquantes, points-virgules oubliés, mots-clés mal écrits.

### 4.2 PHPStan (analyse statique)

Détecte les bugs **sans exécuter le code** : types incorrects, variables inexistantes, méthodes appelées sur `null`.

```bash
# Installation
composer require --dev phpstan/phpstan

# Lancer
./vendor/bin/phpstan analyse src/ tests/ --level=5
```

**Niveaux de 0 (permissif) à 9 (strict).** Commencer à 3-5, augmenter progressivement.

Exemple de ce que PHPStan détecte :
```php
function getClient(): ?Client { return null; }

// PHPStan alerte : appel de méthode sur null possible
$client->getNom();
```

**Fichier de config `phpstan.neon` :**
```neon
parameters:
    level: 5
    paths:
        - src
        - tests
    excludePaths:
        - vendor
```

### 4.3 PHP-CS-Fixer (formatage)

Vérifie (et corrige) que le code respecte un style uniforme (PSR-12).

```bash
# Installation
composer require --dev friendsofphp/php-cs-fixer

# Vérifier sans modifier
./vendor/bin/php-cs-fixer fix --dry-run --diff src/

# Corriger automatiquement
./vendor/bin/php-cs-fixer fix src/
```

**Fichier de config `.php-cs-fixer.php` :**
```php
<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'no_unused_imports' => true,
    ])
    ->setFinder($finder);
```

### 4.4 Ordre recommandé dans le pipeline

```
1. php -l        (rapide, ~2s)   → syntaxe
2. php-cs-fixer  (rapide, ~5s)   → formatage
3. phpstan       (moyen, ~30s)   → types et bugs
4. phpunit       (variable)      → tests fonctionnels
```

---

## 5. Structure GitHub Actions

Tous les workflows sont dans `.github/workflows/`.

### Fichiers recommandés

```
.github/workflows/
├── tests.yml        ← PHPUnit sur chaque Pull Request
├── dev-cicd.yml     ← CI/CD complet sur push dev
└── deploy.yml       ← Déploiement prod sur push prod
```

### Anatomie d'un workflow

```yaml
name: Nom affiché sur GitHub         # Nom du workflow

on:                                   # Déclencheurs
  push:
    branches: [dev]
  pull_request:
    branches: [dev, prod, main]

jobs:                                 # Liste des jobs
  mon-job:
    name: Description du job
    runs-on: ubuntu-latest            # Environnement (ubuntu-latest ou self-hosted)

    steps:
      - uses: actions/checkout@v4     # Checkout du code

      - name: Ma commande
        run: echo "Hello"
```

### Jobs en séquence (un dépend de l'autre)

```yaml
jobs:
  test:
    runs-on: ubuntu-latest
    steps: [...]

  deploy:
    needs: test          # ← deploy ne démarre que si test réussit
    runs-on: self-hosted
    steps: [...]
```

---

## 6. Workflow : Tests sur PR

**Fichier :** `.github/workflows/tests.yml`  
**Déclencheur :** chaque Pull Request vers `dev`, `prod`, ou `main`  
**But :** bloquer le merge si les tests échouent

```yaml
name: Tests PHPUnit - Pull Request

on:
  pull_request:
    branches: [dev, prod, main]

jobs:
  test:
    name: PHPUnit PHP 8.2
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: json, pdo, pdo_mysql
          coverage: none

      - name: Validate composer.json
        run: composer validate --no-check-lock --no-check-publish

      - name: Cache Composer
        uses: actions/cache@v4
        with:
          path: vendor
          key: ${{ runner.os }}-php-8.2-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-php-8.2-

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Lint PHP
        run: find src/ tests/ -name "*.php" -exec php -l {} \;

      - name: Run PHPUnit
        run: ./vendor/bin/phpunit tests/
```

---

## 7. Workflow : CI/CD Dev

**Fichier :** `.github/workflows/dev-cicd.yml`  
**Déclencheur :** push sur `dev`  
**But :** tester, builder l'image Docker, pousser au registry, déployer sur le serveur de dev

```yaml
name: CI/CD - Dev

on:
  push:
    branches: [dev]

jobs:
  test:
    name: PHPUnit Tests
    runs-on: ubuntu-latest
    # ⚠️ Retirer continue-on-error quand les tests sont stables
    # continue-on-error: true

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: json, pdo, pdo_mysql
          coverage: none

      - name: Cache Composer
        uses: actions/cache@v4
        with:
          path: vendor
          key: ${{ runner.os }}-php-8.2-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-php-8.2-

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run PHPUnit
        run: ./vendor/bin/phpunit tests/

  build-and-deploy:
    name: Build & Deploy
    needs: test
    # ⚠️ Retirer le if:always() quand les tests sont stables
    # if: always()
    runs-on: self-hosted   # runner installé sur le serveur de dev

    steps:
      - uses: actions/checkout@v4

      - name: Login to registry
        run: |
          echo "${{ secrets.REGISTRY_PASSWORD }}" | docker login MON_REGISTRY \
            -u "${{ secrets.REGISTRY_USER }}" --password-stdin

      - name: Build and push Docker image
        run: |
          docker build -t MON_REGISTRY/mon-app:dev .
          docker push MON_REGISTRY/mon-app:dev

      - name: Deploy with docker compose
        run: |
          docker compose pull
          docker compose up -d

      - name: Verify
        run: |
          sleep 10
          docker compose ps --filter status=running
```

---

## 8. Workflow : Déploiement Prod

**Fichier :** `.github/workflows/deploy.yml`  
**Déclencheur :** push sur `prod`  
**But :** retagger l'image `:dev` en `:prod`, déployer sur le serveur de prod, health check, merger dans `main`

```yaml
name: CI/CD - Deploy Prod

on:
  push:
    branches: [prod]

jobs:
  tag-prod-image:
    name: Tag :dev → :prod
    runs-on: self-hosted

    steps:
      - name: Login to registry
        run: |
          echo "${{ secrets.REGISTRY_PASSWORD }}" | docker login MON_REGISTRY \
            -u "${{ secrets.REGISTRY_USER }}" --password-stdin

      - name: Retag dev → prod
        run: |
          docker pull MON_REGISTRY/mon-app:dev
          docker tag MON_REGISTRY/mon-app:dev MON_REGISTRY/mon-app:prod
          docker push MON_REGISTRY/mon-app:prod

  deploy-prod:
    name: Deploy on prod server
    needs: tag-prod-image
    runs-on: ubuntu-latest

    steps:
      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1
        env:
          REGISTRY_USER: ${{ secrets.REGISTRY_USER }}
          REGISTRY_PASSWORD: ${{ secrets.REGISTRY_PASSWORD }}
        with:
          host: mon-serveur-prod.example.com
          username: deploy
          key: ${{ secrets.PROD_SSH_KEY }}
          envs: REGISTRY_USER,REGISTRY_PASSWORD
          script: |
            set -e
            cd /home/deploy/mon-app

            echo "$REGISTRY_PASSWORD" | docker login MON_REGISTRY \
              -u "$REGISTRY_USER" --password-stdin

            docker compose pull
            docker compose up -d

            sleep 15
            docker compose ps --filter status=running

  health-check:
    name: Health Check
    needs: deploy-prod
    runs-on: ubuntu-latest

    steps:
      - name: Wait
        run: sleep 10

      - name: Check API is up
        run: |
          STATUS=$(curl -o /dev/null -s -w "%{http_code}" \
            --max-time 30 \
            https://mon-api.example.com/api/health || echo "000")
          echo "HTTP status: $STATUS"
          if [ "$STATUS" != "200" ] && [ "$STATUS" != "302" ]; then
            echo "Health check failed!"
            exit 1
          fi
          echo "App is up!"

  merge-to-main:
    name: Merge prod → main
    needs: health-check
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0
          token: ${{ secrets.GH_TOKEN }}

      - name: Merge prod into main
        run: |
          git config user.name "github-actions[bot]"
          git config user.email "github-actions[bot]@users.noreply.github.com"
          git checkout main
          git merge origin/prod --ff-only
          git push origin main
```

---

## 9. Docker

### 9.1 Dockerfile recommandé (multi-stage)

```dockerfile
# Stage 1 : dépendances Composer
FROM composer:2 AS composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-scripts --no-progress --optimize-autoloader

# Stage 2 : image finale
FROM php:8.2-fpm-alpine

# Extensions PHP
RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

# Copier vendor depuis stage 1
COPY --from=composer /app/vendor ./vendor

# Copier le code source
COPY src/ ./src/
COPY public/ ./public/

# User non-root pour la sécurité
RUN addgroup -g 1001 app && adduser -u 1001 -G app -s /bin/sh -D app
USER app

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
  CMD php-fpm -t || exit 1
```

### 9.2 .dockerignore

```
vendor/
node_modules/
.git/
.github/
tests/
data/
backups/
*.log
.env
.env.*
docker/mariadb/data/
```

### 9.3 docker-compose.yml (exemple)

```yaml
services:
  app:
    image: MON_REGISTRY/mon-app:dev   # :prod sur le serveur de prod
    restart: unless-stopped
    ports:
      - "9000:9000"
    environment:
      - DB_HOST=db
      - DB_NAME=${DB_NAME}
      - DB_USER=${DB_USER}
      - DB_PASSWORD=${DB_PASSWORD}
    depends_on:
      db:
        condition: service_healthy

  db:
    image: mariadb:11
    restart: unless-stopped
    environment:
      MARIADB_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MARIADB_DATABASE: ${DB_NAME}
      MARIADB_USER: ${DB_USER}
      MARIADB_PASSWORD: ${DB_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
      interval: 10s
      retries: 5

volumes:
  db_data:
```

---

## 10. Secrets GitHub à configurer

**GitHub repo → Settings → Secrets and variables → Actions**

| Secret | Description | Utilisé par |
|---|---|---|
| `REGISTRY_USER` | Login du registry Docker privé | Tous les workflows |
| `REGISTRY_PASSWORD` | Mot de passe du registry | Tous les workflows |
| `PROD_SSH_KEY` | Clé privée SSH (sans passphrase) pour le serveur prod | deploy.yml |
| `GH_TOKEN` | Personal Access Token GitHub (scope: `repo`) | merge prod→main |

### Générer une clé SSH pour CI/CD

```bash
# Sur ta machine locale (jamais sur le serveur)
ssh-keygen -t ed25519 -C "github-actions-prod" -f ~/.ssh/github_actions_prod
# Appuyer Entrée deux fois (pas de passphrase)

# Ajouter la clé publique sur le serveur prod
ssh-copy-id -i ~/.ssh/github_actions_prod.pub deploy@mon-serveur-prod.example.com

# Copier la clé privée dans le secret GitHub PROD_SSH_KEY
cat ~/.ssh/github_actions_prod
```

### Générer un GH_TOKEN

GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)  
Scopes : `repo` (accès complet aux repos)

---

## 11. Flux de travail quotidien

### Développer une feature

```bash
git checkout dev
git pull origin dev

# Coder...

git add .
git commit -m "feat: description de la feature"
git push origin dev
# → déclenche dev-cicd.yml : test + build + deploy sur serveur de dev
```

### Ouvrir une Pull Request

```bash
# Sur GitHub : créer une PR de ta branche vers dev
# → déclenche tests.yml : PHPUnit doit passer pour merger
```

### Passer en production

```bash
git checkout prod
git merge dev
git push origin prod
# → déclenche deploy.yml :
#   1. retag :dev → :prod
#   2. deploy sur serveur prod via SSH
#   3. health check de l'API
#   4. merge automatique prod → main
```

---

## 12. Checklist nouveau projet

### Setup initial

- [ ] Créer les branches `dev`, `prod`, `main` sur GitHub
- [ ] Protéger `main` : interdire les push directs (Settings → Branches → Branch protection)
- [ ] Installer PHPUnit : `composer require --dev phpunit/phpunit`
- [ ] Créer `phpunit.xml`
- [ ] Écrire les premiers tests dans `tests/`

### Quality gates (optionnel mais recommandé)

- [ ] Installer PHPStan : `composer require --dev phpstan/phpstan`
- [ ] Créer `phpstan.neon` avec level 3 minimum
- [ ] Installer PHP-CS-Fixer : `composer require --dev friendsofphp/php-cs-fixer`
- [ ] Créer `.php-cs-fixer.php`

### Infrastructure

- [ ] Créer le `Dockerfile` (multi-stage)
- [ ] Créer le `.dockerignore`
- [ ] Créer `docker-compose.yml`
- [ ] Avoir un registry Docker accessible (privé ou GitHub Container Registry)
- [ ] Installer le self-hosted runner sur le serveur de dev (voir `docs/cicd-setup.md`)
- [ ] Configurer le serveur de prod (dossier + .env + docker-compose)

### GitHub Actions

- [ ] Créer `.github/workflows/tests.yml` (tests sur PR)
- [ ] Créer `.github/workflows/dev-cicd.yml` (CI/CD dev)
- [ ] Créer `.github/workflows/deploy.yml` (déploiement prod)
- [ ] Configurer les secrets GitHub (`REGISTRY_USER`, `REGISTRY_PASSWORD`, `PROD_SSH_KEY`, `GH_TOKEN`)

### Vérifications finales

- [ ] Push sur `dev` → vérifier que le pipeline s'exécute sur GitHub Actions
- [ ] Vérifier que le serveur de dev est mis à jour
- [ ] Push sur `prod` → vérifier le déploiement prod et le health check
- [ ] Vérifier que `main` est bien mis à jour automatiquement
- [ ] Ouvrir une PR avec un test qui échoue → vérifier que le merge est bloqué

---

*Ce guide couvre un pipeline PHP + Docker. Pour d'autres stacks, adapter les étapes 3-4 (remplacer PHPUnit/PHPStan par l'équivalent dans le langage cible).*
