# MCD WCDO - Modèle Conceptuel de Données

**Projet :** WCDO - Borne de commande  
**Date :** 2026-02-09  
**Version :** 1.0 (Skeletal - Sprint 0)

---

## 📊 ENTITÉS (10 TABLES)

### 1. CLIENT

**Description :** Profil utilisateur authentifié avec historique et points de fidélité

**Attributs :**
- `id` (PK, INT, AUTO_INCREMENT)
- `prenom` (VARCHAR 100, NOT NULL)
- `nom` (VARCHAR 100, NOT NULL)
- `email` (VARCHAR 255, UNIQUE, NOT NULL)
- `mot_de_passe` (VARCHAR 255, NOT NULL) - Hashed avec password_hash()
- `points_fidelite` (INT, DEFAULT 0)
- `date_creation` (DATETIME, DEFAULT CURRENT_TIMESTAMP)

**Règles :**
- Email unique (pas de doublons)
- Points de fidélité : 1€ dépensé = 1 point
- Mot de passe hashé obligatoire

---

### 2. ADMIN

**Description :** Profil de gestion pour administrer les stocks

**Attributs :**
- `id` (PK, INT, AUTO_INCREMENT)
- `nom` (VARCHAR 100, NOT NULL)
- `email` (VARCHAR 255, UNIQUE, NOT NULL)
- `mot_de_passe` (VARCHAR 255, NOT NULL) - Hashed

**Règles :**
- Email unique
- Accès complet à la gestion des stocks

---

### 3. CATEGORIE

**Description :** Classification des produits pour organisation affichage

**Attributs :**
- `id` (PK, INT, AUTO_INCREMENT)
- `nom` (VARCHAR 100, NOT NULL, UNIQUE)

**Valeurs initiales :**
- Menus
- Sandwiches
- Wraps
- Frites
- Boissons froides
- Encas
- Desserts

**Règles :**
- Nom de catégorie unique

---

### 4. PRODUIT

**Description :** Article vendable sur la borne

**Attributs :**
- `id` (PK, INT, AUTO_INCREMENT)
- `nom` (VARCHAR 150, NOT NULL)
- `description` (TEXT, NULL)
- `prix` (DECIMAL 10,2, NOT NULL)
- `stock` (INT, NOT NULL, DEFAULT 0)
- `id_categorie` (FK → CATEGORIE.id, NOT NULL)
- `image` (VARCHAR 255, NULL)
- `date_creation` (DATETIME, DEFAULT CURRENT_TIMESTAMP)

**Règles :**
- Si stock = 0 → Produit indisponible sur la borne
- Prix toujours positif
- Appartient obligatoirement à une catégorie

**Relation :**
- `CATEGORIE` ──(1,n)── `PRODUIT`

---

### 5. SAUCE

**Description :** Options de sauce pour les menus

**Attributs :**
- `id` (PK, INT, AUTO_INCREMENT)
- `nom` (VARCHAR 50, NOT NULL, UNIQUE)

**Valeurs initiales :**
- Barbecue
- Moutarde
- Cremy-Deluxe
- Ketchup
- Chinoise
- Curry
- Pomme-Frite

**Règles :**
- Maximum 2 sauces par menu
- Incluses dans le prix du menu (pas de supplément)

---

### 6. TAILLE_BOISSON

**Description :** Formats de boissons froides

**Attributs :**
- `id` (PK, INT, AUTO_INCREMENT)
- `nom` (VARCHAR 10, NOT NULL, UNIQUE)
- `supplement_prix` (DECIMAL 10,2, NOT NULL, DEFAULT 0.00)

**Valeurs initiales :**
- 30cl → supplement_prix = 0.00
- 50cl → supplement_prix = 0.50

**Règles :**
- 50cl = +0,50€ par rapport au prix de base de la boisson

---

### 7. PANIER

**Description :** Liste temporaire de produits avant validation commande

**Attributs :**
- `id` (PK, INT, AUTO_INCREMENT)
- `session_id` (VARCHAR 255, NOT NULL) - Pour clients anonymes
- `date_creation` (DATETIME, DEFAULT CURRENT_TIMESTAMP)
- `client_id` (FK → CLIENT.id, NULL)

