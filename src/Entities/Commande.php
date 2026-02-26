<?php

namespace WCDO\Entities;

class Commande
{
    private ?int $id = null;
    private string $numeroCommande;
    private string $numeroChevalet;
    private string $typeCommande;
    private string $modePaiement;
    private float $montantTotal;
    private ?Client $client;

    private const TYPES_VALIDES = ['sur_place', 'a_emporter'];
    private const PAIEMENTS_VALIDES = ['carte', 'especes'];

    public function __construct(
        string $numeroChevalet,
        string $typeCommande,
        string $modePaiement,
        float $montantTotal,
        bool $payementValide,
        ?Client $client = null
    ) {
        if (!$payementValide) {
            throw new \Exception("Impossible de créer une commande sans paiement validé");
        }

        $chevalet = (int) $numeroChevalet;
        if ($chevalet < 1 || $chevalet > 999) {
            throw new \InvalidArgumentException("Le numéro de chevalet doit être entre 001 et 999");
        }

        if (!in_array($typeCommande, self::TYPES_VALIDES, true)) {
            throw new \InvalidArgumentException("Type de commande invalide : {$typeCommande}");
        }

        if (!in_array($modePaiement, self::PAIEMENTS_VALIDES, true)) {
            throw new \InvalidArgumentException("Mode de paiement invalide : {$modePaiement}");
        }

        $this->numeroChevalet = $numeroChevalet;
        $this->typeCommande = $typeCommande;
        $this->modePaiement = $modePaiement;
        $this->montantTotal = $montantTotal;
        $this->client = $client;
        $this->numeroCommande = $this->genererNumeroCommande();
    }

    private function genererNumeroCommande(): string
    {
        return 'CMD-' . strtoupper(uniqid()) . '-' . date('Ymd');
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }
    public function getNumeroCommande(): string { return $this->numeroCommande; }
    public function getNumeroChevalet(): string { return $this->numeroChevalet; }
    public function getTypeCommande(): string { return $this->typeCommande; }
    public function getModePaiement(): string { return $this->modePaiement; }
    public function getMontantTotal(): float { return $this->montantTotal; }
    public function getClient(): ?Client { return $this->client; }
}
