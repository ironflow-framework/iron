<?php

namespace Forge\Database\Iron\Relationship;

use Forge\Database\Iron\Model;

class Pivot extends Model
{
    protected static $table; // Nom de la table pivot
    protected $db;
    protected $attributes = [];
    protected $fillable = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Définir le nom de la table pivot si nécessaire
        if (!isset(static::$table)) {
            static::$table = strtolower(class_basename(static::class)) . '_pivot';
        }
    }

    // Méthode pour insérer une entrée dans la table pivot
    public static function attach(int $modelID, int $relatedID): bool
    {
        $sql = "INSERT INTO " . static::$table . " (model_id, related_id) VALUES (:model_id, :related_id)";
        $stmt = (new static())->db->prepare($sql);
        return $stmt->execute(['model_id' => $modelID, 'related_id' => $relatedID]);
    }

    // Méthode pour détacher une entrée de la table pivot
    public static function detach(int $modelID, int $relatedID): bool
    {
        $sql = "DELETE FROM " . static::$table . " WHERE model_id = :model_id AND related_id = :related_id";
        $stmt = (new static())->db->prepare($sql);
        return $stmt->execute(['model_id' => $modelID, 'related_id' => $relatedID]);
    }
}
