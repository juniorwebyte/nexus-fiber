<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static $pdo;

    public static function connect()
    {
        if (!self::$pdo) {
            try {
                self::$pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                // Para produção, log o erro. Aqui exibimos para debug inicial.
                die("Erro de conexão: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
