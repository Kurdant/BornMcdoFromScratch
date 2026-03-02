# Plan de Tests TDD - Projet WCDO

**Projet :** WCDO (Borne de commande)  
**Date :** 2026-02-09  
**Créé par :** WCDO-TDD-GENERATOR  
**Pour :** Hugo (Débutant PHP/MariaDB/TDD)

---

## 🎯 OBJECTIF

Créer une suite de tests unitaires complète pour le backend WCDO en suivant la méthodologie **TDD (Test-Driven Development)**.

**Principe TDD :** Tests AVANT code → Red → Green → Refactor

---

## 📊 STRATÉGIE DE TESTS (3 NIVEAUX)

### 🟢 NIVEAU 1 : Entités de Base (Facile)
Tests d'entités simples sans dépendances complexes. Idéal pour débuter.

### 🟡 NIVEAU 2 : Entités avec Relations (Moyen)
Tests d'entités liées à d'autres entités (FK, relations 1-n).

### 🔴 NIVEAU 3 : Règles Métier Complexes (Avancé)
Tests des règles de gestion critiques (relations n-n, logique métier).

---

## 📋 LISTE COMPLÈTE DES TESTS (45 TESTS)

### 🟢 NIVEAU 1 : ENTITÉS DE BASE (9 tests - 30 min)

#### **Suite 1 : Test CATEGORIE** (Difficulté: ⭐)
**Priorité :** MOYENNE  
**Fichier :** `tests/Entities/CategorieTest.php`  
**Durée estimée :** 10 min

| # | Nom du Test | Description | Règle |
|---|-------------|-------------|-------|
| 1.1 | `testCreerCategorieValide()` | Créer une catégorie avec nom valide | - |
| 1.2 | `testNomCategorieUnique()` | Empêcher doublon de nom de catégorie | Contrainte UNIQUE |
| 1.3 | `testNomCategorieNonVide()` | Nom de catégorie obligatoire (NOT NULL) | Contrainte NOT NULL |

**Fixtures nécessaires :** Aucune  
**Concepts testés :** Validation basique, contraintes BDD

---

#### **Suite 2 : Test SAUCE** (Difficulté: ⭐)
**Priorité :** BASSE  
**Fichier :** `tests/Entities/SauceTest.php`  
**Durée estimée :** 10 min

| # | Nom du Test | Description | Règle |
|---|-------------|-------------|-------|
| 2.1 | `testCreerSauceValide()` | Créer une sauce avec nom valide | - |
| 2.2 | `testNomSauceUnique()` | Empêcher doublon de nom de sauce | Contrainte UNIQUE |
| 2.3 | `testListeSaucesPredefinies()` | Vérifier les 7 sauces disponibles | Business |

**Fixtures nécessaires :** Liste des 7 sauces (Barbecue, Moutarde, etc.)  
**Concepts testés :** Validation, énumérations métier

---

#### **Suite 3 : Test TAILLE_BOISSON** (Difficulté: ⭐)
**Priorité :** MOYENNE  
**Fichier :** `tests/Entities/TailleBoissonTest.php`  
**Durée estimée :** 10 min

| # | Nom du Test | Description | Règle |
|---|-------------|-------------|-------|
| 3.1 | `testCreerTailleBoisson30cl()` | Créer taille 30cl avec supplément 0€ | - |
| 3.2 | `testCreerTailleBoisson50cl()` | Créer taille 50cl avec supplément 0.50€ | RG-003 |
| 3.3 | `testSupplementPrixPositifOuNul()` | Supplément >= 0 | Contrainte CHECK |

**Fixtures nécessaires :** 2 tailles prédéfinies  
**Concepts testés :** Validation, règles de prix (RG-003)

---

### 🟡 NIVEAU 2 : ENTITÉS AVEC RELATIONS (18 tests - 1h30)

#### **Suite 4 : Test PRODUIT** ⭐ **(COMMENCE ICI !)**
**Priorité :** CRITIQUE  
**Fichier :** `tests/Entities/ProduitTest.php`  
**Durée estimée :** 30 min

