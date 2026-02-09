<?php

namespace Tests\Entities;

use PHPUnit\Framework\TestCase;
use WCDO\Entities\Commande;
use WCDO\Entities\Client;

/**
 * Test Suite pour l'entité COMMANDE
 * 
 * Règles de gestion testées:
 * - RG-004: Numéro chevalet entre 001 et 999
 * - RG-007: Commande créée uniquement après paiement validé
 * - RG-010: Historique stocké (client connecté OU anonyme)
 */
class CommandeTest extends TestCase
{
    /**
     * TEST 1: RG-007 - Créer commande après paiement validé
     */
    public function testCreerCommandeApresPayementValide()
    {
        // ARRANGE
        $numeroChevalet = "042";
        $typeCommande = "sur_place";
        $modePaiement = "carte";
        $montantTotal = 15.50;
        
        // ACT
        $commande = new Commande(
            numeroChevalet: $numeroChevalet,
            typeCommande: $typeCommande,
            modePaiement: $modePaiement,
            montantTotal: $montantTotal,
            payementValide: true // RG-007: PAIEMENT VALIDÉ
        );
        
        // ASSERT
        $this->assertNotNull($commande);
        $this->assertEquals($numeroChevalet, $commande->getNumeroChevalet());
        $this->assertNotEmpty($commande->getNumeroCommande(), "La commande doit avoir un numéro unique");
    }
    
    /**
     * TEST 2: RG-007 - Impossible créer commande sans paiement
     */
    public function testImpossibleCreerCommandeSansPayement()
    {
        // ARRANGE + ACT + ASSERT
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Impossible de créer une commande sans paiement validé");
        
        // Paiement NON validé → Exception
        new Commande(
            numeroChevalet: "042",
            typeCommande: "sur_place",
            modePaiement: "carte",
            montantTotal: 15.50,
            payementValide: false // ❌ PAS VALIDÉ
        );
    }
    
    /**
     * TEST 3: RG-004 - Numéro chevalet doit être entre 001 et 999
     */
    public function testNumeroChevalet001A999()
    {
        // ARRANGE + ACT
        $commande = new Commande(
            numeroChevalet: "001", // Minimum valide
            typeCommande: "sur_place",
            modePaiement: "carte",
            montantTotal: 10.00,
            payementValide: true
        );
        
        // ASSERT
        $this->assertEquals("001", $commande->getNumeroChevalet());
        
        // Test avec 999 (maximum valide)
        $commande2 = new Commande(
            numeroChevalet: "999",
            typeCommande: "a_emporter",
            modePaiement: "especes",
            montantTotal: 20.00,
            payementValide: true
        );
        
        $this->assertEquals("999", $commande2->getNumeroChevalet());
    }
    
    /**
     * TEST 4: RG-004 - Numéro chevalet 000 invalide
     */
    public function testNumeroChevalet000Invalide()
    {
        // ARRANGE + ACT + ASSERT
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le numéro de chevalet doit être entre 001 et 999");
        
        new Commande(
            numeroChevalet: "000", // ❌ INVALIDE
            typeCommande: "sur_place",
            modePaiement: "carte",
            montantTotal: 10.00,
            payementValide: true
        );
    }
    
    /**
     * TEST 5: RG-004 - Numéro chevalet 1000 invalide
     */
    public function testNumeroChevalet1000Invalide()
    {
        // ARRANGE + ACT + ASSERT
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le numéro de chevalet doit être entre 001 et 999");
        
        new Commande(
            numeroChevalet: "1000", // ❌ INVALIDE
            typeCommande: "sur_place",
            modePaiement: "carte",
            montantTotal: 10.00,
            payementValide: true
        );
    }
    
    /**
     * TEST 6: Génération numéro de commande unique
     */
    public function testGenerationNumeroCommandeUnique()
    {
        // ARRANGE + ACT
        $commande1 = new Commande("042", "sur_place", "carte", 15.00, true);
        $commande2 = new Commande("043", "a_emporter", "especes", 20.00, true);
        
        // ASSERT
        $this->assertNotEquals(
            $commande1->getNumeroCommande(),
            $commande2->getNumeroCommande(),
            "Chaque commande doit avoir un numéro unique"
        );
    }
    
    /**
     * TEST 7: RG-010 - Commande stockée pour client connecté
     */
    public function testCommandeStockeeHistoriqueClientConnecte()
    {
        // ARRANGE
        $client = new Client("Hugo", "Dupont", "hugo@test.com", "pass123");
        
        // ACT
        $commande = new Commande(
            numeroChevalet: "042",
            typeCommande: "sur_place",
            modePaiement: "carte",
            montantTotal: 15.00,
            payementValide: true,
            client: $client
        );
        
        // ASSERT
        $this->assertNotNull($commande->getClient(), "La commande doit être liée au client (RG-010)");
        $this->assertEquals($client->getEmail(), $commande->getClient()->getEmail());
    }
    
    /**
     * TEST 8: RG-010 - Commande stockée même pour client anonyme
     */
    public function testCommandeStockeeHistoriqueClientAnonyme()
    {
        // ARRANGE + ACT
        $commande = new Commande(
            numeroChevalet: "042",
            typeCommande: "a_emporter",
            modePaiement: "especes",
            montantTotal: 10.00,
            payementValide: true,
            client: null // ← CLIENT ANONYME
        );
        
        // ASSERT
        $this->assertNull($commande->getClient(), "Une commande anonyme n'a pas de client lié");
        $this->assertNotNull($commande->getNumeroCommande(), "Mais la commande est quand même stockée (RG-010 - historique légal)");
    }
    
    /**
     * TEST 9: Type de commande (ENUM: sur_place, a_emporter)
     */
    public function testTypeCommandeSurPlaceOuEmporter()
    {
        // ARRANGE + ACT
        $commandeSurPlace = new Commande("042", "sur_place", "carte", 15.00, true);
        $commandeEmporter = new Commande("043", "a_emporter", "especes", 20.00, true);
        
        // ASSERT
        $this->assertEquals("sur_place", $commandeSurPlace->getTypeCommande());
        $this->assertEquals("a_emporter", $commandeEmporter->getTypeCommande());
    }
}
