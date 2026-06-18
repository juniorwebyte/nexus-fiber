<?php
namespace App\Controllers;

use App\Core\Database;

class PerigoController
{
    public function __construct()
    {
        AuthController::requireLogin();
    }

    public function index()
    {
        $db = Database::connect();
        $isManager = in_array($_SESSION['user_role'], ['admin', 'manager']);

        $where = "";
        $params = [];
        if (!$isManager) {
            $where = "WHERE criado_por = ?";
            $params[] = $_SESSION['user_id'];
        }

        $stmt = $db->prepare("SELECT * FROM perigo_records $where ORDER BY id DESC");
        $stmt->execute($params);
        $relatos = $stmt->fetchAll();

        require_once __DIR__ . '/../Views/layout/header.php';
        require_once __DIR__ . '/../Views/perigo/index.php';
        require_once __DIR__ . '/../Views/layout/footer.php';
    }

    public function create()
    {
        require_once __DIR__ . '/../Views/layout/header.php';
        require_once __DIR__ . '/../Views/perigo/create.php';
        require_once __DIR__ . '/../Views/layout/footer.php';
    }

    public function store()
    {
        $db = Database::connect();

        $celular_corporativo = $_POST['celular_corporativo'] ?? null;
        $contrato = $_POST['contrato'] ?? null;
        $setor = $_POST['setor'] ?? null;
        $data_relato = $_POST['data_relato'] ?? null;
        $uf = $_POST['uf'] ?? 'SP';
        $cidade = $_POST['cidade'] ?? null;
        $endereco = $_POST['endereco'] ?? null;
        $os_id = $_POST['os_id'] ?? null;
        $descricao_ocorrido = $_POST['descricao_ocorrido'] ?? null;
        
        $stop_work = isset($_POST['stop_work']) && $_POST['stop_work'] === 'sim' ? 1 : 0;
        
        $categoria_risco = $_POST['categoria_risco'] ?? null;
        $anjo_da_guarda = $_POST['anjo_da_guarda'] ?? null;
        $acoes_equipe = $_POST['acoes_equipe'] ?? null;
        $gestor_avisado = $_POST['gestor_avisado'] ?? null;

        $stmt = $db->prepare("
            INSERT INTO perigo_records (
                celular_corporativo, contrato, setor, data_relato, uf, cidade, endereco,
                os_id, descricao_ocorrido, stop_work, categoria_risco, anjo_da_guarda,
                acoes_equipe, gestor_avisado, criado_por
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?
            )
        ");

        $stmt->execute([
            $celular_corporativo, $contrato, $setor, $data_relato, $uf, $cidade, $endereco,
            $os_id, $descricao_ocorrido, $stop_work, $categoria_risco, $anjo_da_guarda,
            $acoes_equipe, $gestor_avisado, $_SESSION['user_id']
        ]);

        header("Location: " . BASE_URL . "/perigo?success=1");
        exit;
    }

    public function view($id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM perigo_records WHERE id = ?");
        $stmt->execute([$id]);
        $relato = $stmt->fetch();

        require_once __DIR__ . '/../Views/layout/header.php';
        require_once __DIR__ . '/../Views/perigo/view.php';
        require_once __DIR__ . '/../Views/layout/footer.php';
    }
}