| # | Nom du Test | Description | Règle |
|---|-------------|-------------|-------|
| 4.1 | `testCreerProduitValide()` | Créer produit avec tous attributs valides | - |
| 4.2 | `testProduitAppartientAUneCategorie()` | Un produit doit avoir une catégorie (FK) | Relation 1-n |
| 4.3 | `testPrixProduitPositif()` | Prix > 0 obligatoire | Contrainte CHECK |
| 4.4 | `testStockProduitPositifOuNul()` | Stock >= 0 | Contrainte CHECK |
| 4.5 | `testProduitIndisponibleSiStockZero()` | Stock = 0 → `estDisponible()` = false | **RG-001** ⚠️ |
| 4.6 | `testProduitDisponibleSiStockSuperieurZero()` | Stock > 0 → `estDisponible()` = true | **RG-001** |

**Fixtures nécessaires :**
- 7 catégories (une par type)
- 10-15 produits variés (stocks différents : 0, 1, 10, 100)

**Concepts testés :**
- ✅ Relations FK (CATEGORIE)
- ✅ Contraintes (prix, stock)
- ✅ **RG-001 (CRITIQUE)**

---

#### **Suite 5 : Test CLIENT** 
**Priorité :** HAUTE  
**Fichier :** `tests/Entities/ClientTest.php`  
**Durée estimée :** 30 min

| # | Nom du Test | Description | Règle |
|---|-------------|-------------|-------|
| 5.1 | `testCreerClientValide()` | Créer client avec email/mot de passe | - |
| 5.2 | `testEmailClientUnique()` | Empêcher doublon d'email | Contrainte UNIQUE |
| 5.3 | `testMotDePasseHashe()` | Mot de passe doit être hashé (password_hash) | Sécurité |
| 5.4 | `testPointsFideliteInitialisesAZero()` | Nouveau client → 0 points | **RG-005** |
| 5.5 | `testFormatEmailValide()` | Email doit respecter format RFC | Validation |
| 5.6 | `testMotDePasseMinimum8Caracteres()` | Mot de passe >= 8 caractères | Sécurité |

**Fixtures nécessaires :**
- 5 clients (0 points, 50 points, 100 points, 500 points, 1000 points)

**Concepts testés :**
- ✅ Validation email
- ✅ Sécurité (hash mot de passe)
- ✅ **RG-005** (Points de fidélité)

---

#### **Suite 6 : Test ADMIN**
**Priorité :** BASSE  
**Fichier :** `tests/Entities/AdminTest.php`  
**Durée estimée :** 15 min

| # | Nom du Test | Description | Règle |
|---|-------------|-------------|-------|
| 6.1 | `testCreerAdminValide()` | Créer admin avec email/mot de passe | - |
| 6.2 | `testEmailAdminUnique()` | Empêcher doublon d'email | Contrainte UNIQUE |
| 6.3 | `testMotDePasseAdminHashe()` | Mot de passe hashé | Sécurité |

**Fixtures nécessaires :** 1-2 admins  
**Concepts testés :** Validation, sécurité

---

#### **Suite 7 : Test PANIER**
**Priorité :** HAUTE  
**Fichier :** `tests/Entities/PanierTest.php`  
**Durée estimée :** 15 min

| # | Nom du Test | Description | Règle |
|---|-------------|-------------|-------|
| 7.1 | `testCreerPanierVide()` | Créer panier vide pour client | - |
| 7.2 | `testPanierClientConnecte()` | Panier avec client_id renseigné | - |
| 7.3 | `testPanierClientAnonyme()` | Panier avec client_id NULL + session_id | **RG-009** |

**Fixtures nécessaires :** Clients + sessions temporaires  
**Concepts testés :** Gestion client anonyme vs connecté

---

### 🔴 NIVEAU 3 : RÈGLES MÉTIER COMPLEXES (18 tests - 2h)

#### **Suite 8 : Test PANIER + PRODUIT (Relation n-n)**
**Priorité :** CRITIQUE  
**Fichier :** `tests/Business/PanierProduitTest.php`  
**Durée estimée :** 45 min

