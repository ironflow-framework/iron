<?php

namespace Forge\Database;

use PDO;
use PDOException;
use TinyForge\Common\Config;

class Database
{
    private static $instance = null;
    private $connection;

    // Types de bases de données supportés
    private const DB_DRIVERS = [
        'mysql' => 'mysql',
        'pgsql' => 'pgsql',
        'sqlite' => 'sqlite'
    ];

    private function __construct()
    {
        // Charger les variables d'environnement
        // Récupérer les valeurs de configuration
        $driver = Config::get('DB_DRIVER', 'mysql');
        $host = Config::get('DB_HOST');
        $name = Config::get('DB_NAME');
        $user = Config::get('DB_USER');
        $pass = Config::get('DB_PASS');
        $charset = 'utf8';

        // Vérification des variables essentielles
        if (empty($name) || empty($user) || empty($pass)) {
            throw new PDOException("Les variables d'environnement DB_NAME, DB_USER, et DB_PASS sont obligatoires.");
        }

        $charset = 'utf8';

        // Connexion en fonction du type de base de données
        try {
            switch ($driver) {
                case self::DB_DRIVERS['mysql']:
                    $this->connection = new PDO(
                        "mysql:host=$host;dbname=$name;charset=$charset",
                        $user,
                        $pass
                    );
                    break;

                case self::DB_DRIVERS['pgsql']:
                    $this->connection = new PDO(
                        "pgsql:host=$host;dbname=$name",
                        $user,
                        $pass
                    );
                    break;

                case self::DB_DRIVERS['sqlite']:
                    $sqlitePath = $_ENV['DB_SQLITE_PATH'] ?? __DIR__ . '/database.sqlite';
                    $this->connection = new PDO("sqlite:$sqlitePath");
                    break;

                default:
                    throw new PDOException("Driver de base de données non supporté.");
            }

            // Définir les attributs PDO
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    // Méthode pour récupérer l'instance de la base de données
    public static function getInstance(): mixed
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // Méthode pour récupérer la connexion PDO
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function closeConnection(): void
    {
        $this->connection = null;
        self::$instance = null;
    }

}
