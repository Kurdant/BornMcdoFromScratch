<?php

namespace WCDO\Controllers;

use WCDO\Http\Response;
use WCDO\Repositories\CategorieRepository;
use WCDO\Repositories\ProduitRepository;
use WCDO\Repositories\SauceRepository;
use WCDO\Repositories\TailleBoissonRepository;

class CatalogueController
{
    private CategorieRepository    $categories;
    private ProduitRepository      $produits;
    private SauceRepository        $sauces;
    private TailleBoissonRepository $tailles;

    public function __construct()
    {
        $this->categories = new CategorieRepository();
        $this->produits   = new ProduitRepository();
        $this->sauces     = new SauceRepository();
        $this->tailles    = new TailleBoissonRepository();
    }

    /**
     * GET /api/categories
     * Retourne toutes les catégories
     */
    public function getCategories(array $params): void
    {
        $data = $this->categories->findAll();
        Response::success($data);
    }

    /**
     * GET /api/produits
     * GET /api/produits?categorie_id=X
     * Retourne tous les produits, filtrables par catégorie
     */
    public function getProduits(array $params): void
    {
        $categorieId = isset($_GET['categorie_id']) ? (int) $_GET['categorie_id'] : null;
        $data = $this->produits->findAll($categorieId);
        Response::success($data);
    }

    /**
     * GET /api/produits/{id}
     * Retourne un produit par son ID
     */
    public function getProduit(array $params): void
    {
        $id = (int) $params['id'];
        $produit = $this->produits->findById($id);

        if ($produit === null) {
            Response::notFound("Produit #{$id} introuvable");
            return;
        }

        Response::success($produit);
    }

    /**
     * GET /api/boissons
     * Retourne uniquement les produits de catégorie "Boissons Froides"
     */
    public function getBoissons(array $params): void
    {
        $data = $this->produits->findBoissons();
        Response::success($data);
    }

    /**
     * GET /api/tailles-boissons
     * Retourne les tailles de boissons avec leur supplément prix
     */
    public function getTaillesBoissons(array $params): void
    {
        $data = $this->tailles->findAll();
        Response::success($data);
    }

    /**
     * GET /api/sauces
     * Retourne toutes les sauces disponibles
     */
    public function getSauces(array $params): void
    {
        $data = $this->sauces->findAll();
        Response::success($data);
    }
}