| # | Nom du Test | Description | Règle |
|---|-------------|-------------|-------|
| 8.1 | `testAjouterProduitAuPanier()` | Ajouter produit avec stock > 0 | - |
| 8.2 | `testImpossibleAjouterProduitStockZero()` | Stock = 0 → Exception levée | **RG-001** ⚠️ |
| 8.3 | `testAjouterMenuAvecDeuxSauces()` | Menu + 2 sauces → OK | **RG-002** |
| 8.4 | `testImpossibleAjouterMenuAvecTroisSauces()` | Menu + 3 sauces → Exception | **RG-002** ⚠️ |
| 8.5 | `testAjouterBoissonAvecTaille30cl()` | Boisson 30cl → prix de base | **RG-003** |
| 8.6 | `testAjouterBoissonAvecTaille50cl()` | Boisson 50cl → prix + 0.50€ | **RG-003** ⚠️ |
| 8.7 | `testCalculerTotalPanier()` | Total panier = somme produits + suppléments | - |
| 8.8 | `testSupprimerProduitDuPanier()` | Retirer un produit du panier | - |
| 8.9 | `testModifierQuantiteProduitPanier()` | Changer quantité d'un produit | - |

**Fixtures nécessaires :**
- Produits (stock varié)
- Sauces
- Tailles de boisson
- Paniers de test

**Concepts testés :**
- ✅ **RG-001** (Stock = 0)
- ✅ **RG-002** (Max 2 sauces)
- ✅ **RG-003** (Supplément 50cl)
- ✅ Calcul de prix
- ✅ Relations many-to-many

---

#### **Suite 9 : Test COMMANDE**
**Priorité :** CRITIQUE  
**Fichier :** `tests/Entities/CommandeTest.php`  
**Durée estimée :** 30 min

| # | Nom du Test | Description | Règle |
|---|-------------|-------------|-------|
| 9.1 | `testCreerCommandeApresPayementValide()` | Commande créée si paiement OK | **RG-007** ⚠️ |
| 9.2 | `testImpossibleCreerCommandeSansPayement()` | Pas de paiement → Pas de commande | **RG-007** |
| 9.3 | `testNumeroChevalet001A999()` | Numéro chevalet valide (001-999) | **RG-004** |
| 9.4 | `testNumeroChevalet000Invalide()` | Numéro 000 → Exception | **RG-004** |
| 9.5 | `testNumeroChevalet1000Invalide()` | Numéro 1000 → Exception | **RG-004** |
| 9.6 | `testGenerationNumeroCommandeUnique()` | Chaque commande → numéro unique | Contrainte UNIQUE |
| 9.7 | `testCommandeStockeeHistoriqueClientConnecte()` | Client connecté → commande dans historique | **RG-010** |
| 9.8 | `testCommandeStockeeHistoriqueClientAnonyme()` | Client anonyme → commande dans historique | **RG-010** ⚠️ |
| 9.9 | `testCommandeContientTypeSurPlaceOuEmporter()` | Type commande obligatoire (ENUM) | - |

**Fixtures nécessaires :**
- Paniers remplis
- Paiements simulés (succès/échec)
- Clients (connectés + anonymes)

**Concepts testés :**
- ✅ **RG-007** (Commande après paiement)
- ✅ **RG-004** (Numéro chevalet)
- ✅ **RG-010** (Historique légal)
- ✅ Validation business complexe

---

#### **Suite 10 : Test COMMANDE + PRODUIT + STOCK**
**Priorité :** CRITIQUE  
**Fichier :** `tests/Business/CommandeStockTest.php`  
**Durée estimée :** 30 min

| # | Nom du Test | Description | Règle |
|---|-------------|-------------|-------|
| 10.1 | `testStockDecrementeApresCommandeValidee()` | Commande validée → stock - quantité | **RG-008** ⚠️ |
| 10.2 | `testStockNonDecrementeSiCommandeEchoue()` | Commande échouée → stock inchangé | **RG-008** |
| 10.3 | `testStockDecrementePourChaqueProduit()` | Multiple produits → chaque stock décrémenté | **RG-008** |
| 10.4 | `testImpossibleCommanderSiStockInsuffisant()` | Quantité > stock → Exception | **RG-001** |

**Fixtures nécessaires :**
- Produits avec stocks connus
- Commandes de test

**Concepts testés :**
- ✅ **RG-008** (Décrémentation stock - CRITIQUE)
- ✅ **RG-001** (Stock insuffisant)
- ✅ Intégrité transactionnelle

---

#### **Suite 11 : Test POINTS DE FIDÉLITÉ**
**Priorité :** HAUTE  
**Fichier :** `tests/Business/PointsFideliteTest.php`  
**Durée estimée :** 15 min

