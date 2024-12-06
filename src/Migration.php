<?php

namespace Forge\Database;

use Forge\CLI\Logger;

class Migration
{
    protected $db;
    protected $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger(); // Instancier le Logger
        $this->initializeMigrationHistory();
    }

    protected function execute($sql): void
    {
        $this->db->exec($sql);
    }

    /**
     * Initialise la table d'historique des migrations.
     */
    private function initializeMigrationHistory(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations_history(
            `id` INT NOT NULL AUTO_INCREMENT,
            `migration` VARCHAR(255) NOT NULL,
            `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        )";
        $this->execute($sql);
    }

    /**
     * Supprime la table migration history.
     */
    private function dropMigrationHistory(): void
    {
        $sql = "DROP TABLE IF EXISTS migrations_history;";
        $this->execute($sql);
    }

    /**
     * Vérifie si une migration a déjà été exécutée.
     */
    private function isMigrationExecuted(string $migration): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM migrations_history WHERE migration = :migration");
        $stmt->execute(['migration' => $migration]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Marque une migration comme exécutée.
     */
    private function markMigrationAsExecuted(string $migration): void
    {
        $stmt = $this->db->prepare("INSERT INTO migrations_history (migration) VALUES (:migration)");
        $stmt->execute(['migration' => $migration]);
    }

    /**
     * Supprime une migration de l'historique des migrations exécutées.
     */
    private function deleteMigrationAsExecuted(string $migration): void
    {
        $stmt = $this->db->prepare("DELETE FROM migrations_history WHERE migration = :migration");
        $stmt->execute(['migration' => $migration]);
    }

    /**
     * Affiche l'état des migrations (status).
     */
    public function showMigrationStatus(): void
    {
        $stmt = $this->db->query("SELECT migration, executed_at FROM migrations_history ORDER BY executed_at");
        $results = $stmt->fetchAll();

        $this->logger->log("info", "Status des migrations :");
        if (empty($results)) {
            $this->logger->log("success", "Aucune migration exécutée.");
        } else {
            foreach ($results as $row) {
                $this->logger->log("success" , "- {$row['migration']} exécutée le {$row['executed_at']}");
            }
        }
    }

    protected function handleRunMigration($migrationInstance, $migrationName): void
    {
        if (!$this->isMigrationExecuted($migrationName)) {
            $this->logger->log("info", "Exécution de la migration...");
            $migrationInstance->up();
            $this->markMigrationAsExecuted($migrationName);
        }
    }

    protected function handleRollbackMigration($migrationInstance, $migrationName): void
    {
        if ($this->isMigrationExecuted($migrationName)) {
            $this->logger->log("info", "Rollback de la migration...");
            $migrationInstance->down();
            $this->deleteMigrationAsExecuted($migrationName);
        }
    }

    protected function handleRefreshMigration($migrationInstance, $migrationName): void
    {
        if ($this->isMigrationExecuted($migrationName)) {
            $this->logger->log("info", "Refresh de la migration...");
            $migrationInstance->down();
            $this->deleteMigrationAsExecuted($migrationName);
        }
        $migrationInstance->up();
        $this->markMigrationAsExecuted($migrationName);
    }

    protected function handleFreshMigration($migrationInstance, $migrationName): void
    {
        $this->logger->log("info", "Fresh migration...");
        $this->dropMigrationHistory();
        $migrationInstance->down();
        $this->initializeMigrationHistory();
        $migrationInstance->up();
        $this->markMigrationAsExecuted($migrationName);
    }


    /**
     * Exécute les migrations.
     */
    public function migrate($type = "run")
    {
        
        foreach (glob(__DIR__ . '/../../database/migrations/*.php') as $migrationFile) {
            $migrationInstance = require $migrationFile;

            try {
                if (is_object($migrationInstance) && $migrationInstance instanceof MigrationInterface) {
                    $migrationName = basename($migrationFile, '.php');

                    switch ($type) {
                        case "run":
                            $this->handleRunMigration($migrationInstance, $migrationName);
                            break;

                        case "rollback":
                            $this->handleRollbackMigration($migrationInstance, $migrationName);
                            break;

                        case "refresh":
                            $this->handleRefreshMigration($migrationInstance, $migrationName);
                            break;

                        case "fresh":
                            $this->handleFreshMigration($migrationInstance, $migrationName);
                            break;

                        case "status":
                            $this->showMigrationStatus();
                            break;

                        default:
                            $this->logger->log("error", "Commande non reconnue : $type");
                            break;
                    }

                    $this->logger->log("success", "Migration terminée : " . $this->logger->bold($migrationName));
                } else {
                    $this->logger->log("error", "Migration non valide ou non trouvée dans $migrationFile.");
                }
            } catch (\Exception $e) {
                $this->logger->log("error", "Erreur lors de l'exécution de la migration $migrationFile : " . $e->getMessage());
            }
        }
    }
}
