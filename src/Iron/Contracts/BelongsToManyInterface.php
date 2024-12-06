<?php

namespace Forge\Database\Iron\Contracts;

use Forge\Database\Iron\Model;

interface BelongsToManyInterface
{
    /**
     * Récupère les éléments liés à la relation many-to-many.
     *
     * @return array
     */
    public function getRelated(): array;

    /**
     * Attache un élément lié à la table pivot.
     *
     * @param mixed $relatedId L'identifiant de l'élément à attacher.
     * @return bool
     */
    public function attach($relatedId): bool;

    /**
     * Détache un élément lié de la table pivot.
     *
     * @param mixed $relatedId L'identifiant de l'élément à détacher.
     * @return bool
     */
    public function detach($relatedId): bool;

    /**
     * Obtient le modèle auquel cette relation est attachée.
     *
     * @return \Forge\Database\Iron\Model
     */
    public function getModel(): Model;
}
