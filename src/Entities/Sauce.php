<?php

namespace WCDO\Entities;

class Sauce
{
    private ?int $id = null;
    private string $nom;

    public function __construct(string $nom)
    {
        if (empty(trim($nom))) {
            throw new \InvalidArgumentException("Le nom de la sauce est obligatoire");
        }
        $this->nom = $nom;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }
    public function getNom(): string { return $this->nom; }
}
