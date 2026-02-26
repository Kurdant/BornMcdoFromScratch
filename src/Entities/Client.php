<?php

namespace WCDO\Entities;

class Client
{
    private ?int $id = null;
    private string $prenom;
    private string $nom;
    private string $email;
    private string $motDePasseHash;
    private int $pointsFidelite = 0;
    private \DateTimeImmutable $dateCreation;

    public function __construct(
        string $prenom,
        string $nom,
        string $email,
        string $motDePasse
    ) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Email invalide");
        }
        $this->prenom = $prenom;
        $this->nom = $nom;
        $this->email = $email;
        $this->motDePasseHash = password_hash($motDePasse, PASSWORD_BCRYPT);
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }
    public function getPrenom(): string { return $this->prenom; }
    public function getNom(): string { return $this->nom; }
    public function getEmail(): string { return $this->email; }
    public function getMotDePasseHash(): string { return $this->motDePasseHash; }
    public function getPointsFidelite(): int { return $this->pointsFidelite; }
    public function getDateCreation(): \DateTimeImmutable { return $this->dateCreation; }

    public function ajouterPoints(int $points): void
    {
        if ($points < 0) {
            throw new \InvalidArgumentException("Les points ajoutés ne peuvent pas être négatifs");
        }
        $this->pointsFidelite += $points;
    }

    public function verifierMotDePasse(string $motDePasse): bool
    {
        return password_verify($motDePasse, $this->motDePasseHash);
    }

    /** Hydratation depuis la BD (mot de passe déjà hashé) */
    public static function fromDatabase(int $id, string $prenom, string $nom, string $email, string $hashBd, int $points): self
    {
        $client = new self($prenom, $nom, $email, 'placeholder');
        $client->id = $id;
        $client->motDePasseHash = $hashBd;
        $client->pointsFidelite = $points;
        return $client;
    }
}
