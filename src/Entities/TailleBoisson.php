<?php

namespace WCDO\Entities;

class TailleBoisson
{
    private ?int $id = null;
    private string $nom;
    private int $volume;
    private float $prixSupplement; // BD: supplement_prix

    public function __construct(string $nom, int $volume, float $prixSupplement = 0.00)
    {
        if ($volume <= 0) {
            throw new \InvalidArgumentException("Le volume doit être > 0");
        }
        if ($prixSupplement < 0) {
            throw new \InvalidArgumentException("Le prix supplément ne peut pas être négatif");
        }
        $this->nom = $nom;
        $this->volume = $volume;
        $this->prixSupplement = $prixSupplement;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }
    public function getNom(): string { return $this->nom; }
    public function getVolume(): int { return $this->volume; }
    public function getPrixSupplement(): float { return $this->prixSupplement; }
}
