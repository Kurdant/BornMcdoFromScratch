<?php

declare(strict_types=1);

namespace WCDO\Controllers;

use WCDO\Http\Response;
use WCDO\Services\PanierService;

class PanierController
{
    private PanierService $service;

    public function __construct()
    {
        $this->service = new PanierService();
    }

    /** GET /api/panier — Récupère le panier courant */
    public function getPanier(array $params): void
    {
        $sessionId = $this->getSessionId();
        $panier = $this->service->getOrCreate($sessionId);
        Response::json($panier);
    }

    /** POST /api/panier/ajouter — Ajoute un produit */
    public function ajouter(array $params): void
    {
        $sessionId = $this->getSessionId();
        $body = $this->getBody();

        if (empty($body['produit_id']) || empty($body['quantite'])) {
            Response::error('produit_id et quantite sont requis', 400);
            return;
        }

        try {
            $panier = $this->service->ajouter(
                $sessionId,
                (int) $body['produit_id'],
                (int) $body['quantite'],
                $body['details'] ?? null
            );
            Response::json($panier, 201);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 409);
        }
    }

    /** DELETE /api/panier/ligne/{id} — Supprime une ligne */
    public function supprimerLigne(array $params): void
    {
        $sessionId = $this->getSessionId();
        $ligneId = (int) ($params['id'] ?? 0);

        if ($ligneId <= 0) {
            Response::error('ID de ligne invalide', 400);
            return;
        }

        try {
            $panier = $this->service->supprimerLigne($sessionId, $ligneId);
            Response::json($panier);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    /** DELETE /api/panier — Vide le panier */
    public function vider(array $params): void
    {
        $sessionId = $this->getSessionId();
        $this->service->vider($sessionId);
        Response::json(['message' => 'Panier vidé']);
    }

    private function getSessionId(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return session_id();
    }

    private function getBody(): array
    {
        $raw = file_get_contents('php://input');
        return $raw ? (json_decode($raw, true) ?? []) : [];
    }
}
