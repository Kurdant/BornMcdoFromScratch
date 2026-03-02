<?php

declare(strict_types=1);

namespace WCDO\Controllers;

use WCDO\Http\Response;
use WCDO\Services\CommandeService;

class CommandeController
{
    private CommandeService $service;

    public function __construct()
    {
        $this->service = new CommandeService();
    }

    /** POST /api/commande — Passe une commande */
    public function passer(array $params): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionId = session_id();
        $clientId = isset($_SESSION['client_id']) ? (int) $_SESSION['client_id'] : null;
        $body = $this->getBody();

        try {
            $commande = $this->service->passer($sessionId, $body, $clientId);
            Response::json($commande, 201);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 422);
        }
    }

    /** GET /api/commande/{numero} — Détails d'une commande */
    public function getByNumero(array $params): void
    {
        $numero = $params['numero'] ?? '';

        if (empty($numero)) {
            Response::error('Numéro de commande requis', 400);
            return;
        }

        try {
            $commande = $this->service->getByNumero($numero);
            Response::json($commande);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    private function getBody(): array
    {
        $raw = file_get_contents('php://input');
        return $raw ? (json_decode($raw, true) ?? []) : [];
    }
}
