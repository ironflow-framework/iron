<?php

namespace Forge\Database\Iron\Relationship;

use Forge\Database\Iron\Contracts\BelongsToManyInterface;
use Forge\Database\Iron\Model;
use PDO;

class BelongsToManyRelationship implements BelongsToManyInterface
{
    protected $model;
    protected $relatedModel;
    protected $pivotTable;
    protected $foreignKey;
    protected $relatedKey;

    public function __construct(
        Model $model,
        string $relatedModel,
        string $pivotTable,
        string $foreignKey,
        string $relatedKey
    ) {
        $this->model = $model;
        $this->relatedModel = $relatedModel;
        $this->pivotTable = $pivotTable;
        $this->foreignKey = $foreignKey;
        $this->relatedKey = $relatedKey;
    }

    public function getRelated(): array
    {
        // Utilisation de getDb() pour accéder à la connexion
        $sql = "SELECT * FROM {$this->relatedModel} r
                INNER JOIN {$this->pivotTable} p ON p.{$this->relatedKey} = r.id
                WHERE p.{$this->foreignKey} = :id";

        $stmt = $this->model->getDb()->prepare($sql);
        $stmt->execute(['id' => $this->model->getID()]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, $this->relatedModel);
    }

    public function attach($relatedId): bool
    {
        // Ajouter une relation dans la table pivot
        $sql = "INSERT INTO {$this->pivotTable} ({$this->foreignKey}, {$this->relatedKey}) VALUES (:foreign_id, :related_id)";
        $stmt = $this->model->getDb()->prepare($sql);
        return $stmt->execute([
            'foreign_id' => $this->model->getID(),
            'related_id' => $relatedId
        ]);
    }

    public function detach($relatedId): bool
    {
        // Supprimer une relation dans la table pivot
        $sql = "DELETE FROM {$this->pivotTable} WHERE {$this->foreignKey} = :foreign_id AND {$this->relatedKey} = :related_id";
        $stmt = $this->model->getDb()->prepare($sql);
        return $stmt->execute([
            'foreign_id' => $this->model->getID(),
            'related_id' => $relatedId
        ]);
    }

    /**
     * Implémentation de la méthode getModel()
     *
     * @return \Forge\Database\Iron\Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }
}

