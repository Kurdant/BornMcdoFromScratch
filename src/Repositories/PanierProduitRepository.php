<?php

declare(strict_types=1);

namespace WCDO\Repositories;

use WCDO\Config\Database;

class PanierProduitRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findByPanierId(int $panierId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pp.id, pp.id_panier, pp.id_produit, pp.quantite, pp.prix_unitaire, pp.details,
                    p.nom AS produit_nom, p.image, p.stock, p.id_categorie
             FROM PANIER_PRODUIT pp
             JOIN PRODUIT p ON p.id = pp.id_produit
             WHERE pp.id_panier = ?
             ORDER BY pp.id'
        );
        $stmt->execute([$panierId]);
        return array_map([$this, 'cast'], $stmt->fetchAll());
    }

    public function add(int $panierId, int $produitId, int $quantite, float $prixUnitaire, ?array $details = null): int
    {
        // Si le produit est déjà dans le panier, on cumule la quantité
        $stmt = $this->pdo->prepare(
            'SELECT id, quantite FROM PANIER_PRODUIT WHERE id_panier = ? AND id_produit = ?'
        );
        $stmt->execute([$panierId, $produitId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $newQte = $existing['quantite'] + $quantite;
            $upd = $this->pdo->prepare('UPDATE PANIER_PRODUIT SET quantite = ? WHERE id = ?');
            $upd->execute([$newQte, $existing['id']]);
            return (int) $existing['id'];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO PANIER_PRODUIT (id_panier, id_produit, quantite, prix_unitaire, details)
             VALUES (:id_panier, :id_produit, :quantite, :prix_unitaire, :details)'
        );
        $stmt->execute([
            ':id_panier'     => $panierId,
            ':id_produit'    => $produitId,
            ':quantite'      => $quantite,
            ':prix_unitaire' => $prixUnitaire,
            ':details'       => $details ? json_encode($details) : null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function removeLigne(int $ligneId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM PANIER_PRODUIT WHERE id = ?');
        return $stmt->execute([$ligneId]);
    }

    public function clearPanier(int $panierId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM PANIER_PRODUIT WHERE id_panier = ?');
        $stmt->execute([$panierId]);
    }

    private function cast(array $row): array
    {
        $row['id']           = (int) $row['id'];
        $row['id_panier']    = (int) $row['id_panier'];
        $row['id_produit']   = (int) $row['id_produit'];
        $row['quantite']     = (int) $row['quantite'];
        $row['prix_unitaire'] = (float) $row['prix_unitaire'];
        $row['details']      = $row['details'] ? json_decode($row['details'], true) : null;
        $row['stock']        = (int) $row['stock'];
        $row['id_categorie'] = (int) $row['id_categorie'];
        return $row;
    }
}
