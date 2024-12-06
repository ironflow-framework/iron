<?php

namespace Forge\Database\Iron;

use Forge\Database\Database;
use PDO;

class Schema
{
    public static function create($tableName, callable $callback): void
    {
        $anvil = new Anvil($tableName);
        $callback($anvil);
        $sql = $anvil->getCreateTableSQL();
        Database::getInstance()->getConnection()->exec($sql);
    }   

    public static function alter($tableName, callable $callback): void
    {
        $anvil = new Anvil($tableName);
        $callback($anvil);
        $sql = $anvil->getAlterTableSQL();
        Database::getInstance()->getConnection()->exec($sql);
    }

    public static function getColumnListing($table)
    {
        $pdo = Database::getInstance()->getConnection();  // Supposons que vous ayez une méthode pour obtenir une instance PDO
        $query = "DESCRIBE $table";  // Commande SQL pour obtenir les informations des colonnes

        $stmt = $pdo->query($query);
        $columns = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];  // Récupérer le nom de chaque colonne
        }

        return $columns;
    }

    /**
     * 
     * Config PHPAuth
     * 
     */
    public static function configAuth()
    {
        $sql = "INSERT INTO `phpauth_config` (`setting`, `value`) 
                VALUES  ('attack_mitigation_time',  '+30 minutes'),
                        ('attempts_before_ban', '30'),
                        ('attempts_before_verify',  '5'),
                        ('bcrypt_cost', '10'),
                        ('cookie_domain', NULL),
                        ('cookie_forget', '+30 minutes'),
                        ('cookie_http', '1'),
                        ('cookie_name', '{$_ENV['APP_NAME']}_session_cookie'),
                        ('cookie_path', '/'),
                        ('cookie_remember', '+1 month'),
                        ('cookie_samesite', 'Strict'),
                        ('cookie_secure', '1'),
                        ('cookie_renew', '+5 minutes'),
                        ('allow_concurrent_sessions', FALSE),
                        ('emailmessage_suppress_activation',  '0'),
                        ('emailmessage_suppress_reset', '0'),
                        ('mail_charset','UTF-8'),
                        ('password_min_score',  '3'),
                        ('site_activation_page',  'activate'),
                        ('site_activation_page_append_code', '0'), 
                        ('site_email',  '{$_ENV['MAIL_USERNAME']}'),
                        ('site_key',  '{$_ENV['APP_KEY']}'),
                        ('site_name', '{$_ENV['APP_NAME']}'),
                        ('site_password_reset_page',  'reset'),
                        ('site_password_reset_page_append_code',  '0'),
                        ('site_timezone', '{$_ENV['APP_TZ']}'),
                        ('site_url',  '{$_ENV['APP_URL']}'),
                        ('site_language', '{$_ENV['APP_LANG']}'),
                        ('smtp',  '0'),
                        ('smtp_debug',  '0'),
                        ('smtp_auth', '1'),
                        ('smtp_host', '{$_ENV['MAIL_HOST']}'),
                        ('smtp_password', '{$_ENV['MAIL_PASSWORD']}'),
                        ('smtp_port', '{$_ENV['MAIL_PORT']}'),
                        ('smtp_security', NULL),
                        ('smtp_username', '{$_ENV['MAIL_USERNAME']}'),
                        ('table_attempts',  'attempts'),
                        ('table_requests',  'requests'),
                        ('table_sessions',  'sessions'),
                        ('table_users', 'users'),
                        ('table_emails_banned', 'emails_banned'),
                        ('table_translations', 'translation_dictionary'),
                        ('verify_email_max_length', '100'),
                        ('verify_email_min_length', '5'),
                        ('verify_email_use_banlist',  '1'),
                        ('verify_password_min_length',  '3'),
                        ('request_key_expiration', '+10 minutes'),
                        ('translation_source', 'php'),
                        ('recaptcha_enabled', 0),
                        ('recaptcha_site_key', ''),
                        ('recaptcha_secret_key', ''),
                        ('custom_datetime_format', 'Y-m-d H:i'),
                        ('uses_session', 0);";

        Database::getInstance()->getConnection()->exec($sql);
    }


    public static function dropIfExists($tableName): void
    {
        $sql = "DROP TABLE IF EXISTS {$tableName};";
        Database::getInstance()->getConnection()->exec($sql);
    }
}
