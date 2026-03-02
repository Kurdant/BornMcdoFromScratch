<?php

declare(strict_types=1);

namespace WCDO\Repositories;

use WCDO\Config\Database;

class CommandeRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function save(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO COMMANDE (numero_commande, numero_chevalet, type_commande, mode_paiement, montant_total, client_id)
             VALUES (:numero_commande, :numero_chevalet, :type_commande, :mode_paiement, :montant_total, :client_id)'
        );
        $stmt->execute([
            ':numero_commande' => $data['numero_commande'],
            ':numero_chevalet' => (int) $data['numero_chevalet'],
            ':type_commande'   => $data['type_commande'],
            ':mode_paiement'   => $data['mode_paiement'],
            ':montant_total'   => $data['montant_total'],
            ':client_id'       => $data['client_id'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findByNumero(string $numero): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM COMMANDE WHERE numero_commande = ?');
        $stmt->execute([$numero]);
        $row = $stmt->fetch();
        return $row ? $this->cast($row) : null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM COMMANDE WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->cast($row) : null;
    }

    public function findByClientId(int $clientId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM COMMANDE WHERE client_id = ? ORDER BY date_creation DESC'
        );
        $stmt->execute([$clientId]);
        return array_map([$this, 'cast'], $stmt->fetchAll());
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM COMMANDE ORDER BY date_creation DESC');
        return array_map([$this, 'cast'], $stmt->fetchAll());
    }

    private function cast(array $row): array
    {
        $row['id']              = (int)   $row['id'];
        $row['numero_chevalet'] = (int)   $row['numero_chevalet'];
        $row['montant_total']   = (float) $row['montant_total'];
        $row['client_id']       = $row['client_id'] !== null ? (int) $row['client_id'] : null;
        return $row;
    }
}
