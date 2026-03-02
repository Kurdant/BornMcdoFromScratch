# 📊 DICTIONNAIRE DE DONNÉES - PROJET WCDO
**Borne de Commande McDonald's - Self-Order Kiosk**

*Généré par: DB-ARCHITECT Agent*  
*Date: 2026-02-12*  
*Projet: BornMcdoFromScratch*

---

## 📋 TABLE DES MATIÈRES

1. [Vue d'ensemble](#vue-densemble)
2. [Tables principales](#tables-principales)
3. [Tables de liaison](#tables-de-liaison)
4. [Règles de gestion](#règles-de-gestion)
5. [Index et performances](#index-et-performances)
6. [Relations entre tables](#relations-entre-tables)

---

## 🎯 VUE D'ENSEMBLE

### Architecture de la base de données
- **Type**: Base de données relationnelle
- **SGBD**: MySQL / MariaDB
- **Normalisation**: 3NF (Troisième Forme Normale)
- **Nombre de tables**: 10
- **Tables entités**: 7
- **Tables de liaison**: 2
- **Tables système**: 1 (ADMIN)

### Schéma général
```
┌─────────────┐
│   CLIENT    │───┐
└─────────────┘   │
                  ├──► ┌──────────┐      ┌──────────────┐
┌─────────────┐   │    │  PANIER  │──────│PANIER_PRODUIT│
│   ADMIN     │   │    └──────────┘      └──────────────┘
└─────────────┘   │                               │
                  │                               ▼
┌─────────────┐   │    ┌──────────┐      ┌────────────┐
│  CATEGORIE  │───┼───►│ PRODUIT  │◄─────│  COMMANDE_ │
└─────────────┘   │    └──────────┘      │  PRODUIT   │
                  │         ▲            └────────────┘
┌─────────────┐   │         │                   ▲
│    SAUCE    │───┤         │                   │
└─────────────┘   │         │            ┌──────────┐
                  │         │            │ COMMANDE │
┌─────────────┐   │         │            └──────────┘
│ TAILLE_     │───┘         │                   ▲
│ BOISSON     │             │                   │
└─────────────┘             │                   │
                            └───────────────────┘
```

---

## 📦 TABLES PRINCIPALES

### 1️⃣ TABLE: **CLIENT**

**Description**: Gestion des clients avec système de fidélité

| Colonne | Type | Contraintes | Valeur par défaut | Description |
|---------|------|-------------|-------------------|-------------|
| `id` | BIGINT | PRIMARY KEY, AUTO_INCREMENT | - | Identifiant unique du client |
| `prenom` | VARCHAR(100) | NOT NULL | - | Prénom du client |
| `nom` | VARCHAR(100) | NOT NULL | - | Nom de famille du client |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | - | Adresse email (identifiant de connexion) |
| `mot_de_passe` | VARCHAR(255) | NOT NULL | - | Mot de passe hashé (bcrypt) |
| `points_fidelite` | INT | NOT NULL, >= 0 | 0 | Points de fidélité accumulés |
| `date_creation` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Date de création du compte |

**Index**:
- `PRIMARY KEY (id)`
- `UNIQUE INDEX idx_client_email (email)`
- `INDEX idx_client_points (points_fidelite)` - Pour les recherches par niveau de fidélité

**Règles métier**:
- L'email doit être unique dans le système
- Le mot de passe doit être hashé avec bcrypt (coût 12)
- Les points de fidélité ne peuvent pas être négatifs
- Un client peut avoir 0 ou plusieurs commandes
- Un client peut avoir 0 ou 1 panier actif

---

### 2️⃣ TABLE: **ADMIN**

**Description**: Comptes administrateurs pour la gestion du stock

| Colonne | Type | Contraintes | Valeur par défaut | Description |
|---------|------|-------------|-------------------|-------------|
| `id` | BIGINT | PRIMARY KEY, AUTO_INCREMENT | - | Identifiant unique de l'admin |
| `nom` | VARCHAR(100) | NOT NULL | - | Nom de l'administrateur |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | - | Email de connexion admin |
| `mot_de_passe` | VARCHAR(255) | NOT NULL | - | Mot de passe hashé (bcrypt) |

**Index**:
- `PRIMARY KEY (id)`
- `UNIQUE INDEX idx_admin_email (email)`

**Règles métier**:
- Accès complet à la gestion du stock
- Email unique dans la table ADMIN
- Pas de système de points de fidélité

---

### 3️⃣ TABLE: **CATEGORIE**

**Description**: Catégories de produits (menus, sandwiches, boissons, etc.)

| Colonne | Type | Contraintes | Valeur par défaut | Description |
|---------|------|-------------|-------------------|-------------|
| `id` | BIGINT | PRIMARY KEY, AUTO_INCREMENT | - | Identifiant unique de la catégorie |
| `nom` | VARCHAR(100) | NOT NULL, UNIQUE | - | Nom de la catégorie |

**Index**:
- `PRIMARY KEY (id)`
- `UNIQUE INDEX idx_categorie_nom (nom)`

**Valeurs prédéfinies**:
1. **Menu** - Menus complets avec boisson et accompagnement
2. **Sandwiches** - Burgers individuels
3. **Wraps** - Wraps et alternatives
4. **Frites** - Toutes tailles de frites
5. **Boissons Froides** - Sodas, eau, jus
6. **Encas** - Cheeseburger, nuggets
7. **Desserts** - McFlurry, brownies, glaces

**Règles métier**:
- Les catégories sont prédéfinies et rarement modifiées
- Une catégorie peut contenir 0 ou plusieurs produits

---

### 4️⃣ TABLE: **PRODUIT**

**Description**: Catalogue complet des produits vendus (RG-001: Stock management)

| Colonne | Type | Contraintes | Valeur par défaut | Description |
|---------|------|-------------|-------------------|-------------|
| `id` | BIGINT | PRIMARY KEY, AUTO_INCREMENT | - | Identifiant unique du produit |
| `nom` | VARCHAR(200) | NOT NULL | - | Nom du produit |
| `description` | TEXT | NULL | NULL | Description détaillée |
| `prix` | DECIMAL(10,2) | NOT NULL, > 0 | - | Prix unitaire en euros |
| `stock` | INT | NOT NULL, >= 0 | 0 | Quantité en stock |
| `id_categorie` | BIGINT | NOT NULL, FOREIGN KEY | - | Référence vers CATEGORIE |
| `image` | VARCHAR(255) | NULL | NULL | URL de l'image du produit |
| `date_creation` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Date d'ajout du produit |

**Index**:
- `PRIMARY KEY (id)`
- `INDEX idx_produit_categorie (id_categorie)` - Pour filtres par catégorie
- `INDEX idx_produit_stock (stock)` - Pour afficher produits disponibles
- `INDEX idx_produit_nom (nom)` - Pour recherche textuelle

**Clés étrangères**:
```sql
FOREIGN KEY (id_categorie) REFERENCES CATEGORIE(id) ON DELETE RESTRICT
```

**Règles métier (RG-001)**:
- Si `stock = 0` → Produit indisponible à la commande
- Le prix doit être strictement positif
- Le stock ne peut pas être négatif
- Lors d'une commande, décrémenter le stock automatiquement

**Exemples de produits**:
- BigMac: 5.50€, Catégorie: Sandwiches
- Menu 280: 8.90€, Catégorie: Menu
- Coca-Cola 50cl: 2.40€, Catégorie: Boissons Froides
- Frites Moyenne: 2.50€, Catégorie: Frites

---

### 5️⃣ TABLE: **SAUCE**

**Description**: Sauces disponibles pour les menus

| Colonne | Type | Contraintes | Valeur par défaut | Description |
|---------|------|-------------|-------------------|-------------|
| `id` | BIGINT | PRIMARY KEY, AUTO_INCREMENT | - | Identifiant unique de la sauce |
| `nom` | VARCHAR(100) | NOT NULL, UNIQUE | - | Nom de la sauce |

**Index**:
- `PRIMARY KEY (id)`
- `UNIQUE INDEX idx_sauce_nom (nom)`

**Valeurs prédéfinies**:
1. Barbecue
2. Moutarde
3. Cremy-Deluxe
4. Ketchup
5. Chinoise
6. Curry
7. Pomme-Frite

**Règles métier**:
- Maximum 2 sauces par menu (règle applicative)
- Les sauces sont stockées dans le champ JSON `details` des tables PANIER_PRODUIT et COMMANDE_PRODUIT

---

### 6️⃣ TABLE: **TAILLE_BOISSON**

**Description**: Tailles de boissons avec suppléments de prix

| Colonne | Type | Contraintes | Valeur par défaut | Description |
|---------|------|-------------|-------------------|-------------|
| `id` | BIGINT | PRIMARY KEY, AUTO_INCREMENT | - | Identifiant unique |
| `nom` | VARCHAR(50) | NOT NULL, UNIQUE | - | Nom de la taille (ex: 30cl, 50cl) |
| `volume` | INT | NOT NULL, > 0 | - | Volume en centilitres |
| `supplement_prix` | DECIMAL(10,2) | NOT NULL, >= 0 | 0.00 | Supplément de prix en euros |

**Index**:
- `PRIMARY KEY (id)`
- `UNIQUE INDEX idx_taille_nom (nom)`

**Valeurs prédéfinies**:
| Taille | Volume | Supplément |
|--------|--------|------------|
| 30cl | 30 | 0.00€ |
| 50cl | 50 | +0.50€ |

**Règles métier**:
- Le volume doit être strictement positif
- Le supplément de prix peut être 0
- Taille stockée dans le champ JSON `details` des tables de liaison

---

### 7️⃣ TABLE: **PANIER**

**Description**: Panier temporaire avant validation de commande

| Colonne | Type | Contraintes | Valeur par défaut | Description |
|---------|------|-------------|-------------------|-------------|
| `id` | BIGINT | PRIMARY KEY, AUTO_INCREMENT | - | Identifiant unique du panier |
| `session_id` | VARCHAR(255) | NOT NULL | - | ID de session utilisateur |
| `date_creation` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Date de création du panier |
| `client_id` | BIGINT | NULL, FOREIGN KEY | NULL | Référence client (NULL si anonyme) |

**Index**:
- `PRIMARY KEY (id)`
- `INDEX idx_panier_session (session_id)` - Pour récupération rapide du panier actif
- `INDEX idx_panier_client (client_id)` - Pour lier au compte client

**Clés étrangères**:
```sql
FOREIGN KEY (client_id) REFERENCES CLIENT(id) ON DELETE SET NULL
```

**Règles métier**:
- Un panier est temporaire et supprimé après confirmation de commande
- Un utilisateur anonyme peut avoir un panier (client_id = NULL)
- Un client connecté peut reprendre son panier sur n'importe quel terminal
- Les paniers inactifs > 24h doivent être purgés (tâche CRON)

---

### 8️⃣ TABLE: **COMMANDE**

**Description**: Commandes validées après paiement (RG-007)

| Colonne | Type | Contraintes | Valeur par défaut | Description |
|---------|------|-------------|-------------------|-------------|
| `id` | BIGINT | PRIMARY KEY, AUTO_INCREMENT | - | Identifiant unique |
| `numero_commande` | VARCHAR(20) | NOT NULL, UNIQUE | - | Numéro affiché au client (ex: CMD001) |
| `numero_chevalet` | INT | NOT NULL | - | Numéro de table/chevalet (001-999) |
| `type_commande` | ENUM | NOT NULL | - | 'sur_place' ou 'a_emporter' |
| `mode_paiement` | ENUM | NOT NULL | - | 'carte' ou 'especes' |
| `montant_total` | DECIMAL(10,2) | NOT NULL, > 0 | - | Montant total payé |
| `date_creation` | TIMESTAMP | NOT NULL | CURRENT_TIMESTAMP | Date/heure de la commande |
| `client_id` | BIGINT | NULL, FOREIGN KEY | NULL | Référence client (NULL si anonyme) |

**Index**:
- `PRIMARY KEY (id)`
- `UNIQUE INDEX idx_commande_numero (numero_commande)`
- `INDEX idx_commande_date (date_creation)` - Pour statistiques par période
- `INDEX idx_commande_client (client_id)` - Pour historique client
- `INDEX idx_commande_chevalet (numero_chevalet)` - Pour affichage cuisine

**Clés étrangères**:
```sql
FOREIGN KEY (client_id) REFERENCES CLIENT(id) ON DELETE SET NULL
```

**Types de données ENUM**:
- `type_commande`: ('sur_place', 'a_emporter')
- `mode_paiement`: ('carte', 'especes')

**Règles métier (RG-007, RG-004)**:
- Une commande est créée **uniquement après validation du paiement**
- Le numéro de chevalet doit être entre 001 et 999
- Le numéro de commande suit le format: `CMD` + timestamp + random
- Montant total doit correspondre à la somme des produits
- Points de fidélité attribués: 1 point par euro dépensé (arrondi inférieur)

---

## 🔗 TABLES DE LIAISON

### 9️⃣ TABLE: **PANIER_PRODUIT**

**Description**: Table de liaison entre PANIER et PRODUIT (relation N:M)

| Colonne | Type | Contraintes | Valeur par défaut | Description |
|---------|------|-------------|-------------------|-------------|
| `id` | BIGINT | PRIMARY KEY, AUTO_INCREMENT | - | Identifiant unique |
| `id_panier` | BIGINT | NOT NULL, FOREIGN KEY | - | Référence vers PANIER |
| `id_produit` | BIGINT | NOT NULL, FOREIGN KEY | - | Référence vers PRODUIT |
| `quantite` | INT | NOT NULL, > 0 | 1 | Quantité du produit |
| `prix_unitaire` | DECIMAL(10,2) | NOT NULL, > 0 | - | Prix au moment de l'ajout |
| `details` | JSON | NULL | NULL | Détails de personnalisation |

**Index**:
- `PRIMARY KEY (id)`
- `INDEX idx_pp_panier (id_panier)` - Pour récupérer contenu du panier
- `INDEX idx_pp_produit (id_produit)` - Pour analytics

**Clés étrangères**:
```sql
FOREIGN KEY (id_panier) REFERENCES PANIER(id) ON DELETE CASCADE
FOREIGN KEY (id_produit) REFERENCES PRODUIT(id) ON DELETE RESTRICT
```

**Structure du champ JSON `details`**:
```json
{
  "sauces": ["Barbecue", "Ketchup"],
  "taille_boisson": "50cl",
  "personnalisation": "Sans cornichons"
}
```

**Règles métier**:
- Cascade: Supprimer le panier → Supprimer automatiquement toutes les lignes
- Le prix_unitaire est figé au moment de l'ajout (indépendant du prix actuel)
- Quantité doit être >= 1

---

### 🔟 TABLE: **COMMANDE_PRODUIT**

**Description**: Table de liaison entre COMMANDE et PRODUIT (historique immuable)

| Colonne | Type | Contraintes | Valeur par défaut | Description |
|---------|------|-------------|-------------------|-------------|
| `id` | BIGINT | PRIMARY KEY, AUTO_INCREMENT | - | Identifiant unique |
| `id_commande` | BIGINT | NOT NULL, FOREIGN KEY | - | Référence vers COMMANDE |
| `id_produit` | BIGINT | NOT NULL, FOREIGN KEY | - | Référence vers PRODUIT |
| `quantite` | INT | NOT NULL, > 0 | 1 | Quantité commandée |
| `prix_unitaire` | DECIMAL(10,2) | NOT NULL, > 0 | - | Prix au moment de l'achat |
| `details` | JSON | NULL | NULL | Personnalisations client |

**Index**:
- `PRIMARY KEY (id)`
- `INDEX idx_cp_commande (id_commande)` - Pour ticket de caisse
- `INDEX idx_cp_produit (id_produit)` - Pour statistiques ventes

**Clés étrangères**:
```sql
FOREIGN KEY (id_commande) REFERENCES COMMANDE(id) ON DELETE CASCADE
FOREIGN KEY (id_produit) REFERENCES PRODUIT(id) ON DELETE RESTRICT
```

**Structure du champ JSON `details`**:
```json
{
  "sauces": ["Moutarde", "Curry"],
  "taille_boisson": "30cl",
  "notes": "Bien cuit"
}
```

**Règles métier**:
- **Historique immuable**: Les prix sont conservés même si le produit change de prix
- Cascade: Supprimer une commande → Supprimer ses produits
- Restrict: Impossible de supprimer un produit ayant un historique de vente
- Cette table est utilisée pour les statistiques et analyses de vente

---

## 📐 RÈGLES DE GESTION

### RG-001: Gestion du stock
- **Description**: Si `PRODUIT.stock = 0`, le produit ne doit pas être commandable
- **Tables impliquées**: PRODUIT
- **Implémentation**: Vérification applicative avant ajout au panier

### RG-002: Limite de sauces
- **Description**: Maximum 2 sauces par menu
- **Tables impliquées**: SAUCE, PANIER_PRODUIT, COMMANDE_PRODUIT
- **Implémentation**: Validation du champ JSON `details.sauces` (longueur max = 2)

### RG-003: Points de fidélité
- **Description**: 1 point par euro dépensé (arrondi inférieur)
- **Tables impliquées**: CLIENT, COMMANDE
- **Implémentation**: Mise à jour automatique de `CLIENT.points_fidelite` après commande

### RG-004: Numéro de chevalet
- **Description**: Doit être entre 001 et 999
- **Tables impliquées**: COMMANDE
- **Implémentation**: Contrainte CHECK ou validation applicative

### RG-005: Prix figé
- **Description**: Le prix dans les tables de liaison est historique
- **Tables impliquées**: PANIER_PRODUIT, COMMANDE_PRODUIT
- **Implémentation**: `prix_unitaire` copié depuis PRODUIT au moment de l'ajout

### RG-006: Suppression cascade panier
- **Description**: Supprimer un panier supprime tous ses produits
- **Tables impliquées**: PANIER, PANIER_PRODUIT
- **Implémentation**: `ON DELETE CASCADE`

### RG-007: Commande après paiement
- **Description**: Une commande n'existe qu'après paiement validé
- **Tables impliquées**: COMMANDE
- **Implémentation**: Transaction atomique (paiement + création commande)

### RG-008: Anonymat possible
- **Description**: Un utilisateur non connecté peut commander
- **Tables impliquées**: PANIER, COMMANDE
- **Implémentation**: `client_id` nullable

### RG-009: Décrémentation stock
- **Description**: Décrémenter le stock lors de la validation de commande
- **Tables impliquées**: PRODUIT, COMMANDE
- **Implémentation**: Transaction ACID (stock - quantité)

### RG-010: Purge paniers inactifs
- **Description**: Supprimer les paniers > 24h sans activité
- **Tables impliquées**: PANIER
- **Implémentation**: Tâche CRON quotidienne

---

## ⚡ INDEX ET PERFORMANCES

### Index primaires
Toutes les tables ont un index PRIMARY KEY sur la colonne `id` (AUTO_INCREMENT).

### Index uniques
| Table | Colonne | Raison |
|-------|---------|--------|
| CLIENT | email | Authentification unique |
| ADMIN | email | Authentification unique |
| CATEGORIE | nom | Pas de doublons |
| SAUCE | nom | Pas de doublons |
| TAILLE_BOISSON | nom | Pas de doublons |
| COMMANDE | numero_commande | Identification unique client |

### Index de performance
| Table | Colonne | Raison | Type de requête optimisée |
|-------|---------|--------|---------------------------|
| PRODUIT | id_categorie | Filtres par catégorie | SELECT WHERE id_categorie |
| PRODUIT | stock | Produits disponibles | SELECT WHERE stock > 0 |
| PRODUIT | nom | Recherche textuelle | SELECT WHERE nom LIKE |
| PANIER | session_id | Récupération panier actif | SELECT WHERE session_id |
| PANIER | client_id | Historique client | SELECT WHERE client_id |
| COMMANDE | date_creation | Statistiques temporelles | SELECT WHERE date_creation BETWEEN |
| COMMANDE | client_id | Historique commandes client | SELECT WHERE client_id |
| COMMANDE | numero_chevalet | Affichage cuisine | SELECT WHERE numero_chevalet |
| PANIER_PRODUIT | id_panier | Contenu du panier | SELECT WHERE id_panier |
| COMMANDE_PRODUIT | id_commande | Ticket de caisse | SELECT WHERE id_commande |

### Requêtes fréquentes optimisées

#### 1. Récupération du panier actif
```sql
SELECT p.*, pp.quantite, pp.details
FROM PANIER pa
JOIN PANIER_PRODUIT pp ON pp.id_panier = pa.id
JOIN PRODUIT p ON p.id = pp.id_produit
WHERE pa.session_id = ?
```
**Index utilisés**: `idx_panier_session`, `idx_pp_panier`, `PRIMARY KEY produit`

#### 2. Produits disponibles par catégorie
```sql
SELECT * FROM PRODUIT
WHERE id_categorie = ? AND stock > 0
ORDER BY nom
```
**Index utilisés**: `idx_produit_categorie`, `idx_produit_stock`

#### 3. Historique commandes client
```sql
SELECT * FROM COMMANDE
WHERE client_id = ?
ORDER BY date_creation DESC
LIMIT 10
```
**Index utilisés**: `idx_commande_client`, `idx_commande_date`

---

## 🔀 RELATIONS ENTRE TABLES

### Diagramme entité-relation (textuel)

```
CLIENT (1) ──────────── (0,n) PANIER
   │
   └── (0,n) COMMANDE

CATEGORIE (1) ──────── (0,n) PRODUIT

PANIER (n) ──────────── (n) PRODUIT
   └─── PANIER_PRODUIT (table de liaison)

COMMANDE (n) ──────────── (n) PRODUIT
   └─── COMMANDE_PRODUIT (table de liaison)

SAUCE (référence dans JSON)
TAILLE_BOISSON (référence dans JSON)
```

### Cardinalités détaillées

| Table Source | Relation | Table Cible | Cardinalité | Description |
|--------------|----------|-------------|-------------|-------------|
| CLIENT | possède | PANIER | 1:n | Un client peut avoir plusieurs paniers (historique) |
| CLIENT | passe | COMMANDE | 1:n | Un client peut avoir plusieurs commandes |
| CATEGORIE | contient | PRODUIT | 1:n | Une catégorie a plusieurs produits |
| PANIER | contient | PRODUIT | n:m | Un panier contient plusieurs produits, un produit est dans plusieurs paniers |
| COMMANDE | contient | PRODUIT | n:m | Une commande contient plusieurs produits |

### Contraintes d'intégrité référentielle

#### Suppressions en cascade (CASCADE)
- `PANIER` → `PANIER_PRODUIT`: Supprimer un panier supprime ses lignes
- `COMMANDE` → `COMMANDE_PRODUIT`: Supprimer une commande supprime ses lignes

#### Suppressions restreintes (RESTRICT)
- `CATEGORIE` → `PRODUIT`: Impossible de supprimer une catégorie avec des produits
- `PRODUIT` → `COMMANDE_PRODUIT`: Impossible de supprimer un produit déjà commandé

#### Suppressions avec NULL (SET NULL)
- `CLIENT` → `PANIER`: Supprimer un client met `client_id` à NULL
- `CLIENT` → `COMMANDE`: Supprimer un client met `client_id` à NULL (historique conservé)

---

## 📊 STATISTIQUES DE LA BASE

### Volume estimé de données

| Table | Nb lignes estimé | Croissance | Remarques |
|-------|------------------|------------|-----------|
| CLIENT | 1 000 - 10 000 | Lente | Base fidèle stable |
| ADMIN | 5 - 20 | Statique | Équipe restreinte |
| CATEGORIE | 10 | Statique | Prédéfinies |
| PRODUIT | 50 - 200 | Lente | Carte évolutive |
| SAUCE | 7 - 15 | Statique | Standard |
| TAILLE_BOISSON | 2 - 5 | Statique | Standard |
| PANIER | 0 - 100 | Variable | Purgé régulièrement |
| PANIER_PRODUIT | 0 - 500 | Variable | Temporaire |
| COMMANDE | 10 000 - 1M | **Rapide** | ~100-500/jour |
| COMMANDE_PRODUIT | 30 000 - 3M | **Rapide** | Historique complet |

### Tables critiques pour les performances
1. **COMMANDE** et **COMMANDE_PRODUIT**: Croissance rapide, nécessite partitionnement après 1M lignes
2. **PRODUIT**: Table centrale, tous les JOIN passent par elle
3. **PANIER**: Temporaire mais accès très fréquent

---

## 🔒 SÉCURITÉ ET CONTRAINTES

### Contraintes CHECK à implémenter
```sql
ALTER TABLE PRODUIT ADD CONSTRAINT chk_produit_prix CHECK (prix > 0);
ALTER TABLE PRODUIT ADD CONSTRAINT chk_produit_stock CHECK (stock >= 0);
ALTER TABLE COMMANDE ADD CONSTRAINT chk_commande_chevalet CHECK (numero_chevalet BETWEEN 1 AND 999);
ALTER TABLE COMMANDE ADD CONSTRAINT chk_commande_montant CHECK (montant_total > 0);
ALTER TABLE PANIER_PRODUIT ADD CONSTRAINT chk_pp_quantite CHECK (quantite > 0);
ALTER TABLE COMMANDE_PRODUIT ADD CONSTRAINT chk_cp_quantite CHECK (quantite > 0);
```

### Champs sensibles
- `CLIENT.mot_de_passe` : **HASHÉ** (bcrypt, coût 12)
- `ADMIN.mot_de_passe` : **HASHÉ** (bcrypt, coût 12)
- `CLIENT.email` : Données personnelles (RGPD)
- `COMMANDE.montant_total` : Données financières

### Recommandations RGPD
- Anonymisation des données client après 3 ans d'inactivité
- Droit à l'oubli: `SET NULL` sur `client_id` dans COMMANDE (historique conservé)
- Logs d'accès aux données personnelles

---

## 📝 NOTES D'IMPLÉMENTATION

### Technologies détectées
- **Backend**: PHP avec namespace WCDO
- **Tests**: PHPUnit (tests unitaires pour toutes les entités)
- **ORM**: Probablement doctrine ou eloquent (à confirmer)
- **Frontend**: JSON-based (bd.json, produits.json)

### Scripts DDL recommandés
Voir fichier séparé: `schema-creation-wcdo.sql`

### Migrations à prévoir
1. **Migration initiale**: Création de toutes les tables
2. **Migration sauces**: Insertion des 7 sauces prédéfinies
3. **Migration catégories**: Insertion des 7 catégories
4. **Migration tailles**: Insertion des tailles de boissons
5. **Migration produits**: Import depuis `bd.json`

---

## 📚 RÉFÉRENCES

- **MCD complet**: `_bmad-output/bmb-creations/wcdo-mcd-schema.md`
- **Plan de tests TDD**: `_bmad-output/bmb-creations/wcdo-plan-tests-tdd.md`
- **Tests entités**: `tests/Entities/`
- **Données JSON**: `Front/bd.json`, `Front/images/produits.json`

---

**Document généré par DB-ARCHITECT Agent**  
*Pour toute modification du schéma, consulter cet expert avant déploiement.*

---

# 📖 DICTIONNAIRE DE DONNÉES - FORMAT NORMALISÉ

*Référentiel exhaustif des attributs du système WCDO*

---

## TABLE: CLIENT

| Code Attribut | Libellé | Type | Taille | Contraintes | Valeur par défaut | Description |
|---------------|---------|------|--------|-------------|-------------------|-------------|
| CLI_ID | Identifiant client | BIGINT | 20 | PK, AUTO_INCREMENT, NOT NULL | AUTO | Clé primaire unique du client |
| CLI_PRENOM | Prénom | VARCHAR | 100 | NOT NULL | - | Prénom du client |
| CLI_NOM | Nom de famille | VARCHAR | 100 | NOT NULL | - | Nom de famille du client |
| CLI_EMAIL | Adresse email | VARCHAR | 255 | UNIQUE, NOT NULL | - | Email unique pour authentification |
| CLI_MDP | Mot de passe | VARCHAR | 255 | NOT NULL | - | Hash bcrypt du mot de passe (coût 12) |
| CLI_POINTS | Points de fidélité | INT | 11 | NOT NULL, >= 0 | 0 | Nombre de points accumulés |
| CLI_DATE_CREATION | Date de création | TIMESTAMP | - | NOT NULL | CURRENT_TIMESTAMP | Horodatage de création du compte |

---

## TABLE: ADMIN

| Code Attribut | Libellé | Type | Taille | Contraintes | Valeur par défaut | Description |
|---------------|---------|------|--------|-------------|-------------------|-------------|
| ADM_ID | Identifiant admin | BIGINT | 20 | PK, AUTO_INCREMENT, NOT NULL | AUTO | Clé primaire unique de l'administrateur |
| ADM_NOM | Nom administrateur | VARCHAR | 100 | NOT NULL | - | Nom complet de l'administrateur |
| ADM_EMAIL | Email admin | VARCHAR | 255 | UNIQUE, NOT NULL | - | Email unique pour connexion admin |
| ADM_MDP | Mot de passe admin | VARCHAR | 255 | NOT NULL | - | Hash bcrypt du mot de passe |

---

## TABLE: CATEGORIE

| Code Attribut | Libellé | Type | Taille | Contraintes | Valeur par défaut | Description |
|---------------|---------|------|--------|-------------|-------------------|-------------|
| CAT_ID | Identifiant catégorie | BIGINT | 20 | PK, AUTO_INCREMENT, NOT NULL | AUTO | Clé primaire unique de la catégorie |
| CAT_NOM | Nom catégorie | VARCHAR | 100 | UNIQUE, NOT NULL | - | Nom de la catégorie (Menu, Sandwiches, etc.) |

**Domaine de valeurs CAT_NOM**:
- `Menu`
- `Sandwiches`
- `Wraps`
- `Frites`
- `Boissons Froides`
- `Encas`
- `Desserts`

---

## TABLE: PRODUIT

| Code Attribut | Libellé | Type | Taille | Contraintes | Valeur par défaut | Description |
|---------------|---------|------|--------|-------------|-------------------|-------------|
| PRO_ID | Identifiant produit | BIGINT | 20 | PK, AUTO_INCREMENT, NOT NULL | AUTO | Clé primaire unique du produit |
| PRO_NOM | Nom produit | VARCHAR | 200 | NOT NULL | - | Nom commercial du produit |
| PRO_DESCRIPTION | Description | TEXT | 65535 | NULL | NULL | Description détaillée du produit |
| PRO_PRIX | Prix unitaire | DECIMAL | 10,2 | NOT NULL, > 0 | - | Prix de vente en euros (ex: 5.50) |
| PRO_STOCK | Quantité en stock | INT | 11 | NOT NULL, >= 0 | 0 | Quantité disponible en stock |
| PRO_ID_CATEGORIE | Catégorie | BIGINT | 20 | FK(CATEGORIE), NOT NULL | - | Référence vers la table CATEGORIE |
| PRO_IMAGE | URL image | VARCHAR | 255 | NULL | NULL | Chemin ou URL de l'image produit |
| PRO_DATE_CREATION | Date de création | TIMESTAMP | - | NOT NULL | CURRENT_TIMESTAMP | Date d'ajout du produit au catalogue |

---

## TABLE: SAUCE

| Code Attribut | Libellé | Type | Taille | Contraintes | Valeur par défaut | Description |
|---------------|---------|------|--------|-------------|-------------------|-------------|
| SAU_ID | Identifiant sauce | BIGINT | 20 | PK, AUTO_INCREMENT, NOT NULL | AUTO | Clé primaire unique de la sauce |
| SAU_NOM | Nom sauce | VARCHAR | 100 | UNIQUE, NOT NULL | - | Nom de la sauce |

**Domaine de valeurs SAU_NOM**:
- `Barbecue`
- `Moutarde`
- `Cremy-Deluxe`
- `Ketchup`
- `Chinoise`
- `Curry`
- `Pomme-Frite`

---

## TABLE: TAILLE_BOISSON

| Code Attribut | Libellé | Type | Taille | Contraintes | Valeur par défaut | Description |
|---------------|---------|------|--------|-------------|-------------------|-------------|
| TAI_ID | Identifiant taille | BIGINT | 20 | PK, AUTO_INCREMENT, NOT NULL | AUTO | Clé primaire unique de la taille |
| TAI_NOM | Nom taille | VARCHAR | 50 | UNIQUE, NOT NULL | - | Libellé de la taille (30cl, 50cl) |
| TAI_VOLUME | Volume | INT | 11 | NOT NULL, > 0 | - | Volume en centilitres |
| TAI_SUPPLEMENT | Supplément prix | DECIMAL | 10,2 | NOT NULL, >= 0 | 0.00 | Supplément de prix en euros |

**Domaine de valeurs TAI_NOM**:
- `30cl` (volume: 30, supplément: 0.00)
- `50cl` (volume: 50, supplément: 0.50)

---

## TABLE: PANIER

| Code Attribut | Libellé | Type | Taille | Contraintes | Valeur par défaut | Description |
|---------------|---------|------|--------|-------------|-------------------|-------------|
| PAN_ID | Identifiant panier | BIGINT | 20 | PK, AUTO_INCREMENT, NOT NULL | AUTO | Clé primaire unique du panier |
| PAN_SESSION_ID | ID de session | VARCHAR | 255 | NOT NULL | - | Identifiant de session utilisateur |
| PAN_DATE_CREATION | Date de création | TIMESTAMP | - | NOT NULL | CURRENT_TIMESTAMP | Horodatage de création du panier |
| PAN_ID_CLIENT | Client | BIGINT | 20 | FK(CLIENT), NULL | NULL | Référence vers CLIENT (NULL si anonyme) |

---

## TABLE: PANIER_PRODUIT

| Code Attribut | Libellé | Type | Taille | Contraintes | Valeur par défaut | Description |
|---------------|---------|------|--------|-------------|-------------------|-------------|
| PPR_ID | Identifiant ligne | BIGINT | 20 | PK, AUTO_INCREMENT, NOT NULL | AUTO | Clé primaire unique de la ligne |
| PPR_ID_PANIER | Panier | BIGINT | 20 | FK(PANIER), NOT NULL | - | Référence vers PANIER |
| PPR_ID_PRODUIT | Produit | BIGINT | 20 | FK(PRODUIT), NOT NULL | - | Référence vers PRODUIT |
| PPR_QUANTITE | Quantité | INT | 11 | NOT NULL, > 0 | 1 | Nombre d'unités du produit |
| PPR_PRIX_UNITAIRE | Prix unitaire | DECIMAL | 10,2 | NOT NULL, > 0 | - | Prix au moment de l'ajout au panier |
| PPR_DETAILS | Détails JSON | JSON | - | NULL | NULL | Personnalisations (sauces, taille, etc.) |

**Structure du champ JSON PPR_DETAILS**:
```json
{
  "sauces": ["string", "string"],
  "taille_boisson": "string",
  "personnalisation": "string"
}
```

---

## TABLE: COMMANDE

| Code Attribut | Libellé | Type | Taille | Contraintes | Valeur par défaut | Description |
|---------------|---------|------|--------|-------------|-------------------|-------------|
| CMD_ID | Identifiant commande | BIGINT | 20 | PK, AUTO_INCREMENT, NOT NULL | AUTO | Clé primaire unique de la commande |
| CMD_NUMERO | Numéro de commande | VARCHAR | 20 | UNIQUE, NOT NULL | - | Numéro affiché au client (ex: CMD001) |
| CMD_CHEVALET | Numéro de chevalet | INT | 11 | NOT NULL, 1-999 | - | Numéro de table/chevalet (001 à 999) |
| CMD_TYPE | Type de commande | ENUM | - | NOT NULL | - | 'sur_place' ou 'a_emporter' |
| CMD_PAIEMENT | Mode de paiement | ENUM | - | NOT NULL | - | 'carte' ou 'especes' |
| CMD_MONTANT_TOTAL | Montant total | DECIMAL | 10,2 | NOT NULL, > 0 | - | Montant total de la commande en euros |
| CMD_DATE_CREATION | Date de création | TIMESTAMP | - | NOT NULL | CURRENT_TIMESTAMP | Horodatage de la commande |
| CMD_ID_CLIENT | Client | BIGINT | 20 | FK(CLIENT), NULL | NULL | Référence vers CLIENT (NULL si anonyme) |

**Domaine de valeurs CMD_TYPE**:
- `sur_place`
- `a_emporter`

**Domaine de valeurs CMD_PAIEMENT**:
- `carte`
- `especes`

---

## TABLE: COMMANDE_PRODUIT

| Code Attribut | Libellé | Type | Taille | Contraintes | Valeur par défaut | Description |
|---------------|---------|------|--------|-------------|-------------------|-------------|
| CPR_ID | Identifiant ligne | BIGINT | 20 | PK, AUTO_INCREMENT, NOT NULL | AUTO | Clé primaire unique de la ligne |
| CPR_ID_COMMANDE | Commande | BIGINT | 20 | FK(COMMANDE), NOT NULL | - | Référence vers COMMANDE |
| CPR_ID_PRODUIT | Produit | BIGINT | 20 | FK(PRODUIT), NOT NULL | - | Référence vers PRODUIT |
| CPR_QUANTITE | Quantité | INT | 11 | NOT NULL, > 0 | 1 | Nombre d'unités commandées |
| CPR_PRIX_UNITAIRE | Prix unitaire | DECIMAL | 10,2 | NOT NULL, > 0 | - | Prix historique au moment de l'achat |
| CPR_DETAILS | Détails JSON | JSON | - | NULL | NULL | Personnalisations client |

**Structure du champ JSON CPR_DETAILS**:
```json
{
  "sauces": ["string", "string"],
  "taille_boisson": "string",
  "notes": "string"
}
```

---

## LÉGENDE DES CODES

### Préfixes de tables
- **CLI_** : CLIENT
- **ADM_** : ADMIN
- **CAT_** : CATEGORIE
- **PRO_** : PRODUIT
- **SAU_** : SAUCE
- **TAI_** : TAILLE_BOISSON
- **PAN_** : PANIER
- **PPR_** : PANIER_PRODUIT
- **CMD_** : COMMANDE
- **CPR_** : COMMANDE_PRODUIT

### Contraintes
- **PK** : Clé Primaire (Primary Key)
- **FK** : Clé Étrangère (Foreign Key)
- **UNIQUE** : Valeur unique dans la table
- **NOT NULL** : Valeur obligatoire
- **NULL** : Valeur optionnelle
- **AUTO_INCREMENT** : Valeur générée automatiquement
- **>= 0** : Supérieur ou égal à zéro
- **> 0** : Strictement positif

### Types de données
- **BIGINT** : Entier 64 bits (-9223372036854775808 à 9223372036854775807)
- **INT** : Entier 32 bits (-2147483648 à 2147483647)
- **VARCHAR(n)** : Chaîne de caractères de longueur variable (max n)
- **TEXT** : Chaîne de caractères longue (65 535 caractères)
- **DECIMAL(p,d)** : Nombre décimal (p chiffres total, d après la virgule)
- **TIMESTAMP** : Date et heure (YYYY-MM-DD HH:MM:SS)
- **ENUM** : Énumération de valeurs prédéfinies
- **JSON** : Objet JSON natif

---

## RÈGLES DE NOMMAGE

### Conventions appliquées
1. **Tables** : Nom au singulier, MAJUSCULES (ex: CLIENT, PRODUIT)
2. **Attributs** : Préfixe de table + nom descriptif (ex: CLI_EMAIL, PRO_PRIX)
3. **Clés primaires** : Toujours suffixe `_ID` (ex: CLI_ID, PRO_ID)
4. **Clés étrangères** : Préfixe + `ID_` + table référencée (ex: PRO_ID_CATEGORIE)
5. **Dates** : Suffixe `_DATE_` + action (ex: CLI_DATE_CREATION)
6. **Montants** : Mots complets sans abréviation (ex: CMD_MONTANT_TOTAL)

### Standards techniques
- Encodage : **UTF-8**
- Collation : **utf8mb4_unicode_ci**
- Moteur : **InnoDB** (support transactions ACID)
- Format dates : **ISO 8601** (YYYY-MM-DD HH:MM:SS)
- Format prix : **DECIMAL(10,2)** (centimes d'euros)

---

## VOLUMÉTRIE PRÉVISIONNELLE

| Table | Taille ligne (bytes) | Nb lignes An 1 | Nb lignes An 3 | Taille estimée An 3 |
|-------|---------------------|----------------|----------------|---------------------|
| CLIENT | ~600 | 2 000 | 10 000 | ~6 MB |
| ADMIN | ~500 | 10 | 20 | ~10 KB |
| CATEGORIE | ~120 | 10 | 15 | ~2 KB |
| PRODUIT | ~800 | 100 | 200 | ~160 KB |
| SAUCE | ~120 | 7 | 12 | ~1.5 KB |
| TAILLE_BOISSON | ~150 | 2 | 5 | ~750 bytes |
| PANIER | ~300 | 50 | 100 | ~30 KB |
| PANIER_PRODUIT | ~400 | 200 | 500 | ~200 KB |
| COMMANDE | ~700 | 50 000 | 500 000 | ~350 MB |
| COMMANDE_PRODUIT | ~500 | 150 000 | 1 500 000 | ~750 MB |
| **TOTAL** | - | **~200 000** | **~2 000 000** | **~1.1 GB** |

---

## MATRICE DES DÉPENDANCES FONCTIONNELLES

### CLIENT
- `CLI_ID` → `{CLI_PRENOM, CLI_NOM, CLI_EMAIL, CLI_MDP, CLI_POINTS, CLI_DATE_CREATION}`
- `CLI_EMAIL` → `{CLI_ID}` (contrainte UNIQUE)

### PRODUIT
- `PRO_ID` → `{PRO_NOM, PRO_DESCRIPTION, PRO_PRIX, PRO_STOCK, PRO_ID_CATEGORIE, PRO_IMAGE, PRO_DATE_CREATION}`
- `PRO_ID_CATEGORIE` → `{CAT_NOM}` (dépendance transitive)

### COMMANDE
- `CMD_ID` → `{CMD_NUMERO, CMD_CHEVALET, CMD_TYPE, CMD_PAIEMENT, CMD_MONTANT_TOTAL, CMD_DATE_CREATION, CMD_ID_CLIENT}`
- `CMD_NUMERO` → `{CMD_ID}` (contrainte UNIQUE)

### PANIER_PRODUIT
- `PPR_ID` → `{PPR_ID_PANIER, PPR_ID_PRODUIT, PPR_QUANTITE, PPR_PRIX_UNITAIRE, PPR_DETAILS}`
- `{PPR_ID_PANIER, PPR_ID_PRODUIT}` → `{PPR_QUANTITE, PPR_PRIX_UNITAIRE, PPR_DETAILS}` (dépendance fonctionnelle composite)

### COMMANDE_PRODUIT
- `CPR_ID` → `{CPR_ID_COMMANDE, CPR_ID_PRODUIT, CPR_QUANTITE, CPR_PRIX_UNITAIRE, CPR_DETAILS}`
- `{CPR_ID_COMMANDE, CPR_ID_PRODUIT}` → `{CPR_QUANTITE, CPR_PRIX_UNITAIRE, CPR_DETAILS}` (historique immuable)

---

## CONTRAINTES D'INTÉGRITÉ DÉTAILLÉES

### Contraintes de domaine
```
CONSTRAINT chk_cli_points CHECK (CLI_POINTS >= 0)
CONSTRAINT chk_pro_prix CHECK (PRO_PRIX > 0)
CONSTRAINT chk_pro_stock CHECK (PRO_STOCK >= 0)
CONSTRAINT chk_tai_volume CHECK (TAI_VOLUME > 0)
CONSTRAINT chk_tai_supplement CHECK (TAI_SUPPLEMENT >= 0)
CONSTRAINT chk_ppr_quantite CHECK (PPR_QUANTITE > 0)
CONSTRAINT chk_ppr_prix CHECK (PPR_PRIX_UNITAIRE > 0)
CONSTRAINT chk_cmd_chevalet CHECK (CMD_CHEVALET BETWEEN 1 AND 999)
CONSTRAINT chk_cmd_montant CHECK (CMD_MONTANT_TOTAL > 0)
CONSTRAINT chk_cpr_quantite CHECK (CPR_QUANTITE > 0)
CONSTRAINT chk_cpr_prix CHECK (CPR_PRIX_UNITAIRE > 0)
```

### Contraintes référentielles
```
FOREIGN KEY (PRO_ID_CATEGORIE) REFERENCES CATEGORIE(CAT_ID) ON DELETE RESTRICT
FOREIGN KEY (PAN_ID_CLIENT) REFERENCES CLIENT(CLI_ID) ON DELETE SET NULL
FOREIGN KEY (PPR_ID_PANIER) REFERENCES PANIER(PAN_ID) ON DELETE CASCADE
FOREIGN KEY (PPR_ID_PRODUIT) REFERENCES PRODUIT(PRO_ID) ON DELETE RESTRICT
FOREIGN KEY (CMD_ID_CLIENT) REFERENCES CLIENT(CLI_ID) ON DELETE SET NULL
FOREIGN KEY (CPR_ID_COMMANDE) REFERENCES COMMANDE(CMD_ID) ON DELETE CASCADE
FOREIGN KEY (CPR_ID_PRODUIT) REFERENCES PRODUIT(PRO_ID) ON DELETE RESTRICT
```

---

**📖 FIN DU DICTIONNAIRE DE DONNÉES NORMALISÉ**

---

## 🎯 MENU DB-ARCHITECT

1. **[DESIGN]** Concevoir schéma de base de données
2. **[OPTIMIZE]** Optimiser schéma existant
3. **[MIGRATE]** Créer migration
4. **[NORMALIZE]** Normaliser base de données
5. **[INDEX]** Créer indexes
6. **[QUERY]** Optimiser requêtes
7. **[RELATIONS]** Gérer relations
8. **[MODEL]** Modélisation données
9. **[SECURITY]** Sécurité BD
10. **[EXIT]** Quitter

**Entrez votre choix (1-10) ou tapez 'help'**
