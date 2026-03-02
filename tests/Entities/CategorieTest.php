<?php

namespace Tests\Entities;

use PHPUnit\Framework\TestCase;
use WCDO\Entities\Categorie;

/**
 * Test Suite pour l'entité CATEGORIE
 * 
 * Tests simples pour la classification des produits.
 */
class CategorieTest extends TestCase
{
    /**
     * TEST 1: Créer une catégorie valide
     */
    public function testCreerCategorieValide()
    {
        // ARRANGE + ACT
        $categorie = new Categorie(
            nom: "Burgers",
            description: "Nos délicieux burgers"
        );
        
        // ASSERT
        $this->assertNotNull($categorie);
        $this->assertEquals("Burgers", $categorie->getNom());
        $this->assertEquals("Nos délicieux burgers", $categorie->getDescription());
    }
    
    /**
     * TEST 2: Nom de catégorie obligatoire
     */
    public function testNomCategorieObligatoire()
    {
        // ARRANGE + ACT + ASSERT
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le nom de la catégorie est obligatoire");
        
        new Categorie(nom: "", description: "Test");
    }
    
    /**
     * TEST 3: Description optionnelle
     */
    public function testDescriptionOptionnelle()
    {
        // ARRANGE + ACT
        $categorie = new Categorie(nom: "Boissons");
        
        // ASSERT
        $this->assertNotNull($categorie);
        $this->assertEquals("Boissons", $categorie->getNom());
    }
}
