<?php

namespace Forge\Database\Iron;

class Anvil
{
    protected string $tableName;
    protected array $columns = [];
    protected array $foreignKeys = [];
    protected array $constraints = [];
    protected array $indexes = [];

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }

    // Méthode pour définir une colonne ID auto-incrémentée
    public function id(): static
    {
        $this->columns[] = [
            'name' => 'id',
            'type' => 'INT AUTO_INCREMENT',
            'primary' => true,
            'nullable' => false, // ID ne doit pas être nullable
        ];
        return $this;
    }

    // Méthode pour ajouter les timestamps (created_at et updated_at)
    public function timestamps(): static
    {
        $this->columns[] = [
            'name' => 'created_at',
            'type' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'nullable' => false,
        ];
        $this->columns[] = [
            'name' => 'updated_at',
            'type' => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'nullable' => false,
        ];
        return $this;
    }

    // Méthode pour définir une colonne string avec option de valeur par défaut
    public function string(string $name, int $length = 255): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => "VARCHAR($length)",
            'nullable' => false,
            'unique' => false,
            'default' => null, // Initialement sans valeur par défaut
        ];
        return $this;
    }

    public function text(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => "TEXT",
            'nullable' => false,
            'unique' => false,
            'default' => null, // Initialement sans valeur par défaut
        ];
        return $this;
    }

    public function boolean(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => "BOOLEAN",
            'nullable' => false,
            'unique' => false,
            'default' => null,
        ];
        return $this;
    }

    // Méthode pour définir une colonne double avec option de valeur par défaut
    public function double(string $name, int $precision = 10, int $scale = 2): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => "DOUBLE($precision, $scale)",
            'nullable' => false,
            'unique' => false,
            'default' => null, // Initialement sans valeur par défaut
        ];
        return $this;
    }

    public function integer(string $name, bool $autoIncrement = false): static
    {
        $type = $autoIncrement ? "INT AUTO_INCREMENT" : "INT";
        $this->columns[] = [
            'name' => $name,
            'type' => $type,
            'nullable' => false,
        ];
        return $this;
    }

    public function uuid(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => "CHAR(36)", // Utilisé pour stocker l'UUID en format standard (36 caractères)
            'nullable' => false,
            'unique' => false,
            'default' => null,
        ];
        return $this;
    }

    public function datetime(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'DATETIME',
            'nullable' => false,
        ];
        return $this;
    }

    public function char(string $name, int $length = 1): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => "CHAR($length)",
            'nullable' => false,
        ];
        return $this;
    }


    public function enum(string $name, array $values): static
    {
        $enumValues = implode(", ", array_map(fn($v) => "'$v'", $values));
        $this->columns[] = [
            'name' => $name,
            'type' => "ENUM($enumValues)",
            'nullable' => false,
            'unique' => false,
            'default' => null, // Initialement sans valeur par défaut
        ];
        return $this;
    }

    // Méthode pour définir une valeur par défaut pour la dernière colonne ajoutée
    public function default(mixed $value): static
    {
        $index = count($this->columns) - 1; // Index de la dernière colonne
        if (isset($this->columns[$index])) {
            $this->columns[$index]['default'] = $value; // Assigner la valeur par défaut
        }
        return $this; // Permet le chaînage
    }

    // Marquer la dernière colonne comme nullable
    public function nullable(): static
    {
        $index = count($this->columns) - 1;
        if (isset($this->columns[$index])) {
            $this->columns[$index]['nullable'] = true;
        }
        return $this;
    }

    // Marquer la dernière colonne comme unique
    public function unique(): static
    {
        $index = count($this->columns) - 1;
        if (isset($this->columns[$index])) {
            $this->columns[$index]['unique'] = true;
        }
        return $this;
    }

    public function unsigned(): static
    {
        $index = count($this->columns) - 1;
        if (isset($this->columns[$index])) {
            $this->columns[$index]['unsigned'] = true;
        }
        return $this;
    }

    // Méthode pour ajouter un index sur une ou plusieurs colonnes
    public function index(array $columns): static
    {
        $indexName = implode('_', $columns) . '_index';
        $this->indexes[] = [
            'name' => $indexName,
            'columns' => $columns,
        ];
        return $this;
    }

    public function foreignKey(string $column, string $referencedTable, string $referencedColumn = 'id'): static
    {
        $this->foreignKeys[] = [
            'column' => $column,
            'referencedTable' => $referencedTable,
            'referencedColumn' => $referencedColumn,
            'onDelete' => 'CASCADE', // Valeur par défaut
            'onUpdate' => 'CASCADE', // Valeur par défaut
        ];
        return $this;
    }

    // Permet de modifier la règle ON DELETE de la dernière clé étrangère ajoutée
    public function onDelete(string $action): static
    {
        $index = count($this->foreignKeys) - 1;
        if (isset($this->foreignKeys[$index])) {
            $this->foreignKeys[$index]['onDelete'] = strtoupper($action);
        }
        return $this; // Permet le chaînage
    }

    // Permet de modifier la règle ON UPDATE de la dernière clé étrangère ajoutée
    public function onUpdate(string $action): static
    {
        $index = count($this->foreignKeys) - 1;
        if (isset($this->foreignKeys[$index])) {
            $this->foreignKeys[$index]['onUpdate'] = strtoupper($action);
        }
        return $this; // Permet le chaînage
    }

    // Génère le SQL pour créer la table
    public function getCreateTableSQL(): string
    {
        if (empty($this->columns)) {
            throw new \Exception("No columns defined for table '{$this->tableName}'.");
        }

        // Transformation de chaque colonne en SQL
        $columnsSQL = array_map(function ($column) {
            $sql = "{$column['name']} {$column['type']}";

            if (isset($column['primary']) && $column['primary']) {
                $sql .= " PRIMARY KEY";
            }

            if (isset($column['nullable']) && !$column['nullable']) {
                $sql .= " NOT NULL";
            }

            if (isset($column['default']) && $column['default'] !== null) {

                if (is_string($column['default'])) {
                    $default = "'{$column['default']}'";
                } elseif (is_bool($column['default'])) {
                    $default = $column['default'] ? 1 : 0;
                } else {
                    $default =
                        $column['default'];
                }

                $sql .= " DEFAULT {$default}";
            }

            if (!empty($column['unique'])) {
                $sql .= " UNIQUE";
            }

            return $sql;
        }, $this->columns);

        // Ajout des clés étrangères dans la génération du SQL
        $foreignKeysSQL = array_map(function ($foreignKey) {
            return "CONSTRAINT `fk_{$this->tableName}_{$foreignKey['column']}` FOREIGN KEY (`{$foreignKey['column']}`) 
                    REFERENCES `{$foreignKey['referencedTable']}` (`{$foreignKey['referencedColumn']}`)
                    ON DELETE {$foreignKey['onDelete']} 
                    ON UPDATE {$foreignKey['onUpdate']}";
        }, $this->foreignKeys);

        // Merge des colonnes et des clés étrangères
        $columnsSQL = array_merge($columnsSQL, $foreignKeysSQL);

        $columnsSQL = implode(", ", $columnsSQL);
        return "CREATE TABLE IF NOT EXISTS `{$this->tableName}` ({$columnsSQL});";
    }

    public function constraint(string $constraintSQL): static
    {
        $this->constraints[] = $constraintSQL;
        return $this;
    }

    public function getAlterTableSQL(): string
    {
        if (empty($this->columns)) {
            throw new \Exception("No columns defined for table '{$this->tableName}'.");
        }

        // Transformation des colonnes en SQL pour ALTER
        $addColumnsSQL = array_map(function ($column) {
            $sql = "ADD COLUMN `{$column['name']}` {$column['type']}";

            if (isset($column['primary']) && $column['primary']) {
                $sql .= " PRIMARY KEY";
            }

            if (isset($column['nullable']) && !$column['nullable']) {
                $sql .= " NOT NULL";
            }

            if (isset($column['default']) && $column['default'] !== null) {
                $default = is_string($column['default']) ? "'{$column['default']}'" : $column['default'];
                $sql .= " DEFAULT {$default}";
            }

            if (!empty($column['unique'])) {
                $sql .= " UNIQUE";
            }

            return $sql;
        }, $this->columns);

        // Transformation des clés étrangères en SQL
        $foreignKeysSQL = array_map(function ($fk) {
            return "ADD CONSTRAINT `fk_{$fk['column']}_{$fk['referencedTable']}` FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$fk['referencedTable']}`(`{$fk['referencedColumn']}`) ON DELETE {$fk['onDelete']} ON UPDATE {$fk['onUpdate']}";
        }, $this->foreignKeys);

        // Combine les commandes pour ajouter des colonnes et des clés étrangères
        $sqlParts = array_merge($addColumnsSQL, $foreignKeysSQL);

        if (empty($sqlParts)) {
            throw new \Exception("No alterations defined for table '{$this->tableName}'.");
        }

        $alterSQL = implode(", ", $sqlParts);
        return "ALTER TABLE `{$this->tableName}` {$alterSQL};";
    }

}
