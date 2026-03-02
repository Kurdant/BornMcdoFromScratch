<?php

declare(strict_types=1);

namespace WCDO\Services;

use WCDO\Repositories\CommandeRepository;
use WCDO\Repositories\CommandeProduitRepository;
use WCDO\Repositories\PanierRepository;
use WCDO\Repositories\PanierProduitRepository;
use WCDO\Repositories\ProduitRepository;
use WCDO\Repositories\ClientRepository;

class CommandeService
{
    private CommandeRepository $commandeRepo;
    private CommandeProduitRepository $cpRepo;
    private PanierRepository $panierRepo;
    private PanierProduitRepository $ppRepo;
    private ProduitRepository $produitRepo;
    private ClientRepository $clientRepo;

    public function __construct()
    {
        $this->commandeRepo = new CommandeRepository();
        $this->cpRepo       = new CommandeProduitRepository();
        $this->panierRepo   = new PanierRepository();
        $this->ppRepo       = new PanierProduitRepository();
        $this->produitRepo  = new ProduitRepository();
        $this->clientRepo   = new ClientRepository();
    }

    /**
     * Crée une commande à partir du panier courant.
     *
     * @param string $sessionId
     * @param array  $data  { numero_chevalet, type_commande, mode_paiement }
     * @param int|null $clientId
     * @return array
     */
    public function passer(string $sessionId, array $data, ?int $clientId = null): array
    {
        // Validation des champs requis
        foreach (['numero_chevalet', 'type_commande', 'mode_paiement'] as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Champ requis manquant : {$field}");
            }
        }

        $panier = $this->panierRepo->findBySessionId($sessionId);
        if (!$panier) {
            throw new \RuntimeException("Aucun panier trouvé pour cette session");
        }

        $lignes = $this->ppRepo->findByPanierId((int) $panier['id']);
        if (empty($lignes)) {
            throw new \RuntimeException("Le panier est vide");
        }

        // Calcul du total
        $total = array_sum(array_map(
            fn(array $l) => $l['prix_unitaire'] * $l['quantite'],
            $lignes
        ));

        // Génération du numéro de commande unique
        $numeroCommande = 'CMD-' . strtoupper(uniqid()) . '-' . date('Ymd');

        // Validation numéro chevalet
        $chevalet = (int) $data['numero_chevalet'];
        if ($chevalet < 1 || $chevalet > 999) {
            throw new \InvalidArgumentException("Le numéro de chevalet doit être entre 001 et 999");
        }

        // Validation type et mode
        if (!in_array($data['type_commande'], ['sur_place', 'a_emporter'], true)) {
            throw new \InvalidArgumentException("Type de commande invalide");
        }
        if (!in_array($data['mode_paiement'], ['carte', 'especes'], true)) {
            throw new \InvalidArgumentException("Mode de paiement invalide");
        }

        // Décrémenter le stock de chaque produit
        foreach ($lignes as $ligne) {
            $produit = $this->produitRepo->findById($ligne['id_produit']);
            if (!$produit) {
                continue;
            }
            $newStock = max(0, $produit['stock'] - $ligne['quantite']);
            $this->produitRepo->update($ligne['id_produit'], array_merge($produit, ['stock' => $newStock]));
        }

        // Sauvegarde de la commande
        $commandeId = $this->commandeRepo->save([
            'numero_commande' => $numeroCommande,
            'numero_chevalet' => $chevalet,
            'type_commande'   => $data['type_commande'],
            'mode_paiement'   => $data['mode_paiement'],
            'montant_total'   => $total,
            'client_id'       => $clientId,
        ]);

        // Sauvegarde des lignes de commande
        $this->cpRepo->saveAll($commandeId, $lignes);

        // Points fidélité (1 point par euro, arrondi inférieur)
        if ($clientId) {
            $client = $this->clientRepo->findById($clientId);
            if ($client) {
                $pointsGagnes = (int) floor($total);
                $nouveauxPoints = $client->getPointsFidelite() + $pointsGagnes;
                $this->clientRepo->updatePoints($clientId, $nouveauxPoints);
            }
        }

        // Vider le panier après commande
        $this->ppRepo->clearPanier((int) $panier['id']);
        $this->panierRepo->delete((int) $panier['id']);

        return $this->getByNumero($numeroCommande);
    }

    public function getByNumero(string $numero): array
    {
        $commande = $this->commandeRepo->findByNumero($numero);
        if (!$commande) {
            throw new \RuntimeException("Commande introuvable : {$numero}");
        }

        $lignes = $this->cpRepo->findByCommandeId((int) $commande['id']);

        return array_merge($commande, ['lignes' => $lignes]);
    }
}
