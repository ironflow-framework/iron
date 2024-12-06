<?php

namespace Forge\Database\Iron;

use Forge\Database\Database;
use PDO;

class QueryBuilder
{
    protected $table;
    protected $db;
    protected $columns = '*';
    protected $orderBy = "";
    protected $limit = "";
    protected $offset = "";
    protected $joins = "";
    protected $groupBy = "";
    protected $having = "";
    protected $whereClauses = [];
    protected $params = [];

    public function __construct(string $table)
    {
        $this->table = $table;
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Permet de spécifier les colonnes à sélectionner.
     * @param string[] $columns
     * @return \Forge\Database\Iron\QueryBuilder
     */
    public function select(string ...$columns): self
    {
        $this->columns = implode(', ', $columns);
        return $this;
    }

    /**
     * Retourne le nombre total de résultats correspondant à la requête.
     * @return int
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        if (!empty($this->whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $this->whereClauses);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->params);

        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    /**
     * Permet d'ajouter une clausse where
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return \Forge\Database\Iron\QueryBuilder
     */
    public function where(string $column, string $operator, $value): self
    {
        $this->whereClauses[] = "$column $operator :$column";
        $this->params[$column] = $value;
        return $this;
    }

    /**
     * Met à jour un ou plusieurs enregistrements.
     * @param array $data
     * @return int
     */
    public function update(array $data): int
    {
        $columns = implode(', ', array_map(fn($col) => "$col = :$col", array_keys($data)));
        $where = implode(' AND ', $this->whereClauses);

        $sql = "UPDATE {$this->table} SET $columns WHERE $where";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute(array_merge($data, $this->params)) ? $stmt->rowCount() : 0;
    }

    /**
     * Supprimer un ou plusieurs enregistrements.
     * @return mixed
     */
    public function delete()
    {
        $where = implode(' AND ', $this->whereClauses);

        // Requête SQL de suppression
        $sql = "DELETE FROM {$this->table} WHERE $where";

        // Exécution de la requête
        $statement = Database::getInstance()->prepare($sql);
        $statement->execute($this->params);

        return $statement->rowCount();
    }

    /**
     * Permet de faire une pagination
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function paginate(int $perPage, int $page): array
    {
        $offset = ($page - 1) * $perPage;
        return $this->limit($perPage)->offset($offset)->execute();
    }

    /**
     * Récupère le premier résultat
     *
     * @return self|null Le premier enregistrement ou null si aucun résultat n'existe
     */
    public function first(): ?self
    {
        $results = $this->execute();

        return $results ? $results[0] : null;
    }

    /**
     * Récupère le dernier résultat
     *
     * @return self|null Le dernier enregistrement ou null si aucun résultat n'existe
     */
    public function last(): ?self
    {
        $results = $this->execute();

        return $results ? $results[count($results) - 1] : null;
    }

    /**
     * Jointures de type INNER JOIN par defaut entre tables.
     * @param string $table
     * @param string $localKey
     * @param string $operator
     * @param string $foreignKey
     * @param "INNER" | "LEFT" | "RIGHT" $type
     * @return \Forge\Database\Iron\QueryBuilder
     */
    public function join(string $table, string $localKey, string $operator, string $foreignKey, string $type = 'INNER'): self
    {
        $this->joins .= " $type JOIN $table ON $localKey $operator $foreignKey";
        return $this;
    }


    /**
     * Permet de filtrer sur des colonnes agrégées
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return \Forge\Database\Iron\QueryBuilder
     */
    public function having(string $column, string $operator, $value): self
    {
        $this->having = "HAVING $column $operator :having_$column";
        $this->params["having_$column"] = $value;
        return $this;
    }

    /**
     * Permet d'exécuter une requête brute.
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function raw(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ajoute un tri sur les résultats.
     * @param string $column
     * @param string $direction
     * @return \Forge\Database\Iron\QueryBuilder
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy = "ORDER BY $column $direction";
        return $this;
    }

    /**
     * Permet de regrouper les résultats.
     * @param string[] $columns
     * @return \Forge\Database\Iron\QueryBuilder
     */
    public function groupBy(string ...$columns): self
    {
        $this->groupBy = "GROUP BY " . implode(', ', $columns);
        return $this;
    }


    /**
     * Permet de limiter les résultats.
     * @param int $value
     * @return \Forge\Database\Iron\QueryBuilder
     */
    public function limit(int $value): self
    {
        $this->limit = "LIMIT $value";
        return $this;
    }

    /**
     * Permet de paginer les résultats.
     * @param int $value
     * @return \Forge\Database\Iron\QueryBuilder
     */
    public function offset(int $value): self
    {
        $this->offset = "OFFSET $value";
        return $this;
    }

    /**
     * Permet d'executer le QueryBuilder
     * @return array
     */
    public function execute(): array
    {
        $sql = "SELECT {$this->columns} FROM {$this->table} {$this->joins}";

        if (!empty($this->whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $this->whereClauses);
        }

        if (!empty($this->groupBy)) {
            $sql .= " {$this->groupBy}";
        }

        if (!empty($this->having)) {
            $sql .= " {$this->having}";
        }

        if (!empty($this->orderBy)) {
            $sql .= " {$this->orderBy}";
        }

        if (!empty($this->limit)) {
            $sql .= " {$this->limit}";
        }

        if (!empty($this->offset)) {
            $sql .= " {$this->offset}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->params);

        return $stmt->fetchAll(PDO::FETCH_CLASS, 'App\Models\\' . ucfirst($this->table));
    }
}
