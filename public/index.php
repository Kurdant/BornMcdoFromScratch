<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use WCDO\Http\Router;
use WCDO\Http\Response;
use WCDO\Controllers\CatalogueController;

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

// ── Groupe 1 : Catalogue ──────────────────────────────────────
$router->get('/api/categories',        [$catalogue, 'getCategories']);
$router->get('/api/produits',          [$catalogue, 'getProduits']);
$router->get('/api/produits/{id}',     [$catalogue, 'getProduit']);
$router->get('/api/boissons',          [$catalogue, 'getBoissons']);
$router->get('/api/tailles-boissons',  [$catalogue, 'getTaillesBoissons']);
$router->get('/api/sauces',            [$catalogue, 'getSauces']);

// ── Dispatch ──────────────────────────────────────────────────
$router->dispatch();
