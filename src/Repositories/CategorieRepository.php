<?php

namespace WCDO\Repositories;

use WCDO\Config\Database;

class CategorieRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, nom FROM CATEGORIE ORDER BY id');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nom FROM CATEGORIE WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
