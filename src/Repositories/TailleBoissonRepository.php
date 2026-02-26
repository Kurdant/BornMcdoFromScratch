<?php

namespace WCDO\Repositories;

use WCDO\Config\Database;

class TailleBoissonRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, nom, volume, supplement_prix FROM TAILLE_BOISSON ORDER BY volume');
        return array_map(fn($r) => [
            'id'             => (int)   $r['id'],
            'nom'            => $r['nom'],
            'volume'         => (int)   $r['volume'],
            'supplement_prix' => (float) $r['supplement_prix'],
        ], $stmt->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nom, volume, supplement_prix FROM TAILLE_BOISSON WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return [
            'id'             => (int)   $row['id'],
            'nom'            => $row['nom'],
            'volume'         => (int)   $row['volume'],
            'supplement_prix' => (float) $row['supplement_prix'],
        ];
    }
}
