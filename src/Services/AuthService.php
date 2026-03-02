<?php

declare(strict_types=1);

namespace WCDO\Services;

use WCDO\Repositories\ClientRepository;
use WCDO\Repositories\AdminRepository;
use WCDO\Entities\Client;

class AuthService
{
    private ClientRepository $clientRepo;
    private AdminRepository $adminRepo;

    public function __construct()
    {
        $this->clientRepo = new ClientRepository();
        $this->adminRepo  = new AdminRepository();
    }

    /** Inscription d'un nouveau client */
    public function register(array $data): array
    {
        foreach (['prenom', 'nom', 'email', 'mot_de_passe'] as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Champ requis manquant : {$field}");
            }
        }

        if ($this->clientRepo->emailExiste($data['email'])) {
            throw new \RuntimeException("Cet email est déjà utilisé");
        }

        if (strlen($data['mot_de_passe']) < 6) {
            throw new \InvalidArgumentException("Le mot de passe doit contenir au moins 6 caractères");
        }

        $client = new Client(
            $data['prenom'],
            $data['nom'],
            $data['email'],
            $data['mot_de_passe']
        );

        $id = $this->clientRepo->save($client);

        return $this->clientToArray($this->clientRepo->findById($id));
    }

    /** Connexion client */
    public function loginClient(string $email, string $motDePasse): array
    {
        $client = $this->clientRepo->findByEmail($email);

        if (!$client || !$client->verifierMotDePasse($motDePasse)) {
            throw new \RuntimeException("Email ou mot de passe incorrect");
        }

        return $this->clientToArray($client);
    }

    /** Connexion admin */
    public function loginAdmin(string $email, string $motDePasse): array
    {
        $admin = $this->adminRepo->findByEmail($email);

        if (!$admin) {
            throw new \RuntimeException("Email ou mot de passe incorrect");
        }

        if (!password_verify($motDePasse, $admin['mot_de_passe'])) {
            throw new \RuntimeException("Email ou mot de passe incorrect");
        }

        return [
            'id'    => (int) $admin['id'],
            'nom'   => $admin['nom'],
            'email' => $admin['email'],
            'role'  => 'admin',
        ];
    }

    public function getClientById(int $id): ?array
    {
        $client = $this->clientRepo->findById($id);
        return $client ? $this->clientToArray($client) : null;
    }

    private function clientToArray(Client $client): array
    {
        return [
            'id'              => $client->getId(),
            'prenom'          => $client->getPrenom(),
            'nom'             => $client->getNom(),
            'email'           => $client->getEmail(),
            'points_fidelite' => $client->getPointsFidelite(),
        ];
    }
}
