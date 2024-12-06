<?php

namespace Forge\Database\Iron\Relationship;

use Forge\Database\Iron\Contracts\RelationshipInterface;
use Forge\Database\Iron\Model;

class BelongsToRelationship implements RelationshipInterface
{
    protected $model;
    protected $relatedModel;
    protected $foreignKey;

    /**
     * Constructeur pour initialiser la relation.
     *
     * @param Model $model L'instance du modèle principal.
     * @param string $relatedModel Le nom du modèle lié (par exemple, "User").
     * @param string $foreignKey La clé étrangère dans le modèle principal.
     */
    public function __construct(Model $model, string $relatedModel, string $foreignKey)
    {
        $this->model = $model;
        $this->relatedModel = $relatedModel;
        $this->foreignKey = $foreignKey;
    }

    /**
     * Associe un modèle en définissant la clé étrangère et en sauvegardant le modèle.
     *
     * @param Model $related Le modèle à associer.
     * @return void
     */
    public function associate($related): void
    {
        // Définir la clé étrangère sur le modèle principal
        $this->model->{$this->foreignKey} = $related->getID();

        // Sauvegarder le modèle principal avec la clé étrangère
        $this->model->save();
    }

    /**
     * Dissocie le modèle en mettant la clé étrangère à null et en sauvegardant.
     *
     * @return void
     */
    public function dissociate(): void
    {
        // Enlever la clé étrangère
        $this->model->{$this->foreignKey} = null;

        // Sauvegarder les changements
        $this->model->save();
    }

    /**
     * Récupère le modèle associé en utilisant la clé étrangère.
     *
     * @return mixed Le modèle associé, ou null si non trouvé.
     */
    public function getRelated(): mixed
    {
        // Créer une instance du modèle lié
        $relatedModel = new $this->relatedModel;

        // Utiliser la méthode `find()` pour récupérer l'instance liée via la clé étrangère
        return $relatedModel->find($this->model->{$this->foreignKey});
    }
}
