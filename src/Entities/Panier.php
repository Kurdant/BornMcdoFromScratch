<?php

namespace WCDO\Entities;

class Panier
{
    private ?int $id = null;
    private string $sessionId;
    private ?Client $client;
    /** @var PanierLigne[] */
    private array $produits = [];

    public function __construct(string $sessionId, ?Client $client = null)
    {
        $this->sessionId = $sessionId;
        $this->client = $client;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }
    public function getSessionId(): string { return $this->sessionId; }
    public function getClient(): ?Client { return $this->client; }

    /** @return PanierLigne[] */
    public function getProduits(): array { return $this->produits; }

    public function ajouterProduit(Produit $produit, int $quantite): void
    {
        if ($quantite <= 0) {
            throw new \InvalidArgumentException("La quantité doit être > 0");
        }
        if (!$produit->estDisponible()) {
            throw new \Exception("Le produit '{$produit->getNom()}' n'est plus disponible");
        }
        $this->produits[] = new PanierLigne($produit, $produit->getPrix(), $quantite);
    }

    public function getTotal(): float
    {
        return array_sum(array_map(fn(PanierLigne $l) => $l->getSousTotal(), $this->produits));
    }

    public function estVide(): bool
    {
        return empty($this->produits);
    }

    public function vider(): void
    {
        $this->produits = [];
    }
}
