# Checklist d'Apprentissage - WCDO RNCP 37805

**Objectif :** Maîtriser chaque technologie du projet pour répondre aux questions du jury lors de la soutenance.

**Format de chaque item :**
- **Technologie** : Nom exact
- **Rôle dans le projet** : Pourquoi elle est là
- **À comprendre** : Concepts clés
- **Points clés à pouvoir expliquer** : Questions probables du jury
- **Statut** : ⭕ À apprendre | 🟡 En cours | ✅ Maîtrisé
- **Notes personnelles** : Espace pour écrire ce qu'on apprend

---

## 1. Composer

**Statut :** 🟡 En cours

**Rôle dans le projet :**
Gestionnaire de dépendances PHP. Permet d'installer automatiquement les packages dont le projet a besoin (PHPUnit pour les tests, par exemple) et configure l'autoloading PSR-4 (chargement automatique des classes PHP).

**À comprendre :**
- La différence entre `composer.json` (déclaration des dépendances) et `composer.lock` (versions exactes installées)
- Le système de versioning sémantique (`^8.0` = compatible 8.x, `~1.2` = entre 1.2 et 1.3)
- L'autoloading PSR-4 : fait la correspondance namespace WCDO\ → dossier src/
- Les dépendances de dev (dev-dependencies) : PHPUnit, outils de test/lint

**Points clés à pouvoir expliquer :**
- "À quoi sert composer.json dans ton projet ?" → Déclare les dépendances PHP (actuellement minimal car PHP natif)
- "Comment Composer charge-t-il tes classes PHP ?" → Autoloading PSR-4 configuré, `require_once __DIR__ . '/../vendor/autoload.php'` dans public/index.php
- "Pourquoi `composer install --no-dev --optimize-autoloader` dans le Dockerfile ?" → Installe uniquement les dépendances de production, optimise la performance
- "Que contient composer.lock ?" → Versions exactes installées, garantit reproductibilité d'une build à l'autre

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 2. PSR-4 Autoloading

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Standard PHP pour charger automatiquement les classes sans require/include explicite. Exemple : `WCDO\Controllers\CatalogueController` charge automatiquement le fichier `src/Controllers/CatalogueController.php`.

