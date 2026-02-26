<?php

namespace WCDO\Exceptions;

class StockInsuffisantException extends \Exception
{
    public function __construct(string $nomProduit, int $stockDisponible, int $quantiteDemandee)
    {
        parent::__construct(
            "Stock insuffisant pour '{$nomProduit}' : {$stockDisponible} disponible(s), {$quantiteDemandee} demandé(s)"
        );
    }
}
