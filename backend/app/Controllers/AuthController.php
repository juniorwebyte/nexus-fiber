<?php
namespace App\Controllers;

use App\Models\User;

class AuthController
{
    public function login()
    {
        // Se já estiver logado
        if (isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $re = $_POST['re_matricula'] ?? '';
            $senha = $_POST['senha'] ?? '';

            $user = User::findByRE($re);

            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nome'] = $user['nome'];
                $_SESSION['cargo'] = $user['cargo'];
                header("Location: " . BASE_URL . "/");
                exit;
            } else {
                $error = "RE ou Senha inválidos.";
            }
        }

        $viewPath = __DIR__ . '/../Views/auth/login.php';
        require_once __DIR__ . '/../Views/layout_clean.php';
    }

    public function logout()
    {
        session_destroy();
        header("Location: " . BASE_URL . "/login");
        exit;
    }
    
    // Middleware-like function for other controllers
    public static function requireLogin()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/login");
            exit;
        }
    }
}
