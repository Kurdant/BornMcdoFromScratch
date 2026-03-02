<?php

declare(strict_types=1);

namespace WCDO\Controllers;

use WCDO\Http\Response;
use WCDO\Services\AuthService;

class AuthController
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService();
    }

    /** POST /api/auth/register */
    public function register(array $params): void
    {
        $body = $this->getBody();
        try {
            $client = $this->service->register($body);
            $this->startSession();
            $_SESSION['client_id'] = $client['id'];
            Response::json($client, 201);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 409);
        }
    }

    /** POST /api/auth/login — Connexion client */
    public function login(array $params): void
    {
        $body = $this->getBody();
        $email = $body['email'] ?? '';
        $mdp   = $body['mot_de_passe'] ?? '';

        if (!$email || !$mdp) {
            Response::error('email et mot_de_passe sont requis', 400);
            return;
        }

        try {
            $client = $this->service->loginClient($email, $mdp);
            $this->startSession();
            $_SESSION['client_id'] = $client['id'];
            Response::json($client);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 401);
        }
    }

    /** POST /api/auth/logout */
    public function logout(array $params): void
    {
        $this->startSession();
        session_destroy();
        Response::json(['message' => 'Déconnecté avec succès']);
    }

    /** GET /api/auth/me — Profil du client connecté */
    public function me(array $params): void
    {
        $this->startSession();
        if (empty($_SESSION['client_id'])) {
            Response::error('Non authentifié', 401);
            return;
        }
        $client = $this->service->getClientById((int) $_SESSION['client_id']);
        if (!$client) {
            Response::error('Client introuvable', 404);
            return;
        }
        Response::json($client);
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
