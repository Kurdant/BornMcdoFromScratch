<?php

namespace WCDO\Repositories;

use WCDO\Config\Database;

class ProduitRepository
{
    private \PDO $pdo;

    // ID de la catégorie "Boissons Froides" en BD
    private const CATEGORIE_BOISSONS_ID = 5;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findAll(?int $categorieId = null): array
    {
        if ($categorieId !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT p.id, p.nom, p.description, p.prix, p.stock, p.image,
                        p.id_categorie, c.nom AS categorie_nom,
                        (p.stock > 0) AS disponible
                 FROM PRODUIT p
                 JOIN CATEGORIE c ON c.id = p.id_categorie
                 WHERE p.id_categorie = ?
                 ORDER BY p.nom'
            );
            $stmt->execute([$categorieId]);
        } else {
            $stmt = $this->pdo->query(
                'SELECT p.id, p.nom, p.description, p.prix, p.stock, p.image,
                        p.id_categorie, c.nom AS categorie_nom,
                        (p.stock > 0) AS disponible
                 FROM PRODUIT p
                 JOIN CATEGORIE c ON c.id = p.id_categorie
                 ORDER BY p.id_categorie, p.nom'
            );
        }

        return array_map([$this, 'cast'], $stmt->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.nom, p.description, p.prix, p.stock, p.image,
                    p.id_categorie, c.nom AS categorie_nom,
                    (p.stock > 0) AS disponible
             FROM PRODUIT p
             JOIN CATEGORIE c ON c.id = p.id_categorie
             WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->cast($row) : null;
    }

    public function findBoissons(): array
    {
        return $this->findAll(self::CATEGORIE_BOISSONS_ID);
    }

    public function save(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO PRODUIT (nom, description, prix, stock, id_categorie, image)
             VALUES (:nom, :description, :prix, :stock, :id_categorie, :image)'
        );
        $stmt->execute([
            ':nom'          => $data['nom'],
            ':description'  => $data['description'] ?? null,
            ':prix'         => $data['prix'],
            ':stock'        => $data['stock'] ?? 0,
            ':id_categorie' => $data['id_categorie'],
            ':image'        => $data['image'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE PRODUIT SET nom = :nom, description = :description,
             prix = :prix, stock = :stock, id_categorie = :id_categorie, image = :image
             WHERE id = :id'
        );
        return $stmt->execute([
            ':nom'          => $data['nom'],
            ':description'  => $data['description'] ?? null,
            ':prix'         => $data['prix'],
            ':stock'        => $data['stock'],
            ':id_categorie' => $data['id_categorie'],
            ':image'        => $data['image'] ?? null,
            ':id'           => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM PRODUIT WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /** Typage correct des colonnes retournées par PDO */
    private function cast(array $row): array
    {
        $row['id']           = (int)  $row['id'];
        $row['prix']         = (float) $row['prix'];
        $row['stock']        = (int)  $row['stock'];
        $row['id_categorie'] = (int)  $row['id_categorie'];
        $row['disponible']   = (bool) $row['disponible'];
        return $row;
    }
}
