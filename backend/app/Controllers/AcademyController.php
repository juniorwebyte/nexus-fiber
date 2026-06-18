<?php
namespace App\Controllers;

use App\Controllers\AuthController;
use App\Core\Database;
use App\Models\User;

class AcademyController
{
    public function __construct()
    {
        AuthController::requireLogin();
    }

    public function index()
    {
        $activeMenu = 'academia';
        $viewPath = __DIR__ . '/../Views/academy/index.php';
        
        $db = Database::connect();
        $cargo = $_SESSION['cargo'];
        
        // Buscar progresso
        $stmt = $db->prepare("SELECT SUM(pontos_ganhos) as total_pontos FROM user_progress WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $progresso = $stmt->fetch();
        $pontos = $progresso['total_pontos'] ?? 0;
        
        // Ranking
        $rankings = User::getRankings();

        require_once __DIR__ . '/../Views/layout.php';
    }

    public function quiz()
    {
        $activeMenu = 'academia';
        $viewPath = __DIR__ . '/../Views/academy/quiz.php';
        
        $db = Database::connect();
        
        // Se responder ao formulário
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $question_id = $_POST['question_id'] ?? 0;
            $resposta = strtolower($_POST['resposta'] ?? '');
            
            $stmt = $db->prepare("SELECT * FROM quiz_questions WHERE id = :id");
            $stmt->execute(['id' => $question_id]);
            $question = $stmt->fetch();
            
            if ($question) {
                if ($question['resposta_correta'] === $resposta) {
                    // Acertou
                    $stmt = $db->prepare("INSERT INTO user_progress (user_id, tipo_atividade, referencia_id, pontos_ganhos) VALUES (:user_id, 'quiz', :ref, :pontos)");
                    $stmt->execute([
                        'user_id' => $_SESSION['user_id'],
                        'ref' => 'Q'.$question['id'],
                        'pontos' => $question['pontos']
                    ]);
                    
                    // Atualiza pontos do usuário
                    $stmt = $db->prepare("UPDATE users SET pontuacao = pontuacao + :pontos WHERE id = :user_id");
                    $stmt->execute(['pontos' => $question['pontos'], 'user_id' => $_SESSION['user_id']]);
                    
                    $mensagem = "Resposta correta! Você ganhou {$question['pontos']} pontos.";
                    $sucesso = true;
                } else {
                    $mensagem = "Resposta incorreta. A alternativa correta era: " . strtoupper($question['resposta_correta']);
                    $sucesso = false;
                }
            }
        }
        
        // Puxar pergunta aleatória do cargo do usuário
        $stmt = $db->prepare("SELECT * FROM quiz_questions WHERE cargo_alvo = :cargo ORDER BY RAND() LIMIT 1");
        $stmt->execute(['cargo' => $_SESSION['cargo']]);
        $question = $stmt->fetch();

        require_once __DIR__ . '/../Views/layout.php';
    }
}
