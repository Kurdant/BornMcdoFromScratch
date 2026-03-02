<?php

namespace Tests\Business;

use PHPUnit\Framework\TestCase;
use WCDO\Entities\Commande;
use WCDO\Entities\Produit;
use WCDO\Entities\Categorie;

/**
 * Test Suite pour la LOGIQUE MÉTIER: Commande + Stock
 * 
 * Tests de décrément automatique du stock après commande.
 * 
 * Règles de gestion testées:
 * - RG-008: Stock auto-décrémenté après commande validée
 */
class CommandeStockTest extends TestCase
{
    private Categorie $categorie;
    
    protected function setUp(): void
    {
        $this->categorie = new Categorie(nom: "Burgers", description: "Nos burgers");
    }
    
    /**
     * TEST 1: RG-008 - Stock décrémenté après commande validée
     */
    public function testStockDecrementeApresCommande()
    {
        // ARRANGE
        $stockInitial = 50;
        $produit = new Produit("Big Mac", 7.50, $this->categorie, $stockInitial);
        
        // ACT - Simuler une commande de 2 produits
        $quantiteCommandee = 2;
        $produit->decrementerStock($quantiteCommandee);
        
        // ASSERT
        $stockFinal = $stockInitial - $quantiteCommandee;
        $this->assertEquals($stockFinal, $produit->getStock(), "Le stock doit être décrémenté de 2 unités (RG-008)");
    }
    
    /**
     * TEST 2: RG-008 - Impossible de décrémenter plus que le stock disponible
     */
    public function testImpossibleDecrementerPlusQueStockDisponible()
    {
        // ARRANGE
        $produit = new Produit("Big Mac", 7.50, $this->categorie, stock: 5);
        
        // ACT + ASSERT
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Stock insuffisant");
        
        // Tentative de décrémenter 10 unités alors qu'il n'y en a que 5
        $produit->decrementerStock(10);
    }
    
    /**
     * TEST 3: Stock = 0 après commande du dernier produit
     */
    public function testStockZeroApresCommandeDernierProduit()
    {
        // ARRANGE
        $produit = new Produit("Big Mac", 7.50, $this->categorie, stock: 3);
        
        // ACT - Commander les 3 derniers produits
        $produit->decrementerStock(3);
        
        // ASSERT
        $this->assertEquals(0, $produit->getStock());
        $this->assertFalse($produit->estDisponible(), "Le produit ne doit plus être disponible (stock = 0)");
    }
    
    /**
     * TEST 4: Multiples commandes décrémentent correctement le stock
     */
    public function testMultiplesCommandesDecrementStock()
    {
        // ARRANGE
        $produit = new Produit("Big Mac", 7.50, $this->categorie, stock: 100);
        
        // ACT - Simuler 3 commandes successives
        $produit->decrementerStock(5);  // 100 - 5 = 95
        $produit->decrementerStock(10); // 95 - 10 = 85
        $produit->decrementerStock(20); // 85 - 20 = 65
        
        // ASSERT
        $this->assertEquals(65, $produit->getStock(), "Le stock doit être décrémenté correctement après multiples commandes");
    }
}
