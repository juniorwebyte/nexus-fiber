<?php
namespace App\Controllers;

use App\Core\Database;
use App\Models\User;

class DashboardController
{
    public function index()
    {
        $activeMenu = 'dashboard';
        $cargo = $_SESSION['cargo'] ?? 'Auxiliar';
        
        $isManager = in_array($cargo, ['Admin', 'Gerente Regional', 'Gerente Local', 'Gestor', 'Coordenador', 'Supervisor']);
        
        $stats = [];
        if ($isManager) {
            $db = Database::connect();
            // Total de Usuários
            $stmt = $db->query("SELECT COUNT(*) as total FROM users");
            $stats['total_users'] = $stmt->fetch()['total'];
            
            // Total de Provas Realizadas
            $stmt = $db->query("SELECT COUNT(*) as total FROM user_progress WHERE tipo_atividade = 'quiz'");
            $stats['total_quizzes'] = $stmt->fetch()['total'];
            
            // Top 3 Técnicos
            $stmt = $db->query("SELECT nome, pontuacao FROM users ORDER BY pontuacao DESC LIMIT 3");
            $stats['top_users'] = $stmt->fetchAll();
        }
        
        $viewPath = __DIR__ . '/../Views/dashboard/index.php';
        require_once __DIR__ . '/../Views/layout.php';
    }
}
