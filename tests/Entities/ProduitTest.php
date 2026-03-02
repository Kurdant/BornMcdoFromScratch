<?php

namespace Tests\Entities;

use PHPUnit\Framework\TestCase;
use WCDO\Entities\Produit;
use WCDO\Entities\Categorie;
use WCDO\Exceptions\StockInsuffisantException;

/**
 * Test Suite pour l'entité PRODUIT
 * 
 * Cette suite teste l'entité PRODUIT qui représente un article vendable
 * (burger, boisson, dessert, accompagnement) dans la borne WCDO.
 * 
 * Règles de gestion testées:
 * - RG-001: Un produit avec stock = 0 est indisponible
 * 
 * Pattern utilisé: AAA (Arrange-Act-Assert)
 * - ARRANGE: Préparer les données et le contexte
 * - ACT: Exécuter l'action à tester
 * - ASSERT: Vérifier que le résultat est conforme aux attentes
 */
class ProduitTest extends TestCase
{
    // Fixtures (données réutilisables dans les tests)
    private $categorieMenus;
    private $categorieSandwiches;
    
    /**
     * Méthode setUp() exécutée AVANT chaque test
     * Utile pour préparer les données communes
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // On crée des catégories pour les tests
        $this->categorieMenus = new Categorie("Menus");
        $this->categorieSandwiches = new Categorie("Sandwiches");
    }
    
    /**
     * TEST 1: Créer un produit valide
     * 
     * CONTEXTE MÉTIER:
     * Un BigMac est un produit valide: il a un nom, un prix positif, 
     * un stock positif et appartient à une catégorie.
     * 
     * OBJECTIVE DU TEST:
     * Vérifier qu'on peut créer un produit avec tous les attributs corrects.
     * 
     * CYCLE TDD:
     * 1. RED: Le test échoue car Produit n'existe pas
     * 2. GREEN: On code Produit::__construct() pour que le test passe
     * 3. REFACTOR: On améliore le code
     */
    public function testCreerProduitValide()
    {
        // ARRANGE: Préparer les données
        $nom = "BigMac";
        $prix = 5.50;
        $stock = 10;
        $categorie = $this->categorieMenus;
        
        // ACT: Créer le produit
        $produit = new Produit(
            nom: $nom,
            prix: $prix,
            stock: $stock,
            categorie: $categorie
        );
        
        // ASSERT: Vérifier que le produit est bien créé
        $this->assertNotNull($produit, "Le produit ne devrait pas être null");
        $this->assertEquals($nom, $produit->getNom(), "Le nom du produit devrait être BigMac");
        $this->assertEquals($prix, $produit->getPrix(), "Le prix devrait être 5.50");
        $this->assertEquals($stock, $produit->getStock(), "Le stock devrait être 10");
    }
    
    /**
     * TEST 2: Un produit DOIT appartenir à une catégorie
     * 
     * CONTEXTE MÉTIER:
     * En base de données, un produit a une clé étrangère (FK) vers CATEGORIE.
     * Cela garantit qu'un produit ne peut pas exister sans catégorie.
     * 
     * OBJECTIVE DU TEST:
     * Vérifier que la relation PRODUIT ← FK → CATEGORIE fonctionne.
     */
    public function testProduitAppartientAUneCategorie()
    {
        // ARRANGE
        $produit = new Produit(
            nom: "Frites",
            prix: 2.50,
            stock: 50,
            categorie: $this->categorieSandwiches
        );
        
        // ACT
        $categorieProduit = $produit->getCategorie();
        
        // ASSERT
        $this->assertNotNull($categorieProduit, "Le produit doit avoir une catégorie");
        $this->assertEquals("Sandwiches", $categorieProduit->getNom(), "La catégorie devrait être Sandwiches");
    }
    
