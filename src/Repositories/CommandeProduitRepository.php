<?php

declare(strict_types=1);

namespace WCDO\Repositories;

use WCDO\Config\Database;

class CommandeProduitRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function saveAll(int $commandeId, array $lignes): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO COMMANDE_PRODUIT (id_commande, id_produit, quantite, prix_unitaire, details)
             VALUES (:id_commande, :id_produit, :quantite, :prix_unitaire, :details)'
        );
        foreach ($lignes as $ligne) {
            $stmt->execute([
                ':id_commande'   => $commandeId,
                ':id_produit'    => $ligne['id_produit'],
                ':quantite'      => $ligne['quantite'],
                ':prix_unitaire' => $ligne['prix_unitaire'],
                ':details'       => isset($ligne['details']) ? json_encode($ligne['details']) : null,
            ]);
        }
    }

    public function findByCommandeId(int $commandeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cp.id, cp.id_commande, cp.id_produit, cp.quantite, cp.prix_unitaire, cp.details,
                    p.nom AS produit_nom, p.image
             FROM COMMANDE_PRODUIT cp
             JOIN PRODUIT p ON p.id = cp.id_produit
             WHERE cp.id_commande = ?
             ORDER BY cp.id'
        );
        $stmt->execute([$commandeId]);
        return array_map(function (array $row): array {
            $row['id']           = (int)   $row['id'];
            $row['id_commande']  = (int)   $row['id_commande'];
            $row['id_produit']   = (int)   $row['id_produit'];
            $row['quantite']     = (int)   $row['quantite'];
            $row['prix_unitaire'] = (float) $row['prix_unitaire'];
            $row['details']      = $row['details'] ? json_decode($row['details'], true) : null;
            return $row;
        }, $stmt->fetchAll());
    }
}
