<?php

namespace Tests\Entities;

use PHPUnit\Framework\TestCase;
use WCDO\Entities\TailleBoisson;

/**
 * Test Suite pour l'entité TAILLE_BOISSON
 * 
 * Tests simples pour les tailles de boissons.
 */
class TailleBoissonTest extends TestCase
{
    /**
     * TEST 1: Créer une taille valide
     */
    public function testCreerTailleValide()
    {
        // ARRANGE + ACT
        $taille = new TailleBoisson(
            nom: "Moyenne",
            volume: 50,
            prixSupplement: 0.50
        );
        
        // ASSERT
        $this->assertNotNull($taille);
        $this->assertEquals("Moyenne", $taille->getNom());
        $this->assertEquals(50, $taille->getVolume());
        $this->assertEquals(0.50, $taille->getPrixSupplement());
    }
    
    /**
     * TEST 2: Volume doit être positif
     */
    public function testVolumeDoitEtrePositif()
    {
        // ARRANGE + ACT + ASSERT
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le volume doit être > 0");
        
        new TailleBoisson(nom: "Petite", volume: 0, prixSupplement: 0.00);
    }
    
    /**
     * TEST 3: Prix supplément peut être zéro (taille standard)
     */
    public function testPrixSupplementPeutEtreZero()
    {
        // ARRANGE + ACT
        $taille = new TailleBoisson(
            nom: "Petite",
            volume: 33,
            prixSupplement: 0.00 // Taille standard sans supplément
        );
        
        // ASSERT
        $this->assertEquals(0.00, $taille->getPrixSupplement());
    }
}