| # | Nom du Test | Description | Règle |
|---|-------------|-------------|-------|
| 11.1 | `testPointsCreditesApresCommande()` | 15€ dépensés → +15 points | **RG-005** ⚠️ |
| 11.2 | `testPointsArrondisAuEuroInferieur()` | 15.50€ → +15 points (pas 16) | **RG-005** |
| 11.3 | `testPointsNonCreditesClientAnonyme()` | Client anonyme → 0 points | **RG-009** |
| 11.4 | `testPointsCumulatifsPlusieursCommandes()` | Commande 1 (10€) + Commande 2 (20€) → 30 points | **RG-005** |

**Fixtures nécessaires :**
- Clients avec points variés
- Commandes de montants différents

**Concepts testés :**
- ✅ **RG-005** (1€ = 1 point)
- ✅ **RG-009** (Anonyme = pas de points)
- ✅ Calcul métier

---

#### **Suite 12 : Test CONVERSION PANIER → COMMANDE**
**Priorité :** HAUTE  
**Fichier :** `tests/Business/ConversionPanierCommandeTest.php`  
**Durée estimée :** 30 min

| # | Nom du Test | Description | Règle |
|---|-------------|-------------|-------|
| 12.1 | `testPanierTransformeEnCommandeApresPayement()` | Panier + paiement → Commande | **RG-007** |
| 12.2 | `testPanierDetruiteApresConversionCommande()` | Panier supprimé après commande créée | **RG-006** ⚠️ |
| 12.3 | `testDetailsProduitsCopieDansCom mande()` | Sauces, tailles → copiés dans commande | - |
| 12.4 | `testPrixFigesDansCommande()` | Prix au moment commande (pas prix actuel produit) | Business |

**Fixtures nécessaires :**
- Paniers remplis avec détails (sauces, tailles)
- Simulations de paiement

**Concepts testés :**
- ✅ **RG-006** (Panier temporaire)
- ✅ **RG-007** (Commande après paiement)
- ✅ Intégrité des données (prix figés)

---

## 📈 RÉCAPITULATIF PAR PRIORITÉ

### ⚠️ TESTS CRITIQUES (20 tests - À FAIRE EN PRIORITÉ)
Ces tests valident les règles de gestion critiques. **Commence par ceux-là !**

| Priorité | Suite | Tests Critiques |
|----------|-------|-----------------|
| 1 | Suite 4 | Test PRODUIT (RG-001) |
| 2 | Suite 8 | Test PANIER + PRODUIT (RG-001, RG-002, RG-003) |
| 3 | Suite 9 | Test COMMANDE (RG-007, RG-004, RG-010) |
| 4 | Suite 10 | Test STOCK (RG-008) |
| 5 | Suite 11 | Test POINTS FIDÉLITÉ (RG-005) |
| 6 | Suite 12 | Test CONVERSION (RG-006, RG-007) |

**Estimation :** 3h pour tous les tests critiques

---

### 🔸 TESTS HAUTE PRIORITÉ (12 tests)
Tests importants mais moins critiques.

| Suite | Description |
|-------|-------------|
| Suite 5 | Test CLIENT (sécurité, validation) |
| Suite 7 | Test PANIER (gestion anonyme) |

**Estimation :** 45 min

---

### 🔹 TESTS MOYENNE/BASSE PRIORITÉ (13 tests)
Tests de validation basique. À faire en dernier.

| Suite | Description |
|-------|-------------|
| Suite 1 | Test CATEGORIE |
| Suite 2 | Test SAUCE |
| Suite 3 | Test TAILLE_BOISSON |
| Suite 6 | Test ADMIN |

**Estimation :** 45 min

---

## 🗓️ PLANNING RECOMMANDÉ (Ordre d'exécution)

### **Semaine 1 : Fondations (8-10h)**

**Jour 1 (2h) :** Installation + Tests simples
- Installer PHPUnit
- Suite 1 : Test CATEGORIE (débuter tranquille)
- Suite 4 : Test PRODUIT (commence les tests critiques)

**Jour 2 (2h) :** Règles métier produit
- Finir Suite 4 : Test PRODUIT
- Commencer Suite 8 : Test PANIER + PRODUIT (RG-001, RG-002, RG-003)

**Jour 3 (2h) :** Client et authentification
- Suite 5 : Test CLIENT
- Suite 7 : Test PANIER

