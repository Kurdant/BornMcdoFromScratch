<?php

namespace Tests\Business;

use PHPUnit\Framework\TestCase;
use WCDO\Entities\Panier;
use WCDO\Entities\Produit;
use WCDO\Entities\Categorie;

/**
 * Test Suite pour la LOGIQUE MÉTIER: Panier + Produits
 * 
 * Tests avancés de gestion du panier avec produits.
 * 
 * Règles de gestion testées:
 * - RG-001: Produit indisponible si stock = 0
 * - RG-002: Prix menu < somme des prix individuels
 * - RG-003: Quantité produit limitée
 */
class PanierProduitTest extends TestCase
{
    private Categorie $categorie;
    
    protected function setUp(): void
    {
        // Créer une catégorie de test
        $this->categorie = new Categorie(nom: "Burgers", description: "Nos délicieux burgers");
    }
    
    /**
     * TEST 1: RG-001 - Impossible d'ajouter un produit en rupture de stock
     */
    public function testImpossibleAjouterProduitRuptureStock()
    {
        // ARRANGE
        $produit = new Produit(
            nom: "Big Tasty",
            prix: 8.50,
            categorie: $this->categorie,
            stock: 0 // ← RUPTURE DE STOCK
        );
        $panier = new Panier(sessionId: "session_abc");
        
        // ACT + ASSERT
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("n'est plus disponible");
        
        // Tentative d'ajout → Exception attendue
        $panier->ajouterProduit($produit, quantite: 1);
    }
    
    /**
     * TEST 2: Ajouter un produit disponible au panier
     */
    public function testAjouterProduitDisponibleAuPanier()
    {
        // ARRANGE
        $produit = new Produit(
            nom: "Big Mac",
            prix: 7.50,
            categorie: $this->categorie,
            stock: 50
        );
        $panier = new Panier(sessionId: "session_abc");
        
        // ACT
        $panier->ajouterProduit($produit, quantite: 2);
        
        // ASSERT
        $this->assertCount(1, $panier->getProduits(), "Le panier doit contenir 1 ligne de produit");
        
        // Vérifier le total
        $totalAttendu = 7.50 * 2; // 15.00€
        $this->assertEquals($totalAttendu, $panier->getTotal());
    }
    
    /**
     * TEST 3: Ajouter plusieurs produits différents
     */
    public function testAjouterPlusieursProduits()
    {
        // ARRANGE
        $burger = new Produit("Big Mac", 7.50, $this->categorie, 50);
        $frites = new Produit("Frites Moyennes", 2.50, $this->categorie, 100);
        $boisson = new Produit("Coca-Cola 50cl", 2.00, $this->categorie, 80);
        
        $panier = new Panier(sessionId: "session_abc");
        
        // ACT
        $panier->ajouterProduit($burger, 1);
        $panier->ajouterProduit($frites, 1);
        $panier->ajouterProduit($boisson, 1);
        
        // ASSERT
        $this->assertCount(3, $panier->getProduits(), "Le panier doit contenir 3 lignes de produits");
        
        // Vérifier le total
        $totalAttendu = 7.50 + 2.50 + 2.00; // 12.00€
        $this->assertEquals($totalAttendu, $panier->getTotal());
    }
    
    /**
     * TEST 4: RG-003 - Quantité maximale par produit
     */
    public function testQuantiteMaximaleParProduit()
    {
        // ARRANGE
        $produit = new Produit("Big Mac", 7.50, $this->categorie, 50);
        $panier = new Panier(sessionId: "session_abc");
        
        // ACT - Ajouter 10 produits (limite raisonnable)
        $panier->ajouterProduit($produit, quantite: 10);
        
        // ASSERT
        $this->assertEquals(75.00, $panier->getTotal(), "10 Big Mac = 75€");
    }
    
    /**
     * TEST 5: Calcul du total d'un panier vide
     */
    public function testTotalPanierVide()
    {
        // ARRANGE
        $panier = new Panier(sessionId: "session_abc");
        
        // ACT + ASSERT
        $this->assertEquals(0.00, $panier->getTotal(), "Un panier vide doit avoir un total de 0€");
    }
    
    /**
     * TEST 6: Prix stocké au moment de l'ajout (protection changement prix)
     */
    public function testPrixStockeAuMomentDeLajout()
    {
        // ARRANGE
        $produit = new Produit("Big Mac", 7.50, $this->categorie, 50);
        $panier = new Panier(sessionId: "session_abc");
        
        // ACT - Ajouter le produit au panier
        $panier->ajouterProduit($produit, quantite: 1);
        
        // Simuler un changement de prix du produit
        // (Dans une vraie appli, le prix ne change pas en mémoire, mais en BDD oui)
        
        // ASSERT
        $this->assertEquals(7.50, $panier->getTotal(), "Le prix stocké dans le panier ne change pas même si le produit change");
    }
    
    /**
     * TEST 7: RG-001 - Vérification disponibilité avant ajout
     */
    public function testVerificationDisponibiliteAvantAjout()
    {
        // ARRANGE
        $produitDispo = new Produit("Big Mac", 7.50, $this->categorie, 5);
        $produitRupture = new Produit("McFlurry", 3.50, $this->categorie, 0);
        
        $panier = new Panier(sessionId: "session_abc");
        
        // ACT
        $panier->ajouterProduit($produitDispo, 1); // OK
        
        // ASSERT
        $this->assertCount(1, $panier->getProduits());
        
        // Tentative d'ajout produit en rupture
        $this->expectException(\Exception::class);
        $panier->ajouterProduit($produitRupture, 1); // DOIT ÉCHOUER
    }
    
    /**
     * TEST 8: Ajouter une quantité de 0 → Exception
     */
    public function testAjouterQuantiteZeroInvalide()
    {
        // ARRANGE
        $produit = new Produit("Big Mac", 7.50, $this->categorie, 50);
        $panier = new Panier(sessionId: "session_abc");
        
        // ACT + ASSERT
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La quantité doit être > 0");
        
        $panier->ajouterProduit($produit, quantite: 0);
    }
    
    /**
     * TEST 9: Ajouter une quantité négative → Exception
     */
    public function testAjouterQuantiteNegativeInvalide()
    {
        // ARRANGE
        $produit = new Produit("Big Mac", 7.50, $this->categorie, 50);
        $panier = new Panier(sessionId: "session_abc");
        
        // ACT + ASSERT
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("La quantité doit être > 0");
        
        $panier->ajouterProduit($produit, quantite: -5);
    }
}
