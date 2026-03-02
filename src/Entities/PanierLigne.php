<?php

namespace WCDO\Entities;

class PanierLigne
{
    public function __construct(
        public readonly Produit $produit,
        public readonly float $prixUnitaire,
        public int $quantite
    ) {}

    public function getSousTotal(): float
    {
        return $this->prixUnitaire * $this->quantite;
    }
}
