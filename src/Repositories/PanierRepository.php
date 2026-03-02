<?php

declare(strict_types=1);

namespace WCDO\Repositories;

use WCDO\Config\Database;

class PanierRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findBySessionId(string $sessionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM PANIER WHERE session_id = ? ORDER BY updated_at DESC LIMIT 1');
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM PANIER WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $sessionId, ?int $clientId = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO PANIER (session_id, client_id) VALUES (:session_id, :client_id)'
        );
        $stmt->execute([':session_id' => $sessionId, ':client_id' => $clientId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function attachClient(int $panierId, int $clientId): void
    {
        $stmt = $this->pdo->prepare('UPDATE PANIER SET client_id = ? WHERE id = ?');
        $stmt->execute([$clientId, $panierId]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM PANIER WHERE id = ?');
        $stmt->execute([$id]);
    }
}
