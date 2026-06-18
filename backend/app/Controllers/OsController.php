<?php
namespace App\Controllers;

use App\Core\Database;
use App\Models\User;

class OsController
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

        if ($isManager) {
            // Gestores veem todas as O.S.
            $stmt = $db->query("SELECT w.*, u.nome as tecnico_nome FROM work_orders w LEFT JOIN users u ON w.tecnico_id = u.id ORDER BY w.id DESC");
        }
        $orders = $stmt->fetchAll();

        // Se for técnico, filtra pela fila baseada em Multi-Skill ou Cabista
        // Mas o app atual permite ver 'tecnico_id' específico. Vamos manter a lógica do tecnico_id.
        if (!$isManager) {
            $stmt = $db->prepare("SELECT w.*, u.nome as tecnico_nome FROM work_orders w LEFT JOIN users u ON w.tecnico_id = u.id WHERE w.tecnico_id = ? ORDER BY w.status DESC, w.id DESC");
            $stmt->execute([$_SESSION['user_id']]);
            $orders = $stmt->fetchAll();
        }
        
        // Puxar lista de técnicos para o formulário de nova O.S (só útil para o gestor)
        $tecnicos = [];
        if ($isManager) {
            $stmtTec = $db->query("SELECT id, nome FROM users WHERE cargo NOT IN ('Admin', 'Gerente Regional', 'Gerente Local', 'Gestor')");
            $tecnicos = $stmtTec->fetchAll();
        }

        $activeMenu = 'dashboard';
        $viewPath = __DIR__ . '/../Views/os/index.php';
        require_once __DIR__ . '/../Views/layout.php';
    }

    public function store()
    {
        $cargo = $_SESSION['cargo'] ?? '';
        $isManager = in_array($cargo, ['Admin', 'Gerente Regional', 'Gerente Local', 'Gestor', 'Coordenador', 'Supervisor']);
        if (!$isManager) die("Acesso Negado.");

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titulo = $_POST['titulo'] ?? '';
            $descricao = $_POST['descricao'] ?? '';
            $tecnico_id = $_POST['tecnico_id'] ?? null;
            $pontos = $_POST['pontos_recompensa'] ?? 50;
            $tipo_os = $_POST['tipo_os'] ?? 'instalacao';

            if ($titulo) {
                $db = Database::connect();
                $stmt = $db->prepare("INSERT INTO work_orders (titulo, descricao, tecnico_id, pontos_recompensa, tipo_os) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$titulo, $descricao, $tecnico_id, $pontos, $tipo_os]);
            }
            header("Location: " . BASE_URL . "/os");
            exit;
        }
    }

    public function checkout()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "/os");
            exit;
        }
        
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM work_orders WHERE id = ?");
        $stmt->execute([$id]);
        $os = $stmt->fetch();

        // Segurança: Apenas o técnico dono da O.S (ou gestor) pode dar checkout
        if ($os['tecnico_id'] != $_SESSION['user_id'] && !in_array($_SESSION['cargo'], ['Admin', 'Gestor'])) {
            die("Acesso Negado.");
        }

        // Puxar estoque atual do técnico
        $stmtStock = $db->prepare("SELECT u.id as user_stock_id, m.id as material_id, m.nome, m.unidade, u.quantidade FROM user_stock u JOIN materials m ON u.material_id = m.id WHERE u.user_id = ? AND u.quantidade > 0");
        $stmtStock->execute([$os['tecnico_id']]);
        $myStock = $stmtStock->fetchAll();

        $activeMenu = 'dashboard';
        $viewPath = __DIR__ . '/../Views/os/checkout.php';
        require_once __DIR__ . '/../Views/layout.php';
    }

    public function complete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            if (!$id) die("O.S inválida.");

            $db = Database::connect();
            $stmt = $db->prepare("SELECT * FROM work_orders WHERE id = ? AND status = 'pendente'");
            $stmt->execute([$id]);
            $os = $stmt->fetch();

            if ($os && isset($_FILES['foto'])) {
                $file = $_FILES['foto'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    $uploadDir = __DIR__ . '/../../../uploads/os/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    
                    $filename = uniqid('os_') . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        
                        // Processar desconto de materiais do estoque
                        $materiais_usados = $_POST['materiais'] ?? []; // Array [material_id => quantidade]
                        $texto_materiais = "";
                        foreach ($materiais_usados as $mat_id => $qtd) {
                            if ($qtd > 0) {
                                // Subtrai do user_stock
                                $stmtSub = $db->prepare("UPDATE user_stock SET quantidade = quantidade - ? WHERE user_id = ? AND material_id = ? AND quantidade >= ?");
                                $stmtSub->execute([$qtd, $os['tecnico_id'], $mat_id, $qtd]);
                                
                                // Registra movimento
                                $stmtMov = $db->prepare("INSERT INTO stock_movements (user_id, material_id, quantidade, tipo, descricao) VALUES (?, ?, ?, 'saida', ?)");
                                $stmtMov->execute([$os['tecnico_id'], $mat_id, $qtd, "Uso na OS #" . $id]);
                                
                                $texto_materiais .= "Material ID $mat_id: $qtd unid. | ";
                            }
                        }

                        // Capturar dados do Croqui e Assinatura
                        $croqui_porta = $_POST['croqui_porta_cto'] ?? null;
                        $croqui_sinal = $_POST['croqui_sinal_dbm'] ?? null;
                        $croqui_mac = $_POST['croqui_mac'] ?? null;
                        $croqui_obs = $_POST['croqui_obs'] ?? null;
                        
                        $assinatura_base64 = $_POST['assinatura_base64'] ?? '';
                        $assinatura_filename = null;
                        if (!empty($assinatura_base64) && strpos($assinatura_base64, 'data:image') === 0) {
                            $parts = explode(',', $assinatura_base64);
                            $data = base64_decode($parts[1]);
                            $assinatura_filename = uniqid('assinatura_') . '.png';
                            file_put_contents($uploadDir . $assinatura_filename, $data);
                        }

                        // Lógica de SLA
                        $xp_bonus_sla = 0;
                        if (!empty($os['prazo_limite'])) {
                            if (time() <= strtotime($os['prazo_limite'])) {
                                $xp_bonus_sla = 15; // 15 XP extras por entregar no prazo
                            }
                        }

                        $pontos_totais = $os['pontos_recompensa'] + $xp_bonus_sla;

                        $rede_causa = $_POST['rede_causa_rompimento'] ?? null;
                        $rede_atenuacao = $_POST['rede_atenuacao_fusao'] ?? null;

                        // O.S concluída
                        $stmtUpdate = $db->prepare("UPDATE work_orders SET status = 'concluida', foto_url = ?, concluido_em = NOW(), descricao = CONCAT(descricao, '\n\nBaixa Estoque: ', ?), croqui_porta_cto = ?, croqui_sinal_dbm = ?, croqui_mac = ?, croqui_obs = ?, assinatura_url = ?, rede_causa_rompimento = ?, rede_atenuacao_fusao = ? WHERE id = ?");
                        $stmtUpdate->execute([$filename, $texto_materiais, $croqui_porta, $croqui_sinal, $croqui_mac, $croqui_obs, $assinatura_filename, $rede_causa, $rede_atenuacao, $id]);
                        
                        // Dar pontos de recompensa ao técnico na Gamificação (inclui bônus de SLA)
                        $stmtPts = $db->prepare("UPDATE users SET pontuacao = pontuacao + ? WHERE id = ?");
                        $stmtPts->execute([$pontos_totais, $os['tecnico_id']]);

                        header("Location: " . BASE_URL . "/os?success=1");
                        exit;
                    }
                }
            }
            header("Location: " . BASE_URL . "/os/checkout?id=$id&error=1");
            exit;
        }
    }
}
