<?php

namespace Tests\Business;

use PHPUnit\Framework\TestCase;
use WCDO\Entities\Client;
use WCDO\Entities\Commande;

/**
 * Test Suite pour la LOGIQUE MÉTIER: Points de Fidélité
 * 
 * Tests du système de points de fidélité.
 * 
 * Règles de gestion testées:
 * - RG-005: 1€ dépensé = 1 point de fidélité
 */
class PointsFideliteTest extends TestCase
{
    /**
     * TEST 1: RG-005 - 1€ dépensé = 1 point de fidélité
     */
    public function testUnEuroEgalUnPoint()
    {
        // ARRANGE
        $client = new Client("Hugo", "Dupont", "hugo@test.com", "pass123");
        $montantCommande = 15.50;
        
        // ACT - Ajouter les points après commande
        $pointsAjoutes = floor($montantCommande); // 15 points (on arrondit à l'entier inférieur)
        $client->ajouterPoints((int) $pointsAjoutes);
        
        // ASSERT
        $this->assertEquals(15, $client->getPointsFidelite(), "15€ dépensés = 15 points de fidélité (RG-005)");
    }
    
    /**
     * TEST 2: Points cumulés sur plusieurs commandes
     */
    public function testPointsCumulesPlusieursCommandes()
    {
        // ARRANGE
        $client = new Client("Hugo", "Dupont", "hugo@test.com", "pass123");
        
        // ACT - Simuler 3 commandes
        $client->ajouterPoints(10); // Commande 1 : 10€
        $client->ajouterPoints(25); // Commande 2 : 25€
        $client->ajouterPoints(8);  // Commande 3 : 8€
        
        // ASSERT
        $this->assertEquals(43, $client->getPointsFidelite(), "Les points doivent se cumuler : 10 + 25 + 8 = 43");
    }
    
    /**
     * TEST 3: Client anonyme ne reçoit PAS de points
     */
    public function testClientAnonymeNeRecoitPasDePoints()
    {
        // ARRANGE
        $commande = new Commande(
            numeroChevalet: "042",
            typeCommande: "sur_place",
            modePaiement: "carte",
            montantTotal: 20.00,
            payementValide: true,
            client: null // ← CLIENT ANONYME
        );
        
        // ACT + ASSERT
        $this->assertNull($commande->getClient(), "Un client anonyme ne peut pas recevoir de points (pas de compte)");
    }
    
    /**
     * TEST 4: Points arrondis à l'entier inférieur
     */
    public function testPointsArrondisEntierInferieur()
    {
        // ARRANGE
        $client = new Client("Hugo", "Dupont", "hugo@test.com", "pass123");
        
        // ACT - Commande de 9.90€
        $montantCommande = 9.90;
        $pointsAjoutes = floor($montantCommande); // 9 points (pas 10)
        $client->ajouterPoints((int) $pointsAjoutes);
        
        // ASSERT
        $this->assertEquals(9, $client->getPointsFidelite(), "9.90€ = 9 points (arrondi à l'entier inférieur)");
    }
}
