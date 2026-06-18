<?php
namespace App\Controllers;

use App\Core\Database;

class EstoqueController
{
    public function __construct()
    {
        AuthController::requireLogin();
    }

    public function index()
    {
        $db = Database::connect();
        $cargo = $_SESSION['cargo'] ?? '';
        $isManager = in_array($cargo, ['Admin', 'Gerente Regional', 'Gerente Local', 'Gestor', 'Coordenador', 'Supervisor']);
        
        $myStock = [];
        $stmtMy = $db->prepare("SELECT m.nome, m.unidade, u.quantidade FROM user_stock u JOIN materials m ON u.material_id = m.id WHERE u.user_id = ?");
        $stmtMy->execute([$_SESSION['user_id']]);
        $myStock = $stmtMy->fetchAll();

        $materiais = [];
        $tecnicos = [];
        if ($isManager) {
            $stmtM = $db->query("SELECT * FROM materials ORDER BY nome ASC");
            $materiais = $stmtM->fetchAll();

            $stmtTec = $db->query("SELECT id, nome FROM users WHERE cargo NOT IN ('Admin', 'Gerente Regional', 'Gerente Local', 'Gestor')");
            $tecnicos = $stmtTec->fetchAll();
        }

        $activeMenu = 'dashboard';
        $viewPath = __DIR__ . '/../Views/estoque/index.php';
        require_once __DIR__ . '/../Views/layout.php';
    }

    public function transfer()
    {
        $cargo = $_SESSION['cargo'] ?? '';
        $isManager = in_array($cargo, ['Admin', 'Gerente Regional', 'Gerente Local', 'Gestor', 'Coordenador', 'Supervisor']);
        if (!$isManager) die("Acesso Negado.");

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tecnico_id = $_POST['tecnico_id'] ?? null;
            $material_id = $_POST['material_id'] ?? null;
            $quantidade = $_POST['quantidade'] ?? 0;

            if ($tecnico_id && $material_id && $quantidade > 0) {
                $db = Database::connect();
                
                // Add to user stock
                $stmtCheck = $db->prepare("SELECT id FROM user_stock WHERE user_id = ? AND material_id = ?");
                $stmtCheck->execute([$tecnico_id, $material_id]);
                if ($stmtCheck->rowCount() > 0) {
                    $stmtUpdate = $db->prepare("UPDATE user_stock SET quantidade = quantidade + ? WHERE user_id = ? AND material_id = ?");
                    $stmtUpdate->execute([$quantidade, $tecnico_id, $material_id]);
                } else {
                    $stmtInsert = $db->prepare("INSERT INTO user_stock (user_id, material_id, quantidade) VALUES (?, ?, ?)");
                    $stmtInsert->execute([$tecnico_id, $material_id, $quantidade]);
                }

                // Log movement
                $stmtLog = $db->prepare("INSERT INTO stock_movements (user_id, material_id, quantidade, tipo, descricao) VALUES (?, ?, ?, 'entrada', 'Transferência do Almoxarifado')");
                $stmtLog->execute([$tecnico_id, $material_id, $quantidade]);
            }
            header("Location: " . BASE_URL . "/estoque?success=1");
            exit;
        }
    }
}
