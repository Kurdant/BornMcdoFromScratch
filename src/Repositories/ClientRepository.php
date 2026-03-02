<?php

declare(strict_types=1);

namespace WCDO\Repositories;

use WCDO\Config\Database;
use WCDO\Entities\Client;

class ClientRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findByEmail(string $email): ?Client
    {
        $stmt = $this->pdo->prepare('SELECT * FROM CLIENT WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function findById(int $id): ?Client
    {
        $stmt = $this->pdo->prepare('SELECT * FROM CLIENT WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function save(Client $client): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO CLIENT (prenom, nom, email, mot_de_passe, points_fidelite)
             VALUES (:prenom, :nom, :email, :mot_de_passe, :points_fidelite)'
        );
        $stmt->execute([
            ':prenom'          => $client->getPrenom(),
            ':nom'             => $client->getNom(),
            ':email'           => $client->getEmail(),
            ':mot_de_passe'    => $client->getMotDePasseHash(),
            ':points_fidelite' => $client->getPointsFidelite(),
        ]);
        $id = (int) $this->pdo->lastInsertId();
        $client->setId($id);
        return $id;
    }

    public function updatePoints(int $clientId, int $points): void
    {
        $stmt = $this->pdo->prepare('UPDATE CLIENT SET points_fidelite = ? WHERE id = ?');
        $stmt->execute([$points, $clientId]);
    }

    public function emailExiste(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM CLIENT WHERE email = ?');
        $stmt->execute([$email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function hydrate(array $row): Client
    {
        return Client::fromDatabase(
            (int) $row['id'],
            $row['prenom'],
            $row['nom'],
            $row['email'],
            $row['mot_de_passe'],
            (int) $row['points_fidelite']
        );
    }
}
