<?php 

namespace Forge\Database\Iron;

use Forge\Database\Iron\Relationship\BelongsToRelationship;
use Forge\Database\Iron\Relationship\HasManyRelationship;
use PDO;
use Exception;
use Forge\Database\Database;
use Forge\Database\Iron\Relationship\BelongsToManyRelationship;
use Forge\Database\Iron\Relationship\HasOneRelationship;
use PDOException;

class Model
{
    protected static $table; // Nom de la table

    protected $db; // Connexion à la base de données
    protected $sql; // Requête SQL
    protected $params = []; // Paramètres pour la requête
    protected $attributes = []; // Attributs du modèle
    protected $fillable = []; // Colonnes modifiables
    protected $id; // ID de l'enregistrement

    public function __construct(array $attributes = [])
    {
        $this->db = Database::getInstance()->getConnection(); // Connexion à la base de données

        // Initialiser les attributs
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key) || in_array($key, $this->fillable)) {
                $this->attributes[$key] = $value;
            }
        }

        // Définir la table si non spécifiée
        if (!isset(static::$table)) {
            static::$table = strtolower(class_basename(static::class)) . 's';
        }
    }

    public function getID(): int
    {
        return $this->id;
    }

    // Méthode getter pour accéder à la connexion
    public function getDb()
    {
        return $this->db;
    }

    protected static function setTimestamps(array &$data): void
    {
        $currentTimestamp = self::formatDateToString(new \DateTime());

        if (!isset($data['created_at'])) {
            $data['created_at'] = $currentTimestamp;
        }
        $data['updated_at'] = $currentTimestamp;
    }

    // ----- CRUD Operations -----

    // Créer un nouvel enregistrement
    public static function create(array $data): Model
    {

        self::setTimestamps($data);

        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_map(fn($col) => ":$col", array_keys($data)));

        $sql = "INSERT INTO " . static::$table . " ($columns) VALUES ($placeholders)";
        $stmt = (new static())->db->prepare($sql);

        try {
            $stmt->execute($data);
            // Récupérer l'ID de l'enregistrement nouvellement créé
            $data['id'] = (new static())->db->lastInsertId();
            return (new static())->populate($data);
        } catch (PDOException $e) {
            // Log the error or handle it
            throw new Exception("Database error: " . $e->getMessage());
        }

    }

    // Récupérer tous les enregistrements
    public static function all(): mixed
    {
        $sql = "SELECT * FROM " . static::$table;
        $stmt = (new static())->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
    }

    // Récupérer un enregistrement via l'id
    public static function find($id): mixed
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE id = :id";
        $stmt = (new static())->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new Exception("Aucun élément trouvé.");
        }
        else {
            return $stmt->fetchObject(static::class);
        }
        
    }

    // Mettre à jour un enregistrement
    public static function update($id, $data): mixed
    {

        $existing_element = static::find($id);

        if ($existing_element === false) {
            throw new Exception("Impossible de mettre à jour cette élément");
        }

        $fields = implode(", ", array_map(fn($col) => "$col = :$col", array_keys($data)));

        $sql = "UPDATE " . static::$table . " SET $fields WHERE id = :id";
        $stmt = (new static())->db->prepare($sql);
        $data['id'] = $id;

        try {
            $result = $stmt->execute($data);
            return $result;
        } catch (PDOException $e) {
            // Log the error or handle it
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    // Supprimer un enregistrement
    public static function delete($id): mixed
    {
        $sql = "DELETE FROM " . static::$table . " WHERE id = :id";
        $stmt = (new static())->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    // ----- Filtering and Counting -----

    // Compter les enregistrements
    public static function count(): mixed
    {
        $sql = "SELECT COUNT(*) FROM " . static::$table;
        $stmt = (new static())->db->query($sql);
        return $stmt->fetchColumn();
    }

    // Pagination
    public static function paginate(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM " . static::$table . " LIMIT :limit OFFSET :offset";
        $stmt = (new static())->db->prepare($sql);
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
        $total = static::count(); // Nombre total d'enregistrements

        return [
            'data' => $results,
            'total' => $total,
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage),
        ];
    }

    public static function first(): mixed
    {
        $sql = "SELECT * FROM " . static::$table . " LIMIT 1";
        $stmt = (new static())->db->query($sql);
        return $stmt->fetchObject(static::class);
    }

    public static function exists(array $conditions): bool
    {
        $whereClause = implode(" AND ", array_map(fn($col) => "$col = :$col", array_keys($conditions)));
        $sql = "SELECT COUNT(*) FROM " . static::$table . " WHERE $whereClause";
        $stmt = (new static())->db->prepare($sql);
        $stmt->execute($conditions);
        return (bool)$stmt->fetchColumn();
    }

    public static function latest(string $column = 'created_at'): mixed
    {
        $sql = "SELECT * FROM " . static::$table . " ORDER BY $column DESC LIMIT 1";
        $stmt = (new static())->db->query($sql);
        return $stmt->fetchObject(static::class);
    }

    public static function oldest(string $column = 'created_at'): mixed
    {
        $sql = "SELECT * FROM " . static::$table . " ORDER BY $column ASC LIMIT 1";
        $stmt = (new static())->db->query($sql);
        return $stmt->fetchObject(static::class);
    }

    public static function pluck(string $column): array
    {
        $sql = "SELECT $column FROM " . static::$table;
        $stmt = (new static())->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function chunk(int $size, callable $callback): void
    {
        $offset = 0;
        do {
            $sql = "SELECT * FROM " . static::$table . " LIMIT $size OFFSET $offset";
            $stmt = (new static())->db->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_CLASS, static::class);

            if (empty($results)) {
                break;
            }

            $callback($results);
            $offset += $size;
        } while (count($results) === $size);
    }

    public static function increment($id, string $column, int $amount = 1): bool
    {
        $sql = "UPDATE " . static::$table . " SET $column = $column + :amount WHERE id = :id";
        $stmt = (new static())->db->prepare($sql);
        return $stmt->execute(['amount' => $amount, 'id' => $id]);
    }

    public static function decrement($id, string $column, int $amount = 1): bool
    {
        $sql = "UPDATE " . static::$table . " SET $column = $column - :amount WHERE id = :id";
        $stmt = (new static())->db->prepare($sql);
        return $stmt->execute(['amount' => $amount, 'id' => $id]);
    }

    public static function withTrashed(): array
    {
        $sql = "SELECT * FROM " . static::$table;
        $stmt = (new static())->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
    }

    public static function onlyTrashed(): array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE deleted_at IS NOT NULL";
        $stmt = (new static())->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
    }

    public static function restore($id): bool
    {
        $sql = "UPDATE " . static::$table . " SET deleted_at = NULL WHERE id = :id";
        $stmt = (new static())->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public static function forceDelete($id): bool
    {
        $sql = "DELETE FROM " . static::$table . " WHERE id = :id";
        $stmt = (new static())->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }


    // Filtrer les enregistrements
    public static function filter(array $conditions): mixed
    {
        $whereClause = implode(" AND ", array_map(function ($col) {
            return "$col = :$col";
        }, array_keys($conditions)));

        $sql = "SELECT * FROM " . static::$table . " WHERE $whereClause";
        $stmt = (new static())->db->prepare($sql);
        $stmt->execute($conditions);
        return $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
    }


    // Relationship
    public function relationship(string $relatedModel, string $foreignKey, string $type, string $pivotTable = "", string $relatedKey = "")
    {
        switch ($type) {
            case 'belongsTo':
                return new BelongsToRelationship($this, $relatedModel, $foreignKey);
            case 'hasOne':
                return new HasOneRelationship($this, $relatedModel, $foreignKey);
            case 'hasMany':
                return new HasManyRelationship($this, $relatedModel, $foreignKey);
            case 'belongsToMany':
                return new BelongsToManyRelationship($this, $relatedModel, $pivotTable, $foreignKey, $relatedKey);
            default:
                throw new Exception("Unknown relationship type: $type");
        }
    }

    // ----- Query Builder -----

    // Méthode pour créer une nouvelle instance de QueryBuilder
    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::$table);
    }

    // Méthode where pour ajouter des conditions
    public function where(string $column, string $operator = '=', mixed $value): static
    {
        // Ajoute la condition à la requête
        $this->sql .= " WHERE $column $operator :$column";
        $this->params[$column] = $value; // Ajoute le paramètre
        return $this; // Retourne l'instance pour chaînage
    }

    // ----- Save and Populate -----

    // Méthode pour enregistrer ou mettre à jour l'enregistrement
    public function save(): bool
    {
        $data = $this->toArray(); // Obtenez les données sous forme de tableau

        if (isset($this->id)) {
            // Mettre à jour l'enregistrement existant
            return static::update($this->id, $data);
        } else {
            // Créer un nouvel enregistrement
            $newInstance = static::create($data);
            // Peupler l'instance actuelle avec les données nouvellement créées
            $this->populate((array)$newInstance);
            return true;
        }
    }

    // ----- Gestion des factories -----

    public static function factory(int $count = 1)
    {
        $factoryClass = 'Database\\Factories\\' . class_basename(static::class) . 'Factory';

        if (!class_exists($factoryClass)) {
            throw new Exception("Factory class $factoryClass does not exist.");
        }

        $factory = new $factoryClass(static::class);
        return $factory->count($count);
    }


    public static function form()
    {
        $formClass = 'App\\Forms\\' . class_basename(static::class) . 'Form';

        if (!class_exists($formClass)) {
            throw new Exception("Form class $formClass does not exist.");
        }

        $form = new $formClass(static::class);
        return $form;
    }

    // Convertir l'objet en tableau
    protected function toArray(): array
    {
        return $this->attributes;
    }

    // Peupler les propriétés de l'objet
    protected function populate($data): static
    {
        $this->attributes = $data;
        return $this; // Retourner l'instance courante
    }


    /**
     * Formate une date ou une chaîne en objet DateTime en une chaîne au format 'Y-m-d H:i:s'.
     *
     * @param mixed $date Une instance de \DateTime ou une chaîne représentant une date.
     * @return string|null Retourne la date formatée ou null si l'entrée est invalide.
     */
    private static function formatDateToString($date): ?string
    {
        if ($date instanceof \DateTime) {
            return $date->format('Y-m-d H:i:s');
        }

        if (is_string($date)) {
            try {
                $parsedDate = new \DateTime($date);
                return $parsedDate->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // Si la chaîne n'est pas une date valide
                return null;
            }
        }

        // Si l'entrée n'est ni une chaîne ni un objet DateTime
        return null;
    }

}