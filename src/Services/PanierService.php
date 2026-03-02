<?php

declare(strict_types=1);

namespace WCDO\Services;

use WCDO\Repositories\PanierRepository;
use WCDO\Repositories\PanierProduitRepository;
use WCDO\Repositories\ProduitRepository;

class PanierService
{
    private PanierRepository $panierRepo;
    private PanierProduitRepository $ppRepo;
    private ProduitRepository $produitRepo;

    public function __construct()
    {
        $this->panierRepo  = new PanierRepository();
        $this->ppRepo      = new PanierProduitRepository();
        $this->produitRepo = new ProduitRepository();
    }

    /** Récupère ou crée le panier pour la session courante */
    public function getOrCreate(string $sessionId, ?int $clientId = null): array
    {
        $panier = $this->panierRepo->findBySessionId($sessionId);

        if (!$panier) {
            $id = $this->panierRepo->create($sessionId, $clientId);
            $panier = $this->panierRepo->findById($id);
        }

        $lignes = $this->ppRepo->findByPanierId((int) $panier['id']);

        return [
            'id'         => (int) $panier['id'],
            'session_id' => $panier['session_id'],
            'client_id'  => $panier['client_id'] ? (int) $panier['client_id'] : null,
            'lignes'     => $lignes,
            'total'      => $this->calcTotal($lignes),
        ];
    }

    /** Ajoute un produit au panier */
    public function ajouter(string $sessionId, int $produitId, int $quantite, ?array $details = null): array
    {
        if ($quantite <= 0) {
            throw new \InvalidArgumentException("La quantité doit être supérieure à 0");
        }

        $produit = $this->produitRepo->findById($produitId);
        if (!$produit) {
            throw new \InvalidArgumentException("Produit introuvable");
        }
        if (!$produit['disponible']) {
            throw new \RuntimeException("Le produit '{$produit['nom']}' n'est plus disponible");
        }

        $panier = $this->panierRepo->findBySessionId($sessionId);
        if (!$panier) {
            $id = $this->panierRepo->create($sessionId);
            $panier = $this->panierRepo->findById($id);
        }

        $panierId = (int) $panier['id'];

        // Vérification stock (quantité déjà dans le panier + nouvelle)
        $lignes = $this->ppRepo->findByPanierId($panierId);
        $existingQte = 0;
        foreach ($lignes as $l) {
            if ($l['id_produit'] === $produitId) {
                $existingQte = $l['quantite'];
                break;
            }
        }

        if (($existingQte + $quantite) > $produit['stock']) {
            throw new \RuntimeException("Stock insuffisant pour '{$produit['nom']}'");
        }

        $this->ppRepo->add($panierId, $produitId, $quantite, (float) $produit['prix'], $details);

        return $this->getOrCreate($sessionId);
    }

    /** Supprime une ligne du panier */
    public function supprimerLigne(string $sessionId, int $ligneId): array
    {
        $panier = $this->panierRepo->findBySessionId($sessionId);
        if (!$panier) {
            throw new \RuntimeException("Panier introuvable");
        }

        $this->ppRepo->removeLigne($ligneId);
        return $this->getOrCreate($sessionId);
    }

    /** Vide le panier */
    public function vider(string $sessionId): void
    {
        $panier = $this->panierRepo->findBySessionId($sessionId);
        if ($panier) {
            $this->ppRepo->clearPanier((int) $panier['id']);
        }
    }

    private function calcTotal(array $lignes): float
    {
        return array_sum(array_map(
            fn(array $l) => $l['prix_unitaire'] * $l['quantite'],
            $lignes
        ));
    }
}
