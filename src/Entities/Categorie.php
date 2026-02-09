<?php

namespace WCDO\Entities;

/**
 * Entité CATEGORIE
 * 
 * Représente une catégorie de produit sur la borne WCDO.
 * Catégories disponibles: Menus, Sandwiches, Wraps, Frites, Boissons froides, Encas, Desserts
 */
class Categorie
{
    private ?int $id = null;
    private string $nom;
    
    public function __construct(string $nom)
    {
        if (empty(trim($nom))) {
            throw new \InvalidArgumentException("Le nom de la catégorie ne peut pas être vide");
        }
        
        $this->nom = trim($nom);
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getNom(): string
    {
        return $this->nom;
    }
}
