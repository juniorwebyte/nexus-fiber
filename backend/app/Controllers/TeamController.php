<?php
namespace App\Controllers;

use App\Core\Database;
use App\Models\User;

class TeamController
{
    private $allowedRoles = ['Admin', 'Gerente Regional', 'Gerente Local', 'Gestor', 'Coordenador', 'Supervisor'];

    public function __construct()
    {
        AuthController::requireLogin();
        
        $cargo = $_SESSION['cargo'] ?? '';
        if (!in_array($cargo, $this->allowedRoles)) {
            die("Acesso Negado: Apenas gestores podem gerenciar a equipe.");
        }
    }

    public function index()
    {
        $db = Database::connect();
        $stmt = $db->query("SELECT * FROM users ORDER BY pontuacao DESC");
        $users = $stmt->fetchAll();
        
        $activeMenu = 'dashboard';
        $viewPath = __DIR__ . '/../Views/team/index.php';
        require_once __DIR__ . '/../Views/layout.php';
    }

    public function create()
    {
        $activeMenu = 'dashboard';
        $user = null;
        $viewPath = __DIR__ . '/../Views/team/form.php';
        require_once __DIR__ . '/../Views/layout.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = $_POST['nome'] ?? '';
            $re_matricula = $_POST['re_matricula'] ?? '';
            $cargo = $_POST['cargo'] ?? '';
            $senhaRaw = $_POST['senha'] ?? '';
            
            if ($nome && $re_matricula && $cargo && $senhaRaw) {
                $senhaHash = password_hash($senhaRaw, PASSWORD_DEFAULT);
                $db = Database::connect();
                $stmt = $db->prepare("INSERT INTO users (nome, re_matricula, cargo, senha) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $re_matricula, $cargo, $senhaHash]);
            }
            header("Location: " . BASE_URL . "/equipe");
            exit;
        }
    }

    public function edit()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "/equipe");
            exit;
        }
        
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        $activeMenu = 'dashboard';
        $viewPath = __DIR__ . '/../Views/team/form.php';
        require_once __DIR__ . '/../Views/layout.php';
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $nome = $_POST['nome'] ?? '';
            $re_matricula = $_POST['re_matricula'] ?? '';
            $cargo = $_POST['cargo'] ?? '';
            $senhaRaw = $_POST['senha'] ?? '';
            
            if ($id && $nome && $re_matricula && $cargo) {
                $db = Database::connect();
                if (!empty($senhaRaw)) {
                    $senhaHash = password_hash($senhaRaw, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET nome=?, re_matricula=?, cargo=?, senha=? WHERE id=?");
                    $stmt->execute([$nome, $re_matricula, $cargo, $senhaHash, $id]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET nome=?, re_matricula=?, cargo=? WHERE id=?");
                    $stmt->execute([$nome, $re_matricula, $cargo, $id]);
                }
            }
            header("Location: " . BASE_URL . "/equipe");
            exit;
        }
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                $db = Database::connect();
                $stmt = $db->prepare("DELETE FROM users WHERE id=?");
                $stmt->execute([$id]);
            }
            header("Location: " . BASE_URL . "/equipe");
            exit;
        }
    }
}
