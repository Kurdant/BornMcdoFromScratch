<?php

namespace Tests\Entities;

use PHPUnit\Framework\TestCase;
use WCDO\Entities\Sauce;

/**
 * Test Suite pour l'entité SAUCE
 * 
 * Tests simples pour les sauces disponibles.
 */
class SauceTest extends TestCase
{
    /**
     * TEST 1: Créer une sauce valide
     */
    public function testCreerSauceValide()
    {
        // ARRANGE + ACT
        $sauce = new Sauce(nom: "Ketchup");
        
        // ASSERT
        $this->assertNotNull($sauce);
        $this->assertEquals("Ketchup", $sauce->getNom());
    }
    
    /**
     * TEST 2: Nom de sauce obligatoire
     */
    public function testNomSauceObligatoire()
    {
        // ARRANGE + ACT + ASSERT
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom de la sauce est obligatoire");
        
        new Sauce(nom: "");
    }
    
    /**
     * TEST 3: Plusieurs sauces différentes
     */
    public function testPlusieursSaucesDifferentes()
    {
        // ARRANGE + ACT
        $ketchup = new Sauce(nom: "Ketchup");
        $mayo = new Sauce(nom: "Mayonnaise");
        $bbq = new Sauce(nom: "BBQ");
        
        // ASSERT
        $this->assertNotEquals($ketchup->getNom(), $mayo->getNom());
        $this->assertEquals("BBQ", $bbq->getNom());
    }
}
