<?php

namespace Forge\Database;

use Forge\CLI\Logger;

abstract class Seeder
{

    protected $logger;
    public function __construct() {
        $this->logger = new Logger();
    }
    /**
     * Méthode à implémenter par chaque seeder.
     */
    abstract public function run(): void;

    public function call(string $seederClass): void
    {
        $seeder = new $seederClass();
        $seeder->run();
    }

    /**
     * Méthode statique pour exécuter le SeederManager.
     */
    public static function execute(): void
    {
        $manager = new self();
        $manager->run();
    }

    /**
     * Méthode utilitaire pour insérer des données dans la base.
     *
     * @param string $table Nom de la table.
     * @param array $data Données à insérer.
     */
    protected function insert($table, $data)
    {
        // Exemple d'insertion via PDO
        $db = Database::getInstance()->getConnection();
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(',:', array_keys($data));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $db->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->execute();
    }
}
