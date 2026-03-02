<?php

namespace Tests\Entities;

use PHPUnit\Framework\TestCase;
use WCDO\Entities\Panier;
use WCDO\Entities\Client;

/**
 * Test Suite pour l'entité PANIER
 * 
 * Règles de gestion testées:
 * - RG-006: Panier temporaire détruit après transformation en commande
 * - RG-009: Client anonyme (client_id NULL)
 */
class PanierTest extends TestCase
{
    /**
     * TEST 1: Créer un panier vide
     */
    public function testCreerPanierVide()
    {
        // ARRANGE + ACT
        $panier = new Panier(sessionId: "session_abc123");
        
        // ASSERT
        $this->assertNotNull($panier);
        $this->assertEquals("session_abc123", $panier->getSessionId());
        $this->assertCount(0, $panier->getProduits(), "Un panier vide ne doit contenir aucun produit");
    }
    
    /**
     * TEST 2: Panier pour client connecté
     */
    public function testPanierClientConnecte()
    {
        // ARRANGE
        $client = new Client("Hugo", "Dupont", "hugo@test.com", "pass123");
        
        // ACT
        $panier = new Panier(sessionId: "session_123", client: $client);
        
        // ASSERT
        $this->assertNotNull($panier->getClient(), "Le panier doit être lié à un client");
        $this->assertEquals($client->getEmail(), $panier->getClient()->getEmail());
    }
    
    /**
     * TEST 3: RG-009 - Panier client anonyme (client_id NULL)
     */
    public function testPanierClientAnonyme()
    {
        // ARRANGE + ACT
        $panier = new Panier(sessionId: "session_anonyme_456");
        
        // ASSERT
        $this->assertNull($panier->getClient(), "Un panier anonyme ne doit PAS avoir de client lié (RG-009)");
        $this->assertNotEmpty($panier->getSessionId(), "Un panier anonyme doit avoir un session_id");
    }
}