**Jour 4 (2h) :** Commandes
- Suite 9 : Test COMMANDE (RG-007, RG-004, RG-010)

**Jour 5 (2h) :** Stock et fidélité
- Suite 10 : Test STOCK (RG-008)
- Suite 11 : Test POINTS FIDÉLITÉ (RG-005)

---

### **Semaine 2 : Finitions (3-5h)**

**Jour 6 (1h30) :** Conversion et finalisation
- Suite 12 : Test CONVERSION PANIER → COMMANDE

**Jour 7 (1h30) :** Tests complémentaires
- Suite 2, 3, 6 : Tests restants (sauce, taille, admin)

---

## ✅ COUVERTURE DES RÈGLES DE GESTION

| Règle | Description | Testée dans | Priorité |
|-------|-------------|-------------|----------|
| **RG-001** | Stock = 0 → Indisponible | Suite 4, 8, 10 | ⚠️ CRITIQUE |
| **RG-002** | Max 2 sauces/menu | Suite 8 | ⚠️ CRITIQUE |
| **RG-003** | Boisson 50cl = +0.50€ | Suite 3, 8 | ⚠️ CRITIQUE |
| **RG-004** | Chevalet 001-999 | Suite 9 | ⚠️ CRITIQUE |
| **RG-005** | 1€ = 1 point | Suite 5, 11 | ⚠️ CRITIQUE |
| **RG-006** | Panier temporaire | Suite 12 | ⚠️ CRITIQUE |
| **RG-007** | Commande après paiement | Suite 9, 12 | ⚠️ CRITIQUE |
| **RG-008** | Stock décrémenté | Suite 10 | ⚠️ CRITIQUE |
| **RG-009** | Anonyme = pas historique | Suite 7, 11 | HAUTE |
| **RG-010** | Historique stocké | Suite 9 | ⚠️ CRITIQUE |

**Couverture : 10/10 règles testées ✅**

---

## 🛠️ OUTILS ET STRUCTURE

### Structure des fichiers de tests
```
tests/
├── Entities/           # Tests des entités simples
│   ├── CategorieTest.php
│   ├── ProduitTest.php
│   ├── ClientTest.php
│   ├── AdminTest.php
│   ├── PanierTest.php
│   ├── CommandeTest.php
│   ├── SauceTest.php
│   └── TailleBoissonTest.php
├── Business/           # Tests des règles métier
│   ├── PanierProduitTest.php
│   ├── CommandeStockTest.php
│   ├── PointsFideliteTest.php
│   └── ConversionPanierCommandeTest.php
├── Fixtures/           # Données de test
│   ├── CategorieFixtures.php
│   ├── ProduitFixtures.php
│   ├── ClientFixtures.php
│   └── SauceFixtures.php
└── bootstrap.php       # Configuration PHPUnit
```

### Commandes PHPUnit
```bash
# Lancer tous les tests
./vendor/bin/phpunit

# Lancer une suite spécifique
./vendor/bin/phpunit tests/Entities/ProduitTest.php

# Lancer un test spécifique
./vendor/bin/phpunit --filter testProduitIndisponibleSiStockZero

# Avec couverture de code
./vendor/bin/phpunit --coverage-html coverage/
```

---

## 📚 RESSOURCES

### Documentation PHPUnit
- Installation : https://phpunit.de/getting-started.html
- Assertions : https://phpunit.de/manual/current/en/assertions.html
- Annotations : https://phpunit.de/manual/current/en/annotations.html

### Patterns TDD
- AAA (Arrange-Act-Assert)
- GIVEN-WHEN-THEN
- Red-Green-Refactor

---

## 🎯 PROCHAINES ÉTAPES

1. **Installer PHPUnit** :
   ```bash
   composer require --dev phpunit/phpunit ^10
   ```

2. **Créer la structure de dossiers** :
   ```bash
   mkdir -p tests/{Entities,Business,Fixtures}
   ```

3. **Commencer par Suite 4 : Test PRODUIT** (le plus important)

4. **Demande-moi de générer le premier test !**
   Dis : "Génère le test pour PRODUIT" ou active l'option [GT]

---

**📁 Fichier sauvegardé et prêt à l'emploi !**

Tu as maintenant une roadmap complète. Par quoi veux-tu commencer ? 🚀
