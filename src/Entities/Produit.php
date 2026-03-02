<?php

namespace WCDO\Entities;

use WCDO\Exceptions\StockInsuffisantException;

class Produit
{
    private ?int $id = null;
    private string $nom;
    private float $prix;
    private int $stock;
    private ?Categorie $categorie;
    private ?string $description;
    private ?string $image;

    public function __construct(
        string $nom,
        float $prix,
        ?Categorie $categorie = null,
        int $stock = 0,
        ?string $description = null,
        ?string $image = null
    ) {
        if ($prix <= 0) {
            throw new \InvalidArgumentException("Le prix doit être positif");
        }
        if ($stock < 0) {
            throw new \InvalidArgumentException("Le stock ne peut pas être négatif");
        }
        $this->nom = $nom;
        $this->prix = $prix;
        $this->categorie = $categorie;
        $this->stock = $stock;
        $this->description = $description;
        $this->image = $image;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }
    public function getNom(): string { return $this->nom; }
    public function getPrix(): float { return $this->prix; }
    public function getStock(): int { return $this->stock; }
    public function getCategorie(): ?Categorie { return $this->categorie; }
    public function getDescription(): ?string { return $this->description; }
    public function getImage(): ?string { return $this->image; }

    public function estDisponible(): bool
    {
        return $this->stock > 0;
    }

    public function decrementerStock(int $quantite): void
    {
        if ($quantite > $this->stock) {
            throw new StockInsuffisantException($this->nom, $this->stock, $quantite);
        }
        $this->stock -= $quantite;
    }
}
