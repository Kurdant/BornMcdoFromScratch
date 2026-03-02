<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use WCDO\Http\Router;
use WCDO\Http\Response;
use WCDO\Controllers\CatalogueController;
use WCDO\Controllers\PanierController;
use WCDO\Controllers\CommandeController;
use WCDO\Controllers\AuthController;
use WCDO\Controllers\AdminController;

// ── Chargement des variables d'environnement Docker ──────────
foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $var) {
    if (getenv($var) !== false) {
        $_ENV[$var] = getenv($var);
    }
}

// ── Gestion globale des erreurs ───────────────────────────────
set_exception_handler(function (\Throwable $e) {
    Response::error($e->getMessage(), 500);
});

// ── Router ────────────────────────────────────────────────────
$router = new Router();

$catalogue = new CatalogueController();
$panier    = new PanierController();
$commande  = new CommandeController();
$auth      = new AuthController();
$admin     = new AdminController();

// ── Groupe 1 : Catalogue ──────────────────────────────────────
$router->get('/api/categories',        [$catalogue, 'getCategories']);
$router->get('/api/produits',          [$catalogue, 'getProduits']);
$router->get('/api/produits/{id}',     [$catalogue, 'getProduit']);
$router->get('/api/boissons',          [$catalogue, 'getBoissons']);
$router->get('/api/tailles-boissons',  [$catalogue, 'getTaillesBoissons']);
$router->get('/api/sauces',            [$catalogue, 'getSauces']);

// ── Groupe 2 : Panier ─────────────────────────────────────────
$router->get('/api/panier',              [$panier, 'getPanier']);
$router->post('/api/panier/ajouter',     [$panier, 'ajouter']);
$router->delete('/api/panier/ligne/{id}',[$panier, 'supprimerLigne']);
$router->delete('/api/panier',           [$panier, 'vider']);

// ── Groupe 3 : Commande ───────────────────────────────────────
$router->post('/api/commande',              [$commande, 'passer']);
$router->get('/api/commande/{numero}',      [$commande, 'getByNumero']);

// ── Groupe 4 : Auth Client ────────────────────────────────────
$router->post('/api/auth/register', [$auth, 'register']);
$router->post('/api/auth/login',    [$auth, 'login']);
$router->post('/api/auth/logout',   [$auth, 'logout']);
$router->get('/api/auth/me',        [$auth, 'me']);

// ── Groupe 5 : Admin ──────────────────────────────────────────
$router->post('/api/admin/login',         [$admin, 'login']);
$router->post('/api/admin/logout',        [$admin, 'logout']);
$router->get('/api/admin/produits',       [$admin, 'getProduits']);
$router->post('/api/admin/produits',      [$admin, 'createProduit']);
$router->put('/api/admin/produits/{id}',  [$admin, 'updateProduit']);
$router->delete('/api/admin/produits/{id}',[$admin, 'deleteProduit']);
$router->get('/api/admin/commandes',      [$admin, 'getCommandes']);

// ── Dispatch ──────────────────────────────────────────────────
$router->dispatch();
