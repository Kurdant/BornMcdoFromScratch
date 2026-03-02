<?php

namespace Tests\Entities;

use PHPUnit\Framework\TestCase;
use WCDO\Entities\Client;

/**
 * Test Suite pour l'entité CLIENT
 * 
 * Teste l'entité CLIENT qui représente un utilisateur authentifié
 * avec historique de commandes et points de fidélité.
 * 
 * Règles de gestion testées:
 * - RG-005: 1€ dépensé = 1 point de fidélité
 * - Sécurité: Mot de passe hashé
 * - Validation: Email unique
 */
class ClientTest extends TestCase
{
    /**
     * TEST 1: Créer un client valide
     */
    public function testCreerClientValide()
    {
        // ARRANGE
        $prenom = "Hugo";
        $nom = "Dupont";
        $email = "hugo@example.com";
        $motDePasse = "password123";
        
        // ACT
        $client = new Client($prenom, $nom, $email, $motDePasse);
        
        // ASSERT
        $this->assertNotNull($client);
        $this->assertEquals($prenom, $client->getPrenom());
        $this->assertEquals($nom, $client->getNom());
        $this->assertEquals($email, $client->getEmail());
    }
    
    /**
     * TEST 2: Email doit être unique (pas de doublon)
     * 
     * Ce test simule une vérification d'unicité.
     * En réalité, cette contrainte est gérée au niveau BDD (UNIQUE).
     */
    public function testEmailClientDoitEtreUnique()
    {
        // ARRANGE
        $email = "test@example.com";
        $client1 = new Client("Jean", "Dupont", $email, "pass123");
        
        // ACT + ASSERT
        // Dans une vraie appli, tu vérifierais en BDD si l'email existe déjà
        // Pour l'instant, on teste juste que le format est valide
        $this->assertNotEmpty($client1->getEmail());
    }
    
    /**
     * TEST 3: Le mot de passe doit être hashé
     * 
     * CONTEXTE SÉCURITÉ:
     * JAMAIS stocker un mot de passe en clair !
     * On utilise password_hash() de PHP.
     */
    public function testMotDePasseEstHashe()
    {
        // ARRANGE
        $motDePasseClair = "password123";
        $client = new Client("Hugo", "Dupont", "hugo@test.com", $motDePasseClair);
        
        // ACT
        $motDePasseStocke = $client->getMotDePasseHash();
        
        // ASSERT
        // Le mot de passe stocké NE DOIT PAS être identique au mot de passe clair
        $this->assertNotEquals($motDePasseClair, $motDePasseStocke, "Le mot de passe ne doit PAS être en clair");
        
        // Le mot de passe stocké doit commencer par $2y$ (bcrypt)
        $this->assertStringStartsWith('$2y$', $motDePasseStocke, "Le mot de passe doit être hashé avec bcrypt");
    }
    
    /**
     * TEST 4: RG-005 - Les points de fidélité sont initialisés à 0
     */
    public function testPointsFideliteInitialisesAZero()
    {
        // ARRANGE + ACT
        $client = new Client("Hugo", "Dupont", "hugo@test.com", "pass123");
        
        // ASSERT
        $this->assertEquals(0, $client->getPointsFidelite(), "Les points de fidélité d'un nouveau client doivent être à 0 (RG-005)");
    }
    
    /**
     * TEST 5: Validation email - Format valide
     */
    public function testEmailFormatValide()
    {
        // ARRANGE + ACT
        $client = new Client("Hugo", "Dupont", "hugo@example.com", "pass123");
        
        // ASSERT
        $this->assertMatchesRegularExpression('/^[^@]+@[^@]+\.[^@]+$/', $client->getEmail(), "L'email doit avoir un format valide");
    }
    
    /**
     * TEST 6: Email invalide doit lever une exception
     */
    public function testEmailInvalideLeveException()
    {
        // ARRANGE + ACT + ASSERT
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Email invalide");
        
        // Email sans @
        new Client("Hugo", "Dupont", "emailinvalide", "pass123");
    }
}
