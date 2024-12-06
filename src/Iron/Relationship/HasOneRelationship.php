<?php

namespace Forge\Database\Iron\Relationship;

use Forge\Database\Iron\Contracts\HasOne;
use Forge\Database\Iron\Model;

class HasOneRelationship implements HasOne
{
    protected $parent;
    protected $relatedModel;
    protected $foreignKey;

    public function __construct(Model $parent, string $relatedModel, string $foreignKey)
    {
        $this->parent = $parent;
        $this->relatedModel = $relatedModel;
        $this->foreignKey = $foreignKey;
    }

    public function associate($model): void
    {
        // Associer l'enregistrement lié
        $model->{$this->foreignKey} = $this->parent->getID();
        $model->save();
    }

    public function dissociate(): void
    {
        // Dissocier la relation
        $relatedModel = new $this->relatedModel;
        $relatedModel->{$this->foreignKey} = null;
        $relatedModel->save();
    }

    public function getRelated(): mixed
    {
        // Récupérer l'enregistrement lié
        $relatedModel = new $this->relatedModel;
        return $relatedModel::where($this->foreignKey, '=', $this->parent->getID())->first();
    }
}
