<?php

namespace WCDO\Entities;

/**
 * Entité PRODUIT
 * 
 * Représente un article vendable sur la borne WCDO.
 * Un produit peut être: un burger, une boisson, un dessert, un accompagnement, etc.
 * 
 * Attributs:
 * - id: Identifiant unique en base de données
 * - nom: Nom du produit (ex: "BigMac")
 * - description: Description optionnelle
 * - prix: Prix unitaire en euros (doit être > 0)
 * - stock: Quantité disponible en stock (doit être >= 0)
 * - categorie: Catégorie du produit (Menus, Sandwiches, Wraps, etc.)
 * - image: Chemin vers l'image du produit (optionnel)
 * 
 * Règles de gestion:
 * - RG-001: Si stock = 0, le produit est indisponible
 * - Prix doit toujours être > 0
 * - Stock doit toujours être >= 0
 */
class Produit
{
    private ?int $id = null;
    private string $nom;
    private ?string $description = null;
    private float $prix;
    private int $stock;
    private Categorie $categorie;
    private ?string $image = null;
    private ?\DateTime $dateCreation = null;
    
    /**
     * Constructeur de Produit
     * 
     * @param string $nom Nom du produit (obligatoire)
     * @param float $prix Prix en euros (obligatoire, doit être > 0)
     * @param int $stock Quantité en stock (obligatoire, doit être >= 0)
     * @param Categorie $categorie Catégorie du produit (obligatoire)
     * @param ?string $description Description optionnelle
     * @param ?string $image Chemin image optionnel
     * 
     * @throws \InvalidArgumentException Si prix <= 0 ou stock < 0
     */
    public function __construct(
        string $nom,
        float $prix,
        int $stock,
        Categorie $categorie,
        ?string $description = null,
        ?string $image = null
    ) {
        // Validation du prix (RG métier: prix > 0)
        if ($prix <= 0) {
            throw new \InvalidArgumentException("Le prix doit être positif");
        }
        
        // Validation du stock (RG métier: stock >= 0)
        if ($stock < 0) {
            throw new \InvalidArgumentException("Le stock ne peut pas être négatif");
        }
        
        // Affectation des attributs
        $this->nom = $nom;
        $this->prix = $prix;
        $this->stock = $stock;
        $this->categorie = $categorie;
        $this->description = $description;
        $this->image = $image;
        $this->dateCreation = new \DateTime();
    }
    
    // === GETTERS ===
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getNom(): string
    {
        return $this->nom;
    }
    
    public function getDescription(): ?string
    {
        return $this->description;
    }
    
    public function getPrix(): float
    {
        return $this->prix;
    }
    
    public function getStock(): int
    {
        return $this->stock;
    }
    
    public function getCategorie(): Categorie
    {
        return $this->categorie;
    }
    
    public function getImage(): ?string
    {
        return $this->image;
    }
    
    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }
    
    // === MÉTHODES MÉTIER ===
    
    /**
     * Vérifie si le produit est disponible
     * 
     * Implémente RG-001: Un produit avec stock = 0 est indisponible
     * 
     * @return bool TRUE si stock > 0, FALSE sinon
     */
    public function estDisponible(): bool
    {
        return $this->stock > 0;
    }
    
    /**
     * Diminue le stock après une commande
     * 
     * Implémente RG-008: Le stock est décrémenté après chaque commande validée
     * 
     * @param int $quantite Quantité à décrémenter
     * 
     * @throws \InvalidArgumentException Si quantité > stock disponible
     */
    public function decrementerStock(int $quantite): void
    {
        if ($quantite > $this->stock) {
            throw new \InvalidArgumentException(
                "Impossible de décrémenter de $quantite: stock insuffisant (stock actuel: {$this->stock})"
            );
        }
        
        $this->stock -= $quantite;
    }
}
