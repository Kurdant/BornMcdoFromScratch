<?php

declare(strict_types=1);

namespace WCDO\Controllers;

use WCDO\Http\Response;
use WCDO\Services\AuthService;
use WCDO\Repositories\ProduitRepository;
use WCDO\Repositories\CommandeRepository;
use WCDO\Repositories\CommandeProduitRepository;

class AdminController
{
    private AuthService $authService;
    private ProduitRepository $produitRepo;
    private CommandeRepository $commandeRepo;
    private CommandeProduitRepository $cpRepo;

    public function __construct()
    {
        $this->authService  = new AuthService();
        $this->produitRepo  = new ProduitRepository();
        $this->commandeRepo = new CommandeRepository();
        $this->cpRepo       = new CommandeProduitRepository();
    }

    /** POST /api/admin/login */
    public function login(array $params): void
    {
        $body  = $this->getBody();
        $email = $body['email'] ?? '';
        $mdp   = $body['mot_de_passe'] ?? '';

        if (!$email || !$mdp) {
            Response::error('email et mot_de_passe sont requis', 400);
            return;
        }

        try {
            $admin = $this->authService->loginAdmin($email, $mdp);
            $this->startSession();
            $_SESSION['admin_id'] = $admin['id'];
            Response::json($admin);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 401);
        }
    }

    /** POST /api/admin/logout */
    public function logout(array $params): void
    {
        $this->startSession();
        session_destroy();
        Response::json(['message' => 'Déconnecté']);
    }

    // ── CRUD Produits ──────────────────────────────────────────

    /** GET /api/admin/produits */
    public function getProduits(array $params): void
    {
        $this->requireAdmin();
        $produits = $this->produitRepo->findAll();
        Response::json($produits);
    }

    /** POST /api/admin/produits */
    public function createProduit(array $params): void
    {
        $this->requireAdmin();
        $body = $this->getBody();

        foreach (['nom', 'prix', 'id_categorie'] as $field) {
            if (!isset($body[$field]) || $body[$field] === '') {
                Response::error("Champ requis manquant : {$field}", 400);
                return;
            }
        }

        try {
            $id = $this->produitRepo->save($body);
            $produit = $this->produitRepo->findById($id);
            Response::json($produit, 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 422);
        }
    }

    /** PUT /api/admin/produits/{id} */
    public function updateProduit(array $params): void
    {
        $this->requireAdmin();
        $id   = (int) ($params['id'] ?? 0);
        $body = $this->getBody();

        if ($id <= 0) {
            Response::error('ID produit invalide', 400);
            return;
        }

        $existing = $this->produitRepo->findById($id);
        if (!$existing) {
            Response::error('Produit introuvable', 404);
            return;
        }

        try {
            $this->produitRepo->update($id, array_merge($existing, $body));
            Response::json($this->produitRepo->findById($id));
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 422);
        }
    }

    /** DELETE /api/admin/produits/{id} */
    public function deleteProduit(array $params): void
    {
        $this->requireAdmin();
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            Response::error('ID produit invalide', 400);
            return;
        }

        if (!$this->produitRepo->findById($id)) {
            Response::error('Produit introuvable', 404);
            return;
        }

        $this->produitRepo->delete($id);
        Response::json(['message' => 'Produit supprimé'], 200);
    }

    /** GET /api/admin/commandes — Historique de toutes les commandes */
    public function getCommandes(array $params): void
    {
        $this->requireAdmin();
        $commandes = $this->commandeRepo->findAll();

        // Enrichir avec les lignes
        $result = array_map(function (array $c): array {
            $c['lignes'] = $this->cpRepo->findByCommandeId($c['id']);
            return $c;
        }, $commandes);

        Response::json($result);
    }

    // ── Helpers ────────────────────────────────────────────────

    private function requireAdmin(): void
    {
        $this->startSession();
        if (empty($_SESSION['admin_id'])) {
            Response::error('Accès refusé - authentification admin requise', 403);
            exit;
        }
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function getBody(): array
    {
        $raw = file_get_contents('php://input');
        return $raw ? (json_decode($raw, true) ?? []) : [];
    }
}