**À comprendre :**
- La correspondance namespace-to-path : `WCDO\Controllers\` → `src/Controllers/`
- Comment Composer configure cela dans `composer.json` via la clé `autoload`
- Pas besoin de `require_once` pour chaque classe (c'est magique !)

**Points clés à pouvoir expliquer :**
- "Comment tes classes sont-elles chargées automatiquement ?" → PSR-4 autoloading configuré par Composer
- "Quel est le lien entre le namespace `WCDO\Entities\Client` et le fichier ?" → C'est du chemin relatif : WCDO\ = src/, Entities = Entities/, Client.php

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 3. Namespace PHP

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Organise le code en packages logiques. Évite les conflits de noms de classes et structure le projet. Tout le code est sous `namespace WCDO\`.

**À comprendre :**
- Un namespace est comme un dossier pour les classes
- `namespace WCDO\Controllers;` au top du fichier
- Les use statements : `use WCDO\Services\CommandeService;`

**Points clés à pouvoir expliquer :**
- "Pourquoi tout est-il dans le namespace WCDO\ ?" → Éviter collisions, organiser le projet, professionnalisme
- "Quel est l'effet du namespace sur les imports ?" → Tous les imports doivent utiliser le namespace complet ou `use` statement

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 4. PHP 8.2 + declare(strict_types=1)

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
PHP 8.2 offre les dernières features (union types, match expressions, named arguments). `declare(strict_types=1)` force un typage strict dans chaque fichier pour éviter les conversions implicites.

**À comprendre :**
- Typage strict : `int 5` ≠ `string "5"`, PHP refuse la conversion silencieuse
- Propriétés typées : `private int $id;`
- Return type hints : `function getId(): int`

**Points clés à pouvoir expliquer :**
- "Pourquoi `declare(strict_types=1)` au top de chaque fichier ?" → Évite les bugs silencieux dus à conversions implicites
- "Quel est l'impact du typage strict ?" → Erreur si on passe un string à la place d'un int, force la précision

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 5. PDO (PHP Data Objects)

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Abstraction pour communiquer avec la base de données MariaDB. PDO = interface générique qui fonctionne avec MySQL, PostgreSQL, SQLite, etc.

**À comprendre :**
- PDO c'est un objet qui représente la connexion à la BDD
- `PDO::prepare()` crée une requête préparée (protection injection SQL)
- `PDOStatement::execute()` exécute la requête avec les paramètres bindés
- `PDO::FETCH_ASSOC` retourne les résultats sous forme de tableaux associatifs

**Points clés à pouvoir expliquer :**
- "Comment se connecte-t-on à la BDD ?" → Via PDO Singleton dans Config/Database.php
- "Pourquoi utiliser `prepare()` et `execute()` ?" → Protection contre l'injection SQL via paramètres bindés
- "À quoi sert `EMULATE_PREPARES=false` ?" → Force les vraies requêtes préparées côté serveur (sécurité maximale)
- "Pourquoi un Singleton pour PDO ?" → Une seule connexion BDD partagée dans tout le cycle de vie de la requête HTTP = performance

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 6. Pattern Repository

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Encapsule l'accès à la base de données. Chaque Repository expose des méthodes publiques (find, findAll, save, delete) et le reste du code (Services) n'accède JAMAIS directement à la BDD.

**À comprendre :**
- Repository = interface vers une entité/table
- Avantage : si on change de BDD (MySQL → PostgreSQL), on change juste le Repository
- Les Services utilisent les Repositories, pas les requêtes SQL directes
- Facilite le mocking pour les tests

**Points clés à pouvoir expliquer :**
- "Quel est le rôle d'un Repository ?" → Encapsuler l'accès BDD, exposer des méthodes de haut niveau
- "Pourquoi une couche Repository entre Service et BDD ?" → Séparation des responsabilités, testabilité, maintenabilité
- "Comment un Repository communique-t-il avec la BDD ?" → Via PDO en utilisant `Database::getConnection()`

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 7. Pattern Service Layer

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Contient la logique métier. Exemple : CommandeService orchestre plusieurs Repositories (Panier, Produit, Commande, Client) pour implémenter le flux métier "passer une commande".

**À comprendre :**
- Les Services NE connaissent pas HTTP, NE retournent pas des réponses JSON
- Les Services orchestrent les Repositories et appliquent les règles métier
- Ils lancent des exceptions (InvalidArgumentException, RuntimeException) que les Controllers attrapent
- Facilité de test : mocker les Repositories, tester la logique métier indépendamment

**Points clés à pouvoir expliquer :**
- "Quelle est la différence entre un Controller et un Service ?" → Controller gère HTTP, Service gère logique métier
- "Pourquoi séparer Services et Repositories ?" → Chacun a une responsabilité. Repository = accès données, Service = logique business
- "Donne un exemple du flux dans CommandeService" → Valider panier → calculer total → générer numéro unique → décrémenter stock → points fidélité

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 8. Pattern MVC (Model-View-Controller)

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Architecture globale du projet. Model = Entities + Repositories, View = Responses JSON, Controller = orchestration.

**À comprendre :**
- Model : la donnée (Entities + Repositories)
- View : la présentation (JSON responses)
- Controller : l'intermédiaire (reçoit requête, appelle Service, retourne response)
- Flux HTTP : requête → Router → Controller → Service → Repositories → BDD, puis remontée inverse

**Points clés à pouvoir expliquer :**
- "Décris l'architecture MVC de ton projet" → Controllers reçoivent HTTP, délèguent à Services (logique métier), Services utilisent Repositories (accès données), résultats retournés en JSON
- "Pourquoi MVC et pas monolithe ?" → Séparation des responsabilités, testabilité, maintenabilité, évolutivité

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 9. Router Personnalisé (Regex Nommée)

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Décide quelle méthode du Controller exécuter en fonction de la requête HTTP. Transforme les routes avec placeholders (`/api/produits/{id}`) en regex avec groupes nommés.

**À comprendre :**
- Parsing de l'URI avec `parse_url($_SERVER['REQUEST_URI'])`
- Transformation regex : `{id}` → `(?P<id>[^/]+)` (groupe nommé)
- Matching et extraction des paramètres via `preg_match()`
- Dispatch : appelle la callback (Controller + méthode) avec les paramètres extraits

**Points clés à pouvoir expliquer :**
- "Comment fonctionne le Router ?" → Lit la requête HTTP, identifie la route, appelle le bon Controller
- "À quoi sert la regex nommée ?" → Extraire les paramètres d'URL (ex: `{id}` devient accessible dans $params['id'])
- "Pourquoi ne pas utiliser un framework avec routing intégré ?" → Pédagogie : comprendre les mécanismes internes

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 10. Sessions PHP vs JWT

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Authentifier les utilisateurs (client + admin). Sessions PHP utilisées (stateful), pas JWT (stateless).

**À comprendre :**
- Sessions PHP : données stockées côté serveur, identifiant envoyé au client via cookie
- JWT : token encodé, aucun stockage côté serveur, décodage côté client
- `session_start()`, `$_SESSION['client_id']`, `session_destroy()`
- Quand utiliser l'un vs l'autre

**Points clés à pouvoir expliquer :**
- "Pourquoi des sessions PHP et pas JWT ?" → Contexte borne locale, sessions plus simples et adaptées
- "Comment fonctionne l'authentification dans ton projet ?" → LoginClient → password_verify() → session_start() → $_SESSION['client_id']
- "Quel est l'avantage des sessions ?" → Stockage côté serveur = sécurité, pas d'exposition d'infos au client
- "Quel est l'avantage de JWT ?" → Stateless = scalable, pas besoin de stockage serveur (hors-projet)

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 11. Hachage des Mots de Passe (bcrypt)

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Sécuriser les mots de passe. `password_hash()` crée un hash bcrypt, `password_verify()` vérifie.

**À comprendre :**
- Bcrypt = algorithme itératif lent (par design), difficult à bruteforcer
- `password_hash($pwd, PASSWORD_BCRYPT)` génère un hash aléatoire (salt inclus)
- `password_verify($input, $hash)` compare sécurisé
- Jamais stocker le mot de passe en clair en BDD

**Points clés à pouvoir expliquer :**
- "Comment sécurises-tu les mots de passe ?" → Hachage bcrypt via password_hash()
- "Pourquoi pas md5 ou sha256 ?" → Ces algos sont trop rapides à bruteforcer, bcrypt itératif résiste mieux
- "Quel est le rôle du salt dans bcrypt ?" → Rend chaque hash différent même pour le même pwd, impossible de pré-calculer les hashs

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 12. Injection SQL et Protection (PDO Préparé)

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Prévenir les attaques par injection SQL. Utiliser `PDO::prepare()` et `execute()` avec paramètres bindés.

**À comprendre :**
- L'injection SQL : exploiter la concaténation de chaînes dans une requête
- Exemple d'attaque : `SELECT * FROM CLIENT WHERE email = '' OR '1'='1'`
- Requête préparée : le template est séparé des données
- Binding : les données sont fournies séparément à `execute()`

**Points clés à pouvoir expliquer :**
- "Qu'est-ce que l'injection SQL ?" → Attaque où on modifie la requête en injectant du code SQL via les données
- "Comment te protèges-tu ?" → PDO préparé avec binding de paramètres
- "Pourquoi `EMULATE_PREPARES=false` ?" → Force les vraies requêtes préparées côté MariaDB (double protection)
- "Donne un exemple de requête préparée sécurisée" → `$stmt = $pdo->prepare('SELECT * FROM CLIENT WHERE email = :email'); $stmt->execute([':email' => $email]);`

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 13. CORS (Cross-Origin Resource Sharing)

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Autoriser les requêtes du front vers l'API backend (domaines différents). Headers CORS pour gérer les accès cross-origin.

**À comprendre :**
- Requête cross-origin : domain1.com → domain2.com
- Preflight OPTIONS : le navigateur envoie OPTIONS avant la vraie requête
- Headers CORS : `Access-Control-Allow-Origin`, `Access-Control-Allow-Methods`, `Access-Control-Allow-Headers`
- Same-origin policy : sécurité par défaut, CORS l'assouplit

**Points clés à pouvoir expliquer :**
- "Qu'est-ce que CORS ?" → Mécanisme de sécurité pour contrôler les requêtes cross-origin
- "Pourquoi préflight OPTIONS ?" → Le navigateur vérifie si le serveur accepte la requête avant de l'envoyer
- "C'est quoi `Access-Control-Allow-Origin: *` ?" → Accepte les requêtes de n'importe quel domaine (permissif)
- "Est-ce bien en production ?" → Non ! À restreindre aux domaines autorisés pour la sécurité

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 14. Docker et Conteneurisation

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Empaqueter l'application avec toutes ses dépendances dans des conteneurs isolés. Garantir reproductibilité : "ça marche chez moi et chez toi".

**À comprendre :**
- Dockerfile = recette pour construire une image Docker
- Image = template immutable
- Conteneur = instance d'une image (runtime)
- Volume = stockage persistant pour les données
- Network = communication entre conteneurs

**Points clés à pouvoir expliquer :**
- "À quoi sert Docker dans ton projet ?" → Empaqueter PHP 8.2-FPM + dépendances, reproductibilité, pré-configuration
- "Qu'y a-t-il dans le Dockerfile ?" → Base alpine, extensions PDO, Composer install --no-dev, EXPOSE 9000
- "C'est quoi le Singleton PDO par rapport à Docker ?" → PDO dans public/index.php, une seule connexion BDD pour toute la requête HTTP
- "Pourquoi PHP-FPM vs PHP-CLI ?" → PHP-FPM = mode serveur (gère concurrence), PHP-CLI = exécution de scripts

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 15. Docker Compose et Orchestration

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Orchestrer 4 services (DB, PHP, Nginx, phpMyAdmin) avec un seul fichier `docker-compose.yml`. Gère l'ordre de démarrage, les réseaux, les volumes.

**À comprendre :**
- docker-compose.yml = définition des services, volumes, réseaux, variables d'env
- `depends_on` : OrderSprint de démarrage
- `healthcheck` : vérifier que le service est prêt
- Réseaux Docker : internal (privé), admin_proxy (externe pour Traefik)
- Volumes : persistance des données BDD

**Points clés à pouvoir expliquer :**
- "À quoi sert docker-compose.yml ?" → Définir les 4 services et leur interaction
- "Quel est l'ordre de démarrage ?" → DB d'abord, puis PHP (après health check DB), puis Nginx
- "Pourquoi deux réseaux ?" → internal (privé inter-services), admin_proxy (Traefik externe)
- "C'est quoi le healthcheck sur MariaDB ?" → Vérifier que la DB est prête avant de lancer PHP

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 16. Nginx et PHP-FPM

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Nginx = serveur HTTP, reçoit les requêtes, route les `.php` vers PHP-FPM (processus PHP). Configuration dual-vhost pour front statique et back API.

**À comprendre :**
- Nginx = reverse proxy léger, très efficace
- PHP-FPM = processus PHP qui exécute le code (separate de Nginx)
- `fastcgi_pass php:9000` : Nginx communique avec PHP-FPM via FastCGI sur le port 9000
- Vhost = configuration d'un site/domaine spécifique
- Dual-vhost : deux domaines sur le même Nginx

**Points clés à pouvoir expliquer :**
- "Rôle de Nginx ?" → Serveur HTTP, reçoit les requêtes, les route vers les bonnes destinations
- "Pourquoi séparer Nginx et PHP-FPM ?" → Responsabilités différentes = scalabilité, performance (Nginx ultra-léger)
- "Comment communiquent Nginx et PHP-FPM ?" → FastCGI via TCP (port 9000 ou socket Unix)
- "Qu'y a-t-il dans nginx.conf ?" → Deux vhost : front statique (wakdo-front.acadenice.fr), back API (wakdo-back.acadenice.fr → fastcgi_pass php:9000)

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 17. Traefik (Reverse Proxy & TLS)

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Reverse proxy externe qui gère le routage par hostname et les certificats SSL/TLS via Let's Encrypt.

**À comprendre :**
- Traefik = reverse proxy moderne, configuration dynamique via labels Docker
- Routage par hostname : `Host(wakdo-front.acadenice.fr)` → rediriger vers service Nginx
- Let's Encrypt = certificat SSL/TLS gratuit, renouvellement automatique
- Labels dans docker-compose.yml = déclaration de routeurs Traefik

**Points clés à pouvoir expliquer :**
- "Rôle de Traefik ?" → Reverse proxy externe, TLS/SSL, routage par hostname
- "C'est la différence entre Traefik et Nginx ?" → Nginx = intérieur containers, Traefik = extérieur (reverse proxy principal)
- "Comment Traefik sait-il où router ?" → Via labels dans docker-compose.yml (règles Traefik déclarées)
- "Qui gère les certificats SSL ?" → Traefik s'intègre avec Let's Encrypt pour renouvellement auto

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 18. MariaDB et SQL

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
SGBD relationnel stockant la donnée. Schéma 3NF avec 10 tables, requêtes SQL via PDO.

**À comprendre :**
- MariaDB = fork open-source de MySQL, compatible, performant
- SQL : SELECT, INSERT, UPDATE, DELETE, JOIN, WHERE
- Normalisation 3NF : éliminer redondances, dépendances fonctionnelles
- Contraintes : PRIMARY KEY, FOREIGN KEY, UNIQUE, CHECK, INDEX, ENUM, JSON

**Points clés à pouvoir expliquer :**
- "Pourquoi MariaDB et pas MySQL ?" → Communauté active, performances équivalentes, open-source
- "C'est quoi la 3NF ?" → Troisième Forme Normale : éliminer redondances, chaque colonne dépend de la clé primaire
- "Donne la structure des 10 tables" → CATEGORIE, PRODUIT, PANIER, COMMANDE, CLIENT, ADMIN, SAUCE, TAILLE_BOISSON, PANIER_PRODUIT, COMMANDE_PRODUIT
- "Comment te connectes-tu à MariaDB ?" → PDO Singleton depuis public/index.php
- "C'est quoi un INDEX ?" → Optimisation : accélérer les recherches sur une colonne fréquemment interrogée

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 19. 3NF (Troisième Forme Normale)

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Rationaliser le schéma BDD pour éviter les redondances et les anomalies.

**À comprendre :**
- 1NF : pas d'attributs multivalués (chaque colonne = un seul type)
- 2NF : toutes les colonnes dépendent de la clé primaire complète
- 3NF : pas de dépendances transitives (une colonne non-clé ne dépend d'une autre colonne non-clé)
- Avantages : performance, intégrité, pas de redondance

**Points clés à pouvoir expliquer :**
- "Qu'est-ce que la 3NF ?" → Troisième niveau de normalisation pour rationaliser le schéma
- "Quel est l'intérêt de la 3NF ?" → Éviter redondances, anomalies d'insertion/suppression/modification, meilleure performance
- "Exemple 3NF dans ton projet ?" → PRODUIT a id_categorie (FK), pas une colonne "nom_categorie" (redondance)

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## 20. Entities (Objets Métier)

**Statut :** ⭕ À apprendre

**Rôle dans le projet :**
Représenter les objets métier PHP. Différent d'un ORM : pas d'accès à la BDD, juste des propriétés et quelques méthodes métier.

**À comprendre :**
- Une Entité = classe PHP avec propriétés typées
- Méthodes : getters/setters, méthodes métier (ex: Client::verifierMotDePasse)
- Pas d'accès BDD depuis une entité (c'est le boulot des Repositories)
- Mapping manuel : BDD array → Entity objet (pas d'ORM)

**Points clés à pouvoir expliquer :**
- "Qu'est-ce qu'une Entity ?" → Objet métier PHP représentant une ligne BDD
- "Différence Entity vs ORM ?" → Entity = juste les propriétés + logique métier simple, pas d'ORM donc pas de magic
- "Donne un exemple de méthode métier" → `Client::verifierMotDePasse()` : encapsule la logique bcrypt
- "Comment une Entity est-elle créée ?" → Repository fetch une ligne BDD, mappe les données dans l'Entity

**Notes personnelles :**
```
À ajouter ici après révision
```

---

## À ajouter (en attente)

- [ ] Transactionsm SQL
- [ ] Gestion des erreurs et exceptions
- [ ] Logging
- [ ] Testing (PHPUnit)
- [ ] Git et versionning
- [ ] REST API design
- [ ] ...

---

**Généré le :** 2026-03-08
**Prochaine mise à jour :** Au fur et à mesure que Hugo maîtrise chaque technologie
