<?php

namespace WCDO\Repositories;

use WCDO\Config\Database;

class SauceRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, nom FROM SAUCE ORDER BY nom');
        return array_map(fn($r) => ['id' => (int) $r['id'], 'nom' => $r['nom']], $stmt->fetchAll());
    }
}
