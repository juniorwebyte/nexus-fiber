<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function findByRE($re_matricula)
    {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM users WHERE re_matricula = :re");
        $stmt->execute(['re' => $re_matricula]);
        return $stmt->fetch();
    }
    
    public static function getRankings()
    {
        $db = Database::connect();
        $stmt = $db->query("SELECT nome, cargo, pontuacao FROM users ORDER BY pontuacao DESC LIMIT 10");
        return $stmt->fetchAll();
    }
}
