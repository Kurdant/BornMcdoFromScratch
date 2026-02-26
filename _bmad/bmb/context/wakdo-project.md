# Contexte Projet - Borne de commande Wakdo (examen AcadéNice)

## Vue d'ensemble

Projet d'examen pour la certification **37805 "Développeur Web"** (AcadéNice).  
Développement d'un ensemble applicatif pour les **bornes de commande numériques Wakdo** (inspiré McDonald's).

## Fonctionnement métier

- Le client choisit de **dîner sur place ou à emporter**
- Il compose son **panier** (produits uniques ou menus complets)
- Un **menu** = 1 burger + 1 accompagnement (frites/salade, 2 tailles) + 1 boisson (2 tailles) + 1 sauce
- Les grandes tailles coûtent **+0,50€**
- À la fin, le client saisit un **numéro d'identification** (remplace le paiement dans cet examen)
- Il récupère sa commande au comptoir avec ce numéro

## Bloc 1 — Front-end (Borne client)

**Technologies** : HTML, CSS, JavaScript (AJAX pour les données)  
**Résolution cible** : 1920×1080, mais responsive  

Fonctionnalités :
- Interface de sélection de produits et menus (données chargées en AJAX depuis fichiers JSON)
- Gestion du panier (ajout, options, calcul prix)
- Formulaire de numéro de commande final
- Envoi du JSON de commande à une API fictive (le back-end API n'est pas à développer pour ce bloc)

Livrables : application déployée + fichiers sur dépôt GitHub public

## Bloc 2 — Back-end + Back-office (Administration)

**Technologies** : Langage serveur OOP (ex: PHP), base de données MySQL/PostgreSQL  
**Architecture** : MVC, programmation orientée objet avec héritage  

### Rôles utilisateurs

| Rôle | Droits |
|------|--------|
| **Admin** | Gestion complète : produits, menus, utilisateurs, commandes |
| **Préparation** | Voir les commandes à préparer, déclarer "prête" |
| **Accueil** | Saisir une commande (comptoir/téléphone), remettre au client |

### Fonctionnalités back-office

- **Gestion Produits** : CRUD (nom, description, prix, image, disponibilité)
- **Gestion Menus** : CRUD avec composition et options
- **Gestion Utilisateurs** : CRUD avec rôles
- **Saisie commandes** : par équipiers accueil
- **File de préparation** : triée par heure croissante, passage "prête"
- **Livraison** : équipiers marquent commande "livrée"

### API à développer

- `GET /menus` → liste détaillée des menus
- `GET /produits` → liste produits (par catégorie)
- `POST /commandes` → recevoir une commande depuis le front

### Sécurité

- Authentification sécurisée (sessions)
- Protection des données
- Accès restreint par rôle

## Entités principales pressenties

- **Produit** (id, nom, description, prix, image, categorie, disponible)
- **Categorie** (id, nom) → burger, boisson, accompagnement, sauce, dessert, wrap
- **Menu** (id, nom, prix_base)
- **CompositionMenu** (menu_id, type_slot, produit_id) → slots: burger, accompagnement, boisson, sauce
- **OptionTaille** (grande taille = +0,50€ sur accompagnement et boisson)
- **Commande** (id, numero_client, type: sur_place|a_emporter, statut, created_at)
- **LigneCommande** (commande_id, type: produit|menu, ref_id, quantite, prix_unitaire, options_json)
- **Utilisateur** (id, nom, email, password_hash, role: admin|preparation|accueil)

## Livrables attendus (Bloc 2)

- Schémas conceptuels et physiques du modèle de données
- Schémas fonctionnels
- Base de données déployée
- Application fonctionnelle sur hébergement AcadéNice
- Dépôt GitHub public avec sources

## Notes importantes

- Pas de paiement réel (remplacé par numéro d'identification)
- Code from scratch (pas de framework préconstruit)
- Le jury peut demander des modifications en direct
- Doit respecter les critères RNCP 37805
