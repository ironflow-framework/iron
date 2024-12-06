<?php

namespace Forge\Database\Iron\Contracts;

interface RelationshipInterface
{
    /**
     * Associe un modèle au modèle courant.
     *
     * @param mixed $related Le modèle à associer.
     * @return void
     */
    public function associate($model): void;

    /**
     * Dissocie un modèle du modèle courant.
     *
     * @return void
     */
    public function dissociate(): void;

    /**
     * Récupère le modèle lié.
     *
     * @return mixed Le modèle lié ou null.
     */
    public function getRelated(): mixed;
}