**Règles :**
- Un client = un seul panier actif
- Détruit à la déconnexion ou après transformation en commande
- Peut être créé par client connecté (client_id renseigné) OU anonyme (client_id NULL)

**Relation :**
- `CLIENT` ──(0,n)── `PANIER` (un client peut avoir 0 ou plusieurs paniers temporaires)

---

### 8. PANIER_PRODUIT

**Description :** Table de liaison - Produits dans un panier

**Attributs :**
- `id` (PK, INT, AUTO_INCREMENT)
- `id_panier` (FK → PANIER.id, NOT NULL, ON DELETE CASCADE)
- `id_produit` (FK → PRODUIT.id, NOT NULL)
- `quantite` (INT, NOT NULL, DEFAULT 1)
- `prix_unitaire` (DECIMAL 10,2, NOT NULL) - Prix au moment de l'ajout
- `details` (JSON, NULL) - Pour stocker sauces, taille boisson, composition menu

**Exemple JSON `details` :**
```json
{
  "sauces": ["Barbecue", "Ketchup"],
  "taille_boisson": "50cl",
  "composition_menu": {
    "sandwich": "280",
    "frites": "Maxi Best Of",
    "boisson": "Coca-Cola"
  }
}
```

**Règles :**
- Quantité >= 1
- Prix unitaire figé au moment de l'ajout (pour historique)
- ON DELETE CASCADE : si panier supprimé, produits aussi

**Relations :**
- `PANIER` ──(n,n)── `PRODUIT` via `PANIER_PRODUIT`

---

### 9. COMMANDE

**Description :** Transaction finalisée après paiement validé

**Attributs :**
- `id` (PK, INT, AUTO_INCREMENT)
- `numero_commande` (VARCHAR 20, UNIQUE, NOT NULL) - Généré automatiquement
- `numero_chevalet` (CHAR 3, NOT NULL) - Format 001-999
- `type_commande` (ENUM('sur_place', 'a_emporter'), NOT NULL)
- `mode_paiement` (ENUM('carte', 'especes'), NOT NULL)
- `montant_total` (DECIMAL 10,2, NOT NULL)
- `date_creation` (DATETIME, DEFAULT CURRENT_TIMESTAMP)
- `client_id` (FK → CLIENT.id, NULL)

**Règles :**
- Numéro de commande unique généré (ex: CMD-20260209-001)
- Numéro chevalet entre 001 et 999 (NON unique)
- Créée UNIQUEMENT après paiement validé
- Stockée pour raisons légales (historique)
- Client_id NULL si commande anonyme

**Relation :**
- `CLIENT` ──(0,n)── `COMMANDE` (un client peut avoir 0 ou plusieurs commandes)

---

### 10. COMMANDE_PRODUIT

**Description :** Table de liaison - Produits dans une commande

**Attributs :**
- `id` (PK, INT, AUTO_INCREMENT)
- `id_commande` (FK → COMMANDE.id, NOT NULL, ON DELETE CASCADE)
- `id_produit` (FK → PRODUIT.id, NOT NULL)
- `quantite` (INT, NOT NULL, DEFAULT 1)
- `prix_unitaire` (DECIMAL 10,2, NOT NULL) - Prix au moment de la commande
- `details` (JSON, NULL) - Sauces, taille, composition menu

**Exemple JSON `details` :**
```json
{
  "sauces": ["Curry", "Chinoise"],
  "taille_boisson": "30cl",
  "composition_menu": {
    "sandwich": "BigMac",
    "frites": "Best Of",
    "boisson": "Sprite"
  }
}
```

**Règles :**
- Quantité >= 1
- Prix unitaire figé au moment de la commande (historique)
- ON DELETE CASCADE : si commande supprimée, produits aussi

**Relations :**
- `COMMANDE` ──(n,n)── `PRODUIT` via `COMMANDE_PRODUIT`

---

## 🔗 RELATIONS (RÉCAPITULATIF)

1. **CATEGORIE** ──(1,n)── **PRODUIT**
   - Une catégorie contient plusieurs produits
   - Un produit appartient à une seule catégorie

2. **CLIENT** ──(0,n)── **PANIER**
   - Un client peut avoir 0 ou plusieurs paniers temporaires
   - Un panier appartient à 0 (anonyme) ou 1 client

