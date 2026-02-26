<?php

namespace WCDO\Entities;

class Categorie
{
    private ?int $id = null;
    private string $nom;
    private string $description; // transient : pas en BD, usage mémoire uniquement

    public function __construct(string $nom, string $description = '')
    {
        if (empty(trim($nom))) {
            throw new \InvalidArgumentException("Le nom de la catégorie est obligatoire");
        }
        $this->nom = $nom;
        $this->description = $description;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }
    public function getNom(): string { return $this->nom; }
    public function getDescription(): string { return $this->description; }
    public function setNom(string $nom): void { $this->nom = $nom; }
}