    /**
     * TEST 3: Le prix d'un produit DOIT être positif
     * 
     * CONTEXTE MÉTIER:
     * On ne peut pas vendre un produit à prix négatif ou zéro.
     * C'est une contrainte CHECK en base de données: prix > 0
     * 
     * OBJECTIVE DU TEST:
     * Vérifier que le système refuse un prix invalide (0 ou négatif).
     */
    public function testPrixProduitDoitEtrePositif()
    {
        // ARRANGE
        // On essaie de créer un produit avec prix = 0 (invalide)
        
        // ACT + ASSERT
        // On s'attend à ce qu'une exception soit levée
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le prix doit être positif");
        
        // Cette ligne doit lever une exception
        $produit = new Produit(
            nom: "Produit gratuit",
            prix: 0, // ← INVALIDE !
            stock: 10,
            categorie: $this->categorieMenus
        );
    }
    
    /**
     * TEST 4: Le stock d'un produit DOIT être >= 0
     * 
     * CONTEXTE MÉTIER:
     * Un produit peut avoir 0 en stock (rupture de stock), 
     * mais pas de stock négatif (ça n'a pas de sens métier).
     * 
     * OBJECTIVE DU TEST:
     * Vérifier que le système refuse un stock négatif.
     */
    public function testStockProduitDoitEtrePositifOuNul()
    {
        // ARRANGE + ACT + ASSERT
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le stock ne peut pas être négatif");
        
        // Cette ligne doit lever une exception (stock = -5 invalide)
        $produit = new Produit(
            nom: "Produit",
            prix: 5.50,
            stock: -5, // ← INVALIDE !
            categorie: $this->categorieMenus
        );
    }
    
    /**
     * TEST 5: ⚠️ RG-001 CRITIQUE - Un produit avec stock = 0 est INDISPONIBLE
     * 
     * CONTEXTE MÉTIER ET RÈGLE:
     * C'est LA règle la plus critique du système !
     * Si un produit a un stock à 0, il est en rupture de stock.
     * Quand on appelle estDisponible(), ça doit retourner FALSE.
     * 
     * Pourquoi c'est critique ?
     * - Si on permet de commander un produit indisponible, on casse le métier
     * - C'est un bug qui frustre les clients
     * - C'est potentiellement un problème légal
     * 
     * OBJECTIVE DU TEST:
     * Vérifier que estDisponible() retourne FALSE quand stock = 0.
     * 
     * C'EST LE TEST LE PLUS IMPORTANT. IL VALIDE RG-001.
     */
    public function testProduitAvecStockZeroEstIndisponible()
    {
        // ARRANGE: Créer un produit avec stock = 0
        $produit = new Produit(
            nom: "BigMac",
            prix: 5.50,
            stock: 0  // ← LE POINT CRITIQUE DU TEST
        );
        
        // ACT: Vérifier la disponibilité
        $disponible = $produit->estDisponible();
        
        // ASSERT: Le produit DOIT être indisponible
        $this->assertFalse(
            $disponible, 
            "Un produit avec stock = 0 DOIT être indisponible (RG-001 CRITIQUE)"
        );
    }
    
    /**
     * TEST 6: ✅ Un produit avec stock > 0 EST disponible
     * 
     * CONTEXTE MÉTIER:
     * C'est le cas normal/happy path.
     * Si un produit a du stock disponible, on peut le commander.
     * 
     * OBJECTIVE DU TEST:
     * Vérifier que estDisponible() retourne TRUE quand stock > 0.
     * 
     * IMPORTANCE:
     * Ce test valide aussi RG-001, mais le cas inverse.
     */
    public function testProduitAvecStockSuperieureZeroEstDisponible()
    {
        // ARRANGE: Créer un produit avec stock > 0
        $produit = new Produit(
            nom: "BigMac",
            prix: 5.50,
            stock: 10  // ← Stock positif
        );
        
        // ACT: Vérifier la disponibilité
        $disponible = $produit->estDisponible();
        
        // ASSERT: Le produit DOIT être disponible
        $this->assertTrue(
            $disponible,
            "Un produit avec stock > 0 DOIT être disponible"
        );
    }
}