3. **CLIENT** ──(0,n)── **COMMANDE**
   - Un client peut avoir 0 ou plusieurs commandes
   - Une commande appartient à 0 (anonyme) ou 1 client

4. **PANIER** ──(n,n)── **PRODUIT** via **PANIER_PRODUIT**
   - Un panier contient plusieurs produits
   - Un produit peut être dans plusieurs paniers

5. **COMMANDE** ──(n,n)── **PRODUIT** via **COMMANDE_PRODUIT**
   - Une commande contient plusieurs produits
   - Un produit peut être dans plusieurs commandes

---

## 📐 DIAGRAMME TEXTUEL (Pour DrawIO)

```
CATEGORIE (id, nom)
    |
    | 1,n
    |
PRODUIT (id, nom, description, prix, stock, id_categorie, image)


CLIENT (id, prenom, nom, email, mot_de_passe, points_fidelite)
    |
    | 0,n
    |
PANIER (id, session_id, client_id, date_creation)
    |
    | n,n
    |
PANIER_PRODUIT (id, id_panier, id_produit, quantite, prix_unitaire, details)
    |
    | n,n
    |
PRODUIT


CLIENT (id, prenom, nom, email, mot_de_passe, points_fidelite)
    |
    | 0,n
    |
COMMANDE (id, numero_commande, numero_chevalet, type_commande, mode_paiement, montant_total, client_id)
    |
    | n,n
    |
COMMANDE_PRODUIT (id, id_commande, id_produit, quantite, prix_unitaire, details)
    |
    | n,n
    |
PRODUIT


SAUCE (id, nom) -- Référencée dans JSON details de PANIER_PRODUIT / COMMANDE_PRODUIT

TAILLE_BOISSON (id, nom, supplement_prix) -- Référencée dans JSON details

ADMIN (id, nom, email, mot_de_passe) -- Table indépendante pour gestion
```

---

## ✅ VALIDATION MERISE

### Normalisation

- **1NF (Première Forme Normale)** : ✅ Tous les attributs sont atomiques
- **2NF (Deuxième Forme Normale)** : ✅ Pas de dépendance partielle (toutes les clés étrangères sont liées à la PK entière)
- **3NF (Troisième Forme Normale)** : ✅ Pas de dépendance transitive

### Cardinalités

- **CLIENT ──(0,n)── PANIER** : Un client peut avoir 0 ou n paniers (temporaires)
- **CLIENT ──(0,n)── COMMANDE** : Un client peut avoir 0 ou n commandes
- **CATEGORIE ──(1,n)── PRODUIT** : Une catégorie a au moins 1 produit
- **PANIER ──(n,n)── PRODUIT** : Relation many-to-many via table de liaison
- **COMMANDE ──(n,n)── PRODUIT** : Relation many-to-many via table de liaison

---

## 📋 RÈGLES DE GESTION IMPLÉMENTÉES

1. **RG-001** : Stock = 0 → Produit indisponible (vérification applicative)
2. **RG-002** : Max 2 sauces par menu (vérification applicative + JSON)
3. **RG-003** : Boisson 50cl = +0,50€ (TAILLE_BOISSON.supplement_prix)
4. **RG-004** : Numéro chevalet 001-999 (vérification applicative + CHAR 3)
5. **RG-005** : 1€ = 1 point fidélité (trigger ou logique métier)
6. **RG-006** : Panier temporaire détruit (logique applicative)
7. **RG-007** : Commande créée après paiement validé (logique métier)
8. **RG-008** : Stock décrémenté après commande (trigger ou logique métier)
9. **RG-009** : Client anonyme = client_id NULL (structure BDD)
10. **RG-010** : Historique légal stocké (table COMMANDE + COMMANDE_PRODUIT)

---

## 🚀 PROCHAINES ÉTAPES

1. ✅ MCD validé → Prêt pour DrawIO
2. ⏭️ Générer SQL MariaDB (script de création des tables)
3. ⏭️ Créer tests TDD pour chaque entité
4. ⏭️ Implémenter classes PHP + logique métier
5. ⏭️ Implémenter API REST

---

**Fichier prêt pour DrawIO !** 🎨
